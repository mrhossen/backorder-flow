<?php
/**
 * Frontend Display
 *
 * Handles displaying backorder status on frontend.
 *
 * @package Backorder_Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WMBF_Frontend_Display class.
 */
class WMBF_Frontend_Display {

	/**
	 * The single instance of the class.
	 *
	 * @var WMBF_Frontend_Display
	 */
	protected static $_instance = null;

	/**
	 * Whether a shortcode or Elementor widget already rendered the status on this page.
	 *
	 * @var bool
	 */
	protected static $widget_rendered = false;

	/**
	 * Main instance.
	 *
	 * @return WMBF_Frontend_Display
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
		// Single product page ONLY - display before add to cart button.
		// Use priority 999 to run after most content (including Elementor widgets)
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'display_backorder_status_single' ), 999 );

		// Cart page.
		add_filter( 'woocommerce_cart_item_name', array( $this, 'display_backorder_status_cart' ), 10, 3 );

		// Checkout page.
		add_filter( 'woocommerce_checkout_cart_item_quantity', array( $this, 'display_backorder_status_checkout' ), 10, 3 );

		// Override WooCommerce backorder notification - return empty string to hide it.
		add_filter( 'woocommerce_get_availability_text', array( $this, 'filter_availability_text' ), 10, 2 );

		// Also filter the availability HTML to completely suppress the default display.
		add_filter( 'woocommerce_get_availability', array( $this, 'filter_availability_html' ), 10, 2 );

		// Enqueue frontend scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	}

	/**
	 * Enqueue frontend scripts for product pages.
	 */
	public function enqueue_frontend_scripts() {
		// Only load on single product pages.
		if ( ! is_product() ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
	}

	/**
	 * Display backorder status on shop/archive pages.
	 *
	 * @param WC_Product $product Product object (global if not passed).
	 */
	public function display_backorder_status_shop( $product = null ) {
		global $product;

		if ( ! $product ) {
			return;
		}

		// For shop pages, only show if product is actually on backorder (stock is 0 or less)
		if ( ! self::accepts_backorders( $product ) ) {
			return;
		}

		$status = WMBF_Product_Meta::get_product_backorder_status( $product );

		if ( ! $status ) {
			return;
		}

		// Only show on shop if product is managing stock and stock is 0 or less
		if ( $product->managing_stock() ) {
			$stock_qty = $product->get_stock_quantity();
			if ( $stock_qty === null || $stock_qty > 0 ) {
				return;
			}
		}

		$this->render_backorder_badge( $status, 'shop' );
	}

	/**
	 * Display backorder status on single product page.
	 */
	public function display_backorder_status_single() {
		// Prevent duplicate display - only run once per page load
		static $already_displayed = false;
		if ( $already_displayed ) {
			return;
		}

		// Check if Elementor widget has already rendered
		if ( self::elementor_widget_rendered() ) {
			return;
		}

		// Check if page content contains our shortcode or widget
		if ( $this->page_has_backorder_display() ) {
			return;
		}

		global $product;

		if ( ! $product ) {
			return;
		}

		// For variable products, render a placeholder that will be updated via JavaScript
		if ( $product->is_type( 'variable' ) ) {
			$this->render_variable_product_placeholder();
			$already_displayed = true;
			return;
		}

		// Simple products use a placeholder that follows the selected quantity.
		if ( ! self::accepts_backorders( $product ) ) {
			return;
		}

		// Products without a status show the default backorder message in the same card.
		$status = WMBF_Product_Meta::get_product_backorder_status( $product );
		if ( ! $status ) {
			$status = self::get_default_status();
		}

		$this->render_simple_product_placeholder( $product, $status );
		$already_displayed = true;
	}

	/**
	 * Check if page content already has backorder display (widget or shortcode).
	 *
	 * @return bool
	 */
	private function page_has_backorder_display() {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$content = $post->post_content;

		// Check for shortcodes
		if ( has_shortcode( $content, 'wmbf_backorder_status' ) ||
		     has_shortcode( $content, 'wmbf_backorder_badge' ) ) {
			return true;
		}

		// Check for Elementor data
		$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
		if ( ! empty( $elementor_data ) ) {
			// Check if Elementor data contains our widgets (check for actual widget names)
			if ( strpos( $elementor_data, 'wc-cbs-product-backorder-status' ) !== false ||
			     strpos( $elementor_data, 'wc-cbs-backorder-badge' ) !== false ||
			     strpos( $elementor_data, 'wc-cbs-widget' ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if current page is using Elementor.
	 *
	 * @return bool
	 */
	private function is_elementor_page() {
		// Primary check: If Elementor widget has been rendered (flag set by widget)
		// This is the most reliable method - if widget renders, we know Elementor is used
		if ( self::elementor_widget_rendered() ) {
			return true;
		}

		// Secondary checks only if Elementor is active
		if ( ! did_action( 'elementor/loaded' ) ) {
			return false;
		}

		// Check if Elementor edit/preview mode is active using official Elementor API
		if ( class_exists( '\Elementor\Plugin' ) ) {
			try {
				if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
					return false; // Don't hide in preview mode
				}
				if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
					return false; // Don't hide in edit mode
				}
			} catch ( Exception $e ) {
				// If there's any error, just return false
				return false;
			}
		}

		// If we get here, rely solely on the flag system
		// The widget will set the flag when it renders, that's the safest check
		return false;
	}

	/**
	 * Check if Elementor widget has been rendered.
	 *
	 * @return bool
	 */
	public static function elementor_widget_rendered() {
		return self::$widget_rendered;
	}

	/**
	 * Mark that Elementor widget has been rendered.
	 */
	public static function set_elementor_widget_rendered() {
		self::$widget_rendered = true;
	}

	/**
	 * Print a script that hides the default single product badge.
	 *
	 * Used by the shortcode and Elementor widgets, which can render after the default badge.
	 */
	public static function print_hide_default_badge_script() {
		static $printed = false;
		if ( $printed ) {
			return;
		}
		$printed = true;
		?>
		<script type="text/javascript">
		(function() {
			function wmbfHideDefaultBadges() {
				document.querySelectorAll( '.wc-cbs-default-badge' ).forEach( function( badge ) {
					badge.classList.add( 'wc-cbs-hidden-by-widget' );
					badge.style.setProperty( 'display', 'none', 'important' );
				} );
			}
			wmbfHideDefaultBadges();
			document.addEventListener( 'DOMContentLoaded', wmbfHideDefaultBadges );
		})();
		</script>
		<?php
	}

	/**
	 * Check if a product is currently on backorder (out of stock with backorders allowed).
	 *
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	public static function is_currently_on_backorder( $product ) {
		if ( ! self::accepts_backorders( $product ) ) {
			return false;
		}

		if ( $product->managing_stock() ) {
			$stock_quantity = $product->get_stock_quantity();
			return null !== $stock_quantity && $stock_quantity <= 0;
		}

		return $product->is_on_backorder();
	}

	/**
	 * Check if a product can be ordered on backorder.
	 *
	 * Covers products that manage stock with backorders allowed, and products that do not
	 * manage stock but have their stock status set to "On backorder".
	 *
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	public static function accepts_backorders( $product ) {
		if ( ! $product ) {
			return false;
		}

		if ( $product->backorders_allowed() ) {
			return true;
		}

		return ! $product->managing_stock() && 'onbackorder' === $product->get_stock_status();
	}

	/**
	 * Get the default backorder message.
	 *
	 * @return string
	 */
	public static function get_default_message() {
		return (string) get_option( 'wmbf_default_message', __( 'This item is currently on backorder.', 'backorder-flow' ) );
	}

	/**
	 * The status used for products on backorder that have no status of their own.
	 *
	 * @return array
	 */
	public static function get_default_status() {
		return array(
			'id'          => 'default_backorder',
			'name'        => apply_filters( 'wmbf_default_backorder_label', __( 'On Backorder', 'backorder-flow' ) ),
			'description' => apply_filters( 'wmbf_default_backorder_description', self::get_default_message() ),
			'color'       => apply_filters( 'wmbf_default_backorder_color', '#8b8e94' ),
			'enabled'     => true,
		);
	}

	/**
	 * Display backorder status in cart.
	 *
	 * @param string $product_name Product name HTML.
	 * @param array  $cart_item    Cart item data.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public function display_backorder_status_cart( $product_name, $cart_item, $cart_item_key ) {
		$product = $cart_item['data'];

		if ( ! $product || ! self::accepts_backorders( $product ) ) {
			return $product_name;
		}

		// Only in the cart table itself, not in the mini cart or the checkout summary.
		if ( ! is_cart() || ! did_action( 'woocommerce_before_cart_contents' ) || did_action( 'woocommerce_cart_contents' ) ) {
			return $product_name;
		}

		$quantity      = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;
		$backorder_qty = self::get_backorder_quantity( $product, $quantity );

		if ( $backorder_qty <= 0 ) {
			return $product_name;
		}

		$status = WMBF_Product_Meta::get_product_backorder_status( $product );
		if ( ! $status ) {
			$status = self::get_default_status();
		}

		$label = $status['name'];
		if ( $backorder_qty < $quantity ) {
			/* translators: 1: quantity on backorder, 2: quantity in the cart, 3: status name */
			$label = sprintf( __( '%1$d of %2$d · %3$s', 'backorder-flow' ), $backorder_qty, $quantity, $status['name'] );
		}

		$color = ! empty( $status['color'] ) && sanitize_hex_color( $status['color'] ) ? $status['color'] : '#8b8e94';

		return $product_name . '<span class="wmbf-cart-tag" style="--wmbf-color: ' . esc_attr( $color ) . ';">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Display backorder status on checkout.
	 *
	 * @param string $quantity      Quantity HTML.
	 * @param array  $cart_item     Cart item data.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public function display_backorder_status_checkout( $quantity, $cart_item, $cart_item_key ) {
		$product = $cart_item['data'];

		if ( ! $product ) {
			return $quantity;
		}

		if ( ! $this->should_display_backorder_status( $product ) ) {
			return $quantity;
		}

		// Don't show here - will be shown in grouped cart display.
		return $quantity;
	}

	/**
	 * Filter WooCommerce availability text to use custom status.
	 *
	 * @param string     $availability Availability text.
	 * @param WC_Product $product      Product object.
	 * @return string
	 */
	public function filter_availability_text( $availability, $product ) {
		if ( ! $product || ! self::is_currently_on_backorder( $product ) ) {
			return $availability;
		}

		// Products with a custom status show the status badge instead of the default text.
		if ( $this->should_display_backorder_status( $product ) ) {
			return '';
		}

		// Products without a custom status show the default backorder message.
		$default_message = self::get_default_message();
		if ( '' !== $default_message ) {
			return $default_message;
		}

		return $availability;
	}

	/**
	 * Filter WooCommerce availability HTML to suppress default display.
	 *
	 * @param array      $availability Availability data (class and text).
	 * @param WC_Product $product      Product object.
	 * @return array
	 */
	public function filter_availability_html( $availability, $product ) {
		if ( ! $product ) {
			return $availability;
		}

		// If the product is on backorder and shows a custom status, suppress the default HTML.
		if ( self::is_currently_on_backorder( $product ) && $this->should_display_backorder_status( $product ) ) {
			// Return empty availability to suppress default display
			return array(
				'availability' => '',
				'class'        => '',
			);
		}

		return $availability;
	}

	/**
	 * Check if backorder status should be displayed.
	 *
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	private function should_display_backorder_status( $product ) {
		if ( ! $product ) {
			return false;
		}

		// Check if product has a custom backorder status assigned.
		$status = WMBF_Product_Meta::get_product_backorder_status( $product );
		if ( ! $status ) {
			return false;
		}

		// The status only applies to products that can be ordered on backorder.
		// Whether it is currently visible depends on stock vs. selected quantity (handled in JS).
		return self::accepts_backorders( $product );
	}

	/**
	 * Render backorder badge.
	 *
	 * @param array  $status  Status data.
	 * @param string $context Display context (shop, single, cart, checkout).
	 */
	private function render_backorder_badge( $status, $context = 'single' ) {
		self::render_status_card( $status, $context, 'single' === $context ? 'wc-cbs-default-badge' : '' );
	}

	/**
	 * Render the status card from the single-product-status.php template.
	 *
	 * @param array  $status      Status data.
	 * @param string $context     Display context (single, shop, shortcode).
	 * @param string $extra_class Extra CSS classes.
	 */
	public static function render_status_card( $status, $context = 'single', $extra_class = '' ) {
		$template_path = WMBF()->locate_template( 'single-product-status.php' );

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Get backorder status for a product (public method).
	 *
	 * @param int|WC_Product $product Product ID or object.
	 * @return array|null
	 */
	public static function get_status_for_product( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product ) {
			return null;
		}

		return WMBF_Product_Meta::get_product_backorder_status( $product );
	}

	/**
	 * Check if product is on backorder with stock quantity check.
	 *
	 * @param WC_Product $product     Product object.
	 * @param int        $quantity    Quantity being ordered.
	 * @return bool
	 */
	public static function is_product_on_backorder( $product, $quantity = 1 ) {
		if ( ! $product ) {
			return false;
		}

		if ( ! self::accepts_backorders( $product ) ) {
			return false;
		}

		$stock_quantity = $product->get_stock_quantity();

		// If managing stock and quantity exceeds available stock.
		if ( $product->managing_stock() && $stock_quantity !== null ) {
			return $quantity > $stock_quantity;
		}

		// Not managing stock: the stock status decides.
		return 'onbackorder' === $product->get_stock_status();
	}

	/**
	 * Get backorder quantity for a product.
	 *
	 * @param WC_Product $product  Product object.
	 * @param int        $quantity Quantity being ordered.
	 * @return int
	 */
	public static function get_backorder_quantity( $product, $quantity ) {
		if ( ! $product ) {
			return 0;
		}

		if ( ! $product->managing_stock() ) {
			return 'onbackorder' === $product->get_stock_status() ? $quantity : 0;
		}

		$stock_quantity = $product->get_stock_quantity();

		if ( $stock_quantity === null ) {
			return 0;
		}

		if ( $stock_quantity >= $quantity ) {
			return 0;
		}

		return max( 0, $quantity - max( 0, $stock_quantity ) );
	}

	/**
	 * Get in-stock quantity for a product.
	 *
	 * @param WC_Product $product  Product object.
	 * @param int        $quantity Quantity being ordered.
	 * @return int
	 */
	public static function get_instock_quantity( $product, $quantity ) {
		if ( ! $product ) {
			return 0;
		}

		if ( ! $product->managing_stock() ) {
			return 'onbackorder' === $product->get_stock_status() ? 0 : $quantity;
		}

		$stock_quantity = $product->get_stock_quantity();

		if ( $stock_quantity === null ) {
			return $quantity;
		}

		return min( $quantity, max( 0, $stock_quantity ) );
	}

	/**
	 * Strings and helpers shared by the product page scripts.
	 *
	 * @return array
	 */
	private static function card_script_data() {
		return array(
			/* translators: %d: quantity (always 1) */
			'shipsNowOne'  => __( '%d ships now', 'backorder-flow' ),
			/* translators: %d: quantity */
			'shipsNowMany' => __( '%d ship now', 'backorder-flow' ),
			/* translators: %d: quantity */
			'onBackorder'  => __( '%d on backorder', 'backorder-flow' ),
			'nowColor'     => apply_filters( 'wmbf_ships_now_color', '#2e9e63' ),
		);
	}

	/**
	 * JavaScript that fills a placeholder with the status card.
	 *
	 * Builds the same markup as templates/single-product-status.php, plus a "ships now /
	 * on backorder" split when the shopper picks more than is in stock.
	 *
	 * @return string
	 */
	private static function card_script() {
		return "
			function wmbfRenderCard(el, status, now, later, strings) {
				var ns = 'http://www.w3.org/2000/svg';
				var color = status.color || '#8b8e94';

				function node(tag, className, text) {
					var n = document.createElement(tag);
					if (className) n.className = className;
					if (text) n.textContent = text;
					return n;
				}

				var icon = node('span', 'wmbf-status-card__icon');
				icon.setAttribute('aria-hidden', 'true');
				var svg = document.createElementNS(ns, 'svg');
				svg.setAttribute('viewBox', '0 0 24 24');
				var circle = document.createElementNS(ns, 'circle');
				circle.setAttribute('cx', '12');
				circle.setAttribute('cy', '12');
				circle.setAttribute('r', '9');
				var hands = document.createElementNS(ns, 'path');
				hands.setAttribute('d', 'M12 7v5l3 2');
				svg.appendChild(circle);
				svg.appendChild(hands);
				icon.appendChild(svg);

				var body = node('div', 'wmbf-status-card__body wc-cbs-backorder-badge-content');
				body.appendChild(node('strong', 'wmbf-status-card__title wc-cbs-backorder-badge-title', status.name));
				if (status.description) {
					body.appendChild(node('span', 'wmbf-status-card__text wc-cbs-backorder-badge-description', status.description));
				}

				if (now > 0 && later > 0) {
					var split = node('div', 'wmbf-status-card__split');
					var bar = node('div', 'wmbf-status-card__bar');
					bar.setAttribute('aria-hidden', 'true');
					var barNow = node('i', 'is-now');
					barNow.style.flexGrow = now;
					barNow.style.background = strings.nowColor;
					var barLater = node('i', 'is-later');
					barLater.style.flexGrow = later;
					bar.appendChild(barNow);
					bar.appendChild(barLater);
					var legend = node('span', 'wmbf-status-card__legend');
					var nowText = node('em', 'is-now', (1 === now ? strings.shipsNowOne : strings.shipsNowMany).replace('%d', now));
					nowText.style.setProperty('--wmbf-dot', strings.nowColor);
					legend.appendChild(nowText);
					legend.appendChild(node('em', 'is-later', strings.onBackorder.replace('%d', later)));
					split.appendChild(bar);
					split.appendChild(legend);
					body.appendChild(split);
				}

				el.textContent = '';
				el.style.setProperty('--wmbf-color', color);
				el.appendChild(icon);
				el.appendChild(body);
				el.style.display = el.classList.contains('wc-cbs-hidden-by-widget') ? 'none' : '';
			}

			// Units that ship now and units that wait, for a quantity.
			function wmbfSplit(managing, stock, quantity) {
				var qty = parseInt(quantity, 10) || 1;
				if (!managing) {
					return { now: 0, later: qty };
				}
				var inStock = Math.max(0, parseInt(stock, 10) || 0);
				return { now: Math.min(qty, inStock), later: Math.max(0, qty - inStock) };
			}
		";
	}

	/**
	 * Render placeholder for simple products with quantity checking.
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $status  Backorder status data.
	 */
	private function render_simple_product_placeholder( $product, $status ) {
		// Output the placeholder container.
		echo '<div class="wc-cbs-backorder-badge wmbf-status-card wc-cbs-backorder-badge-single wc-cbs-simple-placeholder wc-cbs-default-badge" style="display: none;" aria-live="polite"></div>';

		$script_data = array(
			'status'        => array(
				'name'        => $status['name'],
				'description' => isset( $status['description'] ) ? $status['description'] : '',
				'color'       => isset( $status['color'] ) ? $status['color'] : '',
			),
			'stockQuantity' => $product->get_stock_quantity(),
			'managingStock' => $product->managing_stock(),
			'strings'       => self::card_script_data(),
		);
		?>
		<script type="text/javascript">
		(function() {
			var data = <?php echo wp_json_encode( $script_data ); ?>;
			<?php echo self::card_script(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static script. ?>

			function updateBackorderDisplay() {
				var placeholder = document.querySelector('.wc-cbs-simple-placeholder');
				if (!placeholder) return;

				var qtyInput = document.querySelector('form.cart input.qty');
				var split = wmbfSplit(data.managingStock, data.stockQuantity, qtyInput ? qtyInput.value : 1);

				if (split.later > 0) {
					wmbfRenderCard(placeholder, data.status, split.now, split.later, data.strings);
				} else {
					placeholder.style.display = 'none';
				}
			}

			function init() {
				updateBackorderDisplay();

				var qtyInput = document.querySelector('form.cart input.qty');
				if (qtyInput) {
					qtyInput.addEventListener('change', updateBackorderDisplay);
					qtyInput.addEventListener('input', updateBackorderDisplay);
				}

				// Theme quantity buttons.
				document.querySelectorAll('.quantity .plus, .quantity .minus').forEach(function(btn) {
					btn.addEventListener('click', function() {
						setTimeout(updateBackorderDisplay, 50);
					});
				});
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', init);
			} else {
				init();
			}
		})();
		</script>
		<?php
	}

	/**
	 * Render placeholder for variable products that will be updated via JavaScript.
	 */
	private function render_variable_product_placeholder() {
		echo '<div class="wc-cbs-backorder-badge wmbf-status-card wc-cbs-backorder-badge-single wc-cbs-variable-placeholder wc-cbs-default-badge" style="display: none;" aria-live="polite"></div>';
		?>
		<script type="text/javascript">
		(function() {
			var strings = <?php echo wp_json_encode( self::card_script_data() ); ?>;
			var currentVariation = null;
			<?php echo self::card_script(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static script. ?>

			function updateBackorderDisplay() {
				var placeholder = document.querySelector('.wc-cbs-variable-placeholder');
				if (!placeholder) return;

				var status = currentVariation && currentVariation.wmbf_backorder_status;
				if (!status) {
					placeholder.style.display = 'none';
					return;
				}

				var variationsForm = document.querySelector('.variations_form');
				var qtyInput = variationsForm ? variationsForm.querySelector('input.qty') : null;
				var split = wmbfSplit(status.managing_stock, status.stock_quantity, qtyInput ? qtyInput.value : 1);

				if (split.later > 0) {
					wmbfRenderCard(placeholder, status, split.now, split.later, strings);
				} else {
					placeholder.style.display = 'none';
				}
			}

			function init() {
				var variationsForm = document.querySelector('.variations_form');
				if (!variationsForm) return;

				// WooCommerce fires these as jQuery events.
				if (window.jQuery) {
					jQuery('.variations_form').on('found_variation', function(event, variation) {
						currentVariation = variation;
						updateBackorderDisplay();
					});

					jQuery('.variations_form').on('reset_data', function() {
						currentVariation = null;
						updateBackorderDisplay();
					});
				}

				var qtyInput = variationsForm.querySelector('input.qty');
				if (qtyInput) {
					qtyInput.addEventListener('change', updateBackorderDisplay);
					qtyInput.addEventListener('input', updateBackorderDisplay);
				}

				// Theme quantity buttons.
				variationsForm.querySelectorAll('.quantity .plus, .quantity .minus').forEach(function(btn) {
					btn.addEventListener('click', function() {
						setTimeout(updateBackorderDisplay, 50);
					});
				});

				// A product with a single variation has it selected on page load.
				if (window.jQuery) {
					var variations = jQuery('.variations_form').data('product_variations');
					if (variations && variations.length === 1) {
						currentVariation = variations[0];
						updateBackorderDisplay();
					}
				}
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', init);
			} else {
				// Wait for jQuery and WooCommerce scripts
				setTimeout(init, 100);
			}
		})();
		</script>
		<?php
	}
}
