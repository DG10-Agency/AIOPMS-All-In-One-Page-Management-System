<?php
/**
 * Data Validation System for AIOPMS
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
 * AIOPMS Data Validator Class
 * 
 * Handles all data validation, sanitization, and security checks.
 * 
 * @since 3.0
 */
class AIOPMS_Data_Validator {
	
	/**
	 * Instance of the class.
	 * 
	 * @var AIOPMS_Data_Validator
	 * @since 3.0
	 */
	private static $instance = null;
	
	/**
	 * Validation rules.
	 * 
	 * @var array
	 * @since 3.0
	 */
	private $validation_rules = array();
	
	/**
	 * Get instance of the class.
	 * 
	 * @return AIOPMS_Data_Validator
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
		$this->init_validation_rules();
	}
	
	/**
	 * Initialize validation rules.
	 * 
	 * @since 3.0
	 */
	private function init_validation_rules() {
		$this->validation_rules = array(
			'page_title' => array(
				'required' => true,
				'min_length' => 1,
				'max_length' => 255,
				'sanitize' => 'sanitize_text_field',
				'validate' => 'validate_text',
			),
			'page_content' => array(
				'required' => false,
				'min_length' => 0,
				'max_length' => 1000000, // 1MB
				'sanitize' => 'wp_kses_post',
				'validate' => 'validate_content',
			),
			'page_slug' => array(
				'required' => false,
				'min_length' => 1,
				'max_length' => 200,
				'sanitize' => 'sanitize_title',
				'validate' => 'validate_slug',
			),
			'page_status' => array(
				'required' => true,
				'allowed_values' => array( 'draft', 'publish', 'private', 'pending' ),
				'sanitize' => 'sanitize_key',
				'validate' => 'validate_status',
			),
			'page_template' => array(
				'required' => false,
				'min_length' => 0,
				'max_length' => 100,
				'sanitize' => 'sanitize_file_name',
				'validate' => 'validate_template',
			),
			'api_key' => array(
				'required' => true,
				'min_length' => 10,
				'max_length' => 500,
				'sanitize' => 'sanitize_text_field',
				'validate' => 'validate_api_key',
			),
			'email' => array(
				'required' => false,
				'sanitize' => 'sanitize_email',
				'validate' => 'validate_email',
			),
			'url' => array(
				'required' => false,
				'sanitize' => 'esc_url_raw',
				'validate' => 'validate_url',
			),
			'file_upload' => array(
				'required' => false,
				'validate' => 'validate_file_upload',
			),
			'csv_data' => array(
				'required' => true,
				'validate' => 'validate_csv_data',
			),
			'json_data' => array(
				'required' => true,
				'validate' => 'validate_json_data',
			),
		);
	}
	
	/**
	 * Validate data against rules.
	 * 
	 * @param mixed  $data  Data to validate.
	 * @param string $field Field name.
	 * @param array  $rules Custom validation rules.
	 * @return array Validation result.
	 * @since 3.0
	 */
	public function validate( $data, $field, $rules = array() ) {
		$result = array(
			'valid' => true,
			'data' => $data,
			'errors' => array(),
		);
		
		// Get validation rules.
		$field_rules = ! empty( $rules ) ? $rules : $this->get_field_rules( $field );
		
		if ( empty( $field_rules ) ) {
			$result['errors'][] = sprintf( __( 'No validation rules found for field: %s', 'aiopms' ), $field );
			$result['valid'] = false;
			return $result;
		}
		
		// Check if field is required.
		if ( isset( $field_rules['required'] ) && $field_rules['required'] && empty( $data ) ) {
			$result['errors'][] = sprintf( __( 'Field %s is required.', 'aiopms' ), $field );
			$result['valid'] = false;
			return $result;
		}
		
		// Skip validation if field is empty and not required.
		if ( empty( $data ) && ( ! isset( $field_rules['required'] ) || ! $field_rules['required'] ) ) {
			return $result;
		}
		
		// Sanitize data.
		if ( isset( $field_rules['sanitize'] ) && is_callable( $field_rules['sanitize'] ) ) {
			$result['data'] = call_user_func( $field_rules['sanitize'], $data );
		}
		
		// Validate data.
		if ( isset( $field_rules['validate'] ) && is_callable( array( $this, $field_rules['validate'] ) ) ) {
			$validation_result = call_user_func( array( $this, $field_rules['validate'] ), $result['data'], $field_rules );
			if ( ! $validation_result['valid'] ) {
				$result['errors'] = array_merge( $result['errors'], $validation_result['errors'] );
				$result['valid'] = false;
			}
		}
		
		return $result;
	}
	
