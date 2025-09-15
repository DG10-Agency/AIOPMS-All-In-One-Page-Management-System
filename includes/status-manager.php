<?php
/**
 * Status Management System for AIOPMS
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
 * AIOPMS Status Manager Class
 * 
 * Handles status management, operation states, and system health monitoring.
 * 
 * @since 3.0
 */
class AIOPMS_Status_Manager {
	
	/**
	 * Instance of the class.
	 * 
	 * @var AIOPMS_Status_Manager
	 * @since 3.0
	 */
	private static $instance = null;
	
	/**
	 * Status data storage.
	 * 
	 * @var array
	 * @since 3.0
	 */
	private $status_data = array();
	
	/**
	 * Get instance of the class.
	 * 
	 * @return AIOPMS_Status_Manager
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
		$this->init_hooks();
		$this->load_status_data();
	}
	
	/**
	 * Initialize hooks.
	 * 
	 * @since 3.0
	 */
	private function init_hooks() {
		// Add AJAX handlers.
		add_action( 'wp_ajax_aiopms_get_system_status', array( $this, 'ajax_get_system_status' ) );
		add_action( 'wp_ajax_aiopms_update_operation_status', array( $this, 'update_operation_status' ) );
		add_action( 'wp_ajax_aiopms_get_operation_status', array( $this, 'ajax_get_operation_status' ) );
		
		// Add cleanup hooks.
		add_action( 'aiopms_cleanup_status_data', array( $this, 'cleanup_old_status_data' ) );
		
		// Schedule cleanup if not already scheduled.
		if ( ! wp_next_scheduled( 'aiopms_cleanup_status_data' ) ) {
			wp_schedule_event( time(), 'daily', 'aiopms_cleanup_status_data' );
		}
	}
	
	/**
	 * Load status data from database.
	 * 
	 * @since 3.0
	 */
	private function load_status_data() {
		$this->status_data = get_option( 'aiopms_status_data', array() );
	}
	
	/**
	 * Save status data to database.
	 * 
	 * @since 3.0
	 */
	private function save_status_data() {
		update_option( 'aiopms_status_data', $this->status_data );
	}
	
	/**
	 * Set operation status.
	 * 
	 * @param string $operation_id Operation ID.
	 * @param string $status Operation status.
	 * @param array  $data Additional status data.
	 * @since 3.0
	 */
	public function set_operation_status( $operation_id, $status, $data = array() ) {
		$this->status_data['operations'][ $operation_id ] = array(
			'status' => $status,
			'timestamp' => current_time( 'mysql' ),
			'data' => $data,
		);
		
		$this->save_status_data();
		
		// Log status change.
		aiopms_log_error( "Operation status changed: {$operation_id} -> {$status}", 'Info', $data );
	}
	
	/**
	 * Get operation status.
	 * 
	 * @param string $operation_id Operation ID.
	 * @return array Operation status data.
	 * @since 3.0
	 */
	public function get_operation_status( $operation_id ) {
		return isset( $this->status_data['operations'][ $operation_id ] ) ? 
			$this->status_data['operations'][ $operation_id ] : 
			array( 'status' => 'unknown', 'timestamp' => null, 'data' => array() );
	}
	
	/**
	 * Check if operation is running.
	 * 
	 * @param string $operation_id Operation ID.
	 * @return bool
	 * @since 3.0
	 */
	public function is_operation_running( $operation_id ) {
		$status = $this->get_operation_status( $operation_id );
		return $status['status'] === 'running';
	}
	
	/**
	 * Check if operation is completed.
	 * 
	 * @param string $operation_id Operation ID.
	 * @return bool
	 * @since 3.0
	 */
	public function is_operation_completed( $operation_id ) {
		$status = $this->get_operation_status( $operation_id );
		return in_array( $status['status'], array( 'completed', 'error', 'cancelled' ) );
	}
	
	/**
	 * Get all running operations.
	 * 
	 * @return array Running operations.
	 * @since 3.0
	 */
	public function get_running_operations() {
		$running = array();
		
		if ( isset( $this->status_data['operations'] ) ) {
			foreach ( $this->status_data['operations'] as $operation_id => $status_data ) {
				if ( $status_data['status'] === 'running' ) {
					$running[ $operation_id ] = $status_data;
				}
			}
		}
		
		return $running;
	}
	
