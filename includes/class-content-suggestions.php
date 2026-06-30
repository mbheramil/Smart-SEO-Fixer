<?php
/**
 * Content Suggestions
 * 
 * AI-powered content improvement recommendations for individual posts.
 * Analyzes structure, SEO, engagement, and provides actionable tips.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Content_Suggestions {
    
    /**
     * Generate content suggestions for a post using AI
     */
    public static function generate($post_id, $mode = 'rules') {
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('invalid_post', __('Post not found.', 'smart-seo-fixer'));
        }
        
        // Use enriched context so page-builder / location CPTs with empty
        // post_content still receive AI suggestions based on meta / excerpt / image alts.
        $enriched = class_exists('SSF_Job_Queue') ? SSF_Job_Queue::enrich_post_context($post) : (string) $post->post_content;
        $content = wp_strip_all_tags(strip_shortcodes($enriched));
        $word_count = str_word_count($content);
        
        if ($word_count < 20) {
            return [
                'suggestions' => [
                    ['category' => 'content', 'priority' => 'high', 'title' => __('Content too short', 'smart-seo-fixer'), 'description' => __('This post has very little text content. Add at least 300 words for basic SEO.', 'smart-seo-fixer')],
                ],
                'score' => 10,
                'source' => 'rules',
                'has_ai' => false,
            ];
        }
        
        $all = [];
        $source = 'rules';
        
        if ($mode === 'ai') {
            if (!class_exists('SSF_AI')) {
                return new \WP_Error('no_ai', __('AI module not available.', 'smart-seo-fixer'));
            }
            $openai = SSF_AI::get();
            if (!$openai->is_configured()) {
                return new \WP_Error('no_credentials', SSF_AI::not_configured_message());
            }
            $all = self::ai_suggestions($post, $content);
            $source = 'ai';
            if (empty($all)) {
                return new \WP_Error('ai_empty', __('AI analysis returned no suggestions for this content.', 'smart-seo-fixer'));
            }
        } else {
            // Rules mode (fast, instant)
            $all = self::rule_based_suggestions($post);
        }
        
        usort($all, function($a, $b) {
            $order = ['high' => 0, 'medium' => 1, 'low' => 2];
            return ($order[$a['priority']] ?? 2) - ($order[$b['priority']] ?? 2);
        });
        
        $high = count(array_filter($all, function($s) { return $s['priority'] === 'high'; }));
        $medium = count(array_filter($all, function($s) { return $s['priority'] === 'medium'; }));
        $score = max(0, 100 - ($high * 15) - ($medium * 8));
        
        // Check if AI is available for the "enhance" button
        $has_ai = false;
        if ($mode === 'rules' && class_exists('SSF_AI')) {
            $openai = SSF_AI::get();
            $has_ai = $openai->is_configured();
        }
        
        return [
            'suggestions' => $all,
            'score'       => $score,
            'source'      => $source,
            'count'       => count($all),
            'has_ai'      => $has_ai,
        ];
    }
    
    /**
     * Rule-based suggestions (no AI needed)
     */
    private static function rule_based_suggestions($post) {
        $suggestions = [];
        $content = $post->post_content;
        $text = wp_strip_all_tags(strip_shortcodes($content));
        $word_count = str_word_count($text);
        $post_id = $post->ID;
        
        // 1. Word count
        if ($word_count < 300) {
            $suggestions[] = [
                'category' => 'content',
                'priority' => 'high',
                'title'    => __('Very short content', 'smart-seo-fixer'),
                'description' => sprintf(__('Only %d words. Google prefers 300+ words minimum for indexing. Aim for 600-1500 for competitive topics.', 'smart-seo-fixer'), $word_count),
            ];
        } elseif ($word_count < 600) {
            $suggestions[] = [
                'category' => 'content',
                'priority' => 'medium',
                'title'    => __('Content could be longer', 'smart-seo-fixer'),
                'description' => sprintf(__('%d words. Consider expanding to 600-1500 words for better ranking potential.', 'smart-seo-fixer'), $word_count),
            ];
        }
        
        // 2. Heading structure
        $h2_count = preg_match_all('/<h2[\s>]/i', $content);
        $h3_count = preg_match_all('/<h3[\s>]/i', $content);
        
        if ($h2_count === 0 && $word_count > 300) {
            $suggestions[] = [
                'category' => 'structure',
                'priority' => 'high',
                'title'    => __('No H2 headings found', 'smart-seo-fixer'),
                'description' => __('Break your content into sections with H2 headings. This helps both readers and search engines understand your content.', 'smart-seo-fixer'),
            ];
        } elseif ($h2_count === 1 && $word_count > 800) {
            $suggestions[] = [
                'category' => 'structure',
                'priority' => 'medium',
                'title'    => __('Add more H2 headings', 'smart-seo-fixer'),
                'description' => __('For longer content, use multiple H2 headings (one every 200-300 words) to improve scannability.', 'smart-seo-fixer'),
            ];
        }
        
        // 3. Images
        $img_count = preg_match_all('/<img[\s]/i', $content);
        if ($img_count === 0 && $word_count > 200) {
            $suggestions[] = [
                'category' => 'media',
                'priority' => 'medium',
                'title'    => __('No images in content', 'smart-seo-fixer'),
                'description' => __('Add at least one relevant image. Posts with images get 94% more views and rank better in search.', 'smart-seo-fixer'),
            ];
        }
        
        // 4. Images without alt text
        if ($img_count > 0) {
            $imgs_no_alt = preg_match_all('/<img(?![^>]*alt=["\'][^"\']+["\'])[^>]*>/i', $content);
            if ($imgs_no_alt > 0) {
                $suggestions[] = [
                    'category' => 'seo',
                    'priority' => 'medium',
                    'title'    => sprintf(__('%d image(s) missing alt text', 'smart-seo-fixer'), $imgs_no_alt),
                    'description' => __('Add descriptive alt text to all images for accessibility and image SEO.', 'smart-seo-fixer'),
                ];
            }
        }
        
        // 5. Internal links
        $site_url = home_url();
        $internal_links = 0;
        if (preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $content, $link_matches)) {
            foreach ($link_matches[1] as $href) {
                if (strpos($href, $site_url) === 0 || (strpos($href, '/') === 0 && strpos($href, '//') !== 0)) {
                    $internal_links++;
                }
            }
        }
        
        if ($internal_links === 0 && $word_count > 200) {
            $suggestions[] = [
                'category' => 'seo',
                'priority' => 'high',
                'title'    => __('No internal links', 'smart-seo-fixer'),
                'description' => __('Add 2-3 internal links to related posts. Internal linking helps search engines discover and rank your content.', 'smart-seo-fixer'),
            ];
        }
        
        // 6. External links
        $external_links = 0;
        if (!empty($link_matches[1])) {
            foreach ($link_matches[1] as $href) {
                if (strpos($href, 'http') === 0 && strpos($href, $site_url) !== 0) {
                    $external_links++;
                }
            }
        }
        
        if ($external_links === 0 && $word_count > 500) {
            $suggestions[] = [
                'category' => 'seo',
                'priority' => 'low',
                'title'    => __('No external links', 'smart-seo-fixer'),
                'description' => __('Consider linking to 1-2 authoritative external sources. It signals credibility to search engines.', 'smart-seo-fixer'),
            ];
        }
        
        // 7. Focus keyword usage
        $focus_keyword = get_post_meta($post_id, '_ssf_focus_keyword', true);
        if (!empty($focus_keyword)) {
            $kw_lower = strtolower($focus_keyword);
            $text_lower = strtolower($text);
            $kw_count = substr_count($text_lower, $kw_lower);
            $density = ($word_count > 0) ? round(($kw_count / $word_count) * 100, 1) : 0;
            
            if ($kw_count === 0) {
                $suggestions[] = [
                    'category' => 'seo',
                    'priority' => 'high',
                    'title'    => __('Focus keyword not found in content', 'smart-seo-fixer'),
                    'description' => sprintf(__('Your focus keyword "%s" doesn\'t appear in the content. Use it naturally 2-5 times.', 'smart-seo-fixer'), $focus_keyword),
                ];
            } elseif ($density > 3) {
                $suggestions[] = [
                    'category' => 'seo',
                    'priority' => 'medium',
                    'title'    => __('Keyword density too high', 'smart-seo-fixer'),
                    'description' => sprintf(__('Keyword "%s" has %.1f%% density. Keep it under 2-3%% to avoid keyword stuffing.', 'smart-seo-fixer'), $focus_keyword, $density),
                ];
            }
            
            // Check keyword in title
            $seo_title = get_post_meta($post_id, '_ssf_seo_title', true) ?: $post->post_title;
            if (stripos($seo_title, $focus_keyword) === false) {
                $suggestions[] = [
                    'category' => 'seo',
                    'priority' => 'high',
                    'title'    => __('Focus keyword missing from SEO title', 'smart-seo-fixer'),
                    'description' => sprintf(__('Include "%s" in your SEO title for better rankings.', 'smart-seo-fixer'), $focus_keyword),
                ];
            }
            
            // Check keyword in first paragraph
            $first_para = mb_substr($text, 0, 300);
            if (stripos($first_para, $focus_keyword) === false) {
                $suggestions[] = [
                    'category' => 'seo',
                    'priority' => 'medium',
                    'title'    => __('Keyword not in first paragraph', 'smart-seo-fixer'),
                    'description' => __('Use your focus keyword in the first 100-150 words for stronger SEO signals.', 'smart-seo-fixer'),
                ];
            }
        } else {
            $suggestions[] = [
                'category' => 'seo',
                'priority' => 'medium',
                'title'    => __('No focus keyword set', 'smart-seo-fixer'),
                'description' => __('Set a focus keyword to get targeted SEO suggestions.', 'smart-seo-fixer'),
            ];
        }
        
        // 8. Meta description
        $meta_desc = get_post_meta($post_id, '_ssf_meta_description', true);
        if (empty($meta_desc)) {
            $suggestions[] = [
                'category' => 'seo',
                'priority' => 'high',
                'title'    => __('Missing meta description', 'smart-seo-fixer'),
                'description' => __('Write a compelling 150-160 character meta description that includes your keyword and a call to action.', 'smart-seo-fixer'),
            ];
        }
        
        // 9. Featured image
        if (!has_post_thumbnail($post_id)) {
            $suggestions[] = [
                'category' => 'media',
                'priority' => 'medium',
                'title'    => __('No featured image', 'smart-seo-fixer'),
                'description' => __('Add a featured image. It\'s used in social sharing, Google Discover, and archive pages.', 'smart-seo-fixer'),
            ];
        }
        
        // 10. Lists/bullet points
        $has_lists = preg_match('/<[ou]l[\s>]/i', $content);
        if (!$has_lists && $word_count > 500) {
            $suggestions[] = [
                'category' => 'engagement',
                'priority' => 'low',
                'title'    => __('No lists found', 'smart-seo-fixer'),
                'description' => __('Use bullet points or numbered lists to improve scannability and potentially earn featured snippets.', 'smart-seo-fixer'),
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * AI-powered suggestions (requires OpenAI)
     */
    private static function ai_suggestions($post, $text) {
        $excerpt       = mb_substr($text, 0, 2500);
        $word_count    = str_word_count($text);
        $focus_keyword = get_post_meta($post->ID, '_ssf_focus_keyword', true);

        // Keyword-ranking-focused analysis: tell the user exactly what to ADD or
        // change so this page can rank for its target keyword.
        $prompt  = "You are an SEO content strategist. Analyze whether this page can rank for its target keyword, ";
        $prompt .= "and list the SPECIFIC things to ADD or CHANGE so it ranks higher.\n\n";
        $prompt .= 'Target keyword: ' . (!empty($focus_keyword) ? $focus_keyword : '(none set)') . "\n";
        $prompt .= 'Current title: ' . $post->post_title . "\n";
        $prompt .= 'Current length: ' . $word_count . " words\n\n";
        $prompt .= "Content:\n" . $excerpt . "\n\n";
        $prompt .= "Provide 4-7 concrete, actionable items covering, where relevant:\n";
        $prompt .= "- topical: subtopics/sections a top-ranking page for this keyword would cover that are missing here\n";
        $prompt .= "- keyword: how/where to use the target keyword and closely related terms that fit the content\n";
        $prompt .= "- engagement: real questions searchers ask (People Also Ask style) this page should answer\n";
        $prompt .= "- links: internal-link opportunities (what kinds of related pages to link to/from)\n";
        $prompt .= "- content: depth, length, or structure gaps vs a competitive page\n\n";
        $prompt .= "RULES: Base every suggestion strictly on the actual content and target keyword above. ";
        $prompt .= "Do NOT invent facts, statistics, prices, or claims about the business. ";
        $prompt .= "If no target keyword is set, recommend one specific keyword phrase that already appears in the content.\n\n";
        $prompt .= "Return ONLY a JSON array; each item: {category: topical|keyword|engagement|links|content, priority: high|medium|low, title: short label, description: 1-2 sentence concrete instruction}.";

        $openai = SSF_AI::get();
        $response = $openai->request([
            ['role' => 'system', 'content' => 'You are an expert SEO content strategist. You ground every recommendation in the supplied content and never invent facts. Return valid JSON only.'],
            ['role' => 'user', 'content' => $prompt],
        ], 800, 0.4);
        
        if (is_wp_error($response)) {
            if (class_exists('SSF_Logger')) {
                SSF_Logger::warning('Content suggestions AI failed: ' . $response->get_error_message(), 'ai');
            }
            return [];
        }
        
        $text_response = is_array($response) && isset($response['content']) ? $response['content'] : (is_string($response) ? $response : '');
        
        // Extract JSON from response
        $json_str = $text_response;
        if (preg_match('/\[.*\]/s', $text_response, $json_match)) {
            $json_str = $json_match[0];
        }
        
        $decoded = json_decode($json_str, true);
        if (!is_array($decoded)) {
            return [];
        }
        
        // Validate and sanitize each suggestion
        $clean = [];
        foreach ($decoded as $item) {
            if (empty($item['title']) || empty($item['description'])) continue;
            
            $clean[] = [
                'category'    => in_array($item['category'] ?? '', ['content', 'engagement', 'topical', 'seo', 'keyword', 'links']) ? $item['category'] : 'content',
                'priority'    => in_array($item['priority'] ?? '', ['high', 'medium', 'low']) ? $item['priority'] : 'medium',
                'title'       => sanitize_text_field(mb_substr($item['title'], 0, 200)),
                'description' => sanitize_text_field(mb_substr($item['description'], 0, 500)),
            ];
        }
        
        return $clean;
    }
}
