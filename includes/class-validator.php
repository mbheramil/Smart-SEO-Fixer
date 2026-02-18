<?php
/**
 * Input Validator
 * 
 * Centralized sanitization and validation for all plugin inputs.
 * Use this instead of inline sanitization to ensure consistency.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Validator {
    
    /**
     * Sanitize and validate an SEO title
     * 
     * @param string $title Raw input
     * @return string|WP_Error Clean title or error
     */
    public static function seo_title($title) {
        $title = sanitize_text_field(trim($title));
        
        if (empty($title)) {
            return '';
        }
        
        // Strip any remaining HTML entities that shouldn't be in titles
        $title = wp_strip_all_tags($title);
        
        // Limit length (Google typically shows 50-60 chars)
        if (mb_strlen($title) > 200) {
            $title = mb_substr($title, 0, 200);
        }
        
        return $title;
    }
    
    /**
     * Sanitize and validate a meta description
     */
    public static function meta_description($desc) {
        $desc = sanitize_textarea_field(trim($desc));
        
        if (empty($desc)) {
            return '';
        }
        
        // Strip tags
        $desc = wp_strip_all_tags($desc);
        
        // Collapse whitespace
        $desc = preg_replace('/\s+/', ' ', $desc);
        
        // Limit length (Google typically shows 150-160 chars)
        if (mb_strlen($desc) > 500) {
            $desc = mb_substr($desc, 0, 500);
        }
        
        return $desc;
    }
    
    /**
     * Sanitize a focus keyword
     */
    public static function focus_keyword($keyword) {
        $keyword = sanitize_text_field(trim($keyword));
        
        // Keywords shouldn't be too long
        if (mb_strlen($keyword) > 100) {
            $keyword = mb_substr($keyword, 0, 100);
        }
        
        return $keyword;
    }
    
    /**
     * Validate and sanitize a URL
     */
    public static function url($url) {
        $url = trim($url);
        
        if (empty($url)) {
            return '';
        }
        
        $url = esc_url_raw($url);
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }
        
        return $url;
    }
    
    /**
     * Validate a post ID
     * Returns the valid post ID or 0 if invalid.
     */
    public static function post_id($id) {
        $id = absint($id);
        
        if ($id <= 0) {
            return 0;
        }
        
        return $id;
    }
    
    /**
     * Validate and sanitize an array of post IDs
     */
    public static function post_ids($ids) {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $clean = array_map('absint', $ids);
        $clean = array_filter($clean, function($id) {
            return $id > 0;
        });
        
        return array_values($clean);
    }
    
    /**
     * Sanitize an API key (preserve special chars but strip whitespace)
     */
    public static function api_key($key) {
        $key = trim($key);
        
        // API keys should only contain printable ASCII
        $key = preg_replace('/[^\x20-\x7E]/', '', $key);
        
        return $key;
    }
    
    /**
     * Validate an OpenAI API key format
     */
    public static function is_valid_openai_key($key) {
        $key = self::api_key($key);
        
        // OpenAI keys start with 'sk-' and are typically 40-60 chars
        return !empty($key) && (
            strpos($key, 'sk-') === 0 || 
            strpos($key, 'sk-proj-') === 0
        );
    }
    
    /**
     * Sanitize schema JSON
     */
    public static function schema_json($json) {
        if (is_string($json)) {
            $decoded = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return '';
            }
            return wp_json_encode($decoded);
        }
        
        if (is_array($json)) {
            return wp_json_encode($json);
        }
        
        return '';
    }
    
    /**
     * Sanitize redirect settings
     */
    public static function redirect_url($from, $to) {
        $from = self::url($from);
        $to   = self::url($to);
        
        if (empty($from) || empty($to)) {
            return new WP_Error('invalid_redirect', __('Both source and target URLs are required.', 'smart-seo-fixer'));
        }
        
        if ($from === $to) {
            return new WP_Error('redirect_loop', __('Source and target URLs cannot be the same.', 'smart-seo-fixer'));
        }
        
        return ['from' => $from, 'to' => $to];
    }
    
    /**
     * Sanitize post type selection
     */
    public static function post_types($types) {
        if (!is_array($types)) {
            return ['post', 'page'];
        }
        
        $valid = get_post_types(['public' => true]);
        $clean = array_filter($types, function($type) use ($valid) {
            return isset($valid[$type]);
        });
        
        return !empty($clean) ? array_values($clean) : ['post', 'page'];
    }
    
    /**
     * Sanitize a title separator
     */
    public static function title_separator($sep) {
        $allowed = ['|', '-', '—', '·', '>', '»', '/', '\\'];
        $sep = trim($sep);
        
        return in_array($sep, $allowed) ? $sep : '|';
    }
    
    /**
     * Sanitize robots meta value
     */
    public static function robots_meta($value) {
        $allowed = ['index', 'noindex', 'follow', 'nofollow', 'noindex, nofollow', 'index, follow'];
        return in_array($value, $allowed) ? $value : '';
    }
    
    /**
     * Validate pagination parameters
     */
    public static function pagination($page, $per_page, $max_per_page = 100) {
        return [
            'page'     => max(1, absint($page)),
            'per_page' => min($max_per_page, max(1, absint($per_page))),
        ];
    }
    
    /**
     * Sanitize search query
     */
    public static function search_query($query) {
        $query = sanitize_text_field(trim($query));
        
        // Limit length
        if (mb_strlen($query) > 200) {
            $query = mb_substr($query, 0, 200);
        }
        
        return $query;
    }
    
    /**
     * Validate AJAX request (nonce + capability in one call)
     * Returns true or sends wp_send_json_error and dies.
     */
    public static function verify_ajax($capability = 'edit_posts') {
        if (!check_ajax_referer('ssf_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'smart-seo-fixer')]);
        }
        
        if (!current_user_can($capability)) {
            wp_send_json_error(['message' => __('Permission denied.', 'smart-seo-fixer')]);
        }
        
        return true;
    }
}
