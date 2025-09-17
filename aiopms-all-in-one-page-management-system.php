<?php
/**
 * Plugin Name: AIOPMS - All In One Page Management System
 * Plugin URI: https://dg10.agency/free-wordpress-page-management-plugin/
 * Description: A comprehensive page management system for WordPress with bulk creation, AI generation, hierarchy management, schema markup, and menu generation.
 * Version: 3.0
 * Requires at least: 5.6
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Author: DG10 Agency
 * Author URI: https://www.dg10.agency
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aiopms
 * Domain Path: /languages
 * Network: true
 * Update URI: https://wordpress.org/plugins/aiopms-all-in-one-page-management-system/
 * 
 * @package AIOPMS
 * @version 3.0
 * @author DG10 Agency
 * @license GPL-2.0+
 * @copyright 2024 DG10 Agency
 * 
 * AIOPMS - All In One Page Management System
 * Copyright (C) 2024 DG10 Agency
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin version and requirements.
define( 'AIOPMS_VERSION', '3.0' );
define( 'AIOPMS_MIN_WP_VERSION', '5.6' );
define( 'AIOPMS_MIN_PHP_VERSION', '7.4' );

/**
 * Check WordPress and PHP version compatibility.
 * 
 * @since 3.0
 */
function aiopms_check_requirements() {
	global $wp_version;
	
	// Check WordPress version.
	if ( version_compare( $wp_version, AIOPMS_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'aiopms_wp_version_notice' );
		return false;
	}
	
	// Check PHP version.
	if ( version_compare( PHP_VERSION, AIOPMS_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'aiopms_php_version_notice' );
		return false;
	}
	
	return true;
}

/**
 * Display WordPress version compatibility notice.
 * 
 * @since 3.0
 */
function aiopms_wp_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: %1$s: Plugin name, %2$s: Required WordPress version, %3$s: Current WordPress version */
				esc_html__( '%1$s requires WordPress %2$s or higher. You are running WordPress %3$s. Please upgrade WordPress.', 'aiopms' ),
				'<strong>AIOPMS - All In One Page Management System</strong>',
				AIOPMS_MIN_WP_VERSION,
				$GLOBALS['wp_version']
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Display PHP version compatibility notice.
 * 
 * @since 3.0
 */
function aiopms_php_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: %1$s: Plugin name, %2$s: Required PHP version, %3$s: Current PHP version */
				esc_html__( '%1$s requires PHP %2$s or higher. You are running PHP %3$s. Please upgrade PHP.', 'aiopms' ),
				'<strong>AIOPMS - All In One Page Management System</strong>',
				AIOPMS_MIN_PHP_VERSION,
				PHP_VERSION
			);
			?>
		</p>
	</div>
	<?php
}

// Check requirements on admin_init.
add_action( 'admin_init', 'aiopms_check_requirements' );

// Additional security headers for admin pages
add_action( 'admin_init', function() {
    if ( ! headers_sent() ) {
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'X-XSS-Protection: 1; mode=block' );
    }
});

// Define plugin constants.
define( 'AIOPMS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AIOPMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AIOPMS_GITHUB_URL', 'https://github.com/DG10-Agency/AIOPMS-All-In-One-Page-Management-System' );

/**
 * Plugin activation hook.
 * 
 * Sets up default options and initializes plugin data.
 * Supports both single site and multisite installations.
 *
 * @param bool $network_wide Whether the plugin is being activated network-wide.
 * @since 3.0
 */
