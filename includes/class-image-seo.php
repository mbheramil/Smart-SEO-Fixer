<?php
/**
 * Image SEO Class
 * 
 * Enhances image output with lazy loading, missing width/height attributes,
 * and ensures all images have proper alt text for accessibility and SEO.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Image_SEO {
    
    /**
     * Initialize frontend image optimization hooks
     */
    public static function init() {
        if (is_admin()) {
            return;
        }
        
        // Filter post content to add missing image attributes
        add_filter('the_content', [__CLASS__, 'optimize_content_images'], 99);
        
        // Filter post thumbnails
        add_filter('post_thumbnail_html', [__CLASS__, 'optimize_single_image'], 99);
        
        // Add native lazy loading to all images (WordPress 5.5+ does this for some,
        // but we catch any that slip through)
        add_filter('wp_get_attachment_image_attributes', [__CLASS__, 'add_lazy_load_attr'], 10, 3);
    }
    
    /**
     * Process all images in post content
     * - Add missing width/height from attachment metadata
     * - Add loading="lazy" for below-fold images
     * - Add decoding="async" for performance
     */
    public static function optimize_content_images($content) {
        if (empty($content)) {
            return $content;
        }
        
        // Match all <img> tags
        if (!preg_match_all('/<img\s[^>]+>/i', $content, $matches)) {
            return $content;
        }
        
        $first_image = true;
        
        foreach ($matches[0] as $img_tag) {
            $new_tag = $img_tag;
            
            // Add loading="lazy" if not already present (skip first image — likely LCP)
            if ($first_image) {
                $first_image = false;
                // First image should load eagerly (likely LCP candidate)
                if (strpos($new_tag, 'loading=') === false) {
                    $new_tag = str_replace('<img ', '<img loading="eager" ', $new_tag);
                }
            } else {
                if (strpos($new_tag, 'loading=') === false) {
                    $new_tag = str_replace('<img ', '<img loading="lazy" ', $new_tag);
                }
            }
            
            // Add decoding="async" if not present
            if (strpos($new_tag, 'decoding=') === false) {
                $new_tag = str_replace('<img ', '<img decoding="async" ', $new_tag);
            }
            
            // Add missing width/height
            if (strpos($new_tag, 'width=') === false || strpos($new_tag, 'height=') === false) {
                $new_tag = self::add_dimensions($new_tag);
            }
            
            if ($new_tag !== $img_tag) {
                $content = str_replace($img_tag, $new_tag, $content);
            }
        }
        
        return $content;
    }
    
    /**
     * Optimize a single image tag (thumbnails, etc.)
     */
    public static function optimize_single_image($html) {
        if (empty($html)) {
            return $html;
        }
        
        // Add decoding="async" if not present
        if (strpos($html, 'decoding=') === false) {
            $html = str_replace('<img ', '<img decoding="async" ', $html);
        }
        
        return $html;
    }
    
    /**
     * Add lazy loading attribute to attachment images.
     */
    public static function add_lazy_load_attr($attr, $attachment, $size) {
        if (!isset($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }
        if (!isset($attr['decoding'])) {
            $attr['decoding'] = 'async';
        }
        return $attr;
    }
    
    /**
     * Try to add missing width/height attributes by resolving the image.
     * First checks WordPress attachment metadata, then falls back to getimagesize
     * for local files only (no remote calls).
     */
    private static function add_dimensions($img_tag) {
        // Already has both? Skip
        if (strpos($img_tag, ' width=') !== false && strpos($img_tag, ' height=') !== false) {
            return $img_tag;
        }
        
        // Extract src
        if (!preg_match('/src=["\']([^"\']+)["\']/', $img_tag, $src_match)) {
            return $img_tag;
        }
        
        $src = $src_match[1];
        $width = 0;
        $height = 0;
        
        // Try to get attachment ID from URL
        $attachment_id = attachment_url_to_postid($src);
        
        if ($attachment_id > 0) {
            $meta = wp_get_attachment_metadata($attachment_id);
            if ($meta && !empty($meta['width']) && !empty($meta['height'])) {
                $width = $meta['width'];
                $height = $meta['height'];
                
                // Check if it's a specific size (e.g., -300x200.jpg)
                if (preg_match('/-(\d+)x(\d+)\.[a-z]+$/i', $src, $size_match)) {
                    $width = intval($size_match[1]);
                    $height = intval($size_match[2]);
                }
            }
        }
        
        // Fallback: try getimagesize for local files only
        if (($width === 0 || $height === 0) && strpos($src, home_url()) === 0) {
            $upload_dir = wp_upload_dir();
            $relative_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $src);
            
            if (file_exists($relative_path)) {
                $size_info = @getimagesize($relative_path);
                if ($size_info) {
                    $width = $size_info[0];
                    $height = $size_info[1];
                }
            }
        }
        
        if ($width > 0 && $height > 0) {
            if (strpos($img_tag, ' width=') === false) {
                $img_tag = str_replace('<img ', '<img width="' . intval($width) . '" ', $img_tag);
            }
            if (strpos($img_tag, ' height=') === false) {
                $img_tag = str_replace('<img ', '<img height="' . intval($height) . '" ', $img_tag);
            }
        }
        
        return $img_tag;
    }
    
    /**
     * Scan a post and return images missing alt text or dimensions.
     * Used by the analyzer/dashboard for reporting (not for auto-fix).
     * 
     * @param int $post_id
     * @return array List of image issues
     */
    public static function audit_post_images($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }
        
        $issues = [];
        $content = $post->post_content;
        
        if (!preg_match_all('/<img\s[^>]+>/i', $content, $matches)) {
            return $issues;
        }
        
        foreach ($matches[0] as $img_tag) {
            $src = '';
            if (preg_match('/src=["\']([^"\']+)["\']/', $img_tag, $m)) {
                $src = $m[1];
            }
            
            $has_alt = preg_match('/alt=["\']([^"\']*)["\']/', $img_tag, $alt_match);
            $alt_text = $has_alt ? $alt_match[1] : '';
            $has_width = strpos($img_tag, ' width=') !== false;
            $has_height = strpos($img_tag, ' height=') !== false;
            $has_lazy = strpos($img_tag, 'loading=') !== false;
            
            $img_issues = [];
            
            if (!$has_alt || empty(trim($alt_text))) {
                $img_issues[] = 'missing_alt';
            }
            if (!$has_width || !$has_height) {
                $img_issues[] = 'missing_dimensions';
            }
            
            if (!empty($img_issues)) {
                $issues[] = [
                    'src'     => $src,
                    'issues'  => $img_issues,
                    'has_alt' => !empty(trim($alt_text)),
                    'has_dimensions' => $has_width && $has_height,
                    'has_lazy' => $has_lazy,
                ];
            }
        }
        
        return $issues;
    }
}
