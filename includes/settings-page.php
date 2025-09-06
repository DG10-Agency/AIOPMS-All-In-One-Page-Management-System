<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register settings with sanitization callbacks
function aiopms_register_settings() {
    register_setting('aiopms_settings_group', 'aiopms_ai_provider', 'sanitize_key');
    register_setting('aiopms_settings_group', 'aiopms_openai_api_key', 'sanitize_text_field');
    register_setting('aiopms_settings_group', 'aiopms_gemini_api_key', 'sanitize_text_field');
    register_setting('aiopms_settings_group', 'aiopms_deepseek_api_key', 'sanitize_text_field');
    register_setting('aiopms_settings_group', 'aiopms_brand_color', 'sanitize_hex_color');
    register_setting('aiopms_settings_group', 'aiopms_sitemap_url', 'esc_url_raw');
    register_setting('aiopms_settings_group', 'aiopms_auto_schema_generation', 'absint');
}
add_action('admin_init', 'aiopms_register_settings');

// Settings tab content
function aiopms_settings_tab() {
    ?>
    <form method="post" action="options.php">
        <?php
        settings_fields('aiopms_settings_group');
        do_settings_sections('aiopms-page-management');
        submit_button();
        ?>
    </form>
    <?php
}

// Add settings section and fields
function aiopms_settings_init() {
    add_settings_section(
        'aiopms_settings_section',
        'AI Settings',
        'aiopms_settings_section_callback',
        'aiopms-page-management'
    );

    add_settings_field(
        'aiopms_ai_provider',
        'AI Provider',
        'aiopms_ai_provider_callback',
        'aiopms-page-management',
        'aiopms_settings_section'
    );

    add_settings_field(
        'aiopms_openai_api_key',
        'OpenAI API Key',
        'aiopms_openai_api_key_callback',
        'aiopms-page-management',
        'aiopms_settings_section'
    );

    add_settings_field(
        'aiopms_gemini_api_key',
        'Gemini API Key',
        'aiopms_gemini_api_key_callback',
        'aiopms-page-management',
        'aiopms_settings_section'
    );

    add_settings_field(
        'aiopms_deepseek_api_key',
        'DeepSeek API Key',
        'aiopms_deepseek_api_key_callback',
        'aiopms-page-management',
        'aiopms_settings_section'
    );

    add_settings_field(
        'aiopms_brand_color',
        'Brand Color',
        'aiopms_brand_color_callback',
        'aiopms-page-management',
        'aiopms_settings_section'
    );

    add_settings_field(
        'aiopms_sitemap_url',
        'Sitemap URL',
        'aiopms_sitemap_url_callback',
        'aiopms-page-management',
        'aiopms_settings_section'
    );

    // Schema settings section
    add_settings_section(
        'aiopms_schema_settings_section',
        'Schema Settings',
        'aiopms_schema_settings_section_callback',
        'aiopms-page-management'
    );

    add_settings_field(
        'aiopms_auto_schema_generation',
        'Auto Schema Generation',
        'aiopms_auto_schema_generation_callback',
        'aiopms-page-management',
        'aiopms_schema_settings_section'
    );
}
add_action('admin_init', 'aiopms_settings_init');

// Section callback
function aiopms_settings_section_callback() {
    echo '<p>Select your preferred AI provider and enter the corresponding API key. Set your brand color for AI-generated featured images and configure the sitemap URL for menu generation.</p>';
}

// AI Provider field callback
function aiopms_ai_provider_callback() {
    $provider = get_option('aiopms_ai_provider', 'openai');
    ?>
    <select name="aiopms_ai_provider">
        <option value="openai" <?php selected($provider, 'openai'); ?>>OpenAI</option>
        <option value="gemini" <?php selected($provider, 'gemini'); ?>>Gemini</option>
        <option value="deepseek" <?php selected($provider, 'deepseek'); ?>>DeepSeek</option>
    </select>
    <?php
}

// OpenAI API Key field callback
function aiopms_openai_api_key_callback() {
    $api_key = get_option('aiopms_openai_api_key');
    echo '<input type="text" name="aiopms_openai_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
}

// Gemini API Key field callback
function aiopms_gemini_api_key_callback() {
    $api_key = get_option('aiopms_gemini_api_key');
    echo '<input type="text" name="aiopms_gemini_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
}

// DeepSeek API Key field callback
function aiopms_deepseek_api_key_callback() {
    $api_key = get_option('aiopms_deepseek_api_key');
    echo '<input type="text" name="aiopms_deepseek_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
}

// Brand Color field callback
function aiopms_brand_color_callback() {
    $brand_color = get_option('aiopms_brand_color', '#b47cfd');
    ?>
    <input type="color" name="aiopms_brand_color" value="<?php echo esc_attr($brand_color); ?>" class="regular-text">
    <p class="description">Select your brand's primary color. This will be used for AI-generated featured images.</p>
    <?php
}

// Sitemap URL field callback
function aiopms_sitemap_url_callback() {
    $sitemap_url = get_option('aiopms_sitemap_url', '');
    ?>
    <input type="url" name="aiopms_sitemap_url" value="<?php echo esc_attr($sitemap_url); ?>" class="regular-text" placeholder="https://yoursite.com/sitemap.xml">
    <p class="description">Enter the URL of your sitemap page. This will be used in the universal bottom menu.</p>
    <?php
}

// Schema settings section callback
function aiopms_schema_settings_section_callback() {
    echo '<p>Configure schema.org markup generation settings for your pages.</p>';
}

// Auto Schema Generation field callback
function aiopms_auto_schema_generation_callback() {
    $auto_generate = get_option('aiopms_auto_schema_generation', true);
    ?>
    <label>
        <input type="checkbox" name="aiopms_auto_schema_generation" value="1" <?php checked($auto_generate, true); ?>>
        Automatically generate schema markup when pages are created or updated
    </label>
    <p class="description">When enabled, schema markup will be automatically generated for all pages when they are saved.</p>
    <?php
}
