<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Memory monitoring utilities
if (!function_exists('aiopms_get_memory_usage')) {
    function aiopms_get_memory_usage() {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ];
    }
}

if (!function_exists('aiopms_log_memory_usage')) {
    function aiopms_log_memory_usage($context, $additional_info = '') {
        $memory = aiopms_get_memory_usage();
        $message = sprintf(
            'AIOPMS Memory Usage [%s]: Current: %s MB, Peak: %s MB, Limit: %s%s',
            $context,
            $memory['current_mb'],
            $memory['peak_mb'],
            $memory['limit'],
            $additional_info ? ' - ' . $additional_info : ''
        );
        error_log($message);
        return $memory;
    }
}

if (!function_exists('aiopms_check_memory_limit')) {
    function aiopms_check_memory_limit($threshold_percent = 80) {
        $memory = aiopms_get_memory_usage();
        
        // Convert memory limit to bytes
        $limit_bytes = aiopms_convert_memory_limit_to_bytes($memory['limit']);
        
        if ($limit_bytes > 0) {
            $usage_percent = ($memory['current'] / $limit_bytes) * 100;
            
            if ($usage_percent >= $threshold_percent) {
                aiopms_log_memory_usage('WARNING', sprintf(
                    'Memory usage at %.1f%% of limit (%s). Consider optimizing or increasing memory limit.',
                    $usage_percent,
                    $memory['limit']
                ));
                return false;
            }
        }
        
        return true;
    }
}

