<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Auto-detect business information from WordPress
 * Scans WP core settings, pages, WooCommerce, and SEO plugins
 * @return array Detected business information
 */
function aiopms_auto_detect_business_info() {
    $detected = [
        'name' => '',
        'description' => '',
        'address' => '',
        'phone' => '',
        'email' => '',
        'facebook' => '',
        'twitter' => '',
        'linkedin' => '',
    ];

    // 1. WordPress Core
    $detected['name'] = get_bloginfo('name');
    $detected['description'] = get_bloginfo('description');
    $detected['email'] = get_option('admin_email', '');

    // 2. Check WooCommerce settings if active
    if (class_exists('WooCommerce')) {
        $wc_address = get_option('woocommerce_store_address', '');
        $wc_address_2 = get_option('woocommerce_store_address_2', '');
        $wc_city = get_option('woocommerce_store_city', '');
        $wc_postcode = get_option('woocommerce_store_postcode', '');
        
        $address_parts = array_filter([$wc_address, $wc_address_2, $wc_city, $wc_postcode]);
        if (!empty($address_parts)) {
            $detected['address'] = implode(', ', $address_parts);
        }
    }

    // 3. Check Yoast SEO if active
    if (defined('WPSEO_VERSION')) {
        $yoast_social = get_option('wpseo_social', []);
        if (!empty($yoast_social['facebook_site'])) {
            $detected['facebook'] = $yoast_social['facebook_site'];
        }
        if (!empty($yoast_social['twitter_site'])) {
            $detected['twitter'] = 'https://twitter.com/' . ltrim($yoast_social['twitter_site'], '@');
        }
        if (!empty($yoast_social['linkedin_url'])) {
            $detected['linkedin'] = $yoast_social['linkedin_url'];
        }
        // Yoast company name
        $yoast_titles = get_option('wpseo_titles', []);
        if (!empty($yoast_titles['company_name'])) {
            $detected['name'] = $yoast_titles['company_name'];
        }
    }

    // 4. Check RankMath SEO if active
    if (class_exists('RankMath')) {
        $rm_local = get_option('rank-math-options-titles', []);
        if (!empty($rm_local['local_business_name'])) {
            $detected['name'] = $rm_local['local_business_name'];
        }
        if (!empty($rm_local['phone'])) {
            $detected['phone'] = $rm_local['phone'];
        }
        if (!empty($rm_local['local_address'])) {
            $detected['address'] = $rm_local['local_address'];
        }
    }

    // 5. Scan Contact/About pages for phone, email, address
    $pages_to_scan = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'numberposts' => 20,
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ]);

    foreach ($pages_to_scan as $page) {
        $title_lower = strtolower($page->post_title);
        $slug_lower = strtolower($page->post_name);
        
        // Look for contact or about pages
        if (strpos($title_lower, 'contact') !== false || strpos($slug_lower, 'contact') !== false ||
            strpos($title_lower, 'about') !== false || strpos($slug_lower, 'about') !== false) {
            
            $content = wp_strip_all_tags($page->post_content);
            
            // Extract phone (if not already found)
            if (empty($detected['phone'])) {
                if (preg_match('/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $content, $phone_match)) {
                    $detected['phone'] = trim($phone_match[0]);
                }
            }
            
            // Extract address patterns (if not already found)
            if (empty($detected['address'])) {
                // Look for common address patterns
                if (preg_match('/\d+\s+[\w\s]+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Drive|Dr|Lane|Ln)[,.\s]+[\w\s]+[,.\s]+[A-Z]{2}\s+\d{5}/i', $content, $addr_match)) {
                    $detected['address'] = trim($addr_match[0]);
                }
            }
            
            // Extract better description from About page
            if ((strpos($title_lower, 'about') !== false || strpos($slug_lower, 'about') !== false) && empty($detected['description'])) {
                $excerpt = wp_trim_words($content, 50, '...');
                if (strlen($excerpt) > 50) {
                    $detected['description'] = $excerpt;
                }
            }
        }
    }

    // 6. Scan menus for social links
    $nav_menus = wp_get_nav_menus();
    foreach ($nav_menus as $menu) {
        $menu_items = wp_get_nav_menu_items($menu->term_id);
        if ($menu_items) {
            foreach ($menu_items as $item) {
                $url = strtolower($item->url);
                if (empty($detected['facebook']) && strpos($url, 'facebook.com') !== false) {
                    $detected['facebook'] = $item->url;
                }
                if (empty($detected['twitter']) && (strpos($url, 'twitter.com') !== false || strpos($url, 'x.com') !== false)) {
                    $detected['twitter'] = $item->url;
                }
                if (empty($detected['linkedin']) && strpos($url, 'linkedin.com') !== false) {
                    $detected['linkedin'] = $item->url;
                }
            }
        }
    }

    return $detected;
}