function aiopms_activate( $network_wide = false ) {
	// Check requirements before activation.
	if ( ! aiopms_check_requirements() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'AIOPMS cannot be activated. Please check the system requirements.', 'aiopms' ),
			esc_html__( 'Plugin Activation Error', 'aiopms' ),
			array( 'back_link' => true )
		);
	}

	// Set default plugin options.
	$default_options = array(
		'aiopms_version'                    => AIOPMS_VERSION,
		'aiopms_ai_provider'               => 'openai',
		'aiopms_openai_api_key'            => '',
		'aiopms_gemini_api_key'            => '',
		'aiopms_deepseek_api_key'          => '',
		'aiopms_brand_color'               => '#4A90E2',
		'aiopms_default_status'            => 'draft',
		'aiopms_auto_schema_generation'    => true,
		'aiopms_enable_image_generation'   => true,
		'aiopms_image_quality'             => 'standard',
		'aiopms_image_size'                => '1024x1024',
		'aiopms_max_tokens'                => 400,
		'aiopms_temperature'               => 0.5,
		'aiopms_seo_intensity'             => 'high',
		'aiopms_api_timeout'               => 30,
		'aiopms_batch_size'                => 10,
		'aiopms_activation_date'           => current_time( 'mysql' ),
		'aiopms_first_activation'          => true,
		'aiopms_network_activated'         => $network_wide,
	);

	if ( $network_wide && is_multisite() ) {
		// Network activation - set options for all sites.
		$sites = get_sites( array( 'number' => 0 ) );
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			aiopms_activate_single_site( $default_options );
			restore_current_blog();
		}
	} else {
		// Single site activation.
		aiopms_activate_single_site( $default_options );
	}

	// Set network-wide activation flag if applicable.
	if ( $network_wide && is_multisite() ) {
		update_site_option( 'aiopms_network_activated', true );
		update_site_option( 'aiopms_network_activation_date', current_time( 'mysql' ) );
	}

	// Set activation flag.
	update_option( 'aiopms_plugin_activated', true );

	// Clear any cached data.
	wp_cache_flush();

	// Log activation.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'AIOPMS Plugin Activated - Version ' . AIOPMS_VERSION . ' (Network: ' . ( $network_wide ? 'Yes' : 'No' ) . ')' );
	}
}

/**
 * Activate plugin for a single site.
 * 
 * @param array $default_options Default options to set.
 * @since 3.0
 */
function aiopms_activate_single_site( $default_options ) {
	// Set options only if they don't exist.
	foreach ( $default_options as $option_name => $default_value ) {
		if ( false === get_option( $option_name ) ) {
			add_option( $option_name, $default_value );
		}
	}

	// Create custom database tables if needed.
	aiopms_create_database_tables();

	// Set up default capabilities for administrators.
	$role = get_role( 'administrator' );
	if ( $role ) {
		$role->add_cap( 'manage_aiopms' );
		$role->add_cap( 'manage_aiopms_cpts' );
		$role->add_cap( 'manage_aiopms_schema' );
	}
}

/**
 * Plugin deactivation hook.
 * 
 * Performs cleanup tasks when plugin is deactivated.
 * Supports both single site and multisite installations.
 *
 * @param bool $network_wide Whether the plugin is being deactivated network-wide.
 * @since 3.0
 */
function aiopms_deactivate( $network_wide = false ) {
	if ( $network_wide && is_multisite() ) {
		// Network deactivation - clear events for all sites.
		$sites = get_sites( array( 'number' => 0 ) );
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			aiopms_deactivate_single_site();
			restore_current_blog();
		}
		
		// Clear network-wide scheduled events.
		wp_clear_scheduled_hook( 'aiopms_network_cleanup' );
		
		// Update network deactivation flag.
		update_site_option( 'aiopms_network_deactivated', true );
		update_site_option( 'aiopms_network_deactivation_date', current_time( 'mysql' ) );
	} else {
		// Single site deactivation.
		aiopms_deactivate_single_site();
	}

	// Clear any cached data.
	wp_cache_flush();

	// Log deactivation.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'AIOPMS Plugin Deactivated (Network: ' . ( $network_wide ? 'Yes' : 'No' ) . ')' );
	}
}

/**
 * Deactivate plugin for a single site.
 * 
 * @since 3.0
 */
function aiopms_deactivate_single_site() {
	// Clear scheduled events.
	wp_clear_scheduled_hook( 'aiopms_cleanup_temporary_data' );
	wp_clear_scheduled_hook( 'aiopms_schema_cleanup' );
	wp_clear_scheduled_hook( 'aiopms_performance_cleanup' );

	// Set deactivation flag.
	update_option( 'aiopms_plugin_deactivated', true );
	update_option( 'aiopms_deactivation_date', current_time( 'mysql' ) );

	// Remove custom capabilities (optional - you may want to keep them).
	$role = get_role( 'administrator' );
	if ( $role ) {
		$role->remove_cap( 'manage_aiopms' );
		$role->remove_cap( 'manage_aiopms_cpts' );
		$role->remove_cap( 'manage_aiopms_schema' );
	}
}

/**
 * Plugin uninstall hook.
 * 
 * Removes all plugin data when plugin is deleted.
 *
 * @since 3.0
 */