	/**
	 * Get validation rules for a field.
	 * 
	 * @param string $field Field name.
	 * @return array Validation rules.
	 * @since 3.0
	 */
	private function get_field_rules( $field ) {
		return isset( $this->validation_rules[ $field ] ) ? $this->validation_rules[ $field ] : array();
	}
	
	/**
	 * Validate text data.
	 * 
	 * @param string $data  Data to validate.
	 * @param array  $rules Validation rules.
	 * @return array Validation result.
	 * @since 3.0
	 */
	private function validate_text( $data, $rules ) {
		$result = array( 'valid' => true, 'errors' => array() );
		
		// Check minimum length.
		if ( isset( $rules['min_length'] ) && strlen( $data ) < $rules['min_length'] ) {
			$result['errors'][] = sprintf( __( 'Text must be at least %d characters long.', 'aiopms' ), $rules['min_length'] );
			$result['valid'] = false;
		}
		
		// Check maximum length.
		if ( isset( $rules['max_length'] ) && strlen( $data ) > $rules['max_length'] ) {
			$result['errors'][] = sprintf( __( 'Text must not exceed %d characters.', 'aiopms' ), $rules['max_length'] );
			$result['valid'] = false;
		}
		
		// Check for allowed characters.
		if ( isset( $rules['allowed_chars'] ) && ! preg_match( $rules['allowed_chars'], $data ) ) {
			$result['errors'][] = __( 'Text contains invalid characters.', 'aiopms' );
			$result['valid'] = false;
		}
		
		return $result;
	}
	
	/**
	 * Validate content data.
	 * 
	 * @param string $data  Data to validate.
	 * @param array  $rules Validation rules.
	 * @return array Validation result.
	 * @since 3.0
	 */
	private function validate_content( $data, $rules ) {
		$result = array( 'valid' => true, 'errors' => array() );
		
		// Check minimum length.
		if ( isset( $rules['min_length'] ) && strlen( $data ) < $rules['min_length'] ) {
			$result['errors'][] = sprintf( __( 'Content must be at least %d characters long.', 'aiopms' ), $rules['min_length'] );
			$result['valid'] = false;
		}
		
		// Check maximum length.
		if ( isset( $rules['max_length'] ) && strlen( $data ) > $rules['max_length'] ) {
			$result['errors'][] = sprintf( __( 'Content must not exceed %d characters.', 'aiopms' ), $rules['max_length'] );
			$result['valid'] = false;
		}
		
		// Check for malicious content.
		if ( $this->contains_malicious_content( $data ) ) {
			$result['errors'][] = __( 'Content contains potentially malicious code.', 'aiopms' );
			$result['valid'] = false;
		}
		
		return $result;
	}
	
	/**
	 * Validate slug data.
	 * 
	 * @param string $data  Data to validate.
	 * @param array  $rules Validation rules.
	 * @return array Validation result.
	 * @since 3.0
	 */
	private function validate_slug( $data, $rules ) {
		$result = array( 'valid' => true, 'errors' => array() );
		
		// Check if slug is valid.
		if ( ! preg_match( '/^[a-z0-9-]+$/', $data ) ) {
			$result['errors'][] = __( 'Slug can only contain lowercase letters, numbers, and hyphens.', 'aiopms' );
			$result['valid'] = false;
		}
		
		// Check if slug starts or ends with hyphen.
		if ( preg_match( '/^-|-$/', $data ) ) {
			$result['errors'][] = __( 'Slug cannot start or end with a hyphen.', 'aiopms' );
			$result['valid'] = false;
		}
		
		// Check if slug is already in use.
		if ( $this->slug_exists( $data ) ) {
			$result['errors'][] = __( 'Slug is already in use.', 'aiopms' );
			$result['valid'] = false;
		}
		
		return $result;
	}
	
