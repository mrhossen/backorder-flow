<?php
/**
 * Order Meta
 *
 * Handles saving and displaying backorder information in orders.
 *
 * @package Backorder_Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WMBF_Order_Meta class.
 */
class WMBF_Order_Meta {

	/**
	 * The single instance of the class.
	 *
	 * @var WMBF_Order_Meta
	 */
	protected static $_instance = null;

	/**
	 * Main instance.
	 *
	 * @return WMBF_Order_Meta
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
		// Save backorder information when order is created.
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_backorder_item_meta' ), 10, 4 );

		// Display backorder info in admin order details.
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'display_admin_order_backorder_info' ) );

		// Display backorder info on customer order details page.
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_customer_order_backorder_info' ) );

		// Add backorder status column to admin orders list.
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_column' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'display_order_column' ), 10, 2 );

		// HPOS compatibility.
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_column' ), 20 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'display_order_column_hpos' ), 10, 2 );
	}

	/**
	 * Save backorder information to order item meta.
	 *
	 * @param WC_Order_Item_Product $item          Order item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $cart_item     Cart item data.
	 * @param WC_Order              $order         Order object.
	 */
	public function save_backorder_item_meta( $item, $cart_item_key, $cart_item, $order ) {
		$product = $cart_item['data'];

		if ( ! $product ) {
			return;
		}

		$quantity = $cart_item['quantity'];

		// Calculate in-stock and backorder quantities.
		$instock_qty = WMBF_Frontend_Display::get_instock_quantity( $product, $quantity );
		$backorder_qty = WMBF_Frontend_Display::get_backorder_quantity( $product, $quantity );

		// Save quantities as meta.
		if ( $instock_qty > 0 ) {
			$item->add_meta_data( '_wmbf_instock_quantity', $instock_qty, true );
		}

		if ( $backorder_qty > 0 ) {
			$item->add_meta_data( '_wmbf_backorder_quantity', $backorder_qty, true );

			// Save backorder status.
			$status = WMBF_Product_Meta::get_product_backorder_status( $product );
			if ( $status ) {
				$item->add_meta_data( '_wmbf_backorder_status_id', $status['id'], true );
				$item->add_meta_data( '_wmbf_backorder_status_name', $status['name'], true );
			}
		}
	}

	/**
	 * Display backorder information in admin order details.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function display_admin_order_backorder_info( $order ) {
		$groups = self::get_order_groups( $order );

		if ( ! self::groups_have_backorder( $groups ) ) {
			return;
		}

		?>
		<div class="wc-cbs-order-backorder-info" style="margin-top: 20px;">
			<h3><?php esc_html_e( 'Backorder Shipping Groups', 'backorder-flow' ); ?></h3>
			<?php foreach ( $groups as $group ) : ?>
				<div class="wc-cbs-order-group" style="margin-bottom: 15px; padding: 10px; border-left: 4px solid <?php echo esc_attr( $group['color'] ); ?>; background: #f9f9f9;">
					<h4 style="margin: 0 0 10px; color: <?php echo esc_attr( $group['color'] ); ?>;">
						<?php echo esc_html( $group['name'] ); ?>
					</h4>
					<ul style="margin: 0; padding-left: 20px;">
						<?php foreach ( $group['items'] as $item_data ) : ?>
							<li>
								<?php echo esc_html( $item_data['product']->get_name() ); ?>
								<strong>× <?php echo (int) $item_data['quantity']; ?></strong>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Display backorder information on customer order details page.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function display_customer_order_backorder_info( $order ) {
		$groups = self::get_order_groups( $order );

		if ( ! self::groups_have_backorder( $groups ) ) {
			return;
		}

		$template_path = WMBF()->locate_template( 'order-details-backorder.php' );

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			?>
			<section class="wc-cbs-order-backorder-groups">
				<h2><?php esc_html_e( 'Shipping Information', 'backorder-flow' ); ?></h2>
				<p><?php esc_html_e( 'Your order contains items that will ship at different times:', 'backorder-flow' ); ?></p>

				<?php foreach ( $groups as $group ) : ?>
					<div class="wc-cbs-order-group" style="margin-bottom: 20px; padding: 15px; border-left: 4px solid <?php echo esc_attr( $group['color'] ); ?>; background: #f9f9f9;">
						<h3 style="margin: 0 0 10px; color: <?php echo esc_attr( $group['color'] ); ?>;">
							<?php echo esc_html( $group['name'] ); ?>
						</h3>
						<ul>
							<?php foreach ( $group['items'] as $item_data ) : ?>
								<li>
									<?php echo esc_html( $item_data['product']->get_name() ); ?> × <?php echo (int) $item_data['quantity']; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</section>
			<?php
		}
	}

	/**
	 * Add backorder status column to orders list.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_order_column( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $column;
			if ( 'order_status' === $key ) {
				$new_columns['backorder_status'] = __( 'Backorder', 'backorder-flow' );
			}
		}

		return $new_columns;
	}

	/**
	 * Display backorder status column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function display_order_column( $column, $post_id ) {
		if ( 'backorder_status' === $column ) {
			$order = wc_get_order( $post_id );
			$this->render_order_column_content( $order );
		}
	}

	/**
	 * Display backorder status column content (HPOS).
	 *
	 * @param string   $column Column name.
	 * @param WC_Order $order  Order object.
	 */
	public function display_order_column_hpos( $column, $order ) {
		if ( 'backorder_status' === $column ) {
			$this->render_order_column_content( $order );
		}
	}

