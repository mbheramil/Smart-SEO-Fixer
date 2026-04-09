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
        ];

        foreach ($sections as $section) {
            if (!isset($method_map[$section])) {
                continue;
            }
            $method = $method_map[$section];
            $needs_dates = in_array($method, ['get_overview', 'get_keyword_highlights', 'get_optimization_count']);
            $result = $needs_dates ? self::$method($dates) : self::$method();

            // Only include sections that have meaningful positive data
            if (self::section_has_value($section, $result)) {
                $data['sections'][$section] = $result;
            }
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
    private static function get_overview($dates) {
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

        return [
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
    }

    /**
     * Meta coverage: % of posts with title, description, focus keyword.
     */
    private static function get_meta_coverage() {
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

        return [
            'total'               => $total,
            'with_title'          => $with_title,
            'with_description'    => $with_desc,
            'with_keyword'        => $with_kw,
            'title_pct'           => round(($with_title / $total) * 100),
            'description_pct'     => round(($with_desc / $total) * 100),
            'keyword_pct'         => round(($with_kw / $total) * 100),
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
             FROM (
                SELECT post_id, score
                FROM $table t1
                WHERE t1.id = (SELECT MAX(t2.id) FROM $table t2 WHERE t2.post_id = t1.post_id)
             ) latest
             GROUP BY bucket
             ORDER BY FIELD(bucket, 'excellent', 'good', 'fair', 'ok', 'skip')"
        );

        $dist = [];
        $total_positive = 0;
        foreach ($rows as $row) {
            if ($row->bucket === 'skip') {
                continue;
            }
            $cnt = intval($row->cnt);
            $total_positive += $cnt;
            $dist[] = [
                'label' => ucfirst($row->bucket),
                'count' => $cnt,
            ];
        }

        // Add percentage
        foreach ($dist as &$item) {
            $item['pct'] = $total_positive > 0 ? round(($item['count'] / $total_positive) * 100) : 0;
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
    private static function get_image_seo() {
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

        return [
            'total_images'      => $total_images,
            'with_alt'          => $with_alt,
            'alt_pct'           => $alt_pct,
            'posts_with_images' => $posts_with_images,
        ];
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
     * Broken links fixed.
     */
    private static function get_broken_links_fixed() {
        global $wpdb;
        $table = $wpdb->prefix . 'ssf_broken_links';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return ['fixed' => 0];
        }

        $fixed = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE dismissed = 1");

        return [
            'fixed' => intval($fixed),
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
