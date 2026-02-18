<?php
/**
 * Broken Links View
 */
if (!defined('ABSPATH')) exit;

$stats = class_exists('SSF_Broken_Links') ? SSF_Broken_Links::get_stats() : ['total' => 0, 'internal' => 0, 'external' => 0, 'dismissed' => 0, 'posts_affected' => 0];
?>
<div class="wrap ssf-wrap">
    <h1 class="ssf-page-title">
        <span class="dashicons dashicons-editor-unlink"></span>
        <?php esc_html_e('Broken Link Checker', 'smart-seo-fixer'); ?>
    </h1>
    
    <!-- Stats Cards -->
    <div class="ssf-stats-row">
        <div class="ssf-mini-stat">
            <span class="ssf-mini-val" style="color: #ef4444;"><?php echo esc_html($stats['total']); ?></span>
            <span class="ssf-mini-label"><?php esc_html_e('Broken Links', 'smart-seo-fixer'); ?></span>
        </div>
        <div class="ssf-mini-stat">
            <span class="ssf-mini-val" style="color: #f59e0b;"><?php echo esc_html($stats['internal']); ?></span>
            <span class="ssf-mini-label"><?php esc_html_e('Internal', 'smart-seo-fixer'); ?></span>
        </div>
        <div class="ssf-mini-stat">
            <span class="ssf-mini-val" style="color: #3b82f6;"><?php echo esc_html($stats['external']); ?></span>
            <span class="ssf-mini-label"><?php esc_html_e('External', 'smart-seo-fixer'); ?></span>
        </div>
        <div class="ssf-mini-stat">
            <span class="ssf-mini-val" style="color: #6b7280;"><?php echo esc_html($stats['posts_affected']); ?></span>
            <span class="ssf-mini-label"><?php esc_html_e('Posts Affected', 'smart-seo-fixer'); ?></span>
        </div>
        <div class="ssf-mini-stat">
            <span class="ssf-mini-val" style="color: #94a3b8;"><?php echo esc_html($stats['dismissed']); ?></span>
            <span class="ssf-mini-label"><?php esc_html_e('Dismissed', 'smart-seo-fixer'); ?></span>
        </div>
    </div>
    
    <!-- Controls -->
    <div class="ssf-toolbar">
        <div class="ssf-toolbar-left">
            <select id="ssf-bl-type" class="ssf-select">
                <option value=""><?php esc_html_e('All Types', 'smart-seo-fixer'); ?></option>
                <option value="internal"><?php esc_html_e('Internal', 'smart-seo-fixer'); ?></option>
                <option value="external"><?php esc_html_e('External', 'smart-seo-fixer'); ?></option>
            </select>
            <select id="ssf-bl-status" class="ssf-select">
                <option value="active"><?php esc_html_e('Active', 'smart-seo-fixer'); ?></option>
                <option value="dismissed"><?php esc_html_e('Dismissed', 'smart-seo-fixer'); ?></option>
                <option value=""><?php esc_html_e('All', 'smart-seo-fixer'); ?></option>
            </select>
            <input type="text" id="ssf-bl-search" class="ssf-input" placeholder="<?php esc_attr_e('Search URL or anchor...', 'smart-seo-fixer'); ?>">
        </div>
        <div class="ssf-toolbar-right">
            <button type="button" class="button" id="ssf-bl-scan-now">
                <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                <?php esc_html_e('Scan Now', 'smart-seo-fixer'); ?>
            </button>
        </div>
    </div>
    
    <!-- Results Table -->
    <div class="ssf-table-wrap">
        <table class="ssf-table" id="ssf-bl-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('URL', 'smart-seo-fixer'); ?></th>
                    <th><?php esc_html_e('Found In', 'smart-seo-fixer'); ?></th>
                    <th><?php esc_html_e('Anchor Text', 'smart-seo-fixer'); ?></th>
                    <th><?php esc_html_e('Status', 'smart-seo-fixer'); ?></th>
                    <th><?php esc_html_e('Type', 'smart-seo-fixer'); ?></th>
                    <th><?php esc_html_e('Detected', 'smart-seo-fixer'); ?></th>
                    <th><?php esc_html_e('Actions', 'smart-seo-fixer'); ?></th>
                </tr>
            </thead>
            <tbody id="ssf-bl-body">
                <tr><td colspan="7" class="ssf-loading"><?php esc_html_e('Loading...', 'smart-seo-fixer'); ?></td></tr>
            </tbody>
        </table>
    </div>
    
    <div class="ssf-pagination" id="ssf-bl-pagination"></div>