function aiopms_uninstall() {
	// Only run if user has proper permissions.
	if ( ! current_user_can( 'delete_plugins' ) ) {
		return;
	}

	// Include necessary files for cleanup.
	require_once AIOPMS_PLUGIN_PATH . 'includes/error-handler.php';
	require_once AIOPMS_PLUGIN_PATH . 'includes/backup-restore.php';
	require_once AIOPMS_PLUGIN_PATH . 'includes/status-manager.php';

	// Log uninstall start.
	aiopms_log_error( 'AIOPMS Plugin Uninstall Started', 'Info' );

	// Remove all plugin options.
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
		'aiopms_deactivation_date',
		'aiopms_optimize_assets',
		'aiopms_performance_mode',
		'aiopms_cache_version',
		'aiopms_status_data',
		'aiopms_admin_notices',
		'aiopms_progress_',
		'aiopms_db_version',
		'aiopms_last_cache_flush',
		'aiopms_network_activated',
		'aiopms_network_activation_date',
		'aiopms_network_deactivated',
		'aiopms_network_deactivation_date',
	);

	foreach ( $options_to_remove as $option ) {
		delete_option( $option );
	}

	// Remove all transients.
	aiopms_cleanup_all_transients();

	// Remove custom database tables.
	aiopms_drop_database_tables();

	// Clean up files and directories.
	aiopms_cleanup_plugin_files();

	// Clear any cached data.
	wp_cache_flush();

	// Clear scheduled events.
	wp_clear_scheduled_hook( 'aiopms_cleanup_temporary_data' );
	wp_clear_scheduled_hook( 'aiopms_schema_cleanup' );
	wp_clear_scheduled_hook( 'aiopms_performance_cleanup' );
	wp_clear_scheduled_hook( 'aiopms_cleanup_old_backups' );
	wp_clear_scheduled_hook( 'aiopms_cleanup_status_data' );

	// Log uninstall completion.
	aiopms_log_error( 'AIOPMS Plugin Uninstalled - All data removed', 'Info' );
}

/**
 * Clean up all plugin transients.
 * 
 * @since 3.0
 */
function aiopms_cleanup_all_transients() {
	global $wpdb;
	
	// Remove all transients with aiopms prefix.
	$wpdb->query( $wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'_transient_aiopms_%',
		'_transient_timeout_aiopms_%'
	) );
}

/**
 * Clean up plugin files and directories.
 * 
 * @since 3.0
 */
function aiopms_cleanup_plugin_files() {
	$upload_dir = wp_upload_dir();
	
	// Directories to clean up.
	$directories_to_remove = array(
		$upload_dir['basedir'] . '/aiopms-backups/',
		$upload_dir['basedir'] . '/aiopms/temp/',
		$upload_dir['basedir'] . '/aiopms/',
	);
	
	foreach ( $directories_to_remove as $dir ) {
		if ( is_dir( $dir ) ) {
			aiopms_recursive_rmdir( $dir );
		}
	}
	
	// Remove error log file.
	$error_log_file = WP_CONTENT_DIR . '/aiopms-error.log';
	if ( file_exists( $error_log_file ) ) {
		unlink( $error_log_file );
	}
	
	// Remove optimized asset files.
	$asset_files = glob( $upload_dir['basedir'] . '/aiopms-combined-*.min.*' );
	foreach ( $asset_files as $file ) {
		if ( file_exists( $file ) ) {
			unlink( $file );
		}
	}
}

/**
 * Recursively remove directory and all contents.
 * 
 * @param string $dir Directory path.
 * @since 3.0
 */
function aiopms_recursive_rmdir( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	
	$files = array_diff( scandir( $dir ), array( '.', '..' ) );
	
	foreach ( $files as $file ) {
		$path = $dir . '/' . $file;
		if ( is_dir( $path ) ) {
			aiopms_recursive_rmdir( $path );
		} else {
			unlink( $path );
		}
	}
	
	rmdir( $dir );
}

/**
 * Create custom database tables for plugin.
 * 
 * @since 3.0
 */
