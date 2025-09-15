<?php
/**
 * User Feedback and Progress Indicator System for AIOPMS
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
 * AIOPMS User Feedback Class
 * 
 * Handles user feedback, progress indicators, and notifications.
 * 
 * @since 3.0
 */
class AIOPMS_User_Feedback {
	
	/**
	 * Instance of the class.
	 * 
	 * @var AIOPMS_User_Feedback
	 * @since 3.0
	 */
	private static $instance = null;
	
	/**
	 * Progress tracking data.
	 * 
	 * @var array
	 * @since 3.0
	 */
	private $progress_data = array();
	
	/**
	 * Get instance of the class.
	 * 
	 * @return AIOPMS_User_Feedback
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
	}
	
	/**
	 * Initialize hooks.
	 * 
	 * @since 3.0
	 */
	private function init_hooks() {
		// Add AJAX handlers.
		add_action( 'wp_ajax_aiopms_get_progress', array( $this, 'get_progress' ) );
		add_action( 'wp_ajax_aiopms_cancel_operation', array( $this, 'cancel_operation' ) );
		add_action( 'wp_ajax_aiopms_dismiss_notice', array( $this, 'dismiss_notice' ) );
		
		// Add admin footer scripts.
		add_action( 'admin_footer', array( $this, 'add_feedback_scripts' ) );
	}
	
	/**
	 * Start progress tracking.
	 * 
	 * @param string $operation_id Operation ID.
	 * @param string $operation_name Operation name.
	 * @param int    $total_steps Total number of steps.
	 * @since 3.0
	 */
	public function start_progress( $operation_id, $operation_name, $total_steps = 0 ) {
		$this->progress_data[ $operation_id ] = array(
			'operation_name' => $operation_name,
			'total_steps'    => $total_steps,
			'current_step'   => 0,
			'status'         => 'running',
			'start_time'     => time(),
			'last_update'    => time(),
			'messages'       => array(),
			'errors'         => array(),
		);
		
		// Store in database for persistence.
		update_option( 'aiopms_progress_' . $operation_id, $this->progress_data[ $operation_id ] );
	}
	
	/**
	 * Update progress.
	 * 
	 * @param string $operation_id Operation ID.
	 * @param int    $current_step Current step.
	 * @param string $message Progress message.
	 * @param string $status Status (running, completed, error, cancelled).
	 * @since 3.0
	 */
	public function update_progress( $operation_id, $current_step, $message = '', $status = 'running' ) {
		if ( ! isset( $this->progress_data[ $operation_id ] ) ) {
			return;
		}
		
		$this->progress_data[ $operation_id ]['current_step'] = $current_step;
		$this->progress_data[ $operation_id ]['status'] = $status;
		$this->progress_data[ $operation_id ]['last_update'] = time();
		
		if ( ! empty( $message ) ) {
			$this->progress_data[ $operation_id ]['messages'][] = array(
				'timestamp' => time(),
				'message'   => $message,
			);
		}
		
		// Store in database for persistence.
		update_option( 'aiopms_progress_' . $operation_id, $this->progress_data[ $operation_id ] );
	}
	
	/**
	 * Add error to progress.
	 * 
	 * @param string $operation_id Operation ID.
	 * @param string $error_message Error message.
	 * @since 3.0
	 */
	public function add_progress_error( $operation_id, $error_message ) {
		if ( ! isset( $this->progress_data[ $operation_id ] ) ) {
			return;
		}
		
		$this->progress_data[ $operation_id ]['errors'][] = array(
			'timestamp' => time(),
			'message'   => $error_message,
		);
		
		// Store in database for persistence.
		update_option( 'aiopms_progress_' . $operation_id, $this->progress_data[ $operation_id ] );
	}
	
	/**
	 * Complete progress.
	 * 
	 * @param string $operation_id Operation ID.
	 * @param string $message Completion message.
	 * @since 3.0
	 */
	public function complete_progress( $operation_id, $message = '' ) {
		if ( ! isset( $this->progress_data[ $operation_id ] ) ) {
			return;
		}
		
		$this->progress_data[ $operation_id ]['status'] = 'completed';
		$this->progress_data[ $operation_id ]['last_update'] = time();
		
		if ( ! empty( $message ) ) {
			$this->progress_data[ $operation_id ]['messages'][] = array(
				'timestamp' => time(),
				'message'   => $message,
			);
		}
		
		// Store in database for persistence.
		update_option( 'aiopms_progress_' . $operation_id, $this->progress_data[ $operation_id ] );
	}
	
