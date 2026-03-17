# AIOPMS - Developer Documentation

## Table of Contents

1. [Plugin Overview](#plugin-overview)
2. [Plugin Structure](#plugin-structure)
3. [Hooks and Filters](#hooks-and-filters)
4. [API Endpoints](#api-endpoints)
5. [Database Schema](#database-schema)
6. [Security Measures](#security-measures)
7. [Development Setup](#development-setup)
8. [Contribution Guidelines](#contribution-guidelines)
9. [Code Standards](#code-standards)
10. [Testing](#testing)
11. [Performance Considerations](#performance-considerations)
12. [Troubleshooting](#troubleshooting)

## Plugin Overview

**AIOPMS (All In One Page Management System)** is a comprehensive WordPress plugin that provides advanced page management capabilities including:

- Bulk page creation with AI assistance
- Page hierarchy visualization and management
- Schema markup generation
- Menu generation
- Custom post type management
- Keyword analysis
- CSV import/export functionality

### Version Information
- **Current Version**: 3.0
- **Minimum WordPress**: 5.6
- **Minimum PHP**: 7.4
- **Tested up to**: WordPress 6.4

## Plugin Structure

```
aiopms-all-in-one-page-management-system/
├── aiopms-all-in-one-page-management-system.php  # Main plugin file
├── includes/                                      # Core functionality
│   ├── admin-menu.php                            # Admin interface
│   ├── ai-generator.php                          # AI integration
│   ├── csv-handler.php                           # CSV processing
│   ├── custom-post-type-manager.php              # CPT management
│   ├── hierarchy-manager.php                     # Page hierarchy
│   ├── keyword-analyzer.php                      # Keyword analysis
│   ├── menu-generator.php                        # Menu generation
│   ├── page-creation.php                         # Page creation logic
│   ├── schema-generator.php                      # Schema markup
│   └── settings-page.php                         # Settings management
├── assets/                                        # Frontend assets
│   ├── css/                                      # Stylesheets
│   ├── js/                                       # JavaScript files
│   └── images/                                   # Images and icons
├── languages/                                     # Internationalization
└── documentation/                                 # Documentation files
```

### Core Components

#### 1. Main Plugin File (`aiopms-all-in-one-page-management-system.php`)
- Plugin header and metadata
- Activation/deactivation hooks
- Constants definition
- Core initialization

#### 2. Admin Interface (`includes/admin-menu.php`)
- Admin menu registration
- Tab-based interface
- User permissions handling

#### 3. AI Integration (`includes/ai-generator.php`)
- OpenAI, Gemini, and DeepSeek API integration
- Rate limiting implementation
- Content generation logic
- Image generation support

#### 4. Hierarchy Management (`includes/hierarchy-manager.php`)
- Page hierarchy visualization
- Export functionality (CSV, Markdown, JSON)
- Memory monitoring
- REST API endpoints

#### 5. Schema Generator (`includes/schema-generator.php`)
- Automatic schema markup generation
- Schema type detection
- Schema management dashboard
- Schema validation

## Hooks and Filters

### Actions

#### Plugin Lifecycle
```php
// Plugin activation
add_action('aiopms_activate', 'aiopms_setup_default_options');

// Plugin deactivation
add_action('aiopms_deactivate', 'aiopms_cleanup_data');
```

#### Admin Interface
```php
// Admin menu registration
add_action('admin_menu', 'aiopms_add_admin_menu');

// Admin scripts and styles
add_action('admin_enqueue_scripts', 'aiopms_enqueue_hierarchy_assets');
```

#### Content Generation
```php
// AI content generation
add_action('aiopms_generate_content', 'aiopms_process_ai_generation');

// Schema generation
add_action('save_post', 'aiopms_generate_schema_on_save');
add_action('aiopms_generate_schema_for_post', 'aiopms_generate_cpt_schema', 10, 2);
```

#### Custom Post Types
```php
// CPT initialization
add_action('init', 'aiopms_register_existing_dynamic_cpts', 20);
add_action('plugins_loaded', 'aiopms_init_custom_post_type_manager');

// CPT management
add_action('admin_menu', 'aiopms_add_cpt_management_menu');
```

#### Schema Management
```php
// Schema output
add_action('wp_head', 'aiopms_output_schema_markup');

// Schema column management
add_action('manage_page_posts_custom_column', 'aiopms_display_schema_column', 10, 2);
add_action('pre_get_posts', 'aiopms_handle_schema_column_sorting');
```

### Filters

#### Data Processing
```php
// Hierarchy export data
add_filter('aiopms_hierarchy_export_data', 'aiopms_add_cpt_to_hierarchy_export');

// Menu generation
add_filter('aiopms_menu_generation_pages', 'aiopms_add_cpt_archives_to_menus');
```

#### Admin Interface
```php
// Page list columns
add_filter('manage_page_posts_columns', 'aiopms_add_schema_column');
add_filter('manage_edit-page_sortable_columns', 'aiopms_make_schema_column_sortable');

// Page row actions
add_filter('page_row_actions', 'aiopms_add_schema_quick_actions', 10, 2);
```

### Custom Hooks

#### Content Generation Hooks
```php
// Before AI generation
do_action('aiopms_before_ai_generation', $business_type, $business_details);

// After AI generation
do_action('aiopms_after_ai_generation', $generated_content, $provider);

// Before schema generation
do_action('aiopms_before_schema_generation', $post_id, $post_type);

// After schema generation
do_action('aiopms_after_schema_generation', $post_id, $schema_data);
```

#### Hierarchy Hooks
```php
// Before hierarchy export
do_action('aiopms_before_hierarchy_export', $export_type, $pages);

// After hierarchy export
do_action('aiopms_after_hierarchy_export', $export_type, $export_data);
```

## API Endpoints

### REST API Endpoints

#### Hierarchy Data
```php
GET /wp-json/aiopms/v1/hierarchy
```
- **Description**: Retrieve page hierarchy data
- **Authentication**: Requires `edit_pages` capability
- **Response**: JSON array of hierarchy nodes

#### Custom Post Types
```php
GET /wp-json/aiopms/v1/cpts
```
- **Description**: Retrieve custom post type data
- **Authentication**: Requires `edit_posts` capability
- **Response**: JSON array of CPT information

### AJAX Endpoints

#### Export Functions
```php
// CSV Export
wp_ajax_aiopms_export_csv

// Markdown Export
wp_ajax_aiopms_export_markdown

// JSON Export
wp_ajax_aiopms_export_json
```

#### Keyword Analysis
```php
// Analyze keywords
wp_ajax_aiopms_analyze_keywords

// Get pages for analysis
wp_ajax_aiopms_get_pages

// Export analysis results
wp_ajax_aiopms_export_keyword_analysis
```

#### Schema Management
```php
// Get schema preview
wp_ajax_aiopms_get_schema_preview
```

## Database Schema

### WordPress Options Table

The plugin stores configuration data in the `wp_options` table:

```sql
-- Plugin version and settings
aiopms_version                    -- Plugin version
aiopms_ai_provider               -- Selected AI provider (openai, gemini, deepseek)
aiopms_openai_api_key           -- OpenAI API key
aiopms_gemini_api_key           -- Gemini API key
aiopms_deepseek_api_key         -- DeepSeek API key
aiopms_brand_color              -- Brand color for AI-generated images
aiopms_default_status           -- Default page status (draft, publish)
aiopms_auto_schema_generation   -- Auto-generate schema markup
aiopms_enable_image_generation  -- Enable AI image generation
aiopms_image_quality            -- Image generation quality
aiopms_image_size               -- Image generation size
aiopms_max_tokens               -- Maximum tokens for AI generation

-- Custom Post Type settings
aiopms_dynamic_cpts             -- Dynamic custom post types data
aiopms_cpt_settings             -- CPT configuration settings

-- Schema settings
aiopms_schema_settings          -- Schema generation settings
aiopms_schema_cache             -- Cached schema data
```

### Custom Tables

#### Generation Logs Table
```sql
CREATE TABLE wp_aiopms_generation_logs (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_id bigint(20) unsigned NOT NULL,
    type varchar(50) NOT NULL,
    provider varchar(50) NOT NULL,
    success tinyint(1) NOT NULL DEFAULT 0,
    count int(11) NOT NULL DEFAULT 0,
    error_message text,
    ip_address varchar(45),
    user_agent text,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY type (type),
    KEY provider (provider),
    KEY created_at (created_at)
);
```

### Post Meta

The plugin uses WordPress post meta for schema data:

```sql
-- Schema markup storage
_aiopms_schema_type     -- Schema type (WebPage, Article, etc.)
_aiopms_schema_data     -- Serialized schema data
_aiopms_schema_generated -- Timestamp of schema generation
```

## Security Measures

### Input Validation and Sanitization

#### Data Sanitization
```php
// Text fields
sanitize_text_field($input)

// Textarea fields
sanitize_textarea_field($input)

// File uploads
sanitize_file_name($filename)

// URLs
esc_url($url)

// HTML output
esc_html($html)
esc_attr($attribute)
```

#### Nonce Verification
```php
// Form submissions
wp_verify_nonce($_POST['_wpnonce'], 'aiopms_action_name')

// AJAX requests
wp_verify_nonce($_GET['nonce'], 'aiopms_export_nonce')
```

### Permission Checks

#### Capability Checks
```php
// Page editing
current_user_can('edit_pages')

// Post editing
current_user_can('edit_posts')

// Plugin management
current_user_can('manage_options')
```

#### REST API Authentication
```php
// Permission callback for REST endpoints
'permission_callback' => function () {
    return current_user_can('edit_pages');
}
```

### Rate Limiting

#### AI API Rate Limiting
```php
// Rate limiting implementation
function aiopms_check_ai_rate_limit($provider = null) {
    // 10 requests per minute per provider per user
    // Uses WordPress transients for storage
}
```

### File Upload Security

#### Upload Validation
```php
// File type validation
$allowed_types = ['csv'];
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// File size limits
$max_size = 1048576; // 1MB

// Upload error checking
if ($file['error'] !== UPLOAD_ERR_OK) {
    // Handle upload errors
}
```

## Development Setup

### Prerequisites

- WordPress 5.6 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- Node.js (for asset compilation)
- Composer (for dependencies)

### Local Development

1. **Clone the repository**
```bash
git clone https://github.com/DG10-Agency/AIOPMS-All-In-One-Page-Management-System.git
cd AIOPMS-All-In-One-Page-Management-System
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Set up WordPress environment**
```bash
# Create wp-config.php with database credentials
# Set up local WordPress installation
```

4. **Activate the plugin**
```bash
# Copy plugin to wp-content/plugins/
# Activate through WordPress admin
```

### Environment Configuration

#### Required Environment Variables
```bash
# AI API Keys (for testing)
OPENAI_API_KEY=your_openai_key
GEMINI_API_KEY=your_gemini_key
DEEPSEEK_API_KEY=your_deepseek_key

# WordPress Configuration
WP_DEBUG=true
WP_DEBUG_LOG=true
```

#### Development Constants
```php
// Add to wp-config.php for development
define('AIOPMS_DEBUG', true);
define('AIOPMS_DEV_MODE', true);
```

## Contribution Guidelines

### Getting Started

1. **Fork the repository**
2. **Create a feature branch**
```bash
git checkout -b feature/your-feature-name
```

3. **Make your changes**
4. **Test thoroughly**
5. **Submit a pull request**

### Code Standards

#### PHP Standards
- Follow WordPress Coding Standards
- Use PSR-12 for modern PHP features
- Document all functions with PHPDoc
- Use meaningful variable and function names

#### JavaScript Standards
- Follow WordPress JavaScript Coding Standards
- Use ES6+ features where appropriate
- Comment complex logic
- Use consistent indentation (2 spaces)

#### CSS Standards
- Follow WordPress CSS Coding Standards
- Use BEM methodology for class naming
- Organize styles logically
- Use CSS custom properties for theming

### Pull Request Process

1. **Update documentation** for any new features
2. **Add tests** for new functionality
3. **Update version numbers** if applicable
4. **Test on multiple WordPress versions**
5. **Ensure backward compatibility**

### Issue Reporting

When reporting issues, include:
- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected vs actual behavior
- Error messages (if any)

## Code Standards

### PHP Standards

#### Function Naming
```php
// Use descriptive prefixes
function aiopms_get_page_hierarchy() {}
function aiopms_generate_schema_markup() {}
function aiopms_validate_api_key() {}
```

#### Class Structure
```php
class AIOPMS_Keyword_Analyzer {
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('aiopms_settings');
    }
    
    public function analyze_keywords($keywords) {
        // Implementation
    }
}
```

#### Error Handling
```php
try {
    $result = aiopms_generate_content($data);
    return $result;
} catch (Exception $e) {
    error_log('AIOPMS Error: ' . $e->getMessage());
    return new WP_Error('generation_failed', $e->getMessage());
}
```

### JavaScript Standards

#### Function Structure
```javascript
(function($) {
    'use strict';
    
    var AIOPMSHierarchy = {
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            $(document).on('click', '.aiopms-button', this.handleClick);
        },
        
        handleClick: function(e) {
            e.preventDefault();
            // Handle click
        }
    };
    
    $(document).ready(function() {
        AIOPMSHierarchy.init();
    });
    
})(jQuery);
```

### CSS Standards

#### Class Naming
```css
/* BEM methodology */
.aiopms-hierarchy-container {}
.aiopms-hierarchy-container__header {}
.aiopms-hierarchy-container__header--active {}

/* Component-based organization */
.aiopms-button {}
.aiopms-button--primary {}
.aiopms-button--secondary {}
```

## Testing

### Unit Testing

#### PHP Unit Tests
```php
class Test_AIOPMS_Hierarchy extends WP_UnitTestCase {
    
    public function test_get_page_hierarchy() {
        $hierarchy = aiopms_get_page_hierarchy();
        $this->assertIsArray($hierarchy);
    }
    
    public function test_memory_monitoring() {
        $start_memory = aiopms_get_memory_usage();
        $this->assertIsArray($start_memory);
        $this->assertArrayHasKey('current_mb', $start_memory);
    }
}
```

#### JavaScript Tests
```javascript
describe('AIOPMS Hierarchy', function() {
    it('should initialize correctly', function() {
        expect(AIOPMSHierarchy).toBeDefined();
    });
    
    it('should handle button clicks', function() {
        // Test implementation
    });
});
```

### Integration Testing

#### API Endpoint Testing
```php
class Test_AIOPMS_API extends WP_UnitTestCase {
    
    public function test_hierarchy_endpoint() {
        $request = new WP_REST_Request('GET', '/aiopms/v1/hierarchy');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
    }
}
```

### Performance Testing

#### Memory Usage Testing
```php
function test_memory_usage_with_large_dataset() {
    $start_memory = memory_get_usage(true);
    
    // Generate large dataset
    $pages = aiopms_generate_test_pages(1000);
    $hierarchy = aiopms_get_page_hierarchy();
    
    $end_memory = memory_get_usage(true);
    $memory_used = ($end_memory - $start_memory) / 1024 / 1024;
    
    $this->assertLessThan(100, $memory_used); // Should use less than 100MB
}
```

## Performance Considerations

### Memory Management

#### Memory Monitoring
```php
// Monitor memory usage in large operations
$start_memory = aiopms_monitor_memory_usage('OPERATION_NAME');

// Perform operation
$result = aiopms_process_large_dataset();

// Log memory usage
aiopms_monitor_memory_usage('OPERATION_NAME', $start_memory);
```

#### Optimization Strategies
- Use WordPress transients for caching
- Implement pagination for large datasets
- Use lazy loading for heavy operations
- Monitor memory usage with built-in functions

### Database Optimization

#### Query Optimization
```php
// Use specific fields instead of SELECT *
$pages = get_pages(array(
    'fields' => 'ids',
    'number' => 100
));

// Use proper indexing
// Add indexes for frequently queried fields
```

#### Caching Strategy
```php
// Cache expensive operations
$cache_key = 'aiopms_hierarchy_' . md5(serialize($args));
$cached_data = get_transient($cache_key);

if ($cached_data === false) {
    $cached_data = aiopms_generate_hierarchy_data($args);
    set_transient($cache_key, $cached_data, HOUR_IN_SECONDS);
}
```

### Frontend Performance

#### Asset Optimization
- Minify CSS and JavaScript
- Use CDN for external libraries
- Implement lazy loading for images
- Optimize asset loading order

#### JavaScript Performance
```javascript
// Debounce expensive operations
var debouncedSearch = _.debounce(function(query) {
    AIOPMSHierarchy.performSearch(query);
}, 300);

// Use event delegation
$(document).on('click', '.aiopms-button', function() {
    // Handle click
});
```

## Troubleshooting

### Common Issues

#### Memory Exhaustion
**Problem**: Fatal error: Allowed memory size exhausted
**Solution**: 
- Increase PHP memory limit
- Use memory monitoring functions
- Implement pagination for large datasets

#### API Rate Limiting
**Problem**: AI API requests being blocked
**Solution**:
- Check rate limiting implementation
- Verify API key validity
- Monitor rate limit logs

#### Schema Generation Issues
**Problem**: Schema markup not appearing
**Solution**:
- Check schema generation settings
- Verify post meta data
- Clear schema cache

### Debug Mode

#### Enable Debug Logging
```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('AIOPMS_DEBUG', true);
```

#### Debug Functions
```php
// Log debug information
if (defined('AIOPMS_DEBUG') && AIOPMS_DEBUG) {
    error_log('AIOPMS Debug: ' . print_r($data, true));
}
```

### Support Resources

- **GitHub Issues**: Report bugs and feature requests
- **Documentation**: Check this file and other docs
- **WordPress.org Support**: Community support forum
- **DG10 Agency**: Professional support available

---

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## Credits

- **Developed by**: DG10 Agency
- **Website**: https://www.dg10.agency
- **GitHub**: https://github.com/DG10-Agency/AIOPMS-All-In-One-Page-Management-System

---

*Last updated: December 2024*
*Version: 3.0*
