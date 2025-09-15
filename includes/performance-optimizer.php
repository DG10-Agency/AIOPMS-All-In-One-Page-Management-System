<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIOPMS Performance Optimizer
 * 
 * Handles caching, memory monitoring, and performance optimizations
 * for the AIOPMS plugin to ensure optimal performance.
 *
 * @package AIOPMS
 * @version 3.0
 * @author DG10 Agency
 */

// Performance constants
define('AIOPMS_CACHE_VERSION', '3.0');
define('AIOPMS_CACHE_GROUP', 'aiopms');
define('AIOPMS_CACHE_EXPIRY', 3600); // 1 hour
define('AIOPMS_MEMORY_THRESHOLD', 80); // 80% memory usage threshold
define('AIOPMS_BATCH_SIZE', 50); // Default batch size for bulk operations

/**
 * Performance Cache Manager
 */
class AIOPMS_Performance_Cache {
    
    private static $instance = null;
    private $cache_prefix = 'aiopms_cache_';
    private $cache_stats = [];
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get cached data
     */
    public function get($key, $group = 'default') {
        $cache_key = $this->cache_prefix . $group . '_' . $key;
        $cached_data = wp_cache_get($cache_key, AIOPMS_CACHE_GROUP);
        
        if ($cached_data !== false) {
            $this->cache_stats['hits'] = ($this->cache_stats['hits'] ?? 0) + 1;
            return $cached_data;
        }
        
        $this->cache_stats['misses'] = ($this->cache_stats['misses'] ?? 0) + 1;
        return false;
    }
    
    /**
     * Set cached data
     */
    public function set($key, $data, $group = 'default', $expiry = AIOPMS_CACHE_EXPIRY) {
        $cache_key = $this->cache_prefix . $group . '_' . $key;
        $result = wp_cache_set($cache_key, $data, AIOPMS_CACHE_GROUP, $expiry);
        
        if ($result) {
            $this->cache_stats['sets'] = ($this->cache_stats['sets'] ?? 0) + 1;
        }
        
        return $result;
    }
    
    /**
     * Delete cached data
     */
    public function delete($key, $group = 'default') {
        $cache_key = $this->cache_prefix . $group . '_' . $key;
        $result = wp_cache_delete($cache_key, AIOPMS_CACHE_GROUP);
        
        if ($result) {
            $this->cache_stats['deletes'] = ($this->cache_stats['deletes'] ?? 0) + 1;
        }
        
        return $result;
    }
    
    /**
     * Clear all cache for a group
     */
    public function clear_group($group = 'default') {
        global $wp_object_cache;
        
        if (method_exists($wp_object_cache, 'flush_group')) {
            $wp_object_cache->flush_group(AIOPMS_CACHE_GROUP);
        } else {
            // Fallback: clear all cache
            wp_cache_flush();
        }
        
        $this->cache_stats['clears'] = ($this->cache_stats['clears'] ?? 0) + 1;
    }
    
    /**
     * Get cache statistics
     */
    public function get_stats() {
        return $this->cache_stats;
    }
    
    /**
     * Reset cache statistics
     */
    public function reset_stats() {
        $this->cache_stats = [];
    }
}

/**
 * Memory Monitor
 */
class AIOPMS_Memory_Monitor {
    
    private static $instance = null;
    private $memory_usage = [];
    private $threshold = AIOPMS_MEMORY_THRESHOLD;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if memory usage is within acceptable limits
     */
    public function check_memory_limit($threshold_percent = null) {
        if ($threshold_percent === null) {
            $threshold_percent = $this->threshold;
        }
        
        $memory_info = $this->get_memory_info();
        $usage_percent = ($memory_info['current'] / $memory_info['limit']) * 100;
        
        return $usage_percent < $threshold_percent;
    }
    
