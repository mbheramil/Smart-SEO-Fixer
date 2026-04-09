<?php
/**
 * Settings View
 */

if (!defined('ABSPATH')) {
    exit;
}

$bedrock_region  = Smart_SEO_Fixer::get_option('bedrock_region', 'us-east-1');
$bedrock_access  = Smart_SEO_Fixer::get_option('bedrock_access_key');
$bedrock_secret  = Smart_SEO_Fixer::get_option('bedrock_secret_key');

// Check if credentials are set as wp-config.php constants
$const_access = defined('SSF_BEDROCK_ACCESS_KEY') && SSF_BEDROCK_ACCESS_KEY !== '';
$const_secret = defined('SSF_BEDROCK_SECRET_KEY') && SSF_BEDROCK_SECRET_KEY !== '';
$const_region = defined('SSF_BEDROCK_REGION')     && SSF_BEDROCK_REGION     !== '';
$using_consts = $const_access && $const_secret;

// For display purposes, use configured value from whichever source is active
$effective_access = $const_access ? SSF_BEDROCK_ACCESS_KEY : $bedrock_access;
$effective_secret = $const_secret ? SSF_BEDROCK_SECRET_KEY : $bedrock_secret;
$effective_region = $const_region ? SSF_BEDROCK_REGION     : $bedrock_region;
$is_configured    = !empty($effective_access) && !empty($effective_secret);
$github_token = Smart_SEO_Fixer::get_option('github_token', '');
$gsc_client_id = Smart_SEO_Fixer::get_option('gsc_client_id', '');
$gsc_client_secret = Smart_SEO_Fixer::get_option('gsc_client_secret', '');
$gsc_connected = false;
$gsc_site_url = '';
$gsc_sites = [];
if (class_exists('SSF_GSC_Client')) {
    $gsc_client = new SSF_GSC_Client();
    $gsc_connected = $gsc_client->is_connected();
    $gsc_site_url = Smart_SEO_Fixer::get_option('gsc_site_url', '');
    if ($gsc_connected) {
        // Use cached site list first (fast), live fetch as fallback
        $cached = get_transient('ssf_gsc_sites_cache');
        if (!empty($cached) && is_array($cached)) {
            $gsc_sites = $cached;
        } else {
            $gsc_sites_result = $gsc_client->get_sites();
            if (!is_wp_error($gsc_sites_result) && !empty($gsc_sites_result)) {
                $gsc_sites = $gsc_sites_result;
                set_transient('ssf_gsc_sites_cache', $gsc_sites, DAY_IN_SECONDS);
            }
        }
    }
}
$auto_meta = Smart_SEO_Fixer::get_option('auto_meta');
$auto_alt_text = Smart_SEO_Fixer::get_option('auto_alt_text');
$enable_schema = Smart_SEO_Fixer::get_option('enable_schema', true);
$enable_sitemap = Smart_SEO_Fixer::get_option('enable_sitemap', true);
$disable_other_seo_output = Smart_SEO_Fixer::get_option('disable_other_seo_output', false);
$redirect_attachments = Smart_SEO_Fixer::get_option('redirect_attachments', '');
$title_separator = Smart_SEO_Fixer::get_option('title_separator', '|');
$homepage_title = Smart_SEO_Fixer::get_option('homepage_title');
$homepage_description = Smart_SEO_Fixer::get_option('homepage_description');
$post_types = Smart_SEO_Fixer::get_option('post_types', ['post', 'page']);

// Detect conflicting SEO plugins
$conflicting_plugins = [];
if (defined('WPSEO_VERSION')) $conflicting_plugins[] = 'Yoast SEO';
if (defined('RANK_MATH_VERSION')) $conflicting_plugins[] = 'Rank Math';
if (defined('AIOSEO_VERSION') || class_exists('AIOSEOP_Core')) $conflicting_plugins[] = 'All in One SEO';
if (defined('THE_SEO_FRAMEWORK_VERSION')) $conflicting_plugins[] = 'The SEO Framework';
if (defined('SEOPRESS_VERSION')) $conflicting_plugins[] = 'SEOPress';