if (!function_exists('aiopms_convert_memory_limit_to_bytes')) {
    function aiopms_convert_memory_limit_to_bytes($memory_limit) {
        if ($memory_limit == -1) {
            return PHP_INT_MAX; // Unlimited memory
        }
        
        $memory_limit = trim($memory_limit);
        $last = strtolower($memory_limit[strlen($memory_limit) - 1]);
        $value = (int) $memory_limit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}

if (!function_exists('aiopms_monitor_memory_usage')) {
    function aiopms_monitor_memory_usage($context, $start_memory = null, $additional_info = '') {
        $current_memory = aiopms_get_memory_usage();
        
        if ($start_memory === null) {
            // Starting memory monitoring
            aiopms_log_memory_usage($context . ' - START', $additional_info);
            return $current_memory;
        } else {
            // Ending memory monitoring
            $memory_diff = $current_memory['current'] - $start_memory['current'];
            $memory_diff_mb = round($memory_diff / 1024 / 1024, 2);
            
            $additional_info .= sprintf(' (Memory used: %s MB)', $memory_diff_mb);
            aiopms_log_memory_usage($context . ' - END', $additional_info);
            
            // Check if memory usage is concerning
            if ($memory_diff_mb > 50) { // More than 50MB used
                aiopms_log_memory_usage('HIGH_MEMORY_USAGE', sprintf(
                    '%s used %s MB of memory. Consider optimizing for large datasets.',
                    $context,
                    $memory_diff_mb
                ));
            }
            
            return $current_memory;
        }
    }
}

// Hierarchy tab content
// Hierarchy tab content
function abpcwa_hierarchy_tab() {
    ?>
    <div class="abpcwa-hierarchy-container dg10-form-container">
        <div class="dg10-tab-header" style="margin-bottom: 30px;">
            <h3>🌳 <?php esc_html_e('Intelligent Hierarchy Visualizer', 'aiopms'); ?></h3>
            <p><?php esc_html_e('Analyze and visualize your content architecture through multiple strategic lenses.', 'aiopms'); ?></p>
        </div>

        <div class="aiopms-hierarchy-toolbar dg10-card" style="padding: 24px; margin-bottom: 32px;">
            <div class="dg10-form-grid" style="grid-template-columns: 1fr auto; align-items: center; gap: 24px;">
                <div class="abpcwa-hierarchy-search">
                    <div class="dg10-search-wrapper">
                        <span class="search-icon">🔍</span>
                        <input type="text" id="abpcwa-hierarchy-search" placeholder="<?php esc_attr_e('Search site structure...', 'aiopms'); ?>" class="dg10-form-input">
                    </div>
                </div>

                <div class="abpcwa-view-controls dg10-btn-group">
                    <button class="dg10-btn dg10-btn-primary" data-view="grid">
                        <span class="nav-icon">📁</span> <?php esc_html_e('Grid View', 'aiopms'); ?>
                    </button>
                    <button class="dg10-btn dg10-btn-outline" data-view="tree">
                        <span class="nav-icon">🌲</span> <?php esc_html_e('Tree View', 'aiopms'); ?>
                    </button>
                    <button class="dg10-btn dg10-btn-outline" data-view="orgchart">
                        <span class="nav-icon">📊</span> <?php esc_html_e('Org Chart', 'aiopms'); ?>
                    </button>
                </div>
            </div>

            <div class="aiopms-export-controls" style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--dg10-glass-border);">
                <div class="dg10-form-grid" style="grid-template-columns: auto 1fr; align-items: center; gap: 20px;">
                    <span class="dg10-label-sm"><?php esc_html_e('Export Strategy:', 'aiopms'); ?></span>
                    <div class="aiopms-export-buttons" style="display: flex; gap: 12px;">
                        <button class="dg10-btn dg10-btn-sm dg10-btn-outline aiopms-export-trigger" data-type="csv">
                            📥 CSV
                        </button>
                        <button class="dg10-btn dg10-btn-sm dg10-btn-outline aiopms-export-trigger" data-type="markdown">
                            📝 Markdown
                        </button>
                        <button class="dg10-btn dg10-btn-sm dg10-btn-outline aiopms-export-trigger" data-type="json">
                            📦 JSON
                        </button>
                        
                        <div class="aiopms-export-divider" style="width: 1px; background: var(--dg10-glass-border); margin: 0 8px;"></div>
                        
                        <div class="aiopms-copy-action dg10-form-inline">
                            <select id="aiopms-copy-type" class="dg10-form-input dg10-input-sm">
                                <option value="titles"><?php esc_html_e('Copy Titles', 'aiopms'); ?></option>
                                <option value="urls"><?php esc_html_e('Copy URLs', 'aiopms'); ?></option>
                                <option value="both"><?php esc_html_e('Copy Titles + URLs', 'aiopms'); ?></option>
                            </select>
                            <button class="dg10-btn dg10-btn-sm dg10-btn-primary" id="aiopms-copy-hierarchy">
                                📋 <?php esc_html_e('Copy', 'aiopms'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="abpcwa-hierarchy-view-container">
            <div id="abpcwa-hierarchy-grid" class="abpcwa-hierarchy-view active-view dg10-glass-card" style="padding: 40px;">
                <div class="aiopms-loading-state">
                    <div class="dg10-spinner"></div>
                    <p><?php esc_html_e('Synthesizing site architecture...', 'aiopms'); ?></p>
                </div>
            </div>
            <div id="abpcwa-hierarchy-tree" class="abpcwa-hierarchy-view dg10-glass-card"></div>
            <div id="abpcwa-hierarchy-orgchart" class="abpcwa-hierarchy-view dg10-glass-card"></div>
        </div>

        <div class="dg10-notice dg10-notice-info" style="margin-top: 40px;">
            <p><strong><?php esc_html_e('Note:', 'aiopms'); ?></strong> <?php esc_html_e('This is an analytical visualization. To restructure your hierarchy, please use the native WordPress Page Attributes in the editor.', 'aiopms'); ?></p>
        </div>
    </div>
    <input type="hidden" id="aiopms-export-nonce" value="<?php echo wp_create_nonce('aiopms_export_nonce'); ?>">
    <?php
}

// Get page hierarchy data (read-only)
function aiopms_get_page_hierarchy() {
    try {
        // Check for cached hierarchy data
        $cached_data = get_transient('aiopms_page_hierarchy_cache');
        if (false !== $cached_data) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AIOPMS: Using cached hierarchy data');
            }
            return $cached_data;
        }
        // Start memory monitoring
        $start_memory = aiopms_monitor_memory_usage('PAGE_HIERARCHY_GENERATION');
        
        // Performance optimization: Limit pages for large datasets
        $max_pages = apply_filters('aiopms_max_hierarchy_pages', 1000);
        
        $pages = get_pages(array(
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC',
            'hierarchical' => 0, // Get flat list, we'll build hierarchy in JS
            'number' => $max_pages, // Limit for performance
            'post_status' => 'publish,private,draft' // Only get relevant statuses
        ));

        $hierarchy_data = array();
        
        // Debug: Log page count and check memory after getting pages
        $page_count = count($pages);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AIOPMS: Found ' . $page_count . ' pages');
        }
        aiopms_log_memory_usage('AFTER_GET_PAGES', "Processing {$page_count} pages");
        
        // Check memory limit before processing
        if (!aiopms_check_memory_limit(75)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AIOPMS: Memory usage warning before processing pages');
            }
            // Add warning to hierarchy data
            $hierarchy_data[] = array(
                'id' => 'memory_warning',
                'parent' => '#',
                'text' => '⚠️ Memory usage high - some pages may not be displayed',
                'type' => 'warning',
                'state' => array('opened' => false, 'disabled' => true),
                'li_attr' => array('data-warning' => 'memory'),
                'a_attr' => array('href' => '#', 'target' => '_self'),
                'meta' => array(
                    'description' => 'Consider increasing PHP memory limit or reducing page count',
                    'type' => 'System Warning'
                )
            );
        }
        
        // Add performance warning for large datasets
        if ($page_count >= $max_pages) {
            $hierarchy_data[] = array(
                'id' => 'performance_warning',
                'parent' => '#',
                'text' => '📊 Large dataset detected - showing first ' . $max_pages . ' pages',
                'type' => 'info',
                'state' => array('opened' => false, 'disabled' => true),
                'li_attr' => array('data-warning' => 'performance'),
                'a_attr' => array('href' => '#', 'target' => '_self'),
                'meta' => array(
                    'description' => 'Use filters to reduce the number of pages displayed',
                    'type' => 'Performance Notice'
                )
            );
        }
    
    // Get homepage ID for prioritization
    $homepage_id = get_option('page_on_front');
    if (!$homepage_id) {
        $homepage_id = get_option('page_for_posts');
    }
    
    // Separate homepage from other pages
    $homepage = null;
    $other_pages = array();
    
    foreach ($pages as $page) {
        if ($page->ID == $homepage_id) {
            $homepage = $page;
        } else {
            $other_pages[] = $page;
        }
    }
    
    // Add homepage first if it exists
    if ($homepage) {
        $author = get_userdata($homepage->post_author);
        $author_name = $author ? $author->display_name : 'Unknown';
        $author_login = $author ? $author->user_login : 'unknown';
        
        $publish_date = date('M j, Y', strtotime($homepage->post_date));
        $modified_date = date('M j, Y', strtotime($homepage->post_modified));
        
        $hierarchy_data[] = array(
            'id' => $homepage->ID,
            'parent' => $homepage->post_parent ? $homepage->post_parent : '#',
            'text' => esc_html($homepage->post_title),
            'type' => 'page',
            'state' => array(
                'opened' => false,
                'disabled' => false
            ),
            'li_attr' => array(
                'data-page-id' => $homepage->ID,
                'data-page-status' => $homepage->post_status,
                'data-is-homepage' => 'true'
            ),
            'a_attr' => array(
                'href' => get_permalink($homepage->ID),
                'target' => '_blank',
                'title' => 'View: ' . esc_attr($homepage->post_title)
            ),
            'meta' => array(
                'description' => esc_html($homepage->post_excerpt),
                'published' => $publish_date,
                'modified' => $modified_date,
                'author' => $author_name . ' (' . $author_login . ')',
                'status' => $homepage->post_status,
                'is_homepage' => true
            )
        );
    }
    
    // Add other pages (sorted alphabetically)
    foreach ($other_pages as $page) {
        // Get author information
        $author = get_userdata($page->post_author);
        $author_name = $author ? $author->display_name : 'Unknown';
        $author_login = $author ? $author->user_login : 'unknown';
        
        // Format dates
        $publish_date = date('M j, Y', strtotime($page->post_date));
        $modified_date = date('M j, Y', strtotime($page->post_modified));
        
        $hierarchy_data[] = array(
            'id' => $page->ID,
            'parent' => $page->post_parent ? $page->post_parent : '#',
            'text' => esc_html($page->post_title),
            'type' => 'page',
            'state' => array(
                'opened' => false,
                'disabled' => false
            ),
            'li_attr' => array(
                'data-page-id' => $page->ID,
                'data-page-status' => $page->post_status
            ),
            'a_attr' => array(
                'href' => get_permalink($page->ID),
                'target' => '_blank',
                'title' => 'View: ' . esc_attr($page->post_title)
            ),
            'meta' => array(
                'description' => esc_html($page->post_excerpt),
                'published' => $publish_date,
                'modified' => $modified_date,
                'author' => $author_name . ' (' . $author_login . ')',
                'status' => $page->post_status
            )
        );
    }

    // Log memory usage after processing standard pages
    aiopms_log_memory_usage('AFTER_STANDARD_PAGES', "Processed {$page_count} standard pages");

    // Add custom post types if enabled in settings
    $settings = get_option('aiopms_cpt_settings', array());
    if (isset($settings['include_in_hierarchy']) && $settings['include_in_hierarchy']) {
        $dynamic_cpts = get_option('aiopms_dynamic_cpts', []);
        
        if (is_array($dynamic_cpts)) {
            foreach ($dynamic_cpts as $post_type => $cpt_info) {
                if (!is_array($cpt_info) || !isset($cpt_info['label'])) {
                    continue; // Skip invalid CPT entries
                }
            // Add CPT archive as a parent node
            $archive_url = get_post_type_archive_link($post_type);
            if ($archive_url) {
                $hierarchy_data[] = array(
                    'id' => 'cpt_archive_' . $post_type,
                    'parent' => '#',
                    'text' => $cpt_info['label'] . ' (Archive)',
                    'type' => 'cpt_archive',
                    'state' => array(
                        'opened' => false,
                        'disabled' => false
                    ),
                    'li_attr' => array(
                        'data-cpt-type' => $post_type,
                        'data-cpt-archive' => 'true'
                    ),
                    'a_attr' => array(
                        'href' => $archive_url,
                        'target' => '_blank',
                        'title' => 'View Archive: ' . esc_attr($cpt_info['label'])
                    ),
                    'meta' => array(
                        'description' => 'Archive page for ' . $cpt_info['label'],
                        'type' => 'Custom Post Type Archive'
                    )
                );
            }
            
            // Add individual CPT posts as children
            $cpt_posts = get_posts(array(
                'post_type' => $post_type,
                'numberposts' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            foreach ($cpt_posts as $post) {
                $author = get_userdata($post->post_author);
                $author_name = $author ? $author->display_name : 'Unknown';
                $author_login = $author ? $author->user_login : 'unknown';
                
                $publish_date = date('M j, Y', strtotime($post->post_date));
                $modified_date = date('M j, Y', strtotime($post->post_modified));
                
                $hierarchy_data[] = array(
                    'id' => 'cpt_' . $post->ID,
                    'parent' => 'cpt_archive_' . $post_type,
                    'text' => esc_html($post->post_title),
                    'type' => 'cpt_post',
                    'state' => array(
                        'opened' => false,
                        'disabled' => false
                    ),
                    'li_attr' => array(
                        'data-post-id' => $post->ID,
                        'data-post-type' => $post_type,
                        'data-post-status' => $post->post_status
                    ),
                    'a_attr' => array(
                        'href' => get_permalink($post->ID),
                        'target' => '_blank',
                        'title' => 'View: ' . esc_attr($post->post_title)
                    ),
                    'meta' => array(
                        'description' => esc_html($post->post_excerpt),
                        'published' => $publish_date,
                        'modified' => $modified_date,
                        'author' => $author_name . ' (' . $author_login . ')',
                        'status' => $post->post_status,
                        'type' => $cpt_info['label']
                    )
                );
            }
        }
        }
    }

    // Log memory usage after processing custom post types
    aiopms_log_memory_usage('AFTER_CUSTOM_POST_TYPES', "Completed hierarchy data generation");
    
    // End memory monitoring and log final results
    $final_memory = aiopms_monitor_memory_usage('PAGE_HIERARCHY_GENERATION', $start_memory, 
        "Generated hierarchy for " . count($hierarchy_data) . " items");
    
    // Final memory check
    if (!aiopms_check_memory_limit(90)) {
        error_log('AIOPMS: High memory usage detected after hierarchy generation');
    }

        // Cache the results for 1 hour
        set_transient('aiopms_page_hierarchy_cache', $hierarchy_data, HOUR_IN_SECONDS);
        
        return $hierarchy_data;
    } catch (Exception $e) {
        error_log('AIOPMS: Error in get_page_hierarchy: ' . $e->getMessage());
        return array(); // Return empty array on error
    }
}

