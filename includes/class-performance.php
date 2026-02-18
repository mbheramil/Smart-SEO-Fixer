<?php
/**
 * Performance Profiler
 * 
 * Tracks plugin load time, database query count, and memory usage.
 * Provides an admin dashboard with trend data.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Performance {
    
    /**
     * Profile start time
     */
    private static $start_time = 0;
    private static $start_memory = 0;
    private static $start_queries = 0;
    
    /**
     * Begin profiling (called at plugin load)
     */
    public static function start() {
        self::$start_time = microtime(true);
        self::$start_memory = memory_get_usage();
        self::$start_queries = get_num_queries();
    }
    
    /**
     * End profiling and record (called at wp_loaded or shutdown)
     */
    public static function end() {
        if (self::$start_time === 0) return;
        
        $duration = round((microtime(true) - self::$start_time) * 1000, 2); // ms
        $memory = round((memory_get_usage() - self::$start_memory) / 1024, 1); // KB
        $queries = get_num_queries() - self::$start_queries;
        $peak_memory = round(memory_get_peak_usage() / 1024 / 1024, 2); // MB
        
        // Store as transient (refreshed each request, no DB bloat)
        set_transient('ssf_perf_latest', [
            'duration'    => $duration,
            'memory_kb'   => $memory,
            'queries'     => $queries,
            'peak_mb'     => $peak_memory,
            'timestamp'   => current_time('mysql'),
            'is_admin'    => is_admin(),
            'page'        => isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '',
        ], HOUR_IN_SECONDS);
        
        // Append to rolling history (last 100 samples)
        self::record_sample($duration, $memory, $queries, $peak_memory);
    }
    
    /**
     * Record a performance sample to history
     */
    private static function record_sample($duration, $memory, $queries, $peak) {
        $history = get_option('ssf_perf_history', []);
        
        $history[] = [
            'time'     => current_time('U'),
            'duration' => $duration,
            'memory'   => $memory,
            'queries'  => $queries,
            'peak'     => $peak,
            'admin'    => is_admin() ? 1 : 0,
        ];
        
        // Keep last 200 samples
        if (count($history) > 200) {
            $history = array_slice($history, -200);
        }
        
        update_option('ssf_perf_history', $history, false); // autoload=false
    }
    
    /**
     * Get the latest performance snapshot
     */
    public static function get_latest() {
        return get_transient('ssf_perf_latest') ?: [
            'duration' => 0, 'memory_kb' => 0, 'queries' => 0, 'peak_mb' => 0,
            'timestamp' => '', 'is_admin' => false, 'page' => '',
        ];
    }
    
    /**
     * Get performance history for charting
     */
    public static function get_history($limit = 50) {
        $history = get_option('ssf_perf_history', []);
        return array_slice($history, -$limit);
    }
    
    /**
     * Get average stats
     */
    public static function get_averages() {
        $history = get_option('ssf_perf_history', []);
        
        if (empty($history)) {
            return ['avg_duration' => 0, 'avg_memory' => 0, 'avg_queries' => 0, 'samples' => 0, 'avg_peak' => 0];
        }
        
        $count = count($history);
        $sum_duration = 0;
        $sum_memory = 0;
        $sum_queries = 0;
        $sum_peak = 0;
        
        foreach ($history as $sample) {
            $sum_duration += $sample['duration'];
            $sum_memory += $sample['memory'];
            $sum_queries += $sample['queries'];
            $sum_peak += $sample['peak'];
        }
        
        return [
            'avg_duration' => round($sum_duration / $count, 1),
            'avg_memory'   => round($sum_memory / $count, 1),
            'avg_queries'  => round($sum_queries / $count, 1),
            'avg_peak'     => round($sum_peak / $count, 2),
            'samples'      => $count,
        ];
    }
    
    /**
     * Get environment info
     */
    public static function get_environment() {
        global $wpdb;
        
        return [
            'php_version'    => PHP_VERSION,
            'wp_version'     => get_bloginfo('version'),
            'plugin_version' => defined('SSF_VERSION') ? SSF_VERSION : 'unknown',
            'mysql_version'  => $wpdb->get_var('SELECT VERSION()'),
            'server'         => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field($_SERVER['SERVER_SOFTWARE']) : 'Unknown',
            'memory_limit'   => ini_get('memory_limit'),
            'max_exec_time'  => ini_get('max_execution_time'),
            'php_sapi'       => php_sapi_name(),
            'active_plugins' => count(get_option('active_plugins', [])),
            'db_tables'      => self::count_plugin_tables(),
            'total_posts'    => wp_count_posts()->publish,
        ];
    }
    
    /**
     * Count plugin-specific database tables and their sizes
     */
    private static function count_plugin_tables() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'ssf_';
        
        $tables = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT table_name, 
                        ROUND(data_length / 1024, 1) as data_kb,
                        ROUND(index_length / 1024, 1) as index_kb,
                        table_rows
                 FROM information_schema.tables 
                 WHERE table_schema = %s AND table_name LIKE %s",
                DB_NAME, $prefix . '%'
            )
        );
        
        return $tables ?: [];
    }
    
    /**
     * Clear performance history
     */
    public static function clear_history() {
        delete_option('ssf_perf_history');
        delete_transient('ssf_perf_latest');
    }
}
