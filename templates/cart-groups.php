<?php
/**
 * Cart Groups Template
 *
 * This template can be overridden by copying it to yourtheme/custom-backorder-pro/cart-groups.php
 *
 * @package Custom_Backorder_Pro
 * @var array $groups Cart groups data
 * @var string $context Display context (cart or checkout)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wc-cbs-cart-groups wc-cbs-cart-groups-<?php echo esc_attr( $context ); ?>">
	<h2 class="wc-cbs-cart-groups-title">
		<?php esc_html_e( 'Shipping Groups', 'custom-backorder-pro' ); ?>
	</h2>
	<p class="wc-cbs-cart-groups-description">
		<?php esc_html_e( 'Your order contains items that will ship at different times:', 'custom-backorder-pro' ); ?>
	</p>

	<?php foreach ( $groups as $wmcbp_group ) : ?>
		<div class="wc-cbs-cart-group" data-group-id="<?php echo esc_attr( $wmcbp_group['id'] ); ?>">
			<div class="wc-cbs-cart-group-header" style="border-left-color: <?php echo esc_attr( $wmcbp_group['color'] ); ?>;">
				<h3 class="wc-cbs-cart-group-title" style="color: <?php echo esc_attr( $wmcbp_group['color'] ); ?>;">
					<?php echo esc_html( $wmcbp_group['name'] ); ?>
				</h3>
				<?php if ( ! empty( $wmcbp_group['description'] ) ) : ?>
					<p class="wc-cbs-cart-group-description">
						<?php echo esc_html( $wmcbp_group['description'] ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="wc-cbs-cart-group-items">
				<table class="wc-cbs-cart-group-table">
					<tbody>
						<?php foreach ( $wmcbp_group['items'] as $wmcbp_item ) : ?>
							<?php
							$wmcbp_product    = $wmcbp_item['product'];
							$wmcbp_quantity   = $wmcbp_item['quantity'];
							$wmcbp_is_partial = $wmcbp_item['is_partial'];
							?>
							<tr class="wc-cbs-cart-group-item <?php echo $wmcbp_is_partial ? 'wc-cbs-partial-item' : ''; ?>">
								<td class="wc-cbs-item-thumbnail">
									<?php echo wp_kses_post( $wmcbp_product->get_image( 'thumbnail' ) ); ?>
								</td>
								<td class="wc-cbs-item-name">
									<strong><?php echo esc_html( $wmcbp_product->get_name() ); ?></strong>
									<?php
									// Display variation details if applicable.
									if ( isset( $wmcbp_item['cart_item']['variation'] ) && is_array( $wmcbp_item['cart_item']['variation'] ) ) {
										echo '<div class="wc-cbs-item-variation">';
										foreach ( $wmcbp_item['cart_item']['variation'] as $wmcbp_attr_key => $wmcbp_attr_value ) {
											echo '<div>' . esc_html( wc_attribute_label( str_replace( 'attribute_', '', $wmcbp_attr_key ) ) ) . ': ' . esc_html( $wmcbp_attr_value ) . '</div>';
										}
										echo '</div>';
									}
									?>
									<?php if ( $wmcbp_is_partial ) : ?>
										<span class="wc-cbs-partial-badge">
											<?php
											/* translators: %1$d: quantity from this group, %2$d: total quantity */
											printf( esc_html__( '(%1$d of %2$d total)', 'custom-backorder-pro' ), (int) $wmcbp_quantity, (int) $wmcbp_item['total_qty'] );
											?>
										</span>
									<?php endif; ?>
								</td>
								<td class="wc-cbs-item-quantity">
									<?php
									/* translators: %d: quantity */
									printf( esc_html__( 'Qty: %d', 'custom-backorder-pro' ), (int) $wmcbp_quantity );
									?>
								</td>
								<td class="wc-cbs-item-price">
									<?php
									if ( $wmcbp_quantity > 0 && $wmcbp_item['total_qty'] > 0 ) {
										$wmcbp_item_total = ( $wmcbp_item['cart_item']['line_total'] / $wmcbp_item['total_qty'] ) * $wmcbp_quantity;
										echo wp_kses_post( wc_price( $wmcbp_item_total ) );
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