	/**
	 * Cancel progress.
	 * 
	 * @param string $operation_id Operation ID.
	 * @param string $message Cancellation message.
	 * @since 3.0
	 */
	public function cancel_progress( $operation_id, $message = '' ) {
		if ( ! isset( $this->progress_data[ $operation_id ] ) ) {
			return;
		}
		
		$this->progress_data[ $operation_id ]['status'] = 'cancelled';
		$this->progress_data[ $operation_id ]['last_update'] = time();
		
		if ( ! empty( $message ) ) {
			$this->progress_data[ $operation_id ]['messages'][] = array(
				'timestamp' => time(),
				'message'   => $message,
			);
		}
		
		// Store in database for persistence.
		update_option( 'aiopms_progress_' . $operation_id, $this->progress_data[ $operation_id ] );
	}
	
	/**
	 * Get progress data.
	 * 
	 * @param string $operation_id Operation ID.
	 * @return array Progress data.
	 * @since 3.0
	 */
	public function get_progress_data( $operation_id ) {
		if ( isset( $this->progress_data[ $operation_id ] ) ) {
			return $this->progress_data[ $operation_id ];
		}
		
		// Try to get from database.
		$progress_data = get_option( 'aiopms_progress_' . $operation_id, null );
		if ( $progress_data ) {
			$this->progress_data[ $operation_id ] = $progress_data;
			return $progress_data;
		}
		
		return null;
	}
	
	/**
	 * Get progress percentage.
	 * 
	 * @param string $operation_id Operation ID.
	 * @return int Progress percentage.
	 * @since 3.0
	 */
	public function get_progress_percentage( $operation_id ) {
		$progress_data = $this->get_progress_data( $operation_id );
		if ( ! $progress_data || $progress_data['total_steps'] <= 0 ) {
			return 0;
		}
		
		return min( 100, round( ( $progress_data['current_step'] / $progress_data['total_steps'] ) * 100 ) );
	}
	
	/**
	 * Get progress status.
	 * 
	 * @param string $operation_id Operation ID.
	 * @return string Progress status.
	 * @since 3.0
	 */
	public function get_progress_status( $operation_id ) {
		$progress_data = $this->get_progress_data( $operation_id );
		return $progress_data ? $progress_data['status'] : 'unknown';
	}
	
	/**
	 * Check if operation is running.
	 * 
	 * @param string $operation_id Operation ID.
	 * @return bool
	 * @since 3.0
	 */
	public function is_operation_running( $operation_id ) {
		return $this->get_progress_status( $operation_id ) === 'running';
	}
	
	/**
	 * Check if operation is completed.
	 * 
	 * @param string $operation_id Operation ID.
	 * @return bool
	 * @since 3.0
	 */
	public function is_operation_completed( $operation_id ) {
		return $this->get_progress_status( $operation_id ) === 'completed';
	}
	
	/**
	 * Check if operation has errors.
	 * 
	 * @param string $operation_id Operation ID.
	 * @return bool
	 * @since 3.0
	 */
	public function has_operation_errors( $operation_id ) {
		$progress_data = $this->get_progress_data( $operation_id );
		return $progress_data && ! empty( $progress_data['errors'] );
	}
	
	/**
	 * Get operation errors.
	 * 
	 * @param string $operation_id Operation ID.
	 * @return array Operation errors.
	 * @since 3.0
	 */
	public function get_operation_errors( $operation_id ) {
		$progress_data = $this->get_progress_data( $operation_id );
		return $progress_data ? $progress_data['errors'] : array();
	}
	
	/**
	 * Get operation messages.
	 * 
	 * @param string $operation_id Operation ID.
	 * @return array Operation messages.
	 * @since 3.0
	 */
	public function get_operation_messages( $operation_id ) {
		$progress_data = $this->get_progress_data( $operation_id );
		return $progress_data ? $progress_data['messages'] : array();
	}
	
	/**
	 * Get operation duration.
	 * 
	 * @param string $operation_id Operation ID.
	 * @return int Operation duration in seconds.
	 * @since 3.0
	 */
	public function get_operation_duration( $operation_id ) {
		$progress_data = $this->get_progress_data( $operation_id );
		if ( ! $progress_data ) {
			return 0;
		}
		
		$end_time = $progress_data['last_update'];
		$start_time = $progress_data['start_time'];
		
		return $end_time - $start_time;
	}
	
