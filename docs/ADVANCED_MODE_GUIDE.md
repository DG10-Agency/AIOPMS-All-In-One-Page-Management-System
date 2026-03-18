# ArtitechCore Advanced Mode Complete Guide

## Overview

The Advanced Mode is a powerful enhancement to the ArtitechCore plugin that goes beyond simple page creation to generate complete content ecosystems with custom post types. Unlike traditional page builders that use predefined templates, Advanced Mode uses AI to dynamically analyze your specific business and suggest relevant content structures.

## Key Features

### 🧠 Dynamic Business Analysis
- **No Templates**: AI analyzes each business uniquely, not using predefined patterns
- **Context-Aware**: Suggestions are based on your specific business model and target audience
- **Intelligent Reasoning**: AI provides clear explanations for why each content type is suggested

### 🏗️ Custom Post Type Generation
- **Dynamic Registration**: Custom post types are created based on AI analysis
- **Relevant Custom Fields**: Fields are tailored to what would actually be useful for your business
- **Sample Content**: Automatic generation of sample entries to get you started

### 🔗 Seamless Integration
- **Schema Markup**: Automatic schema generation for custom post types
- **Menu Integration**: Custom post type archives included in menu generation
- **Hierarchy View**: Custom post types appear in page hierarchy and exports
- **Backward Compatible**: Works alongside existing ArtitechCore features

## How to Use Advanced Mode

### Using Advanced Mode

1. Navigate to **ArtitechCore → Generate with AI**
2. Fill in your business information:
   **Business Type**: e.g., "Digital Marketing Agency", "Pet Grooming Service"
   **Business Details**: Detailed description of your business and services
   **SEO Keywords**: Primary keywords for your business
   **Target Audience**: Who your customers are
3. Check **"Enable Advanced Mode"** to generate custom post types and dynamic content ecosystem
4. Click **"Generate Page Suggestions"**
5. Review the AI analysis and suggestions
6. Select which pages and custom post types to create
7. Click **"Create Selected Content"**

## Business Analysis Examples

### Digital Marketing Agency
**AI Analysis**: Service-based business with recurring client relationships
**Suggested Custom Post Types**:
- **Case Studies**: Client Name, Industry, Results, Project Duration, Testimonial
- **Testimonials**: Client Name, Company, Service Used, Rating, Testimonial Text

### Pet Grooming Service
**AI Analysis**: Local service business with visual portfolio needs
**Suggested Custom Post Types**:
- **Services**: Service Name, Price, Duration, Pet Types, Description, Before/After Photos
- **Gallery**: Pet Name, Pet Type, Breed, Grooming Style, Before/After Photos

### Online Course Platform
**AI Analysis**: Educational business with structured content delivery
**Suggested Custom Post Types**:
- **Courses**: Course Title, Price, Duration, Skill Level, Prerequisites, Instructor, Curriculum
- **Instructors**: Name, Bio, Specialties, Experience, Certifications, Profile Photo

### Wedding Photography Business
**AI Analysis**: Portfolio-based business with package offerings
**Suggested Custom Post Types**:
- **Portfolio**: Couple Names, Wedding Date, Venue, Event Type, Photo Count, Gallery
- **Packages**: Package Name, Price, Hours Included, Photos Delivered, Services Included

## Custom Post Type Management

### Viewing Custom Post Types
1. Navigate to **ArtitechCore → Custom Post Types**
2. View all dynamically created custom post types
3. See post counts, custom fields, and manage entries

### Manual Custom Post Type Creation
1. Go to **ArtitechCore → Custom Post Types → Create New CPT**
2. Define post type slug, label, and description
3. Add custom fields with appropriate types
4. Create the custom post type

### Settings Configuration
1. Go to **ArtitechCore → Custom Post Types → Settings**
2. Configure integration options:
   **Auto Schema Generation**: Automatically generate schema markup
   **Include in Menu Generation**: Add CPT archives to menus
   **Include in Hierarchy**: Show CPTs in hierarchy view
   **Auto Generate Sample Content**: Create sample entries

## Integration Features

### Schema Markup
- Custom post types automatically get appropriate schema markup
- Based on content type and custom field data
- Improves SEO and search engine understanding

### Menu Generation
- Custom post type archives are included in menu generation
- Appears in relevant menu types (Services, Resources, etc.)
- Configurable through CPT settings

### Hierarchy Management
- Custom post types appear in page hierarchy view
- Shows as archive pages with individual posts as children
- Included in export functionality (CSV, Markdown, JSON)

### Export Functionality
- Custom post type data included in all export formats
- Maintains hierarchical structure
- Includes custom field data

## Best Practices

### 1. Provide Detailed Business Information
- **Business Type**: Be specific (e.g., "Local Pet Grooming Service" vs "Pet Business")
- **Business Details**: Include services, target market, unique value proposition
- **Target Audience**: Describe your ideal customers
- **Keywords**: Include primary and secondary keywords

### 2. Review AI Suggestions
- Read the business analysis to understand the AI's reasoning
- Consider which custom post types would be most valuable
- Select only the content types that make sense for your business

### 3. Customize After Creation
- Review and edit custom fields as needed
- Add or remove fields based on your specific requirements
- Create sample content to test the structure

### 4. Leverage Integration Features
- Use schema markup for better SEO
- Include custom post types in your site navigation
- Export data for documentation or migration

## Troubleshooting

### No Custom Post Types Suggested
- Ensure you provided detailed business information
- Try being more specific about your business type and services
- Check that Advanced Mode is enabled

### Custom Post Types Not Appearing
- Verify the custom post types were created successfully
- Check CPT settings to ensure they're included in menus/hierarchy
- Clear any caching plugins

### Schema Markup Issues
- Ensure auto schema generation is enabled in CPT settings
- Check that custom fields have appropriate data
- Verify schema is being generated for published content

## Technical Details

### File Structure
- `includes/advanced-ai-generator.php`: Core Advanced Mode functionality
- `includes/custom-post-type-manager.php`: CPT registration and management
- Enhanced existing files for integration

### Database Storage
- Custom post type definitions stored in `artitechcore_dynamic_cpts` option
- CPT settings stored in `artitechcore_cpt_settings` option
- Custom field data stored as post meta

### API Integration
- Uses existing AI provider system (OpenAI, Gemini, DeepSeek)
- Enhanced prompts for dynamic business analysis
- JSON response parsing for structured data

## Support and Development

### Getting Help
- Check the plugin documentation
- Review the test file (`test-advanced-mode.php`) for examples
- Contact DG10 Agency for custom development

### Contributing
- This is an open-source project
- Star the repository on GitHub
- Submit issues and feature requests

### Custom Development
- DG10 Agency offers custom WordPress development
- Contact for advanced integrations and customizations
- Professional support and maintenance available

---

**Note**: Advanced Mode is designed to be flexible and handle any business type. The AI analyzes each business uniquely and provides relevant suggestions based on the specific context provided. This ensures that the generated content structure is always appropriate for your particular business model and target audience.
