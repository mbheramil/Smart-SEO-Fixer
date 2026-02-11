<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap ssf-wrap">
    <h1 class="ssf-page-title">
        <span class="dashicons dashicons-randomize"></span>
        <?php esc_html_e('Redirect Manager', 'smart-seo-fixer'); ?>
        <span class="ssf-version">v<?php echo esc_html(SSF_VERSION); ?></span>
    </h1>
    
    <!-- Add New Redirect -->
    <div class="ssf-card" style="margin-bottom:20px;">
        <div class="ssf-card-header">
            <h2><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Add Redirect', 'smart-seo-fixer'); ?></h2>
        </div>
        <div class="ssf-card-body">
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                <div style="flex:1;min-width:200px;">
                    <label style="display:block;font-weight:500;margin-bottom:4px;font-size:13px;"><?php esc_html_e('From URL (old path)', 'smart-seo-fixer'); ?></label>
                    <input type="text" id="ssf-redir-from" class="regular-text" style="width:100%;" placeholder="/old-page/">
                </div>
                <div style="flex:1;min-width:200px;">
                    <label style="display:block;font-weight:500;margin-bottom:4px;font-size:13px;"><?php esc_html_e('To URL (new destination)', 'smart-seo-fixer'); ?></label>
                    <input type="text" id="ssf-redir-to" class="regular-text" style="width:100%;" placeholder="<?php echo esc_attr(home_url('/new-page/')); ?>">
                </div>
                <div style="min-width:120px;">
                    <label style="display:block;font-weight:500;margin-bottom:4px;font-size:13px;"><?php esc_html_e('Type', 'smart-seo-fixer'); ?></label>
                    <select id="ssf-redir-type" style="width:100%;">
                        <option value="301"><?php esc_html_e('301 Permanent', 'smart-seo-fixer'); ?></option>
                        <option value="302"><?php esc_html_e('302 Temporary', 'smart-seo-fixer'); ?></option>
                        <option value="307"><?php esc_html_e('307 Temporary', 'smart-seo-fixer'); ?></option>
                    </select>
                </div>
                <div style="min-width:200px;">
                    <label style="display:block;font-weight:500;margin-bottom:4px;font-size:13px;"><?php esc_html_e('Note (optional)', 'smart-seo-fixer'); ?></label>
                    <input type="text" id="ssf-redir-note" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e('Why this redirect exists', 'smart-seo-fixer'); ?>">
                </div>
                <button type="button" class="button button-primary" id="ssf-add-redirect" style="height:30px;">
                    <span class="dashicons dashicons-plus" style="line-height:1.5;"></span> <?php esc_html_e('Add', 'smart-seo-fixer'); ?>
                </button>
            </div>
            <p class="description" style="margin-top:8px;">
                <?php esc_html_e('Use relative paths for "From" (e.g., /old-page/). Use full URLs for "To" (e.g., https://site.com/new-page/). Add * at end for wildcard matching.', 'smart-seo-fixer'); ?>
            </p>
        </div>
    </div>
    
    <!-- Redirects Table -->
    <div class="ssf-card" style="margin-bottom:20px;">
        <div class="ssf-card-header">
            <h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e('Active Redirects', 'smart-seo-fixer'); ?> <span id="ssf-redir-count" class="ssf-badge" style="display:none;">0</span></h2>
            <button type="button" class="button" id="ssf-refresh-redirects"><span class="dashicons dashicons-update" style="line-height:1.4;"></span> <?php esc_html_e('Refresh', 'smart-seo-fixer'); ?></button>
        </div>
        <div class="ssf-card-body" style="padding:0;">
            <div id="ssf-redir-loading" style="padding:30px;text-align:center;"><span class="spinner is-active" style="float:none;"></span></div>
            <div id="ssf-redir-empty" style="display:none;padding:30px;text-align:center;color:#6b7280;">
                <span class="dashicons dashicons-randomize" style="font-size:40px;width:40px;height:40px;color:#d1d5db;"></span>
                <p><?php esc_html_e('No redirects yet. Add one above or change a post slug — the plugin will auto-create one.', 'smart-seo-fixer'); ?></p>
            </div>
            <table id="ssf-redir-table" class="widefat striped" style="display:none;border:0;">
                <thead>
                    <tr>
                        <th style="width:40px;"><?php esc_html_e('On', 'smart-seo-fixer'); ?></th>
                        <th><?php esc_html_e('From', 'smart-seo-fixer'); ?></th>
                        <th><?php esc_html_e('To', 'smart-seo-fixer'); ?></th>
                        <th style="width:60px;"><?php esc_html_e('Type', 'smart-seo-fixer'); ?></th>
                        <th><?php esc_html_e('Note', 'smart-seo-fixer'); ?></th>
                        <th style="width:80px;"><?php esc_html_e('Actions', 'smart-seo-fixer'); ?></th>
                    </tr>
                </thead>
                <tbody id="ssf-redir-tbody"></tbody>
            </table>
        </div>
    </div>
    
    <!-- 404 Log -->
    <div class="ssf-card">
        <div class="ssf-card-header">
            <h2><span class="dashicons dashicons-warning"></span> <?php esc_html_e('404 Error Log', 'smart-seo-fixer'); ?></h2>
            <button type="button" class="button" id="ssf-clear-404" style="color:#dc2626;border-color:#dc2626;"><?php esc_html_e('Clear Log', 'smart-seo-fixer'); ?></button>
        </div>
        <div class="ssf-card-body" style="padding:0;">
            <div id="ssf-404-loading" style="padding:30px;text-align:center;"><span class="spinner is-active" style="float:none;"></span></div>
            <div id="ssf-404-empty" style="display:none;padding:30px;text-align:center;color:#6b7280;">
                <p><?php esc_html_e('No 404 errors logged yet.', 'smart-seo-fixer'); ?></p>
            </div>
            <table id="ssf-404-table" class="widefat striped" style="display:none;border:0;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('URL', 'smart-seo-fixer'); ?></th>
                        <th style="width:60px;"><?php esc_html_e('Hits', 'smart-seo-fixer'); ?></th>
                        <th><?php esc_html_e('Last Hit', 'smart-seo-fixer'); ?></th>
                        <th style="width:120px;"><?php esc_html_e('Action', 'smart-seo-fixer'); ?></th>
                    </tr>
                </thead>
                <tbody id="ssf-404-tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function loadRedirects() {
        $('#ssf-redir-loading').show();
        $('#ssf-redir-table,#ssf-redir-empty').hide();
        $.post(ssfAdmin.ajax_url, {action:'ssf_get_redirects',nonce:ssfAdmin.nonce}, function(r) {
            $('#ssf-redir-loading').hide();
            if (!r.success || !r.data.redirects.length) { $('#ssf-redir-empty').show(); $('#ssf-redir-count').hide(); return; }
            var html = '';
            r.data.redirects.forEach(function(rd) {
                var badge = rd.type == 301 ? 'background:#dbeafe;color:#1d4ed8;' : 'background:#fef3c7;color:#92400e;';
                html += '<tr data-id="'+rd.id+'">';
                html += '<td><input type="checkbox" class="ssf-toggle-redir" data-id="'+rd.id+'" '+(rd.enabled?'checked':'')+'></td>';
                html += '<td style="'+(rd.enabled?'':'opacity:0.5;')+'"><code>'+escH(rd.from)+'</code>'+(rd.auto?' <small style="color:#059669;">⚡ auto</small>':'')+'</td>';
                html += '<td style="'+(rd.enabled?'':'opacity:0.5;')+'"><a href="'+escH(rd.to)+'" target="_blank">'+escH(rd.to)+'</a></td>';
                html += '<td><span style="'+badge+'padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600;">'+rd.type+'</span></td>';
                html += '<td style="font-size:12px;color:#6b7280;">'+escH(rd.note||'')+'</td>';
                html += '<td><button type="button" class="button ssf-del-redir" data-id="'+rd.id+'" style="color:#dc2626;border-color:#dc2626;font-size:12px;padding:0 8px;min-height:26px;">Delete</button></td>';
                html += '</tr>';
            });
            $('#ssf-redir-tbody').html(html);
            $('#ssf-redir-table').show();
            $('#ssf-redir-count').text(r.data.redirects.length).show();
        });
    }
    
    function load404Log() {
        $('#ssf-404-loading').show();
        $('#ssf-404-table,#ssf-404-empty').hide();
        $.post(ssfAdmin.ajax_url, {action:'ssf_get_404_log',nonce:ssfAdmin.nonce}, function(r) {
            $('#ssf-404-loading').hide();
            if (!r.success || !r.data.log.length) { $('#ssf-404-empty').show(); return; }
            var html = '';
            r.data.log.forEach(function(entry) {
                html += '<tr>';
                html += '<td><code>'+escH(entry.url)+'</code></td>';
                html += '<td><strong>'+entry.hits+'</strong></td>';
                html += '<td style="font-size:12px;">'+escH(entry.last_hit||'')+'</td>';
                html += '<td><button type="button" class="button ssf-404-to-redir" data-url="'+escA(entry.url)+'" style="font-size:12px;padding:0 8px;min-height:26px;">→ Redirect</button></td>';
                html += '</tr>';
            });
            $('#ssf-404-tbody').html(html);
            $('#ssf-404-table').show();
        });
    }
    
    loadRedirects();
    load404Log();
    
    $('#ssf-refresh-redirects').on('click', loadRedirects);
    
    // Add redirect
    $('#ssf-add-redirect').on('click', function() {
        var $btn = $(this);
        var from = $('#ssf-redir-from').val().trim();
        var to = $('#ssf-redir-to').val().trim();
        if (!from || !to) { alert('<?php echo esc_js(__('Both From and To URLs are required.', 'smart-seo-fixer')); ?>'); return; }
        $btn.prop('disabled',true);
        $.post(ssfAdmin.ajax_url, {action:'ssf_add_redirect',nonce:ssfAdmin.nonce,from:from,to:to,redirect_type:$('#ssf-redir-type').val(),note:$('#ssf-redir-note').val()}, function(r) {
            $btn.prop('disabled',false);
            if (r.success) { $('#ssf-redir-from,#ssf-redir-to,#ssf-redir-note').val(''); loadRedirects(); }
            else { alert(r.data.message); }
        }).fail(function() { $btn.prop('disabled',false); });
    });
    
    // Toggle redirect
    $(document).on('change', '.ssf-toggle-redir', function() {
        $.post(ssfAdmin.ajax_url, {action:'ssf_toggle_redirect',nonce:ssfAdmin.nonce,redirect_id:$(this).data('id')});
        var $row = $(this).closest('tr');
        $row.find('td:not(:first-child):not(:last-child)').css('opacity', this.checked?1:0.5);
    });
    
    // Delete redirect
    $(document).on('click', '.ssf-del-redir', function() {
        if (!confirm('<?php echo esc_js(__('Delete this redirect?', 'smart-seo-fixer')); ?>')) return;
        var $btn = $(this);
        $.post(ssfAdmin.ajax_url, {action:'ssf_delete_redirect',nonce:ssfAdmin.nonce,redirect_id:$btn.data('id')}, function(r) {
            if (r.success) { $btn.closest('tr').fadeOut(200, function() { $(this).remove(); }); }
        });
    });
    
    // 404 → Redirect
    $(document).on('click', '.ssf-404-to-redir', function() {
        var url = $(this).data('url');
        $('#ssf-redir-from').val(url);
        $('#ssf-redir-to').focus();
        $('html,body').animate({scrollTop:0}, 300);
    });
    
    // Clear 404 log
    $('#ssf-clear-404').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Clear all 404 log entries?', 'smart-seo-fixer')); ?>')) return;
        $.post(ssfAdmin.ajax_url, {action:'ssf_clear_404_log',nonce:ssfAdmin.nonce}, function() { load404Log(); });
    });
    
    function escH(t) { var d=document.createElement('div'); d.textContent=t||''; return d.innerHTML; }
    function escA(t) { return (t||'').replace(/'/g,"&#39;").replace(/"/g,"&quot;"); }
});
</script>
