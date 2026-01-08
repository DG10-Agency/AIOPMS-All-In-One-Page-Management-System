<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add admin menu
function aiopms_add_admin_menu() {
    add_menu_page(
        __('AIOPMS - Page Management', 'aiopms'),
        __('AIOPMS', 'aiopms'),
        'manage_options',
        'aiopms-page-management',
        'aiopms_admin_page',
        AIOPMS_PLUGIN_URL . 'assets/images/logo.svg',
        25
    );
}
add_action('admin_menu', 'aiopms_add_admin_menu');

// Admin page content
function aiopms_admin_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'manual';
    
    // Define menu items with their details
    $menu_items = array(
        'manual' => array(
            'title' => __('Manual Page Creation', 'aiopms'),
            'icon' => '',
            'description' => __('Create pages manually with custom hierarchy and attributes', 'aiopms')
        ),
        'csv' => array(
            'title' => __('CSV Upload', 'aiopms'),
            'icon' => '',
            'description' => __('Bulk import pages from CSV files', 'aiopms')
        ),
        'ai' => array(
            'title' => __('AI Generation', 'aiopms'),
            'icon' => '',
            'description' => __('Generate pages with AI assistance', 'aiopms')
        ),
        'schema' => array(
            'title' => __('Schema Generator', 'aiopms'),
            'icon' => '',
            'description' => __('Create structured data markup', 'aiopms')
        ),
        'menu' => array(
            'title' => __('Menu Generator', 'aiopms'),
            'icon' => '',
            'description' => __('Automatically generate WordPress menus', 'aiopms')
        ),
        'hierarchy' => array(
            'title' => __('Page Hierarchy', 'aiopms'),
            'icon' => '',
            'description' => __('Visualize and manage page structure', 'aiopms')
        ),
        'keyword-analysis' => array(
            'title' => __('Keyword Analysis', 'aiopms'),
            'icon' => '',
            'description' => __('Analyze keyword density and SEO', 'aiopms')
        ),
        'cpt' => array(
            'title' => __('Custom Post Types', 'aiopms'),
            'icon' => '',
            'description' => __('Create and manage custom post types and fields', 'aiopms')
        ),
        'settings' => array(
            'title' => __('Settings', 'aiopms'),
            'icon' => '',
            'description' => __('Configure plugin options', 'aiopms')
        )
    );
    ?>
    <div class="wrap dg10-brand">
        <!-- Skip Link for Accessibility - Positioned at page level -->
        <a href="#page-title" class="skip-link"><?php _e('Skip to main content', 'aiopms'); ?></a>
        
        <div class="dg10-main-layout">
            <!-- Admin Sidebar -->
            <aside class="dg10-admin-sidebar" role="complementary" aria-label="<?php esc_attr_e('AIOPMS Navigation Menu', 'aiopms'); ?>">
                <div class="dg10-sidebar-header">
                    <div class="dg10-sidebar-title">
                        <img src="<?php echo AIOPMS_PLUGIN_URL; ?>assets/images/logo.svg" alt="<?php esc_attr_e('AIOPMS Plugin Logo', 'aiopms'); ?>" class="dg10-logo-img" role="img">
                        <span class="dg10-plugin-name"><?php _e('AIOPMS', 'aiopms'); ?></span>
                    </div>
                    <p class="dg10-sidebar-subtitle" role="text"><?php _e('All In One Page Management System', 'aiopms'); ?></p>
                </div>
                
                <nav class="dg10-sidebar-nav" role="navigation" aria-label="<?php esc_attr_e('Main Navigation', 'aiopms'); ?>">
                    <ul role="list">
                        <?php foreach ($menu_items as $tab_key => $item): ?>
                            <li role="listitem">
                                <a href="?page=aiopms-page-management&tab=<?php echo esc_attr($tab_key); ?>" 
                                   class="dg10-sidebar-nav-item <?php echo $active_tab == $tab_key ? 'active' : ''; ?>"
                                   role="menuitem"
                                   aria-label="<?php echo esc_attr($item['title'] . ' - ' . $item['description']); ?>"
                                   aria-current="<?php echo $active_tab == $tab_key ? 'page' : 'false'; ?>"
                                   title="<?php echo esc_attr($item['description']); ?>">
                                    <span class="nav-icon" aria-hidden="true" role="img" aria-label="<?php echo esc_attr($item['title'] . ' icon'); ?>"><?php echo esc_html($item['icon']); ?></span>
                                    <span class="nav-text"><?php echo esc_html($item['title']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </aside>
            
            <!-- Main Content Area -->
            <main class="dg10-main-content" role="main" aria-label="<?php esc_attr_e('Main Content Area', 'aiopms'); ?>">
                <!-- Notification Container for JS Relocation -->
                <div id="aiopms-notices-container" class="dg10-notices-area"></div>

                <article class="dg10-card" role="article" aria-labelledby="page-title">
                    <header class="dg10-card-header" role="banner">
                        <div class="dg10-hero-content">
                            <div class="dg10-hero-text">
                                <?php 
                                // Safe access with fallback for undefined tabs
                                $tab_title = isset($menu_items[$active_tab]['title']) 
                                    ? $menu_items[$active_tab]['title'] 
                                    : __('AIOPMS', 'aiopms');
                                $tab_description = isset($menu_items[$active_tab]['description']) 
                                    ? $menu_items[$active_tab]['description'] 
                                    : '';
                                ?>
                                <h1 id="page-title" role="heading" aria-level="1"><?php echo esc_html($tab_title); ?></h1>
                                <p class="dg10-hero-description" role="text" aria-describedby="page-title">
                                    <?php echo esc_html($tab_description); ?>
                                </p>
                            </div>
                        </div>
                    </header>
                    <div class="dg10-card-body" role="main" aria-label="<?php esc_attr_e('Content Section', 'aiopms'); ?>">
        <?php
        if ($active_tab == 'manual') {
            aiopms_manual_creation_tab();
        } elseif ($active_tab == 'csv') {
            aiopms_csv_upload_tab();
        } elseif ($active_tab == 'ai') {
            aiopms_ai_generation_tab();
        } elseif ($active_tab == 'schema') {
            aiopms_schema_generator_tab();
        } elseif ($active_tab == 'menu') {
            aiopms_menu_generator_tab();
        } elseif ($active_tab == 'hierarchy') {
            abpcwa_hierarchy_tab();
        } elseif ($active_tab == 'keyword-analysis') {
            aiopms_keyword_analysis_tab();
        } elseif ($active_tab == 'cpt') {
            // Check if function exists (it should as it's included in main plugin file)
            if (function_exists('aiopms_render_cpt_management_content')) {
                aiopms_render_cpt_management_content(true);
            }
        } elseif ($active_tab == 'templates') {
            // Templates tab - render CPT templates directly
            if (function_exists('aiopms_cpt_templates_tab')) {
                aiopms_cpt_templates_tab();
            } else {
                echo '<div class="notice notice-warning"><p>' . esc_html__('Templates functionality is part of the CPT Manager. Please access it via the Custom Post Types tab.', 'aiopms') . '</p></div>';
            }
        } elseif ($active_tab == 'settings') {
            aiopms_settings_tab();
        }
        ?>
                    </div>
                    <footer class="dg10-card-footer" role="contentinfo" aria-label="<?php esc_attr_e('About DG10 Agency', 'aiopms'); ?>">
                        <section class="dg10-promotion-section" role="region" aria-labelledby="about-us-heading">
                            <header class="dg10-promotion-header">
                                <img src="<?php echo AIOPMS_PLUGIN_URL; ?>assets/images/dg10-brand-logo.svg" alt="<?php esc_attr_e('DG10 Agency Logo', 'aiopms'); ?>" class="dg10-promotion-logo" role="img">
                                <h3 id="about-us-heading" role="heading" aria-level="3"><?php _e('About us', 'aiopms'); ?></h3>
                            </header>
                            <div class="dg10-promotion-content">
                                <p role="text"><?php _e('DG10 Agency specializes in creating powerful WordPress and Elementor solutions. We help businesses build custom websites, optimize performance, and implement complex integrations that drive results.', 'aiopms'); ?></p>
                                <div class="dg10-promotion-buttons" role="group" aria-label="<?php esc_attr_e('Action Buttons', 'aiopms'); ?>">
                                    <a href="https://www.dg10.agency" target="_blank" class="dg10-btn dg10-btn-primary" role="button" aria-label="<?php esc_attr_e('Visit DG10 Agency Website - Opens in new tab', 'aiopms'); ?>">
                                        <span class="btn-text"><?php _e('Visit Website', 'aiopms'); ?></span>
                                        <span class="dg10-btn-icon" aria-hidden="true" role="img" aria-label="<?php esc_attr_e('External link icon'); ?>">→</span>
                                    </a>
                                    <a href="https://calendly.com/dg10-agency/30min" target="_blank" class="dg10-btn dg10-btn-outline" role="button" aria-label="<?php esc_attr_e('Book a Free Consultation - Opens in new tab', 'aiopms'); ?>">
                                        <span class="dg10-btn-icon" aria-hidden="true" role="img" aria-label="<?php esc_attr_e('Calendar icon'); ?>">📅</span>
                                        <span class="btn-text"><?php _e('Book a Free Consultation', 'aiopms'); ?></span>
                                    </a>
                                </div>
                                <p class="dg10-promotion-footer" role="text">
                                    <?php printf(__('This is an open-source project - please %s.', 'aiopms'), '<a href="' . esc_url(AIOPMS_GITHUB_URL) . '" target="_blank" role="link" aria-label="' . esc_attr__('Star the repository on GitHub - Opens in new tab', 'aiopms') . '">' . __('star the repo on GitHub', 'aiopms') . '</a>'); ?>
                                </p>
                            </div>
                        </section>
                    </footer>
                </article>
            </main>
        </div>
    </div>
    
    <!-- Sidebar JavaScript -->
    <script>
    jQuery(document).ready(function($) {
        // Handle sidebar navigation
        $('.dg10-sidebar-nav-item').on('click', function(e) {
            // Remove active class and aria-current from all items
            $('.dg10-sidebar-nav-item').removeClass('active').attr('aria-current', 'false');
            // Add active class and aria-current to clicked item
            $(this).addClass('active').attr('aria-current', 'page');
        });
        
        // Handle responsive sidebar behavior
        function handleSidebarResponsive() {
            if ($(window).width() <= 960) {
                // Mobile/tablet view - horizontal scroll
                $('.dg10-sidebar-nav').addClass('mobile-nav').attr('aria-orientation', 'horizontal');
            } else {
                // Desktop view - vertical sidebar
                $('.dg10-sidebar-nav').removeClass('mobile-nav').attr('aria-orientation', 'vertical');
            }
        }
        
        // Run on load and resize
        handleSidebarResponsive();
        $(window).on('resize', handleSidebarResponsive);
        
        // Smooth scroll for mobile navigation
        $('.dg10-sidebar-nav').on('scroll', function() {
            // Optional: Add scroll indicators or other mobile nav enhancements
        });
        
        // Enhanced keyboard navigation support
        $('.dg10-sidebar-nav-item').on('keydown', function(e) {
            var $items = $('.dg10-sidebar-nav-item');
            var currentIndex = $items.index(this);
            var $currentItem = $(this);
            
            switch(e.key) {
                case 'Enter':
                case ' ':
                    e.preventDefault();
                    $currentItem.click();
                    break;
                case 'ArrowDown':
                case 'ArrowRight':
                    e.preventDefault();
                    var nextIndex = (currentIndex + 1) % $items.length;
                    $items.eq(nextIndex).focus();
                    break;
                case 'ArrowUp':
                case 'ArrowLeft':
                    e.preventDefault();
                    var prevIndex = currentIndex === 0 ? $items.length - 1 : currentIndex - 1;
                    $items.eq(prevIndex).focus();
                    break;
                case 'Home':
                    e.preventDefault();
                    $items.first().focus();
                    break;
                case 'End':
                    e.preventDefault();
                    $items.last().focus();
                    break;
            }
        });
        
        // Add focus management for better accessibility
        $('.dg10-sidebar-nav-item').on('focus', function() {
            $(this).attr('aria-selected', 'true');
        }).on('blur', function() {
            $(this).attr('aria-selected', 'false');
        });
        
        // Focus management for accessibility
        $('.dg10-sidebar-nav-item').on('focus', function() {
            $(this).addClass('focused');
        }).on('blur', function() {
            $(this).removeClass('focused');
        });
    });
    </script>
    
    <!-- Sidebar Styles moved to admin-ui.css -->
    <?php
}

