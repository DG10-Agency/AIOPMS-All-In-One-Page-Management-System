<?php
/**
 * Help and Tooltips System for AIOPMS
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
 * AIOPMS Help and Tooltips Class
 * 
 * Handles help text, tooltips, and user guidance throughout the plugin.
 * 
 * @since 3.0
 */
class AIOPMS_Help_Tooltips {
	
	/**
	 * Instance of the class.
	 * 
	 * @var AIOPMS_Help_Tooltips
	 * @since 3.0
	 */
	private static $instance = null;
	
	/**
	 * Help data storage.
	 * 
	 * @var array
	 * @since 3.0
	 */
	private $help_data = array();
	
	/**
	 * Get instance of the class.
	 * 
	 * @return AIOPMS_Help_Tooltips
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
		$this->init_help_data();
		$this->init_hooks();
	}
	
	/**
	 * Initialize help data.
	 * 
	 * @since 3.0
	 */
	private function init_help_data() {
		$this->help_data = array(
			'manual_creation' => array(
				'title' => __( 'Manual Page Creation', 'aiopms' ),
				'description' => __( 'Create pages manually with custom hierarchy and attributes. Use hyphens for nesting levels and special syntax for metadata.', 'aiopms' ),
				'tooltips' => array(
					'titles_field' => __( 'Enter one page title per line. Use hyphens (-) for child pages, one hyphen per level. Use special syntax for metadata.', 'aiopms' ),
					'syntax_guide' => __( 'Syntax Guide: Use - for child pages, :+ for meta description, :* for featured image URL, ::template=name for page template, ::status=draft for post status.', 'aiopms' ),
					'seo_slugs' => __( 'SEO-optimized slugs are automatically generated from page titles (max 72 characters).', 'aiopms' ),
				),
			),
			'csv_upload' => array(
				'title' => __( 'CSV Upload', 'aiopms' ),
				'description' => __( 'Bulk import pages from CSV files. Upload a properly formatted CSV file with page data.', 'aiopms' ),
				'tooltips' => array(
					'csv_file' => __( 'Upload a CSV file with columns: post_title, slug (optional), post_parent, meta_description, featured_image, page_template, post_status.', 'aiopms' ),
					'file_size' => __( 'Maximum file size is determined by your server settings. Large files will be processed in batches.', 'aiopms' ),
					'csv_format' => __( 'CSV should have headers in the first row. Empty slug fields will generate SEO-optimized slugs automatically.', 'aiopms' ),
				),
			),
			'ai_generation' => array(
				'title' => __( 'AI Generation', 'aiopms' ),
				'description' => __( 'Generate pages with AI assistance. Choose your AI provider and let the system create relevant pages for your business.', 'aiopms' ),
				'tooltips' => array(
					'business_type' => __( 'Select the type of business you run. This helps AI generate relevant page suggestions.', 'aiopms' ),
					'business_details' => __( 'Provide specific details about your business, services, or products. More details lead to better AI suggestions.', 'aiopms' ),
					'seo_keywords' => __( 'Enter relevant SEO keywords for your business. These will be used to optimize the generated pages.', 'aiopms' ),
					'target_audience' => __( 'Describe your target audience. This helps AI create content that resonates with your customers.', 'aiopms' ),
					'ai_provider' => __( 'Choose your preferred AI provider. Each provider has different capabilities and pricing.', 'aiopms' ),
					'api_key' => __( 'Enter your API key for the selected AI provider. Keys are stored securely and never shared.', 'aiopms' ),
					'generate_images' => __( 'Enable to automatically generate featured images for created pages using AI.', 'aiopms' ),
				),
			),
			'schema_generator' => array(
				'title' => __( 'Schema Generator', 'aiopms' ),
				'description' => __( 'Create structured data markup for your pages. This helps search engines understand your content better.', 'aiopms' ),
				'tooltips' => array(
					'auto_generation' => __( 'Enable automatic schema generation for new pages. The system will detect the best schema type for each page.', 'aiopms' ),
					'schema_types' => __( 'Available schema types: WebPage, Article, BlogPosting, Service, Product, Organization, LocalBusiness, FAQPage, HowTo, Review, Event.', 'aiopms' ),
					'manual_override' => __( 'You can manually override the automatically detected schema type for specific pages.', 'aiopms' ),
				),
			),
			'menu_generator' => array(
				'title' => __( 'Menu Generator', 'aiopms' ),
				'description' => __( 'Automatically generate WordPress menus based on your created pages. Create various types of navigation menus.', 'aiopms' ),
				'tooltips' => array(
					'menu_types' => __( 'Different menu types: Main Navigation, Services, Company, Products, Resources, Support, Footer Quick Links, Social Media.', 'aiopms' ),
					'auto_detection' => __( 'The system automatically detects relevant pages for each menu type based on page titles and content.', 'aiopms' ),
					'menu_locations' => __( 'Generated menus are created in WordPress Appearance → Menus. You can assign them to menu locations manually.', 'aiopms' ),
				),
			),
			'hierarchy_management' => array(
				'title' => __( 'Page Hierarchy', 'aiopms' ),
				'description' => __( 'Visualize and manage your page structure. View your site hierarchy in different formats.', 'aiopms' ),
				'tooltips' => array(
					'org_chart' => __( 'View your page hierarchy as an organizational chart. Perfect for understanding site structure.', 'aiopms' ),
					'grid_view' => __( 'View pages in a grid format with detailed information about each page.', 'aiopms' ),
					'drag_drop' => __( 'Drag and drop pages to reorganize your site structure. Changes are saved automatically.', 'aiopms' ),
				),
			),
			'keyword_analysis' => array(
				'title' => __( 'Keyword Analysis', 'aiopms' ),
				'description' => __( 'Analyze keyword density and SEO performance of your pages. Get recommendations for improvement.', 'aiopms' ),
				'tooltips' => array(
					'page_selection' => __( 'Select a published page or post to analyze for keyword density and SEO performance.', 'aiopms' ),
					'keywords_input' => __( 'Enter keywords to analyze, one per line or comma-separated. The analyzer counts exact matches (case-insensitive).', 'aiopms' ),
					'analysis_results' => __( 'View detailed analysis including keyword density, relevance scores, and SEO recommendations.', 'aiopms' ),
				),
			),
			'performance_dashboard' => array(
				'title' => __( 'Performance Dashboard', 'aiopms' ),
				'description' => __( 'Monitor and optimize plugin performance. View memory usage, cache status, and asset optimization.', 'aiopms' ),
				'tooltips' => array(
					'memory_usage' => __( 'Monitor current and peak memory usage. High usage may indicate performance issues.', 'aiopms' ),
					'cache_status' => __( 'View cache hit/miss ratios and manage cached data. Higher hit ratios indicate better performance.', 'aiopms' ),
					'asset_optimization' => __( 'Enable CSS/JS minification and combination to reduce file sizes and improve loading times.', 'aiopms' ),
				),
			),
			'backup_restore' => array(
				'title' => __( 'Backup & Restore', 'aiopms' ),
				'description' => __( 'Create backups of your plugin data and restore them when needed. Protect your work with regular backups.', 'aiopms' ),
				'tooltips' => array(
					'create_backup' => __( 'Create a backup of your plugin settings, pages, schema data, and other information.', 'aiopms' ),
					'restore_backup' => __( 'Restore data from a previously created backup. Choose what to restore and whether to overwrite existing data.', 'aiopms' ),
					'backup_options' => __( 'Select what to include in your backup: settings, pages, schema data, logs, and cache data.', 'aiopms' ),
				),
			),
		);
	}
	
