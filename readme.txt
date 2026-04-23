=== Smart SEO Fixer ===
Contributors: mbheramil
Tags: seo, ai, openai, meta description, schema, sitemap, search engine optimization, breadcrumbs, redirects, local seo
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 2.0.47
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered SEO optimization plugin that analyzes and fixes SEO issues using OpenAI.

== Description ==

Smart SEO Fixer is a powerful WordPress plugin that uses AI (powered by OpenAI) to analyze and optimize your website's SEO. It automatically detects issues and generates optimized titles, meta descriptions, and alt text — with zero gaps.

**Key Features:**

* **SEO Analysis** - Comprehensive analysis of titles, meta descriptions, content, headings, images, and links
* **AI-Powered Generation** - Generate optimized SEO titles, meta descriptions, and focus keywords using OpenAI
* **4-Layer SEO Protection** - Auto-generate on publish/update, background cron, dashboard alerts, and bulk fix
* **SEO Score** - Get a score from 0-100 for each post/page with detailed feedback
* **Readability Scoring** - Flesch Reading Ease, sentence length, passive voice detection
* **Bulk Analysis & Fix** - Analyze and AI-fix all posts at once
* **Schema Markup** - Automatic JSON-LD structured data for articles, pages, products, and local business
* **XML Sitemap** - Built-in sitemap generator with automatic search engine pinging
* **Meta Tags** - Full control over titles, descriptions, canonical URLs, and robots meta
* **Open Graph & Twitter Cards** - Automatic social media meta tags with image fallbacks
* **Social Previews** - Live Google, Facebook, and Twitter preview in the editor
* **Redirect Manager** - 301/302 redirects, auto-detect slug changes, 404 tracking
* **Breadcrumbs** - Schema-enriched breadcrumbs via shortcode or PHP function
* **Local SEO** - Multiple business locations with LocalBusiness schema
* **WooCommerce SEO** - Product schema, category SEO, brand/GTIN fields
* **Search Console Fixer** - Detect and fix trailing slash issues, redirect chains, canonical conflicts
* **Migration Tool** - Import from Yoast, Rank Math, AIOSEO, SEOPress, The SEO Framework
* **Auto-Updater** - GitHub-based updates with private repo support
* **Theme Compatibility** - Works with any theme, including those without title-tag support

**What Gets Analyzed:**

* Title length and keyword placement
* Meta description length and content
* Content length and keyword density
* Heading structure (H1, H2, H3)
* Image alt text
* Internal and external links
* URL/slug optimization
* Readability metrics

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/smart-seo-fixer/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Smart SEO → Settings** and enter your OpenAI API key
4. Click **Analyze All Posts** on the dashboard to get started
5. Enable **Auto Meta Generation** in Settings for hands-free SEO

== Frequently Asked Questions ==

= Do I need an OpenAI API key? =

Yes, to use the AI-powered features (title generation, meta description generation, keyword suggestions), you need an OpenAI API key. You can get one from https://platform.openai.com/api-keys

The plugin will still work for manual SEO analysis without an API key.

= What OpenAI model should I use? =

We recommend **GPT-4o Mini** for the best balance of quality and cost. It's fast, affordable, and produces excellent SEO content.

= How much does it cost to use? =

The plugin itself is free. OpenAI charges based on usage. GPT-4o Mini costs approximately $0.15 per million input tokens and $0.60 per million output tokens - very affordable for SEO content generation.

= Will this conflict with other SEO plugins? =

Smart SEO Fixer includes a migration tool to import data from Yoast, Rank Math, and others. You can also enable "Disable Other SEO Output" in settings to prevent duplicate meta tags.

= Is my content sent to OpenAI? =

When you use AI features, relevant content is sent to OpenAI for processing. OpenAI does not use API data for training.

= Does this work with any theme? =

Yes. The plugin forces title-tag support for themes that don't declare it, and includes a fallback output buffer to ensure titles always render correctly.

== Screenshots ==

1. Dashboard showing SEO health scores and statistics
2. SEO analysis metabox in post editor with social previews
3. Bulk analysis and AI fix of all posts
4. Search Console Fixer for URL consistency
5. Redirect Manager with 404 tracking
6. Settings page with API configuration

== Changelog ==
= 2.0.47 =
* Fix: "The provided model identifier is invalid" error on Bedrock after Haiku switch. Claude 3.x models use the direct Bedrock model ID (`anthropic.claude-haiku-3-5-20241022-v1:0`) without the `us.` cross-region prefix — that prefix is only for Claude 4.x inference profiles.

= 2.0.46 =
* Fix: Orphaned page "Add Link with AI" always failed with "Could not find a natural placement" for location/service-area pages (e.g. "Inspections in Webster Groves, MO"). Root cause: no other page on the site mentions the city name, so AI correctly returns `{found:false}` for all candidates — and the fallback page also fails. Added a guaranteed last-resort: when all AI placement attempts fail, the plugin now appends a `Related: [page title]` link paragraph to the highest-relevance candidate page, ensuring every orphan gets at least one incoming internal link.

= 2.0.45 =
* Change: Switched AWS Bedrock model from Claude Sonnet 4.6 to Claude 3.5 Haiku (`us.anthropic.claude-haiku-3-5-20241022-v1:0`) — same quality for SEO tasks at 4x lower cost.

= 2.0.44 =
* Fixed: Image alt-text generation was producing hallucinated / generic output because every provider (Bedrock, Claude, OpenAI) was sending only the image URL as plain text — the AI could not actually see the image, it was just guessing from the filename slug. Now uses true vision: the image bytes are fetched, base64-encoded, and sent as a multimodal message block. Claude on Bedrock (vision-capable), Anthropic Claude, and OpenAI GPT-4o/4-turbo all receive the actual pixels and describe what they see. Images larger than 4 MB are auto-resized to Claude's recommended max 1568px edge. Non-vision models (Llama / Mistral / Titan on Bedrock) fall back to the URL-only prompt.
* Added: `SSF_AI::fetch_image_as_base64()` helper that reads images from local uploads (fast path) or falls back to wp_remote_get, sniffs media type, and rejects unsupported formats (accepts jpeg/png/gif/webp only).

= 2.0.43 =
* Fixed: Bulk AI Fix silently capped at ~999 posts per run no matter how many the user selected. Root cause: the frontend sent `post_ids[]` as an array, which on large selections exceeds PHP's default `max_input_vars = 1000` and gets silently truncated by PHP before WordPress ever sees it. With 1453 selected, only the first 999 (ordered by post_date DESC) reached the server — and those were the same 999 that prior runs had already filled in, so every run returned "0 generated · 999 skipped — already has SEO data". Frontend now sends the selection as a single CSV field (`post_ids_csv`), so the entire list reaches the server in one input var regardless of size. Applied the same defense to `bulk_fix` and `bulk_analyze` endpoints.