	/**
	 * Clean up old progress data.
	 * 
	 * @param int $max_age Maximum age in seconds.
	 * @since 3.0
	 */
	public function cleanup_old_progress( $max_age = 86400 ) { // 24 hours
		global $wpdb;
		
		$cutoff_time = time() - $max_age;
		
		// Get all progress options.
		$options = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				'aiopms_progress_%'
			)
		);
		
		foreach ( $options as $option ) {
			$progress_data = get_option( $option->option_name );
			if ( $progress_data && $progress_data['last_update'] < $cutoff_time ) {
				delete_option( $option->option_name );
			}
		}
	}
	
	/**
	 * Display progress modal with existing loader.png integration.
	 * 
	 * @param string $operation_id Operation ID.
	 * @param string $title Modal title.
	 * @param string $loading_type Type of loading (ai_generation, page_creation, csv_import, etc.).
	 * @since 3.0
	 */
	public function display_progress_modal( $operation_id, $title = '', $loading_type = 'default' ) {
		if ( empty( $title ) ) {
			$title = __( 'Operation in Progress', 'aiopms' );
		}
		
		// Get loading messages based on type
		$loading_messages = $this->get_loading_messages( $loading_type );
		
		?>
		<div id="aiopms-progress-modal" class="aiopms-modal dg10-loading-overlay" style="display: none;">
			<div class="aiopms-modal-content dg10-loading-content">
				<div class="aiopms-modal-header">
					<h3 class="dg10-loading-title"><?php echo esc_html( $title ); ?></h3>
					<button type="button" class="aiopms-modal-close dg10-btn-close" aria-label="<?php esc_attr_e( 'Close', 'aiopms' ); ?>">&times;</button>
				</div>
				<div class="aiopms-modal-body">
					<!-- Enhanced loader with existing loader.png -->
					<div class="aiopms-progress-container">
						<div class="dg10-loading-spinner-enhanced">
							<img src="<?php echo AIOPMS_PLUGIN_URL; ?>assets/images/loader.png" alt="<?php esc_attr_e( 'Loading...', 'aiopms' ); ?>" class="dg10-loader-image">
						</div>
						<div class="aiopms-progress-bar dg10-progress-bar">
							<div class="aiopms-progress-fill dg10-progress-fill" style="width: 0%;"></div>
						</div>
						<div class="aiopms-progress-text dg10-loading-message">
							<span class="aiopms-progress-percentage">0%</span>
							<span class="aiopms-progress-message"><?php echo esc_html( $loading_messages['starting'] ); ?></span>
						</div>
					</div>
					<div class="aiopms-progress-messages dg10-progress-messages">
						<div class="aiopms-progress-messages-list"></div>
					</div>
					<div class="aiopms-progress-errors dg10-progress-errors" style="display: none;">
						<h4><?php esc_html_e( 'Errors:', 'aiopms' ); ?></h4>
						<div class="aiopms-progress-errors-list"></div>
					</div>
				</div>
				<div class="aiopms-modal-footer dg10-modal-footer">
					<button type="button" class="button button-secondary aiopms-cancel-operation dg10-btn dg10-btn-outline" data-operation-id="<?php echo esc_attr( $operation_id ); ?>">
						<?php esc_html_e( 'Cancel', 'aiopms' ); ?>
					</button>
					<button type="button" class="button button-primary aiopms-close-modal dg10-btn dg10-btn-primary" style="display: none;">
						<?php esc_html_e( 'Close', 'aiopms' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Get loading messages based on operation type.
	 * 
	 * @param string $loading_type Type of loading operation.
	 * @return array Loading messages.
	 * @since 3.0
	 */
	private function get_loading_messages( $loading_type ) {
		$messages = array(
			'default' => array(
				'starting' => __( 'Starting operation...', 'aiopms' ),
				'processing' => __( 'Processing your request...', 'aiopms' ),
				'completing' => __( 'Finalizing...', 'aiopms' ),
			),
			'ai_generation' => array(
				'starting' => __( '🤖 Analyzing your business with AI...', 'aiopms' ),
				'processing' => __( 'Crafting the perfect page structure for your needs...', 'aiopms' ),
				'completing' => __( 'Generating AI-powered content...', 'aiopms' ),
			),
			'page_creation' => array(
				'starting' => __( '📝 Creating pages...', 'aiopms' ),
				'processing' => __( 'Setting up page hierarchy and content...', 'aiopms' ),
				'completing' => __( 'Finalizing page structure...', 'aiopms' ),
			),
			'csv_import' => array(
				'starting' => __( '📊 Processing CSV file...', 'aiopms' ),
				'processing' => __( 'Importing pages from CSV data...', 'aiopms' ),
				'completing' => __( 'Validating imported data...', 'aiopms' ),
			),
			'schema_generation' => array(
				'starting' => __( '🏷️ Generating schema markup...', 'aiopms' ),
				'processing' => __( 'Analyzing content for structured data...', 'aiopms' ),
				'completing' => __( 'Applying schema markup...', 'aiopms' ),
			),
			'menu_generation' => array(
				'starting' => __( '🍔 Creating navigation menus...', 'aiopms' ),
				'processing' => __( 'Organizing menu structure...', 'aiopms' ),
				'completing' => __( 'Finalizing menu configuration...', 'aiopms' ),
			),
			'backup_restore' => array(
				'starting' => __( '💾 Processing backup data...', 'aiopms' ),
				'processing' => __( 'Restoring plugin settings and data...', 'aiopms' ),
				'completing' => __( 'Validating restored data...', 'aiopms' ),
			),
		);
		
		return isset( $messages[ $loading_type ] ) ? $messages[ $loading_type ] : $messages['default'];
	}
	
	/**
	 * Display success notice.
	 * 
	 * @param string $message Success message.
	 * @param string $title Notice title.
	 * @since 3.0
	 */
	public function display_success_notice( $message, $title = '' ) {
		if ( empty( $title ) ) {
			$title = __( 'Success', 'aiopms' );
		}
		
		?>
		<div class="notice notice-success is-dismissible aiopms-notice">
			<p>
				<strong><?php echo esc_html( $title ); ?>:</strong>
				<?php echo esc_html( $message ); ?>
			</p>
		</div>
		<?php
	}
	
	/**
	 * Display error notice.
	 * 
	 * @param string $message Error message.
	 * @param string $title Notice title.
	 * @since 3.0
	 */
	public function display_error_notice( $message, $title = '' ) {
		if ( empty( $title ) ) {
			$title = __( 'Error', 'aiopms' );
		}
		
		?>
		<div class="notice notice-error is-dismissible aiopms-notice">
			<p>
				<strong><?php echo esc_html( $title ); ?>:</strong>
				<?php echo esc_html( $message ); ?>
			</p>
		</div>
		<?php
	}
	
	/**
	 * Display warning notice.
	 * 
	 * @param string $message Warning message.
	 * @param string $title Notice title.
	 * @since 3.0
	 */
	public function display_warning_notice( $message, $title = '' ) {
		if ( empty( $title ) ) {
			$title = __( 'Warning', 'aiopms' );
		}
		
		?>
		<div class="notice notice-warning is-dismissible aiopms-notice">
			<p>
				<strong><?php echo esc_html( $title ); ?>:</strong>
				<?php echo esc_html( $message ); ?>
			</p>
		</div>
		<?php
	}
	
	/**
	 * Display info notice.
	 * 
	 * @param string $message Info message.
	 * @param string $title Notice title.
	 * @since 3.0
	 */
	public function display_info_notice( $message, $title = '' ) {
		if ( empty( $title ) ) {
			$title = __( 'Info', 'aiopms' );
		}
		
		?>
		<div class="notice notice-info is-dismissible aiopms-notice">
			<p>
				<strong><?php echo esc_html( $title ); ?>:</strong>
				<?php echo esc_html( $message ); ?>
			</p>
		</div>
		<?php
	}
	
	/**
	 * Add feedback scripts to admin footer.
	 * 
	 * @since 3.0
	 */
	public function add_feedback_scripts() {
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Enhanced progress modal functionality with loader.png integration
			$('.aiopms-modal-close, .aiopms-close-modal').on('click', function() {
				$('#aiopms-progress-modal').fadeOut(300);
			});
			
			// Cancel operation
			$('.aiopms-cancel-operation').on('click', function() {
				var operationId = $(this).data('operation-id');
				if (confirm('<?php esc_js( __( 'Are you sure you want to cancel this operation?', 'aiopms' ) ); ?>')) {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'aiopms_cancel_operation',
							operation_id: operationId,
							nonce: '<?php echo wp_create_nonce( 'aiopms_cancel_operation' ); ?>'
						},
						success: function(response) {
							if (response.success) {
								$('#aiopms-progress-modal').fadeOut(300);
								location.reload();
							}
						}
					});
				}
			});
			
			// Dismiss notices
			$('.aiopms-notice .notice-dismiss').on('click', function() {
				$(this).closest('.aiopms-notice').fadeOut();
			});
			
			// Enhanced progress tracking with real-time updates
			window.AIOPMSProgressTracker = {
				operationId: null,
				interval: null,
				
				start: function(operationId, loadingType) {
					this.operationId = operationId;
					this.loadingType = loadingType || 'default';
					
					// Show progress modal
					$('#aiopms-progress-modal').fadeIn(300);
					
					// Start progress polling
					this.interval = setInterval(this.updateProgress.bind(this), 1000);
				},
				
				updateProgress: function() {
					if (!this.operationId) return;
					
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'aiopms_get_progress',
							operation_id: this.operationId,
							nonce: '<?php echo wp_create_nonce( 'aiopms_get_progress' ); ?>'
						},
						success: function(response) {
							if (response.success) {
								var data = response.data;
								
								// Update progress bar
								$('.dg10-progress-fill').css('width', data.percentage + '%');
								$('.aiopms-progress-percentage').text(data.percentage + '%');
								
								// Update progress message
								var message = data.messages && data.messages.length > 0 ? 
									data.messages[data.messages.length - 1].message : 
									'Processing...';
								$('.aiopms-progress-message').text(message);
								
								// Update messages list
								if (data.messages && data.messages.length > 0) {
									var messagesHtml = '';
									data.messages.forEach(function(msg) {
										messagesHtml += '<div class="message-item">' +
											'<span class="message-timestamp">' + new Date(msg.timestamp * 1000).toLocaleTimeString() + '</span> ' +
											msg.message +
											'</div>';
									});
									$('.aiopms-progress-messages-list').html(messagesHtml);
								}
								
								// Update errors
								if (data.errors && data.errors.length > 0) {
									var errorsHtml = '';
									data.errors.forEach(function(error) {
										errorsHtml += '<div class="message-item">' +
											'<span class="message-timestamp">' + new Date(error.timestamp * 1000).toLocaleTimeString() + '</span> ' +
											error.message +
											'</div>';
									});
									$('.aiopms-progress-errors-list').html(errorsHtml);
									$('.dg10-progress-errors').show();
								}
								
								// Check if operation is complete
								if (data.status === 'completed' || data.status === 'error' || data.status === 'cancelled') {
									this.stop();
									
									if (data.status === 'completed') {
										$('.aiopms-progress-message').text('Operation completed successfully!');
										$('.aiopms-close-modal').show();
										$('.aiopms-cancel-operation').hide();
									} else if (data.status === 'error') {
										$('.aiopms-progress-message').text('Operation failed. Please check errors below.');
										$('.aiopms-close-modal').show();
										$('.aiopms-cancel-operation').hide();
									} else if (data.status === 'cancelled') {
										$('.aiopms-progress-message').text('Operation cancelled.');
										$('.aiopms-close-modal').show();
										$('.aiopms-cancel-operation').hide();
									}
								}
							}
						}.bind(this),
						error: function() {
							console.error('Failed to get progress update');
						}
					});
				},
				
				stop: function() {
					if (this.interval) {
						clearInterval(this.interval);
						this.interval = null;
					}
				}
			};
			
			// Global function to start progress tracking
			window.aiopmsStartProgress = function(operationId, loadingType) {
				window.AIOPMSProgressTracker.start(operationId, loadingType);
			};
			
			// Global function to stop progress tracking
			window.aiopmsStopProgress = function() {
				window.AIOPMSProgressTracker.stop();
			};
		});
		</script>
		
		<style type="text/css">
		/* Enhanced loader with existing loader.png integration */
		.dg10-loading-spinner-enhanced {
			width: 4rem;
			height: 4rem;
			margin: 0 auto var(--dg10-spacing-lg);
			position: relative;
		}
		
		.dg10-loader-image {
			width: 100%;
			height: 100%;
			animation: dg10-spin 1.5s linear infinite;
			filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
		}
		
		/* Enhanced progress bar styling */
		.dg10-progress-bar {
			width: 100%;
			height: 8px;
			background: var(--dg10-light-purple);
			border-radius: 4px;
			overflow: hidden;
			margin: var(--dg10-spacing-md) 0;
		}
		
		.dg10-progress-fill {
			height: 100%;
			background: linear-gradient(90deg, var(--dg10-primary), var(--dg10-secondary));
			border-radius: 4px;
			transition: width 0.3s ease;
			position: relative;
		}
		
		.dg10-progress-fill::after {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
			animation: dg10-progress-shimmer 2s infinite;
		}
		
		@keyframes dg10-progress-shimmer {
			0% { transform: translateX(-100%); }
			100% { transform: translateX(100%); }
		}
		
		/* Enhanced progress messages */
		.dg10-progress-messages {
			max-height: 200px;
			overflow-y: auto;
			border: 1px solid var(--dg10-border);
			border-radius: var(--dg10-radius-md);
			padding: var(--dg10-spacing-md);
			background: var(--dg10-light-gray);
			margin-top: var(--dg10-spacing-md);
		}
		
		.dg10-progress-messages-list {
			font-family: 'Courier New', monospace;
			font-size: var(--dg10-font-size-sm);
			line-height: 1.4;
			color: var(--dg10-neutral);
		}
		
		.dg10-progress-messages-list .message-item {
			padding: var(--dg10-spacing-xs) 0;
			border-bottom: 1px solid var(--dg10-border);
		}
		
		.dg10-progress-messages-list .message-item:last-child {
			border-bottom: none;
		}
		
		.dg10-progress-messages-list .message-timestamp {
			color: var(--dg10-muted);
			font-size: var(--dg10-font-size-xs);
		}
		
		/* Enhanced error styling */
		.dg10-progress-errors {
			margin-top: var(--dg10-spacing-md);
			padding: var(--dg10-spacing-md);
			background: rgba(220, 38, 38, 0.1);
			border: 1px solid var(--dg10-error);
			border-radius: var(--dg10-radius-md);
		}
		
		.dg10-progress-errors h4 {
			margin: 0 0 var(--dg10-spacing-sm) 0;
			color: var(--dg10-error);
			font-size: var(--dg10-font-size-md);
		}
		
		.dg10-progress-errors-list {
			font-family: 'Courier New', monospace;
			font-size: var(--dg10-font-size-sm);
			line-height: 1.4;
			color: var(--dg10-error);
		}
		
		/* Modal enhancements */
		.aiopms-modal {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.5);
			z-index: 999999;
		}
		
		.aiopms-modal-content {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			background: #fff;
			border-radius: 4px;
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
			min-width: 400px;
			max-width: 600px;
		}
		
		.aiopms-modal-header {
			padding: 20px;
			border-bottom: 1px solid #ddd;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		
		.aiopms-modal-header h3 {
			margin: 0;
		}
		
		.aiopms-modal-close {
			background: none;
			border: none;
			font-size: 24px;
			cursor: pointer;
			padding: 0;
			width: 30px;
			height: 30px;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		
		.aiopms-modal-body {
			padding: 20px;
		}
		
		.aiopms-progress-container {
			margin-bottom: 20px;
		}
		
		.aiopms-progress-bar {
			width: 100%;
			height: 20px;
			background: #f0f0f0;
			border-radius: 10px;
			overflow: hidden;
			margin-bottom: 10px;
		}
		
		.aiopms-progress-fill {
			height: 100%;
			background: #0073aa;
			transition: width 0.3s ease;
		}
		
		.aiopms-progress-text {
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		
		.aiopms-progress-percentage {
			font-weight: bold;
			color: #0073aa;
		}
		
		.aiopms-progress-messages {
			max-height: 200px;
			overflow-y: auto;
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 10px;
		}
		
		.aiopms-progress-messages-list {
			font-family: monospace;
			font-size: 12px;
			line-height: 1.4;
		}
		
		.aiopms-progress-errors {
			margin-top: 20px;
			padding: 10px;
			background: #f8f8f8;
			border-radius: 4px;
		}
		
		.aiopms-progress-errors h4 {
			margin: 0 0 10px 0;
			color: #d63638;
		}
		
		.aiopms-progress-errors-list {
			font-family: monospace;
			font-size: 12px;
			line-height: 1.4;
			color: #d63638;
		}
		
		.aiopms-modal-footer {
			padding: 20px;
			border-top: 1px solid #ddd;
			display: flex;
			justify-content: flex-end;
			gap: 10px;
		}
		
		.aiopms-notice {
			margin: 15px 0;
		}
		</style>
		<?php
	}
	
	/**
	 * Get progress via AJAX.
	 * 
	 * @since 3.0
	 */
	public function get_progress() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aiopms_get_progress' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		$operation_id = sanitize_text_field( $_POST['operation_id'] );
		$progress_data = $this->get_progress_data( $operation_id );
		
		if ( ! $progress_data ) {
			wp_send_json_error( __( 'Progress data not found.', 'aiopms' ) );
		}
		
		$response = array(
			'operation_id' => $operation_id,
			'status' => $progress_data['status'],
			'current_step' => $progress_data['current_step'],
			'total_steps' => $progress_data['total_steps'],
			'percentage' => $this->get_progress_percentage( $operation_id ),
			'messages' => $progress_data['messages'],
			'errors' => $progress_data['errors'],
			'duration' => $this->get_operation_duration( $operation_id ),
		);
		
		wp_send_json_success( $response );
	}
	
	/**
	 * Cancel operation via AJAX.
	 * 
	 * @since 3.0
	 */
	public function cancel_operation() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aiopms_cancel_operation' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		$operation_id = sanitize_text_field( $_POST['operation_id'] );
		$this->cancel_progress( $operation_id, __( 'Operation cancelled by user.', 'aiopms' ) );
		
		wp_send_json_success( __( 'Operation cancelled.', 'aiopms' ) );
	}
	
	/**
	 * Dismiss notice via AJAX.
	 * 
	 * @since 3.0
	 */
	public function dismiss_notice() {
		// Check nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aiopms_dismiss_notice' ) ) {
			wp_die( __( 'Security check failed.', 'aiopms' ) );
		}
		
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'aiopms' ) );
		}
		
		$notice_id = sanitize_text_field( $_POST['notice_id'] );
		update_user_meta( get_current_user_id(), 'aiopms_dismissed_notices', array( $notice_id ) );
		
		wp_send_json_success( __( 'Notice dismissed.', 'aiopms' ) );
	}
}