// Clear hierarchy cache on post updates
function aiopms_clear_hierarchy_cache($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    delete_transient('aiopms_page_hierarchy_cache');
}
add_action('save_post', 'aiopms_clear_hierarchy_cache');
add_action('deleted_post', 'aiopms_clear_hierarchy_cache');
add_action('switch_theme', 'aiopms_clear_hierarchy_cache');

// Register REST API endpoint for read-only access
function aiopms_register_hierarchy_rest_routes() {
    register_rest_route('aiopms/v1', '/hierarchy', array(
        'methods' => 'GET',
        'callback' => 'aiopms_rest_get_hierarchy',
        'permission_callback' => function () {
            return current_user_can('edit_pages');
        }
    ));

}

// Register AJAX handlers for file exports
function aiopms_register_export_ajax_handlers() {
    add_action('wp_ajax_aiopms_export_csv', 'aiopms_ajax_export_hierarchy_csv');
    add_action('wp_ajax_aiopms_export_markdown', 'aiopms_ajax_export_hierarchy_markdown');
    add_action('wp_ajax_aiopms_export_json', 'aiopms_ajax_export_hierarchy_json');
}

// Register REST API routes on the proper hook (rest_api_init)
add_action('rest_api_init', 'aiopms_register_hierarchy_rest_routes');

