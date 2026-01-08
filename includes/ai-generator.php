<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register AJAX handlers
add_action('wp_ajax_aiopms_ai_generate_suggestions', 'aiopms_handle_ai_generate_suggestions_ajax');
add_action('wp_ajax_aiopms_ai_create_content', 'aiopms_handle_ai_create_content_ajax');

/**
 * AI generation tab content - Modernized with AJAX support
 * 
 * @since 3.0
 */
function aiopms_ai_generation_tab() {
    ?>
    <div class="aiopms-ai-tab dg10-form-container">
        <div class="aiopms-tab-header">
            <h3>🤖 <?php esc_html_e('AI Page & Ecosystem Generator', 'aiopms'); ?></h3>
            <p><?php esc_html_e('Our advanced AI analyzes your business mission to suggest a complete website architecture, including pages and custom post types.', 'aiopms'); ?></p>
        </div>

        <form id="aiopms-ai-request-form" class="dg10-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('aiopms_ai_ajax', 'nonce'); ?>
            
            <div class="dg10-form-section">
                <div class="dg10-form-group">
                    <label for="aiopms_business_type" class="dg10-form-label"><?php esc_html_e('Business Type', 'aiopms'); ?></label>
                    <input type="text" name="aiopms_business_type" id="aiopms_business_type" class="dg10-form-input" 
                           placeholder="<?php esc_attr_e('e.g., Digital Agency, Dental Clinic, Law Firm', 'aiopms'); ?>" required>
                    <div class="aiopms-field-validation"></div>
                </div>

                <div class="dg10-form-group">
                    <label for="aiopms_business_details" class="dg10-form-label"><?php esc_html_e('Mission & Details', 'aiopms'); ?></label>
                    <textarea name="aiopms_business_details" id="aiopms_business_details" rows="4" class="dg10-form-textarea" 
                              placeholder="<?php esc_attr_e('Briefly describe what your business does and what makes you unique...', 'aiopms'); ?>" required></textarea>
                </div>

                <div class="dg10-form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="dg10-form-group">
                        <label for="aiopms_seo_keywords" class="dg10-form-label"><?php esc_html_e('Primary SEO Keywords', 'aiopms'); ?></label>
                        <input type="text" name="aiopms_seo_keywords" id="aiopms_seo_keywords" class="dg10-form-input" 
                               placeholder="<?php esc_attr_e('e.g., low-cost dentistry, web design London', 'aiopms'); ?>">
                        <p class="dg10-form-help"><?php esc_html_e('Comma-separated list.', 'aiopms'); ?></p>
                    </div>

                    <div class="dg10-form-group">
                        <label for="aiopms_target_audience" class="dg10-form-label"><?php esc_html_e('Target Audience', 'aiopms'); ?></label>
                        <input type="text" name="aiopms_target_audience" id="aiopms_target_audience" class="dg10-form-input" 
                               placeholder="<?php esc_attr_e('e.g., local businesses in Miami, pet owners', 'aiopms'); ?>">
                    </div>
                </div>

                <div class="dg10-form-group">
                    <label for="aiopms_keywords_csv" class="dg10-form-label"><?php esc_html_e('Bulk Keywords Upload (CSV)', 'aiopms'); ?></label>
                    <input type="file" name="aiopms_keywords_csv" id="aiopms_keywords_csv" class="dg10-form-input" accept=".csv">
                    <p class="dg10-form-help"><?php esc_html_e('Upload a CSV file for deep content analysis.', 'aiopms'); ?></p>
                </div>

                <div class="dg10-form-group">
                    <label class="dg10-checkbox-label aiopms-advanced-toggle">
                        <input type="checkbox" name="aiopms_advanced_mode" id="aiopms_advanced_mode" value="1" checked>
                        <span>
                            <strong><?php esc_html_e('Advanced Mode', 'aiopms'); ?></strong> - 
                            <?php esc_html_e('Generate custom post types (CPTs) and dynamic fields specialized for your niche.', 'aiopms'); ?>
                        </span>
                    </label>
                </div>
            </div>

            <div class="dg10-form-actions">
                <button type="submit" class="dg10-btn dg10-btn-primary">
                    <span class="nav-icon">✨</span>
                    <?php esc_html_e('Generate Page Suggestions', 'aiopms'); ?>
                </button>
            </div>
        </form>

        <div id="aiopms-ai-results" class="aiopms-results-container">
            <!-- Results will be loaded here via AJAX -->
        </div>
    </div>
    <?php
}

/**
 * Handle AI suggestion generation AJAX request
 * 
 * @since 3.0
 */
function aiopms_handle_ai_generate_suggestions_ajax() {
    check_ajax_referer('aiopms_ai_ajax', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'aiopms')));
    }

    $business_type = isset($_POST['aiopms_business_type']) ? sanitize_text_field(wp_unslash($_POST['aiopms_business_type'])) : '';
    $business_details = isset($_POST['aiopms_business_details']) ? sanitize_textarea_field(wp_unslash($_POST['aiopms_business_details'])) : '';
    $seo_keywords = isset($_POST['aiopms_seo_keywords']) ? sanitize_text_field(wp_unslash($_POST['aiopms_seo_keywords'])) : '';
    $target_audience = isset($_POST['aiopms_target_audience']) ? sanitize_text_field(wp_unslash($_POST['aiopms_target_audience'])) : '';
    $advanced_mode = isset($_POST['aiopms_advanced_mode']) && $_POST['aiopms_advanced_mode'] == '1';
    
    // Combine keywords with CSV if uploaded
    $csv_keywords = '';
    if (isset($_FILES['aiopms_keywords_csv']) && !empty($_FILES['aiopms_keywords_csv']['tmp_name'])) {
        $csv_keywords = abpcwa_process_keywords_csv($_FILES['aiopms_keywords_csv']);
    }
    
    $all_keywords = trim($seo_keywords);
    if (!empty($csv_keywords)) {
        $all_keywords = !empty($all_keywords) ? $all_keywords . ', ' . $csv_keywords : $csv_keywords;
    }

    ob_start();
    if ($advanced_mode) {
        aiopms_generate_advanced_content_with_ai($business_type, $business_details, $all_keywords, $target_audience);
    } else {
        abpcwa_generate_pages_with_ai($business_type, $business_details, $all_keywords, $target_audience);
    }
    $html = ob_get_clean();

    if (empty($html) || strpos($html, 'notice-error') !== false) {
        wp_send_json_error(array('html' => $html, 'message' => __('AI generation failed.', 'aiopms')));
    }

    wp_send_json_success(array('html' => $html));
}

/**
 * Handle content creation AJAX request
 * 
 * @since 3.0
 */
function aiopms_handle_ai_create_content_ajax() {
    check_ajax_referer('aiopms_create_suggested_pages', 'nonce'); // For compatibility with existing nonce names
    if (!isset($_POST['_wpnonce'])) {
        check_ajax_referer('aiopms_create_advanced_content', 'nonce');
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'aiopms')));
    }

    $generate_images = isset($_POST['aiopms_generate_images']) && $_POST['aiopms_generate_images'] == '1';
    $message = '';

    if (isset($_POST['aiopms_selected_pages'])) {
        $selected_pages = array_map('wp_unslash', $_POST['aiopms_selected_pages']);
        
        // Handle both simple and advanced page creation
        if (isset($_POST['action_type']) && $_POST['action_type'] === 'advanced') {
            $selected_cpts = isset($_POST['aiopms_selected_cpts']) ? array_map('wp_unslash', $_POST['aiopms_selected_cpts']) : array();
            
            // Re-map JSON strings back to arrays
            $pages_data = array_map(function($p) { return json_decode($p, true); }, $selected_pages);
            $cpts_data = array_map(function($c) { return json_decode($c, true); }, $selected_cpts);
            
            aiopms_create_advanced_content($pages_data, $cpts_data, $generate_images);
            $message = sprintf(__('Successfully created %d pages and %d custom post types.', 'aiopms'), count($pages_data), count($cpts_data));
        } else {
            abpcwa_create_suggested_pages($selected_pages, $generate_images);
            $message = sprintf(__('Successfully created %d pages.', 'aiopms'), count($selected_pages));
        }
    } else {
        wp_send_json_error(array('message' => __('No content selected for creation.', 'aiopms')));
    }

    wp_send_json_success(array('message' => $message));
}

