<?php
/**
 * Order Details Backorder Template
 *
 * This template can be overridden by copying it to yourtheme/custom-backorder-pro/order-details-backorder.php
 *
 * @package Custom_Backorder_Pro
 * @var array $groups Order groups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="wc-cbs-order-backorder-groups">
	<h2><?php esc_html_e( 'Shipping Information', 'custom-backorder-pro' ); ?></h2>
	<p><?php esc_html_e( 'Your order contains items that will ship at different times:', 'custom-backorder-pro' ); ?></p>

	<?php foreach ( $groups as $wmcbp_group ) : ?>
		<div class="wc-cbs-order-group" style="margin-bottom: 20px; padding: 15px; border-left: 4px solid <?php echo esc_attr( $wmcbp_group['color'] ); ?>; background: #f9f9f9;">
			<h3 style="margin: 0 0 10px; color: <?php echo esc_attr( $wmcbp_group['color'] ); ?>;">
				<?php echo esc_html( $wmcbp_group['name'] ); ?>
			</h3>
			<ul style="margin: 0; padding-left: 20px;">
				<?php foreach ( $wmcbp_group['items'] as $wmcbp_item_data ) : ?>
					<li>
						<?php echo esc_html( $wmcbp_item_data['product']->get_name() ); ?> × <?php echo (int) $wmcbp_item_data['quantity']; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endforeach; ?>
</section>
