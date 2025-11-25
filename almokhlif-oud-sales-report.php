<?php
/**
 * Plugin Name: Almokhlif Oud Sales Report
 * Plugin URI: https://www.moroccoder.com
 * Description: Extended WooCommerce reporting with detailed order and item-level data, advanced filtering, and Excel export.
 * Version: 1.0.4
 * Author: Tarik BOUKJIJ
 * Author URI: https://www.moroccoder.com
 * Text Domain: almokhlif-oud-sales-report
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Define plugin constants
define( 'ALMOKHLIF_OUDSR_VERSION', '1.0.3' );
define( 'ALMOKHLIF_OUDSR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALMOKHLIF_OUDSR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ALMOKHLIF_OUDSR_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class
 */
class Almokhlif_Oud_Sales_Report {
	
	/**
	 * Instance of this class
	 *
	 * @var Almokhlif_Oud_Sales_Report
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
	 *
	 * @return Almokhlif_Oud_Sales_Report
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init();
	}
	
	/**
	 * Initialize plugin
	 */
	private function init() {
		// Check if WooCommerce is active
		add_action( 'plugins_loaded', array( $this, 'check_woocommerce' ) );
		
		// Activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}
	
	/**
	 * Check if WooCommerce is active
	 */
	public function check_woocommerce() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}
		
		// Load plugin files
		$this->load_dependencies();
	}
	
	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies() {
		require_once ALMOKHLIF_OUDSR_PLUGIN_DIR . 'includes/class-database.php';
		require_once ALMOKHLIF_OUDSR_PLUGIN_DIR . 'includes/class-sync.php';
		require_once ALMOKHLIF_OUDSR_PLUGIN_DIR . 'includes/class-admin.php';
		require_once ALMOKHLIF_OUDSR_PLUGIN_DIR . 'includes/class-list-page.php';
		require_once ALMOKHLIF_OUDSR_PLUGIN_DIR . 'includes/class-settings-page.php';
		require_once ALMOKHLIF_OUDSR_PLUGIN_DIR . 'includes/class-excel-export.php';
		require_once ALMOKHLIF_OUDSR_PLUGIN_DIR . 'includes/class-update-checker.php';
		
		// Initialize classes
		Almokhlif_Oud_Sales_Report_Database::get_instance();
		Almokhlif_Oud_Sales_Report_Sync::get_instance();
		Almokhlif_Oud_Sales_Report_Admin::get_instance();
		Almokhlif_Oud_Sales_Report_Update_Checker::get_instance();
	}
	
	/**
	 * Plugin activation
	 */
	public function activate() {
		// Load database class for activation
		require_once ALMOKHLIF_OUDSR_PLUGIN_DIR . 'includes/class-database.php';
		
		// Create database table
		Almokhlif_Oud_Sales_Report_Database::create_table();
		
		// Set default settings
		$default_settings = array(
			'default_order_statuses' => array( 'wc-processing', 'wc-completed' )
		);
		add_option( 'almokhlif_oud_sales_report_settings', $default_settings );
		
		// Flush rewrite rules
		flush_rewrite_rules();
	}
	
	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}
	
	/**
	 * WooCommerce missing notice
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'Almokhlif Oud Sales Report requires WooCommerce to be installed and active.', 'almokhlif-oud-sales-report' ); ?></p>
		</div>
		<?php
	}
}

/**
 * Initialize the plugin
 */
function almokhlif_oud_sales_report_init() {
	return Almokhlif_Oud_Sales_Report::get_instance();
}

// Start the plugin
almokhlif_oud_sales_report_init();