function aiopms_create_database_tables() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	// Table for storing AI generation logs.
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

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	
	// Create generation logs table.
	$results = array();
	$results[] = dbDelta( $sql );

	// Table for storing schema generation data.
	$schema_table = $wpdb->prefix . 'aiopms_schema_data';

	$schema_sql = "CREATE TABLE $schema_table (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		post_id bigint(20) NOT NULL,
		schema_type varchar(100) NOT NULL,
		schema_data longtext NOT NULL,
		created_date datetime DEFAULT CURRENT_TIMESTAMP,
		updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		site_id bigint(20) DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY post_id (post_id, site_id),
		KEY schema_type (schema_type),
		KEY site_id (site_id)
	) $charset_collate;";

	$results[] = dbDelta( $schema_sql );

	// Table for storing performance logs.
	$performance_table = $wpdb->prefix . 'aiopms_performance_logs';

	$performance_sql = "CREATE TABLE $performance_table (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		operation varchar(100) NOT NULL,
		execution_time decimal(10,4) NOT NULL,
		memory_usage int(11) DEFAULT 0,
		timestamp datetime DEFAULT CURRENT_TIMESTAMP,
		site_id bigint(20) DEFAULT 0,
		PRIMARY KEY (id),
		KEY operation (operation),
		KEY timestamp (timestamp),
		KEY site_id (site_id)
	) $charset_collate;";

	$results[] = dbDelta( $performance_sql );

	// Table for storing cache data.
	$cache_table = $wpdb->prefix . 'aiopms_cache_data';

	$cache_sql = "CREATE TABLE $cache_table (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		cache_key varchar(255) NOT NULL,
		cache_value longtext NOT NULL,
		expires datetime DEFAULT NULL,
		created datetime DEFAULT CURRENT_TIMESTAMP,
		site_id bigint(20) DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY cache_key (cache_key, site_id),
		KEY expires (expires),
		KEY site_id (site_id)
	) $charset_collate;";

	$results[] = dbDelta( $cache_sql );

	// Table for storing CPT metadata.
	$cpt_table = $wpdb->prefix . 'aiopms_cpt_meta';

	$cpt_sql = "CREATE TABLE $cpt_table (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		post_type varchar(100) NOT NULL,
		meta_key varchar(100) NOT NULL,
		meta_value longtext,
		created datetime DEFAULT CURRENT_TIMESTAMP,
		updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		site_id bigint(20) DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY post_type_meta (post_type, meta_key, site_id),
		KEY site_id (site_id)
	) $charset_collate;";

	$results[] = dbDelta( $cpt_sql );

	// Log table creation results.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'AIOPMS Database tables created: ' . print_r( $results, true ) );
	}

	// Update database version.
	update_option( 'aiopms_db_version', AIOPMS_VERSION );

	return $results;
}

/**
 * Drop custom database tables.
 * 
 * @since 3.0
 */
function aiopms_drop_database_tables() {
	global $wpdb;

	$tables = array(
		$wpdb->prefix . 'aiopms_generation_logs',
		$wpdb->prefix . 'aiopms_schema_data',
		$wpdb->prefix . 'aiopms_performance_logs',
		$wpdb->prefix . 'aiopms_cache_data',
		$wpdb->prefix . 'aiopms_cpt_meta',
	);

	foreach ( $tables as $table ) {
		$result = $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "AIOPMS: Dropped table {$table} - Result: {$result}" );
		}
	}

	// Remove database version option.
	delete_option( 'aiopms_db_version' );
}

// Register activation, deactivation, and uninstall hooks.
register_activation_hook( __FILE__, 'aiopms_activate' );
register_deactivation_hook( __FILE__, 'aiopms_deactivate' );
register_uninstall_hook( __FILE__, 'aiopms_uninstall' );

/**
 * Load plugin textdomain for internationalization.
 * 
 * @since 3.0
 */
function aiopms_load_textdomain() {
	$domain = 'aiopms';
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
	
	// Load from wp-content/languages/plugins/ first (for translations from WordPress.org).
	$mo_file = WP_LANG_DIR . '/plugins/' . $domain . '-' . $locale . '.mo';
	if ( file_exists( $mo_file ) ) {
		load_textdomain( $domain, $mo_file );
	}
	
	// Load from plugin languages directory as fallback.
	load_plugin_textdomain(
		$domain,
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages/'
	);
}

/**
 * Add plugin action links.
 * 
 * @param array $links Existing action links.
 * @return array Modified action links.
 * @since 3.0
 */
function aiopms_add_action_links( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=aiopms-page-management&tab=settings' ) . '">' . esc_html__( 'Settings', 'aiopms' ) . '</a>';
	array_unshift( $links, $settings_link );
	
	return $links;
}

/**
 * Add plugin row meta links.
 * 
 * @param array $links Existing plugin meta links.
 * @param string $file Plugin file.
 * @return array Modified plugin meta links.
 * @since 3.0
 */
