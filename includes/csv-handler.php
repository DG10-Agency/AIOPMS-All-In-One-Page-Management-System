<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Create pages from CSV file
function aiopms_create_pages_from_csv($file) {
    try {
        // 1. Validate file upload
        if (!is_uploaded_file($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            $error_message = aiopms_get_upload_error_message($file['error']);
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
            return;
        }

        // 2. Check file size (5MB limit)
        $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $max_file_size) {
            $file_size_mb = round($file['size'] / (1024 * 1024), 2);
            $max_size_mb = round($max_file_size / (1024 * 1024), 2);
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                sprintf(
                    esc_html__('File size too large. Your file is %s MB, but the maximum allowed size is %s MB. Please reduce the file size and try again.', 'aiopms'),
                    $file_size_mb,
                    $max_size_mb
                ) . '</p></div>';
            return;
        }

        // 3. Additional file size validation using filesize()
        $actual_file_size = filesize($file['tmp_name']);
        if ($actual_file_size === false) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Unable to determine file size. Please try again.', 'aiopms') . '</p></div>';
            return;
        }

        if ($actual_file_size > $max_file_size) {
            $file_size_mb = round($actual_file_size / (1024 * 1024), 2);
            $max_size_mb = round($max_file_size / (1024 * 1024), 2);
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                sprintf(
                    esc_html__('File size too large. Your file is %s MB, but the maximum allowed size is %s MB. Please reduce the file size and try again.', 'aiopms'),
                    $file_size_mb,
                    $max_size_mb
                ) . '</p></div>';
            return;
        }

        // 4. Check if file is empty
        if ($actual_file_size === 0) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('The uploaded file is empty. Please upload a valid CSV file with content.', 'aiopms') . '</p></div>';
            return;
        }

        // 5. Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Unable to verify file type. Please try again.', 'aiopms') . '</p></div>';
            return;
        }

        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($mime_type === false) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Unable to read file type. Please try again.', 'aiopms') . '</p></div>';
            return;
        }

        // Allow various CSV MIME types
        $allowed_mime_types = [
            'text/csv',
            'application/csv',
            'text/plain',
            'application/vnd.ms-excel',
            'text/comma-separated-values'
        ];

        if (!in_array($mime_type, $allowed_mime_types)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                sprintf(
                    esc_html__('Invalid file type. Expected CSV file, but received %s. Please upload a valid CSV file.', 'aiopms'),
                    esc_html($mime_type)
                ) . '</p></div>';
            return;
        }

        // 6. Additional file extension check
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'csv') {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                sprintf(
                    esc_html__('Invalid file extension. Expected .csv file, but received .%s. Please upload a valid CSV file.', 'aiopms'),
                    esc_html($file_extension)
                ) . '</p></div>';
            return;
        }
        
        // 7. Additional security: Check file content for CSV validity
        $file_handle = fopen($file['tmp_name'], 'r');
        if ($file_handle === false) {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                esc_html__('Unable to read the uploaded file. Please try again.', 'aiopms') . '</p></div>';
            return;
        }
        
        // Read first few lines to validate CSV format
        $first_line = fgetcsv($file_handle);
        if ($first_line === false || empty($first_line)) {
            fclose($file_handle);
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                esc_html__('The uploaded file does not contain valid CSV data. Please check the file format.', 'aiopms') . '</p></div>';
            return;
        }
        fclose($file_handle);

    } catch (Exception $e) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('An error occurred during file validation. Please try again.', 'aiopms') . '</p></div>';
        error_log('AIOPMS CSV File Validation Error: ' . $e->getMessage());
        return;
    }

    // 7. Read and validate CSV content
    try {
        $csv_content = file_get_contents($file['tmp_name']);
        if ($csv_content === false) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Unable to read CSV file content. Please try again.', 'aiopms') . '</p></div>';
            return;
        }

        // Check if file content is too large (additional safety check)
        if (strlen($csv_content) > $max_file_size) {
            $content_size_mb = round(strlen($csv_content) / (1024 * 1024), 2);
            $max_size_mb = round($max_file_size / (1024 * 1024), 2);
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                sprintf(
                    esc_html__('CSV content too large. File content is %s MB, but the maximum allowed size is %s MB. Please reduce the file size and try again.', 'aiopms'),
                    $content_size_mb,
                    $max_size_mb
                ) . '</p></div>';
            return;
        }

        $csv_data = array_map('str_getcsv', file($file['tmp_name']));
        if (empty($csv_data)) {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('The CSV file is empty.', 'aiopms') . '</p></div>';
            return;
        }

        // 8. Validate CSV structure and size
        $max_rows = 10000; // Limit to prevent memory issues
        if (count($csv_data) > $max_rows) {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                sprintf(
                    esc_html__('CSV file has too many rows. Maximum allowed: %d rows, but your file has %d rows. Please reduce the number of rows and try again.', 'aiopms'),
                    $max_rows,
                    count($csv_data)
                ) . '</p></div>';
            return;
        }

    } catch (Exception $e) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('An error occurred while reading the CSV file. Please try again.', 'aiopms') . '</p></div>';
        error_log('AIOPMS CSV Reading Error: ' . $e->getMessage());
        return;
    }

    $header = array_shift($csv_data);
    $header = array_map('trim', $header); // Trim whitespace from headers
    $created_pages = 0;
    $page_map = []; // To map titles to page IDs for parent lookup
    $pages_to_create = []; // For batch processing

    // Check memory before processing
    if (!aiopms_check_memory_limit(75)) {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('Insufficient memory available for CSV processing. Please try with a smaller file.', 'aiopms') . '</p></div>';
        return;
    }

    foreach ($csv_data as $row_index => $row) {
        if (count($header) !== count($row)) {
            // Skip malformed rows
            continue;
        }
        $row_data = array_combine($header, $row);

        $post_title = isset($row_data['post_title']) ? sanitize_text_field(wp_unslash($row_data['post_title'])) : '';
        if (empty($post_title)) {
            continue; // A title is required
        }

        $post_parent_title = isset($row_data['post_parent']) ? sanitize_text_field(wp_unslash($row_data['post_parent'])) : '';
        $meta_description = isset($row_data['meta_description']) ? sanitize_textarea_field(wp_unslash($row_data['meta_description'])) : '';
        $featured_image_url = isset($row_data['featured_image']) ? esc_url_raw(wp_unslash($row_data['featured_image'])) : '';
        $page_template = isset($row_data['page_template']) ? sanitize_text_field(wp_unslash($row_data['page_template'])) : '';
        $post_status = isset($row_data['post_status']) ? sanitize_key(wp_unslash($row_data['post_status'])) : 'publish';
        
        // Use custom slug if provided, otherwise generate SEO-optimized slug
        $post_name = isset($row_data['slug']) && !empty($row_data['slug']) 
            ? sanitize_title(wp_unslash($row_data['slug']))
            : aiopms_generate_seo_slug($post_title);

        // Determine parent ID
        $parent_id = 0;
        if (!empty($post_parent_title) && isset($page_map[$post_parent_title])) {
            $parent_id = $page_map[$post_parent_title];
        }

        // Prepare page data for batch processing
        $pages_to_create[] = array(
            'post_title'   => $post_title,
            'post_name'    => $post_name,
            'post_content' => '',
            'post_status'  => $post_status,
            'post_type'    => 'page',
            'post_parent'  => $parent_id,
            'page_template' => $page_template,
            'post_excerpt'  => $meta_description,
            'featured_image_url' => $featured_image_url,
            'post_title_original' => $post_title
        );
    }

    // Process pages using batch processing if available
    if (class_exists('AIOPMS_DB_Optimizer') && count($pages_to_create) > 10) {
        $db_optimizer = AIOPMS_DB_Optimizer::get_instance();
        $batch_result = $db_optimizer->batch_insert_posts($pages_to_create, AIOPMS_BATCH_SIZE);
        
        $created_pages = $batch_result['inserted'];
        $errors = $batch_result['errors'];
        
        // Process featured images for created pages
        if ($created_pages > 0) {
            foreach ($pages_to_create as $page_data) {
                if (!empty($page_data['featured_image_url'])) {
                    $page_id = get_page_by_path($page_data['post_name'], OBJECT, 'page');
                    if ($page_id) {
                        $image_title = "Featured Image for " . sanitize_text_field($page_data['post_title_original']);
                        $keywords = aiopms_extract_primary_keywords($page_data['post_title_original']);
                        $image_alt = "Visual representation of " . $keywords . " concept";
                        $image_description = "Featured image for " . sanitize_text_field($page_data['post_title_original']) . " page";
                        
                        aiopms_set_featured_image($page_id->ID, $page_data['featured_image_url'], $image_title, $image_alt, $image_description);
                    }
                }
            }
        }
    } else {
        // Process pages individually
        foreach ($pages_to_create as $page_data) {
            $page_id = wp_insert_post($page_data);
            
            if ($page_id && !is_wp_error($page_id)) {
                $created_pages++;
                $page_map[$page_data['post_title']] = $page_id;
                
                // Set featured image with SEO metadata
                if (!empty($page_data['featured_image_url'])) {
                    $image_title = "Featured Image for " . sanitize_text_field($page_data['post_title_original']);
                    $keywords = aiopms_extract_primary_keywords($page_data['post_title_original']);
                    $image_alt = "Visual representation of " . $keywords . " concept";
                    $image_description = "Featured image for " . sanitize_text_field($page_data['post_title_original']) . " page";
                    
                    aiopms_set_featured_image($page_id, $page_data['featured_image_url'], $image_title, $image_alt, $image_description);
                }
            }
        }
    }

    if ($created_pages > 0) {
        echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d pages created successfully from the CSV file!', 'aiopms'), absint($created_pages)) . '</p></div>';
    } else {
        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('No pages were created from the CSV file. Please check the file format and content.', 'aiopms') . '</p></div>';
    }
}

