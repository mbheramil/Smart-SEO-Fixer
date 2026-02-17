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
        
        $openai = new SSF_OpenAI();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured. Please add your API key in settings.', 'smart-seo-fixer')]);
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
        
        $openai = new SSF_OpenAI();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured. Please add your API key in settings.', 'smart-seo-fixer')]);
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
        
        $openai = new SSF_OpenAI();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured. Please add your API key in settings.', 'smart-seo-fixer')]);
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
        
        $openai = new SSF_OpenAI();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured. Please add your API key in settings.', 'smart-seo-fixer')]);
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
        
        $openai = new SSF_OpenAI();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured. Please add your API key in settings.', 'smart-seo-fixer')]);
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
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $data = [
            'seo_title' => sanitize_text_field($_POST['seo_title'] ?? ''),
            'meta_description' => sanitize_textarea_field($_POST['meta_description'] ?? ''),
            'focus_keyword' => sanitize_text_field($_POST['focus_keyword'] ?? ''),
            'canonical_url' => esc_url_raw($_POST['canonical_url'] ?? ''),
            'noindex' => !empty($_POST['noindex']) ? 1 : 0,
            'nofollow' => !empty($_POST['nofollow']) ? 1 : 0,
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
     * Save plugin settings
     */
    public function save_settings() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $settings = [
            'openai_api_key' => sanitize_text_field($_POST['openai_api_key'] ?? ''),
            'openai_model' => sanitize_text_field($_POST['openai_model'] ?? 'gpt-4o-mini'),
            'auto_meta' => !empty($_POST['auto_meta']) ? 1 : 0,
            'auto_alt_text' => !empty($_POST['auto_alt_text']) ? 1 : 0,
            'enable_schema' => !empty($_POST['enable_schema']) ? 1 : 0,
            'enable_sitemap' => !empty($_POST['enable_sitemap']) ? 1 : 0,
            'disable_other_seo_output' => !empty($_POST['disable_other_seo_output']) ? 1 : 0,
            'background_seo_cron' => !empty($_POST['background_seo_cron']) ? 1 : 0,
            'github_token' => sanitize_text_field($_POST['github_token'] ?? ''),
            'gsc_client_id' => sanitize_text_field($_POST['gsc_client_id'] ?? ''),
            'gsc_client_secret' => sanitize_text_field($_POST['gsc_client_secret'] ?? ''),
            'title_separator' => sanitize_text_field($_POST['title_separator'] ?? '|'),
            'homepage_title' => sanitize_text_field($_POST['homepage_title'] ?? ''),
            'homepage_description' => sanitize_textarea_field($_POST['homepage_description'] ?? ''),
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
            $settings['post_types'] = array_map('sanitize_text_field', $_POST['post_types']);
        }
        
        // Handle GSC site URL selection
        if (isset($_POST['gsc_site_url'])) {
            $settings['gsc_site_url'] = esc_url_raw($_POST['gsc_site_url']);
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
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $issue_code = sanitize_text_field($_POST['issue_code'] ?? '');
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'smart-seo-fixer')]);
        }
        
        $openai = new SSF_OpenAI();
        $result = [];
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured.', 'smart-seo-fixer')]);
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
        
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
        $issue_types = isset($_POST['issue_types']) ? array_map('sanitize_text_field', $_POST['issue_types']) : [];
        
        if (empty($post_ids)) {
            wp_send_json_error(['message' => __('No posts selected.', 'smart-seo-fixer')]);
        }
        
        $openai = new SSF_OpenAI();
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured.', 'smart-seo-fixer')]);
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
        
        // Get keywords from the post
        $content = $post->post_content . ' ' . $post->post_title;
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
        
        $links = [];
        foreach ($related as $related_post) {
            // Check if not already linked in content
            $url = get_permalink($related_post->ID);
            if (strpos($post->post_content, $url) === false) {
                $links[] = [
                    'id' => $related_post->ID,
                    'title' => $related_post->post_title,
                    'url' => $url,
                    'excerpt' => wp_trim_words($related_post->post_content, 15),
                ];
            }
        }
        
        wp_send_json_success(['links' => array_slice($links, 0, 5)]);
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
        
        $openai = new SSF_OpenAI();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured.', 'smart-seo-fixer')]);
        }
        
        // Use AI to suggest authoritative sources with actual URLs
        $prompt = "Based on this content, suggest 3-5 authoritative external sources that would be good to link to for credibility and SEO.\n\n";
        $prompt .= "For each suggestion, provide:\n";
        $prompt .= "- 'url': a real, working URL to the authoritative source\n";
        $prompt .= "- 'anchor': suggested anchor text for the link\n";
        $prompt .= "- 'reason': brief reason why this source adds value\n\n";
        $prompt .= "Only suggest well-known, established sources (Wikipedia, government sites, major publications, official documentation, etc.).\n\n";
        $prompt .= "Format as JSON array: [{\"url\": \"https://...\", \"anchor\": \"...\", \"reason\": \"...\"}]\n\n";
        $prompt .= "Content:\n" . wp_trim_words($post->post_content, 300);
        
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
        
        wp_send_json_success(['suggestions' => $suggestions]);
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
        
        $openai = new SSF_OpenAI();
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
        
        $openai = new SSF_OpenAI();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured.', 'smart-seo-fixer')]);
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
        
        $options = $_POST['options'] ?? [];
        
        $generate_title = !empty($options['generate_title']);
        $generate_desc = !empty($options['generate_desc']);
        $generate_keywords = !empty($options['generate_keywords']);
        $apply_to = sanitize_text_field($options['apply_to'] ?? 'missing');
        
        // Validate OpenAI is configured BEFORE doing any work
        $openai = new SSF_OpenAI();
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured. Go to Settings to add it.', 'smart-seo-fixer')]);
        }
        
        // Accept explicit post IDs from the frontend (preview selection)
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', (array) $_POST['post_ids']) : [];
        $post_ids = array_filter($post_ids, function($id) { return $id > 0; });
        
        if (empty($post_ids)) {
            wp_send_json_error(['message' => __('No posts selected.', 'smart-seo-fixer')]);
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
        
        $openai = new SSF_OpenAI();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured.', 'smart-seo-fixer')]);
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
        
        $openai = new SSF_OpenAI();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured.', 'smart-seo-fixer')]);
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
        
        $openai = new SSF_OpenAI();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured.', 'smart-seo-fixer')]);
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
        $openai = new SSF_OpenAI();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured.', 'smart-seo-fixer')]);
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
        
        $openai = new SSF_OpenAI();
        
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured.', 'smart-seo-fixer')]);
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
        
        $openai = new SSF_OpenAI();
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured.', 'smart-seo-fixer')]);
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
        
        $openai = new SSF_OpenAI();
        if (!$openai->is_configured()) {
            wp_send_json_error(['message' => __('OpenAI API key not configured.', 'smart-seo-fixer')]);
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
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        if (strlen($search) < 2) {
            wp_send_json_success(['results' => []]);
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
        
        wp_send_json_success(['results' => $results]);
    }
    
    // ========================================================
    // Google Search Console AJAX Handlers
    // ========================================================
    
    /**
     * Disconnect from Google Search Console
     */
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
    
    /**
     * Find pages not appearing in GSC search data (likely not indexed)
     */
    public function gsc_not_indexed() {
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
        
        // Get all pages that appear in GSC (last 90 days for broader coverage)
        $gsc_pages = $gsc->get_top_pages(90, 5000);
        
        if (is_wp_error($gsc_pages)) {
            wp_send_json_error(['message' => $gsc_pages->get_error_message()]);
        }
        
        $indexed_urls = [];
        if (!empty($gsc_pages['rows'])) {
            foreach ($gsc_pages['rows'] as $row) {
                if (!empty($row['keys'][0])) {
                    $indexed_urls[] = rtrim($row['keys'][0], '/');
                }
            }
        }
        
        // Get all published posts/pages
        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $all_posts = get_posts([
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);
        
        $not_indexed = [];
        foreach ($all_posts as $post_id) {
            $url = get_permalink($post_id);
            $url_normalized = rtrim($url, '/');
            
            if (!in_array($url_normalized, $indexed_urls)) {
                $post = get_post($post_id);
                $seo_title = get_post_meta($post_id, '_ssf_seo_title', true);
                $seo_desc = get_post_meta($post_id, '_ssf_meta_description', true);
                
                // Check for internal links in content
                $has_internal_links = false;
                $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
                if (preg_match_all('/href=["\']([^"\']+)["\']/', $post->post_content, $link_matches)) {
                    foreach ($link_matches[1] as $href) {
                        $link_host = wp_parse_url($href, PHP_URL_HOST);
                        if ($link_host === $site_host || (empty($link_host) && strpos($href, '/') === 0)) {
                            $has_internal_links = true;
                            break;
                        }
                    }
                }
                
                // Check if any other page links TO this page
                $has_incoming_links = false;
                global $wpdb;
                $incoming = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} 
                     WHERE post_status = 'publish' 
                     AND ID != %d 
                     AND (post_content LIKE %s OR post_content LIKE %s)
                     LIMIT 1",
                    $post_id,
                    '%' . $wpdb->esc_like($url) . '%',
                    '%' . $wpdb->esc_like(wp_parse_url($url, PHP_URL_PATH)) . '%'
                ));
                $has_incoming_links = intval($incoming) > 0;
                
                $issues = [];
                if (empty($seo_title)) $issues[] = 'missing_title';
                if (empty($seo_desc)) $issues[] = 'missing_description';
                if (!$has_internal_links) $issues[] = 'no_outgoing_links';
                if (!$has_incoming_links) $issues[] = 'no_incoming_links';
                
                $not_indexed[] = [
                    'id'          => $post_id,
                    'title'       => $post->post_title,
                    'url'         => $url,
                    'post_type'   => $post->post_type,
                    'has_title'   => !empty($seo_title),
                    'has_desc'    => !empty($seo_desc),
                    'has_outgoing' => $has_internal_links,
                    'has_incoming' => $has_incoming_links,
                    'issues'      => $issues,
                    'issue_count' => count($issues),
                ];
            }
        }
        
        // Sort by most issues first
        usort($not_indexed, function($a, $b) {
            return $b['issue_count'] - $a['issue_count'];
        });
        
        wp_send_json_success([
            'total_published' => count($all_posts),
            'total_in_gsc'    => count($indexed_urls),
            'not_indexed'     => $not_indexed,
            'count'           => count($not_indexed),
        ]);
    }
}

