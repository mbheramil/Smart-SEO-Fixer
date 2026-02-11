=== Smart SEO Fixer ===
Contributors: yourname
Donate link: https://example.com/donate
Tags: seo, ai, openai, meta description, schema, sitemap, search engine optimization
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered SEO optimization plugin that analyzes and fixes SEO issues using OpenAI.

== Description ==

Smart SEO Fixer is a powerful WordPress plugin that uses AI (powered by OpenAI) to analyze and optimize your website's SEO. It automatically detects issues and can generate optimized titles, meta descriptions, and alt text.

**Key Features:**

* **SEO Analysis** - Comprehensive analysis of titles, meta descriptions, content, headings, images, and links
* **AI-Powered Suggestions** - Generate optimized SEO titles and meta descriptions using OpenAI
* **SEO Score** - Get a score from 0-100 for each post/page with detailed feedback
* **Bulk Analysis** - Analyze all posts at once and identify issues
* **Bulk Fix** - Auto-fix SEO issues across multiple posts with AI
* **Schema Markup** - Automatic JSON-LD structured data for articles, pages, and products
* **XML Sitemap** - Built-in sitemap generator with automatic search engine pinging
* **Meta Tags** - Full control over titles, descriptions, canonical URLs, and robots meta
* **Open Graph & Twitter Cards** - Automatic social media meta tags
* **Focus Keywords** - AI can suggest focus keywords for your content
* **Google Preview** - See how your post will appear in search results

**What Gets Analyzed:**

* Title length and keyword placement
* Meta description length and content
* Content length and keyword density
* Heading structure (H1, H2, H3)
* Image alt text
* Internal and external links
* URL/slug optimization

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/smart-seo-fixer/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Smart SEO → Settings** and enter your OpenAI API key
4. Start optimizing your content!

== Frequently Asked Questions ==

= Do I need an OpenAI API key? =

Yes, to use the AI-powered features (title generation, meta description generation, keyword suggestions), you need an OpenAI API key. You can get one from https://platform.openai.com/api-keys

The plugin will still work for manual SEO analysis without an API key.

= What OpenAI model should I use? =

We recommend **GPT-4o Mini** for the best balance of quality and cost. It's fast, affordable, and produces excellent SEO content.

= How much does it cost to use? =

The plugin itself is free. OpenAI charges based on usage. GPT-4o Mini costs approximately $0.15 per million input tokens and $0.60 per million output tokens - very affordable for SEO content generation.

= Will this conflict with other SEO plugins? =

Smart SEO Fixer can work alongside other plugins, but you may want to disable overlapping features (like sitemaps) in one plugin to avoid conflicts.

= Is my content sent to OpenAI? =

When you use AI features (generate title, description, etc.), relevant content is sent to OpenAI for processing. This is necessary for the AI to understand your content. OpenAI does not use API data for training.

== Screenshots ==

1. Dashboard showing SEO health scores and statistics
2. SEO analysis metabox in post editor
3. Bulk analysis of all posts
4. Settings page with API configuration

== Changelog ==

= 1.0.0 =
* Initial release
* SEO analysis engine with scoring
* OpenAI integration for content generation
* Schema markup generator
* XML sitemap
* Meta tags management
* Dashboard with statistics
* Bulk analysis and fix features

== Upgrade Notice ==

= 1.0.0 =
Initial release of Smart SEO Fixer.

