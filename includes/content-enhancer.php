<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * ArtitechCore AI Content Enhancer & Conversion Booster
 *
 * Production-grade implementation with:
 * - XSS-safe output everywhere
 * - Capability checks on all endpoints
 * - Conditional CSS loading (frontend performance)
 * - Deduplicated prompt builder
 * - Rate-limiting via transient locks
 * - Safe first-paragraph injection (no duplicate replacement)
 * - Proper mid-post CTA insertion
 * - Detailed API error messages
 */

// Hook to initialize
add_action('init', 'artitechcore_ce_init');

function artitechcore_ce_init() {
    $enabled = get_option('artitechcore_ce_enabled', 0);
    if (!$enabled) return;

    // Admin Hooks
    if (is_admin()) {
        add_action('add_meta_boxes', 'artitechcore_ce_add_meta_box');
        add_action('save_post', 'artitechcore_ce_save_meta_box');
        add_action('admin_enqueue_scripts', 'artitechcore_ce_admin_scripts');
        
        // AJAX
        add_action('wp_ajax_artitechcore_ce_generate', 'artitechcore_ce_ajax_handler');
    }

    // Public AJAX for Native CTA Form
    add_action('wp_ajax_artitechcore_ce_submit_cta', 'artitechcore_ce_native_cta_ajax_handler');
    add_action('wp_ajax_nopriv_artitechcore_ce_submit_cta', 'artitechcore_ce_native_cta_ajax_handler');

    if (!is_admin()) {
        // Frontend Hooks — conditional CSS/injection
        add_filter('the_content', 'artitechcore_ce_inject_content', 99);
    }
}

/**
 * Register Meta Box
 */
function artitechcore_ce_add_meta_box() {
    $supported_types = get_option('artitechcore_ce_post_types', ['post']);
    if (empty($supported_types) || !is_array($supported_types)) return;

    foreach ($supported_types as $pt) {
        add_meta_box(
            'artitechcore_ce_meta_box',
            '✨ ArtitechCore AI Content Enhancer',
            'artitechcore_ce_meta_box_html',
            $pt,
            'normal',
            'high'
        );
    }
}

