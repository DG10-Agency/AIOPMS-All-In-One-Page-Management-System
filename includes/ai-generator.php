<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// AI generation tab content
function aiopms_ai_generation_tab() {
    // Handle creation of suggested pages
    if (isset($_POST['action']) && $_POST['action'] == 'create_suggested_pages' && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'aiopms_create_suggested_pages')) {
        if (isset($_POST['aiopms_selected_pages']) && is_array($_POST['aiopms_selected_pages'])) {
            $selected_pages = array_map('sanitize_text_field', wp_unslash($_POST['aiopms_selected_pages']));
            $generate_images = isset($_POST['aiopms_generate_images']) && $_POST['aiopms_generate_images'] == '1';
            aiopms_create_suggested_pages($selected_pages, $generate_images);
        }
        return; // Stop further processing
    }
    ?>
    <form method="post" action="" enctype="multipart/form-data">
        <?php wp_nonce_field('aiopms_ai_generate_pages'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Business Type</th>
                <td>
                    <input type="text" name="aiopms_business_type" class="regular-text" placeholder="e.g., E-commerce, Blog, Corporate">
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Business Details</th>
                <td>
                    <textarea name="aiopms_business_details" rows="5" class="large-text" placeholder="Provide a brief description of the business and its services."></textarea>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">SEO Keywords</th>
                <td>
                    <input type="text" name="aiopms_seo_keywords" class="regular-text" placeholder="e.g., digital marketing, web design, SEO services">
                    <p class="description">Comma-separated list of primary keywords for SEO optimization</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Upload Keywords CSV</th>
                <td>
                    <input type="file" name="aiopms_keywords_csv" id="aiopms_keywords_csv" accept=".csv">
                    <p class="description">Upload a CSV file with keywords (one keyword per line or comma-separated)</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Target Audience</th>
                <td>
                    <input type="text" name="aiopms_target_audience" class="regular-text" placeholder="e.g., small businesses, entrepreneurs, local clients">
                    <p class="description">Describe your target audience for better content optimization</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Advanced Mode</th>
                <td>
                    <label>
                        <input type="checkbox" name="aiopms_advanced_mode" id="aiopms_advanced_mode" value="1">
                        Enable Advanced Mode - Generate custom post types and dynamic content ecosystem
                    </label>
                    <p class="description">
                        <strong>Standard Mode:</strong> Creates standard pages only<br>
                        <strong>Advanced Mode:</strong> Analyzes your business and suggests custom post types with relevant fields<br>
                        <em>Advanced Mode will show business analysis and custom post type suggestions below</em>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button('Generate Page Suggestions'); ?>
    </form>
    <?php
    if (isset($_POST['submit']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'aiopms_ai_generate_pages')) {
        $business_type = isset($_POST['aiopms_business_type']) ? sanitize_text_field(wp_unslash($_POST['aiopms_business_type'])) : '';
        $business_details = isset($_POST['aiopms_business_details']) ? sanitize_textarea_field(wp_unslash($_POST['aiopms_business_details'])) : '';
        $seo_keywords = isset($_POST['aiopms_seo_keywords']) ? sanitize_text_field(wp_unslash($_POST['aiopms_seo_keywords'])) : '';
        $target_audience = isset($_POST['aiopms_target_audience']) ? sanitize_text_field(wp_unslash($_POST['aiopms_target_audience'])) : '';
        $advanced_mode = isset($_POST['aiopms_advanced_mode']) && $_POST['aiopms_advanced_mode'] == '1';
        
        // Process CSV file if uploaded
        $csv_keywords = '';
        if (isset($_FILES['aiopms_keywords_csv']) && !empty($_FILES['aiopms_keywords_csv']['tmp_name']) && $_FILES['aiopms_keywords_csv']['error'] == UPLOAD_ERR_OK) {
            $csv_keywords = aiopms_process_keywords_csv($_FILES['aiopms_keywords_csv']);
        }
        
        // Combine manual keywords and CSV keywords
        $all_keywords = trim($seo_keywords);
        if (!empty($csv_keywords)) {
            if (!empty($all_keywords)) {
                $all_keywords .= ', ' . $csv_keywords;
            } else {
                $all_keywords = $csv_keywords;
            }
        }
        
        if ($advanced_mode) {
            // Use advanced mode functionality directly
            aiopms_generate_advanced_content_with_ai($business_type, $business_details, $all_keywords, $target_audience);
        } else {
            aiopms_generate_pages_with_ai($business_type, $business_details, $all_keywords, $target_audience);
        }
    }
}

