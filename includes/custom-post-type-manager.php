<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Custom Post Type Manager for AIOPMS - Complete Overhaul
 * Handles dynamic custom post type registration, management, and integration
 * 
 * @package AIOPMS
 * @version 3.0
 * @author DG10 Agency
 * @since 3.0
 */

// Initialize custom post type manager with enhanced security and performance
function aiopms_init_custom_post_type_manager() {
    // Register existing dynamic CPTs on init with caching
    add_action('init', 'aiopms_register_existing_dynamic_cpts', 20);
    
    // Add admin menu for CPT management
    add_action('admin_menu', 'aiopms_add_cpt_management_menu');
    
    // Add REST API endpoints for CPT data
    add_action('rest_api_init', 'aiopms_register_cpt_rest_endpoints');
    
    // Add CPT data to hierarchy export
    add_filter('aiopms_hierarchy_export_data', 'aiopms_add_cpt_to_hierarchy_export');
    
    // Add CPT archives to menu generation
    add_filter('aiopms_menu_generation_pages', 'aiopms_add_cpt_archives_to_menus');
    
    // Add schema generation for CPTs
    add_action('aiopms_generate_schema_for_post', 'aiopms_generate_cpt_schema', 10, 2);
    
    // Add AJAX handlers for CPT management
    add_action('wp_ajax_aiopms_create_cpt_ajax', 'aiopms_handle_cpt_creation_ajax');
    add_action('wp_ajax_aiopms_delete_cpt_ajax', 'aiopms_handle_cpt_deletion_ajax');
    add_action('wp_ajax_aiopms_get_cpt_data', 'aiopms_get_cpt_data_ajax');
    add_action('wp_ajax_aiopms_bulk_cpt_operations', 'aiopms_handle_bulk_cpt_operations_ajax');
    add_action('wp_ajax_aiopms_update_cpt_ajax', 'aiopms_handle_cpt_update_ajax');
    
    // Add custom field meta boxes and save handlers
    add_action('add_meta_boxes', 'aiopms_add_custom_field_meta_boxes');
    add_action('save_post', 'aiopms_save_custom_field_data', 10, 2);
    
    // Performance optimization: Clear cache when CPTs are updated
    add_action('updated_option', 'aiopms_clear_cpt_cache', 10, 3);
    
    // Security: Add capability checks
    add_action('admin_init', 'aiopms_check_cpt_management_capabilities');
}
add_action('plugins_loaded', 'aiopms_init_custom_post_type_manager');

/**
 * Register existing dynamic CPTs with caching and performance optimization
 * 
 * @since 3.0
 */
function aiopms_register_existing_dynamic_cpts() {
    // Check cache first for performance
    $cached_cpts = wp_cache_get('aiopms_dynamic_cpts', 'aiopms_cpt_cache');
    
    if (false === $cached_cpts) {
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', []);
        wp_cache_set('aiopms_dynamic_cpts', $dynamic_cpts, 'aiopms_cpt_cache', HOUR_IN_SECONDS);
        $cached_cpts = $dynamic_cpts;
    }
    
    if (!empty($cached_cpts) && is_array($cached_cpts)) {
        foreach ($cached_cpts as $post_type => $cpt_data) {
            if (aiopms_validate_cpt_data($cpt_data)) {
        aiopms_register_dynamic_custom_post_type($cpt_data);
            }
        }
    }
}

/**
 * Complete CPT registration function with security and validation
 * Moved from ai-generator.php and enhanced
 * 
 * @param array $cpt_data CPT configuration data
 * @return bool|WP_Error Success status or error object
 * @since 3.0
 */
function aiopms_register_dynamic_custom_post_type($cpt_data) {
    // Validate input data
    if (!aiopms_validate_cpt_data($cpt_data)) {
        return new WP_Error('invalid_cpt_data', __('Invalid CPT data provided', 'aiopms'));
    }
    
    // Sanitize all input data
    $post_type = sanitize_key($cpt_data['name']);
    $label = sanitize_text_field($cpt_data['label']);
    $description = sanitize_textarea_field($cpt_data['description'] ?? '');
    
    // Validate post type name
    if (empty($post_type) || strlen($post_type) > 20) {
        return new WP_Error('invalid_post_type', __('Invalid post type name', 'aiopms'));
    }
    
    // Build comprehensive labels array
    $labels = array(
        'name'                  => $label,
        'singular_name'         => $label,
        'menu_name'             => $label,
        'name_admin_bar'        => $label,
        'archives'              => $label . ' Archives',
        'attributes'            => $label . ' Attributes',
        'parent_item_colon'     => 'Parent ' . $label . ':',
        'all_items'             => 'All ' . $label,
        'add_new_item'          => 'Add New ' . $label,
        'add_new'               => 'Add New',
        'new_item'              => 'New ' . $label,
        'edit_item'             => 'Edit ' . $label,
        'update_item'           => 'Update ' . $label,
        'view_item'             => 'View ' . $label,
        'view_items'            => 'View ' . $label,
        'search_items'          => 'Search ' . $label,
        'not_found'             => 'No ' . strtolower($label) . ' found',
        'not_found_in_trash'    => 'No ' . strtolower($label) . ' found in Trash',
        'featured_image'        => 'Featured Image',
        'set_featured_image'    => 'Set featured image',
        'remove_featured_image' => 'Remove featured image',
        'use_featured_image'    => 'Use as featured image',
        'insert_into_item'      => 'Insert into ' . strtolower($label),
        'uploaded_to_this_item' => 'Uploaded to this ' . strtolower($label),
        'items_list'            => $label . ' list',
        'items_list_navigation' => $label . ' list navigation',
        'filter_items_list'     => 'Filter ' . strtolower($label) . ' list',
    );
    
    // Configure CPT arguments with security and performance in mind
    $args = array(
        'label'                 => $label,
        'labels'                => $labels,
        'description'           => $description,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'show_in_nav_menus'     => true,
        'show_in_admin_bar'     => true,
        'show_in_rest'          => true,
        'rest_base'             => $post_type,
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'rest_namespace'        => 'wp/v2',
        'has_archive'           => true,
        'hierarchical'          => isset($cpt_data['hierarchical']) ? (bool) $cpt_data['hierarchical'] : false,
        'supports'              => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions', 'author', 'page-attributes'),
        'taxonomies'            => array('category', 'post_tag'),
        'menu_icon'             => sanitize_text_field($cpt_data['menu_icon'] ?? 'dashicons-admin-post'),
        'menu_position'         => (int) ($cpt_data['menu_position'] ?? 25),
        'capability_type'       => 'post',
        'map_meta_cap'          => true,
        'query_var'             => true,
        'can_export'            => true,
        'delete_with_user'      => false,
    );
    
    // Apply filters for extensibility
    $args = apply_filters('aiopms_cpt_registration_args', $args, $post_type, $cpt_data);
    
    // Register the post type
    $result = register_post_type($post_type, $args);
    
    if (is_wp_error($result)) {
        error_log('AIOPMS CPT Registration Error: ' . $result->get_error_message());
        return $result;
    }
    
    // Register custom fields if provided
    if (!empty($cpt_data['custom_fields']) && is_array($cpt_data['custom_fields'])) {
        $field_result = aiopms_register_custom_fields($post_type, $cpt_data['custom_fields']);
        if (is_wp_error($field_result)) {
            return $field_result;
        }
    }
    
    // Store CPT data for persistence with proper sanitization
    $existing_cpts = get_option('aiopms_dynamic_cpts', array());
    $existing_cpts[$post_type] = aiopms_sanitize_cpt_data($cpt_data);
    update_option('aiopms_dynamic_cpts', $existing_cpts);
    
    // Clear cache
    wp_cache_delete('aiopms_dynamic_cpts', 'aiopms_cpt_cache');
    
    // Generate sample content if enabled
    $settings = get_option('aiopms_cpt_settings', array());
    if (!empty($settings['auto_generate_sample_content']) && !empty($cpt_data['sample_entries'])) {
        aiopms_create_sample_cpt_entries($cpt_data);
    }
    
    // Trigger action for other plugins/themes
    do_action('aiopms_cpt_registered', $post_type, $cpt_data);
    
    // Log successful registration
    aiopms_log_cpt_activity('register', $post_type, true);
    
    return true;
}

/**
 * Add CPT management menu with proper capabilities
 * 
 * @since 3.0
 */
function aiopms_add_cpt_management_menu() {
    add_submenu_page(
        'aiopms-page-management',
        __('Custom Post Types', 'aiopms'),
        __('Custom Post Types', 'aiopms'),
        'manage_options',
        'aiopms-cpt-management',
        'aiopms_cpt_management_page'
    );
}

/**
 * Enhanced CPT management page with AJAX, loading states, and accessibility
 * 
 * @since 3.0
 */