    /**
     * Get current memory information
     */
    public function get_memory_info() {
        $memory_limit = ini_get('memory_limit');
        $current_usage = memory_get_usage(true);
        $peak_usage = memory_get_peak_usage(true);
        
        return [
            'current' => $current_usage,
            'peak' => $peak_usage,
            'limit' => $this->convert_memory_limit_to_bytes($memory_limit),
            'current_mb' => round($current_usage / 1024 / 1024, 2),
            'peak_mb' => round($peak_usage / 1024 / 1024, 2),
            'limit_mb' => round($this->convert_memory_limit_to_bytes($memory_limit) / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Convert memory limit string to bytes
     */
    private function convert_memory_limit_to_bytes($memory_limit) {
        if ($memory_limit == -1) {
            return PHP_INT_MAX;
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
    
    /**
     * Log memory usage for monitoring
     */
    public function log_memory_usage($operation = '') {
        $memory_info = $this->get_memory_info();
        $this->memory_usage[] = [
            'operation' => $operation,
            'timestamp' => current_time('mysql'),
            'current_mb' => $memory_info['current_mb'],
            'peak_mb' => $memory_info['peak_mb'],
            'usage_percent' => round(($memory_info['current'] / $memory_info['limit']) * 100, 2)
        ];
        
        // Keep only last 100 entries
        if (count($this->memory_usage) > 100) {
            $this->memory_usage = array_slice($this->memory_usage, -100);
        }
    }
    
    /**
     * Get memory usage history
     */
    public function get_memory_history() {
        return $this->memory_usage;
    }
    
    /**
     * Clear memory usage history
     */
    public function clear_memory_history() {
        $this->memory_usage = [];
    }
}

/**
 * Database Query Optimizer
 */
class AIOPMS_DB_Optimizer {
    
    private static $instance = null;
    private $query_cache = [];
    private $query_stats = [];
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Optimized get_posts with caching
     */
    public function get_posts_optimized($args = []) {
        $cache_key = 'get_posts_' . md5(serialize($args));
        $cache = AIOPMS_Performance_Cache::get_instance();
        
        // Try cache first
        $cached_posts = $cache->get($cache_key, 'posts');
        if ($cached_posts !== false) {
            return $cached_posts;
        }
        
        // Execute query
        $start_time = microtime(true);
        $posts = get_posts($args);
        $execution_time = microtime(true) - $start_time;
        
        // Log query performance
        $this->log_query_performance('get_posts', $execution_time, count($posts));
        
        // Cache results
        $cache->set($cache_key, $posts, 'posts', AIOPMS_CACHE_EXPIRY);
        
        return $posts;
    }
    
    /**
     * Optimized get_post with caching
     */
    public function get_post_optimized($post_id) {
        $cache_key = 'get_post_' . $post_id;
        $cache = AIOPMS_Performance_Cache::get_instance();
        
        // Try cache first
        $cached_post = $cache->get($cache_key, 'posts');
        if ($cached_post !== false) {
            return $cached_post;
        }
        
        // Execute query
        $start_time = microtime(true);
        $post = get_post($post_id);
        $execution_time = microtime(true) - $start_time;
        
        // Log query performance
        $this->log_query_performance('get_post', $execution_time, $post ? 1 : 0);
        
        // Cache results
        if ($post) {
            $cache->set($cache_key, $post, 'posts', AIOPMS_CACHE_EXPIRY);
        }
        
        return $post;
    }
    
    /**
     * Batch insert posts for better performance
     */
    public function batch_insert_posts($posts_data, $batch_size = AIOPMS_BATCH_SIZE) {
        $memory_monitor = AIOPMS_Memory_Monitor::get_instance();
        $inserted_count = 0;
        $errors = [];
        
        // Process in batches
        $batches = array_chunk($posts_data, $batch_size);
        
        foreach ($batches as $batch_index => $batch) {
            // Check memory before processing batch
            if (!$memory_monitor->check_memory_limit(85)) {
                $errors[] = sprintf(
                    __('Memory limit reached at batch %d. Stopping batch processing.', 'aiopms'),
                    $batch_index + 1
                );
                break;
            }
            
            $memory_monitor->log_memory_usage("batch_insert_posts_batch_{$batch_index}");
            
            foreach ($batch as $post_data) {
                $post_id = wp_insert_post($post_data);
                
                if ($post_id && !is_wp_error($post_id)) {
                    $inserted_count++;
                    
                    // Clear cache for this post
                    $cache = AIOPMS_Performance_Cache::get_instance();
                    $cache->delete('get_post_' . $post_id, 'posts');
                } else {
                    $errors[] = sprintf(
                        __('Failed to insert post: %s', 'aiopms'),
                        is_wp_error($post_id) ? $post_id->get_error_message() : 'Unknown error'
                    );
                }
            }
            
            // Small delay to prevent overwhelming the database
            usleep(100000); // 0.1 second
        }
        
        return [
            'inserted' => $inserted_count,
            'errors' => $errors,
            'total_batches' => count($batches)
        ];
    }
    
    /**
     * Log query performance
     */
    private function log_query_performance($query_type, $execution_time, $result_count) {
        $this->query_stats[] = [
            'type' => $query_type,
            'execution_time' => $execution_time,
            'result_count' => $result_count,
            'timestamp' => current_time('mysql')
        ];
        
        // Keep only last 1000 entries
        if (count($this->query_stats) > 1000) {
            $this->query_stats = array_slice($this->query_stats, -1000);
        }
    }
    
    /**
     * Get query performance statistics
     */
    public function get_query_stats() {
        return $this->query_stats;
    }
    
    /**
     * Clear query statistics
     */
    public function clear_query_stats() {
        $this->query_stats = [];
    }
}

/**
 * Asset Optimizer
 */
class AIOPMS_Asset_Optimizer {
    
    private static $instance = null;
    private $minified_assets = [];
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Optimize CSS by minifying and combining
     */
    public function optimize_css($css_files, $combined_name = 'aiopms-combined') {
        $cache = AIOPMS_Performance_Cache::get_instance();
        $cache_key = 'css_' . md5(serialize($css_files));
        
        // Try cache first
        $cached_css = $cache->get($cache_key, 'assets');
        if ($cached_css !== false) {
            return $cached_css;
        }
        
        $combined_css = '';
        $upload_dir = wp_upload_dir();
        $assets_dir = $upload_dir['basedir'] . '/aiopms-assets';
        
        // Create assets directory if it doesn't exist
        if (!file_exists($assets_dir)) {
            wp_mkdir_p($assets_dir);
        }
        
        foreach ($css_files as $css_file) {
            $file_path = AIOPMS_PLUGIN_PATH . 'assets/css/' . $css_file;
            if (file_exists($file_path)) {
                $css_content = file_get_contents($file_path);
                $minified_css = $this->minify_css($css_content);
                $combined_css .= $minified_css . "\n";
            }
        }
        
        // Save combined CSS
        $combined_file = $assets_dir . '/' . $combined_name . '.css';
        file_put_contents($combined_file, $combined_css);
        
        // Cache the result
        $cache->set($cache_key, $combined_file, 'assets', AIOPMS_CACHE_EXPIRY * 24); // 24 hours
        
        return $combined_file;
    }
    
    /**
     * Optimize JavaScript by minifying and combining
     */
    public function optimize_js($js_files, $combined_name = 'aiopms-combined') {
        $cache = AIOPMS_Performance_Cache::get_instance();
        $cache_key = 'js_' . md5(serialize($js_files));
        
        // Try cache first
        $cached_js = $cache->get($cache_key, 'assets');
        if ($cached_js !== false) {
            return $cached_js;
        }
        
        $combined_js = '';
        $upload_dir = wp_upload_dir();
        $assets_dir = $upload_dir['basedir'] . '/aiopms-assets';
        
        // Create assets directory if it doesn't exist
        if (!file_exists($assets_dir)) {
            wp_mkdir_p($assets_dir);
        }
        
        foreach ($js_files as $js_file) {
            $file_path = AIOPMS_PLUGIN_PATH . 'assets/js/' . $js_file;
            if (file_exists($file_path)) {
                $js_content = file_get_contents($file_path);
                $minified_js = $this->minify_js($js_content);
                $combined_js .= $minified_js . ";\n";
            }
        }
        
        // Save combined JS
        $combined_file = $assets_dir . '/' . $combined_name . '.js';
        file_put_contents($combined_file, $combined_js);
        
        // Cache the result
        $cache->set($cache_key, $combined_file, 'assets', AIOPMS_CACHE_EXPIRY * 24); // 24 hours
        
        return $combined_file;
    }
    
    /**
     * Minify CSS
     */
    private function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace(['; ', ' {', '{ ', ' }', '} ', ': ', ' ,', ', '], [';', '{', '{', '}', '}', ':', ',', ','], $css);
        
        return trim($css);
    }
    
    /**
     * Minify JavaScript
     */
    private function minify_js($js) {
        // Remove single-line comments
        $js = preg_replace('~//[^\r\n]*~', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('~/\*.*?\*/~s', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        $js = str_replace([' = ', ' + ', ' - ', ' * ', ' / ', ' == ', ' != ', ' <= ', ' >= '], 
                         ['=', '+', '-', '*', '/', '==', '!=', '<=', '>='], $js);
        
        return trim($js);
    }
}

/**
 * Performance Monitor
 */
class AIOPMS_Performance_Monitor {
    
    private static $instance = null;
    private $performance_log = [];
    private $start_time;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Start performance monitoring
     */
    public function start_monitoring($operation = '') {
        $this->start_time = microtime(true);
        $this->performance_log[] = [
            'operation' => $operation,
            'start_time' => $this->start_time,
            'memory_start' => memory_get_usage(true)
        ];
    }
    
    /**
     * End performance monitoring
     */
    public function end_monitoring($operation = '') {
        $end_time = microtime(true);
        $execution_time = $end_time - $this->start_time;
        $memory_used = memory_get_usage(true) - $this->performance_log[count($this->performance_log) - 1]['memory_start'];
        
        $this->performance_log[count($this->performance_log) - 1] = array_merge(
            $this->performance_log[count($this->performance_log) - 1],
            [
                'end_time' => $end_time,
                'execution_time' => $execution_time,
                'memory_used' => $memory_used,
                'memory_used_mb' => round($memory_used / 1024 / 1024, 2)
            ]
        );
        
        return $this->performance_log[count($this->performance_log) - 1];
    }
    
    /**
     * Get performance statistics
     */
    public function get_performance_stats() {
        return $this->performance_log;
    }
    
    /**
     * Clear performance log
     */
    public function clear_performance_log() {
        $this->performance_log = [];
    }
}

/**
 * Initialize performance optimizations
 */
function aiopms_init_performance_optimizer() {
    // Initialize cache
    $cache = AIOPMS_Performance_Cache::get_instance();
    
    // Initialize memory monitor
    $memory_monitor = AIOPMS_Memory_Monitor::get_instance();
    
    // Initialize DB optimizer
    $db_optimizer = AIOPMS_DB_Optimizer::get_instance();
    
    // Initialize asset optimizer
    $asset_optimizer = AIOPMS_Asset_Optimizer::get_instance();
    
    // Initialize performance monitor
    $performance_monitor = AIOPMS_Performance_Monitor::get_instance();
}

// Initialize on plugin load
add_action('plugins_loaded', 'aiopms_init_performance_optimizer');

/**
 * Helper functions for backward compatibility
 */

// Cached get_posts
function aiopms_get_posts_cached($args = []) {
    $db_optimizer = AIOPMS_DB_Optimizer::get_instance();
    return $db_optimizer->get_posts_optimized($args);
}

// Cached get_post
function aiopms_get_post_cached($post_id) {
    $db_optimizer = AIOPMS_DB_Optimizer::get_instance();
    return $db_optimizer->get_post_optimized($post_id);
}

// Check memory limit
function aiopms_check_memory_limit($threshold_percent = AIOPMS_MEMORY_THRESHOLD) {
    $memory_monitor = AIOPMS_Memory_Monitor::get_instance();
    return $memory_monitor->check_memory_limit($threshold_percent);
}

// Get memory info
function aiopms_get_memory_info() {
    $memory_monitor = AIOPMS_Memory_Monitor::get_instance();
    return $memory_monitor->get_memory_info();
}

// Cache data
function aiopms_cache_set($key, $data, $group = 'default', $expiry = AIOPMS_CACHE_EXPIRY) {
    $cache = AIOPMS_Performance_Cache::get_instance();
    return $cache->set($key, $data, $group, $expiry);
}

// Get cached data
function aiopms_cache_get($key, $group = 'default') {
    $cache = AIOPMS_Performance_Cache::get_instance();
    return $cache->get($key, $group);
}

// Delete cached data
function aiopms_cache_delete($key, $group = 'default') {
    $cache = AIOPMS_Performance_Cache::get_instance();
    return $cache->delete($key, $group);
}

// Clear cache group
function aiopms_cache_clear_group($group = 'default') {
    $cache = AIOPMS_Performance_Cache::get_instance();
    return $cache->clear_group($group);
}
