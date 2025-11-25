<?php
/**
 * List Page for displaying sales report
 *
 * @package Almokhlif_Oud_Sales_Report
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Almokhlif_Oud_Sales_Report_List_Page {
	
	/**
	 * Render the list page
	 */
	public function render() {
		// Handle Excel export
		if ( isset( $_GET['export'] ) && $_GET['export'] === 'excel' ) {
			$export = new Almokhlif_Oud_Sales_Report_Excel_Export();
			$export->export();
			exit;
		}
		
		// Get filter values
		$filters = $this->get_filters();
		
		// Get pagination settings
		$per_page = isset( $_GET['per_page'] ) ? intval( $_GET['per_page'] ) : 50;
		$per_page = max( 10, min( 500, $per_page ) ); // Between 10 and 500
		$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$filters['limit'] = $per_page;
		$filters['offset'] = ( $current_page - 1 ) * $per_page;
		
		// Get orders
		$orders = Almokhlif_Oud_Sales_Report_Database::get_orders( $filters );
		$total_count = Almokhlif_Oud_Sales_Report_Database::get_orders_count( $filters );
		$total_pages = ceil( $total_count / $per_page );
		
		// Get available order statuses
		$order_statuses = wc_get_order_statuses();
		
		// Get settings for default statuses
		$settings = get_option( 'almokhlif_oud_sales_report_settings', array() );
		$default_statuses = isset( $settings['default_order_statuses'] ) ? $settings['default_order_statuses'] : array();
		
		// Get selected statuses for display (with wc- prefix for the select field)
		$selected_statuses_for_display = array();
		$is_first_visit_display = empty( $_GET ) || ( count( $_GET ) === 1 && isset( $_GET['page'] ) );
		$form_submitted_display = isset( $_GET['order_id'] ) || isset( $_GET['date_from'] ) || isset( $_GET['date_to'] ) || isset( $_GET['order_status'] ) || isset( $_GET['search'] ) || isset( $_GET['per_page'] ) || isset( $_GET['paged'] );
		
		if ( isset( $_GET['order_status'] ) ) {
			// User has explicitly set status filter
			if ( is_array( $_GET['order_status'] ) && ! empty( $_GET['order_status'] ) ) {
				$selected_statuses_for_display = array_map( 'sanitize_text_field', $_GET['order_status'] );
			}
			// If empty, leave $selected_statuses_for_display as empty array (show all)
		} elseif ( $is_first_visit_display && ! $form_submitted_display ) {
			// First visit - use defaults
			$selected_statuses_for_display = $default_statuses;
		}
		// Otherwise leave empty (show all statuses)
		
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'تقارير المخلف - تقرير المبيعات', 'almokhlif-oud-sales-report' ); ?></h1>
			
			<!-- Filter Card -->
			<div class="almokhlif-filter-card">
				<form method="get" action="">
					<?php
					// Preserve page parameter (per_page is now in the form as select)
					foreach ( $_GET as $key => $value ) {
						if ( $key !== 'order_id' && $key !== 'date_from' && $key !== 'date_to' && $key !== 'order_status' && $key !== 'search' && $key !== 'paged' && $key !== 'per_page' ) {
							echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
						}
					}
					?>
					
					<div class="almokhlif-filter-row">
						<div class="almokhlif-filter-field">
							<label for="order_id"><?php esc_html_e( 'رقم الطلب', 'almokhlif-oud-sales-report' ); ?></label>
							<input type="number" id="order_id" name="order_id" value="<?php echo esc_attr( $filters['order_id'] ? $filters['order_id'] : '' ); ?>" placeholder="<?php esc_attr_e( 'رقم الطلب', 'almokhlif-oud-sales-report' ); ?>">
						</div>
						
						<div class="almokhlif-filter-field">
							<label for="date_from"><?php esc_html_e( 'التاريخ من', 'almokhlif-oud-sales-report' ); ?></label>
							<input type="text" id="date_from" name="date_from" class="datepicker" value="<?php echo esc_attr( $filters['date_from'] ); ?>" placeholder="YYYY-MM-DD">
						</div>
						
						<div class="almokhlif-filter-field">
							<label for="date_to"><?php esc_html_e( 'التاريخ إلى', 'almokhlif-oud-sales-report' ); ?></label>
							<input type="text" id="date_to" name="date_to" class="datepicker" value="<?php echo esc_attr( $filters['date_to'] ); ?>" placeholder="YYYY-MM-DD">
						</div>
						
						<div class="almokhlif-filter-field">
							<label for="order_status"><?php esc_html_e( 'حالات الطلب', 'almokhlif-oud-sales-report' ); ?></label>
							<select id="order_status" name="order_status[]" multiple="multiple" style="width: 100%;">
								<?php foreach ( $order_statuses as $status_key => $status_label ) : ?>
									<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( in_array( $status_key, $selected_statuses_for_display ), true ); ?>>
										<?php echo esc_html( $status_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					
					<div class="almokhlif-filter-row">
						<div class="almokhlif-filter-field" style="flex: 2;">
							<label for="search"><?php esc_html_e( 'البحث (رقم الفاتورة أو الاسم)', 'almokhlif-oud-sales-report' ); ?></label>
							<input type="text" id="search" name="search" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'بحث...', 'almokhlif-oud-sales-report' ); ?>">
						</div>
						
						<div class="almokhlif-filter-field" style="flex: 0 0 auto; display: flex; align-items: flex-end; gap: 10px;">
							<div>
								<label for="per_page" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'عدد الطلبات في الصفحة', 'almokhlif-oud-sales-report' ); ?></label>
								<select id="per_page" name="per_page">
									<option value="10" <?php selected( $per_page, 10 ); ?>>10</option>
									<option value="25" <?php selected( $per_page, 25 ); ?>>25</option>
									<option value="50" <?php selected( $per_page, 50 ); ?>>50</option>
									<option value="100" <?php selected( $per_page, 100 ); ?>>100</option>
									<option value="200" <?php selected( $per_page, 200 ); ?>>200</option>
									<option value="500" <?php selected( $per_page, 500 ); ?>>500</option>
								</select>
							</div>
							<!----->
						</div>
					</div>
					<div class="almokhlif-filter-row">
						<div>
							<button type="submit" class="button button-primary"><?php esc_html_e( 'تصفية', 'almokhlif-oud-sales-report' ); ?></button>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=almokhlif-oud-sales-report' ) ); ?>" class="button"><?php esc_html_e( 'إفراغ', 'almokhlif-oud-sales-report' ); ?></a>
						</div>
					</div>
				</form>
			</div>
			
			<!-- Export Button and Column Visibility -->
			<div class="almokhlif-export-button" style="margin: 20px 0; display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
				<a href="<?php echo esc_url( add_query_arg( array_merge( $_GET, array( 'export' => 'excel' ) ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'تصدير إلى Excel', 'almokhlif-oud-sales-report' ); ?>
				</a>
				<span style="margin-right: 10px; color: #666; font-weight: bold;">
					<?php
					/* translators: %d: number of orders */
					printf( esc_html__( 'الإجمالي: %d طلبات', 'almokhlif-oud-sales-report' ), esc_html( $total_count ) );
					?>
				</span>
				<div class="almokhlif-column-toggle-wrapper" style="margin-right: auto;">
					<button type="button" class="button" id="almokhlif-column-toggle-btn">
						<span class="dashicons dashicons-admin-settings" style="vertical-align: middle; margin-top: 3px;"></span>
						<?php esc_html_e( 'رؤية الأعمدة', 'almokhlif-oud-sales-report' ); ?>
					</button>
					<div class="almokhlif-column-toggle-menu" id="almokhlif-column-toggle-menu" style="display: none;">
						<div class="almokhlif-column-toggle-header">
							<strong><?php esc_html_e( 'إظهار/إخفاء الأعمدة', 'almokhlif-oud-sales-report' ); ?></strong>
							<button type="button" class="almokhlif-column-toggle-close" style="float: left; background: none; border: none; cursor: pointer; font-size: 18px; color: #666;">&times;</button>
						</div>
						<div class="almokhlif-column-toggle-list" id="almokhlif-column-toggle-list">
							<!-- Column checkboxes will be populated by JavaScript -->
						</div>
						<div class="almokhlif-column-toggle-actions">
							<button type="button" class="button button-small" id="almokhlif-column-show-all"><?php esc_html_e( 'إظهار الكل', 'almokhlif-oud-sales-report' ); ?></button>
							<button type="button" class="button button-small" id="almokhlif-column-hide-all"><?php esc_html_e( 'إخفاء الكل', 'almokhlif-oud-sales-report' ); ?></button>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav-pages" style="float: right; margin: 10px 0;">
					<span class="displaying-num" style="margin-left: 10px;">
						<?php
						$start = ( $current_page - 1 ) * $per_page + 1;
						$end = min( $current_page * $per_page, $total_count );
						printf(
							/* translators: 1: start number, 2: end number, 3: total */
							esc_html__( 'عرض %1$d-%2$d من %3$d', 'almokhlif-oud-sales-report' ),
							$start,
							$end,
							$total_count
						);
						?>
					</span>
					<span class="pagination-links">
						<?php
						// Preserve all GET parameters except paged
						$base_url = remove_query_arg( 'paged' );
						// Ensure per_page is set
						if ( ! isset( $_GET['per_page'] ) ) {
							$base_url = add_query_arg( 'per_page', $per_page, $base_url );
						}
						
						// First page
						if ( $current_page > 1 ) {
							echo '<a style="margin-left: 5px;" class="first-page button" href="' . esc_url( add_query_arg( 'paged', 1, $base_url ) ) . '">&laquo;</a>';
							echo '<a style="margin-left: 5px;" class="prev-page button" href="' . esc_url( add_query_arg( 'paged', max( 1, $current_page - 1 ), $base_url ) ) . '">&lsaquo;</a>';
						} else {
							echo '<span style="margin-left: 5px;" class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
							echo '<span style="margin-left: 5px;" class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
						}
						
						// Page numbers
						echo '<span style="margin-left: 5px;" class="paging-input">';
						printf(
							/* translators: 1: current page, 2: total pages */
							esc_html__( 'الصفحة %1$d من %2$d', 'almokhlif-oud-sales-report' ),
							$current_page,
							$total_pages
						);
						echo '</span>';
						
						// Last page
						if ( $current_page < $total_pages ) {
							echo '<a style="margin-left: 5px;" class="next-page button" href="' . esc_url( add_query_arg( 'paged', min( $total_pages, $current_page + 1 ), $base_url ) ) . '">&rsaquo;</a>';
							echo '<a class="last-page button" href="' . esc_url( add_query_arg( 'paged', $total_pages, $base_url ) ) . '">&raquo;</a>';
						} else {
							echo '<span style="margin-left: 5px;" class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
							echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
						}
						?>
					</span>
				</div>
				<div style="clear: both;"></div>
			<?php endif; ?>
			
			<!-- Table with Horizontal Scroll -->
			<div class="almokhlif-table-wrapper">
				<table class="wp-list-table widefat fixed striped" id="almokhlif-sales-table">
				<thead>
				<tr>
						<th data-column="order-id"><?php esc_html_e( 'Order ID', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="invoice-number"><?php esc_html_e( 'Invoice Number', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="billing-first-name"><?php esc_html_e( 'Billing First Name', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="billing-phone"><?php esc_html_e( 'Billing Phone', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="modified-date"><?php esc_html_e( 'Modified Date', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="order-date"><?php esc_html_e( 'Order Date', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="billing-country"><?php esc_html_e( 'Billing Country', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="billing-address"><?php esc_html_e( 'Billing Address', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="billing-city"><?php esc_html_e( 'Billing City', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="order-status"><?php esc_html_e( 'Order Status', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="payment-method"><?php esc_html_e( 'Payment Method', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="payment-reference"><?php esc_html_e( 'Payment Reference', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="odoo-order"><?php esc_html_e( 'Odoo Order', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="vat-number"><?php esc_html_e( 'VAT Number', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="order-discount"><?php esc_html_e( 'Order Discount', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="order-coupon"><?php esc_html_e( 'Order Coupon', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="staff"><?php esc_html_e( 'Staff', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="product-name"><?php esc_html_e( 'Product Name', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="sku"><?php esc_html_e( 'SKU', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="product-categories"><?php esc_html_e( 'Product Categories', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="quantity"><?php esc_html_e( 'Quantity', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="item-price"><?php esc_html_e( 'Item Price', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="total-item-price"><?php esc_html_e( 'Total Item Price', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="amount-of-discount"><?php esc_html_e( 'Amount of Discount', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="shipping-cost"><?php esc_html_e( 'Shipping Cost', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="item-tax"><?php esc_html_e( 'Item Tax', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="order-total"><?php esc_html_e( 'Order Total', 'almokhlif-oud-sales-report' ); ?></th>
						<th data-column="customer-notes"><?php esc_html_e( 'Customer Notes', 'almokhlif-oud-sales-report' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $orders ) ) : ?>
						<?php foreach ( $orders as $order ) : ?>
							<tr>
								<td data-column="order-id"><?php echo esc_html( $order['order_id'] ); ?></td>
								<td data-column="invoice-number"><?php echo esc_html( $order['invoice_number'] ); ?></td>
								<td data-column="billing-first-name"><?php echo esc_html( $order['billing_first_name'] ); ?></td>
								<td data-column="billing-phone"><?php echo esc_html( $order['billing_phone'] ); ?></td>
								<td data-column="modified-date"><?php echo esc_html( $order['modified_date'] ); ?></td>
								<td data-column="order-date"><?php echo esc_html( $order['order_date'] ); ?></td>
								<td data-column="billing-country"><?php echo esc_html( $order['billing_country'] ); ?></td>
								<td data-column="billing-address"><?php echo esc_html( $order['billing_address'] ); ?></td>
								<td data-column="billing-city"><?php echo esc_html( $order['billing_city'] ); ?></td>
								<td data-column="order-status"><?php echo esc_html( wc_get_order_status_name( $order['order_status'] ) ); ?></td>
								<td data-column="payment-method"><?php echo esc_html( $order['payment_method'] ); ?></td>
								<td data-column="payment-reference"><?php echo esc_html( isset( $order['payment_reference'] ) ? $order['payment_reference'] : '' ); ?></td>
								<td data-column="odoo-order"><?php echo esc_html( isset( $order['odoo_order'] ) ? $order['odoo_order'] : '' ); ?></td>
								<td data-column="vat-number"><?php echo esc_html( $order['vat_number'] ); ?></td>
								<td data-column="order-discount"><?php echo esc_html( number_format( floatval( $order['order_discount'] ), 2, '.', '' ) ); ?></td>
								<td data-column="order-coupon"><?php echo esc_html( ! empty( $order['order_coupon'] ) ? $order['order_coupon'] : '' ); ?></td>
								<td data-column="staff"><?php echo esc_html( isset( $order['staff'] ) ? $order['staff'] : '' ); ?></td>
								<td data-column="product-name"><?php echo esc_html( isset( $order['product_name'] ) ? $order['product_name'] : '' ); ?></td>
								<td data-column="sku"><?php echo esc_html( isset( $order['sku'] ) ? $order['sku'] : '' ); ?></td>
								<td data-column="product-categories"><?php echo esc_html( isset( $order['categories'] ) ? $order['categories'] : '' ); ?></td>
								<td data-column="quantity"><?php echo esc_html( isset( $order['quantity'] ) ? $order['quantity'] : '' ); ?></td>
								<td data-column="item-price"><?php echo esc_html( isset( $order['item_price'] ) ? number_format( floatval( $order['item_price'] ), 4, '.', '' ) : '' ); ?></td>
								<td data-column="total-item-price"><?php echo esc_html( isset( $order['total_item_price'] ) ? number_format( floatval( $order['total_item_price'] ), 4, '.', '' ) : '' ); ?></td>
								<td data-column="amount-of-discount"><?php echo esc_html( isset( $order['amount_of_discount'] ) ? number_format( floatval( $order['amount_of_discount'] ), 4, '.', '' ) : '' ); ?></td>
								<td data-column="shipping-cost"><?php echo esc_html( number_format( floatval( $order['shipping_cost'] ), 4, '.', '' ) ); ?></td>
								<td data-column="item-tax"><?php echo esc_html( number_format( floatval( $order['item_tax'] ), 4, '.', '' ) ); ?></td>
								<td data-column="order-total"><?php echo esc_html( number_format( floatval( $order['order_total'] ), 2, '.', '' ) ); ?></td>
								<td data-column="customer-notes">
									<?php
									if ( ! empty( $order['customer_notes'] ) ) {
										// Split notes by newlines and format as unordered list
										$notes_lines = array_filter( array_map( 'trim', explode( "\n", $order['customer_notes'] ) ) );
										if ( ! empty( $notes_lines ) ) {
											echo '<ul style="margin: 0; padding-left: 20px;">';
											foreach ( $notes_lines as $note_line ) {
												if ( ! empty( $note_line ) ) {
													echo '<li>' . esc_html( $note_line ) . '</li>';
												}
											}
											echo '</ul>';
										}
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="28" style="text-align: right;font-weight: bold;color: red;">
								<?php esc_html_e( 'No orders found.', 'almokhlif-oud-sales-report' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
		
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Initialize datepicker
			$('.datepicker').datepicker({
				dateFormat: 'yy-mm-dd'
			});
			
			// Initialize select2 for multi-select
			$('#order_status').select2({
				width: '100%',
				placeholder: '<?php esc_attr_e( 'حدد حالات الطلب', 'almokhlif-oud-sales-report' ); ?>'
			});
			
			// Column Visibility Feature
			var columnVisibility = {
				storageKey: 'almokhlif_oud_sales_report_hidden_columns',
				init: function() {
					this.setupColumnList();
					this.loadSavedVisibility();
					this.bindEvents();
				},
				setupColumnList: function() {
					var $table = $('#almokhlif-sales-table');
					var $menu = $('#almokhlif-column-toggle-list');
					var columns = [];
					
					$table.find('thead th[data-column]').each(function() {
						var $th = $(this);
						var columnKey = $th.data('column');
						var columnName = $th.text().trim();
						columns.push({
							key: columnKey,
							name: columnName
						});
					});
					
					var html = '';
					columns.forEach(function(col) {
						html += '<label style="display: block; padding: 5px 10px; cursor: pointer;">';
						html += '<input type="checkbox" class="almokhlif-column-checkbox" data-column="' + col.key + '" checked> ';
						html += col.name;
						html += '</label>';
					});
					$menu.html(html);
				},
				loadSavedVisibility: function() {
					var hiddenColumns = this.getHiddenColumns();
					var self = this;
					
					// Update checkboxes
					$('.almokhlif-column-checkbox').each(function() {
						var columnKey = $(this).data('column');
						var isHidden = hiddenColumns.indexOf(columnKey) !== -1;
						$(this).prop('checked', !isHidden);
					});
					
					// Apply visibility
					hiddenColumns.forEach(function(columnKey) {
						self.toggleColumn(columnKey, false);
					});
				},
				getHiddenColumns: function() {
					var saved = localStorage.getItem(this.storageKey);
					return saved ? JSON.parse(saved) : [];
				},
				saveHiddenColumns: function(hiddenColumns) {
					localStorage.setItem(this.storageKey, JSON.stringify(hiddenColumns));
				},
				toggleColumn: function(columnKey, show) {
					var $table = $('#almokhlif-sales-table');
					var selector = '[data-column="' + columnKey + '"]';
					
					if (show) {
						$table.find(selector).removeClass('almokhlif-column-hidden');
					} else {
						$table.find(selector).addClass('almokhlif-column-hidden');
					}
				},
				updateVisibility: function() {
					var hiddenColumns = [];
					var self = this;
					
					$('.almokhlif-column-checkbox').each(function() {
						var $checkbox = $(this);
						var columnKey = $checkbox.data('column');
						var isChecked = $checkbox.is(':checked');
						
						if (!isChecked) {
							hiddenColumns.push(columnKey);
						}
						
						self.toggleColumn(columnKey, isChecked);
					});
					
					this.saveHiddenColumns(hiddenColumns);
				},
				bindEvents: function() {
					var self = this;
					
					// Toggle menu
					$('#almokhlif-column-toggle-btn').on('click', function(e) {
						e.stopPropagation();
						$('#almokhlif-column-toggle-menu').toggle();
					});
					
					// Close menu
					$('.almokhlif-column-toggle-close').on('click', function() {
						$('#almokhlif-column-toggle-menu').hide();
					});
					
					// Close menu when clicking outside
					$(document).on('click', function(e) {
						if (!$(e.target).closest('.almokhlif-column-toggle-wrapper').length) {
							$('#almokhlif-column-toggle-menu').hide();
						}
					});
					
					// Column checkbox change
					$(document).on('change', '.almokhlif-column-checkbox', function() {
						self.updateVisibility();
					});
					
					// Show all
					$('#almokhlif-column-show-all').on('click', function() {
						$('.almokhlif-column-checkbox').prop('checked', true);
						self.updateVisibility();
					});
					
					// Hide all
					$('#almokhlif-column-hide-all').on('click', function() {
						$('.almokhlif-column-checkbox').prop('checked', false);
						self.updateVisibility();
					});
				}
			};
			
			// Initialize column visibility
			columnVisibility.init();
		});
		</script>
		<?php
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
			'limit' => 50, // Will be overridden in render()
			'offset' => 0, // Will be overridden in render()
		);
		
		// Check if this is first visit (only page parameter or no GET params)
		$is_first_visit = empty( $_GET ) || ( count( $_GET ) === 1 && isset( $_GET['page'] ) );
		
		// Check if form was submitted (any filter field is present)
		$form_submitted = isset( $_GET['order_id'] ) || isset( $_GET['date_from'] ) || isset( $_GET['date_to'] ) || isset( $_GET['order_status'] ) || isset( $_GET['search'] ) || isset( $_GET['per_page'] ) || isset( $_GET['paged'] );
		
		// Get order status filter
		if ( isset( $_GET['order_status'] ) ) {
			// User has explicitly set status filter
			if ( is_array( $_GET['order_status'] ) && ! empty( $_GET['order_status'] ) ) {
				// Remove 'wc-' prefix from status keys for database matching
				$statuses = array_map( 'sanitize_text_field', $_GET['order_status'] );
				$filters['order_status'] = array_map( function( $status ) {
					// Remove 'wc-' prefix if present, as database stores status without it
					return str_replace( 'wc-', '', $status );
				}, $statuses );
			} else {
				// Empty array or single empty value - show all statuses
				$filters['order_status'] = array();
			}
		} elseif ( $is_first_visit && ! $form_submitted ) {
			// First visit and form not submitted - use default from settings
			$settings = get_option( 'almokhlif_oud_sales_report_settings', array() );
			if ( isset( $settings['default_order_statuses'] ) && ! empty( $settings['default_order_statuses'] ) ) {
				// Remove 'wc-' prefix from default statuses
				$filters['order_status'] = array_map( function( $status ) {
					return str_replace( 'wc-', '', $status );
				}, $settings['default_order_statuses'] );
			}
		} else {
			// Form was submitted but no status filter - show all statuses
			$filters['order_status'] = array();
		}
		
		return $filters;
	}
}

