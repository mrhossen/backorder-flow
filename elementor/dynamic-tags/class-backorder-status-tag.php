<?php
/**
 * Elementor Dynamic Tag: Backorder Status
 *
 * @package Backorder_Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Core\DynamicTags\Tag;

/**
 * Backorder Status Dynamic Tag.
 */
class WMBF_Backorder_Status_Tag extends Tag {

	/**
	 * Get tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wc-cbs-backorder-status';
	}

	/**
	 * Get tag title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Product Backorder Status', 'backorder-flow' );
	}

	/**
	 * Get tag group.
	 *
	 * @return string|array
	 */
	public function get_group() {
		// Handle different Elementor versions
		if ( class_exists( '\Elementor\Modules\DynamicTags\Module' ) ) {
			$module = '\Elementor\Modules\DynamicTags\Module';
			if ( defined( "{$module}::POST_GROUP" ) ) {
				return constant( "{$module}::POST_GROUP" );
			}
		}
		// Fallback to string for compatibility
		return 'post';
	}

	/**
	 * Get tag categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		// Handle different Elementor versions
		if ( class_exists( '\Elementor\Modules\DynamicTags\Module' ) ) {
			$module = '\Elementor\Modules\DynamicTags\Module';
			if ( defined( "{$module}::TEXT_CATEGORY" ) ) {
				return array( constant( "{$module}::TEXT_CATEGORY" ) );
			}
		}
		// Fallback for compatibility
		return array( 'text' );
	}

	/**
	 * Render tag output.
	 */
	public function render() {
		global $product;

		$wmbf_product = $product;
		if ( ! $wmbf_product ) {
			$wmbf_product = wc_get_product( get_the_ID() );
		}

		if ( ! $wmbf_product ) {
			return;
		}

		$status = WMBF_Product_Meta::get_product_backorder_status( $wmbf_product );

		if ( ! $status ) {
			return;
		}

		echo esc_html( $status['name'] );
	}
}
