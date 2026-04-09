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
            // Auto alt text on upload
            if (Smart_SEO_Fixer::get_option('auto_alt_text', false)) {
                add_action('add_attachment', [__CLASS__, 'auto_alt_on_upload']);
            }
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

    /**
     * Generate alt text from an image filename.
     * Strips extension, replaces separators with spaces, capitalizes words.
     *
     * @param string $filename  The image filename (e.g., "bounce-house_rental-nj.jpg")
     * @return string           Cleaned alt text (e.g., "Bounce House Rental Nj")
     */
    public static function generate_alt_from_filename($filename) {
        // Remove extension
        $name = pathinfo($filename, PATHINFO_FILENAME);
        // Remove size suffix like -300x200
        $name = preg_replace('/-\d+x\d+$/', '', $name);
        // Replace separators with spaces
        $name = str_replace(['-', '_', '.', '+'], ' ', $name);
        // Remove extra spaces
        $name = preg_replace('/\s+/', ' ', trim($name));
        // Remove leading numbers/hashes (e.g., "123 " or "IMG ")
        $name = preg_replace('/^(IMG|DSC|DSCN|DSCF|P|DC|MOV|VID|WP|wp|Screenshot)\s*/i', '', $name);
        $name = preg_replace('/^\d+\s*/', '', $name);
        // Capitalize words
        $name = ucwords(strtolower($name));
        return $name;
    }

    /**
     * Auto-generate alt text when an image is uploaded.
     * Only sets alt if the image is an image type and has no alt text yet.
     *
     * @param int $attachment_id
     */
    public static function auto_alt_on_upload($attachment_id) {
        $mime = get_post_mime_type($attachment_id);
        if (!$mime || strpos($mime, 'image/') !== 0) {
            return;
        }

        // Only set if no alt text exists
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty(trim($existing_alt))) {
            return;
        }

        $filename = basename(get_attached_file($attachment_id));
        $alt_text = self::generate_alt_from_filename($filename);

        if (!empty($alt_text)) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
        }
    }

    /**
     * Bulk generate alt text for all images missing it.
     * Processes in batches to avoid timeouts.
     *
     * @param int $batch_size  Number of images to process per call
     * @return array           Results with counts
     */
    public static function bulk_generate_alt_text($batch_size = 100) {
        global $wpdb;

        // Find image attachments missing alt text
        $attachment_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
             WHERE p.post_type = 'attachment'
             AND p.post_mime_type LIKE %s
             AND (pm.meta_value IS NULL OR pm.meta_value = '')
             LIMIT %d",
            'image/%',
            $batch_size
        ));

        $updated = 0;
        $skipped = 0;

        foreach ($attachment_ids as $id) {
            $filename = basename(get_attached_file($id));
            $alt_text = self::generate_alt_from_filename($filename);

            if (!empty($alt_text)) {
                update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
                $updated++;
            } else {
                $skipped++;
            }
        }

        // Count remaining
        $remaining = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
             WHERE p.post_type = 'attachment'
             AND p.post_mime_type LIKE %s
             AND (pm.meta_value IS NULL OR pm.meta_value = '')",
            'image/%'
        ));

        return [
            'updated'   => $updated,
            'skipped'   => $skipped,
            'remaining' => $remaining,
            'done'      => $remaining === 0,
        ];
    }

    /**
     * Count images missing alt text.
     *
     * @return int
     */
    public static function count_missing_alt() {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
             WHERE p.post_type = 'attachment'
             AND p.post_mime_type LIKE %s
             AND (pm.meta_value IS NULL OR pm.meta_value = '')",
            'image/%'
        ));
    }
}
