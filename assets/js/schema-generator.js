jQuery(document).ready(function ($) {
    'use strict';

    // Select all functionality
    $('#select-all-pages').on('change', function () {
        $('input[name="selected_pages[]"]').prop('checked', this.checked);
    });

    // Update select all when individual checkboxes change
    $('input[name="selected_pages[]"]').on('change', function () {
        var total = $('input[name="selected_pages[]"]').length;
        var checked = $('input[name="selected_pages[]"]:checked').length;
        $('#select-all-pages').prop('checked', total === checked);
    });

    // Preview Schema Modal
    const $modal = $('#aiopms-schema-modal');
    const $modalCode = $('#aiopms-schema-code');
    const $closeBtn = $('.aiopms-modal-close');
    const $editor = $('#aiopms-schema-editor');
    const $pageIdField = $('#aiopms-schema-page-id');
    const $saveBtn = $('#aiopms-schema-save');
    const $validateBtn = $('#aiopms-schema-validate');
    const $status = $('#aiopms-schema-save-status');

    // Open Modal
    $('.aiopms-preview-schema').on('click', function (e) {
        e.preventDefault();
        const pageId = $(this).data('page-id');
        const $btn = $(this);

        $btn.prop('disabled', true).text('Loading...');
        $status.text('');

        // Use localized data or fallback. 
        // Note: Ensure wp_localize_script is called in PHP with 'aiopms_schema_data'
        const ajaxUrl = typeof aiopms_schema_data !== 'undefined' ? aiopms_schema_data.ajaxurl : ajaxurl;
        const nonce = typeof aiopms_schema_data !== 'undefined' ? aiopms_schema_data.nonce : '';

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'aiopms_get_schema_preview',
                nonce: nonce,
                page_id: pageId
            },
            success: function (response) {
                if (response.success) {
                    const jsonStr = JSON.stringify(response.data, null, 2);
                    $modalCode.text(jsonStr);
                    $editor.val(jsonStr);
                    $pageIdField.val(pageId);
                    $modal.fadeIn(200).css('display', 'flex');
                } else {
                    alert('Error: ' + (response.data.message || 'Could not fetch schema.'));
                }
            },
            error: function () {
                alert('Network error occurred.');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Preview');
            }
        });
    });

    // Close Modal
    $closeBtn.on('click', function () {
        $modal.fadeOut(200);
    });

    // Close on click outside
    $(window).on('click', function (e) {
        if ($(e.target).is($modal)) {
            $modal.fadeOut(200);
        }
    });

    function validateJson(text) {
        try {
            const parsed = JSON.parse(text);
            if (parsed === null || typeof parsed !== 'object') {
                return { ok: false, message: 'JSON must be an object or array.' };
            }
            return { ok: true, value: parsed };
        } catch (err) {
            return { ok: false, message: err && err.message ? err.message : 'Invalid JSON.' };
        }
    }

    $validateBtn.on('click', function () {
        const result = validateJson($editor.val());
        if (result.ok) {
            $status.html('<span style="color: green;">✓ Valid JSON</span>');
        } else {
            $status.html('<span style="color: #b32d2e;">✗ ' + result.message + '</span>');
        }
    });

    $saveBtn.on('click', function () {
        const pageId = parseInt($pageIdField.val(), 10);
        if (!pageId) {
            $status.html('<span style="color: #b32d2e;">✗ Missing page id</span>');
            return;
        }

        const jsonText = $editor.val();
        const result = validateJson(jsonText);
        if (!result.ok) {
            $status.html('<span style="color: #b32d2e;">✗ ' + result.message + '</span>');
            return;
        }

        const ajaxUrl = typeof aiopms_schema_data !== 'undefined' ? aiopms_schema_data.ajaxurl : ajaxurl;
        const nonce = typeof aiopms_schema_data !== 'undefined' ? aiopms_schema_data.nonce : '';

        $saveBtn.prop('disabled', true).text('Saving...');
        $status.text('');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'aiopms_save_schema_override',
                nonce: nonce,
                page_id: pageId,
                schema_json: jsonText
            },
            success: function (response) {
                if (response.success) {
                    $status.html('<span style="color: green;">✓ ' + (response.data.message || 'Saved') + '</span>');
                    const pretty = JSON.stringify(response.data.data, null, 2);
                    $modalCode.text(pretty);
                    $editor.val(pretty);
                } else {
                    $status.html('<span style="color: #b32d2e;">✗ ' + (response.data.message || 'Save failed') + '</span>');
                }
            },
            error: function () {
                $status.html('<span style="color: #b32d2e;">✗ Network error occurred.</span>');
            },
            complete: function () {
                $saveBtn.prop('disabled', false).text('Save Schema');
            }
        });
    });
});
