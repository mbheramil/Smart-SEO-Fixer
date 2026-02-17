<?php
/**
 * Google Search Console API Client
 * 
 * Handles OAuth2 authentication, token management,
 * and all Google Search Console API interactions.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_GSC_Client {
    
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    
    private $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth';
    private $token_url = 'https://oauth2.googleapis.com/token';
    private $api_base  = 'https://www.googleapis.com/webmasters/v3';
    private $inspection_api = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';
    
    private $scopes = [
        'https://www.googleapis.com/auth/webmasters.readonly',
        'https://www.googleapis.com/auth/webmasters',
    ];
    
    public function __construct() {
        $this->client_id     = Smart_SEO_Fixer::get_option('gsc_client_id', '');
        $this->client_secret = Smart_SEO_Fixer::get_option('gsc_client_secret', '');
        $this->redirect_uri  = admin_url('admin.php?page=smart-seo-fixer-settings');
        
        // Handle OAuth callback
        add_action('admin_init', [$this, 'handle_oauth_callback']);
    }
    
    /**
     * Check if OAuth credentials are configured
     */
    public function has_credentials() {
        return !empty($this->client_id) && !empty($this->client_secret);
    }
    
    /**
     * Check if we have a valid access token (connected)
     */
    public function is_connected() {
        $tokens = $this->get_tokens();
        return !empty($tokens['access_token']);
    }
    
    /**
     * Get the OAuth2 authorization URL
     */
    public function get_auth_url() {
        $params = [
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'response_type' => 'code',
            'scope'         => implode(' ', $this->scopes),
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => wp_create_nonce('ssf_gsc_oauth'),
        ];
        
        return $this->auth_url . '?' . http_build_query($params);
    }
    
    /**
     * Handle the OAuth callback from Google
     */
    public function handle_oauth_callback() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'smart-seo-fixer-settings') {
            return;
        }
        
        if (!isset($_GET['code']) || !isset($_GET['state'])) {
            return;
        }
        
        if (!wp_verify_nonce($_GET['state'], 'ssf_gsc_oauth')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $code = sanitize_text_field($_GET['code']);
        $result = $this->exchange_code($code);
        
        if (is_wp_error($result)) {
            set_transient('ssf_gsc_error', $result->get_error_message(), 60);
        } else {
            // Fetch and store the site URL
            $sites = $this->get_sites();
            if (!is_wp_error($sites) && !empty($sites)) {
                $site_url = home_url('/');
                $matched = false;
                foreach ($sites as $site) {
                    if (rtrim($site['siteUrl'], '/') === rtrim($site_url, '/') 
                        || $site['siteUrl'] === $site_url
                        || strpos($site_url, rtrim($site['siteUrl'], '/')) === 0) {
                        Smart_SEO_Fixer::update_option('gsc_site_url', $site['siteUrl']);
                        $matched = true;
                        break;
                    }
                }
                if (!$matched && !empty($sites[0]['siteUrl'])) {
                    Smart_SEO_Fixer::update_option('gsc_site_url', $sites[0]['siteUrl']);
                }
            }
            set_transient('ssf_gsc_success', __('Google Search Console connected successfully!', 'smart-seo-fixer'), 60);
        }
        
        wp_redirect(admin_url('admin.php?page=smart-seo-fixer-settings&ssf_gsc_connected=1'));
        exit;
    }
    
    /**
     * Exchange authorization code for tokens
     */
    private function exchange_code($code) {
        $response = wp_remote_post($this->token_url, [
            'timeout' => 30,
            'body'    => [
                'code'          => $code,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $this->redirect_uri,
                'grant_type'    => 'authorization_code',
            ],
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('gsc_auth_error', $body['error_description'] ?? $body['error']);
        }
        
        if (empty($body['access_token'])) {
            return new WP_Error('gsc_auth_error', __('No access token received.', 'smart-seo-fixer'));
        }
        
        $tokens = [
            'access_token'  => $body['access_token'],
            'refresh_token' => $body['refresh_token'] ?? '',
            'expires_at'    => time() + ($body['expires_in'] ?? 3600),
            'token_type'    => $body['token_type'] ?? 'Bearer',
        ];
        
        $this->save_tokens($tokens);
        
        return true;
    }
    
    /**
     * Refresh the access token using the refresh token
     */
    private function refresh_access_token() {
        $tokens = $this->get_tokens();
        
        if (empty($tokens['refresh_token'])) {
            return new WP_Error('gsc_no_refresh', __('No refresh token available. Please reconnect.', 'smart-seo-fixer'));
        }
        
        $response = wp_remote_post($this->token_url, [
            'timeout' => 30,
            'body'    => [
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'refresh_token' => $tokens['refresh_token'],
                'grant_type'    => 'refresh_token',
            ],
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            // If refresh fails, disconnect
            if ($body['error'] === 'invalid_grant') {
                $this->disconnect();
            }
            return new WP_Error('gsc_refresh_error', $body['error_description'] ?? $body['error']);
        }
        
        $tokens['access_token'] = $body['access_token'];
        $tokens['expires_at']   = time() + ($body['expires_in'] ?? 3600);
        if (!empty($body['refresh_token'])) {
            $tokens['refresh_token'] = $body['refresh_token'];
        }
        
        $this->save_tokens($tokens);
        
        return $tokens['access_token'];
    }
    
    /**
     * Get a valid access token (auto-refreshing if expired)
     */
    private function get_access_token() {
        $tokens = $this->get_tokens();
        
        if (empty($tokens['access_token'])) {
            return new WP_Error('gsc_not_connected', __('Not connected to Google Search Console.', 'smart-seo-fixer'));
        }
        
        // Refresh if expired (with 60s buffer)
        if (time() >= ($tokens['expires_at'] - 60)) {
            $result = $this->refresh_access_token();
            if (is_wp_error($result)) {
                return $result;
            }
            return $result;
        }
        
        return $tokens['access_token'];
    }
    
    /**
     * Make an authenticated API request
     */
    private function api_request($url, $method = 'GET', $body = null) {
        $access_token = $this->get_access_token();
        
        if (is_wp_error($access_token)) {
            return $access_token;
        }
        
        $args = [
            'method'  => $method,
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
        ];
        
        if ($body !== null) {
            $args['body'] = wp_json_encode($body);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code === 401) {
            // Token might have been revoked — try refresh once
            $new_token = $this->refresh_access_token();
            if (is_wp_error($new_token)) {
                return $new_token;
            }
            $args['headers']['Authorization'] = 'Bearer ' . $new_token;
            $response = wp_remote_request($url, $args);
            if (is_wp_error($response)) {
                return $response;
            }
            $code = wp_remote_retrieve_response_code($response);
            $data = json_decode(wp_remote_retrieve_body($response), true);
        }
        
        if ($code >= 400) {
            $error_msg = $data['error']['message'] ?? __('API request failed.', 'smart-seo-fixer');
            return new WP_Error('gsc_api_error', $error_msg);
        }
        
        return $data;
    }
    
    // ========================================================
    // Public API Methods
    // ========================================================
    
    /**
     * Get list of verified sites
     */
    public function get_sites() {
        $url = $this->api_base . '/sites';
        $result = $this->api_request($url);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $sites = [];
        if (!empty($result['siteEntry'])) {
            foreach ($result['siteEntry'] as $site) {
                $sites[] = [
                    'siteUrl'         => $site['siteUrl'],
                    'permissionLevel' => $site['permissionLevel'] ?? '',
                ];
            }
        }
        
        return $sites;
    }
    
    /**
     * Get the currently selected site URL
     */
    public function get_site_url() {
        return Smart_SEO_Fixer::get_option('gsc_site_url', '');
    }
    
    /**
     * Search Analytics query
     * 
     * @param array $params Query parameters
     * @return array|WP_Error
     */
    public function get_search_analytics($params = []) {
        $site_url = $this->get_site_url();
        if (empty($site_url)) {
            return new WP_Error('gsc_no_site', __('No site selected. Please connect GSC first.', 'smart-seo-fixer'));
        }
        
        $defaults = [
            'startDate'  => date('Y-m-d', strtotime('-28 days')),
            'endDate'    => date('Y-m-d', strtotime('-1 day')),
            'dimensions' => ['date'],
            'rowLimit'   => 25000,
        ];
        
        $query = array_merge($defaults, $params);
        
        $url = $this->api_base . '/sites/' . urlencode($site_url) . '/searchAnalytics/query';
        
        return $this->api_request($url, 'POST', $query);
    }
    
    /**
     * Get search performance overview (totals)
     */
    public function get_performance_overview($days = 28) {
        $result = $this->get_search_analytics([
            'startDate'  => date('Y-m-d', strtotime("-{$days} days")),
            'endDate'    => date('Y-m-d', strtotime('-1 day')),
            'dimensions' => ['date'],
        ]);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $totals = [
            'clicks'      => 0,
            'impressions' => 0,
            'ctr'         => 0,
            'position'    => 0,
            'days'        => [],
        ];
        
        if (!empty($result['rows'])) {
            $position_sum = 0;
            foreach ($result['rows'] as $row) {
                $totals['clicks']      += $row['clicks'] ?? 0;
                $totals['impressions'] += $row['impressions'] ?? 0;
                $position_sum          += $row['position'] ?? 0;
                $totals['days'][] = [
                    'date'        => $row['keys'][0] ?? '',
                    'clicks'      => $row['clicks'] ?? 0,
                    'impressions' => $row['impressions'] ?? 0,
                    'ctr'         => round(($row['ctr'] ?? 0) * 100, 2),
                    'position'    => round($row['position'] ?? 0, 1),
                ];
            }
            $count = count($result['rows']);
            $totals['ctr']      = $totals['impressions'] > 0 ? round(($totals['clicks'] / $totals['impressions']) * 100, 2) : 0;
            $totals['position'] = $count > 0 ? round($position_sum / $count, 1) : 0;
        }
        
        return $totals;
    }
    
    /**
     * Get top queries (keywords)
     */
    public function get_top_queries($days = 28, $limit = 50) {
        return $this->get_search_analytics([
            'startDate'  => date('Y-m-d', strtotime("-{$days} days")),
            'endDate'    => date('Y-m-d', strtotime('-1 day')),
            'dimensions' => ['query'],
            'rowLimit'   => $limit,
        ]);
    }
    
    /**
     * Get top pages
     */
    public function get_top_pages($days = 28, $limit = 50) {
        return $this->get_search_analytics([
            'startDate'  => date('Y-m-d', strtotime("-{$days} days")),
            'endDate'    => date('Y-m-d', strtotime('-1 day')),
            'dimensions' => ['page'],
            'rowLimit'   => $limit,
        ]);
    }
    
    /**
     * URL Inspection — check if a URL is indexed
     */
    public function inspect_url($url) {
        $site_url = $this->get_site_url();
        if (empty($site_url)) {
            return new WP_Error('gsc_no_site', __('No site selected.', 'smart-seo-fixer'));
        }
        
        $body = [
            'inspectionUrl' => $url,
            'siteUrl'       => $site_url,
        ];
        
        return $this->api_request($this->inspection_api, 'POST', $body);
    }
    
    /**
     * Submit a sitemap
     */
    public function submit_sitemap($sitemap_url) {
        $site_url = $this->get_site_url();
        if (empty($site_url)) {
            return new WP_Error('gsc_no_site', __('No site selected.', 'smart-seo-fixer'));
        }
        
        $url = $this->api_base . '/sites/' . urlencode($site_url) . '/sitemaps/' . urlencode($sitemap_url);
        
        return $this->api_request($url, 'PUT');
    }
    
    /**
     * List sitemaps
     */
    public function get_sitemaps() {
        $site_url = $this->get_site_url();
        if (empty($site_url)) {
            return new WP_Error('gsc_no_site', __('No site selected.', 'smart-seo-fixer'));
        }
        
        $url = $this->api_base . '/sites/' . urlencode($site_url) . '/sitemaps';
        
        return $this->api_request($url);
    }
    
    /**
     * Get index status summary for the site
     */
    public function get_index_status_summary() {
        $pages = $this->get_top_pages(28, 1000);
        
        if (is_wp_error($pages)) {
            return $pages;
        }
        
        $indexed_urls = [];
        if (!empty($pages['rows'])) {
            foreach ($pages['rows'] as $row) {
                if (!empty($row['keys'][0])) {
                    $indexed_urls[] = $row['keys'][0];
                }
            }
        }
        
        return [
            'indexed_count' => count($indexed_urls),
            'urls'          => $indexed_urls,
        ];
    }
    
    // ========================================================
    // Token Storage
    // ========================================================
    
    /**
     * Get stored tokens
     */
    private function get_tokens() {
        $tokens = get_option('ssf_gsc_tokens', []);
        if (!is_array($tokens)) {
            return [];
        }
        return $tokens;
    }
    
    /**
     * Save tokens
     */
    private function save_tokens($tokens) {
        update_option('ssf_gsc_tokens', $tokens, false);
    }
    
    /**
     * Disconnect — clear all GSC data
     */
    public function disconnect() {
        delete_option('ssf_gsc_tokens');
        Smart_SEO_Fixer::update_option('gsc_site_url', '');
        delete_transient('ssf_gsc_performance');
        delete_transient('ssf_gsc_queries');
        delete_transient('ssf_gsc_pages');
    }
    
    /**
     * Get connected account info
     */
    public function get_account_info() {
        if (!$this->is_connected()) {
            return null;
        }
        
        $site_url = $this->get_site_url();
        $tokens = $this->get_tokens();
        
        return [
            'connected' => true,
            'site_url'  => $site_url,
            'expires'   => date('Y-m-d H:i:s', $tokens['expires_at'] ?? 0),
        ];
    }
}