// Generate pages with AI
function aiopms_generate_pages_with_ai($business_type, $business_details, $seo_keywords = '', $target_audience = '') {
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
                $suggested_pages = aiopms_get_openai_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            case 'gemini':
                $suggested_pages = aiopms_get_gemini_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            case 'deepseek':
                $suggested_pages = aiopms_get_deepseek_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
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

    echo '<h3>Suggested Pages:</h3>';
    echo '<form method="post" action="">';
    wp_nonce_field('aiopms_create_suggested_pages');
    echo '<input type="hidden" name="action" value="create_suggested_pages">';
    
    // Display in table format
    echo '<table class="widefat striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th width="20px"><input type="checkbox" id="select-all-pages" checked></th>';
    echo '<th>Page Title</th>';
    echo '<th>Meta Description</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($suggested_pages as $page_line) {
        $excerpt = '';
        $page_title = $page_line;
        
        // Parse the page title and excerpt
        if (strpos($page_line, ':+') !== false) {
            list($page_title, $excerpt) = explode(':+', $page_line, 2);
            $excerpt = trim($excerpt);
        }
        
        // Calculate depth for indentation
        $depth = 0;
        $display_title = $page_title;
        while (substr($display_title, 0, 1) === '-') {
            $display_title = substr($display_title, 1);
            $depth++;
        }
        $display_title = trim($display_title);
        
        echo '<tr>';
        echo '<td><input type="checkbox" name="aiopms_selected_pages[]" value="' . esc_attr($page_line) . '" checked></td>';
        echo '<td>' . str_repeat('&nbsp;&nbsp;&nbsp;', $depth) . esc_html($display_title) . '</td>';
        echo '<td>' . esc_html($excerpt) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Add select all JavaScript
    echo '<script>
    jQuery(document).ready(function($) {
        $("#select-all-pages").on("change", function() {
            $("input[name=\'aiopms_selected_pages[]\']").prop("checked", $(this).prop("checked"));
        });
    });
    </script>';
    
    // Add image generation checkbox
    $provider = get_option('aiopms_ai_provider', 'openai');
    $is_deepseek = $provider === 'deepseek';
    
    echo '<p>';
    echo '<input type="checkbox" name="aiopms_generate_images" id="aiopms_generate_images" value="1" ' . checked(true, !$is_deepseek, false) . '>';
    echo '<label for="aiopms_generate_images"> Generate featured images with AI</label>';
    
    if ($is_deepseek) {
        echo ' <span class="description" style="color: #d63638;">(Image generation not supported with DeepSeek)</span>';
    }
    echo '</p>';
    
    submit_button('Create Selected Pages');
    echo '</form>';
}

// Get page suggestions from OpenAI API
function aiopms_get_openai_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    try {
        // Input validation
        if (empty($api_key) || empty($business_type) || empty($business_details)) {
            throw new Exception(__('Missing required parameters for OpenAI API call.', 'aiopms'));
        }

        // Check cache first
        $cache_key = 'openai_suggestions_' . md5($business_type . $business_details . $seo_keywords . $target_audience);
        $cached_result = aiopms_cache_get($cache_key, 'ai_suggestions');
        if ($cached_result !== false) {
            return $cached_result;
        }

        $url = 'https://api.openai.com/v1/chat/completions';
        
        // Build enhanced SEO prompt using custom prompt system
        $prompt = aiopms_build_ai_prompt( $business_type, $business_details, $seo_keywords, $target_audience, 'standard' );
        
        // If no custom prompt, use the original detailed prompt
        if ( empty( get_option( 'aiopms_custom_prompt', '' ) ) ) {
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
        }

        $body = json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.5,
            'max_tokens' => 400,
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
            'timeout' => 30,
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

        // Cache the results for 24 hours
        aiopms_cache_set($cache_key, $pages, 'ai_suggestions', AIOPMS_CACHE_EXPIRY * 24);

        return $pages;

    } catch (Exception $e) {
        error_log('AIOPMS OpenAI API Error: ' . $e->getMessage());
        return [];
    }
}

