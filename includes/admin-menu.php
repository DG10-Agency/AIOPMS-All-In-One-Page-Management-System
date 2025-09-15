<?php
/**
 * Admin menu functionality for AIOPMS plugin.
 *
 * @package AIOPMS
 * @since 3.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add admin menu for AIOPMS plugin.
 * 
 * @since 3.0
 */
function aiopms_add_admin_menu() {
	add_menu_page(
		__( 'AIOPMS - Page Management', 'aiopms' ),
		__( 'AIOPMS', 'aiopms' ),
		'manage_options',
		'aiopms-page-management',
		'aiopms_admin_page',
		AIOPMS_PLUGIN_URL . 'assets/images/logo.svg',
		25
	);
}
add_action( 'admin_menu', 'aiopms_add_admin_menu' );

/**
 * Display admin page content.
 * 
 * @since 3.0
 */
function aiopms_admin_page() {
	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'manual';

	// Define menu items with their details.
	$menu_items = array(
		'manual'          => array(
			'title'       => __( 'Manual Page Creation', 'aiopms' ),
			'icon'        => '📝',
			'description' => __( 'Create pages manually with custom hierarchy and attributes', 'aiopms' ),
		),
		'csv'             => array(
			'title'       => __( 'CSV Upload', 'aiopms' ),
			'icon'        => '📊',
			'description' => __( 'Bulk import pages from CSV files', 'aiopms' ),
		),
		'ai'              => array(
			'title'       => __( 'AI Generation', 'aiopms' ),
			'icon'        => '🚀',
			'description' => __( 'Generate pages with AI assistance', 'aiopms' ),
		),
		'bulk-management' => array(
			'title'       => __( 'Bulk Management', 'aiopms' ),
			'icon'        => '🗑️',
			'description' => __( 'Bulk page deletion and management', 'aiopms' ),
		),
		'schema'          => array(
			'title'       => __( 'Schema Generator', 'aiopms' ),
			'icon'        => '🏷️',
			'description' => __( 'Create structured data markup', 'aiopms' ),
		),
		'menu'            => array(
			'title'       => __( 'Menu Generator', 'aiopms' ),
			'icon'        => '🍔',
			'description' => __( 'Automatically generate WordPress menus', 'aiopms' ),
		),
		'hierarchy'       => array(
			'title'       => __( 'Page Hierarchy', 'aiopms' ),
			'icon'        => '🌳',
			'description' => __( 'Visualize and manage page structure', 'aiopms' ),
		),
		'export'          => array(
			'title'       => __( 'Hierarchy Export', 'aiopms' ),
			'icon'        => '📤',
			'description' => __( 'Export page hierarchy data', 'aiopms' ),
		),
		'keyword-analysis' => array(
			'title'       => __( 'Keyword Analysis', 'aiopms' ),
			'icon'        => '🔍',
			'description' => __( 'Analyze keyword density and SEO', 'aiopms' ),
		),
		'settings'        => array(
			'title'       => __( 'Settings', 'aiopms' ),
			'icon'        => '⚙️',
			'description' => __( 'Configure plugin options', 'aiopms' ),
		),
	);
	?>
	<div class="wrap dg10-brand">
		<!-- Skip Links for Accessibility - Positioned at page level -->
		<a href="#main-content" class="skip-link"><?php esc_html_e( 'Skip to main content', 'aiopms' ); ?></a>
		<a href="#navigation" class="skip-link skip-to-nav"><?php esc_html_e( 'Skip to navigation', 'aiopms' ); ?></a>
		
		<div class="dg10-main-layout">
			<!-- Admin Sidebar -->
			<aside id="navigation" class="dg10-admin-sidebar" role="complementary" aria-label="<?php esc_attr_e( 'AIOPMS Navigation Menu', 'aiopms' ); ?>">
				<div class="dg10-sidebar-header">
					<div class="dg10-sidebar-title">
						<img src="<?php echo esc_url( AIOPMS_PLUGIN_URL ); ?>assets/images/logo.svg" alt="<?php esc_attr_e( 'AIOPMS Plugin Logo', 'aiopms' ); ?>" style="width: 24px; height: 24px;" role="img">
						<span class="dg10-plugin-name"><?php esc_html_e( 'AIOPMS', 'aiopms' ); ?></span>
					</div>
					<p class="dg10-sidebar-subtitle" role="text"><?php esc_html_e( 'All In One Page Management System', 'aiopms' ); ?></p>
				</div>
				
				<nav class="dg10-sidebar-nav" role="navigation" aria-label="<?php esc_attr_e( 'Main Navigation', 'aiopms' ); ?>">
					<ul role="list">
						<?php foreach ( $menu_items as $tab_key => $item ) : ?>
							<li role="listitem">
								<a href="?page=aiopms-page-management&tab=<?php echo esc_attr( $tab_key ); ?>" 
								   class="dg10-sidebar-nav-item <?php echo $active_tab === $tab_key ? 'active' : ''; ?>"
								   role="menuitem"
								   aria-label="<?php echo esc_attr( $item['title'] . ' - ' . $item['description'] ); ?>"
								   aria-current="<?php echo $active_tab === $tab_key ? 'page' : 'false'; ?>"
								   title="<?php echo esc_attr( $item['description'] ); ?>">
									<span class="nav-icon" aria-hidden="true" role="img" aria-label="<?php echo esc_attr( $item['title'] . ' icon' ); ?>"><?php echo esc_html( $item['icon'] ); ?></span>
									<span class="nav-text"><?php echo esc_html( $item['title'] ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			</aside>
			
			<!-- Main Content Area -->
			<main id="main-content" class="dg10-main-content" role="main" aria-label="<?php esc_attr_e( 'Main Content Area', 'aiopms' ); ?>" tabindex="-1">
				<article class="dg10-card" role="article" aria-labelledby="page-title">
					<header class="dg10-card-header" role="banner">
						<div class="dg10-hero-content">
							<div class="dg10-hero-text">
								<h1 id="page-title" role="heading" aria-level="1"><?php echo esc_html( $menu_items[ $active_tab ]['title'] ); ?></h1>
								<p class="dg10-hero-description" role="text" aria-describedby="page-title">
									<?php echo esc_html( $menu_items[ $active_tab ]['description'] ); ?>
								</p>
							</div>
						</div>
					</header>
					<div class="dg10-card-body" role="main" aria-label="<?php esc_attr_e( 'Content Section', 'aiopms' ); ?>">
						<?php
						if ( 'manual' === $active_tab ) {
							aiopms_manual_creation_tab();
						} elseif ( 'csv' === $active_tab ) {
							aiopms_csv_upload_tab();
						} elseif ( 'ai' === $active_tab ) {
							aiopms_ai_generation_tab();
						} elseif ( 'bulk-management' === $active_tab ) {
							aiopms_bulk_management_tab();
						} elseif ( 'schema' === $active_tab ) {
							aiopms_schema_generator_tab();
						} elseif ( 'menu' === $active_tab ) {
							aiopms_menu_generator_tab();
						} elseif ( 'hierarchy' === $active_tab ) {
							aiopms_hierarchy_tab();
						} elseif ( 'export' === $active_tab ) {
							aiopms_hierarchy_export_tab();
						} elseif ( 'keyword-analysis' === $active_tab ) {
							aiopms_keyword_analysis_tab();
						} elseif ( 'settings' === $active_tab ) {
							aiopms_settings_tab();
						}
						?>
					</div>
					<footer class="dg10-card-footer" role="contentinfo" aria-label="<?php esc_attr_e( 'About DG10 Agency', 'aiopms' ); ?>">
						<section class="dg10-promotion-section" role="region" aria-labelledby="about-us-heading">
							<header class="dg10-promotion-header">
								<img src="<?php echo esc_url( AIOPMS_PLUGIN_URL ); ?>assets/images/dg10-brand-logo.svg" alt="<?php esc_attr_e( 'DG10 Agency Logo', 'aiopms' ); ?>" class="dg10-promotion-logo" role="img">
								<h3 id="about-us-heading" role="heading" aria-level="3"><?php esc_html_e( 'About us', 'aiopms' ); ?></h3>
							</header>
							<div class="dg10-promotion-content">
								<p role="text"><?php esc_html_e( 'DG10 Agency specializes in creating powerful WordPress and Elementor solutions. We help businesses build custom websites, optimize performance, and implement complex integrations that drive results.', 'aiopms' ); ?></p>
								<div class="dg10-promotion-buttons" role="group" aria-label="<?php esc_attr_e( 'Action Buttons', 'aiopms' ); ?>">
									<a href="https://www.dg10.agency" target="_blank" class="dg10-btn dg10-btn-primary" role="button" aria-label="<?php esc_attr_e( 'Visit DG10 Agency Website - Opens in new tab', 'aiopms' ); ?>" rel="noopener noreferrer">
										<span class="btn-text"><?php esc_html_e( 'Visit Website', 'aiopms' ); ?></span>
										<span class="dg10-btn-icon" aria-hidden="true" role="img" aria-label="<?php esc_attr_e( 'External link icon' ); ?>">→</span>
									</a>
									<a href="https://calendly.com/dg10-agency/30min" target="_blank" class="dg10-btn dg10-btn-outline" role="button" aria-label="<?php esc_attr_e( 'Book a Free Consultation - Opens in new tab', 'aiopms' ); ?>" rel="noopener noreferrer">
										<span class="dg10-btn-icon" aria-hidden="true" role="img" aria-label="<?php esc_attr_e( 'Calendar icon' ); ?>">📅</span>
										<span class="btn-text"><?php esc_html_e( 'Book a Free Consultation', 'aiopms' ); ?></span>
									</a>
								</div>
								<p class="dg10-promotion-footer" role="text">
									<?php
									printf(
										/* translators: %1$s: Link to plugin page, %2$s: Link to GitHub repository */
										esc_html__( 'Visit our %1$s or %2$s to support this open-source project.', 'aiopms' ),
										'<a href="https://dg10.agency/free-wordpress-page-management-plugin/" target="_blank" role="link" aria-label="' . esc_attr__( 'Visit AIOPMS Plugin Page - Opens in new tab', 'aiopms' ) . '" rel="noopener noreferrer">' . esc_html__( 'plugin page', 'aiopms' ) . '</a>',
										'<a href="' . esc_url( AIOPMS_GITHUB_URL ) . '" target="_blank" role="link" aria-label="' . esc_attr__( 'Star the repository on GitHub - Opens in new tab', 'aiopms' ) . '" rel="noopener noreferrer">' . esc_html__( 'star the repo on GitHub', 'aiopms' ) . '</a>'
									);
									?>
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
    
    <style>
    /* Additional JavaScript-triggered styles */
    .dg10-sidebar-nav-item.focused {
        outline: 2px solid #2271b1;
        outline-offset: 2px;
    }
    
    .dg10-sidebar-nav.mobile-nav {
        scrollbar-width: thin;
        scrollbar-color: #2271b1 #f1f1f1;
    }
    
    .dg10-sidebar-nav.mobile-nav::-webkit-scrollbar {
        height: 6px;
    }
    
    .dg10-sidebar-nav.mobile-nav::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    .dg10-sidebar-nav.mobile-nav::-webkit-scrollbar-thumb {
        background: #2271b1;
        border-radius: 3px;
    }
    
    .dg10-sidebar-nav.mobile-nav::-webkit-scrollbar-thumb:hover {
        background: #1e5a96;
    }
    </style>
    <?php
}

/**
 * Display manual creation tab content.
 * 
 * @since 3.0
 */
function aiopms_manual_creation_tab() {
	?>
	<section class="dg10-card" role="region" aria-labelledby="manual-creation-heading">
		<div class="dg10-card-body">
			<form method="post" action="" role="form" aria-label="<?php esc_attr_e( 'Manual Page Creation Form', 'aiopms' ); ?>">
				<?php wp_nonce_field( 'aiopms_manual_create_pages' ); ?>
				<fieldset class="dg10-form-group">
					<legend class="sr-only"><?php esc_html_e( 'Page Creation Settings', 'aiopms' ); ?></legend>
					<div class="dg10-form-field">
						<label for="aiopms_titles" class="dg10-form-label" id="titles-label"><?php esc_html_e( 'Page Titles', 'aiopms' ); ?></label>
						<textarea name="aiopms_titles" 
								  id="aiopms_titles" 
								  rows="10" 
								  class="dg10-form-textarea" 
								  placeholder="<?php esc_attr_e( 'Enter one page title per line. Use hyphens for nesting...', 'aiopms' ); ?>"
								  aria-labelledby="titles-label"
								  aria-describedby="syntax-guide"
								  aria-required="true"
								  role="textbox"></textarea>
						<div id="syntax-guide" class="dg10-form-help" role="region" aria-label="<?php esc_attr_e( 'Syntax Guide', 'aiopms' ); ?>">
							<strong><?php esc_html_e( 'Syntax Guide:', 'aiopms' ); ?></strong><br>
							• <?php esc_html_e( 'Use', 'aiopms' ); ?> <code>-</code> <?php esc_html_e( 'for child pages (one hyphen per level)', 'aiopms' ); ?><br>
							• <?php esc_html_e( 'Use', 'aiopms' ); ?> <code>:+</code> <?php esc_html_e( 'for meta description', 'aiopms' ); ?><br>
							• <?php esc_html_e( 'Use', 'aiopms' ); ?> <code>:*</code> <?php esc_html_e( 'for featured image URL', 'aiopms' ); ?><br>
							• <?php esc_html_e( 'Use', 'aiopms' ); ?> <code>::template=template-name.php</code> <?php esc_html_e( 'for page template', 'aiopms' ); ?><br>
							• <?php esc_html_e( 'Use', 'aiopms' ); ?> <code>::status=draft</code> <?php esc_html_e( 'for post status', 'aiopms' ); ?><br>
							• <strong><?php esc_html_e( 'SEO slugs are automatically generated', 'aiopms' ); ?></strong> (<?php esc_html_e( 'max 72 chars', 'aiopms' ); ?>)
						</div>
					</div>
				</fieldset>
				<div class="dg10-form-actions">
					<button type="submit" 
							name="submit" 
							class="dg10-btn dg10-btn-primary"
							role="button"
							aria-label="<?php esc_attr_e( 'Create Pages from Titles', 'aiopms' ); ?>">
						<span class="btn-icon" aria-hidden="true" role="img" aria-label="<?php esc_attr_e( 'Rocket icon' ); ?>">🚀</span>
						<span class="btn-text"><?php esc_html_e( 'Create Pages', 'aiopms' ); ?></span>
					</button>
				</div>
			</form>
		</div>
	</section>
	<?php
	if ( isset( $_POST['submit'] ) && check_admin_referer( 'aiopms_manual_create_pages' ) ) {
		$titles = isset( $_POST['aiopms_titles'] ) ? sanitize_textarea_field( wp_unslash( $_POST['aiopms_titles'] ) ) : '';
		if ( ! empty( $titles ) ) {
			aiopms_create_pages_manually( $titles );
		}
	}
}

/**
 * Display CSV upload tab content.
 * 
 * @since 3.0
 */
function aiopms_csv_upload_tab() {
	?>
	<section class="dg10-card" role="region" aria-labelledby="csv-upload-heading">
		<div class="dg10-card-body">
			<form method="post" action="" enctype="multipart/form-data" role="form" aria-label="<?php esc_attr_e( 'CSV File Upload Form', 'aiopms' ); ?>">
				<?php wp_nonce_field( 'aiopms_csv_upload' ); ?>
				<fieldset class="dg10-form-group">
					<legend class="sr-only"><?php esc_html_e( 'CSV Upload Settings', 'aiopms' ); ?></legend>
					<div class="dg10-form-field">
						<label for="aiopms_csv_file" class="dg10-form-label" id="csv-file-label">
							<?php esc_html_e( 'CSV File', 'aiopms' ); ?>
							<span class="required-indicator" aria-label="<?php esc_attr_e( 'required', 'aiopms' ); ?>">*</span>
						</label>
						<div class="dg10-file-upload-wrapper">
							<input type="file" 
								   name="aiopms_csv_file" 
								   id="aiopms_csv_file" 
								   accept=".csv"
								   class="dg10-form-control dg10-file-input"
								   aria-labelledby="csv-file-label"
								   aria-describedby="csv-instructions csv-file-status"
								   aria-required="true"
								   aria-invalid="false">
							<div id="csv-file-status" class="dg10-file-status" role="status" aria-live="polite"></div>
						</div>
						<div id="csv-instructions" class="dg10-form-help" role="region" aria-label="<?php esc_attr_e( 'CSV File Instructions', 'aiopms' ); ?>">
							<p><?php
							printf(
								/* translators: %s: CSV column names */
								esc_html__( 'Upload a CSV file with the following columns: %s, %s (optional), %s, %s, %s, %s, %s.', 'aiopms' ),
								'<code>post_title</code>',
								'<code>slug</code>',
								'<code>post_parent</code>',
								'<code>meta_description</code>',
								'<code>featured_image</code>',
								'<code>page_template</code>',
								'<code>post_status</code>'
							);
							?></p>
							<ul>
								<li><?php
								printf(
									/* translators: %s: Column name */
									esc_html__( 'The %s column should contain the title of the parent page.', 'aiopms' ),
									'<code>post_parent</code>'
								);
								?></li>
								<li><?php
								printf(
									/* translators: %s: Column name */
									esc_html__( '%s is optional - if empty, SEO-optimized slugs are automatically generated.', 'aiopms' ),
									'<code>slug</code>'
								);
								?></li>
								<li><strong><?php echo esc_html( aiopms_get_max_file_size_display() ); ?></strong></li>
								<li><?php esc_html_e( 'Maximum rows: 10,000', 'aiopms' ); ?></li>
							</ul>
						</div>
					</div>
				</fieldset>
				<div class="dg10-form-actions">
					<button type="submit" 
							name="submit" 
							class="dg10-btn dg10-btn-primary"
							role="button"
							aria-label="<?php esc_attr_e( 'Upload CSV File and Create Pages', 'aiopms' ); ?>">
						<span class="btn-icon" aria-hidden="true" role="img" aria-label="<?php esc_attr_e( 'Upload icon' ); ?>">📤</span>
						<span class="btn-text"><?php esc_html_e( 'Upload and Create Pages', 'aiopms' ); ?></span>
					</button>
				</div>
			</form>
		</div>
	</section>
	<?php
	if ( isset( $_POST['submit'] ) && check_admin_referer( 'aiopms_csv_upload' ) ) {
		if ( isset( $_FILES['aiopms_csv_file'] ) && ! empty( $_FILES['aiopms_csv_file']['tmp_name'] ) ) {
			aiopms_create_pages_from_csv( $_FILES['aiopms_csv_file'] );
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Please select a CSV file to upload.', 'aiopms' ) . '</p></div>';
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
    
    <style>
    .menu-generator-options {
        display: flex;
        flex-direction: column;
        gap: 40px;
        margin: 30px 0;
    }

    .menu-category {
        background: #f9f9f9;
        border-radius: 8px;
        padding: 25px;
        border: 1px solid #e0e0e0;
    }

    .menu-category h3 {
        margin: 0 0 20px 0;
        color: #1d2327;
        font-size: 20px;
        font-weight: 600;
        border-bottom: 2px solid #2271b1;
        padding-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .menu-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
    }

    .menu-option-card {
        background: #fff;
        padding: 25px;
        border: 1px solid #dcdcde;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        position: relative;
    }

    .menu-option-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-color: #2271b1;
    }

    .menu-option-card h3 {
        margin: 0 0 15px 0;
        color: #2271b1;
        font-size: 16px;
        font-weight: 600;
    }

    .menu-option-card p {
        margin: 0 0 15px 0;
        color: #646970;
        font-size: 14px;
        line-height: 1.5;
    }

    .menu-option-card ul {
        margin: 15px 0 20px 0;
        padding-left: 20px;
    }

    .menu-option-card li {
        margin-bottom: 6px;
        color: #50575e;
        font-size: 13px;
        line-height: 1.4;
    }

    .menu-option-card .button {
        width: 100%;
        text-align: center;
        padding: 10px 16px;
        height: auto;
        font-size: 14px;
        font-weight: 500;
    }

    .menu-generator-info {
        background: #f0f6fc;
        padding: 25px;
        border-left: 4px solid #2271b1;
        margin-top: 40px;
        border-radius: 0 8px 8px 0;
    }

    .menu-generator-info h3 {
        margin: 0 0 15px 0;
        color: #1d2327;
        font-size: 16px;
    }

    .menu-generator-info ul {
        margin: 15px 0;
        padding-left: 20px;
    }

    .menu-generator-info li {
        margin-bottom: 8px;
        color: #50575e;
    }

    /* Responsive Design */
    @media screen and (max-width: 920px) {
        .menu-cards-grid {
            grid-template-columns: 1fr;
        }
    }

    @media screen and (max-width: 600px) {
        .menu-category {
            padding: 20px 15px;
        }

        .menu-option-card {
            padding: 20px;
        }

        .menu-cards-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
}

// Hierarchy Export tab content
function aiopms_hierarchy_export_tab() {
    ?>
    <section class="dg10-card" role="region" aria-labelledby="hierarchy-export-heading">
        <div class="dg10-card-body">
            <h2 id="hierarchy-export-heading">Hierarchy Export Tools</h2>
            <p>Export and copy your page hierarchy for documentation, planning, or analysis purposes.</p>

        <div class="aiopms-export-section">
            <div class="aiopms-export-info">
                <h3>📋 Copy Hierarchy</h3>
                <p>Copy complete page hierarchy as plain, indented text to clipboard.</p>
                <ul>
                    <li>All pages from the website</li>
                    <li>Complete subpage structure</li>
                    <li>Accurate nesting levels</li>
                    <li>Current hierarchy format</li>
                </ul>
                <div class="aiopms-export-copy-controls">
                    <button id="aiopms-export-copy-hierarchy" class="button button-primary">📋 Copy Hierarchy</button>
                </div>
            </div>

            <div class="aiopms-export-info">
                <h3>📊 Export Files</h3>
                <p>Download complete page hierarchy data in different formats.</p>
                <ul>
                    <li>CSV format with all metadata</li>
                    <li>Markdown format for documentation</li>
                    <li>JSON format for data processing</li>
                    <li>Proper file naming with site title</li>
                    <li>Complete hierarchical structure</li>
                </ul>
                <div class="aiopms-export-file-controls">
                    <button id="aiopms-export-csv" class="button button-secondary">📊 Export as CSV</button>
                    <button id="aiopms-export-markdown" class="button button-secondary">📄 Export as Markdown</button>
                    <button id="aiopms-export-json" class="button button-secondary">📋 Export as JSON</button>
                </div>
            </div>
        </div>

        <div class="aiopms-export-preview">
            <h3>File Format Preview</h3>
            <div class="aiopms-export-format-examples">
                <div class="format-example">
                    <h4>Copy to Clipboard Output:</h4>
                    <pre class="example-code">Home
  About Us
    Team
    History
  Services
    Web Development
    SEO</pre>
                </div>

                <div class="format-example">
                    <h4>CSV Export Sample:</h4>
                    <pre class="example-code">"Title","URL","Excerpt","Published Date","Status"
"Home Page","https://example.com/home","Welcome to homepage","2024-01-15","publish"
"About Us","https://example.com/about","About our company","2024-01-16","publish"</pre>
                </div>

                <div class="format-example">
                    <h4>Markdown Export Sample:</h4>
                    <pre class="example-code"># Page Hierarchy

- [Home Page](https://example.com/home)
  - [About Us](https://example.com/about)
    - [Team](https://example.com/team)
    - [History](https://example.com/history)
  - [Services](https://example.com/services)</pre>
                </div>

                <div class="format-example">
                    <h4>JSON Export Sample:</h4>
                    <pre class="example-code">{
  "site_title": "My Website",
  "export_date": "2024-01-15 10:30:00",
  "total_pages": 5,
  "hierarchy": {
    "1": {
      "id": 1,
      "title": "Home Page",
      "parent": 0,
      "url": "https://example.com/home"
    }
  }
}</pre>
                </div>
            </div>
        </div>

        <!-- Toast notifications container -->
        <div id="aiopms-export-toast-container"></div>
        </div>
    </section>

    <style>
    .aiopms-export-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 30px;
        margin: 30px 0;
    }

    .aiopms-export-info {
        background: #fff;
        padding: 25px;
        border: 1px solid #dcdcde;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .aiopms-export-info h3 {
        margin: 0 0 15px 0;
        color: #2271b1;
        font-size: 18px;
    }

    .aiopms-export-info ul {
        margin: 15px 0 20px 0;
        padding-left: 20px;
    }

    .aiopms-export-info li {
        margin-bottom: 8px;
        color: #646970;
    }

    .aiopms-export-copy-controls,
    .aiopms-export-file-controls {
        margin-top: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .aiopms-export-preview {
        background: #f6f7f7;
        padding: 25px;
        border-radius: 6px;
        border-left: 4px solid #2271b1;
        margin-top: 30px;
    }

    .aiopms-export-preview h3 {
        margin: 0 0 20px 0;
        color: #1d2327;
    }

    .aiopms-export-format-examples {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
    }

    .format-example {
        background: #fff;
        padding: 20px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
    }

    .format-example h4 {
        margin: 0 0 10px 0;
        color: #2271b1;
        font-size: 14px;
        font-weight: 600;
    }

    .format-example pre {
        margin: 0;
        padding: 10px;
        background: #f8f8f8;
        border: 1px solid #e0e0e0;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        line-height: 1.4;
        max-height: 150px;
        overflow-y: auto;
    }

    /* Toast Styles */
    .aiopms-toast {
        position: fixed;
        top: 40px;
        right: 20px;
        padding: 12px 16px;
        border-radius: 4px;
        color: #fff;
        font-size: 14px;
        font-weight: 500;
        z-index: 99999;
        opacity: 0;
        transform: translateY(-10px);
        animation: aiopmsToastSlideIn 0.3s ease forwards;
        max-width: 400px;
        word-wrap: break-word;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .aiopms-toast.success {
        background: #28a745;
        border: 1px solid #218838;
    }

    .aiopms-toast.error {
        background: #dc3545;
        border: 1px solid #c82333;
    }

    @keyframes aiopmsToastSlideIn {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive adjustments */
    @media screen and (max-width: 782px) {
        .aiopms-export-section {
            grid-template-columns: 1fr;
        }

        .aiopms-export-copy-controls,
        .aiopms-export-file-controls {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .aiopms-export-format-examples {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Get hierarchy data
        let exportData = null;

        // Load hierarchy data
        function loadExportData() {
            $.ajax({
                url: '<?php echo esc_url(rest_url('aiopms/v1/hierarchy')); ?>',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                },
                success: function(data) {
                    exportData = data;
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load export data:', error);
                    showToast('Error loading page data. Please try again.', 'error');
                }
            });
        }

        // Copy hierarchy to clipboard
        $('#aiopms-export-copy-hierarchy').on('click', function() {
            if (!exportData) {
                loadExportData();
                setTimeout(() => copyHierarchyToClipboard(), 1000);
                return;
            }
            copyHierarchyToClipboard();
        });

        function copyHierarchyToClipboard() {
            if (!exportData) {
                showToast('No page data available. Please wait for data to load.', 'error');
                return;
            }

            const result = generateHierarchicalText(exportData);

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(result).then(() => {
                    showToast('Page hierarchy copied to clipboard!');
                }).catch((err) => {
                    console.error('Failed to copy: ', err);
                    fallbackCopyTextToClipboard(result);
                });
            } else {
                fallbackCopyTextToClipboard(result);
            }
        }

        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showToast('Page hierarchy copied to clipboard!');
                } else {
                    showToast('Failed to copy to clipboard', 'error');
                }
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
                showToast('Failed to copy to clipboard', 'error');
            }

            document.body.removeChild(textArea);
        }

        function generateHierarchicalText(data) {
            let output = '';

            function buildText(nodeId, prefix = '', seen = new Set()) {
                if (seen.has(nodeId)) return;
                seen.add(nodeId);

                const node = data.find(item => item.id === nodeId);
                if (!node) return;

                output += prefix + node.text + '\n';

                const children = data.filter(item => item.parent === nodeId).sort((a, b) => a.text.localeCompare(b.text));
                children.forEach(child => {
                    const childPrefix = prefix + '  ';
                    buildText(child.id, childPrefix, new Set(seen));
                });
            }

            const roots = data.filter(item => item.parent === '#').sort((a, b) => a.text.localeCompare(b.text));
            roots.forEach(root => {
                buildText(root.id);
            });

            return output.trim();
        }

        // Export to CSV
        $('#aiopms-export-csv').on('click', function() {
            const csvUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=aiopms_export_csv&nonce=<?php echo wp_create_nonce('aiopms_export_nonce'); ?>';
            const link = document.createElement('a');
            link.href = csvUrl;
            link.download = '<?php echo sanitize_file_name(get_bloginfo('name')); ?>.csv';
            link.style.display = 'none';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showToast('CSV export started! Check your downloads.');
        });

        // Export to Markdown
        $('#aiopms-export-markdown').on('click', function() {
            const markdownUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=aiopms_export_markdown&nonce=<?php echo wp_create_nonce('aiopms_export_nonce'); ?>';
            const link = document.createElement('a');
            link.href = markdownUrl;
            link.download = '<?php echo sanitize_file_name(get_bloginfo('name')); ?>.md';
            link.style.display = 'none';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showToast('Markdown export started! Check your downloads.');
        });

        // Export to JSON
        $('#aiopms-export-json').on('click', function() {
            const jsonUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=aiopms_export_json&nonce=<?php echo wp_create_nonce('aiopms_export_nonce'); ?>';
            const link = document.createElement('a');
            link.href = jsonUrl;
            link.download = '<?php echo sanitize_file_name(get_bloginfo('name')); ?>.json';
            link.style.display = 'none';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showToast('JSON export started! Check your downloads.');
        });

        // Toast notification
        function showToast(message, type = 'success') {
            const toast = $(`<div class="aiopms-toast ${type}">${message}</div>`);
            $('#aiopms-export-toast-container').append(toast);
            setTimeout(() => toast.remove(), 5000);
        }

        // Load data on page load
        loadExportData();
    });
    </script>
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
    
    <style>
    .analysis-actions {
        display: flex;
        gap: var(--dg10-spacing-sm);
        margin-left: auto;
    }
    
    .analysis-section {
        margin-bottom: var(--dg10-spacing-xl);
        padding-bottom: var(--dg10-spacing-lg);
        border-bottom: 1px solid #E5E7EB;
    }
    
    .analysis-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .analysis-section h4 {
        margin: 0 0 var(--dg10-spacing-md) 0;
        color: var(--dg10-primary);
        font-size: var(--dg10-font-size-lg);
    }
    
    .page-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--dg10-spacing-md);
        margin-bottom: var(--dg10-spacing-lg);
    }
    
    .page-info-item {
        background: var(--dg10-light-gray);
        padding: var(--dg10-spacing-md);
        border-radius: var(--dg10-radius-md);
        border-left: 4px solid var(--dg10-primary);
    }
    
    .page-info-label {
        font-size: var(--dg10-font-size-xs);
        font-weight: 600;
        color: var(--dg10-neutral);
        text-transform: uppercase;
        letter-spacing: 0.025em;
        margin-bottom: var(--dg10-spacing-xs);
    }
    
    .page-info-value {
        font-size: var(--dg10-font-size-sm);
        color: var(--dg10-dark-blue);
        font-weight: 500;
    }
    
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: var(--dg10-spacing-md);
        margin-bottom: var(--dg10-spacing-lg);
    }
    
    .summary-item {
        text-align: center;
        padding: var(--dg10-spacing-lg);
        background: var(--dg10-white);
        border: 1px solid #E5E7EB;
        border-radius: var(--dg10-radius-lg);
        box-shadow: var(--dg10-shadow-sm);
    }
    
    .summary-number {
        font-size: var(--dg10-font-size-2xl);
        font-weight: 700;
        color: var(--dg10-primary);
        margin-bottom: var(--dg10-spacing-xs);
    }
    
    .summary-label {
        font-size: var(--dg10-font-size-sm);
        color: var(--dg10-neutral);
        font-weight: 500;
    }
    
    .dg10-table {
        width: 100%;
        border-collapse: collapse;
        background: var(--dg10-white);
        border-radius: var(--dg10-radius-lg);
        overflow: hidden;
        box-shadow: var(--dg10-shadow-sm);
    }
    
    .dg10-table th {
        background: var(--dg10-gradient-dark);
        color: var(--dg10-white);
        padding: var(--dg10-spacing-md);
        text-align: left;
        font-weight: 600;
        font-size: var(--dg10-font-size-sm);
    }
    
    .dg10-table td {
        padding: var(--dg10-spacing-md);
        border-bottom: 1px solid #E5E7EB;
        font-size: var(--dg10-font-size-sm);
        vertical-align: top;
    }
    
    .dg10-table tr:hover {
        background: var(--dg10-light-gray);
    }
    
    .keyword-cell {
        font-weight: 600;
        color: var(--dg10-dark-blue);
    }
    
    .count-cell {
        text-align: center;
        font-weight: 600;
    }
    
    .density-cell {
        text-align: center;
        font-weight: 600;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: var(--dg10-spacing-xs) var(--dg10-spacing-sm);
        border-radius: var(--dg10-radius-sm);
        font-size: var(--dg10-font-size-xs);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    
    .status-high {
        background: var(--dg10-gradient-error);
        color: var(--dg10-white);
    }
    
    .status-good {
        background: var(--dg10-gradient-success);
        color: var(--dg10-white);
    }
    
    .status-moderate {
        background: var(--dg10-gradient-warning);
        color: var(--dg10-white);
    }
    
    .status-low {
        background: var(--dg10-gradient-info);
        color: var(--dg10-white);
    }
    
    .status-none {
        background: #6B7280;
        color: var(--dg10-white);
    }
    
    .areas-found {
        font-size: var(--dg10-font-size-xs);
        color: var(--dg10-neutral);
    }
    
    .context-preview {
        max-width: 200px;
        font-size: var(--dg10-font-size-xs);
        color: var(--dg10-neutral);
        line-height: 1.4;
    }
    
    .context-preview strong {
        color: var(--dg10-primary);
        font-weight: 600;
    }
    
    .recommendations-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .recommendations-list li {
        padding: var(--dg10-spacing-md);
        margin-bottom: var(--dg10-spacing-sm);
        background: var(--dg10-light-gray);
        border-left: 4px solid var(--dg10-primary);
        border-radius: 0 var(--dg10-radius-md) var(--dg10-radius-md) 0;
        font-size: var(--dg10-font-size-sm);
        line-height: 1.5;
    }
    
    .table-responsive {
        overflow-x: auto;
        border-radius: var(--dg10-radius-lg);
    }
    
    /* SEO Score Styles */
    .seo-excellent {
        color: #10B981 !important;
    }
    
    .seo-good {
        color: #3B82F6 !important;
    }
    
    .seo-fair {
        color: #F59E0B !important;
    }
    
    .seo-poor {
        color: #EF4444 !important;
    }
    
    /* Performance Metrics */
    .performance-metrics {
        margin-top: var(--dg10-spacing-lg);
        padding: var(--dg10-spacing-md);
        background: var(--dg10-light-gray);
        border-radius: var(--dg10-radius-md);
    }
    
    .performance-metrics h5 {
        margin: 0 0 var(--dg10-spacing-md) 0;
        color: var(--dg10-dark-blue);
        font-size: var(--dg10-font-size-md);
    }
    
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: var(--dg10-spacing-sm);
    }
    
    .metric-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--dg10-spacing-sm);
        background: var(--dg10-white);
        border-radius: var(--dg10-radius-sm);
        font-size: var(--dg10-font-size-sm);
    }
    
    .metric-label {
        color: var(--dg10-neutral);
        font-weight: 500;
    }
    
    .metric-value {
        font-weight: 600;
        padding: 2px 8px;
        border-radius: var(--dg10-radius-sm);
    }
    
    .metric-value.good {
        background: #D1FAE5;
        color: #065F46;
    }
    
    .metric-value.warning {
        background: #FEF3C7;
        color: #92400E;
    }
    
    .metric-value.info {
        background: #DBEAFE;
        color: #1E40AF;
    }
    
    .metric-value.error {
        background: #FEE2E2;
        color: #991B1B;
    }
    
    /* Relevance Score */
    .relevance-cell {
        text-align: center;
    }
    
    .relevance-score {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 24px;
        border-radius: var(--dg10-radius-sm);
        font-size: var(--dg10-font-size-xs);
        font-weight: 600;
    }
    
    .relevance-high {
        background: #D1FAE5;
        color: #065F46;
    }
    
    .relevance-medium {
        background: #FEF3C7;
        color: #92400E;
    }
    
    .relevance-low {
        background: #FEE2E2;
        color: #991B1B;
    }
    
    /* Enhanced Recommendations */
    .recommendations-container {
        display: flex;
        flex-direction: column;
        gap: var(--dg10-spacing-md);
    }
    
    .recommendation-item {
        padding: var(--dg10-spacing-md);
        border-radius: var(--dg10-radius-md);
        border-left: 4px solid;
    }
    
    .recommendation-simple {
        background: var(--dg10-light-gray);
        border-left-color: var(--dg10-primary);
    }
    
    .recommendation-success {
        background: #F0FDF4;
        border-left-color: #10B981;
    }
    
    .recommendation-warning {
        background: #FFFBEB;
        border-left-color: #F59E0B;
    }
    
    .recommendation-error {
        background: #FEF2F2;
        border-left-color: #EF4444;
    }
    
    .recommendation-info {
        background: #EFF6FF;
        border-left-color: #3B82F6;
    }
    
    .recommendation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--dg10-spacing-sm);
    }
    
    .recommendation-title {
        margin: 0;
        font-size: var(--dg10-font-size-md);
        font-weight: 600;
        color: var(--dg10-dark-blue);
    }
    
    .recommendation-type {
        padding: 2px 8px;
        border-radius: var(--dg10-radius-sm);
        font-size: var(--dg10-font-size-xs);
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .recommendation-success .recommendation-type {
        background: #D1FAE5;
        color: #065F46;
    }
    
    .recommendation-warning .recommendation-type {
        background: #FEF3C7;
        color: #92400E;
    }
    
    .recommendation-error .recommendation-type {
        background: #FEE2E2;
        color: #991B1B;
    }
    
    .recommendation-info .recommendation-type {
        background: #DBEAFE;
        color: #1E40AF;
    }
    
    .recommendation-message {
        margin: 0 0 var(--dg10-spacing-sm) 0;
        font-size: var(--dg10-font-size-sm);
        line-height: 1.5;
        color: var(--dg10-neutral);
    }
    
    .recommendation-keywords,
    .recommendation-action {
        margin-top: var(--dg10-spacing-sm);
        padding: var(--dg10-spacing-sm);
        background: rgba(255, 255, 255, 0.7);
        border-radius: var(--dg10-radius-sm);
        font-size: var(--dg10-font-size-sm);
    }
    
    .recommendation-keywords strong,
    .recommendation-action strong {
        color: var(--dg10-dark-blue);
    }
    
    /* Notification System */
    .aiopms-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
        padding: var(--dg10-spacing-md);
        border-radius: var(--dg10-radius-md);
        box-shadow: var(--dg10-shadow-lg);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--dg10-spacing-sm);
        animation: slideInRight 0.3s ease-out;
    }
    
    .notification-success {
        background: #F0FDF4;
        border: 1px solid #10B981;
        color: #065F46;
    }
    
    .notification-error {
        background: #FEF2F2;
        border: 1px solid #EF4444;
        color: #991B1B;
    }
    
    .notification-warning {
        background: #FFFBEB;
        border: 1px solid #F59E0B;
        color: #92400E;
    }
    
    .notification-info {
        background: #EFF6FF;
        border: 1px solid #3B82F6;
        color: #1E40AF;
    }
    
    .notification-message {
        flex: 1;
        font-size: var(--dg10-font-size-sm);
        font-weight: 500;
    }
    
    .notification-close {
        background: none;
        border: none;
        font-size: var(--dg10-font-size-lg);
        cursor: pointer;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.2s;
    }
    
    .notification-close:hover {
        background: rgba(0, 0, 0, 0.1);
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @media (max-width: 768px) {
        .analysis-actions {
            flex-direction: column;
            margin-left: 0;
            margin-top: var(--dg10-spacing-md);
        }
        
        .page-info-grid,
        .summary-grid {
            grid-template-columns: 1fr;
        }
        
        .dg10-table {
            font-size: var(--dg10-font-size-xs);
        }
        
        .dg10-table th,
        .dg10-table td {
            padding: var(--dg10-spacing-sm);
        }
        
        .context-preview {
            max-width: 150px;
        }
        
        .metrics-grid {
            grid-template-columns: 1fr;
        }
        
        .recommendation-header {
            flex-direction: column;
            align-items: flex-start;
            gap: var(--dg10-spacing-xs);
        }
        
        .recommendation-type {
            align-self: flex-end;
        }
        
        .aiopms-notification {
            top: 10px;
            right: 10px;
            left: 10px;
            max-width: none;
        }
    }
    
    /* Laptop view optimizations */
    @media (min-width: 769px) and (max-width: 1366px) {
        #aiopms_page_select {
            min-width: 250px;
            font-size: var(--dg10-font-size-sm);
        }
        
        #aiopms_keywords_input {
            min-height: 120px;
        }
        
        .dg10-form-group {
            margin-bottom: var(--dg10-spacing-lg);
        }
        
        .analysis-actions {
            flex-wrap: wrap;
            gap: var(--dg10-spacing-sm);
        }
        
        .dg10-table {
            font-size: var(--dg10-font-size-sm);
        }
        
        .dg10-table th,
        .dg10-table td {
            padding: var(--dg10-spacing-sm) var(--dg10-spacing-md);
        }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        let analysisData = null;
        
        // Load pages on page load
        loadPages();
        
        // Keyword count display
        $('#aiopms_keywords_input').on('input', function() {
            const text = $(this).val();
            const keywords = text.split(/[\r\n,]+/).filter(k => k.trim().length > 0);
            $('#keyword-count').text('Keywords: ' + keywords.length);
        });
        
        // Form submission
        $('#aiopms-keyword-analysis-form').on('submit', function(e) {
            e.preventDefault();
            
            const pageId = $('#aiopms_page_select').val();
            const keywords = $('#aiopms_keywords_input').val();
            
            if (!pageId || !keywords.trim()) {
                alert('Please select a page and enter keywords to analyze.');
                return;
            }
            
            analyzeKeywords(pageId, keywords);
        });
        
        // Export functions
        $('#export-csv-btn').on('click', function() {
            if (analysisData) {
                exportAnalysis('csv');
            }
        });
        
        $('#export-json-btn').on('click', function() {
            if (analysisData) {
                exportAnalysis('json');
            }
        });
        
        function loadPages() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiopms_get_pages',
                    nonce: $('#aiopms_keyword_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        const select = $('#aiopms_page_select');
                        select.empty();
                        select.append('<option value="">Select a page...</option>');
                        
                        response.data.forEach(function(page) {
                            select.append(`<option value="${page.id}">${page.title} (${page.type})</option>`);
                        });
                    } else {
                        console.error('Failed to load pages:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading pages:', error);
                }
            });
        }
        
        function analyzeKeywords(pageId, keywords) {
            const btn = $('#aiopms-analyze-btn');
            const btnText = btn.find('.btn-text');
            const spinner = btn.find('.dg10-spinner');
            
            // Show loading state
            btn.prop('disabled', true);
            btnText.addClass('dg10-hidden');
            spinner.removeClass('dg10-hidden');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiopms_analyze_keywords',
                    page_id: pageId,
                    keywords: keywords,
                    nonce: $('#aiopms_keyword_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        analysisData = response.data;
                        displayResults(response.data);
                        $('#aiopms-analysis-results').removeClass('dg10-hidden');
                    } else {
                        alert('Analysis failed: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Analysis error:', error);
                    alert('Analysis failed. Please try again.');
                },
                complete: function() {
                    // Hide loading state
                    btn.prop('disabled', false);
                    btnText.removeClass('dg10-hidden');
                    spinner.addClass('dg10-hidden');
                }
            });
        }
        
        function showNotification(message, type = 'info') {
            const notification = $(`
                <div class="aiopms-notification notification-${type}">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close" aria-label="Close notification">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual close
            notification.find('.notification-close').on('click', function() {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }
        
        function displayResults(data) {
            // Display page info
            displayPageInfo(data.page_info);
            
            // Display summary
            displaySummary(data.summary);
            
            // Display keywords table
            displayKeywordsTable(data.keywords);
            
            // Display recommendations
            displayRecommendations(data.summary.recommendations);
        }
        
        function displayPageInfo(pageInfo) {
            const content = `
                <div class="page-info-grid">
                    <div class="page-info-item">
                        <div class="page-info-label">Page Title</div>
                        <div class="page-info-value">${pageInfo.title}</div>
                    </div>
                    <div class="page-info-item">
                        <div class="page-info-label">URL</div>
                        <div class="page-info-value"><a href="${pageInfo.url}" target="_blank">View Page</a></div>
                    </div>
                    <div class="page-info-item">
                        <div class="page-info-label">Word Count</div>
                        <div class="page-info-value">${pageInfo.word_count.toLocaleString()}</div>
                    </div>
                    <div class="page-info-item">
                        <div class="page-info-label">Content Size</div>
                        <div class="page-info-value">${(pageInfo.content_size / 1024).toFixed(1)} KB</div>
                    </div>
                    <div class="page-info-item">
                        <div class="page-info-label">Memory Used</div>
                        <div class="page-info-value">${pageInfo.memory_used} MB</div>
                    </div>
                    <div class="page-info-item">
                        <div class="page-info-label">Analysis Date</div>
                        <div class="page-info-value">${pageInfo.analysis_date}</div>
                    </div>
                </div>
            `;
            $('#page-info-content').html(content);
        }
        
        function displaySummary(summary) {
            const seoScoreClass = summary.seo_score >= 80 ? 'seo-excellent' : 
                                 summary.seo_score >= 60 ? 'seo-good' : 
                                 summary.seo_score >= 40 ? 'seo-fair' : 'seo-poor';
            
            const content = `
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-number">${summary.total_keywords}</div>
                        <div class="summary-label">Total Keywords</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number">${summary.keywords_found}</div>
                        <div class="summary-label">Keywords Found</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number">${summary.average_density}%</div>
                        <div class="summary-label">Avg Density</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number">${summary.average_relevance || 'N/A'}</div>
                        <div class="summary-label">Avg Relevance</div>
                    </div>
                    <div class="summary-item seo-score-item">
                        <div class="summary-number ${seoScoreClass}">${summary.seo_score || 'N/A'}</div>
                        <div class="summary-label">SEO Score</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number">${summary.total_words.toLocaleString()}</div>
                        <div class="summary-label">Total Words</div>
                    </div>
                </div>
                
                ${summary.performance_metrics ? `
                <div class="performance-metrics">
                    <h5>Performance Metrics</h5>
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <span class="metric-label">Well Optimized:</span>
                            <span class="metric-value good">${summary.performance_metrics.well_optimized}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Over Optimized:</span>
                            <span class="metric-value warning">${summary.performance_metrics.over_optimized}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Under Optimized:</span>
                            <span class="metric-value info">${summary.performance_metrics.under_optimized}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Not Found:</span>
                            <span class="metric-value error">${summary.performance_metrics.not_found}</span>
                        </div>
                    </div>
                </div>
                ` : ''}
            `;
            $('#summary-content').html(content);
        }
        
        function displayKeywordsTable(keywords) {
            const tbody = $('#keywords-table tbody');
            tbody.empty();
            
            keywords.forEach(function(keyword) {
                const areas = [];
                if (keyword.area_counts.title > 0) areas.push(`Title (${keyword.area_counts.title})`);
                if (keyword.area_counts.content > 0) areas.push(`Content (${keyword.area_counts.content})`);
                if (keyword.area_counts.meta_description > 0) areas.push(`Meta (${keyword.area_counts.meta_description})`);
                if (keyword.area_counts.excerpt > 0) areas.push(`Excerpt (${keyword.area_counts.excerpt})`);
                if (keyword.area_counts.headings > 0) areas.push(`Headings (${keyword.area_counts.headings})`);
                
                const context = keyword.context.length > 0 ? 
                    keyword.context[0].substring(0, 100) + '...' : 'No context found';
                
                const relevanceScore = keyword.relevance_score || 0;
                const relevanceClass = relevanceScore >= 70 ? 'relevance-high' : 
                                     relevanceScore >= 40 ? 'relevance-medium' : 'relevance-low';
                
                const row = `
                    <tr>
                        <td class="keyword-cell">${keyword.keyword}</td>
                        <td class="count-cell">${keyword.count}</td>
                        <td class="density-cell">${keyword.density}%</td>
                        <td><span class="status-badge status-${keyword.status}">${keyword.status}</span></td>
                        <td class="relevance-cell">
                            <span class="relevance-score ${relevanceClass}">${relevanceScore}</span>
                        </td>
                        <td class="areas-found">${areas.join(', ') || 'Not found'}</td>
                        <td class="context-preview">${context}</td>
                    </tr>
                `;
                tbody.append(row);
            });
        }
        
        function displayRecommendations(recommendations) {
            if (!recommendations || recommendations.length === 0) {
                $('#recommendations-content').html('<p>No specific recommendations available.</p>');
                return;
            }
            
            const content = `
                <div class="recommendations-container">
                    ${recommendations.map(rec => {
                        if (typeof rec === 'string') {
                            return `<div class="recommendation-item recommendation-simple">
                                <p>${rec}</p>
                            </div>`;
                        } else {
                            const typeClass = rec.type || 'info';
                            const priorityClass = rec.priority || 'medium';
                            const keywordsList = rec.keywords && rec.keywords.length > 0 ? 
                                `<div class="recommendation-keywords">
                                    <strong>Keywords:</strong> ${rec.keywords.join(', ')}
                                </div>` : '';
                            
                            return `<div class="recommendation-item recommendation-${typeClass} priority-${priorityClass}">
                                <div class="recommendation-header">
                                    <h5 class="recommendation-title">${rec.title || 'Recommendation'}</h5>
                                    <span class="recommendation-type">${rec.type || 'info'}</span>
                                </div>
                                <div class="recommendation-content">
                                    <p class="recommendation-message">${rec.message || rec}</p>
                                    ${keywordsList}
                                    ${rec.action ? `<div class="recommendation-action">
                                        <strong>Action:</strong> ${rec.action}
                                    </div>` : ''}
                                </div>
                            </div>`;
                        }
                    }).join('')}
                </div>
            `;
            $('#recommendations-content').html(content);
        }
        
        function exportAnalysis(format) {
            if (!analysisData) return;
            
            const form = $('<form>', {
                method: 'POST',
                action: ajaxurl,
                target: '_blank'
            });
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'aiopms_export_keyword_analysis'
            }));
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: $('#aiopms_keyword_nonce').val()
            }));
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'format',
                value: format
            }));
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'analysis_data',
                value: JSON.stringify(analysisData)
            }));
            
            $('body').append(form);
            form.submit();
            form.remove();
        }
    });
    </script>
    
    <!-- Accessibility CSS -->
    <style>
    /* Screen reader only content */
    .sr-only {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }
    
    /* Focus management */
    .dg10-sidebar-nav-item:focus,
    .dg10-btn:focus,
    input:focus,
    textarea:focus,
    select:focus {
        outline: 2px solid #0073aa !important;
        outline-offset: 2px !important;
    }
    
    /* Focus visible for keyboard navigation */
    .dg10-sidebar-nav-item:focus-visible {
        outline: 2px solid #0073aa !important;
        outline-offset: 2px !important;
    }
    
    /* Skip link for keyboard navigation */
    .skip-link {
        position: absolute;
        left: -9999px;
        top: 0;
        z-index: 999999;
        padding: 8px 16px;
        background: #0073aa;
        color: white;
        text-decoration: none;
        border-radius: 0 0 4px 4px;
    }
    
    .skip-link:focus {
        left: 0;
    }
    
    /* High contrast mode support */
    @media (prefers-contrast: high) {
        .dg10-sidebar-nav-item {
            border: 2px solid currentColor;
        }
        
        .dg10-btn {
            border: 2px solid currentColor;
        }
    }
    
    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
    
    /* Form field focus indicators */
    .dg10-form-textarea:focus,
    .dg10-form-input:focus {
        border-color: #0073aa !important;
        box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2) !important;
    }
    
    /* Button focus states */
    .dg10-btn:focus {
        box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2) !important;
    }
    
    /* Navigation focus states */
    .dg10-sidebar-nav-item.focused {
        background-color: rgba(0, 115, 170, 0.1) !important;
    }
    
    /* Error message accessibility */
    .notice {
        border-left: 4px solid #dc3232;
        padding: 12px;
        margin: 15px 0;
    }
    
    .notice-success {
        border-left-color: #46b450;
    }
    
    .notice-warning {
        border-left-color: #ffb900;
    }
    
    .notice-info {
        border-left-color: #00a0d2;
    }
    </style>
    <?php
}

/**
 * Display bulk management tab content.
 * 
 * @since 3.0
 */
function aiopms_bulk_management_tab() {
    ?>
    <div class="aiopms-bulk-management-container">
        <div class="aiopms-bulk-management-header">
            <h2><?php esc_html_e( 'Bulk Page Management', 'aiopms' ); ?></h2>
            <p><?php esc_html_e( 'Safely delete multiple pages with advanced filtering and recovery options.', 'aiopms' ); ?></p>
        </div>

        <div class="aiopms-bulk-management-content">
            <!-- Safety Warning -->
            <div class="aiopms-safety-warning">
                <div class="aiopms-warning-icon">⚠️</div>
                <div class="aiopms-warning-content">
                    <h3><?php esc_html_e( 'Important Safety Notice', 'aiopms' ); ?></h3>
                    <p><?php esc_html_e( 'Bulk deletion is permanent and cannot be undone. Always create a backup before proceeding.', 'aiopms' ); ?></p>
                </div>
            </div>

            <!-- Filter Options -->
            <div class="aiopms-bulk-filters">
                <h3><?php esc_html_e( 'Filter Pages for Deletion', 'aiopms' ); ?></h3>
                
                <form id="aiopms-bulk-filter-form" class="aiopms-filter-form">
                    <?php wp_nonce_field( 'aiopms_bulk_management', 'aiopms_bulk_nonce' ); ?>
                    
                    <div class="aiopms-filter-row">
                        <div class="aiopms-filter-group">
                            <label for="aiopms-deletion-type"><?php esc_html_e( 'Deletion Type:', 'aiopms' ); ?></label>
                            <select id="aiopms-deletion-type" name="deletion_type" class="aiopms-form-select">
                                <option value="ai-generated"><?php esc_html_e( 'AI-Generated Pages Only', 'aiopms' ); ?></option>
                                <option value="plugin-created"><?php esc_html_e( 'All Plugin-Created Pages', 'aiopms' ); ?></option>
                                <option value="date-range"><?php esc_html_e( 'Pages from Date Range', 'aiopms' ); ?></option>
                                <option value="status"><?php esc_html_e( 'Pages by Status', 'aiopms' ); ?></option>
                                <option value="custom"><?php esc_html_e( 'Custom Selection', 'aiopms' ); ?></option>
                            </select>
                        </div>

                        <div class="aiopms-filter-group" id="aiopms-date-range-filters" style="display: none;">
                            <label for="aiopms-start-date"><?php esc_html_e( 'Start Date:', 'aiopms' ); ?></label>
                            <input type="date" id="aiopms-start-date" name="start_date" class="aiopms-form-input">
                            
                            <label for="aiopms-end-date"><?php esc_html_e( 'End Date:', 'aiopms' ); ?></label>
                            <input type="date" id="aiopms-end-date" name="end_date" class="aiopms-form-input">
                        </div>

                        <div class="aiopms-filter-group" id="aiopms-status-filters" style="display: none;">
                            <label for="aiopms-page-status"><?php esc_html_e( 'Page Status:', 'aiopms' ); ?></label>
                            <select id="aiopms-page-status" name="page_status" class="aiopms-form-select">
                                <option value="draft"><?php esc_html_e( 'Draft', 'aiopms' ); ?></option>
                                <option value="pending"><?php esc_html_e( 'Pending Review', 'aiopms' ); ?></option>
                                <option value="private"><?php esc_html_e( 'Private', 'aiopms' ); ?></option>
                                <option value="trash"><?php esc_html_e( 'Trash', 'aiopms' ); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="aiopms-filter-actions">
                        <button type="button" id="aiopms-preview-pages" class="dg10-btn dg10-btn-primary">
                            <?php esc_html_e( 'Preview Pages', 'aiopms' ); ?>
                        </button>
                        <button type="button" id="aiopms-clear-filters" class="dg10-btn dg10-btn-secondary">
                            <?php esc_html_e( 'Clear Filters', 'aiopms' ); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview Results -->
            <div id="aiopms-preview-results" class="aiopms-preview-results" style="display: none;">
                <h3><?php esc_html_e( 'Pages to be Deleted', 'aiopms' ); ?></h3>
                <div id="aiopms-pages-list" class="aiopms-pages-list"></div>
                <div id="aiopms-deletion-summary" class="aiopms-deletion-summary"></div>
            </div>

            <!-- Confirmation Modal -->
            <div id="aiopms-deletion-modal" class="aiopms-modal" style="display: none;">
                <div class="aiopms-modal-content">
                    <div class="aiopms-modal-header">
                        <h3><?php esc_html_e( 'Confirm Bulk Deletion', 'aiopms' ); ?></h3>
                        <button type="button" class="aiopms-modal-close">&times;</button>
                    </div>
                    <div class="aiopms-modal-body">
                        <div class="aiopms-confirmation-warning">
                            <div class="aiopms-warning-icon">🚨</div>
                            <div class="aiopms-warning-content">
                                <h4><?php esc_html_e( 'This action cannot be undone!', 'aiopms' ); ?></h4>
                                <p><?php esc_html_e( 'You are about to permanently delete the selected pages. This action cannot be reversed.', 'aiopms' ); ?></p>
                            </div>
                        </div>
                        
                        <div class="aiopms-confirmation-details">
                            <p><strong><?php esc_html_e( 'Pages to delete:', 'aiopms' ); ?></strong> <span id="aiopms-confirmation-count">0</span></p>
                            <p><strong><?php esc_html_e( 'Deletion type:', 'aiopms' ); ?></strong> <span id="aiopms-confirmation-type">-</span></p>
                        </div>

                        <div class="aiopms-confirmation-input">
                            <label for="aiopms-confirm-text">
                                <?php esc_html_e( 'Type "DELETE" to confirm:', 'aiopms' ); ?>
                            </label>
                            <input type="text" id="aiopms-confirm-text" name="confirm_text" class="aiopms-form-input" placeholder="DELETE">
                        </div>

                        <div class="aiopms-backup-options">
                            <label>
                                <input type="checkbox" id="aiopms-create-backup" name="create_backup" checked>
                                <?php esc_html_e( 'Create backup before deletion', 'aiopms' ); ?>
                            </label>
                        </div>
                    </div>
                    <div class="aiopms-modal-footer">
                        <button type="button" id="aiopms-cancel-deletion" class="dg10-btn dg10-btn-secondary">
                            <?php esc_html_e( 'Cancel', 'aiopms' ); ?>
                        </button>
                        <button type="button" id="aiopms-confirm-deletion" class="dg10-btn dg10-btn-error" disabled>
                            <?php esc_html_e( 'Delete Pages', 'aiopms' ); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Progress Indicator -->
            <div id="aiopms-deletion-progress" class="aiopms-deletion-progress" style="display: none;">
                <div class="aiopms-progress-header">
                    <h3><?php esc_html_e( 'Deleting Pages...', 'aiopms' ); ?></h3>
                </div>
                <div class="aiopms-progress-bar">
                    <div id="aiopms-progress-fill" class="aiopms-progress-fill"></div>
                </div>
                <div id="aiopms-progress-text" class="aiopms-progress-text">
                    <?php esc_html_e( 'Preparing deletion...', 'aiopms' ); ?>
                </div>
            </div>

            <!-- Results -->
            <div id="aiopms-deletion-results" class="aiopms-deletion-results" style="display: none;">
                <h3><?php esc_html_e( 'Deletion Complete', 'aiopms' ); ?></h3>
                <div id="aiopms-results-content" class="aiopms-results-content"></div>
                <div class="aiopms-results-actions">
                    <button type="button" id="aiopms-export-results" class="dg10-btn dg10-btn-secondary">
                        <?php esc_html_e( 'Export Results', 'aiopms' ); ?>
                    </button>
                    <button type="button" id="aiopms-new-deletion" class="dg10-btn dg10-btn-primary">
                        <?php esc_html_e( 'New Deletion', 'aiopms' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .aiopms-bulk-management-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .aiopms-bulk-management-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .aiopms-bulk-management-header h2 {
        color: var(--dg10-primary);
        margin-bottom: 0.5rem;
    }

    .aiopms-safety-warning {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 2px solid #f59e0b;
        border-radius: 0.75rem;
        margin-bottom: 2rem;
    }

    .aiopms-warning-icon {
        font-size: 2rem;
        flex-shrink: 0;
    }

    .aiopms-warning-content h3 {
        margin: 0 0 0.5rem 0;
        color: #92400e;
    }

    .aiopms-warning-content p {
        margin: 0;
        color: #92400e;
    }

    .aiopms-bulk-filters {
        background: var(--dg10-white);
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .aiopms-filter-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .aiopms-filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        align-items: end;
    }

    .aiopms-filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .aiopms-filter-group label {
        font-weight: 600;
        color: var(--dg10-dark-blue);
    }

    .aiopms-filter-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    .aiopms-preview-results {
        background: var(--dg10-white);
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .aiopms-pages-list {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }

    .aiopms-page-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .aiopms-page-item:last-child {
        border-bottom: none;
    }

    .aiopms-page-info {
        flex: 1;
    }

    .aiopms-page-title {
        font-weight: 600;
        color: var(--dg10-dark-blue);
        margin-bottom: 0.25rem;
    }

    .aiopms-page-meta {
        font-size: 0.875rem;
        color: var(--dg10-neutral);
    }

    .aiopms-deletion-summary {
        background: #f3f4f6;
        padding: 1rem;
        border-radius: 0.5rem;
        text-align: center;
    }

    .aiopms-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    }

    .aiopms-modal-content {
        background: var(--dg10-white);
        border-radius: 0.75rem;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .aiopms-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .aiopms-modal-header h3 {
        margin: 0;
        color: var(--dg10-dark-blue);
    }

    .aiopms-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--dg10-neutral);
    }

    .aiopms-modal-body {
        padding: 1.5rem;
    }

    .aiopms-confirmation-warning {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem;
        background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
        border: 2px solid #ef4444;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .aiopms-confirmation-details {
        background: #f9fafb;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .aiopms-confirmation-input {
        margin-bottom: 1.5rem;
    }

    .aiopms-backup-options {
        margin-bottom: 1rem;
    }

    .aiopms-modal-footer {
        display: flex;
        gap: 1rem;
        padding: 1.5rem;
        border-top: 1px solid #e5e7eb;
        justify-content: flex-end;
    }

    .aiopms-deletion-progress {
        background: var(--dg10-white);
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        text-align: center;
    }

    .aiopms-progress-bar {
        width: 100%;
        height: 8px;
        background: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
        margin: 1rem 0;
    }

    .aiopms-progress-fill {
        height: 100%;
        background: linear-gradient(135deg, var(--dg10-primary) 0%, var(--dg10-accent) 100%);
        border-radius: 4px;
        transition: width 0.3s ease;
        width: 0%;
    }

    .aiopms-deletion-results {
        background: var(--dg10-white);
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .aiopms-results-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    @media (max-width: 768px) {
        .aiopms-filter-row {
            grid-template-columns: 1fr;
        }
        
        .aiopms-filter-actions {
            flex-direction: column;
        }
        
        .aiopms-modal-footer {
            flex-direction: column;
        }
    }
    </style>
    <?php
}