/**
 * Initialize user feedback.
 * 
 * @since 3.0
 */
function aiopms_init_user_feedback() {
	return AIOPMS_User_Feedback::get_instance();
}

// Initialize user feedback.
aiopms_init_user_feedback();

/**
 * Helper function to start progress.
 * 
 * @param string $operation_id Operation ID.
 * @param string $operation_name Operation name.
 * @param int    $total_steps Total number of steps.
 * @since 3.0
 */
function aiopms_start_progress( $operation_id, $operation_name, $total_steps = 0 ) {
	$feedback = AIOPMS_User_Feedback::get_instance();
	$feedback->start_progress( $operation_id, $operation_name, $total_steps );
}

/**
 * Helper function to update progress.
 * 
 * @param string $operation_id Operation ID.
 * @param int    $current_step Current step.
 * @param string $message Progress message.
 * @param string $status Status.
 * @since 3.0
 */
function aiopms_update_progress( $operation_id, $current_step, $message = '', $status = 'running' ) {
	$feedback = AIOPMS_User_Feedback::get_instance();
	$feedback->update_progress( $operation_id, $current_step, $message, $status );
}

/**
 * Helper function to complete progress.
 * 
 * @param string $operation_id Operation ID.
 * @param string $message Completion message.
 * @since 3.0
 */
