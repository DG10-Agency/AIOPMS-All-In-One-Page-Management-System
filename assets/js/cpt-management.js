/**
 * AIOPMS Custom Post Type Management JavaScript
 * Enhanced functionality with AJAX, accessibility, and modern UX
 * 
 * @package AIOPMS
 * @version 3.0
 * @author DG10 Agency
 */

(function ($) {
    'use strict';

    // Global CPT Management object
    window.AIOPMSCPTManager = {
        init: function () {
            this.bindEvents();
            this.initializeComponents();
            this.setupAccessibility();
            this.loadCPTData();
        },

        // Event bindings
        bindEvents: function () {
            // Search and filter functionality
            $('#cpt-search').on('input', this.debounce(this.handleSearch, 300));
            $('#cpt-filter-status').on('change', this.handleFilter);
            $('#refresh-cpt-list').on('click', this.refreshCPTList);

            // Bulk operations
            $('#bulk-action-select').on('change', this.toggleBulkActionButton);
            $('#apply-bulk-action').on('click', this.handleBulkAction);
            $(document).on('change', '.cpt-checkbox', this.updateBulkSelection);
            $('#select-all-cpts').on('change', this.handleSelectAll);

            // CPT card actions
            $(document).on('click', '.aiopms-action-btn', this.handleCPTAction);

            // Form submissions
            $('#aiopms-cpt-form').on('submit', this.handleCPTFormSubmission);

            // Field builder
            $('.aiopms-add-field-btn').on('click', this.addCustomField);
            $(document).on('click', '.remove-field-btn', this.removeCustomField);
            $(document).on('change', '.field-type-select', this.handleFieldTypeChange);

            // Modal functionality
            $(document).on('click', '[data-modal]', this.openModal);
            $(document).on('click', '.aiopms-modal-close, .aiopms-modal-overlay', this.closeModal);

            // Tab functionality
            $(document).on('click', '.aiopms-tab', this.switchTab);

            // Bulk operations
            $('#select-all-bulk').on('change', this.handleSelectAllBulk);
            $(document).on('change', '.aiopms-cpt-checkbox', this.updateBulkSelection);
            $('#bulk-action-selector').on('change', this.toggleBulkActionButton);
            $('#apply-bulk-action-btn').on('click', this.handleBulkAction);

            // Import/Export
            $('input[name="export_type"]').on('change', this.toggleExportSelection);
            $('#cpt-import-file').on('change', this.handleFileSelection);

            // Templates
            $('.aiopms-preview-template').on('click', this.previewTemplate);
            $('.aiopms-template-form').on('submit', this.handleTemplateCreation);

            // Keyboard navigation
            $(document).on('keydown', this.handleKeyboardNavigation);

            // Auto-save functionality
            $(document).on('input change', '.auto-save', this.debounce(this.autoSave, 1000));
        },

        // Initialize components
        initializeComponents: function () {
            this.initializeTooltips();
            this.initializeValidation();
            this.setupLoadingStates();
            this.initializeNotifications();
        },

        // Setup accessibility features
        setupAccessibility: function () {
            // Add ARIA labels and descriptions
            this.enhanceAriaLabels();

            // Setup keyboard navigation
            this.setupKeyboardNavigation();

            // Setup screen reader announcements
            this.setupScreenReaderAnnouncements();
        },

        // Search functionality
        handleSearch: function () {
            const searchTerm = $('#cpt-search').val().toLowerCase();
            const $cards = $('.aiopms-cpt-card');

            $cards.each(function () {
                const $card = $(this);
                const title = $card.find('.aiopms-cpt-title').text().toLowerCase();
                const slug = $card.find('.aiopms-cpt-slug').text().toLowerCase();
                const description = $card.find('.aiopms-cpt-description').text().toLowerCase();

                const matches = title.includes(searchTerm) ||
                    slug.includes(searchTerm) ||
                    description.includes(searchTerm);

                $card.toggle(matches);
            });

            this.updateResultsCount();
            this.announceSearchResults(searchTerm);
        },

        // Filter functionality
        handleFilter: function () {
            const filterValue = $('#cpt-filter-status').val();
            const $cards = $('.aiopms-cpt-card');

            $cards.each(function () {
                const $card = $(this);
                const isActive = $card.find('.aiopms-status-indicator').hasClass('active');

                let shouldShow = true;

                if (filterValue === 'active' && !isActive) {
                    shouldShow = false;
                } else if (filterValue === 'inactive' && isActive) {
                    shouldShow = false;
                }

                $card.toggle(shouldShow);
            });

            this.updateResultsCount();
        },

        // Refresh CPT list
        refreshCPTList: function () {
            AIOPMSCPTManager.showLoadingOverlay();

            $.ajax({
                url: aiopms_cpt_data.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiopms_get_cpt_data',
                    nonce: aiopms_cpt_data.nonce
                },
                success: function (response) {
                    if (response.success) {
                        AIOPMSCPTManager.updateCPTGrid(response.data);
                        AIOPMSCPTManager.showNotification('CPT list refreshed successfully', 'success');
                    } else {
                        AIOPMSCPTManager.showNotification(response.data || 'Failed to refresh CPT list', 'error');
                    }
                },
                error: function () {
                    AIOPMSCPTManager.showNotification('Network error occurred', 'error');
                },
                complete: function () {
                    AIOPMSCPTManager.hideLoadingOverlay();
                }
            });
        },

        // Bulk operations
        toggleBulkActionButton: function () {
            const hasAction = $('#bulk-action-select').val() !== '';
            const hasSelection = $('.cpt-checkbox:checked').length > 0;
            $('#apply-bulk-action').prop('disabled', !hasAction || !hasSelection);
        },

        handleBulkAction: function (e) {
            e.preventDefault();

            const action = $('#bulk-action-select').val();
            const selectedCPTs = $('.cpt-checkbox:checked').map(function () {
                return $(this).val();
            }).get();

            if (!action || selectedCPTs.length === 0) {
                AIOPMSCPTManager.showNotification('Please select an action and at least one CPT', 'warning');
                return;
            }

            // Confirm destructive actions
            if (action === 'delete') {
                const confirmMessage = `Are you sure you want to delete ${selectedCPTs.length} custom post type(s)? This action cannot be undone.`;
                if (!confirm(confirmMessage)) {
                    return;
                }
            }

            AIOPMSCPTManager.performBulkAction(action, selectedCPTs);
        },

        performBulkAction: function (action, cptList) {
            AIOPMSCPTManager.showLoadingOverlay();

            $.ajax({
                url: aiopms_cpt_data.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiopms_bulk_cpt_operations',
                    bulk_action: action,
                    cpt_ids: cptList,
                    nonce: aiopms_cpt_data.nonce
                },
                success: function (response) {
                    if (response.success) {
                        AIOPMSCPTManager.showNotification(response.data.message, 'success');

                        // Show detailed results if available
                        if (response.data.results && response.data.results.length > 0) {
                            AIOPMSCPTManager.showBulkResults(response.data.results);
                        }

                        AIOPMSCPTManager.refreshCPTList();
                    } else {
                        AIOPMSCPTManager.showNotification(response.data || 'Bulk operation failed', 'error');
                    }
                },
                error: function () {
                    AIOPMSCPTManager.showNotification('Network error occurred', 'error');
                },
                complete: function () {
                    AIOPMSCPTManager.hideLoadingOverlay();
                    // Reset form
                    $('#bulk-action-select').val('');
                    $('.cpt-checkbox').prop('checked', false);
                    AIOPMSCPTManager.toggleBulkActionButton();
                }
            });
        },

        // Show detailed bulk operation results
        showBulkResults: function (results) {
            let resultHtml = '<div class="aiopms-bulk-results"><h4>Operation Results:</h4><ul>';

            results.forEach(function (result) {
                const statusClass = result.status === 'success' ? 'success' : 'error';
                resultHtml += `<li class="${statusClass}"><strong>${result.post_type}:</strong> ${result.message}</li>`;
            });

            resultHtml += '</ul></div>';

            AIOPMSCPTManager.showNotification(resultHtml, 'info');
        },

        // Get CPT data for editing
        getCPTData: function (postType) {
            return $.ajax({
                url: aiopms_cpt_data.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiopms_get_cpt_data',
                    nonce: aiopms_cpt_data.nonce,
                    post_type: postType
                }
            });
        },

        handleSelectAll: function () {
            const isChecked = $('#select-all-cpts').prop('checked');
            $('.cpt-checkbox:visible').prop('checked', isChecked);
            AIOPMSCPTManager.toggleBulkActionButton();
        },

        updateBulkSelection: function () {
            const totalVisible = $('.cpt-checkbox:visible').length;
            const totalChecked = $('.cpt-checkbox:visible:checked').length;

            $('#select-all-cpts').prop({
                'checked': totalChecked === totalVisible && totalVisible > 0,
                'indeterminate': totalChecked > 0 && totalChecked < totalVisible
            });

            AIOPMSCPTManager.toggleBulkActionButton();
        },

        // CPT card actions
        handleCPTAction: function (e) {
            e.preventDefault();

            const $btn = $(this);
            const action = $btn.data('action');
            const cptSlug = $btn.data('cpt');

            switch (action) {
                case 'edit':
                    AIOPMSCPTManager.editCPT(cptSlug);
                    break;
                case 'duplicate':
                    AIOPMSCPTManager.duplicateCPT(cptSlug);
                    break;
                case 'delete':
                    AIOPMSCPTManager.deleteCPT(cptSlug);
                    break;
                case 'toggle-status':
                    AIOPMSCPTManager.toggleCPTStatus(cptSlug);
                    break;
            }
        },

        editCPT: function (cptSlug) {
            // Get CPT data first, then open edit modal
            AIOPMSCPTManager.showLoadingOverlay();

            AIOPMSCPTManager.getCPTData(cptSlug).done(function (response) {
                AIOPMSCPTManager.hideLoadingOverlay();

                if (response.success) {
                    // Open edit modal with CPT data
                    AIOPMSCPTManager.openEditModal(response.data);
                } else {
                    AIOPMSCPTManager.showNotification('Failed to load CPT data', 'error');
                }
            }).fail(function () {
                AIOPMSCPTManager.hideLoadingOverlay();
                AIOPMSCPTManager.showNotification('Network error occurred', 'error');
            });
        },

        // Open edit modal with CPT data
        openEditModal: function (cptData) {
            // Create and show edit modal
            const modalHtml = `
                <div class="aiopms-modal" id="edit-cpt-modal">
                    <div class="aiopms-modal-content">
                        <div class="aiopms-modal-header">
                            <h3>Edit Custom Post Type: ${cptData.cpt_data.label}</h3>
                            <button class="aiopms-modal-close">&times;</button>
                        </div>
                        <div class="aiopms-modal-body">
                            <form id="edit-cpt-form">
                                <input type="hidden" name="cpt_name" value="${cptData.cpt_data.name}">
                                <div class="form-group">
                                    <label>Display Label:</label>
                                    <input type="text" name="cpt_label" value="${cptData.cpt_data.label}" required>
                                </div>
                                <div class="form-group">
                                    <label>Description:</label>
                                    <textarea name="cpt_description">${cptData.cpt_data.description || ''}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Menu Icon:</label>
                                    <input type="text" name="cpt_menu_icon" value="${cptData.cpt_data.menu_icon || 'dashicons-admin-post'}">
                                </div>
                            </form>
                        </div>
                        <div class="aiopms-modal-footer">
                            <button type="button" class="button button-secondary aiopms-modal-close">Cancel</button>
                            <button type="button" class="button button-primary" id="save-cpt-changes">Save Changes</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            $('#edit-cpt-modal').show();

            // Bind save handler
            $('#save-cpt-changes').on('click', function () {
                AIOPMSCPTManager.saveCPTChanges();
            });
        },

        // Save CPT changes
        saveCPTChanges: function () {
            const formData = $('#edit-cpt-form').serialize();

            AIOPMSCPTManager.showLoadingOverlay();

            $.ajax({
                url: aiopms_cpt_data.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiopms_update_cpt_ajax',
                    nonce: aiopms_cpt_data.nonce,
                    ...formData
                },
                success: function (response) {
                    AIOPMSCPTManager.hideLoadingOverlay();

                    if (response.success) {
                        AIOPMSCPTManager.showNotification('CPT updated successfully', 'success');
                        $('#edit-cpt-modal').remove();
                        AIOPMSCPTManager.refreshCPTList();
                    } else {
                        AIOPMSCPTManager.showNotification(response.data || 'Failed to update CPT', 'error');
                    }
                },
                error: function () {
                    AIOPMSCPTManager.hideLoadingOverlay();
                    AIOPMSCPTManager.showNotification('Network error occurred', 'error');
                }
            });
        },

        duplicateCPT: function (cptSlug) {
            if (!confirm('Are you sure you want to duplicate this custom post type?')) {
                return;
            }

            AIOPMSCPTManager.showLoadingOverlay();

            $.ajax({
                url: aiopms_cpt_data.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiopms_duplicate_cpt',
                    cpt_slug: cptSlug,
                    nonce: aiopms_cpt_data.nonce
                },
                success: function (response) {
                    if (response.success) {
                        AIOPMSCPTManager.showNotification('Custom post type duplicated successfully', 'success');
                        AIOPMSCPTManager.refreshCPTList();
                    } else {
                        AIOPMSCPTManager.showNotification(response.data || 'Failed to duplicate CPT', 'error');
                    }
                },
                error: function () {
                    AIOPMSCPTManager.showNotification('Network error occurred', 'error');
                },
                complete: function () {
                    AIOPMSCPTManager.hideLoadingOverlay();
                }
            });
        },

        deleteCPT: function (cptSlug) {
            const confirmMessage = 'Are you sure you want to delete this custom post type? This action cannot be undone and will affect all posts of this type.';

            if (!confirm(confirmMessage)) {
                return;
            }

            AIOPMSCPTManager.showLoadingOverlay();

            $.ajax({
                url: aiopms_cpt_data.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiopms_delete_cpt_ajax',
                    post_type: cptSlug,
                    nonce: aiopms_cpt_data.nonce
                },
                success: function (response) {
                    if (response.success) {
                        AIOPMSCPTManager.showNotification('Custom post type deleted successfully', 'success');
                        $(`.aiopms-cpt-card[data-cpt="${cptSlug}"]`).fadeOut(300, function () {
                            $(this).remove();
                            AIOPMSCPTManager.updateResultsCount();
                        });
                    } else {
                        // Extract message from WP_Error if applicable
                        const message = response.data && response.data.message ? response.data.message : (response.data || 'Failed to delete CPT');
                        AIOPMSCPTManager.showNotification(message, 'error');
                    }
                },
                error: function () {
                    AIOPMSCPTManager.showNotification('Network error occurred', 'error');
                },
                complete: function () {
                    AIOPMSCPTManager.hideLoadingOverlay();
                }
            });
        },

        // Form handling
        handleCPTFormSubmission: function (e) {
            e.preventDefault();

            const $form = $(this);
            const formData = new FormData(this);
            formData.append('action', 'aiopms_create_cpt_ajax');
            formData.append('nonce', aiopms_cpt_data.nonce);

            // Validate form
            if (!AIOPMSCPTManager.validateCPTForm($form)) {
                return;
            }

            AIOPMSCPTManager.showLoadingOverlay();

            $.ajax({
                url: aiopms_cpt_data.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        AIOPMSCPTManager.showNotification('Custom post type created successfully!', 'success');
                        $form[0].reset();
                        AIOPMSCPTManager.refreshCPTList();

                        // Switch to list tab
                        $('.aiopms-tab[data-tab="list"]').click();
                    } else {
                        AIOPMSCPTManager.showNotification(response.data || 'Failed to create CPT', 'error');
                    }
                },
                error: function () {
                    AIOPMSCPTManager.showNotification('Network error occurred', 'error');
                },
                complete: function () {
                    AIOPMSCPTManager.hideLoadingOverlay();
                }
            });
        },

        // Form validation
        validateCPTForm: function ($form) {
            let isValid = true;
            const requiredFields = $form.find('[required]');

            // Clear previous errors
            $('.aiopms-field-validation').hide().removeClass('error success');

            requiredFields.each(function () {
                const $field = $(this);
                const $validation = $field.closest('.aiopms-form-group').find('.aiopms-field-validation');

                if (!$field.val().trim()) {
                    $validation.addClass('error').text('This field is required').show();
                    isValid = false;
                } else {
                    $validation.removeClass('error').hide();
                }
            });

            // Validate CPT name format
            const cptName = $('#cpt_name').val();
            if (cptName) {
                const nameRegex = /^[a-z_][a-z0-9_]*$/;
                const $nameValidation = $('#cpt_name').closest('.aiopms-form-group').find('.aiopms-field-validation');

                if (!nameRegex.test(cptName)) {
                    $nameValidation.addClass('error').text('Post type name must contain only lowercase letters, numbers, and underscores').show();
                    isValid = false;
                } else if (cptName.length > 20) {
                    $nameValidation.addClass('error').text('Post type name must be 20 characters or less').show();
                    isValid = false;
                } else {
                    $nameValidation.removeClass('error').hide();
                }
            }

            return isValid;
        },

        // Custom field management
        addCustomField: function (e) {
            e.preventDefault();

            const fieldIndex = $('.custom-field-row').length;
            const fieldTemplate = AIOPMSCPTManager.getFieldTemplate(fieldIndex);

            $('#custom-fields-container').append(fieldTemplate);

            // Focus on the new field name input
            $(`#custom_field_${fieldIndex}_name`).focus();

            AIOPMSCPTManager.announceToScreenReader('New custom field added');
        },

        removeCustomField: function (e) {
            e.preventDefault();

            const $fieldRow = $(this).closest('.custom-field-row');
            const fieldName = $fieldRow.find('.field-name-input').val() || 'Untitled field';

            if (confirm(`Are you sure you want to remove the "${fieldName}" field?`)) {
                $fieldRow.fadeOut(300, function () {
                    $(this).remove();
                    AIOPMSCPTManager.announceToScreenReader('Custom field removed');
                });
            }
        },

        getFieldTemplate: function (index) {
            return `
                <div class="custom-field-row" data-field-index="${index}">
                    <div class="aiopms-field-controls">
                        <div class="aiopms-form-group">
                            <label for="custom_field_${index}_name" class="aiopms-form-label">Field Name</label>
                            <input type="text" 
                                   id="custom_field_${index}_name"
                                   name="custom_fields[${index}][name]" 
                                   class="aiopms-form-input field-name-input" 
                                   placeholder="field_name"
                                   required>
                        </div>
                        <div class="aiopms-form-group">
                            <label for="custom_field_${index}_label" class="aiopms-form-label">Field Label</label>
                            <input type="text" 
                                   id="custom_field_${index}_label"
                                   name="custom_fields[${index}][label]" 
                                   class="aiopms-form-input" 
                                   placeholder="Field Label"
                                   required>
                        </div>
                        <div class="aiopms-form-group">
                            <label for="custom_field_${index}_type" class="aiopms-form-label">Field Type</label>
                            <select id="custom_field_${index}_type"
                                    name="custom_fields[${index}][type]" 
                                    class="aiopms-form-select field-type-select">
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="url">URL</option>
                                <option value="email">Email</option>
                                <option value="select">Select</option>
                                <option value="checkbox">Checkbox</option>
                            </select>
                        </div>
                        <div class="aiopms-form-group">
                            <label for="custom_field_${index}_description" class="aiopms-form-label">Description</label>
                            <input type="text" 
                                   id="custom_field_${index}_description"
                                   name="custom_fields[${index}][description]" 
                                   class="aiopms-form-input" 
                                   placeholder="Field description">
                        </div>
                        <div class="aiopms-form-group">
                            <label>
                                <input type="checkbox" 
                                       name="custom_fields[${index}][required]" 
                                       value="1">
                                Required
                            </label>
                        </div>
                        <div class="aiopms-form-group">
                            <button type="button" class="aiopms-btn aiopms-btn-danger remove-field-btn">
                                <span class="dashicons dashicons-trash"></span>
                                Remove
                            </button>
                        </div>
                    </div>
                    <div class="field-options-container" style="display: none;"></div>
                </div>
            `;
        },

        handleFieldTypeChange: function () {
            const $select = $(this);
            const fieldType = $select.val();
            const $fieldRow = $select.closest('.custom-field-row');
            const $optionsContainer = $fieldRow.find('.field-options-container');

            if (fieldType === 'select') {
                $optionsContainer.html(`
                    <div class="aiopms-form-group">
                        <label class="aiopms-form-label">Options (comma-separated)</label>
                        <input type="text" 
                               name="custom_fields[${$fieldRow.data('field-index')}][options]" 
                               class="aiopms-form-input" 
                               placeholder="Option 1, Option 2, Option 3">
                        <div class="aiopms-form-help">Enter options separated by commas</div>
                    </div>
                `).show();
            } else {
                $optionsContainer.hide().empty();
            }
        },

        // Modal functionality
        openModal: function (e) {
            e.preventDefault();

            const modalId = $(this).data('modal');
            const $modal = $(`#${modalId}`);

            if ($modal.length) {
                $modal.fadeIn(200);
                $modal.find('[tabindex], input, button, select, textarea').first().focus();

                // Trap focus within modal
                AIOPMSCPTManager.trapFocus($modal);
            }
        },

        closeModal: function (e) {
            if (e.target === this || $(e.target).hasClass('aiopms-modal-close')) {
                $(this).closest('.aiopms-modal-overlay').fadeOut(200);
            }
        },

        trapFocus: function ($modal) {
            const focusableElements = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            const firstElement = focusableElements.first();
            const lastElement = focusableElements.last();

            $modal.on('keydown.modal-focus', function (e) {
                if (e.key === 'Tab') {
                    if (e.shiftKey && document.activeElement === firstElement[0]) {
                        e.preventDefault();
                        lastElement.focus();
                    } else if (!e.shiftKey && document.activeElement === lastElement[0]) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }

                if (e.key === 'Escape') {
                    AIOPMSCPTManager.closeModal.call($modal[0], e);
                }
            });

            $modal.on('hidden.modal', function () {
                $modal.off('keydown.modal-focus');
            });
        },

        // Tab functionality
        switchTab: function (e) {
            e.preventDefault();

            const $tab = $(this);
            const tabId = $tab.data('tab');

            // Update active tab
            $('.aiopms-tab').removeClass('active');
            $tab.addClass('active');

            // Update tab content
            $('.aiopms-tab-content').removeClass('active');
            $(`#tab-${tabId}`).addClass('active');

            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);

            // Announce tab change
            AIOPMSCPTManager.announceToScreenReader(`Switched to ${$tab.text()} tab`);
        },

        // Keyboard navigation
        handleKeyboardNavigation: function (e) {
            // Handle Escape key globally
            if (e.key === 'Escape') {
                // Close any open modals
                $('.aiopms-modal-overlay:visible').fadeOut(200);
            }
        },

        setupKeyboardNavigation: function () {
            // Add keyboard support for card actions
            $('.aiopms-cpt-card').attr('tabindex', '0').on('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).find('.aiopms-cpt-title a')[0].click();
                }
            });
        },

        // Notification system
        showNotification: function (message, type = 'info', duration = 5000) {
            const iconMap = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };

            const $notification = $(`
                <div class="aiopms-message ${type}" role="alert" aria-live="polite">
                    <span class="aiopms-message-icon">${iconMap[type] || iconMap.info}</span>
                    <span class="aiopms-message-text">${message}</span>
                    <button class="aiopms-message-close" aria-label="Close notification">&times;</button>
                </div>
            `);

            $('#aiopms-messages').prepend($notification);

            // Auto-remove after duration
            setTimeout(() => {
                $notification.fadeOut(300, function () {
                    $(this).remove();
                });
            }, duration);

            // Manual close
            $notification.find('.aiopms-message-close').on('click', function () {
                $notification.fadeOut(300, function () {
                    $(this).remove();
                });
            });

            return $notification;
        },

        // Loading states
        showLoadingOverlay: function () {
            $('#aiopms-loading-overlay').show();
        },

        hideLoadingOverlay: function () {
            $('#aiopms-loading-overlay').hide();
        },

        setupLoadingStates: function () {
            // Add loading states to buttons
            $(document).on('click', '.aiopms-btn[data-loading-text]', function () {
                const $btn = $(this);
                const originalText = $btn.html();
                const loadingText = $btn.data('loading-text');

                $btn.prop('disabled', true).html(`
                    <span class="spinner" style="width: 16px; height: 16px; margin-right: 8px;"></span>
                    ${loadingText}
                `);

                // Store original text for restoration
                $btn.data('original-text', originalText);
            });
        },

        // Accessibility enhancements
        enhanceAriaLabels: function () {
            // Add aria-labels where missing
            $('[data-action]').each(function () {
                const $btn = $(this);
                const action = $btn.data('action');
                const cpt = $btn.data('cpt');

                if (!$btn.attr('aria-label')) {
                    $btn.attr('aria-label', `${action} ${cpt}`);
                }
            });
        },

        setupScreenReaderAnnouncements: function () {
            // Create screen reader announcement area
            if (!$('#sr-announcements').length) {
                $('body').append('<div id="sr-announcements" class="screen-reader-text" aria-live="polite" aria-atomic="true"></div>');
            }
        },

        announceToScreenReader: function (message) {
            $('#sr-announcements').text(message);

            // Clear after announcement
            setTimeout(() => {
                $('#sr-announcements').empty();
            }, 1000);
        },

        announceSearchResults: function (searchTerm) {
            const visibleCount = $('.aiopms-cpt-card:visible').length;
            const totalCount = $('.aiopms-cpt-card').length;

            let message;
            if (searchTerm) {
                message = `${visibleCount} of ${totalCount} custom post types match "${searchTerm}"`;
            } else {
                message = `Showing all ${totalCount} custom post types`;
            }

            this.announceToScreenReader(message);
        },

        // Utility functions
        debounce: function (func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        updateResultsCount: function () {
            const visibleCount = $('.aiopms-cpt-card:visible').length;
            const totalCount = $('.aiopms-cpt-card').length;

            let $counter = $('#results-counter');
            if (!$counter.length) {
                $counter = $('<div id="results-counter" class="results-counter"></div>');
                $('.aiopms-cpt-list-header').append($counter);
            }

            if (visibleCount === totalCount) {
                $counter.text(`${totalCount} custom post types`);
            } else {
                $counter.text(`${visibleCount} of ${totalCount} custom post types`);
            }
        },

        updateCPTGrid: function (cptData) {
            // Update the CPT grid with new data
            // This would be implemented based on the specific data structure
            console.log('Updating CPT grid with data:', cptData);
        },

        loadCPTData: function () {
            // Load initial CPT data if needed
            this.updateResultsCount();
        },

        initializeTooltips: function () {
            // Initialize tooltips for buttons with titles
            $('[title]').each(function () {
                const $element = $(this);
                const title = $element.attr('title');

                $element.on('mouseenter focus', function () {
                    // Show custom tooltip
                }).on('mouseleave blur', function () {
                    // Hide custom tooltip
                });
            });
        },

        initializeValidation: function () {
            // Real-time validation for form fields
            $('[data-validate]').on('input blur', function () {
                const $field = $(this);
                const validationType = $field.data('validate');

                // Perform validation based on type
                AIOPMSCPTManager.validateField($field, validationType);
            });
        },

        validateField: function ($field, type) {
            const value = $field.val();
            const $validation = $field.closest('.aiopms-form-group').find('.aiopms-field-validation');

            let isValid = true;
            let message = '';

            switch (type) {
                case 'slug':
                    const slugRegex = /^[a-z_][a-z0-9_]*$/;
                    isValid = slugRegex.test(value);
                    message = isValid ? 'Valid post type name' : 'Only lowercase letters, numbers, and underscores allowed';
                    break;
                case 'required':
                    isValid = value.trim().length > 0;
                    message = isValid ? '' : 'This field is required';
                    break;
            }

            if (message) {
                $validation.removeClass('error success')
                    .addClass(isValid ? 'success' : 'error')
                    .text(message)
                    .show();
            } else {
                $validation.hide();
            }

            return isValid;
        },

        initializeNotifications: function () {
            // Initialize notification system
            if (!$('#aiopms-messages').length) {
                $('.dg10-card-body').prepend('<div id="aiopms-messages" class="aiopms-messages"></div>');
            }
        },

        autoSave: function () {
            // Auto-save functionality for form data
            const formData = $('#aiopms-cpt-form').serialize();

            // Save to localStorage as backup
            localStorage.setItem('aiopms_cpt_draft', formData);

            // Show subtle indication of auto-save
            const $indicator = $('#auto-save-indicator');
            if ($indicator.length) {
                $indicator.text('Draft saved').fadeIn(200).delay(2000).fadeOut(200);
            }
        },

        // ===== BULK OPERATIONS FUNCTIONALITY =====

        handleSelectAllBulk: function () {
            const isChecked = $('#select-all-bulk').prop('checked');
            $('.aiopms-cpt-checkbox').prop('checked', isChecked);
            this.updateBulkSelection();
        },

        updateBulkSelection: function () {
            const selectedCount = $('.aiopms-cpt-checkbox:checked').length;
            const totalCount = $('.aiopms-cpt-checkbox').length;

            $('#selected-count').text(selectedCount);
            $('#select-all-bulk').prop('checked', selectedCount === totalCount);

            this.toggleBulkActionButton();
        },

        toggleBulkActionButton: function () {
            const hasSelection = $('.aiopms-cpt-checkbox:checked').length > 0;
            const hasAction = $('#bulk-action-selector').val() !== '';

            $('#apply-bulk-action-btn').prop('disabled', !hasSelection || !hasAction);
        },

        handleBulkAction: function (e) {
            e.preventDefault();

            const action = $('#bulk-action-selector').val();
            const selectedCPTs = $('.aiopms-cpt-checkbox:checked').map(function () {
                return $(this).val();
            }).get();

            if (!action || selectedCPTs.length === 0) {
                this.showNotification('Please select an action and at least one CPT.', 'warning');
                return;
            }

            if (action === 'delete' && !confirm('Are you sure you want to delete the selected CPTs? This action cannot be undone.')) {
                return;
            }

            this.showLoadingOverlay();

            $.ajax({
                url: aiopms_cpt_data.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiopms_bulk_cpt_operations',
                    nonce: aiopms_cpt_data.nonce,
                    bulk_action: action,
                    cpt_ids: selectedCPTs
                },
                success: (response) => {
                    this.hideLoadingOverlay();
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.showBulkResults(response.data.results);
                        this.refreshCPTList();
                    } else {
                        this.showNotification(response.data || 'Bulk operation failed.', 'error');
                    }
                },
                error: () => {
                    this.hideLoadingOverlay();
                    this.showNotification('Network error occurred.', 'error');
                }
            });
        },

        showBulkResults: function (results) {
            let resultHtml = '<div class="aiopms-bulk-results"><h4>Operation Results:</h4><ul>';

            results.forEach(function (result) {
                const statusClass = result.status === 'success' ? 'success' : 'error';
                resultHtml += `<li class="${statusClass}"><strong>${result.post_type}:</strong> ${result.message}</li>`;
            });

            resultHtml += '</ul></div>';

            $('#bulk-results-content').html(resultHtml);
            $('#bulk-results').show();
        },

        // ===== IMPORT/EXPORT FUNCTIONALITY =====

        toggleExportSelection: function () {
            const exportType = $('input[name="export_type"]:checked').val();
            if (exportType === 'selected') {
                $('#cpt-selection').show();
            } else {
                $('#cpt-selection').hide();
            }
        },

        handleFileSelection: function () {
            const file = this.files[0];
            if (file) {
                const $label = $(this).closest('.aiopms-upload-label');
                $label.find('.upload-text').text(file.name);
                $label.addClass('file-selected');
            }
        },

        // ===== TEMPLATES FUNCTIONALITY =====

        previewTemplate: function (e) {
            e.preventDefault();

            const templateId = $(this).data('template');
            const $card = $(this).closest('.aiopms-template-card');

            // Get template data (this would normally come from an AJAX call)
            const templateData = this.getTemplateData(templateId);

            if (templateData) {
                this.showTemplatePreview(templateData);
            }
        },

        getTemplateData: function (templateId) {
            // This would normally fetch template data via AJAX
            // For now, return mock data
            return {
                id: templateId,
                name: 'Template Name',
                description: 'Template description',
                fields: [],
                features: []
            };
        },

        showTemplatePreview: function (templateData) {
            const modalHtml = `
                <div class="aiopms-modal" id="template-preview-modal">
                    <div class="aiopms-modal-content aiopms-modal-large">
                        <div class="aiopms-modal-header">
                            <h3>Template Preview: ${templateData.name}</h3>
                            <button class="aiopms-modal-close">&times;</button>
                        </div>
                        <div class="aiopms-modal-body">
                            <p>${templateData.description}</p>
                            <!-- Template preview content would go here -->
                        </div>
                        <div class="aiopms-modal-footer">
                            <button type="button" class="button button-secondary aiopms-modal-close">Close</button>
                            <button type="button" class="button button-primary" data-template="${templateData.id}">Create CPT</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            $('#template-preview-modal').show();

            // Bind close handlers
            $('.aiopms-modal-close').on('click', function () {
                $('#template-preview-modal').remove();
            });
        },

        handleTemplateCreation: function (e) {
            // Template creation is handled by form submission
            // This function can be used for additional validation or feedback
            const $form = $(this);
            const templateId = $form.find('input[name="template_id"]').val();

            // Show loading state
            $form.find('button[type="submit"]').prop('disabled', true).text('Creating...');
        }
    };

    // Initialize when document is ready
    $(document).ready(function () {
        // Only initialize on CPT management pages
        if ($('#aiopms-cpt-management').length) {
            AIOPMSCPTManager.init();
        }
    });

    // Make globally available
    window.AIOPMSCPTManager = AIOPMSCPTManager;

})(jQuery);
