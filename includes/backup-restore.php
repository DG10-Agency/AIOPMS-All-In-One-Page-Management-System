<?php
/**
 * Backup and Restore System for AIOPMS
 * 
 * @package AIOPMS
 * @version 3.0
 * @author DG10 Agency
 * @license GPL-2.0+
 * @copyright 2024 DG10 Agency
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AIOPMS Backup and Restore Class
 * 
 * Handles backup and restore functionality for plugin data.
 * 
 * @since 3.0
 */
class AIOPMS_Backup_Restore {
	
	/**
	 * Instance of the class.
	 * 
	 * @var AIOPMS_Backup_Restore
	 * @since 3.0
	 */
	private static $instance = null;
	
	/**
	 * Backup directory.
	 * 
	 * @var string
	 * @since 3.0
	 */
	private $backup_dir;
	
	/**
	 * Maximum backup age in days.
	 * 
	 * @var int
	 * @since 3.0
	 */
	private $max_backup_age = 30;
	
	/**
	 * Get instance of the class.
	 * 
	 * @return AIOPMS_Backup_Restore
	 * @since 3.0
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor.
	 * 
	 * @since 3.0
	 */
	private function __construct() {
		$this->backup_dir = WP_CONTENT_DIR . '/aiopms-backups/';
		$this->init_hooks();
	}
	
	/**
	 * Initialize hooks.
	 * 
	 * @since 3.0
	 */
	private function init_hooks() {
		// Add AJAX handlers.
		add_action( 'wp_ajax_aiopms_create_backup', array( $this, 'ajax_create_backup' ) );
		add_action( 'wp_ajax_aiopms_restore_backup', array( $this, 'ajax_restore_backup' ) );
		add_action( 'wp_ajax_aiopms_delete_backup', array( $this, 'ajax_delete_backup' ) );
		add_action( 'wp_ajax_aiopms_download_backup', array( $this, 'ajax_download_backup' ) );
		add_action( 'wp_ajax_aiopms_list_backups', array( $this, 'ajax_list_backups' ) );
		
		// Add cleanup hook.
		add_action( 'aiopms_cleanup_old_backups', array( $this, 'cleanup_old_backups' ) );
		
		// Schedule cleanup if not already scheduled.
		if ( ! wp_next_scheduled( 'aiopms_cleanup_old_backups' ) ) {
			wp_schedule_event( time(), 'daily', 'aiopms_cleanup_old_backups' );
		}
	}
	