/**
 * AJAX handler for re-scanning website
 */
function aiopms_ajax_rescan_business_info() {
    check_ajax_referer('aiopms_rescan_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    $detected = aiopms_auto_detect_business_info();
    
    // Save detected values to options
    update_option('aiopms_business_name', $detected['name']);
    update_option('aiopms_business_description', $detected['description']);
    update_option('aiopms_business_address', $detected['address']);
    update_option('aiopms_business_phone', $detected['phone']);
    update_option('aiopms_business_email', $detected['email']);
    update_option('aiopms_business_social_facebook', $detected['facebook']);
    update_option('aiopms_business_social_twitter', $detected['twitter']);
    update_option('aiopms_business_social_linkedin', $detected['linkedin']);
    
    wp_send_json_success([
        'message' => __('Business information detected and saved!', 'aiopms'),
        'data' => $detected
    ]);
}
add_action('wp_ajax_aiopms_rescan_business_info', 'aiopms_ajax_rescan_business_info');

/**
 * Auto-detect on plugin activation (if settings are empty)
 */
function aiopms_maybe_auto_detect_on_load() {
    // Only run once if business name is empty
    if (empty(get_option('aiopms_business_name', ''))) {
        $detected = aiopms_auto_detect_business_info();
        
        // Only save if we actually detected something
        if (!empty($detected['name'])) {
            update_option('aiopms_business_name', $detected['name']);
        }
        if (!empty($detected['description'])) {
            update_option('aiopms_business_description', $detected['description']);
        }
        if (!empty($detected['address'])) {
            update_option('aiopms_business_address', $detected['address']);
        }
        if (!empty($detected['phone'])) {
            update_option('aiopms_business_phone', $detected['phone']);
        }
        if (!empty($detected['email'])) {
            update_option('aiopms_business_email', $detected['email']);
        }
        if (!empty($detected['facebook'])) {
            update_option('aiopms_business_social_facebook', $detected['facebook']);
        }
        if (!empty($detected['twitter'])) {
            update_option('aiopms_business_social_twitter', $detected['twitter']);
        }
        if (!empty($detected['linkedin'])) {
            update_option('aiopms_business_social_linkedin', $detected['linkedin']);
        }
    }
}
add_action('admin_init', 'aiopms_maybe_auto_detect_on_load');


// Register settings with sanitization callbacks
function aiopms_register_settings() {
    register_setting('aiopms_settings_group', 'aiopms_ai_provider', 'sanitize_key');
    register_setting('aiopms_settings_group', 'aiopms_openai_api_key', 'sanitize_text_field');
    register_setting('aiopms_settings_group', 'aiopms_gemini_api_key', 'sanitize_text_field');
    register_setting('aiopms_settings_group', 'aiopms_deepseek_api_key', 'sanitize_text_field');
    register_setting('aiopms_settings_group', 'aiopms_brand_color', 'sanitize_hex_color');
    register_setting('aiopms_settings_group', 'aiopms_sitemap_url', 'esc_url_raw');
    register_setting('aiopms_settings_group', 'aiopms_auto_schema_generation', 'absint');
    
    // Business Information Settings
    register_setting('aiopms_settings_group', 'aiopms_business_name', 'sanitize_text_field');
    register_setting('aiopms_settings_group', 'aiopms_business_description', 'sanitize_textarea_field');
    register_setting('aiopms_settings_group', 'aiopms_business_address', 'sanitize_textarea_field');
    register_setting('aiopms_settings_group', 'aiopms_business_phone', 'sanitize_text_field');
    register_setting('aiopms_settings_group', 'aiopms_business_email', 'sanitize_email');
    register_setting('aiopms_settings_group', 'aiopms_business_social_facebook', 'esc_url_raw');
    register_setting('aiopms_settings_group', 'aiopms_business_social_twitter', 'esc_url_raw');
    register_setting('aiopms_settings_group', 'aiopms_business_social_linkedin', 'esc_url_raw');
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
        __('AI Settings', 'aiopms'),
        'aiopms_settings_section_callback',
        'aiopms-page-management'
    );

    add_settings_field(
        'aiopms_ai_provider',
        __('AI Provider', 'aiopms'),
        'aiopms_ai_provider_callback',
        'aiopms-page-management',
        'aiopms_settings_section'
    );

    add_settings_field(
        'aiopms_openai_api_key',
        __('OpenAI API Key', 'aiopms'),
        'aiopms_openai_api_key_callback',
        'aiopms-page-management',
        'aiopms_settings_section'
    );

    add_settings_field(
        'aiopms_gemini_api_key',
        __('Gemini API Key', 'aiopms'),
        'aiopms_gemini_api_key_callback',
        'aiopms-page-management',
        'aiopms_settings_section'
    );

    add_settings_field(
        'aiopms_deepseek_api_key',
        __('DeepSeek API Key', 'aiopms'),
        'aiopms_deepseek_api_key_callback',
        'aiopms-page-management',
        'aiopms_settings_section'
    );

    add_settings_field(
        'aiopms_brand_color',
        __('Brand Color', 'aiopms'),
        'aiopms_brand_color_callback',
        'aiopms-page-management',
        'aiopms_settings_section'
    );

    add_settings_field(
        'aiopms_sitemap_url',
        __('Sitemap URL', 'aiopms'),
        'aiopms_sitemap_url_callback',
        'aiopms-page-management',
        'aiopms_settings_section'
    );

    // Schema settings section
    add_settings_section(
        'aiopms_schema_settings_section',
        __('Schema Settings', 'aiopms'),
        'aiopms_schema_settings_section_callback',
        'aiopms-page-management'
    );

    add_settings_field(
        'aiopms_auto_schema_generation',
        __('Auto Schema Generation', 'aiopms'),
        'aiopms_auto_schema_generation_callback',
        'aiopms-page-management',
        'aiopms_schema_settings_section'
    );

    // Business Information Section
    add_settings_section(
        'aiopms_business_settings_section',
        __('Business Information (Knowledge Base)', 'aiopms'),
        'aiopms_business_settings_section_callback',
        'aiopms-page-management'
    );

    add_settings_field('aiopms_business_name', __('Business Name', 'aiopms'), 'aiopms_business_name_callback', 'aiopms-page-management', 'aiopms_business_settings_section');
    add_settings_field('aiopms_business_description', __('Business Description / Knowledge', 'aiopms'), 'aiopms_business_description_callback', 'aiopms-page-management', 'aiopms_business_settings_section');
    add_settings_field('aiopms_business_address', __('Business Address', 'aiopms'), 'aiopms_business_address_callback', 'aiopms-page-management', 'aiopms_business_settings_section');
    add_settings_field('aiopms_business_phone', __('Phone Number', 'aiopms'), 'aiopms_business_phone_callback', 'aiopms-page-management', 'aiopms_business_settings_section');
    add_settings_field('aiopms_business_email', __('Email Address', 'aiopms'), 'aiopms_business_email_callback', 'aiopms-page-management', 'aiopms_business_settings_section');
    add_settings_field('aiopms_business_social', __('Social Media Links', 'aiopms'), 'aiopms_business_social_callback', 'aiopms-page-management', 'aiopms_business_settings_section');
}
add_action('admin_init', 'aiopms_settings_init');

