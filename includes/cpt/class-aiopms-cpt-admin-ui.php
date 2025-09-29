<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AIOPMS_CPT_Admin_UI {

    private $cpt_manager;

    public function __construct(AIOPMS_CPT_Manager $cpt_manager) {
        $this->cpt_manager = $cpt_manager;
    }

    public function cpt_management_page() {
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
                                    $this->cpt_list_tab();
                                    break;
                                case 'create':
                                    $this->cpt_create_tab();
                                    break;
                                case 'templates':
                                    $this->cpt_templates_tab();
                                    break;
                                case 'bulk':
                                    $this->cpt_bulk_operations_tab();
                                    break;
                                case 'import-export':
                                    $this->cpt_import_export_tab();
                                    break;
                                case 'settings':
                                    $this->cpt_settings_tab();
                                    break;
                                default:
                                    $this->cpt_list_tab();
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

    public function cpt_list_tab() {
        $dynamic_cpts = $this->cpt_manager->get_dynamic_cpts();
        ?>
        <div class="aiopms-cpt-list" id="cpt-list-container">
            <div class="aiopms-cpt-list-header">
                <div class="aiopms-search-filter">
                    <label for="cpt-search" class="screen-reader-text"><?php esc_html_e('Search CPTs', 'aiopms'); ?></label>
                    <input type="search" id="cpt-search" placeholder="<?php esc_attr_e('Search custom post types...', 'aiopms'); ?>" 
                           class="aiopms-form-input aiopms-search-input" aria-label="<?php esc_attr_e('Search custom post types', 'aiopms'); ?>">
                    
                    <select id="cpt-filter-status" class="aiopms-form-select" aria-label="<?php esc_attr_e('Filter by status', 'aiopms'); ?>">
                        <option value=""><?php esc_html_e('All Statuses', 'aiopms'); ?></option>
                        <option value="active"><?php esc_html_e('Active', 'aiopms'); ?></option>
                        <option value="inactive"><?php esc_html_e('Inactive', 'aiopms'); ?></option>
                    </select>
                    
                    <button type="button" class="dg10-btn dg10-btn-primary" id="refresh-cpt-list" 
                            aria-label="<?php esc_attr_e('Refresh CPT list', 'aiopms'); ?>">
                        <span class="dashicons dashicons-update" aria-hidden="true"></span>
                        <?php esc_html_e('Refresh', 'aiopms'); ?>
                    </button>
                </div>
                
                <div class="aiopms-bulk-actions" role="group" aria-label="<?php esc_attr_e('Bulk actions for custom post types', 'aiopms'); ?>">
                    <label for="bulk-action-select" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'aiopms'); ?></label>
                    <select id="bulk-action-select" class="aiopms-form-select" aria-label="<?php esc_attr_e('Bulk actions', 'aiopms'); ?>" aria-describedby="bulk-action-help">
                        <option value=""><?php esc_html_e('Bulk Actions', 'aiopms'); ?></option>
                        <option value="activate"><?php esc_html_e('Activate', 'aiopms'); ?></option>
                        <option value="deactivate"><?php esc_html_e('Deactivate', 'aiopms'); ?></option>
                        <option value="export"><?php esc_html_e('Export', 'aiopms'); ?></option>
                        <option value="delete"><?php esc_html_e('Delete', 'aiopms'); ?></option>
                    </select>
                    <button type="button" class="dg10-btn dg10-btn-primary" id="apply-bulk-action" disabled aria-describedby="bulk-action-status">
                        <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                        <?php esc_html_e('Apply', 'aiopms'); ?>
                    </button>
                    <div id="bulk-action-help" class="screen-reader-text"><?php esc_html_e('Select an action and check the CPTs you want to modify, then click Apply.', 'aiopms'); ?></div>
                    <div id="bulk-action-status" class="screen-reader-text" aria-live="polite"><?php esc_html_e('No action selected', 'aiopms'); ?></div>
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
                        <a href="<?php echo esc_url(add_query_arg('tab', 'create')); ?>" class="dg10-btn dg10-btn-primary">
                            <span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
                            <?php esc_html_e('Create New CPT', 'aiopms'); ?>
                        </a>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'templates')); ?>" class="dg10-btn dg10-btn-secondary">
                            <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                            <?php esc_html_e('Use Template', 'aiopms'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="aiopms-cpt-grid" id="cpt-grid">
                        <?php foreach ($dynamic_cpts as $post_type => $cpt_data): ?>
                        <?php $this->render_cpt_card($post_type, $cpt_data); ?>
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

    public function cpt_create_tab() {
        ?>
        <div class="aiopms-cpt-create">
            <p>Manually create a custom post type with custom fields.</p>
            
            <form method="post" action="" id="aiopms-create-cpt-form">
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
                                <td><button type="button" class="dg10-btn dg10-btn-error dg10-btn-sm remove-field">Remove</button></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <p>
                    <button type="button" id="add-custom-field" class="dg10-btn dg10-btn-primary">Add Custom Field</button>
                </p>
                
                <?php submit_button('Create Custom Post Type', 'primary', 'create_manual_cpt'); ?>
            </form>
        </div>
        <?php
    }

    public function cpt_templates_tab() {
        $templates = $this->cpt_manager->get_cpt_templates();
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
                            <button type="button" class="dg10-btn dg10-btn-secondary aiopms-preview-template" 
                                    data-template="<?php echo esc_attr($template_id); ?>">
                                <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                                <?php esc_html_e('Preview', 'aiopms'); ?>
                            </button>
                            
                            <form method="post" action="" class="aiopms-template-form" style="display: inline;">
                                <?php wp_nonce_field('aiopms_create_from_template'); ?>
                                <input type="hidden" name="template_id" value="<?php echo esc_attr($template_id); ?>">
                                <button type="submit" name="create_from_template" class="dg10-btn dg10-btn-primary">
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
                        <button class="aiopms-modal-close dg10-btn dg10-btn-secondary">&times;</button>
                    </div>
                    <div class="aiopms-modal-body" id="preview-template-content">
                        <!-- Template preview content will be loaded here -->
                    </div>
                    <div class="aiopms-modal-footer">
                        <button type="button" class="dg10-btn dg10-btn-secondary aiopms-modal-close">
                            <?php esc_html_e('Close', 'aiopms'); ?>
                        </button>
                        <button type="button" class="dg10-btn dg10-btn-primary" id="create-from-preview">
                            <span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
                            <?php esc_html_e('Create CPT', 'aiopms'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function cpt_bulk_operations_tab() {
        $dynamic_cpts = $this->cpt_manager->get_dynamic_cpts();
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
                    <a href="<?php echo esc_url(add_query_arg('tab', 'create')); ?>" class="dg10-btn dg10-btn-primary">
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
                        
                        <div class="aiopms-bulk-actions-panel" role="group" aria-label="<?php esc_attr_e('Bulk operations for selected custom post types', 'aiopms'); ?>">
                            <label for="bulk-action-selector" class="screen-reader-text"><?php esc_html_e('Select bulk operation', 'aiopms'); ?></label>
                            <select id="bulk-action-selector" class="aiopms-bulk-selector" aria-label="<?php esc_attr_e('Bulk operations', 'aiopms'); ?>" aria-describedby="bulk-operation-help">
                                <option value=""><?php esc_html_e('Choose Bulk Action...', 'aiopms'); ?></option>
                                <option value="activate"><?php esc_html_e('Activate Selected CPTs', 'aiopms'); ?></option>
                                <option value="deactivate"><?php esc_html_e('Deactivate Selected CPTs', 'aiopms'); ?></option>
                                <option value="export"><?php esc_html_e('Export Selected CPTs', 'aiopms'); ?></option>
                                <option value="duplicate"><?php esc_html_e('Duplicate Selected CPTs', 'aiopms'); ?></option>
                                <option value="delete"><?php esc_html_e('Delete Selected CPTs', 'aiopms'); ?></option>
                            </select>
                            
                            <button type="button" id="apply-bulk-action-btn" class="dg10-btn dg10-btn-primary" disabled aria-describedby="bulk-operation-status">
                                <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                                <?php esc_html_e('Apply Action', 'aiopms'); ?>
                            </button>
                            <div id="bulk-operation-help" class="screen-reader-text"><?php esc_html_e('Select an operation and check the CPTs you want to modify, then click Apply Action.', 'aiopms'); ?></div>
                            <div id="bulk-operation-status" class="screen-reader-text" aria-live="polite"><?php esc_html_e('No operation selected', 'aiopms'); ?></div>
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

    public function cpt_import_export_tab() {
        $dynamic_cpts = $this->cpt_manager->get_dynamic_cpts();
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
                                <button type="submit" name="export_cpts" class="dg10-btn dg10-btn-primary">
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
                            <button type="submit" name="import_cpts" class="dg10-btn dg10-btn-primary">
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
                    <?php $this->display_import_export_history(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function display_import_export_history() {
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

    public function cpt_settings_tab() {
        $settings = get_option('aiopms_cpt_settings', array(
            'auto_schema_generation' => true,
            'include_in_menus' => true,
            'include_in_hierarchy' => true,
            'auto_generate_sample_content' => true
        ));
        
        ?>
        <div class="aiopms-cpt-settings">
            <p>Configure how custom post types integrate with other AIOPMS features.</p>
            
            <form method="post" action="" id="aiopms-cpt-settings-form">
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

    public function render_cpt_card($post_type, $cpt_data) {
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
                       class="dg10-btn dg10-btn-secondary dg10-btn-sm">
                        <?php esc_html_e('View Posts', 'aiopms'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . $post_type)); ?>" 
                       class="dg10-btn dg10-btn-primary dg10-btn-sm">
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
}
