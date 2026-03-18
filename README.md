# ArtitechCore 

[![WordPress](https://img.shields.io/badge/WordPress-5.6%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

ArtitechCore is a high-performance, AI-driven infrastructure for WordPress. Built for the modern DG10 Agency ecosystem, it combines advanced content generation with a professional-grade SEO and Schema management suite.

---

## 📖 Table of Contents
- [🎯 Overview](#-overview)
- [🚀 Core Pillars](#-core-pillars)
- [🛠️ Detailed Feature Guide](#-detailed-feature-guide)
  [Bulk Page Engine](#bulk-page-engine)
  [AI Agency Mode](#ai-agency-mode)
  [Schema Generator PRO](#schema-generator-pro)
  [CPT & Taxonomy Manager](#cpt--taxonomy-manager)
  [Menu Architecture](#menu-architecture)
- [🎨 Branding & UX Standards](#-branding--ux-standards)
- [⚙️ Configuration & API Settings](#-configuration--api-settings)
- [🔒 Security & Technical Specs](#-security--technical-specs)
- [📈 Performance Benchmark](#-performance-benchmark)
- [🆕 Project Architecture & Launch](#-project-architecture--launch)
- [📋 Installation & Quick Start](#-installation--quick-start)
- [📄 License & Credits](#-license--credits)

---

## 🎯 Overview

**ArtitechCore** is not just a page creator; it is a full-site architectural tool. It solves the bottleneck of manual site building by allowing users to deploy complex, inter-linked content ecosystems using simple syntax, CSV imports, or high-intelligence AI agents (OpenAI, Gemini, DeepSeek).

### Why use ArtitechCore?
- **AI-Native**: Unlike other plugins that simply "add AI text," ArtitechCore uses AI to build the *structure* of your business (Post Types, Fields, and Categories).
- **Agency-Ready**: Built following the DG10 Agency design system—high contrast, glassmorphic elements, and premium feel.
- **SEO-First**: Every page is born with optimized meta-data and a tailored Schema structure.

---

## 🚀 Core Pillars

### 1. The Bulk Creation Engine
ArtitechCore provides three distinct ways to build your site:
- **Manual Input**: A powerful hyphen-based syntax for rapid drafting.
- **CSV Data Sync**: Import thousands of records with parent-child logic and custom meta.
- **AI Business Generator**: Natural language prompts turn business descriptions into full site maps.

### 2. The Structured Data Dashboard
A central hub for Schema.org management. 
- **Auto-Detect**: Scans your site to determine if a page is a "Service," "Product," "FAQ," etc.
- **Mass Generate**: Build schema for thousands of pages in batched background processes.
- **Pro Editor**: A code-editor modal with JSON validation.

### 3. Dynamic CPT Ecosystems
The plugin registers and manages Custom Post Types and Taxonomies that are intelligently linked. If you generate a "Restaurant" site, the plugin builds the "Menu Items" CPT and links it to "Cuisines" taxonomies automatically.

---

## 🛠️ Detailed Feature Guide

### Bulk Page Engine
The engine handles complex relationships and metadata formatting via the manual tab:
```plaintext
# Syntax: Page Name :+ Meta Description :* Featured Image URL ::template=slug ::status=publish
Service Area :+ We cover all regions :* https://img.com/hero.jpg ::status=publish
- Northern Region :+ Local services for the north
-- Local Branch A
```
- **Hyphens (`-`, `--`)**: Define depth in the page hierarchy.
- **Metadata Tags**: Add SEO descriptions and status triggers on the fly.

### AI Agency Mode
Advanced Mode utilizes the full power of your selected AI Provider:
1. **Business Analysis**: The AI maps out your entire industry ecosystem.
2. **CPT Generation**: It creates specialized post types (e.g., 'Team Members', 'Portfolio').
3. **Taxonomy Linking**: It registers categories specifically for those CPTs.
4. **Custom Fields**: It implements business-specific fields like `Price`, `Rating`, or `Duration`.

### Schema Generator PRO
The most advanced part of the current 1.0 release:
- **Dashboard Filters**: Filter your site by Post Type or Status to identify gaps in SEO coverage.
- **One-Click Preview**: Opens a high-performance modal with your JSON-LD.
- **Live Editor**: Edit the JSON directly in the admin.
- **Validation**: Integrated "Validate JSON" tool to ensure Google compatibility.
- **Bulk CSV Export**: Captures all entity schemas into a single portable file for audit.

---

## 🎨 Branding & UX Standards

The interface follows the **DG10 Agency Branding** guidelines:
- **Visual Palette**: Deep purples, neutral greys, and vibrant accents.
- **Glassmorphism**: Panels use semi-transparent backdrops and subtle borders.
- **Consistency**: Buttons, forms, and badges share a unified CSS variable system.
- **Accessibility**: 
  4.5:1 Contrast ratios for all text.
  Viewport-locked modals to prevent "double scrolling."
  Sticky headers and footers for long data tables.

---

## ⚙️ Configuration & API Settings

| Provider | Recommended Usage | Key Features |
|----------|-------------------|--------------|
| **OpenAI** | Best Results | Supports DALL-E 3, GPT-4o, and best structured JSON. |
| **Google Gemini** | High Speed | Exceptional response latency and massive context. |
| **DeepSeek** | Cost Efficient | High performance with simplified pricing. |

### API Controls
- **Rate Limiting**: Users are capped at 10 requests per minute per provider to ensure API health.
- **Cache Busting**: CSS and JS assets are versioned using `filemtime()` for immediate updates.
- **Business Identity**: Set your brand colors for AI image generation in the Global Settings.

---

## 🔒 Security & Technical Specs

- **CSRF Protection**: Nonces are checked on *every* AJAX and POST request.
- **Input Sanitization**: 250+ individual sanitization points using `sanitize_text_field`, `intval`, and `wp_kses`.
- **Capability Guard**: Minimal `manage_options` check required for all administrative actions.
- **Robust SQL**: Queries use `prepare()` or structured `WP_Query` to prevent injections.

---

## 📈 Performance Benchmark

- **Memory Monitoring**: The plugin tracks PHP memory usage during imports and warns users before a crash.
- **Sequential Looping**: Bulk actions process in batches (200 records) to stay within server timeout limits.
- **Asset Loading**: CSS/JS is only enqueued on ArtitechCore-specific admin pages to keep the rest of your site fast.

---

## 🆕 Project Architecture & Launch

ArtitechCore was built as a consolidated infrastructure for high-end agency deployments.

### Version 1.0 Initial Launch
This version represents the full stabilization of the page management core and AI integration layers.
- **[Core]** Consolidated all generation logic into a unified dashboard.
- **[AI]** Implemented intelligent Taxonomy-to-CPT linking logic.
- **[Security]** Completed full audit of nonces and permission guards.
- **[Accessibility]** Achieved WCAG 2.1 AA compliance across all dashboards.
- **[Compatibility]** Tested up to WordPress 6.9.4 (Bleeding Edge).

---

## 📋 Installation & Quick Start

1. **Install**: Upload the ZIP and activate.
2. **Provider**: Go to `ArtitechCore> Settings` and add your OpenAI/Gemini/DeepSeek key.
3. **Build**: Use `AI Generation> Advanced Mode` to map your business.
4. **SEO**: Go to `Schema Generator` to finalize your structured data.
5. **Launch**: Export your finalized schema at any time via the dashboard.

---

## 📄 License & Credits

- **License**: GPL v2 or later.
- **Lead Developer**: DG10 Agency Team.
- **AI Tools**: Powered by OpenAI, Google, and DeepSeek.

---
*Transforming WordPress content architecture with Artificial Intelligence.* 🚀
