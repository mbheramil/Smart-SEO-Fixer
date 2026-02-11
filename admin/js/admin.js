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
    });
    
    // Expose to global scope
    window.SSF = SSF;
    
})(jQuery);