function artitechcore_ce_admin_scripts($hook) {
    global $post;
    if ($hook == 'post-new.php' || $hook == 'post.php') {
        $supported_types = get_option('artitechcore_ce_post_types', ['post']);
        if ($post && is_array($supported_types) && in_array($post->post_type, $supported_types)) {
            wp_add_inline_style('wp-admin', '
                .artitechcore-ce-panel { background: #fff; border: 1px solid #ccd0d4; padding: 15px; border-radius: 4px; }
                .artitechcore-ce-field { margin-bottom: 20px; }
                .artitechcore-ce-field label { font-weight: bold; display: block; margin-bottom: 5px; }
                .artitechcore-ce-field textarea { width: 100%; min-height: 100px; }
                .artitechcore-ce-field input[type="text"] { width: 100%; }
                .artitechcore-ce-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; flex-wrap: wrap; gap: 10px; }
                .artitechcore-ce-badge { background: #b47cfd; color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
                .artitechcore-ce-warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px 15px; margin-bottom: 15px; font-size: 13px; }
            ');
        }
    }
}

/**
 * Meta Box HTML
 */
function artitechcore_ce_meta_box_html($post) {
    wp_nonce_field('artitechcore_ce_save_nonce', 'artitechcore_ce_nonce');

    $kt       = get_post_meta($post->ID, '_artitechcore_ce_key_takeaways', true);
    $conc     = get_post_meta($post->ID, '_artitechcore_ce_conclusion', true);
    $cta_head = get_post_meta($post->ID, '_artitechcore_ce_cta_heading', true);
    $cta_desc = get_post_meta($post->ID, '_artitechcore_ce_cta_desc', true);

    $kt_text = is_array($kt) ? implode("\n", $kt) : (string) $kt;

    // Check if API key is configured
    $provider = get_option('artitechcore_ai_provider', 'openai');
    $api_key = get_option('artitechcore_' . $provider . '_api_key');
    $has_api_key = !empty($api_key);
    ?>
    <div class="artitechcore-ce-panel">
        <?php if (!$has_api_key): ?>
        <div class="artitechcore-ce-warning">
            ⚠️ <strong>No API Key configured</strong> for <?php echo esc_html(ucfirst($provider)); ?>. 
            Please add your key in <a href="<?php echo esc_url(admin_url('admin.php?page=artitechcore-main&tab=settings')); ?>">ArtitechCore Settings</a> to use AI generation.
        </div>
        <?php endif; ?>

        <div class="artitechcore-ce-header">
            <div>
                <span class="artitechcore-ce-badge">AI Powered</span>
                <p style="margin: 5px 0 0 0; color: #666;">Generate enhancements individually or all at once.</p>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                <button type="button" class="button button-primary" data-ce-generate="all" <?php echo !$has_api_key ? 'disabled' : ''; ?>>✨ Generate All</button>
                <button type="button" class="button" data-ce-generate="kt" <?php echo !$has_api_key ? 'disabled' : ''; ?>>🎯 KT Only</button>
                <button type="button" class="button" data-ce-generate="conclusion" <?php echo !$has_api_key ? 'disabled' : ''; ?>>📝 Conclusion Only</button>
                <button type="button" class="button" data-ce-generate="cta" <?php echo !$has_api_key ? 'disabled' : ''; ?>>📢 CTA Only</button>
            </div>
        </div>

        <div id="artitechcore-ce-status" style="display:none; margin-bottom:15px; padding:10px; background:#f0f0f1; border-left:4px solid #b47cfd;">
            Generating content... Please wait.
        </div>

        <div class="artitechcore-ce-field">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <label>Key Takeaways (One per line)</label>
                <button type="button" class="button button-link-delete button-small" data-ce-clear="kt" title="Clear Key Takeaways">✕ Clear</button>
            </div>
            <textarea name="artitechcore_ce_key_takeaways" id="artitechcore_ce_kt_input"><?php echo esc_textarea($kt_text); ?></textarea>
            <p class="description">These bullet points will be displayed at the very top of the post, before the content.</p>
        </div>

        <div class="artitechcore-ce-field">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <label>Smart CTA Heading (Contextual)</label>
                <button type="button" class="button button-link-delete button-small" data-ce-clear="cta" title="Clear CTA fields">✕ Clear CTA</button>
            </div>
            <input type="text" name="artitechcore_ce_cta_heading" id="artitechcore_ce_cta_head_input" value="<?php echo esc_attr($cta_head); ?>">
            <p class="description">Inserted dynamically with your CTA form (shortcode or native).</p>
        </div>

        <div class="artitechcore-ce-field">
            <label>Smart CTA Description (Contextual)</label>
            <textarea name="artitechcore_ce_cta_desc" id="artitechcore_ce_cta_desc_input" style="min-height: 60px;"><?php echo esc_textarea($cta_desc); ?></textarea>
        </div>

        <div class="artitechcore-ce-field">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <label>Conclusion Paragraph</label>
                <button type="button" class="button button-link-delete button-small" data-ce-clear="conclusion" title="Clear Conclusion">✕ Clear</button>
            </div>
            <textarea name="artitechcore_ce_conclusion" id="artitechcore_ce_conc_input"><?php echo esc_textarea($conc); ?></textarea>
            <p class="description">Displayed at the very end of the post. If your post already has a conclusion, you can skip generating this.</p>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var _ceGenerating = false;
        
        // Individual generate buttons
        $('[data-ce-generate]').on('click', function(e) {
            e.preventDefault();
            if (_ceGenerating) return;
            
            var btn = $(this);
            var genType = btn.data('ce-generate'); // all, kt, conclusion, cta
            var status = $('#artitechcore-ce-status');
            var postId = <?php echo intval($post->ID); ?>;
            var editorContent = '';
            
            if (typeof wp !== 'undefined' && wp.data && wp.data.select("core/editor")) {
                editorContent = wp.data.select("core/editor").getEditedPostContent();
            } else if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
                editorContent = tinyMCE.activeEditor.getContent();
            } else {
                editorContent = $('#content').val();
            }

            if (!editorContent || editorContent.trim() === '') {
                alert('Please write some content in the post first so the AI has context!');
                return;
            }

            var typeLabels = {all: 'All Enhancements', kt: 'Key Takeaways', conclusion: 'Conclusion', cta: 'CTA'};
            _ceGenerating = true;
            $('[data-ce-generate]').prop('disabled', true);
            btn.text('⏳ Generating...');
            status.text('Generating ' + typeLabels[genType] + '... (Takes ~10-20 sec)').css('border-left-color', '#b47cfd').show();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'artitechcore_ce_generate',
                    nonce: '<?php echo wp_create_nonce("artitechcore_ce_ajax"); ?>',
                    post_id: postId,
                    content: editorContent,
                    generate_type: genType
                },
                success: function(res) {
                    _ceGenerating = false;
                    $('[data-ce-generate]').prop('disabled', false);
                    btn.text(btn.is('[data-ce-generate="all"]') ? '✨ Generate All' : (btn.is('[data-ce-generate="kt"]') ? '🎯 KT Only' : (btn.is('[data-ce-generate="conclusion"]') ? '📝 Conclusion Only' : '📢 CTA Only')));
                    
                    if (res.success && res.data) {
                        status.text('✓ ' + typeLabels[genType] + ' generated! Save the post to keep changes.').css('border-left-color', 'green');
                        
                        if (res.data.key_takeaways && Array.isArray(res.data.key_takeaways)) {
                            $('#artitechcore_ce_kt_input').val(res.data.key_takeaways.join("\n"));
                        }
                        if (res.data.conclusion) {
                            $('#artitechcore_ce_conc_input').val(res.data.conclusion);
                        }
                        if (res.data.cta_heading) {
                            $('#artitechcore_ce_cta_head_input').val(res.data.cta_heading);
                        }
                        if (res.data.cta_description) {
                            $('#artitechcore_ce_cta_desc_input').val(res.data.cta_description);
                        }
                    } else {
                        status.text('✗ Error: ' + (res.data || 'Unknown error')).css('border-left-color', 'red');
                    }
                },
                error: function(xhr) {
                    _ceGenerating = false;
                    $('[data-ce-generate]').prop('disabled', false);
                    btn.text(btn.is('[data-ce-generate="all"]') ? '✨ Generate All' : (btn.is('[data-ce-generate="kt"]') ? '🎯 KT Only' : (btn.is('[data-ce-generate="conclusion"]') ? '📝 Conclusion Only' : '📢 CTA Only')));
                    status.text('✗ Network or server error (HTTP ' + xhr.status + '). Please try again.').css('border-left-color', 'red');
                }
            });
        });

        // Clear buttons
        $('[data-ce-clear]').on('click', function(e) {
            e.preventDefault();
            var field = $(this).data('ce-clear');
            if (field === 'kt') {
                $('#artitechcore_ce_kt_input').val('');
            } else if (field === 'conclusion') {
                $('#artitechcore_ce_conc_input').val('');
            } else if (field === 'cta') {
                $('#artitechcore_ce_cta_head_input').val('');
                $('#artitechcore_ce_cta_desc_input').val('');
            }
        });
    });
    </script>
    <?php
}

/**
 * Save Meta Box
 */
