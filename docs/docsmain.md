# DOCSMAIN.md

## Repository Overview

ArtitechCore () is a WordPress plugin that provides AI-powered bulk page creation, hierarchical management, schema markup generation, and menu automation. The plugin integrates with multiple AI providers (OpenAI, Google Gemini, DeepSeek) to generate content and supports manual creation via CSV import or hierarchical input.

## Essential Development Commands

### WordPress CLI Commands
```bash
# Activate plugin
wp plugin activate artitechcore-main

# Deactivate plugin
wp plugin deactivate artitechcore-main

# Check plugin status
wp plugin status artitechcore-main

# Update database after code changes
wp option update artitechcore_version 1.0

# Clear WordPress caches
wp cache flush
```

### Testing & Debugging
```bash
# Enable WordPress debug mode
wp config set WP_DEBUG true-type=constant
wp config set WP_DEBUG_LOG true-type=constant
wp config set WP_DEBUG_DISPLAY false-type=constant

# View plugin error logs
tailf wp-content/debug.log

# Test API connectivity (requires API keys)
wp eval "echo artitechcore_get_openai_suggestions('test', 'test', 'test', 'test', get_option('artitechcore_openai_api_key'));"
```

### Database Operations
```bash
# Export plugin settings
wp option get artitechcore_ai_provider
wp option get artitechcore_auto_schema_generation

# Reset plugin settings (development only)
wp option delete artitechcore_ai_provider
wp option delete artitechcore_openai_api_key
wp option delete artitechcore_gemini_api_key
wp option delete artitechcore_deepseek_api_key
```

## Architecture Overview

### Plugin Structure
```
artitechcore-for-wordpress.php  # Main plugin file
includes/
├── admin-menu.php           # Admin interface & tab management
├── ai-generator.php         # AI provider integrations
├── page-creation.php        # Core page creation logic
├── csv-handler.php          # CSV import/export functionality
├── schema-generator.php     # Schema.org markup generation
├── hierarchy-manager.php    # Page hierarchy visualization
├── menu-generator.php       # WordPress menu automation
└── settings-page.php        # Plugin settings management
```

### Core Components

#### 1. Tab-Based Admin Interface (`admin-menu.php`)
- **Pattern**: Single-page application with tab navigation
- **Entry Point**: `artitechcore_admin_page()` function
- **Tabs**: Manual Creation, CSV Upload, AI Generation, Schema Generator, Menu Generator, Hierarchy Viewer, Settings

#### 2. AI Integration System (`ai-generator.php`)
- **Providers**: OpenAI (GPT-4 + DALL-E), Google Gemini, DeepSeek
- **Pattern**: Strategy pattern for AI provider switching
- **Key Functions**:
  `artitechcore_get_openai_suggestions()` OpenAI content generation
  `artitechcore_get_gemini_suggestions()` Gemini content generation  
  `artitechcore_get_deepseek_suggestions()` DeepSeek content generation

#### 3. Page Creation Engine (`page-creation.php`)
- **Pattern**: Factory pattern for different creation methods
- **Core Function**: `artitechcore_create_pages_manually()` Handles hierarchical parsing
- **SEO Optimization**: `artitechcore_generate_seo_slug()` Generates 72-char max slugs
- **Hierarchy Parsing**: Uses hyphen depth counting (`-`, `--`, `---`)

#### 4. Schema Generator (`schema-generator.php`)
- **Pattern**: Strategy pattern with automatic type detection
- **Schema Types**: FAQ, Blog, Article, Service, Product, Organization, LocalBusiness, WebPage
- **Detection Logic**: Content analysis using regex patterns and keyword matching
- **Storage**: Uses WordPress post meta (`_artitechcore_schema_type`, `_artitechcore_schema_data`)

### Data Flow Architecture

1. **Input Processing**: Manual text → CSV → AI suggestions
2. **Content Generation**: AI providers → structured content with meta descriptions
3. **Page Creation**: WordPress `wp_insert_post()` with hierarchical relationships
4. **Enhancement**: Schema generation → Featured image processing → Menu integration

### WordPress Integration Points

#### Hooks Used
- `admin_menu` Plugin admin interface
- `admin_enqueue_scripts` Asset loading
- `admin_init` Settings registration
- `save_post` Schema generation triggers
- `wp_head` Frontend schema output
- `rest_api_init` REST API endpoints

#### Settings API Integration
- Settings Group: `artitechcore_settings_group`
- Options Stored: `artitechcore_ai_provider`, `artitechcore_openai_api_key`, `artitechcore_brand_color`, etc.

## Key Features & Implementation

### 1. Hierarchical Page Creation
```php
// Input syntax: Use hyphens for nesting
Home
-About Us
--Our Team
--Our History
Services
-Web Design
-SEO Services

// Meta description syntax: :+description
About Us:+Learn about our company story and mission

// Template syntax: ::template=template-name
Services::template=full-width

// Status syntax: ::status=draft
Draft Page::status=draft
```

### 2. CSV Import System
**Required Column**: `post_title`
**Optional Columns**: `slug`, `post_parent`, `meta_description`, `featured_image`, `page_template`, `post_status`

**Parent-Child Relationships**: Uses `post_parent` column with parent page titles, resolved via `$page_map` array.

