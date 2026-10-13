<?php
/**
 * Elementor Widget: Backorder Products
 *
 * @package Backorder_Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;

/**
 * Backorder Products Widget.
 */
class WMBF_Widget_Backorder_Products extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wc-cbs-backorder-products';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Backorder Products', 'backorder-flow' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'wc-cbs', 'woocommerce-elements' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'woocommerce', 'products', 'backorder', 'list', 'grid' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Content Section - Query.
		$this->start_controls_section(
			'section_query',
			array(
				'label' => __( 'Query', 'backorder-flow' ),
			)
		);

		$statuses = WMBF_Admin_Settings::instance()->get_statuses();
		$status_options = array( '' => __( 'Select Status', 'backorder-flow' ) );

		foreach ( $statuses as $status ) {
			if ( isset( $status['enabled'] ) && $status['enabled'] ) {
				$status_options[ $status['id'] ] = $status['name'];
			}
		}

		$this->add_control(
			'backorder_status',
			array(
				'label'   => __( 'Backorder Status', 'backorder-flow' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $status_options,
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'   => __( 'Products Per Page', 'backorder-flow' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 12,
				'min'     => 1,
				'max'     => 100,
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => __( 'Order By', 'backorder-flow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array(
					'date'  => __( 'Date', 'backorder-flow' ),
					'title' => __( 'Title', 'backorder-flow' ),
					'price' => __( 'Price', 'backorder-flow' ),
					'rand'  => __( 'Random', 'backorder-flow' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => __( 'Order', 'backorder-flow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array(
					'ASC'  => __( 'Ascending', 'backorder-flow' ),
					'DESC' => __( 'Descending', 'backorder-flow' ),
				),
			)
		);

		$this->end_controls_section();

		// Content Section - Layout.
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Layout', 'backorder-flow' ),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'backorder-flow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => array(
					'grid'     => __( 'Grid', 'backorder-flow' ),
					'list'     => __( 'List', 'backorder-flow' ),
					'carousel' => __( 'Carousel', 'backorder-flow' ),
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => __( 'Columns', 'backorder-flow' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '4',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'condition'      => array(
					'layout' => array( 'grid', 'carousel' ),
				),
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label'        => __( 'Show Image', 'backorder-flow' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'backorder-flow' ),
				'label_off'    => __( 'No', 'backorder-flow' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'        => __( 'Show Title', 'backorder-flow' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'backorder-flow' ),
				'label_off'    => __( 'No', 'backorder-flow' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => __( 'Show Price', 'backorder-flow' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'backorder-flow' ),
				'label_off'    => __( 'No', 'backorder-flow' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_add_to_cart',
			array(
				'label'        => __( 'Show Add to Cart', 'backorder-flow' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'backorder-flow' ),
				'label_off'    => __( 'No', 'backorder-flow' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_badge',
			array(
				'label'        => __( 'Show Backorder Badge', 'backorder-flow' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'backorder-flow' ),
				'label_off'    => __( 'No', 'backorder-flow' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'image_size',
				'default'   => 'medium',
				'condition' => array(
					'show_image' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Products.
		$this->start_controls_section(
			'section_product_style',
			array(
				'label' => __( 'Products', 'backorder-flow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'column_gap',
			array(
				'label'      => __( 'Column Gap', 'backorder-flow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wc-cbs-products-grid' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'product_border',
				'selector' => '{{WRAPPER}} .wc-cbs-product-item',
			)
		);

		$this->add_control(
			'product_padding',
			array(
				'label'      => __( 'Padding', 'backorder-flow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-cbs-product-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( empty( $settings['backorder_status'] ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Please select a backorder status in the widget settings.', 'backorder-flow' ) . '</p>';
			}
			return;
		}

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $settings['posts_per_page'],
			'orderby'        => $settings['orderby'],
			'order'          => $settings['order'],
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to filter products by backorder status for Elementor widget.
			'meta_query'     => array(
				array(
					'key'   => '_wmbf_backorder_status',
					'value' => $settings['backorder_status'],
				),
			),
		);

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'No products found with this backorder status.', 'backorder-flow' ) . '</p>';
			}
			return;
		}

		$columns = $settings['columns'];
		?>
		<div class="wc-cbs-products wc-cbs-layout-<?php echo esc_attr( $settings['layout'] ); ?>">
			<div class="wc-cbs-products-grid" style="grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr);">
				<?php
				while ( $query->have_posts() ) {
					$query->the_post();
					$product = wc_get_product( get_the_ID() );
					$this->render_product_item( $product, $settings );
				}
				wp_reset_postdata();
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render single product item.
	 *
	 * @param WC_Product $product  Product object.
	 * @param array      $settings Widget settings.
	 */
	private function render_product_item( $product, $settings ) {
		?>
		<div class="wc-cbs-product-item">
			<?php if ( 'yes' === $settings['show_image'] ) : ?>
				<div class="wc-cbs-product-image">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
						<?php echo wp_kses_post( $product->get_image( $settings['image_size_size'] ) ); ?>
					</a>
					<?php if ( 'yes' === $settings['show_badge'] ) : ?>
						<?php
						$status = WMBF_Product_Meta::get_product_backorder_status( $product );
						if ( $status ) :
							?>
							<span class="wc-cbs-product-badge" style="background-color: <?php echo esc_attr( $status['color'] ); ?>;">
								<?php echo esc_html( $status['name'] ); ?>
							</span>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( 'yes' === $settings['show_title'] ) : ?>
				<h3 class="wc-cbs-product-title">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
						<?php echo esc_html( $product->get_name() ); ?>
					</a>
				</h3>
			<?php endif; ?>

			<?php if ( 'yes' === $settings['show_price'] ) : ?>
				<div class="wc-cbs-product-price">
					<?php echo wp_kses_post( $product->get_price_html() ); ?>
				</div>
			<?php endif; ?>

			<?php if ( 'yes' === $settings['show_add_to_cart'] ) : ?>
				<div class="wc-cbs-product-add-to-cart">
					<?php woocommerce_template_loop_add_to_cart(); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