	/**
	 * Validate status data.
	 * 
	 * @param string $data  Data to validate.
	 * @param array  $rules Validation rules.
	 * @return array Validation result.
	 * @since 3.0
	 */
	private function validate_status( $data, $rules ) {
		$result = array( 'valid' => true, 'errors' => array() );
		
		// Check if status is allowed.
		if ( isset( $rules['allowed_values'] ) && ! in_array( $data, $rules['allowed_values'] ) ) {
			$result['errors'][] = sprintf( __( 'Status must be one of: %s', 'aiopms' ), implode( ', ', $rules['allowed_values'] ) );
			$result['valid'] = false;
		}
		
		return $result;
	}
	
	/**
	 * Validate template data.
	 * 
	 * @param string $data  Data to validate.
	 * @param array  $rules Validation rules.
	 * @return array Validation result.
	 * @since 3.0
	 */
	private function validate_template( $data, $rules ) {
		$result = array( 'valid' => true, 'errors' => array() );
		
		// Check if template exists.
		if ( ! empty( $data ) && ! $this->template_exists( $data ) ) {
			$result['errors'][] = __( 'Template does not exist.', 'aiopms' );
			$result['valid'] = false;
		}
		
		return $result;
	}
	
	/**
	 * Validate API key data.
	 * 
	 * @param string $data  Data to validate.
	 * @param array  $rules Validation rules.
	 * @return array Validation result.
	 * @since 3.0
	 */
	private function validate_api_key( $data, $rules ) {
		$result = array( 'valid' => true, 'errors' => array() );
		
		// Check if API key is valid format.
		if ( ! preg_match( '/^[a-zA-Z0-9-_]+$/', $data ) ) {
			$result['errors'][] = __( 'API key contains invalid characters.', 'aiopms' );
			$result['valid'] = false;
		}
		
		// Check minimum length.
		if ( isset( $rules['min_length'] ) && strlen( $data ) < $rules['min_length'] ) {
			$result['errors'][] = sprintf( __( 'API key must be at least %d characters long.', 'aiopms' ), $rules['min_length'] );
			$result['valid'] = false;
		}
		
		return $result;
	}
	
	/**
	 * Validate email data.
	 * 
	 * @param string $data  Data to validate.
	 * @param array  $rules Validation rules.
	 * @return array Validation result.
	 * @since 3.0
	 */
	private function validate_email( $data, $rules ) {
		$result = array( 'valid' => true, 'errors' => array() );
		
		// Check if email is valid.
		if ( ! is_email( $data ) ) {
			$result['errors'][] = __( 'Invalid email address.', 'aiopms' );
			$result['valid'] = false;
		}
		
		return $result;
	}
	
	/**
	 * Validate URL data.
	 * 
	 * @param string $data  Data to validate.
	 * @param array  $rules Validation rules.
	 * @return array Validation result.
	 * @since 3.0
	 */
	private function validate_url( $data, $rules ) {
		$result = array( 'valid' => true, 'errors' => array() );
		
		// Check if URL is valid.
		if ( ! filter_var( $data, FILTER_VALIDATE_URL ) ) {
			$result['errors'][] = __( 'Invalid URL.', 'aiopms' );
			$result['valid'] = false;
		}
		
		return $result;
	}
	