</div>

<style>
.ssf-stats-row { display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.ssf-mini-stat { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 20px; text-align: center; min-width: 120px; flex: 1; }
.ssf-mini-val { display: block; font-size: 28px; font-weight: 700; line-height: 1.2; }
.ssf-mini-label { color: #6b7280; font-size: 12px; margin-top: 4px; display: block; }
.ssf-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 12px; flex-wrap: wrap; }
.ssf-toolbar-left { display: flex; gap: 8px; flex-wrap: wrap; }
.ssf-select, .ssf-input { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; }
.ssf-input { min-width: 220px; }
.ssf-table-wrap { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; overflow-x: auto; }
.ssf-table { width: 100%; border-collapse: collapse; }
.ssf-table th { background: #f9fafb; padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.ssf-table td { padding: 10px 14px; border-bottom: 1px solid #f3f4f6; font-size: 13px; color: #1f2937; }
.ssf-table tr:last-child td { border-bottom: none; }
.ssf-table tr:hover { background: #f9fafb; }
.ssf-loading { text-align: center; color: #94a3b8; padding: 40px !important; }
.ssf-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
.ssf-badge-error { background: #fef2f2; color: #dc2626; }
.ssf-badge-warning { background: #fffbeb; color: #d97706; }
.ssf-badge-internal { background: #eff6ff; color: #2563eb; }
.ssf-badge-external { background: #f5f3ff; color: #7c3aed; }
.ssf-url-cell { max-width: 300px; word-break: break-all; }
.ssf-btn-sm { padding: 3px 8px; font-size: 11px; border-radius: 4px; cursor: pointer; border: 1px solid #d1d5db; background: #fff; color: #374151; }
.ssf-btn-sm:hover { background: #f3f4f6; }
.ssf-btn-sm.ssf-btn-danger { border-color: #fca5a5; color: #dc2626; }
.ssf-btn-sm.ssf-btn-danger:hover { background: #fef2f2; }
.ssf-pagination { display: flex; justify-content: center; gap: 4px; margin-top: 16px; }
.ssf-pagination button { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; background: #fff; cursor: pointer; font-size: 13px; }
.ssf-pagination button.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.ssf-pagination button:hover:not(.active) { background: #f3f4f6; }
.ssf-scan-status { padding: 12px 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; margin-bottom: 16px; color: #1e40af; font-size: 13px; display: none; }
</style>

<script>
jQuery(document).ready(function($) {
    var currentPage = 1;
    
    function loadLinks() {
        var data = {
            action: 'ssf_get_broken_links',
            nonce: ssfAdmin.nonce,
            page: currentPage,
            link_type: $('#ssf-bl-type').val(),
            status: $('#ssf-bl-status').val(),
            search: $('#ssf-bl-search').val()
        };
        
        $('#ssf-bl-body').html('<tr><td colspan="7" class="ssf-loading">Loading...</td></tr>');
        
        $.post(ssfAdmin.ajax_url, data, function(response) {
            if (!response.success) {
                $('#ssf-bl-body').html('<tr><td colspan="7" class="ssf-loading">' + (response.data?.message || 'Error') + '</td></tr>');
                return;
            }
            
            var items = response.data.items;
            var html = '';
            
            if (!items.length) {
                html = '<tr><td colspan="7" class="ssf-loading">No broken links found. Click "Scan Now" to check your content.</td></tr>';
            }
            
            $.each(items, function(i, link) {
                var statusBadge = link.status_code >= 400 ? '<span class="ssf-badge ssf-badge-error">' + link.status_code + '</span>' : '<span class="ssf-badge ssf-badge-warning">Error</span>';
                var typeBadge = link.link_type === 'internal' ? '<span class="ssf-badge ssf-badge-internal">Internal</span>' : '<span class="ssf-badge ssf-badge-external">External</span>';
                var editUrl = '<?php echo admin_url('post.php?action=edit&post='); ?>' + link.post_id;
                
                html += '<tr>';
                html += '<td class="ssf-url-cell"><a href="' + link.url + '" target="_blank" rel="noopener">' + $('<span>').text(link.url.substring(0, 60) + (link.url.length > 60 ? '...' : '')).html() + '</a></td>';
                html += '<td><a href="' + editUrl + '" target="_blank">' + $('<span>').text(link.post_title || 'Post #' + link.post_id).html() + '</a></td>';
                html += '<td>' + $('<span>').text(link.anchor_text || '—').html() + '</td>';
                html += '<td>' + statusBadge + '</td>';
                html += '<td>' + typeBadge + '</td>';
                html += '<td>' + link.last_checked + '</td>';
                html += '<td>';
                html += '<button class="ssf-btn-sm ssf-bl-recheck" data-id="' + link.id + '">Recheck</button> ';
                if (link.dismissed == 0) {
                    html += '<button class="ssf-btn-sm ssf-bl-dismiss" data-id="' + link.id + '">Dismiss</button>';
                } else {
                    html += '<button class="ssf-btn-sm ssf-bl-undismiss" data-id="' + link.id + '">Undismiss</button>';
                }
                html += '</td>';
                html += '</tr>';
            });
            
            $('#ssf-bl-body').html(html);
            
            // Pagination
            var pages = response.data.pages;
            var paginationHtml = '';
            for (var p = 1; p <= Math.min(pages, 10); p++) {
                paginationHtml += '<button ' + (p === currentPage ? 'class="active"' : '') + ' data-page="' + p + '">' + p + '</button>';
            }
            $('#ssf-bl-pagination').html(paginationHtml);
        });
    }
    
    // Filters
    $('#ssf-bl-type, #ssf-bl-status').on('change', function() { currentPage = 1; loadLinks(); });
    var searchTimer;
    $('#ssf-bl-search').on('input', function() { clearTimeout(searchTimer); searchTimer = setTimeout(function() { currentPage = 1; loadLinks(); }, 400); });
    
    // Pagination
    $(document).on('click', '#ssf-bl-pagination button', function() { currentPage = $(this).data('page'); loadLinks(); });
    
    // Recheck
    $(document).on('click', '.ssf-bl-recheck', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Checking...');
        $.post(ssfAdmin.ajax_url, { action: 'ssf_recheck_broken_link', nonce: ssfAdmin.nonce, id: $btn.data('id') }, function(response) {
            if (response.success && !response.data.still_broken) {
                $btn.closest('tr').fadeOut(function() { $(this).remove(); });
            } else {
                $btn.prop('disabled', false).text('Recheck');
                alert(response.data?.error || 'Still broken');
            }
        });
    });
    
    // Dismiss
    $(document).on('click', '.ssf-bl-dismiss', function() {
        var $btn = $(this);
        $.post(ssfAdmin.ajax_url, { action: 'ssf_dismiss_broken_link', nonce: ssfAdmin.nonce, id: $btn.data('id') }, function() {
            $btn.closest('tr').fadeOut(function() { $(this).remove(); });
        });
    });
    
    // Undismiss
    $(document).on('click', '.ssf-bl-undismiss', function() {
        var $btn = $(this);
        $.post(ssfAdmin.ajax_url, { action: 'ssf_undismiss_broken_link', nonce: ssfAdmin.nonce, id: $btn.data('id') }, function() {
            loadLinks();
        });
    });
    
    // Scan Now
    $('#ssf-bl-scan-now').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).find('.dashicons').addClass('spin');
        $.post(ssfAdmin.ajax_url, { action: 'ssf_scan_broken_links', nonce: ssfAdmin.nonce }, function(response) {
            $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
            loadLinks();
            if (response.success) {
                alert('Scan complete: ' + response.data.checked + ' links checked, ' + response.data.broken + ' broken found.');
            }
        });
    });
    
    loadLinks();
});
</script>
