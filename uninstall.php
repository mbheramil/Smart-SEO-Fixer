<?php
/**
 * Uninstall Smart SEO Fixer
 *
 * Removes all plugin data when the plugin is deleted (not just deactivated).
 * This only runs when a user explicitly deletes the plugin from the admin.
 */

// Abort if not called by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Only clean up if the user opted in (future setting) or always clean
// For now, always clean up on delete

global $wpdb;

// 1. Remove plugin options
$options = [
    'ssf_openai_api_key',
    'ssf_openai_model',
    'ssf_auto_meta',
    'ssf_auto_alt_text',
    'ssf_enable_schema',
    'ssf_enable_sitemap',
    'ssf_disable_other_seo_output',
    'ssf_background_seo_cron',
    'ssf_github_token',
    'ssf_title_separator',
    'ssf_homepage_title',
    'ssf_homepage_description',
    'ssf_post_types',
    'ssf_local_seo_data',
    'ssf_redirects',
    'ssf_404_log',
    'ssf_cron_last_run',
    'ssf_gsc_client_id',
    'ssf_gsc_client_secret',
    'ssf_gsc_site_url',
    'ssf_gsc_tokens',
];

foreach ($options as $option) {
    delete_option($option);
}

// 2. Remove post meta
$meta_keys = [
    '_ssf_seo_title',
    '_ssf_meta_description',
    '_ssf_focus_keyword',
    '_ssf_canonical_url',
    '_ssf_noindex',
    '_ssf_nofollow',
    '_ssf_custom_schema',
    '_ssf_schema_type',
    '_ssf_product_brand',
    '_ssf_product_gtin',
    '_ssf_product_mpn',
];

foreach ($meta_keys as $key) {
    $wpdb->query(
        $wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", $key)
    );
}

// 3. Remove term meta (WooCommerce category SEO)
$term_meta_keys = [
    '_ssf_cat_seo_title',
    '_ssf_cat_meta_description',
    '_ssf_cat_focus_keyword',
];

foreach ($term_meta_keys as $key) {
    $wpdb->query(
        $wpdb->prepare("DELETE FROM {$wpdb->termmeta} WHERE meta_key = %s", $key)
    );
}

// 4. Drop custom database tables
$tables = [
    $wpdb->prefix . 'ssf_seo_scores',
    $wpdb->prefix . 'ssf_history',
    $wpdb->prefix . 'ssf_logs',
    $wpdb->prefix . 'ssf_jobs',
];
foreach ($tables as $table_name) {
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// 5. Clear any scheduled cron events
$timestamp = wp_next_scheduled('ssf_cron_generate_missing_seo');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'ssf_cron_generate_missing_seo');
}
$timestamp = wp_next_scheduled('ssf_process_job_queue');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'ssf_process_job_queue');
}

// 6. Clear transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%ssf_%' AND option_name LIKE '%transient%'");

// 7. Flush rewrite rules (sitemap rules)
flush_rewrite_rules();