// Section callback
function aiopms_settings_section_callback() {
    echo '<p>' . __('Select your preferred AI provider and enter the corresponding API key. Set your brand color for AI-generated featured images and configure the sitemap URL for menu generation.', 'aiopms') . '</p>';
}

// AI Provider field callback
function aiopms_ai_provider_callback() {
    $provider = get_option('aiopms_ai_provider', 'openai');
    ?>
    <select name="aiopms_ai_provider" class="aiopms-ai-provider-select">
        <option value="openai" <?php selected($provider, 'openai'); ?>>🤖 OpenAI (GPT-4)</option>
        <option value="gemini" <?php selected($provider, 'gemini'); ?>>🧠 Google Gemini</option>
        <option value="deepseek" <?php selected($provider, 'deepseek'); ?>>⚡ DeepSeek</option>
    </select>
    <p class="description"><?php esc_html_e('Choose your preferred AI provider. Each has different strengths and pricing models.', 'aiopms'); ?></p>
    <?php
}

// OpenAI API Key field callback
function aiopms_openai_api_key_callback() {
    $api_key = get_option('aiopms_openai_api_key');
    echo '<input type="password" name="aiopms_openai_api_key" value="' . esc_attr($api_key) . '" class="regular-text" autocomplete="off">';
}

// Gemini API Key field callback
function aiopms_gemini_api_key_callback() {
    $api_key = get_option('aiopms_gemini_api_key');
    echo '<input type="password" name="aiopms_gemini_api_key" value="' . esc_attr($api_key) . '" class="regular-text" autocomplete="off">';
}

