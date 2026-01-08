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
                    <p>Our AI is analyzing your industry and target audience. This may take up to 30 seconds.</p>
                </div>
            `);

            $.ajax({
                url: aiopms_ai_data.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
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
                error: function () {
                    $results.html(`
                        <div class="notice notice-error dg10-notice-error">
                            <p>Network error occurred. Please check your connection and try again.</p>
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
            const $results = $form.find('.aiopms-creation-status');

            const formData = new FormData($form[0]);
            formData.append('action', 'aiopms_ai_create_content');

            $btn.prop('disabled', true).addClass('loading').text('Creating Content...');
            $results.show().html('<div class="dg10-progress-bar"><div class="dg10-progress-inner"></div></div><p>Creating pages and configuring post types...</p>');

            $.ajax({
                url: aiopms_ai_data.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        $form.html(`
                            <div class="aiopms-success-state">
                                <div class="aiopms-success-icon">✅</div>
                                <h3>Everything is ready!</h3>
                                <p>${response.data.message}</p>
                                <div class="dg10-action-links">
                                    <a href="edit.php?post_type=page" class="dg10-btn dg10-btn-primary">View New Pages</a>
                                    <a href="admin.php?page=aiopms-cpt-management" class="dg10-btn dg10-btn-outline">Manage CPTs</a>
                                </div>
                            </div>
                        `);
                    } else {
                        $results.html(`
                            <div class="notice notice-error dg10-notice-error">
                                <p>${response.data.message || 'An error occurred during creation.'}</p>
                            </div>
                        `);
                        $btn.prop('disabled', false).text('Create Selected Content');
                    }
                },
                error: function () {
                    $results.html('<p class="error">Network error. Please try again.</p>');
                    $btn.prop('disabled', false).text('Create Selected Content');
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
