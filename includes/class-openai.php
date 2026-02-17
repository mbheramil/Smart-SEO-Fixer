<?php
/**
 * OpenAI Integration Class
 * 
 * Handles all AI-powered SEO suggestions and content generation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_OpenAI {
    
    /**
     * API endpoint
     */
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    
    /**
     * Get API key
     */
    private function get_api_key() {
        return Smart_SEO_Fixer::get_option('openai_api_key');
    }
    
    /**
     * Get model
     */
    private function get_model() {
        return Smart_SEO_Fixer::get_option('openai_model', 'gpt-4o-mini');
    }
    
    /**
     * Check if API is configured
     */
    public function is_configured() {
        return !empty($this->get_api_key());
    }
    
    /**
     * Make API request
     */
    public function request($messages, $max_tokens = 500, $temperature = 0.7) {
        $api_key = $this->get_api_key();
        
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('OpenAI API key not configured.', 'smart-seo-fixer'));
        }
        
        $response = wp_remote_post($this->api_url, [
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'model' => $this->get_model(),
                'messages' => $messages,
                'max_tokens' => $max_tokens,
                'temperature' => $temperature,
            ]),
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']['message']);
        }
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
        
        return new WP_Error('invalid_response', __('Invalid response from OpenAI.', 'smart-seo-fixer'));
    }
    
    /**
     * Generate SEO title
     */
    public function generate_title($content, $current_title = '', $focus_keyword = '') {
        $prompt = "You are an SEO expert. Generate an optimized SEO title for the following content.\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Maximum 60 characters (critical for Google display)\n";
        $prompt .= "- Include the focus keyword naturally if provided\n";
        $prompt .= "- Make it compelling and click-worthy\n";
        $prompt .= "- Avoid clickbait, be accurate to the content\n\n";
        
        if (!empty($focus_keyword)) {
            $prompt .= "Focus Keyword: {$focus_keyword}\n\n";
        }
        
        if (!empty($current_title)) {
            $prompt .= "Current Title: {$current_title}\n\n";
        }
        
        $clean_content = wp_trim_words(wp_strip_all_tags(strip_shortcodes($content)), 500);
        $prompt .= "Content:\n" . $clean_content . "\n\n";
        $prompt .= "Respond with ONLY the optimized title, nothing else.";
        
        $messages = [
            ['role' => 'system', 'content' => 'You are an SEO expert that generates concise, optimized titles.'],
            ['role' => 'user', 'content' => $prompt],
        ];
        
        $result = $this->request($messages, 100, 0.7);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Strip surrounding quotes that AI sometimes adds
        $result = trim($result);
        $result = trim($result, '"\'');
        
        return $result;
    }
    
    /**
     * Generate meta description
     */
    public function generate_meta_description($content, $current_description = '', $focus_keyword = '') {
        $prompt = "You are an SEO expert. Generate an optimized meta description for the following content.\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Between 150-160 characters (critical for Google display)\n";
        $prompt .= "- Include the focus keyword naturally if provided\n";
        $prompt .= "- Include a subtle call-to-action\n";
        $prompt .= "- Accurately summarize the content\n";
        $prompt .= "- Make it compelling to increase click-through rate\n\n";
        
        if (!empty($focus_keyword)) {
            $prompt .= "Focus Keyword: {$focus_keyword}\n\n";
        }
        
        if (!empty($current_description)) {
            $prompt .= "Current Description: {$current_description}\n\n";
        }
        
        $clean_content = wp_trim_words(wp_strip_all_tags(strip_shortcodes($content)), 500);
        $prompt .= "Content:\n" . $clean_content . "\n\n";
        $prompt .= "Respond with ONLY the meta description, nothing else.";
        
        $messages = [
            ['role' => 'system', 'content' => 'You are an SEO expert that generates concise, compelling meta descriptions.'],
            ['role' => 'user', 'content' => $prompt],
        ];
        
        $result = $this->request($messages, 200, 0.7);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Strip surrounding quotes that AI sometimes adds
        $result = trim($result);
        $result = trim($result, '"\'');
        
        return $result;
    }
    
    /**
     * Generate image alt text
     */
    public function generate_alt_text($image_url, $page_context = '', $focus_keyword = '') {
        $prompt = "Generate descriptive, SEO-friendly alt text for an image.\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Maximum 125 characters\n";
        $prompt .= "- Be descriptive and accurate\n";
        $prompt .= "- Include the keyword naturally if relevant\n";
        $prompt .= "- Don't start with 'Image of' or 'Picture of'\n";
        $prompt .= "- Make it useful for screen readers\n\n";
        
        $prompt .= "Image URL: {$image_url}\n";
        
        if (!empty($page_context)) {
            $prompt .= "Page Context: " . wp_trim_words($page_context, 100) . "\n";
        }
        
        if (!empty($focus_keyword)) {
            $prompt .= "Focus Keyword: {$focus_keyword}\n";
        }
        
        $prompt .= "\nRespond with ONLY the alt text, nothing else.";
        
        $messages = [
            ['role' => 'system', 'content' => 'You are an SEO expert that generates accessible, descriptive image alt text.'],
            ['role' => 'user', 'content' => $prompt],
        ];
        
        $result = $this->request($messages, 100, 0.7);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Strip surrounding quotes that AI sometimes adds
        $result = trim($result);
        $result = trim($result, '"\'');
        
        return $result;
    }
    
    /**
     * Analyze content and suggest improvements
     */
    public function analyze_content($content, $title = '', $focus_keyword = '') {
        $prompt = "You are an SEO expert. Analyze this content and provide specific, actionable SEO recommendations.\n\n";
        
        if (!empty($title)) {
            $prompt .= "Title: {$title}\n";
        }
        
        if (!empty($focus_keyword)) {
            $prompt .= "Focus Keyword: {$focus_keyword}\n";
        }
        
        $clean_content = wp_trim_words(wp_strip_all_tags(strip_shortcodes($content)), 1000);
        $prompt .= "\nContent:\n" . $clean_content . "\n\n";
        
        $prompt .= "Analyze and provide feedback on:\n";
        $prompt .= "1. Keyword usage and placement\n";
        $prompt .= "2. Content structure (headings, paragraphs)\n";
        $prompt .= "3. Readability and engagement\n";
        $prompt .= "4. Content length and depth\n";
        $prompt .= "5. Internal/external linking opportunities\n\n";
        
        $prompt .= "Format your response as JSON with this structure:\n";
        $prompt .= '{"score": 0-100, "issues": ["issue1", "issue2"], "suggestions": ["suggestion1", "suggestion2"], "strengths": ["strength1", "strength2"]}';
        
        $messages = [
            ['role' => 'system', 'content' => 'You are an SEO expert. Respond only with valid JSON.'],
            ['role' => 'user', 'content' => $prompt],
        ];
        
        $response = $this->request($messages, 1000, 0.5);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Clean JSON response
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $response = trim($response);
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Failed to parse AI response.', 'smart-seo-fixer'));
        }
        
        return $data;
    }
    
    /**
     * Generate content outline/structure
     */
    public function generate_outline($topic, $focus_keyword = '') {
        $prompt = "You are an SEO expert. Create a comprehensive content outline for this topic.\n\n";
        $prompt .= "Topic: {$topic}\n";
        
        if (!empty($focus_keyword)) {
            $prompt .= "Focus Keyword: {$focus_keyword}\n";
        }
        
        $prompt .= "\nGenerate an SEO-optimized content outline.\n\n";
        $prompt .= "IMPORTANT: Do NOT include prefixes like 'H1:', 'H2:', 'H3:' in the text. Just write the actual heading text.\n\n";
        $prompt .= "Format as JSON:\n";
        $prompt .= '{"title": "Your Compelling Title Here", "sections": [{"heading": "First Section Heading", "subsections": ["Sub Topic One", "Sub Topic Two"]}], "suggested_word_count": 1500}';
        $prompt .= "\n\nProvide 4-6 sections, each with 2-3 subsections. No descriptions needed.";
        
        $messages = [
            ['role' => 'system', 'content' => 'You are an SEO content strategist. Respond only with valid JSON.'],
            ['role' => 'user', 'content' => $prompt],
        ];
        
        $response = $this->request($messages, 1500, 0.7);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Clean JSON response
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $response = trim($response);
        
        return json_decode($response, true);
    }
    
    /**
     * Suggest focus keywords
     */
    public function suggest_keywords($content, $title = '') {
        $prompt = "You are an SEO keyword research expert. Suggest the best focus keywords for this content.\n\n";
        
        if (!empty($title)) {
            $prompt .= "Title: {$title}\n\n";
        }
        
        $clean_content = wp_trim_words(wp_strip_all_tags(strip_shortcodes($content)), 500);
        $prompt .= "Content:\n" . $clean_content . "\n\n";
        
        $prompt .= "Suggest 5 focus keywords with:\n";
        $prompt .= "- Primary keyword (main focus)\n";
        $prompt .= "- Secondary keywords (supporting)\n";
        $prompt .= "- Long-tail variations\n\n";
        
        $prompt .= "Format as JSON:\n";
        $prompt .= '{"primary": "keyword", "secondary": ["kw1", "kw2"], "long_tail": ["phrase1", "phrase2"]}';
        
        $messages = [
            ['role' => 'system', 'content' => 'You are an SEO keyword expert. Respond only with valid JSON.'],
            ['role' => 'user', 'content' => $prompt],
        ];
        
        $response = $this->request($messages, 500, 0.6);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Clean JSON response
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $response = trim($response);
        
        return json_decode($response, true);
    }
    
    /**
     * Generate schema markup suggestions
     */
    public function suggest_schema($content, $post_type = 'article', $post_url = '', $site_url = '', $post_title = '', $site_name = '', $logo_url = '') {
        $prompt = "You are a structured data expert. Suggest additional Schema.org markup for this content.\n\n";
        $prompt .= "=== REAL SITE DATA (use these exact values, do NOT make up alternatives) ===\n";
        if (!empty($site_name)) {
            $prompt .= "Organization Name: {$site_name}\n";
        }
        if (!empty($site_url)) {
            $prompt .= "Site URL: {$site_url}\n";
        }
        if (!empty($logo_url)) {
            $prompt .= "Logo URL: {$logo_url}\n";
        }
        if (!empty($post_url)) {
            $prompt .= "Post URL: {$post_url}\n";
        }
        if (!empty($post_title)) {
            $prompt .= "Title: {$post_title}\n";
        }
        $prompt .= "Content Type: {$post_type}\n";
        $prompt .= "=== END SITE DATA ===\n\n";
        $clean_content = wp_trim_words(wp_strip_all_tags(strip_shortcodes($content)), 300);
        $prompt .= "Content:\n" . $clean_content . "\n\n";
        
        $prompt .= "CRITICAL RULES:\n";
        $prompt .= "1. Do NOT suggest Article, BlogPosting, NewsArticle, WebPage, BreadcrumbList, Organization, or WebSite schemas — these are ALREADY generated automatically by the plugin.\n";
        $prompt .= "2. Only suggest schemas that add NEW value. Good examples: Review, Product, Service, Recipe, Event, FAQPage, HowTo, Person, VideoObject, Course, SoftwareApplication, MedicalEntity, LegalService.\n";
        $prompt .= "3. Use ONLY the real URLs provided in the SITE DATA section above. NEVER guess or fabricate URLs like example.com, /logo.png, or any URL not explicitly provided.\n";
        $prompt .= "4. For any logo or image property, use ONLY the Logo URL from SITE DATA. If no logo URL was provided, omit the logo property entirely.\n";
        $prompt .= "5. Do NOT include articleBody — it's unnecessary when the full content is on the page.\n";
        $prompt .= "6. If the content is a client review/testimonial, use a Review schema.\n";
        $prompt .= "7. If there is truly no additional schema that would add value beyond Article, respond with exactly: {\"_no_schema\": true}\n\n";
        $prompt .= "Format as valid JSON-LD with @context and @type.";
        
        $messages = [
            ['role' => 'system', 'content' => 'You are a Schema.org expert. Respond only with valid JSON-LD. Never use placeholder or example.com URLs.'],
            ['role' => 'user', 'content' => $prompt],
        ];
        
        return $this->request($messages, 1000, 0.5);
    }
    
    /**
     * Improve readability of content
     */
    public function improve_readability($content) {
        $prompt = "You are a content editor. Improve the readability of this content while maintaining SEO value.\n\n";
        $prompt .= "Content:\n{$content}\n\n";
        
        $prompt .= "Improvements to make:\n";
        $prompt .= "- Shorter sentences where appropriate\n";
        $prompt .= "- Active voice\n";
        $prompt .= "- Clearer structure\n";
        $prompt .= "- Better transitions\n";
        $prompt .= "- Maintain all keywords and SEO elements\n\n";
        
        $prompt .= "Return ONLY the improved content, nothing else.";
        
        $messages = [
            ['role' => 'system', 'content' => 'You are an expert content editor focused on readability.'],
            ['role' => 'user', 'content' => $prompt],
        ];
        
        return $this->request($messages, 2000, 0.6);
    }
}

