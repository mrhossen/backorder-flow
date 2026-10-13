<?php
/**
 * Email Backorder Info Template
 *
 * This template can be overridden by copying it to yourtheme/custom-backorder-pro/email-backorder-info.php
 *
 * @package Custom_Backorder_Pro
 * @var array $groups Order groups
 * @var bool $plain_text Whether email is plain text
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $plain_text ) :
	?>

<?php echo esc_html__( 'SHIPPING GROUPS', 'custom-backorder-pro' ); ?>

<?php echo esc_html( str_repeat( '=', 50 ) ); ?>


	<?php foreach ( $groups as $wmcbp_group ) : ?>

<?php echo esc_html( $wmcbp_group['name'] ); ?>:
		<?php foreach ( $wmcbp_group['items'] as $wmcbp_item_data ) : ?>
- <?php echo esc_html( $wmcbp_item_data['product']->get_name() ); ?> × <?php echo (int) $wmcbp_item_data['quantity']; ?>

		<?php endforeach; ?>
	<?php endforeach; ?>

<?php else : ?>

<h2 style="color: #333; font-size: 18px; margin: 20px 0 10px;">
	<?php esc_html_e( 'Shipping Groups', 'custom-backorder-pro' ); ?>
</h2>
<p style="margin: 0 0 20px;">
	<?php esc_html_e( 'Your order contains items that will ship at different times:', 'custom-backorder-pro' ); ?>
</p>

	<?php foreach ( $groups as $wmcbp_group ) : ?>
<div style="margin-bottom: 20px; border-left: 4px solid <?php echo esc_attr( $wmcbp_group['color'] ); ?>; padding-left: 15px;">
	<h3 style="color: <?php echo esc_attr( $wmcbp_group['color'] ); ?>; font-size: 16px; margin: 0 0 10px;">
		<?php echo esc_html( $wmcbp_group['name'] ); ?>
	</h3>
	<table style="width: 100%; border-collapse: collapse;">
		<?php foreach ( $wmcbp_group['items'] as $wmcbp_item_data ) : ?>
		<tr>
			<td style="padding: 5px 0;">
				<?php echo esc_html( $wmcbp_item_data['product']->get_name() ); ?>
			</td>
			<td style="padding: 5px 0; text-align: right;">
				× <?php echo (int) $wmcbp_item_data['quantity']; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
</div>
	<?php endforeach; ?>

<?php endif; ?>