function aiopms_cpt_management_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'aiopms'));
    }
    
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'list';
    
    // Define menu items with their details
    $menu_items = array(
        'list' => array(
            'title' => __('Manage CPTs', 'aiopms'),
            'icon' => '📋',
            'description' => __('View and manage existing custom post types', 'aiopms')
        ),
        'create' => array(
            'title' => __('Create New CPT', 'aiopms'),
            'icon' => '➕',
            'description' => __('Create new custom post types manually', 'aiopms')
        ),
        'templates' => array(
            'title' => __('Templates & Presets', 'aiopms'),
            'icon' => '📋',
            'description' => __('Use predefined CPT templates for common use cases', 'aiopms')
        ),
        'bulk' => array(
            'title' => __('Bulk Operations', 'aiopms'),
            'icon' => '⚡',
            'description' => __('Perform bulk operations on multiple CPTs', 'aiopms')
        ),
        'import-export' => array(
            'title' => __('Import/Export', 'aiopms'),
            'icon' => '📤',
            'description' => __('Import and export CPT configurations', 'aiopms')
        ),
        'settings' => array(
            'title' => __('Settings', 'aiopms'),
            'icon' => '⚙️',
            'description' => __('Configure custom post type settings', 'aiopms')
        )
    );
    ?>
    <div class="wrap dg10-brand" id="aiopms-cpt-management">
        <!-- Skip Link for Accessibility -->
        <a href="#main-content" class="skip-link"><?php esc_html_e('Skip to main content', 'aiopms'); ?></a>
        
        <div class="dg10-main-layout">
            <!-- Admin Sidebar -->
            <aside class="dg10-admin-sidebar" role="complementary" aria-label="<?php esc_attr_e('CPT Management Navigation', 'aiopms'); ?>">
                <div class="dg10-sidebar-header">
                    <div class="dg10-sidebar-title">
                        <img src="<?php echo esc_url(AIOPMS_PLUGIN_URL . 'assets/images/logo.svg'); ?>" 
                             alt="<?php esc_attr_e('AIOPMS Plugin Logo', 'aiopms'); ?>" 
                             style="width: 24px; height: 24px;">
                        <?php esc_html_e('AIOPMS', 'aiopms'); ?>
                    </div>
                    <p class="dg10-sidebar-subtitle"><?php esc_html_e('Custom Post Type Management', 'aiopms'); ?></p>
                </div>
                
                <nav class="dg10-sidebar-nav" role="navigation" aria-label="<?php esc_attr_e('CPT Management Navigation', 'aiopms'); ?>">
                    <ul role="list">
                    <?php foreach ($menu_items as $tab_key => $item): ?>
                            <li role="listitem">
                                <a href="<?php echo esc_url(add_query_arg(array('page' => 'aiopms-cpt-management', 'tab' => $tab_key), admin_url('admin.php'))); ?>" 
                                   class="dg10-sidebar-nav-item <?php echo $active_tab === $tab_key ? 'active' : ''; ?>"
                                   role="menuitem"
                                   aria-label="<?php echo esc_attr($item['title'] . ' - ' . $item['description']); ?>"
                                   aria-current="<?php echo $active_tab === $tab_key ? 'page' : 'false'; ?>"
                           title="<?php echo esc_attr($item['description']); ?>">
                                    <span class="nav-icon" aria-hidden="true"><?php echo $item['icon']; ?></span>
                                    <span class="nav-text"><?php echo esc_html($item['title']); ?></span>
                        </a>
                            </li>
                    <?php endforeach; ?>
                    </ul>
                </nav>
            </aside>
            
            <!-- Main Content Area -->
            <main class="dg10-main-content" role="main" aria-label="<?php esc_attr_e('Main Content Area', 'aiopms'); ?>" id="main-content">
                <article class="dg10-card">
                    <header class="dg10-card-header">
                        <div class="dg10-hero-content">
                            <div class="dg10-hero-text">
                                <h1 id="page-title"><?php echo esc_html($menu_items[$active_tab]['title']); ?></h1>
                                <p class="dg10-hero-description">
                                    <?php echo esc_html($menu_items[$active_tab]['description']); ?>
                                </p>
                            </div>
                        </div>
                    </header>
                    
                    <div class="dg10-card-body">
                        <!-- Loading Overlay -->
                        <div id="aiopms-loading-overlay" class="aiopms-loading-overlay" style="display: none;" aria-hidden="true">
                            <div class="aiopms-loading-spinner">
                                <div class="spinner"></div>
                                <p><?php esc_html_e('Processing...', 'aiopms'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Error/Success Messages -->
                        <div id="aiopms-messages" class="aiopms-messages" role="status" aria-live="polite"></div>
                        
                        <?php
                        // Route to appropriate tab content
                        switch ($active_tab) {
                            case 'list':
                            aiopms_cpt_list_tab();
                                break;
                            case 'create':
                            aiopms_cpt_create_tab();
                                break;
                            case 'templates':
                                aiopms_cpt_templates_tab();
                                break;
                            case 'bulk':
                                aiopms_cpt_bulk_operations_tab();
                                break;
                            case 'import-export':
                                aiopms_cpt_import_export_tab();
                                break;
                            case 'settings':
                            aiopms_cpt_settings_tab();
                                break;
                            default:
                                aiopms_cpt_list_tab();
                                break;
                        }
                        ?>
                    </div>
                    
                    <!-- Footer -->
                    <footer class="dg10-card-footer">
                        <div class="dg10-promotion-section">
                            <div class="dg10-promotion-header">
                                <img src="<?php echo esc_url(AIOPMS_PLUGIN_URL . 'assets/images/dg10-brand-logo.svg'); ?>" 
                                     alt="<?php esc_attr_e('DG10 Agency Logo', 'aiopms'); ?>" 
                                     class="dg10-promotion-logo">
                                <h3><?php esc_html_e('About us', 'aiopms'); ?></h3>
                            </div>
                            <div class="dg10-promotion-content">
                                <p><?php esc_html_e('DG10 Agency specializes in creating powerful WordPress and Elementor solutions. We help businesses build custom websites, optimize performance, and implement complex integrations that drive results.', 'aiopms'); ?></p>
                                <div class="dg10-promotion-buttons">
                                    <a href="https://www.dg10.agency" target="_blank" class="dg10-btn dg10-btn-primary">
                                        <?php esc_html_e('Visit Website', 'aiopms'); ?>
                                        <span class="dg10-btn-icon">→</span>
                                    </a>
                                    <a href="https://calendly.com/dg10-agency/30min" target="_blank" class="dg10-btn dg10-btn-outline">
                                        <span class="dg10-btn-icon">📅</span>
                                        <?php esc_html_e('Book a Free Consultation', 'aiopms'); ?>
                                    </a>
                                </div>
                                <p class="dg10-promotion-footer">
                                    <?php 
                                    printf(
                                        esc_html__('This is an open-source project - please %s.', 'aiopms'),
                                        '<a href="' . esc_url(AIOPMS_GITHUB_URL) . '" target="_blank">' . esc_html__('star the repo on GitHub', 'aiopms') . '</a>'
                                    ); 
                                    ?>
                                </p>
                            </div>
                        </div>
                    </footer>
                </article>
            </main>
                    </div>
                </div>
    <?php
}

/**
 * Enhanced CPT list tab with AJAX and better UX
 * 
 * @since 3.0
 */
function aiopms_cpt_list_tab() {
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', array());
    ?>
    <div class="aiopms-cpt-list" id="cpt-list-container">
        <div class="aiopms-cpt-list-header">
            <div class="aiopms-search-filter">
                <label for="cpt-search" class="screen-reader-text"><?php esc_html_e('Search CPTs', 'aiopms'); ?></label>
                <input type="search" id="cpt-search" placeholder="<?php esc_attr_e('Search custom post types...', 'aiopms'); ?>" 
                       class="aiopms-search-input" aria-label="<?php esc_attr_e('Search custom post types', 'aiopms'); ?>">
                
                <select id="cpt-filter-status" aria-label="<?php esc_attr_e('Filter by status', 'aiopms'); ?>">
                    <option value=""><?php esc_html_e('All Statuses', 'aiopms'); ?></option>
                    <option value="active"><?php esc_html_e('Active', 'aiopms'); ?></option>
                    <option value="inactive"><?php esc_html_e('Inactive', 'aiopms'); ?></option>
                </select>
                
                <button type="button" class="button" id="refresh-cpt-list" 
                        aria-label="<?php esc_attr_e('Refresh CPT list', 'aiopms'); ?>">
                    <span class="dashicons dashicons-update" aria-hidden="true"></span>
                    <?php esc_html_e('Refresh', 'aiopms'); ?>
                </button>
            </div>
            
            <div class="aiopms-bulk-actions">
                <select id="bulk-action-select" aria-label="<?php esc_attr_e('Bulk actions', 'aiopms'); ?>">
                    <option value=""><?php esc_html_e('Bulk Actions', 'aiopms'); ?></option>
                    <option value="activate"><?php esc_html_e('Activate', 'aiopms'); ?></option>
                    <option value="deactivate"><?php esc_html_e('Deactivate', 'aiopms'); ?></option>
                    <option value="export"><?php esc_html_e('Export', 'aiopms'); ?></option>
                    <option value="delete"><?php esc_html_e('Delete', 'aiopms'); ?></option>
                </select>
                <button type="button" class="button" id="apply-bulk-action" disabled>
                    <?php esc_html_e('Apply', 'aiopms'); ?>
                </button>
            </div>
        </div>
        
        <?php if (empty($dynamic_cpts)): ?>
            <div class="aiopms-empty-state">
                <div class="aiopms-empty-state-icon">
                    <span class="dashicons dashicons-admin-post" aria-hidden="true"></span>
                </div>
                <h3><?php esc_html_e('No Custom Post Types', 'aiopms'); ?></h3>
                <p><?php esc_html_e('You haven\'t created any custom post types yet. Get started by creating your first CPT.', 'aiopms'); ?></p>
                <div class="aiopms-empty-state-actions">
                    <a href="<?php echo esc_url(add_query_arg('tab', 'create')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
                        <?php esc_html_e('Create New CPT', 'aiopms'); ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'templates')); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                        <?php esc_html_e('Use Template', 'aiopms'); ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="aiopms-cpt-grid" id="cpt-grid">
                    <?php foreach ($dynamic_cpts as $post_type => $cpt_data): ?>
                    <?php aiopms_render_cpt_card($post_type, $cpt_data); ?>
                    <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <div class="aiopms-pagination" id="cpt-pagination">
                <!-- Pagination will be populated via AJAX if needed -->
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Enhanced CPT create tab with AJAX support and better UX
 * 
 * @since 3.0
 */
function aiopms_cpt_create_tab() {
    // Handle manual CPT creation (legacy support)
    if (isset($_POST['create_manual_cpt']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'aiopms_create_manual_cpt')) {
        $result = aiopms_process_cpt_creation($_POST);
        
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>' . esc_html__('Custom post type created successfully!', 'aiopms') . '</p></div>';
        }
    }
    
    ?>
    <div class="aiopms-cpt-create">
        <p>Manually create a custom post type with custom fields.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('aiopms_create_manual_cpt'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Post Type Slug</th>
                    <td>
                        <input type="text" name="cpt_name" class="regular-text" required placeholder="e.g., portfolio, case_study">
                        <p class="description">Lowercase, no spaces, use underscores for multiple words</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Display Label</th>
                    <td>
                        <input type="text" name="cpt_label" class="regular-text" required placeholder="e.g., Portfolio, Case Study">
                        <p class="description">Human-readable name for the post type</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Description</th>
                    <td>
                        <textarea name="cpt_description" rows="3" class="large-text" placeholder="Brief description of what this post type is for"></textarea>
                    </td>
                </tr>
            </table>
            
            <h3>Custom Fields</h3>
            <div id="custom-fields-container">
                <div class="custom-field-row">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Field Name</th>
                            <th scope="row">Field Label</th>
                            <th scope="row">Field Type</th>
                            <th scope="row">Description</th>
                            <th scope="row">Required</th>
                            <th scope="row">Actions</th>
                        </tr>
                        <tr>
                            <td><input type="text" name="custom_fields[0][name]" class="regular-text" placeholder="field_slug"></td>
                            <td><input type="text" name="custom_fields[0][label]" class="regular-text" placeholder="Field Label"></td>
                            <td>
                                <select name="custom_fields[0][type]">
                                    <option value="text">Text</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="number">Number</option>
                                    <option value="date">Date</option>
                                    <option value="url">URL</option>
                                    <option value="image">Image URL</option>
                                    <option value="select">Select</option>
                                </select>
                            </td>
                            <td><input type="text" name="custom_fields[0][description]" class="regular-text" placeholder="Field description"></td>
                            <td><input type="checkbox" name="custom_fields[0][required]"></td>
                            <td><button type="button" class="button remove-field">Remove</button></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <p>
                <button type="button" id="add-custom-field" class="button">Add Custom Field</button>
            </p>
            
            <?php submit_button('Create Custom Post Type', 'primary', 'create_manual_cpt'); ?>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        let fieldIndex = 1;
        
        $('#add-custom-field').on('click', function() {
            const fieldRow = `
                <div class="custom-field-row">
                    <table class="form-table">
                        <tr>
                            <td><input type="text" name="custom_fields[${fieldIndex}][name]" class="regular-text" placeholder="field_slug"></td>
                            <td><input type="text" name="custom_fields[${fieldIndex}][label]" class="regular-text" placeholder="Field Label"></td>
                            <td>
                                <select name="custom_fields[${fieldIndex}][type]">
                                    <option value="text">Text</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="number">Number</option>
                                    <option value="date">Date</option>
                                    <option value="url">URL</option>
                                    <option value="image">Image URL</option>
                                    <option value="select">Select</option>
                                </select>
                            </td>
                            <td><input type="text" name="custom_fields[${fieldIndex}][description]" class="regular-text" placeholder="Field description"></td>
                            <td><input type="checkbox" name="custom_fields[${fieldIndex}][required]"></td>
                            <td><button type="button" class="button remove-field">Remove</button></td>
                        </tr>
                    </table>
                </div>
            `;
            
            $('#custom-fields-container').append(fieldRow);
            fieldIndex++;
        });
        
        $(document).on('click', '.remove-field', function() {
            $(this).closest('.custom-field-row').remove();
        });
    });
    </script>
    
    <style>
    .custom-field-row {
        margin-bottom: 20px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #f9f9f9;
    }
    
    .custom-field-row table {
        margin: 0;
    }
    
    .custom-field-row th,
    .custom-field-row td {
        padding: 8px;
        vertical-align: middle;
    }
    </style>
    <?php
}