	/**
	 * Set system status.
	 * 
	 * @param string $component System component.
	 * @param string $status Component status.
	 * @param array  $data Additional status data.
	 * @since 3.0
	 */
	public function set_system_status( $component, $status, $data = array() ) {
		$this->status_data['system'][ $component ] = array(
			'status' => $status,
			'timestamp' => current_time( 'mysql' ),
			'data' => $data,
		);
		
		$this->save_status_data();
	}
	
	/**
	 * Get system status.
	 * 
	 * @param string $component System component.
	 * @return array Component status data.
	 * @since 3.0
	 */
	public function get_system_status( $component = null ) {
		if ( $component ) {
			return isset( $this->status_data['system'][ $component ] ) ? 
				$this->status_data['system'][ $component ] : 
				array( 'status' => 'unknown', 'timestamp' => null, 'data' => array() );
		}
		
		return isset( $this->status_data['system'] ) ? $this->status_data['system'] : array();
	}
	
	/**
	 * Check system health.
	 * 
	 * @return array System health data.
	 * @since 3.0
	 */
	public function check_system_health() {
		$health = array(
			'overall' => 'healthy',
			'components' => array(),
			'issues' => array(),
		);
		
		// Check WordPress environment.
		$wp_status = $this->check_wordpress_environment();
		$health['components']['wordpress'] = $wp_status;
		if ( $wp_status['status'] !== 'healthy' ) {
			$health['issues'][] = $wp_status['message'];
		}
		
		// Check PHP environment.
		$php_status = $this->check_php_environment();
		$health['components']['php'] = $php_status;
		if ( $php_status['status'] !== 'healthy' ) {
			$health['issues'][] = $php_status['message'];
		}
		
		// Check database connectivity.
		$db_status = $this->check_database_connectivity();
		$health['components']['database'] = $db_status;
		if ( $db_status['status'] !== 'healthy' ) {
			$health['issues'][] = $db_status['message'];
		}
		
		// Check plugin tables.
		$tables_status = $this->check_plugin_tables();
		$health['components']['tables'] = $tables_status;
		if ( $tables_status['status'] !== 'healthy' ) {
			$health['issues'][] = $tables_status['message'];
		}
		
		// Check file permissions.
		$permissions_status = $this->check_file_permissions();
		$health['components']['permissions'] = $permissions_status;
		if ( $permissions_status['status'] !== 'healthy' ) {
			$health['issues'][] = $permissions_status['message'];
		}
		
		// Check memory usage.
		$memory_status = $this->check_memory_usage();
		$health['components']['memory'] = $memory_status;
		if ( $memory_status['status'] !== 'healthy' ) {
			$health['issues'][] = $memory_status['message'];
		}
		
		// Determine overall health.
		$unhealthy_components = array_filter( $health['components'], function( $component ) {
			return $component['status'] !== 'healthy';
		} );
		
		if ( count( $unhealthy_components ) > 0 ) {
			$health['overall'] = count( $unhealthy_components ) > 2 ? 'critical' : 'warning';
		}
		
		// Update system status.
		$this->set_system_status( 'health', $health['overall'], $health );
		
		return $health;
	}
	
	/**
	 * Check WordPress environment.
	 * 
	 * @return array WordPress status.
	 * @since 3.0
	 */
	private function check_wordpress_environment() {
		global $wp_version;
		
		$status = array(
			'status' => 'healthy',
			'message' => '',
			'data' => array(),
		);
		
		// Check WordPress version.
		if ( version_compare( $wp_version, AIOPMS_MIN_WP_VERSION, '<' ) ) {
			$status['status'] = 'warning';
			$status['message'] = sprintf( 
				__( 'WordPress version %s is below recommended version %s', 'aiopms' ),
				$wp_version,
				AIOPMS_MIN_WP_VERSION
			);
		}
		
		$status['data']['version'] = $wp_version;
		$status['data']['min_required'] = AIOPMS_MIN_WP_VERSION;
		
		return $status;
	}
	
