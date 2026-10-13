<?php
/**
 * Shortcodes
 *
 * Handles all plugin shortcodes.
 *
 * @package Custom_Backorder_Pro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WMCBP_Shortcodes class.
 */
class WMCBP_Shortcodes {

	/**
	 * The single instance of the class.
	 *
	 * @var WMCBP_Shortcodes
	 */
	protected static $_instance = null;

	/**
	 * Main instance.
	 *
	 * @return WMCBP_Shortcodes
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
		$this->register_shortcodes();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_shortcode_scripts' ) );
	}

	/**
	 * Register all shortcodes.
	 */
	private function register_shortcodes() {
		add_shortcode( 'wmcbp_backorder_status', array( $this, 'backorder_status_shortcode' ) );
		add_shortcode( 'wmcbp_backorder_groups', array( $this, 'backorder_groups_shortcode' ) );
		add_shortcode( 'wmcbp_backorder_products', array( $this, 'backorder_products_shortcode' ) );
		add_shortcode( 'wmcbp_backorder_badge', array( $this, 'backorder_badge_shortcode' ) );
	}

	/**
	 * Enqueue scripts for shortcodes.
	 */
	public function enqueue_shortcode_scripts() {
		// Only enqueue if shortcode is used on current page.
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		// Check if any of our shortcodes are present.
		if ( has_shortcode( $post->post_content, 'wmcbp_backorder_status' ) ||
		     has_shortcode( $post->post_content, 'wmcbp_backorder_badge' ) ) {
			wp_enqueue_script( 'jquery' );
		}
	}