// Get page suggestions from Gemini API
function aiopms_get_gemini_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    try {
        // Input validation
        if (empty($api_key) || empty($business_type) || empty($business_details)) {
            throw new Exception(__('Missing required parameters for Gemini API call.', 'aiopms'));
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key;
        
        // Build enhanced SEO prompt using custom prompt system
        $prompt = aiopms_build_ai_prompt( $business_type, $business_details, $seo_keywords, $target_audience, 'standard' );
        
        // If no custom prompt, use the original detailed prompt
        if ( empty( get_option( 'aiopms_custom_prompt', '' ) ) ) {
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
        }

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
function aiopms_get_deepseek_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    try {
        // Input validation
        if (empty($api_key) || empty($business_type) || empty($business_details)) {
            throw new Exception(__('Missing required parameters for DeepSeek API call.', 'aiopms'));
        }

        $url = 'https://api.deepseek.com/v1/chat/completions';
        
        // Build enhanced SEO prompt using custom prompt system
        $prompt = aiopms_build_ai_prompt( $business_type, $business_details, $seo_keywords, $target_audience, 'standard' );
        
        // If no custom prompt, use the original detailed prompt
        if ( empty( get_option( 'aiopms_custom_prompt', '' ) ) ) {
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
        }

        $body = json_encode([
            'model' => 'deepseek-chat',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.5,
            'max_tokens' => 400,
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
function aiopms_create_suggested_pages($pages, $generate_images = false) {
    try {
        // Input validation
        if (empty($pages) || !is_array($pages)) {
            echo '<div class="notice notice-error"><p>' . __('No pages provided for creation.', 'aiopms') . '</p></div>';
            return;
        }

        // Check memory before starting
        if (!aiopms_check_memory_limit(75)) {
            echo '<div class="notice notice-error"><p>' . __('Insufficient memory available for page creation. Please try with fewer pages.', 'aiopms') . '</p></div>';
            return;
        }

        $created_count = 0;
        $failed_count = 0;
        $parent_id_stack = [];
        $errors = [];
        $pages_to_create = [];

        // Prepare pages for batch processing
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
                
                $pages_to_create[] = array(
                    'post_title'   => $page_title,
                    'post_name'    => $post_name,
                    'post_content' => '',
                    'post_status'  => 'draft',
                    'post_type'    => 'page',
                    'post_parent'  => $parent_id,
                    'post_excerpt' => $excerpt,
                    'page_line'    => $page_line,
                    'depth'        => $depth
                );

            } catch (Exception $e) {
                $failed_count++;
                $errors[] = sprintf(__('Error processing page "%s": %s', 'aiopms'), $page_line, $e->getMessage());
            }
        }

        // Use batch processing if available
        if (class_exists('AIOPMS_DB_Optimizer') && count($pages_to_create) > 10) {
            $db_optimizer = AIOPMS_DB_Optimizer::get_instance();
            $batch_result = $db_optimizer->batch_insert_posts($pages_to_create, AIOPMS_BATCH_SIZE);
            
            $created_count = $batch_result['inserted'];
            $errors = array_merge($errors, $batch_result['errors']);
            $failed_count = count($pages_to_create) - $created_count;
        } else {
            // Process pages individually
            foreach ($pages_to_create as $page_data) {
                $page_id = wp_insert_post($page_data);
                
                if ($page_id && !is_wp_error($page_id)) {
                    $created_count++;
                    
                    // Generate and set featured image if enabled
                    if ($generate_images) {
                        try {
                            aiopms_generate_and_set_featured_image($page_id, $page_data['post_title']);
                        } catch (Exception $e) {
                            $errors[] = sprintf(__('Failed to generate image for "%s": %s', 'aiopms'), $page_data['post_title'], $e->getMessage());
                        }
                    }
                    
                    // Generate schema markup for the new page
                    $auto_generate = get_option('aiopms_auto_schema_generation', true);
                    if ($auto_generate) {
                        try {
                            aiopms_generate_schema_markup($page_id);
                        } catch (Exception $e) {
                            $errors[] = sprintf(__('Failed to generate schema for "%s": %s', 'aiopms'), $page_data['post_title'], $e->getMessage());
                        }
                    }
                } else {
                    $failed_count++;
                    $error_message = is_wp_error($page_id) ? $page_id->get_error_message() : __('Unknown error', 'aiopms');
                    $errors[] = sprintf(__('Failed to create page "%s": %s', 'aiopms'), $page_data['post_title'], $error_message);
                }
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
function aiopms_generate_and_set_featured_image($post_id, $page_title) {
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
                $image_url = aiopms_generate_openai_image($prompt, $api_key);
                break;
            case 'gemini':
                $image_url = aiopms_generate_gemini_image($prompt, $api_key);
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
function aiopms_generate_openai_image($prompt, $api_key) {
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
function aiopms_generate_gemini_image($prompt, $api_key) {
    // Note: As of current implementation, Gemini doesn't have a direct image generation API like DALL-E
    // This function is a placeholder for future implementation when Gemini releases image generation
    // For now, we'll return empty string to indicate no image generation
    return '';
}

// Process keywords from CSV file
function aiopms_process_keywords_csv($file) {
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
            
            // Additional security: Check for common patterns that might indicate invalid keys
            if (strlen($api_key) < 20 || strlen($api_key) > 200) {
                return false;
            }
            
            // Check for suspicious patterns
            if (preg_match('/^(test|demo|example|fake|dummy)/i', $api_key)) {
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
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key;
    
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
    // Use the custom prompt system for advanced mode
    return aiopms_build_ai_prompt( $business_type, $business_details, $seo_keywords, $target_audience, 'advanced' );
}

function aiopms_parse_advanced_ai_response($ai_response) {
    $parsed = [
        'pages' => [],
        'custom_post_types' => []
    ];
    
    // Try to parse JSON response
    $data = json_decode($ai_response, true);
    
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        // Handle standard pages
        if (isset($data['standard_pages']) && is_array($data['standard_pages'])) {
            foreach ($data['standard_pages'] as $page) {
                if (isset($page['title'])) {
                    $parsed['pages'][] = [
                        'title' => sanitize_text_field($page['title']),
                        'meta_description' => sanitize_text_field($page['meta_description'] ?? ''),
                        'reasoning' => sanitize_text_field($page['reasoning'] ?? ''),
                        'hierarchy_level' => intval($page['hierarchy_level'] ?? 0)
                    ];
                }
            }
        }
        
        // Handle custom post types
        if (isset($data['custom_post_types']) && is_array($data['custom_post_types'])) {
            foreach ($data['custom_post_types'] as $cpt) {
                if (isset($cpt['name']) && isset($cpt['label'])) {
                    $parsed['custom_post_types'][] = [
                        'name' => sanitize_text_field($cpt['name']),
                        'label' => sanitize_text_field($cpt['label']),
                        'description' => sanitize_text_field($cpt['description'] ?? ''),
                        'reasoning' => sanitize_text_field($cpt['reasoning'] ?? ''),
                        'custom_fields' => $cpt['custom_fields'] ?? [],
                        'sample_entries' => $cpt['sample_entries'] ?? []
                    ];
                }
            }
        }
    } else {
        // Fallback: try to parse as simple list format
        $lines = explode("\n", $ai_response);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, ':') === false) continue;
            
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $parsed['pages'][] = [
                    'title' => sanitize_text_field(trim($parts[0])),
                    'meta_description' => sanitize_text_field(trim($parts[1])),
                    'reasoning' => '',
                    'hierarchy_level' => 0
                ];
            }
        }
    }
    
    return $parsed;
}

// Display advanced content suggestions
function aiopms_display_advanced_content_suggestions($suggestions) {
    echo '<div class="aiopms-advanced-suggestions">';
    
    // Display business analysis if available
    if (!empty($suggestions['business_analysis'])) {
        echo '<div class="aiopms-business-analysis">';
        echo '<h3>📊 Business Analysis</h3>';
        echo '<div class="analysis-content">';
        if (isset($suggestions['business_analysis']['business_model'])) {
            echo '<p><strong>Business Model:</strong> ' . esc_html($suggestions['business_analysis']['business_model']) . '</p>';
        }
        if (isset($suggestions['business_analysis']['content_needs'])) {
            echo '<p><strong>Content Needs:</strong> ' . esc_html($suggestions['business_analysis']['content_needs']) . '</p>';
        }
        if (isset($suggestions['business_analysis']['target_audience_insights'])) {
            echo '<p><strong>Target Audience Insights:</strong> ' . esc_html($suggestions['business_analysis']['target_audience_insights']) . '</p>';
        }
        echo '</div>';
        echo '</div>';
    }

    echo '<form method="post" action="">';
    wp_nonce_field('aiopms_create_advanced_content');
    echo '<input type="hidden" name="action" value="create_advanced_content">';

    // Display standard pages
    if (!empty($suggestions['pages'])) {
        echo '<div class="aiopms-pages-section">';
        echo '<h3>📄 Suggested Standard Pages</h3>';
        echo '<table class="widefat striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th width="20px"><input type="checkbox" id="select-all-pages" checked></th>';
        echo '<th>Page Title</th>';
        echo '<th>Meta Description</th>';
        echo '<th>Reasoning</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($suggestions['pages'] as $page) {
            echo '<tr>';
            echo '<td><input type="checkbox" name="aiopms_selected_pages[]" value="' . esc_attr(json_encode($page)) . '" checked></td>';
            echo '<td>' . esc_html($page['title']) . '</td>';
            echo '<td>' . esc_html($page['meta_description']) . '</td>';
            echo '<td>' . esc_html($page['reasoning']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    // Display custom post types
    if (!empty($suggestions['custom_post_types'])) {
        echo '<div class="aiopms-cpts-section">';
        echo '<h3>🏗️ Suggested Custom Post Types</h3>';
        
        foreach ($suggestions['custom_post_types'] as $cpt) {
            echo '<div class="cpt-suggestion">';
            echo '<div class="cpt-header">';
            echo '<label>';
            echo '<input type="checkbox" name="aiopms_selected_cpts[]" value="' . esc_attr(json_encode($cpt)) . '" checked>';
            echo '<strong>' . esc_html($cpt['label']) . '</strong> (' . esc_html($cpt['name']) . ')';
            echo '</label>';
            echo '</div>';
            echo '<div class="cpt-details">';
            echo '<p><strong>Description:</strong> ' . esc_html($cpt['description']) . '</p>';
            echo '<p><strong>Why this is valuable:</strong> ' . esc_html($cpt['reasoning']) . '</p>';
            
            if (!empty($cpt['custom_fields'])) {
                echo '<div class="custom-fields">';
                echo '<h4>Custom Fields:</h4>';
                echo '<ul>';
                foreach ($cpt['custom_fields'] as $field) {
                    echo '<li><strong>' . esc_html($field['label']) . '</strong> (' . esc_html($field['type']) . ') - ' . esc_html($field['description']);
                    if ($field['required']) {
                        echo ' <span class="required">*Required</span>';
                    }
                    echo '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            if (!empty($cpt['sample_entries'])) {
                echo '<div class="sample-entries">';
                echo '<h4>Sample Entries:</h4>';
                echo '<ul>';
                foreach ($cpt['sample_entries'] as $entry) {
                    echo '<li><strong>' . esc_html($entry['title']) . '</strong> - ' . esc_html($entry['content']) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }

    // Add image generation checkbox
    $provider = get_option('aiopms_ai_provider', 'openai');
    $is_deepseek = $provider === 'deepseek';
    
    echo '<div class="aiopms-options">';
    echo '<p>';
    echo '<input type="checkbox" name="aiopms_generate_images" id="aiopms_generate_images" value="1" ' . checked(true, !$is_deepseek, false) . '>';
    echo '<label for="aiopms_generate_images"> Generate featured images with AI</label>';
    
    if ($is_deepseek) {
        echo ' <span class="description" style="color: #d63638;">(Image generation not supported with DeepSeek)</span>';
    }
    echo '</p>';
    echo '</div>';

    submit_button('Create Selected Content');
    echo '</form>';

    // Add JavaScript for select all functionality
    echo '<script>
    jQuery(document).ready(function($) {
        $("#select-all-pages").on("change", function() {
            $("input[name=\'aiopms_selected_pages[]\']").prop("checked", $(this).prop("checked"));
        });
    });
    </script>';

    echo '</div>';

    // Add CSS for the advanced suggestions display
    echo '<style>
    .aiopms-advanced-suggestions {
        margin: 20px 0;
    }
    
    .aiopms-business-analysis {
        background: #f0f6fc;
        padding: 20px;
        border-left: 4px solid #2271b1;
        margin-bottom: 30px;
        border-radius: 0 4px 4px 0;
    }
    
    .aiopms-business-analysis h3 {
        margin: 0 0 15px 0;
        color: #1d2327;
    }
    
    .analysis-content p {
        margin: 10px 0;
        line-height: 1.6;
    }
    
    .aiopms-pages-section,
    .aiopms-cpts-section {
        margin: 30px 0;
    }
    
    .aiopms-pages-section h3,
    .aiopms-cpts-section h3 {
        color: #2271b1;
        border-bottom: 2px solid #2271b1;
        padding-bottom: 8px;
    }
    
    .cpt-suggestion {
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 6px;
        padding: 20px;
        margin: 15px 0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .cpt-header {
        margin-bottom: 15px;
    }
    
    .cpt-header label {
        font-size: 16px;
        cursor: pointer;
    }
    
    .cpt-details p {
        margin: 10px 0;
        line-height: 1.6;
    }
    
    .custom-fields,
    .sample-entries {
        margin: 15px 0;
    }
    
    .custom-fields h4,
    .sample-entries h4 {
        margin: 0 0 10px 0;
        color: #1d2327;
        font-size: 14px;
    }
    
    .custom-fields ul,
    .sample-entries ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .custom-fields li,
    .sample-entries li {
        margin: 8px 0;
        line-height: 1.5;
    }
    
    .required {
        color: #d63638;
        font-weight: bold;
    }
    
    .aiopms-options {
        margin: 20px 0;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 4px;
    }
    </style>';
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
                aiopms_generate_and_set_featured_image($page_id, $page_title);
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

/**
 * Build AI prompt with custom prompt support.
 * 
 * @since 3.0
 * @param string $business_type
 * @param string $business_details
 * @param string $seo_keywords
 * @param string $target_audience
 * @param string $prompt_type 'standard' or 'advanced'
 * @return string
 */
function aiopms_build_ai_prompt( $business_type, $business_details, $seo_keywords, $target_audience, $prompt_type = 'standard' ) {
    // Get custom prompt or use default
    $custom_prompt = get_option( 'aiopms_custom_prompt', '' );
    $default_prompt = aiopms_get_default_ai_prompt();
    $prompt_template = !empty( $custom_prompt ) ? $custom_prompt : $default_prompt;
    
    // Replace variables in the prompt
    $prompt = str_replace( '{business_type}', $business_type, $prompt_template );
    $prompt = str_replace( '{business_details}', $business_details, $prompt );
    $prompt = str_replace( '{seo_keywords}', $seo_keywords, $prompt );
    $prompt = str_replace( '{target_audience}', $target_audience, $prompt );
    $prompt = str_replace( '{current_date}', current_time( 'F j, Y' ), $prompt );
    
    // If using custom prompt, we need to add the specific format requirements
    if ( !empty( $custom_prompt ) ) {
        if ( $prompt_type === 'standard' ) {
            $prompt .= "\n\n## OUTPUT FORMAT
Generate a comprehensive list of essential website pages. For each page, provide:
1. Page Title (use hyphens '-' for nesting child pages to indicate hierarchy)
2. SEO-optimized Meta Description (separated by ':+' from the title)

Return the response as a simple list format:
Page Title 1:+Meta Description 1
Page Title 2:+Meta Description 2
...";
        } else {
            $prompt .= "\n\n## OUTPUT FORMAT
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
}";
        }
    }
    
    return $prompt;
}
