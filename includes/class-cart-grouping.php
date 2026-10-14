<?php
/**
 * Cart Grouping
 *
 * Handles grouping cart items by backorder status.
 *
 * @package Backorder_Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WMBF_Cart_Grouping class.
 */
class WMBF_Cart_Grouping {

	/**
	 * The single instance of the class.
	 *
	 * @var WMBF_Cart_Grouping
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
	 * @return WMBF_Cart_Grouping
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
		// Display grouped cart on cart page - after the cart table, inside the cart form so it refreshes with the cart.
		add_action( 'woocommerce_after_cart_table', array( $this, 'display_cart_groups' ), 10 );

		// Display grouped cart on checkout page - before payment.
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'display_checkout_groups' ), 10 );

		// Enqueue frontend scripts for WooCommerce Blocks.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_block_scripts' ) );

		// Register REST API endpoint for blocks integration.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

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
			'wmbf-blocks-integration',
			WMBF_ASSETS_URL . 'js/blocks-integration.js',
			array( 'wp-api-fetch' ),
			WMBF_VERSION,
			true
		);

		// Get cart groups data directly for inline use.
		$groups_data = $this->get_cart_groups_for_blocks();

		// Localize script with data.
		wp_localize_script(
			'wmbf-blocks-integration',
			'wmbfBlocksData',
			array(
				'restUrl'    => esc_url_raw( rest_url( 'wmbf/v1/' ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'inlineData' => $groups_data,
			)
		);
	}

	/**
	 * Get cart groups data formatted for blocks and the REST API.
	 *
	 * @return array
	 */
	public function get_cart_groups_for_blocks() {
		$groups = $this->get_cart_groups();

		$formatted_groups = array();
		foreach ( $groups as $group ) {
			$formatted_items = array();
			foreach ( $group['items'] as $item ) {
				$product   = $item['product'];
				$quantity  = $item['quantity'];
				$total_qty = $item['total_qty'];

				// Get variation attributes.
				$variation = array();
				if ( isset( $item['cart_item']['variation'] ) && is_array( $item['cart_item']['variation'] ) ) {
					foreach ( $item['cart_item']['variation'] as $attr_key => $attr_value ) {
						$attr_label               = wc_attribute_label( str_replace( 'attribute_', '', $attr_key ) );
						$variation[ $attr_label ] = $attr_value;
					}
				}

				$formatted_items[] = array(
					'name'       => $product->get_name(),
					'thumbnail'  => $product->get_image( 'thumbnail' ),
					'quantity'   => (int) $quantity,
					'total_qty'  => (int) $total_qty,
					'is_partial' => (bool) $item['is_partial'],
					'price_html' => wc_price( self::get_item_price( $item ) ),
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

		$has_backorder = self::groups_have_backorder( $groups );

		return array(
			'groups'        => $formatted_groups,
			'has_backorder' => $has_backorder,
			// The same markup as the classic cart and checkout, rendered from the template.
			'html'          => array(
				'cart'     => $has_backorder ? $this->get_cart_groups_html( $groups, 'cart' ) : '',
				'checkout' => $has_backorder ? $this->get_cart_groups_html( $groups, 'checkout' ) : '',
			),
		);
	}

	/**
	 * Get the price of the part of a cart line that falls in a group.
	 *
	 * @param array $item Group item.
	 * @return float
	 */
	public static function get_item_price( $item ) {
		$quantity  = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
		$total_qty = isset( $item['total_qty'] ) ? (int) $item['total_qty'] : 0;

		if ( $quantity <= 0 || $total_qty <= 0 || ! isset( $item['cart_item']['line_total'] ) ) {
			return 0;
		}

		$line_total = (float) $item['cart_item']['line_total'];

		// Match the cart's tax display setting.
		if ( WC()->cart && WC()->cart->display_prices_including_tax() && isset( $item['cart_item']['line_tax'] ) ) {
			$line_total += (float) $item['cart_item']['line_tax'];
		}

		return ( $line_total / $total_qty ) * $quantity;
	}

	/**
	 * Total quantity in a group.
	 *
	 * @param array $group Cart group.
	 * @return int
	 */
	public static function get_group_units( $group ) {
		$units = 0;
		foreach ( $group['items'] as $item ) {
			$units += (int) $item['quantity'];
		}
		return $units;
	}

	/**
	 * Variation attributes of a group item, as "Size: L, Color: Red".
	 *
	 * @param array $item Group item.
	 * @return string
	 */
	public static function get_item_variation_text( $item ) {
		$parts = array();
		if ( isset( $item['cart_item']['variation'] ) && is_array( $item['cart_item']['variation'] ) ) {
			foreach ( $item['cart_item']['variation'] as $key => $value ) {
				if ( '' !== $value ) {
					$parts[] = wc_attribute_label( str_replace( 'attribute_', '', $key ) ) . ': ' . $value;
				}
			}
		}
		return implode( ', ', $parts );
	}

	/**
	 * Check if groups contain backordered items.
	 *
	 * @param array $groups Cart groups.
	 * @return bool
	 */
	public static function groups_have_backorder( $groups ) {
		foreach ( $groups as $group ) {
			if ( 'ships_now' !== $group['id'] && ! empty( $group['items'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Make sure the cart is loaded during REST API requests.
	 *
	 * WooCommerce only loads the cart for frontend requests, so REST callbacks must load it.
	 */
	public static function maybe_load_cart() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		if ( null === WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		if ( WC()->cart ) {
			// Loads the cart from the session if needed, then refreshes line totals.
			WC()->cart->get_cart();
			WC()->cart->calculate_totals();
		}
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
			'wmbf/v1',
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
		self::maybe_load_cart();

		$response = rest_ensure_response( $this->get_cart_groups_for_blocks() );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
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

		// Only display when the cart contains backordered items.
		if ( ! self::groups_have_backorder( $groups ) ) {
			return;
		}

		$context = is_cart() ? 'cart' : 'checkout';

		// Output a hidden copy that the script below moves into the block once it renders.
		?>
		<div id="wmbf-shipping-groups-fallback-<?php echo esc_attr( $context ); ?>" class="wc-cbs-blocks-groups wc-cbs-blocks-fallback" style="display:none;">
			<?php $this->render_cart_groups( $groups, $context ); ?>
		</div>

		<script type="text/javascript">
		(function() {
			var fallback = document.getElementById('wmbf-shipping-groups-fallback-<?php echo esc_js( $context ); ?>');
			if (!fallback) return;

			function tryInsertFallback() {
				// Check if JS-rendered version exists.
				var jsRendered = document.getElementById('wmbf-shipping-groups-<?php echo esc_js( $context ); ?>');
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
						clone.id = 'wmbf-shipping-groups-<?php echo esc_js( $context ); ?>';
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

		// Only display when the cart contains backordered items.
		if ( empty( $groups ) || ! self::groups_have_backorder( $groups ) ) {
			return;
		}

		$this->render_cart_groups( $groups, 'cart' );
		$this->cart_displayed = true;
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

		// Only display when the cart contains backordered items.
		if ( empty( $groups ) || ! self::groups_have_backorder( $groups ) ) {
			return;
		}

		// Simply display groups (no table closing/opening needed at this hook).
		$this->render_cart_groups( $groups, 'checkout' );
		$this->checkout_displayed = true;
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
		$statuses = WMBF_Admin_Settings::instance()->get_statuses();

		// Create "Ships Now" group.
		$groups['ships_now'] = array(
			'id'          => 'ships_now',
			'name'        => apply_filters( 'wmbf_ships_now_label', __( 'Ships Now', 'backorder-flow' ) ),
			'description' => apply_filters( 'wmbf_ships_now_description', __( 'These items are in stock and will ship immediately.', 'backorder-flow' ) ),
			'color'       => apply_filters( 'wmbf_ships_now_color', '#2e9e63' ),
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

		// Group for backordered items without a custom status.
		$groups['default_backorder'] = array(
			'id'          => 'default_backorder',
			'name'        => apply_filters( 'wmbf_default_backorder_label', __( 'On Backorder', 'backorder-flow' ) ),
			'description' => apply_filters( 'wmbf_default_backorder_description', WMBF_Frontend_Display::get_default_message() ),
			'color'       => apply_filters( 'wmbf_default_backorder_color', '#8b8e94' ),
			'priority'    => PHP_INT_MAX,
			'items'       => array(),
		);

		// Process each cart item.
		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			$product  = $cart_item['data'];
			$quantity = $cart_item['quantity'];

			if ( ! $product ) {
				continue;
			}

			// Get in-stock and backorder quantities.
			$instock_qty   = WMBF_Frontend_Display::get_instock_quantity( $product, $quantity );
			$backorder_qty = WMBF_Frontend_Display::get_backorder_quantity( $product, $quantity );

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
				$backorder_status = WMBF_Product_Meta::get_product_backorder_status( $product );
				$group_id         = ( $backorder_status && isset( $groups[ $backorder_status['id'] ] ) ) ? $backorder_status['id'] : 'default_backorder';

				if ( isset( $groups[ $group_id ] ) ) {
					$groups[ $group_id ]['items'][] = array(
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
				return $priority_a <=> $priority_b;
			}
		);

		return apply_filters( 'wmbf_cart_groups', $groups );
	}

	/**
	 * Render cart groups.
	 *
	 * @param array  $groups  Cart groups.
	 * @param string $context Display context (cart or checkout).
	 */
	private function render_cart_groups( $groups, $context = 'cart' ) {
		$template_path = WMBF()->locate_template( 'cart-groups.php' );

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Cart groups markup as a string.
	 *
	 * @param array  $groups  Cart groups.
	 * @param string $context Display context (cart or checkout).
	 * @return string
	 */
	private function get_cart_groups_html( $groups, $context ) {
		ob_start();
		$this->render_cart_groups( $groups, $context );
		return trim( ob_get_clean() );
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
		return self::groups_have_backorder( self::instance()->get_cart_groups() );
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