function aiopms_complete_progress( $operation_id, $message = '' ) {
	$feedback = AIOPMS_User_Feedback::get_instance();
	$feedback->complete_progress( $operation_id, $message );
}

/**
 * Helper function to add progress error.
 * 
 * @param string $operation_id Operation ID.
 * @param string $error_message Error message.
 * @since 3.0
 */
function aiopms_add_progress_error( $operation_id, $error_message ) {
	$feedback = AIOPMS_User_Feedback::get_instance();
	$feedback->add_progress_error( $operation_id, $error_message );
}

/**
 * Helper function to display success notice.
 * 
 * @param string $message Success message.
 * @param string $title Notice title.
 * @since 3.0
 */
function aiopms_display_success_notice( $message, $title = '' ) {
	$feedback = AIOPMS_User_Feedback::get_instance();
	$feedback->display_success_notice( $message, $title );
}

/**
 * Helper function to display error notice.
 * 
 * @param string $message Error message.
 * @param string $title Notice title.
 * @since 3.0
 */
function aiopms_display_error_notice( $message, $title = '' ) {
	$feedback = AIOPMS_User_Feedback::get_instance();
	$feedback->display_error_notice( $message, $title );
}

/**
 * Helper function to display warning notice.
 * 
 * @param string $message Warning message.
 * @param string $title Notice title.
 * @since 3.0
 */
