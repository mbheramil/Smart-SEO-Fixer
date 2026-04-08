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
     * Max URLs per sitemap file (Google's limit is 50,000)
     */
    const MAX_URLS_PER_SITEMAP = 2000;
    
    /**
     * Constructor
     */
    public function __construct() {
        if (Smart_SEO_Fixer::get_option('enable_sitemap', true)) {
            add_action('init', [$this, 'add_rewrite_rules'], 1);
            add_filter('query_vars', [$this, 'add_query_vars']);
            add_action('template_redirect', [$this, 'render_sitemap'], 1);
            
            // Intercept sitemap + XSL requests early, before other plugins can serve theirs
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
     * Get all public post types that should be in the sitemap.
     */
    private function get_sitemap_post_types() {
        $post_types = get_post_types(['public' => true], 'objects');
        $result = [];
        foreach ($post_types as $pt) {
            // Skip attachments — they are just media files
            if ($pt->name === 'attachment') {
                continue;
            }
            $result[] = $pt->name;
        }
        return $result;
    }
    
    /**
     * Get all public taxonomies that should be in the sitemap.
     */
    private function get_sitemap_taxonomies() {
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        $result = [];
        foreach ($taxonomies as $tax) {
            // Skip post_format
            if ($tax->name === 'post_format') {
                continue;
            }
            $result[] = $tax->name;
        }
        return $result;
    }
    
    /**
     * Intercept sitemap requests early before other plugins can serve theirs.
     */
    public function intercept_sitemap_request($wp) {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
        
        // Strip the site subdirectory if WordPress is in a subdirectory
        $home_path = trim(parse_url(home_url(), PHP_URL_PATH) ?: '', '/');
        if ($home_path && strpos($path, $home_path . '/') === 0) {
            $path = substr($path, strlen($home_path) + 1);
        }
        
        // XSL stylesheets
        if ($path === 'ssf-sitemap-index.xsl') {
            $wp->query_vars['ssf_sitemap'] = 'xsl-index';
            return;
        }
        if ($path === 'ssf-sitemap.xsl') {
            $wp->query_vars['ssf_sitemap'] = 'xsl';
            return;
        }
        
        if ($path === 'sitemap.xml') {
            $wp->query_vars['ssf_sitemap'] = 'index';
            return;
        }
        
        // Match post type sitemaps: sitemap-{type}.xml or sitemap-{type}{page}.xml
        if (preg_match('/^sitemap-(.+?)(\d*)\.xml$/', $path, $m)) {
            $slug = $m[1];
            $page = $m[2] !== '' ? intval($m[2]) : 1;
            
            // Check post types
            $post_types = $this->get_sitemap_post_types();
            foreach ($post_types as $pt) {
                $pt_slug = $this->post_type_slug($pt);
                if ($slug === $pt_slug || $slug === $pt_slug . '-') {
                    $wp->query_vars['ssf_sitemap'] = 'pt:' . $pt . ':' . $page;
                    return;
                }
            }
            
            // Check taxonomies
            $taxonomies = $this->get_sitemap_taxonomies();
            foreach ($taxonomies as $tax) {
                $tax_slug = $this->taxonomy_slug($tax);
                if ($slug === $tax_slug || $slug === $tax_slug . '-') {
                    $wp->query_vars['ssf_sitemap'] = 'tax:' . $tax . ':' . $page;
                    return;
                }
            }
            
            // Authors
            if ($slug === 'authors' || $slug === 'authors-') {
                $wp->query_vars['ssf_sitemap'] = 'authors:' . $page;
                return;
            }
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
        // Index
        add_rewrite_rule('^sitemap\.xml$', 'index.php?ssf_sitemap=index', 'top');
        
        // XSL stylesheets
        add_rewrite_rule('^ssf-sitemap-index\.xsl$', 'index.php?ssf_sitemap=xsl-index', 'top');
        add_rewrite_rule('^ssf-sitemap\.xsl$', 'index.php?ssf_sitemap=xsl', 'top');
        
        // Catch-all for any sub-sitemap: sitemap-{anything}.xml
        // The actual parsing is done in intercept_sitemap_request
        add_rewrite_rule('^sitemap-([a-zA-Z0-9_-]+?)(\d*)\.xml$', 'index.php?ssf_sitemap=dynamic', 'top');
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
        
        $output = '';
        
        if ($sitemap_type === 'index') {
            $output = $this->generate_index_sitemap();
            header('Content-Type: application/xml; charset=UTF-8');
        } elseif ($sitemap_type === 'xsl-index') {
            header('Content-Type: text/xsl; charset=UTF-8');
            header('X-Robots-Tag: noindex, follow');
            echo $this->get_xsl_stylesheet(true);
            exit;
        } elseif ($sitemap_type === 'xsl') {
            header('Content-Type: text/xsl; charset=UTF-8');
            header('X-Robots-Tag: noindex, follow');
            echo $this->get_xsl_stylesheet(false);
            exit;
        } elseif (strpos($sitemap_type, 'pt:') === 0) {
            // Post type: pt:{post_type}:{page}
            $parts = explode(':', $sitemap_type);
            $pt = $parts[1] ?? 'post';
            $page = intval($parts[2] ?? 1);
            $output = $this->generate_post_type_sitemap($pt, $page);
        } elseif (strpos($sitemap_type, 'tax:') === 0) {
            // Taxonomy: tax:{taxonomy}:{page}
            $parts = explode(':', $sitemap_type);
            $tax = $parts[1] ?? 'category';
            $page = intval($parts[2] ?? 1);
            $output = $this->generate_taxonomy_sitemap($tax, $page);
        } elseif (strpos($sitemap_type, 'authors') === 0) {
            $parts = explode(':', $sitemap_type);
            $page = intval($parts[1] ?? 1);
            $output = $this->generate_authors_sitemap($page);
        }
        
        if (empty($output)) {
            return;
        }
        
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex, follow');
        echo $output;
        exit;
    }
    
    /**
     * Get a URL-friendly slug for a post type sitemap.
     */
    private function post_type_slug($post_type) {
        $map = [
            'post' => 'post',
            'page' => 'page',
        ];
        return isset($map[$post_type]) ? $map[$post_type] : sanitize_title($post_type);
    }
    
    /**
     * Get a URL-friendly slug for a taxonomy sitemap.
     */
    private function taxonomy_slug($taxonomy) {
        $map = [
            'category' => 'category',
            'post_tag' => 'post_tag',
        ];
        return isset($map[$taxonomy]) ? $map[$taxonomy] : sanitize_title($taxonomy);
    }
    
    /**
     * Count published posts for a post type.
     */
    private function count_posts_for_type($post_type) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
            $post_type
        ));
    }
    
    /**
     * Count terms for a taxonomy.
     */
    private function count_terms_for_tax($taxonomy) {
        return (int) wp_count_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
    }
    
    /**
     * Generate sitemap index — automatically includes all public post types and taxonomies.
     */
    private function generate_index_sitemap() {
        $xsl_url = esc_url(home_url('/?ssf_sitemap=xsl-index'));
        $output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $output .= '<?xml-stylesheet type="text/xsl" href="' . $xsl_url . '"?>' . "\n";
        $output .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Post type sitemaps
        foreach ($this->get_sitemap_post_types() as $pt) {
            $count = $this->count_posts_for_type($pt);
            if ($count === 0) {
                continue;
            }
            
            $pages = max(1, ceil($count / self::MAX_URLS_PER_SITEMAP));
            $slug = $this->post_type_slug($pt);
            
            // Get latest modified date for this post type
            $last_post = get_posts([
                'numberposts' => 1,
                'post_type' => $pt,
                'post_status' => 'publish',
                'orderby' => 'modified',
                'order' => 'DESC',
            ]);
            $lastmod = !empty($last_post) ? get_post_modified_time('c', true, $last_post[0]) : '';
            
            for ($p = 1; $p <= $pages; $p++) {
                $suffix = $pages > 1 ? $p : '';
                $output .= $this->sitemap_entry(
                    home_url('/sitemap-' . $slug . $suffix . '.xml'),
                    $lastmod
                );
            }
        }
        
        // Taxonomy sitemaps
        foreach ($this->get_sitemap_taxonomies() as $tax) {
            $count = $this->count_terms_for_tax($tax);
            if ($count === 0) {
                continue;
            }
            
            $pages = max(1, ceil($count / self::MAX_URLS_PER_SITEMAP));
            $slug = $this->taxonomy_slug($tax);
            
            for ($p = 1; $p <= $pages; $p++) {
                $suffix = $pages > 1 ? $p : '';
                $output .= $this->sitemap_entry(
                    home_url('/sitemap-' . $slug . $suffix . '.xml')
                );
            }
        }
        
        // Authors sitemap
        $author_count = count(get_users([
            'has_published_posts' => true,
            'fields' => 'ID',
        ]));
        if ($author_count > 0) {
            $output .= $this->sitemap_entry(home_url('/sitemap-authors.xml'));
        }
        
        $output .= '</sitemapindex>';
        
        return $output;
    }
    
    /**
     * Generate sitemap for any post type, with pagination.
     */
    private function generate_post_type_sitemap($post_type, $page = 1) {
        $output = $this->sitemap_header();
        
        $offset = ($page - 1) * self::MAX_URLS_PER_SITEMAP;
        
        // Add homepage for page type, page 1
        if ($post_type === 'page' && $page === 1) {
            $output .= $this->url_entry(
                home_url('/'),
                current_time('c'),
                'daily',
                '1.0'
            );
        }
        
        $posts = get_posts([
            'numberposts' => self::MAX_URLS_PER_SITEMAP,
            'offset'      => $offset,
            'post_type'   => $post_type,
            'post_status' => 'publish',
            'orderby'     => 'modified',
            'order'       => 'DESC',
        ]);
        
        $is_page = ($post_type === 'page');
        $front_page_id = $is_page ? (int) get_option('page_on_front') : 0;
        
        foreach ($posts as $post) {
            // Skip noindex
            if (get_post_meta($post->ID, '_ssf_noindex', true)) {
                continue;
            }
            // Skip homepage (already added above)
            if ($is_page && $post->ID === $front_page_id) {
                continue;
            }
            
            $priority = $is_page ? '0.6' : '0.8';
            $freq = $is_page ? 'monthly' : 'weekly';
            
            $output .= $this->url_entry(
                get_permalink($post->ID),
                get_post_modified_time('c', true, $post),
                $freq,
                $priority
            );
        }
        
        $output .= '</urlset>';
        return $output;
    }
    
    /**
     * Generate sitemap for any taxonomy, with pagination.
     */
    private function generate_taxonomy_sitemap($taxonomy, $page = 1) {
        $output = $this->sitemap_header();
        
        $offset = ($page - 1) * self::MAX_URLS_PER_SITEMAP;
        
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
            'number'     => self::MAX_URLS_PER_SITEMAP,
            'offset'     => $offset,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ]);
        
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $output .= $this->url_entry(
                    get_term_link($term),
                    '',
                    'weekly',
                    '0.5'
                );
            }
        }
        
        $output .= '</urlset>';
        return $output;
    }
    
    /**
     * Generate authors sitemap, with pagination.
     */
    private function generate_authors_sitemap($page = 1) {
        $output = $this->sitemap_header();
        
        $offset = ($page - 1) * self::MAX_URLS_PER_SITEMAP;
        
        $authors = get_users([
            'has_published_posts' => true,
            'number'  => self::MAX_URLS_PER_SITEMAP,
            'offset'  => $offset,
            'orderby' => 'post_count',
            'order'   => 'DESC',
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
     * Generate XSL stylesheet for styled sitemap display in browsers.
     */
    private function get_xsl_stylesheet($is_index = false) {
        $site_name = esc_html(get_bloginfo('name'));
        $plugin_name = 'Smart SEO Fixer';
        $home = esc_url(home_url('/'));
        $sitemap_url = esc_url(home_url('/sitemap.xml'));
        
        $xsl = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xsl .= '<xsl:stylesheet version="2.0"
            xmlns:html="http://www.w3.org/TR/REC-html40"
            xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
            xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
        <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
        <xsl:template match="/">';
        
        $xsl .= '<html xmlns="http://www.w3.org/1999/xhtml"><head>
        <title>' . ($is_index ? 'XML Sitemap Index' : 'XML Sitemap') . ' — ' . $site_name . '</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="robots" content="noindex, follow"/>
        <style type="text/css">
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                color: #1e293b;
                background: #f1f5f9;
                line-height: 1.6;
            }
            .container {
                max-width: 1024px;
                margin: 0 auto;
                padding: 32px 20px;
            }
            .header {
                background: linear-gradient(135deg, #0f172a, #1e40af);
                color: #fff;
                padding: 32px 40px;
                border-radius: 12px;
                margin-bottom: 24px;
            }
            .header h1 {
                font-size: 22px;
                font-weight: 700;
                margin-bottom: 6px;
            }
            .header p {
                font-size: 13px;
                color: #94a3b8;
                margin: 0;
            }
            .header p a {
                color: #60a5fa;
                text-decoration: none;
            }
            .header p a:hover {
                text-decoration: underline;
            }
            .badge {
                display: inline-block;
                background: rgba(255,255,255,0.15);
                color: #e2e8f0;
                font-size: 11px;
                padding: 3px 10px;
                border-radius: 20px;
                margin-left: 10px;
                font-weight: 500;
                vertical-align: middle;
            }
            .info {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 14px 20px;
                margin-bottom: 20px;
                font-size: 13px;
                color: #64748b;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                background: #fff;
                border-radius: 10px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            }
            th {
                background: #f8fafc;
                color: #475569;
                font-weight: 600;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                padding: 12px 16px;
                text-align: left;
                border-bottom: 2px solid #e2e8f0;
            }
            td {
                padding: 10px 16px;
                font-size: 13px;
                border-bottom: 1px solid #f1f5f9;
                color: #334155;
            }
            tr:last-child td { border-bottom: none; }
            tr:hover td { background: #f8fafc; }
            td a {
                color: #2563eb;
                text-decoration: none;
                word-break: break-all;
            }
            td a:hover { text-decoration: underline; }
            .row-num {
                color: #94a3b8;
                font-size: 12px;
                font-variant-numeric: tabular-nums;
            }
            .date {
                color: #64748b;
                font-size: 12px;
                white-space: nowrap;
            }
            .priority {
                font-weight: 600;
                font-size: 12px;
            }
            .freq {
                font-size: 12px;
                color: #64748b;
                text-transform: capitalize;
            }
            .footer {
                text-align: center;
                padding: 20px;
                font-size: 12px;
                color: #94a3b8;
            }
            .footer a { color: #64748b; text-decoration: none; }
            .footer a:hover { text-decoration: underline; }
        </style>
        </head><body><div class="container">';
        
        // Header
        $xsl .= '<div class="header">';
        $xsl .= '<h1>' . ($is_index ? 'XML Sitemap Index' : 'XML Sitemap');
        
        if ($is_index) {
            $xsl .= '<span class="badge"><xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap)"/> sitemaps</span>';
        } else {
            $xsl .= '<span class="badge"><xsl:value-of select="count(sitemap:urlset/sitemap:url)"/> URLs</span>';
        }
        
        $xsl .= '</h1>';
        $xsl .= '<p>Generated by <strong>' . $plugin_name . '</strong> for <a href="' . $home . '">' . $site_name . '</a></p>';
        $xsl .= '</div>';
        
        // Info box
        if ($is_index) {
            $xsl .= '<div class="info">This XML sitemap index lists all sub-sitemaps for this website. It is used by search engines like Google and Bing to discover and crawl your content.</div>';
        } else {
            $xsl .= '<div class="info">This XML sitemap contains the list of URLs for this section. <a href="' . $sitemap_url . '">← Back to Sitemap Index</a></div>';
        }
        
        // Table
        if ($is_index) {
            $xsl .= '<table>';
            $xsl .= '<thead><tr><th style="width:50px">#</th><th>Sitemap</th><th style="width:200px">Last Modified</th></tr></thead>';
            $xsl .= '<tbody>';
            $xsl .= '<xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">';
            $xsl .= '<tr>';
            $xsl .= '<td class="row-num"><xsl:value-of select="position()"/></td>';
            $xsl .= '<td><a><xsl:attribute name="href"><xsl:value-of select="sitemap:loc"/></xsl:attribute><xsl:value-of select="sitemap:loc"/></a></td>';
            $xsl .= '<td class="date"><xsl:if test="sitemap:lastmod"><xsl:value-of select="concat(substring(sitemap:lastmod,1,10),\' \',substring(sitemap:lastmod,12,5))"/></xsl:if></td>';
            $xsl .= '</tr>';
            $xsl .= '</xsl:for-each>';
            $xsl .= '</tbody></table>';
        } else {
            $xsl .= '<table>';
            $xsl .= '<thead><tr><th style="width:50px">#</th><th>URL</th><th style="width:100px">Priority</th><th style="width:100px">Frequency</th><th style="width:180px">Last Modified</th></tr></thead>';
            $xsl .= '<tbody>';
            $xsl .= '<xsl:for-each select="sitemap:urlset/sitemap:url">';
            $xsl .= '<tr>';
            $xsl .= '<td class="row-num"><xsl:value-of select="position()"/></td>';
            $xsl .= '<td><a><xsl:attribute name="href"><xsl:value-of select="sitemap:loc"/></xsl:attribute><xsl:value-of select="sitemap:loc"/></a></td>';
            $xsl .= '<td class="priority"><xsl:value-of select="sitemap:priority"/></td>';
            $xsl .= '<td class="freq"><xsl:value-of select="sitemap:changefreq"/></td>';
            $xsl .= '<td class="date"><xsl:if test="sitemap:lastmod"><xsl:value-of select="concat(substring(sitemap:lastmod,1,10),\' \',substring(sitemap:lastmod,12,5))"/></xsl:if></td>';
            $xsl .= '</tr>';
            $xsl .= '</xsl:for-each>';
            $xsl .= '</tbody></table>';
        }
        
        $xsl .= '<div class="footer">Generated by <a href="https://github.com/mbheramil/Smart-SEO-Fixer">' . $plugin_name . '</a></div>';
        $xsl .= '</div></body></html>';
        
        $xsl .= '</xsl:template></xsl:stylesheet>';
        
        return $xsl;
    }
    
    /**
     * Sitemap header
     */
    private function sitemap_header() {
        $xsl_url = esc_url(home_url('/?ssf_sitemap=xsl'));
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<?xml-stylesheet type="text/xsl" href="' . $xsl_url . '"?>' . "\n"
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

