<?php
/**
 * Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ssf-wrap">
    <h1 class="ssf-page-title">
        <span class="dashicons dashicons-chart-line"></span>
        <?php esc_html_e('Smart SEO Fixer Dashboard', 'smart-seo-fixer'); ?>
        <span class="ssf-version">v<?php echo esc_html(SSF_VERSION); ?></span>
    </h1>
    
    <?php if (!Smart_SEO_Fixer::get_option('openai_api_key')): ?>
    <div class="ssf-notice ssf-notice-warning">
        <p>
            <strong><?php esc_html_e('API Key Required:', 'smart-seo-fixer'); ?></strong>
            <?php esc_html_e('Add your OpenAI API key in', 'smart-seo-fixer'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-settings')); ?>">
                <?php esc_html_e('Settings', 'smart-seo-fixer'); ?>
            </a>
            <?php esc_html_e('to enable AI-powered features.', 'smart-seo-fixer'); ?>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="ssf-dashboard" id="ssf-dashboard">
        <!-- Stats Cards -->
        <div class="ssf-stats-grid">
            <div class="ssf-stat-card">
                <div class="ssf-stat-icon ssf-stat-score">
                    <span class="dashicons dashicons-awards"></span>
                </div>
                <div class="ssf-stat-content">
                    <span class="ssf-stat-value" id="stat-avg-score">—</span>
                    <span class="ssf-stat-label"><?php esc_html_e('Average SEO Score', 'smart-seo-fixer'); ?></span>
                </div>
            </div>
            
            <div class="ssf-stat-card">
                <div class="ssf-stat-icon ssf-stat-good">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="ssf-stat-content">
                    <span class="ssf-stat-value" id="stat-good">—</span>
                    <span class="ssf-stat-label"><?php esc_html_e('Good (80+)', 'smart-seo-fixer'); ?></span>
                </div>
            </div>
            
            <div class="ssf-stat-card">
                <div class="ssf-stat-icon ssf-stat-ok">
                    <span class="dashicons dashicons-marker"></span>
                </div>
                <div class="ssf-stat-content">
                    <span class="ssf-stat-value" id="stat-ok">—</span>
                    <span class="ssf-stat-label"><?php esc_html_e('OK (60-79)', 'smart-seo-fixer'); ?></span>
                </div>
            </div>
            
            <div class="ssf-stat-card">
                <div class="ssf-stat-icon ssf-stat-poor">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <div class="ssf-stat-content">
                    <span class="ssf-stat-value" id="stat-poor">—</span>
                    <span class="ssf-stat-label"><?php esc_html_e('Needs Work (<60)', 'smart-seo-fixer'); ?></span>
                </div>
            </div>
            
            <div class="ssf-stat-card">
                <div class="ssf-stat-icon ssf-stat-unanalyzed">
                    <span class="dashicons dashicons-search"></span>
                </div>
                <div class="ssf-stat-content">
                    <span class="ssf-stat-value" id="stat-unanalyzed">—</span>
                    <span class="ssf-stat-label"><?php esc_html_e('Not Analyzed', 'smart-seo-fixer'); ?></span>
                </div>
            </div>
            
            <div class="ssf-stat-card ssf-stat-card-missing" id="stat-card-missing" style="display:none;">
                <div class="ssf-stat-icon ssf-stat-missing">
                    <span class="dashicons dashicons-editor-help"></span>
                </div>
                <div class="ssf-stat-content">
                    <span class="ssf-stat-value" id="stat-missing-titles">—</span>
                    <span class="ssf-stat-label"><?php esc_html_e('Missing AI Titles', 'smart-seo-fixer'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Missing SEO Alert Banner -->
        <div class="ssf-missing-seo-banner" id="missing-seo-banner" style="display:none;">
            <div class="ssf-banner-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="ssf-banner-content">
                <strong id="missing-banner-title"><?php esc_html_e('Posts missing AI-generated SEO', 'smart-seo-fixer'); ?></strong>
                <p id="missing-banner-desc"></p>
            </div>
            <div class="ssf-banner-actions">
                <button type="button" class="button button-primary" id="quick-generate-all-btn">
                    <span class="dashicons dashicons-superhero-alt"></span>
                    <?php esc_html_e('Generate All Missing SEO Now', 'smart-seo-fixer'); ?>
                </button>
            </div>
        </div>
        
        <!-- Main Content Grid -->
        <div class="ssf-content-grid">
            <!-- Needs Attention -->
            <div class="ssf-card ssf-card-attention">
                <div class="ssf-card-header">
                    <h2>
                        <span class="dashicons dashicons-flag"></span>
                        <?php esc_html_e('Posts Needing Attention', 'smart-seo-fixer'); ?>
                    </h2>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-posts&filter=poor')); ?>" class="ssf-link">
                        <?php esc_html_e('View All', 'smart-seo-fixer'); ?>
                    </a>
                </div>
                <div class="ssf-card-body">
                    <div class="ssf-post-list" id="needs-attention-list">
                        <div class="ssf-loading">
                            <span class="spinner is-active"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recently Analyzed -->
            <div class="ssf-card ssf-card-recent">
                <div class="ssf-card-header">
                    <h2>
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e('Recently Analyzed', 'smart-seo-fixer'); ?>
                    </h2>
                </div>
                <div class="ssf-card-body">
                    <div class="ssf-post-list" id="recent-list">
                        <div class="ssf-loading">
                            <span class="spinner is-active"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="ssf-card ssf-card-actions">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php esc_html_e('Quick Actions', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <div class="ssf-actions-grid">
                    <button type="button" class="ssf-action-btn" id="analyze-all-btn">
                        <span class="dashicons dashicons-search"></span>
                        <span class="ssf-action-text">
                            <strong><?php esc_html_e('Analyze Unanalyzed Posts', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Scan only posts not yet analyzed', 'smart-seo-fixer'); ?></small>
                        </span>
                    </button>
                    
                    <button type="button" class="ssf-action-btn" id="reanalyze-all-btn">
                        <span class="dashicons dashicons-update"></span>
                        <span class="ssf-action-text">
                            <strong><?php esc_html_e('Re-Analyze All Posts', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Re-scan every published post and update scores', 'smart-seo-fixer'); ?></small>
                        </span>
                    </button>
                    
                    <button type="button" class="ssf-action-btn" id="bulk-fix-btn">
                        <span class="dashicons dashicons-superhero-alt"></span>
                        <span class="ssf-action-text">
                            <strong><?php esc_html_e('Bulk AI Fix', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Preview & fix posts missing SEO data', 'smart-seo-fixer'); ?></small>
                        </span>
                    </button>
                    
                    <button type="button" class="ssf-action-btn" id="regen-schemas-btn">
                        <span class="dashicons dashicons-shortcode"></span>
                        <span class="ssf-action-text">
                            <strong><?php esc_html_e('Regenerate Custom Schemas', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Re-run AI schema for all posts with custom markup', 'smart-seo-fixer'); ?></small>
                        </span>
                    </button>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-settings')); ?>" class="ssf-action-btn">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <span class="ssf-action-text">
                            <strong><?php esc_html_e('Settings', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Configure plugin options', 'smart-seo-fixer'); ?></small>
                        </span>
                    </a>
                    
                    <a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank" class="ssf-action-btn">
                        <span class="dashicons dashicons-networking"></span>
                        <span class="ssf-action-text">
                            <strong><?php esc_html_e('View Sitemap', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Open XML sitemap', 'smart-seo-fixer'); ?></small>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bulk AI Fix Modal (Step 1: Configure + Preview, Step 2: Run) -->
    <div class="ssf-modal ssf-modal-large" id="bulk-fix-modal" style="display: none;">
        <div class="ssf-modal-content ssf-modal-wide">
            <div class="ssf-modal-header">
                <h3 id="bulk-modal-title"><?php esc_html_e('Bulk AI Fix', 'smart-seo-fixer'); ?></h3>
                <button type="button" class="ssf-modal-close" onclick="jQuery('#bulk-fix-modal').hide();">&times;</button>
            </div>
            
            <!-- Step 1: Configure & Preview -->
            <div id="bulk-step-config" class="ssf-modal-body">
                <div class="ssf-bulk-config-row">
                    <!-- Left: Options -->
                    <div class="ssf-bulk-config-left">
                        <div class="ssf-bulk-options">
                            <h4><?php esc_html_e('What to generate:', 'smart-seo-fixer'); ?></h4>
                            <label class="ssf-checkbox-option">
                                <input type="checkbox" name="bulk_opt_title" checked>
                                <span class="ssf-checkbox-label">
                                    <strong><?php esc_html_e('SEO Titles', 'smart-seo-fixer'); ?></strong>
                                    <small><?php esc_html_e('Optimized titles (50-60 chars)', 'smart-seo-fixer'); ?></small>
                                </span>
                            </label>
                            <label class="ssf-checkbox-option">
                                <input type="checkbox" name="bulk_opt_desc" checked>
                                <span class="ssf-checkbox-label">
                                    <strong><?php esc_html_e('Meta Descriptions', 'smart-seo-fixer'); ?></strong>
                                    <small><?php esc_html_e('Compelling descriptions (150-160 chars)', 'smart-seo-fixer'); ?></small>
                                </span>
                            </label>
                            <label class="ssf-checkbox-option">
                                <input type="checkbox" name="bulk_opt_keywords" checked>
                                <span class="ssf-checkbox-label">
                                    <strong><?php esc_html_e('Focus Keywords', 'smart-seo-fixer'); ?></strong>
                                    <small><?php esc_html_e('AI-suggested focus keywords', 'smart-seo-fixer'); ?></small>
                                </span>
                            </label>
                        </div>
                        
                        <div class="ssf-bulk-options">
                            <h4><?php esc_html_e('Apply to:', 'smart-seo-fixer'); ?></h4>
                            <label class="ssf-radio-option">
                                <input type="radio" name="bulk_apply_to" value="missing" checked>
                                <span><?php esc_html_e('Only posts with MISSING SEO data (safe)', 'smart-seo-fixer'); ?></span>
                            </label>
                            <label class="ssf-radio-option">
                                <input type="radio" name="bulk_apply_to" value="poor">
                                <span><?php esc_html_e('Posts with score below 60', 'smart-seo-fixer'); ?></span>
                            </label>
                            <label class="ssf-radio-option ssf-option-danger">
                                <input type="radio" name="bulk_apply_to" value="all">
                                <span><?php esc_html_e('ALL posts (overwrite everything)', 'smart-seo-fixer'); ?></span>
                            </label>
                        </div>
                        
                        <button type="button" class="button button-primary button-hero" id="load-preview-btn" style="width:100%; margin-top: 10px;">
                            <span class="dashicons dashicons-visibility" style="margin-top:4px;"></span>
                            <?php esc_html_e('Load Preview — Show Affected Posts', 'smart-seo-fixer'); ?>
                        </button>
                    </div>
                    
                    <!-- Right: Preview List -->
                    <div class="ssf-bulk-config-right">
                        <div class="ssf-preview-header">
                            <h4>
                                <span class="dashicons dashicons-list-view"></span>
                                <?php esc_html_e('Posts That Will Be Fixed', 'smart-seo-fixer'); ?>
                                <span class="ssf-preview-count" id="preview-count"></span>
                            </h4>
                            <label class="ssf-select-all-label" id="select-all-wrap" style="display:none;">
                                <input type="checkbox" id="preview-select-all" checked>
                                <?php esc_html_e('Select All', 'smart-seo-fixer'); ?>
                            </label>
                        </div>
                        <div class="ssf-preview-list" id="preview-list">
                            <div class="ssf-preview-empty">
                                <span class="dashicons dashicons-arrow-left-alt"></span>
                                <p><?php esc_html_e('Choose your options on the left, then click "Load Preview" to see which posts will be affected.', 'smart-seo-fixer'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Progress (replaces config when running) -->
            <div id="bulk-step-progress" class="ssf-modal-body" style="display:none;">
                <div class="ssf-progress-bar">
                    <div class="ssf-progress-fill" id="bulk-progress-fill" style="width: 0%"></div>
                </div>
                <p class="ssf-progress-text" id="bulk-progress-text">0%</p>
                <div class="ssf-progress-log" id="bulk-progress-log"></div>
            </div>
            
            <div class="ssf-modal-footer" id="bulk-modal-footer">
                <button type="button" class="button" onclick="jQuery('#bulk-fix-modal').hide();">
                    <?php esc_html_e('Cancel', 'smart-seo-fixer'); ?>
                </button>
                <button type="button" class="button button-primary" id="start-bulk-fix" disabled>
                    <span class="dashicons dashicons-superhero-alt" style="margin-top:4px;"></span>
                    <?php esc_html_e('Fix All Selected Posts', 'smart-seo-fixer'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Progress Modal (for analyze and schema actions) -->
    <div class="ssf-modal" id="progress-modal" style="display: none;">
        <div class="ssf-modal-content">
            <div class="ssf-modal-header">
                <h3 id="progress-title"><?php esc_html_e('Processing...', 'smart-seo-fixer'); ?></h3>
            </div>
            <div class="ssf-modal-body">
                <div class="ssf-progress-bar">
                    <div class="ssf-progress-fill" id="progress-fill" style="width: 0%"></div>
                </div>
                <p class="ssf-progress-text" id="progress-text">0%</p>
                <div class="ssf-progress-log" id="progress-log"></div>
            </div>
        </div>
    </div>
</div>

<style>
.ssf-modal-close { position: absolute; right: 15px; top: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
.ssf-modal-close:hover { color: #333; }

/* Bulk options */
.ssf-bulk-options { margin: 0 0 16px 0; }
.ssf-bulk-options h4 { margin: 0 0 8px 0; font-size: 13px; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; }
.ssf-checkbox-option, .ssf-radio-option { display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; background: #f9fafb; border-radius: 6px; margin-bottom: 6px; cursor: pointer; transition: background 0.2s; }
.ssf-checkbox-option:hover, .ssf-radio-option:hover { background: #f3f4f6; }
.ssf-checkbox-option input, .ssf-radio-option input { margin-top: 3px; }
.ssf-checkbox-label { display: flex; flex-direction: column; }
.ssf-checkbox-label strong { color: #1f2937; font-size: 13px; }
.ssf-checkbox-label small { color: #6b7280; font-size: 11px; }
.ssf-option-danger { border: 1px solid #fecaca; background: #fef2f2; }
.ssf-option-danger:hover { background: #fee2e2; }

/* Wide modal for bulk fix */
.ssf-modal-wide { max-width: 960px !important; width: 95vw !important; }
.ssf-bulk-config-row { display: flex; gap: 24px; min-height: 400px; }
.ssf-bulk-config-left { flex: 0 0 320px; }
.ssf-bulk-config-right { flex: 1; display: flex; flex-direction: column; min-width: 0; }

/* Preview header */
.ssf-preview-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.ssf-preview-header h4 { margin: 0; font-size: 14px; color: #1f2937; display: flex; align-items: center; gap: 6px; }
.ssf-preview-header h4 .dashicons { color: #6b7280; }
.ssf-preview-count { background: #2563eb; color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 10px; font-weight: 600; }
.ssf-select-all-label { font-size: 13px; color: #6b7280; cursor: pointer; display: flex; align-items: center; gap: 4px; }

/* Preview list */
.ssf-preview-list { flex: 1; border: 1px solid #e5e7eb; border-radius: 8px; overflow-y: auto; max-height: 420px; background: #fff; }
.ssf-preview-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; min-height: 200px; color: #9ca3af; text-align: center; padding: 30px; }
.ssf-preview-empty .dashicons { font-size: 32px; width: 32px; height: 32px; margin-bottom: 8px; }
.ssf-preview-empty p { margin: 0; font-size: 13px; line-height: 1.5; }

/* Preview loading */
.ssf-preview-loading { display: flex; align-items: center; justify-content: center; height: 200px; color: #6b7280; font-size: 14px; gap: 8px; }

/* Preview items */
.ssf-preview-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-bottom: 1px solid #f3f4f6; transition: background 0.15s; }
.ssf-preview-item:last-child { border-bottom: none; }
.ssf-preview-item:hover { background: #f9fafb; }
.ssf-preview-item input[type="checkbox"] { flex-shrink: 0; }
.ssf-preview-item-info { flex: 1; min-width: 0; }
.ssf-preview-item-title { font-weight: 600; font-size: 13px; color: #1f2937; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ssf-preview-item-title a { color: #1f2937; text-decoration: none; }
.ssf-preview-item-title a:hover { color: #2563eb; }
.ssf-preview-item-meta { font-size: 11px; color: #9ca3af; margin-top: 2px; display: flex; gap: 8px; flex-wrap: wrap; }
.ssf-preview-item-meta .ssf-tag-missing { color: #dc2626; font-weight: 600; }
.ssf-preview-item-meta .ssf-tag-has { color: #059669; }
.ssf-preview-item-score { flex-shrink: 0; font-size: 12px; font-weight: 700; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.ssf-preview-item-score.score-good { background: #d1fae5; color: #065f46; }
.ssf-preview-item-score.score-ok { background: #fef3c7; color: #92400e; }
.ssf-preview-item-score.score-poor { background: #fee2e2; color: #991b1b; }
.ssf-preview-item-score.score-na { background: #f3f4f6; color: #9ca3af; font-size: 10px; }

/* Footer */
.ssf-modal-footer { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 15px 20px; border-top: 1px solid #e5e7eb; background: #f9fafb; }
.ssf-modal-footer .dashicons { margin-right: 4px; }
.ssf-footer-left { font-size: 13px; color: #6b7280; }
.ssf-footer-left strong { color: #1f2937; }

/* Stat cards */
.ssf-stat-missing { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
.ssf-stat-card-missing { border-left: 4px solid #f59e0b; }

/* Missing SEO alert banner */
.ssf-missing-seo-banner { display: flex; align-items: center; gap: 16px; padding: 16px 20px; margin-bottom: 20px; background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-left: 4px solid #d97706; border-radius: 8px; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.15); }
.ssf-banner-icon { flex-shrink: 0; width: 40px; height: 40px; background: #d97706; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.ssf-banner-icon .dashicons { color: #fff; font-size: 20px; width: 20px; height: 20px; }
.ssf-banner-content { flex: 1; }
.ssf-banner-content strong { display: block; color: #92400e; font-size: 14px; margin-bottom: 4px; }
.ssf-banner-content p { margin: 0; color: #78350f; font-size: 13px; line-height: 1.4; }
.ssf-banner-actions { flex-shrink: 0; }
.ssf-banner-actions .button-primary { background: #d97706; border-color: #b45309; padding: 6px 16px; height: auto; display: flex; align-items: center; gap: 6px; font-weight: 600; white-space: nowrap; }
.ssf-banner-actions .button-primary:hover { background: #b45309; border-color: #92400e; }
.ssf-banner-actions .button-primary .dashicons { font-size: 16px; width: 16px; height: 16px; line-height: 16px; }

@media (max-width: 860px) {
    .ssf-bulk-config-row { flex-direction: column; }
    .ssf-bulk-config-left { flex: none; }
    .ssf-modal-wide { max-width: 98vw !important; }
}
</style>

<script>
jQuery(document).ready(function($) {
    var previewData = [];
    
    function loadDashboardStats() {
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_get_dashboard_stats',
            nonce: ssfAdmin.nonce
        }, function(response) {
            if (response.success) {
                var d = response.data;
                window.dashboardStats = d;
                $('#stat-avg-score').text(d.avg_score || 0);
                $('#stat-good').text(d.good_count || 0);
                $('#stat-ok').text(d.ok_count || 0);
                $('#stat-poor').text(d.poor_count || 0);
                $('#stat-unanalyzed').text(d.unanalyzed || 0);
                
                var mt = d.missing_titles || 0, md = d.missing_descs || 0;
                $('#stat-missing-titles').text(mt);
                mt > 0 ? $('#stat-card-missing').show() : $('#stat-card-missing').hide();
                
                if (mt > 0 || md > 0) {
                    var parts = [];
                    if (mt > 0) parts.push(mt + ' <?php echo esc_js(__('missing SEO titles', 'smart-seo-fixer')); ?>');
                    if (md > 0) parts.push(md + ' <?php echo esc_js(__('missing meta descriptions', 'smart-seo-fixer')); ?>');
                    $('#missing-banner-desc').text(parts.join(', ') + '. <?php echo esc_js(__('Click below to review and fix them.', 'smart-seo-fixer')); ?>');
                    $('#missing-seo-banner').slideDown(300);
                } else {
                    $('#missing-seo-banner').slideUp(200);
                }
                
                renderPostList('#needs-attention-list', d.needs_attention);
                renderPostList('#recent-list', d.recent);
            } else {
                $('#needs-attention-list, #recent-list').html('<p class="ssf-empty"><?php echo esc_js(__('Could not load data.', 'smart-seo-fixer')); ?></p>');
            }
        });
    }
    
    function renderPostList(sel, posts) {
        var $c = $(sel);
        if (!posts || !posts.length) { $c.html('<p class="ssf-empty"><?php echo esc_js(__('No posts found.', 'smart-seo-fixer')); ?></p>'); return; }
        var h = '';
        posts.forEach(function(p) {
            var sc = p.score >= 80 ? 'good' : (p.score >= 60 ? 'ok' : 'poor');
            h += '<div class="ssf-post-item"><a href="<?php echo esc_url(admin_url('post.php?action=edit&post=')); ?>' + p.post_id + '" class="ssf-post-title">' + esc(p.post_title) + '</a><span class="ssf-score ssf-score-' + sc + '">' + p.score + '</span></div>';
        });
        $c.html(h);
    }
    
    function esc(t) { return $('<span>').text(t || '').html(); }
    
    loadDashboardStats();
    
    // === Analyze buttons ===
    $('#analyze-all-btn').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Analyze all unanalyzed posts?', 'smart-seo-fixer')); ?>')) return;
        runProgress('<?php echo esc_js(__('Analyzing Posts...', 'smart-seo-fixer')); ?>', 'ssf_bulk_analyze', { analyze_mode: 'unanalyzed' });
    });
    
    $('#reanalyze-all-btn').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Re-analyze ALL published posts? This may take a while.', 'smart-seo-fixer')); ?>')) return;
        runProgress('<?php echo esc_js(__('Re-Analyzing All Posts...', 'smart-seo-fixer')); ?>', 'ssf_bulk_analyze', { analyze_mode: 'all' });
    });
    
    // === Regenerate schemas ===
    $('#regen-schemas-btn').on('click', function() {
        <?php if (!Smart_SEO_Fixer::get_option('openai_api_key')): ?>
        alert('<?php echo esc_js(__('Please configure your OpenAI API key in Settings first.', 'smart-seo-fixer')); ?>');
        return;
        <?php endif; ?>
        if (!confirm('<?php echo esc_js(__('Regenerate all custom schemas with AI?', 'smart-seo-fixer')); ?>')) return;
        runProgress('<?php echo esc_js(__('Regenerating Schemas...', 'smart-seo-fixer')); ?>', 'ssf_bulk_regenerate_schemas', { mode: 'regenerate' });
    });
    
    // === BULK AI FIX — Preview-first workflow ===
    
    // Open modal
    $('#bulk-fix-btn, #quick-generate-all-btn').on('click', function() {
        <?php if (!Smart_SEO_Fixer::get_option('openai_api_key')): ?>
        alert('<?php echo esc_js(__('Please configure your OpenAI API key in Settings first.', 'smart-seo-fixer')); ?>');
        return;
        <?php endif; ?>
        resetBulkModal();
        
        // If clicked from the banner, auto-load preview
        if ($(this).attr('id') === 'quick-generate-all-btn') {
            $('input[name="bulk_apply_to"][value="missing"]').prop('checked', true);
            loadPreview();
        }
        
        $('#bulk-fix-modal').show();
    });
    
    function resetBulkModal() {
        previewData = [];
        $('#bulk-step-config').show();
        $('#bulk-step-progress').hide();
        $('#start-bulk-fix').prop('disabled', true).text('<?php echo esc_js(__('Fix All Selected Posts', 'smart-seo-fixer')); ?>');
        $('#bulk-modal-title').text('<?php echo esc_js(__('Bulk AI Fix', 'smart-seo-fixer')); ?>');
        $('#preview-list').html('<div class="ssf-preview-empty"><span class="dashicons dashicons-arrow-left-alt"></span><p><?php echo esc_js(__('Choose your options, then click "Load Preview".', 'smart-seo-fixer')); ?></p></div>');
        $('#preview-count').text('');
        $('#select-all-wrap').hide();
        $('.ssf-footer-left').remove();
    }
    
    // Load preview
    $('#load-preview-btn').on('click', loadPreview);
    
    function loadPreview() {
        var applyTo = $('input[name="bulk_apply_to"]:checked').val();
        
        $('#preview-list').html('<div class="ssf-preview-loading"><span class="spinner is-active"></span> <?php echo esc_js(__('Loading affected posts...', 'smart-seo-fixer')); ?></div>');
        $('#start-bulk-fix').prop('disabled', true);
        
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_preview_bulk_fix',
            nonce: ssfAdmin.nonce,
            apply_to: applyTo
        }, function(response) {
            if (response.success && response.data.posts) {
                previewData = response.data.posts;
                renderPreview(previewData);
            } else {
                var msg = (response.data && response.data.message) || '<?php echo esc_js(__('Failed to load preview.', 'smart-seo-fixer')); ?>';
                $('#preview-list').html('<div class="ssf-preview-empty" style="color:#dc2626;"><span class="dashicons dashicons-warning"></span><p>' + esc(msg) + '</p></div>');
            }
        }).fail(function() {
            $('#preview-list').html('<div class="ssf-preview-empty" style="color:#dc2626;"><span class="dashicons dashicons-warning"></span><p><?php echo esc_js(__('Request failed. Check connection.', 'smart-seo-fixer')); ?></p></div>');
        });
    }
    
    function renderPreview(posts) {
        if (!posts.length) {
            $('#preview-list').html('<div class="ssf-preview-empty"><span class="dashicons dashicons-smiley"></span><p><?php echo esc_js(__('All posts already have SEO data. Nothing to fix!', 'smart-seo-fixer')); ?></p></div>');
            $('#preview-count').text('0');
            $('#select-all-wrap').hide();
            $('#start-bulk-fix').prop('disabled', true);
            return;
        }
        
        var html = '';
        posts.forEach(function(p) {
            var scoreClass = p.score === null ? 'na' : (p.score >= 80 ? 'good' : (p.score >= 60 ? 'ok' : 'poor'));
            var scoreText = p.score === null ? '—' : p.score;
            
            var tags = '';
            if (!p.has_title)   tags += '<span class="ssf-tag-missing">⚠ Title</span>';
            else                tags += '<span class="ssf-tag-has">✓ Title</span>';
            if (!p.has_desc)    tags += '<span class="ssf-tag-missing">⚠ Desc</span>';
            else                tags += '<span class="ssf-tag-has">✓ Desc</span>';
            if (!p.has_keyword) tags += '<span class="ssf-tag-missing">⚠ Keyword</span>';
            else                tags += '<span class="ssf-tag-has">✓ Keyword</span>';
            
            html += '<div class="ssf-preview-item">';
            html += '<input type="checkbox" class="preview-item-cb" value="' + p.id + '" checked>';
            html += '<div class="ssf-preview-item-info">';
            html += '<span class="ssf-preview-item-title"><a href="' + p.edit_url + '" target="_blank">' + esc(p.title) + '</a></span>';
            html += '<div class="ssf-preview-item-meta">' + tags + ' <span style="color:#9ca3af;">(' + esc(p.type) + ')</span></div>';
            html += '</div>';
            html += '<div class="ssf-preview-item-score score-' + scoreClass + '">' + scoreText + '</div>';
            html += '</div>';
        });
        
        $('#preview-list').html(html);
        $('#preview-count').text(posts.length);
        $('#select-all-wrap').show();
        $('#preview-select-all').prop('checked', true);
        updateFixButton();
        $('#start-bulk-fix').prop('disabled', false);
    }
    
    // Select all toggle
    $(document).on('change', '#preview-select-all', function() {
        var checked = $(this).is(':checked');
        $('.preview-item-cb').prop('checked', checked);
        updateFixButton();
    });
    
    // Individual checkbox
    $(document).on('change', '.preview-item-cb', function() {
        updateFixButton();
        var total = $('.preview-item-cb').length;
        var selected = $('.preview-item-cb:checked').length;
        $('#preview-select-all').prop('checked', selected === total);
    });
    
    function updateFixButton() {
        var count = $('.preview-item-cb:checked').length;
        if (count > 0) {
            $('#start-bulk-fix').prop('disabled', false).html('<span class="dashicons dashicons-superhero-alt" style="margin-top:4px;"></span> <?php echo esc_js(__('Fix', 'smart-seo-fixer')); ?> ' + count + ' <?php echo esc_js(__('Selected Posts', 'smart-seo-fixer')); ?>');
        } else {
            $('#start-bulk-fix').prop('disabled', true).html('<span class="dashicons dashicons-superhero-alt" style="margin-top:4px;"></span> <?php echo esc_js(__('Select posts to fix', 'smart-seo-fixer')); ?>');
        }
    }
    
    // Start bulk fix from modal
    $('#start-bulk-fix').on('click', function() {
        var genTitle = $('input[name="bulk_opt_title"]').is(':checked');
        var genDesc = $('input[name="bulk_opt_desc"]').is(':checked');
        var genKw = $('input[name="bulk_opt_keywords"]').is(':checked');
        
        if (!genTitle && !genDesc && !genKw) {
            alert('<?php echo esc_js(__('Select at least one option to generate.', 'smart-seo-fixer')); ?>');
            return;
        }
        
        var selectedCount = $('.preview-item-cb:checked').length;
        if (!selectedCount) {
            alert('<?php echo esc_js(__('No posts selected.', 'smart-seo-fixer')); ?>');
            return;
        }
        
        if (!confirm('<?php echo esc_js(__('AI will generate SEO data for', 'smart-seo-fixer')); ?> ' + selectedCount + ' <?php echo esc_js(__('posts. This uses your OpenAI API credits. Continue?', 'smart-seo-fixer')); ?>')) {
            return;
        }
        
        // Switch to progress view inside same modal
        $('#bulk-step-config').hide();
        $('#bulk-step-progress').show();
        $('#bulk-modal-title').text('<?php echo esc_js(__('Generating SEO Data...', 'smart-seo-fixer')); ?>');
        $('#start-bulk-fix').prop('disabled', true);
        $('#bulk-progress-fill').css('width', '0%');
        $('#bulk-progress-text').text('0%');
        $('#bulk-progress-log').html('');
        
        var options = {
            generate_title: genTitle,
            generate_desc: genDesc,
            generate_keywords: genKw,
            apply_to: $('input[name="bulk_apply_to"]:checked').val()
        };
        
        runBulkInModal(options);
    });
    
    function runBulkInModal(options) {
        var offset = 0, batchSize = 5, processed = 0, total = 0;
        
        function next() {
            var postData = $.extend({
                action: 'ssf_bulk_ai_fix',
                nonce: ssfAdmin.nonce,
                offset: offset,
                batch_size: batchSize
            }, options);
            postData.options = options;
            
            $.post(ssfAdmin.ajax_url, postData, function(response) {
                if (response.success) {
                    processed += response.data.processed || 0;
                    total = response.data.total || total;
                    var pct = total > 0 ? Math.round((processed / total) * 100) : 100;
                    $('#bulk-progress-fill').css('width', pct + '%');
                    $('#bulk-progress-text').text(pct + '% (' + processed + '/' + total + ')');
                    
                    if (response.data.log) {
                        response.data.log.forEach(function(e) {
                            $('#bulk-progress-log').append('<div>' + e + '</div>');
                        });
                        var el = document.getElementById('bulk-progress-log');
                        el.scrollTop = el.scrollHeight;
                    }
                    
                    if (response.data.done) {
                        $('#bulk-modal-title').text('<?php echo esc_js(__('Complete!', 'smart-seo-fixer')); ?>');
                        $('#start-bulk-fix').prop('disabled', false).html('<span class="dashicons dashicons-yes-alt" style="margin-top:4px;"></span> <?php echo esc_js(__('Done — Close', 'smart-seo-fixer')); ?>').off('click').on('click', function() {
                            $('#bulk-fix-modal').hide();
                            loadDashboardStats();
                        });
                    } else {
                        offset += batchSize;
                        setTimeout(next, 300);
                    }
                } else {
                    var msg = (response.data && response.data.message) || '<?php echo esc_js(__('Unknown error', 'smart-seo-fixer')); ?>';
                    $('#bulk-progress-log').append('<div style="color:#dc2626;">❌ ' + esc(msg) + '</div>');
                    $('#bulk-modal-title').text('<?php echo esc_js(__('Error', 'smart-seo-fixer')); ?>');
                }
            }).fail(function() {
                $('#bulk-progress-log').append('<div style="color:#dc2626;"><?php echo esc_js(__('Request failed. Retrying...', 'smart-seo-fixer')); ?></div>');
                setTimeout(next, 2000);
            });
        }
        
        next();
    }
    
    // === Generic progress modal (for analyze & schema) ===
    function runProgress(title, action, options) {
        $('#progress-modal').show();
        $('#progress-title').text(title);
        $('#progress-fill').css('width', '0%');
        $('#progress-text').text('0%');
        $('#progress-log').html('');
        
        var offset = 0, batchSize = (action === 'ssf_bulk_regenerate_schemas') ? 2 : 5, processed = 0, total = 0;
        
        function next() {
            var postData = $.extend({ action: action, nonce: ssfAdmin.nonce, offset: offset, batch_size: batchSize }, options || {});
            postData.options = options;
            
            $.post(ssfAdmin.ajax_url, postData, function(response) {
                if (response.success) {
                    processed += response.data.processed || 0;
                    total = response.data.total || total;
                    var pct = total > 0 ? Math.round((processed / total) * 100) : 100;
                    $('#progress-fill').css('width', pct + '%');
                    $('#progress-text').text(pct + '% (' + processed + '/' + total + ')');
                    
                    if (response.data.log) {
                        response.data.log.forEach(function(e) { $('#progress-log').append('<div>' + e + '</div>'); });
                        var el = document.getElementById('progress-log');
                        el.scrollTop = el.scrollHeight;
                    }
                    
                    if (response.data.done) {
                        $('#progress-title').text('<?php echo esc_js(__('Complete!', 'smart-seo-fixer')); ?>');
                        setTimeout(function() { $('#progress-modal').hide(); loadDashboardStats(); }, 1500);
                    } else {
                        offset += batchSize;
                        setTimeout(next, 500);
                    }
                } else {
                    $('#progress-log').append('<div style="color:#dc2626;">Error: ' + ((response.data && response.data.message) || 'Unknown') + '</div>');
                }
            }).fail(function() {
                $('#progress-log').append('<div style="color:#dc2626;"><?php echo esc_js(__('Request failed. Retrying...', 'smart-seo-fixer')); ?></div>');
                setTimeout(next, 2000);
            });
        }
        
        next();
    }
});
</script>