// CPT settings tab
function aiopms_cpt_settings_tab() {
    // Handle settings save
    if (isset($_POST['save_cpt_settings']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'aiopms_save_cpt_settings')) {
        $settings = array(
            'auto_schema_generation' => isset($_POST['auto_schema_generation']),
            'include_in_menus' => isset($_POST['include_in_menus']),
            'include_in_hierarchy' => isset($_POST['include_in_hierarchy']),
            'auto_generate_sample_content' => isset($_POST['auto_generate_sample_content'])
        );
        
        update_option('aiopms_cpt_settings', $settings);
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    $settings = get_option('aiopms_cpt_settings', array(
        'auto_schema_generation' => true,
        'include_in_menus' => true,
        'include_in_hierarchy' => true,
        'auto_generate_sample_content' => true
    ));
    
    ?>
    <div class="aiopms-cpt-settings">
        <p>Configure how custom post types integrate with other AIOPMS features.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('aiopms_save_cpt_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Auto Schema Generation</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_schema_generation" value="1" <?php checked($settings['auto_schema_generation']); ?>>
                            Automatically generate schema markup for custom post types
                        </label>
                        <p class="description">When enabled, schema markup will be generated for all custom post type entries</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Include in Menu Generation</th>
                    <td>
                        <label>
                            <input type="checkbox" name="include_in_menus" value="1" <?php checked($settings['include_in_menus']); ?>>
                            Include custom post type archives in menu generation
                        </label>
                        <p class="description">When enabled, custom post type archive pages will be included in generated menus</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Include in Hierarchy</th>
                    <td>
                        <label>
                            <input type="checkbox" name="include_in_hierarchy" value="1" <?php checked($settings['include_in_hierarchy']); ?>>
                            Include custom post types in hierarchy view and export
                        </label>
                        <p class="description">When enabled, custom post types will appear in the page hierarchy</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Auto Generate Sample Content</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_generate_sample_content" value="1" <?php checked($settings['auto_generate_sample_content']); ?>>
                            Automatically create sample entries when creating custom post types
                        </label>
                        <p class="description">When enabled, sample entries will be created automatically for new custom post types</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Settings', 'primary', 'save_cpt_settings'); ?>
        </form>
    </div>
    <?php
}

// Register REST API endpoints for CPT data
function aiopms_register_cpt_rest_endpoints() {
    register_rest_route('aiopms/v1', '/cpts', array(
        'methods' => 'GET',
        'callback' => 'aiopms_get_cpts_rest_data',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
}

// Get CPTs data for REST API
function aiopms_get_cpts_rest_data($request) {
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', []);
    $cpt_data = array();
    
    foreach ($dynamic_cpts as $post_type => $cpt_info) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        $cpt_data[] = array(
            'post_type' => $post_type,
            'label' => $cpt_info['label'],
            'description' => $cpt_info['description'],
            'posts_count' => count($posts),
            'custom_fields' => $cpt_info['custom_fields'] ?? array(),
            'posts' => array_map(function($post) {
                return array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'status' => $post->post_status,
                    'url' => get_permalink($post->ID)
                );
            }, $posts)
        );
    }
    
    return rest_ensure_response($cpt_data);
}

// Add CPT data to hierarchy export
function aiopms_add_cpt_to_hierarchy_export($data) {
    $settings = get_option('aiopms_cpt_settings', array());
    if (!isset($settings['include_in_hierarchy']) || !$settings['include_in_hierarchy']) {
        return $data;
    }
    
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', []);
    
    foreach ($dynamic_cpts as $post_type => $cpt_info) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        foreach ($posts as $post) {
            $data[] = array(
                'id' => 'cpt_' . $post->ID,
                'text' => $cpt_info['label'] . ': ' . $post->post_title,
                'parent' => '#',
                'type' => 'cpt',
                'post_type' => $post_type,
                'url' => get_permalink($post->ID)
            );
        }
    }
    
    return $data;
}

// Add CPT archives to menu generation
function aiopms_add_cpt_archives_to_menus($pages) {
    $settings = get_option('aiopms_cpt_settings', array());
    if (!isset($settings['include_in_menus']) || !$settings['include_in_menus']) {
        return $pages;
    }
    
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', []);
    
    foreach ($dynamic_cpts as $post_type => $cpt_info) {
        $archive_url = get_post_type_archive_link($post_type);
        if ($archive_url) {
            $pages[] = array(
                'title' => $cpt_info['label'],
                'url' => $archive_url,
                'type' => 'cpt_archive',
                'post_type' => $post_type
            );
        }
    }
    
    return $pages;
}

// Generate schema markup for custom post types
function aiopms_generate_cpt_schema($post_id, $post) {
    $settings = get_option('aiopms_cpt_settings', array());
    if (!isset($settings['auto_schema_generation']) || !$settings['auto_schema_generation']) {
        return;
    }
    
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', []);
    $post_type = $post->post_type;
    
    if (!isset($dynamic_cpts[$post_type])) {
        return;
    }
    
    $cpt_info = $dynamic_cpts[$post_type];
    
    // Generate appropriate schema based on post type
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'CreativeWork',
        'name' => $post->post_title,
        'description' => wp_trim_words($post->post_content, 20),
        'url' => get_permalink($post_id),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_date('c', $post_id)
    );
    
    // Add custom fields to schema
    if (!empty($cpt_info['custom_fields'])) {
        foreach ($cpt_info['custom_fields'] as $field) {
            $value = get_post_meta($post_id, $field['name'], true);
            if (!empty($value)) {
                $schema[$field['name']] = $value;
            }
        }
    }
    
    // Add schema to post meta
    update_post_meta($post_id, '_aiopms_schema_markup', $schema);
}

/**
 * Security and validation functions
 */

// Validate CPT data structure and content
function aiopms_validate_cpt_data($cpt_data) {
    if (!is_array($cpt_data)) return false;
    
    // Required fields
    if (empty($cpt_data['name']) || empty($cpt_data['label'])) return false;
    
    // Validate post type name format
    $post_type = sanitize_key($cpt_data['name']);
    if ($post_type !== $cpt_data['name'] || strlen($post_type) > 20) return false;
    
    // Check for reserved post type names
    $reserved_names = array('post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset');
    if (in_array($post_type, $reserved_names)) return false;
    
    return true;
}

// Validate field data structure
function aiopms_validate_field_data($field) {
    if (!is_array($field)) return false;
    if (empty($field['name']) || empty($field['label']) || empty($field['type'])) return false;
    
    $allowed_types = array('text', 'textarea', 'number', 'date', 'datetime', 'url', 'email', 'image', 'select', 'radio', 'checkbox', 'color', 'wysiwyg');
    if (!in_array($field['type'], $allowed_types)) return false;
    
    return true;
}

// Sanitize CPT data for storage
function aiopms_sanitize_cpt_data($cpt_data) {
    $sanitized = array(
        'name' => sanitize_key($cpt_data['name']),
        'label' => sanitize_text_field($cpt_data['label']),
        'description' => sanitize_textarea_field($cpt_data['description'] ?? ''),
        'menu_icon' => sanitize_text_field($cpt_data['menu_icon'] ?? 'dashicons-admin-post'),
        'menu_position' => (int) ($cpt_data['menu_position'] ?? 25),
        'hierarchical' => (bool) ($cpt_data['hierarchical'] ?? false),
        'custom_fields' => array()
    );
    
    if (!empty($cpt_data['custom_fields']) && is_array($cpt_data['custom_fields'])) {
        foreach ($cpt_data['custom_fields'] as $field) {
            if (aiopms_validate_field_data($field)) {
                $sanitized['custom_fields'][] = array(
                    'name' => sanitize_key($field['name']),
                    'label' => sanitize_text_field($field['label']),
                    'type' => sanitize_key($field['type']),
                    'description' => sanitize_textarea_field($field['description'] ?? ''),
                    'required' => (bool) ($field['required'] ?? false),
                    'options' => isset($field['options']) ? array_map('sanitize_text_field', (array) $field['options']) : array()
                );
            }
        }
    }
    
    return $sanitized;
}

// Sanitize field values based on field type
function aiopms_sanitize_field_value($value, $field_type) {
    switch ($field_type) {
        case 'textarea':
        case 'wysiwyg':
            return sanitize_textarea_field($value);
        case 'url':
            return esc_url_raw($value);
        case 'email':
            return sanitize_email($value);
        case 'number':
            return (int) $value;
        case 'checkbox':
            return (bool) $value;
        case 'color':
            return sanitize_hex_color($value);
        default:
            return sanitize_text_field($value);
    }
}

// Validate field values
function aiopms_validate_field_value($value, $field_config) {
    switch ($field_config['type']) {
        case 'url':
            return empty($value) || filter_var($value, FILTER_VALIDATE_URL);
        case 'email':
            return empty($value) || filter_var($value, FILTER_VALIDATE_EMAIL);
        case 'number':
            return is_numeric($value);
        case 'date':
        case 'datetime':
            return empty($value) || strtotime($value) !== false;
        default:
            return true;
    }
}

// Check user capabilities for CPT management
function aiopms_check_cpt_management_capabilities() {
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'aiopms-cpt-management') {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'aiopms'));
        }
    }
}

// Clear CPT cache when options are updated
function aiopms_clear_cpt_cache($option_name, $old_value, $value) {
    if ($option_name === 'aiopms_dynamic_cpts') {
        wp_cache_delete('aiopms_dynamic_cpts', 'aiopms_cpt_cache');
    }
}

