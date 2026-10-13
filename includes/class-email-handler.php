<?php
/**
 * Email Handler
 *
 * Handles backorder information in order emails.
 *
 * @package Backorder_Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WMBF_Email_Handler class.
 */
class WMBF_Email_Handler {

	/**
	 * The single instance of the class.
	 *
	 * @var WMBF_Email_Handler
	 */
	protected static $_instance = null;

	/**
	 * Main instance.
	 *
	 * @return WMBF_Email_Handler
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
		$groups = WMBF_Order_Meta::get_order_groups( $order );

		if ( ! WMBF_Order_Meta::groups_have_backorder( $groups ) ) {
			return;
		}

		$template_path = WMBF()->locate_template( 'email-backorder-info.php' );

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
		if ( ! is_callable( array( $item, 'get_product' ) ) ) {
			return;
		}

		// Use the status saved when the order was placed.
		$backorder_qty = (int) $item->get_meta( '_wmbf_backorder_quantity', true );
		$status_id     = (string) $item->get_meta( '_wmbf_backorder_status_id', true );
		$status_name   = (string) $item->get_meta( '_wmbf_backorder_status_name', true );

		if ( ! $backorder_qty || ! $status_name ) {
			return;
		}

		$status = $status_id ? WMBF_Admin_Settings::instance()->get_status( $status_id ) : null;
		$color  = ( $status && ! empty( $status['color'] ) ) ? $status['color'] : '#ff9800';

		if ( $backorder_qty ) {
			echo '<div class="wc-cbs-item-backorder-status" style="font-size: 12px; color: ' . esc_attr( $color ) . ';">';
			echo '<strong>' . esc_html( $status_name ) . '</strong>';
			if ( $backorder_qty < $item->get_quantity() ) {
				/* translators: %d: backorder quantity */
				echo ' - ' . sprintf( esc_html__( '%d item(s)', 'backorder-flow' ), (int) $backorder_qty );
			}
			echo '</div>';
		}
	}

	/**
	 * Render inline email info (fallback).
	 *
	 * @param array $groups     Order groups.
	 * @param bool  $plain_text Whether email is plain text.
	 */
	private function render_inline_email_info( $groups, $plain_text ) {
		if ( $plain_text ) {
			echo "\n\n" . esc_html__( 'SHIPPING GROUPS', 'backorder-flow' ) . "\n";
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
				<?php esc_html_e( 'Shipping Groups', 'backorder-flow' ); ?>
			</h2>
			<p style="margin: 0 0 20px;">
				<?php esc_html_e( 'Your order contains items that will ship at different times:', 'backorder-flow' ); ?>
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
