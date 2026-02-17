<?php
/**
 * Plugin Name: Smart SEO Fixer
 * Plugin URI: https://github.com/mbheramil/Smart-SEO-Fixer
 * Description: AI-powered SEO optimization plugin that analyzes and fixes SEO issues using OpenAI.
 * Version: 1.8.1
 * Author: mbheramil
 * Author URI: https://github.com/mbheramil
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-seo-fixer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('SSF_VERSION', '1.8.1');
define('SSF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SSF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SSF_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
final class Smart_SEO_Fixer {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Plugin components
     */
    public $openai;
    public $analyzer;
    public $meta_manager;
    public $schema;
    public $sitemap;
    public $local_seo;
    public $redirects;
    public $breadcrumbs;
    public $woocommerce;
    public $updater;
    public $search_console;
    public $admin;
    
    /**
     * Get instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        $includes = [
            'includes/class-openai.php',
            'includes/class-analyzer.php',
            'includes/class-meta-manager.php',
            'includes/class-schema.php',
            'includes/class-sitemap.php',
            'includes/class-local-seo.php',
            'includes/class-migration.php',
            'includes/class-redirects.php',
            'includes/class-breadcrumbs.php',
            'includes/class-woocommerce.php',
            'includes/class-updater.php',
            'includes/class-search-console.php',
            'includes/class-ajax.php',
        ];
        
        foreach ($includes as $file) {
            $path = SSF_PLUGIN_DIR . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
        
        if (is_admin()) {
            $admin_path = SSF_PLUGIN_DIR . 'includes/class-admin.php';
            if (file_exists($admin_path)) {
                require_once $admin_path;
            }
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Background SEO generation cron
        add_action('ssf_cron_generate_missing_seo', [$this, 'cron_generate_missing_seo']);
        
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    /**
     * Initialize components
     */
    public function init() {
        // Safely instantiate each class (defensive if a file failed to load)
        if (class_exists('SSF_OpenAI'))       $this->openai       = new SSF_OpenAI();
        if (class_exists('SSF_Analyzer'))     $this->analyzer      = new SSF_Analyzer();
        if (class_exists('SSF_Meta_Manager')) $this->meta_manager  = new SSF_Meta_Manager();
        if (class_exists('SSF_Schema'))       $this->schema        = new SSF_Schema();
        if (class_exists('SSF_Sitemap'))      $this->sitemap       = new SSF_Sitemap();
        if (class_exists('SSF_Local_SEO'))    $this->local_seo     = new SSF_Local_SEO();
        if (class_exists('SSF_Redirects'))    $this->redirects     = new SSF_Redirects();
        if (class_exists('SSF_Breadcrumbs'))  $this->breadcrumbs   = new SSF_Breadcrumbs();
        if (class_exists('SSF_WooCommerce'))  $this->woocommerce   = new SSF_WooCommerce();
        if (class_exists('SSF_Search_Console')) $this->search_console = new SSF_Search_Console();
        if (class_exists('SSF_Ajax'))         new SSF_Ajax();
        
        if (is_admin()) {
            $this->admin = new SSF_Admin();
            // Updater only needed in admin (update checks, plugin info, download auth)
            if (class_exists('SSF_Updater')) $this->updater = new SSF_Updater();
            // Ensure DB table exists (self-healing if plugin was updated without reactivation)
            add_action('admin_init', [$this, 'maybe_create_tables']);
            // Ensure cron is scheduled (self-healing if cron was lost)
            add_action('admin_init', [$this, 'maybe_schedule_cron']);
            // Handle force update check from plugins page
            add_action('admin_init', ['SSF_Updater', 'force_check']);
            // Auto-detect custom post types (no need on every frontend request)
            add_action('init', [$this, 'auto_detect_post_types'], 999);
        }
    }
    
    /**
     * Auto-detect all public post types and ensure they're in the post_types setting
     */
    public function auto_detect_post_types() {
        $saved = self::get_option('post_types', ['post', 'page']);
        
        // Get all public post types (excluding built-in non-content types)
        $all_public = get_post_types(['public' => true], 'names');
        unset($all_public['attachment']); // Don't include media attachments
        
        // Merge: add any new public post types that weren't in settings
        $merged = array_unique(array_merge($saved, array_values($all_public)));
        
        // Only update if we found new ones
        if (count($merged) > count($saved)) {
            update_option('ssf_post_types', $merged);
        }
    }
    
    /**
     * Load translations
     */
    public function load_textdomain() {
        load_plugin_textdomain('smart-seo-fixer', false, dirname(SSF_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Activation
     */
    public function activate() {
        // Set default options
        $defaults = [
            'openai_api_key' => '',
            'openai_model' => 'gpt-4o-mini',
            'auto_meta' => false,
            'auto_alt_text' => false,
            'enable_schema' => true,
            'enable_sitemap' => true,
            'disable_other_seo_output' => false,
            'title_separator' => '|',
            'homepage_title' => '',
            'homepage_description' => '',
            'post_types' => ['post', 'page'], // Updated on init to include custom post types
            'background_seo_cron' => true, // Auto-fill missing SEO in background
        ];
        
        foreach ($defaults as $key => $value) {
            if (get_option('ssf_' . $key) === false) {
                update_option('ssf_' . $key, $value);
            }
        }
        
        // Create database tables if needed
        $this->create_tables();
        
        // Schedule background SEO generation cron (twice daily)
        if (!wp_next_scheduled('ssf_cron_generate_missing_seo')) {
            wp_schedule_event(time(), 'twicedaily', 'ssf_cron_generate_missing_seo');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Deactivation
     */
    public function deactivate() {
        // Clear scheduled cron
        $timestamp = wp_next_scheduled('ssf_cron_generate_missing_seo');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ssf_cron_generate_missing_seo');
        }
        
        flush_rewrite_rules();
    }
    
    /**
     * Background cron: Auto-generate missing SEO titles & descriptions
     * Runs twice daily — processes up to 10 posts per run to respect API limits
     */
    public function cron_generate_missing_seo() {
        // Check if background cron is enabled
        if (!self::get_option('background_seo_cron', true)) {
            return;
        }
        
        // Must have OpenAI configured
        if (!class_exists('SSF_OpenAI')) {
            return;
        }
        
        $openai = new SSF_OpenAI();
        if (!$openai->is_configured()) {
            return;
        }
        
        global $wpdb;
        $post_types = self::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        
        // Find published posts missing SEO title (limit 10 per cron run)
        $posts_missing_title = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT p.ID 
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_ssf_seo_title'
                WHERE p.post_status = 'publish'
                AND p.post_type IN ($placeholders)
                AND (pm.meta_value IS NULL OR pm.meta_value = '')
                ORDER BY p.post_date DESC
                LIMIT 10",
                ...$post_types
            )
        );
        
        $generated_count = 0;
        
        foreach ($posts_missing_title as $post_id) {
            $post = get_post($post_id);
            if (!$post || str_word_count(strip_tags($post->post_content)) < 10) {
                continue;
            }
            
            $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
            
            // Generate title
            $seo_title = get_post_meta($post_id, '_ssf_seo_title', true);
            if (empty($seo_title)) {
                $title = $openai->generate_title($post->post_content, $post->post_title, $focus_keyword);
                if (!is_wp_error($title) && !empty(trim($title))) {
                    update_post_meta($post_id, '_ssf_seo_title', sanitize_text_field(trim($title)));
                    $generated_count++;
                }
            }
            
            // Generate description
            $meta_desc = get_post_meta($post_id, '_ssf_meta_description', true);
            if (empty($meta_desc)) {
                $desc = $openai->generate_meta_description($post->post_content, '', $focus_keyword);
                if (!is_wp_error($desc) && !empty(trim($desc))) {
                    update_post_meta($post_id, '_ssf_meta_description', sanitize_textarea_field(trim($desc)));
                }
            }
            
            // Generate focus keyword if empty
            if (empty($focus_keyword)) {
                $keywords = $openai->suggest_keywords($post->post_content, $post->post_title);
                if (!is_wp_error($keywords) && is_array($keywords) && !empty($keywords['primary'])) {
                    update_post_meta($post_id, '_ssf_focus_keyword', sanitize_text_field($keywords['primary']));
                }
            }
            
            // Run analysis to update score
            if (class_exists('SSF_Analyzer')) {
                $analyzer = new SSF_Analyzer();
                $analyzer->analyze_post($post_id);
            }
        }
        
        // Log for debugging
        if ($generated_count > 0) {
            update_option('ssf_cron_last_run', [
                'time' => current_time('mysql'),
                'generated' => $generated_count,
                'remaining' => max(0, count($posts_missing_title) - $generated_count),
            ]);
        }
    }
    
    /**
     * Ensure cron is scheduled (self-healing if lost after server migration, etc.)
     */
    public function maybe_schedule_cron() {
        if (self::get_option('background_seo_cron', true)) {
            if (!wp_next_scheduled('ssf_cron_generate_missing_seo')) {
                wp_schedule_event(time() + 3600, 'twicedaily', 'ssf_cron_generate_missing_seo');
            }
        }
    }
    
    /**
     * Ensure database tables exist (runs on admin_init)
     */
    public function maybe_create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ssf_seo_scores';
        // Only run if table doesn't exist yet
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $this->create_tables();
        }
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'ssf_seo_scores';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            score int(3) NOT NULL DEFAULT 0,
            issues longtext,
            suggestions longtext,
            last_analyzed datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id),
            KEY score (score)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get option helper
     */
    public static function get_option($key, $default = '') {
        return get_option('ssf_' . $key, $default);
    }
    
    /**
     * Update option helper
     */
    public static function update_option($key, $value) {
        return update_option('ssf_' . $key, $value);
    }
}

/**
 * Returns the main instance
 */
function ssf() {
    return Smart_SEO_Fixer::instance();
}

// Initialize
ssf();

