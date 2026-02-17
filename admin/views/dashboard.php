<?php
/**
 * Dashboard View
 * Clean overview with stats and navigation cards to dedicated tool pages.
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
                    <span class="ssf-stat-label"><?php esc_html_e('Missing SEO Data', 'smart-seo-fixer'); ?></span>
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
                <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-bulk-fix&auto=missing')); ?>" class="button button-primary">
                    <span class="dashicons dashicons-superhero-alt"></span>
                    <?php esc_html_e('Fix Missing SEO Now', 'smart-seo-fixer'); ?>
                </a>
            </div>
        </div>
        
        <!-- Navigation Cards — Tools -->
        <div class="ssf-card" style="margin-bottom: 20px;">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php esc_html_e('SEO Tools', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <div class="ssf-nav-grid">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-analyzer')); ?>" class="ssf-nav-card">
                        <div class="ssf-nav-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                            <span class="dashicons dashicons-search"></span>
                        </div>
                        <div class="ssf-nav-info">
                            <strong><?php esc_html_e('SEO Analyzer', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Analyze posts, view scores, find issues', 'smart-seo-fixer'); ?></small>
                        </div>
                        <span class="dashicons dashicons-arrow-right-alt2 ssf-nav-arrow"></span>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-bulk-fix')); ?>" class="ssf-nav-card">
                        <div class="ssf-nav-icon" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
                            <span class="dashicons dashicons-superhero-alt"></span>
                        </div>
                        <div class="ssf-nav-info">
                            <strong><?php esc_html_e('Bulk AI Fix', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Preview and fix posts with missing SEO data', 'smart-seo-fixer'); ?></small>
                        </div>
                        <span class="dashicons dashicons-arrow-right-alt2 ssf-nav-arrow"></span>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-schema')); ?>" class="ssf-nav-card">
                        <div class="ssf-nav-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <span class="dashicons dashicons-shortcode"></span>
                        </div>
                        <div class="ssf-nav-info">
                            <strong><?php esc_html_e('Schema Manager', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Manage and regenerate schema markup', 'smart-seo-fixer'); ?></small>
                        </div>
                        <span class="dashicons dashicons-arrow-right-alt2 ssf-nav-arrow"></span>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-gsc')); ?>" class="ssf-nav-card">
                        <div class="ssf-nav-icon" style="background: linear-gradient(135deg, #ef4444, #b91c1c);">
                            <span class="dashicons dashicons-flag"></span>
                        </div>
                        <div class="ssf-nav-info">
                            <strong><?php esc_html_e('Indexability Auditor', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Detect and fix Google indexing issues', 'smart-seo-fixer'); ?></small>
                        </div>
                        <span class="dashicons dashicons-arrow-right-alt2 ssf-nav-arrow"></span>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-redirects')); ?>" class="ssf-nav-card">
                        <div class="ssf-nav-icon" style="background: linear-gradient(135deg, #10b981, #047857);">
                            <span class="dashicons dashicons-randomize"></span>
                        </div>
                        <div class="ssf-nav-info">
                            <strong><?php esc_html_e('Redirects & 404s', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Manage redirects and track 404 errors', 'smart-seo-fixer'); ?></small>
                        </div>
                        <span class="dashicons dashicons-arrow-right-alt2 ssf-nav-arrow"></span>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-history')); ?>" class="ssf-nav-card">
                        <div class="ssf-nav-icon" style="background: linear-gradient(135deg, #0891b2, #0e7490);">
                            <span class="dashicons dashicons-backup"></span>
                        </div>
                        <div class="ssf-nav-info">
                            <strong><?php esc_html_e('Change History', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Track all changes with one-click undo', 'smart-seo-fixer'); ?></small>
                        </div>
                        <span class="dashicons dashicons-arrow-right-alt2 ssf-nav-arrow"></span>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-debug-log')); ?>" class="ssf-nav-card">
                        <div class="ssf-nav-icon" style="background: linear-gradient(135deg, #dc2626, #991b1b);">
                            <span class="dashicons dashicons-code-standards"></span>
                        </div>
                        <div class="ssf-nav-info">
                            <strong><?php esc_html_e('Debug Log', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('View errors, warnings, and event logs', 'smart-seo-fixer'); ?></small>
                        </div>
                        <span class="dashicons dashicons-arrow-right-alt2 ssf-nav-arrow"></span>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-settings')); ?>" class="ssf-nav-card">
                        <div class="ssf-nav-icon" style="background: linear-gradient(135deg, #6b7280, #374151);">
                            <span class="dashicons dashicons-admin-settings"></span>
                        </div>
                        <div class="ssf-nav-info">
                            <strong><?php esc_html_e('Settings', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('API keys, post types, auto-generation options', 'smart-seo-fixer'); ?></small>
                        </div>
                        <span class="dashicons dashicons-arrow-right-alt2 ssf-nav-arrow"></span>
                    </a>
                </div>
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
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-analyzer&score_filter=poor')); ?>" class="ssf-link">
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
    </div>
</div>

<style>
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
.ssf-banner-actions .button-primary { background: #d97706; border-color: #b45309; padding: 6px 16px; height: auto; display: flex; align-items: center; gap: 6px; font-weight: 600; white-space: nowrap; text-decoration: none; }
.ssf-banner-actions .button-primary:hover { background: #b45309; border-color: #92400e; }
.ssf-banner-actions .button-primary .dashicons { font-size: 16px; width: 16px; height: 16px; line-height: 16px; }

/* Navigation cards grid */
.ssf-nav-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 12px; }
.ssf-nav-card { display: flex; align-items: center; gap: 14px; padding: 14px 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; transition: all 0.2s; }
.ssf-nav-card:hover { background: #f3f4f6; border-color: #d1d5db; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transform: translateY(-1px); }
.ssf-nav-icon { flex-shrink: 0; width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.ssf-nav-icon .dashicons { color: #fff; font-size: 20px; width: 20px; height: 20px; }
.ssf-nav-info { flex: 1; min-width: 0; }
.ssf-nav-info strong { display: block; color: #1f2937; font-size: 14px; margin-bottom: 2px; }
.ssf-nav-info small { color: #6b7280; font-size: 12px; line-height: 1.4; }
.ssf-nav-arrow { color: #d1d5db; flex-shrink: 0; transition: color 0.2s; }
.ssf-nav-card:hover .ssf-nav-arrow { color: #6b7280; }
</style>

<script>
jQuery(document).ready(function($) {
    function esc(t) { return $('<span>').text(t || '').html(); }
    
    function loadDashboardStats() {
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_get_dashboard_stats',
            nonce: ssfAdmin.nonce
        }, function(response) {
            if (response.success) {
                var d = response.data;
                $('#stat-avg-score').text(d.avg_score || 0);
                $('#stat-good').text(d.good_count || 0);
                $('#stat-ok').text(d.ok_count || 0);
                $('#stat-poor').text(d.poor_count || 0);
                $('#stat-unanalyzed').text(d.unanalyzed || 0);
                
                var mt = d.missing_titles || 0, md = d.missing_descs || 0;
                var total_missing = Math.max(mt, md);
                $('#stat-missing-titles').text(total_missing);
                total_missing > 0 ? $('#stat-card-missing').show() : $('#stat-card-missing').hide();
                
                if (mt > 0 || md > 0) {
                    var parts = [];
                    if (mt > 0) parts.push(mt + ' <?php echo esc_js(__('missing SEO titles', 'smart-seo-fixer')); ?>');
                    if (md > 0) parts.push(md + ' <?php echo esc_js(__('missing meta descriptions', 'smart-seo-fixer')); ?>');
                    $('#missing-banner-desc').text(parts.join(', ') + '. <?php echo esc_js(__('Go to Bulk AI Fix to review and fix them.', 'smart-seo-fixer')); ?>');
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
    
    loadDashboardStats();
});
</script>