function artitechcore_ce_save_meta_box($post_id) {
    if (!isset($_POST['artitechcore_ce_nonce']) || !wp_verify_nonce($_POST['artitechcore_ce_nonce'], 'artitechcore_ce_save_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['artitechcore_ce_key_takeaways'])) {
        $kt_raw = explode("\n", sanitize_textarea_field($_POST['artitechcore_ce_key_takeaways']));
        $kt = array_values(array_filter(array_map('trim', $kt_raw)));
        update_post_meta($post_id, '_artitechcore_ce_key_takeaways', $kt);
    }
    
    if (isset($_POST['artitechcore_ce_conclusion'])) {
        update_post_meta($post_id, '_artitechcore_ce_conclusion', sanitize_textarea_field($_POST['artitechcore_ce_conclusion']));
    }
    
    if (isset($_POST['artitechcore_ce_cta_heading'])) {
        update_post_meta($post_id, '_artitechcore_ce_cta_heading', sanitize_text_field($_POST['artitechcore_ce_cta_heading']));
    }
    
    if (isset($_POST['artitechcore_ce_cta_desc'])) {
        update_post_meta($post_id, '_artitechcore_ce_cta_desc', sanitize_textarea_field($_POST['artitechcore_ce_cta_desc']));
    }
}

/**
 * AJAX Handler with server-side rate limiting
 */
function artitechcore_ce_ajax_handler() {
    check_ajax_referer('artitechcore_ce_ajax', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    
    // Server-side rate limit: 1 generation per post per 10 seconds
    if ($post_id) {
        $lock_key = 'artitechcore_ce_lock_' . $post_id;
        if (get_transient($lock_key)) {
            wp_send_json_error('Please wait a few seconds before generating again.');
        }
        set_transient($lock_key, 1, 10);
    }

    $content = isset($_POST['content']) ? wp_unslash($_POST['content']) : '';
    $clean_content = wp_strip_all_tags(strip_shortcodes($content));
    $clean_content = wp_trim_words($clean_content, 3000, '');

    if (empty($clean_content)) {
        wp_send_json_error('Content is empty.');
    }

    $provider = get_option('artitechcore_ai_provider', 'openai');
    $api_key = get_option('artitechcore_' . $provider . '_api_key');
    
    if (empty($api_key)) {
        wp_send_json_error('API Key is missing for ' . ucfirst($provider) . '. Please check your ArtitechCore Settings.');
    }

    $generate_type = isset($_POST['generate_type']) ? sanitize_key($_POST['generate_type']) : 'all';
    if (!in_array($generate_type, ['all', 'kt', 'conclusion', 'cta'], true)) {
        $generate_type = 'all';
    }

    $prompt = artitechcore_ce_build_prompt($clean_content, $generate_type);
    $json_result = null;

    try {
        if ($provider === 'openai') {
            $json_result = artitechcore_ce_call_openai($prompt, $api_key);
        } elseif ($provider === 'gemini') {
            $json_result = artitechcore_ce_call_gemini($prompt, $api_key);
        } elseif ($provider === 'deepseek') {
            $json_result = artitechcore_ce_call_deepseek($prompt, $api_key);
        } else {
            wp_send_json_error('Invalid AI provider.');
        }

        if (empty($json_result)) {
            wp_send_json_error('AI returned invalid data format. Please try again.');
        }

        wp_send_json_success($json_result);
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

/**
 * Shared prompt builder (FIX #6: single source of truth)
 */
function artitechcore_ce_build_prompt($clean_content, $generate_type = 'all') {
    $base = "You are an expert conversion copywriter and content strategist.\n\n";
    $footer = "\n\nReturn ONLY valid JSON. No markdown blocking like ```json.\n\nPost Content:\n" . $clean_content;

    switch ($generate_type) {
        case 'kt':
            return $base
                . "Task: Analyze the following blog post and generate JSON.\n"
                . "Generate ONLY: 'key_takeaways' — an array of 3 to 5 very concise, punchy bullet points summarizing the core value.\n"
                . "Return JSON with only the key 'key_takeaways'."
                . $footer;
        case 'conclusion':
            return $base
                . "Task: Analyze the following blog post and generate JSON.\n"
                . "Generate ONLY: 'conclusion' — a smart, engaging concluding wrap-up paragraph.\n"
                . "Return JSON with only the key 'conclusion'."
                . $footer;
        case 'cta':
            return $base
                . "Task: Analyze the post and generate a HIGHLY CONCISE Call To Action.\n"
                . "Generate ONLY: 'cta_heading' (A punchy heading, max 7 words) and 'cta_description' (ONE short sentence, max 15 words).\n"
                . "Return JSON with only the keys 'cta_heading' and 'cta_description'."
                . $footer;
        default: // 'all'
            return $base
                . "Task: Analyze the blog post and generate JSON formatted enhancements.\n"
                . "1. 'key_takeaways': 3 to 5 punchy bullet points.\n"
                . "2. 'conclusion': A short concluding paragraph.\n"
                . "3. 'cta_heading': A HIGHLY CONCISE CTA heading (max 7 words).\n"
                . "4. 'cta_description': ONE short, high-conversion sentence (max 15 words)."
                . $footer;
    }
}

/**
 * API Callers — with detailed error messages (FIX #8)
 */
function artitechcore_ce_call_openai($prompt, $api_key) {
    $url = 'https://api.openai.com/v1/chat/completions';
    $body = json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'response_format' => ['type' => 'json_object'],
        'temperature' => 0.5,
    ]);

    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key],
        'body' => $body, 'timeout' => 60
    ]);
    if (is_wp_error($response)) throw new Exception('OpenAI: ' . $response->get_error_message());
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($status_code !== 200) {
        $err_msg = isset($data['error']['message']) ? $data['error']['message'] : 'HTTP ' . $status_code;
        throw new Exception('OpenAI Error: ' . $err_msg);
    }

    if (!isset($data['choices'][0]['message']['content'])) {
        throw new Exception('OpenAI: Unexpected response structure.');
    }
    
    return json_decode($data['choices'][0]['message']['content'], true);
}

function artitechcore_ce_call_gemini($prompt, $api_key) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $api_key;
    $prompt .= "\n\nYou must return a bare JSON object matching the requested keys.";
    $body = json_encode(['contents' => [['parts' => [['text' => $prompt]]]]]);

    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => $body, 'timeout' => 60
    ]);
    if (is_wp_error($response)) throw new Exception('Gemini: ' . $response->get_error_message());
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($status_code !== 200) {
        $err_msg = isset($data['error']['message']) ? $data['error']['message'] : 'HTTP ' . $status_code;
        throw new Exception('Gemini Error: ' . $err_msg);
    }

    if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception('Gemini: Unexpected response structure.');
    }
    
    $text = $data['candidates'][0]['content']['parts'][0]['text'];
    $text = preg_replace('/```json\s*|\s*```/', '', $text);
    return json_decode(trim($text), true);
}

