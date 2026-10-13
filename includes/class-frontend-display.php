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
		if ( ! $product->backorders_allowed() ) {
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

		// For simple products, also use placeholder with quantity checking
		if ( ! $this->should_display_backorder_status( $product ) ) {
			return;
		}

		$status = WMBF_Product_Meta::get_product_backorder_status( $product );

		if ( ! $status ) {
			return;
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
		static $rendered = false;
		return $rendered;
	}

	/**
	 * Mark that Elementor widget has been rendered.
	 */
	public static function set_elementor_widget_rendered() {
		static $rendered;
		$rendered = true;
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

		if ( ! $product ) {
			return $product_name;
		}

		if ( ! $this->should_display_backorder_status( $product ) ) {
			return $product_name;
		}

		$status = WMBF_Product_Meta::get_product_backorder_status( $product );

		if ( ! $status ) {
			return $product_name;
		}

		// Don't show here - will be shown in grouped cart display.
		return $product_name;
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
		if ( ! $product ) {
			return $availability;
		}

		// Check if this product has a custom backorder status
		$status = WMBF_Product_Meta::get_product_backorder_status( $product );

		// If it has a custom status and we're showing it, hide the default WooCommerce message
		if ( $status && $this->should_display_backorder_status( $product ) ) {
			// Return empty string to suppress default WooCommerce availability text
			return '';
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

		// Check if this product has a custom backorder status
		$status = WMBF_Product_Meta::get_product_backorder_status( $product );

		// If it has a custom status and we're showing it, suppress the default HTML
		if ( $status && $this->should_display_backorder_status( $product ) ) {
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

		// Check if product allows backorders.
		if ( ! $product->backorders_allowed() ) {
			return false;
		}

		// Show the backorder status if:
		// 1. Product is out of stock, OR
		// 2. Product allows backorders (even if currently in stock)
		// This allows customers to know about backorder policies upfront
		return true;
	}

	/**
	 * Render backorder badge.
	 *
	 * @param array  $status  Status data.
	 * @param string $context Display context (shop, single, cart, checkout).
	 */
	private function render_backorder_badge( $status, $context = 'single' ) {
		$template_path = WMBF_TEMPLATES_DIR . 'single-product-status.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			// Fallback inline template.
			$color = isset( $status['color'] ) ? $status['color'] : '#ff9800';
			$name = isset( $status['name'] ) ? $status['name'] : '';
			$description = isset( $status['description'] ) ? $status['description'] : '';
			// Add a class to identify this as default (non-Elementor) badge
			$extra_class = ( $context === 'single' ) ? ' wc-cbs-default-badge' : '';
			?>
			<div class="wc-cbs-backorder-badge wc-cbs-backorder-badge-<?php echo esc_attr( $context ); ?><?php echo esc_attr( $extra_class ); ?>" style="border-color: <?php echo esc_attr( $color ); ?>;">
				<span class="wc-cbs-backorder-badge-icon" style="background-color: <?php echo esc_attr( $color ); ?>;"></span>
				<div class="wc-cbs-backorder-badge-content">
					<strong class="wc-cbs-backorder-badge-title"><?php echo esc_html( $name ); ?></strong>
					<?php if ( $description ) : ?>
						<span class="wc-cbs-backorder-badge-description"><?php echo esc_html( $description ); ?></span>
					<?php endif; ?>
				</div>
			</div>
			<?php
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

		if ( ! $product->backorders_allowed() ) {
			return false;
		}

		$stock_quantity = $product->get_stock_quantity();

		// If managing stock and quantity exceeds available stock.
		if ( $product->managing_stock() && $stock_quantity !== null ) {
			return $quantity > $stock_quantity;
		}

		// If not in stock.
		if ( ! $product->is_in_stock() ) {
			return true;
		}

		return false;
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
			return $product->is_in_stock() ? 0 : $quantity;
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
			return $product->is_in_stock() ? $quantity : 0;
		}

		$stock_quantity = $product->get_stock_quantity();

		if ( $stock_quantity === null ) {
			return $quantity;
		}

		return min( $quantity, max( 0, $stock_quantity ) );
	}

	/**
	 * Render placeholder for simple products with quantity checking.
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $status  Backorder status data.
	 */
	private function render_simple_product_placeholder( $product, $status ) {
		$stock_quantity = $product->get_stock_quantity();
		$managing_stock = $product->managing_stock();
		$color          = isset( $status['color'] ) ? $status['color'] : '#ff9800';

		// Output the placeholder container.
		echo '<div class="wc-cbs-backorder-badge wc-cbs-backorder-badge-single wc-cbs-simple-placeholder wc-cbs-default-badge" style="display: none;"></div>';

		// Prepare data for inline script.
		$script_data = array(
			'status'         => $status,
			'stockQuantity'  => $stock_quantity,
			'managingStock'  => $managing_stock,
		);
		?>
		<script type="text/javascript">
		(function() {
			var backorderStatus = <?php echo wp_json_encode( $script_data['status'] ); ?>;
			var stockQuantity = <?php echo wp_json_encode( $script_data['stockQuantity'] ); ?>;
			var managingStock = <?php echo wp_json_encode( $script_data['managingStock'] ); ?>;

			function shouldShowBackorderMessage(quantity) {
				// Always show if not managing stock but backorders allowed (means it's on backorder)
				if (!managingStock) {
					return true;
				}

				var stockQty = parseInt(stockQuantity) || 0;
				var selectedQty = parseInt(quantity) || 1;

				// Show message if:
				// 1. Stock is 0 or less (out of stock), OR
				// 2. Selected quantity exceeds available stock
				return (stockQty <= 0 || selectedQty > stockQty);
			}

			function updateBackorderDisplay() {
				var placeholder = document.querySelector('.wc-cbs-simple-placeholder');
				if (!placeholder) return;

				var qtyInput = document.querySelector('form.cart input.qty');
				var quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

				if (shouldShowBackorderMessage(quantity)) {
					var color = backorderStatus.color || '#ff9800';

					var html = '<span class="wc-cbs-backorder-badge-icon" style="background-color: ' + color + ';"></span>';
					html += '<div class="wc-cbs-backorder-badge-content">';
					html += '<strong class="wc-cbs-backorder-badge-title">' + backorderStatus.name + '</strong>';
					if (backorderStatus.description) {
						html += '<span class="wc-cbs-backorder-badge-description">' + backorderStatus.description + '</span>';
					}
					html += '</div>';

					placeholder.innerHTML = html;
					placeholder.style.borderColor = color;
					placeholder.style.display = 'inline-flex';
				} else {
					placeholder.style.display = 'none';
				}
			}

			// Run when DOM is ready
			function init() {
				updateBackorderDisplay();

				// Listen for quantity changes
				var qtyInput = document.querySelector('form.cart input.qty');
				if (qtyInput) {
					qtyInput.addEventListener('change', updateBackorderDisplay);
					qtyInput.addEventListener('input', updateBackorderDisplay);
				}

				// Also listen for WooCommerce quantity button clicks
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
		echo '<div class="wc-cbs-backorder-badge wc-cbs-backorder-badge-single wc-cbs-variable-placeholder wc-cbs-default-badge" style="display: none;"></div>';

		?>
		<script type="text/javascript">
		(function() {
			var currentVariation = null;

			function shouldShowBackorderMessage(variation, quantity) {
				if (!variation || !variation.wmbf_backorder_status) {
					return false;
				}

				var status = variation.wmbf_backorder_status;

				// Always show if backorder status exists and backorders allowed
				if (!status.managing_stock) {
					return true;
				}

				var stockQty = parseInt(status.stock_quantity) || 0;
				var selectedQty = parseInt(quantity) || 1;

				// Show message if:
				// 1. Stock is 0 or less (out of stock), OR
				// 2. Selected quantity exceeds available stock
				return (stockQty <= 0 || selectedQty > stockQty);
			}

			function updateBackorderDisplay() {
				var placeholder = document.querySelector('.wc-cbs-variable-placeholder');
				if (!placeholder) return;

				if (!currentVariation || !currentVariation.wmbf_backorder_status) {
					placeholder.style.display = 'none';
					return;
				}

				var variationsForm = document.querySelector('.variations_form');
				var qtyInput = variationsForm ? variationsForm.querySelector('input.qty') : null;
				var quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

				if (shouldShowBackorderMessage(currentVariation, quantity)) {
					var status = currentVariation.wmbf_backorder_status;
					var color = status.color || '#ff9800';

					var html = '<span class="wc-cbs-backorder-badge-icon" style="background-color: ' + color + ';"></span>';
					html += '<div class="wc-cbs-backorder-badge-content">';
					html += '<strong class="wc-cbs-backorder-badge-title">' + status.name + '</strong>';
					if (status.description) {
						html += '<span class="wc-cbs-backorder-badge-description">' + status.description + '</span>';
					}
					html += '</div>';

					placeholder.innerHTML = html;
					placeholder.style.borderColor = color;
					placeholder.style.display = 'inline-flex';
				} else {
					placeholder.style.display = 'none';
				}
			}

			function init() {
				var variationsForm = document.querySelector('.variations_form');
				if (!variationsForm) return;

				// Listen for variation changes using jQuery events (WooCommerce uses jQuery)
				if (window.jQuery) {
					jQuery('.variations_form').on('found_variation', function(event, variation) {
						currentVariation = variation;
						updateBackorderDisplay();
					});

					jQuery('.variations_form').on('reset_data', function() {
						currentVariation = null;
						var placeholder = document.querySelector('.wc-cbs-variable-placeholder');
						if (placeholder) {
							placeholder.style.display = 'none';
						}
					});
				}

				// Listen for quantity changes
				var qtyInput = variationsForm.querySelector('input.qty');
				if (qtyInput) {
					qtyInput.addEventListener('change', updateBackorderDisplay);
					qtyInput.addEventListener('input', updateBackorderDisplay);
				}

				// Also listen for WooCommerce quantity button clicks
				variationsForm.querySelectorAll('.quantity .plus, .quantity .minus').forEach(function(btn) {
					btn.addEventListener('click', function() {
						setTimeout(updateBackorderDisplay, 50);
					});
				});

				// Check if variation is already selected on page load (single variation product)
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
