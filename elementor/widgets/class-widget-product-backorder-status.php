<?php
/**
 * Elementor Widget: Product Backorder Status
 *
 * @package Custom_Backorder_Pro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

/**
 * Product Backorder Status Widget.
 */
class WMCBP_Widget_Product_Backorder_Status extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wc-cbs-product-backorder-status';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Product Backorder Status', 'custom-backorder-pro' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-product-info';
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
		return array( 'woocommerce', 'backorder', 'status', 'product', 'stock' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Content Section.
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'custom-backorder-pro' ),
			)
		);

		$this->add_control(
			'style',
			array(
				'label'   => __( 'Display Style', 'custom-backorder-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'banner',
				'options' => array(
					'banner' => __( 'Banner', 'custom-backorder-pro' ),
					'text'   => __( 'Text Only', 'custom-backorder-pro' ),
				),
			)
		);

		$this->add_control(
			'show_icon',
			array(
				'label'        => __( 'Show Icon', 'custom-backorder-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'custom-backorder-pro' ),
				'label_off'    => __( 'No', 'custom-backorder-pro' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'style!' => 'text',
				),
			)
		);

		$this->add_control(
			'icon',
			array(
				'label'     => __( 'Icon', 'custom-backorder-pro' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-clock',
					'library' => 'fa-solid',
				),
				'condition' => array(
					'show_icon' => 'yes',
					'style!'    => 'text',
				),
			)
		);

		$this->add_control(
			'show_description',
			array(
				'label'        => __( 'Show Description', 'custom-backorder-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'custom-backorder-pro' ),
				'label_off'    => __( 'No', 'custom-backorder-pro' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_stock_quantity',
			array(
				'label'        => __( 'Show Stock Quantity', 'custom-backorder-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'custom-backorder-pro' ),
				'label_off'    => __( 'No', 'custom-backorder-pro' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->end_controls_section();

		// Style Section - Title.
		$this->start_controls_section(
			'section_title_style',
			array(
				'label' => __( 'Title', 'custom-backorder-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .wc-cbs-backorder-badge-title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Color', 'custom-backorder-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wc-cbs-backorder-badge-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Description.
		$this->start_controls_section(
			'section_description_style',
			array(
				'label'     => __( 'Description', 'custom-backorder-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_description' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .wc-cbs-backorder-badge-description',
			)
		);

		$this->add_control(
			'description_color',
			array(
				'label'     => __( 'Color', 'custom-backorder-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wc-cbs-backorder-badge-description' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Container.
		$this->start_controls_section(
			'section_container_style',
			array(
				'label' => __( 'Container', 'custom-backorder-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'container_background',
			array(
				'label'     => __( 'Background Color', 'custom-backorder-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wc-cbs-backorder-badge' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .wc-cbs-backorder-badge',
			)
		);

		$this->add_control(
			'container_border_radius',
			array(
				'label'      => __( 'Border Radius', 'custom-backorder-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-cbs-backorder-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'container_padding',
			array(
				'label'      => __( 'Padding', 'custom-backorder-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-cbs-backorder-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'container_box_shadow',
				'selector' => '{{WRAPPER}} .wc-cbs-backorder-badge',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 */
	protected function render() {
		// Set flag to prevent default display
		WMCBP_Frontend_Display::set_elementor_widget_rendered();

		// Ensure jQuery is enqueued.
		wp_enqueue_script( 'jquery' );

		// Hide any default badges that may have already rendered
		$inline_script = "
		(function() {
			// Hide default single product badges when Elementor widget is used
			var singleBadges = document.querySelectorAll('.wc-cbs-backorder-badge-single');
			singleBadges.forEach(function(badge) {
				badge.style.display = 'none';
			});
			// Also hide generic default badges
			var defaultBadges = document.querySelectorAll('.wc-cbs-default-badge');
			defaultBadges.forEach(function(badge) {
				badge.style.display = 'none';
			});
		})();
		";

		wp_add_inline_script( 'jquery', $inline_script );

		global $product;

		$settings = $this->get_settings_for_display();

		// Get current product.
		$wmcbp_product = $product;
		if ( ! $wmcbp_product ) {
			$wmcbp_product = wc_get_product( get_the_ID() );
		}

		if ( ! $wmcbp_product ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'This widget displays backorder status on product pages.', 'custom-backorder-pro' ) . '</p>';
			}
			return;
		}

		// Get backorder status.
		$status = WMCBP_Product_Meta::get_product_backorder_status( $wmcbp_product );

		if ( ! $status ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'No backorder status set for this product.', 'custom-backorder-pro' ) . '</p>';
			}
			return;
		}

		$color = isset( $status['color'] ) ? $status['color'] : '#ff9800';
		$name = isset( $status['name'] ) ? $status['name'] : '';
		$description = isset( $status['description'] ) ? $status['description'] : '';

		?>
		<div class="wc-cbs-backorder-badge wc-cbs-elementor-widget wc-cbs-style-<?php echo esc_attr( $settings['style'] ); ?>">
			<?php if ( 'yes' === $settings['show_icon'] && 'text' !== $settings['style'] ) : ?>
				<span class="wc-cbs-backorder-badge-icon" style="color: <?php echo esc_attr( $color ); ?>;">
					<?php \Elementor\Icons_Manager::render_icon( $settings['icon'], array( 'aria-hidden' => 'true' ) ); ?>
				</span>
			<?php endif; ?>
			<div class="wc-cbs-backorder-badge-content">
				<strong class="wc-cbs-backorder-badge-title"><?php echo esc_html( $name ); ?></strong>
				<?php if ( 'yes' === $settings['show_description'] && $description ) : ?>
					<span class="wc-cbs-backorder-badge-description"><?php echo esc_html( $description ); ?></span>
				<?php endif; ?>
				<?php if ( 'yes' === $settings['show_stock_quantity'] ) : ?>
					<span class="wc-cbs-stock-quantity">
						<?php
						$stock = $product->get_stock_quantity();
						if ( $stock !== null ) {
							/* translators: %d: stock quantity */
							printf( esc_html__( '%d in stock', 'custom-backorder-pro' ), (int) $stock );
						}
						?>
					</span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