function aiopms_add_plugin_row_meta( $links, $file ) {
	if ( plugin_basename( __FILE__ ) === $file ) {
		$row_meta = array(
			'plugin_page'      => '<a href="https://dg10.agency/free-wordpress-page-management-plugin/" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Visit AIOPMS Plugin Page', 'aiopms' ) . '">' . esc_html__( 'Visit Plugin Site', 'aiopms' ) . '</a>',
			'docs'             => '<a href="' . AIOPMS_GITHUB_URL . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'View AIOPMS Documentation', 'aiopms' ) . '">' . esc_html__( 'Documentation', 'aiopms' ) . '</a>',
			'support'          => '<a href="https://dg10.agency/free-wordpress-page-management-plugin/#support" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Get AIOPMS Support', 'aiopms' ) . '">' . esc_html__( 'Support', 'aiopms' ) . '</a>',
			'feature_request'  => '<a href="https://dg10.agency/free-wordpress-page-management-plugin/#feature-request" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Request AIOPMS Feature', 'aiopms' ) . '">' . esc_html__( 'Feature Request', 'aiopms' ) . '</a>',
			'donate'           => '<a href="https://dg10.agency/free-wordpress-page-management-plugin/#donate" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Donate to AIOPMS', 'aiopms' ) . '">' . esc_html__( 'Donate', 'aiopms' ) . '</a>',
			'rate'             => '<a href="https://dg10.agency/free-wordpress-page-management-plugin/" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Rate AIOPMS Plugin', 'aiopms' ) . '">' . esc_html__( 'Rate Plugin', 'aiopms' ) . '</a>',
		);
		
		$links = array_merge( $links, $row_meta );
	}
	
	return $links;
}
add_action( 'plugins_loaded', 'aiopms_load_textdomain' );

// Add plugin action and row meta links.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'aiopms_add_action_links' );
add_filter( 'plugin_row_meta', 'aiopms_add_plugin_row_meta', 10, 2 );

/**
 * Initialize plugin hooks and filters.
 * 
 * @since 3.0
 */
function aiopms_init_hooks() {
	// Add multisite support hooks.
	if ( is_multisite() ) {
		add_action( 'wpmu_new_blog', 'aiopms_new_site_activated', 10, 6 );
		add_action( 'delete_blog', 'aiopms_site_deleted', 10, 2 );
		add_action( 'wp_initialize_site', 'aiopms_site_initialized', 10, 2 );
	}

	// Add upgrade hooks.
	add_action( 'upgrader_process_complete', 'aiopms_plugin_upgrade_complete', 10, 2 );
	
	// Add admin hooks.
	add_action( 'admin_init', 'aiopms_admin_init' );
	add_action( 'admin_notices', 'aiopms_admin_notices' );
	
	// Add frontend hooks.
	add_action( 'wp_head', 'aiopms_output_schema_markup', 5 );
	add_action( 'wp_enqueue_scripts', 'aiopms_enqueue_frontend_assets' );
	
	// Add cleanup hooks.
	add_action( 'aiopms_cleanup_temporary_data', 'aiopms_cleanup_temporary_files' );
	add_action( 'aiopms_schema_cleanup', 'aiopms_cleanup_old_schema_data' );
	add_action( 'aiopms_performance_cleanup', 'aiopms_cleanup_performance_logs' );
	
	// Schedule cleanup events if not already scheduled.
	if ( ! wp_next_scheduled( 'aiopms_cleanup_temporary_data' ) ) {
		wp_schedule_event( time(), 'daily', 'aiopms_cleanup_temporary_data' );
	}
	
	if ( ! wp_next_scheduled( 'aiopms_schema_cleanup' ) ) {
		wp_schedule_event( time(), 'weekly', 'aiopms_schema_cleanup' );
	}
	
	if ( ! wp_next_scheduled( 'aiopms_performance_cleanup' ) ) {
		wp_schedule_event( time(), 'weekly', 'aiopms_performance_cleanup' );
	}
}

/**
 * Handle new site activation in multisite.
 * 
 * @param int $blog_id New blog ID.
 * @param int $user_id User ID.
 * @param string $domain Domain.
 * @param string $path Path.
 * @param int $site_id Site ID.
 * @param array $meta Meta data.
 * @since 3.0
 */
function aiopms_new_site_activated( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
		switch_to_blog( $blog_id );
		aiopms_activate_single_site( array() );
		restore_current_blog();
	}
}

