<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AIOPMS_CPT_Hooks {

    private $cpt_manager;
    private $admin_ui;
    private $ajax;
    private $rest;

    public function __construct() {
        $this->cpt_manager = new AIOPMS_CPT_Manager();
        $this->admin_ui = new AIOPMS_CPT_Admin_UI($this->cpt_manager);
        $this->ajax = new AIOPMS_CPT_AJAX($this->cpt_manager);
        $this->rest = new AIOPMS_CPT_REST($this->cpt_manager);
    }

    public function init() {
        add_action('init', array($this->cpt_manager, 'register_existing_dynamic_cpts'), 20);
        add_action('admin_menu', array($this, 'add_cpt_management_menu'));
        add_action('rest_api_init', array($this->rest, 'register_rest_endpoints'));
        
        // AJAX handlers
        add_action('wp_ajax_aiopms_create_cpt_ajax', array($this->ajax, 'handle_cpt_creation_ajax'));
        add_action('wp_ajax_aiopms_delete_cpt_ajax', array($this->ajax, 'handle_cpt_deletion_ajax'));
        add_action('wp_ajax_aiopms_get_cpt_data', array($this->ajax, 'get_cpt_data_ajax'));
        add_action('wp_ajax_aiopms_bulk_cpt_operations', array($this->ajax, 'handle_bulk_cpt_operations_ajax'));
        add_action('wp_ajax_aiopms_update_cpt_ajax', array($this->ajax, 'handle_cpt_update_ajax'));
    }

    public function add_cpt_management_menu() {
        add_menu_page(
            __('CPT Management', 'aiopms'),
            __('CPT Management', 'aiopms'),
            'manage_options',
            'aiopms-cpt-management',
            array($this->admin_ui, 'cpt_management_page'),
            'dashicons-admin-post',
            30
        );
    }
}
