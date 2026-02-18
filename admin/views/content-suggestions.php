<?php
/**
 * Content Suggestions View
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap ssf-wrap">
    <h1 class="ssf-page-title">
        <span class="dashicons dashicons-lightbulb"></span>
        <?php esc_html_e('Content Suggestions', 'smart-seo-fixer'); ?>
    </h1>
    
    <div class="ssf-cs-selector">
        <label><?php esc_html_e('Select a post to analyze:', 'smart-seo-fixer'); ?></label>
        <input type="text" id="ssf-cs-search" class="ssf-input" placeholder="<?php esc_attr_e('Type to search posts...', 'smart-seo-fixer'); ?>" style="min-width: 350px;">
        <div id="ssf-cs-results" class="ssf-search-dropdown" style="display: none;"></div>
    </div>
    
    <div id="ssf-cs-output" style="display: none;">
        <div class="ssf-cs-header">
            <div>
                <h2 id="ssf-cs-title" style="margin: 0;"></h2>
                <small id="ssf-cs-meta" style="color: #6b7280;"></small>
            </div>
            <div class="ssf-cs-score-circle" id="ssf-cs-score">—</div>
        </div>
        
        <div id="ssf-cs-list"></div>
    </div>
</div>

<style>
.ssf-cs-selector { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; position: relative; }
.ssf-cs-selector label { font-weight: 600; font-size: 14px; white-space: nowrap; }
.ssf-input { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; }
.ssf-search-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 0 0 8px 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 100; max-height: 300px; overflow-y: auto; }
.ssf-search-dropdown .item { padding: 10px 16px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f3f4f6; }
.ssf-search-dropdown .item:hover { background: #f3f4f6; }

.ssf-cs-header { display: flex; justify-content: space-between; align-items: center; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 16px; }
.ssf-cs-score-circle { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; color: #fff; flex-shrink: 0; }

.ssf-cs-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 20px; margin-bottom: 10px; display: flex; gap: 14px; align-items: flex-start; }
.ssf-cs-priority { width: 8px; border-radius: 4px; flex-shrink: 0; min-height: 40px; }
.ssf-cs-priority.high { background: #ef4444; }
.ssf-cs-priority.medium { background: #f59e0b; }
.ssf-cs-priority.low { background: #3b82f6; }
.ssf-cs-body { flex: 1; }
.ssf-cs-body h4 { margin: 0 0 4px; font-size: 14px; color: #1e293b; }
.ssf-cs-body p { margin: 0; font-size: 13px; color: #64748b; line-height: 1.5; }
.ssf-cs-tags { display: flex; gap: 6px; margin-top: 8px; }
.ssf-cs-tag { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; text-transform: uppercase; background: #f3f4f6; color: #64748b; }
.ssf-cs-tag.cat-seo { background: #dbeafe; color: #1d4ed8; }
.ssf-cs-tag.cat-content { background: #dcfce7; color: #166534; }
.ssf-cs-tag.cat-structure { background: #fef3c7; color: #92400e; }
.ssf-cs-tag.cat-media { background: #fce7f3; color: #9d174d; }
.ssf-cs-tag.cat-engagement { background: #ede9fe; color: #5b21b6; }
.ssf-cs-tag.cat-topical { background: #e0e7ff; color: #3730a3; }
.ssf-cs-loading { text-align: center; padding: 40px; color: #94a3b8; }
</style>

<script>
jQuery(document).ready(function($) {
    function esc(t) { return $('<span>').text(t || '').html(); }
    
    function scoreColor(s) {
        if (s >= 80) return '#10b981';
        if (s >= 60) return '#f59e0b';
        if (s >= 40) return '#f97316';
        return '#ef4444';
    }
    
    // Post search
    var timer;
    $('#ssf-cs-search').on('input', function() {
        var q = $(this).val();
        clearTimeout(timer);
        if (q.length < 2) { $('#ssf-cs-results').hide(); return; }
        timer = setTimeout(function() {
            $.post(ssfAdmin.ajax_url, { action: 'ssf_search_posts_for_schema', nonce: ssfAdmin.nonce, search: q }, function(r) {
                if (!r.success || !r.data.length) { $('#ssf-cs-results').hide(); return; }
                var html = '';
                $.each(r.data, function(i, p) { html += '<div class="item" data-id="' + p.ID + '">' + esc(p.post_title) + '</div>'; });
                $('#ssf-cs-results').html(html).show();
            });
        }, 300);
    });
    
    $(document).on('click', '.ssf-search-dropdown .item', function() {
        var id = $(this).data('id');
        var title = $(this).text();
        $('#ssf-cs-results').hide();
        $('#ssf-cs-search').val(title);
        analyzeSuggestions(id, title);
    });
    
    $(document).on('click', function(e) { if (!$(e.target).closest('.ssf-cs-selector').length) $('#ssf-cs-results').hide(); });
    
    function analyzeSuggestions(postId, title) {
        $('#ssf-cs-output').show();
        $('#ssf-cs-title').text(title);
        $('#ssf-cs-meta').text('Analyzing...');
        $('#ssf-cs-score').css('background', '#94a3b8').text('...');
        $('#ssf-cs-list').html('<div class="ssf-cs-loading">Analyzing content and generating suggestions...</div>');
        
        $.post(ssfAdmin.ajax_url, { action: 'ssf_content_suggestions', nonce: ssfAdmin.nonce, post_id: postId }, function(r) {
            if (!r.success) {
                $('#ssf-cs-list').html('<div class="ssf-cs-loading">' + esc(r.data?.message || 'Error') + '</div>');
                return;
            }
            
            var d = r.data;
            $('#ssf-cs-score').css('background', scoreColor(d.score)).text(d.score);
            $('#ssf-cs-meta').text(d.count + ' suggestions · Source: ' + d.source);
            
            var html = '';
            if (!d.suggestions.length) {
                html = '<div class="ssf-cs-loading">No suggestions — your content looks great!</div>';
            }
            $.each(d.suggestions, function(i, s) {
                html += '<div class="ssf-cs-card">';
                html += '<div class="ssf-cs-priority ' + s.priority + '"></div>';
                html += '<div class="ssf-cs-body">';
                html += '<h4>' + esc(s.title) + '</h4>';
                html += '<p>' + esc(s.description) + '</p>';
                html += '<div class="ssf-cs-tags">';
                html += '<span class="ssf-cs-tag cat-' + s.category + '">' + s.category + '</span>';
                html += '<span class="ssf-cs-tag">' + s.priority + '</span>';
                html += '</div></div></div>';
            });
            $('#ssf-cs-list').html(html);
        });
    }
});
</script>
