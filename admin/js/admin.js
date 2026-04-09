/**
 * Smart SEO Fixer - Admin JavaScript
 */

(function($) {
    'use strict';
    
    var SSF = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // General UI events are handled inline in the views
            // This file provides utility functions
        },
        
        /**
         * Make AJAX request with error handling
         */
        ajax: function(action, data, callback, errorCallback) {
            data = data || {};
            data.action = action;
            data.nonce = ssfAdmin.nonce;
            
            $.post(ssfAdmin.ajax_url, data, function(response) {
                if (callback) {
                    callback(response);
                }
            }).fail(function(xhr, status, error) {
                console.error('SSF AJAX Error (' + action + '):', error, xhr.responseText);
                if (errorCallback) {
                    errorCallback(error, xhr);
                } else if (callback) {
                    callback({ success: false, data: { message: error || 'Request failed' } });
                }
            });
        },
        
        /**
         * Analyze a post
         */
        analyzePost: function(postId, callback) {
            this.ajax('ssf_analyze_post', {post_id: postId}, callback);
        },
        
        /**
         * Generate title with AI
         */
        generateTitle: function(postId, focusKeyword, callback) {
            this.ajax('ssf_generate_title', {
                post_id: postId,
                focus_keyword: focusKeyword
            }, callback);
        },
        
        /**
         * Generate meta description with AI
         */
        generateDescription: function(postId, focusKeyword, callback) {
            this.ajax('ssf_generate_description', {
                post_id: postId,
                focus_keyword: focusKeyword
            }, callback);
        },
        
        /**
         * Suggest keywords
         */
        suggestKeywords: function(postId, callback) {
            this.ajax('ssf_suggest_keywords', {post_id: postId}, callback);
        },
        
        /**
         * Save SEO data
         */
        saveSeoData: function(postId, data, callback) {
            data.post_id = postId;
            this.ajax('ssf_save_seo_data', data, callback);
        },
        
        /**
         * Get score class based on score value
         */
        getScoreClass: function(score) {
            if (score >= 80) return 'good';
            if (score >= 60) return 'ok';
            return 'poor';
        },
        
        /**
         * Get grade based on score
         */
        getGrade: function(score) {
            if (score >= 90) return 'A';
            if (score >= 80) return 'B';
            if (score >= 70) return 'C';
            if (score >= 60) return 'D';
            return 'F';
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        /**
         * Show notification
         */
        notify: function(message, type) {
            type = type || 'success';
            
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').first().after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Show loading state on button
         */
        buttonLoading: function($btn, loading) {
            if (loading) {
                $btn.data('original-html', $btn.html());
                $btn.prop('disabled', true);
                $btn.find('.dashicons').addClass('spin');
            } else {
                $btn.prop('disabled', false);
                $btn.find('.dashicons').removeClass('spin');
                
                var originalHtml = $btn.data('original-html');
                if (originalHtml) {
                    $btn.html(originalHtml);
                }
            }
        },
        
        /**
         * Format date
         */
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        },
        
        /**
         * Truncate text
         */
        truncate: function(text, length) {
            if (text.length <= length) return text;
            return text.substring(0, length) + '...';
        },
        
        /**
         * Bulk process posts
         */
        bulkProcess: function(postIds, action, options, progressCallback, completeCallback) {
            var self = this;
            var processed = 0;
            var total = postIds.length;
            var results = [];
            
            function processNext() {
                if (processed >= total) {
                    if (completeCallback) {
                        completeCallback(results);
                    }
                    return;
                }
                
                var postId = postIds[processed];
                
                self.ajax(action, $.extend({post_id: postId}, options), function(response) {
                    processed++;
                    results.push({
                        post_id: postId,
                        success: response.success,
                        data: response.data
                    });
                    
                    if (progressCallback) {
                        progressCallback(processed, total, response);
                    }
                    
                    // Process next with slight delay to avoid overwhelming server
                    setTimeout(processNext, 500);
                });
            }
            
            processNext();
        },
        
        /**
         * Render analysis results
         */
        renderAnalysis: function($container, data) {
            var html = '';
            
            // Issues
            if (data.issues && data.issues.length) {
                html += '<div class="ssf-issues-section">';
                html += '<h4>Issues</h4>';
                data.issues.forEach(function(issue) {
                    html += '<div class="ssf-issue-item">';
                    html += '<span class="dashicons dashicons-warning"></span>';
                    html += '<span>' + this.escapeHtml(issue.message) + '</span>';
                    if (issue.fix) {
                        html += '<small>' + this.escapeHtml(issue.fix) + '</small>';
                    }
                    html += '</div>';
                }.bind(this));
                html += '</div>';
            }
            
            // Warnings
            if (data.warnings && data.warnings.length) {
                html += '<div class="ssf-warnings-section">';
                html += '<h4>Warnings</h4>';
                data.warnings.forEach(function(warning) {
                    html += '<div class="ssf-warning-item">';
                    html += '<span class="dashicons dashicons-info"></span>';
                    html += '<span>' + this.escapeHtml(warning.message) + '</span>';
                    html += '</div>';
                }.bind(this));
                html += '</div>';
            }
            
            // Passed
            if (data.passed && data.passed.length) {
                html += '<div class="ssf-passed-section">';
                html += '<h4>Passed</h4>';
                data.passed.forEach(function(item) {
                    html += '<div class="ssf-passed-item">';
                    html += '<span class="dashicons dashicons-yes-alt"></span>';
                    html += '<span>' + this.escapeHtml(item.message) + '</span>';
                    html += '</div>';
                }.bind(this));
                html += '</div>';
            }
            
            $container.html(html);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        SSF.init();
        SSF_ClientReport.init();
    });
    
    // Expose to global scope
    window.SSF = SSF;

    /* ======================================================================
       Client Report Module
       ====================================================================== */
    var SSF_ClientReport = {
        init: function() {
            if (!$('#ssf-report-config').length) return;

            // Toggle custom dates
            $('#ssf-report-range').on('change', function() {
                $('#ssf-custom-dates').toggle($(this).val() === 'custom');
            });

            // Toggle full-mode-only checkboxes and description
            $('#ssf-report-mode').on('change', function() {
                var isFull = $(this).val() === 'full';
                $('.ssf-full-mode-only').toggle(isFull);
                $('#ssf-mode-description').text(isFull
                    ? 'Show everything — positive results, issues, pages needing work, and recommendations.'
                    : 'Show only positive metrics — great for client-facing reports.');
            });

            $('#ssf-generate-report').on('click', this.generate.bind(this));
            $('#ssf-print-report').on('click', function() { window.print(); });
            $('#ssf-download-pdf').on('click', this.downloadPDF.bind(this));
            $('#ssf-new-report').on('click', function() {
                $('#ssf-report-output').hide();
                $('#ssf-report-config').show();
            });

            // Template fetch
            $('#ssf-fetch-template').on('click', this.fetchTemplate.bind(this));
            $('#ssf-clear-template').on('click', this.clearTemplate.bind(this));
        },

        fetchTemplate: function() {
            var url = $('#ssf-template-url').val().trim();
            if (!url) { alert('Please enter a template URL.'); return; }
            var $status = $('#ssf-template-status');
            $status.html('<span class="spinner is-active" style="float:none;margin:0 5px 0 0;"></span> Fetching template…');
            $.post(ajaxurl, {
                action: 'ssf_fetch_report_template',
                nonce: SSF.nonce,
                template_url: url
            }).done(function(res) {
                if (res.success) {
                    $status.html('<span class="ssf-template-loaded">&#10003; Template loaded</span>');
                } else {
                    $status.html('<span style="color:#d63638;">' + (res.data || 'Failed to fetch template.') + '</span>');
                }
            }).fail(function() {
                $status.html('<span style="color:#d63638;">Request failed.</span>');
            });
        },

        clearTemplate: function() {
            var $status = $('#ssf-template-status');
            $.post(ajaxurl, {
                action: 'ssf_clear_report_template',
                nonce: SSF.nonce
            }).done(function(res) {
                if (res.success) {
                    $('#ssf-template-url').val('');
                    $status.html('<span style="color:#666;">Template cleared.</span>');
                } else {
                    $status.html('<span style="color:#d63638;">Failed to clear template.</span>');
                }
            }).fail(function() {
                $status.html('<span style="color:#d63638;">Request failed.</span>');
            });
        },

        generate: function() {
            var $btn = $('#ssf-generate-report');
            var sections = [];
            $('input[name="ssf_section"]:checked').each(function() {
                sections.push($(this).val());
            });

            if (!sections.length) {
                alert('Please select at least one section.');
                return;
            }

            $btn.prop('disabled', true).text('Generating...');

            $.post(ssfAdmin.ajax_url, {
                action: 'ssf_generate_client_report',
                nonce: ssfAdmin.nonce,
                date_range: $('#ssf-report-range').val(),
                start_date: $('#ssf-report-start').val() || '',
                end_date: $('#ssf-report-end').val() || '',
                mode: $('#ssf-report-mode').val() || 'positive',
                sections: sections
            }, function(res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-analytics"></span> Generate Report');
                if (res.success) {
                    SSF_ClientReport.render(res.data);
                    $('#ssf-report-config').hide();
                    $('#ssf-report-output').show();
                } else {
                    alert(res.data && res.data.message ? res.data.message : 'Failed to generate report.');
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-analytics"></span> Generate Report');
                alert('Request failed. Please try again.');
            });
        },

        render: function(d) {
            // Cover
            if (d.site_icon) {
                $('#ssf-report-icon').html('<img src="' + this.esc(d.site_icon) + '" alt="" />');
            } else {
                $('#ssf-report-icon').html('<span class="dashicons dashicons-admin-site-alt3" style="font-size:60px;width:60px;height:60px;"></span>');
            }
            $('#ssf-report-site-name').text(d.site_name || '');
            $('#ssf-report-tagline').text(d.site_tagline || '');
            $('#ssf-report-url').text(d.site_url || '');
            $('#ssf-report-period').text(d.date_range ? d.date_range.label : '');
            $('#ssf-report-generated').text('Generated: ' + (d.generated_at || ''));

            var sec = d.sections || {};

            // Toggle section visibility — only show sections with data
            var allSections = ['overview', 'meta_coverage', 'score_distribution', 'top_pages', 'content_health', 'image_seo', 'schema_coverage', 'redirects', 'keywords', 'broken_links_fixed', 'optimizations', 'sitemap_status', 'worst_pages', 'issues'];
            allSections.forEach(function(s) {
                var $el = $('#ssf-section-' + s);
                if (sec[s] !== undefined) { $el.show(); } else { $el.hide(); }
            });

            var isFullMode = d.mode === 'full';

            // ── Overview & Score ──
            if (sec.overview) {
                var ov = sec.overview;
                var score = ov.avg_score || 0;
                this.animateRing(score);

                // Grade label under score ring
                if (ov.grade_label) {
                    $('#ssf-report-grade').html('<span class="ssf-grade-badge grade-' + (ov.grade || '').toLowerCase().replace('+','plus') + '">' + this.esc(ov.grade) + ' — ' + this.esc(ov.grade_label) + '</span>');
                }

                var grid = '';
                grid += this.statBox(ov.total_analyzed, 'Pages Analyzed', ov.analyzed_pct ? ov.analyzed_pct + '% of published content' : '');
                grid += this.statBox(ov.good_count, 'Good Score (80+)');
                grid += this.statBox(ov.ok_count, 'OK Score (60-79)');
                grid += this.statBox(ov.healthy_pct + '%', 'Healthy Pages', 'Scoring 60 or above');
                if (isFullMode && ov.needs_work_count !== undefined) {
                    grid += this.statBox(ov.needs_work_count, 'Needs Work (<60)', '', 'ssf-stat-warning');
                }
                if (isFullMode && ov.not_analyzed !== undefined && ov.not_analyzed > 0) {
                    grid += this.statBox(ov.not_analyzed, 'Not Yet Analyzed', '', 'ssf-stat-info');
                }
                $('#ssf-overview-grid').html(grid);
            }

            // ── Meta Tag Coverage ──
            if (sec.meta_coverage) {
                var mc = sec.meta_coverage;
                var mh = '<div class="ssf-report-progress-list">';
                mh += this.progressBar('SEO Titles', mc.with_title, mc.total, mc.title_pct);
                mh += this.progressBar('Meta Descriptions', mc.with_description, mc.total, mc.description_pct);
                mh += this.progressBar('Focus Keywords', mc.with_keyword, mc.total, mc.keyword_pct);
                mh += '</div>';
                if (isFullMode && (mc.missing_title > 0 || mc.missing_description > 0)) {
                    mh += '<div class="ssf-report-alert ssf-alert-warning" style="margin-top:16px;">';
                    mh += '<strong>Action Needed:</strong> ';
                    var parts = [];
                    if (mc.missing_title > 0) parts.push(mc.missing_title + ' pages missing SEO titles');
                    if (mc.missing_description > 0) parts.push(mc.missing_description + ' pages missing meta descriptions');
                    if (mc.missing_keyword > 0) parts.push(mc.missing_keyword + ' pages missing focus keywords');
                    mh += parts.join(', ') + '.';
                    mh += '</div>';
                }
                $('#ssf-meta-coverage-content').html(mh);
            }

            // ── Score distribution ──
            if (sec.score_distribution && sec.score_distribution.length) {
                var maxCount = Math.max.apply(null, sec.score_distribution.map(function(b) { return b.count; }));
                var html = '';
                sec.score_distribution.forEach(function(b) {
                    var pct = maxCount > 0 ? (b.count / maxCount * 100) : 0;
                    html += '<div class="ssf-bar-row">';
                    html += '<span class="ssf-bar-label">' + SSF_ClientReport.esc(b.label) + '</span>';
                    html += '<div class="ssf-bar-track"><div class="ssf-bar-fill ' + b.label.toLowerCase() + '" style="width:' + pct + '%"></div></div>';
                    html += '<span class="ssf-bar-count">' + b.count + ' <small>(' + (b.pct || 0) + '%)</small></span>';
                    html += '</div>';
                });
                $('#ssf-dist-chart').html(html);
            }

            // ── Top pages ──
            if (sec.top_pages && sec.top_pages.length) {
                var tbody = '';
                sec.top_pages.forEach(function(p, i) {
                    var cls = p.score >= 90 ? 'excellent' : (p.score >= 80 ? 'good' : 'fair');
                    tbody += '<tr>';
                    tbody += '<td>' + (i + 1) + '</td>';
                    tbody += '<td>' + SSF_ClientReport.esc(p.title) + '</td>';
                    tbody += '<td>' + SSF_ClientReport.esc(p.post_type) + '</td>';
                    tbody += '<td><span class="score-badge ' + cls + '">' + p.score + '</span></td>';
                    tbody += '<td>' + SSF_ClientReport.esc(p.grade) + '</td>';
                    tbody += '</tr>';
                });
                $('#ssf-top-pages-table tbody').html(tbody);
            }

            // ── Content Health ──
            if (sec.content_health) {
                var ch = sec.content_health;
                var chh = '<div class="ssf-report-info-grid">';
                chh += this.infoCard(ch.avg_word_count.toLocaleString(), 'Avg. Word Count');
                chh += this.infoCard(ch.total_words.toLocaleString(), 'Total Words');
                chh += this.infoCard(ch.avg_readability, 'Readability Score', ch.readability_label || '');
                chh += this.infoCard(ch.avg_images, 'Avg. Images/Page');
                chh += this.infoCard(ch.avg_links, 'Avg. Links/Page');
                chh += this.infoCard(ch.total_analyzed.toLocaleString(), 'Pages Analyzed');
                chh += '</div>';
                $('#ssf-content-health-content').html(chh);
            }

            // ── Image SEO ──
            if (sec.image_seo) {
                var im = sec.image_seo;
                var imh = '<div class="ssf-report-info-grid" style="margin-bottom:16px;">';
                imh += this.infoCard(im.total_images.toLocaleString(), 'Total Images');
                imh += this.infoCard(im.with_alt.toLocaleString(), 'With Alt Text');
                imh += this.infoCard(im.alt_pct + '%', 'Alt Text Coverage');
                imh += this.infoCard(im.posts_with_images.toLocaleString(), 'Pages With Images');
                imh += '</div>';
                if (im.alt_pct >= 80) {
                    imh += '<p class="ssf-report-note ssf-note-positive">Excellent image accessibility — ' + im.alt_pct + '% of images have descriptive alt text.</p>';
                } else if (im.alt_pct >= 50) {
                    imh += '<p class="ssf-report-note ssf-note-positive">Good progress — over half of your images have alt text for accessibility and SEO.</p>';
                }
                if (isFullMode && im.without_alt > 0) {
                    imh += '<div class="ssf-report-alert ssf-alert-warning">' + im.without_alt + ' images are missing alt text. Alt text improves accessibility and helps search engines understand your images.</div>';
                }
                $('#ssf-image-seo-content').html(imh);
            }

            // ── Schema ──
            if (sec.schema_coverage) {
                var sc = sec.schema_coverage;
                var sh = '';
                if (sc.auto_coverage_note) {
                    sh += '<p class="ssf-report-note ssf-note-positive">' + this.esc(sc.auto_coverage_note) + '</p>';
                }
                sh += '<div class="ssf-report-info-grid">';
                if (sc.custom_schema > 0) {
                    sh += this.infoCard(sc.custom_schema, 'Custom Schema Entries');
                }
                sh += this.infoCard(sc.auto_schema_types ? sc.auto_schema_types.length : 0, 'Auto Schema Types');
                sh += '</div>';
                if (sc.auto_schema_types && sc.auto_schema_types.length) {
                    sh += '<div class="ssf-report-tag-list">';
                    sc.auto_schema_types.forEach(function(t) {
                        sh += '<span class="ssf-report-tag">' + SSF_ClientReport.esc(t) + '</span>';
                    });
                    sh += '</div>';
                }
                $('#ssf-schema-coverage-content').html(sh);
            }

            // ── Redirects ──
            if (sec.redirects) {
                var rd = sec.redirects;
                var rh = '<div class="ssf-report-info-grid">';
                rh += this.infoCard(rd.total_active, 'Active Redirects');
                if (rd.auto_created > 0) rh += this.infoCard(rd.auto_created, 'Auto-Created');
                if (rd.manual > 0) rh += this.infoCard(rd.manual, 'Manual');
                rh += '</div>';
                rh += '<p class="ssf-report-note">Active redirects protect your link equity by ensuring old URLs reach the right destination.</p>';
                $('#ssf-redirects-content').html(rh);
            }

            // ── Keywords ──
            if (sec.keywords) {
                var kw = sec.keywords;
                var kh = '<div class="ssf-report-info-grid" style="margin-bottom:16px;">';
                kh += this.infoCard(kw.total_tracked, 'Keywords Tracked');
                if (kw.total_clicks > 0) kh += this.infoCard(kw.total_clicks.toLocaleString(), 'Total Clicks');
                if (kw.total_impressions > 0) kh += this.infoCard(kw.total_impressions.toLocaleString(), 'Total Impressions');
                kh += '</div>';
                if (kw.top_keywords && kw.top_keywords.length) {
                    kh += '<table class="ssf-report-kw-table"><thead><tr>';
                    kh += '<th>Keyword</th><th>Avg. Position</th><th>Clicks</th><th>Impressions</th>';
                    kh += '</tr></thead><tbody>';
                    kw.top_keywords.forEach(function(k) {
                        kh += '<tr>';
                        kh += '<td><strong>' + SSF_ClientReport.esc(k.keyword) + '</strong></td>';
                        kh += '<td>' + k.position + '</td>';
                        kh += '<td>' + k.clicks + '</td>';
                        kh += '<td>' + k.impressions.toLocaleString() + '</td>';
                        kh += '</tr>';
                    });
                    kh += '</tbody></table>';
                }
                $('#ssf-keywords-content').html(kh);
            }

            // ── Broken links ──
            if (sec.broken_links_fixed) {
                var bl = sec.broken_links_fixed;
                var bh = '<div class="ssf-report-info-grid">';
                bh += this.infoCard(bl.fixed, 'Links Fixed');
                if (isFullMode && bl.unfixed > 0) bh += this.infoCard(bl.unfixed, 'Still Broken', '', 'ssf-card-error');
                if (isFullMode && bl.total > 0) bh += this.infoCard(bl.total, 'Total Found');
                bh += '</div>';
                if (bl.fixed > 0) {
                    bh += '<p class="ssf-report-note ssf-note-positive">' + bl.fixed + ' broken link(s) have been identified and addressed, keeping your visitors on the right path.</p>';
                }
                if (isFullMode && bl.unfixed > 0) {
                    bh += '<div class="ssf-report-alert ssf-alert-error">' + bl.unfixed + ' broken link(s) still need attention. Review them in the Broken Links page.</div>';
                }
                $('#ssf-broken-links-content').html(bh);
            }

            // ── Optimizations ──
            if (sec.optimizations) {
                var op = sec.optimizations;
                var oh = '<div class="ssf-report-info-grid">';
                oh += this.infoCard(op.total, 'Total Changes');
                if (op.posts_optimized > 0) oh += this.infoCard(op.posts_optimized, 'Pages Optimized');
                if (op.ai_generated > 0) oh += this.infoCard(op.ai_generated, 'AI-Generated');
                if (op.manual > 0) oh += this.infoCard(op.manual, 'Manual Edits');
                oh += '</div>';
                if (op.by_type && op.by_type.length) {
                    oh += '<div class="ssf-report-tag-list" style="margin-top:16px;">';
                    op.by_type.forEach(function(t) {
                        oh += '<span class="ssf-report-tag">' + SSF_ClientReport.esc(t.category) + ': ' + t.count + '</span>';
                    });
                    oh += '</div>';
                }
                $('#ssf-optimizations-content').html(oh);
            }

            // ── Sitemap Status ──
            if (sec.sitemap_status) {
                var sm = sec.sitemap_status;
                var smh = '<div class="ssf-report-info-grid">';
                smh += this.infoCard('<span class="dashicons dashicons-yes-alt" style="color:#22c55e;font-size:28px;"></span>', 'Sitemap Active');
                smh += this.infoCard(sm.indexable_pages.toLocaleString(), 'Indexable Pages');
                smh += this.infoCard(sm.post_types.length, 'Content Types');
                smh += '</div>';
                smh += '<p class="ssf-report-note">Sitemap URL: <a href="' + this.esc(sm.url) + '" target="_blank">' + this.esc(sm.url) + '</a></p>';
                if (sm.post_types && sm.post_types.length) {
                    smh += '<div class="ssf-report-tag-list">';
                    sm.post_types.forEach(function(pt) {
                        smh += '<span class="ssf-report-tag">' + SSF_ClientReport.esc(pt) + '</span>';
                    });
                    smh += '</div>';
                }
                $('#ssf-sitemap-status-content').html(smh);
            }

            // ── Worst Pages (full mode) ──
            if (sec.worst_pages && sec.worst_pages.length) {
                var wbody = '';
                sec.worst_pages.forEach(function(p, i) {
                    wbody += '<tr>';
                    wbody += '<td>' + (i + 1) + '</td>';
                    wbody += '<td>' + SSF_ClientReport.esc(p.title) + '</td>';
                    wbody += '<td><span class="score-badge needs-work">' + p.score + '</span></td>';
                    wbody += '<td>';
                    if (p.issues && p.issues.length) {
                        p.issues.forEach(function(issue) {
                            wbody += '<span class="ssf-issue-tag">' + SSF_ClientReport.esc(issue) + '</span> ';
                        });
                    }
                    wbody += '</td>';
                    wbody += '</tr>';
                });
                $('#ssf-worst-pages-table tbody').html(wbody);
            }

            // ── Issues & Recommendations (full mode) ──
            if (sec.issues && sec.issues.length) {
                var ih = '';
                sec.issues.forEach(function(issue) {
                    var icon = issue.severity === 'error' ? 'dismiss' : (issue.severity === 'warning' ? 'warning' : 'info-outline');
                    ih += '<div class="ssf-report-issue ssf-issue-' + issue.severity + '">';
                    ih += '<div class="ssf-issue-icon"><span class="dashicons dashicons-' + icon + '"></span></div>';
                    ih += '<div class="ssf-issue-body">';
                    ih += '<strong>' + SSF_ClientReport.esc(issue.title) + '</strong>';
                    if (issue.detail) ih += '<p>' + SSF_ClientReport.esc(issue.detail) + '</p>';
                    ih += '</div></div>';
                });
                $('#ssf-issues-content').html(ih);
            }
        },

        animateRing: function(score) {
            var circumference = 2 * Math.PI * 54; // r=54
            var offset = circumference - (score / 100) * circumference;
            var $fg = $('#ssf-ring-fg');
            var color = score >= 80 ? '#22c55e' : (score >= 60 ? '#f59e0b' : '#ef4444');

            $fg.css({ 'stroke-dasharray': circumference, 'stroke-dashoffset': circumference, 'stroke': color });
            setTimeout(function() { $fg.css('stroke-dashoffset', offset); }, 50);

            // Animate number
            var $val = $('#ssf-ring-value');
            $({ n: 0 }).animate({ n: score }, {
                duration: 1200,
                step: function() { $val.text(Math.round(this.n)); },
                complete: function() { $val.text(score); }
            });
        },

        downloadPDF: function() {
            // Use browser print-to-PDF as native approach
            // The print CSS hides all admin chrome, giving a clean PDF
            window.print();
        },

        statBox: function(number, label, subtitle, extraClass) {
            var cls = 'ssf-report-stat-box' + (extraClass ? ' ' + extraClass : '');
            var h = '<div class="' + cls + '"><span class="stat-number">' + (number !== undefined ? number : 0) + '</span><span class="stat-label">' + this.esc(label) + '</span>';
            if (subtitle) h += '<span class="stat-subtitle">' + this.esc(subtitle) + '</span>';
            h += '</div>';
            return h;
        },

        infoCard: function(value, label, subtitle, extraClass) {
            var cls = 'ssf-report-info-card' + (extraClass ? ' ' + extraClass : '');
            var h = '<div class="' + cls + '"><span class="info-value">' + (value !== undefined ? value : 0) + '</span><span class="info-label">' + this.esc(label) + '</span>';
            if (subtitle) h += '<span class="info-subtitle">' + this.esc(subtitle) + '</span>';
            h += '</div>';
            return h;
        },

        progressBar: function(label, count, total, pct) {
            var color = pct >= 80 ? '#22c55e' : (pct >= 50 ? '#f59e0b' : '#ef4444');
            return '<div class="ssf-report-progress-item">' +
                '<div class="ssf-progress-header"><span class="ssf-progress-label">' + this.esc(label) + '</span><span class="ssf-progress-value">' + count + ' / ' + total + ' (' + pct + '%)</span></div>' +
                '<div class="ssf-progress-track"><div class="ssf-progress-fill" style="width:' + pct + '%;background:' + color + '"></div></div>' +
                '</div>';
        },

        esc: function(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }
    };

    window.SSF_ClientReport = SSF_ClientReport;
    
})(jQuery);