// Log CPT activities for debugging and monitoring
function aiopms_log_cpt_activity($action, $post_type, $success, $error_message = '') {
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'action' => $action,
        'post_type' => $post_type,
        'success' => $success,
        'user_id' => get_current_user_id(),
        'error_message' => $error_message
    );
    
    $logs = get_option('aiopms_cpt_logs', array());
    $logs[] = $log_entry;
    
    // Keep only last 100 log entries
    if (count($logs) > 100) {
        $logs = array_slice($logs, -100);
    }
    
    update_option('aiopms_cpt_logs', $logs);
}

/**
 * Enhanced custom field registration with security
 */
function aiopms_register_custom_fields($post_type, $fields) {
    if (empty($fields) || !is_array($fields)) {
        return new WP_Error('invalid_fields', __('Invalid fields data provided', 'aiopms'));
    }
    
    foreach ($fields as $field) {
        if (!aiopms_validate_field_data($field)) {
            continue;
        }
        
        $field_name = sanitize_key($field['name']);
        $field_config = array(
            'name' => $field_name,
            'label' => sanitize_text_field($field['label']),
            'type' => sanitize_key($field['type']),
            'description' => sanitize_textarea_field($field['description'] ?? ''),
            'required' => (bool) ($field['required'] ?? false),
            'options' => isset($field['options']) ? array_map('sanitize_text_field', (array) $field['options']) : array(),
            'post_type' => $post_type
        );
        
        // Register field in REST API for Gutenberg support
        register_rest_field($post_type, $field_name, array(
            'get_callback' => function($post) use ($field_name) {
                return get_post_meta($post['id'], $field_name, true);
            },
            'update_callback' => function($value, $post) use ($field_name, $field_config) {
                return update_post_meta($post->ID, $field_name, aiopms_sanitize_field_value($value, $field_config['type']));
            },
            'schema' => aiopms_get_field_schema($field_config['type']),
        ));
        
        // Store field configuration
        $existing_fields = get_option('aiopms_custom_fields', array());
        $existing_fields[$post_type][$field_name] = $field_config;
        update_option('aiopms_custom_fields', $existing_fields);
    }
    
    return true;
}

// Get field schema for REST API
function aiopms_get_field_schema($field_type) {
    $schemas = array(
        'text' => array('type' => 'string'),
        'textarea' => array('type' => 'string'),
        'number' => array('type' => 'integer'),
        'url' => array('type' => 'string', 'format' => 'uri'),
        'email' => array('type' => 'string', 'format' => 'email'),
        'date' => array('type' => 'string', 'format' => 'date'),
        'datetime' => array('type' => 'string', 'format' => 'date-time'),
        'checkbox' => array('type' => 'boolean'),
        'color' => array('type' => 'string', 'pattern' => '^#[0-9a-fA-F]{6}$'),
    );
    
    return $schemas[$field_type] ?? array('type' => 'string');
}

// Helper function to get all dynamic CPTs
function aiopms_get_dynamic_cpts() {
    return get_option('aiopms_dynamic_cpts', []);
}

// Helper function to check if a post type is dynamic
function aiopms_is_dynamic_cpt($post_type) {
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', []);
    return isset($dynamic_cpts[$post_type]);
}

// Helper function to get CPT info
function aiopms_get_cpt_info($post_type) {
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', []);
    return isset($dynamic_cpts[$post_type]) ? $dynamic_cpts[$post_type] : null;
}

/**
 * Process CPT creation from form data
 * 
 * @param array $form_data Form submission data
 * @return bool|WP_Error Success status or error object
 * @since 3.0
 */
function aiopms_process_cpt_creation($form_data) {
    // Security check
    if (!current_user_can('manage_options')) {
        return new WP_Error('insufficient_permissions', __('You do not have permission to create custom post types.', 'aiopms'));
    }
    
    $cpt_data = array(
        'name' => sanitize_key($form_data['cpt_name'] ?? ''),
        'label' => sanitize_text_field($form_data['cpt_label'] ?? ''),
        'description' => sanitize_textarea_field($form_data['cpt_description'] ?? ''),
        'menu_icon' => sanitize_text_field($form_data['cpt_menu_icon'] ?? 'dashicons-admin-post'),
        'hierarchical' => isset($form_data['cpt_hierarchical']),
        'custom_fields' => array()
    );
    
    // Process custom fields
    if (isset($form_data['custom_fields']) && is_array($form_data['custom_fields'])) {
        foreach ($form_data['custom_fields'] as $field) {
            if (!empty($field['name']) && !empty($field['label'])) {
                $cpt_data['custom_fields'][] = array(
                    'name' => sanitize_key($field['name']),
                    'label' => sanitize_text_field($field['label']),
                    'type' => sanitize_key($field['type']),
                    'description' => sanitize_textarea_field($field['description'] ?? ''),
                    'required' => isset($field['required']),
                    'options' => isset($field['options']) ? array_map('sanitize_text_field', explode(',', $field['options'])) : array()
                );
            }
        }
    }
    
    return aiopms_register_dynamic_custom_post_type($cpt_data);
}

/**
 * AJAX handler for CPT creation
 * 
 * @since 3.0
 */
function aiopms_handle_cpt_creation_ajax() {
    // Security checks
    check_ajax_referer('aiopms_cpt_ajax', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to create custom post types.', 'aiopms'));
    }
    
    $result = aiopms_process_cpt_creation($_POST);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success(array(
            'message' => __('Custom post type created successfully!', 'aiopms'),
            'cpt_data' => $_POST
        ));
    }
}

/**
 * AJAX handler for CPT deletion
 * 
 * @since 3.0
 */
function aiopms_handle_cpt_deletion_ajax() {
    // Security checks
    check_ajax_referer('aiopms_cpt_ajax', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to delete custom post types.', 'aiopms'));
    }
    
    $post_type = sanitize_key($_POST['post_type'] ?? '');
    
    if (empty($post_type)) {
        wp_send_json_error(__('Invalid post type specified.', 'aiopms'));
    }
    
    $result = aiopms_delete_custom_post_type($post_type);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success(array(
            'message' => __('Custom post type deleted successfully!', 'aiopms')
        ));
    }
}

/**
 * Delete a custom post type
 * 
 * @param string $post_type Post type slug to delete
 * @return bool|WP_Error Success status or error object
 * @since 3.0
 */
function aiopms_delete_custom_post_type($post_type) {
    $post_type = sanitize_key($post_type);
    
    if (empty($post_type)) {
        return new WP_Error('invalid_post_type', __('Invalid post type specified.', 'aiopms'));
    }
    
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', array());
    
    if (!isset($dynamic_cpts[$post_type])) {
        return new WP_Error('cpt_not_found', __('Custom post type not found.', 'aiopms'));
    }
    
    // Remove from stored CPTs
    unset($dynamic_cpts[$post_type]);
    update_option('aiopms_dynamic_cpts', $dynamic_cpts);
    
    // Remove custom fields configuration
    $custom_fields = get_option('aiopms_custom_fields', array());
    if (isset($custom_fields[$post_type])) {
        unset($custom_fields[$post_type]);
        update_option('aiopms_custom_fields', $custom_fields);
    }
    
    // Clear cache
    wp_cache_delete('aiopms_dynamic_cpts', 'aiopms_cpt_cache');
    
    // Log activity
    aiopms_log_cpt_activity('delete', $post_type, true);
    
    return true;
}

/**
 * Add custom field meta boxes with proper security and validation
 * 
 * @since 3.0
 */
function aiopms_add_custom_field_meta_boxes() {
    $custom_fields = get_option('aiopms_custom_fields', array());
    
    foreach ($custom_fields as $post_type => $fields) {
        if (post_type_exists($post_type)) {
            foreach ($fields as $field_name => $field_config) {
                add_meta_box(
                    'aiopms_' . $field_name,
                    $field_config['label'],
                    'aiopms_render_custom_field_meta_box',
                    $post_type,
                    'normal',
                    'high',
                    array('field_config' => $field_config)
                );
            }
        }
    }
}

/**
 * Render custom field meta box with comprehensive security
 * 
 * @param WP_Post $post Current post object
 * @param array $metabox Metabox configuration
 * @since 3.0
 */