function artitechcore_ce_call_deepseek($prompt, $api_key) {
    $url = 'https://api.deepseek.com/v1/chat/completions';
    $body = json_encode([
        'model' => 'deepseek-chat',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'response_format' => ['type' => 'json_object'],
        'temperature' => 0.5,
    ]);

    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key],
        'body' => $body, 'timeout' => 60
    ]);
    if (is_wp_error($response)) throw new Exception('DeepSeek: ' . $response->get_error_message());
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($status_code !== 200) {
        $err_msg = isset($data['error']['message']) ? $data['error']['message'] : 'HTTP ' . $status_code;
        throw new Exception('DeepSeek Error: ' . $err_msg);
    }

    if (!isset($data['choices'][0]['message']['content'])) {
        throw new Exception('DeepSeek: Unexpected response structure.');
    }
    
    return json_decode($data['choices'][0]['message']['content'], true);
}

/**
 * Frontend Styling — only loaded when needed (FIX #5)
 */
function artitechcore_ce_enqueue_frontend_css() {
    static $enqueued = false;
    if ($enqueued) return;
    $enqueued = true;

    $brand_color = get_option('artitechcore_brand_color', '#b47cfd');
    
    $r = hexdec(substr($brand_color, 1, 2));
    $g = hexdec(substr($brand_color, 3, 2));
    $b = hexdec(substr($brand_color, 5, 2));

    $css = "
        :root {
            --artitechcore-brand: " . esc_attr($brand_color) . ";
            --brand-rgb: $r, $g, $b;
        }
        .artitechcore-ce-kt {
            background: #fdfdfd; 
            border-left: 4px solid " . esc_attr($brand_color) . "; 
            padding: 20px 25px; 
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            font-family: inherit;
        }
        .artitechcore-ce-kt-title {
            margin-top: 0;
            color: #121322;
            font-size: 1.25em;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .artitechcore-ce-kt ul {
            margin: 0;
            padding-left: 20px;
            list-style: disc;
        }
        .artitechcore-ce-kt li {
            margin-bottom: 8px;
            line-height: 1.5;
            color: #444;
        }
        .artitechcore-ce-conclusion {
            margin: 40px 0 20px 0;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .artitechcore-ce-conclusion h3 {
            font-size: 1.5em;
            margin-bottom: 15px;
        }

        .artitechcore-ce-cta-wrapper {
            background: #ffffff;
            border: 2px solid " . esc_attr($brand_color) . ";
            padding: 25px;
            margin: 35px 0;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            text-align: left;
            position: relative;
            overflow: hidden;
        }
        .artitechcore-ce-cta-wrapper::before {
            content: '';
            position: absolute;
            top: 0; left: 0; bottom: 0; width: 6px;
            background: " . esc_attr($brand_color) . ";
        }
        .artitechcore-ce-cta-head {
            font-size: 1.35em;
            font-weight: 800;
            margin: 0 0 8px 0;
            color: #121322;
        }
        .artitechcore-ce-cta-desc {
            font-size: 1.0em;
            color: #444;
            margin: 0 0 20px 0;
            line-height: 1.5;
        }
        .artitechcore-ce-native-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-start;
        }
        .artitechcore-ce-form-field {
            flex: 1;
            min-width: 180px;
        }
        .artitechcore-ce-form-field.field-message {
            flex-basis: 100%;
        }
        .artitechcore-ce-form-field input, 
        .artitechcore-ce-form-field textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            background: #fff;
            color: #121322;
        }
        .artitechcore-ce-form-field input:focus {
            border-color: " . esc_attr($brand_color) . ";
            box-shadow: 0 0 0 3px " . artitechcore_ce_hex_to_rgba($brand_color, 0.1) . ";
            outline: none;
        }
        .artitechcore-ce-submit-btn {
            background-color: var(--artitechcore-brand, #b47cfd);
            color: #ffffff !important;
            border: none;
            padding: 11px 25px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(var(--brand-rgb), 0.15);
            -webkit-appearance: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
        }
        .artitechcore-ce-submit-btn:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(var(--brand-rgb), 0.2);
        }
        .artitechcore-ce-submit-btn:active {
            transform: translateY(0);
        }
        .artitechcore-ce-submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            filter: grayscale(0.3);
        }
        .artitechcore-ce-submit-btn.loading {
            position: relative;
            color: transparent !important;
        }
        .artitechcore-ce-submit-btn.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: ce-spinner 0.6s linear infinite;
        }
        @keyframes ce-spinner {
            to { transform: rotate(360deg); }
        }
        .artitechcore-ce-form-response {
            flex-basis: 100%;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }
        .artitechcore-ce-form-response.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .artitechcore-ce-form-response.error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        @media (max-width: 600px) {
            .artitechcore-ce-native-form { flex-direction: column; }
            .artitechcore-ce-form-field { width: 100%; flex: none; }
            .artitechcore-ce-submit-btn { width: 100%; }
        }
    ";
    
    wp_register_style('artitechcore-ce-style', false);
    wp_enqueue_style('artitechcore-ce-style');
    wp_add_inline_style('artitechcore-ce-style', $css);
}

// Utility CSS Functions
function artitechcore_ce_hex_to_rgba($color, $opacity = false) {
    if (empty($color)) return 'rgba(0,0,0,0.05)';
    if ($color[0] == '#') $color = substr($color, 1);
    if (strlen($color) == 6) list($r, $g, $b) = array($color[0].$color[1], $color[2].$color[3], $color[4].$color[5]);
    elseif (strlen($color) == 3) list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
    else return false;
    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
    if ($opacity) return "rgba($r, $g, $b, $opacity)";
    return "rgb($r, $g, $b)";
}
function artitechcore_ce_adjust_brightness($hexCode, $adjustPercent) {
    $hexCode = ltrim($hexCode, '#');
    if (strlen($hexCode) == 3) $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
    $hexCode = array_map('hexdec', str_split($hexCode, 2));
    foreach ($hexCode as & $color) $color = max(0, min(255, $color + ($color * ($adjustPercent / 100))));
    return '#' . implode('', array_map(function($color) { return str_pad(dechex((int)$color), 2, '0', STR_PAD_LEFT); }, $hexCode));
}

/**
 * The Content Injection Filter (FIX #2, #3, #5)
 */
