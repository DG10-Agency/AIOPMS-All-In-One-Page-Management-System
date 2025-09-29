<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AIOPMS_CPT_AJAX {

    private $cpt_manager;

    public function __construct(AIOPMS_CPT_Manager $cpt_manager) {
        $this->cpt_manager = $cpt_manager;
    }

    public function handle_cpt_creation_ajax() {
        check_ajax_referer('aiopms_cpt_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to create custom post types.', 'aiopms'));
        }
        
        $result = $this->cpt_manager->process_cpt_creation($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'message' => __('Custom post type created successfully!', 'aiopms'),
                'cpt_data' => $_POST
            ));
        }
    }

    public function handle_cpt_deletion_ajax() {
        check_ajax_referer('aiopms_cpt_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to delete custom post types.', 'aiopms'));
        }
        
        $post_type = sanitize_key($_POST['post_type'] ?? '');
        
        if (empty($post_type)) {
            wp_send_json_error(__('Invalid post type specified.', 'aiopms'));
        }
        
        $result = $this->cpt_manager->delete_custom_post_type($post_type);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'message' => __('Custom post type deleted successfully!', 'aiopms')
            ));
        }
    }

    public function get_cpt_data_ajax() {
        check_ajax_referer('aiopms_cpt_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'aiopms'));
        }
        
        $post_type = sanitize_key($_POST['post_type'] ?? '');
        
        if (empty($post_type)) {
            wp_send_json_error(__('Invalid post type specified.', 'aiopms'));
        }
        
        $cpt_data = $this->cpt_manager->get_cpt_info($post_type);
        
        if (!$cpt_data) {
            wp_send_json_error(__('Custom post type not found.', 'aiopms'));
        }
        
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

    public function handle_bulk_cpt_operations_ajax() {
        check_ajax_referer('aiopms_cpt_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'aiopms'));
        }
        
        $action = sanitize_key($_POST['bulk_action'] ?? '');
        $cpt_ids = array_map('sanitize_key', (array) ($_POST['cpt_ids'] ?? array()));
        
        if (empty($action) || empty($cpt_ids)) {
            wp_send_json_error(__('Invalid bulk action or no CPTs selected.', 'aiopms'));
        }
        
        $results = array();
        $success_count = 0;
        $error_count = 0;
        
        foreach ($cpt_ids as $post_type) {
            $cpt_data = $this->cpt_manager->get_cpt_info($post_type);
            if (!$cpt_data) {
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
                    $result = $this->cpt_manager->register_dynamic_custom_post_type($cpt_data);
                    if (is_wp_error($result)) {
                        $results[] = array('post_type' => $post_type, 'status' => 'error', 'message' => $result->get_error_message());
                        $error_count++;
                    } else {
                        $results[] = array('post_type' => $post_type, 'status' => 'success', 'message' => __('CPT activated successfully.', 'aiopms'));
                        $success_count++;
                    }
                    break;
                case 'deactivate':
                    unregister_post_type($post_type);
                    $results[] = array('post_type' => $post_type, 'status' => 'success', 'message' => __('CPT deactivated successfully.', 'aiopms'));
                    $success_count++;
                    break;
                case 'delete':
                    $delete_result = $this->cpt_manager->delete_custom_post_type($post_type);
                    if (is_wp_error($delete_result)) {
                        $results[] = array('post_type' => $post_type, 'status' => 'error', 'message' => $delete_result->get_error_message());
                        $error_count++;
                    } else {
                        $results[] = array('post_type' => $post_type, 'status' => 'success', 'message' => __('CPT deleted successfully.', 'aiopms'));
                        $success_count++;
                    }
                    break;
                case 'export':
                    $export_data = array('post_type' => $post_type, 'cpt_data' => $cpt_data, 'export_date' => current_time('mysql'), 'version' => '3.0');
                    $results[] = array('post_type' => $post_type, 'status' => 'success', 'message' => __('CPT exported successfully.', 'aiopms'), 'export_data' => $export_data);
                    $success_count++;
                    break;
                default:
                    $results[] = array('post_type' => $post_type, 'status' => 'error', 'message' => __('Invalid bulk action.', 'aiopms'));
                    $error_count++;
                    break;
            }
        }
        
        wp_cache_delete('aiopms_dynamic_cpts', 'aiopms_cpt_cache');
        
        wp_send_json_success(array(
            'action' => $action,
            'total_processed' => count($cpt_ids),
            'success_count' => $success_count,
            'error_count' => $error_count,
            'results' => $results,
            'message' => sprintf(__('Bulk operation completed. %d successful, %d failed.', 'aiopms'), $success_count, $error_count)
        ));
    }

    public function handle_cpt_update_ajax() {
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
        
        $dynamic_cpts = $this->cpt_manager->get_dynamic_cpts();
        
        if (!isset($dynamic_cpts[$post_type])) {
            wp_send_json_error(__('Custom post type not found.', 'aiopms'));
        }
        
        $dynamic_cpts[$post_type]['label'] = $label;
        $dynamic_cpts[$post_type]['description'] = $description;
        $dynamic_cpts[$post_type]['menu_icon'] = $menu_icon;
        
        update_option('aiopms_dynamic_cpts', $dynamic_cpts);
        update_option('aiopms_cpt_modified_' . $post_type, current_time('mysql'));
        wp_cache_delete('aiopms_dynamic_cpts', 'aiopms_cpt_cache');
        
        $result = $this->cpt_manager->register_dynamic_custom_post_type($dynamic_cpts[$post_type]);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        $this->cpt_manager->log_cpt_activity('update', $post_type, true);
        
        wp_send_json_success(array(
            'message' => __('CPT updated successfully.', 'aiopms'),
            'cpt_data' => $dynamic_cpts[$post_type]
        ));
    }
}