// Register AJAX handlers on init (this is the correct hook for AJAX)
add_action('init', 'aiopms_register_export_ajax_handlers');

// REST: Get hierarchy data (read-only)
function aiopms_rest_get_hierarchy($request) {
    try {
        // Debug: Log that REST API callback is being called
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AIOPMS: REST API callback called');
        }
        
        $hierarchy_data = aiopms_get_page_hierarchy();
        
        // Debug: Log the data to help troubleshoot
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AIOPMS Hierarchy Data: ' . print_r($hierarchy_data, true));
        }
        
        return rest_ensure_response($hierarchy_data);
    } catch (Exception $e) {
        error_log('AIOPMS Hierarchy Error: ' . $e->getMessage());
        return new WP_Error('hierarchy_error', $e->getMessage(), array('status' => 500));
    }
}

// AJAX: Export hierarchy data as CSV
function aiopms_ajax_export_hierarchy_csv() {
    // Verify nonce for security
    if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_GET['nonce'])), 'aiopms_export_nonce')) {
        wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'aiopms'));
    }

    // Check user permissions
    if (!current_user_can('edit_pages')) {
        wp_send_json_error(__('Insufficient permissions to access this feature.', 'aiopms'));
    }

    try {
        // Start memory monitoring for CSV export
        $export_start_memory = aiopms_monitor_memory_usage('CSV_EXPORT_AJAX', null, 'Starting CSV export');
        
        $pages = get_pages(array(
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC',
            'hierarchical' => 0,
        ));

        aiopms_log_memory_usage('CSV_EXPORT_AFTER_GET_PAGES', "Retrieved " . count($pages) . " pages for export");
        
        $hierarchy_data = aiopms_build_hierarchy_for_export($pages);
        $site_title = sanitize_file_name(get_bloginfo('name'));
        $filename = $site_title . '.csv';
        $csv_content = aiopms_generate_hierarchy_csv($hierarchy_data);

        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set proper headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv_content));
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        // End memory monitoring for CSV export
        aiopms_monitor_memory_usage('CSV_EXPORT_AJAX', $export_start_memory, 
            "Completed CSV export (" . strlen($csv_content) . " bytes)");

        // Output the CSV content
        echo $csv_content;
        exit;

    } catch (Exception $e) {
        wp_die('Export failed: ' . $e->getMessage());
    }
}

