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

