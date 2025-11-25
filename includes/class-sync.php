<?php
/**
 * Sync WooCommerce orders to custom table
 *
 * @package Almokhlif_Oud_Sales_Report
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Almokhlif_Oud_Sales_Report_Sync {
	
	/**
	 * Instance of this class
	 *
	 * @var Almokhlif_Oud_Sales_Report_Sync
	 */
	private static $instance = null;
	
	/**
	 * Track order status changes
	 *
	 * @var array
	 */
	private $status_changes = array();
	
	/**
	 * Get instance of this class
	 *
	 * @return Almokhlif_Oud_Sales_Report_Sync
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
		$this->init_hooks();
	}
	
	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Sync on order creation
		add_action( 'woocommerce_new_order', array( $this, 'sync_order' ), 10, 1 );
		
		// Sync on order update
		add_action( 'woocommerce_update_order', array( $this, 'sync_order' ), 10, 1 );
		
		// Track status changes
		add_action( 'woocommerce_order_status_changed', array( $this, 'track_status_change' ), 10, 4 );
		
		// Sync after status change
		add_action( 'woocommerce_order_status_changed', array( $this, 'sync_order_after_status_change' ), 20, 4 );
		
		// Sync on order meta update
		add_action( 'woocommerce_order_object_updated_props', array( $this, 'sync_order_on_meta_update' ), 10, 2 );
	}
	
	/**
	 * Sync order to custom table
	 *
	 * @param int $order_id Order ID
	 * @param bool $status_changed Whether status changed
	 */
	public function sync_order( $order_id, $status_changed = false ) {
		if ( ! $order_id ) {
			return;
		}
		
		// Check if status changed for this order
		if ( isset( $this->status_changes[ $order_id ] ) ) {
			$status_changed = true;
			unset( $this->status_changes[ $order_id ] );
		}
		
		Almokhlif_Oud_Sales_Report_Database::sync_order( $order_id, $status_changed );
	}
	
	/**
	 * Track status change
	 *
	 * @param int $order_id Order ID
	 * @param string $old_status Old status
	 * @param string $new_status New status
	 * @param WC_Order $order Order object
	 */
	public function track_status_change( $order_id, $old_status, $new_status, $order ) {
		if ( $old_status !== $new_status ) {
			$this->status_changes[ $order_id ] = true;
		}
	}
	
	/**
	 * Sync order after status change
	 *
	 * @param int $order_id Order ID
	 * @param string $old_status Old status
	 * @param string $new_status New status
	 * @param WC_Order $order Order object
	 */
	public function sync_order_after_status_change( $order_id, $old_status, $new_status, $order ) {
		if ( $old_status !== $new_status ) {
			$this->sync_order( $order_id, true );
		}
	}
	
	/**
	 * Sync order on meta update
	 *
	 * @param WC_Order $order Order object
	 * @param array $changed_props Changed properties
	 */
	public function sync_order_on_meta_update( $order, $changed_props ) {
		if ( $order && $order->get_id() ) {
			$this->sync_order( $order->get_id(), false );
		}
	}
}