// AJAX: Export hierarchy data as Markdown
function aiopms_ajax_export_hierarchy_markdown() {
    // Verify nonce for security
    if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_GET['nonce'])), 'aiopms_export_nonce')) {
        wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'aiopms'));
    }

    // Check user permissions
    if (!current_user_can('edit_pages')) {
        wp_send_json_error(__('Insufficient permissions to access this feature.', 'aiopms'));
    }

    try {
        // Start memory monitoring for Markdown export
        $export_start_memory = aiopms_monitor_memory_usage('MARKDOWN_EXPORT_AJAX', null, 'Starting Markdown export');
        
        $pages = get_pages(array(
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC',
            'hierarchical' => 0,
        ));

        aiopms_log_memory_usage('MARKDOWN_EXPORT_AFTER_GET_PAGES', "Retrieved " . count($pages) . " pages for export");
        
        $hierarchy_data = aiopms_build_hierarchy_for_export($pages);
        $site_title = sanitize_file_name(get_bloginfo('name'));
        $filename = $site_title . '.md';
        $markdown_content = aiopms_generate_hierarchy_markdown($hierarchy_data);

        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set proper headers for Markdown download
        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($markdown_content));
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        // End memory monitoring for Markdown export
        aiopms_monitor_memory_usage('MARKDOWN_EXPORT_AJAX', $export_start_memory, 
            "Completed Markdown export (" . strlen($markdown_content) . " bytes)");

        // Output the Markdown content
        echo $markdown_content;
        exit;

    } catch (Exception $e) {
        wp_die('Export failed: ' . $e->getMessage());
    }
}

