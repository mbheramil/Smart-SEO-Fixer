<?php
/**
 * Search Console Fixer View
 * 
 * Displays and helps fix common Google Search Console indexing issues.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ssf-wrap">
    <h1 class="ssf-page-title">
        <span class="dashicons dashicons-google"></span>
        <?php esc_html_e('Search Console Fixer', 'smart-seo-fixer'); ?>
    </h1>
    
    <p class="ssf-page-description">
        <?php esc_html_e('Automatically detect and fix common issues that cause Google Search Console problems like duplicate canonicals, trailing slash inconsistencies, and redirect chains.', 'smart-seo-fixer'); ?>
    </p>
    
    <!-- Issue Type Guide -->
    <div class="ssf-card ssf-gsc-guide">
        <div class="ssf-card-header">
            <h2>
                <span class="dashicons dashicons-info-outline"></span>
                <?php esc_html_e('What This Plugin Fixes', 'smart-seo-fixer'); ?>
            </h2>
        </div>
        <div class="ssf-card-body">
            <div class="ssf-gsc-issues-grid">
                <div class="ssf-gsc-issue-type">
                    <span class="dashicons dashicons-migrate" style="color: #10b981;"></span>
                    <strong><?php esc_html_e('Page with redirect', 'smart-seo-fixer'); ?></strong>
                    <p><?php esc_html_e('URLs with redirects are automatically managed. Use the Redirects page to manage them.', 'smart-seo-fixer'); ?></p>
                </div>
                <div class="ssf-gsc-issue-type">
                    <span class="dashicons dashicons-hidden" style="color: #f59e0b;"></span>
                    <strong><?php esc_html_e('Excluded by noindex tag', 'smart-seo-fixer'); ?></strong>
                    <p><?php esc_html_e('Pages intentionally set to noindex. Review in All Posts if unintended.', 'smart-seo-fixer'); ?></p>
                </div>
                <div class="ssf-gsc-issue-type">
                    <span class="dashicons dashicons-admin-page" style="color: #3b82f6;"></span>
                    <strong><?php esc_html_e('Alternate page with proper canonical', 'smart-seo-fixer'); ?></strong>
                    <p><?php esc_html_e('Expected behavior for duplicate content with canonical tags.', 'smart-seo-fixer'); ?></p>
                </div>
                <div class="ssf-gsc-issue-type">
                    <span class="dashicons dashicons-warning" style="color: #ef4444;"></span>
                    <strong><?php esc_html_e('Duplicate, Google chose different canonical', 'smart-seo-fixer'); ?></strong>
                    <p><?php esc_html_e('Fixed by: Consistent trailing slashes, UTM parameter stripping, and normalized canonicals.', 'smart-seo-fixer'); ?></p>
                </div>
                <div class="ssf-gsc-issue-type">
                    <span class="dashicons dashicons-dismiss" style="color: #ef4444;"></span>
                    <strong><?php esc_html_e('Not found (404)', 'smart-seo-fixer'); ?></strong>
                    <p><?php esc_html_e('Tracked in 404 Log. Create redirects for important broken URLs.', 'smart-seo-fixer'); ?></p>
                </div>
                <div class="ssf-gsc-issue-type">
                    <span class="dashicons dashicons-clock" style="color: #8b5cf6;"></span>
                    <strong><?php esc_html_e('Crawled/Discovered - not indexed', 'smart-seo-fixer'); ?></strong>
                    <p><?php esc_html_e('Improve content quality and internal linking. Use SEO Analyzer.', 'smart-seo-fixer'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Stats -->
    <div class="ssf-stats-grid" id="gsc-stats-grid">
        <div class="ssf-stat-card">
            <div class="ssf-stat-icon ssf-stat-good">
                <span class="dashicons dashicons-randomize"></span>
            </div>
            <div class="ssf-stat-content">
                <span class="ssf-stat-value" id="stat-trailing-slash">—</span>
                <span class="ssf-stat-label"><?php esc_html_e('Trailing Slash Mode', 'smart-seo-fixer'); ?></span>
            </div>
        </div>
        
        <div class="ssf-stat-card">
            <div class="ssf-stat-icon ssf-stat-ok">
                <span class="dashicons dashicons-migrate"></span>
            </div>
            <div class="ssf-stat-content">
                <span class="ssf-stat-value" id="stat-redirects">—</span>
                <span class="ssf-stat-label"><?php esc_html_e('Active Redirects', 'smart-seo-fixer'); ?></span>
            </div>
        </div>
        
        <div class="ssf-stat-card">
            <div class="ssf-stat-icon ssf-stat-poor">
                <span class="dashicons dashicons-hidden"></span>
            </div>
            <div class="ssf-stat-content">
                <span class="ssf-stat-value" id="stat-noindex">—</span>
                <span class="ssf-stat-label"><?php esc_html_e('Noindex Pages', 'smart-seo-fixer'); ?></span>
            </div>
        </div>
        
        <div class="ssf-stat-card">
            <div class="ssf-stat-icon ssf-stat-unanalyzed">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="ssf-stat-content">
                <span class="ssf-stat-value" id="stat-404s">—</span>
                <span class="ssf-stat-label"><?php esc_html_e('Tracked 404s', 'smart-seo-fixer'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Scan & Fix Actions -->
    <div class="ssf-card">
        <div class="ssf-card-header">
            <h2>
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e('Scan & Fix URL Issues', 'smart-seo-fixer'); ?>
            </h2>
        </div>
        <div class="ssf-card-body">
            <div class="ssf-actions-grid">
                <button type="button" class="ssf-action-btn" id="scan-url-issues-btn">
                    <span class="dashicons dashicons-search"></span>
                    <span class="ssf-action-text">
                        <strong><?php esc_html_e('Scan for URL Issues', 'smart-seo-fixer'); ?></strong>
                        <small><?php esc_html_e('Check for trailing slash, canonical, and redirect problems', 'smart-seo-fixer'); ?></small>
                    </span>
                </button>
                
                <button type="button" class="ssf-action-btn" id="fix-all-issues-btn" disabled>
                    <span class="dashicons dashicons-admin-generic"></span>
                    <span class="ssf-action-text">
                        <strong><?php esc_html_e('Fix All Issues', 'smart-seo-fixer'); ?></strong>
                        <small><?php esc_html_e('Automatically resolve detected problems', 'smart-seo-fixer'); ?></small>
                    </span>
                </button>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-redirects')); ?>" class="ssf-action-btn">
                    <span class="dashicons dashicons-migrate"></span>
                    <span class="ssf-action-text">
                        <strong><?php esc_html_e('Manage Redirects', 'smart-seo-fixer'); ?></strong>
                        <small><?php esc_html_e('Add/edit 301 redirects and view 404 log', 'smart-seo-fixer'); ?></small>
                    </span>
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-posts')); ?>" class="ssf-action-btn">
                    <span class="dashicons dashicons-admin-post"></span>
                    <span class="ssf-action-text">
                        <strong><?php esc_html_e('Review All Posts', 'smart-seo-fixer'); ?></strong>
                        <small><?php esc_html_e('Check noindex settings and canonical URLs', 'smart-seo-fixer'); ?></small>
                    </span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Scan Results -->
    <div class="ssf-card" id="scan-results-card" style="display: none;">
        <div class="ssf-card-header">
            <h2>
                <span class="dashicons dashicons-analytics"></span>
                <?php esc_html_e('Scan Results', 'smart-seo-fixer'); ?>
            </h2>
            <span class="ssf-badge" id="total-issues-badge">0 issues</span>
        </div>
        <div class="ssf-card-body">
            <div id="scan-results-content">
                <!-- Results populated by JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- How It Works -->
    <div class="ssf-card">
        <div class="ssf-card-header">
            <h2>
                <span class="dashicons dashicons-lightbulb"></span>
                <?php esc_html_e('How This Plugin Prevents GSC Issues', 'smart-seo-fixer'); ?>
            </h2>
        </div>
        <div class="ssf-card-body">
            <div class="ssf-feature-list">
                <div class="ssf-feature-item">
                    <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                    <div>
                        <strong><?php esc_html_e('Automatic Trailing Slash Normalization', 'smart-seo-fixer'); ?></strong>
                        <p><?php esc_html_e('Detects your permalink structure and ensures all URLs use consistent trailing slashes. Redirects inconsistent requests.', 'smart-seo-fixer'); ?></p>
                    </div>
                </div>
                <div class="ssf-feature-item">
                    <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                    <div>
                        <strong><?php esc_html_e('UTM Parameter Stripping', 'smart-seo-fixer'); ?></strong>
                        <p><?php esc_html_e('Automatically strips UTM and other tracking parameters from canonical URLs to prevent duplicate content issues.', 'smart-seo-fixer'); ?></p>
                    </div>
                </div>
                <div class="ssf-feature-item">
                    <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                    <div>
                        <strong><?php esc_html_e('Consistent Canonical Tags', 'smart-seo-fixer'); ?></strong>
                        <p><?php esc_html_e('Outputs proper canonical URLs on all pages including archives, categories, and author pages.', 'smart-seo-fixer'); ?></p>
                    </div>
                </div>
                <div class="ssf-feature-item">
                    <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                    <div>
                        <strong><?php esc_html_e('Sitemap URL Normalization', 'smart-seo-fixer'); ?></strong>
                        <p><?php esc_html_e('Sitemap URLs are automatically normalized to match your canonical URL format.', 'smart-seo-fixer'); ?></p>
                    </div>
                </div>
                <div class="ssf-feature-item">
                    <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                    <div>
                        <strong><?php esc_html_e('Redirect Chain Detection', 'smart-seo-fixer'); ?></strong>
                        <p><?php esc_html_e('Scans for redirect chains (A→B→C) and can automatically update them to direct redirects (A→C).', 'smart-seo-fixer'); ?></p>
                    </div>
                </div>
                <div class="ssf-feature-item">
                    <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                    <div>
                        <strong><?php esc_html_e('Noindex Conflict Detection', 'smart-seo-fixer'); ?></strong>
                        <p><?php esc_html_e('Warns when noindex pages might be included in the sitemap, causing mixed signals.', 'smart-seo-fixer'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load summary stats
    function loadGSCSummary() {
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_get_gsc_summary',
            nonce: ssfAdmin.nonce
        }, function(response) {
            if (response.success) {
                var data = response.data;
                $('#stat-trailing-slash').text(data.trailing_slash_mode);
                $('#stat-redirects').text(data.active_redirects);
                $('#stat-noindex').text(data.noindex_pages);
                $('#stat-404s').text(data.tracked_404s);
            }
        });
    }
    
    loadGSCSummary();
    
    // Scan for URL issues
    $('#scan-url-issues-btn').on('click', function() {
        var $btn = $(this);
        var originalHtml = $btn.find('strong').text();
        
        $btn.prop('disabled', true);
        $btn.find('strong').text('<?php echo esc_js(__('Scanning...', 'smart-seo-fixer')); ?>');
        
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_scan_url_issues',
            nonce: ssfAdmin.nonce
        }, function(response) {
            $btn.prop('disabled', false);
            $btn.find('strong').text(originalHtml);
            
            if (response.success) {
                displayScanResults(response.data);
            } else {
                alert(response.data.message || '<?php echo esc_js(__('Scan failed.', 'smart-seo-fixer')); ?>');
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            $btn.find('strong').text(originalHtml);
            alert('<?php echo esc_js(__('Request failed.', 'smart-seo-fixer')); ?>');
        });
    });
    
    // Display scan results
    function displayScanResults(data) {
        var $card = $('#scan-results-card');
        var $content = $('#scan-results-content');
        var $badge = $('#total-issues-badge');
        var total = data.total || 0;
        
        $badge.text(total + ' <?php echo esc_js(__('issues', 'smart-seo-fixer')); ?>');
        
        if (total === 0) {
            $content.html('<div class="ssf-notice ssf-notice-success"><p><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_js(__('No URL issues detected! Your site is properly configured.', 'smart-seo-fixer')); ?></p></div>');
            $('#fix-all-issues-btn').prop('disabled', true);
        } else {
            var html = '';
            
            if (data.issues.trailing_slash && data.issues.trailing_slash.length > 0) {
                html += '<div class="ssf-issue-group">';
                html += '<h3><span class="dashicons dashicons-randomize"></span> <?php echo esc_js(__('Trailing Slash Inconsistencies', 'smart-seo-fixer')); ?> (' + data.issues.trailing_slash.length + ')</h3>';
                html += '<ul class="ssf-issue-list">';
                data.issues.trailing_slash.slice(0, 10).forEach(function(issue) {
                    html += '<li><a href="' + issue.url + '" target="_blank">' + issue.title + '</a> - ' + issue.expected + '</li>';
                });
                if (data.issues.trailing_slash.length > 10) {
                    html += '<li><em>... and ' + (data.issues.trailing_slash.length - 10) + ' more</em></li>';
                }
                html += '</ul></div>';
            }
            
            if (data.issues.duplicate_canonical && data.issues.duplicate_canonical.length > 0) {
                html += '<div class="ssf-issue-group">';
                html += '<h3><span class="dashicons dashicons-admin-page"></span> <?php echo esc_js(__('Custom Canonical URLs', 'smart-seo-fixer')); ?> (' + data.issues.duplicate_canonical.length + ')</h3>';
                html += '<ul class="ssf-issue-list">';
                data.issues.duplicate_canonical.forEach(function(issue) {
                    html += '<li><a href="<?php echo esc_url(admin_url('post.php?action=edit&post=')); ?>' + issue.post_id + '">' + issue.title + '</a> → ' + issue.canonical + '</li>';
                });
                html += '</ul></div>';
            }
            
            if (data.issues.redirect_chains && data.issues.redirect_chains.length > 0) {
                html += '<div class="ssf-issue-group">';
                html += '<h3><span class="dashicons dashicons-warning"></span> <?php echo esc_js(__('Redirect Chains', 'smart-seo-fixer')); ?> (' + data.issues.redirect_chains.length + ')</h3>';
                html += '<ul class="ssf-issue-list">';
                data.issues.redirect_chains.forEach(function(issue) {
                    html += '<li>' + issue.from + ' → ' + issue.to + ' → ' + issue.chain_to + '</li>';
                });
                html += '</ul></div>';
            }
            
            if (data.issues.noindex_in_sitemap && data.issues.noindex_in_sitemap.length > 0) {
                html += '<div class="ssf-issue-group">';
                html += '<h3><span class="dashicons dashicons-hidden"></span> <?php echo esc_js(__('Noindex Pages (Check Sitemap)', 'smart-seo-fixer')); ?> (' + data.issues.noindex_in_sitemap.length + ')</h3>';
                html += '<ul class="ssf-issue-list">';
                data.issues.noindex_in_sitemap.forEach(function(issue) {
                    html += '<li><a href="<?php echo esc_url(admin_url('post.php?action=edit&post=')); ?>' + issue.post_id + '">' + issue.title + '</a></li>';
                });
                html += '</ul></div>';
            }
            
            $content.html(html);
            $('#fix-all-issues-btn').prop('disabled', false);
        }
        
        $card.show();
    }
    
    // Fix all issues
    $('#fix-all-issues-btn').on('click', function() {
        var $btn = $(this);
        var originalHtml = $btn.find('strong').text();
        
        if (!confirm('<?php echo esc_js(__('This will attempt to fix all detected URL issues. Continue?', 'smart-seo-fixer')); ?>')) {
            return;
        }
        
        $btn.prop('disabled', true);
        $btn.find('strong').text('<?php echo esc_js(__('Fixing...', 'smart-seo-fixer')); ?>');
        
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_fix_url_issues',
            nonce: ssfAdmin.nonce,
            issue_type: 'all'
        }, function(response) {
            $btn.prop('disabled', false);
            $btn.find('strong').text(originalHtml);
            
            if (response.success) {
                alert('<?php echo esc_js(__('Issues fixed! Re-scanning...', 'smart-seo-fixer')); ?>');
                $('#scan-url-issues-btn').click();
                loadGSCSummary();
            } else {
                alert(response.data.message || '<?php echo esc_js(__('Fix failed.', 'smart-seo-fixer')); ?>');
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            $btn.find('strong').text(originalHtml);
            alert('<?php echo esc_js(__('Request failed.', 'smart-seo-fixer')); ?>');
        });
    });
});
</script>

<style>
.ssf-gsc-issues-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}
.ssf-gsc-issue-type {
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}
.ssf-gsc-issue-type .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}
.ssf-gsc-issue-type strong {
    font-size: 14px;
}
.ssf-gsc-issue-type p {
    margin: 0;
    color: #6b7280;
    font-size: 13px;
}
.ssf-feature-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.ssf-feature-item {
    display: flex;
    gap: 15px;
    align-items: flex-start;
}
.ssf-feature-item .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
    margin-top: 2px;
}
.ssf-feature-item strong {
    display: block;
    margin-bottom: 4px;
}
.ssf-feature-item p {
    margin: 0;
    color: #6b7280;
    font-size: 13px;
}
.ssf-issue-group {
    margin-bottom: 20px;
    padding: 15px;
    background: #fef3c7;
    border-radius: 8px;
    border: 1px solid #fcd34d;
}
.ssf-issue-group h3 {
    margin: 0 0 10px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ssf-issue-list {
    margin: 0;
    padding-left: 20px;
}
.ssf-issue-list li {
    margin-bottom: 5px;
    font-size: 13px;
}
.ssf-badge {
    background: #ef4444;
    color: #fff;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
</style>
