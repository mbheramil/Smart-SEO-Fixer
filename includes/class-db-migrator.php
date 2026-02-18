<?php
/**
 * Database Migrator
 * 
 * Versioned schema updates that apply cleanly on plugin update.
 * Each migration runs once and is tracked by version number.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_DB_Migrator {
    
    const DB_VERSION_KEY = 'ssf_db_version';
    
    /**
     * Current target DB version (increment when adding new migrations)
     */
    const CURRENT_VERSION = 6;
    
    /**
     * Run any pending migrations
     * Called on admin_init to catch updates seamlessly.
     */
    public static function maybe_migrate() {
        $current = intval(get_option(self::DB_VERSION_KEY, 0));
        
        if ($current >= self::CURRENT_VERSION) {
            return;
        }
        
        if (class_exists('SSF_Logger')) {
            SSF_Logger::info(sprintf('Running DB migrations from v%d to v%d', $current, self::CURRENT_VERSION), 'migration');
        }
        
        // Run each migration that hasn't been applied yet
        $migrations = self::get_migrations();
        
        foreach ($migrations as $version => $callback) {
            if ($current < $version) {
                try {
                    call_user_func($callback);
                    update_option(self::DB_VERSION_KEY, $version);
                    
                    if (class_exists('SSF_Logger')) {
                        SSF_Logger::info(sprintf('Migration v%d completed', $version), 'migration');
                    }
                } catch (Exception $e) {
                    if (class_exists('SSF_Logger')) {
                        SSF_Logger::error(sprintf('Migration v%d failed: %s', $version, $e->getMessage()), 'migration');
                    }
                    return; // Stop on failure
                }
            }
        }
    }
    
    /**
     * Define all migrations in order.
     * Each key is a version number, value is a callable.
     */
    private static function get_migrations() {
        return [
            // v1: SEO scores table (original)
            1 => [__CLASS__, 'migrate_v1_seo_scores'],
            // v2: History table (v1.10.0)
            2 => [__CLASS__, 'migrate_v2_history_logs'],
            // v3: Jobs table (v1.11.0)
            3 => [__CLASS__, 'migrate_v3_jobs'],
            // v4: Setup wizard completed flag + input validation defaults (v1.12.0)
            4 => [__CLASS__, 'migrate_v4_setup_validation'],
            // v5: Broken links + 404 log tables (v1.13.0)
            5 => [__CLASS__, 'migrate_v5_broken_links_404'],
            // v6: Keyword tracking table (v1.14.0)
            6 => [__CLASS__, 'migrate_v6_keyword_tracking'],
        ];
    }
    
    /**
     * Migration v1: SEO scores table
     */
    public static function migrate_v1_seo_scores() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'ssf_seo_scores';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
     * Migration v2: History and logs tables
     */
    public static function migrate_v2_history_logs() {
        if (class_exists('SSF_History')) {
            SSF_History::create_table();
        }
        if (class_exists('SSF_Logger')) {
            SSF_Logger::create_table();
        }
    }
    
    /**
     * Migration v3: Jobs table
     */
    public static function migrate_v3_jobs() {
        if (class_exists('SSF_Job_Queue')) {
            SSF_Job_Queue::create_table();
        }
    }
    
    /**
     * Migration v4: Setup wizard flag and validation defaults
     */
    public static function migrate_v4_setup_validation() {
        // If the plugin already has an API key configured, mark setup as complete
        $api_key = get_option('ssf_openai_api_key', '');
        if (!empty($api_key)) {
            update_option('ssf_setup_completed', true);
        }
    }
    
    /**
     * Migration v5: Broken links and 404 log tables
     */
    public static function migrate_v5_broken_links_404() {
        if (class_exists('SSF_Broken_Links')) {
            SSF_Broken_Links::create_table();
        }
        if (class_exists('SSF_404_Monitor')) {
            SSF_404_Monitor::create_table();
        }
    }
    
    /**
     * Migration v6: Keyword tracking table
     */
    public static function migrate_v6_keyword_tracking() {
        if (class_exists('SSF_Keyword_Tracker')) {
            SSF_Keyword_Tracker::create_table();
        }
    }
    
    /**
     * Get current DB version
     */
    public static function get_version() {
        return intval(get_option(self::DB_VERSION_KEY, 0));
    }
    
    /**
     * Force re-run all migrations (useful for repair)
     */
    public static function reset() {
        delete_option(self::DB_VERSION_KEY);
    }
}
