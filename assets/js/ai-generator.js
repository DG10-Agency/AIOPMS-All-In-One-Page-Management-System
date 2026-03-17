/**
 * AI Generator JS
 * Handles AI suggestion requests and content creation via AJAX
 * 
 * @since 3.0
 */
(function ($) {
    'use strict';

    const AIOPMS_AIGenerator = {
        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            const self = this;

            // Handle initial suggestion request
            $(document).on('submit', '#aiopms-ai-request-form', function (e) {
                e.preventDefault();
                self.generateSuggestions($(this));
            });

            // Handle select all pages
            $(document).on('change', '#select-all-pages', function () {
                $('.aiopms-page-checkbox').prop('checked', $(this).prop('checked'));
                self.updateButtonStates();
            });

            // Handle individual page checkboxes
            $(document).on('change', '.aiopms-page-checkbox', function () {
                self.updateButtonStates();
            });

            // Handle creation of selected content
            $(document).on('submit', '#aiopms-ai-creation-form', function (e) {
                e.preventDefault();
                self.createContent($(this));
            });
        },

        generateSuggestions: function ($form) {
            const self = this;
            const $results = $('#aiopms-ai-results');
            const $btn = $form.find('button[type="submit"]');

            const formData = new FormData($form[0]);
            formData.append('action', 'aiopms_ai_generate_suggestions');

            // Show loading state
            $btn.prop('disabled', true).addClass('loading').html('<span class="dg10-spinner"></span> Generating...');
            $results.html(`
                <div class="aiopms-ai-loading">
                    <div class="aiopms-ai-loader"></div>
                    <h3>Creating your custom business strategy...</h3>
                    <p>Our AI is analyzing your industry and target audience. This may take up to 2 minutes.</p>
                </div>
            `);

            $.ajax({
                url: aiopms_ai_data.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 600000, // 600s timeout (10 minutes) for heavy analysis
                success: function (response) {
                    if (response.success) {
                        $results.html(response.data.html);
                        // Smooth scroll to results
                        $('html, body').animate({
                            scrollTop: $results.offset().top - 100
                        }, 500);
                    } else {
                        $results.html(`
                            <div class="notice notice-error dg10-notice-error">
                                <p>${response.data.message || 'An error occurred during generation.'}</p>
                            </div>
                        `);
                    }
                },
                error: function (jqXHR, textStatus) {
                    let msg = 'Network error occurred. Please check your connection and try again.';
                    if (textStatus === 'timeout') {
                        msg = 'Request timed out (120s). The server or AI provider took too long. Please try again or check your API key quotas.';
                    }
                    $results.html(`
                        <div class="notice notice-error dg10-notice-error">
                            <p>${msg}</p>
                        </div>
                    `);
                },
                complete: function () {
                    $btn.prop('disabled', false).removeClass('loading').text('Generate Suggestions');
                }
            });
        },

        createContent: function ($form) {
            const self = this;
            const $btn = $form.find('button[type="submit"]');

            // Ensure results container exists
            if ($form.find('.aiopms-creation-status').length === 0) {
                $form.append('<div class="aiopms-creation-status"></div>');
            }
            const $results = $form.find('.aiopms-creation-status');

            // Scroll to bottom
            $('html, body').animate({
                scrollTop: $results.offset().top - 100
            }, 500);

            const formData = new FormData($form[0]);
            formData.append('action', 'aiopms_ai_create_content');

            $btn.prop('disabled', true).addClass('loading').text('Creating Content...');

            // Progress steps simulation
            const steps = [
                'Initializing content generation...',
                'Creating page structure and drafts...',
                'Configuring Custom Post Types...',
                ' registering Taxonomies and Categories...',
                'Generating AI Featured Images (This may take 60s+)...',
                'Finalizing ecosystem setup...'
            ];

            let currentStep = 0;

            // Show overlay with progress
            $results.show().html(`
                <div class="aiopms-progress-box" style="margin-top: 20px; padding: 30px; background: #fff; border: 1px solid #ccd0d4; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <span class="dashicons dashicons-update spin" style="font-size: 30px; width: 30px; height: 30px; margin-right: 15px; color: #2271b1;"></span>
                        <h3 id="ai-progress-text" style="margin: 0; font-size: 16px;">${steps[0]}</h3>
                    </div>
                    <div class="dg10-progress-bar" style="height: 10px; background: #f0f0f1; border-radius: 5px; overflow: hidden;">
                        <div class="dg10-progress-inner" style="width: 10%; height: 100%; background: #2271b1; transition: width 0.5s;"></div>
                    </div>
                    <p style="margin-top: 10px; color: #646970; font-style: italic;">Please do not close this tab.</p>
                </div>
            `);

            // Cycle messages
            const interval = setInterval(function () {
                currentStep++;
                if (currentStep < steps.length) {
                    $('#ai-progress-text').text(steps[currentStep]);
                    $('.dg10-progress-inner').css('width', Math.min((currentStep + 1) * 18, 95) + '%');
                }
            }, 5000);

            $.ajax({
                url: aiopms_ai_data.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 600000, // 10 minutes
                success: function (response) {
                    clearInterval(interval);
                    if (response.success) {
                        $('.dg10-progress-inner').css('width', '100%');
                        setTimeout(function () {
                            $form.html(`
                                <div class="aiopms-success-state" style="text-align: center; padding: 40px; background: #fff; border: 1px solid #c3c4c7;">
                                    <div class="aiopms-success-icon" style="font-size: 48px; margin-bottom: 20px; color: #46b450;">✅</div>
                                    <h2 style="margin-top: 0;">Deployment Complete!</h2>
                                    <p style="font-size: 16px; margin-bottom: 30px;">${response.data.message || 'Your content ecosystem has been created successfully.'}</p>
                                    <div class="dg10-action-links" style="display: flex; gap: 15px; justify-content: center;">
                                        <a href="edit.php?post_type=page" class="button button-primary button-hero">View Created Pages</a>
                                        <a href="admin.php?page=aiopms_cpt_manager" class="button button-secondary button-hero">Manage Taxonomies & CPTs</a>
                                    </div>
                                </div>
                            `);
                        }, 500);
                    } else {
                        $results.html(`
                            <div class="notice notice-error dg10-notice-error inline">
                                <p><strong>Error:</strong> ${response.data.message || 'An error occurred during creation.'}</p>
                            </div>
                        `);
                        $btn.prop('disabled', false).text('Try Again');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    clearInterval(interval);
                    console.error('AI Generation Error:', textStatus, errorThrown, jqXHR);

                    let msg = 'Network error. Please check your connection.';
                    let isWarning = false;

                    if (textStatus === 'timeout') {
                        msg = 'The operation timed out, but content is likely still generating in the background. Please wait 1 minute and refresh.';
                        isWarning = true;
                    } else if (jqXHR.status === 200 && textStatus === 'parsererror') {
                        msg = 'The operation completed but returned an unexpected response. Please check "Pages" and "Custom Post Types" - it likely succeeded.';
                        isWarning = true;
                    }

                    const noticeClass = isWarning ? 'notice-warning' : 'notice-error';

                    $results.html(`
                        <div class="notice ${noticeClass} inline">
                            <p><strong>${isWarning ? 'Note:' : 'Error:'}</strong> ${msg}</p>
                            ${!isWarning ? '<p><small>Details: ' + textStatus + ' - ' + errorThrown + '</small></p>' : ''}
                        </div>
                    `);
                    $btn.prop('disabled', false).text('Retry Deployment');
                }
            });
        },

        updateButtonStates: function () {
            const hasSelection = $('.aiopms-page-checkbox:checked, .aiopms-cpt-checkbox:checked').length > 0;
            $('#aiopms-ai-creation-form button[type="submit"]').prop('disabled', !hasSelection);
        }
    };

    $(document).ready(function () {
        AIOPMS_AIGenerator.init();
    });

})(jQuery);