	/**
	 * Create backup.
	 * 
	 * @param string $backup_name Backup name.
	 * @param array  $options Backup options.
	 * @return array Backup result.
	 * @since 3.0
	 */
	public function create_backup( $backup_name = '', $options = array() ) {
		try {
			// Generate backup name if not provided.
			if ( empty( $backup_name ) ) {
				$backup_name = 'aiopms-backup-' . date( 'Y-m-d-H-i-s' );
			}
			
			// Sanitize backup name.
			$backup_name = sanitize_file_name( $backup_name );
			
			// Set default options.
			$default_options = array(
				'include_settings' => true,
				'include_pages' => true,
				'include_schema' => true,
				'include_logs' => false,
				'include_cache' => false,
				'compress' => true,
			);
			
			$options = wp_parse_args( $options, $default_options );
			
			// Create backup directory if it doesn't exist.
			if ( ! file_exists( $this->backup_dir ) ) {
				wp_mkdir_p( $this->backup_dir );
			}
			
			// Create backup data structure.
			$backup_data = array(
				'backup_info' => array(
					'name' => $backup_name,
					'created' => current_time( 'mysql' ),
					'version' => AIOPMS_VERSION,
					'wp_version' => get_bloginfo( 'version' ),
					'php_version' => PHP_VERSION,
					'site_url' => get_site_url(),
					'options' => $options,
				),
				'data' => array(),
			);
			
			// Include settings if requested.
			if ( $options['include_settings'] ) {
				$backup_data['data']['settings'] = $this->backup_settings();
			}
			
			// Include pages if requested.
			if ( $options['include_pages'] ) {
				$backup_data['data']['pages'] = $this->backup_pages();
			}
			
			// Include schema data if requested.
			if ( $options['include_schema'] ) {
				$backup_data['data']['schema'] = $this->backup_schema_data();
			}
			
			// Include logs if requested.
			if ( $options['include_logs'] ) {
				$backup_data['data']['logs'] = $this->backup_logs();
			}
			
			// Include cache if requested.
			if ( $options['include_cache'] ) {
				$backup_data['data']['cache'] = $this->backup_cache();
			}
			
			// Create backup file.
			$backup_file = $this->backup_dir . $backup_name . '.json';
			$backup_content = wp_json_encode( $backup_data, JSON_PRETTY_PRINT );
			
			if ( false === file_put_contents( $backup_file, $backup_content ) ) {
				throw new Exception( __( 'Failed to create backup file.', 'aiopms' ) );
			}
			
			// Compress backup if requested.
			if ( $options['compress'] ) {
				$compressed_file = $backup_file . '.gz';
				$gz = gzopen( $compressed_file, 'w9' );
				if ( $gz ) {
					gzwrite( $gz, $backup_content );
					gzclose( $gz );
					unlink( $backup_file );
					$backup_file = $compressed_file;
				}
			}
			
			// Log backup creation.
			aiopms_log_error( 'Backup created: ' . $backup_name, 'Info' );
			
			return array(
				'success' => true,
				'backup_name' => $backup_name,
				'backup_file' => $backup_file,
				'size' => filesize( $backup_file ),
				'message' => __( 'Backup created successfully.', 'aiopms' ),
			);
			
		} catch ( Exception $e ) {
			aiopms_log_error( 'Backup creation failed: ' . $e->getMessage(), 'Error' );
			
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}
	
	/**
	 * Restore backup.
	 * 
	 * @param string $backup_file Backup file path.
	 * @param array  $options Restore options.
	 * @return array Restore result.
	 * @since 3.0
	 */
	public function restore_backup( $backup_file, $options = array() ) {
		try {
			// Check if backup file exists.
			if ( ! file_exists( $backup_file ) ) {
				throw new Exception( __( 'Backup file not found.', 'aiopms' ) );
			}
			
			// Read backup content.
			$backup_content = file_get_contents( $backup_file );
			if ( false === $backup_content ) {
				throw new Exception( __( 'Failed to read backup file.', 'aiopms' ) );
			}
			
			// Decompress if needed.
			if ( substr( $backup_file, -3 ) === '.gz' ) {
				$backup_content = gzdecode( $backup_content );
				if ( false === $backup_content ) {
					throw new Exception( __( 'Failed to decompress backup file.', 'aiopms' ) );
				}
			}
			
			// Parse backup data.
			$backup_data = json_decode( $backup_content, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new Exception( __( 'Invalid backup file format.', 'aiopms' ) );
			}
			
			// Check backup version compatibility.
			if ( ! $this->is_backup_compatible( $backup_data['backup_info'] ) ) {
				throw new Exception( __( 'Backup version is not compatible with current plugin version.', 'aiopms' ) );
			}
			
			// Set default restore options.
			$default_options = array(
				'restore_settings' => true,
				'restore_pages' => true,
				'restore_schema' => true,
				'restore_logs' => false,
				'restore_cache' => false,
				'overwrite_existing' => false,
			);
			
			$options = wp_parse_args( $options, $default_options );
			
			// Restore settings if requested.
			if ( $options['restore_settings'] && isset( $backup_data['data']['settings'] ) ) {
				$this->restore_settings( $backup_data['data']['settings'], $options['overwrite_existing'] );
			}
			
			// Restore pages if requested.
			if ( $options['restore_pages'] && isset( $backup_data['data']['pages'] ) ) {
				$this->restore_pages( $backup_data['data']['pages'], $options['overwrite_existing'] );
			}
			
			// Restore schema data if requested.
			if ( $options['restore_schema'] && isset( $backup_data['data']['schema'] ) ) {
				$this->restore_schema_data( $backup_data['data']['schema'], $options['overwrite_existing'] );
			}
			
			// Restore logs if requested.
			if ( $options['restore_logs'] && isset( $backup_data['data']['logs'] ) ) {
				$this->restore_logs( $backup_data['data']['logs'], $options['overwrite_existing'] );
			}
			
			// Restore cache if requested.
			if ( $options['restore_cache'] && isset( $backup_data['data']['cache'] ) ) {
				$this->restore_cache( $backup_data['data']['cache'], $options['overwrite_existing'] );
			}
			
			// Log backup restoration.
			aiopms_log_error( 'Backup restored: ' . $backup_data['backup_info']['name'], 'Info' );
			
			return array(
				'success' => true,
				'message' => __( 'Backup restored successfully.', 'aiopms' ),
			);
			
		} catch ( Exception $e ) {
			aiopms_log_error( 'Backup restoration failed: ' . $e->getMessage(), 'Error' );
			
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}
	
	/**
	 * Delete backup.
	 * 
	 * @param string $backup_name Backup name.
	 * @return array Delete result.
	 * @since 3.0
	 */
	public function delete_backup( $backup_name ) {
		try {
			$backup_file = $this->backup_dir . $backup_name . '.json';
			$compressed_file = $backup_file . '.gz';
			
			// Try to delete both regular and compressed files.
			$deleted = false;
			if ( file_exists( $backup_file ) ) {
				$deleted = unlink( $backup_file );
			} elseif ( file_exists( $compressed_file ) ) {
				$deleted = unlink( $compressed_file );
			}
			
			if ( ! $deleted ) {
				throw new Exception( __( 'Failed to delete backup file.', 'aiopms' ) );
			}
			
			// Log backup deletion.
			aiopms_log_error( 'Backup deleted: ' . $backup_name, 'Info' );
			
			return array(
				'success' => true,
				'message' => __( 'Backup deleted successfully.', 'aiopms' ),
			);
			
		} catch ( Exception $e ) {
			aiopms_log_error( 'Backup deletion failed: ' . $e->getMessage(), 'Error' );
			
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}
	
	/**
	 * List available backups.
	 * 
	 * @return array List of backups.
	 * @since 3.0
	 */
	public function list_backups() {
		$backups = array();
		
		if ( ! is_dir( $this->backup_dir ) ) {
			return $backups;
		}
		
		$files = glob( $this->backup_dir . '*.json*' );
		
		foreach ( $files as $file ) {
			$backup_name = basename( $file, '.json' );
			$backup_name = str_replace( '.gz', '', $backup_name );
			
			$backup_info = array(
				'name' => $backup_name,
				'file' => $file,
				'size' => filesize( $file ),
				'created' => filemtime( $file ),
				'compressed' => substr( $file, -3 ) === '.gz',
			);
			
			// Try to get backup info from file.
			$backup_content = file_get_contents( $file );
			if ( $backup_content ) {
				if ( substr( $file, -3 ) === '.gz' ) {
					$backup_content = gzdecode( $backup_content );
				}
				
				$backup_data = json_decode( $backup_content, true );
				if ( $backup_data && isset( $backup_data['backup_info'] ) ) {
					$backup_info = array_merge( $backup_info, $backup_data['backup_info'] );
				}
			}
			
			$backups[] = $backup_info;
		}
		
		// Sort by creation date (newest first).
		usort( $backups, function( $a, $b ) {
			return $b['created'] - $a['created'];
		} );
		
		return $backups;
	}
	
	/**
	 * Download backup.
	 * 
	 * @param string $backup_name Backup name.
	 * @since 3.0
	 */
	public function download_backup( $backup_name ) {
		$backup_file = $this->backup_dir . $backup_name . '.json';
		$compressed_file = $backup_file . '.gz';
		
		// Determine which file to download.
		$download_file = $backup_file;
		if ( file_exists( $compressed_file ) ) {
			$download_file = $compressed_file;
		}
		
		if ( ! file_exists( $download_file ) ) {
			wp_die( __( 'Backup file not found.', 'aiopms' ) );
		}
		
		// Set headers for download.
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $download_file ) . '"' );
		header( 'Content-Length: ' . filesize( $download_file ) );
		
		// Output file content.
		readfile( $download_file );
		exit;
	}
	
	/**
	 * Cleanup old backups.
	 * 
	 * @since 3.0
	 */
	public function cleanup_old_backups() {
		$backups = $this->list_backups();
		$cutoff_time = time() - ( $this->max_backup_age * DAY_IN_SECONDS );
		
		foreach ( $backups as $backup ) {
			if ( $backup['created'] < $cutoff_time ) {
				$this->delete_backup( $backup['name'] );
			}
		}
	}
	
	/**
	 * Backup settings.
	 * 
	 * @return array Settings data.
	 * @since 3.0
	 */
	private function backup_settings() {
		$settings = array();
		
		// Get all plugin options.
		global $wpdb;
		$options = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				'aiopms_%'
			)
		);
		
