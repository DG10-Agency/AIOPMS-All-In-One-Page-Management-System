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
	
	// AI Prompt Customization settings
	register_setting( 'aiopms_settings_group', 'aiopms_custom_prompt', 'aiopms_sanitize_prompt' );
	register_setting( 'aiopms_settings_group', 'aiopms_prompt_version', 'sanitize_text_field' );
	register_setting( 'aiopms_settings_group', 'aiopms_prompt_history', 'aiopms_sanitize_json' );
	register_setting( 'aiopms_settings_group', 'aiopms_prompt_templates', 'aiopms_sanitize_json' );
}
add_action( 'admin_init', 'aiopms_register_settings' );

/**
 * Display settings tab content with horizontal layout.
 * 
 * @since 3.0
 */
function aiopms_settings_tab() {
	?>
	<div class="aiopms-settings-container">
		<form method="post" action="options.php">
			<?php
			settings_fields( 'aiopms_settings_group' );
			?>
			
			<div class="aiopms-settings-grid">
				<!-- AI Settings Column -->
				<div class="aiopms-settings-column">
					<div class="dg10-card">
						<header class="dg10-card-header">
							<h3 class="dg10-card-title">
								<span class="dg10-icon">🤖</span>
								<?php esc_html_e( 'AI Settings', 'aiopms' ); ?>
							</h3>
							<p class="dg10-card-description">
								<?php esc_html_e( 'Configure your AI provider and API keys for content generation.', 'aiopms' ); ?>
							</p>
						</header>
						<div class="dg10-card-content">
							<?php aiopms_render_ai_settings_fields(); ?>
						</div>
					</div>
				</div>
				
				<!-- Schema Settings Column -->
				<div class="aiopms-settings-column">
					<div class="dg10-card">
						<header class="dg10-card-header">
							<h3 class="dg10-card-title">
								<span class="dg10-icon">🏷️</span>
								<?php esc_html_e( 'Schema Settings', 'aiopms' ); ?>
							</h3>
							<p class="dg10-card-description">
								<?php esc_html_e( 'Configure structured data markup generation.', 'aiopms' ); ?>
							</p>
						</header>
						<div class="dg10-card-content">
							<?php aiopms_render_schema_settings_fields(); ?>
						</div>
					</div>
				</div>
				
				<!-- Prompt Settings Column -->
				<div class="aiopms-settings-column">
					<div class="dg10-card">
						<header class="dg10-card-header">
							<h3 class="dg10-card-title">
								<span class="dg10-icon">✏️</span>
								<?php esc_html_e( 'Prompt Settings', 'aiopms' ); ?>
							</h3>
							<p class="dg10-card-description">
								<?php esc_html_e( 'Customize AI prompts for better content generation.', 'aiopms' ); ?>
							</p>
						</header>
						<div class="dg10-card-content">
							<?php aiopms_render_prompt_settings_fields(); ?>
						</div>
					</div>
				</div>
			</div>
			
			<div class="aiopms-settings-actions">
				<?php submit_button( __( 'Save All Settings', 'aiopms' ), 'primary', 'submit', false, array( 'class' => 'dg10-btn dg10-btn-primary dg10-btn-lg' ) ); ?>
			</div>
		</form>
	</div>
	<?php
}

/**
 * Render AI settings fields.
 * 
 * @since 3.0
 */
function aiopms_render_ai_settings_fields() {
	?>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'AI Provider', 'aiopms' ); ?></th>
			<td><?php aiopms_ai_provider_callback(); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'OpenAI API Key', 'aiopms' ); ?></th>
			<td><?php aiopms_openai_api_key_callback(); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Gemini API Key', 'aiopms' ); ?></th>
			<td><?php aiopms_gemini_api_key_callback(); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'DeepSeek API Key', 'aiopms' ); ?></th>
			<td><?php aiopms_deepseek_api_key_callback(); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Brand Color', 'aiopms' ); ?></th>
			<td><?php aiopms_brand_color_callback(); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Sitemap URL', 'aiopms' ); ?></th>
			<td><?php aiopms_sitemap_url_callback(); ?></td>
		</tr>
	</table>
	<?php
}