$available_post_types = get_post_types(['public' => true], 'objects');
unset($available_post_types['attachment']);
?>
<div class="wrap ssf-wrap">
    <h1 class="ssf-page-title">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e('Smart SEO Fixer Settings', 'smart-seo-fixer'); ?>
        <span class="ssf-version">v<?php echo esc_html(SSF_VERSION); ?></span>
    </h1>
    
    <form id="ssf-settings-form" class="ssf-settings-form">
                <!-- AI Provider Settings -->
        <div class="ssf-card">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-cloud"></span>
                    <?php esc_html_e('AI Provider', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <input type="hidden" name="ai_provider" value="bedrock">
                <!-- AWS Bedrock Settings -->
                <div id="ssf-bedrock-settings">
                    <hr style="margin:16px 0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:4px;">
                        <h3 style="margin:0;"><?php esc_html_e('AWS Bedrock Configuration', 'smart-seo-fixer'); ?></h3>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span id="ssf-bedrock-status" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;padding:4px 12px;border-radius:20px;
                                <?php echo $is_configured ? 'background:#dcfce7;color:#166534;border:1px solid #bbf7d0;' : 'background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;'; ?>">
                                <span id="ssf-bedrock-status-dot" style="width:8px;height:8px;border-radius:50%;background:<?php echo $is_configured ? '#16a34a' : '#9ca3af'; ?>;"></span>
                                <span id="ssf-bedrock-status-text"><?php echo $is_configured ? esc_html__('Credentials saved', 'smart-seo-fixer') : esc_html__('Not configured', 'smart-seo-fixer'); ?></span>
                            </span>
                            <button type="button" class="button" id="ssf-test-bedrock" <?php echo !$is_configured ? 'disabled' : ''; ?>>
                                <span class="dashicons dashicons-controls-play" style="margin-top:3px;"></span>
                                <?php esc_html_e('Test Connection', 'smart-seo-fixer'); ?>
                            </button>
                        </div>
                    </div>
                    <p class="description" style="margin:0 0 12px;">
                        <?php if ($using_consts): ?>
                            <span style="color:#166534;font-weight:600;">&#128274; <?php esc_html_e('Credentials are set via wp-config.php constants and are not stored in the database.', 'smart-seo-fixer'); ?></span>
                        <?php else: ?>
                            <?php esc_html_e('Uses your own AWS account. Credentials are stored in WordPress options.', 'smart-seo-fixer'); ?>
                            &mdash; <strong><?php esc_html_e('For better security, define them as constants in wp-config.php (see below).', 'smart-seo-fixer'); ?></strong>
                        <?php endif; ?>
                    </p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="bedrock_access_key"><?php esc_html_e('Access Key ID', 'smart-seo-fixer'); ?></label></th>
                            <td>
                                <?php if ($const_access): ?>
                                    <input type="text" class="regular-text" value="<?php echo esc_attr(substr($effective_access, 0, 4) . str_repeat('*', 12)); ?>" disabled>
                                <?php else: ?>
                                    <input type="text" name="bedrock_access_key" id="bedrock_access_key"
                                           value="<?php echo esc_attr($bedrock_access); ?>"
                                           class="regular-text" autocomplete="off"
                                           placeholder="AKIA...">
                                    <p class="description"><?php esc_html_e('Your AWS IAM Access Key ID with Bedrock permissions.', 'smart-seo-fixer'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bedrock_secret_key"><?php esc_html_e('Secret Access Key', 'smart-seo-fixer'); ?></label></th>
                            <td>
                                <?php if ($const_secret): ?>
                                    <input type="text" class="regular-text" value="<?php echo esc_attr(str_repeat('*', 20)); ?>" disabled>
                                <?php else: ?>
                                    <input type="password" name="bedrock_secret_key" id="bedrock_secret_key"
                                           value="<?php echo esc_attr($bedrock_secret); ?>"
                                           class="regular-text" autocomplete="off">
                                    <button type="button" class="button" id="toggle-bedrock-secret"><?php esc_html_e('Show', 'smart-seo-fixer'); ?></button>
                                    <p class="description"><?php esc_html_e('Your AWS IAM Secret Access Key.', 'smart-seo-fixer'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bedrock_region"><?php esc_html_e('AWS Region', 'smart-seo-fixer'); ?></label></th>
                            <td>
                                <?php if ($const_region): ?>
                                    <input type="text" class="regular-text" value="<?php echo esc_attr($effective_region); ?>" disabled>
                                <?php else: ?>
                                <select name="bedrock_region" id="bedrock_region">
                                    <?php
                                    $regions = [
                                        'us-east-1'      => 'US East (N. Virginia)',
                                        'us-west-2'      => 'US West (Oregon)',
                                        'eu-west-1'      => 'EU (Ireland)',
                                        'eu-central-1'   => 'EU (Frankfurt)',
                                        'ap-southeast-1' => 'Asia Pacific (Singapore)',
                                        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
                                    ];
                                    foreach ($regions as $code => $label):
                                    ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($bedrock_region, $code); ?>>
                                        <?php echo esc_html($code . ' — ' . $label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Select the AWS region where Bedrock is enabled on your account.', 'smart-seo-fixer'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>

                    </table>
                    <div id="ssf-bedrock-test-result" style="display:none;margin-top:12px;padding:12px 16px;border-radius:6px;"></div>

                    <?php if (!$using_consts): ?>
                    <div style="margin-top:12px;padding:14px 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;">
                        <p style="margin:0 0 8px;font-weight:600;color:#166534;">
                            <span class="dashicons dashicons-lock" style="font-size:16px;"></span>
                            <?php esc_html_e('Store credentials securely in wp-config.php (recommended)', 'smart-seo-fixer'); ?>
                        </p>
                        <p style="margin:0 0 8px;font-size:12px;color:#14532d;"><?php esc_html_e('Add these lines to your wp-config.php before the "That\'s all, stop editing!" comment. Credentials defined as constants are never stored in the database.', 'smart-seo-fixer'); ?></p>
                        <pre style="margin:0;padding:10px 14px;background:#fff;border:1px solid #d1fae5;border-radius:4px;font-size:12px;line-height:1.7;color:#065f46;overflow-x:auto;">define( 'SSF_BEDROCK_ACCESS_KEY', 'YOUR_ACCESS_KEY_ID' );
define( 'SSF_BEDROCK_SECRET_KEY', 'YOUR_SECRET_ACCESS_KEY' );
define( 'SSF_BEDROCK_REGION',     'us-east-1' );  // optional, defaults to us-east-1</pre>
                        <p style="margin:8px 0 0;font-size:11px;color:#166534;"><?php esc_html_e('Once defined, the fields above will be locked and show a padlock icon. The database values will be ignored.', 'smart-seo-fixer'); ?></p>
                    </div>
                    <?php endif; ?>


                </div>
            </div>
        </div>
        
        <!-- Google Search Console -->
        <div class="ssf-card">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-google"></span>
                    <?php esc_html_e('Google Search Console', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <?php 
                $gsc_error = get_transient('ssf_gsc_error');
                $gsc_success = get_transient('ssf_gsc_success');
                if ($gsc_error): delete_transient('ssf_gsc_error'); ?>
                    <div class="notice notice-error inline" style="margin: 0 0 16px;">
                        <p><?php echo esc_html($gsc_error); ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($gsc_success): delete_transient('ssf_gsc_success'); ?>
                    <div class="notice notice-success inline" style="margin: 0 0 16px;">
                        <p><?php echo esc_html($gsc_success); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($gsc_connected): ?>
                    <div style="padding: 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; margin-bottom: 16px;">
                        <p style="margin: 0 0 8px; font-weight: 600; color: #15803d;">
                            <span class="dashicons dashicons-yes-alt" style="color: #16a34a;"></span>
                            <?php esc_html_e('Connected to Google Search Console', 'smart-seo-fixer'); ?>
                        </p>
                        <?php if ($gsc_site_url): ?>
                            <p style="margin: 0; color: #166534;">
                                <?php printf(esc_html__('Site: %s', 'smart-seo-fixer'), '<strong>' . esc_html($gsc_site_url) . '</strong>'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gsc_site_url"><?php esc_html_e('Site Property', 'smart-seo-fixer'); ?></label>
                            </th>
                            <td>
                                <?php if (!empty($gsc_sites)): ?>
                                <select name="gsc_site_url" id="gsc_site_url" style="min-width: 300px;">
                                    <?php if (empty($gsc_site_url)): ?>
                                        <option value=""><?php esc_html_e('— Select a site —', 'smart-seo-fixer'); ?></option>
                                    <?php endif; ?>
                                    <?php foreach ($gsc_sites as $site): ?>
                                        <option value="<?php echo esc_attr($site['siteUrl']); ?>" <?php selected($gsc_site_url, $site['siteUrl']); ?>>
                                            <?php echo esc_html($site['siteUrl']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="button button-small" id="ssf-gsc-refresh-sites" style="vertical-align: middle;">
                                    <span class="dashicons dashicons-update" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
                                    <?php esc_html_e('Refresh', 'smart-seo-fixer'); ?>
                                </button>
                                <p class="description">
                                    <?php esc_html_e('Select which site property to use, then save settings.', 'smart-seo-fixer'); ?>
                                </p>
                                <?php else: ?>
                                <div style="margin-bottom: 8px;">
                                    <button type="button" class="button button-primary" id="ssf-gsc-refresh-sites">
                                        <span class="dashicons dashicons-update" style="vertical-align: text-bottom;"></span>
                                        <?php esc_html_e('Load Site List', 'smart-seo-fixer'); ?>
                                    </button>
                                    <span id="ssf-gsc-refresh-status" style="margin-left: 8px; color: #666;"></span>
                                </div>
                                <div id="ssf-gsc-sites-dropdown" style="display: none; margin-bottom: 8px;"></div>
                                <details style="margin-top: 8px;">
                                    <summary style="cursor: pointer; color: #666; font-size: 12px;"><?php esc_html_e('Or enter site URL manually', 'smart-seo-fixer'); ?></summary>
                                    <div style="margin-top: 8px;">
                                        <input type="text" name="gsc_site_url" id="gsc_site_url"
                                               value="<?php echo esc_attr($gsc_site_url); ?>"
                                               class="regular-text"
                                               placeholder="<?php esc_attr_e('e.g. https://example.com/ or sc-domain:example.com', 'smart-seo-fixer'); ?>" />
                                    </div>
                                </details>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-search-performance')); ?>" class="button button-primary">
                            <span class="dashicons dashicons-chart-area" style="vertical-align: text-bottom;"></span>
                            <?php esc_html_e('View Search Performance', 'smart-seo-fixer'); ?>
                        </a>
                        <button type="button" class="button" id="ssf-gsc-disconnect" style="color: #dc2626; border-color: #dc2626;">
                            <?php esc_html_e('Disconnect', 'smart-seo-fixer'); ?>
                        </button>
                    </p>
                <?php else: ?>
                    <p class="description" style="margin-bottom: 16px;">
                        <?php esc_html_e('Connect your Google Search Console to see real search performance data, index status, and more — directly inside WordPress.', 'smart-seo-fixer'); ?>
                    </p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gsc_client_id"><?php esc_html_e('Client ID', 'smart-seo-fixer'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       name="gsc_client_id" 
                                       id="gsc_client_id" 
                                       value="<?php echo esc_attr($gsc_client_id); ?>" 
                                       class="regular-text"
                                       placeholder="xxxxxx.apps.googleusercontent.com">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="gsc_client_secret"><?php esc_html_e('Client Secret', 'smart-seo-fixer'); ?></label>
                            </th>
                            <td>
                                <input type="password" 
                                       name="gsc_client_secret" 
                                       id="gsc_client_secret" 
                                       value="<?php echo esc_attr($gsc_client_secret); ?>" 
                                       class="regular-text"
                                       autocomplete="off">
                            </td>
                        </tr>
                    </table>
                    
                    <?php if (!empty($gsc_client_id) && !empty($gsc_client_secret)): ?>
                        <p>
                            <a href="<?php echo esc_url($gsc_client->get_auth_url()); ?>" class="button button-primary button-large">
                                <span class="dashicons dashicons-google" style="vertical-align: text-bottom;"></span>
                                <?php esc_html_e('Connect Google Search Console', 'smart-seo-fixer'); ?>
                            </a>
                        </p>
                    <?php else: ?>
                        <p class="description">
                            <?php esc_html_e('Enter your Client ID and Secret, save settings, then click Connect.', 'smart-seo-fixer'); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div style="margin-top: 12px; padding: 12px 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px;">
                        <p style="margin: 0 0 8px; font-weight: 600; color: #1e40af;">
                            <span class="dashicons dashicons-info" style="font-size: 16px;"></span>
                            <?php esc_html_e('How to get your credentials:', 'smart-seo-fixer'); ?>
                        </p>
                        <ol style="margin: 0; padding-left: 20px; color: #1e3a5f; font-size: 13px;">
                            <li><?php printf(
                                __('Go to %s', 'smart-seo-fixer'),
                                '<a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>'
                            ); ?></li>
                            <li><?php esc_html_e('Create a project (or select existing)', 'smart-seo-fixer'); ?></li>
                            <li><?php esc_html_e('Enable "Google Search Console API"', 'smart-seo-fixer'); ?></li>
                            <li><?php esc_html_e('Create OAuth 2.0 credentials (Web application type)', 'smart-seo-fixer'); ?></li>
                            <li><?php printf(
                                __('Add this as Authorized redirect URI: %s', 'smart-seo-fixer'),
                                '<code>' . esc_html(admin_url('admin.php?page=smart-seo-fixer-settings')) . '</code>'
                            ); ?></li>
                            <li><?php esc_html_e('Copy the Client ID and Client Secret here', 'smart-seo-fixer'); ?></li>
                        </ol>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- General Settings -->
        <div class="ssf-card">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e('General Settings', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="title_separator"><?php esc_html_e('Title Separator', 'smart-seo-fixer'); ?></label>
                        </th>
                        <td>
                            <select name="title_separator" id="title_separator">
                                <option value="|" <?php selected($title_separator, '|'); ?>>|</option>
                                <option value="-" <?php selected($title_separator, '-'); ?>>-</option>
                                <option value="–" <?php selected($title_separator, '–'); ?>>–</option>
                                <option value="—" <?php selected($title_separator, '—'); ?>>—</option>
                                <option value="•" <?php selected($title_separator, '•'); ?>>•</option>
                                <option value="»" <?php selected($title_separator, '»'); ?>>»</option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Separator used between title parts.', 'smart-seo-fixer'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Post Types', 'smart-seo-fixer'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <?php foreach ($available_post_types as $type): ?>
                                <label>
                                    <input type="checkbox" 
                                           name="post_types[]" 
                                           value="<?php echo esc_attr($type->name); ?>"
                                           <?php checked(in_array($type->name, $post_types)); ?>>
                                    <?php echo esc_html($type->labels->name); ?>
                                </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="description">
                                <?php esc_html_e('Select which post types to enable SEO features for.', 'smart-seo-fixer'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Homepage SEO -->
        <div class="ssf-card">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-admin-home"></span>
                    <?php esc_html_e('Homepage SEO', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="homepage_title"><?php esc_html_e('Homepage Title', 'smart-seo-fixer'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="homepage_title" 
                                   id="homepage_title" 
                                   value="<?php echo esc_attr($homepage_title); ?>" 
                                   class="large-text">
                            <p class="description">
                                <?php esc_html_e('Custom title for the homepage. Leave empty to use default.', 'smart-seo-fixer'); ?>
                                <span class="ssf-char-count">
                                    <span id="homepage-title-count"><?php echo strlen($homepage_title); ?></span>/60
                                </span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="homepage_description"><?php esc_html_e('Homepage Description', 'smart-seo-fixer'); ?></label>
                        </th>
                        <td>
                            <textarea name="homepage_description" 
                                      id="homepage_description" 
                                      rows="3" 
                                      class="large-text"><?php echo esc_textarea($homepage_description); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Meta description for the homepage.', 'smart-seo-fixer'); ?>
                                <span class="ssf-char-count">
                                    <span id="homepage-desc-count"><?php echo strlen($homepage_description); ?></span>/160
                                </span>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Features -->
        <div class="ssf-card">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php esc_html_e('Features', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Schema Markup', 'smart-seo-fixer'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="enable_schema" 
                                       value="1" 
                                       <?php checked($enable_schema, true); ?>>
                                <?php esc_html_e('Enable JSON-LD schema markup', 'smart-seo-fixer'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Automatically add structured data to help search engines understand your content.', 'smart-seo-fixer'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Attachment Pages', 'smart-seo-fixer'); ?></th>
                        <td>
                            <select name="redirect_attachments" id="redirect_attachments">
                                <option value="" <?php selected($redirect_attachments, ''); ?>>
                                    <?php esc_html_e('Disabled — keep attachment pages as-is', 'smart-seo-fixer'); ?>
                                </option>
                                <option value="parent" <?php selected($redirect_attachments, 'parent'); ?>>
                                    <?php esc_html_e('Redirect to parent post/page (recommended)', 'smart-seo-fixer'); ?>
                                </option>
                                <option value="file" <?php selected($redirect_attachments, 'file'); ?>>
                                    <?php esc_html_e('Redirect to the media file URL', 'smart-seo-fixer'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('WordPress creates an "attachment page" for every uploaded image/file. These pages show a single media item on a blog-like template and are bad for SEO. Redirecting them (301) to the parent post prevents thin content and wasted crawl budget.', 'smart-seo-fixer'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('XML Sitemap', 'smart-seo-fixer'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="enable_sitemap" 
                                       value="1" 
                                       <?php checked($enable_sitemap, true); ?>>
                                <?php esc_html_e('Enable XML sitemap', 'smart-seo-fixer'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Generate an XML sitemap for search engines.', 'smart-seo-fixer'); ?>
                                <?php if ($enable_sitemap): ?>
                                <a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank">
                                    <?php esc_html_e('View Sitemap', 'smart-seo-fixer'); ?>
                                </a>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Auto Meta Generation', 'smart-seo-fixer'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="auto_meta" 
                                       value="1" 
                                       <?php checked($auto_meta, true); ?>>
                                <?php esc_html_e('Auto-generate SEO titles, descriptions & keywords on publish/update', 'smart-seo-fixer'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, AI will auto-generate missing SEO title, meta description, and focus keyword every time a post is published or updated. Existing values are never overwritten.', 'smart-seo-fixer'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Background SEO Generation', 'smart-seo-fixer'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="background_seo_cron" 
                                       value="1" 
                                       <?php checked(Smart_SEO_Fixer::get_option('background_seo_cron', true), true); ?>>
                                <?php esc_html_e('Automatically fill missing SEO in the background (twice daily)', 'smart-seo-fixer'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('A background cron runs twice daily to catch any posts that still lack AI-generated titles or descriptions. Processes up to 10 posts per run to respect API rate limits.', 'smart-seo-fixer'); ?>
                            </p>
                            <?php
                            $cron_next = wp_next_scheduled('ssf_cron_generate_missing_seo');
                            $cron_last = get_option('ssf_cron_last_run', null);
                            if ($cron_next): ?>
                                <p class="description" style="margin-top: 6px; color: #059669;">
                                    <span class="dashicons dashicons-clock" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
                                    <?php printf(
                                        esc_html__('Next run: %s', 'smart-seo-fixer'),
                                        esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $cron_next))
                                    ); ?>
                                    <?php if ($cron_last): ?>
                                        &nbsp;|&nbsp;
                                        <?php printf(
                                            esc_html__('Last run: %s (%d generated)', 'smart-seo-fixer'),
                                            esc_html($cron_last['time'] ?? '—'),
                                            intval($cron_last['generated'] ?? 0)
                                        ); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Auto Alt Text', 'smart-seo-fixer'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="auto_alt_text" 
                                       value="1" 
                                       <?php checked($auto_alt_text, true); ?>>
                                <?php esc_html_e('Automatically generate alt text for new image uploads', 'smart-seo-fixer'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, alt text is auto-generated from the image filename whenever you upload a new image. Existing images with alt text are not changed.', 'smart-seo-fixer'); ?>
                            </p>
                            <div style="margin-top: 12px; padding: 14px; background: #f6f7f7; border: 1px solid #ddd; border-radius: 6px;">
                                <strong><?php esc_html_e('Bulk Generate Alt Text', 'smart-seo-fixer'); ?></strong>
                                <p class="description" style="margin: 6px 0 10px;">
                                    <?php esc_html_e('Generate alt text from filenames for all existing images that are missing it. Processes 100 images at a time.', 'smart-seo-fixer'); ?>
                                </p>
                                <button type="button" class="button button-secondary" id="ssf-bulk-alt-btn">
                                    <span class="dashicons dashicons-images-alt2" style="margin-top: 4px;"></span>
                                    <?php esc_html_e('Generate Missing Alt Text', 'smart-seo-fixer'); ?>
                                </button>
                                <span id="ssf-bulk-alt-status" style="margin-left: 10px;"></span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Disable Other SEO Plugins Output', 'smart-seo-fixer'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="disable_other_seo_output" 
                                       value="1" 
                                       <?php checked($disable_other_seo_output, true); ?>>
                                <?php esc_html_e('Prevent other SEO plugins from outputting duplicate meta tags', 'smart-seo-fixer'); ?>
                            </label>
                            <?php if (!empty($conflicting_plugins)): ?>
                            <div style="margin-top: 8px; padding: 10px 14px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px;">
                                <strong style="color: #856404;">
                                    <span class="dashicons dashicons-warning" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                    <?php esc_html_e('Conflicting plugin detected:', 'smart-seo-fixer'); ?>
                                </strong>
                                <span style="color: #856404;"><?php echo esc_html(implode(', ', $conflicting_plugins)); ?></span>
                                <p class="description" style="margin: 5px 0 0; color: #856404;">
                                    <?php esc_html_e('This will suppress ALL meta output from the plugin above. Run', 'smart-seo-fixer'); ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-migration')); ?>"><?php esc_html_e('Migration', 'smart-seo-fixer'); ?></a>
                                    <?php esc_html_e('first to copy your existing SEO data into SSF, or those pages will show SSF\'s auto-generated fallback instead of your custom meta.', 'smart-seo-fixer'); ?>
                                </p>
                            </div>
                            <?php elseif ($disable_other_seo_output): ?>
                            <div style="margin-top: 8px; padding: 10px 14px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 6px;">
                                <strong style="color: #0c5460;">
                                    <span class="dashicons dashicons-info" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                    <?php esc_html_e('Other SEO plugin output is suppressed.', 'smart-seo-fixer'); ?>
                                </strong>
                                <p class="description" style="margin: 5px 0 0; color: #0c5460;">
                                    <?php esc_html_e('SSF will use your saved SSF meta fields for each page. If any pages still have data in Yoast/Rank Math that hasn\'t been imported, use the', 'smart-seo-fixer'); ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-migration')); ?>"><?php esc_html_e('Migration page', 'smart-seo-fixer'); ?></a>
                                    <?php esc_html_e('to import it. SSF will automatically fall back to those values for any pages not yet migrated.', 'smart-seo-fixer'); ?>
                                </p>
                            </div>
                            <?php else: ?>
                            <p class="description">
                                <?php esc_html_e('Suppresses meta output from Yoast SEO, Rank Math, All in One SEO, The SEO Framework, and SEOPress. Enable this if you see duplicate meta descriptions in your page source.', 'smart-seo-fixer'); ?>
                                <?php esc_html_e('Always run Migration first to copy your existing SEO data into SSF before enabling this.', 'smart-seo-fixer'); ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-migration')); ?>"><?php esc_html_e('Go to Migration →', 'smart-seo-fixer'); ?></a>
                            </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="github_token"><?php esc_html_e('GitHub Token (Auto-Updates)', 'smart-seo-fixer'); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="github_token" 
                                   id="github_token" 
                                   value="<?php echo esc_attr($github_token); ?>" 
                                   class="regular-text"
                                   autocomplete="off"
                                   placeholder="ghp_xxxxxxxxxxxxxxxxxxxx">
                            <button type="button" class="button" id="toggle-gh-token">
                                <?php esc_html_e('Show', 'smart-seo-fixer'); ?>
                            </button>
                            <p class="description">
                                <?php esc_html_e('Required for auto-updates from a private GitHub repository.', 'smart-seo-fixer'); ?>
                                <a href="https://github.com/settings/tokens/new?scopes=repo&description=Smart+SEO+Fixer+Updater" target="_blank"><?php esc_html_e('Generate a token here', 'smart-seo-fixer'); ?></a>
                                <?php esc_html_e('(select "repo" scope).', 'smart-seo-fixer'); ?>
                            </p>
                            <?php if (!empty($github_token)): ?>
                            <div style="margin-top:6px;">
                                <span style="color:#059669;font-size:13px;">&#10003; <?php esc_html_e('Token saved', 'smart-seo-fixer'); ?></span>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <p class="submit">
            <button type="submit" class="button button-primary button-large" id="save-settings">
                <?php esc_html_e('Save Settings', 'smart-seo-fixer'); ?>
            </button>
            <span class="spinner" style="float: none; margin-top: 0;"></span>
            <span class="ssf-save-status" id="save-status"></span>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
        // Toggle Bedrock secret visibility
    $('#toggle-bedrock-secret').on('click', function() {
        var $input = $('#bedrock_secret_key');
        var $btn = $(this);
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $btn.text('<?php esc_html_e('Hide', 'smart-seo-fixer'); ?>');
        } else {
            $input.attr('type', 'password');
            $btn.text('<?php esc_html_e('Show', 'smart-seo-fixer'); ?>');
        }
    });

    // Enable Test Connection button when credentials are typed
    $('#bedrock_access_key, #bedrock_secret_key').on('input', function() {
        var hasKey    = $('#bedrock_access_key').val().trim().length > 0;
        var hasSecret = $('#bedrock_secret_key').val().trim().length > 0;
        $('#ssf-test-bedrock').prop('disabled', !(hasKey && hasSecret));
    });

    // Test Bedrock Connection
    $('#ssf-test-bedrock').on('click', function() {
        var $btn    = $(this).prop('disabled', true);
        var $result = $('#ssf-bedrock-test-result');
        var $status = $('#ssf-bedrock-status');
        var $dot    = $('#ssf-bedrock-status-dot');
        var $text   = $('#ssf-bedrock-status-text');

        $btn.find('.dashicons').removeClass('dashicons-controls-play').addClass('dashicons-update ssf-spin');
        $result.hide();

        $.post(ssfAdmin.ajax_url, {
            action:  'ssf_test_bedrock',
            nonce:   ssfAdmin.nonce,
            access_key: $('#bedrock_access_key').val(),
            secret_key: $('#bedrock_secret_key').val(),
            region:     $('#bedrock_region').val(),
            model:      'us.anthropic.claude-sonnet-4-6'
        }, function(r) {
            $btn.prop('disabled', false);
            $btn.find('.dashicons').removeClass('dashicons-update ssf-spin').addClass('dashicons-controls-play');

            if (r.success) {
                $result.css({background:'#dcfce7', border:'1px solid #bbf7d0', color:'#166534'})
                       .html('<strong>✅ Connected!</strong> Model responded: <em>' + $('<div>').text(r.data.reply).html() + '</em>')
                       .show();
                $status.css({background:'#dcfce7', color:'#166534', border:'1px solid #bbf7d0'});
                $dot.css('background','#16a34a');
                $text.text('<?php esc_html_e('Connected', 'smart-seo-fixer'); ?>');
            } else {
                var msg    = r.data.message || 'Unknown error';
                var detail = msg;

                // Friendly guidance for the most common errors
                if (msg.toLowerCase().indexOf('model identifier is invalid') !== -1 || msg.toLowerCase().indexOf('validationexception') !== -1) {
                    detail = msg + '<br><br><strong>Make sure your AWS Region is set to us-east-1 or us-west-2 (required for Claude Sonnet 4.6).</strong>';
                } else if (msg.toLowerCase().indexOf('signature') !== -1) {
                    detail = msg + '<br><br><strong>Check that your Secret Access Key is correct.</strong>';
                } else if (msg.toLowerCase().indexOf('credentials') !== -1 || msg.toLowerCase().indexOf('access denied') !== -1) {
                    detail = msg + '<br><br><strong>Your IAM user does not have Bedrock permission. Attach AmazonBedrockFullAccess to the IAM user.</strong>';
                }

                $result.css({background:'#fef2f2', border:'1px solid #fecaca', color:'#991b1b'})
                       .html('<strong>❌ Failed:</strong> ' + detail)
                       .show();
                $status.css({background:'#fef2f2', color:'#991b1b', border:'1px solid #fecaca'});
                $dot.css('background','#dc2626');
                $text.text('<?php esc_html_e('Connection failed', 'smart-seo-fixer'); ?>');
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            $btn.find('.dashicons').removeClass('dashicons-update ssf-spin').addClass('dashicons-controls-play');
            $result.css({background:'#fef2f2', border:'1px solid #fecaca', color:'#991b1b'})
                   .html('<strong>❌ Request failed.</strong> Check your server error log.')
                   .show();
        });
    });
    
    // Toggle GitHub token visibility
    $('#toggle-gh-token').on('click', function() {
        var $input = $('#github_token');
        var $btn = $(this);
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $btn.text('<?php esc_html_e('Hide', 'smart-seo-fixer'); ?>');
        } else {
            $input.attr('type', 'password');
            $btn.text('<?php esc_html_e('Show', 'smart-seo-fixer'); ?>');
        }
    });
    
    // Character counters
    $('#homepage_title').on('input', function() {
        $('#homepage-title-count').text($(this).val().length);
    });
    
    $('#homepage_description').on('input', function() {
        $('#homepage-desc-count').text($(this).val().length);
    });
    
    // GSC Refresh Sites
    $('#ssf-gsc-refresh-sites').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        var $status = $('#ssf-gsc-refresh-status').text('Loading sites from Google...');
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_gsc_refresh_sites',
            nonce: ssfAdmin.nonce
        }, function(r) {
            if (r.success && r.data.sites && r.data.sites.length) {
                var html = '<select name="gsc_site_url" id="gsc_site_url" style="min-width:300px;">';
                html += '<option value="">— Select a site —</option>';
                r.data.sites.forEach(function(s) {
                    html += '<option value="' + s.siteUrl + '">' + s.siteUrl + '</option>';
                });
                html += '</select>';
                var $target = $('#ssf-gsc-sites-dropdown');
                if ($target.length) {
                    $target.html(html).show();
                    $status.text(r.data.sites.length + ' site(s) found. Select one and save settings.');
                } else {
                    $('select#gsc_site_url').replaceWith(html);
                    $status.text('Refreshed! ' + r.data.sites.length + ' site(s) found.');
                }
            } else {
                $status.text(r.data?.message || 'No sites found. Check your GSC account.');
            }
            $btn.prop('disabled', false);
        }).fail(function() {
            $status.text('Network error. Please try again.');
            $btn.prop('disabled', false);
        });
    });

    // GSC Disconnect
    $('#ssf-gsc-disconnect').on('click', function() {
        if (!confirm('<?php esc_html_e('Disconnect Google Search Console?', 'smart-seo-fixer'); ?>')) return;
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e('Disconnecting...', 'smart-seo-fixer'); ?>');
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_gsc_disconnect',
            nonce: ssfAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message || 'Error');
                $btn.prop('disabled', false).text('<?php esc_html_e('Disconnect', 'smart-seo-fixer'); ?>');
            }
        });
    });
    
    // Save settings
    $('#ssf-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $('#save-settings');
        var $spinner = $btn.siblings('.spinner');
        var $status = $('#save-status');
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $status.text('');
        
        var formData = $(this).serializeArray();
        formData.push({name: 'action', value: 'ssf_save_settings'});
        formData.push({name: 'nonce', value: ssfAdmin.nonce});
        
        $.post(ssfAdmin.ajax_url, formData, function(response) {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
            
            if (response.success) {
                $status.html('<span class="dashicons dashicons-yes" style="color: #46b450;"></span> ' + response.data.message);
            } else {
                $status.html('<span class="dashicons dashicons-no" style="color: #dc3232;"></span> ' + response.data.message);
            }
            
            setTimeout(function() {
                $status.text('');
            }, 3000);
        });
    });
});
</script>