function artitechcore_ce_inject_content($content) {
    if (!is_singular() || !in_the_loop() || !is_main_query()) return $content;

    $post_id = get_the_ID();
    $supported_types = get_option('artitechcore_ce_post_types', ['post']);
    if (!is_array($supported_types) || !in_array(get_post_type(), $supported_types)) return $content;

    // Fetch Meta
    $kt = get_post_meta($post_id, '_artitechcore_ce_key_takeaways', true);
    $conc = get_post_meta($post_id, '_artitechcore_ce_conclusion', true);
    $cta_head = get_post_meta($post_id, '_artitechcore_ce_cta_heading', true);
    $cta_desc = get_post_meta($post_id, '_artitechcore_ce_cta_desc', true);
    
    // Early bail if nothing to inject (FIX #5: no CSS loaded either)
    $has_kt = !empty($kt) && is_array($kt) && count($kt) > 0;
    $has_conc = !empty($conc);
    $has_cta = !empty($cta_head);
    
    if (!$has_kt && !$has_conc && !$has_cta) return $content;

    // Only enqueue CSS when we actually have content to inject
    artitechcore_ce_enqueue_frontend_css();
    
    // Global Options
    $global_kt_head = get_option('artitechcore_ce_kt_heading', 'Key Takeaways');
    $global_conc_head = get_option('artitechcore_ce_conclusion_heading', 'Conclusion');
    $shortcode = get_option('artitechcore_ce_cta_shortcode', '');

    $enhanced_content = $content;

    // 1. Inject Key Takeaways at the very TOP of the post (before content)
    if ($has_kt) {
        $kt_html = '<div class="artitechcore-ce-kt">';
        $kt_html .= '<h3 class="artitechcore-ce-kt-title">' . esc_html($global_kt_head) . '</h3>';
        $kt_html .= '<ul>';
        foreach ($kt as $point) {
            $kt_html .= '<li>' . esc_html($point) . '</li>';
        }
        $kt_html .= '</ul></div>';
        
        // Prepend at the very top — users expect KT before the first paragraph
        $enhanced_content = $kt_html . "\n" . $enhanced_content;
    }

    // 3. Inject CTA (Fixed #14: Every 3 H2s Algorithm)
    if ($has_cta) {
        $cta_mode = get_option('artitechcore_ce_cta_mode', 'shortcode');
        $cta_html = '<div class="artitechcore-ce-cta-wrapper">' . 
                    '<h3 class="artitechcore-ce-cta-head">' . esc_html($cta_head) . '</h3>' .
                    (!empty($cta_desc) ? '<p class="artitechcore-ce-cta-desc">' . esc_html($cta_desc) . '</p>' : '') .
                    '<div class="artitechcore-ce-cta-form-container">' . 
                    ($cta_mode === 'native' ? artitechcore_ce_render_native_form($post_id) : do_shortcode($shortcode)) . 
                    '</div></div>';

        $h2_count = preg_match_all('/<h2[^>]*>.*?<\/h2>/i', $enhanced_content, $matches);
        if ($h2_count >= 3) {
            $parts = preg_split('/(<\/h2>)/i', $enhanced_content, -1, PREG_SPLIT_DELIM_CAPTURE);
            // Insert after every 3rd </h2> (indices 5, 11, 17...)
            for ($i = 5; $i < count($parts); $i += 6) {
                if (isset($parts[$i])) {
                    $parts[$i] .= "\n" . $cta_html;
                }
            }
            $enhanced_content = implode('', $parts);
        } else {
            // Fallback for posts with < 3 H2s: inject once in middle or at end
            $paragraphs = explode('</p>', $enhanced_content);
            $para_count = count($paragraphs);
            if ($para_count > 4) {
                $mid = floor($para_count / 2);
                if (isset($paragraphs[$mid])) {
                    $paragraphs[$mid] .= "\n" . $cta_html;
                }
                $enhanced_content = implode('</p>', $paragraphs);
            } else {
                $enhanced_content .= "\n" . $cta_html;
            }
        }
    }

    // 4. Inject Conclusion
    if ($has_conc) {
        $conc_html = '<div class="artitechcore-ce-conclusion">';
        $conc_html .= '<h3>' . esc_html($global_conc_head) . '</h3>';
        $conc_html .= '<p>' . nl2br(esc_html($conc)) . '</p>';
        $conc_html .= '</div>';
        $enhanced_content .= "\n" . $conc_html;
    }

    return $enhanced_content;
}

/**
 * Hook for Single Row Actions (Must run before headers) (FIX #4: capability check)
 */
function artitechcore_ce_admin_init_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'artitechcore-main') return;
    if (!isset($_GET['tab']) || $_GET['tab'] !== 'enhancer') return;
    if (!current_user_can('manage_options')) return; // FIX #4
    
    if (isset($_GET['action']) && isset($_GET['post']) && isset($_GET['_wpnonce'])) {
        $action = sanitize_text_field($_GET['action']);
        $post_id = intval($_GET['post']);
        
        if ($action === 'generate_ce' || $action === 'regenerate_ce') {
            if (wp_verify_nonce($_GET['_wpnonce'], $action . '_' . $post_id)) {
                if (function_exists('set_time_limit')) @set_time_limit(300);
                artitechcore_ce_generate_for_post($post_id);
                $redirect_url = remove_query_arg(['action', 'post', '_wpnonce']);
                $redirect_url = add_query_arg('ce_msg', 'generated', $redirect_url);
                wp_redirect($redirect_url);
                exit;
            }
        } elseif ($action === 'remove_ce') {
            if (wp_verify_nonce($_GET['_wpnonce'], 'remove_ce_' . $post_id)) {
                artitechcore_ce_remove_from_post($post_id);
                $redirect_url = remove_query_arg(['action', 'post', '_wpnonce']);
                $redirect_url = add_query_arg('ce_msg', 'removed', $redirect_url);
                wp_redirect($redirect_url);
                exit;
            }
        }
    }
}
add_action('admin_init', 'artitechcore_ce_admin_init_actions');

/**
 * Main Content Enhancer Dashboard UI (FIX #10: capability check)
 */