### 3. AI Content Generation Workflow
1. User inputs business context (type, details, keywords, audience)
2. System selects AI provider based on settings
3. Structured prompt sent to AI API
4. Response parsed into hierarchical format
5. User selects pages for creation
6. Optional image generation (OpenAI only)

### 4. Schema Markup Generation
- **Automatic Detection**: Content analysis determines schema type
- **JSON-LD Output**: Injected into `<head>` via `wp_head` hook
- **Admin Column**: Shows schema status in Pages list
- **Bulk Operations**: Generate schema for all pages at once

## Development Guidelines

### Code Organization
- **File Naming**: Kebab-case (`admin-menu.php`, `ai-generator.php`)
- **Function Naming**: Snake_case with `artitechcore_` or `artitechcore_` prefix
- **Class Naming**: PascalCase with `ArtitechCore_` prefix (when used)

### Security Implementation
- **Nonce Verification**: All forms use WordPress nonces
- **Capability Checks**: `manage_options` for admin functions, `publish_pages` for creation
- **Input Sanitization**: `sanitize_text_field()`, `sanitize_textarea_field()`, `esc_url_raw()`
- **Output Escaping**: `esc_html()`, `esc_attr()`, `esc_url()`

### API Integration Patterns
```php
// Provider switching pattern
$provider = get_option('artitechcore_ai_provider', 'openai');
switch ($provider) {
    case 'openai':
        return artitechcore_get_openai_suggestions($params);
    case 'gemini':
        return artitechcore_get_gemini_suggestions($params);
    case 'deepseek':
        return artitechcore_get_deepseek_suggestions($params);
}
```

### REST API Endpoints
- `GET /wp-json/artitechcore/v1/hierarchy` Page hierarchy data
- `GET /wp-json/artitechcore/v1/hierarchy/export/csv` CSV export

## Configuration & Settings

### Plugin Options (stored in WordPress options table)
```php
artitechcore_ai_provider          // 'openai'|'gemini'|'deepseek'
artitechcore_openai_api_key       // OpenAI API key
artitechcore_gemini_api_key       // Google Gemini API key  
artitechcore_deepseek_api_key     // DeepSeek API key
artitechcore_brand_color          // Hex color for image generation
artitechcore_sitemap_url          // URL for sitemap link in menus
artitechcore_auto_schema_generation // Boolean for automatic schema
```

### Plugin Constants
```php
ArtitechCore_PLUGIN_PATH          // Plugin directory path
ArtitechCore_PLUGIN_URL           // Plugin URL for assets
ArtitechCore_GITHUB_URL           // GitHub repository URL
```

## Common Development Tasks

### Adding a New AI Provider
1. Add provider option to settings dropdown (`settings-page.php`)
2. Create API key field callback function
3. Implement suggestion function in `ai-generator.php`
4. Add case to provider switch statement
5. Update error handling for new provider

### Adding a New Schema Type
1. Define schema constant in `schema-generator.php`
2. Create detection function (e.g., `artitechcore_is_new_type_page()`)
3. Add case to `artitechcore_detect_schema_type()`
4. Implement generation function (e.g., `artitechcore_generate_new_type_schema()`)
5. Update schema switch statement

### Creating Custom Menu Types
1. Add menu type to `menu-generator.php` 
2. Implement detection logic for relevant pages
3. Create menu generation function
4. Add menu type to admin interface dropdown
5. Update menu generation switch statement

## Debugging & Troubleshooting

### Common Issues

#### AI API Errors
- **Check**: API key validity and format
- **Debug**: Enable WordPress debug logging
- **Test**: Manual API calls using curl

#### Schema Generation Problems  
- **Check**: `artitechcore_auto_schema_generation` setting
- **Debug**: Content analysis patterns in detection functions
- **Fix**: Regenerate schema manually from admin interface

#### Hierarchy Import Issues
- **Check**: CSV column headers match expected format
- **Debug**: Parent-child relationship resolution
- **Fix**: Ensure parent pages are created before children

### Debug Mode Activation
```php
// Add to wp-config.php for detailed debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Plugin-specific debug constant (if implemented)
define('ArtitechCore_DEBUG', true);
```

## Performance Considerations

### Batch Processing
- **Page Creation**: Process in smaller batches for large operations
- **API Calls**: Implement rate limiting and retry logic
- **Memory Management**: Monitor memory usage during bulk operations

### Database Optimization
- **Schema Storage**: Uses post meta for efficient queries
- **Hierarchy Queries**: Leverages WordPress native hierarchy system
- **Caching**: Consider object caching for repeated operations

## WordPress Version Compatibility
- **Minimum**: WordPress 5.6+
- **Recommended**: WordPress 6.0+
- **PHP**: 7.4+ (8.0+ recommended)
- **Tested Up To**: Check plugin header for current version

## External Dependencies
- **AI APIs**: OpenAI GPT-4/DALL-E, Google Gemini, DeepSeek
- **JavaScript Libraries**: jQuery (bundled with WordPress)
- **CDN Resources**: jsTree, D3.js (for hierarchy visualization)

## Support Resources
- **Plugin Documentation**: Comprehensive README.md with usage examples
- **Feature Documentation**: Individual .md files for major features
- **GitHub Repository**: https://github.com/DG10-Agency/ArtitechCore-WP.git
