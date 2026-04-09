<?php
/**
 * Client Report View
 *
 * Admin-only page for generating positive-only SEO reports.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ssf-wrap">
    <h1 class="ssf-page-title">
        <span class="dashicons dashicons-media-document"></span>
        <?php esc_html_e('Client SEO Report', 'smart-seo-fixer'); ?>
    </h1>

    <!-- ── Report Configuration ── -->
    <div class="ssf-card ssf-report-config" id="ssf-report-config">
        <div class="ssf-card-header">
            <h2>
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e('Report Settings', 'smart-seo-fixer'); ?>
            </h2>
        </div>
        <div class="ssf-card-body">
            <div class="ssf-report-options-grid">
                <!-- Date Range -->
                <div class="ssf-option-group">
                    <label for="ssf-report-range"><?php esc_html_e('Date Range', 'smart-seo-fixer'); ?></label>
                    <select id="ssf-report-range">
                        <option value="30"><?php esc_html_e('Last 30 Days', 'smart-seo-fixer'); ?></option>
                        <option value="60"><?php esc_html_e('Last 60 Days', 'smart-seo-fixer'); ?></option>
                        <option value="90"><?php esc_html_e('Last 90 Days', 'smart-seo-fixer'); ?></option>
                        <option value="all"><?php esc_html_e('All Time', 'smart-seo-fixer'); ?></option>
                        <option value="custom"><?php esc_html_e('Custom Range', 'smart-seo-fixer'); ?></option>
                    </select>
                </div>

                <!-- Custom Dates (hidden by default) -->
                <div class="ssf-option-group ssf-custom-dates" id="ssf-custom-dates" style="display:none;">
                    <label><?php esc_html_e('Custom Dates', 'smart-seo-fixer'); ?></label>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <input type="date" id="ssf-report-start" />
                        <span>&ndash;</span>
                        <input type="date" id="ssf-report-end" value="<?php echo esc_attr(current_time('Y-m-d')); ?>" />
                    </div>
                </div>

                <!-- Sections -->
                <div class="ssf-option-group ssf-sections-checkboxes">
                    <label><?php esc_html_e('Sections to Include', 'smart-seo-fixer'); ?></label>
                    <div class="ssf-checkbox-grid">
                        <label><input type="checkbox" name="ssf_section" value="overview" checked /> <?php esc_html_e('Overview & Score', 'smart-seo-fixer'); ?></label>
                        <label><input type="checkbox" name="ssf_section" value="score_distribution" checked /> <?php esc_html_e('Score Distribution', 'smart-seo-fixer'); ?></label>
                        <label><input type="checkbox" name="ssf_section" value="top_pages" checked /> <?php esc_html_e('Top Pages', 'smart-seo-fixer'); ?></label>
                        <label><input type="checkbox" name="ssf_section" value="schema_coverage" checked /> <?php esc_html_e('Schema Coverage', 'smart-seo-fixer'); ?></label>
                        <label><input type="checkbox" name="ssf_section" value="redirects" checked /> <?php esc_html_e('Redirects', 'smart-seo-fixer'); ?></label>
                        <label><input type="checkbox" name="ssf_section" value="keywords" checked /> <?php esc_html_e('Keyword Rankings', 'smart-seo-fixer'); ?></label>
                        <label><input type="checkbox" name="ssf_section" value="broken_links_fixed" checked /> <?php esc_html_e('Broken Links Fixed', 'smart-seo-fixer'); ?></label>
                        <label><input type="checkbox" name="ssf_section" value="optimizations" checked /> <?php esc_html_e('Optimizations Made', 'smart-seo-fixer'); ?></label>
                    </div>
                </div>
            </div>

            <div class="ssf-report-actions">
                <button type="button" class="button button-primary button-hero" id="ssf-generate-report">
                    <span class="dashicons dashicons-analytics"></span>
                    <?php esc_html_e('Generate Report', 'smart-seo-fixer'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Report Output (hidden until generated) ── -->
    <div id="ssf-report-output" style="display:none;">

        <!-- Report Action Bar -->
        <div class="ssf-report-action-bar">
            <button type="button" class="button" id="ssf-print-report">
                <span class="dashicons dashicons-printer"></span>
                <?php esc_html_e('Print Report', 'smart-seo-fixer'); ?>
            </button>
            <button type="button" class="button button-primary" id="ssf-download-pdf">
                <span class="dashicons dashicons-pdf"></span>
                <?php esc_html_e('Download PDF', 'smart-seo-fixer'); ?>
            </button>
            <button type="button" class="button" id="ssf-new-report">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('New Report', 'smart-seo-fixer'); ?>
            </button>
        </div>

        <!-- Printable Report Container -->
        <div class="ssf-report" id="ssf-report">

            <!-- Cover / Header -->
            <div class="ssf-report-cover">
                <div class="ssf-report-cover-icon" id="ssf-report-icon"></div>
                <h1 class="ssf-report-site-name" id="ssf-report-site-name"></h1>
                <p class="ssf-report-tagline" id="ssf-report-tagline"></p>
                <p class="ssf-report-url" id="ssf-report-url"></p>
                <div class="ssf-report-period" id="ssf-report-period"></div>
                <p class="ssf-report-generated" id="ssf-report-generated"></p>
            </div>

            <!-- Overall SEO Score -->
            <div class="ssf-report-section" id="ssf-section-overview">
                <h2 class="ssf-report-section-title">
                    <span class="dashicons dashicons-awards"></span>
                    <?php esc_html_e('SEO Optimization Score', 'smart-seo-fixer'); ?>
                </h2>
                <div class="ssf-report-score-ring-wrap">
                    <div class="ssf-report-score-ring" id="ssf-report-score-ring">
                        <svg viewBox="0 0 120 120">
                            <circle class="ssf-ring-bg" cx="60" cy="60" r="54" />
                            <circle class="ssf-ring-fg" cx="60" cy="60" r="54" id="ssf-ring-fg" />
                        </svg>
                        <span class="ssf-ring-value" id="ssf-ring-value">0</span>
                    </div>
                </div>
                <div class="ssf-report-overview-grid" id="ssf-overview-grid"></div>
            </div>

            <!-- Score Distribution -->
            <div class="ssf-report-section" id="ssf-section-score_distribution">
                <h2 class="ssf-report-section-title">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Score Distribution', 'smart-seo-fixer'); ?>
                </h2>
                <div class="ssf-report-bar-chart" id="ssf-dist-chart"></div>
            </div>

            <!-- Top Pages -->
            <div class="ssf-report-section" id="ssf-section-top_pages">
                <h2 class="ssf-report-section-title">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Top Performing Pages', 'smart-seo-fixer'); ?>
                </h2>
                <table class="ssf-report-table" id="ssf-top-pages-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('#', 'smart-seo-fixer'); ?></th>
                            <th><?php esc_html_e('Page', 'smart-seo-fixer'); ?></th>
                            <th><?php esc_html_e('Type', 'smart-seo-fixer'); ?></th>
                            <th><?php esc_html_e('Score', 'smart-seo-fixer'); ?></th>
                            <th><?php esc_html_e('Grade', 'smart-seo-fixer'); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- Schema Coverage -->
            <div class="ssf-report-section" id="ssf-section-schema_coverage">
                <h2 class="ssf-report-section-title">
                    <span class="dashicons dashicons-shortcode"></span>
                    <?php esc_html_e('Schema Markup Coverage', 'smart-seo-fixer'); ?>
                </h2>
                <div id="ssf-schema-coverage-content"></div>
            </div>

            <!-- Redirects -->
            <div class="ssf-report-section" id="ssf-section-redirects">
                <h2 class="ssf-report-section-title">
                    <span class="dashicons dashicons-randomize"></span>
                    <?php esc_html_e('Redirect Management', 'smart-seo-fixer'); ?>
                </h2>
                <div id="ssf-redirects-content"></div>
            </div>

            <!-- Keyword Rankings -->
            <div class="ssf-report-section" id="ssf-section-keywords">
                <h2 class="ssf-report-section-title">
                    <span class="dashicons dashicons-tag"></span>
                    <?php esc_html_e('Keyword Rankings', 'smart-seo-fixer'); ?>
                </h2>
                <div id="ssf-keywords-content"></div>
            </div>

            <!-- Broken Links Fixed -->
            <div class="ssf-report-section" id="ssf-section-broken_links_fixed">
                <h2 class="ssf-report-section-title">
                    <span class="dashicons dashicons-admin-links"></span>
                    <?php esc_html_e('Link Health', 'smart-seo-fixer'); ?>
                </h2>
                <div id="ssf-broken-links-content"></div>
            </div>

            <!-- Optimizations Made -->
            <div class="ssf-report-section" id="ssf-section-optimizations">
                <h2 class="ssf-report-section-title">
                    <span class="dashicons dashicons-hammer"></span>
                    <?php esc_html_e('SEO Optimizations Performed', 'smart-seo-fixer'); ?>
                </h2>
                <div id="ssf-optimizations-content"></div>
            </div>

            <!-- Footer -->
            <div class="ssf-report-footer">
                <p><?php printf(
                    /* translators: %s: plugin name */
                    esc_html__('Report generated by %s', 'smart-seo-fixer'),
                    '<strong>Smart SEO Fixer</strong>'
                ); ?></p>
            </div>
        </div><!-- .ssf-report -->
    </div><!-- #ssf-report-output -->
</div>
