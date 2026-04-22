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
}
