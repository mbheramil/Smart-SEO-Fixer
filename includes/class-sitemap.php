<?php
/**
 * Sitemap Class
 * 
 * Generates XML sitemaps for the website.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Sitemap {
    
    /**
     * Constructor
     */
    public function __construct() {
        if (Smart_SEO_Fixer::get_option('enable_sitemap', true)) {
            add_action('init', [$this, 'add_rewrite_rules']);
            add_filter('query_vars', [$this, 'add_query_vars']);
            add_action('template_redirect', [$this, 'render_sitemap'], 1);
            
            // Intercept sitemap requests early, before other plugins can serve theirs
            add_action('parse_request', [$this, 'intercept_sitemap_request'], 1);
            
            // Ping search engines on post publish
            add_action('publish_post', [$this, 'ping_search_engines']);
            add_action('publish_page', [$this, 'ping_search_engines']);
            
            // Disable conflicting sitemaps from other plugins
            add_action('init', [$this, 'disable_conflicting_sitemaps'], 99);
            
            // Disable WordPress core sitemaps (WP 5.5+)
            add_filter('wp_sitemaps_enabled', '__return_false');
        }
    }
    
    /**
     * Intercept sitemap requests early before other plugins can serve theirs.
     */
    public function intercept_sitemap_request($wp) {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
        
        $sitemap_map = [
            'sitemap.xml'            => 'index',
            'sitemap-posts.xml'      => 'posts',
            'sitemap-pages.xml'      => 'pages',
            'sitemap-categories.xml' => 'categories',
            'sitemap-tags.xml'       => 'tags',
            'sitemap-authors.xml'    => 'authors',
        ];
        
        // Strip the site subdirectory if WordPress is in a subdirectory
        $home_path = trim(parse_url(home_url(), PHP_URL_PATH) ?: '', '/');
        if ($home_path && strpos($path, $home_path . '/') === 0) {
            $path = substr($path, strlen($home_path) + 1);
        }
        
        if (isset($sitemap_map[$path])) {
            $wp->query_vars['ssf_sitemap'] = $sitemap_map[$path];
        }
    }
    
    /**
     * Disable sitemaps from other SEO plugins to avoid conflicts.
     */
    public function disable_conflicting_sitemaps() {
        // Yoast SEO
        if (defined('WPSEO_VERSION')) {
            add_filter('wpseo_sitemaps_enabled', '__return_false');
            // Remove Yoast sitemap rewrite rules
            global $wp_rewrite;
            if (isset($wp_rewrite->extra_rules_top)) {
                foreach ($wp_rewrite->extra_rules_top as $rule => $rewrite) {
                    if (strpos($rewrite, 'wpseo_sitemap') !== false || strpos($rewrite, 'sitemap_xsl') !== false) {
                        unset($wp_rewrite->extra_rules_top[$rule]);
                    }
                }
            }
            // Prevent Yoast from serving its sitemap via template_redirect
            if (class_exists('WPSEO_Sitemaps')) {
                remove_action('template_redirect', 'redirect_canonical');
            }
        }
        
        // Rank Math
        if (defined('RANK_MATH_VERSION')) {
            add_filter('rank_math/sitemap/enable', '__return_false');
        }
        
        // All in One SEO
        if (defined('AIOSEO_VERSION')) {
            add_filter('aioseo_sitemap_enabled', '__return_false');
        }
    }
    
    /**
     * Add rewrite rules for sitemap
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^sitemap\.xml$', 'index.php?ssf_sitemap=index', 'top');
        add_rewrite_rule('^sitemap-posts\.xml$', 'index.php?ssf_sitemap=posts', 'top');
        add_rewrite_rule('^sitemap-pages\.xml$', 'index.php?ssf_sitemap=pages', 'top');
        add_rewrite_rule('^sitemap-categories\.xml$', 'index.php?ssf_sitemap=categories', 'top');
        add_rewrite_rule('^sitemap-tags\.xml$', 'index.php?ssf_sitemap=tags', 'top');
        add_rewrite_rule('^sitemap-authors\.xml$', 'index.php?ssf_sitemap=authors', 'top');
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'ssf_sitemap';
        return $vars;
    }
    
    /**
     * Render sitemap
     */
    public function render_sitemap() {
        $sitemap_type = get_query_var('ssf_sitemap');
        
        if (empty($sitemap_type)) {
            return;
        }
        
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex, follow');
        
        switch ($sitemap_type) {
            case 'index':
                echo $this->generate_index_sitemap();
                break;
            case 'posts':
                echo $this->generate_posts_sitemap();
                break;
            case 'pages':
                echo $this->generate_pages_sitemap();
                break;
            case 'categories':
                echo $this->generate_categories_sitemap();
                break;
            case 'tags':
                echo $this->generate_tags_sitemap();
                break;
            case 'authors':
                echo $this->generate_authors_sitemap();
                break;
        }
        
        exit;
    }
    
    /**
     * Generate sitemap index
     */
    private function generate_index_sitemap() {
        $output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $output .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Posts sitemap
        $last_post = get_posts([
            'numberposts' => 1,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);
        
        if (!empty($last_post)) {
            $output .= $this->sitemap_entry(
                home_url('/sitemap-posts.xml'),
                get_post_modified_time('c', true, $last_post[0])
            );
        }
        
        // Pages sitemap
        $last_page = get_posts([
            'numberposts' => 1,
            'post_type' => 'page',
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);
        
        if (!empty($last_page)) {
            $output .= $this->sitemap_entry(
                home_url('/sitemap-pages.xml'),
                get_post_modified_time('c', true, $last_page[0])
            );
        }
        
        // Categories sitemap
        $output .= $this->sitemap_entry(home_url('/sitemap-categories.xml'));
        
        // Tags sitemap
        $output .= $this->sitemap_entry(home_url('/sitemap-tags.xml'));
        
        // Authors sitemap
        $output .= $this->sitemap_entry(home_url('/sitemap-authors.xml'));
        
        $output .= '</sitemapindex>';
        
        return $output;
    }
    
    /**
     * Generate posts sitemap
     */
    private function generate_posts_sitemap() {
        $output = $this->sitemap_header();
        
        $posts = get_posts([
            'numberposts' => 1000,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);
        
        foreach ($posts as $post) {
            // Skip if noindex
            if (get_post_meta($post->ID, '_ssf_noindex', true)) {
                continue;
            }
            
            $output .= $this->url_entry(
                get_permalink($post->ID),
                get_post_modified_time('c', true, $post),
                'weekly',
                '0.8'
            );
        }
        
        $output .= '</urlset>';
        
        return $output;
    }
    
    /**
     * Generate pages sitemap
     */
    private function generate_pages_sitemap() {
        $output = $this->sitemap_header();
        
        // Add homepage
        $output .= $this->url_entry(
            home_url('/'),
            current_time('c'),
            'daily',
            '1.0'
        );
        
        $pages = get_posts([
            'numberposts' => 1000,
            'post_type' => 'page',
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);
        
        foreach ($pages as $page) {
            // Skip if noindex
            if (get_post_meta($page->ID, '_ssf_noindex', true)) {
                continue;
            }
            
            // Skip homepage (already added)
            if ($page->ID == get_option('page_on_front')) {
                continue;
            }
            
            $output .= $this->url_entry(
                get_permalink($page->ID),
                get_post_modified_time('c', true, $page),
                'monthly',
                '0.6'
            );
        }
        
        $output .= '</urlset>';
        
        return $output;
    }
    
    /**
     * Generate categories sitemap
     */
    private function generate_categories_sitemap() {
        $output = $this->sitemap_header();
        
        $categories = get_categories([
            'hide_empty' => true,
        ]);
        
        foreach ($categories as $category) {
            $output .= $this->url_entry(
                get_category_link($category->term_id),
                '',
                'weekly',
                '0.5'
            );
        }
        
        $output .= '</urlset>';
        
        return $output;
    }
    
    /**
     * Generate tags sitemap
     */
    private function generate_tags_sitemap() {
        $output = $this->sitemap_header();
        
        $tags = get_tags([
            'hide_empty' => true,
        ]);
        
        foreach ($tags as $tag) {
            $output .= $this->url_entry(
                get_tag_link($tag->term_id),
                '',
                'weekly',
                '0.3'
            );
        }
        
        $output .= '</urlset>';
        
        return $output;
    }
    
    /**
     * Generate authors sitemap
     */
    private function generate_authors_sitemap() {
        $output = $this->sitemap_header();
        
        $authors = get_users([
            'who' => 'authors',
            'has_published_posts' => true,
        ]);
        
        foreach ($authors as $author) {
            $output .= $this->url_entry(
                get_author_posts_url($author->ID),
                '',
                'weekly',
                '0.4'
            );
        }
        
        $output .= '</urlset>';
        
        return $output;
    }
    
    /**
     * Sitemap header
     */
    private function sitemap_header() {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    }
    
    /**
     * Sitemap index entry
     */
    private function sitemap_entry($loc, $lastmod = '') {
        $output = "  <sitemap>\n";
        $output .= "    <loc>" . esc_url($loc) . "</loc>\n";
        
        if (!empty($lastmod)) {
            $output .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        
        $output .= "  </sitemap>\n";
        
        return $output;
    }
    
    /**
     * URL entry
     */
    private function url_entry($loc, $lastmod = '', $changefreq = 'weekly', $priority = '0.5') {
        // Normalize URL for consistency (trailing slashes, etc.)
        $loc = apply_filters('ssf_sitemap_url', $loc);
        
        // Enforce trailing slash consistency to match WordPress permalink structure
        $loc = $this->normalize_url_slash($loc);
        
        $output = "  <url>\n";
        $output .= "    <loc>" . esc_url($loc) . "</loc>\n";
        
        if (!empty($lastmod)) {
            $output .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        
        $output .= "    <changefreq>{$changefreq}</changefreq>\n";
        $output .= "    <priority>{$priority}</priority>\n";
        $output .= "  </url>\n";
        
        return $output;
    }
    
    /**
     * Ping search engines
     */
    public function ping_search_engines() {
        $sitemap_url = home_url('/sitemap.xml');
        
        // Ping Google
        wp_remote_get('https://www.google.com/ping?sitemap=' . urlencode($sitemap_url), [
            'blocking' => false,
        ]);
        
        // Ping Bing
        wp_remote_get('https://www.bing.com/ping?sitemap=' . urlencode($sitemap_url), [
            'blocking' => false,
        ]);
    }
    
    /**
     * Get sitemap URL
     */
    public function get_sitemap_url() {
        return home_url('/sitemap.xml');
    }
    
    /**
     * Normalize trailing slashes on a URL to match WordPress permalink settings
     */
    private function normalize_url_slash($url) {
        if (empty($url)) return $url;
        
        $parsed = wp_parse_url($url);
        $path = $parsed['path'] ?? '/';
        
        // Don't modify URLs with file extensions (e.g., /sitemap.xml)
        if (preg_match('/\.\w{2,5}$/', $path)) {
            return $url;
        }
        
        $permalink_structure = get_option('permalink_structure', '');
        $uses_trailing_slash = !empty($permalink_structure) && substr($permalink_structure, -1) === '/';
        
        if ($uses_trailing_slash) {
            return trailingslashit($url);
        } else {
            return untrailingslashit($url);
        }
    }
    
    /**
     * Flush rewrite rules
     */
    public static function flush_rules() {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }
}

