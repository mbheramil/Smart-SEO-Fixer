<?php
/**
 * Background Job Queue
 * 
 * Processes long-running operations (bulk AI fix, bulk schema, etc.)
 * in small batches via WordPress cron to prevent timeouts.
 * 
 * Architecture:
 * - Jobs are stored in a DB table with status tracking
 * - Each job has a type, payload (serialized args), and item list
 * - A cron event processes pending jobs in small batches (5 items per tick)
 * - The admin UI polls for progress via AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Job_Queue {
    
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_FAILED     = 'failed';
    const STATUS_CANCELLED  = 'cancelled';
    
    const BATCH_SIZE = 5;
    const CRON_HOOK  = 'ssf_process_job_queue';
    
    /**
     * Get the jobs table name
     */
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'ssf_jobs';
    }
    
    /**
     * Create the jobs table
     */
    public static function create_table() {
        global $wpdb;
        
        $table = self::table();
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            job_type varchar(50) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            total_items int(11) NOT NULL DEFAULT 0,
            processed_items int(11) NOT NULL DEFAULT 0,
            failed_items int(11) NOT NULL DEFAULT 0,
            payload longtext,
            items longtext,
            results longtext,
            error_message text,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY job_type (job_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Schedule the cron processor
     */
    public static function schedule_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'ssf_every_minute', self::CRON_HOOK);
        }
    }
    
    /**
     * Unschedule the cron processor
     */
    public static function unschedule_cron() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }
    
    /**
     * Register custom cron interval (every minute)
     */
    public static function add_cron_interval($schedules) {
        $schedules['ssf_every_minute'] = [
            'interval' => 60,
            'display'  => __('Every Minute (Smart SEO Fixer)', 'smart-seo-fixer'),
        ];
        return $schedules;
    }
    
    /**
     * Create a new background job
     * 
     * @param string $job_type  'bulk_ai_fix', 'bulk_schema', 'bulk_alt_text', 'orphan_fix_batch'
     * @param array  $items     Array of post IDs or item identifiers to process
     * @param array  $payload   Job configuration (options, settings, etc.)
     * @return int|WP_Error     Job ID or error
     */
    public static function create($job_type, $items, $payload = []) {
        global $wpdb;
        
        if (empty($items)) {
            return new WP_Error('empty_items', __('No items to process.', 'smart-seo-fixer'));
        }
        
        $result = $wpdb->insert(
            self::table(),
            [
                'job_type'        => sanitize_key($job_type),
                'status'          => self::STATUS_PENDING,
                'total_items'     => count($items),
                'processed_items' => 0,
                'failed_items'    => 0,
                'payload'         => wp_json_encode($payload),
                'items'           => wp_json_encode($items),
                'results'         => wp_json_encode([]),
                'user_id'         => get_current_user_id(),
                'created_at'      => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s']
        );
        
        if (!$result) {
            return new WP_Error('db_error', __('Failed to create job.', 'smart-seo-fixer'));
        }
        
        $job_id = $wpdb->insert_id;
        
        if (class_exists('SSF_Logger')) {
            SSF_Logger::info(sprintf('Job #%d created: %s (%d items)', $job_id, $job_type, count($items)), 'queue');
        }
        
        // Ensure cron is scheduled
        self::schedule_cron();
        
        return $job_id;
    }
    
    /**
     * Get a job by ID
     */
    public static function get($job_id) {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM " . self::table() . " WHERE id = %d", $job_id)
        );
        
        if ($row) {
            $row->payload = json_decode($row->payload, true) ?: [];
            $row->items   = json_decode($row->items, true) ?: [];
            $row->results = json_decode($row->results, true) ?: [];
        }
        
        return $row;
    }
    
    /**
     * Process the next batch of pending/processing jobs
     * Called by WordPress cron every minute
     */
    public static function process_queue() {
        global $wpdb;
        $table = self::table();
        
        // Find the next job to process (oldest pending or in-progress)
        $job = $wpdb->get_row(
            "SELECT * FROM $table 
             WHERE status IN ('pending', 'processing') 
             ORDER BY created_at ASC 
             LIMIT 1"
        );
        
        if (!$job) {
            return;
        }
        
        $job->payload = json_decode($job->payload, true) ?: [];
        $job->items   = json_decode($job->items, true) ?: [];
        $job->results = json_decode($job->results, true) ?: [];
        
        // Mark as processing if pending
        if ($job->status === self::STATUS_PENDING) {
            $wpdb->update(
                $table,
                ['status' => self::STATUS_PROCESSING, 'started_at' => current_time('mysql')],
                ['id' => $job->id],
                ['%s', '%s'],
                ['%d']
            );
        }
        
        // Set history source for tracking
        if (class_exists('SSF_History')) {
            SSF_History::set_source('bulk');
        }
        
        // Determine which items still need processing
        $processed_count = intval($job->processed_items);
        $remaining_items = array_slice($job->items, $processed_count, self::BATCH_SIZE);
        
        if (empty($remaining_items)) {
            self::mark_completed($job->id);
            return;
        }
        
        $batch_results = [];
        $batch_failed = 0;
        
        foreach ($remaining_items as $item_id) {
            $result = self::process_single_item($job->job_type, $item_id, $job->payload);
            
            if (is_wp_error($result)) {
                $batch_results[] = [
                    'item_id' => $item_id,
                    'status'  => 'failed',
                    'message' => $result->get_error_message(),
                ];
                $batch_failed++;
            } else {
                $batch_results[] = [
                    'item_id' => $item_id,
                    'status'  => 'success',
                    'message' => $result,
                ];
            }
        }
        
        // Merge results and update progress
        $all_results = array_merge($job->results, $batch_results);
        $new_processed = $processed_count + count($remaining_items);
        $new_failed = intval($job->failed_items) + $batch_failed;
        
        $update_data = [
            'processed_items' => $new_processed,
            'failed_items'    => $new_failed,
            'results'         => wp_json_encode($all_results),
        ];
        
        // Check if we're done
        if ($new_processed >= intval($job->total_items)) {
            $update_data['status'] = self::STATUS_COMPLETED;
            $update_data['completed_at'] = current_time('mysql');
            
            if (class_exists('SSF_Logger')) {
                SSF_Logger::info(sprintf(
                    'Job #%d completed: %d processed, %d failed',
                    $job->id, $new_processed, $new_failed
                ), 'queue');
            }
        }
        
        $wpdb->update($table, $update_data, ['id' => $job->id]);
    }
    
    /**
     * Process a single item based on job type
     * 
     * @param string $job_type The type of job
     * @param int    $item_id  The post/item ID
     * @param array  $payload  Job configuration
     * @return string|WP_Error Success message or error
     */
    private static function process_single_item($job_type, $item_id, $payload) {
        switch ($job_type) {
            case 'bulk_ai_fix':
                return self::process_ai_fix($item_id, $payload);
                
            case 'bulk_schema':
                return self::process_schema_regen($item_id, $payload);
                
            case 'orphan_fix_batch':
                return self::process_orphan_fix($item_id, $payload);
                
            default:
                return new WP_Error('unknown_type', sprintf('Unknown job type: %s', $job_type));
        }
    }
    
    /**
     * Process a single AI fix item
     */
    private static function process_ai_fix($post_id, $payload) {
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post not found');
        }
        
        $openai = new SSF_OpenAI();
        if (!$openai->is_configured()) {
            return new WP_Error('no_api_key', 'OpenAI not configured');
        }
        
        $clean_content = wp_strip_all_tags(strip_shortcodes($post->post_content));
        if (str_word_count($clean_content) < 10) {
            return 'Skipped (content too short)';
        }
        
        $generate_title    = !empty($payload['generate_title']);
        $generate_desc     = !empty($payload['generate_desc']);
        $generate_keywords = !empty($payload['generate_keywords']);
        $apply_to          = $payload['apply_to'] ?? 'missing';
        $overwrite         = ($apply_to === 'all');
        
        $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
        $generated = [];
        
        // Keywords first
        if ($generate_keywords) {
            $current_kw = trim(get_post_meta($post_id, '_ssf_focus_keyword', true));
            if ($overwrite || empty($current_kw)) {
                $keywords = $openai->suggest_keywords($post->post_content, $post->post_title);
                if (!is_wp_error($keywords) && !empty($keywords['primary'])) {
                    update_post_meta($post_id, '_ssf_focus_keyword', sanitize_text_field($keywords['primary']));
                    $focus_keyword = $keywords['primary'];
                    $generated[] = 'keyword';
                } elseif (is_wp_error($keywords)) {
                    return $keywords;
                }
            }
        }
        
        // Title
        if ($generate_title) {
            $current_title = trim(get_post_meta($post_id, '_ssf_seo_title', true));
            if ($overwrite || empty($current_title)) {
                $title = $openai->generate_title($post->post_content, $post->post_title, $focus_keyword);
                if (!is_wp_error($title) && !empty(trim($title))) {
                    update_post_meta($post_id, '_ssf_seo_title', sanitize_text_field(trim($title)));
                    $generated[] = 'title';
                } elseif (is_wp_error($title)) {
                    return $title;
                }
            }
        }
        
        // Description
        if ($generate_desc) {
            $current_desc = trim(get_post_meta($post_id, '_ssf_meta_description', true));
            if ($overwrite || empty($current_desc)) {
                $desc = $openai->generate_meta_description($post->post_content, '', $focus_keyword);
                if (!is_wp_error($desc) && !empty(trim($desc))) {
                    update_post_meta($post_id, '_ssf_meta_description', sanitize_textarea_field(trim($desc)));
                    $generated[] = 'description';
                } elseif (is_wp_error($desc)) {
                    return $desc;
                }
            }
        }
        
        // Re-analyze
        if (class_exists('SSF_Analyzer')) {
            $analyzer = new SSF_Analyzer();
            $analyzer->analyze_post($post_id);
        }
        
        if (!empty($generated)) {
            return sprintf('Generated: %s', implode(', ', $generated));
        }
        
        return 'Skipped (already has SEO data)';
    }
    
    /**
     * Process a single schema regeneration
     */
    private static function process_schema_regen($post_id, $payload) {
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post not found');
        }
        
        if (!class_exists('SSF_Schema')) {
            return new WP_Error('no_schema', 'Schema module not available');
        }
        
        $schema = new SSF_Schema();
        if (method_exists($schema, 'generate_schema')) {
            $result = $schema->generate_schema($post_id);
            if (is_wp_error($result)) {
                return $result;
            }
            return 'Schema regenerated';
        }
        
        return new WP_Error('no_method', 'Schema generation method not found');
    }
    
    /**
     * Process a single orphan page fix
     */
    private static function process_orphan_fix($post_id, $payload) {
        // Delegate to the search console class if available
        if (!class_exists('SSF_Search_Console')) {
            return new WP_Error('no_module', 'Search Console module not available');
        }
        
        // The orphan fix is complex — we simulate what the AJAX handler does
        // For now, return a placeholder; the actual fix logic is in class-search-console.php
        return new WP_Error('not_implemented', 'Orphan fix via queue not yet implemented');
    }
    
    /**
     * Cancel a job
     */
    public static function cancel($job_id) {
        global $wpdb;
        
        $job = self::get($job_id);
        if (!$job) {
            return new WP_Error('not_found', __('Job not found.', 'smart-seo-fixer'));
        }
        
        if (in_array($job->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            return new WP_Error('invalid_status', __('Job is already finished.', 'smart-seo-fixer'));
        }
        
        $wpdb->update(
            self::table(),
            [
                'status'       => self::STATUS_CANCELLED,
                'completed_at' => current_time('mysql'),
            ],
            ['id' => $job_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if (class_exists('SSF_Logger')) {
            SSF_Logger::info(sprintf('Job #%d cancelled', $job_id), 'queue');
        }
        
        return true;
    }
    
    /**
     * Retry failed items in a job
     */
    public static function retry_failed($job_id) {
        global $wpdb;
        
        $job = self::get($job_id);
        if (!$job) {
            return new WP_Error('not_found', __('Job not found.', 'smart-seo-fixer'));
        }
        
        // Collect failed item IDs
        $failed_ids = [];
        foreach ($job->results as $r) {
            if ($r['status'] === 'failed') {
                $failed_ids[] = $r['item_id'];
            }
        }
        
        if (empty($failed_ids)) {
            return new WP_Error('no_failures', __('No failed items to retry.', 'smart-seo-fixer'));
        }
        
        // Create a new job with just the failed items
        return self::create($job->job_type, $failed_ids, $job->payload);
    }
    
    /**
     * Mark a job as completed
     */
    private static function mark_completed($job_id) {
        global $wpdb;
        $wpdb->update(
            self::table(),
            [
                'status'       => self::STATUS_COMPLETED,
                'completed_at' => current_time('mysql'),
            ],
            ['id' => $job_id],
            ['%s', '%s'],
            ['%d']
        );
    }
    
    /**
     * Get recent jobs (for admin UI)
     */
    public static function get_recent($limit = 20) {
        global $wpdb;
        $table = self::table();
        
        $jobs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, job_type, status, total_items, processed_items, failed_items,
                        error_message, user_id, created_at, started_at, completed_at
                 FROM $table
                 ORDER BY created_at DESC
                 LIMIT %d",
                $limit
            )
        );
        
        return $jobs ?: [];
    }
    
    /**
     * Get active (pending/processing) jobs count
     */
    public static function active_count() {
        global $wpdb;
        return intval($wpdb->get_var(
            "SELECT COUNT(*) FROM " . self::table() . " WHERE status IN ('pending', 'processing')"
        ));
    }
    
    /**
     * Cleanup old completed/cancelled jobs (keep last 30 days)
     */
    public static function cleanup($days = 30) {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . self::table() . " 
                 WHERE status IN ('completed', 'cancelled', 'failed') 
                 AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }
}
