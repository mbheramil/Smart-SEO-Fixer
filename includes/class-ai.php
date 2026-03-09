<?php
/**
 * AI Provider Factory
 *
 * Returns the AWS Bedrock AI provider instance.
 * All AI operations route through SSF_Bedrock via this factory.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_AI {

    /**
     * Get the currently configured AI provider instance.
     *
     * @return SSF_Bedrock
     */
    public static function get() {
        return new SSF_Bedrock();
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
     * Return a human-readable label for the active provider.
     * Used in error messages and admin notices.
     *
     * @return string
     */
    public static function provider_label() {
        return 'AWS Bedrock';
    }

    /**
     * Return the not-configured error message for the active provider.
     *
     * @return string
     */
    public static function not_configured_message() {
        return __( 'AWS Bedrock credentials not configured. Please add your Access Key and Secret Key in Settings.', 'smart-seo-fixer' );
    }
}
