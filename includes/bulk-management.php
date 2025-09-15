<?php
/**
 * Bulk management functionality for AIOPMS plugin.
 *
 * @package AIOPMS
 * @since 3.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize bulk management functionality.
 */
function aiopms_init_bulk_management() {
	// Add AJAX handlers
	add_action( 'wp_ajax_aiopms_bulk_management', 'aiopms_handle_bulk_management_ajax' );
	
	// Add admin notices
	add_action( 'admin_notices', 'aiopms_bulk_management_admin_notices' );
}

/**
 * Handle bulk management AJAX requests.
 */
function aiopms_handle_bulk_management_ajax() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'aiopms_bulk_management_nonce' ) ) {
		wp_die( 'Security check failed' );
	}
	
	// Check capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}
	
	$action = sanitize_text_field( $_POST['bulk_action'] );
	
	switch ( $action ) {
		case 'preview_pages':
			aiopms_preview_pages_for_deletion();
			break;
		case 'delete_pages':
			aiopms_execute_bulk_deletion();
			break;
		default:
			wp_send_json_error( 'Invalid action' );
	}
}

/**
 * Preview pages for deletion based on filters.
 */
function aiopms_preview_pages_for_deletion() {
	$deletion_type = sanitize_text_field( $_POST['deletion_type'] );
	$start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
	$end_date = sanitize_text_field( $_POST['end_date'] ?? '' );
	$page_status = sanitize_text_field( $_POST['page_status'] ?? '' );
	
	$args = array(
		'post_type' => 'page',
		'post_status' => 'any',
		'posts_per_page' => -1,
		'fields' => 'ids'
	);
	
	// Add meta query based on deletion type
	switch ( $deletion_type ) {
		case 'ai-generated':
			$args['meta_query'] = array(
				array(
					'key' => '_aiopms_ai_generated',
					'value' => '1',
					'compare' => '='
				)
			);
			break;
		case 'plugin-created':
			$args['meta_query'] = array(
				array(
					'key' => '_aiopms_created_by_plugin',
					'value' => '1',
					'compare' => '='
				)
			);
			break;
		case 'date-range':
			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
				$args['date_query'] = array(
					array(
						'after' => $start_date,
						'before' => $end_date,
						'inclusive' => true
					)
				);
			}
			break;
		case 'status':
			if ( ! empty( $page_status ) ) {
				$args['post_status'] = $page_status;
			}
			break;
	}
	
	$page_ids = get_posts( $args );
	$pages = array();
	
	foreach ( $page_ids as $page_id ) {
		$page = get_post( $page_id );
		if ( $page ) {
			$pages[] = array(
				'id' => $page->ID,
				'title' => $page->post_title,
				'status' => $page->post_status,
				'date' => get_the_date( 'Y-m-d', $page->ID )
			);
		}
	}
	
	wp_send_json_success( array(
		'pages' => $pages,
		'count' => count( $pages ),
		'deletion_type' => $deletion_type
	) );
}

/**
 * Execute bulk deletion of pages.
 */
function aiopms_execute_bulk_deletion() {
	$page_ids = array_map( 'intval', $_POST['page_ids'] );
	$create_backup = isset( $_POST['create_backup'] ) && $_POST['create_backup'] === 'true';
	
	$results = array(
		'success' => 0,
		'failed' => 0,
		'errors' => array(),
		'deleted_pages' => array(),
		'backup_created' => false,
		'backup_file' => ''
	);
	
	// Create backup if requested
	if ( $create_backup ) {
		$backup_result = aiopms_create_deletion_backup( $page_ids );
		if ( $backup_result['success'] ) {
			$results['backup_created'] = true;
			$results['backup_file'] = $backup_result['file'];
		}
	}
	
	// Delete pages
	foreach ( $page_ids as $page_id ) {
		$page = get_post( $page_id );
		if ( ! $page ) {
			$results['failed']++;
			$results['errors'][] = "Page ID {$page_id} not found";
			continue;
		}
		
		// Store page info for results
		$page_info = array(
			'id' => $page->ID,
			'title' => $page->post_title
		);
		
		// Delete associated meta data
		aiopms_delete_page_meta_data( $page_id );
		
		// Delete associated images
		aiopms_delete_page_images( $page_id );
		
		// Delete the page
		$deleted = wp_delete_post( $page_id, true );
		
		if ( $deleted ) {
			$results['success']++;
			$results['deleted_pages'][] = $page_info;
		} else {
			$results['failed']++;
			$results['errors'][] = "Failed to delete page: {$page->post_title}";
		}
	}
	
	// Clean up orphaned records
	aiopms_cleanup_orphaned_records();
	
	wp_send_json_success( $results );
}

/**
 * Create backup before deletion.
 */