function artitechcore_content_enhancer_tab() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'artitechcore'));
    }

    $supported_types = get_option('artitechcore_ce_post_types', ['post']);
    if (!is_array($supported_types)) $supported_types = ['post'];
    
    // Server-side filters + pagination
    $filter_post_type = isset($_GET['artitechcore_post_type']) ? sanitize_key($_GET['artitechcore_post_type']) : '';
    $filter_status = isset($_GET['artitechcore_status']) ? sanitize_key($_GET['artitechcore_status']) : '';
    $filter_search = isset($_GET['artitechcore_search']) ? sanitize_text_field(wp_unslash($_GET['artitechcore_search'])) : '';
    if ($filter_post_type && !in_array($filter_post_type, $supported_types, true)) {
        $filter_post_type = '';
    }

    $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $per_page = 50;

    $query_args = [
        'post_type' => $filter_post_type ? [$filter_post_type] : $supported_types,
        'post_status' => $filter_status ? [$filter_status] : 'any',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'orderby' => 'title',
        'order' => 'ASC',
        's' => $filter_search ? $filter_search : '',
    ];

    // Handle bulk actions
    artitechcore_ce_handle_bulk_actions($query_args, $supported_types);

    $posts_q = new WP_Query($query_args);
    $pages = $posts_q->posts;

    // Lightweight stats via direct SQL
    global $wpdb;
    $stats = [
        'total' => (int) $posts_q->found_posts,
        'with_enhancements' => 0
    ];

    $stat_post_types = $filter_post_type ? [$filter_post_type] : $supported_types;
    if (!empty($stat_post_types)) {
        $pt_placeholders = implode(',', array_fill(0, count($stat_post_types), '%s'));
        $stat_statuses = $filter_status ? [$filter_status] : ['publish', 'draft', 'pending', 'private', 'future'];
        $st_placeholders = implode(',', array_fill(0, count($stat_statuses), '%s'));
        
        $prepare_args = array_merge($stat_post_types, $stat_statuses);
        $with_eh_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = '_artitechcore_ce_cta_heading'
               AND pm.meta_value != ''
               AND p.post_type IN ($pt_placeholders)
               AND p.post_status IN ($st_placeholders)",
            ...$prepare_args
        ));
        $stats['with_enhancements'] = (int) $with_eh_count;
    }

    // Check if API key exists for warning
    $provider = get_option('artitechcore_ai_provider', 'openai');
    $api_key = get_option('artitechcore_' . $provider . '_api_key');
    ?>
    <div class="wrap artitechcore-schema-dashboard dg10-brand">
        <?php if (empty($api_key)): ?>
        <div class="notice notice-warning" style="margin-bottom: 16px;">
            <p>⚠️ <strong>No API Key configured</strong> for <?php echo esc_html(ucfirst($provider)); ?>. AI generation will fail until you add a key in <a href="<?php echo esc_url(admin_url('admin.php?page=artitechcore-main&tab=settings')); ?>">Settings</a>.</p>
        </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="artitechcore-schema-stats border-box">
            <h2>Content Enhancer Statistics</h2>
            <div class="artitechcore-stats-grid">
                <div class="artitechcore-stat-card">
                    <h3><?php echo esc_html($stats['total']); ?></h3>
                    <p>Total Items</p>
                </div>
                <div class="artitechcore-stat-card">
                    <h3><?php echo esc_html($stats['with_enhancements']); ?></h3>
                    <p>Enhanced Items</p>
                </div>
                <div class="artitechcore-stat-card">
                    <h3><?php echo esc_html($stats['total'] - $stats['with_enhancements']); ?></h3>
                    <p>Unenhanced Items</p>
                </div>
                <div class="artitechcore-stat-card">
                    <h3><?php echo esc_html($stats['total'] > 0 ? round(($stats['with_enhancements'] / $stats['total']) * 100, 1) : 0); ?>%</h3>
                    <p>Coverage</p>
                </div>
            </div>
        </div>

        <!-- Main Workspace -->
        <div class="artitechcore-schema-section" style="margin-bottom: 24px;">
            <form method="get" action="" style="margin-bottom: 20px;">
                <input type="hidden" name="page" value="<?php echo esc_attr(sanitize_text_field($_GET['page'] ?? 'artitechcore-main')); ?>">
                <input type="hidden" name="tab" value="enhancer">

                <div style="display: flex; flex-direction: column; gap: 15px; width: 300px; max-width: 100%;">
                    <label style="display: flex; flex-direction: column; gap: 6px; font-weight: 600; font-size: 13px;">
                        Post Type
                        <select name="artitechcore_post_type" style="width: 100%; max-width: 100%;">
                            <option value="">All Supported</option>
                            <?php foreach ($supported_types as $pt): ?>
                                <?php $obj = get_post_type_object($pt); ?>
                                <option value="<?php echo esc_attr($pt); ?>" <?php selected($filter_post_type, $pt); ?>>
                                    <?php echo esc_html($obj ? $obj->labels->singular_name : $pt); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label style="display: flex; flex-direction: column; gap: 6px; font-weight: 600; font-size: 13px;">
                        Status
                        <select name="artitechcore_status" style="width: 100%; max-width: 100%;">
                            <option value="" <?php selected($filter_status, ''); ?>>All</option>
                            <option value="publish" <?php selected($filter_status, 'publish'); ?>>Publish</option>
                            <option value="draft" <?php selected($filter_status, 'draft'); ?>>Draft</option>
                        </select>
                    </label>

                    <label style="display: flex; flex-direction: column; gap: 6px; font-weight: 600; font-size: 13px;">
                        Search
                        <input type="search" name="artitechcore_search" value="<?php echo esc_attr($filter_search); ?>" placeholder="Search title..." style="width: 100%; max-width: 100%;" />
                    </label>

                    <div style="display: flex; gap: 10px; margin-top: 5px;">
                        <button type="submit" class="button" style="flex: 1;">Filter</button>
                        <?php if ($filter_post_type || $filter_status || $filter_search): ?>
                            <a class="button button-link" href="<?php echo esc_url(admin_url('admin.php?page=artitechcore-main&tab=enhancer')); ?>">Reset</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <hr style="margin-top: 24px; margin-bottom: 24px; border: 0; border-top: 1px solid #e2e8f0;"/>

            <form method="post" action="">
                <?php wp_nonce_field('artitechcore_bulk_ce_action'); ?>
                <input type="hidden" name="artitechcore_post_type" value="<?php echo esc_attr($filter_post_type); ?>">
                <input type="hidden" name="artitechcore_status" value="<?php echo esc_attr($filter_status); ?>">
                <input type="hidden" name="artitechcore_search" value="<?php echo esc_attr($filter_search); ?>">
                
                <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                    <h3 style="margin:0; font-size: 14px; font-weight: 600;">Bulk Actions</h3>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <select name="bulk_ce_action" required style="min-width: 160px;">
                            <option value="">Select Action...</option>
                            <option value="generate">Generate All Enhancements</option>
                            <option value="generate_kt">Generate KT Only</option>
                            <option value="generate_conclusion">Generate Conclusion Only</option>
                            <option value="generate_cta">Generate CTA Only</option>
                            <option value="remove">Remove Enhancements</option>
                        </select>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="radio" name="bulk_apply_scope" value="selected" checked>
                            Selected (this page)
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="radio" name="bulk_apply_scope" value="filtered">
                            All filtered results
                        </label>
                        <button type="submit" class="button button-primary" style="background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); border: none;">Apply</button>
                    </div>
                </div>

                <div class="artitechcore-schema-table-wrap">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column"><input type="checkbox" id="select-all-pages"></td>
                                <th class="manage-column">Post Title</th>
                                <th class="manage-column">Status</th>
                                <th class="manage-column">Enhancements</th>
                                <th class="manage-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pages)): ?>
                            <tr><td colspan="5">No posts found.</td></tr>
                            <?php else: ?>
                            <?php foreach ($pages as $p): ?>
                                <?php $has_enhancement = !empty(get_post_meta($p->ID, '_artitechcore_ce_cta_heading', true)); ?>
                            <tr>
                                <th class="check-column">
                                    <input type="checkbox" name="selected_posts[]" value="<?php echo esc_attr($p->ID); ?>">
                                </th>
                                <td>
                                    <strong><a href="<?php echo esc_url(get_edit_post_link($p->ID)); ?>"><?php echo esc_html($p->post_title); ?></a></strong>
                                    <div class="row-actions">
                                        <span class="view"><a href="<?php echo esc_url(get_permalink($p->ID)); ?>" target="_blank">View</a> |</span>
                                        <span class="edit"><a href="<?php echo esc_url(get_edit_post_link($p->ID)); ?>">Edit</a></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="artitechcore-page-status status-<?php echo esc_attr($p->post_status); ?>">
                                        <?php echo esc_html(ucfirst($p->post_status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($has_enhancement): ?>
                                        <span class="artitechcore-schema-badge artitechcore-schema-webpage" style="background: #b47cfd;">Enhanced</span>
                                    <?php else: ?>
                                        <span class="artitechcore-schema-badge artitechcore-schema-none">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="artitechcore-schema-actions">
                                        <?php if (!$has_enhancement): ?>
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=artitechcore-main&tab=enhancer&action=generate_ce&post=' . $p->ID), 'generate_ce_' . $p->ID)); ?>" class="button button-small">Generate</a>
                                        <?php else: ?>
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=artitechcore-main&tab=enhancer&action=regenerate_ce&post=' . $p->ID), 'regenerate_ce_' . $p->ID)); ?>" class="button button-small">Regenerate</a>
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=artitechcore-main&tab=enhancer&action=remove_ce&post=' . $p->ID), 'remove_ce_' . $p->ID)); ?>" class="button button-small button-link-delete" onclick="return confirm('Remove AI enhancements from this post?')">Remove</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <?php
            $total_pages = (int)$posts_q->max_num_pages;
            if ($total_pages > 1) {
                $base_url = remove_query_arg('paged');
                echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 16px 0;">';
                echo paginate_links([
                    'base' => esc_url_raw(add_query_arg('paged', '%#%', $base_url)),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $paged,
                ]);
                echo '</div></div>';
            }
            wp_reset_postdata(); // FIX #11
            ?>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#select-all-pages').on('change', function() {
            var checked = $(this).is(':checked');
            $('input[name="selected_posts[]"]').prop('checked', checked);
        });
    });
    </script>
    <?php
}