// AJAX: Export hierarchy data as JSON
function aiopms_ajax_export_hierarchy_json() {
    // Verify nonce for security
    if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_GET['nonce'])), 'aiopms_export_nonce')) {
        wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'aiopms'));
    }

    // Check user permissions
    if (!current_user_can('edit_pages')) {
        wp_send_json_error(__('Insufficient permissions to access this feature.', 'aiopms'));
    }

    try {
        // Start memory monitoring for JSON export
        $export_start_memory = aiopms_monitor_memory_usage('JSON_EXPORT_AJAX', null, 'Starting JSON export');
        
        $pages = get_pages(array(
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC',
            'hierarchical' => 0,
        ));

        aiopms_log_memory_usage('JSON_EXPORT_AFTER_GET_PAGES', "Retrieved " . count($pages) . " pages for export");
        
        $hierarchy_data = aiopms_build_hierarchy_for_export($pages);
        $site_title = sanitize_file_name(get_bloginfo('name'));
        $filename = $site_title . '.json';
        
        // Convert to JSON format with proper structure
        $json_data = array(
            'site_title' => get_bloginfo('name'),
            'export_date' => current_time('Y-m-d H:i:s'),
            'total_pages' => count($hierarchy_data['pages_by_id']),
            'hierarchy' => $hierarchy_data['pages_by_id']
        );
        
        $json_content = wp_json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set proper headers for JSON download
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json_content));
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        // End memory monitoring for JSON export
        aiopms_monitor_memory_usage('JSON_EXPORT_AJAX', $export_start_memory, 
            "Completed JSON export (" . strlen($json_content) . " bytes)");

        // Output the JSON content
        echo $json_content;
        exit;

    } catch (Exception $e) {
        wp_die('Export failed: ' . $e->getMessage());
    }
}