/**
 * Handle site deletion in multisite.
 * 
 * @param int $blog_id Blog ID.
 * @param bool $drop Whether to drop tables.
 * @since 3.0
 */
function aiopms_site_deleted( $blog_id, $drop ) {
	if ( $drop ) {
		switch_to_blog( $blog_id );
		aiopms_drop_database_tables();
		restore_current_blog();
	}
}

/**
 * Handle site initialization in multisite.
 * 
 * @param WP_Site $new_site New site object.
 * @param array $args Arguments.
 * @since 3.0
 */
function aiopms_site_initialized( $new_site, $args ) {
	if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
		switch_to_blog( $new_site->blog_id );
		aiopms_activate_single_site( array() );
		restore_current_blog();
	}
}

/**
 * Handle plugin upgrade completion.
 * 
 * @param WP_Upgrader $upgrader Upgrader object.
 * @param array $hook_extra Hook extra data.
 * @since 3.0
 */
function aiopms_plugin_upgrade_complete( $upgrader, $hook_extra ) {
	if ( 'plugin' === $hook_extra['type'] && 'update' === $hook_extra['action'] ) {
		if ( isset( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ) {
			foreach ( $hook_extra['plugins'] as $plugin ) {
				if ( plugin_basename( __FILE__ ) === $plugin ) {
					// Plugin was updated, run upgrade tasks.
					aiopms_handle_plugin_upgrade();
					break;
				}
			}
		}
	}
}

/**
 * Handle plugin upgrade tasks.
 * 
 * @since 3.0
 */
function aiopms_handle_plugin_upgrade() {
	// Update database version.
	$current_db_version = get_option( 'aiopms_db_version', '0.0' );
	if ( version_compare( $current_db_version, AIOPMS_VERSION, '<' ) ) {
		aiopms_create_database_tables();
	}
	
	// Update plugin version.
	update_option( 'aiopms_version', AIOPMS_VERSION );
	
	// Clear any cached data.
	wp_cache_flush();
	
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'AIOPMS Plugin upgraded to version ' . AIOPMS_VERSION );
	}
}

/**
 * Initialize admin functionality.
 * 
 * @since 3.0
 */
function aiopms_admin_init() {
	// Check if plugin was just activated.
	if ( get_option( 'aiopms_plugin_activated' ) ) {
		delete_option( 'aiopms_plugin_activated' );
		add_action( 'admin_notices', 'aiopms_activation_notice' );
	}
	
	// Check if plugin was just upgraded.
	$stored_version = get_option( 'aiopms_version', '0.0' );
	if ( version_compare( $stored_version, AIOPMS_VERSION, '<' ) ) {
		aiopms_handle_plugin_upgrade();
	}
}

/**
 * Display admin notices.
 * 
 * @since 3.0
 */
function aiopms_admin_notices() {
	// Check for any pending notices.
	$notices = get_option( 'aiopms_admin_notices', array() );
	if ( ! empty( $notices ) ) {
		foreach ( $notices as $notice ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $notice['type'] ), esc_html( $notice['message'] ) );
		}
		delete_option( 'aiopms_admin_notices' );
	}
}

/**
 * Display activation notice.
 * 
 * @since 3.0
 */
function aiopms_activation_notice() {
	?>
	<div class="notice notice-success is-dismissible">
		<p>
			<?php
			printf(
				/* translators: %s: Plugin name */
				esc_html__( '%s has been activated successfully!', 'aiopms' ),
				'<strong>AIOPMS - All In One Page Management System</strong>'
			);
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiopms-page-management' ) ); ?>" class="button button-primary" style="margin-left: 10px;">
				<?php esc_html_e( 'Get Started', 'aiopms' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * Enqueue frontend assets.
 * 
 * @since 3.0
 */
function aiopms_enqueue_frontend_assets() {
	// Only enqueue if needed.
	if ( is_page() || is_single() ) {
		wp_enqueue_script( 'aiopms-frontend', AIOPMS_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), AIOPMS_VERSION, true );
		wp_localize_script( 'aiopms-frontend', 'aiopms_frontend', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'aiopms_frontend_nonce' ),
		) );
	}
}

/**
 * Cleanup temporary files.
 * 
 * @since 3.0
 */
