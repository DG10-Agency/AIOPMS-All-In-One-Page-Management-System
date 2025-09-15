<?php
/**
 * Error Handling and Logging System for AIOPMS
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
 * AIOPMS Error Handler Class
 * 
 * Handles all error logging, user feedback, and debugging functionality.
 * 
 * @since 3.0
 */
class AIOPMS_Error_Handler {
	
	/**
	 * Instance of the class.
	 * 
	 * @var AIOPMS_Error_Handler
	 * @since 3.0
	 */
	private static $instance = null;
	
	/**
	 * Error log file path.
	 * 
	 * @var string
	 * @since 3.0
	 */
	private $log_file;
	
	/**
	 * Maximum log file size in bytes.
	 * 
	 * @var int
	 * @since 3.0
	 */
	private $max_log_size = 10485760; // 10MB
	
	/**
	 * Get instance of the class.
	 * 
	 * @return AIOPMS_Error_Handler
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
		$this->log_file = WP_CONTENT_DIR . '/aiopms-error.log';
		$this->init_hooks();
	}
	
	/**
	 * Initialize hooks.
	 * 
	 * @since 3.0
	 */
	private function init_hooks() {
		// Set custom error handler.
		set_error_handler( array( $this, 'handle_error' ) );
		
		// Set custom exception handler.
		set_exception_handler( array( $this, 'handle_exception' ) );
		
		// Add admin notices hook.
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		
		// Add AJAX error handling.
		add_action( 'wp_ajax_aiopms_clear_error_log', array( $this, 'clear_error_log' ) );
		add_action( 'wp_ajax_aiopms_download_error_log', array( $this, 'download_error_log' ) );
	}
	
	/**
	 * Handle PHP errors.
	 * 
	 * @param int    $errno   Error number.
	 * @param string $errstr  Error message.
	 * @param string $errfile Error file.
	 * @param int    $errline Error line.
	 * @return bool
	 * @since 3.0
	 */
	public function handle_error( $errno, $errstr, $errfile, $errline ) {
		// Only handle errors that should be reported.
		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}
		
		// Skip if error is from another plugin or theme.
		if ( false === strpos( $errfile, 'aiopms' ) ) {
			return false;
		}
		
		$error_types = array(
			E_ERROR             => 'Fatal Error',
			E_WARNING           => 'Warning',
			E_PARSE             => 'Parse Error',
			E_NOTICE            => 'Notice',
			E_CORE_ERROR        => 'Core Error',
			E_CORE_WARNING      => 'Core Warning',
			E_COMPILE_ERROR     => 'Compile Error',
			E_COMPILE_WARNING   => 'Compile Warning',
			E_USER_ERROR        => 'User Error',
			E_USER_WARNING      => 'User Warning',
			E_USER_NOTICE       => 'User Notice',
			E_STRICT            => 'Strict Notice',
			E_RECOVERABLE_ERROR => 'Recoverable Error',
			E_DEPRECATED        => 'Deprecated',
			E_USER_DEPRECATED   => 'User Deprecated',
		);
		
		$error_type = isset( $error_types[ $errno ] ) ? $error_types[ $errno ] : 'Unknown Error';
		
		$error_data = array(
			'type'      => $error_type,
			'message'   => $errstr,
			'file'      => $errfile,
			'line'      => $errline,
			'timestamp' => current_time( 'mysql' ),
			'user_id'   => get_current_user_id(),
			'url'       => $this->get_current_url(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
			'ip_address' => $this->get_client_ip(),
		);
		
		$this->log_error( $error_data );
		
		// Don't execute PHP internal error handler.
		return true;
	}
	
