<?php
/**
 * Client Report Generator
 *
 * Generates positive-only SEO reports for client presentations.
 * Admin-only access. Supports HTML preview, print, and PDF export.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Client_Report {

    /**
     * Gather all positive report data for the site.
     *
     * @param string $date_range 'all'|'30'|'60'|'90'|'custom'
     * @param string $start_date Y-m-d (for custom range)
     * @param string $end_date   Y-m-d (for custom range)
     * @param array  $sections   Which sections to include
     * @return array
     */
    public static function generate($date_range = '30', $start_date = '', $end_date = '', $sections = []) {
        $dates = self::resolve_dates($date_range, $start_date, $end_date);

        $default_sections = [
            'overview',
            'score_distribution',
            'top_pages',
            'schema_coverage',
            'redirects',
            'keywords',
            'broken_links_fixed',
            'optimizations',
        ];

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
            'sections'     => [],
        ];

        foreach ($sections as $section) {
            switch ($section) {
                case 'overview':
                    $data['sections']['overview'] = self::get_overview($dates);
                    break;
                case 'score_distribution':
                    $data['sections']['score_distribution'] = self::get_score_distribution();
                    break;
                case 'top_pages':
                    $data['sections']['top_pages'] = self::get_top_pages();
                    break;
                case 'schema_coverage':
                    $data['sections']['schema_coverage'] = self::get_schema_coverage();
                    break;
                case 'redirects':
                    $data['sections']['redirects'] = self::get_redirect_stats();
                    break;
                case 'keywords':
                    $data['sections']['keywords'] = self::get_keyword_highlights($dates);
                    break;
                case 'broken_links_fixed':
                    $data['sections']['broken_links_fixed'] = self::get_broken_links_fixed();
                    break;
                case 'optimizations':
                    $data['sections']['optimizations'] = self::get_optimization_count($dates);
                    break;
            }
        }

        return $data;
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
     * Overview: avg score, total analyzed, total published.
     */
    private static function get_overview($dates) {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_seo_scores';

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        if (!$table_exists) {
            return [
                'avg_score'      => 0,
                'total_analyzed' => 0,
                'total_posts'    => 0,
                'good_count'     => 0,
                'ok_count'       => 0,
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

        $stats = $wpdb->get_row(
            "SELECT COUNT(*) as total, ROUND(AVG(score), 0) as avg_score,
                    SUM(CASE WHEN score >= 80 THEN 1 ELSE 0 END) as good,
                    SUM(CASE WHEN score >= 60 AND score < 80 THEN 1 ELSE 0 END) as ok
             FROM $table"
        );

        return [
            'avg_score'      => intval($stats->avg_score ?? 0),
            'total_analyzed' => intval($stats->total ?? 0),
            'total_posts'    => intval($total_posts),
            'good_count'     => intval($stats->good ?? 0),
            'ok_count'       => intval($stats->ok ?? 0),
        ];
    }

    /**
     * Score distribution — only show good & OK buckets (positive only).
     */
    private static function get_score_distribution() {
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
             FROM $table
             GROUP BY bucket
             ORDER BY FIELD(bucket, 'excellent', 'good', 'fair', 'ok', 'skip')"
        );

        $dist = [];
        foreach ($rows as $row) {
            if ($row->bucket === 'skip') {
                continue; // positive only
            }
            $dist[] = [
                'label' => ucfirst($row->bucket),
                'count' => intval($row->cnt),
            ];
        }

        return $dist;
    }

    /**
     * Top 20 pages by SEO score (only score >= 70).
     */
    private static function get_top_pages() {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_seo_scores';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return [];
        }

        $rows = $wpdb->get_results(
            "SELECT s.post_id, s.score, s.grade, p.post_title, p.post_type
             FROM $table s
             INNER JOIN {$wpdb->posts} p ON s.post_id = p.ID
             WHERE s.score >= 70 AND p.post_status = 'publish'
             ORDER BY s.score DESC
             LIMIT 20"
        );

        $pages = [];
        foreach ($rows as $row) {
            $pages[] = [
                'title'     => $row->post_title,
                'url'       => get_permalink($row->post_id),
                'score'     => intval($row->score),
                'grade'     => $row->grade,
                'post_type' => $row->post_type,
            ];
        }
        return $pages;
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

        // Posts with custom schema
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

        // Auto-schema always covers posts/pages via SSF_Schema
        $auto_types = [];
        if (class_exists('SSF_Schema')) {
            $auto_types = ['Article (posts)', 'WebPage (pages)', 'BreadcrumbList', 'WebSite', 'Organization'];
        }

        return [
            'total_posts'        => intval($total),
            'custom_schema'      => intval($with_schema),
            'auto_schema_types'  => $auto_types,
        ];
    }

    /**
     * Redirect stats — active redirects protecting link equity.
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
     * Keyword highlights — top improving & best performing (positive only).
     */
    private static function get_keyword_highlights($dates) {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_keyword_tracking';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return ['top_keywords' => [], 'total_tracked' => 0];
        }

        // Top 10 keywords by clicks in range
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
            'top_keywords'  => $keywords,
            'total_tracked' => intval($total),
        ];
    }

    /**
     * Broken links that have been dismissed (i.e. fixed/addressed).
     */
    private static function get_broken_links_fixed() {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_broken_links';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return ['fixed' => 0, 'remaining' => 0];
        }

        $fixed = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE dismissed = 1");
        $remaining = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE dismissed = 0");

        return [
            'fixed'     => intval($fixed),
            'remaining' => intval($remaining),
        ];
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
                    SUM(CASE WHEN source != 'ai' THEN 1 ELSE 0 END) as manual_count
             FROM $table
             WHERE created_at >= %s AND created_at <= %s
             AND reverted = 0",
            $dates['start'] . ' 00:00:00',
            $dates['end'] . ' 23:59:59'
        ));

        return [
            'total'        => intval($stats->total ?? 0),
            'ai_generated' => intval($stats->ai ?? 0),
            'manual'       => intval($stats->manual_count ?? 0),
        ];
    }
}
