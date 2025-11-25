<?php
/**
 * Excel Export functionality
 *
 * @package Almokhlif_Oud_Sales_Report
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Almokhlif_Oud_Sales_Report_Excel_Export {
	
	/**
	 * Export orders to Excel
	 */
	public function export() {
		// Check if PHPSpreadsheet is available
		if ( ! class_exists( 'PhpOffice\PhpSpreadsheet\Spreadsheet' ) ) {
			// Try to load via Composer autoload
			$autoload_path = ALMOKHLIF_OUDSR_PLUGIN_DIR . 'vendor/autoload.php';
			if ( file_exists( $autoload_path ) ) {
				require_once $autoload_path;
			}
			
			// Check again after trying to load
			if ( ! class_exists( 'PhpOffice\PhpSpreadsheet\Spreadsheet' ) ) {
				wp_die( 
					esc_html__( 'PHPSpreadsheet library is not installed. Please run "composer install" in the plugin directory.', 'almokhlif-oud-sales-report' ),
					esc_html__( 'Export Error', 'almokhlif-oud-sales-report' ),
					array( 'back_link' => true )
				);
			}
		}
		
		// Get filter values (same as list page)
		$filters = $this->get_filters();
		
		// Remove limit for export
		$filters['limit'] = 0;
		$filters['offset'] = 0;
		
		// Get all orders
		$orders = Almokhlif_Oud_Sales_Report_Database::get_orders( $filters );
		
		// Create new Spreadsheet
		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		
		// Set headers
		$headers = array(
			'Order ID',
			'Invoice Number',
			'Billing First Name',
			'Billing Phone',
			'Modified Date',
			'Order Date',
			'Billing Country',
			'Billing Address',
			'Billing City',
			'Order Status',
			'Payment Method',
			'Payment Reference',
			'Odoo Order',
			'VAT Number',
			'Order Discount',
			'Order Coupon',
			'Staff',
			'Product Name',
			'SKU',
			'Product Categories',
			'Quantity',
			'Item Price',
			'Total Item Price',
			'Amount of Discount',
			'Shipping Cost',
			'Item Tax',
			'Order Total',
			'Customer Notes',
		);
		
		// Write headers
		$col = 1;
		foreach ( $headers as $header ) {
			$sheet->setCellValueByColumnAndRow( $col, 1, $header );
			$col++;
		}
		
		// Style headers
		$header_range = $sheet->getStyleByColumnAndRow( 1, 1, count( $headers ), 1 );
		$header_range->getFont()->setBold( true );
		$header_range->getFill()->setFillType( \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID );
		$header_range->getFill()->getStartColor()->setARGB( 'FFE0E0E0' );
		
		// Write data
		$row = 2;
		foreach ( $orders as $order ) {
			$col = 1;
			
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['order_id'] );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['invoice_number'] );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['billing_first_name'] );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['billing_phone'] );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['modified_date'] );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['order_date'] );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['billing_country'] );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['billing_address'] );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['billing_city'] );
			$sheet->setCellValueByColumnAndRow( $col++, $row, wc_get_order_status_name( $order['order_status'] ) );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['payment_method'] );
			$sheet->setCellValueByColumnAndRow( $col++, $row, isset( $order['payment_reference'] ) ? $order['payment_reference'] : '' );
			$sheet->setCellValueByColumnAndRow( $col++, $row, isset( $order['odoo_order'] ) ? $order['odoo_order'] : '' );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['vat_number'] );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['order_discount'] );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['order_coupon'] ? $order['order_coupon'] : '' );
			$sheet->setCellValueByColumnAndRow( $col++, $row, isset( $order['staff'] ) ? $order['staff'] : '' );
			$sheet->setCellValueByColumnAndRow( $col++, $row, isset( $order['product_name'] ) ? $order['product_name'] : '' );
			$sheet->setCellValueByColumnAndRow( $col++, $row, isset( $order['sku'] ) ? $order['sku'] : '' );
			$sheet->setCellValueByColumnAndRow( $col++, $row, isset( $order['categories'] ) ? $order['categories'] : '' );
			$sheet->setCellValueByColumnAndRow( $col++, $row, isset( $order['quantity'] ) ? $order['quantity'] : '' );
			$sheet->setCellValueByColumnAndRow( $col++, $row, isset( $order['item_price'] ) ? number_format( floatval( $order['item_price'] ), 4, '.', '' ) : '' );
			$sheet->setCellValueByColumnAndRow( $col++, $row, isset( $order['total_item_price'] ) ? number_format( floatval( $order['total_item_price'] ), 4, '.', '' ) : '' );
			$sheet->setCellValueByColumnAndRow( $col++, $row, isset( $order['amount_of_discount'] ) ? number_format( floatval( $order['amount_of_discount'] ), 4, '.', '' ) : '' );
			$sheet->setCellValueByColumnAndRow( $col++, $row, number_format( floatval( $order['shipping_cost'] ), 4, '.', '' ) );
			$sheet->setCellValueByColumnAndRow( $col++, $row, number_format( floatval( $order['item_tax'] ), 4, '.', '' ) );
			$sheet->setCellValueByColumnAndRow( $col++, $row, $order['order_total'] );
			// Format customer notes - join lines with semicolon for Excel
			$customer_notes = '';
			if ( ! empty( $order['customer_notes'] ) ) {
				$notes_lines = array_filter( array_map( 'trim', explode( "\n", $order['customer_notes'] ) ) );
				if ( ! empty( $notes_lines ) ) {
					$customer_notes = implode( '; ', $notes_lines );
				}
			}
			$sheet->setCellValueByColumnAndRow( $col++, $row, $customer_notes );
			
			$row++;
		}
		
		// Auto-size columns
		foreach ( range( 1, count( $headers ) ) as $col ) {
			$sheet->getColumnDimensionByColumn( $col )->setAutoSize( true );
		}
		
		// Set filename
		$filename = 'almokhlif-oud-sales-report-' . date( 'Y-m-d-His' ) . '.xlsx';
		
		// Set headers for download
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="' . $filename . '"' );
		header( 'Cache-Control: max-age=0' );
		
		// Write to output
		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
		$writer->save( 'php://output' );
		exit;
	}
	
	/**
	 * Get filter values from request
	 *
	 * @return array
	 */
	private function get_filters() {
		// Handle order_id - only set if actually provided and not empty
		$order_id = '';
		if ( isset( $_GET['order_id'] ) && $_GET['order_id'] !== '' && $_GET['order_id'] !== '0' ) {
			$order_id = intval( $_GET['order_id'] );
		}
		
		$filters = array(
			'order_id' => $order_id,
			'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '',
			'date_to' => isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '',
			'order_status' => array(),
			'search' => isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '',
			'limit' => 0,
			'offset' => 0,
		);
		
		// Get order status filter
		if ( isset( $_GET['order_status'] ) && ! empty( $_GET['order_status'] ) ) {
			if ( is_array( $_GET['order_status'] ) ) {
				// Remove 'wc-' prefix from status keys for database matching
				$statuses = array_map( 'sanitize_text_field', $_GET['order_status'] );
				$filters['order_status'] = array_map( function( $status ) {
					// Remove 'wc-' prefix if present, as database stores status without it
					return str_replace( 'wc-', '', $status );
				}, $statuses );
			} else {
				$status = sanitize_text_field( $_GET['order_status'] );
				$filters['order_status'] = array( str_replace( 'wc-', '', $status ) );
			}
		} else {
			// Use default from settings if no filter is set
			$settings = get_option( 'almokhlif_oud_sales_report_settings', array() );
			if ( isset( $settings['default_order_statuses'] ) && ! empty( $settings['default_order_statuses'] ) ) {
				// Remove 'wc-' prefix from default statuses
				$filters['order_status'] = array_map( function( $status ) {
					return str_replace( 'wc-', '', $status );
				}, $settings['default_order_statuses'] );
			}
		}
		
		return $filters;
	}
}

