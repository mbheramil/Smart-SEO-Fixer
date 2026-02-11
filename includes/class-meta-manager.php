<?php
/**
 * Meta Manager Class
 * 
 * Handles SEO titles, meta descriptions, and other meta tags.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Meta_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // CRITICAL: Force title-tag support for themes that don't declare it.
        // Without this, WordPress won't output a <title> tag at all,
        // and our pre_get_document_title filter never fires.
        add_action('after_setup_theme', [$this, 'force_title_tag_support'], 99);
        
        // Filter document title (high priority to override other plugins)
        add_filter('pre_get_document_title', [$this, 'filter_title'], 9999);
        add_filter('document_title_parts', [$this, 'filter_title_parts'], 9999);
        add_filter('wp_title', [$this, 'filter_title'], 9999);
        
        // Fallback: directly output <title> tag if theme still doesn't render one
        add_action('wp_head', [$this, 'ensure_title_tag'], 0);
        
        // Add meta tags to head
        add_action('wp_head', [$this, 'output_meta_tags'], 1);
        
        // Open Graph and Twitter Cards
        add_action('wp_head', [$this, 'output_social_tags'], 2);
        
        // Robots meta
        add_action('wp_head', [$this, 'output_robots_meta'], 3);
        
        // Canonical URL
        add_action('wp_head', [$this, 'output_canonical'], 4);
        
        // Disable conflicting SEO plugins output (runs early so hooks are removed before they fire)
        if (Smart_SEO_Fixer::get_option('disable_other_seo_output', false)) {
            $this->disable_conflicting_plugins();
        }
    }
    
    /**
     * Force title-tag theme support
     * 
     * Many custom themes (especially Elementor-based) don't declare title-tag support.
     * Without it, WordPress never outputs a <title> tag and our filters never fire.
     * This ensures every theme gets a proper <title> tag via wp_head().
     */
    public function force_title_tag_support() {
        if (!current_theme_supports('title-tag')) {
            add_theme_support('title-tag');
        }
    }
    
    /**
     * Fallback: ensure a <title> tag exists even if title-tag support fails
     * 
     * Some themes hardcode their header.php in a way that prevents title-tag
     * from working. This hooks into wp_head at priority 0 and uses output
     * buffering to check if a <title> tag was rendered. If not, we inject one.
     */
    public function ensure_title_tag() {
        // Start buffering wp_head output to check for <title> tag later
        ob_start();
        // We'll check the buffer at the end of wp_head
        add_action('wp_head', [$this, 'check_title_tag_buffer'], 9999);
    }
    
    /**
     * Check if <title> tag exists in wp_head output, inject if missing
     */
    public function check_title_tag_buffer() {
        $head_output = ob_get_clean();
        
        // Check if a <title> tag was rendered
        if (stripos($head_output, '<title') === false) {
            // No <title> tag found — inject one
            $title = $this->get_current_page_title();
            echo '<title>' . esc_html($title) . '</title>' . "\n";
        }
        
        // Output the buffered content
        echo $head_output;
    }
    
    /**
     * Get the SEO title for the current page (used by title tag and social tags)
     */
    public function get_current_page_title() {
        if (is_singular()) {
            $post_id = get_the_ID();
            $seo_title = get_post_meta($post_id, '_ssf_seo_title', true);
            
            if (!empty($seo_title)) {
                return $seo_title;
            }
            
            $separator = Smart_SEO_Fixer::get_option('title_separator', '|');
            return get_the_title($post_id) . ' ' . $separator . ' ' . get_bloginfo('name');
        }
        
        if (is_front_page() || is_home()) {
            $homepage_title = Smart_SEO_Fixer::get_option('homepage_title');
            if (!empty($homepage_title)) {
                return $homepage_title;
            }
            $site_name = get_bloginfo('name');
            $tagline = get_bloginfo('description');
            return !empty($tagline) ? $site_name . ' - ' . $tagline : $site_name;
        }
        
        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            $separator = Smart_SEO_Fixer::get_option('title_separator', '|');
            return ($term ? $term->name : '') . ' ' . $separator . ' ' . get_bloginfo('name');
        }
        
        if (is_author()) {
            $author = get_queried_object();
            $separator = Smart_SEO_Fixer::get_option('title_separator', '|');
            return ($author ? $author->display_name : '') . ' ' . $separator . ' ' . get_bloginfo('name');
        }
        
        if (is_search()) {
            $separator = Smart_SEO_Fixer::get_option('title_separator', '|');
            return __('Search Results', 'smart-seo-fixer') . ' ' . $separator . ' ' . get_bloginfo('name');
        }
        
        if (is_404()) {
            $separator = Smart_SEO_Fixer::get_option('title_separator', '|');
            return __('Page Not Found', 'smart-seo-fixer') . ' ' . $separator . ' ' . get_bloginfo('name');
        }
        
        return get_bloginfo('name');
    }
    
    /**
     * Disable meta output from other SEO plugins to prevent duplicates
     */
    public function disable_conflicting_plugins() {
        // === Yoast SEO ===
        if (defined('WPSEO_VERSION') || class_exists('WPSEO_Frontend')) {
            // Yoast 14+ (modern): remove all frontend presenters
            add_filter('wpseo_frontend_presenters', '__return_empty_array');
            
            // Yoast title filter
            add_filter('wpseo_title', '__return_empty_string', 9999);
            
            // Yoast meta description
            add_filter('wpseo_metadesc', '__return_empty_string', 9999);
            
            // Yoast Open Graph
            add_filter('wpseo_opengraph_title', '__return_empty_string', 9999);
            add_filter('wpseo_opengraph_desc', '__return_empty_string', 9999);
            add_filter('wpseo_opengraph_url', '__return_empty_string', 9999);
            add_filter('wpseo_opengraph_image', '__return_empty_string', 9999);
            
            // Yoast Twitter
            add_filter('wpseo_twitter_title', '__return_empty_string', 9999);
            add_filter('wpseo_twitter_description', '__return_empty_string', 9999);
            
            // Yoast canonical
            add_filter('wpseo_canonical', '__return_false', 9999);
            
            // Yoast robots
            add_filter('wpseo_robots', '__return_empty_string', 9999);
            
            // Yoast JSON-LD schema (we have our own)
            add_filter('wpseo_json_ld_output', '__return_empty_array', 9999);
            add_filter('wpseo_schema_graph', '__return_empty_array', 9999);
            
            // Remove Yoast head action entirely (belt-and-suspenders)
            add_action('template_redirect', function() {
                // Remove old-style Yoast frontend
                if (class_exists('WPSEO_Frontend')) {
                    $instance = WPSEO_Frontend::get_instance();
                    remove_action('wp_head', [$instance, 'head'], 1);
                }
                // Remove Yoast's adjacent rel links
                remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
            });
        }
        
        // === Rank Math ===
        if (defined('RANK_MATH_VERSION') || class_exists('RankMath')) {
            // Remove Rank Math head action
            add_action('template_redirect', function() {
                if (class_exists('RankMath\Frontend\Frontend')) {
                    remove_all_actions('rank_math/head');
                }
            }, 1);
            
            // Rank Math filters
            add_filter('rank_math/frontend/title', '__return_empty_string', 9999);
            add_filter('rank_math/frontend/description', '__return_empty_string', 9999);
            add_filter('rank_math/opengraph/facebook', '__return_false', 9999);
            add_filter('rank_math/opengraph/twitter', '__return_false', 9999);
            add_filter('rank_math/json_ld', '__return_empty_array', 9999);
        }
        
        // === All in One SEO ===
        if (defined('AIOSEO_VERSION') || class_exists('AIOSEOP_Core')) {
            add_action('template_redirect', function() {
                // AIOSEO v4+
                if (function_exists('aioseo')) {
                    remove_action('wp_head', [aioseo()->head, 'output'], 1);
                }
                // AIOSEO v3 (legacy)
                if (class_exists('All_in_One_SEO_Pack')) {
                    $aioseop = All_in_One_SEO_Pack::get_instance();
                    if ($aioseop) {
                        remove_action('wp_head', [$aioseop, 'wp_head']);
                    }
                }
            }, 1);
        }
        
        // === The SEO Framework ===
        if (defined('THE_SEO_FRAMEWORK_VERSION') || function_exists('the_seo_framework')) {
            add_action('template_redirect', function() {
                if (function_exists('the_seo_framework')) {
                    $tsf = the_seo_framework();
                    remove_action('wp_head', [$tsf, 'html_output'], 1);
                }
            }, 1);
        }
        
        // === SEOPress ===
        if (defined('SEOPRESS_VERSION') || class_exists('SEOPRESS')) {
            add_filter('seopress_titles_title', '__return_empty_string', 9999);
            add_filter('seopress_titles_desc', '__return_empty_string', 9999);
            add_action('template_redirect', function() {
                remove_action('wp_head', 'seopress_social_fb_og_title');
                remove_action('wp_head', 'seopress_social_fb_og_desc');
            }, 1);
        }
    }
    
    /**
     * Filter document title
     */
    public function filter_title($title) {
        if (is_singular()) {
            $post_id = get_the_ID();
            $seo_title = get_post_meta($post_id, '_ssf_seo_title', true);
            
            if (!empty($seo_title)) {
                return $seo_title;
            }
            
            // If no SSF title is set, always return something meaningful
            if (empty($title)) {
                $separator = Smart_SEO_Fixer::get_option('title_separator', '|');
                $post_title = get_the_title($post_id);
                $site_name = get_bloginfo('name');
                return $post_title . ' ' . $separator . ' ' . $site_name;
            }
        }
        
        // Homepage
        if (is_front_page() || is_home()) {
            $homepage_title = Smart_SEO_Fixer::get_option('homepage_title');
            if (!empty($homepage_title)) {
                return $homepage_title;
            }
            
            // Fallback for homepage
            if (empty($title)) {
                $site_name = get_bloginfo('name');
                $tagline = get_bloginfo('description');
                return !empty($tagline) ? $site_name . ' - ' . $tagline : $site_name;
            }
        }
        
        return $title;
    }
    
    /**
     * Filter title parts
     */
    public function filter_title_parts($title_parts) {
        $separator = Smart_SEO_Fixer::get_option('title_separator', '|');
        
        // The separator is handled by WordPress, but we can modify parts
        if (isset($title_parts['title']) && is_singular()) {
            $post_id = get_the_ID();
            $seo_title = get_post_meta($post_id, '_ssf_seo_title', true);
            
            if (!empty($seo_title)) {
                // If SEO title is set, use it as-is without site name
                return ['title' => $seo_title];
            }
        }
        
        return $title_parts;
    }
    
    /**
     * Output meta description
     */
    public function output_meta_tags() {
        $description = $this->get_meta_description();
        
        if (!empty($description)) {
            echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
        }
        
        // Output focus keyword as meta keywords (optional, mostly deprecated)
        if (is_singular()) {
            $post_id = get_the_ID();
            $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
            
            if (!empty($focus_keyword)) {
                echo '<meta name="keywords" content="' . esc_attr($focus_keyword) . '" />' . "\n";
            }
        }
    }
    
    /**
     * Get meta description for current page
     */
    public function get_meta_description() {
        if (is_singular()) {
            $post_id = get_the_ID();
            $description = get_post_meta($post_id, '_ssf_meta_description', true);
            
            if (!empty($description)) {
                return $description;
            }
            
            // Auto-generate from excerpt/content
            $post = get_post($post_id);
            if ($post) {
                $text = !empty($post->post_excerpt) ? $post->post_excerpt : $post->post_content;
                $text = wp_strip_all_tags($text);
                $text = str_replace(["\n", "\r", "\t"], ' ', $text);
                $text = preg_replace('/\s+/', ' ', $text);
                return wp_trim_words($text, 25, '...');
            }
        }
        
        // Homepage
        if (is_front_page() || is_home()) {
            $description = Smart_SEO_Fixer::get_option('homepage_description');
            if (!empty($description)) {
                return $description;
            }
            return get_bloginfo('description');
        }
        
        // Archive pages
        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term && !empty($term->description)) {
                return wp_trim_words($term->description, 25, '...');
            }
        }
        
        // Author archive
        if (is_author()) {
            $author = get_queried_object();
            if ($author) {
                $bio = get_the_author_meta('description', $author->ID);
                if (!empty($bio)) {
                    return wp_trim_words($bio, 25, '...');
                }
            }
        }
        
        return '';
    }
    
    /**
     * Output Open Graph and Twitter Card tags
     */
    public function output_social_tags() {
        if (!is_singular()) {
            return;
        }
        
        $post_id = get_the_ID();
        $post = get_post($post_id);
        
        if (!$post) {
            return;
        }
        
        // Get SEO data
        $title = get_post_meta($post_id, '_ssf_seo_title', true) ?: get_the_title($post_id);
        $description = get_post_meta($post_id, '_ssf_meta_description', true) ?: $this->get_meta_description();
        $url = get_permalink($post_id);
        
        // Get image — featured image → first content image → site logo fallback
        $image = '';
        $image_id = get_post_thumbnail_id($post_id);
        if ($image_id) {
            $image_data = wp_get_attachment_image_src($image_id, 'large');
            if ($image_data) {
                $image = $image_data[0];
            }
        }
        
        // Fallback: first image in content
        if (empty($image) && !empty($post->post_content)) {
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $post->post_content, $img_match)) {
                $image = $img_match[1];
            }
        }
        
        // Fallback: site logo (from Customizer)
        if (empty($image)) {
            $custom_logo_id = get_theme_mod('custom_logo');
            if ($custom_logo_id) {
                $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
                if ($logo_data) {
                    $image = $logo_data[0];
                }
            }
        }
        
        // Open Graph
        echo '<!-- Smart SEO Fixer - Open Graph -->' . "\n";
        echo '<meta property="og:type" content="article" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
        
        if (!empty($image)) {
            echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
            echo '<meta property="og:image:width" content="1200" />' . "\n";
            echo '<meta property="og:image:height" content="630" />' . "\n";
        }
        
        // Allow extensions (e.g., WooCommerce) to add OG tags
        $extra_tags = apply_filters('ssf_og_tags', [], $post_id);
        if (!empty($extra_tags) && is_array($extra_tags)) {
            foreach ($extra_tags as $property => $content) {
                echo '<meta property="' . esc_attr($property) . '" content="' . esc_attr($content) . '" />' . "\n";
            }
        }
        
        // Twitter Card
        echo '<!-- Smart SEO Fixer - Twitter Card -->' . "\n";
        echo '<meta name="twitter:card" content="' . (!empty($image) ? 'summary_large_image' : 'summary') . '" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
        
        if (!empty($image)) {
            echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";
        }
    }
    
    /**
     * Output robots meta tag
     */
    public function output_robots_meta() {
        if (!is_singular()) {
            return;
        }
        
        $post_id = get_the_ID();
        $robots = [];
        
        // Check noindex
        $noindex = get_post_meta($post_id, '_ssf_noindex', true);
        if ($noindex) {
            $robots[] = 'noindex';
        } else {
            $robots[] = 'index';
        }
        
        // Check nofollow
        $nofollow = get_post_meta($post_id, '_ssf_nofollow', true);
        if ($nofollow) {
            $robots[] = 'nofollow';
        } else {
            $robots[] = 'follow';
        }
        
        if (!empty($robots)) {
            echo '<meta name="robots" content="' . esc_attr(implode(', ', $robots)) . '" />' . "\n";
        }
    }
    
    /**
     * Output canonical URL
     */
    public function output_canonical() {
        if (!is_singular()) {
            return;
        }
        
        $post_id = get_the_ID();
        $canonical = get_post_meta($post_id, '_ssf_canonical_url', true);
        
        if (empty($canonical)) {
            $canonical = get_permalink($post_id);
        }
        
        // Remove default WordPress canonical
        remove_action('wp_head', 'rel_canonical');
        
        echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
    }
    
    /**
     * Get post SEO data
     */
    public function get_post_seo_data($post_id) {
        return [
            'seo_title' => get_post_meta($post_id, '_ssf_seo_title', true),
            'meta_description' => get_post_meta($post_id, '_ssf_meta_description', true),
            'focus_keyword' => get_post_meta($post_id, '_ssf_focus_keyword', true),
            'canonical_url' => get_post_meta($post_id, '_ssf_canonical_url', true),
            'noindex' => get_post_meta($post_id, '_ssf_noindex', true),
            'nofollow' => get_post_meta($post_id, '_ssf_nofollow', true),
            'seo_score' => get_post_meta($post_id, '_ssf_seo_score', true),
            'seo_grade' => get_post_meta($post_id, '_ssf_seo_grade', true),
        ];
    }
    
    /**
     * Save post SEO data
     */
    public function save_post_seo_data($post_id, $data) {
        $fields = [
            '_ssf_seo_title',
            '_ssf_meta_description',
            '_ssf_focus_keyword',
            '_ssf_canonical_url',
            '_ssf_noindex',
            '_ssf_nofollow',
        ];
        
        foreach ($fields as $field) {
            $key = str_replace('_ssf_', '', $field);
            if (isset($data[$key])) {
                update_post_meta($post_id, $field, sanitize_text_field($data[$key]));
            }
        }
    }
}

