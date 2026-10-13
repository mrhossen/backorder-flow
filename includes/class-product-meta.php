<?php
/**
 * Product Meta
 *
 * Handles product-level backorder status settings.
 *
 * @package Backorder_Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WMBF_Product_Meta class.
 */
class WMBF_Product_Meta {

	/**
	 * The single instance of the class.
	 *
	 * @var WMBF_Product_Meta
	 */
	protected static $_instance = null;

	/**
	 * Main instance.
	 *
	 * @return WMBF_Product_Meta
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
		// Simple products - use inventory options hook which is more reliable.
		add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_backorder_status_field' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_backorder_status_field' ) );

		// Variable products - variations.
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_variation_backorder_status_field' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_backorder_status_field' ), 10, 2 );
		add_filter( 'woocommerce_available_variation', array( $this, 'add_variation_backorder_status_data' ), 10, 3 );

		// Quick edit.
		add_action( 'woocommerce_product_quick_edit_end', array( $this, 'add_quick_edit_field' ) );
		add_action( 'woocommerce_product_quick_edit_save', array( $this, 'save_quick_edit_field' ) );

		// Bulk edit.
		add_action( 'woocommerce_product_bulk_edit_end', array( $this, 'add_bulk_edit_field' ) );
		add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'save_bulk_edit_field' ) );

		// Admin column.
		add_filter( 'manage_edit-product_columns', array( $this, 'add_admin_column' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'display_admin_column' ), 10, 2 );

		// Enqueue admin scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Enqueue admin scripts for product edit page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on product edit pages.
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		// Enqueue jQuery as dependency.
		wp_enqueue_script( 'jquery' );

		// Add inline script for backorder status field toggle.
		$inline_script = "
		jQuery(function($) {
			function getCheckedOrSelected(name) {
				var \$radio = $('input[name=\"' + name + '\"]:checked');
				if (\$radio.length) {
					return \$radio.val();
				}
				var \$select = $('select[name=\"' + name + '\"]');
				return \$select.length ? \$select.val() : null;
			}

			function toggleBackorderStatusField() {
				var \$field = $('.wc-cbs-backorder-status-field');
				if (!\$field.length) {
					return;
				}

				var productType = $('#product-type').val();
				var manageStock = $('#_manage_stock').is(':checked');
				var backorders = getCheckedOrSelected('_backorders');
				var stockStatus = getCheckedOrSelected('_stock_status');

				// Show the field when the product can be ordered on backorder:
				// - variable products (variations can allow backorders and use the parent's status),
				// - stock managed with backorders allowed,
				// - stock not managed with the stock status set to 'On backorder'.
				var shouldShow = 'variable' === productType ||
					(manageStock && ('yes' === backorders || 'notify' === backorders)) ||
					(!manageStock && 'onbackorder' === stockStatus);

				\$field.toggle(shouldShow);
				\$field.find('select').prop('disabled', !shouldShow);
			}

			toggleBackorderStatusField();

			$(document).on(
				'change',
				'input[name=\"_backorders\"], select[name=\"_backorders\"], #_manage_stock, input[name=\"_stock_status\"], select[name=\"_stock_status\"], #product-type',
				function() {
					// Run after WooCommerce has shown/hidden its own fields.
					setTimeout(toggleBackorderStatusField, 50);
				}
			);

			$('.inventory_options').on('click', function() {
				setTimeout(toggleBackorderStatusField, 50);
			});
		});
		";

		wp_add_inline_script( 'jquery', $inline_script );
	}

	/**
	 * Get all backorder statuses.
	 *
	 * @return array
	 */
	private function get_statuses() {
		return WMBF_Admin_Settings::instance()->get_statuses();
	}

	/**
	 * Add backorder status field to product data panel.
	 */
	public function add_backorder_status_field() {
		global $post;

		$statuses = $this->get_statuses();
		$options = array( '' => __( 'Default backorder message', 'backorder-flow' ) );

		foreach ( $statuses as $status ) {
			if ( isset( $status['enabled'] ) && $status['enabled'] ) {
				$options[ $status['id'] ] = $status['name'];
			}
		}

		echo '<div class="wc-cbs-backorder-status-field options_group show_if_simple show_if_variable" style="display: block;">';

		woocommerce_wp_select(
			array(
				'id'          => '_wmbf_backorder_status',
				'label'       => __( 'Backorder Status', 'backorder-flow' ),
				'description' => __( 'Select a custom backorder status for this product. Only displayed when the product is on backorder. Variations without their own status use this one.', 'backorder-flow' ),
				'desc_tip'    => true,
				'options'     => $options,
				'value'       => get_post_meta( $post->ID, '_wmbf_backorder_status', true ),
			)
		);

		echo '</div>';
	}