// Get user-friendly upload error message
function aiopms_get_upload_error_message($error_code) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => __('File exceeds the maximum upload size allowed by the server configuration.', 'aiopms'),
        UPLOAD_ERR_FORM_SIZE => __('File exceeds the maximum upload size specified in the form.', 'aiopms'),
        UPLOAD_ERR_PARTIAL => __('File was only partially uploaded. Please try again.', 'aiopms'),
        UPLOAD_ERR_NO_FILE => __('No file was uploaded. Please select a file to upload.', 'aiopms'),
        UPLOAD_ERR_NO_TMP_DIR => __('Missing temporary folder on the server. Please contact the administrator.', 'aiopms'),
        UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk. Please try again.', 'aiopms'),
        UPLOAD_ERR_EXTENSION => __('File upload was stopped by a PHP extension. Please contact the administrator.', 'aiopms'),
    ];

    return isset($error_messages[$error_code]) ? $error_messages[$error_code] : __('Unknown upload error occurred. Please try again.', 'aiopms');
}

// Get maximum allowed file size for display
function aiopms_get_max_file_size_display() {
    $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
    $max_size_mb = round($max_file_size / (1024 * 1024), 2);
    return sprintf(__('Maximum file size: %s MB', 'aiopms'), $max_size_mb);
}

// Validate file size before upload (client-side validation helper)
function aiopms_validate_file_size_before_upload($file_size) {
    $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
    return $file_size <= $max_file_size;
}
