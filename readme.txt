=== AIOPMS - All In One Page Management System ===
Contributors: dg10agency
Tags: pages, bulk pages, page management, ai content, schema markup, menu generator, hierarchy, seo
Requires at least: 5.6
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive page management system for WordPress with bulk creation, AI generation, hierarchy management, schema markup, and menu generation.

== Description ==

**AIOPMS** is the ultimate WordPress page management plugin that streamlines your content workflow. Whether you're building a small business website or managing a large enterprise site, AIOPMS provides powerful tools to create, organize, and optimize your pages.

= Key Features =

* **Manual Page Creation** - Create multiple pages at once with a simple text input. Use hyphens for hierarchy and special syntax for meta descriptions and templates.
* **CSV Upload** - Import pages from CSV files with support for parent pages, meta descriptions, featured images, and page templates.
* **AI-Powered Generation** - Generate page suggestions and full content using OpenAI, Google Gemini, or DeepSeek APIs.
* **Schema Markup Generator** - Automatically generate and manage Schema.org structured data for better SEO. Supports FAQ, HowTo, Review, Event, Service, Product, and more.
* **Menu Generator** - Automatically create WordPress navigation menus based on your page structure.
* **Hierarchy Visualizer** - View your page hierarchy as interactive mindmaps or org charts. Export to CSV, JSON, or Markdown.
* **Keyword Analyzer** - Analyze keyword density and get SEO recommendations for your pages.
* **Custom Post Type Manager** - Create and manage custom post types with a visual interface.

= Auto-Detection =

AIOPMS automatically detects your business information from WordPress settings, WooCommerce, Yoast SEO, RankMath, and your existing pages. Zero configuration required for most sites!

= AI Providers Supported =

* OpenAI (GPT-3.5, GPT-4)
* Google Gemini
* DeepSeek

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/aiopms-all-in-one-page-management-system/` or install through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **AIOPMS** in your admin sidebar.
4. (Optional) Go to Settings tab to configure your AI provider and API keys.

== Frequently Asked Questions ==

= Do I need an AI API key to use this plugin? =

No! The AI features are optional. You can use all other features (manual creation, CSV upload, schema generation, menu generation, etc.) without any API key.

= Which AI providers are supported? =

OpenAI (GPT-3.5/GPT-4), Google Gemini, and DeepSeek are all supported. You only need one API key.

= Is the schema markup generated valid? =

Yes! The schema is generated according to Schema.org specifications and outputs valid JSON-LD that Google can read.

= Can I create nested pages? =

Absolutely! Use hyphens in the manual creation tab to create nested page hierarchies. One hyphen = child, two hyphens = grandchild, etc.

= Does it work with WooCommerce? =

Yes, AIOPMS detects WooCommerce store settings for schema generation and can manage WooCommerce pages.

== Screenshots ==

1. Main dashboard with navigation sidebar
2. Manual page creation with hierarchy syntax
3. AI-powered page generation
4. Schema markup management dashboard
5. Hierarchy visualizer with mindmap view
6. Keyword analysis results

== Changelog ==

= 3.0 =
* NEW: AI-powered schema data extraction
* NEW: Auto-detect business information from WordPress
* NEW: Re-Scan Website button in settings
* NEW: Support for DeepSeek AI provider
* IMPROVED: Schema generators use AI + regex fallback
* IMPROVED: Organization and LocalBusiness schema include contact info
* IMPROVED: DG10 Premium Design System styling
* FIXED: Various code cleanup and security improvements

= 2.0 =
* Added AI content generation
* Added schema markup generator
* Added hierarchy visualizer
* Added keyword analyzer
* Redesigned admin interface

= 1.0 =
* Initial release
* Manual page creation
* CSV page import
* Basic menu generation

== Upgrade Notice ==

= 3.0 =
Major update with AI-powered schema extraction and auto-detection features. Backup your site before upgrading.

== Privacy Policy ==

This plugin does not collect any personal data. If you use AI features, your page content is sent to the selected AI provider (OpenAI, Google, or DeepSeek) for processing according to their privacy policies.