	/**
	 * Render order column content.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function render_order_column_content( $order ) {
		if ( ! $order ) {
			echo '—';
			return;
		}

		$has_backorder = false;
		foreach ( $order->get_items() as $item ) {
			$backorder_qty = $item->get_meta( '_wmbf_backorder_quantity', true );
			if ( $backorder_qty ) {
				$has_backorder = true;
				break;
			}
		}

		if ( $has_backorder ) {
			echo '<span class="dashicons dashicons-clock" style="color: #ff9800;" title="' . esc_attr__( 'Contains backorder items', 'backorder-flow' ) . '"></span>';
		} else {
			echo '—';
		}
	}

	/**
	 * Get order items grouped by backorder status.
	 *
	 * Uses the status saved on each line item at checkout, so later changes to a product's
	 * status (or to the status list) do not change what the customer was told.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	public static function get_order_groups( $order ) {
		$groups = array();

		if ( ! $order ) {
			return $groups;
		}

		// Create "Ships Now" group.
		$groups['ships_now'] = array(
			'id'       => 'ships_now',
			'name'     => apply_filters( 'wmbf_ships_now_label', __( 'Ships Now', 'backorder-flow' ) ),
			'color'    => apply_filters( 'wmbf_ships_now_color', '#4caf50' ),
			'priority' => 0,
			'items'    => array(),
		);

		// Process order items.
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			$quantity      = (int) $item->get_quantity();
			$backorder_qty = (int) $item->get_meta( '_wmbf_backorder_quantity', true );
			$instock_qty   = (int) $item->get_meta( '_wmbf_instock_quantity', true );

			// If no meta saved, assume all in stock.
			if ( ! $backorder_qty && ! $instock_qty ) {
				$groups['ships_now']['items'][] = array(
					'item'     => $item,
					'product'  => $product,
					'quantity' => $quantity,
				);
				continue;
			}

			// Add to ships now group.
			if ( $instock_qty > 0 ) {
				$groups['ships_now']['items'][] = array(
					'item'     => $item,
					'product'  => $product,
					'quantity' => $instock_qty,
				);
			}

			// Add to backorder group.
			if ( $backorder_qty > 0 ) {
				$status_id   = (string) $item->get_meta( '_wmbf_backorder_status_id', true );
				$status_name = (string) $item->get_meta( '_wmbf_backorder_status_name', true );
				$group_id    = $status_id ? $status_id : 'default_backorder';

				if ( ! isset( $groups[ $group_id ] ) ) {
					$groups[ $group_id ] = self::build_order_group( $group_id, $status_name );
				}

				$groups[ $group_id ]['items'][] = array(
					'item'     => $item,
					'product'  => $product,
					'quantity' => $backorder_qty,
				);
			}
		}

		// Remove empty groups.
		$groups = array_filter(
			$groups,
			function( $group ) {
				return ! empty( $group['items'] );
			}
		);

		// Sort groups by priority.
		uasort(
			$groups,
			function( $a, $b ) {
				return $a['priority'] <=> $b['priority'];
			}
		);

		return apply_filters( 'wmbf_order_groups', $groups, $order );
	}

	/**
	 * Build a backorder group for an order.
	 *
	 * @param string $group_id    Status ID, or 'default_backorder'.
	 * @param string $stored_name Status name saved on the order item.
	 * @return array
	 */
	private static function build_order_group( $group_id, $stored_name ) {
		if ( 'default_backorder' === $group_id ) {
			return array(
				'id'       => 'default_backorder',
				'name'     => apply_filters( 'wmbf_default_backorder_label', __( 'On Backorder', 'backorder-flow' ) ),
				'color'    => apply_filters( 'wmbf_default_backorder_color', '#9e9e9e' ),
				'priority' => PHP_INT_MAX,
				'items'    => array(),
			);
		}

		$status = WMBF_Admin_Settings::instance()->get_status( $group_id );

		return array(
			'id'       => $group_id,
			'name'     => $stored_name ? $stored_name : ( $status ? $status['name'] : __( 'On Backorder', 'backorder-flow' ) ),
			'color'    => ( $status && ! empty( $status['color'] ) ) ? $status['color'] : '#ff9800',
			'priority' => ( $status && isset( $status['priority'] ) ) ? (int) $status['priority'] : 999,
			'items'    => array(),
		);
	}

	/**
	 * Check if order groups contain backordered items.
	 *
	 * @param array $groups Order groups.
	 * @return bool
	 */
	public static function groups_have_backorder( $groups ) {
		foreach ( $groups as $group ) {
			if ( 'ships_now' !== $group['id'] && ! empty( $group['items'] ) ) {
				return true;
			}
		}

		return false;
	}
}