// Generate pages with AI
function abpcwa_generate_pages_with_ai($business_type, $business_details, $seo_keywords = '', $target_audience = '') {
    try {
        // Input validation
        if (empty($business_type) || empty($business_details)) {
            echo '<div class="notice notice-error"><p>' . __('Business type and details are required for AI generation.', 'aiopms') . '</p></div>';
            return;
        }

        // Sanitize inputs
        $business_type = sanitize_text_field($business_type);
        $business_details = sanitize_textarea_field($business_details);
        $seo_keywords = sanitize_text_field($seo_keywords);
        $target_audience = sanitize_text_field($target_audience);

        // Validate input lengths
        if (strlen($business_type) > 100) {
            echo '<div class="notice notice-error"><p>' . __('Business type must be 100 characters or less.', 'aiopms') . '</p></div>';
            return;
        }

        if (strlen($business_details) > 1000) {
            echo '<div class="notice notice-error"><p>' . __('Business details must be 1000 characters or less.', 'aiopms') . '</p></div>';
            return;
        }

        $provider = get_option('aiopms_ai_provider', 'openai');
        $api_key = get_option('aiopms_' . $provider . '_api_key');

        if (empty($api_key)) {
            echo '<div class="notice notice-error"><p>' . sprintf(__('Please enter your %s API key in the Settings tab.', 'aiopms'), esc_html(ucfirst($provider))) . '</p></div>';
            return;
        }

        // Validate API key format
        if (!aiopms_validate_api_key($api_key, $provider)) {
            echo '<div class="notice notice-error"><p>' . sprintf(__('Invalid %s API key format. Please check your API key.', 'aiopms'), esc_html(ucfirst($provider))) . '</p></div>';
            return;
        }

        // Rate limiting check
        if (!aiopms_check_ai_rate_limit($provider)) {
            echo '<div class="notice notice-error"><p>' . __('Too many AI requests. Please wait a moment before trying again.', 'aiopms') . '</p></div>';
            return;
        }

        // Call the appropriate function based on the selected provider
        $suggested_pages = [];
        $error_message = '';
        
        switch ($provider) {
            case 'openai':
                $suggested_pages = abpcwa_get_openai_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            case 'gemini':
                $suggested_pages = abpcwa_get_gemini_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            case 'deepseek':
                $suggested_pages = abpcwa_get_deepseek_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            default:
                echo '<div class="notice notice-error"><p>' . __('Invalid AI provider selected.', 'aiopms') . '</p></div>';
                return;
        }

        if (empty($suggested_pages)) {
            echo '<div class="notice notice-warning"><p>' . __('Could not generate page suggestions. Please check your API key and try again.', 'aiopms') . '</p></div>';
            return;
        }

        // Log successful generation
        aiopms_log_ai_generation('page_suggestions', $provider, true, count($suggested_pages));

    } catch (Exception $e) {
        // Log error
        aiopms_log_ai_generation('page_suggestions', $provider ?? 'unknown', false, 0, $e->getMessage());
        echo '<div class="notice notice-error"><p>' . __('An error occurred during AI generation. Please try again.', 'aiopms') . '</p></div>';
        error_log('AIOPMS AI Generation Error: ' . $e->getMessage());
        return;
    }

    // Filter out Privacy Policy page since WordPress creates it automatically
    $suggested_pages = array_filter($suggested_pages, function($page_line) {
        return stripos($page_line, 'Privacy Policy') === false;
    });

    echo '<div class="aiopms-suggestions-results">';
    echo '<h3>✨ ' . esc_html__('Suggested Pages:', 'aiopms') . '</h3>';
    echo '<form id="aiopms-ai-creation-form" method="post" action="">';
    wp_nonce_field('aiopms_create_suggested_pages', 'nonce');
    echo '<input type="hidden" name="action_type" value="simple">';
    
    echo '<table class="dg10-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th width="40px"><input type="checkbox" id="select-all-pages" checked></th>';
    echo '<th>' . esc_html__('Page Title', 'aiopms') . '</th>';
    echo '<th>' . esc_html__('Meta Description', 'aiopms') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($suggested_pages as $page_line) {
        $excerpt = '';
        $page_title = $page_line;
        
        if (strpos($page_line, ':+') !== false) {
            list($page_title, $excerpt) = explode(':+', $page_line, 2);
            $excerpt = trim($excerpt);
        }
        
        $depth = 0;
        $display_title = $page_title;
        while (substr($display_title, 0, 1) === '-') {
            $display_title = substr($display_title, 1);
            $depth++;
        }
        $display_title = trim($display_title);
        
        echo '<tr>';
        echo '<td><input type="checkbox" name="aiopms_selected_pages[]" value="' . esc_attr($page_line) . '" class="aiopms-page-checkbox" checked></td>';
        echo '<td>';
        if ($depth > 0) {
            echo str_repeat('<span class="dg10-indent"></span>', $depth);
            echo '<span class="dg10-child-indicator">➥</span> ';
        }
        echo esc_html($display_title);
        echo '</td>';
        echo '<td>' . esc_html($excerpt) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    $provider = get_option('aiopms_ai_provider', 'openai');
    $is_deepseek = $provider === 'deepseek';
    
    echo '<div class="aiopms-options dg10-card" style="margin-top: 24px; padding: 20px;">';
    echo '<label class="dg10-checkbox-label">';
    echo '<input type="checkbox" name="aiopms_generate_images" id="aiopms_generate_images" value="1" ' . checked(true, !$is_deepseek, false) . '>';
    echo '<span>' . esc_html__('Generate premium featured images with AI', 'aiopms') . '</span>';
    echo '</label>';
    
    if ($is_deepseek) {
        echo '<p class="dg10-form-help dg10-text-danger">' . esc_html__('Note: Image generation is currently not supported with DeepSeek.', 'aiopms') . '</p>';
    }
    echo '</div>';
    
    echo '<div class="dg10-form-actions" style="margin-top: 24px;">';
    echo '<button type="submit" class="dg10-btn dg10-btn-primary">';
    echo '<span class="nav-icon">🚀</span>';
    echo esc_html__('Create Selected Pages', 'aiopms');
    echo '</button>';
    echo '</div>';

    echo '<div class="aiopms-creation-status" style="display: none; margin-top: 20px;"></div>';
    echo '</form>';
    echo '</div>';
}

// Get page suggestions from OpenAI API
function abpcwa_get_openai_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    try {
        // Input validation
        if (empty($api_key) || empty($business_type) || empty($business_details)) {
            throw new Exception(__('Missing required parameters for OpenAI API call.', 'aiopms'));
        }

        $url = 'https://api.openai.com/v1/chat/completions';
        
        // Build enhanced SEO prompt
        $seo_context = '';
        if (!empty($seo_keywords)) {
            $seo_context .= "SEO Keywords: {$seo_keywords}. ";
        }
        if (!empty($target_audience)) {
            $seo_context .= "Target Audience: {$target_audience}. ";
        }
        
        $prompt = "## ROLE & CONTEXT
You are an expert SEO strategist and information architect specializing in website structure optimization for maximum search visibility and user experience.

## BUSINESS CONTEXT
- **Industry**: {$business_type}
- **Business Details**: {$business_details}
- **Target Audience**: {$target_audience}
- **Primary Keywords**: {$seo_keywords}

## TASK OBJECTIVE
Generate a comprehensive list of essential website pages that will establish topical authority and semantic relevance for this business. For each page, provide:
1. Page Title (use hyphens '-' for nesting child pages to indicate hierarchy)
2. SEO-optimized Meta Description (separated by ':+' from the title)

## STRATEGIC REQUIREMENTS

### 1. TOPICAL AUTHORITY ARCHITECTURE
- Create content clusters around core topics
- Establish pillar pages with supporting child pages
- Ensure comprehensive coverage of the business domain
- Include both commercial and informational intent pages

### 2. SEMANTIC SEO IMPLEMENTATION
- Use natural language variations of target keywords
- Incorporate related concepts and entities
- Build semantic relationships between pages
- Avoid keyword stuffing - focus on contextual relevance

### 3. EEAT OPTIMIZATION
- Demonstrate expertise through comprehensive content planning
- Show authoritativeness by covering all essential business aspects
- Build trust with transparent, valuable content
- Include experience-based content where relevant

### 4. USER INTENT MATCHING
- Commercial intent pages (services, products, pricing)
- Informational intent pages (guides, resources, FAQs)
- Navigational intent pages (contact, about, locations)
- Transactional intent pages (checkout, booking, quotes)

### 5. TECHNICAL SEO CONSIDERATIONS
- Logical URL structure with proper hierarchy
- Internal linking opportunities between related pages
- Mobile-first content approach
- Fast-loading, user-friendly page types

## SEO OPTIMIZATION REQUIREMENTS
- **Page Titles**: Must include primary keywords naturally, be compelling, and accurately describe the page content
- **Meta Descriptions**: 155-160 characters, include primary keywords naturally, be compelling and encourage click-throughs
- **Keyword Placement**: Use keywords in titles and descriptions without stuffing - make it sound natural
- **User Intent**: Match the search intent for each keyword (informational, commercial, navigational)

## OUTPUT FORMAT
Return only the list in this exact format:
[SEO-optimized Page Title with primary keywords]:+[Meta Description - 155-160 characters, compelling, includes primary keyword naturally]

Use hyphens for nesting (e.g., '-Services:-+[description]' for child pages)

## CONTEXT-AWARE PAGE SELECTION GUIDELINES:
- **Analyze the business context** and only suggest pages that make sense for this specific business type
- **Use common sense**: A portfolio website doesn't need a Pricing page, an e-commerce site does
- **Consider user intent**: Focus on pages that match what users would actually search for
- **Semantic relationships**: Create pages that build topical authority through related content clusters
- **Business model awareness**: Service businesses need different pages than product businesses or informational sites

## FLEXIBLE STRUCTURE PRINCIPLES:
- **Main Pages**: Use logical hierarchy based on business needs (not fixed templates)
- **Child Pages**: Only nest when there's a clear semantic relationship
- **Avoid unnecessary pages**: Don't include pages that don't serve a clear purpose for this business
- **User-centric**: Focus on what the target audience actually needs to find

## SMART PAGE SELECTION EXAMPLES:
- **Portfolio Website**: Home, About, Portfolio, Services, Contact, Testimonials, Blog
- **E-commerce Store**: Home, Shop, Product Categories, About, Contact, FAQ, Shipping, Returns
- **Service Business**: Home, Services, About, Contact, Testimonials, Blog, FAQ
- **Informational Site**: Home, Resources, Blog, About, Contact, Glossary, Tutorials

## OUTPUT FORMAT:
Return only the list in this exact format:
[SEO-optimized Page Title with primary keywords]:+[Meta Description - 155-160 characters, compelling, includes primary keyword naturally]

Use hyphens for nesting only when there's a clear hierarchical relationship

Focus on creating a website architecture that makes sense for THIS specific business, not a generic template. Use semantic SEO principles and common sense to determine which pages are actually needed.

Focus on creating a complete website architecture that will rank well and convert visitors.";

        $body = json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.5,
            'max_tokens' => 1000,
        ]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Failed to encode request data for OpenAI API.', 'aiopms'));
        }

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => $body,
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            throw new Exception(sprintf(__('OpenAI API request failed: %s', 'aiopms'), $response->get_error_message()));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            throw new Exception(sprintf(__('OpenAI API returned error %d: %s', 'aiopms'), $response_code, $error_message));
        }

        $response_body = wp_remote_retrieve_body($response);
        if (empty($response_body)) {
            throw new Exception(__('Empty response received from OpenAI API.', 'aiopms'));
        }

        $decoded_response = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Invalid JSON response from OpenAI API.', 'aiopms'));
        }

        if (isset($decoded_response['error'])) {
            $error_message = isset($decoded_response['error']['message']) ? $decoded_response['error']['message'] : __('Unknown OpenAI API error.', 'aiopms');
            throw new Exception(sprintf(__('OpenAI API error: %s', 'aiopms'), $error_message));
        }

        if (!isset($decoded_response['choices'][0]['message']['content'])) {
            throw new Exception(__('Unexpected response format from OpenAI API.', 'aiopms'));
        }

        $pages_str = $decoded_response['choices'][0]['message']['content'];
        if (empty($pages_str)) {
            throw new Exception(__('Empty content received from OpenAI API.', 'aiopms'));
        }

        $pages = array_map('trim', explode("\n", $pages_str));
        $pages = array_filter($pages, function($page) {
            return !empty($page) && strpos($page, ':+') !== false;
        });

        if (empty($pages)) {
            throw new Exception(__('No valid page suggestions received from OpenAI API.', 'aiopms'));
        }

        return $pages;

    } catch (Exception $e) {
        error_log('AIOPMS OpenAI API Error: ' . $e->getMessage());
        return [];
    }
}