/**
 * Render Schema settings fields.
 * 
 * @since 3.0
 */
function aiopms_render_schema_settings_fields() {
	?>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Auto Schema Generation', 'aiopms' ); ?></th>
			<td><?php aiopms_auto_schema_generation_callback(); ?></td>
		</tr>
	</table>
	<?php
}

/**
 * Render Prompt settings fields.
 * 
 * @since 3.0
 */
function aiopms_render_prompt_settings_fields() {
	?>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Custom AI Prompt', 'aiopms' ); ?></th>
			<td><?php aiopms_custom_prompt_callback(); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Prompt Management', 'aiopms' ); ?></th>
			<td><?php aiopms_prompt_management_callback(); ?></td>
		</tr>
	</table>
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

	// AI Prompt Customization section
	add_settings_section(
		'aiopms_prompt_settings_section',
		__( 'AI Prompt Settings', 'aiopms' ),
		'aiopms_prompt_settings_section_callback',
		'aiopms-page-management'
	);

	add_settings_field(
		'aiopms_custom_prompt',
		__( 'Custom AI Prompt', 'aiopms' ),
		'aiopms_custom_prompt_callback',
		'aiopms-page-management',
		'aiopms_prompt_settings_section'
	);

	add_settings_field(
		'aiopms_prompt_management',
		__( 'Prompt Management', 'aiopms' ),
		'aiopms_prompt_management_callback',
		'aiopms-page-management',
		'aiopms_prompt_settings_section'
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

/**
 * AI Prompt Settings section callback.
 * 
 * @since 3.0
 */
function aiopms_prompt_settings_section_callback() {
	echo '<p>' . esc_html__( 'Customize the AI prompt used for page generation. Modify the prompt to better suit your business needs and improve AI output quality.', 'aiopms' ) . '</p>';
}

/**
 * Custom AI Prompt field callback.
 * 
 * @since 3.0
 */
function aiopms_custom_prompt_callback() {
	$custom_prompt = get_option( 'aiopms_custom_prompt', '' );
	$default_prompt = aiopms_get_default_ai_prompt();
	$current_prompt = !empty( $custom_prompt ) ? $custom_prompt : $default_prompt;
	
	// Get prompt statistics
	$word_count = str_word_count( $current_prompt );
	$char_count = strlen( $current_prompt );
	$estimated_tokens = ceil( $char_count / 4 ); // Rough estimation
	
	?>
	<div class="aiopms-prompt-editor-container">
		<div class="aiopms-prompt-stats">
			<span class="aiopms-stat-item">
				<strong><?php esc_html_e( 'Characters:', 'aiopms' ); ?></strong> 
				<span id="aiopms-char-count"><?php echo esc_html( $char_count ); ?></span>
			</span>
			<span class="aiopms-stat-item">
				<strong><?php esc_html_e( 'Words:', 'aiopms' ); ?></strong> 
				<span id="aiopms-word-count"><?php echo esc_html( $word_count ); ?></span>
			</span>
			<span class="aiopms-stat-item">
				<strong><?php esc_html_e( 'Est. Tokens:', 'aiopms' ); ?></strong> 
				<span id="aiopms-token-count"><?php echo esc_html( $estimated_tokens ); ?></span>
			</span>
		</div>
		
		<textarea 
			name="aiopms_custom_prompt" 
			id="aiopms-custom-prompt" 
			rows="15" 
			cols="80" 
			class="large-text code"
			placeholder="<?php esc_attr_e( 'Enter your custom AI prompt here...', 'aiopms' ); ?>"
			data-default-prompt="<?php echo esc_attr( $default_prompt ); ?>"
		><?php echo esc_textarea( $current_prompt ); ?></textarea>
		
		<div class="aiopms-prompt-actions">
			<button type="button" id="aiopms-reset-prompt" class="button button-secondary">
				<?php esc_html_e( 'Reset to Default', 'aiopms' ); ?>
			</button>
			<button type="button" id="aiopms-preview-prompt" class="button button-secondary">
				<?php esc_html_e( 'Preview Prompt', 'aiopms' ); ?>
			</button>
			<button type="button" id="aiopms-save-prompt-version" class="button button-primary">
				<?php esc_html_e( 'Save as New Version', 'aiopms' ); ?>
			</button>
		</div>
		
		<div class="aiopms-prompt-variables">
			<h4><?php esc_html_e( 'Available Variables:', 'aiopms' ); ?></h4>
			<div class="aiopms-variable-list">
				<span class="aiopms-variable">{business_type}</span>
				<span class="aiopms-variable">{page_title}</span>
				<span class="aiopms-variable">{seo_keywords}</span>
				<span class="aiopms-variable">{target_audience}</span>
				<span class="aiopms-variable">{business_details}</span>
				<span class="aiopms-variable">{current_date}</span>
			</div>
		</div>
		
		<div class="aiopms-prompt-guidance">
			<h4><?php esc_html_e( 'Prompt Engineering Tips:', 'aiopms' ); ?></h4>
			<ul>
				<li><?php esc_html_e( 'Be specific about your business type and target audience', 'aiopms' ); ?></li>
				<li><?php esc_html_e( 'Include clear instructions for SEO optimization', 'aiopms' ); ?></li>
				<li><?php esc_html_e( 'Specify the tone and style you want for your content', 'aiopms' ); ?></li>
				<li><?php esc_html_e( 'Use the available variables to make prompts dynamic', 'aiopms' ); ?></li>
				<li><?php esc_html_e( 'Test different prompts to find what works best for your business', 'aiopms' ); ?></li>
			</ul>
		</div>
	</div>
	
	<p class="description">
		<strong><?php esc_html_e( 'Warning:', 'aiopms' ); ?></strong> 
		<?php esc_html_e( 'Longer prompts consume more API tokens and cost more. Monitor your usage and optimize for efficiency.', 'aiopms' ); ?>
	</p>
	<?php
}

/**
 * Prompt Management field callback.
 * 
 * @since 3.0
 */
function aiopms_prompt_management_callback() {
	$prompt_history = get_option( 'aiopms_prompt_history', '[]' );
	$prompt_templates = get_option( 'aiopms_prompt_templates', '[]' );
	$current_version = get_option( 'aiopms_prompt_version', '1.0' );
	
	$history = json_decode( $prompt_history, true );
	$templates = json_decode( $prompt_templates, true );
	
	?>
	<div class="aiopms-prompt-management">
		<div class="aiopms-prompt-version-info">
			<p><strong><?php esc_html_e( 'Current Version:', 'aiopms' ); ?></strong> <?php echo esc_html( $current_version ); ?></p>
		</div>
		
		<div class="aiopms-prompt-history">
			<h4><?php esc_html_e( 'Prompt History', 'aiopms' ); ?></h4>
			<?php if ( empty( $history ) ) : ?>
				<p><?php esc_html_e( 'No previous versions saved.', 'aiopms' ); ?></p>
			<?php else : ?>
				<select id="aiopms-prompt-history-select" class="regular-text">
					<option value=""><?php esc_html_e( 'Select a version to restore...', 'aiopms' ); ?></option>
					<?php foreach ( $history as $version => $data ) : ?>
						<option value="<?php echo esc_attr( $version ); ?>" data-prompt="<?php echo esc_attr( $data['prompt'] ); ?>">
							<?php echo esc_html( $version . ' - ' . $data['date'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<button type="button" id="aiopms-restore-prompt" class="button button-secondary">
					<?php esc_html_e( 'Restore Version', 'aiopms' ); ?>
				</button>
			<?php endif; ?>
		</div>
		
		<div class="aiopms-prompt-templates">
			<h4><?php esc_html_e( 'Prompt Templates', 'aiopms' ); ?></h4>
			<select id="aiopms-prompt-templates-select" class="regular-text">
				<option value=""><?php esc_html_e( 'Select a template...', 'aiopms' ); ?></option>
				<option value="ecommerce"><?php esc_html_e( 'E-commerce Store', 'aiopms' ); ?></option>
				<option value="blog"><?php esc_html_e( 'Blog/Content Site', 'aiopms' ); ?></option>
				<option value="service"><?php esc_html_e( 'Service Business', 'aiopms' ); ?></option>
				<option value="portfolio"><?php esc_html_e( 'Portfolio Site', 'aiopms' ); ?></option>
				<option value="local"><?php esc_html_e( 'Local Business', 'aiopms' ); ?></option>
			</select>
			<button type="button" id="aiopms-load-template" class="button button-secondary">
				<?php esc_html_e( 'Load Template', 'aiopms' ); ?>
			</button>
		</div>
		
		<div class="aiopms-prompt-export">
			<h4><?php esc_html_e( 'Export/Import', 'aiopms' ); ?></h4>
			<button type="button" id="aiopms-export-prompt" class="button button-secondary">
				<?php esc_html_e( 'Export Current Prompt', 'aiopms' ); ?>
			</button>
			<input type="file" id="aiopms-import-prompt" accept=".json" style="display: none;">
			<button type="button" id="aiopms-import-prompt-btn" class="button button-secondary">
				<?php esc_html_e( 'Import Prompt', 'aiopms' ); ?>
			</button>
		</div>
	</div>
	<?php
}

/**
 * Get the default AI prompt.
 * 
 * @since 3.0
 * @return string
 */
function aiopms_get_default_ai_prompt() {
	return "You are an expert SEO content strategist and page structure specialist. Your task is to analyze the provided business information and suggest a comprehensive set of pages that will establish topical authority and improve search engine rankings.

BUSINESS CONTEXT:
- Business Type: {business_type}
- Business Details: {business_details}
- Target Audience: {target_audience}
- SEO Keywords: {seo_keywords}
- Current Date: {current_date}

REQUIREMENTS:
1. Create 8-12 high-value pages that cover the main topics and subtopics for this business
2. Each page should target specific long-tail keywords related to the main business
3. Pages should be organized in a logical hierarchy that builds topical authority
4. Include a mix of informational, commercial, and transactional pages
5. Consider user intent and search journey mapping
6. Ensure each page has a clear purpose and unique value proposition

OUTPUT FORMAT:
Return a JSON array with the following structure for each page:
{
  \"title\": \"Page Title (SEO-optimized)\",
  \"slug\": \"page-slug\",
  \"description\": \"Brief description of the page content and purpose\",
  \"keywords\": [\"primary keyword\", \"secondary keyword\", \"long-tail keyword\"],
  \"intent\": \"informational|commercial|transactional|navigational\",
  \"priority\": \"high|medium|low\",
  \"parent\": \"parent-page-slug or null for top-level\",
  \"content_suggestions\": [
    \"Main heading suggestion\",
    \"Key sections to include\",
    \"Call-to-action recommendations\"
  ]
}

FOCUS AREAS:
- Primary business services/products
- Industry-specific topics and terminology
- Common customer questions and pain points
- Local SEO opportunities (if applicable)
- Content that builds trust and authority
- Pages that support the conversion funnel

Ensure the page structure tells a complete story about the business and provides comprehensive coverage of the topic area.";
}

/**
 * Sanitize prompt text.
 * 
 * @since 3.0
 * @param string $input
 * @return string
 */
function aiopms_sanitize_prompt( $input ) {
	return sanitize_textarea_field( $input );
}

/**
 * Sanitize JSON data.
 * 
 * @since 3.0
 * @param string $input
 * @return string
 */
function aiopms_sanitize_json( $input ) {
	$decoded = json_decode( $input, true );
	if ( json_last_error() === JSON_ERROR_NONE ) {
		return wp_json_encode( $decoded );
	}
	return '';
}
