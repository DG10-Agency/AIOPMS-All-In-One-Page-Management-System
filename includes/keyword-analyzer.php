<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Keyword Density Analyzer for AIOPMS Plugin
 * Provides comprehensive keyword analysis functionality
 */

class AIOPMS_Keyword_Analyzer {
    
    private $plugin_url;
    private $plugin_path;
    
    public function __construct() {
        $this->plugin_url = AIOPMS_PLUGIN_URL;
        $this->plugin_path = AIOPMS_PLUGIN_PATH;
        
        // Initialize hooks
        add_action('wp_ajax_aiopms_analyze_keywords', array($this, 'analyze_keywords_ajax'));
        add_action('wp_ajax_aiopms_get_pages', array($this, 'get_pages_ajax'));
        add_action('wp_ajax_aiopms_export_keyword_analysis', array($this, 'export_analysis_ajax'));
    }
    
    /**
     * Get all published pages for dropdown
     */
    public function get_pages_ajax() {
        // Verify nonce for security
        if (!check_ajax_referer('aiopms_keyword_analysis', 'nonce', false)) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'aiopms'));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to access this feature.', 'aiopms'));
        }
        
        $pages = get_posts(array(
            'post_type' => array('page', 'post'),
            'post_status' => 'publish',
            'numberposts' => 500, // Limit to 500 to prevent memory exhaustion on large sites
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $formatted_pages = array();
        foreach ($pages as $page) {
            $formatted_pages[] = array(
                'id' => $page->ID,
                'title' => $page->post_title,
                'url' => get_permalink($page->ID),
                'type' => $page->post_type
            );
        }
        
        wp_send_json_success($formatted_pages);
    }
    
    /**
     * Analyze keywords for a specific page
     */
    public function analyze_keywords_ajax() {
        // Verify nonce for security
        if (!check_ajax_referer('aiopms_keyword_analysis', 'nonce', false)) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'aiopms'));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to access this feature.', 'aiopms'));
        }
        
        // Validate and sanitize input data
        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
        $keywords_input = isset($_POST['keywords']) ? sanitize_textarea_field($_POST['keywords']) : '';
        
        // Additional validation
        if (empty($page_id) || $page_id <= 0) {
            wp_send_json_error(__('Invalid page ID provided.', 'aiopms'));
        }
        
        if (empty($keywords_input)) {
            wp_send_json_error(__('Keywords are required for analysis.', 'aiopms'));
        }
        
        // Check if page exists and user has access
        $page = get_post($page_id);
        if (!$page || $page->post_status !== 'publish') {
            wp_send_json_error(__('Page not found or not accessible.', 'aiopms'));
        }
        
        // Rate limiting check
        if (!$this->check_rate_limit()) {
            wp_send_json_error(__('Too many requests. Please wait a moment before trying again.', 'aiopms'));
        }
        
        try {
            $analysis = $this->analyze_page_keywords($page_id, $keywords_input);
            
            if ($analysis) {
                // Log successful analysis
                $this->log_analysis_activity($page_id, $keywords_input, true);
                wp_send_json_success($analysis);
            } else {
                $this->log_analysis_activity($page_id, $keywords_input, false, 'Analysis failed');
                wp_send_json_error(__('Failed to analyze keywords. Please try again.', 'aiopms'));
            }
        } catch (Exception $e) {
            // Log error
            $this->log_analysis_activity($page_id, $keywords_input, false, $e->getMessage());
            wp_send_json_error(__('An error occurred during analysis. Please try again.', 'aiopms'));
        }
    }
    
    /**
     * Export keyword analysis results
     */
    public function export_analysis_ajax() {
        // Verify nonce for security
        if (!check_ajax_referer('aiopms_keyword_analysis', 'nonce', false)) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'aiopms'));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to access this feature.', 'aiopms'));
        }
        
        // Validate and sanitize input data
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : '';
        $analysis_data_raw = isset($_POST['analysis_data']) ? $_POST['analysis_data'] : '';
        
        // Validate format
        if (!in_array($format, array('csv', 'json'))) {
            wp_send_json_error(__('Invalid export format specified.', 'aiopms'));
        }
        
        // Validate and sanitize analysis data
        if (empty($analysis_data_raw)) {
            wp_send_json_error(__('No analysis data provided for export.', 'aiopms'));
        }
        
        // Decode and validate JSON data
        $analysis_data = json_decode(stripslashes($analysis_data_raw), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid analysis data format.', 'aiopms'));
        }
        
        // Validate data structure
        if (!$this->validate_analysis_data($analysis_data)) {
            wp_send_json_error(__('Invalid analysis data structure.', 'aiopms'));
        }
        
        // Rate limiting check
        if (!$this->check_rate_limit('export')) {
            wp_send_json_error(__('Too many export requests. Please wait a moment before trying again.', 'aiopms'));
        }
        
        try {
            $this->export_analysis($format, $analysis_data);
        } catch (Exception $e) {
            // Log error
            error_log('AIOPMS Export Error: ' . $e->getMessage());
            wp_send_json_error(__('Export failed. Please try again.', 'aiopms'));
        }
    }
    
    /**
     * Check rate limiting for AJAX requests
     */
    private function check_rate_limit($action = 'analysis') {
        $user_id = get_current_user_id();
        $transient_key = 'aiopms_rate_limit_' . $action . '_' . $user_id;
        
        $requests = get_transient($transient_key);
        if ($requests === false) {
            $requests = 0;
        }
        
        // Allow 10 requests per minute for analysis, 5 for export
        $limit = ($action === 'export') ? 5 : 10;
        
        if ($requests >= $limit) {
            return false;
        }
        
        // Increment counter
        set_transient($transient_key, $requests + 1, 60); // 60 seconds
        
        return true;
    }
    
    /**
     * Log analysis activity for security monitoring
     */
    private function log_analysis_activity($page_id, $keywords, $success, $error_message = '') {
        $user_id = get_current_user_id();
        $user_ip = $this->get_user_ip();
        
        $log_data = array(
            'user_id' => $user_id,
            'page_id' => $page_id,
            'keywords_count' => count($this->extract_keywords($keywords)),
            'success' => $success,
            'error_message' => $error_message,
            'ip_address' => $user_ip,
            'timestamp' => current_time('mysql'),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : ''
        );
        
        // Log to WordPress error log for security monitoring
        error_log('AIOPMS Analysis Activity: ' . json_encode($log_data));
        
        // Store in database for detailed tracking (optional)
        $this->store_analysis_log($log_data);
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Store analysis log in database
     */
    private function store_analysis_log($log_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aiopms_generation_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'page_id' => $log_data['page_id'],
                'generation_type' => 'keyword_analysis',
                'ai_provider' => 'none',
                'tokens_used' => 0,
                'generation_time' => $log_data['timestamp'],
                'success' => $log_data['success'] ? 1 : 0,
                'error_message' => $log_data['error_message']
            ),
            array('%d', '%s', '%s', '%d', '%s', '%d', '%s')
        );
    }
    
    /**
     * Validate analysis data structure
     */
    private function validate_analysis_data($data) {
        // Check required structure
        if (!is_array($data)) {
            return false;
        }
        
        $required_keys = array('page_info', 'keywords', 'summary');
        foreach ($required_keys as $key) {
            if (!isset($data[$key])) {
                return false;
            }
        }
        
        // Validate page_info structure
        $page_info_required = array('id', 'title', 'url', 'type', 'word_count', 'analysis_date');
        foreach ($page_info_required as $key) {
            if (!isset($data['page_info'][$key])) {
                return false;
            }
        }
        
        // Validate keywords is array
        if (!is_array($data['keywords'])) {
            return false;
        }
        
        // Validate summary structure
        $summary_required = array('total_keywords', 'keywords_found', 'total_words', 'average_density');
        foreach ($summary_required as $key) {
            if (!isset($data['summary'][$key])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Main keyword analysis function with performance optimizations
     */
    public function analyze_page_keywords($page_id, $keywords_input) {
        // Start memory monitoring
        $start_memory = memory_get_usage();
        
        // Get page content
        $page = get_post($page_id);
        if (!$page) {
            return false;
        }
        
        // Extract and clean keywords
        $keywords = $this->extract_keywords($keywords_input);
        if (empty($keywords)) {
            return false;
        }
        
        // Limit keywords for performance (max 50 keywords)
        if (count($keywords) > 50) {
            $keywords = array_slice($keywords, 0, 50);
        }
        
        // Get page content for analysis with caching
        $content_data = $this->extract_page_content($page);
        
        // Check content size and warn if too large
        $content_size = strlen($content_data['full_content']);
        if ($content_size > 100000) { // 100KB limit
            error_log('AIOPMS: Large content detected (' . $content_size . ' bytes) for page ID: ' . $page_id);
        }
        
        // Analyze each keyword with progress tracking
        $results = array();
        $total_words = $this->count_total_words($content_data['full_content']);
        
        // Pre-process content for better performance
        $content_lower = strtolower($content_data['full_content']);
        $content_words = $this->extract_words($content_lower);
        
        foreach ($keywords as $index => $keyword) {
            // Memory check every 10 keywords
            if ($index % 10 === 0) {
                $current_memory = memory_get_usage();
                if (($current_memory - $start_memory) > 50 * 1024 * 1024) { // 50MB limit
                    error_log('AIOPMS: Memory limit reached during keyword analysis');
                    break;
                }
            }
            
            $keyword_analysis = $this->analyze_single_keyword_optimized($keyword, $content_data, $total_words, $content_lower, $content_words);
            if ($keyword_analysis) {
                $results[] = $keyword_analysis;
            }
        }
        
        // Sort by density (highest first)
        usort($results, function($a, $b) {
            return $b['density'] <=> $a['density'];
        });
        
        // Log memory usage
        $end_memory = memory_get_usage();
        $memory_used = ($end_memory - $start_memory) / 1024 / 1024; // MB
        error_log('AIOPMS: Keyword analysis memory usage: ' . round($memory_used, 2) . 'MB');
        
        return array(
            'page_info' => array(
                'id' => $page->ID,
                'title' => $page->post_title,
                'url' => get_permalink($page->ID),
                'type' => $page->post_type,
                'word_count' => $total_words,
                'analysis_date' => current_time('Y-m-d H:i:s'),
                'content_size' => $content_size,
                'memory_used' => round($memory_used, 2)
            ),
            'keywords' => $results,
            'summary' => $this->generate_summary($results, $total_words)
        );
    }
    
    /**
     * Extract keywords from input text
     */
    private function extract_keywords($keywords_input) {
        // Split by newlines and commas
        $lines = preg_split('/[\r\n]+/', $keywords_input);
        $keywords = array();
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Split by commas if present
            $comma_separated = explode(',', $line);
            foreach ($comma_separated as $keyword) {
                $keyword = trim($keyword);
                if (!empty($keyword) && strlen($keyword) > 1) {
                    $keywords[] = $keyword;
                }
            }
        }
        
        // Remove duplicates and return
        return array_unique($keywords);
    }
    
    /**
     * Extract all relevant content from a page
     */
    private function extract_page_content($page) {
        // Get meta description
        $meta_description = get_post_meta($page->ID, '_yoast_wpseo_metadesc', true);
        if (empty($meta_description)) {
            $meta_description = get_post_meta($page->ID, '_aioseo_description', true);
        }
        
        // Get excerpt
        $excerpt = $page->post_excerpt;
        if (empty($excerpt)) {
            $excerpt = wp_trim_words($page->post_content, 55);
        }
        
        // Extract headings
        $headings = $this->extract_headings($page->post_content);
        
        // Clean main content
        $clean_content = $this->clean_content($page->post_content);
        
        // Combine all content for analysis
        $full_content = implode(' ', array(
            $page->post_title,
            $meta_description,
            $excerpt,
            $clean_content,
            implode(' ', $headings)
        ));
        
        return array(
            'title' => $page->post_title,
            'content' => $clean_content,
            'meta_description' => $meta_description,
            'excerpt' => $excerpt,
            'headings' => $headings,
            'full_content' => $full_content
        );
    }
    
    /**
     * Extract headings from content
     */
    private function extract_headings($content) {
        $headings = array();
        
        // Match h1-h6 tags
        preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $heading) {
                $clean_heading = wp_strip_all_tags($heading);
                if (!empty($clean_heading)) {
                    $headings[] = $clean_heading;
                }
            }
        }
        
        return $headings;
    }
    
    /**
     * Clean content by removing HTML and normalizing text
     */
    private function clean_content($content) {
        // Remove HTML tags
        $content = wp_strip_all_tags($content);
        
        // Decode HTML entities
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        
        // Normalize whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        return trim($content);
    }
    
    /**
     * Count total words in content
     */
    private function count_total_words($content) {
        $words = str_word_count($content, 0, '0123456789');
        return $words;
    }
    
    /**
     * Extract words from content for better analysis
     */
    private function extract_words($content) {
        // Remove HTML tags and normalize
        $content = preg_replace('/<[^>]+>/', ' ', $content);
        $content = preg_replace('/[^\w\s]/', ' ', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        
        return explode(' ', trim($content));
    }
    
    /**
     * Optimized single keyword analysis
     */
    private function analyze_single_keyword_optimized($keyword, $content_data, $total_words, $content_lower, $content_words) {
        $keyword_lower = strtolower(trim($keyword));
        if (empty($keyword_lower)) {
            return false;
        }
        
        $keyword_count = 0;
        $positions = array();
        
        // Count occurrences in different content areas with improved accuracy
        $areas = array(
            'title' => $content_data['title'],
            'content' => $content_data['content'],
            'meta_description' => $content_data['meta_description'],
            'excerpt' => $content_data['excerpt'],
            'headings' => implode(' ', $content_data['headings'])
        );
        
        $area_counts = array();
        foreach ($areas as $area_name => $area_content) {
            if (empty($area_content)) continue;
            
            $area_content_lower = strtolower($area_content);
            
            // Use word boundary matching for better accuracy
            $area_count = $this->count_keyword_occurrences($keyword_lower, $area_content_lower);
            $area_counts[$area_name] = $area_count;
            $keyword_count += $area_count;
        }
        
        // Find positions in full content (limit to first 20 for performance)
        $offset = 0;
        $position_count = 0;
        while (($pos = strpos($content_lower, $keyword_lower, $offset)) !== false && $position_count < 20) {
            $positions[] = $pos;
            $offset = $pos + 1;
            $position_count++;
        }
        
        // Calculate density with improved accuracy
        $density = $total_words > 0 ? ($keyword_count / $total_words) * 100 : 0;
        
        // Determine status with more nuanced thresholds
        $status = $this->get_keyword_status_improved($density, $keyword_count, $total_words);
        
        return array(
            'keyword' => $keyword,
            'count' => $keyword_count,
            'density' => round($density, 2),
            'status' => $status,
            'positions' => $positions,
            'area_counts' => $area_counts,
            'context' => $this->get_keyword_context_optimized($keyword_lower, $content_data['content'], 3),
            'relevance_score' => $this->calculate_relevance_score($keyword_lower, $content_data, $keyword_count)
        );
    }
    
    /**
     * Count keyword occurrences with word boundary matching
     */
    private function count_keyword_occurrences($keyword, $content) {
        // Handle multi-word keywords
        if (strpos($keyword, ' ') !== false) {
            $keyword_parts = explode(' ', $keyword);
            $count = 0;
            $offset = 0;
            
            while (($pos = strpos($content, $keyword, $offset)) !== false) {
                // Check word boundaries
                $before = $pos > 0 ? $content[$pos - 1] : ' ';
                $after = $pos + strlen($keyword) < strlen($content) ? $content[$pos + strlen($keyword)] : ' ';
                
                if (!ctype_alnum($before) && !ctype_alnum($after)) {
                    $count++;
                }
                $offset = $pos + 1;
            }
            return $count;
        } else {
            // Single word keyword with word boundary matching
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            return preg_match_all($pattern, $content);
        }
    }
    
    /**
     * Calculate relevance score for keyword
     */
    private function calculate_relevance_score($keyword, $content_data, $count) {
        $score = 0;
        
        // Title presence (high weight)
        if (stripos($content_data['title'], $keyword) !== false) {
            $score += 30;
        }
        
        // Meta description presence (medium weight)
        if (!empty($content_data['meta_description']) && stripos($content_data['meta_description'], $keyword) !== false) {
            $score += 20;
        }
        
        // Headings presence (medium weight)
        if (!empty($content_data['headings'])) {
            $headings_text = implode(' ', $content_data['headings']);
            if (stripos($headings_text, $keyword) !== false) {
                $score += 15;
            }
        }
        
        // Content frequency (weighted by count)
        $score += min($count * 2, 25);
        
        return min($score, 100); // Cap at 100
    }
    
    
    /**
     * Improved keyword status with more nuanced analysis
     */
    private function get_keyword_status_improved($density, $count, $total_words) {
        // Consider both density and absolute count
        if ($density >= 3.0 || ($density >= 2.0 && $count >= 10)) {
            return 'high';
        } elseif ($density >= 1.5 || ($density >= 1.0 && $count >= 5)) {
            return 'good';
        } elseif ($density >= 0.8 || ($density >= 0.5 && $count >= 3)) {
            return 'moderate';
        } elseif ($density >= 0.2 || $count >= 1) {
            return 'low';
        } else {
            return 'none';
        }
    }
    
    
    /**
     * Optimized context extraction with better performance
     */
    private function get_keyword_context_optimized($keyword, $content, $context_words = 3) {
        $contexts = array();
        $content_lower = strtolower($content);
        
        // Clean content for better word extraction
        $clean_content = preg_replace('/[^\w\s]/', ' ', $content);
        $words = preg_split('/\s+/', trim($clean_content));
        $words_lower = array_map('strtolower', $words);
        
        $found_count = 0;
        foreach ($words_lower as $index => $word) {
            if ($found_count >= 5) break; // Limit contexts for performance
            
            if (strpos($word, $keyword) !== false) {
                $start = max(0, $index - $context_words);
                $end = min(count($words) - 1, $index + $context_words);
                
                $context = array_slice($words, $start, $end - $start + 1);
                $context_text = implode(' ', $context);
                
                // Highlight the keyword in context
                $context_text = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<strong>' . $keyword . '</strong>', $context_text);
                $contexts[] = $context_text;
                $found_count++;
            }
        }
        
        return $contexts;
    }
    
    /**
     * Generate analysis summary with enhanced metrics
     */
    private function generate_summary($results, $total_words) {
        $total_keywords = count($results);
        $keywords_found = count(array_filter($results, function($r) { return $r['count'] > 0; }));
        $avg_density = $total_keywords > 0 ? array_sum(array_column($results, 'density')) / $total_keywords : 0;
        
        $status_counts = array(
            'high' => 0,
            'good' => 0,
            'moderate' => 0,
            'low' => 0,
            'none' => 0
        );
        
        $total_relevance_score = 0;
        $keywords_with_relevance = 0;
        
        foreach ($results as $result) {
            $status_counts[$result['status']]++;
            
            if (isset($result['relevance_score'])) {
                $total_relevance_score += $result['relevance_score'];
                $keywords_with_relevance++;
            }
        }
        
        $avg_relevance = $keywords_with_relevance > 0 ? $total_relevance_score / $keywords_with_relevance : 0;
        
        // Calculate SEO score
        $seo_score = $this->calculate_seo_score($results, $total_words);
        
        return array(
            'total_keywords' => $total_keywords,
            'keywords_found' => $keywords_found,
            'total_words' => $total_words,
            'average_density' => round($avg_density, 2),
            'average_relevance' => round($avg_relevance, 1),
            'seo_score' => $seo_score,
            'status_distribution' => $status_counts,
            'recommendations' => $this->generate_recommendations($results, $status_counts),
            'performance_metrics' => $this->get_performance_metrics($results)
        );
    }
    
    /**
     * Calculate overall SEO score
     */
    private function calculate_seo_score($results, $total_words) {
        $score = 0;
        $max_score = 100;
        
        // Keyword coverage (30 points)
        $found_keywords = count(array_filter($results, function($r) { return $r['count'] > 0; }));
        $coverage_score = min(($found_keywords / count($results)) * 30, 30);
        $score += $coverage_score;
        
        // Density optimization (25 points)
        $good_density_count = count(array_filter($results, function($r) { 
            return $r['density'] >= 0.5 && $r['density'] <= 2.5; 
        }));
        $density_score = min(($good_density_count / count($results)) * 25, 25);
        $score += $density_score;
        
        // Relevance score (25 points)
        $total_relevance = 0;
        $relevance_count = 0;
        foreach ($results as $result) {
            if (isset($result['relevance_score'])) {
                $total_relevance += $result['relevance_score'];
                $relevance_count++;
            }
        }
        if ($relevance_count > 0) {
            $avg_relevance = $total_relevance / $relevance_count;
            $relevance_score = ($avg_relevance / 100) * 25;
            $score += $relevance_score;
        }
        
        // Content length optimization (20 points)
        if ($total_words >= 300 && $total_words <= 2000) {
            $score += 20; // Optimal length
        } elseif ($total_words >= 200 && $total_words < 300) {
            $score += 15; // Good length
        } elseif ($total_words > 2000 && $total_words <= 3000) {
            $score += 15; // Acceptable but long
        } else {
            $score += 5; // Too short or too long
        }
        
        return min(round($score), $max_score);
    }
    
    /**
     * Get performance metrics
     */
    private function get_performance_metrics($results) {
        $metrics = array(
            'over_optimized' => 0,
            'under_optimized' => 0,
            'well_optimized' => 0,
            'not_found' => 0
        );
        
        foreach ($results as $result) {
            switch ($result['status']) {
                case 'high':
                    $metrics['over_optimized']++;
                    break;
                case 'good':
                case 'moderate':
                    $metrics['well_optimized']++;
                    break;
                case 'low':
                    $metrics['under_optimized']++;
                    break;
                case 'none':
                    $metrics['not_found']++;
                    break;
            }
        }
        
        return $metrics;
    }
    
    /**
     * Generate comprehensive SEO recommendations
     */
    private function generate_recommendations($results, $status_counts) {
        $recommendations = array();
        
        // High density keywords (over-optimization)
        if ($status_counts['high'] > 0) {
            $high_keywords = array_filter($results, function($r) { return $r['status'] === 'high'; });
            $keyword_list = array_slice(array_column($high_keywords, 'keyword'), 0, 3);
            
            $recommendations[] = array(
                'type' => 'warning',
                'priority' => 'high',
                'title' => __('Over-Optimization Detected', 'aiopms'),
                'message' => sprintf(__('%d keyword(s) have high density (≥3%%). Consider reducing usage to avoid keyword stuffing penalties.', 'aiopms'), $status_counts['high']),
                'keywords' => $keyword_list,
                'action' => __('Reduce keyword frequency and use synonyms or related terms instead.', 'aiopms')
            );
        }
        
        // Low density keywords (under-optimization)
        if ($status_counts['low'] > 0) {
            $low_keywords = array_filter($results, function($r) { return $r['status'] === 'low'; });
            $keyword_list = array_slice(array_column($low_keywords, 'keyword'), 0, 3);
            
            $recommendations[] = array(
                'type' => 'info',
                'priority' => 'medium',
                'title' => __('Under-Optimization Detected', 'aiopms'),
                'message' => sprintf(__('%d keyword(s) have low density (0.2-0.8%%). Consider increasing usage naturally.', 'aiopms'), $status_counts['low']),
                'keywords' => $keyword_list,
                'action' => __('Add keywords naturally in headings, meta descriptions, and content.', 'aiopms')
            );
        }
        
        // No keywords found
        if ($status_counts['none'] > 0) {
            $none_keywords = array_filter($results, function($r) { return $r['status'] === 'none'; });
            $keyword_list = array_slice(array_column($none_keywords, 'keyword'), 0, 3);
            
            $recommendations[] = array(
                'type' => 'error',
                'priority' => 'high',
                'title' => __('Keywords Not Found', 'aiopms'),
                'message' => sprintf(__('%d keyword(s) were not found in the content.', 'aiopms'), $status_counts['none']),
                'keywords' => $keyword_list,
                'action' => __('Add these keywords to your content, title, or meta description.', 'aiopms')
            );
        }
        
        // Good optimization
        if ($status_counts['good'] > 0 || $status_counts['moderate'] > 0) {
            $good_count = $status_counts['good'] + $status_counts['moderate'];
            $recommendations[] = array(
                'type' => 'success',
                'priority' => 'low',
                'title' => __('Good Optimization', 'aiopms'),
                'message' => sprintf(__('%d keyword(s) are well-optimized with good density levels.', 'aiopms'), $good_count),
                'action' => __('Keep maintaining these keyword levels.', 'aiopms')
            );
        }
        
        // Content length recommendations
        $total_words = 0;
        if (!empty($results)) {
            // Get word count from first result (all should have same total)
            $total_words = $results[0]['total_words'] ?? 0;
        }
        
        if ($total_words > 0) {
            if ($total_words < 300) {
                $recommendations[] = array(
                    'type' => 'warning',
                    'priority' => 'medium',
                    'title' => __('Content Too Short', 'aiopms'),
                    'message' => sprintf(__('Content has only %d words. Google prefers content with 300+ words.', 'aiopms'), $total_words),
                    'action' => __('Add more valuable content to improve SEO performance.', 'aiopms')
                );
            } elseif ($total_words > 3000) {
                $recommendations[] = array(
                    'type' => 'info',
                    'priority' => 'low',
                    'title' => __('Content Very Long', 'aiopms'),
                    'message' => sprintf(__('Content has %d words. Consider breaking into multiple pages if appropriate.', 'aiopms'), $total_words),
                    'action' => __('Ensure content remains engaging and valuable throughout.', 'aiopms')
                );
            }
        }
        
        // Sort by priority
        $priority_order = array('high' => 3, 'medium' => 2, 'low' => 1);
        usort($recommendations, function($a, $b) use ($priority_order) {
            return $priority_order[$b['priority']] <=> $priority_order[$a['priority']];
        });
        
        return $recommendations;
    }
    
    /**
     * Export analysis results
     */
    private function export_analysis($format, $analysis_data) {
        $page_info = $analysis_data['page_info'];
        $keywords = $analysis_data['keywords'];
        $summary = $analysis_data['summary'];
        
        $filename = sanitize_file_name($page_info['title']) . '_keyword_analysis_' . date('Y-m-d');
        
        switch ($format) {
            case 'csv':
                $this->export_csv($filename, $page_info, $keywords, $summary);
                break;
            case 'json':
                $this->export_json($filename, $analysis_data);
                break;
            default:
                wp_die('Invalid export format');
        }
    }
    
    /**
     * Export as CSV
     */
    private function export_csv($filename, $page_info, $keywords, $summary) {
        // Sanitize filename to prevent directory traversal
        $filename = sanitize_file_name($filename);
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        
        // Set security headers
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        $output = fopen('php://output', 'w');
        
        // Page info header
        fputcsv($output, array('Page Analysis Report'));
        fputcsv($output, array('Page Title', $page_info['title']));
        fputcsv($output, array('URL', $page_info['url']));
        fputcsv($output, array('Word Count', $page_info['word_count']));
        fputcsv($output, array('Analysis Date', $page_info['analysis_date']));
        fputcsv($output, array(''));
        
        // Summary
        fputcsv($output, array('Summary'));
        fputcsv($output, array('Total Keywords', $summary['total_keywords']));
        fputcsv($output, array('Keywords Found', $summary['keywords_found']));
        fputcsv($output, array('Average Density', $summary['average_density'] . '%'));
        fputcsv($output, array(''));
        
        // Keywords data
        fputcsv($output, array('Keyword', 'Count', 'Density (%)', 'Status', 'Title', 'Content', 'Meta Description', 'Excerpt', 'Headings'));
        
        foreach ($keywords as $keyword) {
            fputcsv($output, array(
                $keyword['keyword'],
                $keyword['count'],
                $keyword['density'],
                ucfirst($keyword['status']),
                $keyword['area_counts']['title'] ?? 0,
                $keyword['area_counts']['content'] ?? 0,
                $keyword['area_counts']['meta_description'] ?? 0,
                $keyword['area_counts']['excerpt'] ?? 0,
                $keyword['area_counts']['headings'] ?? 0
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export as JSON
     */
    private function export_json($filename, $analysis_data) {
        // Sanitize filename to prevent directory traversal
        $filename = sanitize_file_name($filename);
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        
        // Set security headers
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        echo json_encode($analysis_data, JSON_PRETTY_PRINT);
        exit;
    }
}

// Initialize the keyword analyzer
new AIOPMS_Keyword_Analyzer();
