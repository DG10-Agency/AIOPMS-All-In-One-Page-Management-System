<?php
/**
 * Plugin Name: ArtitechCore WP
 * Plugin URI: https://github.com/DG10-Agency/ArtitechCore-WP
 * Description: The core engine for Artitech WP ecosystem, providing AI-powered page generation, hierarchy management, and structural organization.
 * Version: 1.0
 * Requires at least: 5.6
 * Tested up to: 6.9.4
 * Requires PHP: 7.4
 * Author: DG10 Agency
 * Author URI: https://www.dg10.agency
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: artitechcore
 * Domain Path: /languages
 * Network: false
 * 
 * @package ArtitechCore
 * @version 1.0
 * @author DG10 Agency
 * @license GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('ArtitechCore_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ArtitechCore_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ArtitechCore_GITHUB_URL', 'https://github.com/DG10-Agency/ArtitechCore-WP');

/**
 * Plugin activation hook
 * Sets up default options and initializes plugin data
 */
function artitechcore_activate() {
    // Set default plugin options
    $default_options = array(
        'artitechcore_version' => '1.0',
        'artitechcore_ai_provider' => 'openai',
        'artitechcore_openai_api_key' => '',
        'artitechcore_gemini_api_key' => '',
        'artitechcore_deepseek_api_key' => '',
        'artitechcore_brand_color' => '#4A90E2',
        'artitechcore_default_status' => 'draft',
        'artitechcore_auto_schema_generation' => true,
        'artitechcore_enable_image_generation' => true,
        'artitechcore_image_quality' => 'standard',
        'artitechcore_image_size' => '1024x1024',
        'artitechcore_max_tokens' => 400,
        'artitechcore_temperature' => 0.5,
        'artitechcore_seo_intensity' => 'high',
        'artitechcore_api_timeout' => 30,
        'artitechcore_batch_size' => 10,
        'artitechcore_activation_date' => current_time('mysql'),
        'artitechcore_first_activation' => true
    );
    
    // Set options only if they don't exist
    foreach ($default_options as $option_name => $default_value) {
        if (get_option($option_name) === false) {
            add_option($option_name, $default_value);
        }
    }
    
    // Create custom database tables if needed
    artitechcore_create_database_tables();
    
    // Set activation flag
    update_option('artitechcore_plugin_activated', true);
    
    // Clear any cached data
    wp_cache_flush();
    
    // Log activation
    error_log('ArtitechCore Plugin Activated - Version 1.0');
}

/**
 * Plugin deactivation hook
 * Performs cleanup tasks when plugin is deactivated
 */
function artitechcore_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('artitechcore_cleanup_temporary_data');
    
    // Clear any cached data
    wp_cache_flush();
    
    // Set deactivation flag
    update_option('artitechcore_plugin_deactivated', true);
    update_option('artitechcore_deactivation_date', current_time('mysql'));
    
    // Log deactivation
    error_log('ArtitechCore Plugin Deactivated');
}

/**
 * Plugin uninstall hook
 * Removes all plugin data when plugin is deleted
 */
function artitechcore_uninstall() {
    // Only run if user has proper permissions
    if (!current_user_can('delete_plugins')) {
        return;
    }
    
    // Remove all plugin options
    $options_to_remove = array(
        'artitechcore_version',
        'artitechcore_ai_provider',
        'artitechcore_openai_api_key',
        'artitechcore_gemini_api_key',
        'artitechcore_deepseek_api_key',
        'artitechcore_brand_color',
        'artitechcore_default_status',
        'artitechcore_auto_schema_generation',
        'artitechcore_enable_image_generation',
        'artitechcore_image_quality',
        'artitechcore_image_size',
        'artitechcore_max_tokens',
        'artitechcore_temperature',
        'artitechcore_seo_intensity',
        'artitechcore_api_timeout',
        'artitechcore_batch_size',
        'artitechcore_activation_date',
        'artitechcore_first_activation',
        'artitechcore_plugin_activated',
        'artitechcore_plugin_deactivated',
        'artitechcore_deactivation_date'
    );
    
    foreach ($options_to_remove as $option) {
        delete_option($option);
    }
    
    // Remove custom database tables
    artitechcore_drop_database_tables();
    
    // Clear any cached data
    wp_cache_flush();
    
    // Log uninstall
    error_log('ArtitechCore Plugin Uninstalled - All data removed');
}

/**
 * Create custom database tables for plugin
 */
function artitechcore_create_database_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Table for storing AI generation logs
    $table_name = $wpdb->prefix . 'artitechcore_generation_logs';
    
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
    $table_name = $wpdb->prefix . 'artitechcore_schema_data';
    
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
function artitechcore_drop_database_tables() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'artitechcore_generation_logs',
        $wpdb->prefix . 'artitechcore_schema_data'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

// Register activation, deactivation, and uninstall hooks
register_activation_hook(__FILE__, 'artitechcore_activate');
register_deactivation_hook(__FILE__, 'artitechcore_deactivate');
register_uninstall_hook(__FILE__, 'artitechcore_uninstall');

/**
 * Load plugin textdomain for internationalization
 */
