<?php
/**
 * Cart Groups Template
 *
 * The shipping schedule shown in the cart (a full breakdown) and at checkout (a compact
 * summary) when the cart contains backordered items. The Cart and Checkout blocks show
 * the same markup.
 *
 * This template can be overridden by copying it to yourtheme/backorder-flow/cart-groups.php
 *
 * @package Backorder_Flow
 * @var array  $groups  Cart groups data
 * @var string $context Display context (cart or checkout)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wmbf_count = count( $groups );
$wmbf_clock = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';

?>

<?php if ( 'checkout' === $context ) : ?>
	<div class="wmbf-ship-plan wmbf-ship-plan--checkout">
		<div class="wmbf-ship-plan__head">
			<?php echo $wmbf_clock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
			<h3 class="wmbf-ship-plan__title"><?php esc_html_e( 'Delivery schedule', 'backorder-flow' ); ?></h3>
			<span class="wmbf-ship-plan__count">
				<?php
				/* translators: %d: number of shipments */
				echo esc_html( sprintf( _n( '%d shipment', '%d shipments', $wmbf_count, 'backorder-flow' ), $wmbf_count ) );
				?>
			</span>
		</div>
		<?php foreach ( $groups as $wmbf_group ) : ?>
			<div class="wmbf-ship-group" data-group-id="<?php echo esc_attr( $wmbf_group['id'] ); ?>" style="--wmbf-color: <?php echo esc_attr( $wmbf_group['color'] ); ?>;">
				<i class="wmbf-ship-group__dot" aria-hidden="true"></i>
				<div class="wmbf-ship-group__body">
					<strong class="wmbf-ship-group__name"><?php echo esc_html( $wmbf_group['name'] ); ?></strong>
					<?php if ( ! empty( $wmbf_group['description'] ) ) : ?>
						<p class="wmbf-ship-group__desc"><?php echo esc_html( $wmbf_group['description'] ); ?></p>
					<?php endif; ?>
					<ul class="wmbf-ship-group__lines">
						<?php foreach ( $wmbf_group['items'] as $wmbf_item ) : ?>
							<?php $wmbf_variation = WMBF_Cart_Grouping::get_item_variation_text( $wmbf_item ); ?>
							<li>
								<span><?php echo esc_html( $wmbf_item['product']->get_name() . ( $wmbf_variation ? ' (' . $wmbf_variation . ')' : '' ) ); ?></span>
								<span class="wmbf-ship-group__qty">
									<?php
									if ( $wmbf_item['is_partial'] ) {
										/* translators: 1: quantity in this shipment, 2: quantity in the cart */
										echo esc_html( sprintf( __( '× %1$d of %2$d', 'backorder-flow' ), (int) $wmbf_item['quantity'], (int) $wmbf_item['total_qty'] ) );
									} else {
										echo esc_html( '× ' . (int) $wmbf_item['quantity'] );
									}
									?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<div class="wmbf-ship-plan wmbf-ship-plan--cart">
		<div class="wmbf-ship-plan__head">
			<div>
				<h2 class="wmbf-ship-plan__title">
					<?php echo $wmbf_clock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<?php
					/* translators: %d: number of shipments */
					echo esc_html( sprintf( _n( 'Your order ships in %d part', 'Your order ships in %d parts', $wmbf_count, 'backorder-flow' ), $wmbf_count ) );
					?>
				</h2>
				<p class="wmbf-ship-plan__lead"><?php esc_html_e( 'Some items are on backorder, so they ship separately when they\'re ready.', 'backorder-flow' ); ?></p>
			</div>
			<?php if ( $wmbf_count > 1 ) : ?>
				<ol class="wmbf-ship-plan__steps" style="--wmbf-n: <?php echo (int) $wmbf_count; ?>;">
					<?php foreach ( $groups as $wmbf_group ) : ?>
						<?php $wmbf_units = WMBF_Cart_Grouping::get_group_units( $wmbf_group ); ?>
						<li style="--wmbf-color: <?php echo esc_attr( $wmbf_group['color'] ); ?>;">
							<i aria-hidden="true"></i>
							<b><?php echo esc_html( $wmbf_group['name'] ); ?></b>
							<small>
								<?php
								/* translators: %d: number of items */
								echo esc_html( sprintf( _n( '%d item', '%d items', $wmbf_units, 'backorder-flow' ), $wmbf_units ) );
								?>
							</small>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</div>

		<?php foreach ( $groups as $wmbf_group ) : ?>
			<?php
			$wmbf_units    = WMBF_Cart_Grouping::get_group_units( $wmbf_group );
			$wmbf_subtotal = 0;
			foreach ( $wmbf_group['items'] as $wmbf_item ) {
				$wmbf_subtotal += WMBF_Cart_Grouping::get_item_price( $wmbf_item );
			}
			?>
			<div class="wmbf-ship-group" data-group-id="<?php echo esc_attr( $wmbf_group['id'] ); ?>" style="--wmbf-color: <?php echo esc_attr( $wmbf_group['color'] ); ?>;">
				<div class="wmbf-ship-group__head">
					<h3 class="wmbf-ship-group__name"><?php echo esc_html( $wmbf_group['name'] ); ?></h3>
					<span class="wmbf-ship-group__count">
						<?php
						/* translators: %d: number of items */
						echo esc_html( sprintf( _n( '%d item', '%d items', $wmbf_units, 'backorder-flow' ), $wmbf_units ) );
						?>
					</span>
					<span class="wmbf-ship-group__subtotal"><?php echo wp_kses_post( wc_price( $wmbf_subtotal ) ); ?></span>
				</div>
				<?php if ( ! empty( $wmbf_group['description'] ) ) : ?>
					<p class="wmbf-ship-group__desc"><?php echo esc_html( $wmbf_group['description'] ); ?></p>
				<?php endif; ?>
				<ul class="wmbf-ship-group__items">
					<?php foreach ( $wmbf_group['items'] as $wmbf_item ) : ?>
						<?php
						$wmbf_product   = $wmbf_item['product'];
						$wmbf_variation = WMBF_Cart_Grouping::get_item_variation_text( $wmbf_item );
						?>
						<li class="wmbf-ship-item">
							<span class="wmbf-ship-item__thumb"><?php echo wp_kses_post( $wmbf_product->get_image( 'thumbnail' ) ); ?></span>
							<span class="wmbf-ship-item__info">
								<b><?php echo esc_html( $wmbf_product->get_name() ); ?></b>
								<?php if ( $wmbf_item['is_partial'] ) : ?>
									<span class="wmbf-ship-item__part">
										<?php
										/* translators: 1: quantity in this shipment, 2: quantity in the cart */
										echo esc_html( sprintf( __( '%1$d of %2$d', 'backorder-flow' ), (int) $wmbf_item['quantity'], (int) $wmbf_item['total_qty'] ) );
										?>
									</span>
								<?php endif; ?>
								<?php if ( $wmbf_variation ) : ?>
									<small><?php echo esc_html( $wmbf_variation ); ?></small>
								<?php endif; ?>
							</span>
							<span class="wmbf-ship-item__qty"><?php echo esc_html( '× ' . (int) $wmbf_item['quantity'] ); ?></span>
							<span class="wmbf-ship-item__price"><?php echo wp_kses_post( wc_price( WMBF_Cart_Grouping::get_item_price( $wmbf_item ) ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