	/**
	 * Save backorder status field.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_backorder_status_field( $post_id ) {
		// Check user capabilities.
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		// Verify nonce - WooCommerce uses 'woocommerce_meta_nonce' for product data.
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		if ( isset( $_POST['_wmbf_backorder_status'] ) ) {
			$status = sanitize_text_field( wp_unslash( $_POST['_wmbf_backorder_status'] ) );
			update_post_meta( $post_id, '_wmbf_backorder_status', $status );
		}
	}

	/**
	 * Add backorder status field to variation.
	 *
	 * @param int     $loop           Variation loop index.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Variation post object.
	 */
	public function add_variation_backorder_status_field( $loop, $variation_data, $variation ) {
		$statuses = $this->get_statuses();
		$options = array( '' => __( 'Same as parent product', 'backorder-flow' ) );

		foreach ( $statuses as $status ) {
			if ( isset( $status['enabled'] ) && $status['enabled'] ) {
				$options[ $status['id'] ] = $status['name'];
			}
		}

		$current_value = get_post_meta( $variation->ID, '_wmbf_backorder_status', true );
		?>
		<p class="form-row form-row-full">
			<label><?php esc_html_e( 'Backorder Status', 'backorder-flow' ); ?></label>
			<select name="_wmbf_backorder_status[<?php echo esc_attr( $loop ); ?>]" class="wc-cbs-variation-backorder-status">
				<?php foreach ( $options as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_value, $key ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Save variation backorder status field.
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $i            Variation loop index.
	 */
	public function save_variation_backorder_status_field( $variation_id, $i ) {
		// Check user capabilities.
		if ( ! current_user_can( 'edit_product', $variation_id ) ) {
			return;
		}

		// Verify nonce. Variations are saved over AJAX ('save-variations' nonce in 'security'),
		// or with the product form when JavaScript is unavailable ('woocommerce_meta_nonce').
		$ajax_nonce = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
		$form_nonce = isset( $_POST['woocommerce_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $ajax_nonce, 'save-variations' ) && ! wp_verify_nonce( $form_nonce, 'woocommerce_save_data' ) ) {
			return;
		}

		if ( isset( $_POST['_wmbf_backorder_status'][ $i ] ) ) {
			$status = sanitize_text_field( wp_unslash( $_POST['_wmbf_backorder_status'][ $i ] ) );
			update_post_meta( $variation_id, '_wmbf_backorder_status', $status );
		}
	}

	/**
	 * Add quick edit field.
	 */
	public function add_quick_edit_field() {
		$statuses = $this->get_statuses();
		?>
		<div class="inline-edit-group">
			<label class="alignleft">
				<span class="title"><?php esc_html_e( 'Backorder Status', 'backorder-flow' ); ?></span>
				<span class="input-text-wrap">
					<select name="_wmbf_backorder_status" class="wc-cbs-quick-edit-backorder-status">
						<option value=""><?php esc_html_e( '— No Change —', 'backorder-flow' ); ?></option>
						<option value="_default"><?php esc_html_e( 'Default backorder message', 'backorder-flow' ); ?></option>
						<?php foreach ( $statuses as $status ) : ?>
							<?php if ( isset( $status['enabled'] ) && $status['enabled'] ) : ?>
								<option value="<?php echo esc_attr( $status['id'] ); ?>">
									<?php echo esc_html( $status['name'] ); ?>
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
				</span>
			</label>
		</div>
		<?php
	}

	/**
	 * Save quick edit field.
	 *
	 * @param WC_Product $product Product object.
	 */
	public function save_quick_edit_field( $product ) {
		// Check user capabilities.
		if ( ! current_user_can( 'edit_product', $product->get_id() ) ) {
			return;
		}

		// Verify nonce. WooCommerce adds 'woocommerce_quick_edit_nonce' to both the quick edit and bulk edit forms.
		if ( ! isset( $_REQUEST['woocommerce_quick_edit_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['woocommerce_quick_edit_nonce'] ) ), 'woocommerce_quick_edit_nonce' ) ) {
			return;
		}

		if ( isset( $_REQUEST['_wmbf_backorder_status'] ) && '' !== $_REQUEST['_wmbf_backorder_status'] ) {
			$status = sanitize_text_field( wp_unslash( $_REQUEST['_wmbf_backorder_status'] ) );
			if ( '_default' === $status ) {
				delete_post_meta( $product->get_id(), '_wmbf_backorder_status' );
			} else {
				update_post_meta( $product->get_id(), '_wmbf_backorder_status', $status );
			}
		}
	}

	/**
	 * Add bulk edit field.
	 */
	public function add_bulk_edit_field() {
		$statuses = $this->get_statuses();
		?>
		<div class="inline-edit-group">
			<label class="alignleft">
				<span class="title"><?php esc_html_e( 'Backorder Status', 'backorder-flow' ); ?></span>
				<span class="input-text-wrap">
					<select name="_wmbf_backorder_status" class="wc-cbs-bulk-edit-backorder-status">
						<option value=""><?php esc_html_e( '— No Change —', 'backorder-flow' ); ?></option>
						<option value="_default"><?php esc_html_e( 'Default backorder message', 'backorder-flow' ); ?></option>
						<?php foreach ( $statuses as $status ) : ?>
							<?php if ( isset( $status['enabled'] ) && $status['enabled'] ) : ?>
								<option value="<?php echo esc_attr( $status['id'] ); ?>">
									<?php echo esc_html( $status['name'] ); ?>
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
				</span>
			</label>
		</div>
		<?php
	}

	/**
	 * Save bulk edit field.
	 *
	 * @param WC_Product $product Product object.
	 */
	public function save_bulk_edit_field( $product ) {
		$this->save_quick_edit_field( $product );
	}

	/**
	 * Add admin column.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_admin_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $column;
			if ( 'is_in_stock' === $key ) {
				$new_columns['backorder_status'] = __( 'Backorder Status', 'backorder-flow' );
			}
		}
		return $new_columns;
	}

	/**
	 * Display admin column.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function display_admin_column( $column, $post_id ) {
		if ( 'backorder_status' === $column ) {
			$status_id = get_post_meta( $post_id, '_wmbf_backorder_status', true );
			if ( $status_id ) {
				$status = WMBF_Admin_Settings::instance()->get_status( $status_id );
				if ( $status ) {
					echo '<span style="color: ' . esc_attr( $status['color'] ) . ';">' . esc_html( $status['name'] ) . '</span>';
				} else {
					echo '—';
				}
			} else {
				echo '—';
			}
		}
	}

	/**
	 * Get product backorder status.
	 *
	 * @param int|WC_Product $product Product ID or object.
	 * @return array|null
	 */
	public static function get_product_backorder_status( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product ) {
			return null;
		}

		$status_id = $product->get_meta( '_wmbf_backorder_status', true );

		// Variations without their own status use the parent product's status.
		if ( ! $status_id && $product->is_type( 'variation' ) ) {
			$parent = wc_get_product( $product->get_parent_id() );
			if ( $parent ) {
				$status_id = $parent->get_meta( '_wmbf_backorder_status', true );
			}
		}

		if ( ! $status_id ) {
			return null;
		}

		$status = WMBF_Admin_Settings::instance()->get_status( $status_id );

		// Disabled statuses are not shown anywhere.
		if ( ! $status || empty( $status['enabled'] ) ) {
			return null;
		}

		return $status;
	}

	/**
	 * Add backorder status data to available variation.
	 *
	 * @param array                $variation_data Variation data.
	 * @param WC_Product           $product        Product object.
	 * @param WC_Product_Variation $variation      Variation object.
	 * @return array
	 */
	public function add_variation_backorder_status_data( $variation_data, $product, $variation ) {
		// Get the backorder status for this variation
		$status = self::get_product_backorder_status( $variation );

		if ( ! $status ) {
			return $variation_data;
		}

		// Check if variation allows backorders
		if ( ! WMBF_Frontend_Display::accepts_backorders( $variation ) ) {
			return $variation_data;
		}

		// Add backorder status data with stock information.
		// JavaScript will use this to determine when to show the message.
		$variation_data['wmbf_backorder_status'] = array(
			'name'               => $status['name'],
			'description'        => isset( $status['description'] ) ? $status['description'] : '',
			'color'              => isset( $status['color'] ) ? $status['color'] : '#ff9800',
			'stock_quantity'     => $variation->get_stock_quantity(),
			'managing_stock'     => $variation->managing_stock(),
			'is_in_stock'        => $variation->is_in_stock(),
			'backorders_allowed' => true,
		);

		return $variation_data;
	}
}
