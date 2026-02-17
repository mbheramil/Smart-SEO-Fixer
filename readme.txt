=== Smart SEO Fixer ===
Contributors: mbheramil
Tags: seo, ai, openai, meta description, schema, sitemap, search engine optimization, breadcrumbs, redirects, local seo
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.5.0
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