	/**
	 * Initialize hooks.
	 * 
	 * @since 3.0
	 */
	private function init_hooks() {
		// Add admin footer scripts for tooltips.
		add_action( 'admin_footer', array( $this, 'add_tooltip_scripts' ) );
		
		// Add help tabs to admin pages.
		add_action( 'admin_head', array( $this, 'add_help_tabs' ) );
	}
	
	/**
	 * Get help data for a specific section.
	 * 
	 * @param string $section Section name.
	 * @return array Help data.
	 * @since 3.0
	 */
	public function get_help_data( $section ) {
		return isset( $this->help_data[ $section ] ) ? $this->help_data[ $section ] : array();
	}
	
	/**
	 * Get tooltip text for a specific field.
	 * 
	 * @param string $section Section name.
	 * @param string $field Field name.
	 * @return string Tooltip text.
	 * @since 3.0
	 */
	public function get_tooltip_text( $section, $field ) {
		$help_data = $this->get_help_data( $section );
		return isset( $help_data['tooltips'][ $field ] ) ? $help_data['tooltips'][ $field ] : '';
	}
	
	/**
	 * Display tooltip icon with help text.
	 * 
	 * @param string $section Section name.
	 * @param string $field Field name.
	 * @param string $position Tooltip position (top, bottom, left, right).
	 * @return string Tooltip HTML.
	 * @since 3.0
	 */
	public function display_tooltip( $section, $field, $position = 'top' ) {
		$tooltip_text = $this->get_tooltip_text( $section, $field );
		
		if ( empty( $tooltip_text ) ) {
			return '';
		}
		
		$tooltip_id = 'aiopms-tooltip-' . $section . '-' . $field;
		
		return sprintf(
			'<span class="aiopms-tooltip-trigger" data-tooltip-id="%s" aria-describedby="%s">
				<span class="dashicons dashicons-editor-help" aria-hidden="true"></span>
				<span class="screen-reader-text">%s</span>
			</span>
			<div id="%s" class="aiopms-tooltip aiopms-tooltip-%s" role="tooltip">
				%s
			</div>',
			esc_attr( $tooltip_id ),
			esc_attr( $tooltip_id ),
			esc_attr__( 'Help', 'aiopms' ),
			esc_attr( $tooltip_id ),
			esc_attr( $position ),
			esc_html( $tooltip_text )
		);
	}
	
