<?php
/**
 * Settings Page
 *
 * @package Almokhlif_Oud_Sales_Report
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Almokhlif_Oud_Sales_Report_Settings_Page {
	
	/**
	 * Render the settings page
	 */
	public function render() {
		// Handle form submission
		if ( isset( $_POST['almokhlif_oud_sales_report_save_settings'] ) && check_admin_referer( 'almokhlif_oud_sales_report_settings' ) ) {
			$this->save_settings();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully.', 'almokhlif-oud-sales-report' ) . '</p></div>';
		}
		
		// Handle demo data generation
		if ( isset( $_POST['almokhlif_oud_sales_report_generate_demo'] ) && check_admin_referer( 'almokhlif_oud_sales_report_demo_data' ) ) {
			$result = Almokhlif_Oud_Sales_Report_Database::generate_demo_data();
			if ( $result['success'] ) {
				echo '<div class="notice notice-success"><p>' . esc_html( $result['message'] ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html( $result['message'] ) . '</p></div>';
			}
		}
		
		// Handle GitHub settings submission
		if ( isset( $_POST['almokhlif_oud_sales_report_save_github'] ) && check_admin_referer( 'almokhlif_oud_sales_report_github_settings' ) ) {
			$this->save_github_settings();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'GitHub settings saved successfully.', 'almokhlif-oud-sales-report' ) . '</p></div>';
		}
		
		// Get current settings
		$settings = get_option( 'almokhlif_oud_sales_report_settings', array() );
		$default_statuses = isset( $settings['default_order_statuses'] ) ? $settings['default_order_statuses'] : array();
		
		// Get GitHub settings
		$github_settings = get_option( 'almokhlif_oud_sales_report_github_settings', array() );
		$github_owner = isset( $github_settings['owner'] ) ? $github_settings['owner'] : '';
		$github_repo = isset( $github_settings['repo'] ) ? $github_settings['repo'] : '';
		$github_token = isset( $github_settings['token'] ) ? $github_settings['token'] : '';
		
		// Get available order statuses
		$order_statuses = wc_get_order_statuses();
		
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'تقارير المخلف - الإعدادات', 'almokhlif-oud-sales-report' ); ?></h1>
			
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=almokhlif-oud-sales-report-settings' ) ); ?>" id="almokhlif-settings-form">
				<?php wp_nonce_field( 'almokhlif_oud_sales_report_settings' ); ?>
				
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="default_order_statuses"><?php esc_html_e( 'حالات الطلب الافتراضية', 'almokhlif-oud-sales-report' ); ?></label>
							</th>
							<td>
								<select id="default_order_statuses" name="default_order_statuses[]" multiple="multiple" style="width: 400px;">
									<?php foreach ( $order_statuses as $status_key => $status_label ) : ?>
										<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( in_array( $status_key, $default_statuses ), true ); ?>>
											<?php echo esc_html( $status_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php esc_html_e( 'حدد حالات الطلب الافتراضية للتصفية حسبها عند فتح تقرير المبيعات لأول مرة.', 'almokhlif-oud-sales-report' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
				
				<?php submit_button( __( 'حفظ الإعدادات', 'almokhlif-oud-sales-report' ), 'primary', 'almokhlif_oud_sales_report_save_settings' ); ?>
			</form>
			
			<!-- GitHub Update Settings Section -->
			<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
				<h2><?php esc_html_e( 'إعدادات التحديث من GitHub', 'almokhlif-oud-sales-report' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'قم بتكوين إعدادات GitHub لتمكين التحديثات التلقائية من المستودع الخاص.', 'almokhlif-oud-sales-report' ); ?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=almokhlif-oud-sales-report-settings' ) ); ?>">
					<?php wp_nonce_field( 'almokhlif_oud_sales_report_github_settings' ); ?>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="github_owner"><?php esc_html_e( 'مالك المستودع (Owner)', 'almokhlif-oud-sales-report' ); ?></label>
								</th>
								<td>
									<input type="text" id="github_owner" name="github_owner" value="<?php echo esc_attr( $github_owner ); ?>" class="regular-text" placeholder="username">
									<p class="description"><?php esc_html_e( 'اسم المستخدم أو المنظمة على GitHub', 'almokhlif-oud-sales-report' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="github_repo"><?php esc_html_e( 'اسم المستودع (Repository)', 'almokhlif-oud-sales-report' ); ?></label>
								</th>
								<td>
									<input type="text" id="github_repo" name="github_repo" value="<?php echo esc_attr( $github_repo ); ?>" class="regular-text" placeholder="repository-name">
									<p class="description"><?php esc_html_e( 'اسم المستودع على GitHub', 'almokhlif-oud-sales-report' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="github_token"><?php esc_html_e( 'رمز الوصول (Token)', 'almokhlif-oud-sales-report' ); ?></label>
								</th>
								<td>
									<input type="password" id="github_token" name="github_token" value="<?php echo esc_attr( $github_token ); ?>" class="regular-text" placeholder="ghp_xxxxxxxxxxxx">
									<p class="description">
										<?php esc_html_e( 'رمز الوصول الشخصي (Personal Access Token) للمستودعات الخاصة. اتركه فارغاً للمستودعات العامة.', 'almokhlif-oud-sales-report' ); ?>
										<br>
										<a href="https://github.com/settings/tokens" target="_blank"><?php esc_html_e( 'إنشاء رمز وصول جديد', 'almokhlif-oud-sales-report' ); ?></a>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					<?php submit_button( __( 'حفظ إعدادات GitHub', 'almokhlif-oud-sales-report' ), 'secondary', 'almokhlif_oud_sales_report_save_github' ); ?>
				</form>
			</div>
			
			<!-- Demo Data Section -->
			<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
				<h2><?php esc_html_e( 'الطلبات النموذجية', 'almokhlif-oud-sales-report' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'أنشئ 1000 طلب نموذجي ببيانات واقعية. سيؤدي هذا إلى إنشاء طلبات في الجدول المخصص بحالات وتواريخ ومعلومات منتجات متنوعة.', 'almokhlif-oud-sales-report' ); ?>
				</p>
				<form method="post" action="" onsubmit="return confirm('<?php esc_attr_e( 'هل أنت متأكد أنك تريد إنشاء 1000 طلب نموذجي؟', 'almokhlif-oud-sales-report' ); ?>');">
					<?php wp_nonce_field( 'almokhlif_oud_sales_report_demo_data' ); ?>
					<p>
						<button type="submit" name="almokhlif_oud_sales_report_generate_demo" class="button button-secondary" style="background: #2271b1; color: #fff; border-color: #2271b1;">
							<?php esc_html_e( 'إنشاء 1000 طلب', 'almokhlif-oud-sales-report' ); ?>
						</button>
					</p>
				</form>
			</div>
		</div>
		
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Initialize select2 for multi-select
			$('#default_order_statuses').select2({
				width: '400px',
				placeholder: '<?php esc_attr_e( 'حدد حالات الطلب', 'almokhlif-oud-sales-report' ); ?>',
				allowClear: true
			});
			
			// Ensure select2 values are properly submitted with the form
			$('#almokhlif-settings-form').on('submit', function(e) {
				var selected = $('#default_order_statuses').val();
				// Remove any existing empty flag
				$(this).find('input[name="default_order_statuses_empty"]').remove();
				// If nothing is selected, add a hidden input to ensure empty array is saved
				if (!selected || selected.length === 0) {
					$(this).append('<input type="hidden" name="default_order_statuses_empty" value="1">');
				}
			});
		});
		</script>
		<?php
	}
	
	/**
	 * Save settings
	 */
	private function save_settings() {
		$settings = array();
		
		// Get default order statuses
		// Check if empty flag is set (when nothing selected) or if we have actual values
		if ( isset( $_POST['default_order_statuses_empty'] ) ) {
			// Nothing was selected, save empty array
			$settings['default_order_statuses'] = array();
		} elseif ( isset( $_POST['default_order_statuses'] ) && is_array( $_POST['default_order_statuses'] ) ) {
			// Filter out any empty values and sanitize
			$statuses = array_filter( array_map( 'sanitize_text_field', $_POST['default_order_statuses'] ), function( $value ) {
				return ! empty( $value );
			} );
			$settings['default_order_statuses'] = array_values( $statuses ); // Re-index array
		} else {
			// Fallback: set to empty array
			$settings['default_order_statuses'] = array();
		}
		
		// Save to database
		update_option( 'almokhlif_oud_sales_report_settings', $settings );
	}
	
	/**
	 * Save GitHub settings
	 */
	private function save_github_settings() {
		$github_settings = array(
			'owner' => isset( $_POST['github_owner'] ) ? sanitize_text_field( $_POST['github_owner'] ) : '',
			'repo'  => isset( $_POST['github_repo'] ) ? sanitize_text_field( $_POST['github_repo'] ) : '',
			'token' => isset( $_POST['github_token'] ) ? sanitize_text_field( $_POST['github_token'] ) : '',
		);
		
		// Save to database
		update_option( 'almokhlif_oud_sales_report_github_settings', $github_settings );
		
		// Clear update cache
		delete_transient( 'almokhlif_oud_sr_latest_release' );
	}
}