// Enqueue hierarchy assets
function aiopms_enqueue_hierarchy_assets($hook) {
    if ($hook !== 'toplevel_page_aiopms-page-management') {
        return;
    }

    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'manual';

    if ($active_tab === 'hierarchy') {
        // Debug: Log that hierarchy assets are being enqueued
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AIOPMS: Enqueuing hierarchy assets for tab: ' . $active_tab);
        }
        
        // Enqueue jsTree
        wp_enqueue_style('jstree', AIOPMS_PLUGIN_URL . 'assets/vendor/jstree/themes/default/style.min.css');
        wp_enqueue_script('jstree', AIOPMS_PLUGIN_URL . 'assets/vendor/jstree/jstree.min.js', array('jquery'), '3.3.15', true);
        
        // Enqueue D3.js for Mind Map and Org Chart
        wp_enqueue_script('d3', AIOPMS_PLUGIN_URL . 'assets/vendor/d3/d3.v7.min.js', array(), '7.0.0', true);

        // Enqueue our hierarchy scripts
        wp_enqueue_script('aiopms-hierarchy', AIOPMS_PLUGIN_URL . 'assets/js/hierarchy.js', array('jquery', 'jstree', 'd3'), null, true);
        wp_enqueue_style('aiopms-hierarchy', AIOPMS_PLUGIN_URL . 'assets/css/hierarchy.css');
        
        // Localize script with data
        $localize_data = array(
            'rest_url' => rest_url('aiopms/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'strings' => array(
                'loading' => 'Loading page hierarchy...',
                'search' => 'Search pages...',
                'readonly_note' => 'Visualization only - use WordPress editor to modify hierarchy'
            )
        );
        
        // Debug: Log localization data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AIOPMS: Localizing script with data: ' . print_r($localize_data, true));
        }
        
        wp_localize_script('aiopms-hierarchy', 'aiopmsHierarchy', $localize_data);
    }
}
add_action('admin_enqueue_scripts', 'aiopms_enqueue_hierarchy_assets');

// Build hierarchical structure for export (including all levels)
function aiopms_build_hierarchy_for_export($pages) {
    // Start memory monitoring for export hierarchy building
    $start_memory = aiopms_monitor_memory_usage('EXPORT_HIERARCHY_BUILD', null, 
        "Building hierarchy for " . count($pages) . " pages");
    
    $pages_by_id = array();
    $pages_by_parent = array();

    foreach ($pages as $page) {
        $pages_by_id[$page->ID] = array(
            'id' => $page->ID,
            'title' => $page->post_title,
            'parent' => $page->post_parent,
            'url' => get_permalink($page->ID),
            'excerpt' => $page->post_excerpt,
            'published' => $page->post_date,
            'modified' => $page->post_modified,
            'author_id' => $page->post_author,
            'status' => $page->post_status,
            'level' => 0
        );

        if (!isset($pages_by_parent[$page->post_parent])) {
            $pages_by_parent[$page->post_parent] = array();
        }
        $pages_by_parent[$page->post_parent][] = $page->ID;
    }

    // Calculate hierarchy levels
    $roots = isset($pages_by_parent[0]) ? $pages_by_parent[0] : array();

    function calculate_levels($page_id, &$pages_by_id, $pages_by_parent, $level = 0) {
        if (!isset($pages_by_id[$page_id])) return;

        $pages_by_id[$page_id]['level'] = $level;

        if (isset($pages_by_parent[$page_id])) {
            foreach ($pages_by_parent[$page_id] as $child_id) {
                calculate_levels($child_id, $pages_by_id, $pages_by_parent, $level + 1);
            }
        }
    }

    foreach ($roots as $root_id) {
        calculate_levels($root_id, $pages_by_id, $pages_by_parent, 0);
    }

    // End memory monitoring for export hierarchy building
    aiopms_monitor_memory_usage('EXPORT_HIERARCHY_BUILD', $start_memory, 
        "Built hierarchy structure for " . count($pages_by_id) . " pages");

    return array('pages_by_id' => $pages_by_id, 'roots' => $roots);
}

