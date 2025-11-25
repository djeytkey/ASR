<?php
/**
 * Admin menu and pages
 *
 * @package Almokhlif_Oud_Sales_Report
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Almokhlif_Oud_Sales_Report_Admin {
	
	/**
	 * Instance of this class
	 *
	 * @var Almokhlif_Oud_Sales_Report_Admin
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
	 *
	 * @return Almokhlif_Oud_Sales_Report_Admin
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}
	
	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Almokhlif Oud Sales Report', 'almokhlif-oud-sales-report' ),
			__( 'تقارير المخلف', 'almokhlif-oud-sales-report' ),
			'manage_woocommerce',
			'almokhlif-oud-sales-report',
			array( $this, 'render_list_page' ),
			'dashicons-chart-line',
			56
		);
		
		add_submenu_page(
			'almokhlif-oud-sales-report',
			__( 'تقرير المبيعات', 'almokhlif-oud-sales-report' ),
			__( 'تقرير المبيعات', 'almokhlif-oud-sales-report' ),
			'manage_woocommerce',
			'almokhlif-oud-sales-report',
			array( $this, 'render_list_page' )
		);
		
		add_submenu_page(
			'almokhlif-oud-sales-report',
			__( 'الإعدادات', 'almokhlif-oud-sales-report' ),
			__( 'الإعدادات', 'almokhlif-oud-sales-report' ),
			'manage_woocommerce',
			'almokhlif-oud-sales-report-settings',
			array( $this, 'render_settings_page' )
		);
	}
	
	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'almokhlif-oud-sales-report' ) === false ) {
			return;
		}
		
		// Enqueue jQuery UI for datepicker
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui-datepicker', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css' );
		
		// Enqueue select2 for multi-select
		wp_enqueue_script( 'select2' );
		wp_enqueue_style( 'select2', WC()->plugin_url() . '/assets/css/select2.css' );
		
		// Custom CSS
		wp_add_inline_style( 'select2', '
			* {
				/*font-family: "Cairo" !important;*/
			}
			.almokhlif-filter-card {
				background: #fff;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
				padding: 20px;
				margin: 20px 0;
			}
			.almokhlif-filter-row {
				display: flex;
				gap: 20px;
				margin-bottom: 15px;
				align-items: flex-end;
			}
			.almokhlif-filter-field {
				flex: 1;
			}
			.almokhlif-filter-field label {
				display: block;
				margin-bottom: 5px;
				font-weight: 600;
			}
			.almokhlif-filter-field input,
			.almokhlif-filter-field select {
				width: 100%;
				padding: 5px;
			}
			.almokhlif-export-button {
				margin: 20px 0;
			}
			.almokhlif-table-wrapper {
				position: relative;
				overflow-x: auto;
				overflow-y: visible;
				width: 100%;
				margin: 20px 0;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
			}
			.almokhlif-table-wrapper table {
				min-width: auto;
				table-layout: auto;
			}
			.almokhlif-table-wrapper table th,
			.almokhlif-table-wrapper table td {
				white-space: nowrap;
				padding: 8px 12px;
				min-width: 80px;
			}
			.almokhlif-table-wrapper table th:nth-child(1),
			.almokhlif-table-wrapper table td:nth-child(1) {
				min-width: 70px;
			}
			.almokhlif-table-wrapper table th:nth-child(2),
			.almokhlif-table-wrapper table td:nth-child(2) {
				min-width: 120px;
			}
			.almokhlif-table-wrapper table th:nth-child(3),
			.almokhlif-table-wrapper table td:nth-child(3) {
				min-width: 120px;
			}
			.almokhlif-table-wrapper table th:nth-child(4),
			.almokhlif-table-wrapper table td:nth-child(4) {
				min-width: 120px;
			}
			.almokhlif-table-wrapper table th:nth-child(5),
			.almokhlif-table-wrapper table td:nth-child(5),
			.almokhlif-table-wrapper table th:nth-child(6),
			.almokhlif-table-wrapper table td:nth-child(6) {
				min-width: 150px;
			}
			.almokhlif-table-wrapper table th:nth-child(8),
			.almokhlif-table-wrapper table td:nth-child(8) {
				min-width: 200px;
			}
			.almokhlif-table-wrapper table th:nth-child(15),
			.almokhlif-table-wrapper table td:nth-child(15) {
				min-width: 200px;
			}
			.almokhlif-table-wrapper table th:nth-child(17),
			.almokhlif-table-wrapper table td:nth-child(17) {
				min-width: 150px;
			}
			.almokhlif-table-wrapper table th:nth-child(25),
			.almokhlif-table-wrapper table td:nth-child(25) {
				min-width: 200px;
				white-space: normal;
			}
			/* Column Visibility Styles */
			.almokhlif-column-hidden {
				display: none !important;
			}
			.almokhlif-column-toggle-wrapper {
				position: relative;
			}
			.almokhlif-column-toggle-menu {
				position: absolute;
				top: 100%;
				left: 0;
				background: #fff;
				border: 1px solid #ccd0d4;
				box-shadow: 0 3px 5px rgba(0,0,0,.2);
				z-index: 1000;
				min-width: 250px;
				margin-top: 5px;
			}
			.almokhlif-column-toggle-header {
				padding: 10px 15px;
				border-bottom: 1px solid #ccd0d4;
				background: #f5f5f5;
			}
			.almokhlif-column-toggle-list {
				max-height: 400px;
				overflow-y: auto;
				padding: 5px 0;
			}
			.almokhlif-column-toggle-list label {
				display: block;
				padding: 5px 15px;
				cursor: pointer;
				margin: 0;
			}
			.almokhlif-column-toggle-list label:hover {
				background: #f0f0f0;
			}
			.almokhlif-column-toggle-list input[type="checkbox"] {
				margin-right: 8px;
			}
			.almokhlif-column-toggle-actions {
				padding: 10px 15px;
				border-top: 1px solid #ccd0d4;
				display: flex;
				gap: 10px;
			}
			.almokhlif-column-toggle-actions .button {
				flex: 1;
			}
		' );
	}
	
	/**
	 * Render list page
	 */
	public function render_list_page() {
		$list_page = new Almokhlif_Oud_Sales_Report_List_Page();
		$list_page->render();
	}
	
	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		$settings_page = new Almokhlif_Oud_Sales_Report_Settings_Page();
		$settings_page->render();
	}
}

