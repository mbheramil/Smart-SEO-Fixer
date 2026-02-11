<?php
/**
 * Migration Class
 * 
 * Handles migration from other SEO plugins (Yoast, Rank Math, All in One SEO, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Migration {
    
    /**
     * Supported plugins for migration
     */
    private $supported_plugins = [
        'yoast' => [
            'name' => 'Yoast SEO',
            'file' => 'wordpress-seo/wp-seo.php',
            'meta_keys' => [
                'title' => '_yoast_wpseo_title',
                'description' => '_yoast_wpseo_metadesc',
                'focus_keyword' => '_yoast_wpseo_focuskw',
                'canonical' => '_yoast_wpseo_canonical',
                'noindex' => '_yoast_wpseo_meta-robots-noindex',
                'nofollow' => '_yoast_wpseo_meta-robots-nofollow',
            ],
        ],
        'rankmath' => [
            'name' => 'Rank Math',
            'file' => 'seo-by-rank-math/rank-math.php',
            'meta_keys' => [
                'title' => 'rank_math_title',
                'description' => 'rank_math_description',
                'focus_keyword' => 'rank_math_focus_keyword',
                'canonical' => 'rank_math_canonical_url',
                'noindex' => 'rank_math_robots',
            ],
        ],
        'aioseo' => [
            'name' => 'All in One SEO',
            'file' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
            'meta_keys' => [
                'title' => '_aioseo_title',
                'description' => '_aioseo_description',
                'focus_keyword' => '_aioseo_keywords',
                'canonical' => '_aioseo_canonical_url',
                'noindex' => '_aioseo_noindex',
                'nofollow' => '_aioseo_nofollow',
            ],
        ],
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_ssf_detect_seo_plugins', [$this, 'ajax_detect_plugins']);
        add_action('wp_ajax_ssf_migrate_from_plugin', [$this, 'ajax_migrate']);
        add_action('wp_ajax_ssf_get_migration_preview', [$this, 'ajax_preview']);
        add_action('wp_ajax_ssf_reset_migration', [$this, 'ajax_reset_migration']);
    }
    
    /**
     * AJAX: Reset migration progress
     */
    public function ajax_reset_migration() {
        check_ajax_referer('ssf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        delete_transient('ssf_migration_progress');
        wp_send_json_success();
    }
    
    /**
     * Detect installed SEO plugins
     */
    public function detect_plugins() {
        $detected = [];
        
        foreach ($this->supported_plugins as $key => $plugin) {
            $is_active = is_plugin_active($plugin['file']);
            $has_data = $this->has_plugin_data($key);
            
            if ($is_active || $has_data) {
                $detected[$key] = [
                    'name' => $plugin['name'],
                    'active' => $is_active,
                    'has_data' => $has_data,
                    'post_count' => $this->count_posts_with_data($key),
                ];
            }
        }
        
        return $detected;
    }
    
    /**
     * Check if plugin has data in database
     */
    private function has_plugin_data($plugin_key) {
        global $wpdb;
        
        if (!isset($this->supported_plugins[$plugin_key])) {
            return false;
        }
        
        $meta_key = $this->supported_plugins[$plugin_key]['meta_keys']['title'];
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
            $meta_key
        ));
        
        return $count > 0;
    }
    
    /**
     * Count posts with data from a plugin
     */
    private function count_posts_with_data($plugin_key) {
        global $wpdb;
        
        if (!isset($this->supported_plugins[$plugin_key])) {
            return 0;
        }
        
        $meta_keys = $this->supported_plugins[$plugin_key]['meta_keys'];
        $title_key = $meta_keys['title'];
        $desc_key = $meta_keys['description'];
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
             WHERE (meta_key = %s OR meta_key = %s) AND meta_value != ''",
            $title_key, $desc_key
        ));
        
        return intval($count);
    }
    
    /**
     * Get migration preview
     */
    public function get_preview($plugin_key, $limit = 10) {
        global $wpdb;
        
        if (!isset($this->supported_plugins[$plugin_key])) {
            return [];
        }
        
        $meta_keys = $this->supported_plugins[$plugin_key]['meta_keys'];
        $preview = [];
        
        // Get posts with SEO data
        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = %s AND meta_value != ''
             LIMIT %d",
            $meta_keys['title'], $limit
        ));
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            
            if (!$post) continue;
            
            $preview[] = [
                'id' => $post_id,
                'title' => $post->post_title,
                'type' => $post->post_type,
                'seo_title' => get_post_meta($post_id, $meta_keys['title'], true),
                'seo_description' => get_post_meta($post_id, $meta_keys['description'], true),
                'focus_keyword' => get_post_meta($post_id, $meta_keys['focus_keyword'], true),
                'already_migrated' => !empty(get_post_meta($post_id, '_ssf_seo_title', true)),
            ];
        }
        
        return $preview;
    }
    
    /**
     * Migrate from a plugin (optimized batch processing)
     */
    public function migrate($plugin_key, $options = []) {
        global $wpdb;
        
        if (!isset($this->supported_plugins[$plugin_key])) {
            return new WP_Error('invalid_plugin', __('Unknown plugin for migration.', 'smart-seo-fixer'));
        }
        
        // Increase time limit for large migrations
        if (function_exists('set_time_limit')) {
            set_time_limit(300);
        }
        
        $meta_keys = $this->supported_plugins[$plugin_key]['meta_keys'];
        $overwrite = !empty($options['overwrite']);
        $auto_fix = !empty($options['auto_fix']);
        
        $results = [
            'migrated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'posts' => [],
        ];
        
        // Get all posts with SEO data in one query (with post data)
        $posts_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT p.ID, p.post_title, p.post_content, p.post_excerpt, p.post_author, p.post_status
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE (pm.meta_key = %s OR pm.meta_key = %s) 
             AND pm.meta_value != ''
             AND p.post_status = 'publish'",
            $meta_keys['title'], $meta_keys['description']
        ));
        
        if (empty($posts_data)) {
            return $results;
        }
        
        // Get all existing SSF titles in one query (to check what to skip)
        $post_ids = wp_list_pluck($posts_data, 'ID');
        $post_ids_str = implode(',', array_map('intval', $post_ids));
        
        $existing_ssf_data = [];
        if (!$overwrite) {
            $existing = $wpdb->get_col(
                "SELECT post_id FROM {$wpdb->postmeta} 
                 WHERE post_id IN ($post_ids_str) 
                 AND meta_key = '_ssf_seo_title' 
                 AND meta_value != ''"
            );
            $existing_ssf_data = array_flip($existing);
        }
        
        // Get all source meta data in bulk
        $source_meta = $wpdb->get_results(
            "SELECT post_id, meta_key, meta_value 
             FROM {$wpdb->postmeta} 
             WHERE post_id IN ($post_ids_str)
             AND meta_key IN ('" . implode("','", array_map('esc_sql', array_values($meta_keys))) . "')",
            OBJECT
        );
        
        // Organize source meta by post_id
        $meta_by_post = [];
        foreach ($source_meta as $meta) {
            if (!isset($meta_by_post[$meta->post_id])) {
                $meta_by_post[$meta->post_id] = [];
            }
            $meta_by_post[$meta->post_id][$meta->meta_key] = $meta->meta_value;
        }
        
        // Prepare bulk insert data
        $insert_data = [];
        
        foreach ($posts_data as $post) {
            $post_id = $post->ID;
            
            // Skip if already has our data and overwrite is false
            if (!$overwrite && isset($existing_ssf_data[$post_id])) {
                $results['skipped']++;
                continue;
            }
            
            $post_meta = $meta_by_post[$post_id] ?? [];
            $migrated_fields = [];
            
            // Title
            $source_title = $post_meta[$meta_keys['title']] ?? '';
            if (!empty($source_title)) {
                $source_title = $this->parse_seo_variables($source_title, $post);
                $insert_data[] = ['post_id' => $post_id, 'meta_key' => '_ssf_seo_title', 'meta_value' => $source_title];
                $migrated_fields[] = 'title';
            }
            
            // Description
            $source_desc = $post_meta[$meta_keys['description']] ?? '';
            if (!empty($source_desc)) {
                $source_desc = $this->parse_seo_variables($source_desc, $post);
                $insert_data[] = ['post_id' => $post_id, 'meta_key' => '_ssf_meta_description', 'meta_value' => $source_desc];
                $migrated_fields[] = 'description';
            }
            
            // Focus keyword
            if (isset($meta_keys['focus_keyword'])) {
                $source_kw = $post_meta[$meta_keys['focus_keyword']] ?? '';
                if (!empty($source_kw)) {
                    $source_kw = explode(',', $source_kw)[0];
                    $insert_data[] = ['post_id' => $post_id, 'meta_key' => '_ssf_focus_keyword', 'meta_value' => trim($source_kw)];
                    $migrated_fields[] = 'focus_keyword';
                }
            }
            
            // Canonical
            if (isset($meta_keys['canonical'])) {
                $source_canonical = $post_meta[$meta_keys['canonical']] ?? '';
                if (!empty($source_canonical)) {
                    $insert_data[] = ['post_id' => $post_id, 'meta_key' => '_ssf_canonical_url', 'meta_value' => $source_canonical];
                    $migrated_fields[] = 'canonical';
                }
            }
            
            // Noindex
            if (isset($meta_keys['noindex'])) {
                $source_noindex = $post_meta[$meta_keys['noindex']] ?? '';
                if ($source_noindex === '1' || $source_noindex === 'noindex') {
                    $insert_data[] = ['post_id' => $post_id, 'meta_key' => '_ssf_noindex', 'meta_value' => '1'];
                    $migrated_fields[] = 'noindex';
                }
            }
            
            // Nofollow
            if (isset($meta_keys['nofollow'])) {
                $source_nofollow = $post_meta[$meta_keys['nofollow']] ?? '';
                if ($source_nofollow === '1' || $source_nofollow === 'nofollow') {
                    $insert_data[] = ['post_id' => $post_id, 'meta_key' => '_ssf_nofollow', 'meta_value' => '1'];
                    $migrated_fields[] = 'nofollow';
                }
            }
            
            if (!empty($migrated_fields)) {
                $results['migrated']++;
                $results['posts'][] = [
                    'id' => $post_id,
                    'title' => $post->post_title,
                    'fields' => $migrated_fields,
                ];
            }
        }
        
        // Bulk insert all meta data
        if (!empty($insert_data)) {
            // Delete existing meta first if overwrite
            if ($overwrite) {
                $migrated_ids = wp_list_pluck($results['posts'], 'id');
                if (!empty($migrated_ids)) {
                    $migrated_ids_str = implode(',', array_map('intval', $migrated_ids));
                    $wpdb->query(
                        "DELETE FROM {$wpdb->postmeta} 
                         WHERE post_id IN ($migrated_ids_str) 
                         AND meta_key IN ('_ssf_seo_title', '_ssf_meta_description', '_ssf_focus_keyword', '_ssf_canonical_url', '_ssf_noindex', '_ssf_nofollow')"
                    );
                }
            }
            
            // Batch insert (chunks of 100)
            $chunks = array_chunk($insert_data, 100);
            foreach ($chunks as $chunk) {
                $values = [];
                $placeholders = [];
                
                foreach ($chunk as $row) {
                    $placeholders[] = "(%d, %s, %s)";
                    $values[] = $row['post_id'];
                    $values[] = $row['meta_key'];
                    $values[] = $row['meta_value'];
                }
                
                $wpdb->query($wpdb->prepare(
                    "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode(', ', $placeholders),
                    $values
                ));
            }
        }
        
        // Store migration log (DON'T run analysis during migration - let user do it separately)
        update_option('ssf_last_migration', [
            'plugin' => $plugin_key,
            'date' => current_time('mysql'),
            'results' => $results,
            'auto_fix_pending' => $auto_fix, // Flag to show user should run analysis
        ]);
        
        return $results;
    }
    
    /**
     * Parse SEO plugin variables (%%title%%, %%sitename%%, etc.)
     */
    private function parse_seo_variables($string, $post) {
        // Handle both WP_Post and stdClass objects
        $post_id = is_object($post) ? $post->ID : $post;
        $post_title = is_object($post) ? $post->post_title : '';
        $post_content = is_object($post) ? ($post->post_content ?? '') : '';
        $post_excerpt = is_object($post) ? ($post->post_excerpt ?? '') : '';
        $post_author = is_object($post) ? ($post->post_author ?? 0) : 0;
        
        $replacements = [
            '%%title%%' => $post_title,
            '%%sitename%%' => get_bloginfo('name'),
            '%%sitetitle%%' => get_bloginfo('name'),
            '%%sitedesc%%' => get_bloginfo('description'),
            '%%sep%%' => '|',
            '%%separator%%' => '|',
            '%%date%%' => '',
            '%%year%%' => date('Y'),
            '%%month%%' => date('F'),
            '%%excerpt%%' => wp_trim_words($post_excerpt ?: $post_content, 25),
            '%%category%%' => $this->get_primary_category($post_id),
            '%%tag%%' => $this->get_primary_tag($post_id),
            '%%author%%' => $post_author ? get_the_author_meta('display_name', $post_author) : '',
            '%%page%%' => '',
            '%%pagenumber%%' => '',
            '%%pagetotal%%' => '',
        ];
        
        foreach ($replacements as $var => $value) {
            $string = str_replace($var, $value, $string);
        }
        
        // Clean up any remaining variables
        $string = preg_replace('/%%[a-z_]+%%/i', '', $string);
        
        // Clean up multiple separators/spaces
        $string = preg_replace('/\s*\|\s*\|\s*/', ' | ', $string);
        $string = preg_replace('/\s+/', ' ', $string);
        $string = trim($string, ' |');
        
        return $string;
    }
    
    /**
     * Get primary category name
     */
    private function get_primary_category($post_id) {
        $categories = get_the_category($post_id);
        
        if (empty($categories)) {
            return '';
        }
        
        // Check for Yoast primary category
        $primary = get_post_meta($post_id, '_yoast_wpseo_primary_category', true);
        
        if ($primary) {
            $term = get_term($primary, 'category');
            if ($term && !is_wp_error($term)) {
                return $term->name;
            }
        }
        
        return $categories[0]->name;
    }
    
    /**
     * Get primary tag name
     */
    private function get_primary_tag($post_id) {
        $tags = get_the_tags($post_id);
        
        if (empty($tags)) {
            return '';
        }
        
        return $tags[0]->name;
    }
    
    /**
     * AJAX: Detect plugins
     */
    public function ajax_detect_plugins() {
        check_ajax_referer('ssf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $plugins = $this->detect_plugins();
        
        wp_send_json_success($plugins);
    }
    
    /**
     * AJAX: Get migration preview
     */
    public function ajax_preview() {
        check_ajax_referer('ssf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $plugin = sanitize_text_field($_POST['plugin'] ?? '');
        
        if (empty($plugin)) {
            wp_send_json_error(['message' => __('No plugin specified.', 'smart-seo-fixer')]);
        }
        
        $preview = $this->get_preview($plugin, 20);
        $total = $this->count_posts_with_data($plugin);
        
        wp_send_json_success([
            'preview' => $preview,
            'total' => $total,
        ]);
    }
    
    /**
     * AJAX: Run migration (chunked for progress bar)
     */
    public function ajax_migrate() {
        // Disable error display to prevent JSON corruption
        @ini_set('display_errors', 0);
        
        check_ajax_referer('ssf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $plugin = sanitize_text_field($_POST['plugin'] ?? '');
        $overwrite = !empty($_POST['overwrite']);
        $offset = intval($_POST['offset'] ?? 0);
        $batch_size = 25; // Process 25 posts per request (smaller batches = more reliable)
        
        if (empty($plugin)) {
            wp_send_json_error(['message' => __('No plugin specified.', 'smart-seo-fixer')]);
        }
        
        // Reset progress on first batch
        if ($offset === 0) {
            delete_transient('ssf_migration_progress');
        }
        
        try {
            $result = $this->migrate_batch($plugin, $offset, $batch_size, $overwrite);
            
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            }
            
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Migrate a batch of posts (simplified and robust)
     */
    public function migrate_batch($plugin_key, $offset = 0, $batch_size = 25, $overwrite = false) {
        global $wpdb;
        
        if (!isset($this->supported_plugins[$plugin_key])) {
            return new WP_Error('invalid_plugin', __('Unknown plugin for migration.', 'smart-seo-fixer'));
        }
        
        $meta_keys = $this->supported_plugins[$plugin_key]['meta_keys'];
        $title_key = $meta_keys['title'];
        $desc_key = $meta_keys['description'];
        
        // Get total count
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
             WHERE meta_key IN (%s, %s) AND meta_value != ''",
            $title_key, $desc_key
        ));
        
        if ($total === 0) {
            return [
                'done' => true,
                'total' => 0,
                'processed' => 0,
                'migrated' => 0,
                'skipped' => 0,
                'percent' => 100,
            ];
        }
        
        // Get batch of post IDs
        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key IN (%s, %s) AND meta_value != ''
             ORDER BY post_id ASC
             LIMIT %d OFFSET %d",
            $title_key, $desc_key, $batch_size, $offset
        ));
        
        $migrated = 0;
        $skipped = 0;
        
        foreach ($post_ids as $post_id) {
            $post_id = (int) $post_id;
            
            // Skip if already has SSF data and not overwriting
            if (!$overwrite) {
                $existing = get_post_meta($post_id, '_ssf_seo_title', true);
                if (!empty($existing)) {
                    $skipped++;
                    continue;
                }
            }
            
            // Get source data
            $source_title = get_post_meta($post_id, $title_key, true);
            $source_desc = get_post_meta($post_id, $desc_key, true);
            
            if (empty($source_title) && empty($source_desc)) {
                $skipped++;
                continue;
            }
            
            $post = get_post($post_id);
            if (!$post || $post->post_status !== 'publish') {
                $skipped++;
                continue;
            }
            
            // Migrate title
            if (!empty($source_title)) {
                $source_title = $this->parse_seo_variables($source_title, $post);
                update_post_meta($post_id, '_ssf_seo_title', $source_title);
            }
            
            // Migrate description
            if (!empty($source_desc)) {
                $source_desc = $this->parse_seo_variables($source_desc, $post);
                update_post_meta($post_id, '_ssf_meta_description', $source_desc);
            }
            
            // Migrate focus keyword
            if (isset($meta_keys['focus_keyword'])) {
                $source_kw = get_post_meta($post_id, $meta_keys['focus_keyword'], true);
                if (!empty($source_kw)) {
                    $source_kw = explode(',', $source_kw)[0];
                    update_post_meta($post_id, '_ssf_focus_keyword', trim($source_kw));
                }
            }
            
            // Migrate canonical
            if (isset($meta_keys['canonical'])) {
                $source_canonical = get_post_meta($post_id, $meta_keys['canonical'], true);
                if (!empty($source_canonical)) {
                    update_post_meta($post_id, '_ssf_canonical_url', $source_canonical);
                }
            }
            
            // Migrate noindex
            if (isset($meta_keys['noindex'])) {
                $source_noindex = get_post_meta($post_id, $meta_keys['noindex'], true);
                if ($source_noindex === '1' || strpos($source_noindex, 'noindex') !== false) {
                    update_post_meta($post_id, '_ssf_noindex', 1);
                }
            }
            
            $migrated++;
        }
        
        $processed = $offset + count($post_ids);
        $done = ($processed >= $total) || empty($post_ids);
        
        // Get cumulative progress
        $cumulative = get_transient('ssf_migration_progress') ?: ['migrated' => 0, 'skipped' => 0];
        $cumulative['migrated'] += $migrated;
        $cumulative['skipped'] += $skipped;
        
        if ($done) {
            // Save final results
            update_option('ssf_last_migration', [
                'plugin' => $plugin_key,
                'date' => current_time('mysql'),
                'results' => $cumulative,
            ]);
            delete_transient('ssf_migration_progress');
            
            return [
                'done' => true,
                'total' => $total,
                'processed' => $processed,
                'migrated' => $cumulative['migrated'],
                'skipped' => $cumulative['skipped'],
                'percent' => 100,
            ];
        } else {
            // Save progress
            set_transient('ssf_migration_progress', $cumulative, HOUR_IN_SECONDS);
            
            return [
                'done' => false,
                'total' => $total,
                'processed' => $processed,
                'migrated' => $cumulative['migrated'],
                'skipped' => $cumulative['skipped'],
                'percent' => $total > 0 ? round(($processed / $total) * 100) : 0,
                'next_offset' => $processed,
            ];
        }
    }
}

// Initialize
new SSF_Migration();

