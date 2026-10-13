<?php
/**
 * Admin Settings
 *
 * @package Backorder_Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WMBF_Admin_Settings class.
 */
class WMBF_Admin_Settings {

	/**
	 * The single instance of the class.
	 *
	 * @var WMBF_Admin_Settings
	 */
	protected static $_instance = null;

	/**
	 * Main instance.
	 *
	 * @return WMBF_Admin_Settings
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_backorder_status', array( $this, 'output_settings' ) );
		add_action( 'woocommerce_settings_save_backorder_status', array( $this, 'save_settings' ) );
		add_action( 'wp_ajax_wmbf_save_default_message', array( $this, 'ajax_save_default_message' ) );
		add_action( 'wp_ajax_wmbf_add_status', array( $this, 'ajax_add_status' ) );
		add_action( 'wp_ajax_wmbf_delete_status', array( $this, 'ajax_delete_status' ) );
		add_action( 'wp_ajax_wmbf_update_status_order', array( $this, 'ajax_update_status_order' ) );
		add_action( 'wp_ajax_wmbf_export_statuses', array( $this, 'ajax_export_statuses' ) );
		add_action( 'wp_ajax_wmbf_import_statuses', array( $this, 'ajax_import_statuses' ) );

		// Hide the save button for this tab.
		add_action( 'admin_footer', array( $this, 'hide_save_button' ) );
	}

	/**
	 * Add settings tab to WooCommerce settings.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs.
	 * @return array
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['backorder_status'] = __( 'Backorder Status', 'backorder-flow' );
		return $settings_tabs;
	}

	/**
	 * Output settings page.
	 */
	public function output_settings() {
		$statuses = $this->get_statuses();
		$default_message = get_option( 'wmbf_default_message', __( 'This item is currently on backorder.', 'backorder-flow' ) );
		?>
		<div class="wc-cbs-settings-wrap">
			<h2><?php esc_html_e( 'Custom Backorder Status Settings', 'backorder-flow' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Create and manage custom backorder statuses for your products. These statuses will be displayed throughout your store and grouped in cart/checkout.', 'backorder-flow' ); ?>
			</p>

			<!-- Default Backorder Message -->
			<table class="form-table wc-cbs-general-settings">
				<tbody>
					<tr>
						<th scope="row">
							<label for="wc-cbs-default-message"><?php esc_html_e( 'Default Backorder Message', 'backorder-flow' ); ?></label>
						</th>
						<td>
							<textarea id="wc-cbs-default-message" name="wmbf_default_message" rows="3" class="large-text"><?php echo esc_textarea( $default_message ); ?></textarea>
							<p class="description"><?php esc_html_e( 'This message will be shown when no custom backorder status is assigned to a product.', 'backorder-flow' ); ?></p>
							<button type="button" class="button wc-cbs-save-default-message" style="margin-top: 10px;">
								<?php esc_html_e( 'Save Default Message', 'backorder-flow' ); ?>
							</button>
						</td>
					</tr>
				</tbody>
			</table>

			<h3 style="margin-top: 30px;"><?php esc_html_e( 'Custom Backorder Statuses', 'backorder-flow' ); ?></h3>

			<div class="wc-cbs-actions">
				<button type="button" class="button button-primary wc-cbs-add-status">
					<?php esc_html_e( 'Add New Status', 'backorder-flow' ); ?>
				</button>
				<button type="button" class="button wc-cbs-export-statuses">
					<?php esc_html_e( 'Export Statuses', 'backorder-flow' ); ?>
				</button>
				<button type="button" class="button wc-cbs-import-statuses-btn">
					<?php esc_html_e( 'Import Statuses', 'backorder-flow' ); ?>
				</button>
				<input type="file" id="wc-cbs-import-file" accept=".json" style="display: none;">
			</div>

			<div class="wc-cbs-statuses-list">
				<?php if ( ! empty( $statuses ) ) : ?>
					<table class="wp-list-table widefat fixed striped wc-cbs-statuses-table">
						<thead>
							<tr>
								<th class="wc-cbs-col-sort"><?php esc_html_e( 'Order', 'backorder-flow' ); ?></th>
								<th class="wc-cbs-col-name"><?php esc_html_e( 'Status Name', 'backorder-flow' ); ?></th>
								<th class="wc-cbs-col-description"><?php esc_html_e( 'Description', 'backorder-flow' ); ?></th>
								<th class="wc-cbs-col-color"><?php esc_html_e( 'Color', 'backorder-flow' ); ?></th>
								<th class="wc-cbs-col-priority"><?php esc_html_e( 'Priority', 'backorder-flow' ); ?></th>
								<th class="wc-cbs-col-enabled"><?php esc_html_e( 'Enabled', 'backorder-flow' ); ?></th>
								<th class="wc-cbs-col-actions"><?php esc_html_e( 'Actions', 'backorder-flow' ); ?></th>
							</tr>
						</thead>
						<tbody id="wc-cbs-statuses-tbody">
							<?php foreach ( $statuses as $status ) : ?>
								<?php $this->output_status_row( $status ); ?>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="wc-cbs-no-statuses">
						<?php esc_html_e( 'No backorder statuses found. Click "Add New Status" to create one.', 'backorder-flow' ); ?>
					</p>
				<?php endif; ?>
			</div>

			<!-- Status Edit Modal -->
			<div id="wc-cbs-status-modal" class="wc-cbs-modal" style="display: none;">
				<div class="wc-cbs-modal-content">
					<span class="wc-cbs-modal-close">&times;</span>
					<h3 class="wc-cbs-modal-title"><?php esc_html_e( 'Add New Status', 'backorder-flow' ); ?></h3>
					<form id="wc-cbs-status-form">
						<input type="hidden" name="status_id" id="wc-cbs-status-id" value="">

						<p class="form-field" id="wc-cbs-status-id-display" style="display: none;">
							<label><?php esc_html_e( 'Status ID', 'backorder-flow' ); ?></label>
							<input type="text" id="wc-cbs-status-id-value" readonly style="background: #f5f5f5; cursor: text;">
							<span class="description"><?php esc_html_e( 'Use this ID in shortcodes. Click to select and copy.', 'backorder-flow' ); ?></span>
						</p>

						<p class="form-field">
							<label for="wc-cbs-status-name">
								<?php esc_html_e( 'Status Name', 'backorder-flow' ); ?>
								<span class="required">*</span>
							</label>
							<input type="text" id="wc-cbs-status-name" name="status_name" required>
						</p>

						<p class="form-field">
							<label for="wc-cbs-status-description">
								<?php esc_html_e( 'Description', 'backorder-flow' ); ?>
							</label>
							<textarea id="wc-cbs-status-description" name="status_description" rows="3"></textarea>
							<span class="description"><?php esc_html_e( 'This message will be displayed to customers.', 'backorder-flow' ); ?></span>
						</p>

						<p class="form-field">
							<label for="wc-cbs-status-color">
								<?php esc_html_e( 'Color', 'backorder-flow' ); ?>
							</label>
							<input type="text" id="wc-cbs-status-color" name="status_color" class="wc-cbs-color-picker" value="#ff9800">
						</p>

						<p class="form-field">
							<label for="wc-cbs-status-priority">
								<?php esc_html_e( 'Priority', 'backorder-flow' ); ?>
							</label>
							<input type="number" id="wc-cbs-status-priority" name="status_priority" min="1" value="1">
							<span class="description"><?php esc_html_e( 'Lower numbers appear first in grouping.', 'backorder-flow' ); ?></span>
						</p>

						<p class="form-field">
							<label>
								<input type="checkbox" id="wc-cbs-status-enabled" name="status_enabled" checked>
								<?php esc_html_e( 'Enabled', 'backorder-flow' ); ?>
							</label>
						</p>

						<p class="form-field">
							<button type="button" class="button button-primary wc-cbs-save-status-btn">
								<?php esc_html_e( 'Save Status', 'backorder-flow' ); ?>
							</button>
							<button type="button" class="button wc-cbs-modal-cancel">
								<?php esc_html_e( 'Cancel', 'backorder-flow' ); ?>
							</button>
						</p>
					</form>
				</div>
			</div>
		</div>

		<!-- Shortcode Instructions -->
		<div class="wc-cbs-shortcodes-info" style="margin-top: 40px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
			<h3 style="margin-top: 0;"><?php esc_html_e( 'Available Shortcodes', 'backorder-flow' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Use these shortcodes to display backorder information anywhere on your site:', 'backorder-flow' ); ?></p>

			<table class="widefat" style="margin-top: 15px; background: white;">
				<thead>
					<tr>
						<th style="padding: 10px;"><?php esc_html_e( 'Shortcode', 'backorder-flow' ); ?></th>
						<th style="padding: 10px;"><?php esc_html_e( 'Description', 'backorder-flow' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="padding: 10px;"><code>[wmbf_backorder_status]</code></td>
						<td style="padding: 10px;"><?php esc_html_e( 'Display current product\'s backorder status (use on single product pages)', 'backorder-flow' ); ?></td>
					</tr>
					<tr>
						<td style="padding: 10px;"><code>[wmbf_backorder_groups]</code></td>
						<td style="padding: 10px;"><?php esc_html_e( 'Display cart items grouped by backorder status', 'backorder-flow' ); ?></td>
					</tr>
					<tr>
						<td style="padding: 10px;"><code>[wmbf_backorder_products status="<em>status-id</em>"]</code></td>
						<td style="padding: 10px;"><?php esc_html_e( 'Display products with a specific backorder status (replace status-id with actual status ID)', 'backorder-flow' ); ?></td>
					</tr>
					<tr>
						<td style="padding: 10px;"><code>[wmbf_backorder_badge product_id="<em>123</em>"]</code></td>
						<td style="padding: 10px;"><?php esc_html_e( 'Display backorder badge for a specific product (replace 123 with product ID)', 'backorder-flow' ); ?></td>
					</tr>
				</tbody>
			</table>

			<div style="margin-top: 20px;">
				<h4><?php esc_html_e( 'Finding Status IDs', 'backorder-flow' ); ?></h4>
				<p class="description">
					<?php esc_html_e( 'To get a status ID for use in shortcodes, edit the status above and copy its ID from the modal form, or use the browser\'s developer tools to inspect the status row\'s data-status-id attribute.', 'backorder-flow' ); ?>
				</p>
			</div>
		</div>

		<style>
			.wc-cbs-settings-wrap { margin: 20px 0; }
			.wc-cbs-actions { margin: 20px 0; }
			.wc-cbs-actions .button { margin-right: 10px; }
			.wc-cbs-statuses-list { margin-top: 20px; }
			.wc-cbs-statuses-table { margin-top: 20px; }
			.wc-cbs-statuses-table tbody tr { cursor: move; }
			.wc-cbs-col-sort { width: 50px; text-align: center; }
			.wc-cbs-col-color { width: 80px; }
			.wc-cbs-col-priority { width: 80px; }
			.wc-cbs-col-enabled { width: 80px; }
			.wc-cbs-col-actions { width: 120px; }
			.wc-cbs-color-preview { display: inline-block; width: 30px; height: 30px; border-radius: 3px; border: 1px solid #ddd; }
			.wc-cbs-modal { display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
			.wc-cbs-modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 600px; max-width: 90%; border-radius: 5px; }
			.wc-cbs-modal-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
			.wc-cbs-modal-close:hover { color: #000; }
			.wc-cbs-modal-title { margin-top: 0; }
			.wc-cbs-no-statuses { padding: 20px; background: #f9f9f9; border: 1px solid #ddd; }
			.wc-cbs-status-row.ui-sortable-helper { background: #f9f9f9; }
			.wc-cbs-shortcodes-info table code { background: #fff; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
			.wc-cbs-shortcodes-info h4 { margin-bottom: 10px; }
		</style>
		<?php
	}

	/**
	 * Output status row.
	 *
	 * @param array $status Status data.
	 */
	private function output_status_row( $status ) {
		$status_id = isset( $status['id'] ) ? $status['id'] : '';
		$name = isset( $status['name'] ) ? $status['name'] : '';
		$description = isset( $status['description'] ) ? $status['description'] : '';
		$color = isset( $status['color'] ) ? $status['color'] : '#ff9800';
		$priority = isset( $status['priority'] ) ? $status['priority'] : 1;
		$enabled = isset( $status['enabled'] ) ? $status['enabled'] : true;
		?>
		<tr class="wc-cbs-status-row" data-status-id="<?php echo esc_attr( $status_id ); ?>">
			<td class="wc-cbs-col-sort">
				<span class="dashicons dashicons-menu"></span>
			</td>
			<td class="wc-cbs-col-name">
				<strong><?php echo esc_html( $name ); ?></strong>
			</td>
			<td class="wc-cbs-col-description">
				<?php echo esc_html( $description ); ?>
			</td>
			<td class="wc-cbs-col-color">
				<span class="wc-cbs-color-preview" style="background-color: <?php echo esc_attr( $color ); ?>"></span>
			</td>
			<td class="wc-cbs-col-priority">
				<?php echo esc_html( $priority ); ?>
			</td>
			<td class="wc-cbs-col-enabled">
				<?php if ( $enabled ) : ?>
					<span class="dashicons dashicons-yes" style="color: green;"></span>
				<?php else : ?>
					<span class="dashicons dashicons-no" style="color: red;"></span>
				<?php endif; ?>
			</td>
			<td class="wc-cbs-col-actions">
				<button type="button" class="button button-small wc-cbs-edit-status" data-status='<?php echo esc_attr( wp_json_encode( $status ) ); ?>'>
					<?php esc_html_e( 'Edit', 'backorder-flow' ); ?>
				</button>
				<button type="button" class="button button-small wc-cbs-delete-status" data-status-id="<?php echo esc_attr( $status_id ); ?>">
					<?php esc_html_e( 'Delete', 'backorder-flow' ); ?>
				</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save settings.
	 */
	public function save_settings() {
		// Settings are saved via AJAX - no need for save button.
	}

	/**
	 * Hide the WooCommerce save button on our settings tab.
	 */
	public function hide_save_button() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		// Check if we're on WooCommerce settings page
		if ( 'woocommerce_page_wc-settings' !== $screen->id ) {
			return;
		}

		// Check if we're on our tab
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter to determine active tab, not processing form data.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		if ( 'backorder_status' !== $current_tab ) {
			return;
		}

		// Register an empty style handle and add inline style to hide the save button.
		wp_register_style( 'wmbf-admin-settings', false, array(), WMBF_VERSION );
		wp_enqueue_style( 'wmbf-admin-settings' );

		$custom_css = "
			.woocommerce-save-button,
			p.submit {
				display: none !important;
			}
		";

		wp_add_inline_style( 'wmbf-admin-settings', $custom_css );
	}

	/**
	 * Get all backorder statuses.
	 *
	 * @return array
	 */
	public function get_statuses() {
		$statuses = get_option( 'wmbf_statuses', array() );

		// Sort by priority.
		usort( $statuses, function( $a, $b ) {
			$priority_a = isset( $a['priority'] ) ? $a['priority'] : 999;
			$priority_b = isset( $b['priority'] ) ? $b['priority'] : 999;
			return $priority_a - $priority_b;
		});

		return $statuses;
	}

	/**
	 * Get status by ID.
	 *
	 * @param string $status_id Status ID.
	 * @return array|null
	 */
	public function get_status( $status_id ) {
		$statuses = $this->get_statuses();
		foreach ( $statuses as $status ) {
			if ( isset( $status['id'] ) && $status['id'] === $status_id ) {
				return $status;
			}
		}
		return null;
	}

	/**
	 * AJAX add/update status.
	 */
	public function ajax_add_status() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		$status_id = isset( $_POST['status_id'] ) ? sanitize_text_field( wp_unslash( $_POST['status_id'] ) ) : '';
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$color = isset( $_POST['color'] ) ? sanitize_hex_color( wp_unslash( $_POST['color'] ) ) : '#ff9800';
		$priority = isset( $_POST['priority'] ) ? absint( $_POST['priority'] ) : 1;
		$enabled = isset( $_POST['enabled'] ) && 'true' === $_POST['enabled'];

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Status name is required.', 'backorder-flow' ) ) );
		}

		$statuses = $this->get_statuses();
		$new_status = array(
			'id'          => $status_id ? $status_id : uniqid(),
			'name'        => $name,
			'description' => $description,
			'color'       => $color,
			'priority'    => $priority,
			'enabled'     => $enabled,
		);

		// Update existing or add new.
		if ( $status_id ) {
			$found = false;
			foreach ( $statuses as $key => $status ) {
				if ( isset( $status['id'] ) && $status['id'] === $status_id ) {
					$statuses[ $key ] = $new_status;
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$statuses[] = $new_status;
			}
		} else {
			$statuses[] = $new_status;
		}

		update_option( 'wmbf_statuses', $statuses );

		wp_send_json_success( array(
			'message' => __( 'Status saved successfully.', 'backorder-flow' ),
			'status'  => $new_status,
		) );
	}

	/**
	 * AJAX delete status.
	 */
	public function ajax_delete_status() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		$status_id = isset( $_POST['status_id'] ) ? sanitize_text_field( wp_unslash( $_POST['status_id'] ) ) : '';

		if ( empty( $status_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status ID.', 'backorder-flow' ) ) );
		}

		$statuses = $this->get_statuses();
		$statuses = array_filter( $statuses, function( $status ) use ( $status_id ) {
			return ! isset( $status['id'] ) || $status['id'] !== $status_id;
		});

		update_option( 'wmbf_statuses', array_values( $statuses ) );

		wp_send_json_success( array( 'message' => __( 'Status deleted successfully.', 'backorder-flow' ) ) );
	}

	/**
	 * AJAX update status order.
	 */
	public function ajax_update_status_order() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		$order = isset( $_POST['order'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['order'] ) ) : array();

		if ( empty( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order data.', 'backorder-flow' ) ) );
		}

		$statuses = $this->get_statuses();
		$ordered_statuses = array();

		foreach ( $order as $priority => $status_id ) {
			foreach ( $statuses as $status ) {
				if ( isset( $status['id'] ) && $status['id'] === $status_id ) {
					$status['priority'] = $priority + 1;
					$ordered_statuses[] = $status;
					break;
				}
			}
		}

		update_option( 'wmbf_statuses', $ordered_statuses );

		wp_send_json_success( array( 'message' => __( 'Order updated successfully.', 'backorder-flow' ) ) );
	}

	/**
	 * AJAX export statuses.
	 */
	public function ajax_export_statuses() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		$statuses = $this->get_statuses();
		wp_send_json_success( array( 'data' => $statuses ) );
	}

	/**
	 * AJAX import statuses.
	 */
	public function ajax_import_statuses() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data validated and each field sanitized individually below.
		$data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : '';

		if ( empty( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'No data provided.', 'backorder-flow' ) ) );
		}

		$imported_statuses = json_decode( $data, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array( 'message' => __( 'Invalid JSON data.', 'backorder-flow' ) ) );
		}

		// Validate data structure.
		if ( ! is_array( $imported_statuses ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data format.', 'backorder-flow' ) ) );
		}

		// Sanitize each imported status.
		$sanitized_statuses = array();
		foreach ( $imported_statuses as $status ) {
			if ( ! is_array( $status ) ) {
				continue;
			}

			$sanitized_statuses[] = array(
				'id'          => isset( $status['id'] ) ? sanitize_text_field( $status['id'] ) : uniqid(),
				'name'        => isset( $status['name'] ) ? sanitize_text_field( $status['name'] ) : '',
				'description' => isset( $status['description'] ) ? sanitize_textarea_field( $status['description'] ) : '',
				'color'       => isset( $status['color'] ) ? sanitize_hex_color( $status['color'] ) : '#ff9800',
				'priority'    => isset( $status['priority'] ) ? absint( $status['priority'] ) : 1,
				'enabled'     => isset( $status['enabled'] ) ? (bool) $status['enabled'] : true,
			);
		}

		update_option( 'wmbf_statuses', $sanitized_statuses );

		wp_send_json_success( array( 'message' => __( 'Statuses imported successfully.', 'backorder-flow' ) ) );
	}

	/**
	 * AJAX save default message.
	 */
	public function ajax_save_default_message() {
		check_ajax_referer( 'wmbf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'backorder-flow' ) ) );
		}

		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => __( 'Message cannot be empty.', 'backorder-flow' ) ) );
		}

		update_option( 'wmbf_default_message', $message );

		wp_send_json_success( array( 'message' => __( 'Default message saved successfully.', 'backorder-flow' ) ) );
	}
}