// Get page suggestions from Gemini API
function abpcwa_get_gemini_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    try {
        // Input validation
        if (empty($api_key) || empty($business_type) || empty($business_details)) {
            throw new Exception(__('Missing required parameters for Gemini API call.', 'aiopms'));
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;
        
        // Build enhanced SEO prompt
        $seo_context = '';
        if (!empty($seo_keywords)) {
            $seo_context .= "SEO Keywords: {$seo_keywords}. ";
        }
        if (!empty($target_audience)) {
            $seo_context .= "Target Audience: {$target_audience}. ";
        }
        
        $prompt = "## ROLE & CONTEXT
You are an expert SEO strategist and information architect specializing in website structure optimization for maximum search visibility and user experience.

## BUSINESS CONTEXT
- **Industry**: {$business_type}
- **Business Details**: {$business_details}
- **Target Audience**: {$target_audience}
- **Primary Keywords**: {$seo_keywords}

## TASK OBJECTIVE
Generate a comprehensive list of essential website pages that will establish topical authority and semantic relevance for this business. For each page, provide:
1. Page Title (use hyphens '-' for nesting child pages to indicate hierarchy)
2. SEO-optimized Meta Description (separated by ':+' from the title)

## STRATEGIC REQUIREMENTS

### 1. TOPICAL AUTHORITY ARCHITECTURE
- Create content clusters around core topics
- Establish pillar pages with supporting child pages
- Ensure comprehensive coverage of the business domain
- Include both commercial and informational intent pages

### 2. SEMANTIC SEO IMPLEMENTATION
- Use natural language variations of target keywords
- Incorporate related concepts and entities
- Build semantic relationships between pages
- Avoid keyword stuffing - focus on contextual relevance

### 3. EEAT OPTIMIZATION
- Demonstrate expertise through comprehensive content planning
- Show authoritativeness by covering all essential business aspects
- Build trust with transparent, valuable content
- Include experience-based content where relevant

### 4. USER INTENT MATCHING
- Commercial intent pages (services, products, pricing)
- Informational intent pages (guides, resources, FAQs)
- Navigational intent pages (contact, about, locations)
- Transactional intent pages (checkout, booking, quotes)

### 5. TECHNICAL SEO CONSIDERATIONS
- Logical URL structure with proper hierarchy
- Internal linking opportunities between related pages
- Mobile-first content approach
- Fast-loading, user-friendly page types

## SEO OPTIMIZATION REQUIREMENTS
- **Page Titles**: Must include primary keywords naturally, be compelling, and accurately describe the page content
- **Meta Descriptions**: 155-160 characters, include primary keywords naturally, be compelling and encourage click-throughs
- **Keyword Placement**: Use keywords in titles and descriptions without stuffing - make it sound natural
- **User Intent**: Match the search intent for each keyword (informational, commercial, navigational)

## OUTPUT FORMAT
Return only the list in this exact format:
[SEO-optimized Page Title with primary keywords]:+[Meta Description - 155-160 characters, compelling, includes primary keyword naturally]

Use hyphens for nesting (e.g., '-Services:-+[description]' for child pages)

## CONTEXT-AWARE PAGE SELECTION GUIDELINES:
- **Analyze the business context** and only suggest pages that make sense for this specific business type
- **Use common sense**: A portfolio website doesn't need a Pricing page, an e-commerce site does
- **Consider user intent**: Focus on pages that match what users would actually search for
- **Semantic relationships**: Create pages that build topical authority through related content clusters
- **Business model awareness**: Service businesses need different pages than product businesses or informational sites

## FLEXIBLE STRUCTURE PRINCIPLES:
- **Main Pages**: Use logical hierarchy based on business needs (not fixed templates)
- **Child Pages**: Only nest when there's a clear semantic relationship
- **Avoid unnecessary pages**: Don't include pages that don't serve a clear purpose for this business
- **User-centric**: Focus on what the target audience actually needs to find

## SMART PAGE SELECTION EXAMPLES:
- **Portfolio Website**: Home, About, Portfolio, Services, Contact, Testimonials, Blog
- **E-commerce Store**: Home, Shop, Product Categories, About, Contact, FAQ, Shipping, Returns
- **Service Business**: Home, Services, About, Contact, Testimonials, Blog, FAQ
- **Informational Site**: Home, Resources, Blog, About, Contact, Glossary, Tutorials

## OUTPUT FORMAT:
Return only the list in this exact format:
[SEO-optimized Page Title with primary keywords]:+[Meta Description - 155-160 characters, compelling, includes primary keyword naturally]

Use hyphens for nesting only when there's a clear hierarchical relationship

Focus on creating a website architecture that makes sense for THIS specific business, not a generic template. Use semantic SEO principles and common sense to determine which pages are actually needed.

Focus on creating a complete website architecture that will rank well and convert visitors.";

        $body = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
        ]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Failed to encode request data for Gemini API.', 'aiopms'));
        }

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $body,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            throw new Exception(sprintf(__('Gemini API request failed: %s', 'aiopms'), $response->get_error_message()));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            throw new Exception(sprintf(__('Gemini API returned error %d: %s', 'aiopms'), $response_code, $error_message));
        }

        $response_body = wp_remote_retrieve_body($response);
        if (empty($response_body)) {
            throw new Exception(__('Empty response received from Gemini API.', 'aiopms'));
        }

        $decoded_response = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Invalid JSON response from Gemini API.', 'aiopms'));
        }

        if (isset($decoded_response['error'])) {
            $error_message = isset($decoded_response['error']['message']) ? $decoded_response['error']['message'] : __('Unknown Gemini API error.', 'aiopms');
            throw new Exception(sprintf(__('Gemini API error: %s', 'aiopms'), $error_message));
        }

        if (!isset($decoded_response['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception(__('Unexpected response format from Gemini API.', 'aiopms'));
        }

        $pages_str = $decoded_response['candidates'][0]['content']['parts'][0]['text'];
        if (empty($pages_str)) {
            throw new Exception(__('Empty content received from Gemini API.', 'aiopms'));
        }

        $pages = array_map('trim', explode("\n", $pages_str));
        $pages = array_filter($pages, function($page) {
            return !empty($page) && strpos($page, ':+') !== false;
        });

        if (empty($pages)) {
            throw new Exception(__('No valid page suggestions received from Gemini API.', 'aiopms'));
        }

        return $pages;

    } catch (Exception $e) {
        error_log('AIOPMS Gemini API Error: ' . $e->getMessage());
        return [];
    }
}

