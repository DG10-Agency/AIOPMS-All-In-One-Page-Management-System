<?php
/**
 * Plugin Name: AIOPMS - All In One Page Management System
 * Plugin URI: https://wordpress.org/plugins/aiopms-all-in-one-page-management-system/
 * Description: A comprehensive page management system for WordPress with bulk creation, AI generation, hierarchy management, schema markup, and menu generation.
 * Version: 3.0
 * Requires at least: 5.6
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Author: DG10 Agency
 * Author URI: https://www.dg10.agency
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aiopms
 * Domain Path: /languages
 * Network: false
 * 
 * @package AIOPMS
 * @version 3.0
 * @author DG10 Agency
 * @license GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('AIOPMS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AIOPMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AIOPMS_GITHUB_URL', 'https://github.com/DG10-Agency/AIOPMS-All-In-One-Page-Management-System');

/**
 * Plugin activation hook
 * Sets up default options and initializes plugin data
 */
function aiopms_activate() {
    // Set default plugin options
    $default_options = array(
        'aiopms_version' => '3.0',
        'aiopms_ai_provider' => 'openai',
        'aiopms_openai_api_key' => '',
        'aiopms_gemini_api_key' => '',
        'aiopms_deepseek_api_key' => '',
        'aiopms_brand_color' => '#4A90E2',
        'aiopms_default_status' => 'draft',
        'aiopms_auto_schema_generation' => true,
        'aiopms_enable_image_generation' => true,
        'aiopms_image_quality' => 'standard',
        'aiopms_image_size' => '1024x1024',
        'aiopms_max_tokens' => 400,
        'aiopms_temperature' => 0.5,
        'aiopms_seo_intensity' => 'high',
        'aiopms_api_timeout' => 30,
        'aiopms_batch_size' => 10,
        'aiopms_activation_date' => current_time('mysql'),
        'aiopms_first_activation' => true
    );
    
    // Set options only if they don't exist
    foreach ($default_options as $option_name => $default_value) {
        if (get_option($option_name) === false) {
            add_option($option_name, $default_value);
        }
    }
    
    // Create custom database tables if needed
    aiopms_create_database_tables();
    
    // Set activation flag
    update_option('aiopms_plugin_activated', true);
    
    // Clear any cached data
    wp_cache_flush();
    
    // Log activation
    error_log('AIOPMS Plugin Activated - Version 3.0');
}

/**
 * Plugin deactivation hook
 * Performs cleanup tasks when plugin is deactivated
 */
function aiopms_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('aiopms_cleanup_temporary_data');
    
    // Clear any cached data
    wp_cache_flush();
    
    // Set deactivation flag
    update_option('aiopms_plugin_deactivated', true);
    update_option('aiopms_deactivation_date', current_time('mysql'));
    
    // Log deactivation
    error_log('AIOPMS Plugin Deactivated');
}

/**
 * Plugin uninstall hook
 * Removes all plugin data when plugin is deleted
 */
function aiopms_uninstall() {
    // Only run if user has proper permissions
    if (!current_user_can('delete_plugins')) {
        return;
    }
    
    // Remove all plugin options
    $options_to_remove = array(
        'aiopms_version',
        'aiopms_ai_provider',
        'aiopms_openai_api_key',
        'aiopms_gemini_api_key',
        'aiopms_deepseek_api_key',
        'aiopms_brand_color',
        'aiopms_default_status',
        'aiopms_auto_schema_generation',
        'aiopms_enable_image_generation',
        'aiopms_image_quality',
        'aiopms_image_size',
        'aiopms_max_tokens',
        'aiopms_temperature',
        'aiopms_seo_intensity',
        'aiopms_api_timeout',
        'aiopms_batch_size',
        'aiopms_activation_date',
        'aiopms_first_activation',
        'aiopms_plugin_activated',
        'aiopms_plugin_deactivated',
        'aiopms_deactivation_date'
    );
    
    foreach ($options_to_remove as $option) {
        delete_option($option);
    }
    
    // Remove custom database tables
    aiopms_drop_database_tables();
    
    // Clear any cached data
    wp_cache_flush();
    
    // Log uninstall
    error_log('AIOPMS Plugin Uninstalled - All data removed');
}

/**
 * Create custom database tables for plugin
 */
