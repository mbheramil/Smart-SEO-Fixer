<?php
/**
 * Admin Class
 * 
 * Handles admin menus, pages, and post editor metabox.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box'], 10, 2);
        
        // Auto-generate SEO meta on publish (when auto_meta is enabled)
        add_action('transition_post_status', [$this, 'auto_generate_meta'], 10, 3);
        
        // Show conflict warning if other SEO plugins detected and output not disabled
        add_action('admin_notices', [$this, 'conflict_notice']);
        
        // Auto-generate alt text on image upload (when auto_alt_text is enabled)
        add_action('add_attachment', [$this, 'auto_generate_alt_text']);
        
        // Add SEO score column to posts list
        add_filter('manage_posts_columns', [$this, 'add_seo_column']);
        add_filter('manage_pages_columns', [$this, 'add_seo_column']);
        add_action('manage_posts_custom_column', [$this, 'render_seo_column'], 10, 2);
        add_action('manage_pages_custom_column', [$this, 'render_seo_column'], 10, 2);
        
        // Make column sortable
        add_filter('manage_edit-post_sortable_columns', [$this, 'sortable_seo_column']);
        add_filter('manage_edit-page_sortable_columns', [$this, 'sortable_seo_column']);
        add_action('pre_get_posts', [$this, 'sort_by_seo_score']);
        
        // Grouped admin menu (collapsible categories in sidebar)
        add_action('admin_head', [$this, 'admin_menu_group_css']);
        add_action('admin_footer', [$this, 'admin_menu_group_js']);
    }
    
    /**
     * CSS for grouped admin menu with hover flyouts (native WP style)
     */
    public function admin_menu_group_css() {
        ?>
        <style>
            /* Hide grouped child items from the inline submenu */
            #adminmenu .wp-submenu .ssf-menu-group-item { display: none !important; }
            
            /* Group header as a normal submenu item with arrow */
            #adminmenu .wp-submenu .ssf-flyout-trigger {
                position: relative !important;
                cursor: pointer !important;
            }
            #adminmenu .wp-submenu .ssf-flyout-trigger > a {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
            }
            #adminmenu .wp-submenu .ssf-flyout-trigger .ssf-fly-arrow {
                font-size: 8px;
                opacity: 0.5;
                margin-left: 6px;
            }
            #adminmenu .wp-submenu .ssf-flyout-trigger:hover .ssf-fly-arrow { opacity: 1; }
            
            /* Native WP-style flyout panel */
            .ssf-flyout-panel {
                display: none;
                position: absolute;
                left: 100%;
                top: -7px;
                min-width: 190px;
                background: #fff;
                border: 1px solid #c3c4c7;
                border-left: none;
                box-shadow: 0 3px 5px rgba(0,0,0,0.2);
                padding: 7px 0;
                z-index: 10000;
            }
            .ssf-flyout-trigger:hover > .ssf-flyout-panel,
            .ssf-flyout-trigger.ssf-fly-open > .ssf-flyout-panel {
                display: block;
            }
            .ssf-flyout-panel a {
                display: block;
                padding: 5px 12px;
                color: #50575e !important;
                text-decoration: none !important;
                font-size: 13px;
                line-height: 1.6;
                white-space: nowrap;
            }
            .ssf-flyout-panel a:hover {
                color: #135e96 !important;
                background: #f0f0f1;
            }
            .ssf-flyout-panel a.ssf-fly-current {
                color: #135e96 !important;
                font-weight: 600;
            }
            
            /* Make the current page's group header look active */
            #adminmenu .wp-submenu .ssf-flyout-trigger.ssf-has-current > a {
                color: #fff !important;
                font-weight: 600;
            }
        </style>
        <?php
    }
    
    /**
     * JS for grouped admin menu with hover flyouts
     */
    public function admin_menu_group_js() {
        ?>
        <script>
        (function($) {
            var $menuLi = $('#adminmenu a[href="admin.php?page=smart-seo-fixer"]').first().closest('li.menu-top');
            if (!$menuLi.length) return;
            var $sub = $menuLi.find('ul.wp-submenu');
            if (!$sub.length) return;

            var groups = [
                { label: '<?php echo esc_js(__('Analyze & Fix', 'smart-seo-fixer')); ?>', pages: ['smart-seo-fixer-analyzer','smart-seo-fixer-bulk-fix','smart-seo-fixer-posts','smart-seo-fixer-content-suggestions'] },
                { label: '<?php echo esc_js(__('Technical SEO', 'smart-seo-fixer')); ?>', pages: ['smart-seo-fixer-schema','smart-seo-fixer-local','smart-seo-fixer-redirects','smart-seo-fixer-broken-links','smart-seo-fixer-404-monitor','smart-seo-fixer-robots'] },
                { label: '<?php echo esc_js(__('Search & Social', 'smart-seo-fixer')); ?>', pages: ['smart-seo-fixer-search-performance','smart-seo-fixer-gsc','smart-seo-fixer-social-preview','smart-seo-fixer-keywords'] },
                { label: '<?php echo esc_js(__('System', 'smart-seo-fixer')); ?>', pages: ['smart-seo-fixer-jobs','smart-seo-fixer-history','smart-seo-fixer-migration','smart-seo-fixer-wp-standards','smart-seo-fixer-performance','smart-seo-fixer-debug-log'] }
            ];

            // Detect current page
            var currentSlug = '';
            var $curLi = $sub.find('li.current');
            if ($curLi.length) {
                var m = ($curLi.find('a').attr('href') || '').match(/page=([\w-]+)/);
                if (m) currentSlug = m[1];
            }

            $.each(groups, function(idx, group) {
                var $items = $();
                var hasCurrent = false;
                var flyLinks = '';

                $.each(group.pages, function(_, slug) {
                    var $li = $sub.find('a[href="admin.php?page=' + slug + '"]').closest('li');
                    if ($li.length) {
                        $li.addClass('ssf-menu-group-item');
                        $items = $items.add($li);
                        var txt = $li.find('a').text().trim();
                        var isCur = (slug === currentSlug);
                        if (isCur) hasCurrent = true;
                        flyLinks += '<a href="admin.php?page=' + slug + '"' + (isCur ? ' class="ssf-fly-current"' : '') + '>' + txt + '</a>';
                    }
                });

                if (!$items.length) return;

                // Build the flyout trigger <li>
                var $trigger = $('<li class="ssf-flyout-trigger' + (hasCurrent ? ' ssf-has-current' : '') + '">' +
                    '<a href="#">' + group.label + ' <span class="ssf-fly-arrow">&#9654;</span></a>' +
                    '<div class="ssf-flyout-panel">' + flyLinks + '</div></li>');

                // Prevent the # link from navigating
                $trigger.find('> a').on('click', function(e) { e.preventDefault(); });

                // Insert before the first item of this group
                $items.first().before($trigger);
            });
        })(jQuery);
        </script>
        <?php
    }
    
    /**
     * Show admin notice when conflicting SEO plugins are detected
     */
    public function conflict_notice() {
        // Only show on our plugin pages or the plugins page
        $screen = get_current_screen();
        if (!$screen) return;
        
        $our_pages = [
            'toplevel_page_smart-seo-fixer',
            'smart-seo_page_smart-seo-fixer-analyzer',
            'smart-seo_page_smart-seo-fixer-bulk-fix',
            'smart-seo_page_smart-seo-fixer-settings',
            'smart-seo_page_smart-seo-fixer-posts',
            'smart-seo_page_smart-seo-fixer-schema',
            'smart-seo_page_smart-seo-fixer-local',
            'smart-seo_page_smart-seo-fixer-search-performance',
            'smart-seo_page_smart-seo-fixer-migration',
            'smart-seo_page_smart-seo-fixer-history',
            'smart-seo_page_smart-seo-fixer-debug-log',
            'smart-seo_page_smart-seo-fixer-jobs',
            'smart-seo_page_smart-seo-fixer-broken-links',
            'smart-seo_page_smart-seo-fixer-404-monitor',
            'smart-seo_page_smart-seo-fixer-robots',
            'smart-seo_page_smart-seo-fixer-social-preview',
            'smart-seo_page_smart-seo-fixer-keywords',
            'smart-seo_page_smart-seo-fixer-content-suggestions',
            'smart-seo_page_smart-seo-fixer-wp-standards',
            'smart-seo_page_smart-seo-fixer-performance',
            'plugins',
        ];
        
        if (!in_array($screen->id, $our_pages)) return;
        
        // Already disabled? Don't show
        if (Smart_SEO_Fixer::get_option('disable_other_seo_output', false)) return;
        
        // Detect conflicting plugins
        $conflicts = [];
        if (defined('WPSEO_VERSION')) $conflicts[] = 'Yoast SEO';
        if (defined('RANK_MATH_VERSION')) $conflicts[] = 'Rank Math';
        if (defined('AIOSEO_VERSION') || class_exists('AIOSEOP_Core')) $conflicts[] = 'All in One SEO';
        if (defined('THE_SEO_FRAMEWORK_VERSION')) $conflicts[] = 'The SEO Framework';
        if (defined('SEOPRESS_VERSION')) $conflicts[] = 'SEOPress';
        
        if (empty($conflicts)) return;
        
        $dismiss_key = 'ssf_conflict_dismissed';
        if (get_option($dismiss_key)) return;
        
        $plugins_list = '<strong>' . esc_html(implode(', ', $conflicts)) . '</strong>';
        $settings_url = admin_url('admin.php?page=smart-seo-fixer-settings');
        $migration_url = admin_url('admin.php?page=smart-seo-fixer-migration');
        
        echo '<div class="notice notice-warning" style="border-left-color: #f59e0b; padding: 12px 15px;">';
        echo '<p style="font-size: 14px; margin: 0 0 8px;"><strong>Smart SEO Fixer — ' . esc_html__('Duplicate Meta Tags Detected', 'smart-seo-fixer') . '</strong></p>';
        echo '<p style="margin: 0 0 8px;">';
        printf(
            /* translators: %s: list of conflicting plugins */
            esc_html__('You have %s active alongside Smart SEO Fixer. This causes duplicate meta descriptions, Open Graph tags, and schema markup — which hurts your SEO.', 'smart-seo-fixer'),
            $plugins_list
        );
        echo '</p>';
        echo '<p style="margin: 0;">';
        echo '<a href="' . esc_url($migration_url) . '" class="button" style="margin-right: 8px;">' . esc_html__('1. Import SEO Data', 'smart-seo-fixer') . '</a>';
        echo '<a href="' . esc_url($settings_url) . '" class="button button-primary">' . esc_html__('2. Disable Duplicate Output', 'smart-seo-fixer') . '</a>';
        echo '</p>';
        echo '</div>';
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Smart SEO Fixer', 'smart-seo-fixer'),
            __('Smart SEO', 'smart-seo-fixer'),
            'edit_posts',
            'smart-seo-fixer',
            [$this, 'render_dashboard'],
            'dashicons-chart-line',
            80
        );
        
        // Dashboard submenu
        add_submenu_page(
            'smart-seo-fixer',
            __('Dashboard', 'smart-seo-fixer'),
            __('Dashboard', 'smart-seo-fixer'),
            'edit_posts',
            'smart-seo-fixer',
            [$this, 'render_dashboard']
        );
        
        // ── Analyze & Fix ──
        add_submenu_page('smart-seo-fixer', __('SEO Analyzer', 'smart-seo-fixer'), __('SEO Analyzer', 'smart-seo-fixer'), 'edit_posts', 'smart-seo-fixer-analyzer', [$this, 'render_analyzer']);
        add_submenu_page('smart-seo-fixer', __('Bulk AI Fix', 'smart-seo-fixer'), __('Bulk AI Fix', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-bulk-fix', [$this, 'render_bulk_fix']);
        add_submenu_page('smart-seo-fixer', __('All Posts', 'smart-seo-fixer'), __('All Posts', 'smart-seo-fixer'), 'edit_posts', 'smart-seo-fixer-posts', [$this, 'render_posts_page']);
        add_submenu_page('smart-seo-fixer', __('Content Suggestions', 'smart-seo-fixer'), __('Content Tips', 'smart-seo-fixer'), 'edit_posts', 'smart-seo-fixer-content-suggestions', [$this, 'render_content_suggestions']);
        
        // ── Technical SEO ──
        add_submenu_page('smart-seo-fixer', __('Schema', 'smart-seo-fixer'), __('Schema', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-schema', [$this, 'render_schema_page']);
        add_submenu_page('smart-seo-fixer', __('Local SEO', 'smart-seo-fixer'), __('Local SEO', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-local', [$this, 'render_local_seo']);
        add_submenu_page('smart-seo-fixer', __('Redirects', 'smart-seo-fixer'), __('Redirects', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-redirects', [$this, 'render_redirects_page']);
        add_submenu_page('smart-seo-fixer', __('Broken Links', 'smart-seo-fixer'), __('Broken Links', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-broken-links', [$this, 'render_broken_links']);
        add_submenu_page('smart-seo-fixer', __('404 Monitor', 'smart-seo-fixer'), __('404 Monitor', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-404-monitor', [$this, 'render_404_monitor']);
        add_submenu_page('smart-seo-fixer', __('robots.txt', 'smart-seo-fixer'), __('robots.txt', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-robots', [$this, 'render_robots_editor']);
        
        // ── Search & Social ──
        add_submenu_page('smart-seo-fixer', __('Search Performance', 'smart-seo-fixer'), __('Search Perf.', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-search-performance', [$this, 'render_search_performance']);
        add_submenu_page('smart-seo-fixer', __('Indexability Auditor', 'smart-seo-fixer'), __('Indexability', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-gsc', [$this, 'render_gsc_page']);
        add_submenu_page('smart-seo-fixer', __('Social Preview', 'smart-seo-fixer'), __('Social Preview', 'smart-seo-fixer'), 'edit_posts', 'smart-seo-fixer-social-preview', [$this, 'render_social_preview']);
        add_submenu_page('smart-seo-fixer', __('Keyword Tracker', 'smart-seo-fixer'), __('Keywords', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-keywords', [$this, 'render_keyword_tracker']);
        
        // ── System ──
        add_submenu_page('smart-seo-fixer', __('Background Jobs', 'smart-seo-fixer'), __('Jobs', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-jobs', [$this, 'render_job_queue']);
        add_submenu_page('smart-seo-fixer', __('Change History', 'smart-seo-fixer'), __('History', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-history', [$this, 'render_change_history']);
        add_submenu_page('smart-seo-fixer', __('Migration', 'smart-seo-fixer'), __('Migration', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-migration', [$this, 'render_migration']);
        add_submenu_page('smart-seo-fixer', __('Coding Standards', 'smart-seo-fixer'), __('Code Audit', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-wp-standards', [$this, 'render_wp_standards']);
        add_submenu_page('smart-seo-fixer', __('Performance', 'smart-seo-fixer'), __('Performance', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-performance', [$this, 'render_performance']);
        add_submenu_page('smart-seo-fixer', __('Debug Log', 'smart-seo-fixer'), __('Debug Log', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-debug-log', [$this, 'render_debug_log']);
        
        // ── Always visible ──
        add_submenu_page('smart-seo-fixer', __('Settings', 'smart-seo-fixer'), __('Settings', 'smart-seo-fixer'), 'manage_options', 'smart-seo-fixer-settings', [$this, 'render_settings']);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        global $post;
        
        // Only on our pages or post editor
        $our_pages = [
            'toplevel_page_smart-seo-fixer',
            'smart-seo_page_smart-seo-fixer-analyzer',
            'smart-seo_page_smart-seo-fixer-bulk-fix',
            'smart-seo_page_smart-seo-fixer-posts',
            'smart-seo_page_smart-seo-fixer-settings',
            'smart-seo_page_smart-seo-fixer-local',
            'smart-seo_page_smart-seo-fixer-schema',
            'smart-seo_page_smart-seo-fixer-redirects',
            'smart-seo_page_smart-seo-fixer-gsc',
            'smart-seo_page_smart-seo-fixer-search-performance',
            'smart-seo_page_smart-seo-fixer-migration',
            'smart-seo_page_smart-seo-fixer-history',
            'smart-seo_page_smart-seo-fixer-debug-log',
            'smart-seo_page_smart-seo-fixer-jobs',
            'smart-seo_page_smart-seo-fixer-broken-links',
            'smart-seo_page_smart-seo-fixer-404-monitor',
            'smart-seo_page_smart-seo-fixer-robots',
            'smart-seo_page_smart-seo-fixer-social-preview',
            'smart-seo_page_smart-seo-fixer-keywords',
            'smart-seo_page_smart-seo-fixer-content-suggestions',
            'smart-seo_page_smart-seo-fixer-wp-standards',
            'smart-seo_page_smart-seo-fixer-performance',
        ];
        
        $is_our_page = in_array($hook, $our_pages);
        $is_editor = in_array($hook, ['post.php', 'post-new.php']);
        
        if (!$is_our_page && !$is_editor) {
            return;
        }
        
        wp_enqueue_style(
            'ssf-admin',
            SSF_PLUGIN_URL . 'admin/css/admin.css',
            [],
            SSF_VERSION
        );
        
        wp_enqueue_script(
            'ssf-admin',
            SSF_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery'],
            SSF_VERSION,
            true
        );
        
        wp_localize_script('ssf-admin', 'ssfAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ssf_nonce'),
            'post_id' => $post ? $post->ID : 0,
            'strings' => [
                'analyzing' => __('Analyzing...', 'smart-seo-fixer'),
                'generating' => __('Generating...', 'smart-seo-fixer'),
                'saving' => __('Saving...', 'smart-seo-fixer'),
                'fixing' => __('Fixing...', 'smart-seo-fixer'),
                'error' => __('An error occurred.', 'smart-seo-fixer'),
                'confirm_fix' => __('Apply this AI-generated content?', 'smart-seo-fixer'),
            ],
        ]);
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        include SSF_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Render SEO Analyzer page
     */
    public function render_analyzer() {
        include SSF_PLUGIN_DIR . 'admin/views/analyzer.php';
    }
    
    /**
     * Render Bulk AI Fix page
     */
    public function render_bulk_fix() {
        include SSF_PLUGIN_DIR . 'admin/views/bulk-fix.php';
    }
    
    /**
     * Render posts page
     */
    public function render_posts_page() {
        include SSF_PLUGIN_DIR . 'admin/views/posts.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        include SSF_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Render local SEO page
     */
    public function render_local_seo() {
        include SSF_PLUGIN_DIR . 'admin/views/local-seo.php';
    }
    
    /**
     * Render redirects page
     */
    public function render_redirects_page() {
        include SSF_PLUGIN_DIR . 'admin/views/redirects.php';
    }
    
    /**
     * Render schema management page
     */
    public function render_schema_page() {
        include SSF_PLUGIN_DIR . 'admin/views/schema.php';
    }
    
    /**
     * Render migration page
     */
    public function render_migration() {
        include SSF_PLUGIN_DIR . 'admin/views/migration.php';
    }
    
    /**
     * Render Search Console Fixer page
     */
    public function render_search_performance() {
        include SSF_PLUGIN_DIR . 'admin/views/search-performance.php';
    }
    
    public function render_gsc_page() {
        include SSF_PLUGIN_DIR . 'admin/views/search-console.php';
    }
    
    /**
     * Render Job Queue page
     */
    public function render_job_queue() {
        include SSF_PLUGIN_DIR . 'admin/views/job-queue.php';
    }
    
    /**
     * Render Change History page
     */
    public function render_change_history() {
        include SSF_PLUGIN_DIR . 'admin/views/change-history.php';
    }
    
    /**
     * Render Broken Links page
     */
    public function render_broken_links() {
        include SSF_PLUGIN_DIR . 'admin/views/broken-links.php';
    }
    
    /**
     * Render 404 Monitor page
     */
    public function render_404_monitor() {
        include SSF_PLUGIN_DIR . 'admin/views/404-monitor.php';
    }
    
    /**
     * Render robots.txt Editor page
     */
    public function render_robots_editor() {
        include SSF_PLUGIN_DIR . 'admin/views/robots-editor.php';
    }
    
    /**
     * Render Social Preview page
     */
    public function render_social_preview() {
        include SSF_PLUGIN_DIR . 'admin/views/social-preview.php';
    }
    
    /**
     * Render Keyword Tracker page
     */
    public function render_keyword_tracker() {
        include SSF_PLUGIN_DIR . 'admin/views/keyword-tracker.php';
    }
    
    /**
     * Render Content Suggestions page
     */
    public function render_content_suggestions() {
        include SSF_PLUGIN_DIR . 'admin/views/content-suggestions.php';
    }
    
    /**
     * Render WP Coding Standards page
     */
    public function render_wp_standards() {
        include SSF_PLUGIN_DIR . 'admin/views/wp-standards.php';
    }
    
    /**
     * Render Performance Profiler page
     */
    public function render_performance() {
        include SSF_PLUGIN_DIR . 'admin/views/performance.php';
    }
    
    /**
     * Render Debug Log page
     */
    public function render_debug_log() {
        include SSF_PLUGIN_DIR . 'admin/views/debug-log.php';
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'ssf_seo_metabox',
                __('Smart SEO Fixer', 'smart-seo-fixer'),
                [$this, 'render_meta_box'],
                $post_type,
                'normal',
                'high'
            );
        }
    }
    
    /**
     * Render meta box
     */
    public function render_meta_box($post) {
        wp_nonce_field('ssf_meta_box', 'ssf_meta_box_nonce');
        
        $seo_title = get_post_meta($post->ID, '_ssf_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_ssf_meta_description', true);
        $focus_keyword = get_post_meta($post->ID, '_ssf_focus_keyword', true);
        $canonical_url = get_post_meta($post->ID, '_ssf_canonical_url', true);
        $noindex = get_post_meta($post->ID, '_ssf_noindex', true);
        $nofollow = get_post_meta($post->ID, '_ssf_nofollow', true);
        $seo_score = get_post_meta($post->ID, '_ssf_seo_score', true);
        $seo_grade = get_post_meta($post->ID, '_ssf_seo_grade', true);
        
        include SSF_PLUGIN_DIR . 'admin/views/meta-box.php';
    }
    
    /**
     * Save meta box
     */
    public function save_meta_box($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['ssf_meta_box_nonce']) || !wp_verify_nonce($_POST['ssf_meta_box_nonce'], 'ssf_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save fields
        $fields = [
            '_ssf_seo_title' => 'sanitize_text_field',
            '_ssf_meta_description' => 'sanitize_textarea_field',
            '_ssf_focus_keyword' => 'sanitize_text_field',
            '_ssf_canonical_url' => 'esc_url_raw',
        ];
        
        foreach ($fields as $field => $sanitize) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, $sanitize($_POST[$field]));
            }
        }
        
        // Checkboxes
        update_post_meta($post_id, '_ssf_noindex', !empty($_POST['_ssf_noindex']) ? 1 : 0);
        update_post_meta($post_id, '_ssf_nofollow', !empty($_POST['_ssf_nofollow']) ? 1 : 0);
        
        // Allow extensions to save their own fields
        do_action('ssf_metabox_save', $post_id);
        
        // Run analysis
        $analyzer = new SSF_Analyzer();
        $analyzer->analyze_post($post_id);
    }
    
    /**
     * Add SEO score column
     */
    public function add_seo_column($columns) {
        $columns['seo_score'] = __('SEO', 'smart-seo-fixer');
        return $columns;
    }
    
    /**
     * Render SEO score column
     */
    public function render_seo_column($column, $post_id) {
        if ($column !== 'seo_score') {
            return;
        }
        
        $score = get_post_meta($post_id, '_ssf_seo_score', true);
        $grade = get_post_meta($post_id, '_ssf_seo_grade', true);
        
        if ($score !== '') {
            $class = $this->get_score_class($score);
            echo '<span class="ssf-score ssf-score-' . esc_attr($class) . '">';
            echo esc_html($score) . ' (' . esc_html($grade) . ')';
            echo '</span>';
        } else {
            echo '<span class="ssf-score ssf-score-none">—</span>';
        }
    }
    
    /**
     * Make SEO column sortable
     */
    public function sortable_seo_column($columns) {
        $columns['seo_score'] = 'seo_score';
        return $columns;
    }
    
    /**
     * Sort by SEO score
     */
    public function sort_by_seo_score($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('orderby') === 'seo_score') {
            $query->set('meta_key', '_ssf_seo_score');
            $query->set('orderby', 'meta_value_num');
        }
    }
    
    /**
     * Get score class
     */
    private function get_score_class($score) {
        if ($score >= 80) return 'good';
        if ($score >= 60) return 'ok';
        return 'poor';
    }
    
    /**
     * Auto-generate SEO meta when a post is published or updated
     * Triggered by the auto_meta setting in plugin options
     * 
     * Layer 1: Fires on every publish/update — if title/desc is still empty, AI generates it
     */
    public function auto_generate_meta($new_status, $old_status, $post) {
        // Only process published posts
        if ($new_status !== 'publish') {
            return;
        }
        
        // Check if auto_meta is enabled
        if (!Smart_SEO_Fixer::get_option('auto_meta')) {
            return;
        }
        
        // Prevent infinite loops (flag so we only run once per request)
        static $processed = [];
        if (isset($processed[$post->ID])) {
            return;
        }
        $processed[$post->ID] = true;
        
        // Check if this post type is in our managed types
        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        if (!in_array($post->post_type, $post_types)) {
            return;
        }
        
        // Skip if content is too short
        if (str_word_count(strip_tags($post->post_content)) < 20) {
            return;
        }
        
        // Check if any SEO data is actually missing — skip if all filled
        $seo_title = get_post_meta($post->ID, '_ssf_seo_title', true);
        $meta_desc = get_post_meta($post->ID, '_ssf_meta_description', true);
        $focus_keyword = get_post_meta($post->ID, '_ssf_focus_keyword', true);
        
        if (!empty($seo_title) && !empty($meta_desc) && !empty($focus_keyword)) {
            // Everything already filled — just re-analyze for score
            if (class_exists('SSF_Analyzer')) {
                $analyzer = new SSF_Analyzer();
                $analyzer->analyze_post($post->ID);
            }
            return;
        }
        
        $openai = new SSF_OpenAI();
        if (!$openai->is_configured()) {
            return;
        }
        
        // Auto-generate SEO title if empty
        if (empty($seo_title)) {
            $title = $openai->generate_title($post->post_content, $post->post_title, $focus_keyword);
            if (!is_wp_error($title) && !empty(trim($title))) {
                update_post_meta($post->ID, '_ssf_seo_title', sanitize_text_field(trim($title)));
            }
        }
        
        // Auto-generate meta description if empty
        if (empty($meta_desc)) {
            $desc = $openai->generate_meta_description($post->post_content, '', $focus_keyword);
            if (!is_wp_error($desc) && !empty(trim($desc))) {
                update_post_meta($post->ID, '_ssf_meta_description', sanitize_textarea_field(trim($desc)));
            }
        }
        
        // Auto-generate focus keyword if empty
        if (empty($focus_keyword)) {
            $keywords = $openai->suggest_keywords($post->post_content, $post->post_title);
            if (!is_wp_error($keywords) && is_array($keywords) && !empty($keywords['primary'])) {
                update_post_meta($post->ID, '_ssf_focus_keyword', sanitize_text_field($keywords['primary']));
            }
        }
        
        // Run analysis
        if (class_exists('SSF_Analyzer')) {
            $analyzer = new SSF_Analyzer();
            $analyzer->analyze_post($post->ID);
        }
    }
    
    /**
     * Auto-generate alt text for uploaded images
     * Triggered by the auto_alt_text setting in plugin options
     */
    public function auto_generate_alt_text($attachment_id) {
        // Check if auto_alt_text is enabled
        if (!Smart_SEO_Fixer::get_option('auto_alt_text')) {
            return;
        }
        
        // Only process images
        $mime = get_post_mime_type($attachment_id);
        if (strpos($mime, 'image/') !== 0) {
            return;
        }
        
        // Skip if already has alt text
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($existing_alt)) {
            return;
        }
        
        $image_url = wp_get_attachment_url($attachment_id);
        if (!$image_url) {
            return;
        }
        
        // Get the parent post for context (if attached to a post)
        $attachment = get_post($attachment_id);
        $context = '';
        if ($attachment && $attachment->post_parent) {
            $parent = get_post($attachment->post_parent);
            if ($parent) {
                $context = wp_trim_words($parent->post_content, 50);
            }
        }
        
        $openai = new SSF_OpenAI();
        if ($openai->is_configured()) {
            // Use AI to generate alt text
            $focus_keyword = '';
            if ($attachment && $attachment->post_parent) {
                $focus_keyword = get_post_meta($attachment->post_parent, '_ssf_focus_keyword', true);
            }
            
            $alt_text = $openai->generate_alt_text($image_url, $context, $focus_keyword);
            if (!is_wp_error($alt_text) && !empty(trim($alt_text))) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field(trim($alt_text)));
                return;
            }
        }
        
        // Fallback: generate from filename
        $filename = basename(get_attached_file($attachment_id));
        $alt_text = ucwords(str_replace(['-', '_'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
        // Strip numbers and clean up
        $alt_text = preg_replace('/\b\d+x\d+\b/', '', $alt_text);
        $alt_text = preg_replace('/\s+/', ' ', trim($alt_text));
        
        if (!empty($alt_text)) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
        }
    }
}