	/**
	 * Shortcode: [wmcbp_backorder_status]
	 * Display current product's backorder status.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function backorder_status_shortcode( $atts ) {
		// Set flag to prevent default display when shortcode is used
		WMCBP_Frontend_Display::set_elementor_widget_rendered();

		$atts = shortcode_atts(
			array(
				'product_id' => get_the_ID(),
				'style'      => 'badge', // badge, text, full
			),
			$atts,
			'wmcbp_backorder_status'
		);

		$product = wc_get_product( $atts['product_id'] );

		if ( ! $product ) {
			return '';
		}

		$status = WMCBP_Product_Meta::get_product_backorder_status( $product );

		if ( ! $status ) {
			return '';
		}

		// Add script to hide default badges when shortcode is used
		$inline_script = "
		(function() {
			// Hide default single product badges when shortcode is used
			var singleBadges = document.querySelectorAll('.wc-cbs-backorder-badge-single');
			singleBadges.forEach(function(badge) {
				badge.style.display = 'none';
			});
			var defaultBadges = document.querySelectorAll('.wc-cbs-default-badge');
			defaultBadges.forEach(function(badge) {
				badge.style.display = 'none';
			});
		})();
		";

		wp_add_inline_script( 'jquery', $inline_script );

		ob_start();

		switch ( $atts['style'] ) {
			case 'text':
				echo '<span class="wc-cbs-status-text">' . esc_html( $status['name'] ) . '</span>';
				break;

			case 'full':
				echo '<div class="wc-cbs-status-full">';
				echo '<strong>' . esc_html( $status['name'] ) . '</strong>';
				if ( ! empty( $status['description'] ) ) {
					echo '<p>' . esc_html( $status['description'] ) . '</p>';
				}
				echo '</div>';
				break;

			case 'badge':
			default:
				$color = isset( $status['color'] ) ? $status['color'] : '#ff9800';
				?>
				<div class="wc-cbs-backorder-badge wc-cbs-shortcode-badge" style="border-color: <?php echo esc_attr( $color ); ?>;">
					<span class="wc-cbs-backorder-badge-icon" style="background-color: <?php echo esc_attr( $color ); ?>;"></span>
					<div class="wc-cbs-backorder-badge-content">
						<strong class="wc-cbs-backorder-badge-title"><?php echo esc_html( $status['name'] ); ?></strong>
						<?php if ( ! empty( $status['description'] ) ) : ?>
							<span class="wc-cbs-backorder-badge-description"><?php echo esc_html( $status['description'] ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<?php
				break;
		}

		return ob_get_clean();
	}

	/**
	 * Shortcode: [wmcbp_backorder_groups]
	 * Display cart grouped by backorder status.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function backorder_groups_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'layout' => 'table', // table, list, cards
				'show_thumbnails' => 'yes',
				'show_prices' => 'yes',
			),
			$atts,
			'wmcbp_backorder_groups'
		);

		$groups = WMCBP_Cart_Grouping::get_groups();

		if ( empty( $groups ) ) {
			return '<p class="wc-cbs-no-items">' . esc_html__( 'No items in cart.', 'custom-backorder-pro' ) . '</p>';
		}

		ob_start();
		?>
		<div class="wc-cbs-cart-groups wc-cbs-shortcode-groups wc-cbs-layout-<?php echo esc_attr( $atts['layout'] ); ?>">
			<?php foreach ( $groups as $group ) : ?>
				<div class="wc-cbs-cart-group" data-group-id="<?php echo esc_attr( $group['id'] ); ?>">
					<div class="wc-cbs-cart-group-header" style="border-left-color: <?php echo esc_attr( $group['color'] ); ?>;">
						<h3 class="wc-cbs-cart-group-title" style="color: <?php echo esc_attr( $group['color'] ); ?>;">
							<?php echo esc_html( $group['name'] ); ?>
						</h3>
						<?php if ( ! empty( $group['description'] ) ) : ?>
							<p class="wc-cbs-cart-group-description">
								<?php echo esc_html( $group['description'] ); ?>
							</p>
						<?php endif; ?>
					</div>

					<div class="wc-cbs-cart-group-items">
						<?php if ( 'table' === $atts['layout'] ) : ?>
							<table class="wc-cbs-cart-group-table">
								<tbody>
									<?php foreach ( $group['items'] as $item ) : ?>
										<?php $this->render_group_item_table( $item, $atts ); ?>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php elseif ( 'list' === $atts['layout'] ) : ?>
							<ul class="wc-cbs-cart-group-list">
								<?php foreach ( $group['items'] as $item ) : ?>
									<?php $this->render_group_item_list( $item, $atts ); ?>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<div class="wc-cbs-cart-group-cards">
								<?php foreach ( $group['items'] as $item ) : ?>
									<?php $this->render_group_item_card( $item, $atts ); ?>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: [wmcbp_backorder_products]
	 * Display products with specific backorder status.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function backorder_products_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'status'    => '',
				'limit'     => 10,
				'columns'   => 4,
				'orderby'   => 'date',
				'order'     => 'DESC',
			),
			$atts,
			'wmcbp_backorder_products'
		);

		if ( empty( $atts['status'] ) ) {
			return '<p class="wc-cbs-error">' . esc_html__( 'Please specify a backorder status.', 'custom-backorder-pro' ) . '</p>';
		}

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => intval( $atts['limit'] ),
			'orderby'        => $atts['orderby'],
			'order'          => $atts['order'],
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to filter products by backorder status.
			'meta_query'     => array(
				array(
					'key'   => '_wmcbp_backorder_status',
					'value' => $atts['status'],
				),
			),
		);

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return '<p class="wc-cbs-no-products">' . esc_html__( 'No products found.', 'custom-backorder-pro' ) . '</p>';
		}

		ob_start();
		?>
		<div class="wc-cbs-products woocommerce columns-<?php echo esc_attr( $atts['columns'] ); ?>">
			<ul class="products columns-<?php echo esc_attr( $atts['columns'] ); ?>">
				<?php
				while ( $query->have_posts() ) {
					$query->the_post();
					wc_get_template_part( 'content', 'product' );
				}
				wp_reset_postdata();
				?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: [wmcbp_backorder_badge]
	 * Display backorder badge for specific product.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function backorder_badge_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'product_id' => get_the_ID(),
			),
			$atts,
			'wmcbp_backorder_badge'
		);

		return $this->backorder_status_shortcode( array(
			'product_id' => $atts['product_id'],
			'style'      => 'badge',
		) );
	}

	/**
	 * Render group item as table row.
	 *
	 * @param array $item Item data.
	 * @param array $atts Shortcode attributes.
	 */
	private function render_group_item_table( $item, $atts ) {
		$product = $item['product'];
		$quantity = $item['quantity'];
		$is_partial = $item['is_partial'];
		?>
		<tr class="wc-cbs-cart-group-item <?php echo $is_partial ? 'wc-cbs-partial-item' : ''; ?>">
			<?php if ( 'yes' === $atts['show_thumbnails'] ) : ?>
				<td class="wc-cbs-item-thumbnail">
					<?php echo wp_kses_post( $product->get_image( 'thumbnail' ) ); ?>
				</td>
			<?php endif; ?>
			<td class="wc-cbs-item-name">
				<strong><?php echo esc_html( $product->get_name() ); ?></strong>
			</td>
			<td class="wc-cbs-item-quantity">
				<?php
				/* translators: %d: quantity */
				printf( esc_html__( 'Qty: %d', 'custom-backorder-pro' ), (int) $quantity );
				?>
			</td>
			<?php if ( 'yes' === $atts['show_prices'] ) : ?>
				<td class="wc-cbs-item-price">
					<?php
					if ( $quantity > 0 && $item['total_qty'] > 0 ) {
						$item_total = ( $item['cart_item']['line_total'] / $item['total_qty'] ) * $quantity;
						echo wp_kses_post( wc_price( $item_total ) );
					}
					?>
				</td>
			<?php endif; ?>
		</tr>
		<?php
	}

