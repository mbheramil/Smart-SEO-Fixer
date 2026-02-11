<?php
/**
 * GitHub Plugin Updater
 * 
 * Checks a GitHub repository for new releases and integrates with
 * the WordPress plugin update system so users can click "Update" 
 * in the admin dashboard.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSF_Updater {
    
    /**
     * GitHub username/org
     */
    private $github_user = 'mbheramil';
    
    /**
     * GitHub repository name
     */
    private $github_repo = 'Smart-SEO-Fixer';
    
    /**
     * Plugin slug (basename)
     */
    private $plugin_slug;
    
    /**
     * Plugin file relative to plugins dir
     */
    private $plugin_file;
    
    /**
     * Current plugin version
     */
    private $current_version;
    
    /**
     * Cached GitHub release data
     */
    private $github_response = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin_file    = SSF_PLUGIN_BASENAME;
        $this->plugin_slug    = dirname($this->plugin_file);
        $this->current_version = SSF_VERSION;
        
        // Hook into the WordPress update system
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
        
        // Add "Check for updates" link on plugins page
        add_filter('plugin_action_links_' . $this->plugin_file, [$this, 'action_links']);
    }
    
    /**
     * Fetch the latest release info from GitHub
     */
    private function get_github_release() {
        if ($this->github_response !== null) {
            return $this->github_response;
        }
        
        // Check transient first (cache for 6 hours)
        $cached = get_transient('ssf_github_release');
        if ($cached !== false) {
            $this->github_response = $cached;
            return $cached;
        }
        
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            $this->github_user,
            $this->github_repo
        );
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Smart-SEO-Fixer-Updater',
            ],
            'timeout' => 10,
        ]);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $this->github_response = false;
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response));
        
        if (empty($body) || !isset($body->tag_name)) {
            $this->github_response = false;
            return false;
        }
        
        $this->github_response = $body;
        set_transient('ssf_github_release', $body, 6 * HOUR_IN_SECONDS);
        
        return $body;
    }
    
    /**
     * Check for plugin updates
     * Hooks into: pre_set_site_transient_update_plugins
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $release = $this->get_github_release();
        
        if (!$release) {
            return $transient;
        }
        
        // Normalize version — strip leading "v" from tag (e.g. "v1.1.0" → "1.1.0")
        $remote_version = ltrim($release->tag_name, 'v');
        
        if (version_compare($remote_version, $this->current_version, '>')) {
            $download_url = $this->get_download_url($release);
            
            if ($download_url) {
                $plugin_data = new stdClass();
                $plugin_data->slug        = $this->plugin_slug;
                $plugin_data->plugin      = $this->plugin_file;
                $plugin_data->new_version = $remote_version;
                $plugin_data->url         = $release->html_url;
                $plugin_data->package     = $download_url;
                $plugin_data->icons       = [];
                $plugin_data->banners     = [];
                $plugin_data->tested      = '';
                $plugin_data->requires_php = '7.4';
                
                $transient->response[$this->plugin_file] = $plugin_data;
            }
        }
        
        return $transient;
    }
    
    /**
     * Provide plugin info for the "View Details" popup
     * Hooks into: plugins_api
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }
        
        $release = $this->get_github_release();
        
        if (!$release) {
            return $result;
        }
        
        $remote_version = ltrim($release->tag_name, 'v');
        
        $info = new stdClass();
        $info->name          = 'Smart SEO Fixer';
        $info->slug          = $this->plugin_slug;
        $info->version       = $remote_version;
        $info->author        = '<a href="https://github.com/' . $this->github_user . '">mbheramil</a>';
        $info->homepage      = 'https://github.com/' . $this->github_user . '/' . $this->github_repo;
        $info->requires      = '5.8';
        $info->tested        = '';
        $info->requires_php  = '7.4';
        $info->downloaded    = 0;
        $info->last_updated  = $release->published_at ?? '';
        $info->download_link = $this->get_download_url($release);
        
        // Convert markdown body to HTML for the "Changelog" tab
        $info->sections = [
            'description' => 'AI-powered SEO optimization plugin that analyzes and fixes SEO issues using OpenAI.',
            'changelog'   => nl2br(esc_html($release->body ?? 'See GitHub for changelog.')),
        ];
        
        $info->banners = [];
        
        return $info;
    }
    
    /**
     * Fix directory name after install
     * GitHub zips are named "Repo-main" or "Repo-v1.0.0"; WordPress needs the original folder name.
     * Hooks into: upgrader_post_install
     */
    public function after_install($response, $hook_extra, $result) {
        // Only act on our plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_file) {
            return $result;
        }
        
        global $wp_filesystem;
        
        $install_dir = $result['destination'];
        $proper_dir  = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
        
        // If the extracted folder name doesn't match, rename it
        if ($install_dir !== $proper_dir) {
            $wp_filesystem->move($install_dir, $proper_dir);
            $result['destination'] = $proper_dir;
            $result['destination_name'] = $this->plugin_slug;
        }
        
        // Re-activate plugin if it was active
        if (is_plugin_active($this->plugin_file)) {
            activate_plugin($this->plugin_file);
        }
        
        return $result;
    }
    
    /**
     * Get the best download URL from a release
     * Prefers a .zip asset; falls back to the GitHub source zipball.
     */
    private function get_download_url($release) {
        // Check for uploaded .zip asset first
        if (!empty($release->assets) && is_array($release->assets)) {
            foreach ($release->assets as $asset) {
                if (isset($asset->browser_download_url) && substr($asset->name, -4) === '.zip') {
                    return $asset->browser_download_url;
                }
            }
        }
        
        // Fall back to GitHub's auto-generated source zip
        if (!empty($release->zipball_url)) {
            return $release->zipball_url;
        }
        
        return false;
    }
    
    /**
     * Add action links on the Plugins page
     */
    public function action_links($links) {
        $check_link = '<a href="' . esc_url(wp_nonce_url(
            admin_url('plugins.php?ssf_force_update_check=1'),
            'ssf_force_check'
        )) . '">' . __('Check for updates', 'smart-seo-fixer') . '</a>';
        
        array_unshift($links, $check_link);
        return $links;
    }
    
    /**
     * Force clear the update transient cache
     */
    public static function force_check() {
        if (isset($_GET['ssf_force_update_check']) && wp_verify_nonce($_GET['_wpnonce'], 'ssf_force_check')) {
            delete_transient('ssf_github_release');
            delete_site_transient('update_plugins');
            
            wp_redirect(admin_url('plugins.php?ssf_checked=1'));
            exit;
        }
    }
}