function artitechcore_ce_handle_bulk_actions($query_args, $supported_types) {
    // Notices from redirects
    if (isset($_GET['ce_msg'])) {
        $msg = sanitize_text_field($_GET['ce_msg']);
        if ($msg === 'generated') echo '<div class="notice notice-success is-dismissible"><p>AI Enhancements Generated Successfully!</p></div>';
        if ($msg === 'removed') echo '<div class="notice notice-success is-dismissible"><p>AI Enhancements Removed.</p></div>';
    }

    if (isset($_POST['bulk_ce_action']) && check_admin_referer('artitechcore_bulk_ce_action')) {
        if (!current_user_can('manage_options')) return; // FIX #4
        
        $action = sanitize_text_field($_POST['bulk_ce_action']);
        $apply_scope = isset($_POST['bulk_apply_scope']) ? sanitize_key($_POST['bulk_apply_scope']) : 'selected';
        $selected_posts = isset($_POST['selected_posts']) ? array_map('intval', (array)$_POST['selected_posts']) : [];

        $target_ids = [];
        if ($apply_scope === 'filtered') {
            $q_args = $query_args;
            $q_args['posts_per_page'] = -1;
            $q_args['fields'] = 'ids';
            $q = new WP_Query($q_args);
            $target_ids = $q->posts;
            wp_reset_postdata(); // FIX #11
        } else {
            $target_ids = $selected_posts;
        }

        if (!empty($target_ids)) {
            $processed = 0;
            $failed = 0;

            if (function_exists('set_time_limit')) @set_time_limit(0);
            @ini_set('memory_limit', '512M');

            foreach ($target_ids as $p_id) {
                if (!current_user_can('edit_post', $p_id)) continue;
                
                if (strpos($action, 'generate') === 0) {
                    $gen_type = 'all';
                    if ($action === 'generate_kt') $gen_type = 'kt';
                    if ($action === 'generate_conclusion') $gen_type = 'conclusion';
                    if ($action === 'generate_cta') $gen_type = 'cta';

                    if (function_exists('set_time_limit')) @set_time_limit(300);
                    if (artitechcore_ce_generate_for_post($p_id, $gen_type)) {
                        $processed++;
                    } else {
                        $failed++;
                    }
                } elseif ($action === 'remove') {
                    artitechcore_ce_remove_from_post($p_id);
                    $processed++;
                }
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sprintf('Bulk process complete! %d success, %d failed.', $processed, $failed)) . '</p></div>';
        }
    }
}

