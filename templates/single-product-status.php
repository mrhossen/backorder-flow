<?php
/**
 * Single Product Status Template
 *
 * This template can be overridden by copying it to yourtheme/backorder-flow/single-product-status.php
 *
 * @package Backorder_Flow
 * @var array $status Status data
 * @var string $context Display context
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wmbf_color       = isset( $status['color'] ) ? $status['color'] : '#ff9800';
$wmbf_name        = isset( $status['name'] ) ? $status['name'] : '';
$wmbf_description = isset( $status['description'] ) ? $status['description'] : '';
?>

<div class="wc-cbs-backorder-badge wc-cbs-backorder-badge-<?php echo esc_attr( $context ); ?>" style="border-color: <?php echo esc_attr( $wmbf_color ); ?>;">
	<span class="wc-cbs-backorder-badge-icon" style="background-color: <?php echo esc_attr( $wmbf_color ); ?>;"></span>
	<div class="wc-cbs-backorder-badge-content">
		<strong class="wc-cbs-backorder-badge-title"><?php echo esc_html( $wmbf_name ); ?></strong>
		<?php if ( $wmbf_description ) : ?>
			<span class="wc-cbs-backorder-badge-description"><?php echo esc_html( $wmbf_description ); ?></span>
		<?php endif; ?>
	</div>
</div>
