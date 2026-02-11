<?php
/**
 * Redirect Manager Class
 * 
 * Handles 301/302 redirects, auto-detect slug changes, and 404 tracking.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Redirects {
    
    /**
     * Option key for storing redirects
     */
    const OPTION_KEY = 'ssf_redirects';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Intercept requests to check for redirects (runs early)
        add_action('template_redirect', [$this, 'maybe_redirect'], 1);
        
        // Track slug changes
        add_action('post_updated', [$this, 'detect_slug_change'], 10, 3);
        
        // AJAX handlers
        add_action('wp_ajax_ssf_get_redirects', [$this, 'ajax_get_redirects']);
        add_action('wp_ajax_ssf_add_redirect', [$this, 'ajax_add_redirect']);
        add_action('wp_ajax_ssf_delete_redirect', [$this, 'ajax_delete_redirect']);
        add_action('wp_ajax_ssf_toggle_redirect', [$this, 'ajax_toggle_redirect']);
        add_action('wp_ajax_ssf_get_404_log', [$this, 'ajax_get_404_log']);
        add_action('wp_ajax_ssf_clear_404_log', [$this, 'ajax_clear_404_log']);
        
        // Log 404s
        add_action('template_redirect', [$this, 'log_404'], 99);
    }
    
    /**
     * Check if current request matches a redirect rule
     */
    public function maybe_redirect() {
        if (is_admin()) return;
        
        $request_path = trim(wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $redirects = $this->get_redirects();
        
        foreach ($redirects as $redirect) {
            if (empty($redirect['enabled'])) continue;
            
            $from = trim($redirect['from'], '/');
            
            // Exact match
            if ($from === $request_path) {
                $code = intval($redirect['type'] ?? 301);
                wp_redirect($redirect['to'], $code);
                exit;
            }
            
            // Wildcard match (e.g., /old-path/* → /new-path/)
            if (substr($from, -1) === '*') {
                $prefix = rtrim($from, '*');
                if (strpos($request_path, $prefix) === 0) {
                    $code = intval($redirect['type'] ?? 301);
                    wp_redirect($redirect['to'], $code);
                    exit;
                }
            }
        }
    }
    
    /**
     * Detect when a post slug changes and auto-create a redirect
     */
    public function detect_slug_change($post_id, $post_after, $post_before) {
        // Only for published posts
        if ($post_after->post_status !== 'publish') return;
        if ($post_before->post_status !== 'publish') return;
        
        // Check if slug actually changed
        if ($post_before->post_name === $post_after->post_name) return;
        
        // Build old and new URLs
        // We need to temporarily set the slug back to get the old permalink
        $old_url = str_replace($post_after->post_name, $post_before->post_name, get_permalink($post_id));
        $new_url = get_permalink($post_id);
        
        if ($old_url === $new_url) return;
        
        // Convert to relative paths
        $site_url = home_url();
        $old_path = str_replace($site_url, '', $old_url);
        $new_path = str_replace($site_url, '', $new_url);
        
        // Don't create duplicate redirects
        $redirects = $this->get_redirects();
        foreach ($redirects as $r) {
            if (trim($r['from'], '/') === trim($old_path, '/')) {
                return; // Already exists
            }
        }
        
        // Auto-add redirect
        $this->add_redirect([
            'from' => $old_path,
            'to' => $new_url,
            'type' => 301,
            'enabled' => true,
            'auto' => true,
            'note' => sprintf(
                __('Auto-created: "%s" slug changed', 'smart-seo-fixer'),
                $post_after->post_title
            ),
            'created' => current_time('mysql'),
        ]);
    }
    
    /**
     * Log 404 errors
     */
    public function log_404() {
        if (!is_404()) return;
        if (is_admin()) return;
        
        $url = $_SERVER['REQUEST_URI'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Skip bots and common junk
        $skip = ['.php', 'wp-login', 'wp-admin', 'xmlrpc', '.env', 'wp-config'];
        foreach ($skip as $s) {
            if (stripos($url, $s) !== false) return;
        }
        
        $log = get_option('ssf_404_log', []);
        
        // Keep max 200 entries
        if (count($log) >= 200) {
            $log = array_slice($log, -150);
        }
        
        // Don't log the same URL repeatedly (within last 50 entries)
        $recent_urls = array_column(array_slice($log, -50), 'url');
        if (in_array($url, $recent_urls)) {
            // Just increment the hit count
            for ($i = count($log) - 1; $i >= 0; $i--) {
                if ($log[$i]['url'] === $url) {
                    $log[$i]['hits'] = ($log[$i]['hits'] ?? 1) + 1;
                    $log[$i]['last_hit'] = current_time('mysql');
                    break;
                }
            }
        } else {
            $log[] = [
                'url' => $url,
                'referer' => $referer,
                'hits' => 1,
                'first_hit' => current_time('mysql'),
                'last_hit' => current_time('mysql'),
            ];
        }
        
        update_option('ssf_404_log', $log, false); // Don't autoload
    }
    
    /**
     * Get all redirects
     */
    public function get_redirects() {
        return get_option(self::OPTION_KEY, []);
    }
    
    /**
     * Add a redirect
     */
    public function add_redirect($redirect) {
        $redirects = $this->get_redirects();
        $redirect['id'] = uniqid('r_');
        $redirect['created'] = $redirect['created'] ?? current_time('mysql');
        $redirect['enabled'] = $redirect['enabled'] ?? true;
        $redirects[] = $redirect;
        update_option(self::OPTION_KEY, $redirects);
        return $redirect['id'];
    }
    
    /**
     * Delete a redirect by ID
     */
    public function delete_redirect($id) {
        $redirects = $this->get_redirects();
        $redirects = array_filter($redirects, function($r) use ($id) {
            return ($r['id'] ?? '') !== $id;
        });
        update_option(self::OPTION_KEY, array_values($redirects));
    }
    
    /**
     * Toggle a redirect on/off
     */
    public function toggle_redirect($id) {
        $redirects = $this->get_redirects();
        foreach ($redirects as &$r) {
            if (($r['id'] ?? '') === $id) {
                $r['enabled'] = !$r['enabled'];
                break;
            }
        }
        update_option(self::OPTION_KEY, $redirects);
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
    
    public function ajax_get_redirects() {
        $this->verify();
        wp_send_json_success([
            'redirects' => $this->get_redirects(),
            'total' => count($this->get_redirects()),
        ]);
    }
    
    public function ajax_add_redirect() {
        $this->verify();
        
        $from = sanitize_text_field($_POST['from'] ?? '');
        $to = esc_url_raw($_POST['to'] ?? '');
        $type = intval($_POST['redirect_type'] ?? 301);
        $note = sanitize_text_field($_POST['note'] ?? '');
        
        if (empty($from) || empty($to)) {
            wp_send_json_error(['message' => __('Both "From" and "To" URLs are required.', 'smart-seo-fixer')]);
        }
        
        if (!in_array($type, [301, 302, 307])) {
            $type = 301;
        }
        
        // Check for duplicates
        $existing = $this->get_redirects();
        foreach ($existing as $r) {
            if (trim($r['from'], '/') === trim($from, '/')) {
                wp_send_json_error(['message' => __('A redirect for this URL already exists.', 'smart-seo-fixer')]);
            }
        }
        
        $id = $this->add_redirect([
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'note' => $note,
            'auto' => false,
        ]);
        
        wp_send_json_success(['message' => __('Redirect added.', 'smart-seo-fixer'), 'id' => $id]);
    }
    
    public function ajax_delete_redirect() {
        $this->verify();
        $id = sanitize_text_field($_POST['redirect_id'] ?? '');
        if (empty($id)) {
            wp_send_json_error(['message' => __('Invalid redirect ID.', 'smart-seo-fixer')]);
        }
        $this->delete_redirect($id);
        wp_send_json_success(['message' => __('Redirect deleted.', 'smart-seo-fixer')]);
    }
    
    public function ajax_toggle_redirect() {
        $this->verify();
        $id = sanitize_text_field($_POST['redirect_id'] ?? '');
        if (empty($id)) {
            wp_send_json_error(['message' => __('Invalid redirect ID.', 'smart-seo-fixer')]);
        }
        $this->toggle_redirect($id);
        wp_send_json_success(['message' => __('Redirect toggled.', 'smart-seo-fixer')]);
    }
    
    public function ajax_get_404_log() {
        $this->verify();
        $log = get_option('ssf_404_log', []);
        // Sort by hits descending
        usort($log, function($a, $b) { return ($b['hits'] ?? 1) - ($a['hits'] ?? 1); });
        wp_send_json_success(['log' => $log, 'total' => count($log)]);
    }
    
    public function ajax_clear_404_log() {
        $this->verify();
        delete_option('ssf_404_log');
        wp_send_json_success(['message' => __('404 log cleared.', 'smart-seo-fixer')]);
    }
}