	/**
	 * Validate file upload data.
	 * 
	 * @param array $data  Data to validate.
	 * @param array $rules Validation rules.
	 * @return array Validation result.
	 * @since 3.0
	 */
	private function validate_file_upload( $data, $rules ) {
		$result = array( 'valid' => true, 'errors' => array() );
		
		// Check if file was uploaded.
		if ( ! isset( $data['tmp_name'] ) || empty( $data['tmp_name'] ) ) {
			$result['errors'][] = __( 'No file was uploaded.', 'aiopms' );
			$result['valid'] = false;
			return $result;
		}
		
		// Check for upload errors.
		if ( isset( $data['error'] ) && $data['error'] !== UPLOAD_ERR_OK ) {
			$result['errors'][] = $this->get_upload_error_message( $data['error'] );
			$result['valid'] = false;
			return $result;
		}
		
		// Check file size.
		if ( isset( $data['size'] ) && $data['size'] > wp_max_upload_size() ) {
			$result['errors'][] = sprintf( __( 'File size exceeds maximum allowed size of %s.', 'aiopms' ), size_format( wp_max_upload_size() ) );
			$result['valid'] = false;
		}
		
		// Check file type.
		if ( isset( $data['type'] ) && ! $this->is_allowed_file_type( $data['type'] ) ) {
			$result['errors'][] = __( 'File type is not allowed.', 'aiopms' );
			$result['valid'] = false;
		}
		
		// Check for malicious content.
		if ( isset( $data['tmp_name'] ) && $this->contains_malicious_content( file_get_contents( $data['tmp_name'] ) ) ) {
			$result['errors'][] = __( 'File contains potentially malicious content.', 'aiopms' );
			$result['valid'] = false;
		}
		
		return $result;
	}
	