function aiopms_cleanup_temporary_files() {
	$upload_dir = wp_upload_dir();
	$temp_dir = $upload_dir['basedir'] . '/aiopms/temp/';
	
	if ( is_dir( $temp_dir ) ) {
		$files = glob( $temp_dir . '*' );
		$cutoff_time = time() - ( 24 * 60 * 60 ); // 24 hours ago.
		
		foreach ( $files as $file ) {
			if ( is_file( $file ) && filemtime( $file ) < $cutoff_time ) {
				unlink( $file );
			}
		}
	}
}

/**
 * Cleanup old schema data.
 * 
 * @since 3.0
 */
function aiopms_cleanup_old_schema_data() {
	global $wpdb;
	
	// Remove schema data older than 30 days for unpublished posts.
	$cutoff_date = date( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
	
	$wpdb->query( $wpdb->prepare(
		"DELETE sd FROM {$wpdb->prefix}aiopms_schema_data sd 
		INNER JOIN {$wpdb->posts} p ON sd.post_id = p.ID 
		WHERE p.post_status != 'publish' AND sd.updated_date < %s",
		$cutoff_date
	) );
}

/**
 * Cleanup performance logs.
 * 
 * @since 3.0
 */
function aiopms_cleanup_performance_logs() {
	global $wpdb;
	
	// Remove performance logs older than 7 days.
	$cutoff_date = date( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
	
	$wpdb->query( $wpdb->prepare(
		"DELETE FROM {$wpdb->prefix}aiopms_performance_logs WHERE timestamp < %s",
		$cutoff_date
	) );
}

// Initialize hooks.
add_action( 'init', 'aiopms_init_hooks' );

// Include necessary files.
require_once AIOPMS_PLUGIN_PATH . 'includes/performance-optimizer.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/error-handler.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/data-validator.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/user-feedback.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/backup-restore.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/help-tooltips.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/status-manager.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/admin-menu.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/page-creation.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/csv-handler.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/settings-page.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/ai-generator.php';

// CPT Manager Refactor
require_once AIOPMS_PLUGIN_PATH . 'includes/cpt/class-aiopms-cpt-manager.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/cpt/class-aiopms-cpt-admin-ui.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/cpt/class-aiopms-cpt-ajax.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/cpt/class-aiopms-cpt-rest.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/cpt/class-aiopms-cpt-hooks.php';

// Initialize the CPT Manager
$aiopms_cpt_hooks = new AIOPMS_CPT_Hooks();
add_action('plugins_loaded', array($aiopms_cpt_hooks, 'init'));

require_once AIOPMS_PLUGIN_PATH . 'includes/hierarchy-manager.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/menu-generator.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/schema-generator.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/keyword-analyzer.php';
require_once AIOPMS_PLUGIN_PATH . 'includes/bulk-management.php';

/**
 * Enqueue scripts and styles for admin pages.
 * 
 * @since 3.0
 */
function aiopms_enqueue_assets() {
	// Check if performance optimization is enabled.
	$optimize_assets = get_option( 'aiopms_optimize_assets', true );

	if ( $optimize_assets && class_exists( 'AIOPMS_Asset_Optimizer' ) ) {
		// Use optimized asset loading.
		$asset_optimizer = AIOPMS_Asset_Optimizer::get_instance();

		// Define CSS files to combine.
		$css_files = array(
			'dg10-brand.css',
			'styles.css',
			'admin-menu.css',
			'schema-column.css',
			'hierarchy.css',
		);

		// Check if we're on CPT management page.
		$current_screen = get_current_screen();
		if ( $current_screen && false !== strpos( $current_screen->id, 'aiopms-cpt-management' ) ) {
			$css_files[] = 'cpt-management.css';
		}

		// Optimize and enqueue combined CSS.
		$combined_css = $asset_optimizer->optimize_css( $css_files, 'aiopms-combined-' . AIOPMS_CACHE_VERSION );
		if ( $combined_css && file_exists( $combined_css ) ) {
			$upload_dir = wp_upload_dir();
			$css_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $combined_css );
			wp_enqueue_style( 'aiopms-combined', $css_url, array(), AIOPMS_CACHE_VERSION );
		} else {
			// Fallback to individual files.
			aiopms_enqueue_individual_assets();
		}

		// Define JS files to combine.
		$js_files = array( 'scripts.js' );
		if ( $current_screen && false !== strpos( $current_screen->id, 'aiopms-cpt-management' ) ) {
			$js_files[] = 'cpt-management.js';
		}

		// Optimize and enqueue combined JS.
		$combined_js = $asset_optimizer->optimize_js( $js_files, 'aiopms-combined-' . AIOPMS_CACHE_VERSION );
		if ( $combined_js && file_exists( $combined_js ) ) {
			$upload_dir = wp_upload_dir();
			$js_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $combined_js );
			wp_enqueue_script( 'aiopms-combined', $js_url, array( 'jquery' ), AIOPMS_CACHE_VERSION, true );
		} else {
			// Fallback to individual files.
			aiopms_enqueue_individual_scripts();
		}
	} else {
		// Use individual asset loading.
		aiopms_enqueue_individual_assets();
		aiopms_enqueue_individual_scripts();
	}

	// Localize scripts.
	aiopms_localize_scripts();
}

