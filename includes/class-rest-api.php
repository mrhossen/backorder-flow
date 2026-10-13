<?php
/**
 * REST API
 *
 * Provides REST API endpoints for backorder functionality.
 *
 * @package Backorder_Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WMBF_REST_API class.
 */
class WMBF_REST_API {

	/**
	 * The single instance of the class.
	 *
	 * @var WMBF_REST_API
	 */
	protected static $_instance = null;

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'wmbf/v1';

	/**
	 * Main instance.
	 *
	 * @return WMBF_REST_API
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// Get all backorder statuses.
		register_rest_route(
			$this->namespace,
			'/statuses',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_statuses' ),
				'permission_callback' => array( $this, 'public_permission_callback' ),
			)
		);

		// Get single backorder status.
		register_rest_route(
			$this->namespace,
			'/statuses/(?P<id>[a-zA-Z0-9]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this, 'public_permission_callback' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_string( $param );
						},
					),
				),
			)
		);

		// Get product backorder status.
		register_rest_route(
			$this->namespace,
			'/products/(?P<id>\d+)/backorder-status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_product_backorder_status' ),
				'permission_callback' => array( $this, 'public_permission_callback' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Get cart groups.
		register_rest_route(
			$this->namespace,
			'/cart/groups',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_cart_groups' ),
				'permission_callback' => array( $this, 'public_permission_callback' ),
			)
		);

		// Get products by backorder status.
		register_rest_route(
			$this->namespace,
			'/products/by-status/(?P<status_id>[a-zA-Z0-9]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_products_by_status' ),
				'permission_callback' => array( $this, 'public_permission_callback' ),
				'args'                => array(
					'status_id' => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_string( $param );
						},
					),
					'per_page'  => array(
						'default'           => 10,
						'validate_callback' => function( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
					),
					'page'      => array(
						'default'           => 1,
						'validate_callback' => function( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
					),
				),
			)
		);
	}

	/**
	 * Public permission callback.
	 *
	 * @return bool
	 */
	public function public_permission_callback() {
		return true;
	}

	/**
	 * Get all backorder statuses.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_statuses( $request ) {
		$statuses = WMBF_Admin_Settings::instance()->get_statuses();

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $statuses,
		) );
	}

	/**
	 * Get single backorder status.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_status( $request ) {
		$status_id = $request->get_param( 'id' );
		$status = WMBF_Admin_Settings::instance()->get_status( $status_id );

		if ( ! $status ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Status not found.', 'backorder-flow' ),
				),
				404
			);
		}

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $status,
		) );
	}

	/**
	 * Get product backorder status.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_product_backorder_status( $request ) {
		$product_id = $request->get_param( 'id' );
		$product = wc_get_product( $product_id );

		if ( ! $product || 'publish' !== get_post_status( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Product not found.', 'backorder-flow' ),
				),
				404
			);
		}

		$status = WMBF_Product_Meta::get_product_backorder_status( $product );

		return rest_ensure_response( array(
			'success'            => true,
			'data'               => $status,
			'accepts_backorders' => WMBF_Frontend_Display::accepts_backorders( $product ),
			'on_backorder'       => WMBF_Frontend_Display::is_currently_on_backorder( $product ),
			'stock_quantity'     => $product->get_stock_quantity(),
		) );
	}

	/**
	 * Get cart groups.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_cart_groups( $request ) {
		WMBF_Cart_Grouping::maybe_load_cart();

		if ( ! WC()->cart ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => __( 'Cart not initialized.', 'backorder-flow' ),
			) );
		}

		$groups = WMBF_Cart_Grouping::get_groups();

		// Simplify data for API response.
		$simplified_groups = array();
		foreach ( $groups as $group ) {
			$items = array();
			foreach ( $group['items'] as $item ) {
				$items[] = array(
					'product_id'   => $item['product']->get_id(),
					'product_name' => $item['product']->get_name(),
					'quantity'     => $item['quantity'],
					'total_qty'    => $item['total_qty'],
					'is_partial'   => $item['is_partial'],
				);
			}

			$simplified_groups[] = array(
				'id'          => $group['id'],
				'name'        => $group['name'],
				'description' => $group['description'],
				'color'       => $group['color'],
				'priority'    => $group['priority'],
				'items'       => $items,
			);
		}

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $simplified_groups,
		) );
	}

	/**
	 * Get products by backorder status.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_products_by_status( $request ) {
		$status_id = $request->get_param( 'status_id' );
		$per_page = min( 100, absint( $request->get_param( 'per_page' ) ) );
		$page = absint( $request->get_param( 'page' ) );

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to filter products by backorder status via REST API.
			'meta_query'     => array(
				array(
					'key'   => '_wmbf_backorder_status',
					'value' => $status_id,
				),
			),
		);

		$query = new WP_Query( $args );
		$products = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( $product ) {
					$products[] = array(
						'id'              => $product->get_id(),
						'name'            => $product->get_name(),
						'price'           => $product->get_price(),
						'regular_price'   => $product->get_regular_price(),
						'sale_price'      => $product->get_sale_price(),
						'stock_quantity'  => $product->get_stock_quantity(),
						'stock_status'    => $product->get_stock_status(),
						'permalink'       => get_permalink( $product->get_id() ),
						'image_url'       => wp_get_attachment_url( $product->get_image_id() ),
					);
				}
			}
			wp_reset_postdata();
		}

		return rest_ensure_response( array(
			'success'    => true,
			'data'       => $products,
			'pagination' => array(
				'total'       => $query->found_posts,
				'total_pages' => $query->max_num_pages,
				'current_page' => $page,
				'per_page'    => $per_page,
			),
		) );
	}
}
