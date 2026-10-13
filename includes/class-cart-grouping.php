<?php
/**
 * Cart Grouping
 *
 * Handles grouping cart items by backorder status.
 *
 * @package Custom_Backorder_Pro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WMCBP_Cart_Grouping class.
 */
class WMCBP_Cart_Grouping {

	/**
	 * The single instance of the class.
	 *
	 * @var WMCBP_Cart_Grouping
	 */
	protected static $_instance = null;

	/**
	 * Flag to track if cart groups have been displayed.
	 *
	 * @var bool
	 */
	private $cart_displayed = false;

	/**
	 * Flag to track if checkout groups have been displayed.
	 *
	 * @var bool
	 */
	private $checkout_displayed = false;

	/**
	 * Main instance.
	 *
	 * @return WMCBP_Cart_Grouping
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
		// Display grouped cart on cart page - after cart contents.
		add_action( 'woocommerce_after_cart_contents', array( $this, 'display_cart_groups' ), 10 );

		// Display grouped cart on checkout page - before payment.
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'display_checkout_groups' ), 10 );

		// Enqueue frontend scripts for WooCommerce Blocks.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_block_scripts' ) );

		// Register REST API endpoint for blocks integration.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Extend WooCommerce Store API for cart item data.
		add_filter( 'woocommerce_store_api_cart_item_data', array( $this, 'add_groups_to_store_api' ), 10, 2 );

		// Fallback: Render groups in footer for blocks (noscript support).
		add_action( 'wp_footer', array( $this, 'render_blocks_fallback' ), 50 );
	}

	/**
	 * Enqueue scripts for WooCommerce Blocks.
	 */
	public function enqueue_block_scripts() {
		// Only enqueue on cart or checkout pages.
		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		// Check if page uses WooCommerce blocks.
		if ( ! $this->page_has_wc_blocks() ) {
			return;
		}

		// Enqueue blocks integration script.
		wp_enqueue_script(
			'wmcbp-blocks-integration',
			WMCBP_ASSETS_URL . 'js/blocks-integration.js',
			array( 'wp-api-fetch' ),
			WMCBP_VERSION,
			true
		);

		// Get cart groups data directly for inline use.
		$groups_data = $this->get_cart_groups_for_blocks();

		// Localize script with data.
		wp_localize_script(
			'wmcbp-blocks-integration',
			'wmcbpBlocksData',
			array(
				'restUrl'    => esc_url_raw( rest_url( 'wmcbp/v1/' ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'inlineData' => $groups_data,
				'i18n'       => array(
					'shippingGroups'      => __( 'Shipping Groups', 'custom-backorder-pro' ),
					'shippingDescription' => __( 'Your order contains items that will ship at different times:', 'custom-backorder-pro' ),
					'qty'                 => __( 'Qty:', 'custom-backorder-pro' ),
					/* translators: %d: total quantity of items */
					'ofTotal'             => __( 'of %d total', 'custom-backorder-pro' ),
				),
			)
		);
	}

	/**
	 * Get cart groups data formatted for blocks.
	 *
	 * @return array
	 */
	private function get_cart_groups_for_blocks() {
		$groups = $this->get_cart_groups();

		if ( empty( $groups ) ) {
			return array(
				'groups'       => array(),
				'has_backorder' => false,
			);
		}

		// Check if has backorder items.
		$has_backorder = false;
		foreach ( $groups as $group ) {
			if ( 'ships_now' !== $group['id'] && ! empty( $group['items'] ) ) {
				$has_backorder = true;
				break;
			}
		}

		// Format groups for JavaScript.
		$formatted_groups = array();
		foreach ( $groups as $group ) {
			$formatted_items = array();
			foreach ( $group['items'] as $item ) {
				$product   = $item['product'];
				$quantity  = $item['quantity'];
				$total_qty = $item['total_qty'];

				// Calculate item price.
				$item_price = 0;
				if ( $quantity > 0 && $total_qty > 0 && isset( $item['cart_item']['line_total'] ) ) {
					$item_price = ( $item['cart_item']['line_total'] / $total_qty ) * $quantity;
				}

				// Get variation attributes.
				$variation = array();
				if ( isset( $item['cart_item']['variation'] ) && is_array( $item['cart_item']['variation'] ) ) {
					foreach ( $item['cart_item']['variation'] as $attr_key => $attr_value ) {
						$attr_label              = wc_attribute_label( str_replace( 'attribute_', '', $attr_key ) );
						$variation[ $attr_label ] = $attr_value;
					}
				}

				$formatted_items[] = array(
					'name'       => $product->get_name(),
					'thumbnail'  => $product->get_image( 'thumbnail' ),
					'quantity'   => (int) $quantity,
					'total_qty'  => (int) $total_qty,
					'is_partial' => (bool) $item['is_partial'],
					'price_html' => wc_price( $item_price ),
					'variation'  => $variation,
				);
			}

			$formatted_groups[] = array(
				'id'          => $group['id'],
				'name'        => $group['name'],
				'description' => $group['description'],
				'color'       => $group['color'],
				'items'       => $formatted_items,
			);
		}

		return array(
			'groups'       => $formatted_groups,
			'has_backorder' => $has_backorder,
		);
	}

	/**
	 * Check if current page has WooCommerce blocks.
	 *
	 * @return bool
	 */
	private function page_has_wc_blocks() {
		// Check if WooCommerce Blocks is active.
		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
			return false;
		}

		// Check for block-based cart/checkout pages.
		if ( function_exists( 'has_block' ) ) {
			// Get the page ID for cart and checkout.
			$cart_page_id     = wc_get_page_id( 'cart' );
			$checkout_page_id = wc_get_page_id( 'checkout' );
			$current_page_id  = get_queried_object_id();

			// Check if current page is cart or checkout and has blocks.
			if ( $current_page_id === $cart_page_id || $current_page_id === $checkout_page_id ) {
				$post = get_post( $current_page_id );
				if ( $post && has_blocks( $post->post_content ) ) {
					// Check for WooCommerce cart or checkout blocks.
					if ( has_block( 'woocommerce/cart', $post ) || has_block( 'woocommerce/checkout', $post ) ) {
						return true;
					}
				}
			}
		}

		// Fallback: Check post content directly.
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && has_blocks( $post->post_content ) ) {
				$blocks = parse_blocks( $post->post_content );
				return $this->has_wc_block_recursive( $blocks );
			}
		}