// Manual creation tab content
function aiopms_manual_creation_tab() {
    ?>
    <div class="aiopms-tab-content">
        <form method="post" action="" role="form" aria-label="<?php esc_attr_e('Manual Page Creation Form', 'aiopms'); ?>">
                <?php wp_nonce_field('aiopms_manual_create_pages'); ?>
                <fieldset class="dg10-form-group">
                    <legend class="sr-only"><?php _e('Page Creation Settings', 'aiopms'); ?></legend>
                    <div class="dg10-form-field">
                        <label for="aiopms_titles" class="dg10-form-label" id="titles-label"><?php _e('Page Titles', 'aiopms'); ?></label>
                        <textarea name="aiopms_titles" 
                                  id="aiopms_titles" 
                                  rows="10" 
                                  class="dg10-form-textarea" 
                                  placeholder="<?php esc_attr_e('Enter one page title per line. Use hyphens for nesting...', 'aiopms'); ?>"
                                  aria-labelledby="titles-label"
                                  aria-describedby="syntax-guide"
                                  aria-required="true"
                                  role="textbox"></textarea>
                        <div id="syntax-guide" class="dg10-form-help" role="region" aria-label="<?php esc_attr_e('Syntax Guide', 'aiopms'); ?>">
                            <strong><?php _e('Syntax Guide:', 'aiopms'); ?></strong><br>
                            • <?php _e('Use', 'aiopms'); ?> <code>-</code> <?php _e('for child pages (one hyphen per level)', 'aiopms'); ?><br>
                            • <?php _e('Use', 'aiopms'); ?> <code>:+</code> <?php _e('for meta description', 'aiopms'); ?><br>
                            • <?php _e('Use', 'aiopms'); ?> <code>:*</code> <?php _e('for featured image URL', 'aiopms'); ?><br>
                            • <?php _e('Use', 'aiopms'); ?> <code>::template=template-name.php</code> <?php _e('for page template', 'aiopms'); ?><br>
                            • <?php _e('Use', 'aiopms'); ?> <code>::status=draft</code> <?php _e('for post status', 'aiopms'); ?><br>
                            • <strong><?php _e('SEO slugs are automatically generated', 'aiopms'); ?></strong> (<?php _e('max 72 chars', 'aiopms'); ?>)
                        </div>
                    </div>
                </fieldset>
                <div class="dg10-form-actions">
                    <button type="submit" 
                            name="submit" 
                            class="dg10-btn dg10-btn-primary"
                            role="button"
                            aria-label="<?php esc_attr_e('Create Pages from Titles', 'aiopms'); ?>">
                        <span class="btn-icon" aria-hidden="true" role="img" aria-label="<?php esc_attr_e('Rocket icon'); ?>">🚀</span>
                        <span class="btn-text"><?php _e('Create Pages', 'aiopms'); ?></span>
                </button>
            </form>
    </div>
    <?php
    if (isset($_POST['submit']) && check_admin_referer('aiopms_manual_create_pages')) {
        aiopms_create_pages_manually($_POST['aiopms_titles']);
    }
}

