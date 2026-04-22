<?php
/**
 * AI Provider Factory
 *
 * Routes all AI operations to the configured provider.
 * Supports: AWS Bedrock, OpenAI, Anthropic Claude, Google Gemini.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_AI {

    /**
     * Provider registry: key => class name.
     */
    private static $providers = [
        'bedrock' => 'SSF_Bedrock',
        'openai'  => 'SSF_OpenAI',
        'claude'  => 'SSF_Claude',
        'gemini'  => 'SSF_Gemini',
    ];

    /**
     * Return the slug of the active provider.
     *
     * @return string
     */
    public static function active_provider() {
        $provider = Smart_SEO_Fixer::get_option('ai_provider', 'bedrock');
        return isset(self::$providers[$provider]) ? $provider : 'bedrock';
    }

    /**
     * Get the currently configured AI provider instance.
     *
     * @return SSF_Bedrock|SSF_OpenAI|SSF_Claude|SSF_Gemini
     */
    public static function get() {
        $class = self::$providers[self::active_provider()];
        return new $class();
    }

    /**
     * Check whether the active AI provider is configured (has credentials).
     *
     * @return bool
     */
    public static function is_configured() {
        return self::get()->is_configured();
    }

    /**
     * Human-readable labels for each provider.
     */
    private static $labels = [
        'bedrock' => 'AWS Bedrock',
        'openai'  => 'OpenAI',
        'claude'  => 'Anthropic Claude',
        'gemini'  => 'Google Gemini',
    ];

    /**
     * Return a human-readable label for the active provider.
     *
     * @return string
     */
    public static function provider_label() {
        return self::$labels[self::active_provider()] ?? 'AI';
    }

    /**
     * Return all available providers as slug => label.
     *
     * @return array
     */
    public static function available_providers() {
        return self::$labels;
    }

    /**
     * Return the not-configured error message for the active provider.
     *
     * @return string
     */
    public static function not_configured_message() {
        $label = self::provider_label();
        return sprintf(
            __('%s credentials not configured. Please add your API key in Settings.', 'smart-seo-fixer'),
            $label
        );
    }

    /**
     * Pick a focus keyword that is "grounded" — i.e. actually appears in the
     * post title or content. Prevents the AI from inventing orphan keywords
     * that tank the SEO score because the analyzer can't find them.
     *
     * Strategy:
     *   1. Ask AI for candidates (primary + secondary + long_tail).
     *   2. Return the first candidate that is a substring of title or content.
     *   3. If none match, fall back to the most common 2-4 word phrase
     *      appearing in the title/content (simple n-gram frequency).
     *
     * @param string $content Post content (HTML or plain).
     * @param string $title   Post title.
     * @return string Focus keyword (may be empty string if nothing works).
     */
    public static function pick_grounded_keyword($content, $title = '') {
        $haystack = strtolower(wp_strip_all_tags(strip_shortcodes(
            ($title ? $title . "\n" : '') . $content
        )));

        $candidates = [];

        // Try the AI first.
        $ai = self::get();
        if (method_exists($ai, 'suggest_keywords')) {
            $resp = $ai->suggest_keywords($content, $title);
            if (!is_wp_error($resp) && is_array($resp)) {
                if (!empty($resp['primary'])) {
                    $candidates[] = (string) $resp['primary'];
                }
                if (!empty($resp['secondary']) && is_array($resp['secondary'])) {
                    foreach ($resp['secondary'] as $kw) { $candidates[] = (string) $kw; }
                }
                if (!empty($resp['long_tail']) && is_array($resp['long_tail'])) {
                    foreach ($resp['long_tail'] as $kw) { $candidates[] = (string) $kw; }
                }
            }
        }

        // Return first candidate that actually appears in the content.
        foreach ($candidates as $kw) {
            $kw = trim($kw);
            if ($kw === '') { continue; }
            if (strpos($haystack, strtolower($kw)) !== false) {
                return sanitize_text_field($kw);
            }
        }

        // Fallback: extract most common 2-3 word phrase from title + content.
        $extracted = self::extract_keyword_from_text($haystack);
        if (!empty($extracted)) {
            return sanitize_text_field($extracted);
        }

        // Last resort: return the first AI candidate even if ungrounded, so we
        // still have *something*. Better than empty.
        return !empty($candidates[0]) ? sanitize_text_field(trim($candidates[0])) : '';
    }

    /**
     * Extract the most "keyword-like" 2-3 word phrase from a body of text.
     * Used as a fallback when the AI's suggestions don't appear in the content.
     */
    private static function extract_keyword_from_text($text) {
        if (empty($text)) { return ''; }

        // Common stopwords we never want as a focus keyword.
        $stop = [
            'the','a','an','and','or','but','of','to','in','on','at','by','for',
            'with','from','as','is','are','was','were','be','been','being','have',
            'has','had','do','does','did','will','would','could','should','may',
            'might','must','can','this','that','these','those','it','its','our',
            'we','you','your','they','them','their','he','she','his','her','him',
            'i','my','me','us','if','then','than','so','not','no','yes','about',
            'into','over','under','out','up','down','all','any','some','such',
            'only','also','more','most','other','same','too','very','just','now',
            'when','where','why','how','what','who','whom','which','while','what','after','before','because','during','through','between'
        ];

        // Normalize to alphanumeric + spaces
        $text = preg_replace('/[^a-z0-9\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $words = array_values(array_filter(explode(' ', trim($text)), function($w) use ($stop) {
            return strlen($w) >= 3 && !in_array($w, $stop, true) && !is_numeric($w);
        }));

        if (count($words) < 2) {
            return !empty($words[0]) ? $words[0] : '';
        }

        // Build bigrams and trigrams, count frequency.
        $ngrams = [];
        $count  = count($words);
        for ($i = 0; $i < $count - 1; $i++) {
            $bi = $words[$i] . ' ' . $words[$i + 1];
            if (!isset($ngrams[$bi])) { $ngrams[$bi] = 0; }
            $ngrams[$bi]++;
            if ($i < $count - 2) {
                $tri = $bi . ' ' . $words[$i + 2];
                if (!isset($ngrams[$tri])) { $ngrams[$tri] = 0; }
                // Slight bias toward trigrams (more specific = better SEO keyword).
                $ngrams[$tri] += 2;
            }
        }

        if (empty($ngrams)) { return $words[0]; }

        arsort($ngrams);
        return (string) key($ngrams);
    }
}