	/**
	 * Handle uncaught exceptions.
	 * 
	 * @param Exception $exception The exception object.
	 * @since 3.0
	 */
	public function handle_exception( $exception ) {
		$error_data = array(
			'type'      => 'Uncaught Exception',
			'message'   => $exception->getMessage(),
			'file'      => $exception->getFile(),
			'line'      => $exception->getLine(),
			'trace'     => $exception->getTraceAsString(),
			'timestamp' => current_time( 'mysql' ),
			'user_id'   => get_current_user_id(),
			'url'       => $this->get_current_url(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
			'ip_address' => $this->get_client_ip(),
		);
		
		$this->log_error( $error_data );
		
		// Display user-friendly error message.
		$this->add_admin_notice( 
			sprintf( 
				__( 'An unexpected error occurred in AIOPMS. Error details have been logged. Please contact support if this issue persists.', 'aiopms' ),
				$exception->getMessage()
			),
			'error'
		);
	}
	
	/**
	 * Log error to file and database.
	 * 
	 * @param array $error_data Error data array.
	 * @since 3.0
	 */
	public function log_error( $error_data ) {
		// Log to file.
		$this->write_to_log_file( $error_data );
		
		// Log to database.
		$this->write_to_database( $error_data );
		
		// Send email notification for critical errors.
		if ( in_array( $error_data['type'], array( 'Fatal Error', 'Core Error', 'Compile Error', 'User Error' ) ) ) {
			$this->send_error_notification( $error_data );
		}
	}
	
	/**
	 * Write error to log file.
	 * 
	 * @param array $error_data Error data array.
	 * @since 3.0
	 */
	private function write_to_log_file( $error_data ) {
		// Check if log file exists and is too large.
		if ( file_exists( $this->log_file ) && filesize( $this->log_file ) > $this->max_log_size ) {
			$this->rotate_log_file();
		}
		
		$log_entry = sprintf(
			"[%s] %s: %s in %s on line %d (User: %d, IP: %s, URL: %s)\n",
			$error_data['timestamp'],
			$error_data['type'],
			$error_data['message'],
			$error_data['file'],
			$error_data['line'],
			$error_data['user_id'],
			$error_data['ip_address'],
			$error_data['url']
		);
		
		// Add stack trace if available.
		if ( isset( $error_data['trace'] ) ) {
			$log_entry .= "Stack trace:\n" . $error_data['trace'] . "\n";
		}
		
		$log_entry .= str_repeat( '-', 80 ) . "\n";
		
		// Write to log file.
		file_put_contents( $this->log_file, $log_entry, FILE_APPEND | LOCK_EX );
	}
	
	/**
	 * Write error to database.
	 * 
	 * @param array $error_data Error data array.
	 * @since 3.0
	 */
	private function write_to_database( $error_data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'aiopms_error_logs';
		
		// Create table if it doesn't exist.
		$this->create_error_log_table();
		
		$wpdb->insert(
			$table_name,
			array(
				'error_type'    => $error_data['type'],
				'error_message' => $error_data['message'],
				'error_file'    => $error_data['file'],
				'error_line'    => $error_data['line'],
				'stack_trace'   => isset( $error_data['trace'] ) ? $error_data['trace'] : '',
				'user_id'       => $error_data['user_id'],
				'ip_address'    => $error_data['ip_address'],
				'user_agent'    => $error_data['user_agent'],
				'url'           => $error_data['url'],
				'timestamp'     => $error_data['timestamp'],
				'site_id'       => get_current_blog_id(),
			),
			array(
				'%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d'
			)
		);
	}
	
	/**
	 * Create error log table.
	 * 
	 * @since 3.0
	 */
	private function create_error_log_table() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'aiopms_error_logs';
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			error_type varchar(50) NOT NULL,
			error_message text NOT NULL,
			error_file varchar(255) NOT NULL,
			error_line int(11) NOT NULL,
			stack_trace longtext,
			user_id bigint(20) DEFAULT 0,
			ip_address varchar(45) DEFAULT '',
			user_agent text,
			url varchar(500) DEFAULT '',
			timestamp datetime DEFAULT CURRENT_TIMESTAMP,
			site_id bigint(20) DEFAULT 0,
			PRIMARY KEY (id),
			KEY error_type (error_type),
			KEY timestamp (timestamp),
			KEY site_id (site_id),
			KEY user_id (user_id)
		) $charset_collate;";
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
	
	/**
	 * Rotate log file when it gets too large.
	 * 
	 * @since 3.0
	 */
	private function rotate_log_file() {
		$backup_file = $this->log_file . '.' . date( 'Y-m-d-H-i-s' );
		rename( $this->log_file, $backup_file );
		
		// Compress old log file.
		if ( function_exists( 'gzopen' ) ) {
			$gz = gzopen( $backup_file . '.gz', 'w9' );
			if ( $gz ) {
				$file = fopen( $backup_file, 'r' );
				if ( $file ) {
					while ( ! feof( $file ) ) {
						gzwrite( $gz, fread( $file, 8192 ) );
					}
					fclose( $file );
				}
				gzclose( $gz );
				unlink( $backup_file );
			}
		}
	}
	
	/**
	 * Send error notification email.
	 * 
	 * @param array $error_data Error data array.
	 * @since 3.0
	 */
	private function send_error_notification( $error_data ) {
		$admin_email = get_option( 'admin_email' );
		$site_name = get_bloginfo( 'name' );
		
		$subject = sprintf( '[%s] AIOPMS Critical Error', $site_name );
		
		$message = sprintf(
			"A critical error occurred in AIOPMS on %s:\n\n" .
			"Error Type: %s\n" .
			"Error Message: %s\n" .
			"File: %s\n" .
			"Line: %d\n" .
			"User ID: %d\n" .
			"IP Address: %s\n" .
			"URL: %s\n" .
			"Timestamp: %s\n\n" .
			"Please check the error log for more details.",
			$site_name,
			$error_data['type'],
			$error_data['message'],
			$error_data['file'],
			$error_data['line'],
			$error_data['user_id'],
			$error_data['ip_address'],
			$error_data['url'],
			$error_data['timestamp']
		);
		
		wp_mail( $admin_email, $subject, $message );
	}
	
	/**
	 * Add admin notice.
	 * 
	 * @param string $message Notice message.
	 * @param string $type    Notice type (success, error, warning, info).
	 * @since 3.0
	 */
	public function add_admin_notice( $message, $type = 'info' ) {
		$notices = get_option( 'aiopms_admin_notices', array() );
		$notices[] = array(
			'message' => $message,
			'type'    => $type,
		);
		update_option( 'aiopms_admin_notices', $notices );
	}
	
