<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIOPMS Performance Dashboard
 * 
 * Provides a comprehensive performance monitoring and management interface
 * for the AIOPMS plugin.
 *
 * @package AIOPMS
 * @version 3.0
 * @author DG10 Agency
 */

// Performance dashboard tab content
function aiopms_performance_dashboard_tab() {
    // Handle performance actions
    if (isset($_POST['performance_action']) && check_admin_referer('aiopms_performance_action')) {
        $action = sanitize_text_field($_POST['performance_action']);
        
        switch ($action) {
            case 'clear_cache':
                aiopms_cache_clear_group('default');
                aiopms_cache_clear_group('posts');
                aiopms_cache_clear_group('schema');
                aiopms_cache_clear_group('ai_suggestions');
                aiopms_cache_clear_group('assets');
                echo '<div class="notice notice-success is-dismissible"><p>' . __('All caches cleared successfully!', 'aiopms') . '</p></div>';
                break;
                
            case 'clear_performance_logs':
                $performance_monitor = AIOPMS_Performance_Monitor::get_instance();
                $performance_monitor->clear_performance_log();
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Performance logs cleared successfully!', 'aiopms') . '</p></div>';
                break;
                
            case 'clear_memory_logs':
                $memory_monitor = AIOPMS_Memory_Monitor::get_instance();
                $memory_monitor->clear_memory_history();
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Memory logs cleared successfully!', 'aiopms') . '</p></div>';
                break;
                
            case 'clear_query_stats':
                $db_optimizer = AIOPMS_DB_Optimizer::get_instance();
                $db_optimizer->clear_query_stats();
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Query statistics cleared successfully!', 'aiopms') . '</p></div>';
                break;
        }
    }
    
    // Get performance data
    $cache = AIOPMS_Performance_Cache::get_instance();
    $memory_monitor = AIOPMS_Memory_Monitor::get_instance();
    $db_optimizer = AIOPMS_DB_Optimizer::get_instance();
    $performance_monitor = AIOPMS_Performance_Monitor::get_instance();
    
    $cache_stats = $cache->get_stats();
    $memory_info = $memory_monitor->get_memory_info();
    $memory_history = $memory_monitor->get_memory_history();
    $query_stats = $db_optimizer->get_query_stats();
    $performance_stats = $performance_monitor->get_performance_stats();
    
    ?>
    <div class="wrap aiopms-performance-dashboard">
        <h1><?php _e('Performance Dashboard', 'aiopms'); ?></h1>
        <p><?php _e('Monitor and optimize your AIOPMS plugin performance.', 'aiopms'); ?></p>
        
        <!-- Performance Actions -->
        <div class="aiopms-performance-actions">
            <h2><?php _e('Performance Actions', 'aiopms'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('aiopms_performance_action'); ?>
                <div class="aiopms-action-buttons">
                    <button type="submit" name="performance_action" value="clear_cache" class="button button-secondary">
                        <?php _e('Clear All Cache', 'aiopms'); ?>
                    </button>
                    <button type="submit" name="performance_action" value="clear_performance_logs" class="button button-secondary">
                        <?php _e('Clear Performance Logs', 'aiopms'); ?>
                    </button>
                    <button type="submit" name="performance_action" value="clear_memory_logs" class="button button-secondary">
                        <?php _e('Clear Memory Logs', 'aiopms'); ?>
                    </button>
                    <button type="submit" name="performance_action" value="clear_query_stats" class="button button-secondary">
                        <?php _e('Clear Query Statistics', 'aiopms'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Performance Overview -->
        <div class="aiopms-performance-overview">
            <h2><?php _e('Performance Overview', 'aiopms'); ?></h2>
            <div class="aiopms-overview-grid">
                <div class="aiopms-overview-card">
                    <h3><?php _e('Memory Usage', 'aiopms'); ?></h3>
                    <div class="aiopms-memory-info">
                        <p><strong><?php _e('Current:', 'aiopms'); ?></strong> <?php echo $memory_info['current_mb']; ?> MB</p>
                        <p><strong><?php _e('Peak:', 'aiopms'); ?></strong> <?php echo $memory_info['peak_mb']; ?> MB</p>
                        <p><strong><?php _e('Limit:', 'aiopms'); ?></strong> <?php echo $memory_info['limit_mb']; ?> MB</p>
                        <div class="aiopms-memory-bar">
                            <div class="aiopms-memory-fill" style="width: <?php echo round(($memory_info['current'] / $memory_info['limit']) * 100, 1); ?>%"></div>
                        </div>
                        <p class="aiopms-memory-percentage"><?php echo round(($memory_info['current'] / $memory_info['limit']) * 100, 1); ?>%</p>
                    </div>
                </div>
                
                <div class="aiopms-overview-card">
                    <h3><?php _e('Cache Performance', 'aiopms'); ?></h3>
                    <div class="aiopms-cache-info">
                        <p><strong><?php _e('Hits:', 'aiopms'); ?></strong> <?php echo $cache_stats['hits'] ?? 0; ?></p>
                        <p><strong><?php _e('Misses:', 'aiopms'); ?></strong> <?php echo $cache_stats['misses'] ?? 0; ?></p>
                        <p><strong><?php _e('Hit Rate:', 'aiopms'); ?></strong> 
                            <?php 
                            $total_requests = ($cache_stats['hits'] ?? 0) + ($cache_stats['misses'] ?? 0);
                            echo $total_requests > 0 ? round((($cache_stats['hits'] ?? 0) / $total_requests) * 100, 1) : 0; 
                            ?>%
                        </p>
                    </div>
                </div>
                
                <div class="aiopms-overview-card">
                    <h3><?php _e('Database Queries', 'aiopms'); ?></h3>
                    <div class="aiopms-query-info">
                        <p><strong><?php _e('Total Queries:', 'aiopms'); ?></strong> <?php echo count($query_stats); ?></p>
                        <p><strong><?php _e('Avg Execution Time:', 'aiopms'); ?></strong> 
                            <?php 
                            $total_time = array_sum(array_column($query_stats, 'execution_time'));
                            echo count($query_stats) > 0 ? round($total_time / count($query_stats), 4) : 0; 
                            ?>s
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Memory History Chart -->
        <?php if (!empty($memory_history)): ?>
        <div class="aiopms-memory-chart">
            <h2><?php _e('Memory Usage History', 'aiopms'); ?></h2>
            <div class="aiopms-chart-container">
                <canvas id="memoryChart" width="800" height="400"></canvas>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Query Performance Table -->
        <?php if (!empty($query_stats)): ?>
        <div class="aiopms-query-performance">
            <h2><?php _e('Query Performance', 'aiopms'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Query Type', 'aiopms'); ?></th>
                        <th><?php _e('Execution Time (s)', 'aiopms'); ?></th>
                        <th><?php _e('Result Count', 'aiopms'); ?></th>
                        <th><?php _e('Timestamp', 'aiopms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($query_stats, -20) as $query): ?>
                    <tr>
                        <td><?php echo esc_html($query['type']); ?></td>
                        <td><?php echo round($query['execution_time'], 4); ?></td>
                        <td><?php echo $query['result_count']; ?></td>
                        <td><?php echo esc_html($query['timestamp']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Performance Statistics -->
        <?php if (!empty($performance_stats)): ?>
        <div class="aiopms-performance-stats">
            <h2><?php _e('Performance Statistics', 'aiopms'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Operation', 'aiopms'); ?></th>
                        <th><?php _e('Execution Time (s)', 'aiopms'); ?></th>
                        <th><?php _e('Memory Used (MB)', 'aiopms'); ?></th>
                        <th><?php _e('Start Time', 'aiopms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($performance_stats, -20) as $stat): ?>
                    <tr>
                        <td><?php echo esc_html($stat['operation']); ?></td>
                        <td><?php echo round($stat['execution_time'], 4); ?></td>
                        <td><?php echo $stat['memory_used_mb']; ?></td>
                        <td><?php echo esc_html($stat['start_time']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Performance Settings -->
        <div class="aiopms-performance-settings">
            <h2><?php _e('Performance Settings', 'aiopms'); ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('aiopms_performance_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Optimize Assets', 'aiopms'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="aiopms_optimize_assets" value="1" <?php checked(get_option('aiopms_optimize_assets', true)); ?>>
                                <?php _e('Enable asset optimization (CSS/JS minification and combination)', 'aiopms'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Cache Expiry', 'aiopms'); ?></th>
                        <td>
                            <select name="aiopms_cache_expiry">
                                <option value="3600" <?php selected(get_option('aiopms_cache_expiry', 3600), 3600); ?>><?php _e('1 Hour', 'aiopms'); ?></option>
                                <option value="7200" <?php selected(get_option('aiopms_cache_expiry', 3600), 7200); ?>><?php _e('2 Hours', 'aiopms'); ?></option>
                                <option value="21600" <?php selected(get_option('aiopms_cache_expiry', 3600), 21600); ?>><?php _e('6 Hours', 'aiopms'); ?></option>
                                <option value="43200" <?php selected(get_option('aiopms_cache_expiry', 3600), 43200); ?>><?php _e('12 Hours', 'aiopms'); ?></option>
                                <option value="86400" <?php selected(get_option('aiopms_cache_expiry', 3600), 86400); ?>><?php _e('24 Hours', 'aiopms'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Batch Size', 'aiopms'); ?></th>
                        <td>
                            <input type="number" name="aiopms_batch_size" value="<?php echo get_option('aiopms_batch_size', AIOPMS_BATCH_SIZE); ?>" min="10" max="200">
                            <p class="description"><?php _e('Number of items to process in each batch (10-200)', 'aiopms'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Memory Threshold', 'aiopms'); ?></th>
                        <td>
                            <input type="number" name="aiopms_memory_threshold" value="<?php echo get_option('aiopms_memory_threshold', AIOPMS_MEMORY_THRESHOLD); ?>" min="50" max="95">
                            <p class="description"><?php _e('Memory usage threshold percentage (50-95)', 'aiopms'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Performance Settings', 'aiopms')); ?>
            </form>
        </div>
    </div>
    
    <style>
    .aiopms-performance-dashboard {
        max-width: 1200px;
    }
    
    .aiopms-performance-actions {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .aiopms-action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .aiopms-overview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    
    .aiopms-overview-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .aiopms-overview-card h3 {
        margin-top: 0;
        color: #2271b1;
    }
    
    .aiopms-memory-bar {
        width: 100%;
        height: 20px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        margin: 10px 0;
    }
    
    .aiopms-memory-fill {
        height: 100%;
        background: linear-gradient(90deg, #4CAF50, #FFC107, #FF5722);
        transition: width 0.3s ease;
    }
    
    .aiopms-memory-percentage {
        text-align: center;
        font-weight: bold;
        margin: 5px 0;
    }
    
    .aiopms-chart-container {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .aiopms-query-performance,
    .aiopms-performance-stats {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .aiopms-performance-settings {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    @media screen and (max-width: 782px) {
        .aiopms-overview-grid {
            grid-template-columns: 1fr;
        }
        
        .aiopms-action-buttons {
            flex-direction: column;
        }
    }
    </style>
    
    <script>
    // Memory usage chart
    <?php if (!empty($memory_history)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('memoryChart').getContext('2d');
        const memoryData = <?php echo json_encode($memory_history); ?>;
        
        const labels = memoryData.map(item => item.timestamp);
        const currentData = memoryData.map(item => item.current_mb);
        const peakData = memoryData.map(item => item.peak_mb);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Current Memory (MB)',
                    data: currentData,
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Peak Memory (MB)',
                    data: peakData,
                    borderColor: '#d63638',
                    backgroundColor: 'rgba(214, 54, 56, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Memory Usage (MB)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    }
                }
            }
        });
    });
    <?php endif; ?>
    </script>
    <?php
}

// Register performance settings
function aiopms_register_performance_settings() {
    register_setting('aiopms_performance_settings', 'aiopms_optimize_assets');
    register_setting('aiopms_performance_settings', 'aiopms_cache_expiry');
    register_setting('aiopms_performance_settings', 'aiopms_batch_size');
    register_setting('aiopms_performance_settings', 'aiopms_memory_threshold');
}
add_action('admin_init', 'aiopms_register_performance_settings');