// Get page suggestions from DeepSeek API
function abpcwa_get_deepseek_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    try {
        // Input validation
        if (empty($api_key) || empty($business_type) || empty($business_details)) {
            throw new Exception(__('Missing required parameters for DeepSeek API call.', 'aiopms'));
        }

        $url = 'https://api.deepseek.com/v1/chat/completions';
        
        // Build enhanced SEO prompt
        $seo_context = '';
        if (!empty($seo_keywords)) {
            $seo_context .= "SEO Keywords: {$seo_keywords}. ";
        }
        if (!empty($target_audience)) {
            $seo_context .= "Target Audience: {$target_audience}. ";
        }
        
        $prompt = "## ROLE & CONTEXT
You are an expert SEO strategist and information architect specializing in website structure optimization for maximum search visibility and user experience.

## BUSINESS CONTEXT
- **Industry**: {$business_type}
- **Business Details**: {$business_details}
- **Target Audience**: {$target_audience}
- **Primary Keywords**: {$seo_keywords}

## TASK OBJECTIVE
Generate a comprehensive list of essential website pages that will establish topical authority and semantic relevance for this business. For each page, provide:
1. Page Title (use hyphens '-' for nesting child pages to indicate hierarchy)
2. SEO-optimized Meta Description (separated by ':+' from the title)

## STRATEGIC REQUIREMENTS

### 1. TOPICAL AUTHORITY ARCHITECTURE
- Create content clusters around core topics
- Establish pillar pages with supporting child pages
- Ensure comprehensive coverage of the business domain
- Include both commercial and informational intent pages

### 2. SEMANTIC SEO IMPLEMENTATION
- Use natural language variations of target keywords
- Incorporate related concepts and entities
- Build semantic relationships between pages
- Avoid keyword stuffing - focus on contextual relevance

### 3. EEAT OPTIMIZATION
- Demonstrate expertise through comprehensive content planning
- Show authoritativeness by covering all essential business aspects
- Build trust with transparent, valuable content
- Include experience-based content where relevant

### 4. USER INTENT MATCHING
- Commercial intent pages (services, products, pricing)
- Informational intent pages (guides, resources, FAQs)
- Navigational intent pages (contact, about, locations)
- Transactional intent pages (checkout, booking, quotes)

### 5. TECHNICAL SEO CONSIDERATIONS
- Logical URL structure with proper hierarchy
- Internal linking opportunities between related pages
- Mobile-first content approach
- Fast-loading, user-friendly page types

## SEO OPTIMIZATION REQUIREMENTS
- **Page Titles**: Must include primary keywords naturally, be compelling, and accurately describe the page content
- **Meta Descriptions**: 155-160 characters, include primary keywords naturally, be compelling and encourage click-throughs
- **Keyword Placement**: Use keywords in titles and descriptions without stuffing - make it sound natural
- **User Intent**: Match the search intent for each keyword (informational, commercial, navigational)

## OUTPUT FORMAT
Return only the list in this exact format:
[SEO-optimized Page Title with primary keywords]:+[Meta Description - 155-160 characters, compelling, includes primary keyword naturally]

Use hyphens for nesting (e.g., '-Services:-+[description]' for child pages)

## CONTEXT-AWARE PAGE SELECTION GUIDELINES:
- **Analyze the business context** and only suggest pages that make sense for this specific business type
- **Use common sense**: A portfolio website doesn't need a Pricing page, an e-commerce site does
- **Consider user intent**: Focus on pages that match what users would actually search for
- **Semantic relationships**: Create pages that build topical authority through related content clusters
- **Business model awareness**: Service businesses need different pages than product businesses or informational sites

## FLEXIBLE STRUCTURE PRINCIPLES:
- **Main Pages**: Use logical hierarchy based on business needs (not fixed templates)
- **Child Pages**: Only nest when there's a clear semantic relationship
- **Avoid unnecessary pages**: Don't include pages that don't serve a clear purpose for this business
- **User-centric**: Focus on what the target audience actually needs to find

## SMART PAGE SELECTION EXAMPLES:
- **Portfolio Website**: Home, About, Portfolio, Services, Contact, Testimonials, Blog
- **E-commerce Store**: Home, Shop, Product Categories, About, Contact, FAQ, Shipping, Returns
- **Service Business**: Home, Services, About, Contact, Testimonials, Blog, FAQ
- **Informational Site**: Home, Resources, Blog, About, Contact, Glossary, Tutorials

## OUTPUT FORMAT:
Return only the list in this exact format:
[SEO-optimized Page Title with primary keywords]:+[Meta Description - 155-160 characters, compelling, includes primary keyword naturally]

Use hyphens for nesting only when there's a clear hierarchical relationship

Focus on creating a website architecture that makes sense for THIS specific business, not a generic template. Use semantic SEO principles and common sense to determine which pages are actually needed.

Focus on creating a complete website architecture that will rank well and convert visitors.";

        $body = json_encode([
            'model' => 'deepseek-chat',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.5,
            'max_tokens' => 1000,
        ]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Failed to encode request data for DeepSeek API.', 'aiopms'));
        }

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => $body,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            throw new Exception(sprintf(__('DeepSeek API request failed: %s', 'aiopms'), $response->get_error_message()));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            throw new Exception(sprintf(__('DeepSeek API returned error %d: %s', 'aiopms'), $response_code, $error_message));
        }

        $response_body = wp_remote_retrieve_body($response);
        if (empty($response_body)) {
            throw new Exception(__('Empty response received from DeepSeek API.', 'aiopms'));
        }

        $decoded_response = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Invalid JSON response from DeepSeek API.', 'aiopms'));
        }

        if (isset($decoded_response['error'])) {
            $error_message = isset($decoded_response['error']['message']) ? $decoded_response['error']['message'] : __('Unknown DeepSeek API error.', 'aiopms');
            throw new Exception(sprintf(__('DeepSeek API error: %s', 'aiopms'), $error_message));
        }

        if (!isset($decoded_response['choices'][0]['message']['content'])) {
            throw new Exception(__('Unexpected response format from DeepSeek API.', 'aiopms'));
        }

        $pages_str = $decoded_response['choices'][0]['message']['content'];
        if (empty($pages_str)) {
            throw new Exception(__('Empty content received from DeepSeek API.', 'aiopms'));
        }

        $pages = array_map('trim', explode("\n", $pages_str));
        $pages = array_filter($pages, function($page) {
            return !empty($page) && strpos($page, ':+') !== false;
        });

        if (empty($pages)) {
            throw new Exception(__('No valid page suggestions received from DeepSeek API.', 'aiopms'));
        }

        return $pages;

    } catch (Exception $e) {
        error_log('AIOPMS DeepSeek API Error: ' . $e->getMessage());
        return [];
    }
}

// Create suggested pages
function abpcwa_create_suggested_pages($pages, $generate_images = false) {
    try {
        // Input validation
        if (empty($pages) || !is_array($pages)) {
            echo '<div class="notice notice-error"><p>' . __('No pages provided for creation.', 'aiopms') . '</p></div>';
            return;
        }

        $created_count = 0;
        $failed_count = 0;
        $parent_id_stack = [];
        $errors = [];

        foreach ($pages as $page_line) {
            if (empty($page_line)) continue;

            try {
                $excerpt = '';
                if (strpos($page_line, ':+') !== false) {
                    list($page_title, $excerpt) = explode(':+', $page_line, 2);
                    $excerpt = trim($excerpt);
                } else {
                    $page_title = $page_line;
                }

                // Validate page title
                if (empty($page_title)) {
                    $errors[] = __('Empty page title found, skipping.', 'aiopms');
                    continue;
                }

                $depth = 0;
                while (substr($page_title, 0, 1) === '-') {
                    $page_title = substr($page_title, 1);
                    $depth++;
                }
                $page_title = trim($page_title);

                // Validate title length
                if (strlen($page_title) > 200) {
                    $errors[] = sprintf(__('Page title too long (over 200 characters): %s', 'aiopms'), substr($page_title, 0, 50) . '...');
                    continue;
                }

                $parent_id = ($depth > 0 && isset($parent_id_stack[$depth - 1])) ? $parent_id_stack[$depth - 1] : 0;

                // Validate parent exists if specified
                if ($parent_id > 0 && !get_post($parent_id)) {
                    $errors[] = sprintf(__('Parent page not found for: %s', 'aiopms'), $page_title);
                    $parent_id = 0; // Reset to root level
                }

                // Generate SEO-optimized slug
                $post_name = aiopms_generate_seo_slug($page_title);
                
                $new_page = array(
                    'post_title'   => $page_title,
                    'post_name'    => $post_name,
                    'post_content' => '',
                    'post_status'  => 'draft',
                    'post_type'    => 'page',
                    'post_parent'  => $parent_id,
                    'post_excerpt' => $excerpt,
                );
                
                $page_id = wp_insert_post($new_page);

                if ($page_id && !is_wp_error($page_id)) {
                    $created_count++;
                    
                    // Generate and set featured image if enabled
                    if ($generate_images) {
                        try {
                            abpcwa_generate_and_set_featured_image($page_id, $page_title);
                        } catch (Exception $e) {
                            $errors[] = sprintf(__('Failed to generate image for "%s": %s', 'aiopms'), $page_title, $e->getMessage());
                        }
                    }
                    
                    // Generate schema markup for the new page
                    $auto_generate = get_option('aiopms_auto_schema_generation', true);
                    if ($auto_generate) {
                        try {
                            aiopms_generate_schema_markup($page_id);
                        } catch (Exception $e) {
                            $errors[] = sprintf(__('Failed to generate schema for "%s": %s', 'aiopms'), $page_title, $e->getMessage());
                        }
                    }
                    
                    $parent_id_stack[$depth] = $page_id;
                    $parent_id_stack = array_slice($parent_id_stack, 0, $depth + 1);
                } else {
                    $failed_count++;
                    $error_message = is_wp_error($page_id) ? $page_id->get_error_message() : __('Unknown error', 'aiopms');
                    $errors[] = sprintf(__('Failed to create page "%s": %s', 'aiopms'), $page_title, $error_message);
                }

            } catch (Exception $e) {
                $failed_count++;
                $errors[] = sprintf(__('Error processing page "%s": %s', 'aiopms'), $page_line, $e->getMessage());
            }
        }

        // Display results
        if ($created_count > 0) {
            $message = sprintf(
                __('%d pages created successfully as drafts.', 'aiopms'),
                absint($created_count)
            );
            if ($generate_images) {
                $message .= ' ' . __('Featured images generated with AI.', 'aiopms');
            }
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }

        if ($failed_count > 0) {
            $error_message = sprintf(__('%d pages failed to create.', 'aiopms'), absint($failed_count));
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
        }

        // Log errors if any
        if (!empty($errors)) {
            error_log('AIOPMS Page Creation Errors: ' . implode('; ', $errors));
        }

        // Log successful creation
        aiopms_log_ai_generation('page_creation', 'manual', true, $created_count);

    } catch (Exception $e) {
        echo '<div class="notice notice-error"><p>' . __('An error occurred during page creation. Please try again.', 'aiopms') . '</p></div>';
        error_log('AIOPMS Page Creation Error: ' . $e->getMessage());
        aiopms_log_ai_generation('page_creation', 'manual', false, 0, $e->getMessage());
    }
}

// Generate and set featured image using AI
function abpcwa_generate_and_set_featured_image($post_id, $page_title) {
    try {
        // Input validation
        if (empty($post_id) || empty($page_title)) {
            throw new Exception(__('Missing required parameters for image generation.', 'aiopms'));
        }

        $post_id = absint($post_id);
        if ($post_id <= 0) {
            throw new Exception(__('Invalid post ID for image generation.', 'aiopms'));
        }

        // Verify post exists
        if (!get_post($post_id)) {
            throw new Exception(__('Post not found for image generation.', 'aiopms'));
        }

        $provider = get_option('aiopms_ai_provider', 'openai');
        $api_key = get_option('aiopms_' . $provider . '_api_key');
        $brand_color = get_option('aiopms_brand_color', '#4A90E2');
        
        if (empty($api_key)) {
            throw new Exception(__('API key not configured for image generation.', 'aiopms'));
        }
        
        // Skip if provider is DeepSeek (no image generation support)
        if ($provider === 'deepseek') {
            throw new Exception(__('Image generation not supported with DeepSeek provider.', 'aiopms'));
        }
        
        // Rate limiting check for image generation
        if (!aiopms_check_ai_rate_limit($provider)) {
            throw new Exception(__('Too many AI requests. Please wait a moment before trying again.', 'aiopms'));
        }
        
        // Validate brand color format
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $brand_color)) {
            $brand_color = '#4A90E2'; // Default fallback
        }
        
        // Generate image prompt
        $prompt = "## IMAGE CREATION BRIEF
