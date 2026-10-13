<?php
/**
 * Elementor Widget: Backorder Badge
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
use Elementor\Group_Control_Box_Shadow;

/**
 * Backorder Badge Widget.
 */
class WMBF_Widget_Backorder_Badge extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wc-cbs-backorder-badge';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Backorder Badge', 'backorder-flow' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-price-table';
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
		return array( 'woocommerce', 'backorder', 'badge', 'label', 'tag' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Content Section.
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'backorder-flow' ),
			)
		);

		$this->add_control(
			'product_id',
			array(
				'label'       => __( 'Product ID', 'backorder-flow' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 0,
				'description' => __( 'Leave 0 to use current product. Enter product ID to display specific product badge.', 'backorder-flow' ),
			)
		);

		$this->add_control(
			'badge_style',
			array(
				'label'   => __( 'Badge Style', 'backorder-flow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'flat',
				'options' => array(
					'flat'    => __( 'Flat', 'backorder-flow' ),
					'rounded' => __( 'Rounded', 'backorder-flow' ),
					'pill'    => __( 'Pill', 'backorder-flow' ),
					'custom'  => __( 'Custom', 'backorder-flow' ),
				),
			)
		);

		$this->add_control(
			'badge_size',
			array(
				'label'   => __( 'Size', 'backorder-flow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'medium',
				'options' => array(
					'small'  => __( 'Small', 'backorder-flow' ),
					'medium' => __( 'Medium', 'backorder-flow' ),
					'large'  => __( 'Large', 'backorder-flow' ),
				),
			)
		);

		$this->add_control(
			'show_icon',
			array(
				'label'        => __( 'Show Icon', 'backorder-flow' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'backorder-flow' ),
				'label_off'    => __( 'No', 'backorder-flow' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'icon',
			array(
				'label'     => __( 'Icon', 'backorder-flow' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-clock',
					'library' => 'fa-solid',
				),
				'condition' => array(
					'show_icon' => 'yes',
				),
			)
		);

		$this->add_control(
			'animation_on_hover',
			array(
				'label'        => __( 'Animation on Hover', 'backorder-flow' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'backorder-flow' ),
				'label_off'    => __( 'No', 'backorder-flow' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->end_controls_section();

		// Style Section - Badge.
		$this->start_controls_section(
			'section_badge_style',
			array(
				'label' => __( 'Badge', 'backorder-flow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'badge_background',
			array(
				'label'     => __( 'Background Color', 'backorder-flow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wc-cbs-badge' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_text_color',
			array(
				'label'     => __( 'Text Color', 'backorder-flow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wc-cbs-badge' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'badge_typography',
				'selector' => '{{WRAPPER}} .wc-cbs-badge',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'badge_border',
				'selector' => '{{WRAPPER}} .wc-cbs-badge',
			)
		);

		$this->add_control(
			'badge_border_radius',
			array(
				'label'      => __( 'Border Radius', 'backorder-flow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-cbs-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array(
					'badge_style' => 'custom',
				),
			)
		);

		$this->add_control(
			'badge_padding',
			array(
				'label'      => __( 'Padding', 'backorder-flow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-cbs-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'badge_box_shadow',
				'selector' => '{{WRAPPER}} .wc-cbs-badge',
			)
		);

		$this->end_controls_section();

		// Style Section - Icon.
		$this->start_controls_section(
			'section_icon_style',
			array(
				'label'     => __( 'Icon', 'backorder-flow' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_icon' => 'yes',
				),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Color', 'backorder-flow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wc-cbs-badge-icon' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'icon_size',
			array(
				'label'      => __( 'Size', 'backorder-flow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 10,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wc-cbs-badge-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'icon_spacing',
			array(
				'label'      => __( 'Spacing', 'backorder-flow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wc-cbs-badge-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
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

		// Get product ID.
		$product_id = $settings['product_id'] ? $settings['product_id'] : get_the_ID();
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Product not found. This widget displays backorder badge.', 'backorder-flow' ) . '</p>';
			}
			return;
		}

		// Get backorder status. It only applies to products that can be ordered on backorder.
		$status = WMBF_Frontend_Display::accepts_backorders( $product ) ? WMBF_Product_Meta::get_product_backorder_status( $product ) : null;

		if ( ! $status ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'No backorder status set for this product.', 'backorder-flow' ) . '</p>';
			}
			return;
		}

		// When the badge shows the current product's status, the default badge is not needed.
		if ( is_product() && (int) get_queried_object_id() === (int) $product->get_id() ) {
			WMBF_Frontend_Display::set_elementor_widget_rendered();
			WMBF_Frontend_Display::print_hide_default_badge_script();
		}

		$badge_classes = array(
			'wc-cbs-badge',
			'wc-cbs-badge-' . $settings['badge_style'],
			'wc-cbs-badge-' . $settings['badge_size'],
		);

		if ( 'yes' === $settings['animation_on_hover'] ) {
			$badge_classes[] = 'wc-cbs-badge-animated';
		}

		?>
		<div class="<?php echo esc_attr( implode( ' ', $badge_classes ) ); ?>">
			<?php if ( 'yes' === $settings['show_icon'] ) : ?>
				<span class="wc-cbs-badge-icon">
					<?php \Elementor\Icons_Manager::render_icon( $settings['icon'], array( 'aria-hidden' => 'true' ) ); ?>
				</span>
			<?php endif; ?>
			<span class="wc-cbs-badge-text"><?php echo esc_html( $status['name'] ); ?></span>
		</div>
		<?php
	}
}