	/**
	 * Validate CSV data.
	 * 
	 * @param string $data  Data to validate.
	 * @param array  $rules Validation rules.
	 * @return array Validation result.
	 * @since 3.0
	 */
	private function validate_csv_data( $data, $rules ) {
		$result = array( 'valid' => true, 'errors' => array() );
		
		// Check if data is valid CSV.
		$lines = str_getcsv( $data, "\n" );
		if ( empty( $lines ) ) {
			$result['errors'][] = __( 'Invalid CSV data.', 'aiopms' );
			$result['valid'] = false;
			return $result;
		}
		
		// Check minimum number of rows.
		if ( isset( $rules['min_rows'] ) && count( $lines ) < $rules['min_rows'] ) {
			$result['errors'][] = sprintf( __( 'CSV must contain at least %d rows.', 'aiopms' ), $rules['min_rows'] );
			$result['valid'] = false;
		}
		
		// Check maximum number of rows.
		if ( isset( $rules['max_rows'] ) && count( $lines ) > $rules['max_rows'] ) {
			$result['errors'][] = sprintf( __( 'CSV must not exceed %d rows.', 'aiopms' ), $rules['max_rows'] );
			$result['valid'] = false;
		}
		
		// Check for required columns.
		if ( isset( $rules['required_columns'] ) ) {
			$header = str_getcsv( $lines[0] );
			foreach ( $rules['required_columns'] as $column ) {
				if ( ! in_array( $column, $header ) ) {
					$result['errors'][] = sprintf( __( 'CSV must contain column: %s', 'aiopms' ), $column );
					$result['valid'] = false;
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * Validate JSON data.
	 * 
	 * @param string $data  Data to validate.
	 * @param array  $rules Validation rules.
	 * @return array Validation result.
	 * @since 3.0
	 */
	private function validate_json_data( $data, $rules ) {
		$result = array( 'valid' => true, 'errors' => array() );
		
		// Check if data is valid JSON.
		$decoded = json_decode( $data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$result['errors'][] = sprintf( __( 'Invalid JSON data: %s', 'aiopms' ), json_last_error_msg() );
			$result['valid'] = false;
			return $result;
		}
		
		// Check for required keys.
		if ( isset( $rules['required_keys'] ) ) {
			foreach ( $rules['required_keys'] as $key ) {
				if ( ! array_key_exists( $key, $decoded ) ) {
					$result['errors'][] = sprintf( __( 'JSON must contain key: %s', 'aiopms' ), $key );
					$result['valid'] = false;
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * Check if slug exists.
	 * 
	 * @param string $slug Slug to check.
	 * @return bool
	 * @since 3.0
	 */
	private function slug_exists( $slug ) {
		global $wpdb;
		
		$post_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_status != 'trash'",
			$slug
		) );
		
		return ! empty( $post_id );
	}
	
	/**
	 * Check if template exists.
	 * 
	 * @param string $template Template name.
	 * @return bool
	 * @since 3.0
	 */
	private function template_exists( $template ) {
		$template_file = get_template_directory() . '/' . $template;
		$child_template_file = get_stylesheet_directory() . '/' . $template;
		
		return file_exists( $template_file ) || file_exists( $child_template_file );
	}
	
	/**
	 * Check if content contains malicious code.
	 * 
	 * @param string $content Content to check.
	 * @return bool
	 * @since 3.0
	 */
	private function contains_malicious_content( $content ) {
		// Check for common malicious patterns.
		$malicious_patterns = array(
			'/<script[^>]*>.*?<\/script>/is',
			'/<iframe[^>]*>.*?<\/iframe>/is',
			'/<object[^>]*>.*?<\/object>/is',
			'/<embed[^>]*>.*?<\/embed>/is',
			'/<applet[^>]*>.*?<\/applet>/is',
			'/<form[^>]*>.*?<\/form>/is',
			'/<input[^>]*>.*?<\/input>/is',
			'/<button[^>]*>.*?<\/button>/is',
			'/<select[^>]*>.*?<\/select>/is',
			'/<textarea[^>]*>.*?<\/textarea>/is',
			'/<link[^>]*>.*?<\/link>/is',
			'/<meta[^>]*>.*?<\/meta>/is',
			'/<style[^>]*>.*?<\/style>/is',
			'/<link[^>]*>.*?<\/link>/is',
			'/<meta[^>]*>.*?<\/meta>/is',
			'/<style[^>]*>.*?<\/style>/is',
		);
		
		foreach ( $malicious_patterns as $pattern ) {
			if ( preg_match( $pattern, $content ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Get upload error message.
	 * 
	 * @param int $error_code Error code.
	 * @return string Error message.
	 * @since 3.0
	 */
	private function get_upload_error_message( $error_code ) {
		$error_messages = array(
			UPLOAD_ERR_INI_SIZE   => __( 'File size exceeds server limit.', 'aiopms' ),
			UPLOAD_ERR_FORM_SIZE  => __( 'File size exceeds form limit.', 'aiopms' ),
			UPLOAD_ERR_PARTIAL    => __( 'File was only partially uploaded.', 'aiopms' ),
			UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'aiopms' ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'Missing temporary folder.', 'aiopms' ),
			UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', 'aiopms' ),
			UPLOAD_ERR_EXTENSION  => __( 'File upload stopped by extension.', 'aiopms' ),
		);
		
		return isset( $error_messages[ $error_code ] ) ? $error_messages[ $error_code ] : __( 'Unknown upload error.', 'aiopms' );
	}
	
	/**
	 * Check if file type is allowed.
	 * 
	 * @param string $file_type File type.
	 * @return bool
	 * @since 3.0
	 */
	private function is_allowed_file_type( $file_type ) {
		$allowed_types = array(
			'text/csv',
			'application/csv',
			'text/plain',
			'application/json',
			'text/json',
		);
		
		return in_array( $file_type, $allowed_types );
	}
}

/**
 * Initialize data validator.
 * 
 * @since 3.0
 */
function aiopms_init_data_validator() {
	return AIOPMS_Data_Validator::get_instance();
}

// Initialize data validator.
aiopms_init_data_validator();

/**
 * Helper function to validate data.
 * 
 * @param mixed  $data  Data to validate.
 * @param string $field Field name.
 * @param array  $rules Custom validation rules.
 * @return array Validation result.
 * @since 3.0
 */
function aiopms_validate_data( $data, $field, $rules = array() ) {
	$validator = AIOPMS_Data_Validator::get_instance();
	return $validator->validate( $data, $field, $rules );
}

/**
 * Helper function to sanitize data.
 * 
 * @param mixed  $data  Data to sanitize.
 * @param string $field Field name.
 * @return mixed Sanitized data.
 * @since 3.0
 */
function aiopms_sanitize_data( $data, $field ) {
	$validator = AIOPMS_Data_Validator::get_instance();
	$result = $validator->validate( $data, $field );
	return $result['data'];
}
