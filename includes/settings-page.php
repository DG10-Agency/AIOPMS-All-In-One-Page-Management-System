<?php
/**
 * Settings page functionality for AIOPMS plugin.
 *
 * @package AIOPMS
 * @since 3.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register settings with sanitization callbacks.
 * 
 * @since 3.0
 */
function aiopms_register_settings() {
	register_setting( 'aiopms_settings_group', 'aiopms_ai_provider', 'sanitize_key' );
	register_setting( 'aiopms_settings_group', 'aiopms_openai_api_key', 'sanitize_text_field' );
	register_setting( 'aiopms_settings_group', 'aiopms_gemini_api_key', 'sanitize_text_field' );
	register_setting( 'aiopms_settings_group', 'aiopms_deepseek_api_key', 'sanitize_text_field' );
	register_setting( 'aiopms_settings_group', 'aiopms_brand_color', 'sanitize_hex_color' );
	register_setting( 'aiopms_settings_group', 'aiopms_sitemap_url', 'esc_url_raw' );
	register_setting( 'aiopms_settings_group', 'aiopms_auto_schema_generation', 'absint' );
}
add_action( 'admin_init', 'aiopms_register_settings' );

/**
 * Display settings tab content.
 * 
 * @since 3.0
 */
function aiopms_settings_tab() {
	?>
	<form method="post" action="options.php">
		<?php
		settings_fields( 'aiopms_settings_group' );
		do_settings_sections( 'aiopms-page-management' );
		submit_button();
		?>
	</form>
	<?php
}

/**
 * Initialize settings sections and fields.
 * 
 * @since 3.0
 */
function aiopms_settings_init() {
	add_settings_section(
		'aiopms_settings_section',
		__( 'AI Settings', 'aiopms' ),
		'aiopms_settings_section_callback',
		'aiopms-page-management'
	);

	add_settings_field(
		'aiopms_ai_provider',
		__( 'AI Provider', 'aiopms' ),
		'aiopms_ai_provider_callback',
		'aiopms-page-management',
		'aiopms_settings_section'
	);

	add_settings_field(
		'aiopms_openai_api_key',
		__( 'OpenAI API Key', 'aiopms' ),
		'aiopms_openai_api_key_callback',
		'aiopms-page-management',
		'aiopms_settings_section'
	);

	add_settings_field(
		'aiopms_gemini_api_key',
		__( 'Gemini API Key', 'aiopms' ),
		'aiopms_gemini_api_key_callback',
		'aiopms-page-management',
		'aiopms_settings_section'
	);

	add_settings_field(
		'aiopms_deepseek_api_key',
		__( 'DeepSeek API Key', 'aiopms' ),
		'aiopms_deepseek_api_key_callback',
		'aiopms-page-management',
		'aiopms_settings_section'
	);

	add_settings_field(
		'aiopms_brand_color',
		__( 'Brand Color', 'aiopms' ),
		'aiopms_brand_color_callback',
		'aiopms-page-management',
		'aiopms_settings_section'
	);

	add_settings_field(
		'aiopms_sitemap_url',
		__( 'Sitemap URL', 'aiopms' ),
		'aiopms_sitemap_url_callback',
		'aiopms-page-management',
		'aiopms_settings_section'
	);

	// Schema settings section.
	add_settings_section(
		'aiopms_schema_settings_section',
		__( 'Schema Settings', 'aiopms' ),
		'aiopms_schema_settings_section_callback',
		'aiopms-page-management'
	);

	add_settings_field(
		'aiopms_auto_schema_generation',
		__( 'Auto Schema Generation', 'aiopms' ),
		'aiopms_auto_schema_generation_callback',
		'aiopms-page-management',
		'aiopms_schema_settings_section'
	);
}
add_action( 'admin_init', 'aiopms_settings_init' );

/**
 * AI settings section callback.
 * 
 * @since 3.0
 */
function aiopms_settings_section_callback() {
	echo '<p>' . esc_html__( 'Select your preferred AI provider and enter the corresponding API key. Set your brand color for AI-generated featured images and configure the sitemap URL for menu generation.', 'aiopms' ) . '</p>';
}

/**
 * AI Provider field callback.
 * 
 * @since 3.0
 */