/**
 * Enqueue individual CSS files (fallback).
 * 
 * @since 3.0
 */
function aiopms_enqueue_individual_assets() {
	wp_enqueue_style( 'aiopms-dg10-brand', AIOPMS_PLUGIN_URL . 'assets/css/dg10-brand.css', array(), '3.0' );
	wp_enqueue_style( 'aiopms-styles', AIOPMS_PLUGIN_URL . 'assets/css/styles.css', array( 'aiopms-dg10-brand' ), '3.0' );
	wp_enqueue_style( 'aiopms-admin-menu', AIOPMS_PLUGIN_URL . 'assets/css/admin-menu.css', array( 'aiopms-dg10-brand' ), '3.0' );
	wp_enqueue_style( 'aiopms-schema-column', AIOPMS_PLUGIN_URL . 'assets/css/schema-column.css', array( 'aiopms-dg10-brand' ), '3.0' );
	wp_enqueue_style( 'aiopms-hierarchy', AIOPMS_PLUGIN_URL . 'assets/css/hierarchy.css', array( 'aiopms-dg10-brand' ), '3.0' );

	// Enqueue CPT management assets on CPT management pages.
	$current_screen = get_current_screen();
	if ( $current_screen && false !== strpos( $current_screen->id, 'aiopms-cpt-management' ) ) {
		wp_enqueue_style( 'aiopms-cpt-management', AIOPMS_PLUGIN_URL . 'assets/css/cpt-management.css', array( 'aiopms-dg10-brand' ), '3.0' );
	}
}

/**
 * Enqueue individual JS files (fallback).
 * 
 * @since 3.0
 */
function aiopms_enqueue_individual_scripts() {
	wp_enqueue_script( 'aiopms-scripts', AIOPMS_PLUGIN_URL . 'assets/js/scripts.js', array( 'jquery' ), '3.0', true );

	// Enqueue CPT management script on CPT management pages.
	$current_screen = get_current_screen();
	if ( $current_screen && false !== strpos( $current_screen->id, 'aiopms-cpt-management' ) ) {
		wp_enqueue_script( 'aiopms-cpt-management', AIOPMS_PLUGIN_URL . 'assets/js/cpt-management.js', array( 'jquery' ), '3.0', true );
	}
}

/**
 * Localize scripts with plugin data.
 * 
 * @since 3.0
 */
function aiopms_localize_scripts() {
	$current_screen = get_current_screen();

	// Localize main script.
	wp_localize_script(
		'aiopms-scripts',
		'aiopms_plugin_data',
		array(
			'plugin_url'       => AIOPMS_PLUGIN_URL,
			'ajaxurl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'aiopms_ajax' ),
			'performance_mode' => get_option( 'aiopms_optimize_assets', true ),
		)
	);

	// Localize CPT management script if on CPT management page.
	if ( $current_screen && false !== strpos( $current_screen->id, 'aiopms-cpt-management' ) ) {
		wp_localize_script(
			'aiopms-cpt-management',
			'aiopms_cpt_data',
			array(
				'ajaxurl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'aiopms_cpt_ajax' ),
				'plugin_url' => AIOPMS_PLUGIN_URL,
				'strings'    => array(
					'confirm_delete'      => __( 'Are you sure you want to delete this custom post type? This action cannot be undone.', 'aiopms' ),
					'confirm_bulk_delete' => __( 'Are you sure you want to delete the selected custom post types? This action cannot be undone.', 'aiopms' ),
					'loading'             => __( 'Loading...', 'aiopms' ),
					'success'             => __( 'Success!', 'aiopms' ),
					'error'               => __( 'An error occurred.', 'aiopms' ),
					'network_error'       => __( 'Network error occurred. Please try again.', 'aiopms' ),
				),
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'aiopms_enqueue_assets' );
