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
    /**
     * Cached home URL to avoid repeated option lookups
     */
    private $home_url_host = '';
    
    public function __construct() {
        // Wait for init to detect trailing slash (needs permalink_structure option)
        add_action('init', [$this, 'init_settings'], 1);
        
        // Lightweight frontend hooks only
        if (!is_admin()) {
            // Let WordPress handle trailing slash redirects natively via redirect_canonical().
            // We only hook in to strip tracking parameters from the redirect target.
            add_filter('redirect_canonical', [$this, 'filter_canonical_redirect'], 10, 2);
            
            // Strip UTM parameters from our canonical output only
            add_filter('ssf_canonical_url', [$this, 'strip_tracking_params']);
            
            // Remove default WordPress canonical (let meta-manager handle it)
            add_action('wp', [$this, 'remove_default_canonical']);
            
            // Fix sitemap URLs to match canonical format
            add_filter('ssf_sitemap_url', [$this, 'normalize_sitemap_url']);
        }
        
        // Admin AJAX handlers — only register in admin context
        if (is_admin()) {
            add_action('wp_ajax_ssf_scan_url_issues', [$this, 'ajax_scan_url_issues']);
            add_action('wp_ajax_ssf_fix_url_issues', [$this, 'ajax_fix_url_issues']);
            add_action('wp_ajax_ssf_get_gsc_summary', [$this, 'ajax_get_gsc_summary']);
            add_action('wp_ajax_ssf_fix_indexability_issue', [$this, 'ajax_fix_indexability_issue']);
        }
    }
    
    /**
     * Initialize settings after WordPress is loaded
     */
    public function init_settings() {
        $this->use_trailing_slash = $this->detect_trailing_slash_preference();
        $this->home_url_host = wp_parse_url(home_url(), PHP_URL_HOST);
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
     * Filter WordPress's native redirect_canonical to strip tracking params
     * 
     * WordPress already handles trailing slash normalization, www vs non-www,
     * and other URL canonicalization. We only need to strip UTM/tracking
     * parameters from the redirect target. This avoids redirect loops that
     * occur when a custom redirect function conflicts with WordPress's own.
     *
     * @param string $redirect_url The URL WordPress wants to redirect to
     * @param string $requested_url The originally requested URL
     * @return string|false Modified redirect URL or false to cancel redirect
     */
    public function filter_canonical_redirect($redirect_url, $requested_url) {
        if (empty($redirect_url)) {
            return $redirect_url;
        }
        
        // Strip tracking parameters from the redirect target
        $parsed = wp_parse_url($redirect_url);
        if (!empty($parsed['query'])) {
            $clean_query = $this->strip_tracking_params_from_query($parsed['query']);
            if ($clean_query !== $parsed['query']) {
                $base = strtok($redirect_url, '?');
                $redirect_url = !empty($clean_query) ? $base . '?' . $clean_query : $base;
            }
        }
        
        return $redirect_url;
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
     * Comprehensive indexability audit — maps to all Google Search Console issue types
     * 
     * GSC Issue Types Covered:
     * 1. Page with redirect
     * 2. Excluded by 'noindex' tag
     * 3. Alternate page with proper canonical tag
     * 4. Duplicate without user-selected canonical
     * 5. Not found (404)
     * 6. Crawled - currently not indexed (thin content)
     * 7. Duplicate, Google chose different canonical than user
     * 8. Blocked by robots.txt
     * 9. Discovered - currently not indexed (missing SEO / orphaned)
     */
    public function scan_url_issues() {
        global $wpdb;
        
        $issues = [
            'trailing_slash'       => [],
            'noindex_conflict'     => [],
            'custom_canonical'     => [],
            'redirect_chains'      => [],
            'redirected_pages'     => [],
            'thin_content'         => [],
            'missing_seo'         => [],
            'duplicate_titles'     => [],
            'duplicate_descs'      => [],
            'blocked_by_robots'    => [],
            'orphaned_pages'       => [],
            'not_found_404'        => [],
        ];
        
        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        
        // Get all published posts with content
        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_name, post_type, post_title, post_content 
                FROM {$wpdb->posts} 
                WHERE post_status = 'publish' 
                AND post_type IN ($placeholders)
                ORDER BY post_date DESC",
                ...$post_types
            )
        );
        
        // Pre-load meta for all posts in one query (performance)
        $post_ids = wp_list_pluck($posts, 'ID');
        $meta_cache = [];
        if (!empty($post_ids)) {
            $ids_str = implode(',', array_map('intval', $post_ids));
            $all_meta = $wpdb->get_results(
                "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} 
                WHERE post_id IN ($ids_str) 
                AND meta_key IN ('_ssf_seo_title','_ssf_meta_description','_ssf_focus_keyword','_ssf_noindex','_ssf_canonical_url')"
            );
            foreach ($all_meta as $m) {
                $meta_cache[$m->post_id][$m->meta_key] = $m->meta_value;
            }
        }
        
        // Track titles and descriptions for duplicate detection
        $title_map = [];
        $desc_map = [];
        
        // Build internal link map for orphaned page detection
        $linked_ids = [];
        foreach ($posts as $post) {
            if (preg_match_all('/href=["\']([^"\']+)["\']/', $post->post_content, $matches)) {
                foreach ($matches[1] as $link) {
                    $link_post_id = url_to_postid($link);
                    if ($link_post_id > 0) {
                        $linked_ids[$link_post_id] = true;
                    }
                }
            }
        }
        
        // Parse robots.txt rules
        $robots_rules = $this->parse_robots_txt();
        
        // Get active redirects
        $redirects = get_option('ssf_redirects', []);
        $redirect_froms = [];
        foreach ($redirects as $r) {
            if (!empty($r['enabled'])) {
                $redirect_froms[trim($r['from'], '/')] = $r;
            }
        }
        
        foreach ($posts as $post) {
            $permalink = get_permalink($post->ID);
            $parsed = wp_parse_url($permalink);
            $path = $parsed['path'] ?? '';
            $meta = $meta_cache[$post->ID] ?? [];
            
            $seo_title = $meta['_ssf_seo_title'] ?? '';
            $meta_desc = $meta['_ssf_meta_description'] ?? '';
            $noindex = $meta['_ssf_noindex'] ?? '';
            $canonical = $meta['_ssf_canonical_url'] ?? '';
            $word_count = str_word_count(strip_tags($post->post_content));
            
            // --- 1. Trailing slash consistency ---
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
            
            // --- 2. Noindex conflict (noindex but should be indexed) ---
            if ($noindex) {
                $issues['noindex_conflict'][] = [
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => $permalink,
                    'issue' => __('Page has noindex tag — excluded from Google index', 'smart-seo-fixer'),
                    'fixable' => true,
                ];
            }
            
            // --- 3. Custom canonical (alternate page) ---
            if (!empty($canonical) && $canonical !== $permalink) {
                $issues['custom_canonical'][] = [
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => $permalink,
                    'canonical' => $canonical,
                    'issue' => __('Custom canonical points to different URL', 'smart-seo-fixer'),
                ];
            }
            
            // --- 4 & 7. Duplicate titles (causes "Duplicate without canonical" / "Google chose different canonical") ---
            $effective_title = !empty($seo_title) ? $seo_title : $post->post_title;
            if (!empty($effective_title)) {
                $title_key = strtolower(trim($effective_title));
                if (isset($title_map[$title_key])) {
                    $issues['duplicate_titles'][] = [
                        'post_id' => $post->ID,
                        'title' => $post->post_title,
                        'seo_title' => $effective_title,
                        'url' => $permalink,
                        'duplicate_of' => $title_map[$title_key]['post_id'],
                        'duplicate_title' => $title_map[$title_key]['title'],
                        'issue' => sprintf(__('Same title as "%s"', 'smart-seo-fixer'), $title_map[$title_key]['title']),
                        'fixable' => true,
                    ];
                } else {
                    $title_map[$title_key] = ['post_id' => $post->ID, 'title' => $post->post_title];
                }
            }
            
            // --- 4. Duplicate descriptions ---
            if (!empty($meta_desc)) {
                $desc_key = strtolower(trim($meta_desc));
                if (isset($desc_map[$desc_key])) {
                    $issues['duplicate_descs'][] = [
                        'post_id' => $post->ID,
                        'title' => $post->post_title,
                        'url' => $permalink,
                        'duplicate_of' => $desc_map[$desc_key]['post_id'],
                        'issue' => sprintf(__('Same description as "%s"', 'smart-seo-fixer'), $desc_map[$desc_key]['title']),
                        'fixable' => true,
                    ];
                } else {
                    $desc_map[$desc_key] = ['post_id' => $post->ID, 'title' => $post->post_title];
                }
            }
            
            // --- 6. Thin content (causes "Crawled - currently not indexed") ---
            if ($word_count < 300 && $post->post_type !== 'page') {
                $issues['thin_content'][] = [
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => $permalink,
                    'word_count' => $word_count,
                    'issue' => sprintf(__('%d words (Google prefers 300+)', 'smart-seo-fixer'), $word_count),
                ];
            }
            
            // --- 9. Missing SEO data (causes "Discovered - currently not indexed") ---
            if (empty($seo_title) || empty($meta_desc)) {
                $missing = [];
                if (empty($seo_title)) $missing[] = __('SEO title', 'smart-seo-fixer');
                if (empty($meta_desc)) $missing[] = __('meta description', 'smart-seo-fixer');
                
                $issues['missing_seo'][] = [
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => $permalink,
                    'missing' => $missing,
                    'issue' => sprintf(__('Missing: %s', 'smart-seo-fixer'), implode(', ', $missing)),
                    'fixable' => true,
                ];
            }
            
            // --- 8. Blocked by robots.txt ---
            if (!empty($path) && $this->is_blocked_by_robots($path, $robots_rules)) {
                $issues['blocked_by_robots'][] = [
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => $permalink,
                    'path' => $path,
                    'issue' => __('URL matches a Disallow rule in robots.txt', 'smart-seo-fixer'),
                ];
            }
            
            // --- 9. Orphaned pages (no internal links = hard for Google to discover) ---
            if (!isset($linked_ids[$post->ID]) && $post->post_type !== 'page') {
                $issues['orphaned_pages'][] = [
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => $permalink,
                    'issue' => __('No internal links point to this page', 'smart-seo-fixer'),
                ];
            }
            
            // --- 1. Check if published page has a redirect FROM it ---
            $post_path = trim($path, '/');
            if (isset($redirect_froms[$post_path])) {
                $issues['redirected_pages'][] = [
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => $permalink,
                    'redirect_to' => $redirect_froms[$post_path]['to'],
                    'issue' => sprintf(__('Published page has active redirect to %s', 'smart-seo-fixer'), $redirect_froms[$post_path]['to']),
                ];
            }
        }
        
        // --- Redirect chains ---
        foreach ($redirects as $redirect) {
            if (empty($redirect['enabled'])) continue;
            $to_path = trim(wp_parse_url($redirect['to'], PHP_URL_PATH) ?? '', '/');
            foreach ($redirects as $r2) {
                if (empty($r2['enabled'])) continue;
                if (trim($r2['from'], '/') === $to_path) {
                    $issues['redirect_chains'][] = [
                        'from' => $redirect['from'],
                        'to' => $redirect['to'],
                        'chain_to' => $r2['to'],
                        'issue' => __('Redirect chain detected', 'smart-seo-fixer'),
                    ];
                }
            }
        }
        
        // --- 5. Not found (404) log ---
        $log_404 = get_option('ssf_404_log', []);
        $top_404s = array_slice($log_404, 0, 20);
        foreach ($top_404s as $entry) {
            $issues['not_found_404'][] = [
                'url' => $entry['url'] ?? '',
                'hits' => $entry['hits'] ?? 1,
                'last_hit' => $entry['last_hit'] ?? '',
                'referrer' => $entry['referrer'] ?? '',
                'issue' => sprintf(__('404 error — %d hits', 'smart-seo-fixer'), $entry['hits'] ?? 1),
                'fixable' => true,
            ];
        }
        
        return $issues;
    }
    
    /**
     * Parse robots.txt and extract Disallow rules for all user-agents
     */
    private function parse_robots_txt() {
        $robots_url = home_url('/robots.txt');
        $response = wp_remote_get($robots_url, ['timeout' => 5, 'sslverify' => false]);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return [];
        }
        
        $body = wp_remote_retrieve_body($response);
        $rules = [];
        $current_agent = '*';
        
        foreach (explode("\n", $body) as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') continue;
            
            if (preg_match('/^User-agent:\s*(.+)/i', $line, $m)) {
                $current_agent = trim($m[1]);
            } elseif (preg_match('/^Disallow:\s*(.+)/i', $line, $m)) {
                $path = trim($m[1]);
                if (!empty($path)) {
                    $rules[] = [
                        'agent' => $current_agent,
                        'path' => $path,
                    ];
                }
            }
        }
        
        return $rules;
    }
    
    /**
     * Check if a URL path is blocked by any robots.txt Disallow rule
     */
    private function is_blocked_by_robots($path, $rules) {
        foreach ($rules as $rule) {
            // Only check rules for all agents or Googlebot
            if ($rule['agent'] !== '*' && stripos($rule['agent'], 'googlebot') === false) {
                continue;
            }
            
            $disallow = $rule['path'];
            
            // Wildcard matching
            if (strpos($disallow, '*') !== false) {
                $pattern = str_replace('*', '.*', preg_quote($disallow, '/'));
                if (preg_match('/^' . $pattern . '/', $path)) {
                    return true;
                }
            } elseif (strpos($path, $disallow) === 0) {
                return true;
            }
        }
        
        return false;
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
        
        // Count missing SEO
        $missing_seo = $wpdb->get_var(
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
        
        // Count thin content (< 300 words is approximate via char count)
        $thin_content = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                FROM {$wpdb->posts}
                WHERE post_status = 'publish'
                AND post_type IN ($placeholders)
                AND LENGTH(TRIM(post_content)) < 1500",
                ...$post_types
            )
        );
        
        return [
            'trailing_slash_mode' => $this->use_trailing_slash ? 'with slash' : 'without slash',
            'active_redirects' => $redirect_count,
            'noindex_pages' => intval($noindex_count),
            'custom_canonicals' => intval($custom_canonical_count),
            'tracked_404s' => $count_404,
            'missing_seo' => intval($missing_seo),
            'thin_content' => intval($thin_content),
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
    
    /**
     * Fix a specific indexability issue
     */
    public function ajax_fix_indexability_issue() {
        $this->verify();
        
        $fix_type = sanitize_text_field($_POST['fix_type'] ?? '');
        $post_id = intval($_POST['post_id'] ?? 0);
        $fixed = [];
        
        switch ($fix_type) {
            case 'remove_noindex':
                if ($post_id > 0) {
                    delete_post_meta($post_id, '_ssf_noindex');
                    $fixed[] = sprintf(__('Removed noindex from "%s"', 'smart-seo-fixer'), get_the_title($post_id));
                }
                break;
                
            case 'generate_seo':
                if ($post_id > 0 && class_exists('SSF_OpenAI')) {
                    $openai = new SSF_OpenAI();
                    if ($openai->is_configured()) {
                        $post = get_post($post_id);
                        $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
                        
                        $seo_title = get_post_meta($post_id, '_ssf_seo_title', true);
                        if (empty($seo_title)) {
                            $title = $openai->generate_title($post->post_content, $post->post_title, $focus_keyword);
                            if (!is_wp_error($title) && !empty(trim($title))) {
                                update_post_meta($post_id, '_ssf_seo_title', sanitize_text_field(trim($title)));
                                $fixed[] = __('Generated SEO title', 'smart-seo-fixer');
                            }
                        }
                        
                        $meta_desc = get_post_meta($post_id, '_ssf_meta_description', true);
                        if (empty($meta_desc)) {
                            $desc = $openai->generate_meta_description($post->post_content, '', $focus_keyword);
                            if (!is_wp_error($desc) && !empty(trim($desc))) {
                                update_post_meta($post_id, '_ssf_meta_description', sanitize_textarea_field(trim($desc)));
                                $fixed[] = __('Generated meta description', 'smart-seo-fixer');
                            }
                        }
                        
                        if (empty($focus_keyword)) {
                            $keywords = $openai->suggest_keywords($post->post_content, $post->post_title);
                            if (!is_wp_error($keywords) && is_array($keywords) && !empty($keywords['primary'])) {
                                update_post_meta($post_id, '_ssf_focus_keyword', sanitize_text_field($keywords['primary']));
                                $fixed[] = __('Generated focus keyword', 'smart-seo-fixer');
                            }
                        }
                    } else {
                        wp_send_json_error(['message' => __('OpenAI API key not configured.', 'smart-seo-fixer')]);
                    }
                }
                break;
                
            case 'generate_unique_title':
                if ($post_id > 0 && class_exists('SSF_OpenAI')) {
                    $openai = new SSF_OpenAI();
                    if ($openai->is_configured()) {
                        $post = get_post($post_id);
                        $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
                        $title = $openai->generate_title($post->post_content, $post->post_title, $focus_keyword);
                        if (!is_wp_error($title) && !empty(trim($title))) {
                            update_post_meta($post_id, '_ssf_seo_title', sanitize_text_field(trim($title)));
                            $fixed[] = sprintf(__('Generated unique title: "%s"', 'smart-seo-fixer'), trim($title));
                        }
                    }
                }
                break;
                
            case 'generate_unique_desc':
                if ($post_id > 0 && class_exists('SSF_OpenAI')) {
                    $openai = new SSF_OpenAI();
                    if ($openai->is_configured()) {
                        $post = get_post($post_id);
                        $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
                        $desc = $openai->generate_meta_description($post->post_content, '', $focus_keyword);
                        if (!is_wp_error($desc) && !empty(trim($desc))) {
                            update_post_meta($post_id, '_ssf_meta_description', sanitize_textarea_field(trim($desc)));
                            $fixed[] = sprintf(__('Generated unique description', 'smart-seo-fixer'));
                        }
                    }
                }
                break;
                
            case 'fix_redirect_chains':
                $result = $this->fix_url_issues('redirect_chains');
                $fixed = $result;
                break;
                
            default:
                wp_send_json_error(['message' => __('Unknown fix type.', 'smart-seo-fixer')]);
        }
        
        wp_send_json_success([
            'fixed' => $fixed,
            'message' => !empty($fixed) ? implode('. ', $fixed) : __('No changes needed.', 'smart-seo-fixer'),
        ]);
    }
}