function aiopms_create_database_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Table for storing AI generation logs
    $table_name = $wpdb->prefix . 'aiopms_generation_logs';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        page_id bigint(20) NOT NULL,
        generation_type varchar(50) NOT NULL,
        ai_provider varchar(50) NOT NULL,
        tokens_used int(11) DEFAULT 0,
        generation_time datetime DEFAULT CURRENT_TIMESTAMP,
        success tinyint(1) DEFAULT 1,
        error_message text,
        PRIMARY KEY (id),
        KEY page_id (page_id),
        KEY generation_type (generation_type),
        KEY generation_time (generation_time)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Table for storing schema generation data
    $table_name = $wpdb->prefix . 'aiopms_schema_data';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        schema_type varchar(100) NOT NULL,
        schema_data longtext NOT NULL,
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY post_id (post_id),
        KEY schema_type (schema_type)
    ) $charset_collate;";
    
    dbDelta($sql);
}

/**
 * Drop custom database tables
 */
function aiopms_drop_database_tables() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'aiopms_generation_logs',
        $wpdb->prefix . 'aiopms_schema_data'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

// Register activation, deactivation, and uninstall hooks
register_activation_hook(__FILE__, 'aiopms_activate');
register_deactivation_hook(__FILE__, 'aiopms_deactivate');
register_uninstall_hook(__FILE__, 'aiopms_uninstall');

/**
 * Load plugin textdomain for internationalization
 */
function aiopms_load_textdomain() {
    load_plugin_textdomain(
        'aiopms',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
add_action('plugins_loaded', 'aiopms_load_textdomain');

// Include necessary files
require_once AIOPMS_PLUGIN_PATH . 'includes/admin-menu.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/page-creation.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/csv-handler.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/settings-page.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/ai-generator.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/custom-post-type-manager.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/hierarchy-manager.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/menu-generator.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/schema-generator.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/keyword-analyzer.php';

// Enqueue scripts and styles
function aiopms_enqueue_assets() {
    // Enqueue brand CSS first for proper cascade
    wp_enqueue_style('aiopms-dg10-brand', AIOPMS_PLUGIN_URL . 'assets/css/dg10-brand.css', array(), '3.0');
    wp_enqueue_style('aiopms-styles', AIOPMS_PLUGIN_URL . 'assets/css/styles.css', array('aiopms-dg10-brand'), '3.0');
    wp_enqueue_style('aiopms-admin-menu', AIOPMS_PLUGIN_URL . 'assets/css/admin-menu.css', array('aiopms-dg10-brand'), '3.0');
    wp_enqueue_style('aiopms-schema-column', AIOPMS_PLUGIN_URL . 'assets/css/schema-column.css', array('aiopms-dg10-brand'), '3.0');
    wp_enqueue_style('aiopms-hierarchy', AIOPMS_PLUGIN_URL . 'assets/css/hierarchy.css', array('aiopms-dg10-brand'), '3.0');
    
    // Enqueue CPT management assets on CPT management pages
    $current_screen = get_current_screen();
    if ($current_screen && strpos($current_screen->id, 'aiopms-cpt-management') !== false) {
        wp_enqueue_style('aiopms-cpt-management', AIOPMS_PLUGIN_URL . 'assets/css/cpt-management.css', array('aiopms-dg10-brand'), '3.0');
        wp_enqueue_script('aiopms-cpt-management', AIOPMS_PLUGIN_URL . 'assets/js/cpt-management.js', array('jquery'), '3.0', true);
        
        // Localize CPT management script
        wp_localize_script('aiopms-cpt-management', 'aiopms_cpt_data', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiopms_cpt_ajax'),
            'plugin_url' => AIOPMS_PLUGIN_URL,
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this custom post type? This action cannot be undone.', 'aiopms'),
                'confirm_bulk_delete' => __('Are you sure you want to delete the selected custom post types? This action cannot be undone.', 'aiopms'),
                'loading' => __('Loading...', 'aiopms'),
                'success' => __('Success!', 'aiopms'),
                'error' => __('An error occurred.', 'aiopms'),
                'network_error' => __('Network error occurred. Please try again.', 'aiopms')
            )
        ));
    }
    
    wp_enqueue_script('aiopms-scripts', AIOPMS_PLUGIN_URL . 'assets/js/scripts.js', array('jquery'), '3.0', true);
    
    // Localize script with plugin URL
    wp_localize_script('aiopms-scripts', 'aiopms_plugin_data', array(
        'plugin_url' => AIOPMS_PLUGIN_URL
    ));
}
add_action('admin_enqueue_scripts', 'aiopms_enqueue_assets');
