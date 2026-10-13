<?php
/**
 * Email Handler
 *
 * Handles backorder information in order emails.
 *
 * @package Custom_Backorder_Pro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WMCBP_Email_Handler class.
 */
class WMCBP_Email_Handler {

	/**
	 * The single instance of the class.
	 *
	 * @var WMCBP_Email_Handler
	 */
	protected static $_instance = null;

	/**
	 * Main instance.
	 *
	 * @return WMCBP_Email_Handler
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
		add_action( 'woocommerce_email_order_details', array( $this, 'add_backorder_info_to_email' ), 20, 4 );
		add_action( 'woocommerce_order_item_meta_end', array( $this, 'add_backorder_status_to_item' ), 10, 3 );
	}

	/**
	 * Add backorder information to order emails.
	 *
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether email is sent to admin.
	 * @param bool     $plain_text    Whether email is plain text.
	 * @param WC_Email $email         Email object.
	 */
	public function add_backorder_info_to_email( $order, $sent_to_admin, $plain_text, $email ) {
		$groups = $this->get_order_groups( $order );

		if ( empty( $groups ) || count( $groups ) <= 1 ) {
			return;
		}

		$template_path = WMCBP_TEMPLATES_DIR . 'email-backorder-info.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			$this->render_inline_email_info( $groups, $plain_text );
		}
	}

	/**
	 * Add backorder status to order item meta.
	 *
	 * @param int      $item_id Item ID.
	 * @param WC_Order_Item $item    Order item object.
	 * @param WC_Order $order   Order object.
	 */
	public function add_backorder_status_to_item( $item_id, $item, $order ) {
		$product = $item->get_product();

		if ( ! $product ) {
			return;
		}

		$status = WMCBP_Product_Meta::get_product_backorder_status( $product );

		if ( ! $status ) {
			return;
		}

		// Check if item was on backorder when ordered.
		$backorder_qty = $item->get_meta( '_wmcbp_backorder_quantity', true );

		if ( $backorder_qty ) {
			echo '<div class="wc-cbs-item-backorder-status" style="font-size: 12px; color: ' . esc_attr( $status['color'] ) . ';">';
			echo '<strong>' . esc_html( $status['name'] ) . '</strong>';
			if ( $backorder_qty < $item->get_quantity() ) {
				/* translators: %d: backorder quantity */
				echo ' - ' . sprintf( esc_html__( '%d item(s)', 'custom-backorder-pro' ), (int) $backorder_qty );
			}
			echo '</div>';
		}
	}

	/**
	 * Get order items grouped by backorder status.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private function get_order_groups( $order ) {
		$groups = array();
		$statuses = WMCBP_Admin_Settings::instance()->get_statuses();

		// Create "Ships Now" group.
		$groups['ships_now'] = array(
			'id'    => 'ships_now',
			'name'  => __( 'Ships Now', 'custom-backorder-pro' ),
			'color' => '#4caf50',
			'items' => array(),
		);

		// Create groups for each backorder status.
		foreach ( $statuses as $status ) {
			if ( isset( $status['enabled'] ) && $status['enabled'] ) {
				$groups[ $status['id'] ] = array(
					'id'    => $status['id'],
					'name'  => $status['name'],
					'color' => isset( $status['color'] ) ? $status['color'] : '#ff9800',
					'items' => array(),
				);
			}
		}

		// Process order items.
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			$quantity = $item->get_quantity();
			$backorder_qty = $item->get_meta( '_wmcbp_backorder_quantity', true );
			$instock_qty = $item->get_meta( '_wmcbp_instock_quantity', true );

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
				$backorder_status = WMCBP_Product_Meta::get_product_backorder_status( $product );

				if ( $backorder_status && isset( $groups[ $backorder_status['id'] ] ) ) {
					$groups[ $backorder_status['id'] ]['items'][] = array(
						'item'     => $item,
						'product'  => $product,
						'quantity' => $backorder_qty,
					);
				}
			}
		}

		// Remove empty groups.
		$groups = array_filter( $groups, function( $group ) {
			return ! empty( $group['items'] );
		});

		return $groups;
	}

	/**
	 * Render inline email info (fallback).
	 *
	 * @param array $groups     Order groups.
	 * @param bool  $plain_text Whether email is plain text.
	 */
	private function render_inline_email_info( $groups, $plain_text ) {
		if ( $plain_text ) {
			echo "\n\n" . esc_html__( 'SHIPPING GROUPS', 'custom-backorder-pro' ) . "\n";
			echo esc_html( str_repeat( '=', 50 ) ) . "\n";

			foreach ( $groups as $group ) {
				echo "\n" . esc_html( $group['name'] ) . ":\n";
				foreach ( $group['items'] as $item_data ) {
					echo '- ' . esc_html( $item_data['product']->get_name() ) . ' × ' . (int) $item_data['quantity'] . "\n";
				}
			}
		} else {
			?>
			<h2 style="color: #333; font-size: 18px; margin: 20px 0 10px;">
				<?php esc_html_e( 'Shipping Groups', 'custom-backorder-pro' ); ?>
			</h2>
			<p style="margin: 0 0 20px;">
				<?php esc_html_e( 'Your order contains items that will ship at different times:', 'custom-backorder-pro' ); ?>
			</p>

			<?php foreach ( $groups as $group ) : ?>
				<div style="margin-bottom: 20px; border-left: 4px solid <?php echo esc_attr( $group['color'] ); ?>; padding-left: 15px;">
					<h3 style="color: <?php echo esc_attr( $group['color'] ); ?>; font-size: 16px; margin: 0 0 10px;">
						<?php echo esc_html( $group['name'] ); ?>
					</h3>
					<ul style="margin: 0; padding: 0; list-style: none;">
						<?php foreach ( $group['items'] as $item_data ) : ?>
							<li style="margin: 5px 0;">
								<?php echo esc_html( $item_data['product']->get_name() ); ?> × <?php echo (int) $item_data['quantity']; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
			<?php
		}
	}
}
