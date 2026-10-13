<?php
/**
 * Plugin Name: Custom Backorder Pro for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/custom-backorder-pro
 * Description: Advanced backorder management with intelligent quantity-based displays and automatic cart grouping
 * Version: 1.0.0
 * Author: mrhossen
 * Author URI: https://profiles.wordpress.org/mrhossen/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-backorder-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 *
 * @package Custom_Backorder_Pro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main WMCBP_Plugin class.
 *
 * @class WMCBP_Plugin
 * @version 1.0.0
 */
final class WMCBP_Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * The single instance of the class.
	 *
	 * @var WMCBP_Plugin
	 */
	protected static $_instance = null;

	/**
	 * Main WMCBP_Plugin Instance.
	 *
	 * Ensures only one instance of WMCBP_Plugin is loaded or can be loaded.
	 *
	 * @static
	 * @return WMCBP_Plugin - Main instance.
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
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants() {
		if ( ! defined( 'WMCBP_VERSION' ) ) {
			define( 'WMCBP_VERSION', $this->version );
		}
		if ( ! defined( 'WMCBP_PLUGIN_FILE' ) ) {
			define( 'WMCBP_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WMCBP_PLUGIN_BASENAME' ) ) {
			define( 'WMCBP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
		if ( ! defined( 'WMCBP_PLUGIN_DIR' ) ) {
			define( 'WMCBP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'WMCBP_PLUGIN_URL' ) ) {
			define( 'WMCBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}
		if ( ! defined( 'WMCBP_INCLUDES_DIR' ) ) {
			define( 'WMCBP_INCLUDES_DIR', WMCBP_PLUGIN_DIR . 'includes/' );
		}
		if ( ! defined( 'WMCBP_ELEMENTOR_DIR' ) ) {
			define( 'WMCBP_ELEMENTOR_DIR', WMCBP_PLUGIN_DIR . 'elementor/' );
		}
		if ( ! defined( 'WMCBP_ASSETS_URL' ) ) {
			define( 'WMCBP_ASSETS_URL', WMCBP_PLUGIN_URL . 'assets/' );
		}
		if ( ! defined( 'WMCBP_TEMPLATES_DIR' ) ) {
			define( 'WMCBP_TEMPLATES_DIR', WMCBP_PLUGIN_DIR . 'templates/' );
		}
	}

	/**
	 * Include required core files.
	 */
	private function includes() {
		// Core classes.
		require_once WMCBP_INCLUDES_DIR . 'class-admin-settings.php';
		require_once WMCBP_INCLUDES_DIR . 'class-product-meta.php';
		require_once WMCBP_INCLUDES_DIR . 'class-frontend-display.php';
		require_once WMCBP_INCLUDES_DIR . 'class-cart-grouping.php';
		require_once WMCBP_INCLUDES_DIR . 'class-email-handler.php';
		require_once WMCBP_INCLUDES_DIR . 'class-order-meta.php';
		require_once WMCBP_INCLUDES_DIR . 'class-shortcodes.php';
		require_once WMCBP_INCLUDES_DIR . 'class-rest-api.php';

		// Elementor integration (only if Elementor is active).
		if ( did_action( 'elementor/loaded' ) ) {
			require_once WMCBP_ELEMENTOR_DIR . 'class-elementor-integration.php';
		}
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		// Declare WooCommerce HPOS compatibility.
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );

		// Check dependencies.
		add_action( 'admin_init', array( $this, 'check_dependencies' ) );

		// Initialize plugin.
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );

		// Enqueue scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

		// Add settings link on plugins page.
		add_filter( 'plugin_action_links_' . WMCBP_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );

		// Activation and deactivation hooks.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Declare compatibility with WooCommerce features.
	 */
	public function declare_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WMCBP_PLUGIN_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'orders_cache', WMCBP_PLUGIN_FILE, true );
		}
	}

	/**
	 * Check plugin dependencies.
	 */
	public function check_dependencies() {
		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return false;
		}
		return true;
	}

	/**
	 * WooCommerce missing notice.
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'Custom Backorder Pro for WooCommerce requires WooCommerce to be installed and active.', 'custom-backorder-pro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add settings link to plugins page.
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=backorder_status' ) ) . '">' . esc_html__( 'Settings', 'custom-backorder-pro' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		if ( ! $this->check_dependencies() ) {
			return;
		}

		// Initialize classes.
		WMCBP_Admin_Settings::instance();
		WMCBP_Product_Meta::instance();
		WMCBP_Frontend_Display::instance();
		WMCBP_Cart_Grouping::instance();
		WMCBP_Email_Handler::instance();
		WMCBP_Order_Meta::instance();
		WMCBP_Shortcodes::instance();
		WMCBP_REST_API::instance();

		// Initialize Elementor integration if available.
		if ( did_action( 'elementor/loaded' ) && class_exists( 'WMCBP_Elementor_Integration' ) ) {
			WMCBP_Elementor_Integration::instance();
		}

		do_action( 'wmcbp_loaded' );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function admin_scripts( $hook ) {
		// Admin styles.
		wp_enqueue_style(
			'wmcbp-admin',
			WMCBP_ASSETS_URL . 'css/admin.css',
			array(),
			WMCBP_VERSION
		);

		// Admin scripts.
		wp_enqueue_script(
			'wmcbp-admin',
			WMCBP_ASSETS_URL . 'js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			WMCBP_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'wmcbp-admin',
			'wmcbpAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'wmcbp_admin_nonce' ),
				'i18n'      => array(
					'confirmDelete' => __( 'Are you sure you want to delete this status?', 'custom-backorder-pro' ),
					'error'         => __( 'An error occurred. Please try again.', 'custom-backorder-pro' ),
				),
			)
		);

		// Color picker.
		if ( strpos( $hook, 'wc-settings' ) !== false ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
		}
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function frontend_scripts() {
		// Frontend styles.
		wp_enqueue_style(
			'wmcbp-frontend',
			WMCBP_ASSETS_URL . 'css/frontend.css',
			array(),
			WMCBP_VERSION
		);

		// Frontend scripts.
		wp_enqueue_script(
			'wmcbp-frontend',
			WMCBP_ASSETS_URL . 'js/frontend.js',
			array(),
			WMCBP_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'wmcbp-frontend',
			'wmcbpFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wmcbp_frontend_nonce' ),
			)
		);
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		// Check dependencies.
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				esc_html__( 'Custom Backorder Pro for WooCommerce requires WooCommerce to be installed and active.', 'custom-backorder-pro' ),
				esc_html__( 'Plugin Activation Error', 'custom-backorder-pro' ),
				array( 'back_link' => true )
			);
		}

		// Set default options.
		if ( ! get_option( 'wmcbp_statuses' ) ) {
			$default_statuses = array(
				array(
					'id'          => uniqid(),
					'name'        => __( 'Ships in 10 days', 'custom-backorder-pro' ),
					'description' => __( 'This item will ship within 10 business days', 'custom-backorder-pro' ),
					'color'       => '#ff9800',
					'priority'    => 1,
					'enabled'     => true,
				),
				array(
					'id'          => uniqid(),
					'name'        => __( 'Ships in 15 days', 'custom-backorder-pro' ),
					'description' => __( 'This item will ship within 15 business days', 'custom-backorder-pro' ),
					'color'       => '#f44336',
					'priority'    => 2,
					'enabled'     => true,
				),
			);
			update_option( 'wmcbp_statuses', $default_statuses );
		}

		// Set plugin version.
		update_option( 'wmcbp_version', WMCBP_VERSION );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Get plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Get plugin URL.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}
}

/**
 * Returns the main instance of WMCBP_Plugin.
 *
 * @return WMCBP_Plugin
 */
function WMCBP() {
	return WMCBP_Plugin::instance();
}

// Initialize the plugin.
WMCBP();