= 2.0.42 =
* Fixed: Applied the v2.0.41 enriched-context helper (post_content + post_excerpt + public post_meta + image alt/caption) to EVERY remaining AI generation entry point, not just Bulk AI Fix. Previously these paths still read raw `$post->post_content` and silently skipped page-builder / location-template CPTs the same way Bulk AI Fix did: post-save cron auto-SEO (`smart-seo-fixer.php` + `class-admin.php`), per-post meta-box Generate buttons for title / description / keywords / analyze, Fix Issue (title & description), bulk_fix sequential + parallel, bulk_ai_fix in-request + sequential fallback, ai_fix_single, Search Console duplicate-regen, fix_missing_seo_data, not-indexed AI fix (keyword + title + description), generate_unique_title, generate_unique_desc, content suggestions generator, and the job queue `process_not_indexed_fix` sequential path. All of these now pass the same enriched string used by the parallel Bulk AI Fix pipeline, so page-builder and location CPTs produce real SEO output across every UI surface.

= 2.0.41 =
* Fixed: Bulk AI Fix reported "999/999 completed" but post_meta wasn't actually written for page-builder / location-template post types. Root cause: post_content on those CPTs is empty (real content lives in post_excerpt, ACF/page-builder meta, or attached image alt text), so the word-count gate skipped every post silently as "content too short" and counted the skip as success. Both the parallel and sequential bulk paths now call a new `enrich_post_context()` helper that combines body + excerpt + public meta + image alt/caption before the word-count gate and before prompting the AI.
* New: Completion screen now shows a real outcome breakdown — "N generated · M skipped · K failed" with per-reason skip counts. If every post was skipped, a warning is surfaced so the user knows the job was a no-op instead of quietly assuming success.
* Changed: `ssf_get_job` response now includes a `summary` object with generated / skipped / failed / reasons.

= 2.0.40 =
* Fixed: Job Queue page always said "OpenAI Rate Limit" even when the site was wired to AWS Bedrock, Claude, or Gemini. The card now reflects the active AI provider's label and reads that provider's actual rate-limiter bucket. No behavior change — the bulk pipeline was already using the correctly configured provider; only the label was wrong.
* Fixed: Job Queue page description said "10+ posts, 5 items per minute" which no longer matched the current parallel pipeline. Updated to "5+ posts, batches of 20 in parallel on Bedrock".

= 2.0.39 =
* Fixed: Job Queue page "Recent Jobs" was always empty even when jobs existed in the database. The `ssf_get_jobs` response returned `items` but the view expected `jobs`, so the table never rendered. Now returns both keys and also computes the `progress` percentage per row.
* Fixed: Bulk AI Fix progress bar stuck at 0/N. The `ssf_get_job` polling endpoint was reading non-existent columns (`processed_count`/`failed_count` instead of `processed_items`/`failed_items`), so it always reported zero progress even while batches were actually being processed.
* Fixed: Non-Bedrock bulk jobs (OpenAI, Claude, Gemini) stalled at 0/N on low-traffic sites because the loopback self-tick only re-fired for Bedrock-parallel jobs. Every job type now chains its own next batch, turning long bulk runs from "1 post per minute via WP-Cron" into "continuous processing".
* Fixed: Added a belt-and-braces self-kick inside the progress polling endpoint — if the job is pending/processing, each UI poll nudges the queue forward. On hosts where `wp_remote_post` non-blocking loopbacks get swallowed by caching/reverse-proxy layers, the browser itself now keeps the pipeline moving.

= 2.0.38 =
* Fixed: Bulk AI Fix now routes batches of 5+ posts through the Job Queue so they are visible on the Background Jobs page (previously the client-side loop never triggered queuing because it fragmented into batches of 5).
* Fixed: Bulk AI Fix is dramatically faster. Instead of the browser firing 299 sequential HTTP requests of 5 posts each (each request doing 10 sequential AI calls), the entire selection is now sent once and processed server-side in parallel batches of 20 with curl_multi. For 1,494 posts this drops processing from hours to minutes on Bedrock.
* New: Live progress bar on the Bulk AI Fix page polls the Job Queue every 2 seconds and shows processed/total count + status. Progress stalls automatically trigger a queue self-tick so you don't have to wait for WordPress cron.
* New: `ssf_get_job` AJAX endpoint returns a single job's progress (id, status, total, processed, percent, failed, error) for live UI polling by third-party integrations.
* In-request fast path: batches of <5 posts still run synchronously and now use the same parallel curl_multi Bedrock call for instant results.

= 2.0.37 =
* New: Thin-content auto-noindex. Posts below the word threshold (default 50 words) are automatically marked noindex so Google won't count them against your site's SEO. Applies to image-only posts and super-short "thank you" style reviews. If a post grows above the threshold later, the plugin lifts the noindex automatically. Fully configurable in Settings → General → Thin Content Auto-Noindex.
* New: Image-only SEO enrichment. When a post is mostly images (e.g. a client-review gallery), the plugin now feeds every image's alt text, caption, title, and description to the AI — so it can still generate a relevant SEO title, meta description, and focus keyword instead of skipping the post.
* New: Thin-content warning in the SSF meta box. You now see a clear message in the post editor if your content is below the threshold, with one-click guidance to either expand the content or leave it noindexed.
* Improved: Reports & Search Console reconciliation now exclude noindex posts from the "missing title / missing description / missing keyword" counts — you won't be nagged about posts that are intentionally hidden from search.
* Improved: The Analyzer short-circuits noindex posts with a neutral "excluded from search" result so they don't drag down your site's average SEO score.
* New helpers (for extensions/integrations):
  * `SSF_Validator::get_content_word_count($post)` — real word count after stripping shortcodes/tags/captions.
  * `SSF_Validator::is_thin_content($post, $threshold = 50)`
  * `SSF_Validator::extract_image_seo_context($post)` — pulls all image alt/caption/title text from a post for AI input.
* Meta keys added per post: `_ssf_auto_noindex` (1 if plugin set the noindex), `_ssf_content_word_count`, `_ssf_thin_evaluated` (timestamp).

