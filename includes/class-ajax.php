<?php
/**
 * AJAX Handler Class
 * 
 * Handles all AJAX requests for the plugin.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Analyze actions
        add_action('wp_ajax_ssf_analyze_post', [$this, 'analyze_post']);
        add_action('wp_ajax_ssf_bulk_analyze', [$this, 'bulk_analyze']);
        
        // AI generation actions
        add_action('wp_ajax_ssf_generate_title', [$this, 'generate_title']);
        add_action('wp_ajax_ssf_generate_description', [$this, 'generate_description']);
        add_action('wp_ajax_ssf_generate_alt_text', [$this, 'generate_alt_text']);
        add_action('wp_ajax_ssf_ai_analyze', [$this, 'ai_analyze']);
        add_action('wp_ajax_ssf_suggest_keywords', [$this, 'suggest_keywords']);
        
        // Save actions
        add_action('wp_ajax_ssf_save_seo_data', [$this, 'save_seo_data']);
        add_action('wp_ajax_ssf_save_settings', [$this, 'save_settings']);
        add_action('wp_ajax_ssf_test_bedrock',  [$this, 'test_bedrock']);
        
        // Fix actions
        add_action('wp_ajax_ssf_fix_issue', [$this, 'fix_issue']);
        add_action('wp_ajax_ssf_bulk_fix', [$this, 'bulk_fix']);
        
        // Utility actions
        add_action('wp_ajax_ssf_get_post_seo_data', [$this, 'get_post_seo_data']);
        add_action('wp_ajax_ssf_get_dashboard_stats', [$this, 'get_dashboard_stats']);
        
        // AI content tools
        add_action('wp_ajax_ssf_suggest_internal_links', [$this, 'suggest_internal_links']);
        add_action('wp_ajax_ssf_suggest_external_links', [$this, 'suggest_external_links']);
        add_action('wp_ajax_ssf_fix_image_alt_texts', [$this, 'fix_image_alt_texts']);
        add_action('wp_ajax_ssf_get_map_embed', [$this, 'get_map_embed']);
        
        // Bulk operations
        add_action('wp_ajax_ssf_bulk_ai_fix', [$this, 'bulk_ai_fix']);
        add_action('wp_ajax_ssf_preview_bulk_fix', [$this, 'preview_bulk_fix']);
        add_action('wp_ajax_ssf_suggest_images', [$this, 'suggest_images']);
        
        // Google Search Console
        add_action('wp_ajax_ssf_gsc_refresh_sites', [$this, 'gsc_refresh_sites']);
        add_action('wp_ajax_ssf_gsc_disconnect', [$this, 'gsc_disconnect']);
        add_action('wp_ajax_ssf_gsc_performance', [$this, 'gsc_performance']);
        add_action('wp_ajax_ssf_gsc_inspect_url', [$this, 'gsc_inspect_url']);
        add_action('wp_ajax_ssf_gsc_submit_sitemap', [$this, 'gsc_submit_sitemap']);
        add_action('wp_ajax_ssf_gsc_not_indexed', [$this, 'gsc_not_indexed']);
        add_action('wp_ajax_ssf_ai_fix_single', [$this, 'ai_fix_single']);
        
        // Schema tools
        add_action('wp_ajax_ssf_toggle_local_schema', [$this, 'toggle_local_schema']);
        
        // AI content tools
        add_action('wp_ajax_ssf_generate_outline', [$this, 'generate_outline']);
        add_action('wp_ajax_ssf_improve_readability', [$this, 'improve_readability']);
        add_action('wp_ajax_ssf_suggest_schema', [$this, 'suggest_schema']);
        
        // Bulk schema regeneration
        add_action('wp_ajax_ssf_bulk_regenerate_schemas', [$this, 'bulk_regenerate_schemas']);
        
        // Schema management page
        add_action('wp_ajax_ssf_toggle_setting', [$this, 'toggle_setting']);
        add_action('wp_ajax_ssf_get_schema_list', [$this, 'get_schema_list']);
        add_action('wp_ajax_ssf_delete_single_schema', [$this, 'delete_single_schema']);
        add_action('wp_ajax_ssf_regenerate_single_schema', [$this, 'regenerate_single_schema']);
        add_action('wp_ajax_ssf_generate_schema_for_post', [$this, 'generate_schema_for_post']);
        add_action('wp_ajax_ssf_search_posts_for_schema', [$this, 'search_posts_for_schema']);
        
        // Change History & Undo
        add_action('wp_ajax_ssf_get_history', [$this, 'get_history']);
        add_action('wp_ajax_ssf_undo_change', [$this, 'undo_change']);
        add_action('wp_ajax_ssf_get_history_stats', [$this, 'get_history_stats']);
        
        // Debug Log
        add_action('wp_ajax_ssf_get_logs', [$this, 'get_logs']);
        add_action('wp_ajax_ssf_clear_logs', [$this, 'clear_logs']);
        
        // Job Queue
        add_action('wp_ajax_ssf_get_job_status', [$this, 'get_job_status']);
        add_action('wp_ajax_ssf_get_jobs', [$this, 'get_jobs']);
        add_action('wp_ajax_ssf_cancel_job', [$this, 'cancel_job']);
        add_action('wp_ajax_ssf_retry_job', [$this, 'retry_job']);
        
        // Broken Links
        add_action('wp_ajax_ssf_get_broken_links', [$this, 'get_broken_links']);
        add_action('wp_ajax_ssf_scan_broken_links', [$this, 'scan_broken_links']);
        add_action('wp_ajax_ssf_recheck_broken_link', [$this, 'recheck_broken_link']);
        add_action('wp_ajax_ssf_dismiss_broken_link', [$this, 'dismiss_broken_link']);
        add_action('wp_ajax_ssf_undismiss_broken_link', [$this, 'undismiss_broken_link']);
        add_action('wp_ajax_ssf_bulk_redirect_broken_links', [$this, 'bulk_redirect_broken_links']);
        add_action('wp_ajax_ssf_bulk_dismiss_broken_links', [$this, 'bulk_dismiss_broken_links']);

        // Canonical fixer
        add_action('wp_ajax_ssf_auto_fix_canonicals', [$this, 'auto_fix_canonicals']);
        add_action('wp_ajax_ssf_scan_canonical_issues', [$this, 'scan_canonical_issues']);
        
        // 404 Monitor
        add_action('wp_ajax_ssf_get_404_logs', [$this, 'get_404_logs']);
        add_action('wp_ajax_ssf_dismiss_404', [$this, 'dismiss_404']);
        add_action('wp_ajax_ssf_create_404_redirect', [$this, 'create_404_redirect']);
        add_action('wp_ajax_ssf_clear_404_logs', [$this, 'clear_404_logs']);
        
        // robots.txt Editor
        add_action('wp_ajax_ssf_save_robots', [$this, 'save_robots']);
        
        // Readability
        add_action('wp_ajax_ssf_analyze_readability', [$this, 'analyze_readability']);
        
        // Social Preview
        add_action('wp_ajax_ssf_save_social_data', [$this, 'save_social_data']);
        add_action('wp_ajax_ssf_get_social_data', [$this, 'get_social_data']);
        
        // Keyword Tracker
        add_action('wp_ajax_ssf_get_tracked_keywords', [$this, 'get_tracked_keywords']);
        add_action('wp_ajax_ssf_get_keyword_history', [$this, 'get_keyword_history']);
        add_action('wp_ajax_ssf_fetch_keywords_now', [$this, 'fetch_keywords_now']);
        
        // Content Suggestions
        add_action('wp_ajax_ssf_content_suggestions', [$this, 'content_suggestions']);
        
        // WP Coding Standards
        add_action('wp_ajax_ssf_wp_standards_audit', [$this, 'wp_standards_audit']);
        
        // Performance Profiler
        add_action('wp_ajax_ssf_performance_data', [$this, 'performance_data']);
        add_action('wp_ajax_ssf_performance_clear', [$this, 'performance_clear']);
        
        // Content Duplication Detection
        add_action('wp_ajax_ssf_detect_duplicates', [$this, 'detect_duplicates']);
        
        // Core Web Vitals data
        add_action('wp_ajax_ssf_get_cwv_data', [$this, 'get_cwv_data']);
        
        // Internal Link Auto-Insertion
        add_action('wp_ajax_ssf_insert_internal_links', [$this, 'insert_internal_links']);
        
        // Bulk Fix Preview — Approve/Reject
        add_action('wp_ajax_ssf_apply_bulk_preview', [$this, 'apply_bulk_preview']);
        
        // Image SEO audit
        add_action('wp_ajax_ssf_audit_images', [$this, 'audit_images']);
        
        // Onboarding checklist
        add_action('wp_ajax_ssf_get_onboarding_status', [$this, 'get_onboarding_status']);
        add_action('wp_ajax_ssf_dismiss_onboarding', [$this, 'dismiss_onboarding']);
        
        // Generic background job dispatch & polling
        add_action('wp_ajax_ssf_dispatch_job', [$this, 'dispatch_job']);
        add_action('wp_ajax_ssf_poll_job', [$this, 'poll_job']);
        
        // Client Report
        add_action('wp_ajax_ssf_generate_client_report', [$this, 'generate_client_report']);
    }
    
    /**
     * Verify nonce
     */
    private function verify_nonce() {
        if (!check_ajax_referer('ssf_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'smart-seo-fixer')]);
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
    }
    
    /**
     * Analyze single post
     */
    public function analyze_post() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        $analyzer = new SSF_Analyzer();
        $result = $analyzer->analyze_post($post_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        // Track for onboarding checklist
        if (!get_option('ssf_first_analysis_done', false)) {
            update_option('ssf_first_analysis_done', true);
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Bulk analyze posts (supports both post_ids array and offset/batch_size)
     */
    public function bulk_analyze() {
        $this->verify_nonce();
        
        // Check if using batch mode (offset/batch_size) or direct mode (post_ids)
        if (isset($_POST['offset'])) {
            // Batch mode for dashboard
            $offset = intval($_POST['offset'] ?? 0);
            $batch_size = intval($_POST['batch_size'] ?? 5);
            $mode = sanitize_text_field($_POST['analyze_mode'] ?? 'unanalyzed'); // 'unanalyzed' or 'all'
            
            global $wpdb;
            $table = $wpdb->prefix . 'ssf_seo_scores';
            $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
            $post_types_str = "'" . implode("','", array_map('esc_sql', $post_types)) . "'";
            
            if ($mode === 'all') {
                // Get ALL published posts
                $total = $wpdb->get_var("
                    SELECT COUNT(*) 
                    FROM {$wpdb->posts}
                    WHERE post_status = 'publish'
                    AND post_type IN ($post_types_str)
                ");
                
                $posts = $wpdb->get_col($wpdb->prepare("
                    SELECT ID 
                    FROM {$wpdb->posts}
                    WHERE post_status = 'publish'
                    AND post_type IN ($post_types_str)
                    ORDER BY ID ASC
                    LIMIT %d OFFSET %d
                ", $batch_size, $offset));
            } else {
                // Get only unanalyzed posts
                // Check if table exists first
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
                
                if ($table_exists) {
                    $total = $wpdb->get_var("
                        SELECT COUNT(*) 
                        FROM {$wpdb->posts} p
                        LEFT JOIN $table s ON p.ID = s.post_id
                        WHERE p.post_status = 'publish'
                        AND p.post_type IN ($post_types_str)
                        AND s.post_id IS NULL
                    ");
                    
                    $posts = $wpdb->get_col($wpdb->prepare("
                        SELECT p.ID 
                        FROM {$wpdb->posts} p
                        LEFT JOIN $table s ON p.ID = s.post_id
                        WHERE p.post_status = 'publish'
                        AND p.post_type IN ($post_types_str)
                        AND s.post_id IS NULL
                        ORDER BY p.ID ASC
                        LIMIT %d OFFSET %d
                    ", $batch_size, $offset));
                } else {
                    // Table doesn't exist — treat all as unanalyzed
                    $total = $wpdb->get_var("
                        SELECT COUNT(*) 
                        FROM {$wpdb->posts}
                        WHERE post_status = 'publish'
                        AND post_type IN ($post_types_str)
                    ");
                    
                    $posts = $wpdb->get_col($wpdb->prepare("
                        SELECT ID 
                        FROM {$wpdb->posts}
                        WHERE post_status = 'publish'
                        AND post_type IN ($post_types_str)
                        ORDER BY ID ASC
                        LIMIT %d OFFSET %d
                    ", $batch_size, $offset));
                }
            }
            
            $analyzer = new SSF_Analyzer();
            $log = [];
            
            foreach ($posts as $post_id) {
                $post = get_post($post_id);
                if ($post) {
                    $analysis = $analyzer->analyze_post($post_id);
                    $log[] = sprintf('✅ %s (Score: %d)', $post->post_title, $analysis['score'] ?? 0);
                }
            }
            
            $done = ($offset + $batch_size) >= $total || empty($posts);
            
            // Track for onboarding checklist
            if ($done && !get_option('ssf_bulk_analyze_done', false)) {
                update_option('ssf_bulk_analyze_done', true);
            }
            
            wp_send_json_success([
                'processed' => count($posts),
                'total' => intval($total),
                'done' => $done,
                'log' => $log,
            ]);
        } else {
            // Direct mode with post_ids
            $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
            
            if (empty($post_ids)) {
                wp_send_json_error(['message' => __('No posts selected.', 'smart-seo-fixer')]);
            }
            
            $analyzer = new SSF_Analyzer();
            $results = $analyzer->bulk_analyze($post_ids);
            
            wp_send_json_success($results);
        }
    }
    
    /**
     * Generate AI title
     */
    public function generate_title() {
        $this->verify_nonce();
        if (class_exists('SSF_History')) SSF_History::set_source('ai');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $focus_keyword = sanitize_text_field($_POST['focus_keyword'] ?? '');
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        $result = $openai->generate_title(
            $post->post_content,
            $post->post_title,
            $focus_keyword
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        $title = sanitize_text_field(trim($result));
        
        if (empty($title)) {
            wp_send_json_error(['message' => __('AI returned an empty title. Please try again.', 'smart-seo-fixer')]);
        }
        
        // Auto-save to post meta so generated content persists immediately
        update_post_meta($post_id, '_ssf_seo_title', $title);
        
        wp_send_json_success(['title' => $title]);
    }
    
    /**
     * Generate AI meta description
     */
    public function generate_description() {
        $this->verify_nonce();
        if (class_exists('SSF_History')) SSF_History::set_source('ai');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $focus_keyword = sanitize_text_field($_POST['focus_keyword'] ?? '');
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        $current_desc = get_post_meta($post_id, '_ssf_meta_description', true);
        
        $result = $openai->generate_meta_description(
            $post->post_content,
            $current_desc,
            $focus_keyword
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        $description = sanitize_textarea_field(trim($result));
        
        if (empty($description)) {
            wp_send_json_error(['message' => __('AI returned an empty description. Please try again.', 'smart-seo-fixer')]);
        }
        
        // Auto-save to post meta so generated content persists immediately
        update_post_meta($post_id, '_ssf_meta_description', $description);
        
        wp_send_json_success(['description' => $description]);
    }
    
    /**
     * Generate AI alt text for image
     */
    public function generate_alt_text() {
        $this->verify_nonce();
        
        $image_url = esc_url_raw($_POST['image_url'] ?? '');
        $page_context = sanitize_textarea_field($_POST['page_context'] ?? '');
        $focus_keyword = sanitize_text_field($_POST['focus_keyword'] ?? '');
        
        if (empty($image_url)) {
            wp_send_json_error(['message' => __('Image URL required.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        $result = $openai->generate_alt_text($image_url, $page_context, $focus_keyword);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success(['alt_text' => trim($result)]);
    }
    
    /**
     * AI content analysis
     */
    public function ai_analyze() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $focus_keyword = sanitize_text_field($_POST['focus_keyword'] ?? '');
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        $result = $openai->analyze_content(
            $post->post_content,
            $post->post_title,
            $focus_keyword
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Suggest focus keywords
     */
    public function suggest_keywords() {
        $this->verify_nonce();
        if (class_exists('SSF_History')) SSF_History::set_source('ai');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        $result = $openai->suggest_keywords($post->post_content, $post->post_title);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        if (!is_array($result) || empty($result['primary'])) {
            wp_send_json_error(['message' => __('Could not parse keyword suggestions. Please try again.', 'smart-seo-fixer')]);
        }
        
        // Auto-save primary keyword to post meta
        $primary = sanitize_text_field($result['primary']);
        update_post_meta($post_id, '_ssf_focus_keyword', $primary);
        
        wp_send_json_success($result);
    }
    
    /**
     * Save SEO data for a post
     */
    public function save_seo_data() {
        $this->verify_nonce();
        if (class_exists('SSF_History')) SSF_History::set_source('manual');
        
        $post_id = class_exists('SSF_Validator') ? SSF_Validator::post_id($_POST['post_id'] ?? 0) : intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $data = [
            'seo_title'        => class_exists('SSF_Validator') ? SSF_Validator::seo_title($_POST['seo_title'] ?? '') : sanitize_text_field($_POST['seo_title'] ?? ''),
            'meta_description' => class_exists('SSF_Validator') ? SSF_Validator::meta_description($_POST['meta_description'] ?? '') : sanitize_textarea_field($_POST['meta_description'] ?? ''),
            'focus_keyword'    => class_exists('SSF_Validator') ? SSF_Validator::focus_keyword($_POST['focus_keyword'] ?? '') : sanitize_text_field($_POST['focus_keyword'] ?? ''),
            'canonical_url'    => $this->normalize_canonical_for_storage(
                                       class_exists('SSF_Validator') ? SSF_Validator::url($_POST['canonical_url'] ?? '') : esc_url_raw($_POST['canonical_url'] ?? ''),
                                       intval($_POST['post_id'] ?? 0)
                                   ),
            'noindex'          => !empty($_POST['noindex']) ? 1 : 0,
            'nofollow'         => !empty($_POST['nofollow']) ? 1 : 0,
        ];
        
        $meta_manager = new SSF_Meta_Manager();
        $meta_manager->save_post_seo_data($post_id, $data);
        
        // Re-analyze after save
        $analyzer = new SSF_Analyzer();
        $analysis = $analyzer->analyze_post($post_id);
        
        wp_send_json_success([
            'message' => __('SEO data saved successfully.', 'smart-seo-fixer'),
            'analysis' => $analysis,
        ]);
    }
    
    /**
     * Test AWS Bedrock connection with the provided credentials
     */
    public function test_bedrock() {
        $this->verify_nonce();

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }

        if (!class_exists('SSF_Bedrock')) {
            wp_send_json_error(['message' => __('Bedrock class not available.', 'smart-seo-fixer')]);
        }

        $using_const = defined('SSF_BEDROCK_ACCESS_KEY') && SSF_BEDROCK_ACCESS_KEY !== ''
                    && defined('SSF_BEDROCK_SECRET_KEY') && SSF_BEDROCK_SECRET_KEY !== '';

        if ($using_const) {
            // Constants defined in wp-config.php — test directly, no DB manipulation needed
            $bedrock = new SSF_Bedrock();
            $result  = $bedrock->request(
                [['role' => 'user', 'content' => 'Reply with exactly the word: CONNECTED']],
                10,
                0.0
            );
        } else {
            // Credentials submitted from the settings form
            $access_key = sanitize_text_field($_POST['access_key'] ?? '');
            $secret_key = sanitize_text_field($_POST['secret_key'] ?? '');
            $region     = sanitize_text_field($_POST['region']     ?? 'us-east-1');

            if (empty($access_key) || empty($secret_key)) {
                wp_send_json_error(['message' => __('Access Key and Secret Key are required.', 'smart-seo-fixer')]);
            }

            // Temporarily override options so SSF_Bedrock uses these values
            $original = [
                'bedrock_access_key' => Smart_SEO_Fixer::get_option('bedrock_access_key'),
                'bedrock_secret_key' => Smart_SEO_Fixer::get_option('bedrock_secret_key'),
                'bedrock_region'     => Smart_SEO_Fixer::get_option('bedrock_region', 'us-east-1'),
            ];

            $opts = get_option('smart_seo_fixer_options', []);
            $opts['bedrock_access_key'] = $access_key;
            $opts['bedrock_secret_key'] = $secret_key;
            $opts['bedrock_region']     = $region;
            update_option('smart_seo_fixer_options', $opts);

            $bedrock = new SSF_Bedrock();
            $result  = $bedrock->request(
                [['role' => 'user', 'content' => 'Reply with exactly the word: CONNECTED']],
                10,
                0.0
            );

            // Restore original credentials
            $opts['bedrock_access_key'] = $original['bedrock_access_key'];
            $opts['bedrock_secret_key'] = $original['bedrock_secret_key'];
            $opts['bedrock_region']     = $original['bedrock_region'];
            update_option('smart_seo_fixer_options', $opts);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['reply' => trim($result)]);
    }

    /**
     * Save plugin settings
     */
    public function save_settings() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $v = class_exists('SSF_Validator');
        
                $settings = [
            'ai_provider'             => 'bedrock',
            'bedrock_region'          => sanitize_text_field($_POST['bedrock_region'] ?? 'us-east-1'),
            'bedrock_access_key'      => $v ? SSF_Validator::api_key($_POST['bedrock_access_key'] ?? '') : sanitize_text_field($_POST['bedrock_access_key'] ?? ''),
            'bedrock_secret_key'      => $v ? SSF_Validator::api_key($_POST['bedrock_secret_key'] ?? '') : sanitize_text_field($_POST['bedrock_secret_key'] ?? ''),
            'bedrock_model'           => 'us.anthropic.claude-sonnet-4-6',
            'auto_meta'               => !empty($_POST['auto_meta']) ? 1 : 0,
            'auto_alt_text'           => !empty($_POST['auto_alt_text']) ? 1 : 0,
            'enable_schema'           => !empty($_POST['enable_schema']) ? 1 : 0,
            'enable_sitemap'          => !empty($_POST['enable_sitemap']) ? 1 : 0,
            'disable_other_seo_output'=> !empty($_POST['disable_other_seo_output']) ? 1 : 0,
            'redirect_attachments'    => in_array(($_POST['redirect_attachments'] ?? ''), ['parent', 'file'], true) ? sanitize_text_field($_POST['redirect_attachments']) : '',
            'background_seo_cron'     => !empty($_POST['background_seo_cron']) ? 1 : 0,
            'github_token'            => $v ? SSF_Validator::api_key($_POST['github_token'] ?? '') : sanitize_text_field($_POST['github_token'] ?? ''),
            'gsc_client_id'           => sanitize_text_field($_POST['gsc_client_id'] ?? ''),
            'gsc_client_secret'       => sanitize_text_field($_POST['gsc_client_secret'] ?? ''),
            'title_separator'         => $v ? SSF_Validator::title_separator($_POST['title_separator'] ?? '|') : sanitize_text_field($_POST['title_separator'] ?? '|'),
            'homepage_title'          => $v ? SSF_Validator::seo_title($_POST['homepage_title'] ?? '') : sanitize_text_field($_POST['homepage_title'] ?? ''),
            'homepage_description'    => $v ? SSF_Validator::meta_description($_POST['homepage_description'] ?? '') : sanitize_textarea_field($_POST['homepage_description'] ?? ''),
        ];
        
        // Schedule or unschedule background cron based on setting
        $cron_enabled = !empty($_POST['background_seo_cron']);
        $cron_scheduled = wp_next_scheduled('ssf_cron_generate_missing_seo');
        
        if ($cron_enabled && !$cron_scheduled) {
            wp_schedule_event(time(), 'twicedaily', 'ssf_cron_generate_missing_seo');
        } elseif (!$cron_enabled && $cron_scheduled) {
            wp_unschedule_event($cron_scheduled, 'ssf_cron_generate_missing_seo');
        }
        
        // Handle post types array
        if (isset($_POST['post_types']) && is_array($_POST['post_types'])) {
            $settings['post_types'] = $v ? SSF_Validator::post_types($_POST['post_types']) : array_map('sanitize_text_field', $_POST['post_types']);
        }
        
        // Handle GSC site URL selection (can be URL or sc-domain: format)
        if (isset($_POST['gsc_site_url'])) {
            $val = sanitize_text_field($_POST['gsc_site_url']);
            if (strpos($val, 'sc-domain:') === 0 || filter_var($val, FILTER_VALIDATE_URL)) {
                $settings['gsc_site_url'] = $val;
            }
        }
        
        // Preserve existing Bedrock credentials if not re-submitted
        if (empty($settings['bedrock_access_key'])) {
            unset($settings['bedrock_access_key']);
        }
        if (empty($settings['bedrock_secret_key'])) {
            unset($settings['bedrock_secret_key']);
        }
        
        // Preserve existing GSC credentials if not submitted (connected state hides the fields)
        if (empty($settings['gsc_client_id'])) {
            unset($settings['gsc_client_id']);
        }
        if (empty($settings['gsc_client_secret'])) {
            unset($settings['gsc_client_secret']);
        }
        
        foreach ($settings as $key => $value) {
            Smart_SEO_Fixer::update_option($key, $value);
        }
        
        // Flush rewrite rules if sitemap setting changed
        SSF_Sitemap::flush_rules();
        
        wp_send_json_success(['message' => __('Settings saved successfully.', 'smart-seo-fixer')]);
    }
    
    /**
     * Fix a specific issue
     */
    public function fix_issue() {
        $this->verify_nonce();
        if (class_exists('SSF_History')) SSF_History::set_source('ai');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $issue_code = sanitize_text_field($_POST['issue_code'] ?? '');
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        $result = [];
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        switch ($issue_code) {
            case 'no_title':
            case 'title_too_short':
            case 'keyword_not_in_title':
                $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
                $title = $openai->generate_title($post->post_content, $post->post_title, $focus_keyword);
                
                if (is_wp_error($title)) {
                    wp_send_json_error(['message' => $title->get_error_message()]);
                }
                if (!empty(trim($title))) {
                    update_post_meta($post_id, '_ssf_seo_title', sanitize_text_field(trim($title)));
                    $result['seo_title'] = trim($title);
                } else {
                    wp_send_json_error(['message' => __('AI returned empty title. Try again.', 'smart-seo-fixer')]);
                }
                break;
                
            case 'no_meta_description':
            case 'meta_too_short':
            case 'keyword_not_in_meta':
                $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
                $desc = $openai->generate_meta_description($post->post_content, '', $focus_keyword);
                
                if (is_wp_error($desc)) {
                    wp_send_json_error(['message' => $desc->get_error_message()]);
                }
                if (!empty(trim($desc))) {
                    update_post_meta($post_id, '_ssf_meta_description', sanitize_textarea_field(trim($desc)));
                    $result['meta_description'] = trim($desc);
                } else {
                    wp_send_json_error(['message' => __('AI returned empty description. Try again.', 'smart-seo-fixer')]);
                }
                break;
                
            default:
                wp_send_json_error(['message' => __('Unknown issue type.', 'smart-seo-fixer')]);
        }
        
        // Re-analyze
        $analyzer = new SSF_Analyzer();
        $analysis = $analyzer->analyze_post($post_id);
        
        wp_send_json_success([
            'message' => __('Issue fixed successfully.', 'smart-seo-fixer'),
            'fixed' => $result,
            'analysis' => $analysis,
        ]);
    }
    
    /**
     * Bulk fix issues
     */
    public function bulk_fix() {
        $this->verify_nonce();
        if (class_exists('SSF_History')) SSF_History::set_source('bulk');
        
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
        $issue_types = isset($_POST['issue_types']) ? array_map('sanitize_text_field', $_POST['issue_types']) : [];
        
        if (empty($post_ids)) {
            wp_send_json_error(['message' => __('No posts selected.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        $analyzer = class_exists('SSF_Analyzer') ? new SSF_Analyzer() : null;
        
        $results = [
            'success' => 0,
            'failed' => 0,
            'posts' => [],
        ];
        
        foreach ($post_ids as $post_id) {
            if (!current_user_can('edit_post', $post_id)) {
                $results['failed']++;
                continue;
            }
            
            $post = get_post($post_id);
            if (!$post) {
                $results['failed']++;
                continue;
            }
            
            $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
            $fixed = [];
            
            // Generate title if needed
            if (in_array('title', $issue_types)) {
                $current_title = get_post_meta($post_id, '_ssf_seo_title', true);
                if (empty($current_title) || strlen($current_title) < 30) {
                    $title = $openai->generate_title($post->post_content, $post->post_title, $focus_keyword);
                    if (!is_wp_error($title) && !empty(trim($title))) {
                        update_post_meta($post_id, '_ssf_seo_title', sanitize_text_field(trim($title)));
                        $fixed[] = 'title';
                    }
                }
            }
            
            // Generate meta description if needed
            if (in_array('meta', $issue_types)) {
                $current_desc = get_post_meta($post_id, '_ssf_meta_description', true);
                if (empty($current_desc) || strlen($current_desc) < 120) {
                    $desc = $openai->generate_meta_description($post->post_content, '', $focus_keyword);
                    if (!is_wp_error($desc) && !empty(trim($desc))) {
                        update_post_meta($post_id, '_ssf_meta_description', sanitize_textarea_field(trim($desc)));
                        $fixed[] = 'meta';
                    }
                }
            }
            
            // Re-analyze
            $score = 0;
            if ($analyzer) {
                $analysis = $analyzer->analyze_post($post_id);
                $score = $analysis['score'] ?? 0;
            }
            
            $results['success']++;
            $results['posts'][$post_id] = [
                'fixed' => $fixed,
                'new_score' => $score,
            ];
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Get SEO data for a post
     */
    public function get_post_seo_data() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        $meta_manager = new SSF_Meta_Manager();
        $data = $meta_manager->get_post_seo_data($post_id);
        
        // Get analysis
        $analyzer = new SSF_Analyzer();
        $analysis = $analyzer->get_analysis($post_id);
        
        if ($analysis) {
            $data['analysis'] = [
                'score' => $analysis->score,
                'issues' => json_decode($analysis->issues, true),
                'suggestions' => json_decode($analysis->suggestions, true),
                'last_analyzed' => $analysis->last_analyzed,
            ];
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get dashboard statistics
     */
    public function get_dashboard_stats() {
        $this->verify_nonce();
        
        global $wpdb;
        
        $table = $wpdb->prefix . 'ssf_seo_scores';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        
        $stats = null;
        $needs_attention = [];
        $recent = [];
        
        if ($table_exists) {
            // Get overall stats
            $stats = $wpdb->get_row("
                SELECT 
                    COUNT(*) as total_posts,
                    AVG(score) as avg_score,
                    SUM(CASE WHEN score >= 80 THEN 1 ELSE 0 END) as good_count,
                    SUM(CASE WHEN score >= 60 AND score < 80 THEN 1 ELSE 0 END) as ok_count,
                    SUM(CASE WHEN score < 60 THEN 1 ELSE 0 END) as poor_count
                FROM $table
            ");
            
            // Get posts needing attention
            $needs_attention = $wpdb->get_results("
                SELECT s.*, p.post_title 
                FROM $table s
                JOIN {$wpdb->posts} p ON s.post_id = p.ID
                WHERE s.score < 60
                ORDER BY s.score ASC
                LIMIT 10
            ") ?: [];
            
            // Get recently analyzed
            $recent = $wpdb->get_results("
                SELECT s.*, p.post_title 
                FROM $table s
                JOIN {$wpdb->posts} p ON s.post_id = p.ID
                ORDER BY s.last_analyzed DESC
                LIMIT 10
            ") ?: [];
        }
        
        // Count unanalyzed posts
        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        
        // Sanitize post types for SQL
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        
        if ($table_exists) {
            $unanalyzed = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) 
                    FROM {$wpdb->posts} p
                    LEFT JOIN $table s ON p.ID = s.post_id
                    WHERE p.post_status = 'publish'
                    AND p.post_type IN ($placeholders)
                    AND s.post_id IS NULL",
                    ...$post_types
                )
            );
        } else {
            // If table doesn't exist, all published posts are unanalyzed
            $unanalyzed = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) 
                    FROM {$wpdb->posts}
                    WHERE post_status = 'publish'
                    AND post_type IN ($placeholders)",
                    ...$post_types
                )
            );
        }
        
        // Count posts missing AI-generated SEO title
        $missing_titles = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_ssf_seo_title'
                WHERE p.post_status = 'publish'
                AND p.post_type IN ($placeholders)
                AND (pm.meta_value IS NULL OR pm.meta_value = '')",
                ...$post_types
            )
        );
        
        // Count posts missing meta description
        $missing_descs = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_ssf_meta_description'
                WHERE p.post_status = 'publish'
                AND p.post_type IN ($placeholders)
                AND (pm.meta_value IS NULL OR pm.meta_value = '')",
                ...$post_types
            )
        );
        
        // Cron status
        $cron_last = get_option('ssf_cron_last_run', null);
        $cron_next = wp_next_scheduled('ssf_cron_generate_missing_seo');
        
        wp_send_json_success([
            'total_posts' => intval($stats->total_posts ?? 0),
            'avg_score' => round($stats->avg_score ?? 0),
            'good_count' => intval($stats->good_count ?? 0),
            'ok_count' => intval($stats->ok_count ?? 0),
            'poor_count' => intval($stats->poor_count ?? 0),
            'unanalyzed' => intval($unanalyzed),
            'missing_titles' => intval($missing_titles),
            'missing_descs' => intval($missing_descs),
            'cron_last_run' => $cron_last,
            'cron_next_run' => $cron_next ? date('Y-m-d H:i:s', $cron_next) : null,
            'needs_attention' => $needs_attention,
            'recent' => $recent,
        ]);
    }
    
    /**
     * Suggest internal links based on content
     */
    public function suggest_internal_links() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
        
        // Find related posts
        $args = [
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'post__not_in' => [$post_id],
            's' => $focus_keyword ?: $post->post_title,
        ];
        
        $related = get_posts($args);
        
        $ai    = SSF_AI::get();
        $links = [];
        
        foreach ($related as $related_post) {
            $url = get_permalink($related_post->ID);
            
            // Skip if already linked in content
            if (strpos($post->post_content, $url) !== false) {
                continue;
            }
            
            if ($ai->is_configured()) {
                // Ask AI to find an existing phrase in this post that would serve as an anchor
                $placement = $ai->find_internal_link_placement(
                    $post->post_content,
                    $related_post->post_title,
                    $url
                );
                
                if ( ! is_wp_error($placement)
                    && ! empty($placement['found'])
                    && ! empty($placement['anchor_text'])
                    && stripos($post->post_content, $placement['anchor_text']) !== false
                ) {
                    $links[] = [
                        'title'       => $related_post->post_title,
                        'url'         => $url,
                        'anchor_text' => $placement['anchor_text'],
                    ];
                }
            } else {
                // No AI configured: return link without anchor placement
                $links[] = [
                    'title'       => $related_post->post_title,
                    'url'         => $url,
                    'anchor_text' => '',
                ];
            }
            
            if (count($links) >= 3) {
                break;
            }
        }
        
        if (empty($links)) {
            wp_send_json_error(['message' => __('No suitable anchor phrases found in this content for internal linking.', 'smart-seo-fixer')]);
        }
        
        wp_send_json_success(['links' => $links]);
    }
    
    /**
     * Suggest external authoritative links using AI
     */
    public function suggest_external_links() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        // Prepare content — strip tags and limit length for the AI prompt
        $plain_content = wp_strip_all_tags($post->post_content);
        if (strlen($plain_content) > 2500) {
            $plain_content = substr($plain_content, 0, 2500);
        }
        
        // Use AI to suggest authoritative sources and find exact anchor phrases in the content
        $prompt = "Analyze the following content and suggest 3 authoritative external sources to link to for credibility and SEO.\n\n";
        $prompt .= "For each suggestion provide:\n";
        $prompt .= "- 'url': a real, working URL to an authoritative source (Wikipedia, government site, major publication, or official documentation)\n";
        $prompt .= "- 'anchor_text': an EXACT phrase (2-6 words) copied VERBATIM from the content below — this phrase will become the hyperlink\n";
        $prompt .= "- 'reason': one sentence explaining why this source adds value\n\n";
        $prompt .= "CRITICAL: 'anchor_text' must appear EXACTLY as written in the content. Do not invent or paraphrase phrases.\n\n";
        $prompt .= "Return ONLY a JSON array: [{\"url\":\"https://...\",\"anchor_text\":\"...\",\"reason\":\"...\"}]\n\n";
        $prompt .= "Content:\n" . $plain_content;
        
        $messages = [
            ['role' => 'system', 'content' => 'You are an SEO expert. Suggest authoritative sources with real URLs. Respond only with valid JSON array.'],
            ['role' => 'user', 'content' => $prompt],
        ];
        
        $response = $openai->request($messages, 500, 0.7);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }
        
        // Parse response
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $suggestions = json_decode(trim($response), true);
        
        if (!is_array($suggestions)) {
            wp_send_json_error(['message' => __('Could not parse AI response.', 'smart-seo-fixer')]);
        }
        
        // Filter: only keep suggestions with a valid URL and anchor_text that exists verbatim in the content
        $valid = [];
        foreach ($suggestions as $s) {
            if (empty($s['url']) || strpos($s['url'], 'http') !== 0) {
                continue;
            }
            if (empty($s['anchor_text'])) {
                continue;
            }
            if (stripos($post->post_content, $s['anchor_text']) === false) {
                continue;
            }
            $valid[] = $s;
        }
        
        if (empty($valid)) {
            wp_send_json_error(['message' => __('Could not find suitable anchor phrases in this content for external linking.', 'smart-seo-fixer')]);
        }
        
        wp_send_json_success(['suggestions' => $valid]);
    }
    
    /**
     * Fix missing image alt texts in post
     */
    public function fix_image_alt_texts() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        // Find images in content
        preg_match_all('/<img[^>]+>/i', $post->post_content, $images);
        
        if (empty($images[0])) {
            wp_send_json_success(['message' => __('No images found in content.', 'smart-seo-fixer'), 'fixed' => []]);
        }
        
        $openai = SSF_AI::get();
        $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
        $fixed = [];
        $new_content = $post->post_content;
        
        foreach ($images[0] as $img_tag) {
            // Check if has alt or alt is empty
            if (preg_match('/alt\s*=\s*["\']([^"\']*)["\']/', $img_tag, $alt_match)) {
                if (!empty(trim($alt_match[1]))) {
                    continue; // Already has alt text
                }
            }
            
            // Get image src
            if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/', $img_tag, $src_match)) {
                $image_url = $src_match[1];
                $filename = basename(parse_url($image_url, PHP_URL_PATH));
                
                // Generate alt text
                if ($openai->is_configured()) {
                    $alt_text = $openai->generate_alt_text($image_url, wp_trim_words($post->post_content, 50), $focus_keyword);
                    
                    if (!is_wp_error($alt_text)) {
                        $alt_text = trim($alt_text);
                        
                        // Add or replace alt attribute
                        if (strpos($img_tag, 'alt=') !== false) {
                            $new_img_tag = preg_replace('/alt\s*=\s*["\'][^"\']*["\']/', 'alt="' . esc_attr($alt_text) . '"', $img_tag);
                        } else {
                            $new_img_tag = str_replace('<img', '<img alt="' . esc_attr($alt_text) . '"', $img_tag);
                        }
                        
                        $new_content = str_replace($img_tag, $new_img_tag, $new_content);
                        $fixed[] = ['filename' => $filename, 'alt' => $alt_text];
                    }
                } else {
                    // Generate from filename
                    $alt_text = ucwords(str_replace(['-', '_'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
                    
                    if (strpos($img_tag, 'alt=') !== false) {
                        $new_img_tag = preg_replace('/alt\s*=\s*["\'][^"\']*["\']/', 'alt="' . esc_attr($alt_text) . '"', $img_tag);
                    } else {
                        $new_img_tag = str_replace('<img', '<img alt="' . esc_attr($alt_text) . '"', $img_tag);
                    }
                    
                    $new_content = str_replace($img_tag, $new_img_tag, $new_content);
                    $fixed[] = ['filename' => $filename, 'alt' => $alt_text];
                }
            }
        }
        
        // Update post content
        if (!empty($fixed)) {
            wp_update_post([
                'ID' => $post_id,
                'post_content' => $new_content,
            ]);
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Fixed %d images with missing alt text.', 'smart-seo-fixer'), count($fixed)),
            'fixed' => $fixed,
        ]);
    }
    
    /**
     * Get Google Map embed code
     */
    public function get_map_embed() {
        $this->verify_nonce();
        
        $local_seo = new SSF_Local_SEO();
        $settings = $local_seo->get_settings();
        
        if (empty($settings['address']['street']) && empty($settings['address']['city'])) {
            wp_send_json_error(['message' => __('No business address configured.', 'smart-seo-fixer')]);
        }
        
        // Build address string
        $address_parts = array_filter([
            $settings['address']['street'],
            $settings['address']['city'],
            $settings['address']['state'],
            $settings['address']['zip'],
            $settings['address']['country'],
        ]);
        
        $address = implode(', ', $address_parts);
        $encoded_address = urlencode($address);
        
        // Generate embed code
        $embed = '<iframe 
            width="100%" 
            height="400" 
            style="border:0" 
            loading="lazy" 
            allowfullscreen 
            referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps/embed/v1/place?key=YOUR_API_KEY&q=' . $encoded_address . '">
        </iframe>';
        
        // Alternative without API key (search mode)
        $embed_simple = '<iframe 
            src="https://maps.google.com/maps?q=' . $encoded_address . '&output=embed" 
            width="100%" 
            height="400" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>';
        
        wp_send_json_success([
            'embed' => $embed_simple,
            'address' => $address,
        ]);
    }
    
    /**
     * Suggest images for content using AI
     */
    public function suggest_images() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        $prompt = "Based on this content, suggest 3-5 types of images that would enhance SEO and user engagement. Format as JSON array with 'type' (e.g., 'Hero Image', 'Infographic'), 'description' (what it should show), and 'search_term' (keyword to find similar stock photos).\n\nContent:\n" . wp_trim_words($post->post_content, 300);
        
        $messages = [
            ['role' => 'system', 'content' => 'You are an SEO and content expert. Suggest images that would improve engagement and SEO. Respond only with valid JSON array.'],
            ['role' => 'user', 'content' => $prompt],
        ];
        
        $response = $openai->request($messages, 500, 0.7);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }
        
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $suggestions = json_decode(trim($response), true);
        
        if (!is_array($suggestions)) {
            wp_send_json_error(['message' => __('Could not parse AI response.', 'smart-seo-fixer')]);
        }
        
        wp_send_json_success(['suggestions' => $suggestions]);
    }
    
    /**
     * Preview posts that need AI fixes (returns list without making changes)
     */
    public function preview_bulk_fix() {
        $this->verify_nonce();
        
        $apply_to = sanitize_text_field($_POST['apply_to'] ?? 'missing');
        
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_seo_scores';
        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        
        // Helper: subquery to check if a meta key has a real (non-empty, non-whitespace) value
        $has_meta = function($key) use ($wpdb) {
            return "EXISTS (SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = '{$key}' AND TRIM(meta_value) != '')";
        };
        
        switch ($apply_to) {
            case 'missing':
                // Posts where ANY of title/description/keyword is missing or empty
                $query = $wpdb->prepare(
                    "SELECT DISTINCT p.ID, p.post_title, p.post_type, p.post_date
                    FROM {$wpdb->posts} p
                    WHERE p.post_status = 'publish'
                    AND p.post_type IN ($placeholders)
                    AND (
                        NOT {$has_meta('_ssf_seo_title')}
                        OR NOT {$has_meta('_ssf_meta_description')}
                        OR NOT {$has_meta('_ssf_focus_keyword')}
                    )
                    ORDER BY p.post_date DESC",
                    ...$post_types
                );
                break;
                
            case 'poor':
                $query = $wpdb->prepare(
                    "SELECT DISTINCT p.ID, p.post_title, p.post_type, p.post_date
                    FROM {$wpdb->posts} p
                    LEFT JOIN $table s ON p.ID = s.post_id
                    WHERE p.post_status = 'publish'
                    AND p.post_type IN ($placeholders)
                    AND (s.score < 60 OR s.post_id IS NULL)
                    ORDER BY p.post_date DESC",
                    ...$post_types
                );
                break;
                
            case 'all':
            default:
                $query = $wpdb->prepare(
                    "SELECT p.ID, p.post_title, p.post_type, p.post_date
                    FROM {$wpdb->posts} p
                    WHERE p.post_status = 'publish'
                    AND p.post_type IN ($placeholders)
                    ORDER BY p.post_date DESC",
                    ...$post_types
                );
                break;
        }
        
        $posts = $wpdb->get_results($query);
        
        // Enrich with current SEO status (use trim to catch whitespace-only values)
        $items = [];
        foreach ($posts as $post) {
            $seo_title = trim(get_post_meta($post->ID, '_ssf_seo_title', true));
            $meta_desc = trim(get_post_meta($post->ID, '_ssf_meta_description', true));
            $focus_kw  = trim(get_post_meta($post->ID, '_ssf_focus_keyword', true));
            $score_row = $wpdb->get_row($wpdb->prepare("SELECT score FROM $table WHERE post_id = %d", $post->ID));
            
            $missing = [];
            if (empty($seo_title))  $missing[] = 'title';
            if (empty($meta_desc))  $missing[] = 'description';
            if (empty($focus_kw))   $missing[] = 'keyword';
            
            $items[] = [
                'id'          => $post->ID,
                'title'       => $post->post_title,
                'type'        => $post->post_type,
                'edit_url'    => admin_url('post.php?action=edit&post=' . $post->ID),
                'has_title'   => !empty($seo_title),
                'has_desc'    => !empty($meta_desc),
                'has_keyword' => !empty($focus_kw),
                'seo_title'   => $seo_title,
                'score'       => $score_row ? intval($score_row->score) : null,
                'missing'     => $missing,
            ];
        }
        
        wp_send_json_success([
            'total' => count($items),
            'posts' => $items,
        ]);
    }
    
    /**
     * Bulk AI fix posts
     */
    public function bulk_ai_fix() {
        $this->verify_nonce();
        if (class_exists('SSF_History')) SSF_History::set_source('bulk');
        
        $options = $_POST['options'] ?? [];
        
        $generate_title = !empty($options['generate_title']);
        $generate_desc = !empty($options['generate_desc']);
        $generate_keywords = !empty($options['generate_keywords']);
        $apply_to = sanitize_text_field($options['apply_to'] ?? 'missing');
        
        // Validate OpenAI is configured BEFORE doing any work
        $openai = SSF_AI::get();
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        // Accept explicit post IDs from the frontend (preview selection)
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', (array) $_POST['post_ids']) : [];
        $post_ids = array_filter($post_ids, function($id) { return $id > 0; });
        
        if (empty($post_ids)) {
            wp_send_json_error(['message' => __('No posts selected.', 'smart-seo-fixer')]);
        }
        
        // For large batches (10+ posts), use background job queue
        $use_background = !empty($_POST['background']) || count($post_ids) > 10;
        if ($use_background && class_exists('SSF_Job_Queue')) {
            $payload = [
                'generate_title'    => $generate_title,
                'generate_desc'     => $generate_desc,
                'generate_keywords' => $generate_keywords,
                'apply_to'          => $apply_to,
            ];
            
            $job_id = SSF_Job_Queue::create('bulk_ai_fix', $post_ids, $payload);
            
            if (is_wp_error($job_id)) {
                wp_send_json_error(['message' => $job_id->get_error_message()]);
            }
            
            wp_send_json_success([
                'queued'    => true,
                'job_id'    => $job_id,
                'total'     => count($post_ids),
                'message'   => sprintf(
                    __('Queued %d posts for background processing. Check the Job Queue page for progress.', 'smart-seo-fixer'),
                    count($post_ids)
                ),
            ]);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_seo_scores';
        $posts = $post_ids;
        
        $analyzer = class_exists('SSF_Analyzer') ? new SSF_Analyzer() : null;
        $log = [];
        
        foreach ($posts as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                $log[] = sprintf('⚠️ Post #%d - Not found, skipped', $post_id);
                continue;
            }
            
            // Clean content for AI: strip shortcodes + HTML tags
            $clean_content = wp_strip_all_tags(strip_shortcodes($post->post_content));
            if (str_word_count($clean_content) < 10) {
                $log[] = sprintf('⏭️ %s - Skipped (content too short for AI)', esc_html($post->post_title));
                continue;
            }
            
            $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
            $generated = [];
            $errors = [];
            
            // Generate keywords first (used for better title/desc generation)
            if ($generate_keywords) {
                $current_kw = trim(get_post_meta($post_id, '_ssf_focus_keyword', true));
                if ($apply_to === 'all' || empty($current_kw)) {
                    $keywords = $openai->suggest_keywords($post->post_content, $post->post_title);
                    if (!is_wp_error($keywords) && is_array($keywords) && !empty($keywords['primary'])) {
                        $kw = sanitize_text_field(trim($keywords['primary']));
                        if (!empty($kw)) {
                            update_post_meta($post_id, '_ssf_focus_keyword', $kw);
                            $focus_keyword = $kw;
                            $generated[] = 'keyword';
                        }
                    } elseif (is_wp_error($keywords)) {
                        $errors[] = 'keyword: ' . $keywords->get_error_message();
                    }
                }
            }
            
            // Generate title
            if ($generate_title) {
                $current_title = trim(get_post_meta($post_id, '_ssf_seo_title', true));
                if ($apply_to === 'all' || empty($current_title)) {
                    $title = $openai->generate_title($post->post_content, $post->post_title, $focus_keyword);
                    if (!is_wp_error($title) && !empty(trim($title))) {
                        $clean_title = sanitize_text_field(trim($title));
                        if (!empty($clean_title)) {
                            update_post_meta($post_id, '_ssf_seo_title', $clean_title);
                            $generated[] = 'title';
                        }
                    } elseif (is_wp_error($title)) {
                        $errors[] = 'title: ' . $title->get_error_message();
                    }
                }
            }
            
            // Generate description
            if ($generate_desc) {
                $current_desc = trim(get_post_meta($post_id, '_ssf_meta_description', true));
                if ($apply_to === 'all' || empty($current_desc)) {
                    $desc = $openai->generate_meta_description($post->post_content, '', $focus_keyword);
                    if (!is_wp_error($desc) && !empty(trim($desc))) {
                        $clean_desc = sanitize_textarea_field(trim($desc));
                        if (!empty($clean_desc)) {
                            update_post_meta($post_id, '_ssf_meta_description', $clean_desc);
                            $generated[] = 'description';
                        }
                    } elseif (is_wp_error($desc)) {
                        $errors[] = 'desc: ' . $desc->get_error_message();
                    }
                }
            }
            
            // Re-analyze
            $score = 0;
            if ($analyzer) {
                $analysis = $analyzer->analyze_post($post_id);
                $score = $analysis['score'] ?? 0;
            }
            
            if (!empty($generated)) {
                $log[] = sprintf('✅ %s — Generated: %s (Score: %d)', 
                    esc_html($post->post_title), 
                    implode(', ', $generated),
                    $score
                );
            } elseif (!empty($errors)) {
                $log[] = sprintf('❌ %s — API error: %s', 
                    esc_html($post->post_title),
                    implode('; ', $errors)
                );
            } else {
                $log[] = sprintf('⏭️ %s — Skipped (already has SEO data)', esc_html($post->post_title));
            }
        }
        
        wp_send_json_success([
            'processed' => count($posts),
            'log' => $log,
        ]);
    }
    
    /**
     * AI fix single post with options
     */
    public function ai_fix_single() {
        $this->verify_nonce();
        if (class_exists('SSF_History')) SSF_History::set_source('ai');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $options = $_POST['options'] ?? [];
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $generate_title = !empty($options['generate_title']);
        $generate_desc = !empty($options['generate_desc']);
        $generate_keywords = !empty($options['generate_keywords']);
        $overwrite = !empty($options['overwrite']);
        
        $openai = SSF_AI::get();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
        $generated = [];
        
        // Generate keywords first if requested (so it can be used for title/desc)
        if ($generate_keywords) {
            $current_kw = get_post_meta($post_id, '_ssf_focus_keyword', true);
            if ($overwrite || empty($current_kw)) {
                $keywords = $openai->suggest_keywords($post->post_content, $post->post_title);
                if (!is_wp_error($keywords) && !empty($keywords['primary'])) {
                    update_post_meta($post_id, '_ssf_focus_keyword', sanitize_text_field($keywords['primary']));
                    $focus_keyword = $keywords['primary']; // Use for title/desc generation
                    $generated[] = 'keyword';
                }
            }
        }
        
        // Generate title
        if ($generate_title) {
            $current_title = get_post_meta($post_id, '_ssf_seo_title', true);
            if ($overwrite || empty($current_title)) {
                $title = $openai->generate_title($post->post_content, $post->post_title, $focus_keyword);
                if (!is_wp_error($title) && !empty(trim($title))) {
                    update_post_meta($post_id, '_ssf_seo_title', sanitize_text_field(trim($title)));
                    $generated[] = 'title';
                }
            }
        }
        
        // Generate description
        if ($generate_desc) {
            $current_desc = get_post_meta($post_id, '_ssf_meta_description', true);
            if ($overwrite || empty($current_desc)) {
                $desc = $openai->generate_meta_description($post->post_content, $current_desc, $focus_keyword);
                if (!is_wp_error($desc) && !empty(trim($desc))) {
                    update_post_meta($post_id, '_ssf_meta_description', sanitize_textarea_field(trim($desc)));
                    $generated[] = 'description';
                }
            }
        }
        
        // Re-analyze
        $analyzer = new SSF_Analyzer();
        $analysis = $analyzer->analyze_post($post_id);
        
        if (empty($generated)) {
            wp_send_json_success([
                'title' => $post->post_title,
                'message' => __('Skipped (already has content)', 'smart-seo-fixer'),
                'score' => $analysis['score'] ?? 0,
            ]);
        }
        
        wp_send_json_success([
            'title' => $post->post_title,
            'message' => sprintf(__('Generated: %s (Score: %d)', 'smart-seo-fixer'), implode(', ', $generated), $analysis['score'] ?? 0),
            'generated' => $generated,
            'score' => $analysis['score'] ?? 0,
        ]);
    }
    
    /**
     * Toggle local business schema on a specific post/page
     */
    public function toggle_local_schema() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        // Check if local SEO is configured
        $local_seo = new SSF_Local_SEO();
        $settings = $local_seo->get_settings();
        
        if (!$settings['enabled']) {
            wp_send_json_error(['message' => __('Local SEO is not enabled. Please configure it in the Local SEO settings first.', 'smart-seo-fixer')]);
        }
        
        if (empty($settings['business_name']) && empty($settings['address']['street'])) {
            wp_send_json_error(['message' => __('No business information configured. Please fill in your business details in Local SEO settings.', 'smart-seo-fixer')]);
        }
        
        // Toggle the flag
        $current = get_post_meta($post_id, '_ssf_include_local_schema', true);
        $new_value = empty($current) ? 1 : 0;
        update_post_meta($post_id, '_ssf_include_local_schema', $new_value);
        
        if ($new_value) {
            wp_send_json_success([
                'enabled' => true,
                'message' => __('Local Business schema enabled for this page. It will appear in the page source.', 'smart-seo-fixer'),
                'business_name' => $settings['business_name'],
                'business_type' => $settings['business_type'],
            ]);
        } else {
            wp_send_json_success([
                'enabled' => false,
                'message' => __('Local Business schema removed from this page.', 'smart-seo-fixer'),
            ]);
        }
    }
    
    /**
     * Generate content outline using AI
     */
    public function generate_outline() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
        $topic = !empty($post->post_title) ? $post->post_title : $focus_keyword;
        
        if (empty($topic)) {
            wp_send_json_error(['message' => __('Please add a title or focus keyword first.', 'smart-seo-fixer')]);
        }
        
        $result = $openai->generate_outline($topic, $focus_keyword);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        if (!is_array($result)) {
            wp_send_json_error(['message' => __('Could not parse outline response. Please try again.', 'smart-seo-fixer')]);
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Improve content readability using AI
     */
    public function improve_readability() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        if (empty(trim($post->post_content))) {
            wp_send_json_error(['message' => __('Post has no content to improve.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        // Send trimmed content to avoid token limits
        $content = wp_trim_words(wp_strip_all_tags($post->post_content), 1000);
        $result = $openai->improve_readability($content);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        if (empty(trim($result))) {
            wp_send_json_error(['message' => __('AI returned empty content. Please try again.', 'smart-seo-fixer')]);
        }
        
        wp_send_json_success(['improved_content' => trim($result)]);
    }
    
    /**
     * Suggest schema markup using AI
     */
    public function suggest_schema() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $action_type = sanitize_text_field($_POST['schema_action'] ?? 'generate');
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        // Handle save/remove actions
        if ($action_type === 'save') {
            $schema_json = wp_unslash($_POST['schema_json'] ?? '');
            // Validate it's real JSON
            $decoded = json_decode($schema_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => __('Invalid JSON schema.', 'smart-seo-fixer')]);
            }
            update_post_meta($post_id, '_ssf_custom_schema', $schema_json);
            wp_send_json_success(['message' => __('Schema saved and will appear on the frontend automatically.', 'smart-seo-fixer')]);
        }
        
        if ($action_type === 'remove') {
            delete_post_meta($post_id, '_ssf_custom_schema');
            wp_send_json_success(['message' => __('Custom schema removed from this post.', 'smart-seo-fixer')]);
        }
        
        // Generate new schema
        $openai = SSF_AI::get();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        // Gather real site data so AI doesn't make up URLs
        $logo_url = '';
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_url($custom_logo_id);
        }
        if (empty($logo_url)) {
            $logo_url = get_site_icon_url();
        }
        
        $result = $openai->suggest_schema(
            $post->post_content,
            $post->post_type,
            get_permalink($post_id),
            home_url('/'),
            $post->post_title,
            get_bloginfo('name'),
            $logo_url
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        if (empty(trim($result))) {
            wp_send_json_error(['message' => __('Could not generate schema suggestion.', 'smart-seo-fixer')]);
        }
        
        // Clean JSON response
        $clean = preg_replace('/```json\s*/', '', $result);
        $clean = preg_replace('/```\s*/', '', $clean);
        $clean = trim($clean);
        
        // Validate it's real JSON
        $decoded = json_decode($clean, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => __('AI returned invalid JSON. Please try again.', 'smart-seo-fixer')]);
        }
        
        // Check if AI says no additional schema needed
        if (!empty($decoded['_no_schema'])) {
            wp_send_json_success([
                'no_schema' => true,
                'message' => __('This post already has the right schema types (Article, Breadcrumb, etc.) generated automatically. No additional schema markup is needed.', 'smart-seo-fixer'),
            ]);
        }
        
        // Sanitize: replace any fake/guessed logo URLs with the real one
        if (!empty($logo_url)) {
            $clean_json = json_encode($decoded);
            // Fix common AI hallucinations: /logo.png, example.com, site.com/logo etc.
            $clean_json = preg_replace(
                '#https?://[^"]*?/logo\.(png|jpg|jpeg|svg|webp)#i',
                $logo_url,
                $clean_json
            );
            $clean_json = preg_replace(
                '#https?://example\.com[^"]*#i',
                home_url('/'),
                $clean_json
            );
            $decoded = json_decode($clean_json, true);
            $clean = wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
        
        // Reject if AI returned a duplicate type we already generate
        $auto_types = ['Article', 'BlogPosting', 'NewsArticle', 'WebPage', 'BreadcrumbList', 'Organization', 'WebSite'];
        if (!empty($decoded['@type']) && in_array($decoded['@type'], $auto_types)) {
            wp_send_json_error([
                'message' => sprintf(
                    __('The AI suggested "%s" schema, but this is already generated automatically by the plugin. No additional schema needed for this content.', 'smart-seo-fixer'),
                    $decoded['@type']
                ),
            ]);
        }
        
        // Check if post already has custom schema
        $existing = get_post_meta($post_id, '_ssf_custom_schema', true);
        
        wp_send_json_success([
            'schema' => $clean,
            'has_existing' => !empty($existing),
        ]);
    }
    
    /**
     * Bulk regenerate custom schemas
     * Processes posts in batches, re-running AI schema generation for each
     */
    public function bulk_regenerate_schemas() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $offset = intval($_POST['offset'] ?? 0);
        $batch_size = intval($_POST['batch_size'] ?? 3);
        $mode = sanitize_text_field($_POST['mode'] ?? 'regenerate'); // 'regenerate' or 'remove'
        
        // Get all posts that have custom schema
        $posts_with_schema = get_posts([
            'post_type' => 'any',
            'post_status' => 'publish',
            'meta_key' => '_ssf_custom_schema',
            'meta_compare' => '!=',
            'meta_value' => '',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        
        $total = count($posts_with_schema);
        
        if ($total === 0) {
            wp_send_json_success([
                'done' => true,
                'processed' => 0,
                'total' => 0,
                'log' => [__('No posts with custom schemas found.', 'smart-seo-fixer')],
            ]);
        }
        
        // Handle remove mode
        if ($mode === 'remove') {
            foreach ($posts_with_schema as $pid) {
                delete_post_meta($pid, '_ssf_custom_schema');
            }
            wp_send_json_success([
                'done' => true,
                'processed' => $total,
                'total' => $total,
                'log' => [sprintf(__('Removed custom schemas from %d posts.', 'smart-seo-fixer'), $total)],
            ]);
        }
        
        // Get batch slice
        $batch = array_slice($posts_with_schema, $offset, $batch_size);
        
        if (empty($batch)) {
            wp_send_json_success([
                'done' => true,
                'processed' => $offset,
                'total' => $total,
                'log' => [__('All custom schemas regenerated!', 'smart-seo-fixer')],
            ]);
        }
        
        $openai = SSF_AI::get();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        // Real site data for the AI
        $logo_url = '';
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_url($custom_logo_id);
        }
        if (empty($logo_url)) {
            $logo_url = get_site_icon_url();
        }
        $site_name = get_bloginfo('name');
        $site_url = home_url('/');
        
        $log = [];
        $processed = 0;
        
        foreach ($batch as $post_id) {
            $post = get_post($post_id);
            if (!$post) continue;
            
            $result = $openai->suggest_schema(
                $post->post_content,
                $post->post_type,
                get_permalink($post_id),
                $site_url,
                $post->post_title,
                $site_name,
                $logo_url
            );
            
            if (is_wp_error($result)) {
                $log[] = '❌ ' . $post->post_title . ': ' . $result->get_error_message();
                $processed++;
                continue;
            }
            
            // Clean response
            $clean = preg_replace('/```json\s*/', '', $result);
            $clean = preg_replace('/```\s*/', '', $clean);
            $clean = trim($clean);
            
            $decoded = json_decode($clean, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $log[] = '❌ ' . $post->post_title . ': ' . __('Invalid JSON response', 'smart-seo-fixer');
                $processed++;
                continue;
            }
            
            // If AI says no schema needed, remove it
            if (!empty($decoded['_no_schema'])) {
                delete_post_meta($post_id, '_ssf_custom_schema');
                $log[] = '🗑️ ' . $post->post_title . ': ' . __('No additional schema needed — removed', 'smart-seo-fixer');
                $processed++;
                continue;
            }
            
            // Reject duplicate types
            $auto_types = ['Article', 'BlogPosting', 'NewsArticle', 'WebPage', 'BreadcrumbList', 'Organization', 'WebSite'];
            if (!empty($decoded['@type']) && in_array($decoded['@type'], $auto_types)) {
                delete_post_meta($post_id, '_ssf_custom_schema');
                $log[] = '🗑️ ' . $post->post_title . ': ' . sprintf(__('Duplicate %s removed', 'smart-seo-fixer'), $decoded['@type']);
                $processed++;
                continue;
            }
            
            // Sanitize fake URLs
            if (!empty($logo_url)) {
                $clean_json = json_encode($decoded);
                $clean_json = preg_replace('#https?://[^"]*?/logo\.(png|jpg|jpeg|svg|webp)#i', $logo_url, $clean_json);
                $clean_json = preg_replace('#https?://example\.com[^"]*#i', $site_url, $clean_json);
                $decoded = json_decode($clean_json, true);
                $clean = wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
            
            // Save
            update_post_meta($post_id, '_ssf_custom_schema', $clean);
            $log[] = '✅ ' . $post->post_title . ': ' . ($decoded['@type'] ?? 'Schema') . ' ' . __('regenerated', 'smart-seo-fixer');
            $processed++;
        }
        
        wp_send_json_success([
            'done' => ($offset + $processed) >= $total,
            'processed' => $processed,
            'total' => $total,
            'log' => $log,
        ]);
    }
    
    /**
     * Toggle a single boolean setting safely
     */
    public function toggle_setting() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $key = sanitize_text_field($_POST['setting_key'] ?? '');
        $value = !empty($_POST['setting_value']) ? 1 : 0;
        
        // Only allow toggling known boolean settings
        $allowed = ['enable_schema', 'enable_sitemap', 'auto_meta', 'auto_alt_text', 'disable_other_seo_output'];
        
        if (!in_array($key, $allowed)) {
            wp_send_json_error(['message' => __('Invalid setting.', 'smart-seo-fixer')]);
        }
        
        Smart_SEO_Fixer::update_option($key, $value);
        
        wp_send_json_success(['message' => __('Setting saved.', 'smart-seo-fixer')]);
    }
    
    /**
     * Get list of all posts with custom schemas
     */
    public function get_schema_list() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $posts_with_schema = get_posts([
            'post_type'      => 'any',
            'post_status'    => 'publish',
            'meta_key'       => '_ssf_custom_schema',
            'meta_compare'   => '!=',
            'meta_value'     => '',
            'posts_per_page' => -1,
        ]);
        
        $items = [];
        foreach ($posts_with_schema as $post) {
            $schema_raw = get_post_meta($post->ID, '_ssf_custom_schema', true);
            $decoded = json_decode($schema_raw, true);
            $schema_type = $decoded['@type'] ?? __('Unknown', 'smart-seo-fixer');
            
            $items[] = [
                'id'          => $post->ID,
                'title'       => $post->post_title,
                'post_type'   => get_post_type_object($post->post_type)->labels->singular_name ?? $post->post_type,
                'edit_url'    => get_edit_post_link($post->ID, 'raw'),
                'view_url'    => get_permalink($post->ID),
                'schema_type' => $schema_type,
                'schema_json' => $schema_raw,
            ];
        }
        
        wp_send_json_success(['items' => $items, 'total' => count($items)]);
    }
    
    /**
     * Delete a single post's custom schema
     */
    public function delete_single_schema() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        delete_post_meta($post_id, '_ssf_custom_schema');
        
        wp_send_json_success(['message' => __('Schema removed.', 'smart-seo-fixer')]);
    }
    
    /**
     * Regenerate schema for a single post
     */
    public function regenerate_single_schema() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        $logo_url = '';
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_url($custom_logo_id);
        }
        if (empty($logo_url)) {
            $logo_url = get_site_icon_url();
        }
        
        $result = $openai->suggest_schema(
            $post->post_content,
            $post->post_type,
            get_permalink($post_id),
            home_url('/'),
            $post->post_title,
            get_bloginfo('name'),
            $logo_url
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        // Clean JSON response
        $clean = preg_replace('/```json\s*/', '', $result);
        $clean = preg_replace('/```\s*/', '', $clean);
        $clean = trim($clean);
        
        $decoded = json_decode($clean, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => __('AI returned invalid JSON. Please try again.', 'smart-seo-fixer')]);
        }
        
        // If AI says no schema needed
        if (!empty($decoded['_no_schema'])) {
            delete_post_meta($post_id, '_ssf_custom_schema');
            wp_send_json_success([
                'removed' => true,
                'message' => __('No additional schema needed — custom schema removed.', 'smart-seo-fixer'),
            ]);
            return;
        }
        
        // Reject duplicates
        $auto_types = ['Article', 'BlogPosting', 'NewsArticle', 'WebPage', 'BreadcrumbList', 'Organization', 'WebSite'];
        if (!empty($decoded['@type']) && in_array($decoded['@type'], $auto_types)) {
            delete_post_meta($post_id, '_ssf_custom_schema');
            wp_send_json_success([
                'removed' => true,
                'message' => sprintf(__('"%s" is already auto-generated — custom schema removed.', 'smart-seo-fixer'), $decoded['@type']),
            ]);
            return;
        }
        
        // Sanitize URLs
        if (!empty($logo_url)) {
            $clean_json = json_encode($decoded);
            $clean_json = preg_replace('#https?://[^"]*?/logo\.(png|jpg|jpeg|svg|webp)#i', $logo_url, $clean_json);
            $clean_json = preg_replace('#https?://example\.com[^"]*#i', home_url('/'), $clean_json);
            $decoded = json_decode($clean_json, true);
            $clean = wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
        
        update_post_meta($post_id, '_ssf_custom_schema', $clean);
        
        $schema_type = $decoded['@type'] ?? __('Schema', 'smart-seo-fixer');
        
        wp_send_json_success([
            'message'     => sprintf(__('%s schema regenerated successfully.', 'smart-seo-fixer'), $schema_type),
            'schema_type' => $schema_type,
            'schema_json' => $clean,
        ]);
    }
    
    /**
     * Generate schema for a post that doesn't have one yet
     */
    public function generate_schema_for_post() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        // Check if already has custom schema
        $existing = get_post_meta($post_id, '_ssf_custom_schema', true);
        if (!empty($existing)) {
            wp_send_json_error(['message' => __('This post already has a custom schema. Use Regenerate instead.', 'smart-seo-fixer')]);
        }
        
        $openai = SSF_AI::get();
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => SSF_AI::not_configured_message()]);
        }
        
        $logo_url = '';
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_url($custom_logo_id);
        }
        if (empty($logo_url)) {
            $logo_url = get_site_icon_url();
        }
        
        $result = $openai->suggest_schema(
            $post->post_content,
            $post->post_type,
            get_permalink($post_id),
            home_url('/'),
            $post->post_title,
            get_bloginfo('name'),
            $logo_url
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        $clean = preg_replace('/```json\s*/', '', $result);
        $clean = preg_replace('/```\s*/', '', $clean);
        $clean = trim($clean);
        
        $decoded = json_decode($clean, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => __('AI returned invalid JSON. Please try again.', 'smart-seo-fixer')]);
        }
        
        if (!empty($decoded['_no_schema'])) {
            wp_send_json_success([
                'no_schema' => true,
                'message'   => __('AI determined no additional schema is needed for this content.', 'smart-seo-fixer'),
            ]);
            return;
        }
        
        $auto_types = ['Article', 'BlogPosting', 'NewsArticle', 'WebPage', 'BreadcrumbList', 'Organization', 'WebSite'];
        if (!empty($decoded['@type']) && in_array($decoded['@type'], $auto_types)) {
            wp_send_json_success([
                'no_schema' => true,
                'message'   => sprintf(__('"%s" is already auto-generated. No custom schema needed.', 'smart-seo-fixer'), $decoded['@type']),
            ]);
            return;
        }
        
        // Sanitize URLs
        if (!empty($logo_url)) {
            $clean_json = json_encode($decoded);
            $clean_json = preg_replace('#https?://[^"]*?/logo\.(png|jpg|jpeg|svg|webp)#i', $logo_url, $clean_json);
            $clean_json = preg_replace('#https?://example\.com[^"]*#i', home_url('/'), $clean_json);
            $decoded = json_decode($clean_json, true);
            $clean = wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
        
        update_post_meta($post_id, '_ssf_custom_schema', $clean);
        
        wp_send_json_success([
            'message'     => sprintf(__('%s schema generated and saved.', 'smart-seo-fixer'), $decoded['@type'] ?? 'Custom'),
            'schema_type' => $decoded['@type'] ?? 'Custom',
            'schema_json' => $clean,
            'post_id'     => $post_id,
        ]);
    }
    
    /**
     * Search posts for adding schema (AJAX autocomplete)
     */
    public function search_posts_for_schema() {
        $this->verify_nonce();
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        if (strlen($search) < 2) {
            wp_send_json_success([]);
        }
        
        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        
        $posts = get_posts([
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            's'              => $search,
            'posts_per_page' => 10,
        ]);
        
        $results = [];
        foreach ($posts as $post) {
            $has_schema = !empty(get_post_meta($post->ID, '_ssf_custom_schema', true));
            $results[] = [
                'id'         => $post->ID,
                'title'      => $post->post_title,
                'post_type'  => get_post_type_object($post->post_type)->labels->singular_name ?? $post->post_type,
                'has_schema' => $has_schema,
            ];
        }
        
        wp_send_json_success($results);
    }
    
    // ========================================================
    // Google Search Console AJAX Handlers
    // ========================================================
    
    /**
     * Disconnect from Google Search Console
     */
    public function gsc_refresh_sites() {
        $this->verify_nonce();

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }

        if (!class_exists('SSF_GSC_Client')) {
            wp_send_json_error(['message' => __('GSC module not available.', 'smart-seo-fixer')]);
        }

        try {
            $gsc = new SSF_GSC_Client();
            if (!$gsc->is_connected()) {
                wp_send_json_error(['message' => __('Not connected to GSC. Please connect first.', 'smart-seo-fixer')]);
            }

            $sites = $gsc->get_sites();
            if (is_wp_error($sites)) {
                wp_send_json_error(['message' => $sites->get_error_message()]);
            }

            if (empty($sites)) {
                wp_send_json_error(['message' => __('No site properties found in your GSC account. Make sure your site is verified.', 'smart-seo-fixer')]);
            }

            set_transient('ssf_gsc_sites_cache', $sites, DAY_IN_SECONDS);
            wp_send_json_success(['sites' => $sites]);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function gsc_disconnect() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_GSC_Client')) {
            wp_send_json_error(['message' => __('GSC module not available.', 'smart-seo-fixer')]);
        }
        
        $gsc = new SSF_GSC_Client();
        $gsc->disconnect();
        
        wp_send_json_success(['message' => __('Disconnected from Google Search Console.', 'smart-seo-fixer')]);
    }
    
    /**
     * Get search performance data
     */
    public function gsc_performance() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_GSC_Client')) {
            wp_send_json_error(['message' => __('GSC module not available.', 'smart-seo-fixer')]);
        }
        
        $gsc = new SSF_GSC_Client();
        
        if (!$gsc->is_connected()) {
            wp_send_json_error(['message' => __('Not connected to Google Search Console.', 'smart-seo-fixer')]);
        }
        
        $days = intval($_POST['days'] ?? 28);
        $type = sanitize_text_field($_POST['type'] ?? 'overview');
        
        // Use transient caching (1 hour)
        $cache_key = "ssf_gsc_{$type}_{$days}";
        $cached = get_transient($cache_key);
        if ($cached !== false && empty($_POST['refresh'])) {
            wp_send_json_success($cached);
        }
        
        switch ($type) {
            case 'overview':
                $data = $gsc->get_performance_overview($days);
                break;
            case 'queries':
                $data = $gsc->get_top_queries($days, 100);
                break;
            case 'pages':
                $data = $gsc->get_top_pages($days, 100);
                break;
            default:
                $data = new WP_Error('invalid_type', __('Invalid data type.', 'smart-seo-fixer'));
        }
        
        if (is_wp_error($data)) {
            wp_send_json_error(['message' => $data->get_error_message()]);
        }
        
        set_transient($cache_key, $data, HOUR_IN_SECONDS);
        
        wp_send_json_success($data);
    }
    
    /**
     * Inspect a URL for index status
     */
    public function gsc_inspect_url() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_GSC_Client')) {
            wp_send_json_error(['message' => __('GSC module not available.', 'smart-seo-fixer')]);
        }
        
        $gsc = new SSF_GSC_Client();
        $url = esc_url_raw($_POST['url'] ?? '');
        
        if (empty($url)) {
            wp_send_json_error(['message' => __('URL is required.', 'smart-seo-fixer')]);
        }
        
        $result = $gsc->inspect_url($url);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Submit sitemap to Google
     */
    public function gsc_submit_sitemap() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_GSC_Client')) {
            wp_send_json_error(['message' => __('GSC module not available.', 'smart-seo-fixer')]);
        }
        
        $gsc = new SSF_GSC_Client();
        $sitemap_url = home_url('/sitemap.xml');
        
        $result = $gsc->submit_sitemap($sitemap_url);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success(['message' => __('Sitemap submitted successfully!', 'smart-seo-fixer')]);
    }

    // =========================================================================
    // Broken Links
    // =========================================================================

    public function get_broken_links() {
        $this->verify_nonce();

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }

        if (!class_exists('SSF_Broken_Links')) {
            wp_send_json_error(['message' => __('Broken Links module not available.', 'smart-seo-fixer')]);
        }

        $result = SSF_Broken_Links::query([
            'page'      => intval($_POST['page'] ?? 1),
            'per_page'  => 20,
            'link_type' => sanitize_text_field($_POST['link_type'] ?? ''),
            'status'    => sanitize_text_field($_POST['status'] ?? 'active'),
            'search'    => sanitize_text_field($_POST['search'] ?? ''),
        ]);

        wp_send_json_success($result);
    }

    public function scan_broken_links() {
        $this->verify_nonce();

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }

        if (!class_exists('SSF_Broken_Links')) {
            wp_send_json_error(['message' => __('Broken Links module not available.', 'smart-seo-fixer')]);
        }

        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $posts = get_posts(['post_type' => $post_types, 'post_status' => 'publish', 'posts_per_page' => 50, 'fields' => 'ids']);

        $total_checked = 0;
        $total_broken  = 0;

        foreach ($posts as $post_id) {
            $r = SSF_Broken_Links::scan_post($post_id);
            $total_checked += $r['checked'];
            $total_broken  += $r['broken'];
        }

        wp_send_json_success([
            'checked' => $total_checked,
            'broken'  => $total_broken,
        ]);
    }

    public function recheck_broken_link() {
        $this->verify_nonce();

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }

        if (!class_exists('SSF_Broken_Links')) {
            wp_send_json_error(['message' => __('Broken Links module not available.', 'smart-seo-fixer')]);
        }

        $id     = absint($_POST['id'] ?? 0);
        $result = SSF_Broken_Links::recheck($id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    public function dismiss_broken_link() {
        $this->verify_nonce();

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }

        SSF_Broken_Links::dismiss(absint($_POST['id'] ?? 0));
        wp_send_json_success();
    }

    public function undismiss_broken_link() {
        $this->verify_nonce();

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }

        SSF_Broken_Links::undismiss(absint($_POST['id'] ?? 0));
        wp_send_json_success();
    }

    /**
     * Bulk-redirect broken links: replace the broken URL in each post's content
     * with the target URL specified by the user, then dismiss the records.
     */
    public function bulk_redirect_broken_links() {
        $this->verify_nonce();

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }

        $ids        = array_map('absint', (array) ($_POST['ids'] ?? []));
        $target_url = esc_url_raw(trim($_POST['target_url'] ?? ''));

        if (empty($ids)) {
            wp_send_json_error(['message' => __('No links selected.', 'smart-seo-fixer')]);
        }

        if (empty($target_url) || strpos($target_url, 'http') !== 0) {
            wp_send_json_error(['message' => __('Please provide a valid destination URL.', 'smart-seo-fixer')]);
        }

        global $wpdb;
        $table      = SSF_Broken_Links::table();
        $redirected = 0;
        $failed     = 0;

        foreach ($ids as $id) {
            $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
            if (!$record) {
                $failed++;
                continue;
            }

            $post = get_post(intval($record->post_id));
            if (!$post) {
                $failed++;
                continue;
            }

            // Replace broken URL in post content (simple string replace — handles plain text and href attributes)
            $new_content = str_replace($record->url, $target_url, $post->post_content);

            if ($new_content !== $post->post_content) {
                wp_update_post([
                    'ID'           => $post->ID,
                    'post_content' => $new_content,
                ]);
            }

            // Dismiss the broken link record (no longer needed)
            SSF_Broken_Links::dismiss($id);
            $redirected++;
        }

        /* translators: 1: number updated, 2: number skipped */
        $msg = sprintf(
            _n('%d link updated.', '%d links updated.', $redirected, 'smart-seo-fixer'),
            $redirected
        );
        if ($failed > 0) {
            $msg .= ' ' . sprintf(
                /* translators: %d: number skipped */
                _n('%d skipped (record not found).', '%d skipped (records not found).', $failed, 'smart-seo-fixer'),
                $failed
            );
        }

        wp_send_json_success(['message' => $msg, 'updated' => $redirected, 'failed' => $failed]);
    }

    /**
     * Bulk-dismiss broken links.
     */
    public function bulk_dismiss_broken_links() {
        $this->verify_nonce();

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }

        $ids = array_map('absint', (array) ($_POST['ids'] ?? []));

        foreach ($ids as $id) {
            SSF_Broken_Links::dismiss($id);
        }

        wp_send_json_success(['dismissed' => count($ids)]);
    }

    // =========================================================================
    // Canonical URL Fixer
    // =========================================================================

    /**
     * Normalize a canonical URL for storage:
     * - Fixes scheme to match site (http → https or vice-versa)
     * - Fixes www prefix to match site preference
     * - Normalises trailing slash to match WordPress permalink settings
     * - Returns empty string if the normalised value equals the post's own
     *   permalink (makes the stored meta redundant — plugin outputs it by default)
     */
    private function normalize_canonical_for_storage( $url, $post_id = 0 ) {
        if ( empty( $url ) ) return '';

        $site      = home_url('/');
        $site_p    = wp_parse_url( $site );
        $site_scheme = $site_p['scheme'] ?? 'https';
        $site_host   = $site_p['host']  ?? '';

        $p = wp_parse_url( $url );
        if ( empty( $p['host'] ) ) return $url; // relative — leave as-is

        $url_host = $p['host'];

        // 1. Correct scheme
        $p['scheme'] = $site_scheme;

        // 2. Correct www: if site host and url host differ only by www, align them
        $site_www = strpos( $site_host, 'www.' ) === 0;
        $url_www  = strpos( $url_host,  'www.' ) === 0;

        $site_bare = $site_www ? substr( $site_host, 4 ) : $site_host;
        $url_bare  = $url_www  ? substr( $url_host,  4 ) : $url_host;

        if ( $site_bare === $url_bare ) {
            // Same domain, just different www prefix — align to site preference
            $p['host'] = $site_host;
        }

        // 3. Rebuild URL
        $normalized  = $p['scheme'] . '://' . $p['host'];
        $normalized .= $p['path'] ?? '/';
        if ( !empty( $p['query'] )    ) $normalized .= '?' . $p['query'];
        if ( !empty( $p['fragment'] ) ) $normalized .= '#' . $p['fragment'];

        // 4. Normalise trailing slash to match WordPress permalink settings
        $permalink_structure = get_option( 'permalink_structure', '' );
        $wants_slash = !empty( $permalink_structure ) && substr( $permalink_structure, -1 ) === '/';
        // Only touch paths without a file extension
        $path_only = $p['path'] ?? '/';
        if ( !preg_match('/\.\w{2,5}$/', $path_only) ) {
            $normalized = $wants_slash ? trailingslashit( $normalized ) : untrailingslashit( $normalized );
        }

        // 5. If this now equals the post's own permalink, it's a redundant self-canonical — clear it
        if ( $post_id > 0 ) {
            $permalink = untrailingslashit( get_permalink( $post_id ) );
            if ( untrailingslashit( $normalized ) === $permalink ) {
                return '';
            }
        }

        return $normalized;
    }

    /**
     * Scan for canonical mismatches without fixing — returns a count + sample list.
     */
    public function scan_canonical_issues() {
        $this->verify_nonce();

        if ( !current_user_can('manage_options') ) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }

        global $wpdb;
        $post_types  = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title FROM {$wpdb->posts}
                 WHERE post_status = 'publish' AND post_type IN ($placeholders)
                 ORDER BY ID ASC",
                ...$post_types
            )
        );

        $issues  = [];
        $healthy = 0;

        foreach ( $posts as $post ) {
            $stored = get_post_meta( $post->ID, '_ssf_canonical_url', true );

            if ( empty($stored) ) {
                $healthy++;
                continue; // No custom canonical — plugin outputs self-canonical by default. Good.
            }

            $normalized = $this->normalize_canonical_for_storage( $stored, $post->ID );

            if ( $normalized !== $stored ) {
                // Something needs fixing
                $issues[] = [
                    'post_id'    => $post->ID,
                    'title'      => $post->post_title,
                    'url'        => get_permalink( $post->ID ),
                    'stored'     => $stored,
                    'normalized' => $normalized,
                    'action'     => empty($normalized) ? 'clear' : 'update',
                ];
            } else {
                $healthy++;
            }
        }

        wp_send_json_success([
            'issues'  => array_slice($issues, 0, 20), // preview up to 20
            'total'   => count($issues),
            'healthy' => $healthy,
        ]);
    }

    /**
     * Auto-fix all canonical mismatches:
     * - Wrong scheme (http when site is https)
     * - Wrong www prefix vs site preference
     * - Trailing-slash inconsistency
     * - Redundant self-canonicals (custom canonical = own permalink → clear it)
     */
    public function auto_fix_canonicals() {
        $this->verify_nonce();

        if ( !current_user_can('manage_options') ) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }

        global $wpdb;
        $post_types  = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_status = 'publish' AND post_type IN ($placeholders)
                 ORDER BY ID ASC",
                ...$post_types
            )
        );

        $cleared = 0;
        $updated = 0;
        $skipped = 0;

        foreach ( $posts as $post ) {
            $stored = get_post_meta( $post->ID, '_ssf_canonical_url', true );

            if ( empty($stored) ) {
                $skipped++;
                continue;
            }

            $normalized = $this->normalize_canonical_for_storage( $stored, $post->ID );

            if ( $normalized === $stored ) {
                $skipped++;
                continue; // Already correct or intentional cross-URL canonical
            }

            if ( empty($normalized) ) {
                // Redundant self-canonical — delete it
                delete_post_meta( $post->ID, '_ssf_canonical_url' );
                $cleared++;
            } else {
                // Update to normalised version
                update_post_meta( $post->ID, '_ssf_canonical_url', $normalized );
                $updated++;
            }
        }

        $total_fixed = $cleared + $updated;

        wp_send_json_success([
            'fixed'   => $total_fixed,
            'cleared' => $cleared,
            'updated' => $updated,
            'skipped' => $skipped,
            'message' => $total_fixed > 0
                ? sprintf(
                    /* translators: 1: cleared count, 2: updated count */
                    __('Fixed %1$d canonical issues: %2$d redundant self-canonicals removed, %3$d corrected for scheme/www.', 'smart-seo-fixer'),
                    $total_fixed, $cleared, $updated
                  )
                : __('No canonical issues found — all canonicals are already correct!', 'smart-seo-fixer'),
        ]);
    }
    
    // =========================================================================
    // New Feature Handlers
    // =========================================================================
    
    /**
     * Detect duplicate titles/descriptions across the whole site
     */
    public function detect_duplicates() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $analyzer = new SSF_Analyzer();
        $results = $analyzer->detect_duplicates();
        
        wp_send_json_success($results);
    }
    
    /**
     * Get Core Web Vitals data
     */
    public function get_cwv_data() {
        $this->verify_nonce();
        
        if (!class_exists('SSF_Performance')) {
            wp_send_json_error(['message' => __('Performance module not available.', 'smart-seo-fixer')]);
        }
        
        wp_send_json_success([
            'summary'  => SSF_Performance::get_cwv_data()['summary'],
            'by_page'  => SSF_Performance::get_cwv_by_page(10),
        ]);
    }
    
    /**
     * Insert AI-suggested internal links into post content.
     * 
     * Takes link suggestions and applies them by inserting <a> tags into the content.
     */
    public function insert_internal_links() {
        $this->verify_nonce();
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $links = isset($_POST['links']) ? $_POST['links'] : [];
        
        if (!$post_id || empty($links)) {
            wp_send_json_error(['message' => __('Missing post ID or links.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $content = $post->post_content;
        $inserted = 0;
        
        // Set history source
        if (class_exists('SSF_History')) {
            SSF_History::set_source('ai');
        }
        
        foreach ($links as $link) {
            $anchor = sanitize_text_field($link['anchor'] ?? '');
            $url    = esc_url($link['url'] ?? '');
            
            if (empty($anchor) || empty($url)) {
                continue;
            }
            
            // Only insert if the anchor text exists as plain text (not already linked)
            // Use word boundary matching to avoid partial word matches
            $pattern = '/(?<!["\'>])(' . preg_quote($anchor, '/') . ')(?![^<]*<\/a>)/i';
            
            if (preg_match($pattern, $content)) {
                // Replace only the first occurrence
                $replacement = '<a href="' . esc_url($url) . '">' . esc_html($anchor) . '</a>';
                $content = preg_replace($pattern, $replacement, $content, 1);
                $inserted++;
            }
        }
        
        if ($inserted > 0) {
            wp_update_post([
                'ID'           => $post_id,
                'post_content' => $content,
            ]);
            
            // Re-analyze to update score
            if (class_exists('SSF_Analyzer')) {
                (new SSF_Analyzer())->analyze_post($post_id);
            }
        }
        
        wp_send_json_success([
            'inserted' => $inserted,
            'total'    => count($links),
            'message'  => sprintf(
                __('Inserted %d of %d internal links.', 'smart-seo-fixer'),
                $inserted, count($links)
            ),
        ]);
    }
    
    /**
     * Apply selected items from a bulk fix preview.
     * 
     * The frontend sends an array of approved items (post_id + fields to apply).
     * Rejected items are simply not included. This allows per-item approve/reject.
     */
    public function apply_bulk_preview() {
        $this->verify_nonce();
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $items = isset($_POST['items']) ? $_POST['items'] : [];
        
        if (empty($items) || !is_array($items)) {
            wp_send_json_error(['message' => __('No items to apply.', 'smart-seo-fixer')]);
        }
        
        if (class_exists('SSF_History')) {
            SSF_History::set_source('bulk');
        }
        
        $applied = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($items as $item) {
            $post_id = intval($item['post_id'] ?? 0);
            if (!$post_id || !get_post($post_id)) {
                $skipped++;
                continue;
            }
            
            $changed = false;
            
            if (!empty($item['title'])) {
                update_post_meta($post_id, '_ssf_seo_title', sanitize_text_field($item['title']));
                $changed = true;
            }
            
            if (!empty($item['description'])) {
                update_post_meta($post_id, '_ssf_meta_description', sanitize_textarea_field($item['description']));
                $changed = true;
            }
            
            if (!empty($item['keyword'])) {
                update_post_meta($post_id, '_ssf_focus_keyword', sanitize_text_field($item['keyword']));
                $changed = true;
            }
            
            if ($changed) {
                $applied++;
                
                // Re-analyze
                if (class_exists('SSF_Analyzer')) {
                    (new SSF_Analyzer())->analyze_post($post_id);
                }
            } else {
                $skipped++;
            }
        }
        
        wp_send_json_success([
            'applied' => $applied,
            'skipped' => $skipped,
            'total'   => count($items),
            'message' => sprintf(
                __('Applied changes to %d posts, skipped %d.', 'smart-seo-fixer'),
                $applied, $skipped
            ),
        ]);
    }
    
    /**
     * Audit images in a post for SEO issues
     */
    public function audit_images() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_Image_SEO')) {
            wp_send_json_error(['message' => __('Image SEO module not available.', 'smart-seo-fixer')]);
        }
        
        $issues = SSF_Image_SEO::audit_post_images($post_id);
        
        wp_send_json_success([
            'post_id' => $post_id,
            'issues'  => $issues,
            'total'   => count($issues),
        ]);
    }
    
    /**
     * Get onboarding checklist status
     */
    public function get_onboarding_status() {
        $this->verify_nonce();
        
        if (get_option('ssf_onboarding_dismissed', false)) {
            wp_send_json_success(['dismissed' => true, 'items' => []]);
            return;
        }
        
        $openai = class_exists('SSF_AI') ? SSF_AI::get() : null;
        
        $items = [
            [
                'id'       => 'api_configured',
                'label'    => __('Configure AI provider (AWS Bedrock)', 'smart-seo-fixer'),
                'complete' => $openai && $openai->is_configured(),
                'link'     => admin_url('admin.php?page=smart-seo-fixer-settings'),
            ],
            [
                'id'       => 'first_analysis',
                'label'    => __('Analyze your first post', 'smart-seo-fixer'),
                'complete' => (bool) get_option('ssf_first_analysis_done', false),
                'link'     => admin_url('admin.php?page=smart-seo-fixer'),
            ],
            [
                'id'       => 'bulk_analyze',
                'label'    => __('Run bulk analysis on all posts', 'smart-seo-fixer'),
                'complete' => (bool) get_option('ssf_bulk_analyze_done', false),
                'link'     => admin_url('admin.php?page=smart-seo-fixer'),
            ],
            [
                'id'       => 'schema_enabled',
                'label'    => __('Enable schema markup', 'smart-seo-fixer'),
                'complete' => (bool) Smart_SEO_Fixer::get_option('enable_schema', false),
                'link'     => admin_url('admin.php?page=smart-seo-fixer-settings'),
            ],
            [
                'id'       => 'sitemap_enabled',
                'label'    => __('Enable XML sitemap', 'smart-seo-fixer'),
                'complete' => (bool) Smart_SEO_Fixer::get_option('enable_sitemap', false),
                'link'     => admin_url('admin.php?page=smart-seo-fixer-settings'),
            ],
            [
                'id'       => 'auto_meta',
                'label'    => __('Enable auto meta generation on publish', 'smart-seo-fixer'),
                'complete' => (bool) Smart_SEO_Fixer::get_option('auto_meta', false),
                'link'     => admin_url('admin.php?page=smart-seo-fixer-settings'),
            ],
            [
                'id'       => 'redirects_reviewed',
                'label'    => __('Check your redirects & 404 monitor', 'smart-seo-fixer'),
                'complete' => (bool) get_option('ssf_redirects_reviewed', false),
                'link'     => admin_url('admin.php?page=smart-seo-fixer-redirects'),
            ],
        ];
        
        $completed = count(array_filter($items, function($item) { return $item['complete']; }));
        
        wp_send_json_success([
            'dismissed'  => false,
            'items'      => $items,
            'completed'  => $completed,
            'total'      => count($items),
            'percentage' => round(($completed / count($items)) * 100),
        ]);
    }
    
    /**
     * Dismiss onboarding checklist
     */
    public function dismiss_onboarding() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        update_option('ssf_onboarding_dismissed', true);
        
        wp_send_json_success(['message' => __('Onboarding dismissed.', 'smart-seo-fixer')]);
    }
    
    // =========================================================================
    // 404 Monitor AJAX Handlers
    // =========================================================================
    
    /**
     * Get 404 logs with pagination and filters
     */
    public function get_404_logs() {
        $this->verify_nonce();
        
        if (!class_exists('SSF_404_Monitor')) {
            wp_send_json_error(['message' => __('404 Monitor is not available.', 'smart-seo-fixer')]);
        }
        
        $result = SSF_404_Monitor::query([
            'page'     => intval($_POST['page'] ?? 1),
            'per_page' => 20,
            'search'   => sanitize_text_field($_POST['search'] ?? ''),
            'status'   => sanitize_text_field($_POST['status'] ?? 'active'),
        ]);
        
        $items = [];
        foreach ($result['items'] as $row) {
            $items[] = [
                'id'            => intval($row->id),
                'url'           => $row->url,
                'hit_count'     => intval($row->hit_count),
                'referrer'      => $row->referrer,
                'last_hit'      => $row->last_hit,
                'redirected_to' => $row->redirected_to,
                'dismissed'     => intval($row->dismissed),
            ];
        }
        
        wp_send_json_success([
            'items' => $items,
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page'  => $result['page'],
        ]);
    }
    
    /**
     * Dismiss a 404 entry
     */
    public function dismiss_404() {
        $this->verify_nonce();
        
        if (!class_exists('SSF_404_Monitor')) {
            wp_send_json_error(['message' => __('404 Monitor is not available.', 'smart-seo-fixer')]);
        }
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            wp_send_json_error(['message' => __('Invalid ID.', 'smart-seo-fixer')]);
        }
        
        SSF_404_Monitor::dismiss($id);
        
        wp_send_json_success(['message' => __('Entry dismissed.', 'smart-seo-fixer')]);
    }
    
    /**
     * Create redirect from a 404 entry
     */
    public function create_404_redirect() {
        $this->verify_nonce();
        
        if (!class_exists('SSF_404_Monitor')) {
            wp_send_json_error(['message' => __('404 Monitor is not available.', 'smart-seo-fixer')]);
        }
        
        $id = intval($_POST['id'] ?? 0);
        $redirect_to = esc_url_raw($_POST['redirect_to'] ?? '');
        
        if (!$id || empty($redirect_to)) {
            wp_send_json_error(['message' => __('Both ID and redirect URL are required.', 'smart-seo-fixer')]);
        }
        
        $result = SSF_404_Monitor::create_redirect($id, $redirect_to);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success(['message' => __('Redirect created.', 'smart-seo-fixer')]);
    }
    
    /**
     * Clear all 404 logs
     */
    public function clear_404_logs() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_404_Monitor')) {
            wp_send_json_error(['message' => __('404 Monitor is not available.', 'smart-seo-fixer')]);
        }
        
        SSF_404_Monitor::clear_all();
        
        wp_send_json_success(['message' => __('All 404 logs cleared.', 'smart-seo-fixer')]);
    }
    
    // =========================================================================
    // GSC: Pages Not Indexed
    // =========================================================================
    
    /**
     * Scan for pages not appearing in Google Search
     */
    public function gsc_not_indexed() {
        $this->verify_nonce();
        
        if (!class_exists('SSF_GSC_Client')) {
            wp_send_json_error(['message' => __('Google Search Console is not available.', 'smart-seo-fixer')]);
        }
        
        $gsc = new SSF_GSC_Client();
        if (!$gsc->is_connected()) {
            wp_send_json_error(['message' => __('Google Search Console is not connected. Go to Settings to connect.', 'smart-seo-fixer')]);
        }
        
        // Get all published posts/pages
        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $published = get_posts([
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => 500,
            'fields'         => 'ids',
        ]);
        
        if (empty($published)) {
            wp_send_json_success([
                'total_published' => 0,
                'total_in_gsc'    => 0,
                'count'           => 0,
                'not_indexed'     => [],
            ]);
        }
        
        // Build URL list for all published posts
        $published_urls = [];
        foreach ($published as $post_id) {
            $url = get_permalink($post_id);
            if ($url) {
                $published_urls[$post_id] = $url;
            }
        }
        
        // Get all pages that have appeared in GSC (last 90 days)
        $gsc_result = $gsc->get_search_analytics([
            'startDate'  => date('Y-m-d', strtotime('-90 days')),
            'endDate'    => date('Y-m-d', strtotime('-1 day')),
            'dimensions' => ['page'],
            'rowLimit'   => 5000,
        ]);
        
        $gsc_urls = [];
        if (!is_wp_error($gsc_result) && !empty($gsc_result['rows'])) {
            foreach ($gsc_result['rows'] as $row) {
                $gsc_urls[] = rtrim($row['keys'][0] ?? '', '/');
            }
        }
        
        // Compare: find published pages NOT in GSC
        $not_indexed = [];
        foreach ($published_urls as $post_id => $url) {
            $url_normalized = rtrim($url, '/');
            $found = false;
            foreach ($gsc_urls as $gsc_url) {
                if (strcasecmp($url_normalized, rtrim($gsc_url, '/')) === 0) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $post = get_post($post_id);
                $issues = [];
                
                // Check for common SEO issues
                $title = get_post_meta($post_id, '_ssf_seo_title', true);
                $desc  = get_post_meta($post_id, '_ssf_meta_description', true);
                
                if (empty($title) && empty($post->post_title)) {
                    $issues[] = 'missing_title';
                }
                if (empty($desc)) {
                    $issues[] = 'missing_description';
                }
                $noindex = get_post_meta($post_id, '_ssf_noindex', true);
                if ($noindex) {
                    $issues[] = 'noindex';
                }
                // Check internal links
                $content = $post->post_content ?? '';
                if (substr_count($content, '<a ') < 1) {
                    $issues[] = 'no_internal_links';
                }
                
                $not_indexed[] = [
                    'id'          => $post_id,
                    'title'       => get_the_title($post_id),
                    'url'         => $url,
                    'post_type'   => $post->post_type,
                    'issues'      => $issues,
                    'issue_count' => count($issues),
                    'status'      => count($issues) > 0 ? 'issues' : 'not_found',
                ];
            }
        }
        
        // Sort by issue count descending
        usort($not_indexed, function($a, $b) {
            return $b['issue_count'] - $a['issue_count'];
        });
        
        wp_send_json_success([
            'total_published' => count($published_urls),
            'total_in_gsc'    => count($gsc_urls),
            'count'           => count($not_indexed),
            'not_indexed'     => $not_indexed,
        ]);
    }
    
    // =========================================================================
    // Keyword Tracker AJAX Handlers
    // =========================================================================
    
    /**
     * Get tracked keywords with pagination
     */
    public function get_tracked_keywords() {
        $this->verify_nonce();
        
        if (!class_exists('SSF_Keyword_Tracker')) {
            wp_send_json_error(['message' => __('Keyword Tracker is not available.', 'smart-seo-fixer')]);
        }
        
        $result = SSF_Keyword_Tracker::get_keywords([
            'page'     => intval($_POST['page'] ?? 1),
            'per_page' => 20,
            'days'     => intval($_POST['days'] ?? 30),
            'search'   => sanitize_text_field($_POST['search'] ?? ''),
        ]);
        
        wp_send_json_success([
            'items' => $result['items'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page'  => $result['page'],
        ]);
    }
    
    /**
     * Get keyword position history for a specific keyword
     */
    public function get_keyword_history() {
        $this->verify_nonce();
        
        if (!class_exists('SSF_Keyword_Tracker')) {
            wp_send_json_error(['message' => __('Keyword Tracker is not available.', 'smart-seo-fixer')]);
        }
        
        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        $days    = intval($_POST['days'] ?? 30);
        
        if (empty($keyword)) {
            wp_send_json_error(['message' => __('No keyword specified.', 'smart-seo-fixer')]);
        }
        
        $history = SSF_Keyword_Tracker::get_keyword_history($keyword, $days);
        
        wp_send_json_success($history);
    }
    
    /**
     * Manually fetch keywords from GSC now
     */
    public function fetch_keywords_now() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_Keyword_Tracker')) {
            wp_send_json_error(['message' => __('Keyword Tracker is not available.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_GSC_Client')) {
            wp_send_json_error(['message' => __('Google Search Console client is not available.', 'smart-seo-fixer')]);
        }
        
        $gsc = new SSF_GSC_Client();
        if (!$gsc->is_connected()) {
            wp_send_json_error(['message' => __('Google Search Console is not connected. Go to Settings to connect first.', 'smart-seo-fixer')]);
        }
        
        // Run the tracking cron manually
        SSF_Keyword_Tracker::cron_track();
        
        $stats = SSF_Keyword_Tracker::get_stats();
        
        wp_send_json_success([
            'message'        => sprintf(__('Done! %d keywords tracked.', 'smart-seo-fixer'), $stats['total_keywords']),
            'total_keywords' => $stats['total_keywords'],
        ]);
    }
    
    // =========================================================================
    // Debug Log AJAX Handlers
    // =========================================================================
    
    /**
     * Get plugin logs with pagination and filtering
     */
    public function get_logs() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_Logger')) {
            wp_send_json_error(['message' => __('Logger is not available.', 'smart-seo-fixer')]);
        }
        
        $level    = sanitize_text_field($_POST['level'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        
        $result = SSF_Logger::query([
            'page'     => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 50),
            'level'    => !empty($level) ? $level : null,
            'category' => !empty($category) ? $category : null,
            'search'   => sanitize_text_field($_POST['search'] ?? ''),
        ]);
        
        $counts = SSF_Logger::get_counts();
        
        wp_send_json_success([
            'items'       => $result['items'],
            'total'       => $result['total'],
            'page'        => $result['page'],
            'total_pages' => $result['total_pages'],
            'counts'      => $counts,
        ]);
    }
    
    /**
     * Clear all debug logs
     */
    public function clear_logs() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_Logger')) {
            wp_send_json_error(['message' => __('Logger is not available.', 'smart-seo-fixer')]);
        }
        
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE " . SSF_Logger::table());
        
        wp_send_json_success(['message' => __('All logs cleared.', 'smart-seo-fixer')]);
    }
    
    // =========================================================================
    // Change History AJAX Handlers
    // =========================================================================
    
    /**
     * Get change history
     */
    public function get_history() {
        $this->verify_nonce();
        
        if (!class_exists('SSF_History')) {
            wp_send_json_error(['message' => __('History tracker is not available.', 'smart-seo-fixer')]);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_history';
        
        $page     = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 50);
        $offset   = ($page - 1) * $per_page;
        $search   = sanitize_text_field($_POST['search'] ?? '');
        
        $where = '1=1';
        $params = [];
        
        if (!empty($search)) {
            $where .= ' AND (meta_key LIKE %s OR old_value LIKE %s OR new_value LIKE %s)';
            $like = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        
        $total_sql = "SELECT COUNT(*) FROM $table WHERE $where";
        $total = !empty($params) ? $wpdb->get_var($wpdb->prepare($total_sql, ...$params)) : $wpdb->get_var($total_sql);
        
        $sql = "SELECT h.*, p.post_title FROM $table h LEFT JOIN {$wpdb->posts} p ON h.post_id = p.ID WHERE $where ORDER BY h.changed_at DESC LIMIT %d OFFSET %d";
        $query_params = array_merge($params, [$per_page, $offset]);
        $items = $wpdb->get_results($wpdb->prepare($sql, ...$query_params));
        
        wp_send_json_success([
            'items'       => $items ?: [],
            'total'       => intval($total),
            'page'        => $page,
            'total_pages' => ceil($total / $per_page),
        ]);
    }
    
    /**
     * Undo a change from history
     */
    public function undo_change() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $history_id = intval($_POST['history_id'] ?? 0);
        if (!$history_id) {
            wp_send_json_error(['message' => __('Invalid history ID.', 'smart-seo-fixer')]);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_history';
        $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $history_id));
        
        if (!$record) {
            wp_send_json_error(['message' => __('History record not found.', 'smart-seo-fixer')]);
        }
        
        // Restore old value
        update_post_meta($record->post_id, $record->meta_key, $record->old_value);
        
        wp_send_json_success(['message' => __('Change undone successfully.', 'smart-seo-fixer')]);
    }
    
    /**
     * Get history stats
     */
    public function get_history_stats() {
        $this->verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_history';
        
        $total   = intval($wpdb->get_var("SELECT COUNT(*) FROM $table"));
        $today   = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE DATE(changed_at) = %s", current_time('Y-m-d'))));
        $week    = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE changed_at >= %s", date('Y-m-d', strtotime('-7 days')))));
        
        wp_send_json_success([
            'total' => $total,
            'today' => $today,
            'week'  => $week,
        ]);
    }
    
    // =========================================================================
    // Job Queue AJAX Handlers
    // =========================================================================
    
    /**
     * Get job queue status summary
     */
    public function get_job_status() {
        $this->verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_jobs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            wp_send_json_success(['pending' => 0, 'running' => 0, 'completed' => 0, 'failed' => 0]);
        }
        
        $counts = $wpdb->get_results("SELECT status, COUNT(*) as cnt FROM $table GROUP BY status", OBJECT_K);
        
        wp_send_json_success([
            'pending'   => isset($counts['pending'])   ? intval($counts['pending']->cnt)   : 0,
            'running'   => isset($counts['running'])   ? intval($counts['running']->cnt)   : 0,
            'completed' => isset($counts['completed']) ? intval($counts['completed']->cnt) : 0,
            'failed'    => isset($counts['failed'])    ? intval($counts['failed']->cnt)    : 0,
        ]);
    }
    
    /**
     * Get jobs list
     */
    public function get_jobs() {
        $this->verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_jobs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            wp_send_json_success(['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 0]);
        }
        
        $page     = intval($_POST['page'] ?? 1);
        $per_page = 20;
        $offset   = ($page - 1) * $per_page;
        $status   = sanitize_text_field($_POST['status'] ?? '');
        
        $where = '1=1';
        $params = [];
        
        if (!empty($status)) {
            $where .= ' AND status = %s';
            $params[] = $status;
        }
        
        $total_sql = "SELECT COUNT(*) FROM $table WHERE $where";
        $total = !empty($params) ? $wpdb->get_var($wpdb->prepare($total_sql, ...$params)) : $wpdb->get_var($total_sql);
        
        $sql = "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $query_params = array_merge($params, [$per_page, $offset]);
        $items = $wpdb->get_results($wpdb->prepare($sql, ...$query_params));
        
        wp_send_json_success([
            'items'       => $items ?: [],
            'total'       => intval($total),
            'page'        => $page,
            'total_pages' => ceil($total / $per_page),
        ]);
    }
    
    /**
     * Cancel a queued job
     */
    public function cancel_job() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $job_id = intval($_POST['job_id'] ?? 0);
        if (!$job_id) {
            wp_send_json_error(['message' => __('Invalid job ID.', 'smart-seo-fixer')]);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_jobs';
        $wpdb->update($table, ['status' => 'cancelled'], ['id' => $job_id]);
        
        wp_send_json_success(['message' => __('Job cancelled.', 'smart-seo-fixer')]);
    }
    
    /**
     * Retry a failed job
     */
    public function retry_job() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $job_id = intval($_POST['job_id'] ?? 0);
        if (!$job_id) {
            wp_send_json_error(['message' => __('Invalid job ID.', 'smart-seo-fixer')]);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_jobs';
        $wpdb->update($table, ['status' => 'pending', 'attempts' => 0, 'error' => ''], ['id' => $job_id]);
        
        wp_send_json_success(['message' => __('Job requeued.', 'smart-seo-fixer')]);
    }
    
    // =========================================================================
    // Generic Background Job Dispatch & Polling
    // =========================================================================
    
    /**
     * Dispatch a background job via SSF_Job_Queue
     * 
     * Accepts: job_type, items (array of IDs), payload (optional config)
     * Returns: job_id on success for polling
     */
    public function dispatch_job() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_Job_Queue')) {
            wp_send_json_error(['message' => __('Job queue not available.', 'smart-seo-fixer')]);
        }
        
        $allowed_types = [
            'bulk_ai_fix', 'bulk_schema', 'orphan_fix_batch',
            'not_indexed_ai_fix', 'bulk_404_redirect',
        ];
        
        $job_type = sanitize_key($_POST['job_type'] ?? '');
        if (!in_array($job_type, $allowed_types, true)) {
            wp_send_json_error(['message' => __('Invalid job type.', 'smart-seo-fixer')]);
        }
        
        $items = isset($_POST['items']) ? array_values(array_filter(array_map('sanitize_text_field', (array) $_POST['items']))) : [];
        if (empty($items)) {
            wp_send_json_error(['message' => __('No items to process.', 'smart-seo-fixer')]);
        }
        
        $payload = [];
        if (isset($_POST['payload']) && is_array($_POST['payload'])) {
            foreach ($_POST['payload'] as $k => $v) {
                $payload[sanitize_key($k)] = sanitize_text_field($v);
            }
        }
        
        $job_id = SSF_Job_Queue::create($job_type, $items, $payload);
        
        if (is_wp_error($job_id)) {
            wp_send_json_error(['message' => $job_id->get_error_message()]);
        }
        
        wp_send_json_success([
            'job_id'  => $job_id,
            'total'   => count($items),
            'message' => sprintf(
                __('Job #%d created with %d items. Processing in background.', 'smart-seo-fixer'),
                $job_id, count($items)
            ),
        ]);
    }
    
    /**
     * Poll a specific job's progress
     * 
     * Accepts: job_id
     * Returns: status, processed, total, failed, percent, results (if completed)
     */
    public function poll_job() {
        $this->verify_nonce();
        
        $job_id = intval($_POST['job_id'] ?? 0);
        if (!$job_id) {
            wp_send_json_error(['message' => __('Invalid job ID.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_Job_Queue')) {
            wp_send_json_error(['message' => __('Job queue not available.', 'smart-seo-fixer')]);
        }
        
        $job = SSF_Job_Queue::get($job_id);
        if (!$job) {
            wp_send_json_error(['message' => __('Job not found.', 'smart-seo-fixer')]);
        }
        
        // Actively process the queue if job is still pending/processing
        // This ensures progress even if WP Cron is delayed or disabled
        if (in_array($job->status, ['pending', 'processing'])) {
            SSF_Job_Queue::process_queue();
            // Re-fetch after processing
            $job = SSF_Job_Queue::get($job_id);
        }
        
        $total     = intval($job->total_items);
        $processed = intval($job->processed_items);
        $failed    = intval($job->failed_items);
        $percent   = $total > 0 ? round(($processed / $total) * 100) : 0;
        
        $data = [
            'job_id'    => intval($job->id),
            'status'    => $job->status,
            'total'     => $total,
            'processed' => $processed,
            'failed'    => $failed,
            'percent'   => $percent,
        ];
        
        if (in_array($job->status, ['completed', 'failed', 'cancelled'])) {
            $data['results'] = $job->results;
            $data['error_message'] = $job->error_message;
        }
        
        wp_send_json_success($data);
    }
    
    // =========================================================================
    // Miscellaneous Missing Handlers
    // =========================================================================
    
    /**
     * Save robots.txt content
     */
    public function save_robots() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $content = sanitize_textarea_field($_POST['robots_content'] ?? '');
        Smart_SEO_Fixer::update_option('robots_txt', $content);
        
        if (class_exists('SSF_Logger')) {
            SSF_Logger::info('robots.txt content updated', 'general');
        }
        
        wp_send_json_success(['message' => __('robots.txt saved.', 'smart-seo-fixer')]);
    }
    
    /**
     * Analyze readability of a post
     */
    public function analyze_readability() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        if (class_exists('SSF_Readability')) {
            $readability = new SSF_Readability();
            $result = $readability->analyze($post->post_content);
            wp_send_json_success($result);
        }
        
        wp_send_json_error(['message' => __('Readability analyzer is not available.', 'smart-seo-fixer')]);
    }
    
    /**
     * Save social preview data
     */
    public function save_social_data() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        $fields = ['og_title', 'og_description', 'og_image', 'twitter_title', 'twitter_description', 'twitter_image'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_ssf_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        wp_send_json_success(['message' => __('Social data saved.', 'smart-seo-fixer')]);
    }
    
    /**
     * Get social preview data for a post
     */
    public function get_social_data() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        $data = [];
        $fields = ['og_title', 'og_description', 'og_image', 'twitter_title', 'twitter_description', 'twitter_image'];
        foreach ($fields as $field) {
            $data[$field] = get_post_meta($post_id, '_ssf_' . $field, true);
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get content suggestions for a post
     */
    public function content_suggestions() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid post ID.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_Content_Suggestions')) {
            wp_send_json_error(['message' => __('Content Suggestions module is not available.', 'smart-seo-fixer')]);
        }
        
        $suggestions = new SSF_Content_Suggestions();
        $result = $suggestions->get_suggestions($post_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Run WP coding standards audit
     */
    public function wp_standards_audit() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_WP_Standards')) {
            wp_send_json_error(['message' => __('WP Standards module is not available.', 'smart-seo-fixer')]);
        }
        
        $auditor = new SSF_WP_Standards();
        $result = $auditor->audit();
        
        wp_send_json_success($result);
    }
    
    /**
     * Get performance profiling data
     */
    public function performance_data() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_Performance')) {
            wp_send_json_error(['message' => __('Performance module is not available.', 'smart-seo-fixer')]);
        }
        
        $perf = new SSF_Performance();
        $data = $perf->get_data();
        
        wp_send_json_success($data);
    }
    
    /**
     * Clear performance data
     */
    public function performance_clear() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_Performance')) {
            wp_send_json_error(['message' => __('Performance module is not available.', 'smart-seo-fixer')]);
        }
        
        $perf = new SSF_Performance();
        $perf->clear();
        
        wp_send_json_success(['message' => __('Performance data cleared.', 'smart-seo-fixer')]);
    }
    
    /**
     * Generate client SEO report (admin only)
     */
    public function generate_client_report() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied. Admin access required.', 'smart-seo-fixer')]);
        }
        
        if (!class_exists('SSF_Client_Report')) {
            wp_send_json_error(['message' => __('Client Report module is not available.', 'smart-seo-fixer')]);
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date   = sanitize_text_field($_POST['end_date'] ?? '');
        $sections   = isset($_POST['sections']) && is_array($_POST['sections'])
            ? array_map('sanitize_key', $_POST['sections'])
            : [];
        
        $data = SSF_Client_Report::generate($date_range, $start_date, $end_date, $sections);
        
        wp_send_json_success($data);
    }
}

