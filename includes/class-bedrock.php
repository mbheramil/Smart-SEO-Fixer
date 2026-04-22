<?php
/**
 * AWS Bedrock AI Integration Class
 *
 * Handles all AI-powered SEO suggestions and content generation via AWS Bedrock.
 * Supports Claude (Anthropic) and Llama (Meta) model families.
 * Uses AWS Signature Version 4 (SigV4) — no SDK required.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Bedrock {

    /**
     * AWS region — constant SSF_BEDROCK_REGION in wp-config.php takes priority
     */
    private function get_region() {
        if (defined('SSF_BEDROCK_REGION') && SSF_BEDROCK_REGION !== '') {
            return SSF_BEDROCK_REGION;
        }
        return Smart_SEO_Fixer::get_option('bedrock_region', 'us-east-1');
    }

    /**
     * AWS Access Key ID — constant SSF_BEDROCK_ACCESS_KEY in wp-config.php takes priority
     */
    private function get_access_key() {
        if (defined('SSF_BEDROCK_ACCESS_KEY') && SSF_BEDROCK_ACCESS_KEY !== '') {
            return SSF_BEDROCK_ACCESS_KEY;
        }
        return Smart_SEO_Fixer::get_option('bedrock_access_key');
    }

    /**
     * AWS Secret Access Key — constant SSF_BEDROCK_SECRET_KEY in wp-config.php takes priority
     */
    private function get_secret_key() {
        if (defined('SSF_BEDROCK_SECRET_KEY') && SSF_BEDROCK_SECRET_KEY !== '') {
            return SSF_BEDROCK_SECRET_KEY;
        }
        return Smart_SEO_Fixer::get_option('bedrock_secret_key');
    }

    /**
     * Bedrock model ID — hardcoded to Claude Sonnet 4.6 cross-region inference profile
     */
    private function get_model() {
        return 'us.anthropic.claude-sonnet-4-6';
    }

    /**
     * Check if Bedrock is fully configured
     */
    public function is_configured() {
        return !empty($this->get_access_key()) && !empty($this->get_secret_key());
    }

    /**
     * Detect model family from model ID
     * Returns 'claude', 'llama', or 'titan'
     */
    private function get_model_family() {
        $model = $this->get_model();
        // Cross-region inference profiles (us.anthropic.*) and direct Anthropic models
        if (strpos($model, 'anthropic.claude') !== false) return 'claude';
        if (strpos($model, '.anthropic.claude') !== false) return 'claude'; // us./eu./ap. prefix
        if (strpos($model, 'meta.llama')       !== false) return 'llama';
        if (strpos($model, 'amazon.titan')     !== false) return 'titan';
        if (strpos($model, 'mistral.')         !== false) return 'mistral';
        return 'claude'; // safe default
    }

    /**
     * Build the Bedrock API endpoint URL for the selected model
     */
    private function get_endpoint() {
        $region = $this->get_region();
        $model  = $this->get_model();
        return "https://bedrock-runtime.{$region}.amazonaws.com/model/{$model}/invoke";
    }

    /**
     * Build the request body for the selected model family
     *
     * @param array  $messages     [['role'=>'user','content'=>'...'}, ...]
     * @param string $system       Optional system prompt
     * @param int    $max_tokens
     * @param float  $temperature
     * @return array               Body array ready for wp_json_encode
     */
    private function build_body( $messages, $system, $max_tokens, $temperature ) {
        $family = $this->get_model_family();

        if ( $family === 'claude' ) {
            $body = [
                'anthropic_version' => 'bedrock-2023-05-31',
                'max_tokens'        => $max_tokens,
                'temperature'       => $temperature,
                'messages'          => $messages,
            ];
            if ( ! empty( $system ) ) {
                $body['system'] = $system;
            }
            return $body;
        }

        if ( $family === 'llama' ) {
            // Llama 3 uses a single prompt string
            $prompt = '';
            if ( ! empty( $system ) ) {
                $prompt .= "<|begin_of_text|><|start_header_id|>system<|end_header_id|>\n{$system}<|eot_id|>";
            }
            foreach ( $messages as $msg ) {
                $role    = $msg['role'] === 'user' ? 'user' : 'assistant';
                $prompt .= "<|start_header_id|>{$role}<|end_header_id|>\n{$msg['content']}<|eot_id|>";
            }
            $prompt .= '<|start_header_id|>assistant<|end_header_id|>';
            return [
                'prompt'      => $prompt,
                'max_gen_len' => $max_tokens,
                'temperature' => $temperature,
            ];
        }

        if ( $family === 'mistral' ) {
            $prompt = '';
            if ( ! empty( $system ) ) {
                $prompt .= "[INST] <<SYS>>\n{$system}\n<</SYS>>\n\n";
            }
            $first = true;
            foreach ( $messages as $msg ) {
                if ( $msg['role'] === 'user' ) {
                    if ( $first && empty( $system ) ) {
                        $prompt .= "[INST] {$msg['content']} [/INST]";
                    } else {
                        $prompt .= "{$msg['content']} [/INST]";
                    }
                    $first = false;
                } else {
                    $prompt .= " {$msg['content']} [INST] ";
                }
            }
            return [
                'prompt'      => $prompt,
                'max_tokens'  => $max_tokens,
                'temperature' => $temperature,
            ];
        }

        // Amazon Titan fallback
        $full_prompt = '';
        if ( ! empty( $system ) ) {
            $full_prompt .= $system . "\n\n";
        }
        foreach ( $messages as $msg ) {
            $full_prompt .= ucfirst( $msg['role'] ) . ': ' . $msg['content'] . "\n";
        }
        return [
            'inputText' => $full_prompt,
            'textGenerationConfig' => [
                'maxTokenCount' => $max_tokens,
                'temperature'   => $temperature,
            ],
        ];
    }

    /**
     * Extract the text content from the Bedrock response body
     *
     * @param array  $data   Decoded JSON response
     * @return string|WP_Error
     */
    private function extract_content( $data ) {
        $family = $this->get_model_family();

        if ( $family === 'claude' ) {
            if ( isset( $data['content'][0]['text'] ) ) {
                return $data['content'][0]['text'];
            }
        }

        if ( $family === 'llama' ) {
            if ( isset( $data['generation'] ) ) {
                return $data['generation'];
            }
        }

        if ( $family === 'mistral' ) {
            if ( isset( $data['outputs'][0]['text'] ) ) {
                return $data['outputs'][0]['text'];
            }
        }

        // Titan
        if ( isset( $data['results'][0]['outputText'] ) ) {
            return $data['results'][0]['outputText'];
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from AWS Bedrock.', 'smart-seo-fixer' ) );
    }

    /**
     * Make an AWS Bedrock API request using SigV4 signing (no SDK required).
     *
     * @param array  $messages    Chat messages array [['role'=>'...','content'=>'...']]
     * @param string $system      System prompt (extracted from messages[0] if role=system)
     * @param int    $max_tokens
     * @param float  $temperature
     * @return string|WP_Error
     */
    public function request( $messages, $max_tokens = 500, $temperature = 0.7 ) {
        $access_key = $this->get_access_key();
        $secret_key = $this->get_secret_key();

        if ( empty( $access_key ) || empty( $secret_key ) ) {
            return new WP_Error( 'no_credentials', __( 'AWS Bedrock credentials not configured.', 'smart-seo-fixer' ) );
        }

        $endpoint = $this->get_endpoint();

        // Separate out a system role message if present (Claude style)
        $system   = '';
        $chat     = [];
        foreach ( $messages as $msg ) {
            if ( $msg['role'] === 'system' ) {
                $system = $msg['content'];
            } else {
                $chat[] = $msg;
            }
        }

        $body_array = $this->build_body( $chat, $system, $max_tokens, $temperature );
        $body       = wp_json_encode( $body_array );

        $make_request = function() use ( $access_key, $secret_key, $endpoint, $body ) {
            $signed_headers = $this->sigv4_sign( $access_key, $secret_key, $endpoint, $body );

            $response = wp_remote_post( $endpoint, [
                'timeout' => 60,
                'headers' => $signed_headers,
                'body'    => $body,
            ] );

            if ( is_wp_error( $response ) ) {
                if ( class_exists( 'SSF_Logger' ) ) {
                    SSF_Logger::error( 'Bedrock request failed: ' . $response->get_error_message(), 'ai' );
                }
                return $response;
            }

            $http_code   = wp_remote_retrieve_response_code( $response );
            $body_string = wp_remote_retrieve_body( $response );
            $data        = json_decode( $body_string, true );

            if ( $http_code >= 400 ) {
                $message = $data['message'] ?? ( $data['error']['message'] ?? "HTTP {$http_code}" );
                if ( class_exists( 'SSF_Logger' ) ) {
                    SSF_Logger::error( "Bedrock API error {$http_code}: {$message}", 'ai' );
                }
                return new WP_Error( 'api_error', $message );
            }

            $content = $this->extract_content( $data );

            if ( ! is_wp_error( $content ) && class_exists( 'SSF_Logger' ) ) {
                SSF_Logger::debug( 'Bedrock request successful', 'ai', [
                    'model'        => $this->get_model(),
                    'input_tokens' => $data['usage']['input_tokens'] ?? null,
                    'output_tokens'=> $data['usage']['output_tokens'] ?? null,
                ] );
            }

            return $content;
        };

        // Wrap with retry logic for transient failures (timeouts, 429, 500, 503)
        $max_retries = 3;
        $attempt = 0;
        $last_result = null;

        while ( $attempt < $max_retries ) {
            $attempt++;

            if ( class_exists( 'SSF_Rate_Limiter' ) ) {
                $last_result = SSF_Rate_Limiter::execute( 'bedrock', $make_request );
            } else {
                $last_result = $make_request();
            }

            // Success — return immediately
            if ( ! is_wp_error( $last_result ) ) {
                return $last_result;
            }

            // Check if the error is retryable
            $error_msg = $last_result->get_error_message();
            $is_retryable = (
                strpos( $error_msg, 'cURL error 28' ) !== false ||   // timeout
                strpos( $error_msg, 'cURL error 7' ) !== false ||    // connection refused
                strpos( $error_msg, 'HTTP 429' ) !== false ||        // rate limited
                strpos( $error_msg, 'HTTP 500' ) !== false ||        // server error
                strpos( $error_msg, 'HTTP 502' ) !== false ||        // bad gateway
                strpos( $error_msg, 'HTTP 503' ) !== false ||        // service unavailable
                strpos( $error_msg, 'ThrottlingException' ) !== false
            );

            if ( ! $is_retryable || $attempt >= $max_retries ) {
                break;
            }

            if ( class_exists( 'SSF_Logger' ) ) {
                SSF_Logger::warning( sprintf( 'Bedrock request failed (attempt %d/%d): %s — retrying', $attempt, $max_retries, $error_msg ), 'ai' );
            }

            // Exponential backoff: 2s, 4s (capped at PHP max_execution_time safety)
            $delay = min( pow( 2, $attempt ), 8 );
            sleep( $delay );
        }

        return $last_result;
    }

    // =========================================================================
    // AWS Signature Version 4 (SigV4) — pure PHP, no SDK required
    // =========================================================================

    /**
     * Sign a request with AWS Signature Version 4
     *
     * @param string $access_key
     * @param string $secret_key
     * @param string $endpoint      Full HTTPS URL
     * @param string $body          Raw request body (JSON string)
     * @return array                Headers to pass to wp_remote_post
     */
    private function sigv4_sign( $access_key, $secret_key, $endpoint, $body ) {
        $region  = $this->get_region();
        $service = 'bedrock';

        $parsed   = wp_parse_url( $endpoint );
        $host     = $parsed['host'];
        $raw_path = $parsed['path'] ?? '/';

        // SigV4 canonical URI: each path segment must be URI-encoded (RFC 3986).
        // The model ID contains a colon (e.g. v1:0) which must become %3A.
        // Split on '/' and encode each segment individually, then rejoin.
        $uri = implode( '/', array_map( 'rawurlencode', explode( '/', $raw_path ) ) );

        $amz_date   = gmdate( 'Ymd\THis\Z' );
        $date_stamp = gmdate( 'Ymd' );

        $content_type   = 'application/json';
        $payload_hash   = hash( 'sha256', $body );

        // Canonical headers (must be sorted and lowercase)
        $canonical_headers = "content-type:{$content_type}\nhost:{$host}\nx-amz-date:{$amz_date}\n";
        $signed_headers    = 'content-type;host;x-amz-date';

        // Canonical request
        $canonical_request = implode( "\n", [
            'POST',
            $uri,
            '',                     // query string (empty)
            $canonical_headers,
            $signed_headers,
            $payload_hash,
        ] );

        // Credential scope
        $credential_scope = implode( '/', [ $date_stamp, $region, $service, 'aws4_request' ] );

        // String to sign
        $string_to_sign = implode( "\n", [
            'AWS4-HMAC-SHA256',
            $amz_date,
            $credential_scope,
            hash( 'sha256', $canonical_request ),
        ] );

        // Signing key — derived from secret_key → date → region → service → "aws4_request"
        $signing_key = $this->hmac_sha256(
            $this->hmac_sha256(
                $this->hmac_sha256(
                    $this->hmac_sha256( 'AWS4' . $secret_key, $date_stamp, true ),
                    $region,
                    true
                ),
                $service,
                true
            ),
            'aws4_request',
            true
        );

        $signature = $this->hmac_sha256( $signing_key, $string_to_sign );

        // Authorization header
        $authorization = sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $access_key,
            $credential_scope,
            $signed_headers,
            $signature
        );

        return [
            'Content-Type'  => $content_type,
            'X-Amz-Date'    => $amz_date,
            'Authorization' => $authorization,
        ];
    }

    /**
     * HMAC-SHA256 helper
     *
     * @param string $key     Raw key (binary if $raw = true was used previously)
     * @param string $data
     * @param bool   $raw     Return raw binary (for chained derivation)
     * @return string
     */
    private function hmac_sha256( $key, $data, $raw = false ) {
        return hash_hmac( 'sha256', $data, $key, $raw );
    }

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Generate SEO title
     */
    public function generate_title( $content, $current_title = '', $focus_keyword = '' ) {
        $prompt = "You are an SEO expert. Generate an optimized SEO title for the following content.\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Maximum 60 characters (critical for Google display)\n";
        $prompt .= "- Include the focus keyword naturally if provided\n";
        $prompt .= "- Make it compelling and click-worthy\n";
        $prompt .= "- Avoid clickbait, be accurate to the content\n\n";
        if ( ! empty( $focus_keyword ) ) $prompt .= "Focus Keyword: {$focus_keyword}\n\n";
        if ( ! empty( $current_title ) ) $prompt .= "Current Title: {$current_title}\n\n";
        $clean = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $content ) ), 500 );
        $prompt .= "Content:\n{$clean}\n\nRespond with ONLY the optimized title, nothing else.";

        $messages = [
            [ 'role' => 'system', 'content' => 'You are an SEO expert that generates concise, optimized titles.' ],
            [ 'role' => 'user',   'content' => $prompt ],
        ];
        $result = $this->request( $messages, 100, 0.7 );
        if ( is_wp_error( $result ) ) return $result;
        return trim( $result, " \t\n\r\0\x0B\"'" );
    }

    /**
     * Generate meta description
     */
    public function generate_meta_description( $content, $current_description = '', $focus_keyword = '' ) {
        $prompt = "You are an SEO expert. Generate an optimized meta description for the following content.\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Between 150-160 characters (critical for Google display)\n";
        $prompt .= "- Include the focus keyword naturally if provided\n";
        $prompt .= "- Include a subtle call-to-action\n";
        $prompt .= "- Accurately summarize the content\n";
        $prompt .= "- Make it compelling to increase click-through rate\n\n";
        if ( ! empty( $focus_keyword ) )       $prompt .= "Focus Keyword: {$focus_keyword}\n\n";
        if ( ! empty( $current_description ) ) $prompt .= "Current Description: {$current_description}\n\n";
        $clean = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $content ) ), 500 );
        $prompt .= "Content:\n{$clean}\n\nRespond with ONLY the meta description, nothing else.";

        $messages = [
            [ 'role' => 'system', 'content' => 'You are an SEO expert that generates concise, compelling meta descriptions.' ],
            [ 'role' => 'user',   'content' => $prompt ],
        ];
        $result = $this->request( $messages, 200, 0.7 );
        if ( is_wp_error( $result ) ) return $result;
        return trim( $result, " \t\n\r\0\x0B\"'" );
    }

    /**
     * Generate image alt text
     */
    public function generate_alt_text( $image_url, $page_context = '', $focus_keyword = '' ) {
        $prompt  = "Generate descriptive, SEO-friendly alt text for an image.\n\nRequirements:\n";
        $prompt .= "- Maximum 125 characters\n- Be descriptive and accurate\n";
        $prompt .= "- Include the keyword naturally if relevant\n";
        $prompt .= "- Don't start with 'Image of' or 'Picture of'\n";
        $prompt .= "- Make it useful for screen readers\n\nImage URL: {$image_url}\n";
        if ( ! empty( $page_context ) )  $prompt .= 'Page Context: ' . wp_trim_words( $page_context, 100 ) . "\n";
        if ( ! empty( $focus_keyword ) ) $prompt .= "Focus Keyword: {$focus_keyword}\n";
        $prompt .= "\nRespond with ONLY the alt text, nothing else.";

        $messages = [
            [ 'role' => 'system', 'content' => 'You are an SEO expert that generates accessible, descriptive image alt text.' ],
            [ 'role' => 'user',   'content' => $prompt ],
        ];
        $result = $this->request( $messages, 100, 0.7 );
        if ( is_wp_error( $result ) ) return $result;
        return trim( $result, " \t\n\r\0\x0B\"'" );
    }

    /**
     * Analyze content and suggest improvements
     */
    public function analyze_content( $content, $title = '', $focus_keyword = '' ) {
        $prompt = "You are an SEO expert. Analyze this content and provide specific, actionable SEO recommendations.\n\n";
        if ( ! empty( $title ) )         $prompt .= "Title: {$title}\n";
        if ( ! empty( $focus_keyword ) ) $prompt .= "Focus Keyword: {$focus_keyword}\n";
        $clean = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $content ) ), 1000 );
        $prompt .= "\nContent:\n{$clean}\n\n";
        $prompt .= "Analyze and provide feedback on: keyword usage, content structure, readability, content length, internal/external linking.\n\n";
        $prompt .= 'Format as JSON: {"score":0-100,"issues":["..."],"suggestions":["..."],"strengths":["..."]}';

        $messages = [
            [ 'role' => 'system', 'content' => 'You are an SEO expert. Respond only with valid JSON.' ],
            [ 'role' => 'user',   'content' => $prompt ],
        ];
        $response = $this->request( $messages, 1000, 0.5 );
        if ( is_wp_error( $response ) ) return $response;
        $response = preg_replace( '/```json\s*/', '', $response );
        $response = preg_replace( '/```\s*/',     '', $response );
        $data = json_decode( trim( $response ), true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'json_error', __( 'Failed to parse AI response.', 'smart-seo-fixer' ) );
        }
        return $data;
    }

    /**
     * Generate content outline/structure
     */
    public function generate_outline( $topic, $focus_keyword = '' ) {
        $prompt  = "You are an SEO expert. Create a comprehensive content outline for this topic.\n\nTopic: {$topic}\n";
        if ( ! empty( $focus_keyword ) ) $prompt .= "Focus Keyword: {$focus_keyword}\n";
        $prompt .= "\nGenerate an SEO-optimized content outline.\n\n";
        $prompt .= "IMPORTANT: Do NOT include prefixes like 'H1:', 'H2:', 'H3:' in the text. Just write the actual heading text.\n\n";
        $prompt .= 'Format as JSON: {"title":"Your Compelling Title Here","sections":[{"heading":"First Section Heading","subsections":["Sub Topic One","Sub Topic Two"]}],"suggested_word_count":1500}';
        $prompt .= "\n\nProvide 4-6 sections, each with 2-3 subsections. No descriptions needed.";

        $messages = [
            [ 'role' => 'system', 'content' => 'You are an SEO content strategist. Respond only with valid JSON.' ],
            [ 'role' => 'user',   'content' => $prompt ],
        ];
        $response = $this->request( $messages, 1500, 0.7 );
        if ( is_wp_error( $response ) ) return $response;
        $response = preg_replace( '/```json\s*/', '', $response );
        $response = preg_replace( '/```\s*/',     '', $response );
        return json_decode( trim( $response ), true );
    }

    /**
     * Suggest focus keywords
     */
    public function suggest_keywords( $content, $title = '' ) {
        $prompt = "You are an SEO keyword research expert. Suggest the best focus keywords for this content.\n\n";
        if ( ! empty( $title ) ) $prompt .= "Title: {$title}\n\n";
        $clean = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $content ) ), 500 );
        $prompt .= "Content:\n{$clean}\n\n";
        $prompt .= "CRITICAL: Every keyword you suggest MUST appear as a VERBATIM substring in the title or content above (case-insensitive). Do NOT invent new phrases.\n\n";
        $prompt .= "Suggest 5 focus keywords with primary, secondary, and long-tail variations.\n\n";
        $prompt .= 'Format as JSON: {"primary":"keyword","secondary":["kw1","kw2"],"long_tail":["phrase1","phrase2"]}';

        $messages = [
            [ 'role' => 'system', 'content' => 'You are an SEO keyword expert. Respond only with valid JSON.' ],
            [ 'role' => 'user',   'content' => $prompt ],
        ];
        $response = $this->request( $messages, 500, 0.6 );
        if ( is_wp_error( $response ) ) return $response;
        $response = preg_replace( '/```json\s*/', '', $response );
        $response = preg_replace( '/```\s*/',     '', $response );
        return json_decode( trim( $response ), true );
    }

    /**
     * Generate schema markup suggestions
     */
    public function suggest_schema( $content, $post_type = 'article', $post_url = '', $site_url = '', $post_title = '', $site_name = '', $logo_url = '' ) {
        $prompt  = "You are a structured data expert. Suggest additional Schema.org markup for this content.\n\n";
        $prompt .= "=== REAL SITE DATA (use these exact values, do NOT make up alternatives) ===\n";
        if ( ! empty( $site_name ) )  $prompt .= "Organization Name: {$site_name}\n";
        if ( ! empty( $site_url ) )   $prompt .= "Site URL: {$site_url}\n";
        if ( ! empty( $logo_url ) )   $prompt .= "Logo URL: {$logo_url}\n";
        if ( ! empty( $post_url ) )   $prompt .= "Post URL: {$post_url}\n";
        if ( ! empty( $post_title ) ) $prompt .= "Title: {$post_title}\n";
        $prompt .= "Content Type: {$post_type}\n=== END SITE DATA ===\n\n";
        $clean = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $content ) ), 300 );
        $prompt .= "Content:\n{$clean}\n\nCRITICAL RULES:\n";
        $prompt .= "1. Do NOT suggest Article, BlogPosting, NewsArticle, WebPage, BreadcrumbList, Organization, or WebSite schemas.\n";
        $prompt .= "2. Use ONLY the real URLs provided. NEVER guess or fabricate URLs.\n";
        $prompt .= "3. For any logo/image, use ONLY the Logo URL from SITE DATA. Omit if not provided.\n";
        $prompt .= "4. Do NOT include articleBody.\n";
        $prompt .= '5. If no additional schema adds value, respond with exactly: {"_no_schema": true}' . "\n\n";
        $prompt .= "Format as valid JSON-LD with @context and @type.";

        $messages = [
            [ 'role' => 'system', 'content' => 'You are a Schema.org expert. Respond only with valid JSON-LD. Never use placeholder or example.com URLs.' ],
            [ 'role' => 'user',   'content' => $prompt ],
        ];
        return $this->request( $messages, 1000, 0.5 );
    }

    /**
     * Find a natural place in existing content to insert an internal link
     */
    public function find_internal_link_placement( $source_content, $target_title, $target_url, $target_summary = '' ) {
        $clean = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $source_content ) ), 800 );

        $prompt  = "You are an SEO expert specializing in internal linking strategy.\n\n";
        $prompt .= "TARGET PAGE TO LINK TO:\n- Title: {$target_title}\n- URL: {$target_url}\n";
        if ( ! empty( $target_summary ) ) $prompt .= "- Topic: {$target_summary}\n";
        $prompt .= "\nSOURCE CONTENT (find a natural anchor text phrase in here):\n{$clean}\n\n";
        $prompt .= "CRITICAL RULES:\n";
        $prompt .= "1. Find an existing phrase (2-6 words) that naturally relates to the target page topic\n";
        $prompt .= "2. The phrase MUST exist EXACTLY as-is in the source content\n";
        $prompt .= "3. Do NOT choose phrases already inside <a> tags\n";
        $prompt .= "4. Prefer body content, not headings\n\n";
        $prompt .= 'Respond with ONLY valid JSON: {"found":true,"anchor_text":"exact phrase","context":"...surrounding sentence..."}' . "\n";
        $prompt .= 'If no natural fit exists: {"found":false}' . "\nDo NOT wrap in code blocks.";

        $messages = [
            [ 'role' => 'system', 'content' => 'You are an internal linking expert. Respond only with valid JSON. Never wrap in code blocks.' ],
            [ 'role' => 'user',   'content' => $prompt ],
        ];
        $response = $this->request( $messages, 300, 0.3 );
        if ( is_wp_error( $response ) ) return $response;
        $response = preg_replace( '/```json\s*/', '', $response );
        $response = preg_replace( '/```\s*/',     '', $response );
        $data = json_decode( trim( $response ), true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'json_error', __( 'Failed to parse AI response for internal link.', 'smart-seo-fixer' ) );
        }
        return $data;
    }

    /**
     * Improve readability of content
     */
    public function improve_readability( $content ) {
        $prompt  = "You are a content editor. Improve the readability of this content while maintaining SEO value.\n\n";
        $prompt .= "Content:\n{$content}\n\nImprovements: shorter sentences, active voice, clearer structure, better transitions. Maintain all keywords.\n\n";
        $prompt .= "Return ONLY the improved content, nothing else.";

        $messages = [
            [ 'role' => 'system', 'content' => 'You are an expert content editor focused on readability.' ],
            [ 'role' => 'user',   'content' => $prompt ],
        ];
        return $this->request( $messages, 2000, 0.6 );
    }
}