= 2.0.36 =
* New: Auto-generate SEO title + meta description on first publish is now enabled by default. When you publish a new post/page, a background job runs ~5 seconds later and fills in any missing title, description, and focus keyword using the Bedrock SEO bundle (one parallel call).
* New: Hard character limits enforced everywhere. SEO title is truncated to 60 characters and meta description to 160 characters on every save path (auto publish, bulk AI fix, single-post generate, bulk fix, manual save, not-indexed fix) and on frontend output as a safety net for legacy data. Truncation happens on a word boundary so titles never cut mid-word.
* New helpers: SSF_Validator::enforce_seo_title(\$title, \$max=60) and SSF_Validator::enforce_meta_description(\$desc, \$max=160) for any third-party integrations to reuse.
* Performance: The publish-time auto-generation is now asynchronous (wp_schedule_single_event ~5s) so it never slows down saving or publishing.

= 2.0.35 =
* Fix: "Insert Internal Links" button in the post editor now works on unsaved content. The meta-box JS sends the live editor content to the server so the AI can find anchor phrases that aren't saved to the database yet. Previously it failed on new or freshly-edited posts because the server was reading stale DB content.
* Fix: Internal-link candidate search now uses the same broader word-overlap scoring as the Indexability "Orphan Fix" (trying 6 candidates instead of WP's narrow ?s= search against 10), so it finds related posts even when focus keyword is empty.
* New: Automatic internal linking on first publish. When a new post/page is published for the first time, a background job runs ~30s later to add up to 3 outgoing links from the new post to related posts, and up to 3 incoming links from related posts back to the new post. Uses parallel Bedrock when available. Setting: Settings → General → "Auto Internal Links" (default: on).
* Performance: Meta-box internal-link suggestions now run in parallel on Bedrock via curl_multi (up to 6 AI anchor searches fire concurrently instead of sequentially).

= 2.0.34 =
* Extended parallel Bedrock processing to three more AI flows:
  - Search Console "Fix All Not-Indexed" background job (20 posts concurrent per batch).
  - Synchronous Bulk Fix AJAX (title + description calls for all selected posts fire concurrently — was 2 sequential calls per post, now one parallel burst).
  - Image alt-text repair (all missing-alt images on a post generated concurrently instead of one by one).
* New public message-builder helpers on SSF_Bedrock: build_title_messages, build_desc_messages, build_alt_messages — enable reusing the same prompts with request_multi across the codebase.
* Non-Bedrock providers (OpenAI, Claude direct, Gemini) continue using the sequential path, so this change is backward-compatible.

= 2.0.33 =
* New: Parallel Bedrock AI processing — bulk AI fix now fires 20 posts concurrently via curl_multi instead of sequentially
* New: Combined "SEO bundle" prompt — one Bedrock call returns keyword + title + description as JSON (was 3 separate calls per post)
* New: Loopback self-ticking — bulk jobs no longer wait for WP-Cron's 1-minute tick between batches; each batch immediately triggers the next via a non-blocking HTTP request to admin-ajax.php
* Result: Bulk AI Fix for 1000+ page sites now completes in minutes instead of hours when using AWS Bedrock
* The parallel path includes a grounded-keyword fallback — if the AI returns a keyword that isn't in the post, the n-gram extractor takes over so we never save orphan keywords
* Non-Bedrock providers (OpenAI, Claude direct, Gemini) continue to use the sequential path, no behavior change

= 2.0.32 =
* Fixed: AI-generated focus keywords were often invented phrases that didn't appear in the post, causing the analyzer to deduct points for "keyword not found in title/content" even when meta coverage was 100%
* New: `SSF_AI::pick_grounded_keyword()` — AI suggestions are now validated against the actual post text; if no suggested keyword appears verbatim, a frequency-based n-gram is extracted from the title + content as a fallback
* New: AI keyword prompts now explicitly tell the model "every keyword MUST appear verbatim in the content"
* New: "Re-analyze All Pages" button on the Client Report page — batches through every published page and refreshes scores, so your Generated report reflects the latest state

= 2.0.31 =
* New: Client Report "Download PDF" now generates a real PDF file (using bundled html2pdf.js) instead of opening the browser's print dialog, so the exported file no longer contains the admin URL, page numbers, or the date/title header bar
* New: PDF files are auto-named `seo-report-<site>-<YYYY-MM-DD>.pdf`
* Improved: Removed the "Report generated by Smart SEO Fixer" footer from the Client Report
* Improved: PDF export button shows a spinner while rendering

= 2.0.30 =
* Improved: When a Google API needs to be enabled in your Cloud project, the plugin now shows a friendly banner with a clickable "Enable API" button that deep-links to the exact enablement page (with your project ID pre-filled) instead of showing raw error text
* Applied to: GA4 Use Existing Property picker, Auto-Create GA4 Property, Test Data Fetch
* Raw Google error is still available in a collapsible "Raw error from Google" block

= 2.0.29 =
* New: "Use Existing Property" button in Google Analytics settings — attach to a GA4 property you don't own
* New: Property picker dropdown lists every GA4 property the connected Google account has access to, grouped by account
* New: Optional "Also install tracking code" checkbox when selecting an existing property (auto-fills the Measurement ID from the chosen web stream)
* Use case: client's GA4 is in their Google account, they grant you Viewer/Analyst access, you pick their property here — reports work, optional tracking code installs

= 2.0.28 =
* New: Google Analytics 4 integration — connect GA4 with OAuth, auto-create a new property + web data stream with one click, and install the gtag.js tracking code automatically
* New: "Auto-Create GA4 Property for This Site" button in Settings
* New: Manual Measurement ID field for users who already have a GA4 property
* New: Website Traffic section in Client Report — shows sessions, users, pageviews, bounce rate, engagement rate, avg session duration, top landing pages, and traffic sources from GA4
* New: Test Data Fetch button in Settings to verify GA4 connectivity
* Note: Requires enabling the Google Analytics Admin API and Data API in your Google Cloud project

= 2.0.27 =
* New: One-click Search Console auto-setup — creates property, verifies ownership via meta tag, and submits sitemap automatically
* New: "Auto-Create Property for This Site" button in Settings after connecting Google
* New: Integrated Google Site Verification API (requires siteverification OAuth scope — existing users must disconnect and reconnect)
* New: Self-check step confirms the verification meta tag is actually served before asking Google to verify (catches cache issues early)
* Fix: Client Report accuracy — broken links "fixed" count now uses resolved log instead of dismissed flag
* Fix: Client Report date range now applies consistently across all sections
* Fix: Image SEO stats now scoped to images used in published content (no longer diluted by orphan media library uploads)
* Fix: Image detection now catches Gutenberg image blocks, not just raw <img> tags
* New: Data freshness indicator on Client Report showing score quality (good/partial/stale/none)

= 2.0.26 =
* Fix: Sub-sitemaps (sitemap-post.xml, sitemap-page.xml, etc.) returning blog page instead of XML
* Fix: Rewrite rules were registered too late (init priority bug) - now applied correctly
* Fix: Added fallback URL re-parsing in sitemap renderer when query var is missing or stuck as 'dynamic'
* Fix: flush_rewrite_rules() now called on plugin activation so sitemap URLs route correctly from day one

= 2.0.25 =
* New: AI Fix button on each score factor row — fixes the issue across all affected pages with one click
* New: Auto-detects fix type based on issue category (Title, Description, Keywords, or all)
* New: Progress modal with live log showing each page being fixed

= 2.0.24 =
* New: Click any issue in "Why Your Score Is What It Is" to open the affected pages in a new tab
* New: Posts page now supports filtering by specific SEO issue text
* New: Issue filter notice banner with clear button on Posts page

= 2.0.23 =
* New: "Why Your Score Is What It Is" section — shows top 10 most common SEO issues across all pages with frequency bars
* New: Each issue shows category (Content, Title, Description, etc.), the specific problem, and how many pages are affected
* New: Score Factors checkbox in report config panel

= 2.0.22 =
* Fix: Image alt text count was wrong — was scanning raw post HTML instead of checking attachment metadata (_wp_attachment_image_alt)
* Fix: Report showed >100% analyzed (e.g. 113%) because scores table included deleted/trashed/drafted posts
* Fix: All report queries (overview, score distribution, top pages, worst pages, issues) now filter by published posts in active post types only
* Fix: Analyzed percentage capped at 100%
* Fix: Issues section low-score and not-analyzed counts now accurate

= 2.0.21 =
* New: Auto-generate alt text from filename on image upload (enable in Settings > Auto Alt Text)
* New: Bulk Generate Missing Alt Text button in Settings — processes all existing images missing alt text
* Improved: Alt text generated from filenames (strips extensions, separators, size suffixes, capitalizes words)

= 2.0.20 =
* Improved: Template now replaces the cover section instead of just prepending above it
* Improved: Template CSS is scoped to the template banner area to avoid style conflicts
* Fix: Google Doc template styles were not applying because CSS selectors didn't match report HTML

= 2.0.19 =
* Fix: Fetched template was not applied to the generated report — now injects template styles and body content into the report

= 2.0.18 =
* Fix: Template fetch/clear "Security check failed" — was using wrong nonce and AJAX URL references

= 2.0.17 =
* Fix: Template fetch error showing [object Object] instead of actual error message

= 2.0.16 =
* New: Report Mode toggle — choose between Positive Only or Full Report (includes issues, negatives, recommendations)
* New: Template URL — paste a Google Doc or any URL to use its HTML/CSS as the report template
* New: Worst Pages section in full mode — bottom 20 pages by score with per-page issue tags
* New: Issues & Recommendations section in full mode — aggregated problems sorted by severity
* Improved: Full mode shows missing meta counts, unfixed broken links, needs-work scores, and unanalyzed pages
* Improved: Score distribution includes "Needs Work" bucket in full mode

= 2.0.15 =
* Improved: Client Report — comprehensive rewrite for much more useful, impressive reports
* New: Meta Tag Coverage section with progress bars (SEO titles, descriptions, focus keywords)
* New: Content Health section (avg word count, total words, readability score, images/links per page)
* New: Image SEO section (total images, alt text coverage percentage)
* New: Sitemap Status section (indexable pages, content types, sitemap URL)
* Fix: Top Pages table was empty due to duplicate score entries — now uses latest score per post
* Improved: Sections with zero/empty data are automatically hidden (true positive-only filtering)
* Improved: Score ring now shows grade badge (A/B/C+/C/D) with label (Excellent/Good/Fair/etc)
* Improved: Overview shows healthy-page percentage and analyzed-content percentage
* Improved: Score distribution bars show percentages alongside counts
* Improved: Schema section shows auto-coverage note explaining automatic structured data
* Improved: Optimizations section shows breakdown by type (titles, descriptions, keywords, schema, social)
* Improved: Keywords section shows total clicks and impressions
* Improved: Positive contextual notes throughout the report
* Improved: Print CSS with @page margin and color-adjust for new elements

= 2.0.14 =
* New: Client SEO Report — generate positive-only SEO reports for clients with animated score ring, score distribution, top pages, schema coverage, redirects, keyword rankings, broken links fixed, and optimizations performed
* New: Configurable date range (30/60/90 days, all time, or custom) and section toggles
* New: Print-friendly and PDF-ready output with clean styling (hides all WP admin chrome)
* New: Admin-only access (manage_options capability required)
* New: Dashboard nav card for quick access to Client Report

= 2.0.13 =
* Fix: Yoast meta description still duplicating after v2.0.12 — remove_action during init fires before Yoast registers its wp_head hook so the removal was silently ignored; now removes wpseo_head inside a wp_head priority-0 callback, which runs after Yoast has registered but before it fires

= 2.0.12 =
* Fix: Duplicate title and meta description tags — two bugs caused this: (1) SSF's remove_action for WordPress's built-in title tag was registered on after_setup_theme but SSF initialises on init (after_setup_theme fires first), so the removal never happened; fixed by calling remove_action directly in the constructor; (2) Yoast SEO hooks its entire head output via a standalone wpseo_head() function at wp_head priority 1 which SSF was not removing; added remove_action('wp_head', 'wpseo_head', 1) as the primary Yoast suppression

= 2.0.11 =
* Fix: SSF now falls back to Yoast SEO, Rank Math, All in One SEO, and SEOPress meta fields when SSF's own fields are empty — pages with existing SEO data from other plugins are never left without meta tags
* Fix: "Disable Other SEO Plugins Output" setting now shows clear warnings and Migration page links in all states (active plugin, plugin deactivated but data exists, checkbox enabled)

= 2.0.10 =
* Fix: Sitemap XSL stylesheet URLs now use query parameters (``/?ssf_sitemap=xsl``) instead of ``/ssf-sitemap.xsl`` paths — eliminates dependency on rewrite rules and .htaccess, making the styled sitemap work on all server configurations without needing a Permalink flush

= 2.0.9 =
* Fix: Sitemap XML now displays styled in all browsers — XSL stylesheets were previously intercepted by the webserver before reaching WordPress; now routed through the same WordPress request pipeline as the sitemap XML itself
* Fix: Added rewrite rules for XSL stylesheet URLs so they resolve correctly with any server configuration

= 2.0.8 =
* Enhancement: XML Sitemap now displays with a styled, readable layout in browsers (like Yoast) instead of raw XML
* Enhancement: Sitemap index shows sitemap count badge and "Last Modified" column
* Enhancement: Sub-sitemaps show URL count badge, priority, frequency, and last modified date in a clean table
* Enhancement: "Back to Sitemap Index" link on sub-sitemaps for easy navigation

= 2.0.7 =
* Fix: Auto-updater now uses direct GitHub archive URL instead of API zipball — fixes cURL error 6 (DNS resolution failure) on some servers
* Fix: Increased download timeout to 60 seconds and redirect limit to 10 for more reliable updates

= 2.0.6 =
* Enhancement: Sitemap now automatically includes ALL public post types (services, locations, products, FAQs, etc.) — not just posts and pages
* Enhancement: Sitemap now automatically includes ALL public taxonomies (custom categories, tags, etc.)
* Enhancement: Large sitemaps are automatically paginated (2000 URLs per file) to handle sites with thousands of pages
* Enhancement: Sitemap index only lists sub-sitemaps that actually contain content (no empty sitemaps)

= 2.0.5 =
* Fix: "Pages Not Appearing in Search" scanner now uses correct meta keys (_ssf_seo_title, _ssf_meta_description) — AI fix results now properly persist across page refreshes
* Fix: AI Fix button now works without clicking Inspect first — issue name mismatch (missing_meta vs missing_description) caused fix queue to be empty
* Fix: XML Sitemap now takes priority over Yoast SEO, Rank Math, AIOSEO, and WordPress core sitemaps when SSF sitemap is enabled
* Enhancement: Google inspection verdicts now show friendly labels ("Not Indexed" instead of "NEUTRAL") with helpful explanations
* Enhancement: Added missing issue labels for Noindex, No Internal Links tags in the not-indexed scanner

= 2.0.4 =
* Fix: Bulk 404 redirect reverted to reliable inline sequential processing (background job caused stalling)
* Fix: Schema bulk regenerate reverted to inline batch processing for instant feedback
* Fix: poll_job endpoint now actively drives job processing instead of relying solely on WP Cron
* Enhancement: Background jobs reserved only for AI-heavy operations (Bulk AI Fix on Search Performance)
* Enhancement: Job Queue page now shows clearer empty state with explanation of when jobs appear
* Enhancement: Added missing type labels for Not-Indexed AI Fix and Bulk 404 Redirect jobs

= 2.0.3 =
* New: Bulk AI Fix button for "Pages Not Appearing in Search" — select pages and fix all missing titles/descriptions at once
* New: All bulk operations (AI fix, schema regenerate, 404 redirects) now run as background jobs — you can leave the page while processing continues
* New: Generic job dispatch and polling system (ssf_dispatch_job / ssf_poll_job AJAX endpoints)
* New: Background job types: not_indexed_ai_fix, bulk_404_redirect
* Enhancement: "Select All" on 404 log and Pages Not Indexed now selects ALL items, not just the visible page
* Enhancement: Schema bulk regenerate now dispatches a background job with progress polling
* Enhancement: 404 bulk redirect now dispatches a background job instead of sequential inline AJAX
= 2.0.0 =
* New: Core Web Vitals (LCP, CLS, INP) real-user monitoring with p75 grading
* New: Image SEO — automatic lazy loading, eager first image, missing dimensions, decoding=async
* New: Weekly email digest with SEO score summary and action items
* New: Content duplication detection for titles and meta descriptions
* New: Internal link auto-insertion via AI-powered anchor matching
* New: Onboarding checklist with 7 milestone tracker
* New: Bulk fix preview with per-item approve/reject
* Enhancement: Bedrock API retry logic with exponential backoff (3 retries)
* Enhancement: Broken link scanner concurrency limit (5 parallel checks)
* Enhancement: Job queue dead-letter handling for stuck jobs with admin notification
* Enhancement: Canonical conflict auto-fix for duplicate titles, descriptions, and missing SEO data

= 2.0.2 =
* Enhancement: Bulk 404 redirect now shows a progress bar, counter (X / total), percentage, and completion status

= 2.0.1 =
* Fix: Added missing AJAX handlers for 404 Monitor (get, dismiss, redirect, clear)
* Fix: Added missing AJAX handler for Search Performance "Pages Not Indexed" scanner
* Fix: Added missing AJAX handlers for Keyword Tracker (get keywords, history, fetch now)
* Fix: Added missing AJAX handlers for Debug Log (get logs, clear logs)
* Fix: Added missing AJAX handlers for Change History, Job Queue, Social Preview, Readability, Robots Editor, Content Suggestions, WP Standards, and Performance
* New: Bulk redirect option in Redirect Manager 404 Error Log (select multiple 404s and redirect them at once)

= 1.16.15 =
* UI: Removed padlock label text from credential fields when constants are active
* UI: Removed "Before you start" info box from Bedrock settings

= 1.16.14 =
* Enhancement: AWS credentials can now be defined as PHP constants in wp-config.php (`SSF_BEDROCK_ACCESS_KEY`, `SSF_BEDROCK_SECRET_KEY`, `SSF_BEDROCK_REGION`) for improved security — constants take priority over database values
* Enhancement: Settings page shows a wp-config.php code snippet when constants are not yet set; locks credential fields with a padlock icon when constants are active
* Enhancement: Test Connection uses constants directly when defined, skipping any database credential handling

= 1.16.13 =
* Change: Model selection removed from settings UI — plugin now hardcodes `us.anthropic.claude-sonnet-4-6` (AWS CLI verified working)
* Fix: DB migration v7 now unconditionally sets the correct model ID in the database
* Fix: Setup wizard model dropdown removed; model is fixed in code

= 1.16.12 =
* Fix: Claude 4.x models now use cross-region inference profile IDs (`us.` prefix) — the catalog Model ID `anthropic.claude-sonnet-4-6` is not directly invokable; the invoke API requires `us.anthropic.claude-sonnet-4-6`
* Fix: DB migration v7 updated to fix bare catalog IDs saved without `us.` prefix → correct profile IDs
* Fix: `get_model_family()` now correctly detects `us.anthropic.claude-*` as Claude family

= 1.16.11 =
* Fix: DB migration (v7) automatically corrects any stale Bedrock model ID saved in the database with the old wrong format
* Fix: Custom model ID input now correctly passed to Test Connection and saved to DB
* Fix: Show/hide custom model input works on page load and on dropdown change

= 1.16.10 =
* Fix: Correct AWS Bedrock model ID for Claude Sonnet 4.6 — now uses `anthropic.claude-sonnet-4-6` (removed wrong date suffix and `us.` prefix)
* Fix: All Claude 4.x model IDs in dropdown updated to simplified format (e.g. `anthropic.claude-sonnet-4-6`)
* New: Custom model ID input field — select "Custom model ID" to paste any model ID directly from the Bedrock Model Catalog

= 1.16.9 =
* Fix: Test Connection error guidance now explains Anthropic use case approval requirement (required for first-time Claude model users via AWS)
* Fix: Info box in Bedrock settings now prominently shows the use case approval step before credentials setup

= 1.16.8 =
* Fix: Model list updated with correct cross-region inference IDs (us. prefix) for Claude 4.x models on AWS Bedrock
* New: Claude Sonnet 4.6 is now the default model (us.anthropic.claude-sonnet-4-6-20260301-v1:0)
* New: Added Claude Opus 4.6, Sonnet 4.5, Haiku 4.5 to model dropdown
* Fix: Claude 3.5 models kept as stable fallback options

= 1.16.7 =
* Fix: Test Connection now shows actionable guidance for "model identifier is invalid" error — explains the model must be enabled in AWS Bedrock Model Access console with a direct link
* Fix: Added friendly error messages for signature mismatch and access denied errors in Test Connection result

= 1.16.6 =
* Fix: Replaced invalid model IDs with confirmed available AWS Bedrock model IDs (default: Claude 3.5 Sonnet v2)
* New: AWS Bedrock connection status indicator showing connected/failed state in settings
* New: Test Connection button that fires a real API call with current form credentials before saving

= 1.16.5 =
* Fix: AWS SigV4 signature mismatch — colon in model ID (v1:0) now correctly percent-encoded as %3A in canonical URI, resolving "signature does not match" errors
* Fix: IAM permissions hint updated — clarifies that AmazonBedrockFullAccess is sufficient, no extra policy needed
* New: AWS Bedrock connection status indicator with Test Connection button in settings

= 1.16.4 =
* Fix: Canonical URL scheme now always matches site HTTPS/HTTP setting (prevents "Google chose different canonical" in Search Console)
* Fix: WordPress default `rel_canonical` removal now also hooks on `template_redirect` as a fallback for page builders and caching plugins
* Fix: Canonical output from Yoast, Rank Math, AIOSEO, The SEO Framework, and SEOPress is now always suppressed unconditionally (not just when "disable other SEO output" is on)
* New: Inline anchor wrapping for internal/external link suggestions (finds phrase in content and wraps with `<a>` tag)
* New: Broken links bulk redirect — select multiple broken links and redirect them all to a chosen URL
* New: All missing broken-link AJAX handlers implemented (get, scan, recheck, dismiss, undismiss)
* New: Canonical Health scanner in Search Performance — scan and auto-fix stored canonicals site-wide
* Fix: Canonical URL normalized (scheme + trailing slash) on save via meta box

= 1.16.3 =
* New: GSC site list is cached after OAuth for reliable dropdown loading
* New: "Load Site List" / "Refresh" button to fetch sites from GSC on demand via AJAX
* If dropdown loads → select your site and save
* If dropdown fails → manual text input available as fallback
* Site cache cleared on disconnect

= 1.16.2 =
* Fix: GSC `sc-domain:` site URLs were being destroyed by `esc_url_raw()` on save

= 1.16.1 =
* Fix: Site Property field now ALWAYS shows when GSC is connected
* Manual text input fallback when site list can't be loaded from GSC

= 1.16.0 =
* Fix: GSC auto-match now handles `sc-domain:` properties
* Better error messaging when GSC connects but site list fetch fails

= 1.15.9 =
* Fix: "Enhance with AI Suggestions" was calling non-existent method

= 1.15.8 =
* Fix: Force flyout link padding with !important to override WP admin CSS

= 1.15.7 =
* Fix: Flyout panel text padding increased

= 1.15.6 =
* Improvement: Flyout panels now match native WP admin submenu styling (dark background, same colors as the sidebar)

= 1.15.5 =
* Improvement: Flyout menu panels styling update

= 1.15.4 =
* Fix: Fetch Keywords 500 error — was calling non-existent method `search_analytics()` instead of `get_search_analytics()`
* Fix: Keyword tracker cron had the same wrong method call (also fixed)
* Fix: Added try/catch to keyword fetch handler so PHP errors return proper messages
* Improvement: Flyout menu panels now have more padding, spacing, rounded corners, and a subtle gap from the sidebar

= 1.15.3 =
* Redesign: Admin menu now uses hover flyout panels instead of collapsible groups
* Sidebar shows only Dashboard, 4 category groups, and Settings (6 items instead of 22)
* Hovering a group reveals a flyout panel with its sub-pages
* Fix: "Fetch Keywords Now" now shows actual GSC error messages instead of generic failure
* Fix: Content Tips loads instantly (rule-based) with optional "Enhance with AI" button
* AI suggestions load asynchronously and append below rule-based results

= 1.15.2 =
* Fix: Content Tips and Social Preview post search now works correctly
* Fix: Change History, Debug Log, and Background Jobs pages no longer stuck on loading
* Fix: Broken Links "Scan Now" now scans ALL posts (was only scanning 10)
* Fix: Schema page search compatibility with updated post search API
* New: Keyword Tracker "Fetch Keywords Now" button for immediate GSC data pull
* Fix: Post search no longer requires manage_options capability (works for editors too)

= 1.15.1 =
* Improvement: Admin sidebar menu now organized into 4 collapsible category groups
* Groups: Analyze & Fix, Technical SEO, Search & Social, System
* Click group headers to collapse/expand — state persists via localStorage
* Current page group auto-expands for seamless navigation
* Shorter menu labels for a cleaner sidebar
* Dashboard and Settings remain always visible

= 1.15.0 =
* NEW: Content Suggestions - AI-powered content improvement tips per post (structure, SEO, engagement, topical depth)
* NEW: Rule-based analysis engine - headings, images, alt text, internal/external links, keyword density, meta, lists
* NEW: AI analysis layer - OpenAI-powered content gap analysis when API key is configured
* NEW: WordPress Coding Standards Checker - self-audit plugin code against WP best practices
* NEW: Detects direct DB queries without prepare, unsanitized superglobals, missing ABSPATH checks, deprecated functions
* NEW: Code audit scores with file-by-file breakdown, expandable issue details with severity and line numbers
* NEW: Performance Profiler - tracks plugin load time (ms), DB queries, memory usage (KB), peak memory (MB)
* NEW: Performance history chart with rolling 200-sample trend visualization (Chart.js)
* NEW: Environment info panel - PHP/WP/MySQL versions, memory limits, active plugins, published posts
* NEW: Plugin database tables panel - row counts, data size, index size per table
* NEW: Three new dashboard navigation cards (Content Tips, Code Audit, Performance)
* Improvement: Performance data recorded per admin page load with automatic cleanup

= 1.14.0 =
* NEW: Content Readability Scoring - Flesch-Kincaid Reading Ease, grade level, sentence/paragraph analysis
* NEW: Readability suggestions - actionable tips for word count, sentence length, passive voice, transition words
* NEW: AJAX-powered readability analysis from the SEO Analyzer and meta box
* NEW: Social Preview Cards - dedicated admin page to preview and customize Facebook/Twitter sharing cards
* NEW: Custom OG Title, OG Description, OG Image overrides per post
* NEW: Custom Twitter Title, Twitter Description, Twitter Image overrides per post
* NEW: Live preview updates as you type - see exactly how your post will appear on social media
* NEW: Keyword Tracker - track search keyword rankings over time using Google Search Console data
* NEW: Daily cron fetches GSC keyword data (position, clicks, impressions, CTR) and stores historical snapshots
* NEW: Interactive position trend chart per keyword with Chart.js
* NEW: Keyword search, date range filter (7d/30d/90d), position badges (top 3/10/20)
* NEW: Dashboard navigation cards for Social Preview and Keyword Tracker
* Database: Added ssf_keyword_tracking table (auto-created via migration v6)
* IMPROVED: Social tags output now respects custom OG/Twitter overrides from the Social Preview editor

= 1.13.0 =
* NEW: Broken Link Checker - scans posts for dead links (404s, timeouts, connection errors)
* NEW: Background cron scans 5 posts per day automatically, cycles through all content
* NEW: Manual "Scan Now" checks 10 most recent posts on demand
* NEW: Recheck individual links, dismiss false positives, filter by type (internal/external)
* NEW: 404 Monitor - logs every real 404 hit on your site with hit counts and referrers
* NEW: One-click redirect creation from 404 entries (integrates with Redirects module)
* NEW: Smart noise filtering - ignores bots/scanners hitting common exploit paths
* NEW: robots.txt Editor - view, edit, and manage your site's robots.txt from the plugin
* NEW: "Load Recommended" template with optimized crawl rules and WooCommerce support
* NEW: Real-time validation warnings (blocks-all detection, missing sitemap, etc.)
* NEW: Physical file detection warning (if robots.txt file exists in site root)
* NEW: Dark-themed code editor for robots.txt content
* NEW: Dashboard navigation cards for all three new tools
* Database: Added ssf_broken_links and ssf_404_log tables (auto-created via migration v5)
* IMPROVED: DB Migrator updated to v5 for new tables

= 1.12.0 =
* NEW: Setup Wizard - guided first-run setup for API key, post types, and feature toggles
* NEW: Database Migration System - versioned schema updates that apply cleanly on plugin update
* NEW: Input Validator - centralized sanitization for all SEO titles, descriptions, URLs, API keys, and post types
* NEW: Automatic redirect to setup wizard on first plugin activation
* NEW: Skip option to bypass wizard and configure later in Settings
* IMPROVED: Settings save now uses strict validation (title length limits, URL format, allowed separators)
* IMPROVED: SEO data save uses dedicated validators for title, description, keyword, and URL fields
* IMPROVED: Post type selection validated against registered public post types
* IMPROVED: API keys stripped of non-printable characters
* Database: Versioned migration system tracks schema version and applies updates incrementally

= 1.11.0 =
* NEW: Background Job Queue - bulk operations (10+ posts) are automatically queued and processed in the background
* NEW: Job Queue admin page with real-time progress bars, cancel, and retry failed items
* NEW: API Rate Limiter - throttles OpenAI and GSC requests to prevent hitting rate limits
* NEW: Automatic retry with exponential backoff on rate limit (429) and server errors (5xx)
* NEW: Rate limit usage dashboard showing remaining requests per minute for OpenAI and GSC
* NEW: Dashboard navigation card for Background Jobs
* IMPROVED: Bulk AI Fix auto-routes to background queue when processing 10+ posts
* IMPROVED: Custom cron interval (every minute) for responsive job processing
* Database: Added ssf_jobs table for job tracking (auto-created on activation)

= 1.10.0 =
* NEW: Change History system - every AI/manual change is recorded with before/after values
* NEW: One-click Undo/Rollback - revert any change instantly from the Change History page
* NEW: Debug Log with admin viewer - errors, warnings, and info events logged to a dedicated page
* NEW: Automatic meta tracking via WordPress hooks - all _ssf_ meta changes captured without code duplication
* NEW: Source tagging - changes are tagged as AI, Manual, Bulk, Cron, or Orphan Fix for easy filtering
* NEW: Dashboard navigation cards for Change History and Debug Log
* IMPROVED: OpenAI API calls now log success/failure with token usage for monitoring
* IMPROVED: GSC API errors logged with request context for debugging
* IMPROVED: Plugin updater logs success/failure after install for troubleshooting
* Database: Added ssf_history and ssf_logs tables (auto-created on activation or admin visit)

= 1.9.0 =
* NEW: Google Search Console integration — connect your GSC account directly
* NEW: Search Performance dashboard — see clicks, impressions, CTR, and average position
* NEW: Top Search Queries table — see which keywords bring traffic
* NEW: Top Pages table — see which pages perform best in search
* NEW: Performance chart with daily clicks and impressions over time
* NEW: Submit sitemap to Google directly from the plugin
* NEW: URL Inspection API support — check if specific pages are indexed

= 1.8.7 =
* FIXED: Auto-updater zip packaging - was using backslash paths causing extraction failures on Linux servers
* Orphan fix now adds outgoing internal links within the page's own content (up to 3 relevant links)
* Fixes "No internal links found" SEO analysis warning on orphaned pages
* AI finds natural anchor text phrases and converts them to contextual links to related pages
* Improved post-install verification and error logging in updater

= 1.8.5 =
* FIX: Critical error from incorrect zip packaging in v1.8.3/v1.8.4
* Added defensive class_exists guards for SSF_Admin and SSF_Updater
* Fixed release zip to include proper folder structure for WordPress updater

= 1.8.3 =
* NEW: AI-powered internal linking for orphaned pages — automatically adds contextual links from relevant posts
* AI finds natural anchor text phrases in existing content and converts them into internal links
* Fallback to blog/contact page when no relevant content is found
* "Fix All with AI" bulk action processes all orphaned pages sequentially
* "Show All" button to view all items in any issue group (previously capped at 10)
* Removed trailing slash false positives from Indexability Auditor scan
* Trailing slashes are now auto-enforced by canonical/sitemap/OG — scan no longer flags them as issues
* Scan now displays a green "Automatically handled" status for trailing slash consistency

= 1.8.2 =
* FIX: Enforce trailing slash consistency in canonical tags, OG URLs, and sitemap URLs
* Prevents "Google chose different canonical" errors from slash mismatches
* Canonical and sitemap URLs now match WordPress permalink structure automatically

= 1.8.1 =
* FIX: SEO title not rendering in <title> tag on Elementor/custom themes
* Replaced fragile filter-chain approach with direct <title> tag output in wp_head (same approach as Yoast/Rank Math)
* Removed output buffer fallback — no longer needed with direct output
* Removed dependency on title-tag theme support for title rendering

= 1.8.0 =
* NEW: Each tool now has its own dedicated admin page instead of quick action buttons on the dashboard
* NEW: Bulk AI Fix page — full-page preview and fix workflow with post selection
* NEW: SEO Analyzer page — analyze/re-analyze posts, view scores, filter by status, paginated table
* IMPROVED: Dashboard is now a clean overview with stats and navigation cards to tool pages
* IMPROVED: Schema regeneration available on its own Schema Manager page
* Menu restructured: Dashboard > SEO Analyzer > Bulk AI Fix > All Posts > Local SEO > Schema > Redirects > Indexability Audit > Migration > Settings

= 1.7.1 =
* FIX: Bulk AI Fix preview now correctly detects posts with missing SEO data (was showing "nothing to fix" falsely)
* FIX: Replaced broken LEFT JOIN queries with reliable NOT EXISTS subqueries + TRIM for whitespace-only values
* FIX: Bulk fix now processes the exact posts user selected in preview instead of re-querying (selections were ignored)
* FIX: "Missing" filter now checks all three fields (title, description, keyword) instead of only title
* FIX: Count query was broken for DISTINCT queries, returning wrong total

= 1.7.0 =
* NEW: Redesigned Bulk AI Fix with preview-before-fix workflow
* NEW: See a full list of affected posts before running AI generation
* NEW: Select/deselect individual posts — only fix what you want
* NEW: Live progress with per-post results shown inside the modal
* NEW: Preview endpoint shows current SEO status (title, desc, keyword) for each post
* Improved: Quick Actions reorganized for clearer workflow

= 1.6.4 =
* Fix: Auto-updater now detects new versions from tags (no longer requires formal GitHub Release)
* Fix: Bulk AI generation ("Generate All Missing SEO") now works — was silently failing
* Fix: All AI handlers now validate API key upfront and return clear errors
* Fix: Empty AI responses no longer wipe existing SEO data (all handlers protected)
* Fix: Elementor/shortcode content now properly cleaned before sending to AI
* Fix: Individual AI fix buttons now show actual API errors instead of generic failure
* Fix: Post editor fix buttons validate API configuration before calling
* Hardened: All 8 AJAX handlers audited for empty-response and error-path bugs
* Improved: SQL queries use prepared statements for post type filtering

= 1.6.2 =
* Fix: "AI Unique Title" now generates a genuinely different title (tells AI to avoid repeating current one)
* Fix: "AI Unique Desc" now generates a genuinely different description
* Fix: UI no longer shows "Fixed!" when AI call fails — shows actual error message
* Fix: Validates AI returned something different before saving
* Improved: Higher temperature (0.9) for unique generation to ensure creative variation

= 1.6.1 =
* Fix: Resolved redirect loop on pages with year-prefixed slugs (e.g. 2025-scholarship)
* Fix: Removed custom redirect handler — now uses WordPress native redirect_canonical
* Performance: Removed permalink filters that ran on every link (major speed improvement)
* Performance: Output buffering now conditional (only for themes without title-tag support)
* Performance: Moved updater, post type detection to admin-only (no frontend overhead)
* Performance: Meta manager skips admin requests

= 1.6.0 =
* NEW: Comprehensive Indexability Auditor — detects all 9 Google Search Console issue types
* NEW: Blocked by robots.txt detection — parses robots.txt and flags published pages blocked from crawling
* NEW: Thin content detection — finds pages under 300 words (common "Crawled not indexed" cause)
* NEW: Duplicate title and description detection — prevents "Duplicate without canonical" issues
* NEW: Orphaned page detection — finds pages with no internal links pointing to them
* NEW: Missing SEO data detection — pages without title/description that Google may skip indexing
* NEW: Published pages with redirect detection — flags conflicting redirect/publish states
* NEW: One-click AI fix for missing SEO, duplicate titles, and duplicate descriptions
* NEW: Bulk AI fix — generate all missing SEO data across the site with one button
* NEW: Individual fix buttons for noindex removal, redirect chain flattening, and more
* Enhanced stat dashboard with missing SEO data and thin content counts
* Renamed "Search Console Fixer" to "Indexability Auditor" for clarity

= 1.5.0 =
* Added Search Console Fixer (trailing slashes, redirect chains, canonical conflicts)
* Added Search Console admin page with scan and auto-fix

= 1.4.1 =
* Fixed missing title tag on themes without title-tag support
* Added force title-tag theme support
* Added output buffer fallback for edge-case themes

= 1.4.0 =
* Added 4-layer gapless AI SEO generation system
* Auto-generate on publish AND update (not just first publish)
* Background WP-Cron runs twice daily for missing SEO
* Dashboard alert banner with one-click "Generate All Missing SEO"
* Accurate missing_titles and missing_descs counts

= 1.3.2 =
* Fixed missing meta title and og:image fallback chain

= 1.3.1 =
* Fixed fatal error on activation (mbstring dependency removed)
* Added defensive file_exists and class_exists checks

= 1.3.0 =
* Added GitHub auto-updater with private repo support
* Added GitHub token setting for authentication

= 1.2.0 =
* Added Readability Scoring (Flesch Reading Ease)
* Added Social Previews (Google, Facebook, Twitter)
* Added Redirect Manager with 404 tracking
* Added Breadcrumbs with Schema.org markup
* Added WooCommerce SEO integration

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.5.0 =
New Search Console Fixer detects and auto-fixes common indexing issues.

= 1.4.1 =
Critical fix for themes without title-tag support. Update immediately if your titles are missing.
