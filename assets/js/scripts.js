jQuery(document).ready(function ($) {
    // ===== SIDEBAR NAVIGATION FUNCTIONALITY =====

    // Handle sidebar navigation
    $('.dg10-sidebar-nav-item').on('click', function (e) {
        // Remove active class from all items
        $('.dg10-sidebar-nav-item').removeClass('active');
        // Add active class to clicked item
        $(this).addClass('active');

        // Optional: Add smooth transition effect
        $(this).css('transform', 'scale(0.98)');
        setTimeout(() => {
            $(this).css('transform', 'scale(1)');
        }, 150);
    });

    // --- NOTICE RELOCATION (AGGRESSIVE) ---
    // Move any standard WP notices into our custom container
    function relocateNotices() {
        var $notices = $('#wpbody-content > .notice, #wpbody-content > .updated, #wpbody-content > .error, .wrap > .notice, .wrap > .updated, .wrap > .error, .notice');
        var $container = $('#aiopms-notices-container');

        if ($container.length) {
            $notices.each(function () {
                var $this = $(this);
                // Don't move if already in container or if it is the container itself
                if ($this.closest('#aiopms-notices-container').length || $this.attr('id') === 'aiopms-notices-container') return;

                $this.detach().appendTo($container);
                $this.css('margin', '10px 0'); // Ensure spacing
            });
        }
    }

    // Run immediately
    relocateNotices();

    // Run on MutationObserver to catch late additions (Elementor/Husky)
    var observer = new MutationObserver(function (mutations) {
        relocateNotices();
    });

    var targetNode = document.getElementById('wpbody-content') || document.body;
    if (targetNode) {
        observer.observe(targetNode, { childList: true, subtree: true });
    }
    // ----------------------------------------

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

    // Add keyboard navigation support
    $('.dg10-sidebar-nav-item').on('keydown', function (e) {
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
    $('.dg10-sidebar-nav-item').on('focus', function () {
        $(this).addClass('focused');
    }).on('blur', function () {
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
                navContainer.animate({ scrollLeft: itemOffset - 20 }, 300);
            } else if (itemOffset + itemWidth > scrollLeft + containerWidth) {
                navContainer.animate({ scrollLeft: itemOffset + itemWidth - containerWidth + 20 }, 300);
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
    $('select[name="aiopms_ai_provider"]').on('change', function () {
        updateImageGenerationCheckbox();
    });

    // Show loading state when generating images
    $('form').on('submit', function () {
        if ($('#aiopms_generate_images').is(':checked') && !$('#aiopms_generate_images').is(':disabled')) {
            $('.submit .spinner').css('visibility', 'visible');
            $('input[type="submit"]').prop('disabled', true).val('Generating Images...');
        }
    });

    // Enhanced AI Generation Loading Animation with DG10 Brand Colors
    $('form').on('submit', function (e) {
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

                // Styles moved to admin-ui.css
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
    $('#aiopms_advanced_mode').on('change', function () {
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
    $('form').on('submit', function (e) {
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
