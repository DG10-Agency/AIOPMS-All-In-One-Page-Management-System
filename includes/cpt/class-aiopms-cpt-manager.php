<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AIOPMS_CPT_Manager {

    /**
     * Registers all existing dynamic CPTs from the database.
     */
    public function register_existing_dynamic_cpts() {
        // Check cache first for performance
        $cached_cpts = wp_cache_get('aiopms_dynamic_cpts', 'aiopms_cpt_cache');
        
        if (false === $cached_cpts) {
            $dynamic_cpts = get_option('aiopms_dynamic_cpts', []);
            wp_cache_set('aiopms_dynamic_cpts', $dynamic_cpts, 'aiopms_cpt_cache', HOUR_IN_SECONDS);
            $cached_cpts = $dynamic_cpts;
        }
        
        if (!empty($cached_cpts) && is_array($cached_cpts)) {
            foreach ($cached_cpts as $post_type => $cpt_data) {
                if ($this->validate_cpt_data($cpt_data)) {
                    $this->register_dynamic_custom_post_type($cpt_data);
                }
            }
        }
    }

    /**
     * Validates the CPT data structure and values.
     *
     * @param array $cpt_data The CPT data to validate.
     * @return bool True if valid, false otherwise.
     */
    public function validate_cpt_data($cpt_data) {
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

    /**
     * Registers a single dynamic CPT.
     *
     * @param array $cpt_data The CPT data.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function register_dynamic_custom_post_type($cpt_data) {
        // Validate input data
        if (!$this->validate_cpt_data($cpt_data)) {
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
            $field_result = $this->register_custom_fields($post_type, $cpt_data['custom_fields']);
            if (is_wp_error($field_result)) {
                return $field_result;
            }
        }
        
        // Store CPT data for persistence with proper sanitization
        $existing_cpts = get_option('aiopms_dynamic_cpts', array());
        $existing_cpts[$post_type] = $this->sanitize_cpt_data($cpt_data);
        update_option('aiopms_dynamic_cpts', $existing_cpts);
        
        // Clear cache
        wp_cache_delete('aiopms_dynamic_cpts', 'aiopms_cpt_cache');
        
        // Generate sample content if enabled
        $settings = get_option('aiopms_cpt_settings', array());
        if (!empty($settings['auto_generate_sample_content']) && !empty($cpt_data['sample_entries'])) {
            $this->create_sample_cpt_entries($cpt_data);
        }
        
        // Trigger action for other plugins/themes
        do_action('aiopms_cpt_registered', $post_type, $cpt_data);
        
        // Log successful registration
        $this->log_cpt_activity('register', $post_type, true);
        
        return true;
    }

    /**
     * Registers custom fields for a given post type.
     *
     * @param string $post_type The post type to register fields for.
     * @param array $fields An array of field configurations.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function register_custom_fields($post_type, $fields) {
        if (empty($fields) || !is_array($fields)) {
            return new WP_Error('invalid_fields', __('Invalid fields data provided', 'aiopms'));
        }
        
        foreach ($fields as $field) {
            if (!$this->validate_field_data($field)) {
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
                    return update_post_meta($post->ID, $field_name, $this->sanitize_field_value($value, $field_config['type']));
                },
                'schema' => $this->get_field_schema($field_config['type']),
            ));
            
            // Store field configuration
            $existing_fields = get_option('aiopms_custom_fields', array());
            $existing_fields[$post_type][$field_name] = $field_config;
            update_option('aiopms_custom_fields', $existing_fields);
        }
        
        return true;
    }

    public function sanitize_cpt_data($cpt_data) {
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
                if ($this->validate_field_data($field)) {
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

    public function create_sample_cpt_entries($cpt_data) {
        aiopms_create_sample_cpt_entries($cpt_data);
    }

    public function log_cpt_activity($action, $post_type, $success, $error_message = '') {
        $log_entry = array(
            'timestamp' => current_time('timestamp'),
            'action' => $action,
            'post_type' => $post_type,
            'success' => $success,
            'error_message' => $error_message,
        );
        
        $logs = get_option('aiopms_cpt_logs', array());
        $logs[] = $log_entry;
        
        // Keep only last 100 log entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('aiopms_cpt_logs', $logs);
    }

    public function validate_field_data($field) {
        if (!is_array($field)) return false;
        if (empty($field['name']) || empty($field['label']) || empty($field['type'])) return false;
        
        $allowed_types = array('text', 'textarea', 'number', 'date', 'datetime', 'url', 'email', 'image', 'select', 'radio', 'checkbox', 'color', 'wysiwyg');
        if (!in_array($field['type'], $allowed_types)) return false;
        
        return true;
    }

    public function sanitize_field_value($value, $field_type) {
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
                return $value ? 1 : 0;
            default:
                return sanitize_text_field($value);
        }
    }

    public function get_field_schema($field_type) {
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

    /**
     * Processes the creation of a new CPT from form data.
     *
     * @param array $form_data The form data.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function process_cpt_creation($form_data) {
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
        
        return $this->register_dynamic_custom_post_type($cpt_data);
    }

    /**
     * Deletes a custom post type.
     *
     * @param string $post_type The post type to delete.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function delete_custom_post_type($post_type) {
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
        $this->log_cpt_activity('delete', $post_type, true);
        
        return true;
    }

    /**
     * Retrieves all dynamic CPTs.
     *
     * @return array An array of dynamic CPTs.
     */
    public function get_dynamic_cpts() {
        return get_option('aiopms_dynamic_cpts', []);
    }

    /**
     * Checks if a post type is a dynamic CPT.
     *
     * @param string $post_type The post type to check.
     * @return bool True if it is a dynamic CPT, false otherwise.
     */
    public function is_dynamic_cpt($post_type) {
        $dynamic_cpts = get_option('aiopms_dynamic_cpts', []);
        return isset($dynamic_cpts[$post_type]);
    }

    /**
     * Retrieves information about a specific CPT.
     *
     * @param string $post_type The post type to get information for.
     * @return array|null The CPT data or null if not found.
     */
    public function get_cpt_info($post_type) {
        $dynamic_cpts = get_option('aiopms_dynamic_cpts', []);
        return isset($dynamic_cpts[$post_type]) ? $dynamic_cpts[$post_type] : null;
    }

    public function get_cpt_templates() {
        return array(
            'portfolio' => array(
                'name' => 'Portfolio',
                'description' => 'A template for showcasing projects.',
                'icon' => 'dashicons-portfolio',
                'features' => array('Custom fields for project details', 'Sample entries included'),
                'custom_fields' => array(
                    array('name' => 'project_url', 'label' => 'Project URL', 'type' => 'url', 'required' => true),
                    array('name' => 'client_name', 'label' => 'Client Name', 'type' => 'text'),
                ),
                'sample_entries' => array(
                    array('title' => 'Sample Project 1', 'content' => 'This is a sample project.'),
                ),
            ),
            'events' => array(
                'name' => 'Events',
                'description' => 'A template for managing events.',
                'icon' => 'dashicons-calendar-alt',
                'features' => array('Fields for date, time, and location', 'Sample entries included'),
                'custom_fields' => array(
                    array('name' => 'event_date', 'label' => 'Event Date', 'type' => 'date', 'required' => true),
                    array('name' => 'event_location', 'label' => 'Event Location', 'type' => 'text', 'required' => true),
                ),
                'sample_entries' => array(
                    array('title' => 'Sample Event', 'content' => 'This is a sample event.'),
                ),
            ),
            'team_members' => array(
                'name' => 'Team Members',
                'description' => 'A template for displaying team members.',
                'icon' => 'dashicons-groups',
                'features' => array('Fields for position and social links', 'Sample entries included'),
                'custom_fields' => array(
                    array('name' => 'position', 'label' => 'Position', 'type' => 'text', 'required' => true),
                    array('name' => 'linkedin_url', 'label' => 'LinkedIn URL', 'type' => 'url'),
                ),
                'sample_entries' => array(
                    array('title' => 'John Doe', 'content' => 'John is a sample team member.'),
                ),
            ),
            'testimonials' => array(
                'name' => 'Testimonials',
                'description' => 'A template for managing customer testimonials.',
                'icon' => 'dashicons-testimonial',
                'features' => array('Fields for author and rating', 'Sample entries included'),
                'custom_fields' => array(
                    array('name' => 'author_name', 'label' => 'Author Name', 'type' => 'text', 'required' => true),
                    array('name' => 'rating', 'label' => 'Rating', 'type' => 'number'),
                ),
                'sample_entries' => array(
                    array('title' => 'Great Plugin!', 'content' => 'This is a sample testimonial.'),
                ),
            ),
        );
    }
}
