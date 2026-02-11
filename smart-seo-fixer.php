<?php
/**
 * Plugin Name: Smart SEO Fixer
 * Plugin URI: https://github.com/mbheramil/Smart-SEO-Fixer
 * Description: AI-powered SEO optimization plugin that analyzes and fixes SEO issues using OpenAI.
 * Version: 1.2.0
 * Author: Your Name
 * Author URI: https://example.com
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
define('SSF_VERSION', '1.2.0');
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
        // Core classes
        require_once SSF_PLUGIN_DIR . 'includes/class-openai.php';
        require_once SSF_PLUGIN_DIR . 'includes/class-analyzer.php';
        require_once SSF_PLUGIN_DIR . 'includes/class-meta-manager.php';
        require_once SSF_PLUGIN_DIR . 'includes/class-schema.php';
        require_once SSF_PLUGIN_DIR . 'includes/class-sitemap.php';
        require_once SSF_PLUGIN_DIR . 'includes/class-local-seo.php';
        require_once SSF_PLUGIN_DIR . 'includes/class-migration.php';
        require_once SSF_PLUGIN_DIR . 'includes/class-redirects.php';
        require_once SSF_PLUGIN_DIR . 'includes/class-breadcrumbs.php';
        require_once SSF_PLUGIN_DIR . 'includes/class-woocommerce.php';
        require_once SSF_PLUGIN_DIR . 'includes/class-updater.php';
        require_once SSF_PLUGIN_DIR . 'includes/class-ajax.php';
        
        if (is_admin()) {
            require_once SSF_PLUGIN_DIR . 'includes/class-admin.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    /**
     * Initialize components
     */
    public function init() {
        $this->openai = new SSF_OpenAI();
        $this->analyzer = new SSF_Analyzer();
        $this->meta_manager = new SSF_Meta_Manager();
        $this->schema = new SSF_Schema();
        $this->sitemap = new SSF_Sitemap();
        $this->local_seo = new SSF_Local_SEO();
        $this->redirects = new SSF_Redirects();
        $this->breadcrumbs = new SSF_Breadcrumbs();
        $this->woocommerce = new SSF_WooCommerce();
        $this->updater = new SSF_Updater();
        new SSF_Ajax();
        
        if (is_admin()) {
            $this->admin = new SSF_Admin();
            // Ensure DB table exists (self-healing if plugin was updated without reactivation)
            add_action('admin_init', [$this, 'maybe_create_tables']);
            // Handle force update check from plugins page
            add_action('admin_init', ['SSF_Updater', 'force_check']);
        }
        
        // Auto-detect custom post types and merge into settings (runs late so CPTs are registered)
        add_action('init', [$this, 'auto_detect_post_types'], 999);
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
        ];
        
        foreach ($defaults as $key => $value) {
            if (get_option('ssf_' . $key) === false) {
                update_option('ssf_' . $key, $value);
            }
        }
        
        // Create database tables if needed
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
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