		foreach ( $options as $option ) {
			$settings[ $option->option_name ] = maybe_unserialize( $option->option_value );
		}
		
		return $settings;
	}
	
	/**
	 * Restore settings.
	 * 
	 * @param array $settings Settings data.
	 * @param bool  $overwrite Whether to overwrite existing settings.
	 * @since 3.0
	 */
	private function restore_settings( $settings, $overwrite = false ) {
		foreach ( $settings as $option_name => $option_value ) {
			if ( $overwrite || false === get_option( $option_name ) ) {
				update_option( $option_name, $option_value );
			}
		}
	}
	
	/**
	 * Backup pages.
	 * 
	 * @return array Pages data.
	 * @since 3.0
	 */
	private function backup_pages() {
		$pages = array();
		
		// Get all pages created by the plugin.
		$posts = get_posts( array(
			'post_type' => 'page',
			'post_status' => 'any',
			'numberposts' => -1,
			'meta_query' => array(
				array(
					'key' => '_aiopms_created',
					'value' => '1',
					'compare' => '=',
				),
			),
		) );
		
		foreach ( $posts as $post ) {
			$pages[] = array(
				'post_data' => $post->to_array(),
				'post_meta' => get_post_meta( $post->ID ),
			);
		}
		
		return $pages;
	}
	
	/**
	 * Restore pages.
	 * 
	 * @param array $pages Pages data.
	 * @param bool  $overwrite Whether to overwrite existing pages.
	 * @since 3.0
	 */
	private function restore_pages( $pages, $overwrite = false ) {
		foreach ( $pages as $page_data ) {
			$post_data = $page_data['post_data'];
			$post_meta = $page_data['post_meta'];
			
			// Check if page already exists.
			$existing_post = get_page_by_path( $post_data['post_name'] );
			
			if ( $existing_post && ! $overwrite ) {
				continue;
			}
			
			// Remove ID to create new post.
			unset( $post_data['ID'] );
			
			// Create new post.
			$new_post_id = wp_insert_post( $post_data );
			
			if ( $new_post_id && ! is_wp_error( $new_post_id ) ) {
				// Restore post meta.
				foreach ( $post_meta as $meta_key => $meta_values ) {
					foreach ( $meta_values as $meta_value ) {
						add_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value ) );
					}
				}
			}
		}
	}
	
	/**
	 * Backup schema data.
	 * 
	 * @return array Schema data.
	 * @since 3.0
	 */
	private function backup_schema_data() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'aiopms_schema_data';
		$schema_data = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
		
		return $schema_data;
	}
	
	/**
	 * Restore schema data.
	 * 
	 * @param array $schema_data Schema data.
	 * @param bool  $overwrite Whether to overwrite existing data.
	 * @since 3.0
	 */
	private function restore_schema_data( $schema_data, $overwrite = false ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'aiopms_schema_data';
		
		if ( $overwrite ) {
			$wpdb->query( "TRUNCATE TABLE $table_name" );
		}
		
		foreach ( $schema_data as $row ) {
			$wpdb->insert( $table_name, $row );
		}
	}
	
	/**
	 * Backup logs.
	 * 
	 * @return array Logs data.
	 * @since 3.0
	 */
	private function backup_logs() {
		global $wpdb;
		
		$logs = array();
		
		// Backup error logs.
		$error_logs_table = $wpdb->prefix . 'aiopms_error_logs';
		$error_logs = $wpdb->get_results( "SELECT * FROM $error_logs_table", ARRAY_A );
		$logs['error_logs'] = $error_logs;
		
		// Backup generation logs.
		$generation_logs_table = $wpdb->prefix . 'aiopms_generation_logs';
		$generation_logs = $wpdb->get_results( "SELECT * FROM $generation_logs_table", ARRAY_A );
		$logs['generation_logs'] = $generation_logs;
		
		// Backup performance logs.
		$performance_logs_table = $wpdb->prefix . 'aiopms_performance_logs';
		$performance_logs = $wpdb->get_results( "SELECT * FROM $performance_logs_table", ARRAY_A );
		$logs['performance_logs'] = $performance_logs;
		
		return $logs;
	}
	
	/**
	 * Restore logs.
	 * 
	 * @param array $logs Logs data.
	 * @param bool  $overwrite Whether to overwrite existing data.
	 * @since 3.0
	 */
	private function restore_logs( $logs, $overwrite = false ) {
		global $wpdb;
		
		// Restore error logs.
		if ( isset( $logs['error_logs'] ) ) {
			$error_logs_table = $wpdb->prefix . 'aiopms_error_logs';
			if ( $overwrite ) {
				$wpdb->query( "TRUNCATE TABLE $error_logs_table" );
			}
			foreach ( $logs['error_logs'] as $row ) {
				$wpdb->insert( $error_logs_table, $row );
			}
		}
		
		// Restore generation logs.
		if ( isset( $logs['generation_logs'] ) ) {
			$generation_logs_table = $wpdb->prefix . 'aiopms_generation_logs';
			if ( $overwrite ) {
				$wpdb->query( "TRUNCATE TABLE $generation_logs_table" );
			}
			foreach ( $logs['generation_logs'] as $row ) {
				$wpdb->insert( $generation_logs_table, $row );
			}
		}
		
		// Restore performance logs.
		if ( isset( $logs['performance_logs'] ) ) {
			$performance_logs_table = $wpdb->prefix . 'aiopms_performance_logs';
			if ( $overwrite ) {
				$wpdb->query( "TRUNCATE TABLE $performance_logs_table" );
			}
			foreach ( $logs['performance_logs'] as $row ) {
				$wpdb->insert( $performance_logs_table, $row );
			}
		}
	}
	
	/**
	 * Backup cache.
	 * 
	 * @return array Cache data.
	 * @since 3.0
	 */
	private function backup_cache() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'aiopms_cache_data';
		$cache_data = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
		
		return $cache_data;
	}
	
	/**
	 * Restore cache.
	 * 
	 * @param array $cache_data Cache data.
	 * @param bool  $overwrite Whether to overwrite existing data.
	 * @since 3.0
	 */
	private function restore_cache( $cache_data, $overwrite = false ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'aiopms_cache_data';
		
		if ( $overwrite ) {
			$wpdb->query( "TRUNCATE TABLE $table_name" );
		}
		
		foreach ( $cache_data as $row ) {
			$wpdb->insert( $table_name, $row );
		}
	}
	
	/**
	 * Check if backup is compatible.
	 * 
	 * @param array $backup_info Backup info.
	 * @return bool
	 * @since 3.0
	 */
	private function is_backup_compatible( $backup_info ) {
		// Check WordPress version compatibility.
		$wp_version = get_bloginfo( 'version' );
		if ( version_compare( $wp_version, $backup_info['wp_version'], '<' ) ) {
			return false;
		}
		
		// Check PHP version compatibility.
		if ( version_compare( PHP_VERSION, $backup_info['php_version'], '<' ) ) {
			return false;
		}
		
		// Check plugin version compatibility.
		$current_version = AIOPMS_VERSION;
		$backup_version = $backup_info['version'];
		
		// Allow restore from same major version.
		$current_major = explode( '.', $current_version )[0];
		$backup_major = explode( '.', $backup_version )[0];
		
		return $current_major >= $backup_major;
	}
	
	/**
	 * Create backup via AJAX.
	 * 
	 * @since 3.0
	 */
	public function ajax_create_backup() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aiopms_create_backup' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		$backup_name = sanitize_text_field( $_POST['backup_name'] );
		$options = array(
			'include_settings' => isset( $_POST['include_settings'] ),
			'include_pages' => isset( $_POST['include_pages'] ),
			'include_schema' => isset( $_POST['include_schema'] ),
			'include_logs' => isset( $_POST['include_logs'] ),
			'include_cache' => isset( $_POST['include_cache'] ),
			'compress' => isset( $_POST['compress'] ),
		);
		
		$result = $this->create_backup( $backup_name, $options );
		
		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}
	
	/**
	 * Restore backup via AJAX.
	 * 
	 * @since 3.0
	 */
	public function ajax_restore_backup() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aiopms_restore_backup' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		$backup_name = sanitize_text_field( $_POST['backup_name'] );
		$backup_file = $this->backup_dir . $backup_name . '.json';
		$compressed_file = $backup_file . '.gz';
		
		// Determine which file to restore.
		$restore_file = $backup_file;
		if ( file_exists( $compressed_file ) ) {
			$restore_file = $compressed_file;
		}
		
		$options = array(
			'restore_settings' => isset( $_POST['restore_settings'] ),
			'restore_pages' => isset( $_POST['restore_pages'] ),
			'restore_schema' => isset( $_POST['restore_schema'] ),
			'restore_logs' => isset( $_POST['restore_logs'] ),
			'restore_cache' => isset( $_POST['restore_cache'] ),
			'overwrite_existing' => isset( $_POST['overwrite_existing'] ),
		);
		
		$result = $this->restore_backup( $restore_file, $options );
		
		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}
	
	/**
	 * Delete backup via AJAX.
	 * 
	 * @since 3.0
	 */
	public function ajax_delete_backup() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aiopms_delete_backup' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		$backup_name = sanitize_text_field( $_POST['backup_name'] );
		$result = $this->delete_backup( $backup_name );
		
		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}
	
	/**
	 * Download backup via AJAX.
	 * 
	 * @since 3.0
	 */
	public function ajax_download_backup() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_GET['nonce'], 'aiopms_download_backup' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		$backup_name = sanitize_text_field( $_GET['backup_name'] );
		$this->download_backup( $backup_name );
	}
	
	/**
	 * List backups via AJAX.
	 * 
	 * @since 3.0
	 */
	public function ajax_list_backups() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aiopms_list_backups' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		$backups = $this->list_backups();
		wp_send_json_success( $backups );
	}
}