	/**
	 * Check PHP environment.
	 * 
	 * @return array PHP status.
	 * @since 3.0
	 */
	private function check_php_environment() {
		$status = array(
			'status' => 'healthy',
			'message' => '',
			'data' => array(),
		);
		
		// Check PHP version.
		if ( version_compare( PHP_VERSION, AIOPMS_MIN_PHP_VERSION, '<' ) ) {
			$status['status'] = 'critical';
			$status['message'] = sprintf( 
				__( 'PHP version %s is below minimum required version %s', 'aiopms' ),
				PHP_VERSION,
				AIOPMS_MIN_PHP_VERSION
			);
		}
		
		// Check required PHP extensions.
		$required_extensions = array( 'json', 'curl', 'mbstring', 'xml' );
		$missing_extensions = array();
		
		foreach ( $required_extensions as $extension ) {
			if ( ! extension_loaded( $extension ) ) {
				$missing_extensions[] = $extension;
			}
		}
		
		if ( ! empty( $missing_extensions ) ) {
			$status['status'] = 'critical';
			$status['message'] = sprintf( 
				__( 'Missing required PHP extensions: %s', 'aiopms' ),
				implode( ', ', $missing_extensions )
			);
		}
		
		$status['data']['version'] = PHP_VERSION;
		$status['data']['min_required'] = AIOPMS_MIN_PHP_VERSION;
		$status['data']['extensions'] = $required_extensions;
		$status['data']['missing_extensions'] = $missing_extensions;
		
		return $status;
	}
	
	/**
	 * Check database connectivity.
	 * 
	 * @return array Database status.
	 * @since 3.0
	 */
	private function check_database_connectivity() {
		global $wpdb;
		
		$status = array(
			'status' => 'healthy',
			'message' => '',
			'data' => array(),
		);
		
		// Test database connection.
		$result = $wpdb->get_var( 'SELECT 1' );
		
		if ( $result !== '1' ) {
			$status['status'] = 'critical';
			$status['message'] = __( 'Database connection failed', 'aiopms' );
		}
		
		$status['data']['connection'] = $result === '1' ? 'ok' : 'failed';
		$status['data']['last_error'] = $wpdb->last_error;
		
		return $status;
	}
	
	/**
	 * Check plugin tables.
	 * 
	 * @return array Tables status.
	 * @since 3.0
	 */
	private function check_plugin_tables() {
		global $wpdb;
		
		$status = array(
			'status' => 'healthy',
			'message' => '',
			'data' => array(),
		);
		
		$required_tables = array(
			$wpdb->prefix . 'aiopms_generation_logs',
			$wpdb->prefix . 'aiopms_schema_data',
			$wpdb->prefix . 'aiopms_performance_logs',
			$wpdb->prefix . 'aiopms_cache_data',
			$wpdb->prefix . 'aiopms_cpt_meta',
			$wpdb->prefix . 'aiopms_error_logs',
		);
		
		$missing_tables = array();
		
		foreach ( $required_tables as $table ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 
				"SHOW TABLES LIKE %s", 
				$table 
			) );
			