// DeepSeek API Key field callback
function aiopms_deepseek_api_key_callback() {
    $api_key = get_option('aiopms_deepseek_api_key');
    echo '<input type="password" name="aiopms_deepseek_api_key" value="' . esc_attr($api_key) . '" class="regular-text" autocomplete="off">';
}

// Brand Color field callback
function aiopms_brand_color_callback() {
    $brand_color = get_option('aiopms_brand_color', '#b47cfd');
    ?>
    <input type="color" name="aiopms_brand_color" value="<?php echo esc_attr($brand_color); ?>" class="regular-text">
    <p class="description"><?php _e('Select your brand\'s primary color. This will be used for AI-generated featured images.', 'aiopms'); ?></p>
    <?php
}

// Sitemap URL field callback
function aiopms_sitemap_url_callback() {
    $sitemap_url = get_option('aiopms_sitemap_url', '');
    ?>
    <input type="url" name="aiopms_sitemap_url" value="<?php echo esc_attr($sitemap_url); ?>" class="regular-text" placeholder="https://yoursite.com/sitemap.xml">
    <p class="description"><?php esc_html_e('Enter the URL of your sitemap page. This will be used in the universal bottom menu.', 'aiopms'); ?></p>
    <?php
}

// Schema settings section callback
function aiopms_schema_settings_section_callback() {
    echo '<p>' . esc_html__('Configure schema.org markup generation settings for your pages.', 'aiopms') . '</p>';
}

// Auto Schema Generation field callback
function aiopms_auto_schema_generation_callback() {
    $auto_generate = get_option('aiopms_auto_schema_generation', true);
    ?>
    <label>
        <input type="checkbox" name="aiopms_auto_schema_generation" value="1" <?php checked($auto_generate, true); ?>>
        <?php esc_html_e('Automatically generate schema markup when pages are created or updated', 'aiopms'); ?>
    </label>
    <p class="description"><?php esc_html_e('When enabled, schema markup will be automatically generated for all pages when they are saved.', 'aiopms'); ?></p>
    <?php
}

