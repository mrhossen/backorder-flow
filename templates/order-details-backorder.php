<?php
/**
 * Order Details Backorder Template
 *
 * This template can be overridden by copying it to yourtheme/backorder-flow/order-details-backorder.php
 *
 * @package Backorder_Flow
 * @var array $groups Order groups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="wc-cbs-order-backorder-groups">
	<h2><?php esc_html_e( 'Shipping Information', 'backorder-flow' ); ?></h2>
	<p><?php esc_html_e( 'Your order contains items that will ship at different times:', 'backorder-flow' ); ?></p>

	<?php foreach ( $groups as $wmbf_group ) : ?>
		<div class="wc-cbs-order-group" style="margin-bottom: 20px; padding: 15px; border-left: 4px solid <?php echo esc_attr( $wmbf_group['color'] ); ?>; background: #f9f9f9;">
			<h3 style="margin: 0 0 10px; color: <?php echo esc_attr( $wmbf_group['color'] ); ?>;">
				<?php echo esc_html( $wmbf_group['name'] ); ?>
			</h3>
			<ul style="margin: 0; padding-left: 20px;">
				<?php foreach ( $wmbf_group['items'] as $wmbf_item_data ) : ?>
					<li>
						<?php echo esc_html( $wmbf_item_data['product']->get_name() ); ?> × <?php echo (int) $wmbf_item_data['quantity']; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endforeach; ?>
</section>