Create a professional featured image for a webpage titled: '{$page_title}'

## STYLE & AESTHETIC REQUIREMENTS
- **Style**: Modern, minimalist, abstract background
- **Color Palette**: Primary color: {$brand_color} with complementary tones
- **Mood**: Professional, clean, engaging but not distracting
- **Composition**: Balanced, with visual hierarchy that supports text overlay

## TECHNICAL SPECIFICATIONS
- **Aspect Ratio**: 16:9 (standard for featured images)
- **Resolution**: High-quality, sharp details
- **Text Readability**: Design should allow for clear text overlay
- **Brand Alignment**: Reflect the professional nature of the content

## CREATIVE DIRECTION
- Use abstract shapes, gradients, or subtle patterns
- Incorporate the primary color {$brand_color} as the dominant hue
- Create visual interest without being too busy or distracting
- Ensure the image works well as a background for white text overlay
- Maintain a professional, corporate-appropriate aesthetic

## USAGE CONTEXT
This image will be used as a featured image for a webpage, so it should:
- Be visually appealing but not overpower the content
- Work well at various sizes (thumbnail to full-width)
- Convey professionalism and relevance to the page topic
- Have adequate contrast for text readability

Avoid photorealistic images - focus on abstract, brand-aligned graphics that enhance the page's professional appearance.";
        
        // Call the appropriate image generation API
        $image_url = '';
        switch ($provider) {
            case 'openai':
                $image_url = abpcwa_generate_openai_image($prompt, $api_key);
                break;
            case 'gemini':
                $image_url = abpcwa_generate_gemini_image($prompt, $api_key);
                break;
            default:
                throw new Exception(__('Unsupported provider for image generation.', 'aiopms'));
        }
        
        if (empty($image_url)) {
            throw new Exception(__('Failed to generate image URL from AI provider.', 'aiopms'));
        }

        // Validate image URL
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            throw new Exception(__('Invalid image URL received from AI provider.', 'aiopms'));
        }
        
        // Generate SEO-optimized image metadata based on page title
        $image_title = "Featured Image for " . sanitize_text_field($page_title);
        
        // Extract primary keywords from page title for alt text
        $keywords = aiopms_extract_primary_keywords($page_title);
        $image_alt = "Visual representation of " . $keywords . " concept";
        
        $image_description = "AI-generated featured image showcasing themes related to " . sanitize_text_field($page_title);
        
        // Use the enhanced featured image setting function with metadata
        $result = aiopms_set_featured_image($post_id, $image_url, $image_title, $image_alt, $image_description);
        
        if (!$result) {
            throw new Exception(__('Failed to set featured image for post.', 'aiopms'));
        }

        return true;
        
    } catch (Exception $e) {
        error_log('AIOPMS Image Generation Error: ' . $e->getMessage());
        return false;
    }
}

// Generate image using OpenAI DALL-E
function abpcwa_generate_openai_image($prompt, $api_key) {
    try {
        // Input validation
        if (empty($prompt) || empty($api_key)) {
            throw new Exception(__('Missing required parameters for OpenAI image generation.', 'aiopms'));
        }

        $url = 'https://api.openai.com/v1/images/generations';
        
        $body = json_encode([
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
            'quality' => 'standard'
        ]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Failed to encode request data for OpenAI image generation.', 'aiopms'));
        }
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => $body,
            'timeout' => 60, // Longer timeout for image generation
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception(sprintf(__('OpenAI image generation request failed: %s', 'aiopms'), $response->get_error_message()));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            throw new Exception(sprintf(__('OpenAI image generation returned error %d: %s', 'aiopms'), $response_code, $error_message));
        }

        $response_body = wp_remote_retrieve_body($response);
        if (empty($response_body)) {
            throw new Exception(__('Empty response received from OpenAI image generation.', 'aiopms'));
        }

        $decoded_response = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Invalid JSON response from OpenAI image generation.', 'aiopms'));
        }

        if (isset($decoded_response['error'])) {
            $error_message = isset($decoded_response['error']['message']) ? $decoded_response['error']['message'] : __('Unknown OpenAI image generation error.', 'aiopms');
            throw new Exception(sprintf(__('OpenAI image generation error: %s', 'aiopms'), $error_message));
        }

        if (!isset($decoded_response['data'][0]['url'])) {
            throw new Exception(__('Unexpected response format from OpenAI image generation.', 'aiopms'));
        }

        $image_url = $decoded_response['data'][0]['url'];
        if (empty($image_url)) {
            throw new Exception(__('Empty image URL received from OpenAI.', 'aiopms'));
        }

        return $image_url;
        
    } catch (Exception $e) {
        error_log('AIOPMS OpenAI Image Generation Error: ' . $e->getMessage());
        return '';
    }
}

// Generate image using Google Gemini
function abpcwa_generate_gemini_image($prompt, $api_key) {
    // Note: As of current implementation, Gemini doesn't have a direct image generation API like DALL-E
    // This function is a placeholder for future implementation when Gemini releases image generation
    // For now, we'll return empty string to indicate no image generation
    return '';
}

// Process keywords from CSV file
function abpcwa_process_keywords_csv($file) {
    try {
        // Input validation
        if (!isset($file) || !is_array($file)) {
            throw new Exception(__('Invalid file data provided.', 'aiopms'));
        }

        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            throw new Exception(__('No temporary file found for CSV processing.', 'aiopms'));
        }

        // Check for upload errors
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => __('File exceeds upload_max_filesize directive.', 'aiopms'),
                UPLOAD_ERR_FORM_SIZE => __('File exceeds MAX_FILE_SIZE directive.', 'aiopms'),
                UPLOAD_ERR_PARTIAL => __('File was only partially uploaded.', 'aiopms'),
                UPLOAD_ERR_NO_FILE => __('No file was uploaded.', 'aiopms'),
                UPLOAD_ERR_NO_TMP_DIR => __('Missing temporary folder.', 'aiopms'),
                UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk.', 'aiopms'),
                UPLOAD_ERR_EXTENSION => __('File upload stopped by extension.', 'aiopms'),
            ];
            $error_message = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : __('Unknown upload error.', 'aiopms');
            throw new Exception($error_message);
        }

        // Validate file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'csv') {
            throw new Exception(__('Only CSV files are allowed.', 'aiopms'));
        }

        // Check file size (limit to 1MB)
        if ($file['size'] > 1048576) {
            throw new Exception(__('File size must be less than 1MB.', 'aiopms'));
        }

        // Validate file exists and is readable
        if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
            throw new Exception(__('File is not accessible for reading.', 'aiopms'));
        }
        
        $keywords = [];
        $handle = fopen($file['tmp_name'], 'r');
        
        if ($handle === false) {
            throw new Exception(__('Failed to open CSV file for reading.', 'aiopms'));
        }

        $line_count = 0;
        $max_lines = 1000; // Limit to prevent memory issues
        
        while (($data = fgetcsv($handle)) !== false) {
            $line_count++;
            
            // Prevent processing too many lines
            if ($line_count > $max_lines) {
                fclose($handle);
                throw new Exception(sprintf(__('CSV file has too many lines. Maximum allowed: %d', 'aiopms'), $max_lines));
            }

            if (!is_array($data)) {
                continue; // Skip invalid rows
            }

            foreach ($data as $cell) {
                $cell = trim($cell);
                if (!empty($cell)) {
                    // Validate cell length
                    if (strlen($cell) > 200) {
                        continue; // Skip overly long keywords
                    }

                    // Handle both comma-separated values and individual keywords
                    if (strpos($cell, ',') !== false) {
                        $split_keywords = array_map('trim', explode(',', $cell));
                        foreach ($split_keywords as $keyword) {
                            if (!empty($keyword) && strlen($keyword) <= 200) {
                                $keywords[] = sanitize_text_field($keyword);
                            }
                        }
                    } else {
                        $keywords[] = sanitize_text_field($cell);
                    }
                }
            }
        }
        fclose($handle);
        
        if (empty($keywords)) {
            throw new Exception(__('No valid keywords found in CSV file.', 'aiopms'));
        }
        
        // Remove duplicates and empty values
        $keywords = array_unique(array_filter($keywords));
        
        if (empty($keywords)) {
            throw new Exception(__('No valid keywords remaining after processing.', 'aiopms'));
        }

        // Limit total keywords to prevent issues
        if (count($keywords) > 500) {
            $keywords = array_slice($keywords, 0, 500);
        }
        
        return implode(', ', $keywords);

    } catch (Exception $e) {
        error_log('AIOPMS CSV Processing Error: ' . $e->getMessage());
        return '';
    }
}

// Extract primary keywords from page title for SEO optimization
if (!function_exists('aiopms_extract_primary_keywords')) {
    function aiopms_extract_primary_keywords($title) {
        try {
            if (empty($title)) {
                return '';
            }

            // Remove common stop words and extract meaningful keywords
            $stop_words = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an'];
            
            // Clean the title and split into words
            $words = preg_split('/\s+/', strtolower($title));
            $words = array_map('trim', $words);
            
            // Remove stop words and short words
            $keywords = array_filter($words, function($word) use ($stop_words) {
                return !in_array($word, $stop_words) && strlen($word) > 2 && !is_numeric($word);
            });
            
            // Remove duplicates and return the first 3-4 keywords
            $keywords = array_unique($keywords);
            $keywords = array_slice($keywords, 0, 4);
            
            return implode(' ', $keywords) ?: sanitize_text_field($title);
        } catch (Exception $e) {
            error_log('AIOPMS Keyword Extraction Error: ' . $e->getMessage());
            return sanitize_text_field($title);
        }
    }
}

