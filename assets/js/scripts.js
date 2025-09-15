jQuery(document).ready(function($) {
    // ===== ACCESSIBILITY FOUNDATION =====
    
    // Initialize accessibility features
    initAccessibilityFeatures();
    
    // ===== SIDEBAR NAVIGATION FUNCTIONALITY =====
    
    // Handle sidebar navigation with accessibility
    $('.dg10-sidebar-nav-item').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class and aria-current from all items
        $('.dg10-sidebar-nav-item').removeClass('active').attr('aria-current', 'false');
        
        // Add active class and aria-current to clicked item
        $(this).addClass('active').attr('aria-current', 'page');
        
        // Announce navigation to screen readers
        announceToScreenReader($(this).find('.nav-text').text() + ' selected');
        
        // Navigate to the URL
        const href = $(this).attr('href');
        if (href) {
            window.location.href = href;
        }
    });
    
    // Enhanced keyboard navigation for sidebar
    $('.dg10-sidebar-nav-item').on('keydown', function(e) {
        const $items = $('.dg10-sidebar-nav-item');
        const currentIndex = $items.index(this);
        let newIndex = currentIndex;
        
        switch(e.key) {
            case 'ArrowDown':
            case 'ArrowRight':
                e.preventDefault();
                newIndex = (currentIndex + 1) % $items.length;
                break;
            case 'ArrowUp':
            case 'ArrowLeft':
                e.preventDefault();
                newIndex = currentIndex === 0 ? $items.length - 1 : currentIndex - 1;
                break;
            case 'Home':
                e.preventDefault();
                newIndex = 0;
                break;
            case 'End':
                e.preventDefault();
                newIndex = $items.length - 1;
                break;
            case 'Enter':
            case ' ':
                e.preventDefault();
                $(this).click();
                return;
        }
        
        if (newIndex !== currentIndex) {
            $items.eq(newIndex).focus();
        }
    });
    
    // Handle responsive sidebar behavior
    function handleSidebarResponsive() {
        var windowWidth = $(window).width();
        
        if (windowWidth <= 960) {
            // Mobile/tablet view - horizontal scroll
            $('.dg10-sidebar-nav').addClass('mobile-nav');
            $('.dg10-admin-sidebar').addClass('mobile-sidebar');
        } else {
            // Desktop view - vertical sidebar
            $('.dg10-sidebar-nav').removeClass('mobile-nav');
            $('.dg10-admin-sidebar').removeClass('mobile-sidebar');
        }
    }
    
    // Run on load and resize
    handleSidebarResponsive();
    $(window).on('resize', debounce(handleSidebarResponsive, 250));
    
    // Debounce function for performance
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // ===== ACCESSIBILITY HELPER FUNCTIONS =====
    
    /**
     * Initialize accessibility features
     */
    function initAccessibilityFeatures() {
        // Add skip links
        addSkipLinks();
        
        // Initialize live regions
        initLiveRegions();
        
        // Initialize focus management
        initFocusManagement();
        
        // Initialize keyboard shortcuts
        initKeyboardShortcuts();
        
        // Initialize form accessibility
        initFormAccessibility();
        
        // Initialize enhanced form validation
        initEnhancedFormValidation();
        
        // Initialize table accessibility
        initTableAccessibility();
        
        // Initialize loading states
        initLoadingStates();
        
        // Initialize file upload accessibility
        initFileUploadAccessibility();
        
        // Initialize accessibility testing
        initAccessibilityTesting();
    }
    
    /**
     * Add skip links for better navigation
     */
    function addSkipLinks() {
        const $body = $('body');
        
        // Add skip to main content link if not exists
        if (!$body.find('.skip-link').length) {
            $body.prepend('<a href="#main-content" class="skip-link">Skip to main content</a>');
        }
        
        // Add skip to navigation link
        if (!$body.find('.skip-to-nav').length) {
            $body.prepend('<a href="#navigation" class="skip-link skip-to-nav">Skip to navigation</a>');
        }
    }
    
    /**
     * Initialize live regions for screen reader announcements
     */
    function initLiveRegions() {
        // Add live region for announcements
        if (!$('#aiopms-live-region').length) {
            $('body').append('<div id="aiopms-live-region" class="dg10-live-region" aria-live="polite" aria-atomic="true"></div>');
        }
        
        // Add assertive live region for urgent announcements
        if (!$('#aiopms-live-region-assertive').length) {
            $('body').append('<div id="aiopms-live-region-assertive" class="dg10-live-region" aria-live="assertive" aria-atomic="true"></div>');
        }
    }
    
    /**
     * Announce message to screen readers
     */
    function announceToScreenReader(message, assertive = false) {
        const liveRegionId = assertive ? '#aiopms-live-region-assertive' : '#aiopms-live-region';
        const $liveRegion = $(liveRegionId);
        
        // Clear previous message
        $liveRegion.empty();
        
        // Add new message
        setTimeout(() => {
            $liveRegion.text(message);
        }, 100);
        
        // Clear message after announcement
        setTimeout(() => {
            $liveRegion.empty();
        }, 1000);
    }
    
    /**
     * Initialize focus management
     */
    function initFocusManagement() {
        // Trap focus in modals
        $(document).on('keydown', '.dg10-modal', function(e) {
            if (e.key === 'Tab') {
                trapFocus(e, $(this));
            }
        });
        
        // Return focus to trigger element when modal closes
        $(document).on('click', '.dg10-modal-close', function() {
            const triggerElement = $(this).data('trigger-element');
            if (triggerElement) {
                $(triggerElement).focus();
            }
        });
    }
    
    /**
     * Trap focus within an element
     */
    function trapFocus(e, $container) {
        const focusableElements = $container.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        const firstElement = focusableElements.first();
        const lastElement = focusableElements.last();
        
        if (e.shiftKey) {
            if (document.activeElement === firstElement[0]) {
                e.preventDefault();
                lastElement.focus();
            }
        } else {
            if (document.activeElement === lastElement[0]) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }
    
    /**
     * Initialize keyboard shortcuts
     */
    function initKeyboardShortcuts() {
        $(document).on('keydown', function(e) {
            // Alt + M: Focus main content
            if (e.altKey && e.key === 'm') {
                e.preventDefault();
                $('#main-content').focus();
                announceToScreenReader('Focused on main content');
            }
            
            // Alt + N: Focus navigation
            if (e.altKey && e.key === 'n') {
                e.preventDefault();
                $('#navigation').focus();
                announceToScreenReader('Focused on navigation');
            }
            
            // Escape: Close modals and dropdowns
            if (e.key === 'Escape') {
                $('.dg10-modal:visible').hide();
                $('.dg10-dropdown:visible').hide();
                announceToScreenReader('Modal closed');
            }
        });
    }
    
    /**
     * Initialize form accessibility
     */
    function initFormAccessibility() {
        // Add required field indicators
        $('input[required], select[required], textarea[required]').each(function() {
            const $field = $(this);
            const $label = $('label[for="' + $field.attr('id') + '"]');
            
            if ($label.length && !$label.find('.required-indicator').length) {
                $label.append(' <span class="required-indicator" aria-label="required">*</span>');
            }
        });
        
        // Add field descriptions
        $('.description').each(function() {
            const $desc = $(this);
            const $field = $desc.prev('input, select, textarea');
            
            if ($field.length) {
                const fieldId = $field.attr('id');
                if (fieldId) {
                    $desc.attr('id', fieldId + '-description');
                    $field.attr('aria-describedby', fieldId + '-description');
                }
            }
        });
        
        // Real-time validation feedback
        $('input, select, textarea').on('blur', function() {
            validateField($(this));
        });
    }
    
    /**
     * Validate a form field
     */
    function validateField($field) {
        const value = $field.val();
        const isRequired = $field.prop('required');
        const type = $field.attr('type');
        let isValid = true;
        let message = '';
        
        // Required field validation
        if (isRequired && !value.trim()) {
            isValid = false;
            message = 'This field is required';
        }
        
        // Email validation
        if (type === 'email' && value && !isValidEmail(value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }
        
        // URL validation
        if (type === 'url' && value && !isValidUrl(value)) {
            isValid = false;
            message = 'Please enter a valid URL';
        }
        
        // Update field state
        $field.removeClass('dg10-error dg10-success');
        $field.attr('aria-invalid', !isValid);
        
        if (!isValid) {
            $field.addClass('dg10-error');
            announceToScreenReader(message, true);
        } else if (value) {
            $field.addClass('dg10-success');
        }
        
        // Update or create error message
        const $errorMsg = $field.siblings('.field-error');
        if (!isValid) {
            if ($errorMsg.length) {
                $errorMsg.text(message);
            } else {
                $field.after('<div class="field-error dg10-error" role="alert">' + message + '</div>');
            }
        } else {
            $errorMsg.remove();
        }
    }
    
    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Validate URL format
     */
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
    
    /**
     * Initialize table accessibility
     */
    function initTableAccessibility() {
        $('.dg10-table').each(function() {
            const $table = $(this);
            
            // Add table caption if missing
            if (!$table.find('caption').length) {
                $table.prepend('<caption class="sr-only">Data table</caption>');
            }
            
            // Make table rows focusable
            $table.find('tbody tr').attr('tabindex', '0');
            
            // Add keyboard navigation for table rows
            $table.find('tbody tr').on('keydown', function(e) {
                const $rows = $table.find('tbody tr');
                const currentIndex = $rows.index(this);
                
                switch(e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        if (currentIndex < $rows.length - 1) {
                            $rows.eq(currentIndex + 1).focus();
                        }
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        if (currentIndex > 0) {
                            $rows.eq(currentIndex - 1).focus();
                        }
                        break;
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        $(this).click();
                        break;
                }
            });
        });
    }
    
    /**
     * Initialize loading states
     */
    function initLoadingStates() {
        // Add loading states to buttons
        $('.dg10-btn').on('click', function() {
            const $btn = $(this);
            if ($btn.data('loading') !== 'true') {
                $btn.addClass('dg10-loading');
                $btn.attr('aria-disabled', 'true');
                $btn.data('loading', 'true');
                
                // Announce loading state
                announceToScreenReader('Loading, please wait');
            }
        });
        
        // Remove loading state when AJAX completes
        $(document).ajaxComplete(function() {
            $('.dg10-btn.dg10-loading').removeClass('dg10-loading').attr('aria-disabled', 'false').data('loading', 'false');
        });
    }
    
    /**
     * Initialize file upload accessibility
     */
    function initFileUploadAccessibility() {
        $('.dg10-file-input').on('change', function() {
            const $input = $(this);
            const $status = $input.siblings('.dg10-file-status');
            const files = this.files;
            
            if (files.length > 0) {
                const file = files[0];
                const fileName = file.name;
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                const fileType = file.type;
                
                // Validate file type
                if (fileType !== 'text/csv' && !fileName.toLowerCase().endsWith('.csv')) {
                    $status.removeClass('success info').addClass('error');
                    $status.text('Please select a valid CSV file');
                    $input.attr('aria-invalid', 'true');
                    announceToScreenReader('Invalid file type selected. Please choose a CSV file.', true);
                    return;
                }
                
                // Validate file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    $status.removeClass('success info').addClass('error');
                    $status.text('File size too large. Maximum size is 5MB');
                    $input.attr('aria-invalid', 'true');
                    announceToScreenReader('File size too large. Please choose a smaller file.', true);
                    return;
                }
                
                // Success state
                $status.removeClass('error info').addClass('success');
                $status.text(`Selected: ${fileName} (${fileSize}MB)`);
                $input.attr('aria-invalid', 'false');
                announceToScreenReader(`File selected: ${fileName}`);
            } else {
                $status.removeClass('success error info').text('');
                $input.attr('aria-invalid', 'false');
            }
        });
        
        // Add drag and drop accessibility
        $('.dg10-file-upload-wrapper').on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
            announceToScreenReader('File drag over upload area');
        });
        
        $('.dg10-file-upload-wrapper').on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
        });
        
        $('.dg10-file-upload-wrapper').on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                const $input = $(this).find('.dg10-file-input');
                $input[0].files = files;
                $input.trigger('change');
            }
        });
    }
    
    /**
     * Initialize enhanced form validation
     */
    function initEnhancedFormValidation() {
        // Real-time validation for all form fields
        $('input, select, textarea').on('input blur', function() {
            validateField($(this));
        });
        
        // Form submission validation
        $('form').on('submit', function(e) {
            const $form = $(this);
            let hasErrors = false;
            
            // Validate all required fields
            $form.find('input[required], select[required], textarea[required]').each(function() {
                const $field = $(this);
                validateField($field);
                
                if ($field.attr('aria-invalid') === 'true') {
                    hasErrors = true;
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                announceToScreenReader('Please fix the errors before submitting the form', true);
                
                // Focus first error field
                const $firstError = $form.find('[aria-invalid="true"]').first();
                if ($firstError.length) {
                    $firstError.focus();
                }
            }
        });
    }
    
    /**
     * Initialize accessibility testing and validation
     */
    function initAccessibilityTesting() {
        // Add accessibility testing tools in development mode
        if (window.location.hostname === 'localhost' || window.location.hostname.includes('dev')) {
            // Add accessibility testing button
            $('body').append(`
                <div id="aiopms-accessibility-tools" style="position: fixed; top: 10px; right: 10px; z-index: 999999; background: #000; color: #fff; padding: 10px; border-radius: 5px; font-size: 12px;">
                    <button id="test-contrast" style="margin: 2px; padding: 5px;">Test Contrast</button>
                    <button id="test-keyboard" style="margin: 2px; padding: 5px;">Test Keyboard</button>
                    <button id="test-screen-reader" style="margin: 2px; padding: 5px;">Test Screen Reader</button>
                </div>
            `);
            
            // Test contrast ratios
            $('#test-contrast').on('click', function() {
                testContrastRatios();
            });
            
            // Test keyboard navigation
            $('#test-keyboard').on('click', function() {
                testKeyboardNavigation();
            });
            
            // Test screen reader announcements
            $('#test-screen-reader').on('click', function() {
                testScreenReaderAnnouncements();
            });
        }
    }
    
    /**
     * Test contrast ratios
     */
    function testContrastRatios() {
        const elements = $('*').filter(function() {
            const $el = $(this);
            const computedStyle = window.getComputedStyle(this);
            const color = computedStyle.color;
            const backgroundColor = computedStyle.backgroundColor;
            return color !== 'rgba(0, 0, 0, 0)' && backgroundColor !== 'rgba(0, 0, 0, 0)';
        });
        
        let results = [];
        elements.each(function() {
            const $el = $(this);
            const text = $el.text().trim();
            if (text.length > 0) {
                results.push({
                    element: this,
                    text: text.substring(0, 50),
                    contrast: 'N/A' // Would need actual contrast calculation
                });
            }
        });
        
        console.log('Contrast Test Results:', results);
        announceToScreenReader('Contrast test completed. Check console for results.');
    }
    
    /**
     * Test keyboard navigation
     */
    function testKeyboardNavigation() {
        const focusableElements = $('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        let results = [];
        
        focusableElements.each(function(index) {
            const $el = $(this);
            results.push({
                index: index,
                element: this,
                tagName: this.tagName,
                text: $el.text().trim().substring(0, 30),
                hasAriaLabel: !!$el.attr('aria-label'),
                hasAriaLabelledBy: !!$el.attr('aria-labelledby'),
                tabIndex: $el.attr('tabindex')
            });
        });
        
        console.log('Keyboard Navigation Test Results:', results);
        announceToScreenReader(`Found ${results.length} focusable elements. Check console for details.`);
    }
    
    /**
     * Test screen reader announcements
     */
    function testScreenReaderAnnouncements() {
        const testMessages = [
            'Testing screen reader announcements',
            'This is a test of the live region functionality',
            'Accessibility testing complete'
        ];
        
        testMessages.forEach((message, index) => {
            setTimeout(() => {
                announceToScreenReader(message);
            }, index * 1000);
        });
    }
    
    // Add keyboard navigation support
    $('.dg10-sidebar-nav-item').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).click();
        }
        
        // Arrow key navigation
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            var items = $('.dg10-sidebar-nav-item');
            var currentIndex = items.index(this);
            var nextIndex;
            
            if (e.key === 'ArrowDown') {
                nextIndex = (currentIndex + 1) % items.length;
            } else {
                nextIndex = (currentIndex - 1 + items.length) % items.length;
            }
            
            items.eq(nextIndex).focus();
        }
    });
    
    // Focus management for accessibility
    $('.dg10-sidebar-nav-item').on('focus', function() {
        $(this).addClass('focused');
    }).on('blur', function() {
        $(this).removeClass('focused');
    });
    
    // Auto-scroll active item into view on mobile
    function scrollActiveItemIntoView() {
        var activeItem = $('.dg10-sidebar-nav-item.active');
        if (activeItem.length && $('.dg10-sidebar-nav').hasClass('mobile-nav')) {
            var navContainer = $('.dg10-sidebar-nav');
            var itemOffset = activeItem.position().left;
            var itemWidth = activeItem.outerWidth();
            var containerWidth = navContainer.width();
            var scrollLeft = navContainer.scrollLeft();
            
            if (itemOffset < scrollLeft) {
                navContainer.animate({scrollLeft: itemOffset - 20}, 300);
            } else if (itemOffset + itemWidth > scrollLeft + containerWidth) {
                navContainer.animate({scrollLeft: itemOffset + itemWidth - containerWidth + 20}, 300);
            }
        }
    }
    
    // Run scroll function on load
    setTimeout(scrollActiveItemIntoView, 100);
    
    // ===== EXISTING FUNCTIONALITY =====
    
    // Handle AI provider change to enable/disable image generation checkbox
    function updateImageGenerationCheckbox() {
        var provider = $('select[name="aiopms_ai_provider"]').val();
        var generateImagesCheckbox = $('#aiopms_generate_images');
        
        if (provider === 'deepseek') {
            generateImagesCheckbox.prop('disabled', true);
            generateImagesCheckbox.prop('checked', false);
        } else {
            generateImagesCheckbox.prop('disabled', false);
        }
    }
    
    // Update on page load
    updateImageGenerationCheckbox();
    
    // Update when provider changes
    $('select[name="aiopms_ai_provider"]').on('change', function() {
        updateImageGenerationCheckbox();
    });
    
    // Show loading state when generating images
    $('form').on('submit', function() {
        if ($('#aiopms_generate_images').is(':checked') && !$('#aiopms_generate_images').is(':disabled')) {
            $('.submit .spinner').css('visibility', 'visible');
            $('input[type="submit"]').prop('disabled', true).val('Generating Images...');
        }
    });

    // Enhanced AI Generation Loading Animation with DG10 Brand Colors
    $('form').on('submit', function(e) {
        var $form = $(this);
        var submitButton = $form.find('input[type="submit"], button[type="submit"]');
        var buttonText = submitButton.val() || submitButton.text();
        
        // Check if this is the AI generation form (has business_type field)
        if ($form.find('input[name="aiopms_business_type"]').length > 0 && (buttonText.includes('Generate') || buttonText.includes('Suggestions'))) {
            // Create enhanced loading overlay with brand styling
            if ($('#aiopms-loading-overlay').length === 0) {
                $('body').append(`
                    <div id="aiopms-loading-overlay" class="dg10-loading-overlay">
                        <div class="dg10-loading-content">
                            <div class="dg10-loading-spinner"></div>
                            <h3 class="dg10-loading-title">
                                🤖 Analyzing Your Business with AI
                            </h3>
                            <p class="dg10-loading-message">
                                Crafting the perfect page structure for your needs...
                            </p>
                            <div class="dg10-loading-progress">
                                <div class="dg10-progress-bar">
                                    <div class="dg10-progress-fill"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                
                // Add enhanced CSS animations
                $('<style>')
                    .prop('type', 'text/css')
                    .html(`
                        .dg10-loading-overlay {
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background: rgba(255, 255, 255, 0.95);
                            backdrop-filter: blur(8px);
                            z-index: 9999;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            animation: dg10-fade-in 0.3s ease-out;
                        }
                        
                        .dg10-loading-content {
                            text-align: center;
                            max-width: 500px;
                            padding: 2rem;
                            background: white;
                            border-radius: 16px;
                            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                            border: 1px solid rgba(180, 124, 253, 0.1);
                        }
                        
                        .dg10-loading-spinner {
                            width: 4rem;
                            height: 4rem;
                            margin: 0 auto 1.5rem;
                            border: 4px solid rgba(180, 124, 253, 0.2);
                            border-top: 4px solid #B47CFD;
                            border-radius: 50%;
                            animation: dg10-spin 1.5s linear infinite;
                        }
                        
                        .dg10-loading-title {
                            color: #B47CFD;
                            margin: 0 0 0.5rem 0;
                            font-size: 1.25rem;
                            font-weight: 600;
                            background: linear-gradient(135deg, #B47CFD 0%, #FF7FC2 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                            background-clip: text;
                        }
                        
                        .dg10-loading-message {
                            color: #6B7280;
                            margin: 0 0 1.5rem 0;
                            font-size: 0.875rem;
                            line-height: 1.5;
                        }
                        
                        .dg10-loading-progress {
                            width: 100%;
                            margin-top: 1rem;
                        }
                        
                        .dg10-progress-bar {
                            width: 100%;
                            height: 6px;
                            background: rgba(180, 124, 253, 0.1);
                            border-radius: 3px;
                            overflow: hidden;
                        }
                        
                        .dg10-progress-fill {
                            height: 100%;
                            background: linear-gradient(135deg, #B47CFD 0%, #FF7FC2 100%);
                            border-radius: 3px;
                            animation: dg10-progress-pulse 2s ease-in-out infinite;
                        }
                        
                        @keyframes dg10-spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                        
                        @keyframes dg10-fade-in {
                            0% { opacity: 0; }
                            100% { opacity: 1; }
                        }
                        
                        @keyframes dg10-progress-pulse {
                            0%, 100% { width: 30%; }
                            50% { width: 70%; }
                        }
                    `)
                    .appendTo('head');
            }
            
            // Show loading overlay with animation
            $('#aiopms-loading-overlay').fadeIn(300);
            
            // Disable submit button and update text
            submitButton.prop('disabled', true);
            if (submitButton.is('input')) {
                submitButton.val('🤖 Analyzing with AI...');
            } else {
                submitButton.html('🤖 Analyzing with AI...');
            }
        }
        
        // Check if this is the page creation form (has selected_pages field)
        if ($form.find('input[name="aiopms_selected_pages[]"]').length > 0 && (buttonText.includes('Create') || buttonText.includes('Pages'))) {
            // Create enhanced loading overlay for page creation
            if ($('#aiopms-loading-overlay').length === 0) {
                $('body').append(`
                    <div id="aiopms-loading-overlay" class="dg10-loading-overlay">
                        <div class="dg10-loading-content">
                            <div class="dg10-loading-spinner"></div>
                            <h3 class="dg10-loading-title">
                                🚀 Generating Awesome Pages with Context-Aware AI
                            </h3>
                            <p class="dg10-loading-message">
                                This may take a few moments. Please don't close this window...
                            </p>
                            <div class="dg10-loading-progress">
                                <div class="dg10-progress-bar">
                                    <div class="dg10-progress-fill"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }
            
            // Show loading overlay with animation
            $('#aiopms-loading-overlay').fadeIn(300);
            
            // Disable submit button and update text
            submitButton.prop('disabled', true);
            if (submitButton.is('input')) {
                submitButton.val('🚀 Creating Pages...');
            } else {
                submitButton.html('🚀 Creating Pages...');
            }
        }
    });

    // Remove loading overlay when page reloads (after form submission)
    if (window.history && window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    // ===== AI GENERATOR ADVANCED MODE FUNCTIONALITY =====
    
    // Handle Advanced Mode toggle
    $('#aiopms_advanced_mode').on('change', function() {
        const isAdvancedMode = $(this).is(':checked');
        const $description = $(this).closest('td').find('.description');
        
        if (isAdvancedMode) {
            // Show advanced mode description
            $description.html(`
                <strong>Standard Mode:</strong> Creates standard pages only<br>
                <strong>Advanced Mode:</strong> Analyzes your business and suggests custom post types with relevant fields<br>
                <em style="color: #2271b1; font-weight: bold;">✓ Advanced Mode enabled - AI will analyze your business and suggest custom post types below</em>
            `);
            
            // Add visual indicator
            $(this).closest('tr').addClass('advanced-mode-active');
            
            // Show additional fields if needed
            showAdvancedModeFields();
        } else {
            // Show standard mode description
            $description.html(`
                <strong>Standard Mode:</strong> Creates standard pages only<br>
                <strong>Advanced Mode:</strong> Analyzes your business and suggests custom post types with relevant fields<br>
                <em>Advanced Mode will show business analysis and custom post type suggestions below</em>
            `);
            
            // Remove visual indicator
            $(this).closest('tr').removeClass('advanced-mode-active');
            
            // Hide additional fields
            hideAdvancedModeFields();
        }
    });
    
    // Show additional fields for Advanced Mode
    function showAdvancedModeFields() {
        // Add any additional fields that should appear in Advanced Mode
        // This could include more detailed business analysis options
    }
    
    // Hide additional fields for Standard Mode
    function hideAdvancedModeFields() {
        // Hide any Advanced Mode specific fields
    }
    
    // Enhanced form submission for Advanced Mode
    $('form').on('submit', function(e) {
        const isAdvancedMode = $('#aiopms_advanced_mode').is(':checked');
        
        if (isAdvancedMode) {
            // Add loading state for Advanced Mode
            const $submitBtn = $(this).find('input[type="submit"]');
            const originalText = $submitBtn.val();
            
            $submitBtn.val('🤖 AI is analyzing your business...').prop('disabled', true);
            
            // Add progress indicator
            if (!$('#ai-analyzing-indicator').length) {
                $('<div id="ai-analyzing-indicator" class="aiopms-ai-progress">' +
                  '<div class="aiopms-progress-bar">' +
                  '<div class="aiopms-progress-fill"></div>' +
                  '</div>' +
                  '<p>AI is analyzing your business and generating custom post type suggestions...</p>' +
                  '</div>').insertAfter($submitBtn);
            }
            
            // Reset button after 3 seconds (in case of errors)
            setTimeout(() => {
                $submitBtn.val(originalText).prop('disabled', false);
                $('#ai-analyzing-indicator').remove();
            }, 3000);
        }
    });
});
