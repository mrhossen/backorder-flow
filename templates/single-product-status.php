<?php
/**
 * Single Product Status Template
 *
 * The status card shown for a product on backorder. The product page also builds this
 * card in JavaScript (so it can follow the selected variation and quantity), using the
 * same markup and classes.
 *
 * This template can be overridden by copying it to yourtheme/backorder-flow/single-product-status.php
 *
 * @package Backorder_Flow
 * @var array  $status      Status data (name, description, color).
 * @var string $context     Display context (single, shop, shortcode).
 * @var string $extra_class Optional extra CSS classes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wmbf_color       = ! empty( $status['color'] ) && sanitize_hex_color( $status['color'] ) ? $status['color'] : '#8b8e94';
$wmbf_name        = isset( $status['name'] ) ? $status['name'] : '';
$wmbf_description = isset( $status['description'] ) ? $status['description'] : '';
$wmbf_classes     = 'wc-cbs-backorder-badge wmbf-status-card wc-cbs-backorder-badge-' . $context . ( empty( $extra_class ) ? '' : ' ' . $extra_class );
?>
<div class="<?php echo esc_attr( $wmbf_classes ); ?>" style="--wmbf-color: <?php echo esc_attr( $wmbf_color ); ?>;">
	<span class="wmbf-status-card__icon" aria-hidden="true">
		<svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
	</span>
	<div class="wmbf-status-card__body wc-cbs-backorder-badge-content">
		<strong class="wmbf-status-card__title wc-cbs-backorder-badge-title"><?php echo esc_html( $wmbf_name ); ?></strong>
		<?php if ( $wmbf_description ) : ?>
			<span class="wmbf-status-card__text wc-cbs-backorder-badge-description"><?php echo esc_html( $wmbf_description ); ?></span>
		<?php endif; ?>
	</div>
</div>