function artitechcore_load_textdomain() {
    load_plugin_textdomain(
        'artitechcore',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
add_action('plugins_loaded', 'artitechcore_load_textdomain');

// Output schema markup in wp_head is now handled in includes/schema-generator.php

// Include necessary files
require_once ArtitechCore_PLUGIN_PATH . 'includes/admin-menu.php';
require_once ArtitechCore_PLUGIN_PATH . 'includes/page-creation.php';
require_once ArtitechCore_PLUGIN_PATH . 'includes/csv-handler.php';
require_once ArtitechCore_PLUGIN_PATH . 'includes/settings-page.php';
require_once ArtitechCore_PLUGIN_PATH . 'includes/ai-generator.php';
require_once ArtitechCore_PLUGIN_PATH . 'includes/custom-post-type-manager.php';
require_once ArtitechCore_PLUGIN_PATH . 'includes/hierarchy-manager.php';
require_once ArtitechCore_PLUGIN_PATH . 'includes/menu-generator.php';
require_once ArtitechCore_PLUGIN_PATH . 'includes/schema-generator.php';
require_once ArtitechCore_PLUGIN_PATH . 'includes/keyword-analyzer.php';

// Output schema markup in wp_head is now handled in includes/schema-generator.php

// Enqueue scripts and styles
function artitechcore_enqueue_assets() {
    // Enqueue brand CSS first (Variables & Base)
    wp_enqueue_style('artitechcore-dg10-brand', ArtitechCore_PLUGIN_URL . 'assets/css/dg10-brand.css', array(), '1.0');
    
    // Enqueue Consolidated Admin UI CSS
    wp_enqueue_style('artitechcore-admin-ui', ArtitechCore_PLUGIN_URL . 'assets/css/admin-ui.css', array('artitechcore-dg10-brand'), filemtime(ArtitechCore_PLUGIN_PATH . 'assets/css/admin-ui.css'));

    // Enqueue CPT Management CSS
    wp_enqueue_style('artitechcore-cpt-management', ArtitechCore_PLUGIN_URL . 'assets/css/cpt-management.css', array('artitechcore-admin-ui'), '1.0');

    // Enqueue Scripts
    $current_screen = get_current_screen();

    // CPT Management Scripts (only on CPT tab)
    if ($current_screen && strpos($current_screen->id, 'artitechcore-') !== false && isset($_GET['tab']) && $_GET['tab'] === 'cpt') {
        wp_enqueue_script('artitechcore-cpt-management', ArtitechCore_PLUGIN_URL . 'assets/js/cpt-management.js', array('jquery'), '1.0', true);
        
        // Localize CPT management script
        wp_localize_script('artitechcore-cpt-management', 'artitechcore_cpt_data', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('artitechcore_cpt_ajax'),
            'plugin_url' => ArtitechCore_PLUGIN_URL,
            'strings' => array(
                'loading' => __('Loading...', 'artitechcore'),
                'success' => __('Success!', 'artitechcore'),
                'error' => __('An error occurred.', 'artitechcore'),
                'network_error' => __('Network error occurred. Please try again.', 'artitechcore')
            )
        ));
    }

    // AI Generator Scripts (only on AI tab)
    if ($current_screen && strpos($current_screen->id, 'artitechcore-') !== false && isset($_GET['tab']) && $_GET['tab'] === 'ai') {
        wp_enqueue_script('artitechcore-ai-generator', ArtitechCore_PLUGIN_URL . 'assets/js/ai-generator.js', array('jquery'), '1.0', true);
        
        // Share CPT data for ajaxurl and nonce consistency if needed, 
        // or localize specifically for AI
        wp_localize_script('artitechcore-ai-generator', 'artitechcore_ai_data', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('artitechcore_ai_ajax')
        ));
    }
    
    // Main Admin Scripts
    wp_enqueue_script('artitechcore-scripts', ArtitechCore_PLUGIN_URL . 'assets/js/scripts.js', array('jquery'), '1.0', true);
    
    // Keyword Analyzer Scripts
    wp_enqueue_script('artitechcore-keyword-analyzer', ArtitechCore_PLUGIN_URL . 'assets/js/keyword-analyzer.js', array('jquery'), '1.0', true);
    wp_localize_script('artitechcore-keyword-analyzer', 'artitechcore_keyword_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('artitechcore_keyword_analysis')
    ));

    // Schema Generator Scripts
    wp_enqueue_script('artitechcore-schema-generator', ArtitechCore_PLUGIN_URL . 'assets/js/schema-generator.js', array('jquery'), filemtime(ArtitechCore_PLUGIN_PATH . 'assets/js/schema-generator.js'), true);
    wp_localize_script('artitechcore-schema-generator', 'artitechcore_schema_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('artitechcore_schema_preview')
    ));

    // Localize script with plugin URL
    wp_localize_script('artitechcore-scripts', 'artitechcore_plugin_data', array(
        'plugin_url' => ArtitechCore_PLUGIN_URL
    ));
}
add_action('admin_enqueue_scripts', 'artitechcore_enqueue_assets');
