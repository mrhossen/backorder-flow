<?php
/**
 * Elementor Integration
 *
 * Handles Elementor widgets registration and integration.
 *
 * @package Backorder_Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WMBF_Elementor_Integration class.
 */
class WMBF_Elementor_Integration {

	/**
	 * The single instance of the class.
	 *
	 * @var WMBF_Elementor_Integration
	 */
	protected static $_instance = null;

	/**
	 * Main instance.
	 *
	 * @return WMBF_Elementor_Integration
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
		// Only initialize if Elementor is loaded
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		// Register custom category.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_widget_category' ) );

		// Register widgets.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Enqueue Elementor widget styles and scripts.
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_widget_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'enqueue_widget_scripts' ) );

		// Register dynamic tags.
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_dynamic_tags' ) );
	}

	/**
	 * Register custom widget category.
	 *
	 * @param object $elements_manager Elementor elements manager.
	 */
	public function register_widget_category( $elements_manager ) {
		$elements_manager->add_category(
			'wc-cbs',
			array(
				'title' => __( 'WPMagician', 'backorder-flow' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * Register widgets.
	 *
	 * @param object $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets( $widgets_manager ) {
		// Check if Elementor Widget_Base class exists
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		// Load widget classes.
		require_once WMBF_ELEMENTOR_DIR . 'widgets/class-widget-product-backorder-status.php';
		require_once WMBF_ELEMENTOR_DIR . 'widgets/class-widget-cart-backorder-groups.php';
		require_once WMBF_ELEMENTOR_DIR . 'widgets/class-widget-backorder-products.php';
		require_once WMBF_ELEMENTOR_DIR . 'widgets/class-widget-backorder-badge.php';

		// Register widgets - register() is the Elementor 3.5.0+ API, register_widget_type() is the deprecated one.
		$widgets = array(
			new WMBF_Widget_Product_Backorder_Status(),
			new WMBF_Widget_Cart_Backorder_Groups(),
			new WMBF_Widget_Backorder_Products(),
			new WMBF_Widget_Backorder_Badge(),
		);

		foreach ( $widgets as $widget ) {
			if ( method_exists( $widgets_manager, 'register' ) ) {
				$widgets_manager->register( $widget );
			} elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
				$widgets_manager->register_widget_type( $widget );
			}
		}
	}

	/**
	 * Enqueue widget styles.
	 */
	public function enqueue_widget_styles() {
		wp_enqueue_style(
			'wc-cbs-elementor-widgets',
			WMBF_ASSETS_URL . 'css/elementor-widgets.css',
			array(),
			WMBF_VERSION
		);
	}

	/**
	 * Enqueue widget scripts.
	 */
	public function enqueue_widget_scripts() {
		wp_register_script(
			'wc-cbs-elementor-widgets',
			WMBF_ASSETS_URL . 'js/elementor-widgets.js',
			array( 'jquery' ),
			WMBF_VERSION,
			true
		);
	}

	/**
	 * Register dynamic tags.
	 *
	 * @param object $dynamic_tags_manager Dynamic tags manager.
	 */
	public function register_dynamic_tags( $dynamic_tags_manager ) {
		// Check if required classes exist
		if ( ! class_exists( '\Elementor\Core\DynamicTags\Tag' ) ) {
			return;
		}

		require_once WMBF_ELEMENTOR_DIR . 'dynamic-tags/class-backorder-status-tag.php';

		// Register tag - register() is the Elementor 3.5.0+ API, register_tag() is the deprecated one.
		if ( method_exists( $dynamic_tags_manager, 'register' ) ) {
			$dynamic_tags_manager->register( new WMBF_Backorder_Status_Tag() );
		} elseif ( method_exists( $dynamic_tags_manager, 'register_tag' ) ) {
			$dynamic_tags_manager->register_tag( 'WMBF_Backorder_Status_Tag' );
		}
	}
}
