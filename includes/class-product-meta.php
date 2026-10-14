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
	 * Enqueue admin scripts for the product edit screen and the products list.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script( 'jquery' );

		// Status color dots for every Backorder Flow select (product, variations, quick and bulk edit).
		$inline_script = "
		jQuery(function($) {
			function syncDot(select) {
				var \$select = $(select);
				var \$option = \$select.find('option:selected');
				var color = \$option.data('color') || '#b9b2c7';
				\$select.closest('.wmbf-select').find('.wmbf-sel-dot').css('background', color);
				return \$option;
			}

			$(document).on('change', '.wmbf-select select', function() {
				syncDot(this);
			});
			$('.wmbf-select select').each(function() {
				syncDot(this);
			});
			$(document.body).on('woocommerce_variations_loaded woocommerce_variations_added', function() {
				$('.woocommerce_variations .wmbf-select select').each(function() {
					syncDot(this);
				});
			});
		});
		";

		if ( 'edit.php' !== $hook ) {
			$inline_script .= "
			jQuery(function($) {
				var \$box = $('.wmbf-inline');

				function getCheckedOrSelected(name) {
					var \$radio = $('input[name=\"' + name + '\"]:checked');
					if (\$radio.length) {
						return \$radio.val();
					}
					var \$select = $('select[name=\"' + name + '\"]');
					return \$select.length ? \$select.val() : null;
				}

				// The customer preview and the hint under the select.
				function updatePreview() {
					var \$option = \$box.find('select option:selected');
					if (!\$option.length) {
						return;
					}
					var \$badge = \$box.find('.wmbf-fe-badge');
					\$badge.css('--c', \$option.data('color') || '#8b8e94');
					\$badge.find('strong').text(\$option.data('name') || \$option.text());
					\$badge.find('span').text(\$option.data('msg') || '').prop('hidden', !\$option.data('msg'));
				}

				function updateState() {
					var \$state = \$box.find('.wmbf-inline-state');
					var productType = $('#product-type').val();
					var manageStock = $('#_manage_stock').is(':checked');
					var stock = parseFloat($('#_stock').val());
					var stockStatus = getCheckedOrSelected('_stock_status');
					var key = 'later';

					if ('variable' === productType) {
						key = 'variable';
					} else if ((manageStock && !isNaN(stock) && stock <= 0) || (!manageStock && 'onbackorder' === stockStatus)) {
						key = 'live';
					}
					\$state.attr('data-state', key).find('span').text(\$state.data(key));
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
					updateState();
				}

				toggleBackorderStatusField();
				updatePreview();

				\$box.on('change', 'select', updatePreview);

				$(document).on(
					'change',
					'input[name=\"_backorders\"], select[name=\"_backorders\"], #_manage_stock, input[name=\"_stock_status\"], select[name=\"_stock_status\"], #product-type',
					function() {
						// Run after WooCommerce has shown/hidden its own fields.
						setTimeout(toggleBackorderStatusField, 50);
					}
				);
				$(document).on('input change', '#_stock', updateState);

				$('.inventory_options').on('click', function() {
					setTimeout(toggleBackorderStatusField, 50);
				});
			});
			";
		}

		wp_add_inline_script( 'jquery', $inline_script );
	}

	/**
	 * Print the options of a status select, with each status color and message as data attributes.
	 *
	 * @param array       $first         Value => label of the first option(s), which have no status.
	 * @param string      $selected      Selected value.
	 * @param string|null $default_value The first option that means "default backorder message", if any.
	 */
	private function print_status_options( $first, $selected = '', $default_value = null ) {
		$default_message = WMBF_Frontend_Display::get_default_message();
		$default_name    = apply_filters( 'wmbf_default_backorder_label', __( 'On Backorder', 'backorder-flow' ) );

		foreach ( $first as $value => $label ) {
			$is_default = null !== $default_value && (string) $default_value === (string) $value;
			printf(
				'<option value="%1$s" data-color="%2$s" data-name="%3$s" data-msg="%4$s"%5$s>%6$s</option>',
				esc_attr( $value ),
				esc_attr( $is_default ? '#8b8e94' : '' ),
				esc_attr( $is_default ? $default_name : '' ),
				esc_attr( $is_default ? $default_message : '' ),
				selected( $selected, $value, false ),
				esc_html( $label )
			);
		}

		foreach ( $this->get_statuses() as $status ) {
			if ( empty( $status['enabled'] ) ) {
				continue;
			}
			printf(
				'<option value="%1$s" data-color="%2$s" data-name="%3$s" data-msg="%4$s"%5$s>%3$s</option>',
				esc_attr( $status['id'] ),
				esc_attr( isset( $status['color'] ) ? $status['color'] : '' ),
				esc_attr( $status['name'] ),
				esc_attr( isset( $status['description'] ) ? $status['description'] : '' ),
				selected( $selected, $status['id'], false )
			);
		}
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

		$value = get_post_meta( $post->ID, '_wmbf_backorder_status', true );
		?>
		<div class="wc-cbs-backorder-status-field options_group show_if_simple show_if_variable" style="display: block;">
			<div class="wmbf-ui wmbf-inline">
				<div class="wmbf-inline-h">
					<img src="<?php echo esc_url( WMBF_Admin_Settings::icon_url() ); ?>" alt="" width="20" height="20">
					<?php esc_html_e( 'Backorder Flow', 'backorder-flow' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=backorder_status' ) ); ?>" target="_blank"><?php esc_html_e( 'Manage statuses', 'backorder-flow' ); ?><?php WMBF_Admin_Settings::icon( 'ext' ); ?></a>
				</div>
				<div class="wmbf-inline-b">
					<div class="wmbf-field">
						<label class="wmbf-lbl" for="_wmbf_backorder_status"><?php esc_html_e( 'Backorder status', 'backorder-flow' ); ?></label>
						<div class="wmbf-select has-dot">
							<i class="wmbf-sel-dot"></i>
							<select id="_wmbf_backorder_status" name="_wmbf_backorder_status">
								<?php $this->print_status_options( array( '' => __( 'Default backorder message', 'backorder-flow' ) ), $value, '' ); ?>
							</select>
						</div>
						<p class="wmbf-help"><?php esc_html_e( 'Shown when this product is on backorder. Variations without their own status use this one.', 'backorder-flow' ); ?></p>
						<div class="wmbf-inline-state"
							data-live="<?php esc_attr_e( 'Live: this product is on backorder, so customers see it now.', 'backorder-flow' ); ?>"
							data-later="<?php esc_attr_e( 'Customers see this once a shopper orders more than you have in stock.', 'backorder-flow' ); ?>"
							data-variable="<?php esc_attr_e( 'Variations that allow backorders use this unless they have their own status.', 'backorder-flow' ); ?>">
							<?php WMBF_Admin_Settings::icon( 'check' ); ?><span></span>
						</div>
					</div>
					<div class="wmbf-preview">
						<div class="wmbf-preview-cap"><span><?php esc_html_e( 'What customers see', 'backorder-flow' ); ?></span></div>
						<div class="wmbf-fe-badge"><i></i><div><strong></strong><span></span></div></div>
					</div>
				</div>
			</div>
		</div>
		<?php
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
		$current_value = get_post_meta( $variation->ID, '_wmbf_backorder_status', true );
		?>
		<p class="form-row form-row-full wmbf-ui wmbf-variation-field">
			<label for="wmbf-variation-status-<?php echo esc_attr( $loop ); ?>">
				<img src="<?php echo esc_url( WMBF_Admin_Settings::icon_url() ); ?>" alt="" width="14" height="14">
				<?php esc_html_e( 'Backorder status', 'backorder-flow' ); ?>
			</label>
			<span class="wmbf-select has-dot">
				<i class="wmbf-sel-dot"></i>
				<select id="wmbf-variation-status-<?php echo esc_attr( $loop ); ?>" name="_wmbf_backorder_status[<?php echo esc_attr( $loop ); ?>]" class="wc-cbs-variation-backorder-status">
					<?php $this->print_status_options( array( '' => __( 'Same as parent product', 'backorder-flow' ) ), $current_value ); ?>
				</select>
			</span>
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
		$this->render_inline_edit_field( 'wc-cbs-quick-edit-backorder-status' );
	}

	/**
	 * Print the Backorder status select used by quick edit and bulk edit.
	 *
	 * @param string $class Select class.
	 */
	private function render_inline_edit_field( $class ) {
		?>
		<div class="inline-edit-group wmbf-ui wmbf-inline-edit">
			<label class="alignleft">
				<span class="title"><?php esc_html_e( 'Backorder', 'backorder-flow' ); ?></span>
				<span class="input-text-wrap">
					<span class="wmbf-select has-dot">
						<i class="wmbf-sel-dot"></i>
						<select name="_wmbf_backorder_status" class="<?php echo esc_attr( $class ); ?>">
							<?php
							$this->print_status_options(
								array(
									''         => __( '— No change —', 'backorder-flow' ),
									'_default' => __( 'Default backorder message', 'backorder-flow' ),
								),
								'',
								'_default'
							);
							?>
						</select>
					</span>
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
		$this->render_inline_edit_field( 'wc-cbs-bulk-edit-backorder-status' );
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
		if ( 'backorder_status' !== $column ) {
			return;
		}

		$status_id = get_post_meta( $post_id, '_wmbf_backorder_status', true );
		$status    = $status_id ? WMBF_Admin_Settings::instance()->get_status( $status_id ) : null;

		if ( $status ) {
			$hidden = empty( $status['enabled'] );
			printf(
				'<span class="wmbf-pill%1$s" style="--c:%2$s"%3$s><i></i>%4$s</span>',
				$hidden ? ' is-muted' : '',
				esc_attr( isset( $status['color'] ) ? $status['color'] : '#8b8e94' ),
				$hidden ? ' title="' . esc_attr__( 'This status is turned off, so customers see the default message.', 'backorder-flow' ) . '"' : '',
				esc_html( $status['name'] )
			);
			return;
		}

		$product = wc_get_product( $post_id );
		if ( $product && WMBF_Frontend_Display::accepts_backorders( $product ) ) {
			echo '<span class="wmbf-pill is-muted"><i></i>' . esc_html__( 'Default message', 'backorder-flow' ) . '</span>';
			return;
		}

		echo '<span aria-hidden="true">—</span>';
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
		// Only variations that can be ordered on backorder show a status.
		if ( ! WMBF_Frontend_Display::accepts_backorders( $variation ) ) {
			return $variation_data;
		}

		// Variations without a status (of their own or the parent's) show the default message.
		$status = self::get_product_backorder_status( $variation );
		if ( ! $status ) {
			$status = WMBF_Frontend_Display::get_default_status();
		}

		// Add backorder status data with stock information.
		// JavaScript will use this to determine when to show the message.
		$variation_data['wmbf_backorder_status'] = array(
			'name'               => $status['name'],
			'description'        => isset( $status['description'] ) ? $status['description'] : '',
			'color'              => isset( $status['color'] ) ? $status['color'] : '#8b8e94',
			'stock_quantity'     => $variation->get_stock_quantity(),
			'managing_stock'     => $variation->managing_stock(),
			'is_in_stock'        => $variation->is_in_stock(),
			'backorders_allowed' => true,
		);

		return $variation_data;
	}
}
