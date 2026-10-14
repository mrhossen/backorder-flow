<?php
/**
 * Plugin Name: Backorder Flow
 * Plugin URI: https://wpmagician.com/plugins/backorder-flow/
 * Description: Advanced backorder management with intelligent quantity-based displays and automatic cart grouping
 * Version: 1.0.0
 * Author: WPMagician
 * Author URI: https://wpmagician.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: backorder-flow
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 7.0
 * WC tested up to: 11.2
 *
 * @package Backorder_Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main WMBF_Plugin class.
 *
 * @class WMBF_Plugin
 * @version 1.0.0
 */
final class WMBF_Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * The single instance of the class.
	 *
	 * @var WMBF_Plugin
	 */
	protected static $_instance = null;

	/**
	 * Main WMBF_Plugin Instance.
	 *
	 * Ensures only one instance of WMBF_Plugin is loaded or can be loaded.
	 *
	 * @static
	 * @return WMBF_Plugin - Main instance.
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
		if ( ! defined( 'WMBF_VERSION' ) ) {
			define( 'WMBF_VERSION', $this->version );
		}
		if ( ! defined( 'WMBF_PLUGIN_FILE' ) ) {
			define( 'WMBF_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WMBF_PLUGIN_BASENAME' ) ) {
			define( 'WMBF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
		if ( ! defined( 'WMBF_PLUGIN_DIR' ) ) {
			define( 'WMBF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'WMBF_PLUGIN_URL' ) ) {
			define( 'WMBF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}
		if ( ! defined( 'WMBF_INCLUDES_DIR' ) ) {
			define( 'WMBF_INCLUDES_DIR', WMBF_PLUGIN_DIR . 'includes/' );
		}
		if ( ! defined( 'WMBF_ELEMENTOR_DIR' ) ) {
			define( 'WMBF_ELEMENTOR_DIR', WMBF_PLUGIN_DIR . 'elementor/' );
		}
		if ( ! defined( 'WMBF_ASSETS_URL' ) ) {
			define( 'WMBF_ASSETS_URL', WMBF_PLUGIN_URL . 'assets/' );
		}
		if ( ! defined( 'WMBF_TEMPLATES_DIR' ) ) {
			define( 'WMBF_TEMPLATES_DIR', WMBF_PLUGIN_DIR . 'templates/' );
		}
	}

	/**
	 * Include required core files.
	 */
	private function includes() {
		// Core classes.
		require_once WMBF_INCLUDES_DIR . 'class-admin-settings.php';
		require_once WMBF_INCLUDES_DIR . 'class-product-meta.php';
		require_once WMBF_INCLUDES_DIR . 'class-frontend-display.php';
		require_once WMBF_INCLUDES_DIR . 'class-cart-grouping.php';
		require_once WMBF_INCLUDES_DIR . 'class-email-handler.php';
		require_once WMBF_INCLUDES_DIR . 'class-order-meta.php';
		require_once WMBF_INCLUDES_DIR . 'class-shortcodes.php';
		require_once WMBF_INCLUDES_DIR . 'class-rest-api.php';
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
		add_filter( 'plugin_action_links_' . WMBF_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );

		// Activation and deactivation hooks.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Declare compatibility with WooCommerce features.
	 */
	public function declare_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WMBF_PLUGIN_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'orders_cache', WMBF_PLUGIN_FILE, true );
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
		$action = '';
		$label  = '';

		if ( file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ) {
			if ( current_user_can( 'activate_plugins' ) ) {
				$action = wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php' ), 'activate-plugin_woocommerce/woocommerce.php' );
				$label  = __( 'Activate WooCommerce', 'backorder-flow' );
			}
		} elseif ( current_user_can( 'install_plugins' ) ) {
			$action = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
			$label  = __( 'Install WooCommerce', 'backorder-flow' );
		}
		?>
		<div class="notice notice-error wmbf-notice">
			<img src="<?php echo esc_url( WMBF_ASSETS_URL . 'images/icon.svg' ); ?>" alt="" width="40" height="40">
			<div>
				<strong><?php esc_html_e( 'Backorder Flow needs WooCommerce', 'backorder-flow' ); ?></strong>
				<p><?php esc_html_e( 'Install and activate WooCommerce to start managing backorder statuses.', 'backorder-flow' ); ?></p>
			</div>
			<?php if ( $action ) : ?>
				<a class="button button-primary" href="<?php echo esc_url( $action ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Add Settings and Docs links to the plugins page.
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=backorder_status' ) ) . '">' . esc_html__( 'Settings', 'backorder-flow' ) . '</a>',
			'docs'     => '<a href="' . esc_url( apply_filters( 'wmbf_docs_url', 'https://wpmagician.com/plugins/backorder-flow/' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Docs', 'backorder-flow' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		if ( ! $this->check_dependencies() ) {
			return;
		}

		// Initialize classes.
		WMBF_Admin_Settings::instance();
		WMBF_Product_Meta::instance();
		WMBF_Frontend_Display::instance();
		WMBF_Cart_Grouping::instance();
		WMBF_Email_Handler::instance();
		WMBF_Order_Meta::instance();
		WMBF_Shortcodes::instance();
		WMBF_REST_API::instance();

		// Initialize Elementor integration if available. This runs on plugins_loaded, after
		// every plugin file is loaded, so it works whatever order the plugins load in.
		if ( did_action( 'elementor/loaded' ) ) {
			require_once WMBF_ELEMENTOR_DIR . 'class-elementor-integration.php';
			WMBF_Elementor_Integration::instance();
		}

		do_action( 'wmbf_loaded' );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function admin_scripts( $hook ) {
		// Admin styles. Every rule is scoped to Backorder Flow's own classes.
		wp_enqueue_style(
			'wmbf-admin',
			WMBF_ASSETS_URL . 'css/admin.css',
			array(),
			WMBF_VERSION
		);

		// The settings script is only needed on the WooCommerce settings screen.
		if ( false === strpos( $hook, 'wc-settings' ) ) {
			return;
		}

		// Admin script. The settings screen prints its data (statuses, strings, nonce) as JSON in the page.
		wp_enqueue_script(
			'wmbf-admin',
			WMBF_ASSETS_URL . 'js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			WMBF_VERSION,
			true
		);
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function frontend_scripts() {
		// Frontend styles.
		wp_enqueue_style(
			'wmbf-frontend',
			WMBF_ASSETS_URL . 'css/frontend.css',
			array(),
			WMBF_VERSION
		);

		// Frontend scripts.
		wp_enqueue_script(
			'wmbf-frontend',
			WMBF_ASSETS_URL . 'js/frontend.js',
			array(),
			WMBF_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'wmbf-frontend',
			'wmbfFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wmbf_frontend_nonce' ),
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
				esc_html__( 'Backorder Flow requires WooCommerce to be installed and active.', 'backorder-flow' ),
				esc_html__( 'Plugin Activation Error', 'backorder-flow' ),
				array( 'back_link' => true )
			);
		}

		// Set default options.
		if ( ! get_option( 'wmbf_statuses' ) ) {
			$default_statuses = array(
				array(
					'id'          => uniqid(),
					'name'        => __( 'Ships in 10 days', 'backorder-flow' ),
					'description' => __( 'This item will ship within 10 business days', 'backorder-flow' ),
					'color'       => '#ff9800',
					'priority'    => 1,
					'enabled'     => true,
				),
				array(
					'id'          => uniqid(),
					'name'        => __( 'Ships in 15 days', 'backorder-flow' ),
					'description' => __( 'This item will ship within 15 business days', 'backorder-flow' ),
					'color'       => '#f44336',
					'priority'    => 2,
					'enabled'     => true,
				),
			);
			update_option( 'wmbf_statuses', $default_statuses );
		}

		// Set plugin version.
		update_option( 'wmbf_version', WMBF_VERSION );

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
	 * Locate a template, allowing themes to override it.
	 *
	 * Themes can override a template by copying it to yourtheme/backorder-flow/{template_name}.
	 *
	 * @param string $template_name Template file name, e.g. 'cart-groups.php'.
	 * @return string Absolute path to the template file.
	 */
	public function locate_template( $template_name ) {
		$template = locate_template( array( 'backorder-flow/' . $template_name ) );

		if ( ! $template ) {
			$template = WMBF_TEMPLATES_DIR . $template_name;
		}

		return apply_filters( 'wmbf_locate_template', $template, $template_name );
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
 * Returns the main instance of WMBF_Plugin.
 *
 * @return WMBF_Plugin
 */
function WMBF() {
	return WMBF_Plugin::instance();
}

// Initialize the plugin.
WMBF();