function aiopms_display_warning_notice( $message, $title = '' ) {
	$feedback = AIOPMS_User_Feedback::get_instance();
	$feedback->display_warning_notice( $message, $title );
}

/**
 * Helper function to display info notice.
 * 
 * @param string $message Info message.
 * @param string $title Notice title.
 * @since 3.0
 */
function aiopms_display_info_notice( $message, $title = '' ) {
	$feedback = AIOPMS_User_Feedback::get_instance();
	$feedback->display_info_notice( $message, $title );
}

/**
 * Helper function to display progress modal with loader.png.
 * 
 * @param string $operation_id Operation ID.
 * @param string $title Modal title.
 * @param string $loading_type Type of loading operation.
 * @since 3.0
 */
function aiopms_display_progress_modal( $operation_id, $title = '', $loading_type = 'default' ) {
	$feedback = AIOPMS_User_Feedback::get_instance();
	$feedback->display_progress_modal( $operation_id, $title, $loading_type );
}

/**
 * Helper function to start progress with enhanced loader.
 * 
 * @param string $operation_id Operation ID.
 * @param string $operation_name Operation name.
 * @param int    $total_steps Total number of steps.
 * @param string $loading_type Type of loading operation.
 * @since 3.0
 */
function aiopms_start_enhanced_progress( $operation_id, $operation_name, $total_steps = 0, $loading_type = 'default' ) {
	$feedback = AIOPMS_User_Feedback::get_instance();
	$feedback->start_progress( $operation_id, $operation_name, $total_steps );
	
	// Display progress modal with loader
	$feedback->display_progress_modal( $operation_id, $operation_name, $loading_type );
	
	// Start JavaScript progress tracking
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		aiopmsStartProgress('<?php echo esc_js( $operation_id ); ?>', '<?php echo esc_js( $loading_type ); ?>');
	});
	</script>
	<?php
}