function aiopms_render_custom_field_meta_box($post, $metabox) {
    $field_config = $metabox['args']['field_config'];
    $field_name = $field_config['name'];
    $field_type = $field_config['type'];
    $field_label = $field_config['label'];
    $field_description = $field_config['description'];
    $field_required = $field_config['required'];
    $field_options = $field_config['options'] ?? array();
    
    $value = get_post_meta($post->ID, $field_name, true);
    $required_attr = $field_required ? 'required aria-required="true"' : '';
    $field_id = 'aiopms_' . $field_name;
    
    // Add nonce for security
    wp_nonce_field('aiopms_save_custom_fields_' . $post->ID, 'aiopms_custom_fields_nonce');
    
    ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($field_label); ?>
                    <?php if ($field_required): ?>
                        <span class="required" aria-label="<?php esc_attr_e('Required field', 'aiopms'); ?>">*</span>
                    <?php endif; ?>
                </label>
            </th>
            <td>
                <?php aiopms_render_field_input($field_id, $field_name, $field_type, $value, $field_options, $required_attr, $field_description); ?>
                
                <?php if (!empty($field_description)): ?>
                    <p class="description" id="<?php echo esc_attr($field_id . '_desc'); ?>">
                        <?php echo esc_html($field_description); ?>
                    </p>
                <?php endif; ?>
                
                <div class="aiopms-field-validation" id="<?php echo esc_attr($field_id . '_validation'); ?>" 
                     style="display: none;" role="alert" aria-live="polite"></div>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Render field input based on field type with security
 * 
 * @param string $field_id Field HTML ID
 * @param string $field_name Field name
 * @param string $field_type Field type
 * @param mixed $value Current field value
 * @param array $options Field options for select/radio fields
 * @param string $required_attr Required attribute string
 * @param string $field_description Field description for aria-describedby
 * @since 3.0
 */
function aiopms_render_field_input($field_id, $field_name, $field_type, $value, $options = array(), $required_attr = '', $field_description = '') {
    $aria_describedby = !empty($field_description) ? 'aria-describedby="' . esc_attr($field_id . '_desc') . '"' : '';
    
    switch ($field_type) {
        case 'textarea':
            ?>
            <textarea name="<?php echo esc_attr($field_name); ?>" 
                      id="<?php echo esc_attr($field_id); ?>" 
                      rows="4" 
                      cols="50" 
                      class="large-text"
                      <?php echo $required_attr; ?>
                      <?php echo $aria_describedby; ?>><?php echo esc_textarea($value); ?></textarea>
            <?php
            break;
            
        case 'select':
            ?>
            <select name="<?php echo esc_attr($field_name); ?>" 
                    id="<?php echo esc_attr($field_id); ?>" 
                    class="regular-text"
                    <?php echo $required_attr; ?>
                    <?php echo $aria_describedby; ?>>
                <option value=""><?php esc_html_e('Select an option', 'aiopms'); ?></option>
                <?php foreach ($options as $option_value => $option_label): ?>
                    <option value="<?php echo esc_attr($option_value); ?>" 
                            <?php selected($value, $option_value); ?>>
                        <?php echo esc_html($option_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
            break;
            
        case 'url':
            ?>
            <input type="url" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="regular-text" 
                   placeholder="https://"
                   <?php echo $required_attr; ?>
                   <?php echo $aria_describedby; ?>>
            <?php
            break;
            
        case 'email':
            ?>
            <input type="email" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="regular-text"
                   <?php echo $required_attr; ?>
                   <?php echo $aria_describedby; ?>>
            <?php
            break;
            
        case 'number':
            ?>
            <input type="number" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="small-text"
                   <?php echo $required_attr; ?>
                   <?php echo $aria_describedby; ?>>
            <?php
            break;
            
        case 'date':
            ?>
            <input type="date" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   value="<?php echo esc_attr($value); ?>"
                   <?php echo $required_attr; ?>
                   <?php echo $aria_describedby; ?>>
            <?php
            break;
            
        case 'checkbox':
            ?>
            <label for="<?php echo esc_attr($field_id); ?>">
                <input type="checkbox" 
                       name="<?php echo esc_attr($field_name); ?>" 
                       id="<?php echo esc_attr($field_id); ?>"
                       value="1" 
                       <?php checked($value, 1); ?>
                       <?php echo $aria_describedby; ?>>
                <?php esc_html_e('Check this option', 'aiopms'); ?>
            </label>
            <?php
            break;
            
        default: // text
            ?>
            <input type="text" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="regular-text"
                   <?php echo $required_attr; ?>
                   <?php echo $aria_describedby; ?>>
            <?php
            break;
    }
}

/**
 * Save custom field data with comprehensive security and validation
 * 
 * @param int $post_id Post ID
 * @param WP_Post $post Post object
 * @since 3.0
 */
function aiopms_save_custom_field_data($post_id, $post) {
    // Security checks
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    // Verify nonce
    if (!isset($_POST['aiopms_custom_fields_nonce']) || 
        !wp_verify_nonce(sanitize_key($_POST['aiopms_custom_fields_nonce']), 'aiopms_save_custom_fields_' . $post_id)) {
        return;
    }
    
    $custom_fields = get_option('aiopms_custom_fields', array());
    $post_type = get_post_type($post_id);
    
    if (!isset($custom_fields[$post_type])) {
        return;
    }
    
    foreach ($custom_fields[$post_type] as $field_name => $field_config) {
        if (isset($_POST[$field_name])) {
            $value = aiopms_sanitize_field_value(wp_unslash($_POST[$field_name]), $field_config['type']);
            
            // Validate required fields
            if ($field_config['required'] && empty($value)) {
                add_filter('redirect_post_location', function($location) use ($field_config) {
                    return add_query_arg('aiopms_error', 'required_field_' . $field_config['name'], $location);
                });
                continue;
            }
            
            // Validate field-specific rules
            if (!aiopms_validate_field_value($value, $field_config)) {
                add_filter('redirect_post_location', function($location) use ($field_config) {
                    return add_query_arg('aiopms_error', 'invalid_field_' . $field_config['name'], $location);
                });
                continue;
            }
            
            update_post_meta($post_id, $field_name, $value);
        } else if ($field_config['type'] === 'checkbox') {
            // Handle unchecked checkboxes
            update_post_meta($post_id, $field_name, 0);
        }
    }
}

/**
 * Render individual CPT card with enhanced functionality
 * 
 * @param string $post_type Post type slug
 * @param array $cpt_data CPT configuration data
 * @since 3.0
 */
function aiopms_render_cpt_card($post_type, $cpt_data) {
    $posts_count = wp_count_posts($post_type);
    $total_posts = isset($posts_count->publish) ? ($posts_count->publish + $posts_count->draft + $posts_count->private) : 0;
    $is_active = post_type_exists($post_type);
    $last_modified = get_option('aiopms_cpt_modified_' . $post_type, '');
    ?>
    <div class="aiopms-cpt-card" data-cpt="<?php echo esc_attr($post_type); ?>">
        <div class="aiopms-cpt-card-header">
            <div class="aiopms-cpt-checkbox">
                <input type="checkbox" id="cpt-<?php echo esc_attr($post_type); ?>" 
                       value="<?php echo esc_attr($post_type); ?>" class="cpt-checkbox"
                       aria-label="<?php echo esc_attr(sprintf(__('Select %s', 'aiopms'), $cpt_data['label'])); ?>">
            </div>
            <div class="aiopms-cpt-status">
                <span class="aiopms-status-indicator <?php echo $is_active ? 'active' : 'inactive'; ?>" 
                      title="<?php echo $is_active ? esc_attr__('Active', 'aiopms') : esc_attr__('Inactive', 'aiopms'); ?>">
                </span>
            </div>
            <div class="aiopms-cpt-actions">
                <button type="button" class="aiopms-action-btn" data-action="edit" 
                        data-cpt="<?php echo esc_attr($post_type); ?>"
                        aria-label="<?php echo esc_attr(sprintf(__('Edit %s', 'aiopms'), $cpt_data['label'])); ?>">
                    <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                </button>
                <button type="button" class="aiopms-action-btn" data-action="duplicate" 
                        data-cpt="<?php echo esc_attr($post_type); ?>"
                        aria-label="<?php echo esc_attr(sprintf(__('Duplicate %s', 'aiopms'), $cpt_data['label'])); ?>">
                    <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                </button>
                <button type="button" class="aiopms-action-btn aiopms-danger" data-action="delete" 
                        data-cpt="<?php echo esc_attr($post_type); ?>"
                        aria-label="<?php echo esc_attr(sprintf(__('Delete %s', 'aiopms'), $cpt_data['label'])); ?>">
                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                </button>
            </div>
        </div>
        
        <div class="aiopms-cpt-card-body">
            <div class="aiopms-cpt-icon">
                <span class="dashicons <?php echo esc_attr($cpt_data['menu_icon'] ?? 'dashicons-admin-post'); ?>" aria-hidden="true"></span>
            </div>
            <div class="aiopms-cpt-info">
                <h3 class="aiopms-cpt-title">
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=' . $post_type)); ?>" 
                       title="<?php echo esc_attr(sprintf(__('View all %s', 'aiopms'), $cpt_data['label'])); ?>">
                        <?php echo esc_html($cpt_data['label']); ?>
                    </a>
                </h3>
                <code class="aiopms-cpt-slug"><?php echo esc_html($post_type); ?></code>
                
                <?php if (!empty($cpt_data['description'])): ?>
                    <p class="aiopms-cpt-description"><?php echo esc_html($cpt_data['description']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="aiopms-cpt-card-footer">
            <div class="aiopms-cpt-stats">
                <div class="aiopms-stat">
                    <span class="aiopms-stat-number"><?php echo esc_html($total_posts); ?></span>
                    <span class="aiopms-stat-label"><?php esc_html_e('Posts', 'aiopms'); ?></span>
                </div>
                <div class="aiopms-stat">
                    <span class="aiopms-stat-number"><?php echo esc_html(count($cpt_data['custom_fields'] ?? array())); ?></span>
                    <span class="aiopms-stat-label"><?php esc_html_e('Fields', 'aiopms'); ?></span>
                </div>
            </div>
            
            <div class="aiopms-cpt-quick-actions">
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=' . $post_type)); ?>" 
                   class="button button-small">
                    <?php esc_html_e('View Posts', 'aiopms'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . $post_type)); ?>" 
                   class="button button-small button-primary">
                    <?php esc_html_e('Add New', 'aiopms'); ?>
                </a>
            </div>
            
            <?php if (!empty($last_modified)): ?>
                <div class="aiopms-cpt-meta">
                    <small><?php echo esc_html(sprintf(__('Modified: %s', 'aiopms'), $last_modified)); ?></small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Add placeholder functions for missing tabs
function aiopms_cpt_templates_tab() {
    // Handle template creation
    if (isset($_POST['create_from_template']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'aiopms_create_from_template')) {
        aiopms_create_cpt_from_template();
    }
    
    $templates = aiopms_get_cpt_templates();
    ?>
    <div class="aiopms-cpt-templates">
        <div class="aiopms-templates-header">
            <h3><?php esc_html_e('CPT Templates & Presets', 'aiopms'); ?></h3>
            <p><?php esc_html_e('Choose from pre-built custom post type templates for common use cases. Templates include custom fields and sample content.', 'aiopms'); ?></p>
        </div>
        
        <div class="aiopms-templates-grid">
            <?php foreach ($templates as $template_id => $template): ?>
                <div class="aiopms-template-card" data-template="<?php echo esc_attr($template_id); ?>">
                    <div class="aiopms-template-header">
                        <div class="aiopms-template-icon">
                            <span class="dashicons <?php echo esc_attr($template['icon']); ?>" aria-hidden="true"></span>
                        </div>
                        <div class="aiopms-template-info">
                            <h4><?php echo esc_html($template['name']); ?></h4>
                            <p class="aiopms-template-description"><?php echo esc_html($template['description']); ?></p>
                        </div>
                    </div>
                    
                    <div class="aiopms-template-details">
                        <div class="aiopms-template-stats">
                            <div class="aiopms-stat">
                                <span class="aiopms-stat-number"><?php echo esc_html(count($template['custom_fields'])); ?></span>
                                <span class="aiopms-stat-label"><?php esc_html_e('Fields', 'aiopms'); ?></span>
                            </div>
                            <div class="aiopms-stat">
                                <span class="aiopms-stat-number"><?php echo esc_html(count($template['sample_entries'] ?? [])); ?></span>
                                <span class="aiopms-stat-label"><?php esc_html_e('Samples', 'aiopms'); ?></span>
                            </div>
                        </div>
                        
                        <div class="aiopms-template-features">
                            <h5><?php esc_html_e('Features', 'aiopms'); ?></h5>
                            <ul>
                                <?php foreach ($template['features'] as $feature): ?>
                                    <li><?php echo esc_html($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <?php if (!empty($template['custom_fields'])): ?>
                            <div class="aiopms-template-fields">
                                <h5><?php esc_html_e('Custom Fields', 'aiopms'); ?></h5>
                                <div class="aiopms-fields-preview">
                                    <?php foreach (array_slice($template['custom_fields'], 0, 3) as $field): ?>
                                        <span class="aiopms-field-tag"><?php echo esc_html($field['label']); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($template['custom_fields']) > 3): ?>
                                        <span class="aiopms-field-tag aiopms-more-fields">
                                            +<?php echo esc_html(count($template['custom_fields']) - 3); ?> <?php esc_html_e('more', 'aiopms'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="aiopms-template-actions">
                        <button type="button" class="button button-secondary aiopms-preview-template" 
                                data-template="<?php echo esc_attr($template_id); ?>">
                            <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                            <?php esc_html_e('Preview', 'aiopms'); ?>
                        </button>
                        
                        <form method="post" action="" class="aiopms-template-form" style="display: inline;">
                            <?php wp_nonce_field('aiopms_create_from_template'); ?>
                            <input type="hidden" name="template_id" value="<?php echo esc_attr($template_id); ?>">
                            <button type="submit" name="create_from_template" class="button button-primary">
                                <span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
                                <?php esc_html_e('Create CPT', 'aiopms'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Template Preview Modal -->
        <div id="aiopms-template-preview-modal" class="aiopms-modal" style="display: none;">
            <div class="aiopms-modal-content aiopms-modal-large">
                <div class="aiopms-modal-header">
                    <h3 id="preview-template-name"><?php esc_html_e('Template Preview', 'aiopms'); ?></h3>
                    <button class="aiopms-modal-close">&times;</button>
                </div>
                <div class="aiopms-modal-body" id="preview-template-content">
                    <!-- Template preview content will be loaded here -->
                </div>
                <div class="aiopms-modal-footer">
                    <button type="button" class="button button-secondary aiopms-modal-close">
                        <?php esc_html_e('Close', 'aiopms'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="create-from-preview">
                        <span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
                        <?php esc_html_e('Create CPT', 'aiopms'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function aiopms_cpt_bulk_operations_tab() {
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', array());
    ?>
    <div class="aiopms-cpt-bulk">
        <div class="aiopms-bulk-header">
            <h3><?php esc_html_e('Bulk Operations', 'aiopms'); ?></h3>
            <p><?php esc_html_e('Perform bulk operations on multiple custom post types. Select CPTs and choose an action to apply.', 'aiopms'); ?></p>
        </div>
        
        <?php if (empty($dynamic_cpts)): ?>
            <div class="aiopms-empty-state">
                <div class="aiopms-empty-state-icon">
                    <span class="dashicons dashicons-admin-post" aria-hidden="true"></span>
                </div>
                <h4><?php esc_html_e('No Custom Post Types', 'aiopms'); ?></h4>
                <p><?php esc_html_e('You need to create some custom post types before you can perform bulk operations.', 'aiopms'); ?></p>
                <a href="<?php echo esc_url(add_query_arg('tab', 'create')); ?>" class="button button-primary">
                    <?php esc_html_e('Create Your First CPT', 'aiopms'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="aiopms-bulk-operations">
                <div class="aiopms-bulk-selection">
                    <div class="aiopms-bulk-controls">
                        <label>
                            <input type="checkbox" id="select-all-bulk" class="aiopms-select-all">
                            <?php esc_html_e('Select All CPTs', 'aiopms'); ?>
                        </label>
                        <span class="aiopms-selection-count">
                            <span id="selected-count">0</span> <?php esc_html_e('CPTs selected', 'aiopms'); ?>
                        </span>
                    </div>
                    
                    <div class="aiopms-bulk-actions-panel">
                        <select id="bulk-action-selector" class="aiopms-bulk-selector">
                            <option value=""><?php esc_html_e('Choose Bulk Action...', 'aiopms'); ?></option>
                            <option value="activate"><?php esc_html_e('Activate Selected CPTs', 'aiopms'); ?></option>
                            <option value="deactivate"><?php esc_html_e('Deactivate Selected CPTs', 'aiopms'); ?></option>
                            <option value="export"><?php esc_html_e('Export Selected CPTs', 'aiopms'); ?></option>
                            <option value="duplicate"><?php esc_html_e('Duplicate Selected CPTs', 'aiopms'); ?></option>
                            <option value="delete"><?php esc_html_e('Delete Selected CPTs', 'aiopms'); ?></option>
                        </select>
                        
                        <button type="button" id="apply-bulk-action-btn" class="button button-primary" disabled>
                            <?php esc_html_e('Apply Action', 'aiopms'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="aiopms-bulk-cpt-list">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th width="50px"><?php esc_html_e('Select', 'aiopms'); ?></th>
                                <th><?php esc_html_e('CPT Name', 'aiopms'); ?></th>
                                <th><?php esc_html_e('Label', 'aiopms'); ?></th>
                                <th><?php esc_html_e('Status', 'aiopms'); ?></th>
                                <th><?php esc_html_e('Posts', 'aiopms'); ?></th>
                                <th><?php esc_html_e('Fields', 'aiopms'); ?></th>
                                <th><?php esc_html_e('Last Modified', 'aiopms'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dynamic_cpts as $post_type => $cpt_data): ?>
                                <?php
                                $posts_count = wp_count_posts($post_type);
                                $total_posts = isset($posts_count->publish) ? ($posts_count->publish + $posts_count->draft + $posts_count->private) : 0;
                                $is_active = post_type_exists($post_type);
                                $last_modified = get_option('aiopms_cpt_modified_' . $post_type, '');
                                ?>
                                <tr data-cpt="<?php echo esc_attr($post_type); ?>">
                                    <td>
                                        <input type="checkbox" class="aiopms-cpt-checkbox" value="<?php echo esc_attr($post_type); ?>">
                                    </td>
                                    <td>
                                        <code><?php echo esc_html($post_type); ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($cpt_data['label']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="aiopms-status-badge <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                            <?php echo $is_active ? esc_html__('Active', 'aiopms') : esc_html__('Inactive', 'aiopms'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo esc_html($total_posts); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(count($cpt_data['custom_fields'] ?? array())); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($last_modified ? date('M j, Y', strtotime($last_modified)) : 'Never'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="aiopms-bulk-results" id="bulk-results" style="display: none;">
                    <h4><?php esc_html_e('Operation Results', 'aiopms'); ?></h4>
                    <div id="bulk-results-content"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function aiopms_cpt_import_export_tab() {
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', array());
    
    // Handle export request
    if (isset($_POST['export_cpts']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'aiopms_export_cpts')) {
        aiopms_handle_cpt_export();
        return;
    }
    
    // Handle import request
    if (isset($_POST['import_cpts']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'aiopms_import_cpts')) {
        aiopms_handle_cpt_import();
    }
    ?>
    <div class="aiopms-cpt-import-export">
        <div class="aiopms-import-export-header">
            <h3><?php esc_html_e('Import/Export CPTs', 'aiopms'); ?></h3>
            <p><?php esc_html_e('Import and export custom post type configurations to backup, migrate, or share your CPTs.', 'aiopms'); ?></p>
        </div>
        
        <div class="aiopms-import-export-grid">
            <!-- Export Section -->
            <div class="aiopms-export-section">
                <div class="aiopms-section-header">
                    <h4><?php esc_html_e('Export CPTs', 'aiopms'); ?></h4>
                    <p><?php esc_html_e('Export your custom post types to a JSON file for backup or migration.', 'aiopms'); ?></p>
                </div>
                
                <?php if (empty($dynamic_cpts)): ?>
                    <div class="aiopms-empty-state">
                        <span class="dashicons dashicons-download" aria-hidden="true"></span>
                        <p><?php esc_html_e('No custom post types to export.', 'aiopms'); ?></p>
                    </div>
                <?php else: ?>
                    <form method="post" action="" class="aiopms-export-form">
                        <?php wp_nonce_field('aiopms_export_cpts'); ?>
                        
                        <div class="aiopms-export-options">
                            <h5><?php esc_html_e('Export Options', 'aiopms'); ?></h5>
                            
                            <label class="aiopms-export-option">
                                <input type="radio" name="export_type" value="all" checked>
                                <span class="option-label">
                                    <strong><?php esc_html_e('Export All CPTs', 'aiopms'); ?></strong>
                                    <small><?php esc_html_e('Export all custom post types', 'aiopms'); ?></small>
                                </span>
                            </label>
                            
                            <label class="aiopms-export-option">
                                <input type="radio" name="export_type" value="selected">
                                <span class="option-label">
                                    <strong><?php esc_html_e('Export Selected CPTs', 'aiopms'); ?></strong>
                                    <small><?php esc_html_e('Choose specific CPTs to export', 'aiopms'); ?></small>
                                </span>
                            </label>
                        </div>
                        
                        <div class="aiopms-cpt-selection" id="cpt-selection" style="display: none;">
                            <h5><?php esc_html_e('Select CPTs to Export', 'aiopms'); ?></h5>
                            <?php foreach ($dynamic_cpts as $post_type => $cpt_data): ?>
                                <label class="aiopms-cpt-option">
                                    <input type="checkbox" name="export_cpts[]" value="<?php echo esc_attr($post_type); ?>">
                                    <span class="cpt-info">
                                        <strong><?php echo esc_html($cpt_data['label']); ?></strong>
                                        <code><?php echo esc_html($post_type); ?></code>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="aiopms-export-actions">
                            <button type="submit" name="export_cpts" class="button button-primary">
                                <span class="dashicons dashicons-download" aria-hidden="true"></span>
                                <?php esc_html_e('Export CPTs', 'aiopms'); ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Import Section -->
            <div class="aiopms-import-section">
                <div class="aiopms-section-header">
                    <h4><?php esc_html_e('Import CPTs', 'aiopms'); ?></h4>
                    <p><?php esc_html_e('Import custom post types from a JSON file.', 'aiopms'); ?></p>
                </div>
                
                <form method="post" action="" enctype="multipart/form-data" class="aiopms-import-form">
                    <?php wp_nonce_field('aiopms_import_cpts'); ?>
                    
                    <div class="aiopms-import-options">
                        <div class="aiopms-file-upload">
                            <label for="cpt-import-file" class="aiopms-upload-label">
                                <span class="dashicons dashicons-upload" aria-hidden="true"></span>
                                <span class="upload-text"><?php esc_html_e('Choose JSON file to import', 'aiopms'); ?></span>
                                <input type="file" id="cpt-import-file" name="cpt_import_file" accept=".json" required>
                            </label>
                            <p class="description"><?php esc_html_e('Select a JSON file exported from AIOPMS CPT Manager.', 'aiopms'); ?></p>
                        </div>
                        
                        <div class="aiopms-import-settings">
                            <h5><?php esc_html_e('Import Settings', 'aiopms'); ?></h5>
                            
                            <label class="aiopms-import-option">
                                <input type="checkbox" name="import_overwrite" value="1">
                                <span class="option-label">
                                    <strong><?php esc_html_e('Overwrite Existing CPTs', 'aiopms'); ?></strong>
                                    <small><?php esc_html_e('Replace existing CPTs with the same name', 'aiopms'); ?></small>
                                </span>
                            </label>
                            
                            <label class="aiopms-import-option">
                                <input type="checkbox" name="import_activate" value="1" checked>
                                <span class="option-label">
                                    <strong><?php esc_html_e('Activate Imported CPTs', 'aiopms'); ?></strong>
                                    <small><?php esc_html_e('Automatically activate imported CPTs', 'aiopms'); ?></small>
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="aiopms-import-actions">
                        <button type="submit" name="import_cpts" class="button button-primary">
                            <span class="dashicons dashicons-upload" aria-hidden="true"></span>
                            <?php esc_html_e('Import CPTs', 'aiopms'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Import/Export History -->
        <div class="aiopms-import-export-history">
            <h4><?php esc_html_e('Recent Operations', 'aiopms'); ?></h4>
            <div class="aiopms-history-list">
                <?php aiopms_display_import_export_history(); ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * AJAX handler to retrieve CPT data for editing/display
 * 
 * @since 3.0
 */
function aiopms_get_cpt_data_ajax() {
    // Security checks
    check_ajax_referer('aiopms_cpt_ajax', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'aiopms'));
    }
    
    $post_type = sanitize_key($_POST['post_type'] ?? '');
    
    if (empty($post_type)) {
        wp_send_json_error(__('Invalid post type specified.', 'aiopms'));
    }
    
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', array());
    
    if (!isset($dynamic_cpts[$post_type])) {
        wp_send_json_error(__('Custom post type not found.', 'aiopms'));
    }
    
    $cpt_data = $dynamic_cpts[$post_type];
    
    // Get additional data
    $posts_count = wp_count_posts($post_type);
    $total_posts = isset($posts_count->publish) ? ($posts_count->publish + $posts_count->draft + $posts_count->private) : 0;
    $is_active = post_type_exists($post_type);
    $last_modified = get_option('aiopms_cpt_modified_' . $post_type, '');
    
    wp_send_json_success(array(
        'cpt_data' => $cpt_data,
        'stats' => array(
            'total_posts' => $total_posts,
            'is_active' => $is_active,
            'last_modified' => $last_modified,
            'custom_fields_count' => count($cpt_data['custom_fields'] ?? array())
        )
    ));
}

/**
 * AJAX handler for bulk CPT operations
 * 
 * @since 3.0
 */
function aiopms_handle_bulk_cpt_operations_ajax() {
    // Security checks
    check_ajax_referer('aiopms_cpt_ajax', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'aiopms'));
    }
    
    $action = sanitize_key($_POST['bulk_action'] ?? '');
    $cpt_ids = array_map('sanitize_key', (array) ($_POST['cpt_ids'] ?? array()));
    
    if (empty($action) || empty($cpt_ids)) {
        wp_send_json_error(__('Invalid bulk action or no CPTs selected.', 'aiopms'));
    }
    
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', array());
    $results = array();
    $success_count = 0;
    $error_count = 0;
    
    foreach ($cpt_ids as $post_type) {
        if (!isset($dynamic_cpts[$post_type])) {
            $results[] = array(
                'post_type' => $post_type,
                'status' => 'error',
                'message' => __('CPT not found.', 'aiopms')
            );
            $error_count++;
            continue;
        }
        
        switch ($action) {
            case 'activate':
                $result = aiopms_register_dynamic_custom_post_type($dynamic_cpts[$post_type]);
                if (is_wp_error($result)) {
                    $results[] = array(
                        'post_type' => $post_type,
                        'status' => 'error',
                        'message' => $result->get_error_message()
                    );
                    $error_count++;
                } else {
                    $results[] = array(
                        'post_type' => $post_type,
                        'status' => 'success',
                        'message' => __('CPT activated successfully.', 'aiopms')
                    );
                    $success_count++;
                }
                break;
                
            case 'deactivate':
                // Remove from WordPress registration (but keep in database)
                unregister_post_type($post_type);
                $results[] = array(
                    'post_type' => $post_type,
                    'status' => 'success',
                    'message' => __('CPT deactivated successfully.', 'aiopms')
                );
                $success_count++;
                break;
                
            case 'delete':
                $delete_result = aiopms_delete_custom_post_type($post_type);
                if (is_wp_error($delete_result)) {
                    $results[] = array(
                        'post_type' => $post_type,
                        'status' => 'error',
                        'message' => $delete_result->get_error_message()
                    );
                    $error_count++;
                } else {
                    $results[] = array(
                        'post_type' => $post_type,
                        'status' => 'success',
                        'message' => __('CPT deleted successfully.', 'aiopms')
                    );
                    $success_count++;
                }
                break;
                
            case 'export':
                $export_data = array(
                    'post_type' => $post_type,
                    'cpt_data' => $dynamic_cpts[$post_type],
                    'export_date' => current_time('mysql'),
                    'version' => '3.0'
                );
                $results[] = array(
                    'post_type' => $post_type,
                    'status' => 'success',
                    'message' => __('CPT exported successfully.', 'aiopms'),
                    'export_data' => $export_data
                );
                $success_count++;
                break;
                
            default:
                $results[] = array(
                    'post_type' => $post_type,
                    'status' => 'error',
                    'message' => __('Invalid bulk action.', 'aiopms')
                );
                $error_count++;
                break;
        }
    }
    
    // Clear cache after bulk operations
    wp_cache_delete('aiopms_dynamic_cpts', 'aiopms_cpt_cache');
    
    wp_send_json_success(array(
        'action' => $action,
        'total_processed' => count($cpt_ids),
        'success_count' => $success_count,
        'error_count' => $error_count,
        'results' => $results,
        'message' => sprintf(
            __('Bulk operation completed. %d successful, %d failed.', 'aiopms'),
            $success_count,
            $error_count
        )
    ));
}

/**
 * AJAX handler for updating CPT data
 * 
 * @since 3.0
 */
function aiopms_handle_cpt_update_ajax() {
    // Security checks
    check_ajax_referer('aiopms_cpt_ajax', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'aiopms'));
    }
    
    $post_type = sanitize_key($_POST['cpt_name'] ?? '');
    $label = sanitize_text_field($_POST['cpt_label'] ?? '');
    $description = sanitize_textarea_field($_POST['cpt_description'] ?? '');
    $menu_icon = sanitize_text_field($_POST['cpt_menu_icon'] ?? 'dashicons-admin-post');
    
    if (empty($post_type) || empty($label)) {
        wp_send_json_error(__('Invalid CPT data provided.', 'aiopms'));
    }
    
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', array());
    
    if (!isset($dynamic_cpts[$post_type])) {
        wp_send_json_error(__('Custom post type not found.', 'aiopms'));
    }
    
    // Update CPT data
    $dynamic_cpts[$post_type]['label'] = $label;
    $dynamic_cpts[$post_type]['description'] = $description;
    $dynamic_cpts[$post_type]['menu_icon'] = $menu_icon;
    
    // Save updated data
    update_option('aiopms_dynamic_cpts', $dynamic_cpts);
    
    // Update last modified timestamp
    update_option('aiopms_cpt_modified_' . $post_type, current_time('mysql'));
    
    // Clear cache
    wp_cache_delete('aiopms_dynamic_cpts', 'aiopms_cpt_cache');
    
    // Re-register the CPT with updated data
    $result = aiopms_register_dynamic_custom_post_type($dynamic_cpts[$post_type]);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    // Log activity
    aiopms_log_cpt_activity('update', $post_type, true);
    
    wp_send_json_success(array(
        'message' => __('CPT updated successfully.', 'aiopms'),
        'cpt_data' => $dynamic_cpts[$post_type]
    ));
}

/**
 * Get available CPT templates
 * 
 * @return array Array of CPT templates
 * @since 3.0
 */
function aiopms_get_cpt_templates() {
    return array(
        'portfolio' => array(
            'name' => 'Portfolio',
            'description' => 'Perfect for showcasing creative work, projects, and case studies.',
            'icon' => 'dashicons-portfolio',
            'features' => array(
                'Project showcase',
                'Client information',
                'Project categories',
                'Featured images',
                'Project links'
            ),
            'custom_fields' => array(
                array(
                    'name' => 'client_name',
                    'label' => 'Client Name',
                    'type' => 'text',
                    'description' => 'Name of the client or company',
                    'required' => false
                ),
                array(
                    'name' => 'project_url',
                    'label' => 'Project URL',
                    'type' => 'url',
                    'description' => 'Link to the live project',
                    'required' => false
                ),
                array(
                    'name' => 'project_date',
                    'label' => 'Project Date',
                    'type' => 'date',
                    'description' => 'When the project was completed',
                    'required' => false
                ),
                array(
                    'name' => 'technologies_used',
                    'label' => 'Technologies Used',
                    'type' => 'textarea',
                    'description' => 'Technologies, tools, and frameworks used',
                    'required' => false
                ),
                array(
                    'name' => 'project_category',
                    'label' => 'Project Category',
                    'type' => 'select',
                    'description' => 'Type of project',
                    'required' => true,
                    'options' => array('Web Design', 'Development', 'Branding', 'Marketing', 'Other')
                )
            ),
            'sample_entries' => array(
                array(
                    'title' => 'E-commerce Website Redesign',
                    'content' => 'Complete redesign of an e-commerce platform with improved user experience and mobile responsiveness.'
                ),
                array(
                    'title' => 'Brand Identity Package',
                    'content' => 'Comprehensive brand identity design including logo, business cards, and marketing materials.'
                )
            )
        ),
        'testimonials' => array(
            'name' => 'Testimonials',
            'description' => 'Collect and display customer testimonials and reviews.',
            'icon' => 'dashicons-format-quote',
            'features' => array(
                'Customer reviews',
                'Star ratings',
                'Customer photos',
                'Company information',
                'Featured testimonials'
            ),
            'custom_fields' => array(
                array(
                    'name' => 'customer_name',
                    'label' => 'Customer Name',
                    'type' => 'text',
                    'description' => 'Full name of the customer',
                    'required' => true
                ),
                array(
                    'name' => 'customer_company',
                    'label' => 'Company',
                    'type' => 'text',
                    'description' => 'Customer\'s company name',
                    'required' => false
                ),
                array(
                    'name' => 'customer_position',
                    'label' => 'Position',
                    'type' => 'text',
                    'description' => 'Customer\'s job title or position',
                    'required' => false
                ),
                array(
                    'name' => 'rating',
                    'label' => 'Rating',
                    'type' => 'select',
                    'description' => 'Star rating (1-5)',
                    'required' => true,
                    'options' => array('1', '2', '3', '4', '5')
                ),
                array(
                    'name' => 'customer_photo',
                    'label' => 'Customer Photo',
                    'type' => 'image',
                    'description' => 'Photo of the customer',
                    'required' => false
                )
            ),
            'sample_entries' => array(
                array(
                    'title' => 'Excellent Service',
                    'content' => 'The team delivered exactly what we needed on time and within budget. Highly recommended!'
                ),
                array(
                    'title' => 'Outstanding Results',
                    'content' => 'Our website traffic increased by 300% after implementing their SEO recommendations.'
                )
            )
        ),
        'team' => array(
            'name' => 'Team Members',
            'description' => 'Showcase your team members with detailed profiles and contact information.',
            'icon' => 'dashicons-groups',
            'features' => array(
                'Team profiles',
                'Social media links',
                'Skills and expertise',
                'Contact information',
                'Department organization'
            ),
            'custom_fields' => array(
                array(
                    'name' => 'position',
                    'label' => 'Position',
                    'type' => 'text',
                    'description' => 'Job title or position',
                    'required' => true
                ),
                array(
                    'name' => 'department',
                    'label' => 'Department',
                    'type' => 'select',
                    'description' => 'Department or team',
                    'required' => true,
                    'options' => array('Management', 'Development', 'Design', 'Marketing', 'Sales', 'Support')
                ),
                array(
                    'name' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'description' => 'Contact email address',
                    'required' => false
                ),
                array(
                    'name' => 'phone',
                    'label' => 'Phone',
                    'type' => 'text',
                    'description' => 'Contact phone number',
                    'required' => false
                ),
                array(
                    'name' => 'linkedin_url',
                    'label' => 'LinkedIn URL',
                    'type' => 'url',
                    'description' => 'LinkedIn profile URL',
                    'required' => false
                ),
                array(
                    'name' => 'skills',
                    'label' => 'Skills',
                    'type' => 'textarea',
                    'description' => 'Key skills and expertise',
                    'required' => false
                )
            ),
            'sample_entries' => array(
                array(
                    'title' => 'John Smith',
                    'content' => 'Experienced project manager with 10+ years in the industry.'
                ),
                array(
                    'title' => 'Sarah Johnson',
                    'content' => 'Creative director specializing in brand identity and user experience design.'
                )
            )
        ),
        'services' => array(
            'name' => 'Services',
            'description' => 'Display your services with pricing, features, and detailed descriptions.',
            'icon' => 'dashicons-admin-tools',
            'features' => array(
                'Service descriptions',
                'Pricing information',
                'Feature lists',
                'Service categories',
                'Call-to-action buttons'
            ),
            'custom_fields' => array(
                array(
                    'name' => 'service_price',
                    'label' => 'Price',
                    'type' => 'text',
                    'description' => 'Service price or pricing range',
                    'required' => false
                ),
                array(
                    'name' => 'service_category',
                    'label' => 'Category',
                    'type' => 'select',
                    'description' => 'Service category',
                    'required' => true,
                    'options' => array('Web Development', 'Design', 'Marketing', 'Consulting', 'Support')
                ),
                array(
                    'name' => 'features_list',
                    'label' => 'Features',
                    'type' => 'textarea',
                    'description' => 'List of service features (one per line)',
                    'required' => false
                ),
                array(
                    'name' => 'delivery_time',
                    'label' => 'Delivery Time',
                    'type' => 'text',
                    'description' => 'Expected delivery time',
                    'required' => false
                ),
                array(
                    'name' => 'cta_text',
                    'label' => 'Call-to-Action Text',
                    'type' => 'text',
                    'description' => 'Button text for the service',
                    'required' => false
                ),
                array(
                    'name' => 'cta_url',
                    'label' => 'Call-to-Action URL',
                    'type' => 'url',
                    'description' => 'Link for the CTA button',
                    'required' => false
                )
            ),
            'sample_entries' => array(
                array(
                    'title' => 'Website Development',
                    'content' => 'Custom website development with modern technologies and responsive design.'
                ),
                array(
                    'title' => 'SEO Optimization',
                    'content' => 'Complete SEO audit and optimization to improve your search engine rankings.'
                )
            )
        ),
        'faq' => array(
            'name' => 'FAQ',
            'description' => 'Create a comprehensive FAQ section with categorized questions and answers.',
            'icon' => 'dashicons-editor-help',
            'features' => array(
                'Question categories',
                'Search functionality',
                'Expandable answers',
                'FAQ ordering',
                'Related questions'
            ),
            'custom_fields' => array(
                array(
                    'name' => 'faq_category',
                    'label' => 'Category',
                    'type' => 'select',
                    'description' => 'FAQ category',
                    'required' => true,
                    'options' => array('General', 'Billing', 'Technical', 'Support', 'Features')
                ),
                array(
                    'name' => 'faq_order',
                    'label' => 'Display Order',
                    'type' => 'number',
                    'description' => 'Order for displaying FAQs (lower numbers first)',
                    'required' => false
                ),
                array(
                    'name' => 'related_faqs',
                    'label' => 'Related FAQs',
                    'type' => 'textarea',
                    'description' => 'IDs of related FAQ posts (comma-separated)',
                    'required' => false
                )
            ),
            'sample_entries' => array(
                array(
                    'title' => 'How do I get started?',
                    'content' => 'Getting started is easy! Simply sign up for an account and follow our onboarding process.'
                ),
                array(
                    'title' => 'What payment methods do you accept?',
                    'content' => 'We accept all major credit cards, PayPal, and bank transfers.'
                )
            )
        )
    );
}

/**
 * Create CPT from template
 * 
 * @since 3.0
 */
function aiopms_create_cpt_from_template() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'aiopms'));
    }
    
    $template_id = sanitize_key($_POST['template_id'] ?? '');
    
    if (empty($template_id)) {
        echo '<div class="notice notice-error"><p>' . __('Invalid template selected.', 'aiopms') . '</p></div>';
        return;
    }
    
    $templates = aiopms_get_cpt_templates();
    
    if (!isset($templates[$template_id])) {
        echo '<div class="notice notice-error"><p>' . __('Template not found.', 'aiopms') . '</p></div>';
        return;
    }
    
    $template = $templates[$template_id];
    
    // Create CPT data from template
    $cpt_data = array(
        'name' => $template_id,
        'label' => $template['name'],
        'description' => $template['description'],
        'menu_icon' => $template['icon'],
        'hierarchical' => false,
        'custom_fields' => $template['custom_fields'],
        'sample_entries' => $template['sample_entries'] ?? array()
    );
    
    // Register the CPT
    $result = aiopms_register_dynamic_custom_post_type($cpt_data);
    
    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>' . sprintf(__('Custom post type "%s" created successfully from template!', 'aiopms'), $template['name']) . '</p></div>';
        
        // Create sample entries if available
        if (!empty($template['sample_entries'])) {
            aiopms_create_sample_cpt_entries($cpt_data);
        }
    }
}

/**
 * Handle CPT export
 * 
 * @since 3.0
 */
function aiopms_handle_cpt_export() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'aiopms'));
    }
    
    $export_type = sanitize_key($_POST['export_type'] ?? 'all');
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', array());
    
    $export_data = array(
        'version' => '3.0',
        'export_date' => current_time('mysql'),
        'site_url' => get_site_url(),
        'cpts' => array()
    );
    
    if ($export_type === 'selected' && isset($_POST['export_cpts'])) {
        $selected_cpts = array_map('sanitize_key', (array) $_POST['export_cpts']);
        foreach ($selected_cpts as $post_type) {
            if (isset($dynamic_cpts[$post_type])) {
                $export_data['cpts'][$post_type] = $dynamic_cpts[$post_type];
            }
        }
    } else {
        $export_data['cpts'] = $dynamic_cpts;
    }
    
    if (empty($export_data['cpts'])) {
        echo '<div class="notice notice-error"><p>' . __('No CPTs selected for export.', 'aiopms') . '</p></div>';
        return;
    }
    
    // Generate filename
    $filename = 'aiopms-cpts-export-' . date('Y-m-d-H-i-s') . '.json';
    
    // Set headers for download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen(json_encode($export_data, JSON_PRETTY_PRINT)));
    
    // Output JSON
    echo json_encode($export_data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Handle CPT import
 * 
 * @since 3.0
 */
function aiopms_handle_cpt_import() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'aiopms'));
    }
    
    if (!isset($_FILES['cpt_import_file']) || $_FILES['cpt_import_file']['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="notice notice-error"><p>' . __('Please select a valid JSON file to import.', 'aiopms') . '</p></div>';
        return;
    }
    
    $file_content = file_get_contents($_FILES['cpt_import_file']['tmp_name']);
    $import_data = json_decode($file_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo '<div class="notice notice-error"><p>' . __('Invalid JSON file format.', 'aiopms') . '</p></div>';
        return;
    }
    
    if (!isset($import_data['cpts']) || !is_array($import_data['cpts'])) {
        echo '<div class="notice notice-error"><p>' . __('Invalid import file format.', 'aiopms') . '</p></div>';
        return;
    }
    
    $overwrite = isset($_POST['import_overwrite']);
    $activate = isset($_POST['import_activate']);
    
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', array());
    $imported_count = 0;
    $skipped_count = 0;
    $errors = array();
    
    foreach ($import_data['cpts'] as $post_type => $cpt_data) {
        if (isset($dynamic_cpts[$post_type]) && !$overwrite) {
            $skipped_count++;
            continue;
        }
        
        // Validate CPT data
        if (!aiopms_validate_cpt_data($cpt_data)) {
            $errors[] = sprintf(__('Invalid data for CPT "%s"', 'aiopms'), $post_type);
            continue;
        }
        
        // Sanitize and save CPT data
        $dynamic_cpts[$post_type] = aiopms_sanitize_cpt_data($cpt_data);
        $imported_count++;
        
        // Activate if requested
        if ($activate) {
            aiopms_register_dynamic_custom_post_type($dynamic_cpts[$post_type]);
        }
    }
    
    // Save updated CPTs
    update_option('aiopms_dynamic_cpts', $dynamic_cpts);
    
    // Clear cache
    wp_cache_delete('aiopms_dynamic_cpts', 'aiopms_cpt_cache');
    
    // Display results
    $message_parts = array();
    if ($imported_count > 0) {
        $message_parts[] = sprintf(__('%d CPTs imported successfully.', 'aiopms'), $imported_count);
    }
    if ($skipped_count > 0) {
        $message_parts[] = sprintf(__('%d CPTs skipped (already exist).', 'aiopms'), $skipped_count);
    }
    if (!empty($errors)) {
        $message_parts[] = sprintf(__('%d errors occurred.', 'aiopms'), count($errors));
    }
    
    if (!empty($message_parts)) {
        $class = !empty($errors) ? 'notice-warning' : 'notice-success';
        echo '<div class="notice ' . $class . '"><p>' . implode(' ', $message_parts) . '</p></div>';
        
        if (!empty($errors)) {
            echo '<div class="notice notice-error"><p><strong>Errors:</strong><br>' . implode('<br>', $errors) . '</p></div>';
        }
    }
}

/**
 * Display import/export history
 * 
 * @since 3.0
 */
function aiopms_display_import_export_history() {
    $history = get_option('aiopms_import_export_history', array());
    
    if (empty($history)) {
        echo '<p>' . __('No recent import/export operations.', 'aiopms') . '</p>';
        return;
    }
    
    // Sort by date (newest first)
    usort($history, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    echo '<div class="aiopms-history-items">';
    foreach (array_slice($history, 0, 10) as $item) {
        $icon = $item['type'] === 'export' ? 'dashicons-download' : 'dashicons-upload';
        $class = $item['type'] === 'export' ? 'export' : 'import';
        
        echo '<div class="aiopms-history-item ' . $class . '">';
        echo '<span class="dashicons ' . $icon . '" aria-hidden="true"></span>';
        echo '<div class="history-details">';
        echo '<strong>' . ucfirst($item['type']) . '</strong> - ';
        echo esc_html($item['description']);
        echo '<br><small>' . esc_html($item['date']) . '</small>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}