// Validate API key format
if (!function_exists('aiopms_validate_api_key')) {
    function aiopms_validate_api_key($api_key, $provider) {
        try {
            if (empty($api_key)) {
                return false;
            }

            switch ($provider) {
                case 'openai':
                    // OpenAI API keys typically start with 'sk-' and are 51 characters long
                    return preg_match('/^sk-[a-zA-Z0-9]{48}$/', $api_key);
                case 'gemini':
                    // Google API keys are typically 39 characters long and alphanumeric
                    return preg_match('/^[a-zA-Z0-9]{39}$/', $api_key);
                case 'deepseek':
                    // DeepSeek API keys typically start with 'sk-' and are 51 characters long
                    return preg_match('/^sk-[a-zA-Z0-9]{48}$/', $api_key);
                default:
                    return false;
            }
        } catch (Exception $e) {
            error_log('AIOPMS API Key Validation Error: ' . $e->getMessage());
            return false;
        }
    }
}

// Check AI rate limiting per provider
if (!function_exists('aiopms_check_ai_rate_limit')) {
    function aiopms_check_ai_rate_limit($provider = null) {
        try {
            // Get current provider if not specified
            if (empty($provider)) {
                $provider = get_option('aiopms_ai_provider', 'openai');
            }
            
            // Validate provider
            $valid_providers = ['openai', 'gemini', 'deepseek'];
            if (!in_array($provider, $valid_providers)) {
                error_log('AIOPMS Rate Limit: Invalid provider specified: ' . $provider);
                return true; // Allow on invalid provider to prevent blocking
            }
            
            $user_id = get_current_user_id();
            if (empty($user_id)) {
                error_log('AIOPMS Rate Limit: No user ID found');
                return true; // Allow for non-logged-in users (shouldn't happen in admin)
            }
            
            // Create provider-specific rate limit key
            $rate_limit_key = 'aiopms_ai_rate_limit_' . $provider . '_' . $user_id;
            $rate_limit_data = get_transient($rate_limit_key);
            
            $current_time = time();
            
            if ($rate_limit_data === false) {
                // No rate limit data, create new entry
                $rate_limit_data = [
                    'count' => 1,
                    'first_request' => $current_time,
                    'last_request' => $current_time,
                    'reset_time' => $current_time + 60
                ];
                set_transient($rate_limit_key, $rate_limit_data, 60);
                return true;
            }
            
            // Check if the rate limit window has expired (1 minute)
            if ($current_time >= $rate_limit_data['reset_time']) {
                // Reset the rate limit window
                $rate_limit_data = [
                    'count' => 1,
                    'first_request' => $current_time,
                    'last_request' => $current_time,
                    'reset_time' => $current_time + 60
                ];
                set_transient($rate_limit_key, $rate_limit_data, 60);
                return true;
            }
            
            // Check if we're within the rate limit (10 requests per minute per provider)
            if ($rate_limit_data['count'] >= 10) {
                // Log rate limit exceeded
                error_log(sprintf(
                    'AIOPMS Rate Limit Exceeded: User %d, Provider %s, Count %d, Reset in %d seconds',
                    $user_id,
                    $provider,
                    $rate_limit_data['count'],
                    $rate_limit_data['reset_time'] - $current_time
                ));
                return false;
            }
            
            // Increment counter and update last request time
            $rate_limit_data['count']++;
            $rate_limit_data['last_request'] = $current_time;
            set_transient($rate_limit_key, $rate_limit_data, 60);
            
            return true;
            
        } catch (Exception $e) {
            error_log('AIOPMS Rate Limit Check Error: ' . $e->getMessage());
            return true; // Allow on error to prevent blocking users
        }
    }
}

// Get rate limit status for a specific provider
if (!function_exists('aiopms_get_rate_limit_status')) {
    function aiopms_get_rate_limit_status($provider = null) {
        try {
            // Get current provider if not specified
            if (empty($provider)) {
                $provider = get_option('aiopms_ai_provider', 'openai');
            }
            
            $user_id = get_current_user_id();
            if (empty($user_id)) {
                return null;
            }
            
            $rate_limit_key = 'aiopms_ai_rate_limit_' . $provider . '_' . $user_id;
            $rate_limit_data = get_transient($rate_limit_key);
            
            if ($rate_limit_data === false) {
                return [
                    'provider' => $provider,
                    'count' => 0,
                    'limit' => 10,
                    'reset_time' => null,
                    'time_remaining' => null,
                    'is_limited' => false
                ];
            }
            
            $current_time = time();
            $time_remaining = max(0, $rate_limit_data['reset_time'] - $current_time);
            
            return [
                'provider' => $provider,
                'count' => $rate_limit_data['count'],
                'limit' => 10,
                'reset_time' => $rate_limit_data['reset_time'],
                'time_remaining' => $time_remaining,
                'is_limited' => $rate_limit_data['count'] >= 10
            ];
            
        } catch (Exception $e) {
            error_log('AIOPMS Rate Limit Status Error: ' . $e->getMessage());
            return null;
        }
    }
}

// Test rate limiting functionality (for debugging purposes)
if (!function_exists('aiopms_test_rate_limiting')) {
    function aiopms_test_rate_limiting($provider = 'openai') {
        try {
            $results = [];
            
            // Test multiple requests to see rate limiting in action
            for ($i = 1; $i <= 12; $i++) {
                $allowed = aiopms_check_ai_rate_limit($provider);
                $status = aiopms_get_rate_limit_status($provider);
                
                $results[] = [
                    'request' => $i,
                    'allowed' => $allowed,
                    'count' => $status['count'],
                    'is_limited' => $status['is_limited'],
                    'time_remaining' => $status['time_remaining']
                ];
                
                // Small delay to simulate real usage
                usleep(100000); // 0.1 second
            }
            
            return $results;
            
        } catch (Exception $e) {
            error_log('AIOPMS Rate Limit Test Error: ' . $e->getMessage());
            return false;
        }
    }
}

// Log AI generation activities
if (!function_exists('aiopms_log_ai_generation')) {
    function aiopms_log_ai_generation($type, $provider, $success, $count = 0, $error_message = '') {
        try {
            global $wpdb;
            
            $user_id = get_current_user_id();
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $log_data = [
                'user_id' => $user_id,
                'type' => sanitize_text_field($type),
                'provider' => sanitize_text_field($provider),
                'success' => $success ? 1 : 0,
                'count' => absint($count),
                'error_message' => sanitize_text_field($error_message),
                'ip_address' => sanitize_text_field($ip_address),
                'user_agent' => sanitize_text_field($user_agent),
                'created_at' => current_time('mysql')
            ];
            
            // Insert into custom table
            $table_name = $wpdb->prefix . 'aiopms_generation_logs';
            $wpdb->insert($table_name, $log_data);
            
            // Also log to error log for debugging
            $log_message = sprintf(
                'AIOPMS Generation: Type=%s, Provider=%s, Success=%s, Count=%d, User=%d',
                $type,
                $provider,
                $success ? 'Yes' : 'No',
                $count,
                $user_id
            );
            
            if (!$success && !empty($error_message)) {
                $log_message .= ', Error=' . $error_message;
            }
            
            error_log($log_message);
            
        } catch (Exception $e) {
            error_log('AIOPMS Logging Error: ' . $e->getMessage());
        }
    }
}

// ===== ADVANCED MODE FUNCTIONALITY =====

// Generate advanced content with AI (pages + custom post types)
function aiopms_generate_advanced_content_with_ai($business_type, $business_details, $seo_keywords = '', $target_audience = '') {
    try {
        // Input validation
        if (empty($business_type) || empty($business_details)) {
            echo '<div class="notice notice-error"><p>' . __('Business type and details are required for advanced AI generation.', 'aiopms') . '</p></div>';
            return;
        }

        // Sanitize inputs
        $business_type = sanitize_text_field($business_type);
        $business_details = sanitize_textarea_field($business_details);
        $seo_keywords = sanitize_text_field($seo_keywords);
        $target_audience = sanitize_text_field($target_audience);

        // Validate input lengths
        if (strlen($business_type) > 100) {
            echo '<div class="notice notice-error"><p>' . __('Business type must be 100 characters or less.', 'aiopms') . '</p></div>';
            return;
        }

        if (strlen($business_details) > 2000) {
            echo '<div class="notice notice-error"><p>' . __('Business details must be 2000 characters or less.', 'aiopms') . '</p></div>';
            return;
        }

        $provider = get_option('aiopms_ai_provider', 'openai');
        $api_key = get_option('aiopms_' . $provider . '_api_key');

        if (empty($api_key)) {
            echo '<div class="notice notice-error"><p>' . sprintf(__('Please enter your %s API key in the Settings tab.', 'aiopms'), esc_html(ucfirst($provider))) . '</p></div>';
            return;
        }

        // Validate API key format
        if (!aiopms_validate_api_key($api_key, $provider)) {
            echo '<div class="notice notice-error"><p>' . sprintf(__('Invalid %s API key format. Please check your API key.', 'aiopms'), esc_html(ucfirst($provider))) . '</p></div>';
            return;
        }

        // Rate limiting check
        if (!aiopms_check_ai_rate_limit($provider)) {
            echo '<div class="notice notice-error"><p>' . __('Too many AI requests. Please wait a moment before trying again.', 'aiopms') . '</p></div>';
            return;
        }

        // Get advanced content suggestions from AI
        $advanced_suggestions = [];
        switch ($provider) {
            case 'openai':
                $advanced_suggestions = aiopms_get_openai_advanced_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            case 'gemini':
                $advanced_suggestions = aiopms_get_gemini_advanced_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            case 'deepseek':
                $advanced_suggestions = aiopms_get_deepseek_advanced_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            default:
                echo '<div class="notice notice-error"><p>' . __('Invalid AI provider selected.', 'aiopms') . '</p></div>';
                return;
        }

        if (empty($advanced_suggestions)) {
            echo '<div class="notice notice-warning"><p>' . __('Could not generate advanced content suggestions. Please check your API key and try again.', 'aiopms') . '</p></div>';
            return;
        }

        // Parse the AI response
        $parsed_suggestions = aiopms_parse_advanced_ai_response($advanced_suggestions);
        
        if (empty($parsed_suggestions['pages']) && empty($parsed_suggestions['custom_post_types'])) {
            echo '<div class="notice notice-warning"><p>' . __('No content suggestions were generated. Please try with more detailed business information.', 'aiopms') . '</p></div>';
            return;
        }

        // Log successful generation
        $total_suggestions = count($parsed_suggestions['pages']) + count($parsed_suggestions['custom_post_types']);
        aiopms_log_ai_generation('advanced_content', $provider, true, $total_suggestions);

        // Display the suggestions
        aiopms_display_advanced_content_suggestions($parsed_suggestions);

    } catch (Exception $e) {
        // Log error
        aiopms_log_ai_generation('advanced_content', $provider ?? 'unknown', false, 0, $e->getMessage());
        echo '<div class="notice notice-error"><p>' . __('An error occurred during advanced AI generation. Please try again.', 'aiopms') . '</p></div>';
        error_log('AIOPMS Advanced AI Generation Error: ' . $e->getMessage());
    }
}