/**
 * Helper function to show enhanced loading overlay for AI generation.
 * 
 * @param string $operation_id Operation ID.
 * @since 3.0
 */
function aiopms_show_ai_loading_overlay( $operation_id ) {
	aiopms_start_enhanced_progress( $operation_id, __( 'AI Generation in Progress', 'aiopms' ), 0, 'ai_generation' );
}

/**
 * Helper function to show enhanced loading overlay for page creation.
 * 
 * @param string $operation_id Operation ID.
 * @param int    $total_pages Total number of pages to create.
 * @since 3.0
 */
function aiopms_show_page_creation_loading_overlay( $operation_id, $total_pages = 0 ) {
	aiopms_start_enhanced_progress( $operation_id, __( 'Creating Pages', 'aiopms' ), $total_pages, 'page_creation' );
}

/**
 * Helper function to show enhanced loading overlay for CSV import.
 * 
 * @param string $operation_id Operation ID.
 * @param int    $total_rows Total number of rows to process.
 * @since 3.0
 */
function aiopms_show_csv_import_loading_overlay( $operation_id, $total_rows = 0 ) {
	aiopms_start_enhanced_progress( $operation_id, __( 'Importing CSV Data', 'aiopms' ), $total_rows, 'csv_import' );
}

/**
 * Helper function to show enhanced loading overlay for schema generation.
 * 
 * @param string $operation_id Operation ID.
 * @param int    $total_posts Total number of posts to process.
 * @since 3.0
 */
function aiopms_show_schema_loading_overlay( $operation_id, $total_posts = 0 ) {
	aiopms_start_enhanced_progress( $operation_id, __( 'Generating Schema Markup', 'aiopms' ), $total_posts, 'schema_generation' );
}

/**
 * Helper function to show enhanced loading overlay for menu generation.
 * 
 * @param string $operation_id Operation ID.
 * @since 3.0
 */
function aiopms_show_menu_loading_overlay( $operation_id ) {
	aiopms_start_enhanced_progress( $operation_id, __( 'Generating Menus', 'aiopms' ), 0, 'menu_generation' );
}

/**
 * Helper function to show enhanced loading overlay for backup/restore.
 * 
 * @param string $operation_id Operation ID.
 * @param string $operation_type Type of backup operation.
 * @since 3.0
 */
function aiopms_show_backup_loading_overlay( $operation_id, $operation_type = 'backup' ) {
	$title = $operation_type === 'restore' ? __( 'Restoring Backup', 'aiopms' ) : __( 'Creating Backup', 'aiopms' );
	aiopms_start_enhanced_progress( $operation_id, $title, 0, 'backup_restore' );
}
