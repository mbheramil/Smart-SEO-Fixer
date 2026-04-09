<?php
/**
 * Client Report Generator
 *
 * Generates SEO reports for client presentations.
 * Supports two modes: 'positive' (highlights only) and 'full' (everything including issues).
 * Admin-only access. Supports HTML preview, print, and PDF export.
 * Optional Google Doc template injection.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Client_Report {

    /**
     * Gather report data for the site.
     *
     * @param string $date_range 'all'|'30'|'60'|'90'|'custom'
     * @param string $start_date Y-m-d (for custom range)
     * @param string $end_date   Y-m-d (for custom range)
     * @param array  $sections   Which sections to include
     * @param string $mode       'positive' or 'full'
     * @return array
     */
    public static function generate($date_range = '30', $start_date = '', $end_date = '', $sections = [], $mode = 'positive') {
        $dates = self::resolve_dates($date_range, $start_date, $end_date);

        $default_sections = [
            'overview',
            'meta_coverage',
            'score_distribution',
            'top_pages',
            'content_health',
            'schema_coverage',
            'image_seo',
            'redirects',
            'keywords',
            'broken_links_fixed',
            'optimizations',
            'sitemap_status',
        ];

        // Full mode adds extra sections
        if ($mode === 'full') {
            $default_sections[] = 'worst_pages';
            $default_sections[] = 'issues';
        }

        if (empty($sections)) {
            $sections = $default_sections;
        }

        $data = [
            'generated_at' => current_time('mysql'),
            'site_name'    => get_bloginfo('name'),
            'site_url'     => home_url(),
            'site_tagline' => get_bloginfo('description'),
            'site_icon'    => get_site_icon_url(512),
            'date_range'   => $dates,
            'mode'         => $mode,
            'sections'     => [],
        ];

        // Check for cached template
        $template = get_option('ssf_report_template', '');
        if (!empty($template)) {
            $data['template'] = $template;
        }

        $method_map = [
            'overview'           => 'get_overview',
            'meta_coverage'      => 'get_meta_coverage',
            'score_distribution' => 'get_score_distribution',
            'top_pages'          => 'get_top_pages',
            'content_health'     => 'get_content_health',
            'schema_coverage'    => 'get_schema_coverage',
            'image_seo'          => 'get_image_seo',
            'redirects'          => 'get_redirect_stats',
            'keywords'           => 'get_keyword_highlights',
            'broken_links_fixed' => 'get_broken_links_fixed',
            'optimizations'      => 'get_optimization_count',
            'sitemap_status'     => 'get_sitemap_status',
            'worst_pages'        => 'get_worst_pages',
            'issues'             => 'get_issues',
        ];

        foreach ($sections as $section) {
            if (!isset($method_map[$section])) {
                continue;
            }
            $method = $method_map[$section];
            $needs_dates = in_array($method, ['get_overview', 'get_keyword_highlights', 'get_optimization_count']);
            $needs_mode  = in_array($method, ['get_overview', 'get_score_distribution', 'get_broken_links_fixed', 'get_meta_coverage', 'get_image_seo']);

            if ($needs_dates && $needs_mode) {
                $result = self::$method($dates, $mode);
            } elseif ($needs_dates) {
                $result = self::$method($dates);
            } elseif ($needs_mode) {
                $result = self::$method($mode);
            } else {
                $result = self::$method();
            }

            // In positive mode, hide empty sections. In full mode, show everything.
            if ($mode === 'positive' && !self::section_has_value($section, $result)) {
                continue;
            }
            $data['sections'][$section] = $result;
        }

        return $data;
    }

    /**
     * Check if a section has meaningful data worth showing.
     */
    private static function section_has_value($section, $result) {
        if (empty($result)) {
            return false;
        }

        switch ($section) {
            case 'overview':
                return ($result['total_analyzed'] ?? 0) > 0;

            case 'meta_coverage':
                return ($result['with_title'] ?? 0) > 0 || ($result['with_description'] ?? 0) > 0;

            case 'score_distribution':
                return !empty($result) && is_array($result);

            case 'top_pages':
                return !empty($result) && is_array($result);

            case 'content_health':
                return ($result['total_analyzed'] ?? 0) > 0;

            case 'schema_coverage':
                return ($result['custom_schema'] ?? 0) > 0 || !empty($result['auto_schema_types']);

            case 'image_seo':
                return ($result['total_images'] ?? 0) > 0;

            case 'redirects':
                return ($result['total_active'] ?? 0) > 0;

            case 'keywords':
                return ($result['total_tracked'] ?? 0) > 0 && !empty($result['top_keywords']);

            case 'broken_links_fixed':
                return ($result['fixed'] ?? 0) > 0;

            case 'optimizations':
                return ($result['total'] ?? 0) > 0;

            case 'sitemap_status':
                return !empty($result['url']);

            default:
                return true;
        }
    }

    /**
     * Resolve date range to start/end dates.
     */
    private static function resolve_dates($range, $start, $end) {
        $end_date = !empty($end) ? $end : current_time('Y-m-d');

        switch ($range) {
            case 'all':
                $start_date = '2000-01-01';
                break;
            case 'custom':
                $start_date = !empty($start) ? $start : date('Y-m-d', strtotime('-30 days'));
                break;
            default:
                $days = intval($range) > 0 ? intval($range) : 30;
                $start_date = date('Y-m-d', strtotime("-{$days} days"));
                break;
        }

        return [
            'start'      => $start_date,
            'end'        => $end_date,
            'label'      => $range === 'all'
                ? __('All Time', 'smart-seo-fixer')
                : sprintf('%s – %s', date_i18n(get_option('date_format'), strtotime($start_date)), date_i18n(get_option('date_format'), strtotime($end_date))),
        ];
    }

    /* ─────────── Section Data Gatherers ─────────── */

    /**
     * Overview: avg score, total analyzed, total published, grade, percentages.
     */
    private static function get_overview($dates, $mode = 'positive') {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_seo_scores';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return [
                'avg_score'        => 0,
                'grade'            => '',
                'total_analyzed'   => 0,
                'total_posts'      => 0,
                'good_count'       => 0,
                'ok_count'         => 0,
                'healthy_pct'      => 0,
                'analyzed_pct'     => 0,
            ];
        }

        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        $total_posts = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($placeholders)",
                ...$post_types
            )
        );

        // Use latest score per post (avoid duplicates from re-analysis)
        $stats = $wpdb->get_row(
            "SELECT COUNT(*) as total, ROUND(AVG(score), 0) as avg_score,
                    SUM(CASE WHEN score >= 80 THEN 1 ELSE 0 END) as good,
                    SUM(CASE WHEN score >= 60 AND score < 80 THEN 1 ELSE 0 END) as ok
             FROM (
                SELECT post_id, score
                FROM $table t1
                WHERE t1.id = (SELECT MAX(t2.id) FROM $table t2 WHERE t2.post_id = t1.post_id)
             ) latest"
        );

        $avg = intval($stats->avg_score ?? 0);
        $total_analyzed = intval($stats->total ?? 0);
        $good = intval($stats->good ?? 0);
        $ok = intval($stats->ok ?? 0);
        $healthy = $good + $ok;

        $result = [
            'avg_score'        => $avg,
            'grade'            => self::score_to_grade($avg),
            'grade_label'      => self::score_to_label($avg),
            'total_analyzed'   => $total_analyzed,
            'total_posts'      => intval($total_posts),
            'good_count'       => $good,
            'ok_count'         => $ok,
            'healthy_pct'      => $total_analyzed > 0 ? round(($healthy / $total_analyzed) * 100) : 0,
            'analyzed_pct'     => intval($total_posts) > 0 ? round(($total_analyzed / intval($total_posts)) * 100) : 0,
        ];

        // Full mode: add needs-work and not-analyzed counts
        if ($mode === 'full') {
            $needs_work = $wpdb->get_var(
                "SELECT COUNT(*)
                 FROM (
                    SELECT t1.post_id, t1.score
                    FROM $table t1
                    WHERE t1.id = (SELECT MAX(t2.id) FROM $table t2 WHERE t2.post_id = t1.post_id)
                 ) latest
                 WHERE score < 60"
            );
            $result['needs_work_count'] = intval($needs_work);
            $result['not_analyzed']     = max(0, intval($total_posts) - $total_analyzed);
        }

        return $result;
    }

    /**
     * Meta coverage: % of posts with title, description, focus keyword.
     */
    private static function get_meta_coverage($mode = 'positive') {
        global $wpdb;

        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($placeholders)",
                ...$post_types
            )
        );

        $total = intval($total);
        if ($total === 0) {
            return ['total' => 0, 'with_title' => 0, 'with_description' => 0, 'with_keyword' => 0];
        }

        $title_key = '_ssf_seo_title';
        $desc_key  = '_ssf_meta_description';
        $kw_key    = '_ssf_focus_keyword';

        $with_title = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_status = 'publish' AND p.post_type IN ($placeholders)
             AND pm.meta_key = %s AND pm.meta_value != ''",
            ...array_merge($post_types, [$title_key])
        )));

        $with_desc = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_status = 'publish' AND p.post_type IN ($placeholders)
             AND pm.meta_key = %s AND pm.meta_value != ''",
            ...array_merge($post_types, [$desc_key])
        )));

        $with_kw = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_status = 'publish' AND p.post_type IN ($placeholders)
             AND pm.meta_key = %s AND pm.meta_value != ''",
            ...array_merge($post_types, [$kw_key])
        )));

        $result = [
            'total'               => $total,
            'with_title'          => $with_title,
            'with_description'    => $with_desc,
            'with_keyword'        => $with_kw,
            'title_pct'           => round(($with_title / $total) * 100),
            'description_pct'     => round(($with_desc / $total) * 100),
            'keyword_pct'         => round(($with_kw / $total) * 100),
        ];

        // Full mode: add missing counts
        if ($mode === 'full') {
            $result['missing_title']       = $total - $with_title;
            $result['missing_description'] = $total - $with_desc;
            $result['missing_keyword']     = $total - $with_kw;
        }

        return $result;
    }

    /**
     * Score distribution — positive mode skips needs-work, full mode includes all.
     */
    private static function get_score_distribution($mode = 'positive') {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_seo_scores';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return [];
        }

        $rows = $wpdb->get_results(
            "SELECT
                CASE
                    WHEN score >= 90 THEN 'excellent'
                    WHEN score >= 80 THEN 'good'
                    WHEN score >= 70 THEN 'fair'
                    WHEN score >= 60 THEN 'ok'
                    ELSE 'skip'
                END as bucket,
                COUNT(*) as cnt
             FROM (
                SELECT post_id, score
                FROM $table t1
                WHERE t1.id = (SELECT MAX(t2.id) FROM $table t2 WHERE t2.post_id = t1.post_id)
             ) latest
             GROUP BY bucket
             ORDER BY FIELD(bucket, 'excellent', 'good', 'fair', 'ok', 'skip')"
        );

        $dist = [];
        $total_count = 0;
        foreach ($rows as $row) {
            if ($row->bucket === 'skip' && $mode === 'positive') {
                continue;
            }
            $cnt = intval($row->cnt);
            $total_count += $cnt;
            $label = $row->bucket === 'skip' ? 'Needs Work' : ucfirst($row->bucket);
            $dist[] = [
                'label'  => $label,
                'count'  => $cnt,
                'bucket' => $row->bucket,
            ];
        }

        // Add percentage
        foreach ($dist as &$item) {
            $item['pct'] = $total_count > 0 ? round(($item['count'] / $total_count) * 100) : 0;
        }

        return $dist;
    }

    /**
     * Top 20 pages by SEO score (only score >= 70).
     * Uses latest score per post to avoid duplicates.
     */
    private static function get_top_pages() {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_seo_scores';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return [];
        }

        $rows = $wpdb->get_results(
            "SELECT latest.post_id, latest.score, p.post_title, p.post_type
             FROM (
                SELECT t1.post_id, t1.score
                FROM $table t1
                WHERE t1.id = (SELECT MAX(t2.id) FROM $table t2 WHERE t2.post_id = t1.post_id)
             ) latest
             INNER JOIN {$wpdb->posts} p ON latest.post_id = p.ID
             WHERE latest.score >= 70 AND p.post_status = 'publish'
             ORDER BY latest.score DESC
             LIMIT 20"
        );

        $pages = [];
        foreach ($rows as $row) {
            $score = intval($row->score);
            $pages[] = [
                'title'     => $row->post_title,
                'url'       => get_permalink($row->post_id),
                'score'     => $score,
                'grade'     => self::score_to_grade($score),
                'post_type' => $row->post_type,
            ];
        }
        return $pages;
    }

    /**
     * Content health: average word count, readability, stats from analyzed posts.
     */
    private static function get_content_health() {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_seo_scores';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return ['total_analyzed' => 0];
        }

        $stats = $wpdb->get_row(
            "SELECT COUNT(*) as total,
                    ROUND(AVG(word_count), 0) as avg_words,
                    ROUND(AVG(flesch_score), 0) as avg_readability,
                    ROUND(AVG(image_count), 1) as avg_images,
                    ROUND(AVG(link_count), 1) as avg_links,
                    SUM(word_count) as total_words
             FROM (
                SELECT t1.post_id, t1.word_count, t1.flesch_score, t1.image_count, t1.link_count
                FROM $table t1
                WHERE t1.id = (SELECT MAX(t2.id) FROM $table t2 WHERE t2.post_id = t1.post_id)
             ) latest
             WHERE word_count > 0"
        );

        $avg_read = intval($stats->avg_readability ?? 0);

        return [
            'total_analyzed'   => intval($stats->total ?? 0),
            'avg_word_count'   => intval($stats->avg_words ?? 0),
            'total_words'      => intval($stats->total_words ?? 0),
            'avg_readability'  => $avg_read,
            'readability_label'=> self::readability_label($avg_read),
            'avg_images'       => floatval($stats->avg_images ?? 0),
            'avg_links'        => floatval($stats->avg_links ?? 0),
        ];
    }

    /**
     * Image SEO: images with alt text.
     */
    private static function get_image_seo($mode = 'positive') {
        global $wpdb;

        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_content FROM {$wpdb->posts}
                 WHERE post_status = 'publish' AND post_type IN ($placeholders)
                 AND post_content LIKE '%s'",
                ...array_merge($post_types, ['%<img%'])
            )
        );

        $total_images = 0;
        $with_alt = 0;
        $posts_with_images = count($posts);

        foreach ($posts as $post) {
            if (preg_match_all('/<img\s[^>]+>/is', $post->post_content, $matches)) {
                foreach ($matches[0] as $img_tag) {
                    $total_images++;
                    if (preg_match('/alt=["\']([^"\']+)["\']/i', $img_tag, $alt_match) && !empty(trim($alt_match[1]))) {
                        $with_alt++;
                    }
                }
            }
        }

        $alt_pct = $total_images > 0 ? round(($with_alt / $total_images) * 100) : 0;

        $result = [
            'total_images'      => $total_images,
            'with_alt'          => $with_alt,
            'alt_pct'           => $alt_pct,
            'posts_with_images' => $posts_with_images,
        ];

        if ($mode === 'full') {
            $result['without_alt'] = $total_images - $with_alt;
        }

        return $result;
    }

    /**
     * Schema coverage: how many posts have schema markup.
     */
    private static function get_schema_coverage() {
        global $wpdb;

        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($placeholders)",
                ...$post_types
            )
        );

        $with_schema = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID)
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_status = 'publish'
                 AND p.post_type IN ($placeholders)
                 AND pm.meta_key = '_ssf_custom_schema'
                 AND pm.meta_value != ''
                 AND pm.meta_value != '[]'",
                ...$post_types
            )
        );

        $auto_types = [];
        if (class_exists('SSF_Schema')) {
            $auto_types = ['Article (posts)', 'WebPage (pages)', 'BreadcrumbList', 'WebSite', 'Organization'];
        }

        return [
            'total_posts'        => intval($total),
            'custom_schema'      => intval($with_schema),
            'auto_schema_types'  => $auto_types,
            'auto_coverage_note' => !empty($auto_types)
                ? sprintf(__('All %d published pages receive automatic structured data markup.', 'smart-seo-fixer'), intval($total))
                : '',
        ];
    }

    /**
     * Redirect stats.
     */
    private static function get_redirect_stats() {
        $redirects = get_option('ssf_redirects', []);
        $active = array_filter($redirects, function ($r) {
            return !empty($r['enabled']);
        });

        $auto = array_filter($active, function ($r) {
            return !empty($r['auto']);
        });

        return [
            'total_active'  => count($active),
            'auto_created'  => count($auto),
            'manual'        => count($active) - count($auto),
        ];
    }

    /**
     * Keyword highlights — top performing (positive only).
     */
    private static function get_keyword_highlights($dates) {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_keyword_tracking';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return ['top_keywords' => [], 'total_tracked' => 0];
        }

        $top = $wpdb->get_results($wpdb->prepare(
            "SELECT keyword,
                    ROUND(AVG(position), 1) as avg_position,
                    SUM(clicks) as total_clicks,
                    SUM(impressions) as total_impressions
             FROM $table
             WHERE tracked_date >= %s AND tracked_date <= %s
             GROUP BY keyword
             HAVING total_clicks > 0
             ORDER BY total_clicks DESC
             LIMIT 10",
            $dates['start'], $dates['end']
        ));

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT keyword) FROM $table WHERE tracked_date >= %s AND tracked_date <= %s",
            $dates['start'], $dates['end']
        ));

        $totals = $wpdb->get_row($wpdb->prepare(
            "SELECT SUM(clicks) as clicks, SUM(impressions) as impressions
             FROM $table
             WHERE tracked_date >= %s AND tracked_date <= %s",
            $dates['start'], $dates['end']
        ));

        $keywords = [];
        foreach ($top as $kw) {
            $keywords[] = [
                'keyword'     => $kw->keyword,
                'position'    => floatval($kw->avg_position),
                'clicks'      => intval($kw->total_clicks),
                'impressions' => intval($kw->total_impressions),
            ];
        }

        return [
            'top_keywords'      => $keywords,
            'total_tracked'     => intval($total),
            'total_clicks'      => intval($totals->clicks ?? 0),
            'total_impressions' => intval($totals->impressions ?? 0),
        ];
    }

    /**
     * Broken links fixed (and unfixed in full mode).
     */
    private static function get_broken_links_fixed($mode = 'positive') {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_broken_links';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return ['fixed' => 0];
        }

        $fixed = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE dismissed = 1"));

        $result = [
            'fixed' => $fixed,
        ];

        if ($mode === 'full') {
            $unfixed = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE dismissed = 0"));
            $result['unfixed'] = $unfixed;
            $result['total']   = $fixed + $unfixed;
        }

        return $result;
    }

    /**
     * Count of SEO optimizations made in the date range (from history).
     */
    private static function get_optimization_count($dates) {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_history';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return ['total' => 0, 'ai_generated' => 0, 'manual' => 0];
        }

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN source = 'ai' THEN 1 ELSE 0 END) as ai,
                    SUM(CASE WHEN source != 'ai' THEN 1 ELSE 0 END) as manual_count,
                    COUNT(DISTINCT post_id) as posts_optimized
             FROM $table
             WHERE created_at >= %s AND created_at <= %s
             AND reverted = 0",
            $dates['start'] . ' 00:00:00',
            $dates['end'] . ' 23:59:59'
        ));

        $breakdown = $wpdb->get_results($wpdb->prepare(
            "SELECT
                CASE
                    WHEN field_key LIKE '%%title%%' THEN 'titles'
                    WHEN field_key LIKE '%%description%%' THEN 'descriptions'
                    WHEN field_key LIKE '%%keyword%%' THEN 'keywords'
                    WHEN field_key LIKE '%%schema%%' THEN 'schema'
                    WHEN field_key LIKE '%%og_%%' THEN 'social'
                    ELSE 'other'
                END as category,
                COUNT(*) as cnt
             FROM $table
             WHERE created_at >= %s AND created_at <= %s AND reverted = 0
             GROUP BY category
             ORDER BY cnt DESC",
            $dates['start'] . ' 00:00:00',
            $dates['end'] . ' 23:59:59'
        ));

        $by_type = [];
        foreach ($breakdown as $row) {
            if ($row->category !== 'other' && intval($row->cnt) > 0) {
                $by_type[] = [
                    'category' => ucfirst($row->category),
                    'count'    => intval($row->cnt),
                ];
            }
        }

        return [
            'total'           => intval($stats->total ?? 0),
            'ai_generated'    => intval($stats->ai ?? 0),
            'manual'          => intval($stats->manual_count ?? 0),
            'posts_optimized' => intval($stats->posts_optimized ?? 0),
            'by_type'         => $by_type,
        ];
    }

    /**
     * Sitemap status.
     */
    private static function get_sitemap_status() {
        global $wpdb;

        $url = home_url('/sitemap.xml');
        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        $indexable_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$wpdb->posts} p
                 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_ssf_noindex'
                 WHERE p.post_status = 'publish'
                 AND p.post_type IN ($placeholders)
                 AND (pm.meta_value IS NULL OR pm.meta_value != '1')",
                ...$post_types
            )
        );

        return [
            'url'             => $url,
            'enabled'         => true,
            'indexable_pages'  => intval($indexable_count),
            'post_types'      => $post_types,
        ];
    }

    /* ─────────── Full-Mode Sections ─────────── */

    /**
     * Worst 20 pages by SEO score (full mode only).
     */
    private static function get_worst_pages() {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_seo_scores';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return [];
        }

        $rows = $wpdb->get_results(
            "SELECT latest.post_id, latest.score, p.post_title, p.post_type
             FROM (
                SELECT t1.post_id, t1.score
                FROM $table t1
                WHERE t1.id = (SELECT MAX(t2.id) FROM $table t2 WHERE t2.post_id = t1.post_id)
             ) latest
             INNER JOIN {$wpdb->posts} p ON latest.post_id = p.ID
             WHERE latest.score < 60 AND p.post_status = 'publish'
             ORDER BY latest.score ASC
             LIMIT 20"
        );

        $pages = [];
        foreach ($rows as $row) {
            $score = intval($row->score);
            $issues = [];
            // Check what's missing for this post
            $title = get_post_meta($row->post_id, '_ssf_seo_title', true);
            $desc  = get_post_meta($row->post_id, '_ssf_meta_description', true);
            $kw    = get_post_meta($row->post_id, '_ssf_focus_keyword', true);
            if (empty($title)) $issues[] = 'Missing SEO title';
            if (empty($desc))  $issues[] = 'Missing meta description';
            if (empty($kw))    $issues[] = 'No focus keyword';

            $pages[] = [
                'title'     => $row->post_title,
                'url'       => get_permalink($row->post_id),
                'score'     => $score,
                'grade'     => self::score_to_grade($score),
                'post_type' => $row->post_type,
                'issues'    => $issues,
            ];
        }
        return $pages;
    }

    /**
     * Aggregate issues and recommendations (full mode only).
     */
    private static function get_issues() {
        global $wpdb;
        $issues = [];

        // 1. Check for pages without SEO titles
        $post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        $total = intval($wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($placeholders)",
                ...$post_types
            )
        ));

        $without_title = $total - intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_status = 'publish' AND p.post_type IN ($placeholders)
             AND pm.meta_key = '_ssf_seo_title' AND pm.meta_value != ''",
            ...$post_types
        )));

        $without_desc = $total - intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_status = 'publish' AND p.post_type IN ($placeholders)
             AND pm.meta_key = '_ssf_meta_description' AND pm.meta_value != ''",
            ...$post_types
        )));

        if ($without_title > 0) {
            $issues[] = [
                'severity' => 'warning',
                'title'    => sprintf(__('%d pages missing SEO titles', 'smart-seo-fixer'), $without_title),
                'detail'   => __('Custom SEO titles help search engines understand your content. Use AI Bulk Fix to generate them quickly.', 'smart-seo-fixer'),
            ];
        }

        if ($without_desc > 0) {
            $issues[] = [
                'severity' => 'warning',
                'title'    => sprintf(__('%d pages missing meta descriptions', 'smart-seo-fixer'), $without_desc),
                'detail'   => __('Meta descriptions appear in search results and improve click-through rates.', 'smart-seo-fixer'),
            ];
        }

        // 2. Check for unfixed broken links
        $bl_table = $wpdb->prefix . 'ssf_broken_links';
        if ($wpdb->get_var("SHOW TABLES LIKE '$bl_table'") === $bl_table) {
            $unfixed = intval($wpdb->get_var("SELECT COUNT(*) FROM $bl_table WHERE dismissed = 0"));
            if ($unfixed > 0) {
                $issues[] = [
                    'severity' => 'error',
                    'title'    => sprintf(__('%d broken links need attention', 'smart-seo-fixer'), $unfixed),
                    'detail'   => __('Broken links hurt user experience and search rankings. Review and fix them in the Broken Links page.', 'smart-seo-fixer'),
                ];
            }
        }

        // 3. Check for low-scoring pages
        $scores_table = $wpdb->prefix . 'ssf_seo_scores';
        if ($wpdb->get_var("SHOW TABLES LIKE '$scores_table'") === $scores_table) {
            $low_score = intval($wpdb->get_var(
                "SELECT COUNT(*) FROM (
                    SELECT t1.post_id, t1.score
                    FROM $scores_table t1
                    WHERE t1.id = (SELECT MAX(t2.id) FROM $scores_table t2 WHERE t2.post_id = t1.post_id)
                 ) latest WHERE score < 40"
            ));
            if ($low_score > 0) {
                $issues[] = [
                    'severity' => 'error',
                    'title'    => sprintf(__('%d pages scoring below 40', 'smart-seo-fixer'), $low_score),
                    'detail'   => __('These pages have significant SEO issues. Prioritize them for optimization.', 'smart-seo-fixer'),
                ];
            }

            // 4. Not analyzed pages
            $analyzed = intval($wpdb->get_var(
                "SELECT COUNT(DISTINCT post_id) FROM $scores_table"
            ));
            $not_analyzed = $total - $analyzed;
            if ($not_analyzed > 0) {
                $issues[] = [
                    'severity' => 'info',
                    'title'    => sprintf(__('%d pages not yet analyzed', 'smart-seo-fixer'), $not_analyzed),
                    'detail'   => __('Run a bulk analysis to get SEO scores for all your content.', 'smart-seo-fixer'),
                ];
            }
        }

        // 5. Image alt text coverage
        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_content FROM {$wpdb->posts}
                 WHERE post_status = 'publish' AND post_type IN ($placeholders)
                 AND post_content LIKE '%s'",
                ...array_merge($post_types, ['%<img%'])
            )
        );
        $imgs_without_alt = 0;
        foreach ($posts as $post) {
            if (preg_match_all('/<img\s[^>]+>/is', $post->post_content, $matches)) {
                foreach ($matches[0] as $img_tag) {
                    if (!preg_match('/alt=["\']([^"\']+)["\']/i', $img_tag, $alt_match) || empty(trim($alt_match[1]))) {
                        $imgs_without_alt++;
                    }
                }
            }
        }
        if ($imgs_without_alt > 0) {
            $issues[] = [
                'severity' => 'warning',
                'title'    => sprintf(__('%d images missing alt text', 'smart-seo-fixer'), $imgs_without_alt),
                'detail'   => __('Alt text improves accessibility and helps search engines understand your images.', 'smart-seo-fixer'),
            ];
        }

        // Sort: errors first, then warnings, then info
        $order = ['error' => 0, 'warning' => 1, 'info' => 2];
        usort($issues, function($a, $b) use ($order) {
            return ($order[$a['severity']] ?? 3) - ($order[$b['severity']] ?? 3);
        });

        return $issues;
    }

    /* ─────────── Template ─────────── */

    /**
     * Fetch a Google Doc (or any URL) as HTML and cache it.
     *
     * @param string $url The document URL.
     * @return array ['success' => bool, 'html' => string, 'message' => string]
     */
    public static function fetch_template($url) {
        // Validate URL
        $url = esc_url_raw($url);
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => __('Invalid URL.', 'smart-seo-fixer')];
        }

        // Auto-convert Google Docs edit URL to export URL
        if (preg_match('#docs\.google\.com/document/d/([a-zA-Z0-9_-]+)#', $url, $m)) {
            $doc_id = $m[1];
            $url = 'https://docs.google.com/document/d/' . $doc_id . '/export?format=html';
        }

        $response = wp_remote_get($url, [
            'timeout'    => 30,
            'user-agent' => 'Mozilla/5.0 (WordPress/' . get_bloginfo('version') . '; Smart SEO Fixer)',
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            if ($code === 401 || $code === 403) {
                return ['success' => false, 'message' => __('Access denied. Please make the document publicly viewable (Share > Anyone with the link > Viewer).', 'smart-seo-fixer')];
            }
            return ['success' => false, 'message' => sprintf(__('HTTP error %d.', 'smart-seo-fixer'), $code)];
        }

        $html = wp_remote_retrieve_body($response);
        if (empty($html) || strlen($html) < 50) {
            return ['success' => false, 'message' => __('The document appears to be empty.', 'smart-seo-fixer')];
        }

        // Extract just the <style> and <body> content
        $style = '';
        if (preg_match('/<style[^>]*>(.*?)<\/style>/si', $html, $sm)) {
            $style = '<style>' . $sm[1] . '</style>';
        }
        $body = '';
        if (preg_match('/<body[^>]*>(.*?)<\/body>/si', $html, $bm)) {
            $body = $bm[1];
        } else {
            $body = $html;
        }

        $template_html = $style . "\n" . $body;

        // Cache in options
        update_option('ssf_report_template', $template_html);
        update_option('ssf_report_template_url', $url);

        return ['success' => true, 'html' => $template_html, 'message' => __('Template loaded and cached.', 'smart-seo-fixer')];
    }

    /**
     * Clear cached template.
     */
    public static function clear_template() {
        delete_option('ssf_report_template');
        delete_option('ssf_report_template_url');
    }

    /* ─────────── Helpers ─────────── */

    private static function score_to_grade($score) {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C+';
        if ($score >= 60) return 'C';
        return 'D';
    }

    private static function score_to_label($score) {
        if ($score >= 90) return __('Excellent', 'smart-seo-fixer');
        if ($score >= 80) return __('Good', 'smart-seo-fixer');
        if ($score >= 70) return __('Fair', 'smart-seo-fixer');
        if ($score >= 60) return __('Needs Improvement', 'smart-seo-fixer');
        return __('Getting Started', 'smart-seo-fixer');
    }

    private static function readability_label($flesch) {
        if ($flesch >= 80) return __('Very Easy to Read', 'smart-seo-fixer');
        if ($flesch >= 60) return __('Easy to Read', 'smart-seo-fixer');
        if ($flesch >= 40) return __('Moderate', 'smart-seo-fixer');
        if ($flesch >= 20) return __('Somewhat Difficult', 'smart-seo-fixer');
        return '';
    }
}
