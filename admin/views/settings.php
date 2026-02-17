<?php
/**
 * Settings View
 */

if (!defined('ABSPATH')) {
    exit;
}

$openai_api_key = Smart_SEO_Fixer::get_option('openai_api_key');
$openai_model = Smart_SEO_Fixer::get_option('openai_model', 'gpt-4o-mini');
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
        $gsc_sites_result = $gsc_client->get_sites();
        if (!is_wp_error($gsc_sites_result)) {
            $gsc_sites = $gsc_sites_result;
        }
    }
}
$auto_meta = Smart_SEO_Fixer::get_option('auto_meta');
$auto_alt_text = Smart_SEO_Fixer::get_option('auto_alt_text');
$enable_schema = Smart_SEO_Fixer::get_option('enable_schema', true);
$enable_sitemap = Smart_SEO_Fixer::get_option('enable_sitemap', true);
$disable_other_seo_output = Smart_SEO_Fixer::get_option('disable_other_seo_output', false);
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
        <!-- OpenAI Settings -->
        <div class="ssf-card">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-cloud"></span>
                    <?php esc_html_e('OpenAI Configuration', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="openai_api_key"><?php esc_html_e('API Key', 'smart-seo-fixer'); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="openai_api_key" 
                                   id="openai_api_key" 
                                   value="<?php echo esc_attr($openai_api_key); ?>" 
                                   class="regular-text"
                                   autocomplete="off">
                            <button type="button" class="button" id="toggle-api-key">
                                <?php esc_html_e('Show', 'smart-seo-fixer'); ?>
                            </button>
                            <p class="description">
                                <?php esc_html_e('Enter your OpenAI API key.', 'smart-seo-fixer'); ?>
                                <a href="https://platform.openai.com/api-keys" target="_blank">
                                    <?php esc_html_e('Get your API key', 'smart-seo-fixer'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="openai_model"><?php esc_html_e('Model', 'smart-seo-fixer'); ?></label>
                        </th>
                        <td>
                            <select name="openai_model" id="openai_model">
                                <option value="gpt-4o-mini" <?php selected($openai_model, 'gpt-4o-mini'); ?>>
                                    GPT-4o Mini (<?php esc_html_e('Recommended - Fast & Affordable', 'smart-seo-fixer'); ?>)
                                </option>
                                <option value="gpt-4o" <?php selected($openai_model, 'gpt-4o'); ?>>
                                    GPT-4o (<?php esc_html_e('Most Capable', 'smart-seo-fixer'); ?>)
                                </option>
                                <option value="gpt-4-turbo" <?php selected($openai_model, 'gpt-4-turbo'); ?>>
                                    GPT-4 Turbo
                                </option>
                                <option value="gpt-3.5-turbo" <?php selected($openai_model, 'gpt-3.5-turbo'); ?>>
                                    GPT-3.5 Turbo (<?php esc_html_e('Budget Option', 'smart-seo-fixer'); ?>)
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Select the AI model to use for content generation.', 'smart-seo-fixer'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
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
                        <?php if (!empty($gsc_sites) && count($gsc_sites) > 1): ?>
                        <tr>
                            <th scope="row">
                                <label for="gsc_site_url"><?php esc_html_e('Site Property', 'smart-seo-fixer'); ?></label>
                            </th>
                            <td>
                                <select name="gsc_site_url" id="gsc_site_url">
                                    <?php foreach ($gsc_sites as $site): ?>
                                        <option value="<?php echo esc_attr($site['siteUrl']); ?>" <?php selected($gsc_site_url, $site['siteUrl']); ?>>
                                            <?php echo esc_html($site['siteUrl']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Select which site property to use.', 'smart-seo-fixer'); ?>
                                </p>
                            </td>
                        </tr>
                        <?php endif; ?>
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
                                <?php esc_html_e('Automatically generate alt text for images using AI', 'smart-seo-fixer'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, AI will suggest alt text for images missing it.', 'smart-seo-fixer'); ?>
                            </p>
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
                                    <?php esc_html_e('Enable this setting to prevent duplicate meta descriptions, Open Graph tags, canonical URLs, and schema markup. You should also migrate your SEO data first from the', 'smart-seo-fixer'); ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-migration')); ?>"><?php esc_html_e('Migration page', 'smart-seo-fixer'); ?></a>.
                                </p>
                            </div>
                            <?php else: ?>
                            <p class="description">
                                <?php esc_html_e('Suppresses meta output from Yoast SEO, Rank Math, All in One SEO, The SEO Framework, and SEOPress. Enable this if you see duplicate meta descriptions in your page source.', 'smart-seo-fixer'); ?>
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
    // Toggle API key visibility
    $('#toggle-api-key').on('click', function() {
        var $input = $('#openai_api_key');
        var $btn = $(this);
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $btn.text('<?php esc_html_e('Hide', 'smart-seo-fixer'); ?>');
        } else {
            $input.attr('type', 'password');
            $btn.text('<?php esc_html_e('Show', 'smart-seo-fixer'); ?>');
        }
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

