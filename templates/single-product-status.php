<?php
/**
 * Single Product Status Template
 *
 * This template can be overridden by copying it to yourtheme/custom-backorder-pro/single-product-status.php
 *
 * @package Custom_Backorder_Pro
 * @var array $status Status data
 * @var string $context Display context
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wmcbp_color       = isset( $status['color'] ) ? $status['color'] : '#ff9800';
$wmcbp_name        = isset( $status['name'] ) ? $status['name'] : '';
$wmcbp_description = isset( $status['description'] ) ? $status['description'] : '';
?>

<div class="wc-cbs-backorder-badge wc-cbs-backorder-badge-<?php echo esc_attr( $context ); ?>" style="border-color: <?php echo esc_attr( $wmcbp_color ); ?>;">
	<span class="wc-cbs-backorder-badge-icon" style="background-color: <?php echo esc_attr( $wmcbp_color ); ?>;"></span>
	<div class="wc-cbs-backorder-badge-content">
		<strong class="wc-cbs-backorder-badge-title"><?php echo esc_html( $wmcbp_name ); ?></strong>
		<?php if ( $wmcbp_description ) : ?>
			<span class="wc-cbs-backorder-badge-description"><?php echo esc_html( $wmcbp_description ); ?></span>
		<?php endif; ?>
	</div>
</div>
