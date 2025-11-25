<?php
/**
 * Database operations for Almokhlif Oud Sales Report
 *
 * @package Almokhlif_Oud_Sales_Report
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Almokhlif_Oud_Sales_Report_Database {
	
	/**
	 * Instance of this class
	 *
	 * @var Almokhlif_Oud_Sales_Report_Database
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
	 *
	 * @return Almokhlif_Oud_Sales_Report_Database
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Get table name
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'almokhlif_oud_sales_report';
	}
	
	/**
	 * Create database table
	 */
	public static function create_table() {
		global $wpdb;
		
		$table_name = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id bigint(20) UNSIGNED NOT NULL,
			invoice_number varchar(255) DEFAULT NULL,
			billing_first_name varchar(255) DEFAULT NULL,
			billing_phone varchar(255) DEFAULT NULL,
			modified_date datetime DEFAULT NULL,
			order_date datetime DEFAULT NULL,
			billing_country varchar(100) DEFAULT NULL,
			billing_address text DEFAULT NULL,
			billing_city varchar(255) DEFAULT NULL,
			order_status varchar(50) DEFAULT NULL,
			payment_method varchar(100) DEFAULT NULL,
			payment_reference varchar(255) DEFAULT NULL,
			odoo_order varchar(255) DEFAULT NULL,
			vat_number varchar(255) DEFAULT NULL,
			order_discount decimal(10,2) DEFAULT 0.00,
			order_coupon varchar(255) DEFAULT NULL,
			staff varchar(255) DEFAULT NULL,
			shipping_cost decimal(10,4) DEFAULT 0.0000,
			item_tax decimal(10,4) DEFAULT 0.0000,
			order_total decimal(10,2) DEFAULT 0.00,
			customer_notes text DEFAULT NULL,
			items_json longtext DEFAULT NULL,
			PRIMARY KEY (id),
			KEY order_id (order_id),
			KEY order_status (order_status),
			KEY invoice_number (invoice_number),
			KEY billing_first_name (billing_first_name),
			KEY order_date (order_date),
			KEY modified_date (modified_date)
		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		// Upgrade existing columns to 4 decimals if needed
		self::upgrade_decimal_columns();
		
		// Add new columns if they don't exist
		self::upgrade_add_new_columns();
	}
	
	/**
	 * Upgrade decimal columns to 4 decimal places
	 */
	private static function upgrade_decimal_columns() {
		global $wpdb;
		
		$table_name = self::get_table_name();
		
		// Check if columns need to be upgraded
		$columns = $wpdb->get_results( "SHOW COLUMNS FROM $table_name WHERE Field IN ('shipping_cost', 'item_tax')" );
		
		foreach ( $columns as $column ) {
			if ( $column->Field === 'shipping_cost' && strpos( $column->Type, 'decimal(10,2)' ) !== false ) {
				$wpdb->query( "ALTER TABLE $table_name MODIFY shipping_cost decimal(10,4) DEFAULT 0.0000" );
			}
			if ( $column->Field === 'item_tax' && strpos( $column->Type, 'decimal(10,2)' ) !== false ) {
				$wpdb->query( "ALTER TABLE $table_name MODIFY item_tax decimal(10,4) DEFAULT 0.0000" );
			}
		}
	}
	
	/**
	 * Add new columns if they don't exist
	 */
	private static function upgrade_add_new_columns() {
		global $wpdb;
		
		$table_name = self::get_table_name();
		
		// Get existing columns
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM $table_name" );
		
		// Add payment_reference after payment_method
		if ( ! in_array( 'payment_reference', $columns ) ) {
			$wpdb->query( "ALTER TABLE $table_name ADD COLUMN payment_reference varchar(255) DEFAULT NULL AFTER payment_method" );
		}
		
		// Add odoo_order after payment_reference
		if ( ! in_array( 'odoo_order', $columns ) ) {
			$wpdb->query( "ALTER TABLE $table_name ADD COLUMN odoo_order varchar(255) DEFAULT NULL AFTER payment_reference" );
		}
		
		// Add staff after order_coupon
		if ( ! in_array( 'staff', $columns ) ) {
			$wpdb->query( "ALTER TABLE $table_name ADD COLUMN staff varchar(255) DEFAULT NULL AFTER order_coupon" );
		}
	}
	
	/**
	 * Insert or update order data
	 *
	 * @param int $order_id Order ID
	 * @param bool $status_changed Whether order status changed
	 * @return int|false Insert/update ID or false on failure
	 */
	public static function sync_order( $order_id, $status_changed = false ) {
		global $wpdb;
		
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}
		
		$table_name = self::get_table_name();
		
		// Get order data
		$order_data = self::prepare_order_data( $order );
		
		// Check if record exists
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table_name WHERE order_id = %d",
			$order_id
		) );
		
		// Prepare modified_date - only update when order status changes
		$current_time = current_time( 'mysql' );
		if ( $existing ) {
			if ( $status_changed ) {
				// Update modified_date only when status changes
				$order_data['modified_date'] = $current_time;
			} else {
				// Keep existing modified_date if status didn't change
				$existing_data = $wpdb->get_row( $wpdb->prepare(
					"SELECT modified_date FROM $table_name WHERE order_id = %d",
					$order_id
				) );
				if ( $existing_data && $existing_data->modified_date ) {
					$order_data['modified_date'] = $existing_data->modified_date;
				} else {
					// If no existing modified_date, set to order_date (shouldn't happen, but handle edge case)
					$order_data['modified_date'] = $order_data['order_date'];
				}
			}
		} else {
			// New insert: modified_date is set to order_date initially (will only change when status changes)
			$order_data['modified_date'] = $order_data['order_date'];
		}
		
		if ( $existing ) {
			// Update existing record - always update coupon field to ensure it's fresh
			$result = $wpdb->update(
				$table_name,
				$order_data,
				array( 'order_id' => $order_id ),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%f', '%f', '%f', '%s', '%s' ),
				array( '%d' )
			);
			
			return $existing;
		} else {
			// Insert new record
			$result = $wpdb->insert(
				$table_name,
				$order_data,
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%f', '%f', '%f', '%s', '%s' )
			);
			return $wpdb->insert_id;
		}
	}
	
	/**
	 * Get Arabic country name from country code
	 *
	 * @param string $country_code Two-letter country code (e.g., 'SA', 'AE')
	 * @return string Arabic country name or original code if not found
	 */
	private static function get_arabic_country_name( $country_code ) {
		if ( empty( $country_code ) ) {
			return '';
		}
		
		// Map of country codes to Arabic names
		$arabic_countries = array(
			'SA' => 'المملكة العربية السعودية',
			'AE' => 'الإمارات العربية المتحدة',
			'KW' => 'الكويت',
			'QA' => 'قطر',
			'BH' => 'البحرين',
			'OM' => 'عمان',
			'YE' => 'اليمن',
			'IQ' => 'العراق',
			'SY' => 'سوريا',
			'JO' => 'الأردن',
			'LB' => 'لبنان',
			'PS' => 'فلسطين',
			'EG' => 'مصر',
			'LY' => 'ليبيا',
			'TN' => 'تونس',
			'DZ' => 'الجزائر',
			'MA' => 'المغرب',
			'SD' => 'السودان',
			'SO' => 'الصومال',
			'DJ' => 'جيبوتي',
			'MR' => 'موريتانيا',
			'US' => 'الولايات المتحدة',
			'GB' => 'المملكة المتحدة',
			'FR' => 'فرنسا',
			'DE' => 'ألمانيا',
			'IT' => 'إيطاليا',
			'ES' => 'إسبانيا',
			'NL' => 'هولندا',
			'BE' => 'بلجيكا',
			'CH' => 'سويسرا',
			'AT' => 'النمسا',
			'SE' => 'السويد',
			'NO' => 'النرويج',
			'DK' => 'الدنمارك',
			'FI' => 'فنلندا',
			'PL' => 'بولندا',
			'GR' => 'اليونان',
			'PT' => 'البرتغال',
			'IE' => 'أيرلندا',
			'CA' => 'كندا',
			'AU' => 'أستراليا',
			'NZ' => 'نيوزيلندا',
			'JP' => 'اليابان',
			'CN' => 'الصين',
			'IN' => 'الهند',
			'PK' => 'باكستان',
			'BD' => 'بنغلاديش',
			'TR' => 'تركيا',
			'IR' => 'إيران',
			'AF' => 'أفغانستان',
			'ID' => 'إندونيسيا',
			'MY' => 'ماليزيا',
			'SG' => 'سنغافورة',
			'TH' => 'تايلاند',
			'VN' => 'فيتنام',
			'PH' => 'الفلبين',
			'KR' => 'كوريا الجنوبية',
			'BR' => 'البرازيل',
			'MX' => 'المكسيك',
			'AR' => 'الأرجنتين',
			'CL' => 'تشيلي',
			'CO' => 'كولومبيا',
			'PE' => 'بيرو',
			'VE' => 'فنزويلا',
			'ZA' => 'جنوب أفريقيا',
			'NG' => 'نيجيريا',
			'KE' => 'كينيا',
			'ET' => 'إثيوبيا',
			'GH' => 'غانا',
			'RU' => 'روسيا',
			'UA' => 'أوكرانيا',
			'IL' => 'إسرائيل',
		);
		
		// Convert to uppercase for case-insensitive lookup
		$country_code = strtoupper( trim( $country_code ) );
		
		// Return Arabic name if found, otherwise return original code
		return isset( $arabic_countries[ $country_code ] ) ? $arabic_countries[ $country_code ] : $country_code;
	}
	
	/**
	 * Prepare order data for database
	 *
	 * @param WC_Order $order WooCommerce order object
	 * @return array
	 */
	private static function prepare_order_data( $order ) {
		// Get invoice number (from PDF invoice plugin meta key, or fallback to other sources)
		$invoice_number = $order->get_meta( '_wcpdf_invoice_number' );
		if ( ! $invoice_number ) {
			// Fallback to other possible invoice number meta keys
			$invoice_number = $order->get_meta( '_invoice_number' );
		}
		if ( ! $invoice_number ) {
			// Final fallback to order number
			$invoice_number = $order->get_order_number();
		}
		
		// Get VAT number from company VAT meta key
		$vat_number = $order->get_meta( '_billing_billing_company_vat' );
		
		// Get billing address
		$billing_address = trim( $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() );
		
		// Calculate total item tax from all items (sum of individual item taxes)
		$item_tax = 0;
		$total_subtotal = 0;
		$total_items_total = 0;
		foreach ( $order->get_items() as $item ) {
			$item_tax += $item->get_total_tax();
			$total_subtotal += $item->get_subtotal();
			$total_items_total += $item->get_total();
		}
		$item_tax = round( $item_tax, 4 );
		
		// Calculate order discount
		// First, check for custom phone order discount (_wpo_cart_discount)
		$wpo_discount_meta = $order->get_meta( '_wpo_cart_discount' );
		$order_discount = 0;
		$discount_type = null;
		$discount_amount = 0;
		$original_subtotal = 0;
		$discount_excl_tax = 0;
		
		if ( ! empty( $wpo_discount_meta ) ) {
			// Unserialize the meta value if it's serialized
			$discount_data = maybe_unserialize( $wpo_discount_meta );
			
			if ( is_array( $discount_data ) && isset( $discount_data['type'] ) && isset( $discount_data['amount'] ) ) {
				$discount_type = $discount_data['type'];
				$discount_amount = floatval( $discount_data['amount'] );
				
				// Calculate tax rate from order items to get discount including tax
				// Tax rate = total tax / total items (excluding tax and discount)
				$tax_rate = 0;
				if ( $total_items_total > 0 ) {
					$tax_rate = $item_tax / $total_items_total;
				}
				
				if ( $discount_type === 'fixed_cart' ) {
					// Fixed cart discount: amount is already the discount excluding tax
					$discount_excl_tax = $discount_amount;
					// Calculate discount including tax
					$order_discount = $discount_excl_tax * ( 1 + $tax_rate );
				} elseif ( $discount_type === 'percent' ) {
					// Percentage discount: calculate from original subtotal before discount
					// The items' get_subtotal() should return original, but if discount was applied,
					// we need to reverse calculate from the current discounted total
					
					// Current items total (after discount, excluding tax)
					$current_items_total = $total_items_total;
					
					// Reverse calculate original subtotal: original = current / (1 - discount%)
					// Example: if current is 2948.40 and discount is 10%, original = 2948.40 / 0.90 = 3276.00
					$original_subtotal = $current_items_total / ( 1 - ( $discount_amount / 100 ) );
					
					// Discount excluding tax = original subtotal * (percentage / 100)
					// Example: 3276.00 * 0.10 = 327.60
					$discount_excl_tax = $original_subtotal * ( $discount_amount / 100 );
					
					// Calculate discount including tax
					$order_discount = $discount_excl_tax * ( 1 + $tax_rate );
				}
			}
		}
		
		// If no custom discount found, use item-level calculation
		// This is the most reliable method: sum of (subtotal - total) for each item
		if ( $order_discount == 0 ) {
			$order_discount = $total_subtotal - $total_items_total;
		}
		
		// Ensure discount is positive and rounded to 2 decimal places
		$order_discount = round( abs( $order_discount ), 2 );
		
		// Get order items as JSON (pass discount info for percentage discounts)
		$items_json = self::prepare_items_json( $order, $discount_type, $discount_amount, $original_subtotal, $discount_excl_tax );
		
		// Get coupons from order items table (type = 'coupon')
		global $wpdb;
		$order_id = $order->get_id();
		$query = $wpdb->prepare(
			"SELECT order_item_name FROM {$wpdb->prefix}woocommerce_order_items 
			WHERE order_id = %d AND order_item_type = 'coupon'",
			$order_id
		);
		$coupon_names = $wpdb->get_col( $query );
		
		// Create comma-separated string of coupon names
		$order_coupon = ! empty( $coupon_names ) ? implode( ', ', array_filter( $coupon_names ) ) : '';
		
		// Get billing country code and convert to Arabic name
		$billing_country_code = $order->get_billing_country();
		$billing_country_arabic = self::get_arabic_country_name( $billing_country_code );
		
		// Get payment reference from transaction ID
		$payment_reference = $order->get_meta( '_transaction_id' );
		
		// Get Odoo order number
		$odoo_order = $order->get_meta( 'odoo_order_number' );
		
		// Get staff name from user ID
		$staff = '';
		$staff_user_id = $order->get_meta( '_wpo_order_creator' );
		if ( ! empty( $staff_user_id ) ) {
			$user = get_user_by( 'ID', $staff_user_id );
			if ( $user ) {
				// Get full name (first name + last name)
				$first_name = get_user_meta( $staff_user_id, 'first_name', true );
				$last_name = get_user_meta( $staff_user_id, 'last_name', true );
				if ( ! empty( $first_name ) || ! empty( $last_name ) ) {
					$staff = trim( $first_name . ' ' . $last_name );
				} else {
					// Fallback to display name if first/last name not available
					$staff = $user->display_name;
				}
			}
		}
		
		return array(
			'order_id' => $order->get_id(),
			'invoice_number' => $invoice_number,
			'billing_first_name' => $order->get_billing_first_name(),
			'billing_phone' => $order->get_billing_phone(),
			'order_date' => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
			'billing_country' => $billing_country_arabic,
			'billing_address' => $billing_address,
			'billing_city' => $order->get_billing_city(),
			'order_status' => $order->get_status(),
			'payment_method' => $order->get_payment_method_title(),
			'payment_reference' => $payment_reference,
			'odoo_order' => $odoo_order,
			'vat_number' => $vat_number,
			'order_discount' => $order_discount,
			'order_coupon' => $order_coupon,
			'staff' => $staff,
			'shipping_cost' => $order->get_shipping_total(),
			'item_tax' => $item_tax,
			'order_total' => $order->get_total(),
			'customer_notes' => $order->get_customer_note(),
			'items_json' => $items_json,
		);
	}
	
	/**
	 * Extract additional product IDs from YWAPO meta data
	 *
	 * @param string|array $ywapo_meta_data Serialized or unserialized YWAPO meta data
	 * @return array Array of product IDs
	 */
	private static function extract_additional_product_ids( $ywapo_meta_data ) {
		$product_ids = array();
		
		if ( empty( $ywapo_meta_data ) ) {
			return $product_ids;
		}
		
		// Unserialize if needed
		$data = maybe_unserialize( $ywapo_meta_data );
		
		if ( ! is_array( $data ) ) {
			return $product_ids;
		}
		
		// Recursively search for product IDs in the array
		// Format: "product-75685-1" -> extract 75685
		$iterator = new RecursiveIteratorIterator( new RecursiveArrayIterator( $data ) );
		foreach ( $iterator as $value ) {
			if ( is_string( $value ) && preg_match( '/^product-(\d+)(?:-\d+)?$/', $value, $matches ) ) {
				$product_id = intval( $matches[1] );
				if ( $product_id > 0 && ! in_array( $product_id, $product_ids, true ) ) {
					$product_ids[] = $product_id;
				}
			}
		}
		
		return $product_ids;
	}
	
	/**
	 * Create item entry for a product
	 *
	 * @param WC_Product $product Product object
	 * @param int $quantity Quantity
	 * @param float $item_price Item price per unit
	 * @param float $total_item_price Total item price
	 * @param float $amount_of_discount Amount of discount
	 * @param float $item_tax Item tax
	 * @return array Item data array
	 */
	private static function create_item_entry( $product, $quantity, $item_price, $total_item_price, $amount_of_discount, $item_tax ) {
		// Get product categories
		$categories = array();
		
		// Get product ID - for variations, get parent product ID for categories
		$product_id = $product->get_id();
		if ( $product->is_type( 'variation' ) ) {
			$product_id = $product->get_parent_id();
		}
		
		// Try multiple methods to get categories
		if ( $product_id ) {
			$terms = wp_get_post_terms( $product_id, 'product_cat' );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$categories[] = $term->name;
				}
			}
		}
		
		// Fallback: try getting categories directly from product object
		if ( empty( $categories ) && method_exists( $product, 'get_category_ids' ) ) {
			$category_ids = $product->get_category_ids();
			if ( ! empty( $category_ids ) ) {
				foreach ( $category_ids as $cat_id ) {
					$term = get_term( $cat_id, 'product_cat' );
					if ( $term && ! is_wp_error( $term ) ) {
						$categories[] = $term->name;
					}
				}
			}
		}
		
		// Final fallback: use WooCommerce's category list function
		if ( empty( $categories ) && function_exists( 'wc_get_product_category_list' ) ) {
			$category_list = wc_get_product_category_list( $product_id, ',', '', '' );
			if ( ! empty( $category_list ) ) {
				// Parse the HTML output to get category names
				$category_names = strip_tags( $category_list );
				$categories = array_map( 'trim', explode( ',', $category_names ) );
			}
		}
		
		return array(
			'product_name' => $product->get_name(),
			'sku' => $product->get_sku() ? $product->get_sku() : '',
			'categories' => $categories,
			'quantity' => $quantity,
			'item_price' => round( $item_price, 4 ),
			'total_item_price' => round( $total_item_price, 4 ),
			'amount_of_discount' => round( $amount_of_discount, 4 ),
			'item_tax' => round( $item_tax, 4 ),
		);
	}
	
	/**
	 * Prepare items JSON from order
	 *
	 * @param WC_Order $order WooCommerce order object
	 * @param string $discount_type Discount type (fixed_cart, percent, or null)
	 * @param float $discount_amount Discount amount or percentage
	 * @param float $original_subtotal Original subtotal before discount (for percentage discounts)
	 * @param float $discount_excl_tax Total discount excluding tax (for percentage discounts)
	 * @return string JSON encoded items
	 */
	private static function prepare_items_json( $order, $discount_type = null, $discount_amount = 0, $original_subtotal = 0, $discount_excl_tax = 0 ) {
		$items = array();
		
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}
			
			// Check for additional products in YWAPO meta data
			$ywapo_meta = $item->get_meta( '_ywapo_meta_data' );
			$additional_product_ids = self::extract_additional_product_ids( $ywapo_meta );
			
			// Get main product quantity
			$main_quantity = $item->get_quantity();
			
			// Get product categories for main product
			$categories = array();
			
			// Get product ID - for variations, get parent product ID for categories
			$product_id = $product->get_id();
			if ( $product->is_type( 'variation' ) ) {
				$product_id = $product->get_parent_id();
			}
			
			// Try multiple methods to get categories
			if ( $product_id ) {
				$terms = wp_get_post_terms( $product_id, 'product_cat' );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					foreach ( $terms as $term ) {
						$categories[] = $term->name;
					}
				}
			}
			
			// Fallback: try getting categories directly from product object
			if ( empty( $categories ) && method_exists( $product, 'get_category_ids' ) ) {
				$category_ids = $product->get_category_ids();
				if ( ! empty( $category_ids ) ) {
					foreach ( $category_ids as $cat_id ) {
						$term = get_term( $cat_id, 'product_cat' );
						if ( $term && ! is_wp_error( $term ) ) {
							$categories[] = $term->name;
						}
					}
				}
			}
			
			// Final fallback: use WooCommerce's category list function
			if ( empty( $categories ) && function_exists( 'wc_get_product_category_list' ) ) {
				$category_list = wc_get_product_category_list( $product_id, ',', '', '' );
				if ( ! empty( $category_list ) ) {
					// Parse the HTML output to get category names
					$category_names = strip_tags( $category_list );
					$categories = array_map( 'trim', explode( ',', $category_names ) );
				}
			}
			
			// Calculate item prices and discount
			$item_subtotal = $item->get_subtotal(); // Original subtotal before discount
			$total_item_price = $item->get_total(); // Price after discount
			
			// Calculate item discount
			$amount_of_discount = 0;
			if ( $discount_type === 'percent' && $original_subtotal > 0 && $discount_excl_tax > 0 ) {
				// For percentage discounts, calculate discount proportionally based on item's share of original subtotal
				// Item's original subtotal (get_subtotal() should return original before discount)
				$item_original_subtotal = $item_subtotal;
				
				// If subtotal equals total, the discount was already applied, so reverse calculate
				if ( abs( $item_subtotal - $total_item_price ) < 0.01 && $discount_amount > 0 ) {
					$item_original_subtotal = $total_item_price / ( 1 - ( $discount_amount / 100 ) );
				}
				
				// Calculate item's share of original subtotal
				$item_share = $item_original_subtotal / $original_subtotal;
				
				// Item's discount excluding tax = total discount * item's share
				$item_discount_excl_tax = $discount_excl_tax * $item_share;
				
				// Calculate tax rate for this item (tax / item total after discount)
				$item_tax_rate = 0;
				if ( $total_item_price > 0 ) {
					$item_tax_rate = $item->get_total_tax() / $total_item_price;
				}
				
				// Item's discount including tax
				$amount_of_discount = $item_discount_excl_tax * ( 1 + $item_tax_rate );
			} else {
				// For fixed_cart or no discount, use standard calculation
				$amount_of_discount = $item_subtotal - $total_item_price;
			}
			
			// Calculate item price per unit (use original subtotal for display)
			$item_price = $item_subtotal / $item->get_quantity();
			
			// Calculate item tax (tax for this specific item)
			$item_tax = $item->get_total_tax();
			
			// Add main product item
			$items[] = array(
				'product_name' => $item->get_name(),
				'sku' => $product->get_sku() ? $product->get_sku() : '',
				'categories' => $categories,
				'quantity' => $item->get_quantity(),
				'item_price' => round( $item_price, 4 ),
				'total_item_price' => round( $total_item_price, 4 ),
				'amount_of_discount' => round( $amount_of_discount, 4 ),
				'item_tax' => round( $item_tax, 4 ),
			);
			
			// Process additional products after main product (they should appear below the main product)
			if ( ! empty( $additional_product_ids ) ) {
				foreach ( $additional_product_ids as $additional_product_id ) {
					$additional_product = wc_get_product( $additional_product_id );
					if ( $additional_product && $additional_product->exists() ) {
						// Get product price
						$additional_price = $additional_product->get_price();
						$additional_item_price = floatval( $additional_price );
						$additional_total_price = $additional_item_price * $main_quantity;
						
						// Calculate tax for additional product
						// Use the same tax rate as the main product if available
						$main_item_tax_rate = 0;
						$main_total_price = $item->get_total();
						if ( $main_total_price > 0 ) {
							$main_item_tax_rate = $item->get_total_tax() / $main_total_price;
						}
						$additional_item_tax = $additional_total_price * $main_item_tax_rate;
						
						// Additional products typically don't have discounts applied
						$additional_discount = 0;
						
						// Create item entry for additional product
						$additional_item = self::create_item_entry(
							$additional_product,
							$main_quantity,
							$additional_item_price,
							$additional_total_price,
							$additional_discount,
							$additional_item_tax
						);
						
						// Add additional product after main product
						$items[] = $additional_item;
					}
				}
			}
		}
		
		return wp_json_encode( $items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}
	
	/**
	 * Get orders with filters
	 *
	 * @param array $args Filter arguments
	 * @return array
	 */
	public static function get_orders( $args = array() ) {
		global $wpdb;
		
		$table_name = self::get_table_name();
		
		$defaults = array(
			'order_id' => '',
			'date_from' => '',
			'date_to' => '',
			'order_status' => array(),
			'search' => '',
			'limit' => 50,
			'offset' => 0,
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$where = array( '1=1' );
		$where_values = array();
		
		// Order ID filter
		if ( ! empty( $args['order_id'] ) ) {
			$where[] = 'order_id = %d';
			$where_values[] = intval( $args['order_id'] );
		}
		
		// Date range filter - search in modified_date
		if ( ! empty( $args['date_from'] ) ) {
			$where[] = 'modified_date >= %s';
			$where_values[] = sanitize_text_field( $args['date_from'] ) . ' 00:00:00';
		}
		
		if ( ! empty( $args['date_to'] ) ) {
			$where[] = 'modified_date <= %s';
			$where_values[] = sanitize_text_field( $args['date_to'] ) . ' 23:59:59';
		}
		
		// Order status filter
		if ( ! empty( $args['order_status'] ) && is_array( $args['order_status'] ) ) {
			$status_placeholders = implode( ',', array_fill( 0, count( $args['order_status'] ), '%s' ) );
			$where[] = "order_status IN ($status_placeholders)";
			$where_values = array_merge( $where_values, array_map( 'sanitize_text_field', $args['order_status'] ) );
		}
		
		// Search filter
		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[] = '(invoice_number LIKE %s OR billing_first_name LIKE %s)';
			$where_values[] = $search;
			$where_values[] = $search;
		}
		
		$where_clause = implode( ' AND ', $where );
		
		// Build query
		$query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY order_date DESC";
		
		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}
		
		// Add limit and offset
		if ( $args['limit'] > 0 ) {
			$query .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );
		}
		
		$results = $wpdb->get_results( $query, ARRAY_A );
		
		// Expand items JSON to create one row per product
		$expanded_results = array();
		foreach ( $results as $row ) {
			// Convert country code to Arabic name for backward compatibility (in case old records still have codes)
			if ( isset( $row['billing_country'] ) && ! empty( $row['billing_country'] ) ) {
				// Check if it's a 2-letter code (uppercase) - if so, convert it
				$country_value = trim( $row['billing_country'] );
				if ( strlen( $country_value ) === 2 && ctype_upper( $country_value ) ) {
					$row['billing_country'] = self::get_arabic_country_name( $country_value );
				}
			}
			
			// Ensure order_coupon is always a string, and handle numeric zeros
			if ( isset( $row['order_coupon'] ) ) {
				$coupon_val = trim( (string) $row['order_coupon'] );
				// If it's a numeric zero (0, 0.0, 0.000000, etc.) or empty, treat as empty
				// But preserve actual coupon names even if they look numeric
				if ( empty( $coupon_val ) || 
				     ( is_numeric( $coupon_val ) && floatval( $coupon_val ) == 0 && strlen( $coupon_val ) <= 10 ) ) {
					// Only treat as zero if it's a short numeric string (to avoid treating long numeric codes as zeros)
					$row['order_coupon'] = '';
				} else {
					$row['order_coupon'] = $coupon_val;
				}
			} else {
				$row['order_coupon'] = '';
			}
			
			$items = json_decode( $row['items_json'], true );
			if ( ! is_array( $items ) || empty( $items ) ) {
				// If no items, still show the order row
				$expanded_results[] = $row;
			} else {
				foreach ( $items as $item ) {
					$expanded_row = $row;
					$expanded_row['product_name'] = isset( $item['product_name'] ) ? $item['product_name'] : '';
					$expanded_row['sku'] = isset( $item['sku'] ) ? $item['sku'] : '';
					$expanded_row['categories'] = isset( $item['categories'] ) ? implode( ', ', $item['categories'] ) : '';
					$expanded_row['quantity'] = isset( $item['quantity'] ) ? $item['quantity'] : 0;
					$expanded_row['item_price'] = isset( $item['item_price'] ) ? $item['item_price'] : 0;
					$expanded_row['total_item_price'] = isset( $item['total_item_price'] ) ? $item['total_item_price'] : 0;
					$expanded_row['amount_of_discount'] = isset( $item['amount_of_discount'] ) ? $item['amount_of_discount'] : 0;
					// Use item tax from JSON (per-item tax) instead of order-level tax
					$expanded_row['item_tax'] = isset( $item['item_tax'] ) ? $item['item_tax'] : 0;
					$expanded_results[] = $expanded_row;
				}
			}
		}
		
		return $expanded_results;
	}
	
	/**
	 * Get total count of orders matching filters
	 *
	 * @param array $args Filter arguments
	 * @return int
	 */
	public static function get_orders_count( $args = array() ) {
		global $wpdb;
		
		$table_name = self::get_table_name();
		
		$defaults = array(
			'order_id' => '',
			'date_from' => '',
			'date_to' => '',
			'order_status' => array(),
			'search' => '',
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$where = array( '1=1' );
		$where_values = array();
		
		// Order ID filter
		if ( ! empty( $args['order_id'] ) ) {
			$where[] = 'order_id = %d';
			$where_values[] = intval( $args['order_id'] );
		}
		
		// Date range filter - search in modified_date
		if ( ! empty( $args['date_from'] ) ) {
			$where[] = 'modified_date >= %s';
			$where_values[] = sanitize_text_field( $args['date_from'] ) . ' 00:00:00';
		}
		
		if ( ! empty( $args['date_to'] ) ) {
			$where[] = 'modified_date <= %s';
			$where_values[] = sanitize_text_field( $args['date_to'] ) . ' 23:59:59';
		}
		
		// Order status filter
		if ( ! empty( $args['order_status'] ) && is_array( $args['order_status'] ) ) {
			$status_placeholders = implode( ',', array_fill( 0, count( $args['order_status'] ), '%s' ) );
			$where[] = "order_status IN ($status_placeholders)";
			$where_values = array_merge( $where_values, array_map( 'sanitize_text_field', $args['order_status'] ) );
		}
		
		// Search filter
		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[] = '(invoice_number LIKE %s OR billing_first_name LIKE %s)';
			$where_values[] = $search;
			$where_values[] = $search;
		}
		
		$where_clause = implode( ' AND ', $where );
		
		// Build query - count distinct orders (before expansion)
		$query = "SELECT COUNT(DISTINCT order_id) FROM $table_name WHERE $where_clause";
		
		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}
		
		return (int) $wpdb->get_var( $query );
	}
	
	/**
	 * Generate demo data - sync latest 1000 real WooCommerce orders
	 *
	 * @return array Result with success status and message
	 */
	public static function generate_demo_data() {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array(
				'success' => false,
				'message' => __( 'WooCommerce is not active. Please activate WooCommerce first.', 'almokhlif-oud-sales-report' )
			);
		}
		
		// Get latest 1000 orders
		$orders = wc_get_orders( array(
			'limit' => 1000,
			'orderby' => 'date',
			'order' => 'DESC',
			'status' => 'any',
		) );
		
		if ( empty( $orders ) ) {
			return array(
				'success' => false,
				'message' => __( 'No WooCommerce orders found. Please create some orders first.', 'almokhlif-oud-sales-report' )
			);
		}
		
		$synced = 0;
		$skipped = 0;
		$errors = 0;
		
		// Sync each order
		foreach ( $orders as $order ) {
			if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
				$errors++;
				continue;
			}
			
			// Check if order already exists in custom table
			global $wpdb;
			$table_name = self::get_table_name();
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $table_name WHERE order_id = %d",
				$order->get_id()
			) );
			
			if ( $existing ) {
				// Order already exists, skip it
				$skipped++;
				continue;
			}
			
			// Sync the order (status_changed = false since we're just syncing existing orders)
			$result = self::sync_order( $order->get_id(), false );
			
			if ( $result !== false ) {
				$synced++;
			} else {
				$errors++;
			}
		}
		
		// Build result message
		$message_parts = array();
		
		if ( $synced > 0 ) {
			$message_parts[] = sprintf( __( 'Successfully synced %d orders', 'almokhlif-oud-sales-report' ), $synced );
		}
		
		if ( $skipped > 0 ) {
			$message_parts[] = sprintf( __( '%d orders already existed and were skipped', 'almokhlif-oud-sales-report' ), $skipped );
		}
		
		if ( $errors > 0 ) {
			$message_parts[] = sprintf( __( '%d orders had errors', 'almokhlif-oud-sales-report' ), $errors );
		}
		
		$message = ! empty( $message_parts ) ? implode( '. ', $message_parts ) . '.' : __( 'No orders were processed.', 'almokhlif-oud-sales-report' );
		
		return array(
			'success' => $synced > 0 || $skipped > 0,
			'message' => $message
		);
	}
}

