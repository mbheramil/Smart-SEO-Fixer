=== Smart SEO Fixer ===
Contributors: mbheramil
Tags: seo, ai, openai, meta description, schema, sitemap, search engine optimization, breadcrumbs, redirects, local seo
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.15.0
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
