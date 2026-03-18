jQuery(document).ready(function ($) {
    'use strict';

    // Select all functionality
    $('#select-all-pages').on('change', function () {
        $('input[name="selected_pages[]"]').prop('checked', this.checked);
    });

    // Update select all when individual checkboxes change
    // Filtering/pagination is server-side (no client-side row hiding)

    $('input[name="selected_pages[]"]').on('change', function () {
        var total = $('input[name="selected_pages[]"]').length;
        var checked = $('input[name="selected_pages[]"]:checked').length;
        $('#select-all-pages').prop('checked', total === checked);
    });

    // Preview Schema Modal
    const $modal = $('#artitechcore-schema-modal');

    // Anchor modal to body to prevent fixed-positioning CSS context bugs within WordPress wrap
    if ($modal.length && $modal.parent()[0] !== document.body) {
        $(document.body).append($modal);
    }

    // Preview target (fallback to older id if needed)
    const $previewCode = $('#artitechcore-schema-preview-code').length ? $('#artitechcore-schema-preview-code') : $('#artitechcore-schema-code');
    const $closeBtn = $('.artitechcore-modal-close');
    const $editor = $('#artitechcore-schema-editor');
    const $editorWrap = $('#artitechcore-schema-editor-wrap');
    const $pageIdField = $('#artitechcore-schema-page-id');
    const $entityTypeField = $('#artitechcore-schema-entity-type');
    const $saveBtn = $('#artitechcore-schema-save');
    const $validateBtn = $('#artitechcore-schema-validate');
    const $status = $('#artitechcore-schema-save-status');
    const $editToggle = $('#artitechcore-schema-edit-toggle');

    let lastLoadedJson = '';

    function setPreviewMode() {
        $previewCode.parent().show(); // Show the <pre> block
        $editorWrap.hide();
        $validateBtn.hide();
        $saveBtn.hide();
        $editToggle.show().text('Edit Schema');
        $('#artitechcore-schema-preview-hint').show();
    }

    function setEditMode() {
        $previewCode.parent().hide(); // Hide the <pre> block
        $editorWrap.show();
        $validateBtn.show();
        $saveBtn.show();
        $editToggle.text('Back to Preview');
        $('#artitechcore-schema-preview-hint').hide();
    }

    // Ensure correct initial state even if CSS/theme overrides
    setPreviewMode();

    // Open Modal
    $('.artitechcore-preview-schema').on('click', function (e) {
        e.preventDefault();
        const pageId = $(this).data('page-id');
        const $btn = $(this);

        $btn.prop('disabled', true).text('Loading...');
        $status.text('');
        setPreviewMode();
        lastLoadedJson = '';
        $previewCode.text('Loading schema...');
        $editor.val('');
        $entityTypeField.val('post');

        // Use localized data or fallback. 
        // Note: Ensure wp_localize_script is called in PHP with 'artitechcore_schema_data'
        const ajaxUrl = typeof artitechcore_schema_data !== 'undefined' ? artitechcore_schema_data.ajaxurl : ajaxurl;
        const nonce = typeof artitechcore_schema_data !== 'undefined' ? artitechcore_schema_data.nonce : '';

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'artitechcore_get_schema_preview',
                nonce: nonce,
                page_id: pageId
            },
            success: function (response) {
                if (response.success) {
                    const jsonStr = (response.data && response.data.schema_json) ? response.data.schema_json : '';
                    lastLoadedJson = jsonStr;
                    const safe = (jsonStr && jsonStr.trim())
                        ? jsonStr
                        : '{\n  "message": "No schema data found for this item."\n}';
                    $previewCode.text(safe);
                    $previewCode.text(safe);
                    $editor.val(safe);
                    $pageIdField.val(pageId);
                    $entityTypeField.val('post');

                    // Ensure it has brand class
                    $modal.addClass('dg10-brand');

                    // Open modal with scroll lock
                    $('body').addClass('artitechcore-modal-open');
                    $modal.fadeIn(200).css('display', 'flex');
                } else {
                    const msg = (response.data && response.data.message) ? response.data.message : 'Could not fetch schema.';
                    $previewCode.text('{\n  "error": ' + JSON.stringify(msg) + '\n}');

                    $('body').addClass('artitechcore-modal-open');
                    $modal.fadeIn(200).css('display', 'flex');
                }
            },
            error: function () {
                $previewCode.text('{\n  "error": "Network error occurred."\n}');
                $('body').addClass('artitechcore-modal-open');
                $modal.fadeIn(200).css('display', 'flex');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Preview');
            }
        });
    });

    // Preview term schema
    $('.artitechcore-preview-schema-term').on('click', function (e) {
        e.preventDefault();
        const termId = $(this).data('term-id');
        const taxonomy = $(this).data('taxonomy');
        const $btn = $(this);

        $btn.prop('disabled', true).text('Loading...');
        $status.text('');
        setPreviewMode();
        lastLoadedJson = '';
        $previewCode.text('Loading schema...');
        $editor.val('');

        const ajaxUrl = typeof artitechcore_schema_data !== 'undefined' ? artitechcore_schema_data.ajaxurl : ajaxurl;
        const nonce = typeof artitechcore_schema_data !== 'undefined' ? artitechcore_schema_data.nonce : '';

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'artitechcore_get_term_schema_preview',
                nonce: nonce,
                term_id: termId,
                taxonomy: taxonomy
            },
            success: function (response) {
                if (response.success) {
                    const jsonStr = (response.data && response.data.schema_json) ? response.data.schema_json : '';
                    lastLoadedJson = jsonStr;
                    const safe = (jsonStr && jsonStr.trim())
                        ? jsonStr
                        : '{\n  "message": "No schema data found for this item."\n}';
                    $previewCode.text(safe);
                    $editor.val(safe);
                    $editor.val(safe);
                    $pageIdField.val(termId);
                    $entityTypeField.val('term:' + taxonomy);

                    // Ensure it has brand class
                    $modal.addClass('dg10-brand');

                    $('body').addClass('artitechcore-modal-open');
                    $modal.fadeIn(200).css('display', 'flex');
                } else {
                    const msg = (response.data && response.data.message) ? response.data.message : 'Could not fetch schema.';
                    $previewCode.text('{\n  "error": ' + JSON.stringify(msg) + '\n}');

                    $('body').addClass('artitechcore-modal-open');
                    $modal.fadeIn(200).css('display', 'flex');
                }
            },
            error: function () {
                $previewCode.text('{\n  "error": "Network error occurred."\n}');

                $('body').addClass('artitechcore-modal-open');
                $modal.fadeIn(200).css('display', 'flex');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Preview');
            }
        });
    });

    // Close Modal
    $closeBtn.on('click', function () {
        $modal.fadeOut(200, function () {
            $('body').removeClass('artitechcore-modal-open');
        });
    });

    // Close on click outside
    $(window).on('click', function (e) {
        if ($(e.target).is($modal)) {
            $modal.fadeOut(200, function () {
                $('body').removeClass('artitechcore-modal-open');
            });
        }
    });

    $editToggle.on('click', function () {
        if ($editorWrap.is(':visible')) {
            // Back to preview, keep preview consistent with last loaded/saved
            $previewCode.text(lastLoadedJson || $editor.val());
            setPreviewMode();
        } else {
            // Enter edit mode with current preview JSON
            const current = lastLoadedJson || $previewCode.text();
            $editor.val(current);
            setEditMode();
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
        const entityType = ($entityTypeField.val() || 'post').toString();
        const entityId = parseInt($pageIdField.val(), 10);
        if (!entityId) {
            $status.html('<span style="color: #b32d2e;">✗ Missing page id</span>');
            return;
        }

        const jsonText = $editor.val();
        const result = validateJson(jsonText);
        if (!result.ok) {
            $status.html('<span style="color: #b32d2e;">✗ ' + result.message + '</span>');
            return;
        }

        const ajaxUrl = typeof artitechcore_schema_data !== 'undefined' ? artitechcore_schema_data.ajaxurl : ajaxurl;
        const nonce = typeof artitechcore_schema_data !== 'undefined' ? artitechcore_schema_data.nonce : '';

        $saveBtn.prop('disabled', true).text('Saving...');
        $status.text('');

        const isTerm = entityType.indexOf('term:') === 0;
        const taxonomy = isTerm ? entityType.replace('term:', '') : '';
        const actionName = isTerm ? 'artitechcore_save_term_schema_override' : 'artitechcore_save_schema_override';

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: actionName,
                nonce: nonce,
                page_id: isTerm ? undefined : entityId,
                term_id: isTerm ? entityId : undefined,
                taxonomy: isTerm ? taxonomy : undefined,
                schema_json: jsonText
            },
            success: function (response) {
                if (response.success) {
                    $status.html('<span style="color: green;">✓ ' + (response.data.message || 'Saved') + '</span>');
                    const pretty = JSON.stringify(response.data.data, null, 2);
                    lastLoadedJson = pretty;
                    $previewCode.text(pretty);
                    $editor.val(pretty);
                    setPreviewMode();
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
