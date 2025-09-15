<?php
/**
 * Uninstall script for AIOPMS - All In One Page Management System
 * 
 * This file is executed when the plugin is deleted from WordPress.
 * It removes all plugin data including options, custom tables, and metadata.
 * 
 * @package AIOPMS
 * @version 3.0
 * @author DG10 Agency
 * @license GPL-2.0+
 * 
 * @since 3.0
 */

// Prevent direct access to this file.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Prevent execution if not called by WordPress uninstaller.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove all plugin data.
 * 
 * @since 3.0
 */
function aiopms_cleanup_all_data() {
	global $wpdb;
	
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
		'aiopms_network_activated',
		'aiopms_network_deactivated',
		'aiopms_network_activation_date',
		'aiopms_network_deactivation_date',
		'aiopms_dynamic_cpts',
		'aiopms_performance_settings',
		'aiopms_cache_settings',
		'aiopms_optimize_assets',
		'aiopms_sitemap_url',
		'aiopms_schema_settings',
		'aiopms_auto_schema_generation',
	);

	// Remove options from database.
	foreach ( $options_to_remove as $option ) {
		delete_option( $option );
		delete_site_option( $option ); // Remove network options if multisite.
	}

	// Remove custom database tables.
	$tables_to_remove = array(
		$wpdb->prefix . 'aiopms_generation_logs',
		$wpdb->prefix . 'aiopms_performance_logs',
		$wpdb->prefix . 'aiopms_cache_data',
		$wpdb->prefix . 'aiopms_schema_data',
		$wpdb->prefix . 'aiopms_cpt_meta',
	);

	foreach ( $tables_to_remove as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	// Remove post meta data.
	$meta_keys_to_remove = array(
		'_aiopms_schema_type',
		'_aiopms_schema_data',
		'_aiopms_generation_log',
		'_aiopms_performance_data',
		'_aiopms_cpt_meta',
		'_aiopms_ai_generated',
		'_aiopms_image_generated',
		'_aiopms_hierarchy_data',
		'_aiopms_keyword_analysis',
	);

	foreach ( $meta_keys_to_remove as $meta_key ) {
		$wpdb->delete(
			$wpdb->postmeta,
			array( 'meta_key' => $meta_key ),
			array( '%s' )
		);
	}

	// Remove user meta data.
	$user_meta_keys_to_remove = array(
		'aiopms_user_preferences',
		'aiopms_user_settings',
		'aiopms_user_permissions',
	);

	foreach ( $user_meta_keys_to_remove as $meta_key ) {
		$wpdb->delete(
			$wpdb->usermeta,
			array( 'meta_key' => $meta_key ),
			array( '%s' )
		);
	}

	// Remove transients.
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_aiopms_%' OR option_name LIKE '_transient_timeout_aiopms_%'"
	);

	// Remove scheduled events.
	wp_clear_scheduled_hook( 'aiopms_cleanup_temporary_data' );
	wp_clear_scheduled_hook( 'aiopms_schema_cleanup' );
	wp_clear_scheduled_hook( 'aiopms_performance_cleanup' );
	wp_clear_scheduled_hook( 'aiopms_network_cleanup' );

	// Clear object cache.
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}

	// Remove custom capabilities.
	$roles_to_update = array( 'administrator', 'editor', 'author' );
	foreach ( $roles_to_update as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			$role->remove_cap( 'manage_aiopms' );
			$role->remove_cap( 'manage_aiopms_cpts' );
			$role->remove_cap( 'manage_aiopms_schema' );
			$role->remove_cap( 'aiopms_bulk_operations' );
			$role->remove_cap( 'aiopms_ai_generation' );
		}
	}

	// Remove uploaded files (if any).
	$upload_dir = wp_upload_dir();
	$aiopms_upload_dir = $upload_dir['basedir'] . '/aiopms/';
	if ( is_dir( $aiopms_upload_dir ) ) {
		aiopms_recursive_rmdir( $aiopms_upload_dir );
	}

	// Log uninstall.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'AIOPMS Plugin Uninstalled - All data removed' );
	}
}

/**
 * Recursively remove directory and all its contents.
 * 
 * @param string $dir Directory path to remove.
 * @since 3.0
 */
function aiopms_recursive_rmdir( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return false;
	}

	$files = array_diff( scandir( $dir ), array( '.', '..' ) );
	foreach ( $files as $file ) {
		$path = $dir . DIRECTORY_SEPARATOR . $file;
		if ( is_dir( $path ) ) {
			aiopms_recursive_rmdir( $path );
		} else {
			unlink( $path );
		}
	}

	return rmdir( $dir );
}

// Check if this is a multisite installation.
if ( is_multisite() ) {
	// Network uninstall - clean up all sites.
	$sites = get_sites( array( 'number' => 0 ) );
	foreach ( $sites as $site ) {
		switch_to_blog( $site->blog_id );
		aiopms_cleanup_all_data();
		restore_current_blog();
	}
} else {
	// Single site uninstall.
	aiopms_cleanup_all_data();
}

// Final cleanup - remove any remaining network options.
if ( is_multisite() ) {
	$network_options_to_remove = array(
		'aiopms_network_activated',
		'aiopms_network_deactivated',
		'aiopms_network_activation_date',
		'aiopms_network_deactivation_date',
	);

	foreach ( $network_options_to_remove as $option ) {
		delete_site_option( $option );
	}
}