// Generate CSV from hierarchy data
function aiopms_generate_hierarchy_csv($hierarchy_data) {
    // Start memory monitoring for CSV generation
    $start_memory = aiopms_monitor_memory_usage('CSV_GENERATION', null, 
        "Generating CSV for " . count($hierarchy_data['pages_by_id']) . " pages");
    
    $pages_by_id = $hierarchy_data['pages_by_id'];

    $headers = array(
        'Title',
        'URL',
        'Excerpt',
        'Published Date',
        'Modified Date',
        'Author',
        'Status',
        'Hierarchy Level',
        'Parent Page ID'
    );

    $csv_lines = array();
    $csv_lines[] = implode(',', array_map('aiopms_escape_csv', $headers));

    // Sort by hierarchy and title
    $sorted_pages = array();
    function add_page_to_sorted($page_ids, $pages_by_id, &$sorted_pages, $level = 0) {
        foreach ($page_ids as $id) {
            if (isset($pages_by_id[$id])) {
                $page = $pages_by_id[$id];
                $sorted_pages[] = $page;

                // Add children recursively
                $children = array_filter($pages_by_id, function($p) use ($id) {
                    return $p['parent'] == $id;
                });
                if (!empty($children)) {
                    $child_ids = array_map(function($c) { return $c['id']; }, $children);
                    add_page_to_sorted($child_ids, $pages_by_id, $sorted_pages, $level + 1);
                }
            }
        }
    }

    $root_ids = array_keys(array_filter($pages_by_id, function($p) { return $p['level'] == 0; }));
    add_page_to_sorted($root_ids, $pages_by_id, $sorted_pages);

    foreach ($sorted_pages as $page) {
        $author = get_userdata($page['author_id']);
        $author_name = $author ? $author->display_name : 'Unknown';

        $row = array(
            $page['title'],
            $page['url'],
            $page['excerpt'],
            date('Y-m-d', strtotime($page['published'])),
            date('Y-m-d', strtotime($page['modified'])),
            $author_name,
            $page['status'],
            $page['level'],
            $page['parent'] ?: ''
        );

        $csv_lines[] = implode(',', array_map('aiopms_escape_csv', $row));
    }

    // End memory monitoring for CSV generation
    $csv_content = implode("\n", $csv_lines);
    aiopms_monitor_memory_usage('CSV_GENERATION', $start_memory, 
        "Generated CSV with " . count($csv_lines) . " lines (" . strlen($csv_content) . " bytes)");

    return $csv_content;
}

// Generate Markdown from hierarchy data
function aiopms_generate_hierarchy_markdown($hierarchy_data) {
    // Start memory monitoring for Markdown generation
    $start_memory = aiopms_monitor_memory_usage('MARKDOWN_GENERATION', null, 
        "Generating Markdown for " . count($hierarchy_data['pages_by_id']) . " pages");
    
    $pages_by_id = $hierarchy_data['pages_by_id'];
    $roots = $hierarchy_data['roots'];

    $markdown_lines = array();
    $markdown_lines[] = '# Page Hierarchy';

    function build_markdown_level($page_ids, $pages_by_id, &$markdown_lines, $level = 0) {
        foreach ($page_ids as $id) {
            if (isset($pages_by_id[$id])) {
                $page = $pages_by_id[$id];
                $indent = str_repeat('  ', $level); // Two spaces for indentation
                $markdown_lines[] = $indent . '- [' . $page['title'] . '](' . $page['url'] . ')';

                // Find and process children
                $children = array_filter($pages_by_id, function($p) use ($id) {
                    return $p['parent'] == $id;
                });

                if (!empty($children)) {
                    $child_ids = array_map(function($c) { return $c['id']; }, $children);
                    build_markdown_level($child_ids, $pages_by_id, $markdown_lines, $level + 1);
                }
            }
        }
    }

    build_markdown_level($roots, $pages_by_id, $markdown_lines);

    // End memory monitoring for Markdown generation
    $markdown_content = implode("\n", $markdown_lines);
    aiopms_monitor_memory_usage('MARKDOWN_GENERATION', $start_memory, 
        "Generated Markdown with " . count($markdown_lines) . " lines (" . strlen($markdown_content) . " bytes)");

    return $markdown_content;
}

// Helper function to escape CSV values
function aiopms_escape_csv($string) {
    // Escape quotes and wrap in quotes if contains comma, quote, or newline
    if (strpos($string, ',') !== false || strpos($string, '"') !== false || strpos($string, "\n") !== false) {
        $string = str_replace('"', '""', $string);
        $string = '"' . $string . '"';
    }
    return $string;
}


