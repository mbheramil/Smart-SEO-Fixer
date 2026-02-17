<?php
/**
 * Search Console Fixer Class
 * 
 * Detects and fixes common Search Console issues:
 * - Trailing slash inconsistencies
 * - URL parameter canonicalization
 * - Duplicate content issues
 * - Redirect chains
 * - noindex conflicts
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Search_Console {
    
    /**
     * Site's preferred trailing slash setting
     */
    private $use_trailing_slash = true;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Wait for init to detect trailing slash (needs permalink_structure option)
        add_action('init', [$this, 'init_settings'], 1);
        
        // Fix canonical URLs to be consistent
        add_filter('get_permalink', [$this, 'normalize_permalink'], 9999, 2);
        add_filter('post_link', [$this, 'normalize_permalink'], 9999, 2);
        add_filter('page_link', [$this, 'normalize_page_link'], 9999, 2);
        add_filter('post_type_link', [$this, 'normalize_permalink'], 9999, 2);
        
        // Redirect inconsistent URLs
        add_action('template_redirect', [$this, 'redirect_inconsistent_urls'], 1);
        
        // Strip UTM parameters from canonical
        add_filter('ssf_canonical_url', [$this, 'strip_tracking_params']);
        
        // Remove default WordPress canonical (let meta-manager handle it)
        add_action('wp', [$this, 'remove_default_canonical']);
        
        // Fix sitemap URLs to match canonical format
        add_filter('ssf_sitemap_url', [$this, 'normalize_sitemap_url']);
        
        // Admin AJAX handlers
        add_action('wp_ajax_ssf_scan_url_issues', [$this, 'ajax_scan_url_issues']);
        add_action('wp_ajax_ssf_fix_url_issues', [$this, 'ajax_fix_url_issues']);
        add_action('wp_ajax_ssf_get_gsc_summary', [$this, 'ajax_get_gsc_summary']);
    }
    
    /**
     * Initialize settings after WordPress is loaded
     */
    public function init_settings() {
        $this->use_trailing_slash = $this->detect_trailing_slash_preference();
    }
    
    /**
     * Remove default WordPress canonical
     */
    public function remove_default_canonical() {
        remove_action('wp_head', 'rel_canonical');
    }
    
    /**
     * Detect if site uses trailing slashes in permalinks
     */
    private function detect_trailing_slash_preference() {
        $permalink_structure = get_option('permalink_structure');
        
        // If permalink structure ends with /, use trailing slashes
        if (!empty($permalink_structure)) {
            return substr($permalink_structure, -1) === '/';
        }
        
        // Default WordPress behavior uses trailing slashes
        return true;
    }
    
    /**
     * Normalize permalink to consistent format
     */
    public function normalize_permalink($permalink, $post = null) {
        if (empty($permalink)) {
            return $permalink;
        }
        
        // Don't modify external URLs
        if (strpos($permalink, home_url()) !== 0) {
            return $permalink;
        }
        
        // Parse URL
        $parsed = wp_parse_url($permalink);
        $path = $parsed['path'] ?? '';
        
        // Skip if it's a file (has extension)
        if (preg_match('/\.[a-zA-Z0-9]+$/', $path)) {
            return $permalink;
        }
        
        // Normalize trailing slash
        if ($this->use_trailing_slash) {
            if (!empty($path) && substr($path, -1) !== '/') {
                $path .= '/';
            }
        } else {
            $path = rtrim($path, '/');
            if (empty($path)) {
                $path = '/';
            }
        }
        
        // Rebuild URL without query params for canonical purposes
        $normalized = $parsed['scheme'] . '://' . $parsed['host'];
        if (!empty($parsed['port']) && $parsed['port'] != 80 && $parsed['port'] != 443) {
            $normalized .= ':' . $parsed['port'];
        }
        $normalized .= $path;
        
        // Keep query string if present (but canonical will strip tracking params)
        if (!empty($parsed['query'])) {
            $normalized .= '?' . $parsed['query'];
        }
        
        return $normalized;
    }
    
    /**
     * Normalize page link
     */
    public function normalize_page_link($link, $post_id) {
        return $this->normalize_permalink($link, get_post($post_id));
    }
    
    /**
     * Redirect requests with inconsistent trailing slashes or tracking params
     */
    public function redirect_inconsistent_urls() {
        // Don't redirect in admin or AJAX
        if (is_admin() || wp_doing_ajax()) {
            return;
        }
        
        // Don't redirect POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }
        
        $request_uri = $_SERVER['REQUEST_URI'];
        $parsed = wp_parse_url($request_uri);
        $path = $parsed['path'] ?? '/';
        $query = $parsed['query'] ?? '';
        
        // Skip files
        if (preg_match('/\.[a-zA-Z0-9]+$/', $path)) {
            return;
        }
        
        // Skip WordPress special paths and feeds
        $skip_paths = ['/wp-admin', '/wp-login', '/wp-json', '/xmlrpc', '/wp-cron', '/feed', '/sitemap'];
        foreach ($skip_paths as $skip) {
            if (strpos($path, $skip) === 0) {
                return;
            }
        }
        
        // Prevent redirect loops — only redirect once per request
        if (!empty($_SERVER['HTTP_X_REDIRECT_BY']) || headers_sent()) {
            return;
        }
        
        $needs_redirect = false;
        $new_path = $path;
        $new_query = $query;
        
        // Check trailing slash
        if ($path !== '/') {
            $has_trailing = substr($path, -1) === '/';
            
            if ($this->use_trailing_slash && !$has_trailing) {
                $new_path = $path . '/';
                $needs_redirect = true;
            } elseif (!$this->use_trailing_slash && $has_trailing) {
                $new_path = rtrim($path, '/');
                $needs_redirect = true;
            }
        }
        
        // Check for tracking parameters that shouldn't be indexed
        if (!empty($query)) {
            $clean_query = $this->strip_tracking_params_from_query($query);
            if ($clean_query !== $query) {
                $new_query = $clean_query;
                $needs_redirect = true;
            }
        }
        
        if ($needs_redirect) {
            $redirect_url = home_url($new_path);
            if (!empty($new_query)) {
                $redirect_url .= '?' . $new_query;
            }
            
            wp_safe_redirect($redirect_url, 301, 'Smart SEO Fixer');
            exit;
        }
    }
    
    /**
     * Strip tracking parameters from URL
     */
    public function strip_tracking_params($url) {
        if (empty($url)) {
            return $url;
        }
        
        $parsed = wp_parse_url($url);
        
        if (empty($parsed['query'])) {
            return $url;
        }
        
        $clean_query = $this->strip_tracking_params_from_query($parsed['query']);
        
        $clean_url = $parsed['scheme'] . '://' . $parsed['host'];
        if (!empty($parsed['port']) && $parsed['port'] != 80 && $parsed['port'] != 443) {
            $clean_url .= ':' . $parsed['port'];
        }
        $clean_url .= $parsed['path'] ?? '/';
        
        if (!empty($clean_query)) {
            $clean_url .= '?' . $clean_query;
        }
        
        return $clean_url;
    }
    
    /**
     * Strip tracking parameters from query string
     */
    private function strip_tracking_params_from_query($query) {
        if (empty($query)) {
            return '';
        }
        
        parse_str($query, $params);
        
        // Common tracking parameters to remove
        $tracking_params = [
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            'utm_id', 'utm_source_platform', 'utm_creative_format',
            'fbclid', 'gclid', 'gclsrc', 'dclid', 'gbraid', 'wbraid',
            'msclkid', 'twclid', 'igshid', 'mc_cid', 'mc_eid',
            'ref', 'referrer', 'source', 'campaign',
            '_ga', '_gl', '_hsenc', '_hsmi', 'hsa_acc', 'hsa_cam', 'hsa_grp',
            'hsa_ad', 'hsa_src', 'hsa_tgt', 'hsa_kw', 'hsa_mt', 'hsa_net', 'hsa_ver'
        ];
        
        // Allow filtering of tracking params
        $tracking_params = apply_filters('ssf_tracking_params', $tracking_params);
        
        foreach ($tracking_params as $param) {
            unset($params[$param]);
        }
        
        return http_build_query($params);
    }
    
    /**
     * Normalize sitemap URLs
     */
    public function normalize_sitemap_url($url) {
        return $this->normalize_permalink($url);
    }
    
    /**
     * Scan site for URL consistency issues
     */
    public function scan_url_issues() {
        global $wpdb;
        
        $issues = [
            'trailing_slash' => [],
            'duplicate_canonical' => [],
            'noindex_in_sitemap' => [],
            'redirect_chains' => [],
            'missing_canonical' => [],
            'parameter_urls' => [],
        ];
        
        // Get all published posts
        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        
        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_name, post_type, post_title 
                FROM {$wpdb->posts} 
                WHERE post_status = 'publish' 
                AND post_type IN ($placeholders)
                ORDER BY post_date DESC",
                ...$post_types
            )
        );
        
        foreach ($posts as $post) {
            $permalink = get_permalink($post->ID);
            $parsed = wp_parse_url($permalink);
            $path = $parsed['path'] ?? '';
            
            // Check trailing slash consistency
            if (!empty($path) && $path !== '/') {
                $has_trailing = substr($path, -1) === '/';
                if ($this->use_trailing_slash !== $has_trailing) {
                    $issues['trailing_slash'][] = [
                        'post_id' => $post->ID,
                        'title' => $post->post_title,
                        'url' => $permalink,
                        'expected' => $this->use_trailing_slash ? 'with trailing slash' : 'without trailing slash',
                    ];
                }
            }
            
            // Check for noindex but in sitemap (conflict)
            $noindex = get_post_meta($post->ID, '_ssf_noindex', true);
            if ($noindex) {
                $issues['noindex_in_sitemap'][] = [
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => $permalink,
                    'issue' => 'Page is noindexed but may be in sitemap',
                ];
            }
            
            // Check for custom canonical pointing elsewhere (potential duplicate)
            $custom_canonical = get_post_meta($post->ID, '_ssf_canonical_url', true);
            if (!empty($custom_canonical) && $custom_canonical !== $permalink) {
                $issues['duplicate_canonical'][] = [
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => $permalink,
                    'canonical' => $custom_canonical,
                    'issue' => 'Custom canonical points to different URL',
                ];
            }
        }
        
        // Check redirects for chains
        $redirects = get_option('ssf_redirects', []);
        foreach ($redirects as $redirect) {
            if (empty($redirect['enabled'])) continue;
            
            // Check if 'to' URL also has a redirect (chain)
            $to_path = trim(wp_parse_url($redirect['to'], PHP_URL_PATH), '/');
            foreach ($redirects as $r2) {
                if (empty($r2['enabled'])) continue;
                if (trim($r2['from'], '/') === $to_path) {
                    $issues['redirect_chains'][] = [
                        'from' => $redirect['from'],
                        'to' => $redirect['to'],
                        'chain_to' => $r2['to'],
                        'issue' => 'Redirect chain detected',
                    ];
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Fix URL issues automatically
     */
    public function fix_url_issues($issue_type = 'all') {
        $fixed = [];
        
        switch ($issue_type) {
            case 'trailing_slash':
            case 'all':
                // Trailing slash issues are fixed by the redirect handler
                // Just flush rewrite rules to ensure consistency
                flush_rewrite_rules();
                $fixed[] = 'Rewrite rules flushed for trailing slash consistency';
                
                if ($issue_type !== 'all') break;
                // Fall through
                
            case 'redirect_chains':
                // Fix redirect chains by updating to final destination
                $redirects = get_option('ssf_redirects', []);
                $redirect_map = [];
                
                foreach ($redirects as $r) {
                    if (!empty($r['enabled'])) {
                        $redirect_map[trim($r['from'], '/')] = $r['to'];
                    }
                }
                
                $updated = false;
                foreach ($redirects as &$redirect) {
                    if (empty($redirect['enabled'])) continue;
                    
                    $final_to = $redirect['to'];
                    $visited = [$redirect['from']];
                    $iterations = 0;
                    
                    // Follow the chain to find final destination
                    while ($iterations < 10) {
                        $to_path = trim(wp_parse_url($final_to, PHP_URL_PATH), '/');
                        if (isset($redirect_map[$to_path]) && !in_array($to_path, $visited)) {
                            $final_to = $redirect_map[$to_path];
                            $visited[] = $to_path;
                            $iterations++;
                        } else {
                            break;
                        }
                    }
                    
                    if ($final_to !== $redirect['to']) {
                        $redirect['to'] = $final_to;
                        $redirect['note'] = ($redirect['note'] ?? '') . ' [Chain fixed]';
                        $updated = true;
                    }
                }
                
                if ($updated) {
                    update_option('ssf_redirects', $redirects);
                    $fixed[] = 'Redirect chains resolved';
                }
                
                if ($issue_type !== 'all') break;
                // Fall through
                
            case 'noindex_in_sitemap':
                // This is handled by the sitemap generator which excludes noindex pages
                $fixed[] = 'Sitemap automatically excludes noindex pages';
                break;
        }
        
        return $fixed;
    }
    
    /**
     * Get summary of potential GSC issues
     */
    public function get_gsc_summary() {
        global $wpdb;
        
        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        
        // Count posts with redirects pointing to them
        $redirects = get_option('ssf_redirects', []);
        $redirect_count = count(array_filter($redirects, function($r) {
            return !empty($r['enabled']);
        }));
        
        // Count noindex pages
        $noindex_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID) 
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_status = 'publish'
                AND p.post_type IN ($placeholders)
                AND pm.meta_key = '_ssf_noindex'
                AND pm.meta_value = '1'",
                ...$post_types
            )
        );
        
        // Count pages with custom canonicals
        $custom_canonical_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID) 
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_status = 'publish'
                AND p.post_type IN ($placeholders)
                AND pm.meta_key = '_ssf_canonical_url'
                AND pm.meta_value != ''",
                ...$post_types
            )
        );
        
        // 404 log count
        $log_404 = get_option('ssf_404_log', []);
        $count_404 = count($log_404);
        
        // Scan for issues
        $issues = $this->scan_url_issues();
        
        return [
            'trailing_slash_mode' => $this->use_trailing_slash ? 'with slash' : 'without slash',
            'active_redirects' => $redirect_count,
            'noindex_pages' => intval($noindex_count),
            'custom_canonicals' => intval($custom_canonical_count),
            'tracked_404s' => $count_404,
            'issues' => [
                'trailing_slash' => count($issues['trailing_slash']),
                'duplicate_canonical' => count($issues['duplicate_canonical']),
                'noindex_conflicts' => count($issues['noindex_in_sitemap']),
                'redirect_chains' => count($issues['redirect_chains']),
            ],
            'total_issues' => array_sum([
                count($issues['trailing_slash']),
                count($issues['duplicate_canonical']),
                count($issues['noindex_in_sitemap']),
                count($issues['redirect_chains']),
            ]),
        ];
    }
    
    // ========================================
    // AJAX Handlers
    // ========================================
    
    private function verify() {
        check_ajax_referer('ssf_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
    }
    
    public function ajax_scan_url_issues() {
        $this->verify();
        
        $issues = $this->scan_url_issues();
        
        wp_send_json_success([
            'issues' => $issues,
            'total' => array_sum(array_map('count', $issues)),
        ]);
    }
    
    public function ajax_fix_url_issues() {
        $this->verify();
        
        $issue_type = sanitize_text_field($_POST['issue_type'] ?? 'all');
        $fixed = $this->fix_url_issues($issue_type);
        
        wp_send_json_success([
            'fixed' => $fixed,
            'message' => __('Issues fixed successfully.', 'smart-seo-fixer'),
        ]);
    }
    
    public function ajax_get_gsc_summary() {
        $this->verify();
        
        $summary = $this->get_gsc_summary();
        
        wp_send_json_success($summary);
    }
}