// Business Information Section Callback - with Re-Scan button
function aiopms_business_settings_section_callback() {
    $nonce = wp_create_nonce('aiopms_rescan_nonce');
    ?>
    <p><?php esc_html_e('Your business details are auto-detected from WordPress. This information is used to generate accurate, context-aware schema markup.', 'aiopms'); ?></p>
    <p>
        <button type="button" id="aiopms-rescan-btn" class="button button-secondary">
            🔍 <?php esc_html_e('Re-Scan Website', 'aiopms'); ?>
        </button>
        <span id="aiopms-rescan-status" style="margin-left: 10px;"></span>
    </p>
    <script>
    jQuery(document).ready(function($) {
        $('#aiopms-rescan-btn').on('click', function() {
            var $btn = $(this);
            var $status = $('#aiopms-rescan-status');
            
            $btn.prop('disabled', true).text('⏳ Scanning...');
            $status.text('');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiopms_rescan_business_info',
                    nonce: '<?php echo esc_js($nonce); ?>'
                },
                success: function(response) {
                    $btn.prop('disabled', false).html('🔍 <?php echo esc_js(__('Re-Scan Website', 'aiopms')); ?>');
                    if (response.success) {
                        $status.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                        // Update form fields with detected values
                        var data = response.data.data;
                        $('input[name="aiopms_business_name"]').val(data.name);
                        $('textarea[name="aiopms_business_description"]').val(data.description);
                        $('textarea[name="aiopms_business_address"]').val(data.address);
                        $('input[name="aiopms_business_phone"]').val(data.phone);
                        $('input[name="aiopms_business_email"]').val(data.email);
                        $('input[name="aiopms_business_social_facebook"]').val(data.facebook);
                        $('input[name="aiopms_business_social_twitter"]').val(data.twitter);
                        $('input[name="aiopms_business_social_linkedin"]').val(data.linkedin);
                    } else {
                        $status.html('<span style="color: red;">✗ Error: ' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).html('🔍 <?php echo esc_js(__('Re-Scan Website', 'aiopms')); ?>');
                    $status.html('<span style="color: red;">✗ <?php echo esc_js(__('Network error. Please try again.', 'aiopms')); ?></span>');
                }
            });
        });
    });
    </script>
    <?php
}


// Business Name Callback
function aiopms_business_name_callback() {
    $value = get_option('aiopms_business_name', get_bloginfo('name'));
    ?>
    <input type="text" name="aiopms_business_name" value="<?php echo esc_attr($value); ?>" class="regular-text">
    <p class="description"><?php esc_html_e('Your official business or organization name.', 'aiopms'); ?></p>
    <?php
}

// Business Description / Knowledge Callback
function aiopms_business_description_callback() {
    $value = get_option('aiopms_business_description', '');
    ?>
    <textarea name="aiopms_business_description" rows="5" class="large-text"><?php echo esc_textarea($value); ?></textarea>
    <p class="description"><?php esc_html_e('A detailed description of your business, services, products, and unique value proposition. The AI uses this as a knowledge base to generate more accurate schema.', 'aiopms'); ?></p>
    <?php
}

// Business Address Callback
function aiopms_business_address_callback() {
    $value = get_option('aiopms_business_address', '');
    ?>
    <textarea name="aiopms_business_address" rows="3" class="large-text" placeholder="123 Business Street, City, State, ZIP"><?php echo esc_textarea($value); ?></textarea>
    <p class="description"><?php esc_html_e('Your physical business address (used for LocalBusiness schema).', 'aiopms'); ?></p>
    <?php
}

// Business Phone Callback
function aiopms_business_phone_callback() {
    $value = get_option('aiopms_business_phone', '');
    ?>
    <input type="tel" name="aiopms_business_phone" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="+1-555-123-4567">
    <p class="description"><?php esc_html_e('Your business phone number.', 'aiopms'); ?></p>
    <?php
}

// Business Email Callback
function aiopms_business_email_callback() {
    $value = get_option('aiopms_business_email', get_option('admin_email'));
    ?>
    <input type="email" name="aiopms_business_email" value="<?php echo esc_attr($value); ?>" class="regular-text">
    <p class="description"><?php esc_html_e('Your primary business email address.', 'aiopms'); ?></p>
    <?php
}

// Business Social Media Callback
function aiopms_business_social_callback() {
    $facebook = get_option('aiopms_business_social_facebook', '');
    $twitter = get_option('aiopms_business_social_twitter', '');
    $linkedin = get_option('aiopms_business_social_linkedin', '');
    ?>
    <p><label>Facebook: <input type="url" name="aiopms_business_social_facebook" value="<?php echo esc_attr($facebook); ?>" class="regular-text" placeholder="https://facebook.com/yourpage"></label></p>
    <p><label>Twitter/X: <input type="url" name="aiopms_business_social_twitter" value="<?php echo esc_attr($twitter); ?>" class="regular-text" placeholder="https://twitter.com/yourhandle"></label></p>
    <p><label>LinkedIn: <input type="url" name="aiopms_business_social_linkedin" value="<?php echo esc_attr($linkedin); ?>" class="regular-text" placeholder="https://linkedin.com/company/yourcompany"></label></p>
    <p class="description"><?php esc_html_e('Your social media profile links (used in Organization schema).', 'aiopms'); ?></p>
    <?php
}