		return false;
	}

	/**
	 * Recursively check for WooCommerce cart/checkout blocks.
	 *
	 * @param array $blocks Blocks array.
	 * @return bool
	 */
	private function has_wc_block_recursive( $blocks ) {
		foreach ( $blocks as $block ) {
			if ( 'woocommerce/cart' === $block['blockName'] || 'woocommerce/checkout' === $block['blockName'] ) {
				return true;
			}

			// Check inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				if ( $this->has_wc_block_recursive( $block['innerBlocks'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Register REST API routes for blocks integration.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'wmcbp/v1',
			'/cart-groups',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_cart_groups_rest' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST API callback for cart groups.
	 *
	 * @return WP_REST_Response
	 */
	public function get_cart_groups_rest() {
		$groups = $this->get_cart_groups();

		if ( empty( $groups ) ) {
			return rest_ensure_response(
				array(
					'groups'       => array(),
					'has_backorder' => false,
				)
			);
		}

		// Check if has backorder items.
		$has_backorder = false;
		foreach ( $groups as $group ) {
			if ( 'ships_now' !== $group['id'] && ! empty( $group['items'] ) ) {
				$has_backorder = true;
				break;
			}
		}

		// Format groups for REST response.
		$formatted_groups = array();
		foreach ( $groups as $group ) {
			$formatted_items = array();
			foreach ( $group['items'] as $item ) {
				$product   = $item['product'];
				$quantity  = $item['quantity'];
				$total_qty = $item['total_qty'];

				// Calculate item price.
				$item_price = 0;
				if ( $quantity > 0 && $total_qty > 0 && isset( $item['cart_item']['line_total'] ) ) {
					$item_price = ( $item['cart_item']['line_total'] / $total_qty ) * $quantity;
				}

				// Get variation attributes.
				$variation = array();
				if ( isset( $item['cart_item']['variation'] ) && is_array( $item['cart_item']['variation'] ) ) {
					foreach ( $item['cart_item']['variation'] as $attr_key => $attr_value ) {
						$attr_label              = wc_attribute_label( str_replace( 'attribute_', '', $attr_key ) );
						$variation[ $attr_label ] = $attr_value;
					}
				}

				$formatted_items[] = array(
					'name'       => $product->get_name(),
					'thumbnail'  => $product->get_image( 'thumbnail' ),
					'quantity'   => (int) $quantity,
					'total_qty'  => (int) $total_qty,
					'is_partial' => (bool) $item['is_partial'],
					'price_html' => wc_price( $item_price ),
					'variation'  => $variation,
				);
			}

			$formatted_groups[] = array(
				'id'          => $group['id'],
				'name'        => $group['name'],
				'description' => $group['description'],
				'color'       => $group['color'],
				'items'       => $formatted_items,
			);
		}

		return rest_ensure_response(
			array(
				'groups'       => $formatted_groups,
				'has_backorder' => $has_backorder,
			)
		);
	}

	/**
	 * Render groups fallback in footer for WooCommerce Blocks.
	 * This provides server-side rendered HTML that JavaScript will use if needed.
	 */
	public function render_blocks_fallback() {
		// Only run on cart or checkout pages with blocks.
		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		// Check if page has WooCommerce blocks.
		if ( ! $this->page_has_wc_blocks() ) {
			return;
		}

		// Get groups.
		$groups = $this->get_cart_groups();
		if ( empty( $groups ) ) {
			return;
		}

		// Check if should display (multiple groups or has backorder).
		$has_backorder = false;
		foreach ( $groups as $group ) {
			if ( 'ships_now' !== $group['id'] && ! empty( $group['items'] ) ) {
				$has_backorder = true;
				break;
			}
		}

		if ( count( $groups ) < 2 && ! $has_backorder ) {
			return;
		}

		$context = is_cart() ? 'cart' : 'checkout';

		// Output hidden container that JavaScript can move/show.
		?>
		<div id="wmcbp-shipping-groups-fallback-<?php echo esc_attr( $context ); ?>" class="wc-cbs-cart-groups wc-cbs-cart-groups-<?php echo esc_attr( $context ); ?> wc-cbs-blocks-groups wc-cbs-blocks-fallback" style="display:none;">
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

		<script type="text/javascript">
		(function() {
			var fallback = document.getElementById('wmcbp-shipping-groups-fallback-<?php echo esc_js( $context ); ?>');
			if (!fallback) return;

			function tryInsertFallback() {
				// Check if JS-rendered version exists.
				var jsRendered = document.getElementById('wmcbp-shipping-groups-<?php echo esc_js( $context ); ?>');
				if (jsRendered) {
					// Remove fallback since JS version is working.
					fallback.parentNode.removeChild(fallback);
					return true;
				}

				// Try to find insertion point.
				var selectors = <?php echo 'cart' === $context ? "[ '.wc-block-cart__main .wc-block-cart-items', '.wc-block-cart__main', '.wc-block-cart' ]" : "[ '.wc-block-checkout__sidebar .wc-block-components-sidebar', '.wc-block-checkout__sidebar', '.wc-block-checkout' ]"; ?>;

				for (var i = 0; i < selectors.length; i++) {
					var target = document.querySelector(selectors[i]);
					if (target) {
						// Clone and insert.
						var clone = fallback.cloneNode(true);
						clone.id = 'wmcbp-shipping-groups-<?php echo esc_js( $context ); ?>';
						clone.style.display = '';
						clone.classList.remove('wc-cbs-blocks-fallback');

						if (i === 0) {
							target.parentNode.insertBefore(clone, target.nextSibling);
						} else {
							target.appendChild(clone);
						}

						// Remove original fallback.
						fallback.parentNode.removeChild(fallback);
						return true;
					}
				}
				return false;
			}

			// Try immediately.
			if (!tryInsertFallback()) {
				// Retry a few times.
				var attempts = 0;
				var interval = setInterval(function() {
					attempts++;
					if (tryInsertFallback() || attempts > 20) {
						clearInterval(interval);
					}
				}, 250);
			}
		})();
		</script>
		<?php
	}

	/**
	 * Display cart groups on cart page.
	 * This runs inside woocommerce_after_cart_contents (inside the cart table).
	 */
	public function display_cart_groups() {
		// Prevent duplicate display.
		if ( $this->cart_displayed ) {
			return;
		}

		$groups = $this->get_cart_groups();

		if ( empty( $groups ) ) {
			return;
		}

		// Close the cart table, display groups, then reopen the table.
		?>
		</tbody>
		</table>
		<?php

		$this->render_cart_groups( $groups, 'cart' );
		$this->cart_displayed = true;

		?>
		<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
		<tbody>
		<?php
	}

	/**
	 * Display cart groups on checkout page.
	 * This runs at woocommerce_review_order_before_payment (before payment methods).
	 */
	public function display_checkout_groups() {
		// Prevent duplicate display.
		if ( $this->checkout_displayed ) {
			return;
		}

		$groups = $this->get_cart_groups();

		if ( empty( $groups ) ) {
			return;
		}

		// Simply display groups (no table closing/opening needed at this hook).
		$this->render_cart_groups( $groups, 'checkout' );
		$this->checkout_displayed = true;
	}

	/**
	 * Add groups data to Store API for WooCommerce Blocks.
	 *
	 * @param array $item_data Item data.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public function add_groups_to_store_api( $item_data, $cart_item ) {
		$product = $cart_item['data'];
		if ( ! $product ) {
			return $item_data;
		}

		$status = WMCBP_Product_Meta::get_product_backorder_status( $product );
		if ( $status ) {
			$item_data['backorder_status'] = $status;
		}

		return $item_data;
	}

	/**
	 * Get cart items grouped by backorder status.
	 *
	 * @return array
	 */
	public function get_cart_groups() {
		if ( ! WC()->cart ) {
			return array();
		}

		$cart_items = WC()->cart->get_cart();

		if ( empty( $cart_items ) ) {
			return array();
		}

		$groups   = array();
		$statuses = WMCBP_Admin_Settings::instance()->get_statuses();

		// Create "Ships Now" group.
		$groups['ships_now'] = array(
			'id'          => 'ships_now',
			'name'        => apply_filters( 'wmcbp_ships_now_label', __( 'Ships Now', 'custom-backorder-pro' ) ),
			'description' => apply_filters( 'wmcbp_ships_now_description', __( 'These items are in stock and will ship immediately.', 'custom-backorder-pro' ) ),
			'color'       => apply_filters( 'wmcbp_ships_now_color', '#4caf50' ),
			'priority'    => 0,
			'items'       => array(),
		);

		// Create groups for each backorder status.
		foreach ( $statuses as $status ) {
			if ( isset( $status['enabled'] ) && $status['enabled'] ) {
				$groups[ $status['id'] ] = array(
					'id'          => $status['id'],
					'name'        => $status['name'],
					'description' => isset( $status['description'] ) ? $status['description'] : '',
					'color'       => isset( $status['color'] ) ? $status['color'] : '#ff9800',
					'priority'    => isset( $status['priority'] ) ? $status['priority'] : 999,
					'items'       => array(),
				);
			}
		}

		// Process each cart item.
		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			$product  = $cart_item['data'];
			$quantity = $cart_item['quantity'];

			if ( ! $product ) {
				continue;
			}

			// Get in-stock and backorder quantities.
			$instock_qty   = WMCBP_Frontend_Display::get_instock_quantity( $product, $quantity );
			$backorder_qty = WMCBP_Frontend_Display::get_backorder_quantity( $product, $quantity );

			// Add to "Ships Now" group if there's in-stock quantity.
			if ( $instock_qty > 0 ) {
				$groups['ships_now']['items'][] = array(
					'cart_item_key' => $cart_item_key,
					'cart_item'     => $cart_item,
					'product'       => $product,
					'quantity'      => $instock_qty,
					'total_qty'     => $quantity,
					'is_partial'    => $backorder_qty > 0,
				);
			}

			// Add to backorder group if there's backorder quantity.
			if ( $backorder_qty > 0 ) {
				$backorder_status = WMCBP_Product_Meta::get_product_backorder_status( $product );

				if ( $backorder_status && isset( $groups[ $backorder_status['id'] ] ) ) {
					$groups[ $backorder_status['id'] ]['items'][] = array(
						'cart_item_key' => $cart_item_key,
						'cart_item'     => $cart_item,
						'product'       => $product,
						'quantity'      => $backorder_qty,
						'total_qty'     => $quantity,
						'is_partial'    => $instock_qty > 0,
					);
				}
			}
		}

		// Remove empty groups.
		$groups = array_filter(
			$groups,
			function( $group ) {
				return ! empty( $group['items'] );
			}
		);

		// Sort groups by priority.
		uasort(
			$groups,
			function( $a, $b ) {
				$priority_a = isset( $a['priority'] ) ? $a['priority'] : 999;
				$priority_b = isset( $b['priority'] ) ? $b['priority'] : 999;
				return $priority_a - $priority_b;
			}
		);

		return apply_filters( 'wmcbp_cart_groups', $groups );
	}

	/**
	 * Render cart groups.
	 *
	 * @param array  $groups  Cart groups.
	 * @param string $context Display context (cart or checkout).
	 */
	private function render_cart_groups( $groups, $context = 'cart' ) {
		$template_path = WMCBP_TEMPLATES_DIR . 'cart-groups.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			// Fallback inline template.
			$this->render_inline_cart_groups( $groups, $context );
		}
	}

	/**
	 * Render inline cart groups (fallback).
	 *
	 * @param array  $groups  Cart groups.
	 * @param string $context Display context.
	 */
	private function render_inline_cart_groups( $groups, $context ) {
		?>
		<div class="wc-cbs-cart-groups wc-cbs-cart-groups-<?php echo esc_attr( $context ); ?>">
			<h2 class="wc-cbs-cart-groups-title">
				<?php esc_html_e( 'Shipping Groups', 'custom-backorder-pro' ); ?>
			</h2>
			<p class="wc-cbs-cart-groups-description">
				<?php esc_html_e( 'Your order contains items that will ship at different times:', 'custom-backorder-pro' ); ?>
			</p>

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
						<table class="wc-cbs-cart-group-table">
							<tbody>
								<?php foreach ( $group['items'] as $item ) : ?>
									<?php
									$product    = $item['product'];
									$quantity   = $item['quantity'];
									$is_partial = $item['is_partial'];
									?>
									<tr class="wc-cbs-cart-group-item <?php echo $is_partial ? 'wc-cbs-partial-item' : ''; ?>">
										<td class="wc-cbs-item-thumbnail">
											<?php
											$thumbnail = $product->get_image( 'thumbnail' );
											echo wp_kses_post( $thumbnail );
											?>
										</td>
										<td class="wc-cbs-item-name">
											<strong><?php echo esc_html( $product->get_name() ); ?></strong>
											<?php
											// Display variation details if applicable.
											if ( isset( $item['cart_item']['variation'] ) && is_array( $item['cart_item']['variation'] ) ) {
												echo '<div class="wc-cbs-item-variation">';
												foreach ( $item['cart_item']['variation'] as $key => $value ) {
													echo '<div>' . esc_html( wc_attribute_label( str_replace( 'attribute_', '', $key ) ) ) . ': ' . esc_html( $value ) . '</div>';
												}
												echo '</div>';
											}
											?>
											<?php if ( $is_partial ) : ?>
												<span class="wc-cbs-partial-badge">
													<?php
													/* translators: %1$d: quantity from this group, %2$d: total quantity */
													printf( esc_html__( '(%1$d of %2$d total)', 'custom-backorder-pro' ), (int) $quantity, (int) $item['total_qty'] );
													?>
												</span>
											<?php endif; ?>
										</td>
										<td class="wc-cbs-item-quantity">
											<?php
											/* translators: %d: quantity */
											printf( esc_html__( 'Qty: %d', 'custom-backorder-pro' ), (int) $quantity );
											?>
										</td>
										<td class="wc-cbs-item-price">
											<?php
											if ( $quantity > 0 && $item['total_qty'] > 0 ) {
												$item_total = ( $item['cart_item']['line_total'] / $item['total_qty'] ) * $quantity;
												echo wp_kses_post( wc_price( $item_total ) );
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
		<?php
	}

	/**
	 * Get groups for specific cart (used by shortcodes and widgets).
	 *
	 * @return array
	 */
	public static function get_groups() {
		return self::instance()->get_cart_groups();
	}

	/**
	 * Check if cart has backorder items.
	 *
	 * @return bool
	 */
	public static function cart_has_backorder_items() {
		$groups = self::instance()->get_cart_groups();

		foreach ( $groups as $group ) {
			if ( 'ships_now' !== $group['id'] && ! empty( $group['items'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get total backorder items count.
	 *
	 * @return int
	 */
	public static function get_backorder_items_count() {
		$groups = self::instance()->get_cart_groups();
		$count  = 0;

		foreach ( $groups as $group ) {
			if ( 'ships_now' !== $group['id'] ) {
				$count += count( $group['items'] );
			}
		}

		return $count;
	}
}