// Get advanced suggestions from OpenAI API
function aiopms_get_openai_advanced_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $prompt = aiopms_build_advanced_ai_prompt($business_type, $business_details, $seo_keywords, $target_audience);

    $body = json_encode([
        'model' => 'gpt-4',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.7,
        'max_tokens' => 2000,
    ]);

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => $body,
        'timeout' => 60,
    ]);

    if (is_wp_error($response)) {
        return [];
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['choices'][0]['message']['content'])) {
        return $response_body['choices'][0]['message']['content'];
    }

    return [];
}

// Get advanced suggestions from Gemini API
function aiopms_get_gemini_advanced_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;
    
    $prompt = aiopms_build_advanced_ai_prompt($business_type, $business_details, $seo_keywords, $target_audience);

    $body = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
    ]);

    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => $body,
        'timeout' => 60,
    ]);

    if (is_wp_error($response)) {
        return [];
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['candidates'][0]['content']['parts'][0]['text'])) {
        return $response_body['candidates'][0]['content']['parts'][0]['text'];
    }

    return [];
}

// Get advanced suggestions from DeepSeek API
function aiopms_get_deepseek_advanced_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    $url = 'https://api.deepseek.com/v1/chat/completions';
    
    $prompt = aiopms_build_advanced_ai_prompt($business_type, $business_details, $seo_keywords, $target_audience);

    $body = json_encode([
        'model' => 'deepseek-chat',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.7,
        'max_tokens' => 2000,
    ]);

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => $body,
        'timeout' => 60,
    ]);

    if (is_wp_error($response)) {
        return [];
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['choices'][0]['message']['content'])) {
        return $response_body['choices'][0]['message']['content'];
    }

    return [];
}

// Build the advanced AI prompt for dynamic business analysis
function aiopms_build_advanced_ai_prompt($business_type, $business_details, $seo_keywords, $target_audience) {
    return "## ROLE & CONTEXT
You are an expert digital strategist and WordPress developer specializing in creating comprehensive content ecosystems for businesses. Your task is to analyze a specific business and generate both standard pages AND custom post types that would be most valuable for that business model.

## BUSINESS CONTEXT TO ANALYZE
- **Business Type**: {$business_type}
- **Business Details**: {$business_details}
- **Target Audience**: {$target_audience}
- **Primary Keywords**: {$seo_keywords}

## CRITICAL REQUIREMENTS

### 1. DYNAMIC BUSINESS ANALYSIS
- **DO NOT use predefined templates or examples**
- **Analyze THIS specific business** and determine what content would be most valuable
- **Think about the business model** - what type of content would help this business succeed?
- **Consider the target audience** - what information would they be looking for?
- **Think about conversion** - what content would help turn visitors into customers?

### 2. INTELLIGENT CONTENT SUGGESTIONS
For each suggestion, provide:
- **Clear reasoning** for why this content type is valuable for THIS business
- **Specific custom fields** that would be useful for managing this content
- **Sample content ideas** that would be relevant to this business

### 3. CUSTOM POST TYPE ANALYSIS
Consider what types of content this business would need to manage regularly:
- **Portfolio/Showcase content** (for service businesses)
- **Product catalogs** (for e-commerce)
- **Case studies** (for agencies/consultants)
- **Testimonials** (for service businesses)
- **Team members** (for professional services)
- **Events** (for event-based businesses)
- **Courses/Tutorials** (for educational businesses)
- **News/Updates** (for any business with regular updates)

## OUTPUT FORMAT

Return your analysis in this EXACT JSON format:

{
  \"business_analysis\": {
    \"business_model\": \"Brief description of the business model\",
    \"content_needs\": \"What types of content this business needs\",
    \"target_audience_insights\": \"What the target audience is looking for\"
  },
  \"standard_pages\": [
    {
      \"title\": \"Page Title\",
      \"meta_description\": \"SEO-optimized meta description (155-160 chars)\",
      \"reasoning\": \"Why this page is essential for this business\",
      \"hierarchy_level\": 0
    }
  ],
  \"custom_post_types\": [
    {
      \"name\": \"post_type_slug\",
      \"label\": \"Display Name\",
      \"description\": \"What this post type is for\",
      \"reasoning\": \"Why this post type is valuable for this specific business\",
      \"custom_fields\": [
        {
          \"name\": \"field_slug\",
          \"label\": \"Field Label\",
          \"type\": \"text|textarea|select|image|url|number|date\",
          \"description\": \"What this field is for\",
          \"required\": true|false
        }
      ],
      \"sample_entries\": [
        {
          \"title\": \"Sample Entry Title\",
          \"content\": \"Brief description of what this entry would contain\"
        }
      ]
    }
  ]
}

## ANALYSIS GUIDELINES

### For Standard Pages:
- Include only pages that make sense for THIS specific business
- Consider what pages customers would expect to find
- Think about the customer journey and what information they need
- Include both commercial and informational pages

### For Custom Post Types:
- Think about what content this business creates regularly
- Consider what would help showcase their expertise
- Think about what would help with SEO and user engagement
- Consider what would help convert visitors to customers

### For Custom Fields:
- Choose fields that would be genuinely useful for this business
- Consider what information customers would want to see
- Think about what would help with content management
- Include fields that would enhance SEO

## EXAMPLES OF GOOD ANALYSIS:

**Digital Marketing Agency** might need:
- Standard pages: Home, About, Services, Contact, Blog
- Custom post type: \"Case Studies\" with fields like Client Name, Industry, Results, Project Duration, Testimonial

**Pet Grooming Service** might need:
- Standard pages: Home, Services, About, Contact, Gallery
- Custom post type: \"Services\" with fields like Service Name, Price, Duration, Pet Types, Description

**Online Course Platform** might need:
- Standard pages: Home, Courses, About, Contact, Pricing
- Custom post type: \"Courses\" with fields like Course Title, Price, Duration, Skill Level, Prerequisites, Instructor

**Wedding Photography Business** might need:
- Standard pages: Home, Portfolio, Services, About, Contact
- Custom post type: \"Portfolio\" with fields like Event Type, Date, Location, Couple Names, Photo Count, Gallery

## REMEMBER:
- Analyze the SPECIFIC business provided
- Don't use generic templates
- Think about what would actually help this business succeed
- Consider the target audience and their needs
- Focus on content that would drive conversions

Provide a comprehensive analysis that would create a complete content ecosystem for this specific business.";
}

// Parse the AI response into structured data
function aiopms_parse_advanced_ai_response($ai_response) {
    $parsed = [
        'pages' => [],
        'custom_post_types' => []
    ];

    // Try to extract JSON from the response
    $json_start = strpos($ai_response, '{');
    $json_end = strrpos($ai_response, '}') + 1;
    
    if ($json_start !== false && $json_end !== false) {
        $json_string = substr($ai_response, $json_start, $json_end - $json_start);
        $data = json_decode($json_string, true);
        
        if ($data) {
            $parsed['pages'] = isset($data['standard_pages']) ? $data['standard_pages'] : [];
            $parsed['custom_post_types'] = isset($data['custom_post_types']) ? $data['custom_post_types'] : [];
            $parsed['business_analysis'] = isset($data['business_analysis']) ? $data['business_analysis'] : [];
        }
    }

    return $parsed;
}

// Display advanced content suggestions
/**
 * Display advanced content suggestions - Modernized
 * 
 * @since 3.0
 */