function aiopms_ai_provider_callback() {
	$provider = get_option( 'aiopms_ai_provider', 'openai' );
	?>
	<select name="aiopms_ai_provider" class="aiopms-ai-provider-select">
		<option value="openai" <?php selected( $provider, 'openai' ); ?>><?php esc_html_e( '🤖 OpenAI (GPT-4)', 'aiopms' ); ?></option>
		<option value="gemini" <?php selected( $provider, 'gemini' ); ?>><?php esc_html_e( '🧠 Google Gemini', 'aiopms' ); ?></option>
		<option value="deepseek" <?php selected( $provider, 'deepseek' ); ?>><?php esc_html_e( '⚡ DeepSeek', 'aiopms' ); ?></option>
	</select>
	<p class="description"><?php esc_html_e( 'Choose your preferred AI provider. Each has different strengths and pricing models.', 'aiopms' ); ?></p>
	<?php
}

/**
 * OpenAI API Key field callback.
 * 
 * @since 3.0
 */
function aiopms_openai_api_key_callback() {
	$api_key = get_option( 'aiopms_openai_api_key' );
	echo '<input type="password" name="aiopms_openai_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" placeholder="sk-...">';
	echo '<p class="description">' . esc_html__( 'Enter your OpenAI API key. It will be stored securely.', 'aiopms' ) . '</p>';
}

/**
 * Gemini API Key field callback.
 * 
 * @since 3.0
 */
function aiopms_gemini_api_key_callback() {
	$api_key = get_option( 'aiopms_gemini_api_key' );
	echo '<input type="password" name="aiopms_gemini_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" placeholder="AI...">';
	echo '<p class="description">' . esc_html__( 'Enter your Google Gemini API key. It will be stored securely.', 'aiopms' ) . '</p>';
}

/**
 * DeepSeek API Key field callback.
 * 
 * @since 3.0
 */
function aiopms_deepseek_api_key_callback() {
	$api_key = get_option( 'aiopms_deepseek_api_key' );
	echo '<input type="password" name="aiopms_deepseek_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" placeholder="sk-...">';
	echo '<p class="description">' . esc_html__( 'Enter your DeepSeek API key. It will be stored securely.', 'aiopms' ) . '</p>';
}

/**
 * Brand Color field callback.
 * 
 * @since 3.0
 */
function aiopms_brand_color_callback() {
	$brand_color = get_option( 'aiopms_brand_color', '#b47cfd' );
	?>
	<input type="color" name="aiopms_brand_color" value="<?php echo esc_attr( $brand_color ); ?>" class="regular-text">
	<p class="description"><?php esc_html_e( 'Select your brand\'s primary color. This will be used for AI-generated featured images.', 'aiopms' ); ?></p>
	<?php
}

/**
 * Sitemap URL field callback.
 * 
 * @since 3.0
 */
function aiopms_sitemap_url_callback() {
	$sitemap_url = get_option( 'aiopms_sitemap_url', '' );
	?>
	<input type="url" name="aiopms_sitemap_url" value="<?php echo esc_attr( $sitemap_url ); ?>" class="regular-text" placeholder="https://yoursite.com/sitemap.xml">
	<p class="description"><?php esc_html_e( 'Enter the URL of your sitemap page. This will be used in the universal bottom menu.', 'aiopms' ); ?></p>
	<?php
}

/**
 * Schema settings section callback.
 * 
 * @since 3.0
 */
function aiopms_schema_settings_section_callback() {
	echo '<p>' . esc_html__( 'Configure schema.org markup generation settings for your pages.', 'aiopms' ) . '</p>';
}

/**
 * Auto Schema Generation field callback.
 * 
 * @since 3.0
 */
function aiopms_auto_schema_generation_callback() {
	$auto_generate = get_option( 'aiopms_auto_schema_generation', true );
	?>
	<label>
		<input type="checkbox" name="aiopms_auto_schema_generation" value="1" <?php checked( $auto_generate, true ); ?>>
		<?php esc_html_e( 'Automatically generate schema markup when pages are created or updated', 'aiopms' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'When enabled, schema markup will be automatically generated for all pages when they are saved.', 'aiopms' ); ?></p>
	<?php
}
