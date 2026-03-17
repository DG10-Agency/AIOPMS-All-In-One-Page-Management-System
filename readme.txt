=== AIOPMS - All In One Page Management System ===
Contributors: dg10agency
Tags: pages, schema markup, bulk creation, ai content, seo generator, menu generator, openai, gemini, hierarchy
Requires at least: 5.6
Tested up to: 6.9.4
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AIOPMS is the ultimate page management and SEO infrastructure plugin, combining DG10 Agency design with powerful AI-driven content and schema generation.

== Description ==

**AIOPMS (All In One Page Management System)** is a comprehensive solution designed to eliminate the manual labor of building WordPress websites. Built for agencies and power-users, it integrates leading AI providers (OpenAI, Google Gemini, DeepSeek) into a professional interface that manages everything from content hierarchy to structured data (Schema.org).

With AIOPMS, you don't just "write pages"—you architect entire business ecosystems. The plugin understands your business goals and suggests the ideal Custom Post Types, categories, and page structures required for your industry.

= 🚀 Main Features in Detail =

*   **🤖 AI-Powered Content Architecture** - Go beyond text. AIOPMS builds your site structure. It generates intelligent page hierarchies based on your business model.
*   **🏗️ Advanced CPT & Taxonomy Engine** - Create business-specific Custom Post Types (e.g., Doctors, Products, etc.) and link them to AI-suggested taxonomies. Business-critical fields (Price, Duration, Location) are automatically implemented.
*   **📊 Pro Schema Management Suite** - A dedicated dashboard to monitor your SEO coverage. Generate, edit, and bulk-manage JSON-LD schema (FAQ, Product, LocalBusiness, etc.) with a live code editor and export your entire schema set to CSV.
*   **📂 Intelligent CSV Bulk Import** - Deploy hundreds of SEO-optimized pages in seconds. Supports robust validation, parent-child relationships, and metadata mapping.
*   **🍔 Smart Menu Generator** - Instantly build navigation, service, and footer menus based on your site's hierarchy. Automatically organizes your content for best UX.
*   **🎨 Premium DG10 Agency Design** - A glassmorphic, modern admin interface built for usability. High contrast, mobile-responsive, and visually stunning.
*   **⚡ High Performance Infrastructure** - Built with efficiency in mind. Sequential batch processing for bulk actions and optimized SQL counts to keep your dashboard lightning fast.
*   **♿ Full Accessibility** - 100% WCAG 2.1 AA compliant. Proper ARIA labels, focus management, and keyboard-first navigation are standard.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the **AIOPMS** dashboard in your admin sidebar.
4. Go to **Settings** to add your OpenAI, Gemini, or DeepSeek API key to unlock the AI features.

== Frequently Asked Questions ==

= Does it support bulk schema generation? =
Yes! In the Schema Generator dashboard, you can filter your pages and apply "Generate" or "Remove" actions to all filtered results at once. It processes items in batches to prevent server timeouts.

= Can I export my schema data for auditing? =
Absolutely. There is a built-in CSV export button that captures all structured data stored for your Posts, Pages, and Taxonomies into a single portable file.

= Which AI provider do you recommend? =
For most content and structural generation, we strictly recommend **OpenAI**. For high-speed large-scale suggestions, Google Gemini is an excellent alternative.

= Is the schema markup invisible to users? =
Yes. All schema is generated as JSON-LD and inserted into the `<head>` of your website. It is designed for search engines like Google and Bing and will not affect your frontend layout.

= Is my API Key secure? =
Yes, your API keys are stored securely in your WordPress database and are only used for direct server-to-server communication with the AI provider.

== Screenshots ==

1. **Branded Dashboard** - The central hub for all business management.
2. **AI Logic Engine** - Creating structured site maps from simple descriptions.
3. **Advanced Schema UI** - The full dashboard for structured data management.
4. **JSON Modal Editor** - Live code editing with validation tools.
5. **Import/Export System** - Managing large datasets with CSV tools.

== Changelog ==

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.0 =
Updates Coming soon...

== Privacy Policy ==

AIOPMS does not store or collect personal user data. If AI features are used, your business context and content are sent to the selected AI provider (OpenAI, Google, or DeepSeek) according to their respective privacy terms. No data is shared with third parties beyond the specific provider selected by the administrator.
