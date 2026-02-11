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
                        <span class="dashicons dashicons-admin-generic"></span>
                        <span class="ssf-action-text">
                            <strong><?php esc_html_e('Bulk AI Fix', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Generate titles & descriptions for poor posts', 'smart-seo-fixer'); ?></small>
                        </span>
                    </button>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-settings')); ?>" class="ssf-action-btn">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <span class="ssf-action-text">
                            <strong><?php esc_html_e('Settings', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Configure plugin options', 'smart-seo-fixer'); ?></small>
                        </span>
                    </a>
                    
                    <button type="button" class="ssf-action-btn" id="regen-schemas-btn">
                        <span class="dashicons dashicons-shortcode"></span>
                        <span class="ssf-action-text">
                            <strong><?php esc_html_e('Regenerate Custom Schemas', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Re-run AI schema for all posts with custom markup', 'smart-seo-fixer'); ?></small>
                        </span>
                    </button>
                    
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
    
    <!-- Bulk AI Fix Modal -->
    <div class="ssf-modal" id="bulk-fix-modal" style="display: none;">
        <div class="ssf-modal-content">
            <div class="ssf-modal-header">
                <h3><?php esc_html_e('Bulk AI Fix Options', 'smart-seo-fixer'); ?></h3>
                <button type="button" class="ssf-modal-close" onclick="jQuery('#bulk-fix-modal').hide();">&times;</button>
            </div>
            <div class="ssf-modal-body">
                <p><?php esc_html_e('Select what the AI should generate for posts with missing or low-quality SEO content:', 'smart-seo-fixer'); ?></p>
                
                <div class="ssf-bulk-options">
                    <h4><?php esc_html_e('Generate:', 'smart-seo-fixer'); ?></h4>
                    <label class="ssf-checkbox-option">
                        <input type="checkbox" name="bulk_opt_title" checked>
                        <span class="ssf-checkbox-label">
                            <strong><?php esc_html_e('SEO Titles', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Generate optimized titles (50-60 chars)', 'smart-seo-fixer'); ?></small>
                        </span>
                    </label>
                    <label class="ssf-checkbox-option">
                        <input type="checkbox" name="bulk_opt_desc" checked>
                        <span class="ssf-checkbox-label">
                            <strong><?php esc_html_e('Meta Descriptions', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Generate compelling descriptions (150-160 chars)', 'smart-seo-fixer'); ?></small>
                        </span>
                    </label>
                    <label class="ssf-checkbox-option">
                        <input type="checkbox" name="bulk_opt_keywords">
                        <span class="ssf-checkbox-label">
                            <strong><?php esc_html_e('Focus Keywords', 'smart-seo-fixer'); ?></strong>
                            <small><?php esc_html_e('Suggest focus keywords for each post', 'smart-seo-fixer'); ?></small>
                        </span>
                    </label>
                </div>
                
                <div class="ssf-bulk-options">
                    <h4><?php esc_html_e('Apply to:', 'smart-seo-fixer'); ?></h4>
                    <label class="ssf-radio-option">
                        <input type="radio" name="bulk_apply_to" value="missing" checked>
                        <span><?php esc_html_e('Only posts with MISSING content (safe)', 'smart-seo-fixer'); ?></span>
                    </label>
                    <label class="ssf-radio-option">
                        <input type="radio" name="bulk_apply_to" value="poor">
                        <span><?php esc_html_e('Posts with score below 60 (missing + poor quality)', 'smart-seo-fixer'); ?></span>
                    </label>
                    <label class="ssf-radio-option ssf-option-danger">
                        <input type="radio" name="bulk_apply_to" value="all">
                        <span><?php esc_html_e('ALL posts (overwrite everything)', 'smart-seo-fixer'); ?></span>
                    </label>
                </div>
                
                <div class="ssf-bulk-estimate" id="bulk-estimate">
                    <span class="dashicons dashicons-info"></span>
                    <?php esc_html_e('Estimated: ~X posts will be processed', 'smart-seo-fixer'); ?>
                </div>
            </div>
            <div class="ssf-modal-footer">
                <button type="button" class="button" onclick="jQuery('#bulk-fix-modal').hide();">
                    <?php esc_html_e('Cancel', 'smart-seo-fixer'); ?>
                </button>
                <button type="button" class="button button-primary" id="start-bulk-fix">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e('Start AI Fix', 'smart-seo-fixer'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Progress Modal -->
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
.ssf-modal-close {
    position: absolute;
    right: 15px;
    top: 15px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.ssf-bulk-options {
    margin: 20px 0;
}

.ssf-bulk-options h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #374151;
}

.ssf-checkbox-option,
.ssf-radio-option {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 6px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.ssf-checkbox-option:hover,
.ssf-radio-option:hover {
    background: #f3f4f6;
}

.ssf-checkbox-option input,
.ssf-radio-option input {
    margin-top: 3px;
}

.ssf-checkbox-label {
    display: flex;
    flex-direction: column;
}

.ssf-checkbox-label strong {
    color: #1f2937;
}

.ssf-checkbox-label small {
    color: #6b7280;
    font-size: 12px;
}

.ssf-option-danger {
    border: 1px solid #fecaca;
    background: #fef2f2;
}

.ssf-option-danger:hover {
    background: #fee2e2;
}

.ssf-bulk-estimate {
    padding: 12px;
    background: #eff6ff;
    border-radius: 6px;
    color: #1e40af;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ssf-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}

.ssf-modal-footer .dashicons {
    margin-right: 5px;
}

/* Missing SEO stat card */
.ssf-stat-missing {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
}

.ssf-stat-card-missing {
    border-left: 4px solid #f59e0b;
}

/* Missing SEO alert banner */
.ssf-missing-seo-banner {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border: 1px solid #f59e0b;
    border-left: 4px solid #d97706;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.15);
}

.ssf-banner-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: #d97706;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ssf-banner-icon .dashicons {
    color: #fff;
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.ssf-banner-content {
    flex: 1;
}

.ssf-banner-content strong {
    display: block;
    color: #92400e;
    font-size: 14px;
    margin-bottom: 4px;
}

.ssf-banner-content p {
    margin: 0;
    color: #78350f;
    font-size: 13px;
    line-height: 1.4;
}

.ssf-banner-actions {
    flex-shrink: 0;
}

.ssf-banner-actions .button-primary {
    background: #d97706;
    border-color: #b45309;
    padding: 6px 16px;
    height: auto;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    white-space: nowrap;
}

.ssf-banner-actions .button-primary:hover {
    background: #b45309;
    border-color: #92400e;
}

.ssf-banner-actions .button-primary .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    line-height: 16px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Load dashboard stats
    function loadDashboardStats() {
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_get_dashboard_stats',
            nonce: ssfAdmin.nonce
        }, function(response) {
            if (response.success) {
                var data = response.data;
                
                // Store for bulk fix modal
                window.dashboardStats = data;
                
                // Update stat cards
                $('#stat-avg-score').text(data.avg_score || 0);
                $('#stat-good').text(data.good_count || 0);
                $('#stat-ok').text(data.ok_count || 0);
                $('#stat-poor').text(data.poor_count || 0);
                $('#stat-unanalyzed').text(data.unanalyzed || 0);
                
                // Missing SEO stat card
                var missingTitles = data.missing_titles || 0;
                var missingDescs = data.missing_descs || 0;
                $('#stat-missing-titles').text(missingTitles);
                
                if (missingTitles > 0) {
                    $('#stat-card-missing').show();
                } else {
                    $('#stat-card-missing').hide();
                }
                
                // Missing SEO alert banner
                if (missingTitles > 0 || missingDescs > 0) {
                    var parts = [];
                    if (missingTitles > 0) parts.push(missingTitles + ' <?php echo esc_js(__('missing SEO titles', 'smart-seo-fixer')); ?>');
                    if (missingDescs > 0) parts.push(missingDescs + ' <?php echo esc_js(__('missing meta descriptions', 'smart-seo-fixer')); ?>');
                    
                    $('#missing-banner-desc').text(parts.join(', ') + '. <?php echo esc_js(__('Click below to generate AI-optimized SEO for all of them instantly.', 'smart-seo-fixer')); ?>');
                    $('#missing-seo-banner').slideDown(300);
                } else {
                    $('#missing-seo-banner').slideUp(200);
                }
                
                // Render needs attention list
                renderPostList('#needs-attention-list', data.needs_attention);
                
                // Render recent list
                renderPostList('#recent-list', data.recent);
            } else {
                // Handle error
                $('#needs-attention-list').html('<p class="ssf-empty"><?php esc_html_e('Could not load data. Try clicking "Analyze All Posts" first.', 'smart-seo-fixer'); ?></p>');
                $('#recent-list').html('<p class="ssf-empty"><?php esc_html_e('No posts analyzed yet. Click "Analyze All Posts" to get started.', 'smart-seo-fixer'); ?></p>');
            }
        }).fail(function() {
            // Handle AJAX failure
            $('#needs-attention-list').html('<p class="ssf-empty" style="color:#dc2626;"><?php esc_html_e('Failed to load dashboard data. Please refresh the page.', 'smart-seo-fixer'); ?></p>');
            $('#recent-list').html('<p class="ssf-empty" style="color:#dc2626;"><?php esc_html_e('Failed to load dashboard data. Please refresh the page.', 'smart-seo-fixer'); ?></p>');
        });
    }
    
    function renderPostList(selector, posts) {
        var $container = $(selector);
        
        if (!posts || posts.length === 0) {
            $container.html('<p class="ssf-empty"><?php esc_html_e('No posts found.', 'smart-seo-fixer'); ?></p>');
            return;
        }
        
        var html = '';
        posts.forEach(function(post) {
            var scoreClass = getScoreClass(post.score);
            html += '<div class="ssf-post-item">';
            html += '<a href="' + '<?php echo esc_url(admin_url('post.php?action=edit&post=')); ?>' + post.post_id + '" class="ssf-post-title">' + escapeHtml(post.post_title) + '</a>';
            html += '<span class="ssf-score ssf-score-' + scoreClass + '">' + post.score + '</span>';
            html += '</div>';
        });
        
        $container.html(html);
    }
    
    function getScoreClass(score) {
        if (score >= 80) return 'good';
        if (score >= 60) return 'ok';
        return 'poor';
    }
    
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Initial load
    loadDashboardStats();
    
    // Analyze unanalyzed posts button
    $('#analyze-all-btn').on('click', function() {
        if (!confirm('<?php echo esc_js(__('This will analyze all unanalyzed posts. Continue?', 'smart-seo-fixer')); ?>')) {
            return;
        }
        analyzeAllPosts('unanalyzed');
    });
    
    // Re-analyze ALL posts button
    $('#reanalyze-all-btn').on('click', function() {
        if (!confirm('<?php echo esc_js(__('This will re-analyze ALL published posts and update their SEO scores. This may take a while for large sites. Continue?', 'smart-seo-fixer')); ?>')) {
            return;
        }
        analyzeAllPosts('all');
    });
    
    // Bulk fix button - Open modal with options
    $('#bulk-fix-btn').on('click', function() {
        <?php if (!Smart_SEO_Fixer::get_option('openai_api_key')): ?>
        alert('<?php esc_html_e('Please configure your OpenAI API key in Settings first.', 'smart-seo-fixer'); ?>');
        return;
        <?php endif; ?>
        
        // Update estimate when modal opens
        updateBulkEstimate();
        $('#bulk-fix-modal').show();
    });
    
    // Update estimate when options change
    $('input[name="bulk_apply_to"]').on('change', updateBulkEstimate);
    
    function updateBulkEstimate() {
        var applyTo = $('input[name="bulk_apply_to"]:checked').val();
        var stats = window.dashboardStats || {};
        var estimate = 0;
        
        switch(applyTo) {
            case 'missing':
                // Use accurate count of posts missing SEO titles (primary metric)
                estimate = Math.max(stats.missing_titles || 0, stats.missing_descs || 0, stats.unanalyzed || 0);
                break;
            case 'poor':
                estimate = (stats.poor_count || 0) + (stats.unanalyzed || 0);
                break;
            case 'all':
                estimate = stats.total_posts || 0;
                break;
        }
        
        $('#bulk-estimate').html('<span class="dashicons dashicons-info"></span> <?php esc_html_e('Estimated:', 'smart-seo-fixer'); ?> ~' + estimate + ' <?php esc_html_e('posts will be processed', 'smart-seo-fixer'); ?>');
    }
    
    // Start bulk fix
    $('#start-bulk-fix').on('click', function() {
        var options = {
            generate_title: $('input[name="bulk_opt_title"]').is(':checked'),
            generate_desc: $('input[name="bulk_opt_desc"]').is(':checked'),
            generate_keywords: $('input[name="bulk_opt_keywords"]').is(':checked'),
            apply_to: $('input[name="bulk_apply_to"]:checked').val()
        };
        
        if (!options.generate_title && !options.generate_desc && !options.generate_keywords) {
            alert('<?php esc_html_e('Please select at least one option to generate.', 'smart-seo-fixer'); ?>');
            return;
        }
        
        $('#bulk-fix-modal').hide();
        bulkFixPosts(options);
    });
    
    // One-click "Generate All Missing SEO" from the alert banner
    $('#quick-generate-all-btn').on('click', function() {
        <?php if (!Smart_SEO_Fixer::get_option('openai_api_key')): ?>
        alert('<?php echo esc_js(__('Please configure your OpenAI API key in Settings first.', 'smart-seo-fixer')); ?>');
        return;
        <?php endif; ?>
        
        var options = {
            generate_title: true,
            generate_desc: true,
            generate_keywords: true,
            apply_to: 'missing'
        };
        
        bulkFixPosts(options);
    });
    
    function analyzeAllPosts(mode) {
        $('#progress-modal').show();
        var title = mode === 'all' 
            ? '<?php echo esc_js(__('Re-Analyzing All Posts...', 'smart-seo-fixer')); ?>' 
            : '<?php echo esc_js(__('Analyzing Posts...', 'smart-seo-fixer')); ?>';
        $('#progress-title').text(title);
        $('#progress-fill').css('width', '0%');
        $('#progress-text').text('0%');
        $('#progress-log').html('');
        
        runBatchProcess('ssf_bulk_analyze', { analyze_mode: mode || 'unanalyzed' }, function() {
            loadDashboardStats();
        });
    }
    
    function bulkFixPosts(options) {
        $('#progress-modal').show();
        $('#progress-title').text('<?php esc_html_e('Fixing SEO Issues with AI...', 'smart-seo-fixer'); ?>');
        $('#progress-fill').css('width', '0%');
        $('#progress-text').text('0%');
        $('#progress-log').html('');
        
        runBatchProcess('ssf_bulk_ai_fix', options, function() {
            loadDashboardStats();
        });
    }
    
    // Regenerate custom schemas
    $('#regen-schemas-btn').on('click', function() {
        <?php if (!Smart_SEO_Fixer::get_option('openai_api_key')): ?>
        alert('<?php esc_html_e('Please configure your OpenAI API key in Settings first.', 'smart-seo-fixer'); ?>');
        return;
        <?php endif; ?>
        
        var choice = confirm('<?php echo esc_js(__('This will regenerate all custom schemas using AI with your current site data (logo, URLs, etc.). Posts that no longer need custom schema will have it removed automatically. Continue?', 'smart-seo-fixer')); ?>');
        if (!choice) return;
        
        $('#progress-modal').show();
        $('#progress-title').text('<?php esc_html_e('Regenerating Custom Schemas...', 'smart-seo-fixer'); ?>');
        $('#progress-fill').css('width', '0%');
        $('#progress-text').text('<?php esc_html_e('Starting...', 'smart-seo-fixer'); ?>');
        $('#progress-log').html('');
        
        runBatchProcess('ssf_bulk_regenerate_schemas', { mode: 'regenerate' }, function() {
            loadDashboardStats();
        });
    });
    
    function runBatchProcess(action, options, callback) {
        var offset = 0;
        // Use smaller batches for AI-heavy operations
        var batchSize = (action === 'ssf_bulk_regenerate_schemas') ? 2 : 5;
        var processed = 0;
        var total = 0;
        
        function processBatch() {
            var postData = $.extend({
                action: action,
                nonce: ssfAdmin.nonce,
                offset: offset,
                batch_size: batchSize
            }, options || {});
            
            // Also pass as nested for backward compat
            postData.options = options;
            
            $.post(ssfAdmin.ajax_url, postData, function(response) {
                if (response.success) {
                    processed += response.data.processed || 0;
                    total = response.data.total || total;
                    
                    var percent = total > 0 ? Math.round((processed / total) * 100) : 100;
                    $('#progress-fill').css('width', percent + '%');
                    $('#progress-text').text(percent + '% (' + processed + '/' + total + ')');
                    
                    // Add log entries
                    if (response.data.log) {
                        response.data.log.forEach(function(entry) {
                            $('#progress-log').append('<div>' + entry + '</div>');
                        });
                        // Scroll to bottom
                        var logEl = document.getElementById('progress-log');
                        logEl.scrollTop = logEl.scrollHeight;
                    }
                    
                    if (response.data.done) {
                        $('#progress-title').text('<?php esc_html_e('Complete!', 'smart-seo-fixer'); ?>');
                        setTimeout(function() {
                            $('#progress-modal').hide();
                            if (callback) callback();
                        }, 1500);
                    } else {
                        offset += batchSize;
                        setTimeout(processBatch, 500);
                    }
                } else {
                    $('#progress-log').append('<div style="color:red;"><?php esc_html_e('Error:', 'smart-seo-fixer'); ?> ' + (response.data.message || '<?php esc_html_e('Unknown error', 'smart-seo-fixer'); ?>') + '</div>');
                }
            }).fail(function() {
                $('#progress-log').append('<div style="color:red;"><?php esc_html_e('Request failed. Retrying...', 'smart-seo-fixer'); ?></div>');
                setTimeout(processBatch, 2000);
            });
        }
        
        processBatch();
    }
});
</script>