function aiopms_display_advanced_content_suggestions($suggestions) {
    echo '<div class="aiopms-advanced-suggestions">';
    
    // Display business analysis if available
    if (!empty($suggestions['business_analysis'])) {
        echo '<div class="aiopms-business-analysis dg10-card">';
        echo '<h3>📊 ' . esc_html__('Business Intelligence Analysis', 'aiopms') . '</h3>';
        echo '<div class="analysis-content">';
        if (isset($suggestions['business_analysis']['business_model'])) {
            echo '<p><strong>' . esc_html__('Strategic Model:', 'aiopms') . '</strong> ' . esc_html($suggestions['business_analysis']['business_model']) . '</p>';
        }
        if (isset($suggestions['business_analysis']['content_needs'])) {
            echo '<p><strong>' . esc_html__('Content Strategy:', 'aiopms') . '</strong> ' . esc_html($suggestions['business_analysis']['content_needs']) . '</p>';
        }
        if (isset($suggestions['business_analysis']['target_audience_insights'])) {
            echo '<p><strong>' . esc_html__('Audience Insights:', 'aiopms') . '</strong> ' . esc_html($suggestions['business_analysis']['target_audience_insights']) . '</p>';
        }
        echo '</div>';
        echo '</div>';
    }

    echo '<form id="aiopms-ai-creation-form" method="post" action="">';
    wp_nonce_field('aiopms_create_advanced_content', 'nonce');
    echo '<input type="hidden" name="action_type" value="advanced">';

    // Display standard pages
    if (!empty($suggestions['pages'])) {
        echo '<div class="aiopms-pages-section">';
        echo '<h3>📄 ' . esc_html__('Core Architecture: Pages', 'aiopms') . '</h3>';
        echo '<table class="dg10-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th width="40px"><input type="checkbox" id="select-all-pages" checked></th>';
        echo '<th>' . esc_html__('Strategic Page', 'aiopms') . '</th>';
        echo '<th>' . esc_html__('SEO Description', 'aiopms') . '</th>';
        echo '<th>' . esc_html__('Strategic Reasoning', 'aiopms') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($suggestions['pages'] as $page) {
            echo '<tr>';
            echo '<td><input type="checkbox" name="aiopms_selected_pages[]" value="' . esc_attr(json_encode($page)) . '" class="aiopms-page-checkbox" checked></td>';
            echo '<td><strong>' . esc_html($page['title']) . '</strong></td>';
            echo '<td>' . esc_html($page['meta_description']) . '</td>';
            echo '<td><small>' . esc_html($page['reasoning']) . '</small></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    // Display custom post types
    if (!empty($suggestions['custom_post_types'])) {
        echo '<div class="aiopms-cpts-section">';
        echo '<h3>🏗️ ' . esc_html__('Dynamic Ecosystem: Custom Post Types', 'aiopms') . '</h3>';
        
        echo '<div class="aiopms-cpt-suggestions-grid">';
        foreach ($suggestions['custom_post_types'] as $cpt) {
            echo '<div class="cpt-suggestion dg10-card">';
            echo '<div class="cpt-header">';
            echo '<label class="dg10-checkbox-label">';
            echo '<input type="checkbox" name="aiopms_selected_cpts[]" value="' . esc_attr(json_encode($cpt)) . '" class="aiopms-cpt-checkbox" checked>';
            echo '<strong>' . esc_html($cpt['label']) . '</strong> <code class="dg10-slug">' . esc_html($cpt['name']) . '</code>';
            echo '</label>';
            echo '</div>';
            
            echo '<div class="cpt-details">';
            echo '<p class="description">' . esc_html($cpt['description']) . '</p>';
            echo '<p class="reasoning"><strong>' . esc_html__('Why it works:', 'aiopms') . '</strong> ' . esc_html($cpt['reasoning']) . '</p>';
            
            if (!empty($cpt['custom_fields'])) {
                echo '<div class="custom-fields">';
                echo '<h4>' . esc_html__('Intelligence Fields:', 'aiopms') . '</h4>';
                echo '<ul>';
                foreach ($cpt['custom_fields'] as $field) {
                    echo '<li>';
                    echo '<strong>' . esc_html($field['label']) . '</strong>';
                    echo ' <span class="field-type">(' . esc_html($field['type']) . ')</span>';
                    if ($field['required']) {
                        echo ' <span class="required">*</span>';
                    }
                    echo '<br><small>' . esc_html($field['description']) . '</small>';
                    echo '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    // Add image generation checkbox
    $provider = get_option('aiopms_ai_provider', 'openai');
    $is_deepseek = $provider === 'deepseek';
    
    echo '<div class="aiopms-options dg10-card" style="margin-top: 32px; padding: 24px;">';
    echo '<label class="dg10-checkbox-label">';
    echo '<input type="checkbox" name="aiopms_generate_images" id="aiopms_generate_images" value="1" ' . checked(true, !$is_deepseek, false) . '>';
    echo '<span><strong>' . esc_html__('AI Visual Generation', 'aiopms') . '</strong> - ' . esc_html__('Automatically create premium featured images for each new page.', 'aiopms') . '</span>';
    echo '</label>';
    
    if ($is_deepseek) {
        echo '<p class="dg10-form-help dg10-text-danger" style="margin-top: 10px;">' . esc_html__('DeepSeek does not currently support image generation.', 'aiopms') . '</p>';
    }
    echo '</div>';

    echo '<div class="dg10-form-actions" style="margin-top: 32px;">';
    echo '<button type="submit" class="dg10-btn dg10-btn-primary">';
    echo '<span class="nav-icon">🚀</span>';
    echo esc_html__('Deploy Selected Strategy', 'aiopms');
    echo '</button>';
    echo '</div>';

    echo '<div class="aiopms-creation-status" style="display: none; margin-top: 24px;"></div>';
    echo '</form>';
    echo '</div>';
}

// Handle creation of advanced content (pages + custom post types)
if (isset($_POST['action']) && $_POST['action'] == 'create_advanced_content' && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'aiopms_create_advanced_content')) {
    if (isset($_POST['aiopms_selected_pages']) && is_array($_POST['aiopms_selected_pages'])) {
        $selected_pages = array_map('sanitize_text_field', wp_unslash($_POST['aiopms_selected_pages']));
        $selected_cpts = isset($_POST['aiopms_selected_cpts']) ? array_map('sanitize_text_field', wp_unslash($_POST['aiopms_selected_cpts'])) : [];
        $generate_images = isset($_POST['aiopms_generate_images']) && $_POST['aiopms_generate_images'] == '1';
        aiopms_create_advanced_content($selected_pages, $selected_cpts, $generate_images);
    }
}

// Create advanced content (pages + custom post types)
function aiopms_create_advanced_content($pages, $custom_post_types, $generate_images = false) {
    set_time_limit(0); // Prevent timeouts for complex generation logic
    $created_pages = 0;
    $created_cpts = 0;
    $parent_id_stack = [];

    // Create standard pages
    foreach ($pages as $page_data) {
        $page = json_decode($page_data, true);
        if (!$page) continue;

        $page_title = $page['title'];
        $meta_description = $page['meta_description'];
        $hierarchy_level = isset($page['hierarchy_level']) ? $page['hierarchy_level'] : 0;

        $parent_id = ($hierarchy_level > 0 && isset($parent_id_stack[$hierarchy_level - 1])) ? $parent_id_stack[$hierarchy_level - 1] : 0;

        // Generate SEO-optimized slug
        $post_name = aiopms_generate_seo_slug($page_title);
        
        $new_page = array(
            'post_title'   => $page_title,
            'post_name'    => $post_name,
            'post_content' => '',
            'post_status'  => 'draft',
            'post_type'    => 'page',
            'post_parent'  => $parent_id,
            'post_excerpt' => $meta_description,
        );
        $page_id = wp_insert_post($new_page);

        if ($page_id) {
            $created_pages++;
            
            // Generate and set featured image if enabled
            if ($generate_images) {
                abpcwa_generate_and_set_featured_image($page_id, $page_title);
            }
            
            // Generate schema markup for the new page
            $auto_generate = get_option('aiopms_auto_schema_generation', true);
            if ($auto_generate) {
                aiopms_generate_schema_markup($page_id);
            }
            
            $parent_id_stack[$hierarchy_level] = $page_id;
            $parent_id_stack = array_slice($parent_id_stack, 0, $hierarchy_level + 1);
        }
    }

    // Create custom post types
    foreach ($custom_post_types as $cpt_data) {
        $cpt = json_decode($cpt_data, true);
        if (!$cpt) continue;

        if (aiopms_register_dynamic_custom_post_type($cpt)) {
            $created_cpts++;
            
            // Create sample entries if specified
            if (!empty($cpt['sample_entries'])) {
                aiopms_create_sample_cpt_entries($cpt);
            }
        }
    }

    // Display success message
    $message_parts = [];
    if ($created_pages > 0) {
        $message_parts[] = sprintf('%d pages created successfully as drafts.', $created_pages);
    }
    if ($created_cpts > 0) {
        $message_parts[] = sprintf('%d custom post types registered successfully.', $created_cpts);
    }
    if ($generate_images && $created_pages > 0) {
        $message_parts[] = 'Featured images generated with AI.';
    }

    if (!empty($message_parts)) {
        echo '<div class="notice notice-success is-dismissible"><p>' . implode(' ', $message_parts) . '</p></div>';
    }
}

// Note: aiopms_register_dynamic_custom_post_type function has been moved to custom-post-type-manager.php
// This ensures proper integration between AI generation and CPT management

// Note: aiopms_register_custom_fields function has been moved to custom-post-type-manager.php
// This ensures proper integration and eliminates code duplication

// Note: Custom field rendering has been moved to custom-post-type-manager.php
// The new implementation includes enhanced security, accessibility, and more field types

// Create sample entries for custom post types
function aiopms_create_sample_cpt_entries($cpt_data) {
    $post_type = sanitize_key($cpt_data['name']);
    
    foreach ($cpt_data['sample_entries'] as $entry) {
        $post_data = array(
            'post_title' => sanitize_text_field($entry['title']),
            'post_content' => sanitize_textarea_field($entry['content']),
            'post_status' => 'draft',
            'post_type' => $post_type,
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !empty($cpt_data['custom_fields'])) {
            // Set sample values for custom fields
            foreach ($cpt_data['custom_fields'] as $field) {
                $sample_value = aiopms_generate_sample_field_value($field);
                update_post_meta($post_id, $field['name'], $sample_value);
            }
        }
    }
}

// Generate sample values for custom fields
function aiopms_generate_sample_field_value($field) {
    switch ($field['type']) {
        case 'number':
            return rand(1, 100);
        case 'date':
            return date('Y-m-d');
        case 'url':
            return 'https://example.com';
        case 'image':
            return 'https://via.placeholder.com/400x300';
        case 'textarea':
            return 'This is a sample ' . strtolower($field['label']) . ' entry.';
        default:
            return 'Sample ' . $field['label'];
    }
}