			if ( ! $exists ) {
				$missing_tables[] = $table;
			}
		}
		
		if ( ! empty( $missing_tables ) ) {
			$status['status'] = 'warning';
			$status['message'] = sprintf( 
				__( 'Missing plugin tables: %s', 'aiopms' ),
				implode( ', ', $missing_tables )
			);
		}
		
		$status['data']['required_tables'] = $required_tables;
		$status['data']['missing_tables'] = $missing_tables;
		
		return $status;
	}
	
	/**
	 * Check file permissions.
	 * 
	 * @return array Permissions status.
	 * @since 3.0
	 */
	private function check_file_permissions() {
		$status = array(
			'status' => 'healthy',
			'message' => '',
			'data' => array(),
		);
		
		$upload_dir = wp_upload_dir();
		$backup_dir = $upload_dir['basedir'] . '/aiopms-backups/';
		$temp_dir = $upload_dir['basedir'] . '/aiopms/temp/';
		
		$directories = array(
			$upload_dir['basedir'] => 'uploads',
			$backup_dir => 'backups',
			$temp_dir => 'temp',
		);
		
		$permission_issues = array();
		
		foreach ( $directories as $dir => $name ) {
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
			
			if ( ! is_writable( $dir ) ) {
				$permission_issues[] = $name;
			}
		}
		
		if ( ! empty( $permission_issues ) ) {
			$status['status'] = 'warning';
			$status['message'] = sprintf( 
				__( 'Directory permission issues: %s', 'aiopms' ),
				implode( ', ', $permission_issues )
			);
		}
		
		$status['data']['directories'] = $directories;
		$status['data']['permission_issues'] = $permission_issues;
		
		return $status;
	}
	
	/**
	 * Check memory usage.
	 * 
	 * @return array Memory status.
	 * @since 3.0
	 */
	private function check_memory_usage() {
		$status = array(
			'status' => 'healthy',
			'message' => '',
			'data' => array(),
		);
		
		$memory_limit = ini_get( 'memory_limit' );
		$current_usage = memory_get_usage( true );
		$peak_usage = memory_get_peak_usage( true );
		
		$limit_bytes = aiopms_convert_memory_limit_to_bytes( $memory_limit );
		$usage_percentage = $limit_bytes > 0 ? ( $current_usage / $limit_bytes ) * 100 : 0;
		
		if ( $usage_percentage > 90 ) {
			$status['status'] = 'critical';
			$status['message'] = sprintf( 
				__( 'Memory usage is at %d%% of limit', 'aiopms' ),
				round( $usage_percentage )
			);
		} elseif ( $usage_percentage > 75 ) {
			$status['status'] = 'warning';
			$status['message'] = sprintf( 
				__( 'Memory usage is at %d%% of limit', 'aiopms' ),
				round( $usage_percentage )
			);
		}
		
		$status['data']['limit'] = $memory_limit;
		$status['data']['current_usage'] = $current_usage;
		$status['data']['peak_usage'] = $peak_usage;
		$status['data']['usage_percentage'] = $usage_percentage;
		
		return $status;
	}
	
	/**
	 * Cleanup old status data.
	 * 
	 * @since 3.0
	 */
	public function cleanup_old_status_data() {
		$cutoff_time = time() - ( 7 * DAY_IN_SECONDS ); // 7 days ago
		
		if ( isset( $this->status_data['operations'] ) ) {
			foreach ( $this->status_data['operations'] as $operation_id => $status_data ) {
				$timestamp = strtotime( $status_data['timestamp'] );
				if ( $timestamp < $cutoff_time ) {
					unset( $this->status_data['operations'][ $operation_id ] );
				}
			}
		}
		
		$this->save_status_data();
	}
	
	/**
	 * Get system status via AJAX.
	 * 
	 * @since 3.0
	 */
	public function ajax_get_system_status() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aiopms_get_system_status' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		$health = $this->check_system_health();
		$running_operations = $this->get_running_operations();
		
		$response = array(
			'health' => $health,
			'running_operations' => $running_operations,
			'timestamp' => current_time( 'mysql' ),
		);
		
		wp_send_json_success( $response );
	}
	
	/**
	 * Update operation status via AJAX.
	 * 
	 * @since 3.0
	 */
	public function update_operation_status() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aiopms_update_operation_status' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		$operation_id = sanitize_text_field( $_POST['operation_id'] );
		$status = sanitize_text_field( $_POST['status'] );
		$data = isset( $_POST['data'] ) ? json_decode( stripslashes( $_POST['data'] ), true ) : array();
		
		$this->set_operation_status( $operation_id, $status, $data );
		
		wp_send_json_success( array( 'message' => __( 'Status updated successfully.', 'aiopms' ) ) );
	}
	
	/**
	 * Get operation status via AJAX.
	 * 
	 * @since 3.0
	 */
	public function ajax_get_operation_status() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aiopms_get_operation_status' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		$operation_id = sanitize_text_field( $_POST['operation_id'] );
		$status = $this->get_operation_status( $operation_id );
		
		wp_send_json_success( $status );
	}
}

/**
 * Initialize status manager.
 * 
 * @since 3.0
 */
function aiopms_init_status_manager() {
	return AIOPMS_Status_Manager::get_instance();
}

// Initialize status manager.
aiopms_init_status_manager();

/**
 * Helper function to set operation status.
 * 
 * @param string $operation_id Operation ID.
 * @param string $status Operation status.
 * @param array  $data Additional status data.
 * @since 3.0
 */
function aiopms_set_operation_status( $operation_id, $status, $data = array() ) {
	$status_manager = AIOPMS_Status_Manager::get_instance();
	$status_manager->set_operation_status( $operation_id, $status, $data );
}

/**
 * Helper function to get operation status.
 * 
 * @param string $operation_id Operation ID.
 * @return array Operation status data.
 * @since 3.0
 */
function aiopms_get_operation_status( $operation_id ) {
	$status_manager = AIOPMS_Status_Manager::get_instance();
	return $status_manager->get_operation_status( $operation_id );
}

/**
 * Helper function to check if operation is running.
 * 
 * @param string $operation_id Operation ID.
 * @return bool
 * @since 3.0
 */
function aiopms_is_operation_running( $operation_id ) {
	$status_manager = AIOPMS_Status_Manager::get_instance();
	return $status_manager->is_operation_running( $operation_id );
}

/**
 * Helper function to check system health.
 * 
 * @return array System health data.
 * @since 3.0
 */
function aiopms_check_system_health() {
	$status_manager = AIOPMS_Status_Manager::get_instance();
	return $status_manager->check_system_health();
}