// CSV upload tab content
function aiopms_csv_upload_tab() {
    ?>
    <div class="aiopms-tab-content">
            <form method="post" action="" enctype="multipart/form-data" role="form" aria-label="<?php esc_attr_e('CSV File Upload Form', 'aiopms'); ?>">
                <?php wp_nonce_field('aiopms_csv_upload'); ?>
                <fieldset class="dg10-form-group">
                    <legend class="sr-only"><?php _e('CSV Upload Settings', 'aiopms'); ?></legend>
                    <div class="dg10-form-field">
                        <label for="aiopms_csv_file" class="dg10-form-label" id="csv-file-label"><?php _e('CSV File', 'aiopms'); ?></label>
                        <input type="file" 
                               name="aiopms_csv_file" 
                               id="aiopms_csv_file" 
                               accept=".csv"
                               aria-labelledby="csv-file-label"
                               aria-describedby="csv-instructions"
                               aria-required="true"
                               role="button">
                        <div id="csv-instructions" class="dg10-form-help" role="region" aria-label="<?php esc_attr_e('CSV File Instructions', 'aiopms'); ?>">
                            <p><?php printf(__('Upload a CSV file with the following columns: %s, %s (optional), %s, %s, %s, %s, %s.', 'aiopms'), 
                                '<code>post_title</code>', 
                                '<code>slug</code>', 
                                '<code>post_parent</code>', 
                                '<code>meta_description</code>', 
                                '<code>featured_image</code>', 
                                '<code>page_template</code>', 
                                '<code>post_status</code>'); ?></p>
                            <ul>
                                <li><?php printf(__('The %s column should contain the title of the parent page.', 'aiopms'), '<code>post_parent</code>'); ?></li>
                                <li><?php printf(__('%s is optional - if empty, SEO-optimized slugs are automatically generated.', 'aiopms'), '<code>slug</code>'); ?></li>
                                <li><strong><?php echo esc_html(aiopms_get_max_file_size_display()); ?></strong></li>
                                <li><?php _e('Maximum rows: 10,000', 'aiopms'); ?></li>
                            </ul>
                        </div>
                    </div>
                </fieldset>
                <div class="dg10-form-actions">
                    <button type="submit" 
                            name="submit" 
                            class="dg10-btn dg10-btn-primary"
                            role="button"
                            aria-label="<?php esc_attr_e('Upload CSV File and Create Pages', 'aiopms'); ?>">
                        <span class="btn-icon" aria-hidden="true" role="img" aria-label="<?php esc_attr_e('Upload icon'); ?>">📤</span>
                        <span class="btn-text"><?php _e('Upload and Create Pages', 'aiopms'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </section>
    <?php
    if (isset($_POST['submit']) && check_admin_referer('aiopms_csv_upload')) {
        if (isset($_FILES['aiopms_csv_file']) && !empty($_FILES['aiopms_csv_file']['tmp_name'])) {
            aiopms_create_pages_from_csv($_FILES['aiopms_csv_file']);
        } else {
            echo '<div class="notice notice-error"><p>' . __('Please select a CSV file to upload.', 'aiopms') . '</p></div>';
        }
    }
}

// Menu generator tab content
function aiopms_menu_generator_tab() {
    // Handle menu generation requests
    if (isset($_POST['generate_menu']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'aiopms_generate_menu')) {
        $menu_type = isset($_POST['menu_type']) ? sanitize_key($_POST['menu_type']) : '';

        switch ($menu_type) {
            case 'universal_bottom':
                $result = aiopms_generate_universal_bottom_menu();
                if ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Universal Bottom Menu created successfully!', 'aiopms') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to create Universal Bottom Menu.', 'aiopms') . '</p></div>';
                }
                break;

            case 'services':
                $result = aiopms_generate_services_menu();
                if ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Services Menu created successfully!', 'aiopms') . '</p></div>';
                } else {
                    echo '<div class="notice notice-warning is-dismissible"><p>' . __('No service pages found to create Services Menu.', 'aiopms') . '</p></div>';
                }
                break;

            case 'company':
                $result = aiopms_generate_company_menu();
                if ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Company Menu created successfully!', 'aiopms') . '</p></div>';
                } else {
                    echo '<div class="notice notice-warning is-dismissible"><p>' . __('No company pages found to create Company Menu.', 'aiopms') . '</p></div>';
                }
                break;

            case 'main_navigation':
                $result = aiopms_generate_main_navigation_menu();
                if ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Main Navigation Menu created successfully!', 'aiopms') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to create Main Navigation Menu.', 'aiopms') . '</p></div>';
                }
                break;

            case 'resources':
                $result = aiopms_generate_resources_menu();
                if ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Resources Menu created successfully!', 'aiopms') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to create Resources Menu.', 'aiopms') . '</p></div>';
                }
                break;

            case 'footer_quick_links':
                $result = aiopms_generate_footer_quick_links_menu();
                if ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Footer Quick Links Menu created successfully!', 'aiopms') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to create Footer Quick Links Menu.', 'aiopms') . '</p></div>';
                }
                break;

            case 'social_media':
                $result = aiopms_generate_social_media_menu();
                if ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Social Media Menu created successfully!', 'aiopms') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to create Social Media Menu.', 'aiopms') . '</p></div>';
                }
                break;

            case 'support':
                $result = aiopms_generate_support_menu();
                if ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Support Menu created successfully!', 'aiopms') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to create Support Menu.', 'aiopms') . '</p></div>';
                }
                break;

            case 'products':
                $result = aiopms_generate_products_menu();
                if ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Products Menu created successfully!', 'aiopms') . '</p></div>';
                } else {
                    echo '<div class="notice notice-warning is-dismissible"><p>' . __('No product pages found to create Products Menu.', 'aiopms') . '</p></div>';
                }
                break;
        }
    }
    ?>
    <section class="dg10-card" role="region" aria-labelledby="menu-generator-heading">
        <div class="dg10-card-body">
            <h2 id="menu-generator-heading"><?php _e('Menu Generator', 'aiopms'); ?></h2>
            <p><?php _e('Automatically generate WordPress menus based on your created pages.', 'aiopms'); ?></p>
        
        <div class="menu-generator-options">
            <!-- Primary Navigation Menus -->
            <div class="menu-category">
                <h3>🧭 Primary Navigation</h3>
                <div class="menu-cards-grid">
                    <form method="post" action="">
                        <?php wp_nonce_field('aiopms_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Main Navigation Menu</h3>
                            <p>Complete primary header navigation with:</p>
                            <ul>
                                <li>Home + About/Company dropdowns</li>
                                <li>Services/Solutions with sub-items</li>
                                <li>Industry/Solution categories</li>
                                <li>Resources/Blog with categories</li>
                                <li>Contact page integration</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="main_navigation">
                            <?php submit_button('Generate Main Navigation', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>

                    <form method="post" action="">
                        <?php wp_nonce_field('aiopms_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Services Menu</h3>
                            <p>Creates a menu with all service-related pages:</p>
                            <ul>
                                <li>Detects pages with "service", "solution", "offer", or "package" in title</li>
                                <li>Includes a main "Services" link</li>
                                <li>Perfect for header navigation</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="services">
                            <?php submit_button('Generate Services Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Content & Product Menus -->
            <div class="menu-category">
                <h3>📄 Content & Products</h3>
                <div class="menu-cards-grid">
                    <form method="post" action="">
                        <?php wp_nonce_field('aiopms_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Products Menu</h3>
                            <p>Catalog menu for product-based businesses:</p>
                            <ul>
                                <li>Detects product, catalog, shop pages</li>
                                <li>Links to pricing and packages</li>
                                <li>Perfect for e-commerce sites</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="products">
                            <?php submit_button('Generate Products Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>

                    <form method="post" action="">
                        <?php wp_nonce_field('aiopms_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Resources Menu</h3>
                            <p>Knowledge base and content navigation:</p>
                            <ul>
                                <li>Blog posts and categories</li>
                                <li>Guides, tutorials, documentation</li>
                                <li>FAQ and help resources</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="resources">
                            <?php submit_button('Generate Resources Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>

                    <form method="post" action="">
                        <?php wp_nonce_field('aiopms_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Support Menu</h3>
                            <p>Customer support and help navigation:</p>
                            <ul>
                                <li>Help, FAQ, troubleshooting pages</li>
                                <li>Contact support integration</li>
                                <li>Documentation and guides</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="support">
                            <?php submit_button('Generate Support Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer & Social Menus -->
            <div class="menu-category">
                <h3>🔗 Footer & Links</h3>
                <div class="menu-cards-grid">
                    <form method="post" action="">
                        <?php wp_nonce_field('aiopms_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Universal Bottom Menu</h3>
                            <p>Comprehensive footer menu with:</p>
                            <ul>
                                <li>Home link</li>
                                <li>All legal pages (Privacy, Terms, etc.)</li>
                                <li>Sitemap link (from settings)</li>
                                <li>Contact page integration</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="universal_bottom">
                            <?php submit_button('Generate Footer Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>

                    <form method="post" action="">
                        <?php wp_nonce_field('aiopms_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Footer Quick Links</h3>
                            <p>Minimal footer navigation:</p>
                            <ul>
                                <li>Home and essential pages</li>
                                <li>Popular content links</li>
                                <li>Sitemap integration</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="footer_quick_links">
                            <?php submit_button('Generate Quick Links', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Company & Social -->
            <div class="menu-category">
                <h3>🏢 Company & Social</h3>
                <div class="menu-cards-grid">
                    <form method="post" action="">
                        <?php wp_nonce_field('aiopms_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Company Menu</h3>
                            <p>Creates a menu with company information pages:</p>
                            <ul>
                                <li>Detects pages like About, Team, Mission, Contact</li>
                                <li>Ideal for footer or secondary navigation</li>
                                <li>Includes all company-related content</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="company">
                            <?php submit_button('Generate Company Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>

                    <form method="post" action="">
                        <?php wp_nonce_field('aiopms_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Social Media Menu</h3>
                            <p>Social media links menu:</p>
                            <ul>
                                <li>Facebook, Twitter, LinkedIn</li>
                                <li>Instagram, YouTube, Pinterest</li>
                                <li>Ready for customization</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="social_media">
                            <?php submit_button('Generate Social Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="menu-generator-info">
            <h3>How it works:</h3>
            <ul>
                <li>Menus are created in WordPress Appearance → Menus</li>
                <li>Universal Bottom Menu tries to auto-assign to footer location</li>
                <li>You can manually assign menus to locations if needed</li>
                <li>Existing menus with the same name will be replaced</li>
            </ul>
            
            <h3>Note:</h3>
            <p>Make sure you have created pages before generating menus. The generator detects pages based on their titles and content.</p>
        </div>
        </div>
    </section>
    
    <!-- Menu Generator Styles moved to admin-ui.css -->
    <?php
}


// Keyword Analysis tab content
function aiopms_keyword_analysis_tab() {
    ?>
    <section class="dg10-card" role="region" aria-labelledby="keyword-analysis-heading">
        <div class="dg10-card-body">
            <form id="aiopms-keyword-analysis-form">
                <?php wp_nonce_field('aiopms_keyword_analysis', 'aiopms_keyword_nonce'); ?>
                
                <div class="dg10-form-group">
                    <label for="aiopms_page_select" class="dg10-form-label">Select Page to Analyze</label>
                    <select name="page_id" id="aiopms_page_select" class="dg10-form-select" required>
                        <option value="">Loading pages...</option>
                    </select>
                    <div class="dg10-form-help">
                        Choose a published page or post to analyze for keyword density
                    </div>
                </div>
                
                <div class="dg10-form-group">
                    <label for="aiopms_keywords_input" class="dg10-form-label">Keywords to Analyze</label>
                    <textarea name="keywords" id="aiopms_keywords_input" rows="8" class="dg10-form-textarea" 
                              placeholder="Enter keywords to analyze, one per line or separated by commas:&#10;&#10;web design&#10;SEO services&#10;digital marketing&#10;responsive design, mobile optimization" required></textarea>
                    <div class="dg10-form-help">
                        <strong>Format:</strong> One keyword per line, or comma-separated keywords. The analyzer will count exact matches (case-insensitive) and calculate density percentages.
                    </div>
                    <div id="keyword-count" class="dg10-form-help" style="margin-top: 8px;"></div>
                </div>
                
                <div class="dg10-form-group">
                    <button type="submit" id="aiopms-analyze-btn" class="dg10-btn dg10-btn-primary">
                        <span class="btn-text">🔍 Analyze Keywords</span>
                        <span class="dg10-spinner dg10-hidden"></span>
                    </button>
                </div>
            </form>
            
            <!-- Results Section -->
            <div id="aiopms-analysis-results" class="dg10-hidden">
                <div class="dg10-card">
                    <div class="dg10-card-header">
                        <h3>📊 Analysis Results</h3>
                        <div class="analysis-actions">
                            <button id="export-csv-btn" class="dg10-btn dg10-btn-outline dg10-btn-sm">
                                📊 Export CSV
                            </button>
                            <button id="export-json-btn" class="dg10-btn dg10-btn-outline dg10-btn-sm">
                                📋 Export JSON
                            </button>
                        </div>
                    </div>
                    <div class="dg10-card-body">
                        <!-- Page Info -->
                        <div id="page-info-section" class="analysis-section">
                            <h4>📄 Page Information</h4>
                            <div id="page-info-content"></div>
                        </div>
                        
                        <!-- Summary -->
                        <div id="summary-section" class="analysis-section">
                            <h4>📈 Analysis Summary</h4>
                            <div id="summary-content"></div>
                        </div>
                        
                        <!-- Keywords Table -->
                        <div id="keywords-section" class="analysis-section">
                            <h4>🔍 Keyword Analysis</h4>
                            <div class="table-responsive">
                                <table id="keywords-table" class="dg10-table">
                                    <thead>
                                        <tr>
                                            <th>Keyword</th>
                                            <th>Count</th>
                                            <th>Density</th>
                                            <th>Status</th>
                                            <th>Relevance</th>
                                            <th>Areas Found</th>
                                            <th>Context</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Recommendations -->
                        <div id="recommendations-section" class="analysis-section">
                            <h4>💡 SEO Recommendations</h4>
                            <div id="recommendations-content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Keyword Analysis Styles moved to admin-ui.css -->
    
    <!-- Keyword Analysis JS moved to keyword-analyzer.js -->    
    <!-- Accessibility CSS -->
    <!-- Accessibility styles moved to admin-ui.css -->
    <?php
}