function artitechcore_ce_remove_from_post($post_id) {
    delete_post_meta($post_id, '_artitechcore_ce_key_takeaways');
    delete_post_meta($post_id, '_artitechcore_ce_conclusion');
    delete_post_meta($post_id, '_artitechcore_ce_cta_heading');
    delete_post_meta($post_id, '_artitechcore_ce_cta_desc');
}

function artitechcore_ce_generate_for_post($post_id, $generate_type = 'all') {
    $post = get_post($post_id);
    if (!$post || empty($post->post_content)) return false;

    $provider = get_option('artitechcore_ai_provider', 'openai');
    $api_key = get_option('artitechcore_' . $provider . '_api_key');
    if (empty($api_key)) return false;

    $clean_content = wp_strip_all_tags(strip_shortcodes($post->post_content));
    $clean_content = wp_trim_words($clean_content, 3000, '');
    if (empty($clean_content)) return false;

    // FIX #6: Use shared prompt builder
    $prompt = artitechcore_ce_build_prompt($clean_content, $generate_type);

    try {
        $json_result = null;
        if ($provider === 'openai') {
            $json_result = artitechcore_ce_call_openai($prompt, $api_key);
        } elseif ($provider === 'gemini') {
            $json_result = artitechcore_ce_call_gemini($prompt, $api_key);
        } elseif ($provider === 'deepseek') {
            $json_result = artitechcore_ce_call_deepseek($prompt, $api_key);
        }

        if (empty($json_result)) {
            return false;
        }

        if (isset($json_result['key_takeaways'])) {
            $kt = $json_result['key_takeaways'];
            if (is_string($kt)) {
                $kt = explode("\n", sanitize_textarea_field($kt));
            }
            if (is_array($kt)) {
                $kt = array_values(array_filter(array_map('trim', $kt)));
                update_post_meta($post_id, '_artitechcore_ce_key_takeaways', $kt);
            }
        }
        if (isset($json_result['conclusion'])) {
            update_post_meta($post_id, '_artitechcore_ce_conclusion', sanitize_textarea_field($json_result['conclusion']));
        }
        if (isset($json_result['cta_heading'])) {
            update_post_meta($post_id, '_artitechcore_ce_cta_heading', sanitize_text_field($json_result['cta_heading']));
        }
        if (isset($json_result['cta_description'])) {
            update_post_meta($post_id, '_artitechcore_ce_cta_desc', sanitize_textarea_field($json_result['cta_description']));
        }

        return true;
    } catch (Exception $e) {
        error_log('ArtitechCore CE Error (Post ' . $post_id . '): ' . $e->getMessage());
        return false;
    }
}

/**
 * Render Native AJAX CTA Form
 */
function artitechcore_ce_render_native_form($post_id) {
    $fields = get_option('artitechcore_ce_cta_native_fields', ['name', 'email', 'message']);
    $btn_text = get_option('artitechcore_ce_cta_native_button', 'Send Message');
    
    ob_start();
    ?>
    <form class="artitechcore-ce-native-form" id="ce-native-form-<?php echo esc_attr($post_id); ?>">
        <?php wp_nonce_field('artitechcore_ce_submit_cta', '_ce_nonce'); ?>
        <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">
        <input type="hidden" name="action" value="artitechcore_ce_submit_cta">
        
        <?php foreach ($fields as $field) : 
            $placeholder = ucfirst($field);
            $type = ($field === 'email') ? 'email' : (($field === 'phone') ? 'tel' : 'text');
        ?>
            <div class="artitechcore-ce-form-field field-<?php echo esc_attr($field); ?>">
                <?php if ($field === 'message') : ?>
                    <textarea name="<?php echo esc_attr($field); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" required rows="1"></textarea>
                <?php else : ?>
                    <input type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($field); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" required>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <button type="submit" class="artitechcore-ce-submit-btn"><?php echo esc_html($btn_text); ?></button>
        <div class="artitechcore-ce-form-response"></div>
    </form>
    <script>
    jQuery(document).ready(function($) {
        $("#ce-native-form-<?php echo esc_js($post_id); ?>").on("submit", function(e) {
            e.preventDefault();
            var $form = $(this);
            var $resp = $form.find(".artitechcore-ce-form-response");
            var $btn = $form.find(".artitechcore-ce-submit-btn");
            
            $btn.prop("disabled", true).addClass("loading");
            $resp.removeClass("error success").hide();
            
            $.post("<?php echo admin_url('admin-ajax.php'); ?>", $form.serialize(), function(res) {
                if (res.success) {
                    $resp.addClass("success").text(res.data).fadeIn();
                    $form[0].reset();
                } else {
                    $resp.addClass("error").text(res.data).fadeIn();
                }
                $btn.prop("disabled", false).removeClass("loading");
            }).fail(function() {
                $resp.addClass("error").text("Connection error. Please try again.").fadeIn();
                $btn.prop("disabled", false).removeClass("loading");
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Handle Native CTA AJAX Submission
 */
function artitechcore_ce_native_cta_ajax_handler() {
    check_ajax_referer('artitechcore_ce_submit_cta', '_ce_nonce');
    
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $to = get_option('artitechcore_ce_cta_native_email', get_option('admin_email'));
    $subject = sprintf('[Lead] New submission from CTA on: %s', get_the_title($post_id));
    
    $fields = get_option('artitechcore_ce_cta_native_fields', ['name', 'email', 'message']);
    $body = "New submission from ArtitechCore CTA on your post.\n\n";
    $body .= "Post URL: " . get_permalink($post_id) . "\n";
    $body .= "--------------------------------------------------\n\n";
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $val = sanitize_textarea_field(wp_unslash($_POST[$field]));
            $body .= strtoupper($field) . ": " . $val . "\n";
        }
    }
    
    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    if (isset($_POST['email'])) {
        $headers[] = 'Reply-To: ' . sanitize_email($_POST['email']);
    }
    
    $sent = wp_mail($to, $subject, $body, $headers);
    
    if ($sent) {
        wp_send_json_success(__('Thank you! Your submission has been received.', 'artitechcore'));
    } else {
        wp_send_json_error(__('Server error. Please try again or contact us directly.', 'artitechcore'));
    }
}