	/**
	 * Render group item as list item.
	 *
	 * @param array $item Item data.
	 * @param array $atts Shortcode attributes.
	 */
	private function render_group_item_list( $item, $atts ) {
		$product = $item['product'];
		$quantity = $item['quantity'];
		?>
		<li class="wc-cbs-cart-group-item">
			<?php if ( 'yes' === $atts['show_thumbnails'] ) : ?>
				<span class="wc-cbs-item-thumbnail">
					<?php echo wp_kses_post( $product->get_image( 'thumbnail' ) ); ?>
				</span>
			<?php endif; ?>
			<span class="wc-cbs-item-name"><?php echo esc_html( $product->get_name() ); ?></span>
			<span class="wc-cbs-item-quantity">(<?php echo (int) $quantity; ?>)</span>
			<?php if ( 'yes' === $atts['show_prices'] ) : ?>
				<span class="wc-cbs-item-price">
					<?php
					if ( $quantity > 0 && $item['total_qty'] > 0 ) {
						$item_total = ( $item['cart_item']['line_total'] / $item['total_qty'] ) * $quantity;
						echo wp_kses_post( wc_price( $item_total ) );
					}
					?>
				</span>
			<?php endif; ?>
		</li>
		<?php
	}

	/**
	 * Render group item as card.
	 *
	 * @param array $item Item data.
	 * @param array $atts Shortcode attributes.
	 */
	private function render_group_item_card( $item, $atts ) {
		$product = $item['product'];
		$quantity = $item['quantity'];
		?>
		<div class="wc-cbs-cart-group-item wc-cbs-item-card">
			<?php if ( 'yes' === $atts['show_thumbnails'] ) : ?>
				<div class="wc-cbs-item-thumbnail">
					<?php echo wp_kses_post( $product->get_image( 'thumbnail' ) ); ?>
				</div>
			<?php endif; ?>
			<div class="wc-cbs-item-details">
				<h4 class="wc-cbs-item-name"><?php echo esc_html( $product->get_name() ); ?></h4>
				<p class="wc-cbs-item-quantity">
					<?php
					/* translators: %d: quantity */
					printf( esc_html__( 'Quantity: %d', 'custom-backorder-pro' ), (int) $quantity );
					?>
				</p>
				<?php if ( 'yes' === $atts['show_prices'] ) : ?>
					<p class="wc-cbs-item-price">
						<?php
						if ( $quantity > 0 && $item['total_qty'] > 0 ) {
							$item_total = ( $item['cart_item']['line_total'] / $item['total_qty'] ) * $quantity;
							echo wp_kses_post( wc_price( $item_total ) );
						}
						?>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