/**
 * Initialize backup and restore.
 * 
 * @since 3.0
 */
function aiopms_init_backup_restore() {
	return AIOPMS_Backup_Restore::get_instance();
}

// Initialize backup and restore.
aiopms_init_backup_restore();

/**
 * Helper function to create backup.
 * 
 * @param string $backup_name Backup name.
 * @param array  $options Backup options.
 * @return array Backup result.
 * @since 3.0
 */
function aiopms_create_backup( $backup_name = '', $options = array() ) {
	$backup_restore = AIOPMS_Backup_Restore::get_instance();
	return $backup_restore->create_backup( $backup_name, $options );
}

/**
 * Helper function to restore backup.
 * 
 * @param string $backup_file Backup file path.
 * @param array  $options Restore options.
 * @return array Restore result.
 * @since 3.0
 */
function aiopms_restore_backup( $backup_file, $options = array() ) {
	$backup_restore = AIOPMS_Backup_Restore::get_instance();
	return $backup_restore->restore_backup( $backup_file, $options );
}

/**
 * Helper function to delete backup.
 * 
 * @param string $backup_name Backup name.
 * @return array Delete result.
 * @since 3.0
 */
function aiopms_delete_backup( $backup_name ) {
	$backup_restore = AIOPMS_Backup_Restore::get_instance();
	return $backup_restore->delete_backup( $backup_name );
}

/**
 * Helper function to list backups.
 * 
 * @return array List of backups.
 * @since 3.0
 */
function aiopms_list_backups() {
	$backup_restore = AIOPMS_Backup_Restore::get_instance();
	return $backup_restore->list_backups();
}