	/**
	 * Display admin notices.
	 * 
	 * @since 3.0
	 */
	public function display_admin_notices() {
		$notices = get_option( 'aiopms_admin_notices', array() );
		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice ) {
				printf(
					'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
					esc_attr( $notice['type'] ),
					esc_html( $notice['message'] )
				);
			}
			delete_option( 'aiopms_admin_notices' );
		}
	}
	
	/**
	 * Get current URL.
	 * 
	 * @return string
	 * @since 3.0
	 */
	private function get_current_url() {
		$protocol = is_ssl() ? 'https://' : 'http://';
		return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}
	
	/**
	 * Get client IP address.
	 * 
	 * @return string
	 * @since 3.0
	 */
	private function get_client_ip() {
		$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}
		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
	}
	
	/**
	 * Clear error log.
	 * 
	 * @since 3.0
	 */
	public function clear_error_log() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aiopms_clear_error_log' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		// Clear file log.
		if ( file_exists( $this->log_file ) ) {
			unlink( $this->log_file );
		}
		
		// Clear database log.
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiopms_error_logs';
		$wpdb->query( "TRUNCATE TABLE $table_name" );
		
		wp_send_json_success( __( 'Error log cleared successfully.', 'aiopms' ) );
	}
	
	/**
	 * Download error log.
	 * 
	 * @since 3.0
	 */
	public function download_error_log() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_GET['nonce'], 'aiopms_download_error_log' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		// Generate log content.
		$log_content = $this->generate_log_content();
		
		// Set headers for download.
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="aiopms-error-log-' . date( 'Y-m-d-H-i-s' ) . '.txt"' );
		header( 'Content-Length: ' . strlen( $log_content ) );
		
		echo $log_content;
		exit;
	}
	
	/**
	 * Generate log content for download.
	 * 
	 * @return string
	 * @since 3.0
	 */
	private function generate_log_content() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'aiopms_error_logs';
		$errors = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 1000" );
		
		$log_content = "AIOPMS Error Log\n";
		$log_content .= "Generated: " . current_time( 'mysql' ) . "\n";
		$log_content .= "Site: " . get_bloginfo( 'name' ) . " (" . get_bloginfo( 'url' ) . ")\n";
		$log_content .= str_repeat( '=', 80 ) . "\n\n";
		
		foreach ( $errors as $error ) {
			$log_content .= sprintf(
				"[%s] %s: %s in %s on line %d (User: %d, IP: %s)\n",
				$error->timestamp,
				$error->error_type,
				$error->error_message,
				$error->error_file,
				$error->error_line,
				$error->user_id,
				$error->ip_address
			);
			
			if ( ! empty( $error->stack_trace ) ) {
				$log_content .= "Stack trace:\n" . $error->stack_trace . "\n";
			}
			
			$log_content .= str_repeat( '-', 80 ) . "\n";
		}
		
		return $log_content;
	}
}

/**
 * Initialize error handler.
 * 
 * @since 3.0
 */
function aiopms_init_error_handler() {
	return AIOPMS_Error_Handler::get_instance();
}

// Initialize error handler.
aiopms_init_error_handler();

/**
 * Helper function to log errors.
 * 
 * @param string $message Error message.
 * @param string $type    Error type.
 * @param array  $context Additional context data.
 * @since 3.0
 */
function aiopms_log_error( $message, $type = 'Error', $context = array() ) {
	$error_handler = AIOPMS_Error_Handler::get_instance();
	
		$error_data = array(
			'type'      => $type,
			'message'   => $message,
			'file'      => debug_backtrace()[0]['file'],
			'line'      => debug_backtrace()[0]['line'],
			'timestamp' => current_time( 'mysql' ),
			'user_id'   => get_current_user_id(),
			'url'       => aiopms_get_current_url(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
			'ip_address' => aiopms_get_client_ip(),
		);
	
	// Add context data.
	if ( ! empty( $context ) ) {
		$error_data['context'] = $context;
	}
	
	$error_handler->log_error( $error_data );
}

/**
 * Helper function to add admin notices.
 * 
 * @param string $message Notice message.
 * @param string $type    Notice type.
 * @since 3.0
 */
function aiopms_add_notice( $message, $type = 'info' ) {
	$error_handler = AIOPMS_Error_Handler::get_instance();
	$error_handler->add_admin_notice( $message, $type );
}

/**
 * Helper function to get current URL.
 * 
 * @return string Current URL.
 * @since 3.0
 */
function aiopms_get_current_url() {
	$protocol = is_ssl() ? 'https://' : 'http://';
	return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Helper function to get client IP address.
 * 
 * @return string Client IP address.
 * @since 3.0
 */
function aiopms_get_client_ip() {
	$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
	foreach ( $ip_keys as $key ) {
		if ( array_key_exists( $key, $_SERVER ) === true ) {
			foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
				$ip = trim( $ip );
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
					return $ip;
				}
			}
		}
	}
	return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}