	/**
	 * Display help section with description and tooltips.
	 * 
	 * @param string $section Section name.
	 * @return string Help section HTML.
	 * @since 3.0
	 */
	public function display_help_section( $section ) {
		$help_data = $this->get_help_data( $section );
		
		if ( empty( $help_data ) ) {
			return '';
		}
		
		$html = '<div class="aiopms-help-section">';
		$html .= '<h3>' . esc_html( $help_data['title'] ) . '</h3>';
		$html .= '<p>' . esc_html( $help_data['description'] ) . '</p>';
		
		if ( ! empty( $help_data['tooltips'] ) ) {
			$html .= '<div class="aiopms-help-tooltips">';
			$html .= '<h4>' . esc_html__( 'Field Help:', 'aiopms' ) . '</h4>';
			$html .= '<ul>';
			
			foreach ( $help_data['tooltips'] as $field => $tooltip_text ) {
				$html .= '<li><strong>' . esc_html( ucfirst( str_replace( '_', ' ', $field ) ) ) . ':</strong> ' . esc_html( $tooltip_text ) . '</li>';
			}
			
			$html .= '</ul>';
			$html .= '</div>';
		}
		
		$html .= '</div>';
		
		return $html;
	}
	
	/**
	 * Add help tabs to admin pages.
	 * 
	 * @since 3.0
	 */
	public function add_help_tabs() {
		$screen = get_current_screen();
		
		if ( ! $screen || strpos( $screen->id, 'aiopms' ) === false ) {
			return;
		}
		
		// Add general help tab.
		$screen->add_help_tab( array(
			'id'      => 'aiopms-general-help',
			'title'   => __( 'General Help', 'aiopms' ),
			'content' => $this->get_general_help_content(),
		) );
		
		// Add specific help tabs based on current page.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'manual';
		
		if ( isset( $this->help_data[ $current_tab ] ) ) {
			$screen->add_help_tab( array(
				'id'      => 'aiopms-specific-help',
				'title'   => $this->help_data[ $current_tab ]['title'],
				'content' => $this->display_help_section( $current_tab ),
			) );
		}
		
		// Add troubleshooting help tab.
		$screen->add_help_tab( array(
			'id'      => 'aiopms-troubleshooting',
			'title'   => __( 'Troubleshooting', 'aiopms' ),
			'content' => $this->get_troubleshooting_content(),
		) );
	}
	
