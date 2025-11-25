# Almokhlif Oud Sales Report

A WooCommerce plugin that extends reporting capabilities with detailed order and item-level data, advanced filtering, and Excel export functionality.

## Features

- **Custom Database Table**: Stores WooCommerce order data with item-level JSON
- **Automatic Sync**: Automatically syncs orders when created or updated
- **Advanced Filtering**: Filter by Order ID, Date Range, Order Status (multi-select)
- **Search Functionality**: Search by invoice number or billing first name
- **Expanded Item View**: Each product in an order appears as its own row
- **Excel Export**: Export filtered results to Excel using PHPSpreadsheet
- **Settings Page**: Configure default order status filters

## Installation

1. Upload the plugin files to `/wp-content/plugins/almokhlif-oud-sales-report/`
2. Install PHPSpreadsheet via Composer:
   ```bash
   cd wp-content/plugins/almokhlif-oud-sales-report
   composer install
   ```
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Oud Sales Report** in the WordPress admin menu

## Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- PHPSpreadsheet (installed via Composer)

## Usage

### List Page

The List Page displays all orders with expanded item rows. Use the filter card to:
- Filter by Order ID
- Filter by Date Range (From/To)
- Filter by Order Status (multi-select)
- Search by Invoice Number or Billing First Name

Click "Export to Excel" to download the current filtered results.

### Settings Page

Configure default order statuses that will be applied when first opening the List Page.

## Database Structure

The plugin creates a custom table `wp_almokhlif_oud_sales_report` with the following columns:

- Order-level data: order_id, invoice_number, billing information, dates, status, payment method, totals, etc.
- Item-level data: stored as JSON in `items_json` column and expanded in the UI

## Author

**Tarik BOUKJIJ**  
Website: https://www.moroccoder.com

## License

This plugin is proprietary software.

