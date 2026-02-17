<?php
/**
 * Search Performance View
 * 
 * Displays Google Search Console data: clicks, impressions, CTR, position,
 * top queries, and top pages.
 */

if (!defined('ABSPATH')) {
    exit;
}

$gsc_connected = false;
if (class_exists('SSF_GSC_Client')) {
    $gsc = new SSF_GSC_Client();
    $gsc_connected = $gsc->is_connected();
    $gsc_site_url = $gsc->get_site_url();
}
?>
<div class="wrap ssf-wrap">
    <h1 class="ssf-page-title">
        <span class="dashicons dashicons-chart-area"></span>
        <?php esc_html_e('Search Performance', 'smart-seo-fixer'); ?>
        <span class="ssf-version">v<?php echo esc_html(SSF_VERSION); ?></span>
    </h1>
    
    <?php if (!$gsc_connected): ?>
        <div class="ssf-card">
            <div class="ssf-card-body" style="text-align: center; padding: 60px 20px;">
                <span class="dashicons dashicons-google" style="font-size: 48px; width: 48px; height: 48px; color: #4285f4; margin-bottom: 16px;"></span>
                <h2 style="margin: 0 0 12px;"><?php esc_html_e('Connect Google Search Console', 'smart-seo-fixer'); ?></h2>
                <p style="color: #666; max-width: 500px; margin: 0 auto 20px;">
                    <?php esc_html_e('See your real search performance data — clicks, impressions, keywords, and page rankings — directly inside WordPress.', 'smart-seo-fixer'); ?>
                </p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=smart-seo-fixer-settings')); ?>" class="button button-primary button-large">
                    <?php esc_html_e('Go to Settings to Connect', 'smart-seo-fixer'); ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        
        <!-- Date Range Selector -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <div>
                <span style="color: #666; font-size: 13px;">
                    <?php printf(esc_html__('Site: %s', 'smart-seo-fixer'), '<strong>' . esc_html($gsc_site_url) . '</strong>'); ?>
                </span>
            </div>
            <div>
                <select id="ssf-gsc-days" class="ssf-select" style="min-width: 160px;">
                    <option value="7"><?php esc_html_e('Last 7 days', 'smart-seo-fixer'); ?></option>
                    <option value="28" selected><?php esc_html_e('Last 28 days', 'smart-seo-fixer'); ?></option>
                    <option value="90"><?php esc_html_e('Last 3 months', 'smart-seo-fixer'); ?></option>
                    <option value="180"><?php esc_html_e('Last 6 months', 'smart-seo-fixer'); ?></option>
                </select>
                <button type="button" class="button" id="ssf-gsc-refresh" title="<?php esc_attr_e('Refresh data', 'smart-seo-fixer'); ?>">
                    <span class="dashicons dashicons-update" style="vertical-align: text-bottom;"></span>
                </button>
                <button type="button" class="button" id="ssf-gsc-submit-sitemap" title="<?php esc_attr_e('Submit sitemap to Google', 'smart-seo-fixer'); ?>">
                    <span class="dashicons dashicons-upload" style="vertical-align: text-bottom;"></span>
                    <?php esc_html_e('Submit Sitemap', 'smart-seo-fixer'); ?>
                </button>
            </div>
        </div>
        
        <!-- Overview Stats Cards -->
        <div class="ssf-stats-grid" id="ssf-gsc-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
            <div class="ssf-stat-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; border-top: 4px solid #4285f4;">
                <div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;"><?php esc_html_e('Total Clicks', 'smart-seo-fixer'); ?></div>
                <div id="ssf-gsc-clicks" style="font-size: 28px; font-weight: 700; color: #4285f4;">--</div>
            </div>
            <div class="ssf-stat-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; border-top: 4px solid #a855f7;">
                <div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;"><?php esc_html_e('Total Impressions', 'smart-seo-fixer'); ?></div>
                <div id="ssf-gsc-impressions" style="font-size: 28px; font-weight: 700; color: #a855f7;">--</div>
            </div>
            <div class="ssf-stat-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; border-top: 4px solid #22c55e;">
                <div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;"><?php esc_html_e('Average CTR', 'smart-seo-fixer'); ?></div>
                <div id="ssf-gsc-ctr" style="font-size: 28px; font-weight: 700; color: #22c55e;">--%</div>
            </div>
            <div class="ssf-stat-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; border-top: 4px solid #f59e0b;">
                <div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;"><?php esc_html_e('Average Position', 'smart-seo-fixer'); ?></div>
                <div id="ssf-gsc-position" style="font-size: 28px; font-weight: 700; color: #f59e0b;">--</div>
            </div>
        </div>
        
        <!-- Performance Chart -->
        <div class="ssf-card" style="margin-bottom: 24px;">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Performance Over Time', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <div id="ssf-gsc-chart" style="position: relative; height: 300px; width: 100%;">
                    <canvas id="ssf-gsc-canvas"></canvas>
                </div>
                <div id="ssf-gsc-chart-loading" style="text-align: center; padding: 80px 20px; color: #9ca3af;">
                    <span class="spinner is-active" style="float: none;"></span>
                    <p><?php esc_html_e('Loading performance data...', 'smart-seo-fixer'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Top Queries and Top Pages -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- Top Queries -->
            <div class="ssf-card">
                <div class="ssf-card-header">
                    <h2>
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Top Search Queries', 'smart-seo-fixer'); ?>
                    </h2>
                </div>
                <div class="ssf-card-body" style="padding: 0;">
                    <div id="ssf-gsc-queries-loading" style="text-align: center; padding: 40px; color: #9ca3af;">
                        <span class="spinner is-active" style="float: none;"></span>
                    </div>
                    <table class="wp-list-table widefat striped" id="ssf-gsc-queries-table" style="display: none;">
                        <thead>
                            <tr>
                                <th style="width: 40%;"><?php esc_html_e('Query', 'smart-seo-fixer'); ?></th>
                                <th style="text-align: right;"><?php esc_html_e('Clicks', 'smart-seo-fixer'); ?></th>
                                <th style="text-align: right;"><?php esc_html_e('Impressions', 'smart-seo-fixer'); ?></th>
                                <th style="text-align: right;"><?php esc_html_e('CTR', 'smart-seo-fixer'); ?></th>
                                <th style="text-align: right;"><?php esc_html_e('Position', 'smart-seo-fixer'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="ssf-gsc-queries-body"></tbody>
                    </table>
                </div>
            </div>
            
            <!-- Top Pages -->
            <div class="ssf-card">
                <div class="ssf-card-header">
                    <h2>
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php esc_html_e('Top Pages', 'smart-seo-fixer'); ?>
                    </h2>
                </div>
                <div class="ssf-card-body" style="padding: 0;">
                    <div id="ssf-gsc-pages-loading" style="text-align: center; padding: 40px; color: #9ca3af;">
                        <span class="spinner is-active" style="float: none;"></span>
                    </div>
                    <table class="wp-list-table widefat striped" id="ssf-gsc-pages-table" style="display: none;">
                        <thead>
                            <tr>
                                <th style="width: 40%;"><?php esc_html_e('Page', 'smart-seo-fixer'); ?></th>
                                <th style="text-align: right;"><?php esc_html_e('Clicks', 'smart-seo-fixer'); ?></th>
                                <th style="text-align: right;"><?php esc_html_e('Impressions', 'smart-seo-fixer'); ?></th>
                                <th style="text-align: right;"><?php esc_html_e('CTR', 'smart-seo-fixer'); ?></th>
                                <th style="text-align: right;"><?php esc_html_e('Position', 'smart-seo-fixer'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="ssf-gsc-pages-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Pages Not Indexed -->
        <div class="ssf-card" style="margin-top: 24px;">
            <div class="ssf-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2>
                    <span class="dashicons dashicons-warning" style="color: #f59e0b;"></span>
                    <?php esc_html_e('Pages Not Appearing in Search', 'smart-seo-fixer'); ?>
                </h2>
                <button type="button" class="button" id="ssf-gsc-scan-indexed">
                    <span class="dashicons dashicons-search" style="vertical-align: text-bottom;"></span>
                    <?php esc_html_e('Scan Now', 'smart-seo-fixer'); ?>
                </button>
            </div>
            <div class="ssf-card-body">
                <p class="description" style="margin: 0 0 16px;" id="ssf-gsc-index-desc">
                    <?php esc_html_e('Click "Scan Now" to compare your published pages against Google Search Console data and find pages that aren\'t appearing in search results.', 'smart-seo-fixer'); ?>
                </p>
                
                <!-- Summary stats (hidden until scan) -->
                <div id="ssf-gsc-index-summary" style="display: none; margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: #16a34a;" id="ssf-idx-in-search">0</div>
                            <div style="font-size: 12px; color: #15803d;"><?php esc_html_e('In Search Results', 'smart-seo-fixer'); ?></div>
                        </div>
                        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: #dc2626;" id="ssf-idx-not-found">0</div>
                            <div style="font-size: 12px; color: #991b1b;"><?php esc_html_e('Not in Search Results', 'smart-seo-fixer'); ?></div>
                        </div>
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: #2563eb;" id="ssf-idx-total">0</div>
                            <div style="font-size: 12px; color: #1e40af;"><?php esc_html_e('Total Published', 'smart-seo-fixer'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Loading state -->
                <div id="ssf-gsc-index-loading" style="display: none; text-align: center; padding: 40px;">
                    <span class="spinner is-active" style="float: none;"></span>
                    <p style="color: #9ca3af;"><?php esc_html_e('Scanning pages... This may take a moment.', 'smart-seo-fixer'); ?></p>
                </div>
                
                <!-- Results table -->
                <div id="ssf-gsc-index-results" style="display: none;">
                    <table class="wp-list-table widefat striped" id="ssf-gsc-index-table">
                        <thead>
                            <tr>
                                <th style="width: 35%;"><?php esc_html_e('Page', 'smart-seo-fixer'); ?></th>
                                <th style="width: 30%;"><?php esc_html_e('Issues Found', 'smart-seo-fixer'); ?></th>
                                <th style="width: 15%;"><?php esc_html_e('Status', 'smart-seo-fixer'); ?></th>
                                <th style="width: 20%; text-align: right;"><?php esc_html_e('Actions', 'smart-seo-fixer'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="ssf-gsc-index-body"></tbody>
                    </table>
                    <div id="ssf-gsc-index-show-more" style="text-align: center; padding: 12px; display: none;">
                        <button type="button" class="button" id="ssf-gsc-show-all">
                            <?php esc_html_e('Show All', 'smart-seo-fixer'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- No issues found -->
                <div id="ssf-gsc-index-clean" style="display: none; text-align: center; padding: 30px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 48px; color: #16a34a; width: 48px; height: 48px;"></span>
                    <p style="color: #15803d; font-weight: 600; margin-top: 12px;">
                        <?php esc_html_e('All your published pages are appearing in Google search results!', 'smart-seo-fixer'); ?>
                    </p>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<?php if ($gsc_connected): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
jQuery(document).ready(function($) {
    var gscChart = null;
    
    function formatNumber(n) {
        if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
        if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
        return n.toLocaleString();
    }
    
    function loadOverview(refresh) {
        var days = $('#ssf-gsc-days').val();
        
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_gsc_performance',
            nonce: ssfAdmin.nonce,
            type: 'overview',
            days: days,
            refresh: refresh ? 1 : 0
        }, function(response) {
            if (!response.success) {
                $('#ssf-gsc-chart-loading').html('<p style="color: #ef4444;">' + (response.data?.message || 'Error loading data') + '</p>');
                return;
            }
            
            var d = response.data;
            $('#ssf-gsc-clicks').text(formatNumber(d.clicks));
            $('#ssf-gsc-impressions').text(formatNumber(d.impressions));
            $('#ssf-gsc-ctr').text(d.ctr + '%');
            $('#ssf-gsc-position').text(d.position);
            
            // Build chart
            if (d.days && d.days.length > 0) {
                var labels = d.days.map(function(r) { return r.date; });
                var clicks = d.days.map(function(r) { return r.clicks; });
                var impressions = d.days.map(function(r) { return r.impressions; });
                
                $('#ssf-gsc-chart-loading').hide();
                $('#ssf-gsc-canvas').show();
                
                var ctx = document.getElementById('ssf-gsc-canvas').getContext('2d');
                
                if (gscChart) gscChart.destroy();
                
                gscChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: '<?php esc_html_e('Clicks', 'smart-seo-fixer'); ?>',
                                data: clicks,
                                borderColor: '#4285f4',
                                backgroundColor: 'rgba(66, 133, 244, 0.1)',
                                fill: true,
                                tension: 0.3,
                                borderWidth: 2,
                                pointRadius: 1,
                                pointHoverRadius: 5,
                                yAxisID: 'y'
                            },
                            {
                                label: '<?php esc_html_e('Impressions', 'smart-seo-fixer'); ?>',
                                data: impressions,
                                borderColor: '#a855f7',
                                backgroundColor: 'rgba(168, 85, 247, 0.05)',
                                fill: true,
                                tension: 0.3,
                                borderWidth: 2,
                                pointRadius: 1,
                                pointHoverRadius: 5,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    maxTicksLimit: 10,
                                    font: { size: 11 }
                                }
                            },
                            y: {
                                type: 'linear',
                                position: 'left',
                                grid: { color: 'rgba(0,0,0,0.05)' },
                                title: {
                                    display: true,
                                    text: '<?php esc_html_e('Clicks', 'smart-seo-fixer'); ?>',
                                    color: '#4285f4'
                                },
                                ticks: { font: { size: 11 } }
                            },
                            y1: {
                                type: 'linear',
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                title: {
                                    display: true,
                                    text: '<?php esc_html_e('Impressions', 'smart-seo-fixer'); ?>',
                                    color: '#a855f7'
                                },
                                ticks: { font: { size: 11 } }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { usePointStyle: true, boxWidth: 8 }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                padding: 12,
                                cornerRadius: 8
                            }
                        }
                    }
                });
            } else {
                $('#ssf-gsc-chart-loading').html('<p style="color: #9ca3af;"><?php esc_html_e('No data available for this period.', 'smart-seo-fixer'); ?></p>');
            }
        });
    }
    
    function loadQueries(refresh) {
        var days = $('#ssf-gsc-days').val();
        
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_gsc_performance',
            nonce: ssfAdmin.nonce,
            type: 'queries',
            days: days,
            refresh: refresh ? 1 : 0
        }, function(response) {
            $('#ssf-gsc-queries-loading').hide();
            
            if (!response.success || !response.data.rows || response.data.rows.length === 0) {
                $('#ssf-gsc-queries-loading').show().html('<p style="color: #9ca3af;"><?php esc_html_e('No query data.', 'smart-seo-fixer'); ?></p>');
                return;
            }
            
            var html = '';
            var rows = response.data.rows.slice(0, 50);
            rows.forEach(function(row) {
                var ctr = (row.ctr * 100).toFixed(1);
                html += '<tr>';
                html += '<td><strong>' + $('<span>').text(row.keys[0]).html() + '</strong></td>';
                html += '<td style="text-align: right;">' + formatNumber(row.clicks) + '</td>';
                html += '<td style="text-align: right;">' + formatNumber(row.impressions) + '</td>';
                html += '<td style="text-align: right;">' + ctr + '%</td>';
                html += '<td style="text-align: right;">' + row.position.toFixed(1) + '</td>';
                html += '</tr>';
            });
            
            $('#ssf-gsc-queries-body').html(html);
            $('#ssf-gsc-queries-table').show();
        });
    }
    
    function loadPages(refresh) {
        var days = $('#ssf-gsc-days').val();
        
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_gsc_performance',
            nonce: ssfAdmin.nonce,
            type: 'pages',
            days: days,
            refresh: refresh ? 1 : 0
        }, function(response) {
            $('#ssf-gsc-pages-loading').hide();
            
            if (!response.success || !response.data.rows || response.data.rows.length === 0) {
                $('#ssf-gsc-pages-loading').show().html('<p style="color: #9ca3af;"><?php esc_html_e('No page data.', 'smart-seo-fixer'); ?></p>');
                return;
            }
            
            var html = '';
            var siteUrl = <?php echo wp_json_encode($gsc_site_url); ?>;
            var rows = response.data.rows.slice(0, 50);
            rows.forEach(function(row) {
                var url = row.keys[0];
                var shortUrl = url.replace(siteUrl, '/').replace(/\/$/, '') || '/';
                var ctr = (row.ctr * 100).toFixed(1);
                html += '<tr>';
                html += '<td><a href="' + $('<span>').text(url).html() + '" target="_blank" title="' + $('<span>').text(url).html() + '" style="text-decoration: none;">' + $('<span>').text(shortUrl).html() + '</a></td>';
                html += '<td style="text-align: right;">' + formatNumber(row.clicks) + '</td>';
                html += '<td style="text-align: right;">' + formatNumber(row.impressions) + '</td>';
                html += '<td style="text-align: right;">' + ctr + '%</td>';
                html += '<td style="text-align: right;">' + row.position.toFixed(1) + '</td>';
                html += '</tr>';
            });
            
            $('#ssf-gsc-pages-body').html(html);
            $('#ssf-gsc-pages-table').show();
        });
    }
    
    function loadAll(refresh) {
        loadOverview(refresh);
        loadQueries(refresh);
        loadPages(refresh);
    }
    
    // Initial load
    loadAll(false);
    
    // Date range change
    $('#ssf-gsc-days').on('change', function() {
        // Reset UI
        $('#ssf-gsc-clicks, #ssf-gsc-impressions').text('--');
        $('#ssf-gsc-ctr').text('--%');
        $('#ssf-gsc-position').text('--');
        $('#ssf-gsc-chart-loading').show().html('<span class="spinner is-active" style="float: none;"></span><p><?php esc_html_e('Loading...', 'smart-seo-fixer'); ?></p>');
        $('#ssf-gsc-queries-loading').show().html('<span class="spinner is-active" style="float: none;"></span>');
        $('#ssf-gsc-pages-loading').show().html('<span class="spinner is-active" style="float: none;"></span>');
        $('#ssf-gsc-queries-table, #ssf-gsc-pages-table').hide();
        loadAll(false);
    });
    
    // Refresh button
    $('#ssf-gsc-refresh').on('click', function() {
        loadAll(true);
    });
    
    // Submit Sitemap
    $('#ssf-gsc-submit-sitemap').on('click', function() {
        var $btn = $(this);
        var origHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 4px 0 0;"></span> <?php esc_html_e('Submitting...', 'smart-seo-fixer'); ?>');
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_gsc_submit_sitemap',
            nonce: ssfAdmin.nonce
        }, function(response) {
            if (response.success) {
                $btn.html('<span class="dashicons dashicons-yes" style="vertical-align:text-bottom;color:#16a34a;"></span> <?php esc_html_e('Sitemap Submitted!', 'smart-seo-fixer'); ?>');
                setTimeout(function() { $btn.prop('disabled', false).html(origHtml); }, 3000);
            } else {
                $btn.prop('disabled', false).html(origHtml);
                alert(response.data?.message || 'Error submitting sitemap');
            }
        }).fail(function() {
            $btn.prop('disabled', false).html(origHtml);
            alert('Request failed. Please try again.');
        });
    });
    
    // =====================================================
    // Not Indexed Pages Scanner
    // =====================================================
    var allNotIndexed = [];
    var showLimit = 20;
    
    function issueTag(code) {
        var labels = {
            'missing_title': '<span style="background:#fef2f2;color:#dc2626;padding:2px 8px;border-radius:4px;font-size:11px;margin-right:4px;">No SEO Title</span>',
            'missing_description': '<span style="background:#fef2f2;color:#dc2626;padding:2px 8px;border-radius:4px;font-size:11px;margin-right:4px;">No Meta Desc</span>',
            'no_outgoing_links': '<span style="background:#fff7ed;color:#c2410c;padding:2px 8px;border-radius:4px;font-size:11px;margin-right:4px;">No Outgoing Links</span>',
            'no_incoming_links': '<span style="background:#fff7ed;color:#c2410c;padding:2px 8px;border-radius:4px;font-size:11px;margin-right:4px;">No Incoming Links</span>'
        };
        return labels[code] || code;
    }
    
    function renderIndexRows(items) {
        var html = '';
        items.forEach(function(p) {
            var issues = p.issues.map(function(i) { return issueTag(i); }).join('');
            if (!issues) issues = '<span style="color:#16a34a;font-size:12px;">No obvious issues</span>';
            
            var statusColor = p.issue_count > 2 ? '#dc2626' : (p.issue_count > 0 ? '#f59e0b' : '#9ca3af');
            var statusLabel = p.issue_count > 2 ? '<?php esc_html_e('Critical', 'smart-seo-fixer'); ?>' : (p.issue_count > 0 ? '<?php esc_html_e('Needs Fix', 'smart-seo-fixer'); ?>' : '<?php esc_html_e('Check', 'smart-seo-fixer'); ?>');
            
            html += '<tr id="ssf-idx-row-' + p.id + '">';
            html += '<td>';
            html += '<a href="' + $('<span>').text(p.url).html() + '" target="_blank" style="text-decoration:none;font-weight:500;">' + $('<span>').text(p.title).html() + '</a>';
            html += '<div style="font-size:11px;color:#9ca3af;">' + p.post_type + '</div>';
            html += '</td>';
            html += '<td>' + issues + '</td>';
            html += '<td><span style="color:' + statusColor + ';font-weight:600;font-size:12px;">' + statusLabel + '</span></td>';
            html += '<td style="text-align:right;">';
            if (p.issue_count > 0) {
                html += '<button type="button" class="button button-small ssf-idx-fix-btn" data-id="' + p.id + '" data-issues=\'' + JSON.stringify(p.issues) + '\' style="margin-right:4px;">AI Fix</button>';
            }
            html += '<button type="button" class="button button-small ssf-idx-inspect-btn" data-url="' + $('<span>').text(p.url).html() + '" data-id="' + p.id + '">Inspect</button>';
            html += '</td>';
            html += '</tr>';
        });
        return html;
    }
    
    // Scan button
    $('#ssf-gsc-scan-indexed').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 4px 0 0;"></span> <?php esc_html_e('Scanning...', 'smart-seo-fixer'); ?>');
        
        $('#ssf-gsc-index-desc').hide();
        $('#ssf-gsc-index-loading').show();
        $('#ssf-gsc-index-results, #ssf-gsc-index-clean, #ssf-gsc-index-summary').hide();
        
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_gsc_not_indexed',
            nonce: ssfAdmin.nonce
        }, function(response) {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-search" style="vertical-align:text-bottom;"></span> <?php esc_html_e('Scan Now', 'smart-seo-fixer'); ?>');
            $('#ssf-gsc-index-loading').hide();
            
            if (!response.success) {
                $('#ssf-gsc-index-desc').show().text(response.data?.message || 'Error scanning.');
                return;
            }
            
            var d = response.data;
            
            // Show summary
            $('#ssf-idx-total').text(d.total_published);
            $('#ssf-idx-in-search').text(d.total_in_gsc);
            $('#ssf-idx-not-found').text(d.count);
            $('#ssf-gsc-index-summary').show();
            
            if (d.count === 0) {
                $('#ssf-gsc-index-clean').show();
                return;
            }
            
            allNotIndexed = d.not_indexed;
            var showing = allNotIndexed.slice(0, showLimit);
            $('#ssf-gsc-index-body').html(renderIndexRows(showing));
            $('#ssf-gsc-index-results').show();
            
            if (allNotIndexed.length > showLimit) {
                $('#ssf-gsc-index-show-more').show();
                $('#ssf-gsc-show-all').text('<?php esc_html_e('Show All', 'smart-seo-fixer'); ?> (' + allNotIndexed.length + ')');
            } else {
                $('#ssf-gsc-index-show-more').hide();
            }
        });
    });
    
    // Show All
    $(document).on('click', '#ssf-gsc-show-all', function() {
        $('#ssf-gsc-index-body').html(renderIndexRows(allNotIndexed));
        $('#ssf-gsc-index-show-more').hide();
    });
    
    // Inspect URL
    $(document).on('click', '.ssf-idx-inspect-btn', function() {
        var $btn = $(this);
        var url = $btn.data('url');
        var postId = $btn.data('id');
        
        $btn.prop('disabled', true).text('...');
        
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_gsc_inspect_url',
            nonce: ssfAdmin.nonce,
            url: url
        }, function(response) {
            $btn.prop('disabled', false).text('Inspect');
            
            if (!response.success) {
                alert(response.data?.message || 'Inspection failed');
                return;
            }
            
            var r = response.data;
            var verdict = r?.inspectionResult?.indexStatusResult?.verdict || 'UNKNOWN';
            var coverage = r?.inspectionResult?.indexStatusResult?.coverageState || '';
            var crawled = r?.inspectionResult?.indexStatusResult?.lastCrawlTime || '';
            
            var verdictColor = verdict === 'PASS' ? '#16a34a' : (verdict === 'NEUTRAL' ? '#f59e0b' : '#dc2626');
            var $row = $('#ssf-idx-row-' + postId);
            $row.find('td:eq(2)').html(
                '<span style="color:' + verdictColor + ';font-weight:600;font-size:12px;">' + verdict + '</span>' +
                (coverage ? '<div style="font-size:11px;color:#6b7280;margin-top:2px;">' + $('<span>').text(coverage).html() + '</div>' : '')
            );
        });
    });
    
    // AI Fix button
    $(document).on('click', '.ssf-idx-fix-btn', function() {
        var $btn = $(this);
        var postId = $btn.data('id');
        var issues = $btn.data('issues');
        
        $btn.prop('disabled', true).text('<?php esc_html_e('Fixing...', 'smart-seo-fixer'); ?>');
        
        var fixQueue = [];
        
        // Queue up fixes based on issues
        if (issues.indexOf('missing_title') !== -1 || issues.indexOf('missing_description') !== -1) {
            fixQueue.push({action: 'ssf_bulk_ai_fix', post_ids: [postId]});
        }
        if (issues.indexOf('no_incoming_links') !== -1 || issues.indexOf('no_outgoing_links') !== -1) {
            fixQueue.push({action: 'ssf_fix_orphaned_page', post_id: postId});
        }
        
        var completed = 0;
        var results = [];
        
        function runNext() {
            if (completed >= fixQueue.length) {
                $btn.text('<?php esc_html_e('Fixed!', 'smart-seo-fixer'); ?>').css('color', '#16a34a');
                // Update the issues column
                var $row = $('#ssf-idx-row-' + postId);
                $row.find('td:eq(1)').html('<span style="color:#16a34a;font-size:12px;">' + results.join('<br>') + '</span>');
                $row.find('td:eq(2)').html('<span style="color:#16a34a;font-weight:600;font-size:12px;"><?php esc_html_e('Fixed', 'smart-seo-fixer'); ?></span>');
                return;
            }
            
            var fix = fixQueue[completed];
            fix.nonce = ssfAdmin.nonce;
            
            $.post(ssfAdmin.ajax_url, fix, function(response) {
                completed++;
                if (response.success) {
                    results.push(response.data?.message || '<?php esc_html_e('Fixed', 'smart-seo-fixer'); ?>');
                } else {
                    results.push(response.data?.message || '<?php esc_html_e('Could not fix', 'smart-seo-fixer'); ?>');
                }
                runNext();
            }).fail(function() {
                completed++;
                results.push('<?php esc_html_e('Request failed', 'smart-seo-fixer'); ?>');
                runNext();
            });
        }
        
        if (fixQueue.length > 0) {
            runNext();
        } else {
            $btn.text('<?php esc_html_e('Nothing to fix', 'smart-seo-fixer'); ?>');
        }
    });
});
</script>

<style>
@media (max-width: 1200px) {
    .ssf-stats-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 768px) {
    .ssf-stats-grid { grid-template-columns: 1fr !important; }
}
#ssf-gsc-queries-table td, #ssf-gsc-pages-table td,
#ssf-gsc-queries-table th, #ssf-gsc-pages-table th {
    padding: 10px 12px;
    font-size: 13px;
}
#ssf-gsc-queries-table td:first-child,
#ssf-gsc-pages-table td:first-child {
    max-width: 250px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
#ssf-gsc-canvas { display: none; }
#ssf-gsc-index-table td, #ssf-gsc-index-table th {
    padding: 10px 12px;
    font-size: 13px;
    vertical-align: middle;
}
.ssf-idx-fix-btn:disabled { opacity: 0.6; }
@media (max-width: 768px) {
    #ssf-gsc-index-table td:nth-child(2) { display: none; }
    #ssf-gsc-index-table th:nth-child(2) { display: none; }
}
</style>
<?php endif; ?>
