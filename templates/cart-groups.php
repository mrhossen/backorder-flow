<?php
/**
 * Cart Groups Template
 *
 * This template can be overridden by copying it to yourtheme/backorder-flow/cart-groups.php
 *
 * @package Backorder_Flow
 * @var array $groups Cart groups data
 * @var string $context Display context (cart or checkout)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wc-cbs-cart-groups wc-cbs-cart-groups-<?php echo esc_attr( $context ); ?>">
	<h2 class="wc-cbs-cart-groups-title">
		<?php esc_html_e( 'Shipping Groups', 'backorder-flow' ); ?>
	</h2>
	<p class="wc-cbs-cart-groups-description">
		<?php esc_html_e( 'Your order contains items that will ship at different times:', 'backorder-flow' ); ?>
	</p>

	<?php foreach ( $groups as $wmbf_group ) : ?>
		<div class="wc-cbs-cart-group" data-group-id="<?php echo esc_attr( $wmbf_group['id'] ); ?>">
			<div class="wc-cbs-cart-group-header" style="border-left-color: <?php echo esc_attr( $wmbf_group['color'] ); ?>;">
				<h3 class="wc-cbs-cart-group-title" style="color: <?php echo esc_attr( $wmbf_group['color'] ); ?>;">
					<?php echo esc_html( $wmbf_group['name'] ); ?>
				</h3>
				<?php if ( ! empty( $wmbf_group['description'] ) ) : ?>
					<p class="wc-cbs-cart-group-description">
						<?php echo esc_html( $wmbf_group['description'] ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="wc-cbs-cart-group-items">
				<table class="wc-cbs-cart-group-table">
					<tbody>
						<?php foreach ( $wmbf_group['items'] as $wmbf_item ) : ?>
							<?php
							$wmbf_product    = $wmbf_item['product'];
							$wmbf_quantity   = $wmbf_item['quantity'];
							$wmbf_is_partial = $wmbf_item['is_partial'];
							?>
							<tr class="wc-cbs-cart-group-item <?php echo $wmbf_is_partial ? 'wc-cbs-partial-item' : ''; ?>">
								<td class="wc-cbs-item-thumbnail">
									<?php echo wp_kses_post( $wmbf_product->get_image( 'thumbnail' ) ); ?>
								</td>
								<td class="wc-cbs-item-name">
									<strong><?php echo esc_html( $wmbf_product->get_name() ); ?></strong>
									<?php
									// Display variation details if applicable.
									if ( isset( $wmbf_item['cart_item']['variation'] ) && is_array( $wmbf_item['cart_item']['variation'] ) ) {
										echo '<div class="wc-cbs-item-variation">';
										foreach ( $wmbf_item['cart_item']['variation'] as $wmbf_attr_key => $wmbf_attr_value ) {
											echo '<div>' . esc_html( wc_attribute_label( str_replace( 'attribute_', '', $wmbf_attr_key ) ) ) . ': ' . esc_html( $wmbf_attr_value ) . '</div>';
										}
										echo '</div>';
									}
									?>
									<?php if ( $wmbf_is_partial ) : ?>
										<span class="wc-cbs-partial-badge">
											<?php
											/* translators: %1$d: quantity from this group, %2$d: total quantity */
											printf( esc_html__( '(%1$d of %2$d total)', 'backorder-flow' ), (int) $wmbf_quantity, (int) $wmbf_item['total_qty'] );
											?>
										</span>
									<?php endif; ?>
								</td>
								<td class="wc-cbs-item-quantity">
									<?php
									/* translators: %d: quantity */
									printf( esc_html__( 'Qty: %d', 'backorder-flow' ), (int) $wmbf_quantity );
									?>
								</td>
								<td class="wc-cbs-item-price">
									<?php
									if ( $wmbf_quantity > 0 && $wmbf_item['total_qty'] > 0 ) {
										$wmbf_item_total = ( $wmbf_item['cart_item']['line_total'] / $wmbf_item['total_qty'] ) * $wmbf_quantity;
										echo wp_kses_post( wc_price( $wmbf_item_total ) );
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endforeach; ?>
</div>