function aiopms_create_deletion_backup( $page_ids ) {
	$backup_data = array(
		'timestamp' => current_time( 'mysql' ),
		'user_id' => get_current_user_id(),
		'pages' => array()
	);
	
	foreach ( $page_ids as $page_id ) {
		$page = get_post( $page_id );
		if ( $page ) {
			$backup_data['pages'][] = array(
				'id' => $page->ID,
				'title' => $page->post_title,
				'content' => $page->post_content,
				'status' => $page->post_status,
				'meta' => get_post_meta( $page_id ),
				'date' => $page->post_date
			);
		}
	}
	
	$backup_file = 'aiopms_deletion_backup_' . date( 'Y-m-d_H-i-s' ) . '.json';
	$backup_path = WP_CONTENT_DIR . '/aiopms_backups/' . $backup_file;
	
	// Create backup directory if it doesn't exist
	$backup_dir = dirname( $backup_path );
	if ( ! file_exists( $backup_dir ) ) {
		wp_mkdir_p( $backup_dir );
	}
	
	$result = file_put_contents( $backup_path, json_encode( $backup_data, JSON_PRETTY_PRINT ) );
	
	if ( $result !== false ) {
		return array(
			'success' => true,
			'file' => $backup_file,
			'path' => $backup_path
		);
	} else {
		return array(
			'success' => false,
			'error' => 'Failed to create backup file'
		);
	}
}

/**
 * Delete page meta data.
 */
function aiopms_delete_page_meta_data( $page_id ) {
	global $wpdb;
	
	// Delete all meta data for the page
	$wpdb->delete(
		$wpdb->postmeta,
		array( 'post_id' => $page_id ),
		array( '%d' )
	);
}

/**
 * Delete page images.
 */
function aiopms_delete_page_images( $page_id ) {
	// Get featured image
	$featured_image_id = get_post_thumbnail_id( $page_id );
	if ( $featured_image_id ) {
		wp_delete_attachment( $featured_image_id, true );
	}
	
	// Get images from content
	$content = get_post_field( 'post_content', $page_id );
	if ( $content ) {
		preg_match_all( '/wp-image-(\d+)/', $content, $matches );
		if ( ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $image_id ) {
				wp_delete_attachment( $image_id, true );
			}
		}
	}
}

/**
 * Clean up orphaned records.
 */
function aiopms_cleanup_orphaned_records() {
	global $wpdb;
	
	// Clean up orphaned meta data
	$wpdb->query( "
		DELETE pm FROM {$wpdb->postmeta} pm
		LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
		WHERE p.ID IS NULL
	" );
	
	// Clean up orphaned term relationships
	$wpdb->query( "
		DELETE tr FROM {$wpdb->term_relationships} tr
		LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
		WHERE p.ID IS NULL
	" );
}

/**
 * Display admin notices for bulk management.
 */
function aiopms_bulk_management_admin_notices() {
	// Check if we're on the bulk management page
	if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'aiopms-page-management' ) {
		return;
	}
	
	if ( ! isset( $_GET['tab'] ) || $_GET['tab'] !== 'bulk-management' ) {
		return;
	}
	
	// Display any relevant notices
	if ( isset( $_GET['deletion_complete'] ) ) {
		$success_count = intval( $_GET['success'] ?? 0 );
		$failed_count = intval( $_GET['failed'] ?? 0 );
		
		if ( $success_count > 0 ) {
			echo '<div class="notice notice-success is-dismissible">';
			echo '<p><strong>Bulk deletion completed!</strong> Successfully deleted ' . $success_count . ' pages.';
			if ( $failed_count > 0 ) {
				echo ' ' . $failed_count . ' pages failed to delete.';
			}
			echo '</p></div>';
		}
	}
}

/**
 * Get bulk management statistics.
 */
function aiopms_get_bulk_management_stats() {
	$stats = array(
		'total_pages' => wp_count_posts( 'page' )->publish,
		'ai_generated' => 0,
		'plugin_created' => 0,
		'draft_pages' => wp_count_posts( 'page' )->draft,
		'trash_pages' => wp_count_posts( 'page' )->trash
	);
	
	// Count AI generated pages
	$ai_pages = get_posts( array(
		'post_type' => 'page',
		'post_status' => 'any',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'meta_query' => array(
			array(
				'key' => '_aiopms_ai_generated',
				'value' => '1',
				'compare' => '='
			)
		)
	) );
	$stats['ai_generated'] = count( $ai_pages );
	
	// Count plugin created pages
	$plugin_pages = get_posts( array(
		'post_type' => 'page',
		'post_status' => 'any',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'meta_query' => array(
			array(
				'key' => '_aiopms_created_by_plugin',
				'value' => '1',
				'compare' => '='
			)
		)
	) );
	$stats['plugin_created'] = count( $plugin_pages );
	
	return $stats;
}

// Initialize bulk management
add_action( 'init', 'aiopms_init_bulk_management' );