	/**
	 * Get general help content.
	 * 
	 * @return string Help content.
	 * @since 3.0
	 */
	private function get_general_help_content() {
		$content = '<h3>' . esc_html__( 'AIOPMS - All In One Page Management System', 'aiopms' ) . '</h3>';
		$content .= '<p>' . esc_html__( 'AIOPMS is a comprehensive page management system for WordPress that helps you create, organize, and optimize your website pages efficiently.', 'aiopms' ) . '</p>';
		
		$content .= '<h4>' . esc_html__( 'Key Features:', 'aiopms' ) . '</h4>';
		$content .= '<ul>';
		$content .= '<li><strong>' . esc_html__( 'Manual Page Creation:', 'aiopms' ) . '</strong> ' . esc_html__( 'Create pages manually with custom hierarchy and attributes.', 'aiopms' ) . '</li>';
		$content .= '<li><strong>' . esc_html__( 'CSV Import:', 'aiopms' ) . '</strong> ' . esc_html__( 'Bulk import pages from CSV files.', 'aiopms' ) . '</li>';
		$content .= '<li><strong>' . esc_html__( 'AI Generation:', 'aiopms' ) . '</strong> ' . esc_html__( 'Generate pages with AI assistance using OpenAI, Gemini, or DeepSeek.', 'aiopms' ) . '</li>';
		$content .= '<li><strong>' . esc_html__( 'Schema Markup:', 'aiopms' ) . '</strong> ' . esc_html__( 'Automatically generate structured data for better SEO.', 'aiopms' ) . '</li>';
		$content .= '<li><strong>' . esc_html__( 'Menu Generation:', 'aiopms' ) . '</strong> ' . esc_html__( 'Create WordPress menus automatically based on your pages.', 'aiopms' ) . '</li>';
		$content .= '<li><strong>' . esc_html__( 'Performance Optimization:', 'aiopms' ) . '</strong> ' . esc_html__( 'Monitor and optimize plugin performance.', 'aiopms' ) . '</li>';
		$content .= '</ul>';
		
		$content .= '<h4>' . esc_html__( 'Getting Started:', 'aiopms' ) . '</h4>';
		$content .= '<ol>';
		$content .= '<li>' . esc_html__( 'Configure your AI API keys in Settings if you plan to use AI generation.', 'aiopms' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Start by creating pages manually or importing from CSV.', 'aiopms' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Use AI generation to create additional relevant pages.', 'aiopms' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Generate schema markup and menus for better SEO and navigation.', 'aiopms' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Monitor performance and optimize as needed.', 'aiopms' ) . '</li>';
		$content .= '</ol>';
		
		return $content;
	}
	
	/**
	 * Get troubleshooting content.
	 * 
	 * @return string Troubleshooting content.
	 * @since 3.0
	 */
	private function get_troubleshooting_content() {
		$content = '<h3>' . esc_html__( 'Troubleshooting', 'aiopms' ) . '</h3>';
		
		$content .= '<h4>' . esc_html__( 'Common Issues:', 'aiopms' ) . '</h4>';
		
		$content .= '<h5>' . esc_html__( 'AI Generation Not Working:', 'aiopms' ) . '</h5>';
		$content .= '<ul>';
		$content .= '<li>' . esc_html__( 'Check that your API key is valid and has sufficient credits.', 'aiopms' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Verify your internet connection and server can make external API calls.', 'aiopms' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Check the error log for specific error messages.', 'aiopms' ) . '</li>';
		$content .= '</ul>';
		
		$content .= '<h5>' . esc_html__( 'CSV Import Issues:', 'aiopms' ) . '</h5>';
		$content .= '<ul>';
		$content .= '<li>' . esc_html__( 'Ensure your CSV file has the correct column headers.', 'aiopms' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Check that the file size is within server limits.', 'aiopms' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Verify the CSV file is properly formatted and encoded as UTF-8.', 'aiopms' ) . '</li>';
		$content .= '</ul>';
		
		$content .= '<h5>' . esc_html__( 'Performance Issues:', 'aiopms' ) . '</h5>';
		$content .= '<ul>';
		$content .= '<li>' . esc_html__( 'Enable asset optimization in the Performance Dashboard.', 'aiopms' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Clear the plugin cache if you experience issues.', 'aiopms' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Check your server memory limits and increase if necessary.', 'aiopms' ) . '</li>';
		$content .= '</ul>';
		
		$content .= '<h4>' . esc_html__( 'Getting Help:', 'aiopms' ) . '</h4>';
		$content .= '<p>' . sprintf( 
			esc_html__( 'If you need additional help, please visit our %1$s, %2$s, or contact support.', 'aiopms' ),
			'<a href="https://dg10.agency/free-wordpress-page-management-plugin/" target="_blank">' . esc_html__( 'plugin page', 'aiopms' ) . '</a>',
			'<a href="' . AIOPMS_GITHUB_URL . '" target="_blank">' . esc_html__( 'documentation', 'aiopms' ) . '</a>'
		) . '</p>';
		
		return $content;
	}
	
	/**
	 * Add tooltip scripts to admin footer.
	 * 
	 * @since 3.0
	 */
	public function add_tooltip_scripts() {
		$screen = get_current_screen();
		
		if ( ! $screen || strpos( $screen->id, 'aiopms' ) === false ) {
			return;
		}
		
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Initialize tooltips
			$('.aiopms-tooltip-trigger').on('mouseenter focus', function() {
				var tooltipId = $(this).data('tooltip-id');
				$('#' + tooltipId).addClass('aiopms-tooltip-visible');
			}).on('mouseleave blur', function() {
				var tooltipId = $(this).data('tooltip-id');
				$('#' + tooltipId).removeClass('aiopms-tooltip-visible');
			});
			
			// Close tooltips when clicking outside
			$(document).on('click', function(e) {
				if (!$(e.target).closest('.aiopms-tooltip-trigger, .aiopms-tooltip').length) {
					$('.aiopms-tooltip').removeClass('aiopms-tooltip-visible');
				}
			});
		});
		</script>
		
		<style type="text/css">
		/* Tooltip Styles */
		.aiopms-tooltip-trigger {
			position: relative;
			display: inline-block;
			cursor: help;
			color: var(--dg10-primary);
			margin-left: 5px;
		}
		
		.aiopms-tooltip-trigger .dashicons {
			font-size: 16px;
			vertical-align: middle;
		}
		
		.aiopms-tooltip {
			position: absolute;
			background: var(--dg10-dark-blue);
			color: white;
			padding: 8px 12px;
			border-radius: 4px;
			font-size: 12px;
			line-height: 1.4;
			white-space: nowrap;
			z-index: 1000;
			opacity: 0;
			visibility: hidden;
			transition: opacity 0.3s ease, visibility 0.3s ease;
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
			max-width: 300px;
			white-space: normal;
		}
		
		.aiopms-tooltip-visible {
			opacity: 1;
			visibility: visible;
		}
		
		.aiopms-tooltip::before {
			content: '';
			position: absolute;
			border: 5px solid transparent;
		}
		
		.aiopms-tooltip-top::before {
			bottom: -10px;
			left: 50%;
			transform: translateX(-50%);
			border-top-color: var(--dg10-dark-blue);
		}
		
		.aiopms-tooltip-bottom::before {
			top: -10px;
			left: 50%;
			transform: translateX(-50%);
			border-bottom-color: var(--dg10-dark-blue);
		}
		
		.aiopms-tooltip-left::before {
			right: -10px;
			top: 50%;
			transform: translateY(-50%);
			border-left-color: var(--dg10-dark-blue);
		}
		
		.aiopms-tooltip-right::before {
			left: -10px;
			top: 50%;
			transform: translateY(-50%);
			border-right-color: var(--dg10-dark-blue);
		}
		
		/* Help Section Styles */
		.aiopms-help-section {
			background: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 20px;
			margin: 20px 0;
		}
		
		.aiopms-help-section h3 {
			margin-top: 0;
			color: var(--dg10-primary);
		}
		
		.aiopms-help-tooltips {
			margin-top: 15px;
		}
		
		.aiopms-help-tooltips h4 {
			margin-bottom: 10px;
			color: var(--dg10-dark-blue);
		}
		
		.aiopms-help-tooltips ul {
			margin: 0;
			padding-left: 20px;
		}
		
		.aiopms-help-tooltips li {
			margin-bottom: 8px;
			line-height: 1.5;
		}
		
		/* Responsive tooltips */
		@media (max-width: 768px) {
			.aiopms-tooltip {
				max-width: 250px;
				font-size: 11px;
			}
		}
		</style>
		<?php
	}
}

/**
 * Initialize help and tooltips.
 * 
 * @since 3.0
 */
function aiopms_init_help_tooltips() {
	return AIOPMS_Help_Tooltips::get_instance();
}

// Initialize help and tooltips.
aiopms_init_help_tooltips();

/**
 * Helper function to display tooltip.
 * 
 * @param string $section Section name.
 * @param string $field Field name.
 * @param string $position Tooltip position.
 * @return string Tooltip HTML.
 * @since 3.0
 */
function aiopms_tooltip( $section, $field, $position = 'top' ) {
	$help_tooltips = AIOPMS_Help_Tooltips::get_instance();
	return $help_tooltips->display_tooltip( $section, $field, $position );
}

/**
 * Helper function to display help section.
 * 
 * @param string $section Section name.
 * @return string Help section HTML.
 * @since 3.0
 */
function aiopms_help_section( $section ) {
	$help_tooltips = AIOPMS_Help_Tooltips::get_instance();
	return $help_tooltips->display_help_section( $section );
}

/**
 * Helper function to get tooltip text.
 * 
 * @param string $section Section name.
 * @param string $field Field name.
 * @return string Tooltip text.
 * @since 3.0
 */
function aiopms_tooltip_text( $section, $field ) {
	$help_tooltips = AIOPMS_Help_Tooltips::get_instance();
	return $help_tooltips->get_tooltip_text( $section, $field );
}
