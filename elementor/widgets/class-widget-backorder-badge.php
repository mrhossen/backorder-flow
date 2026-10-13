<?php
/**
 * Elementor Widget: Backorder Badge
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

/**
 * Backorder Badge Widget.
 */
class WMCBP_Widget_Backorder_Badge extends Widget_Base {

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
		return __( 'Backorder Badge', 'custom-backorder-pro' );
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
				'label' => __( 'Content', 'custom-backorder-pro' ),
			)
		);

		$this->add_control(
			'product_id',
			array(
				'label'       => __( 'Product ID', 'custom-backorder-pro' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 0,
				'description' => __( 'Leave 0 to use current product. Enter product ID to display specific product badge.', 'custom-backorder-pro' ),
			)
		);

		$this->add_control(
			'badge_style',
			array(
				'label'   => __( 'Badge Style', 'custom-backorder-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'flat',
				'options' => array(
					'flat'    => __( 'Flat', 'custom-backorder-pro' ),
					'rounded' => __( 'Rounded', 'custom-backorder-pro' ),
					'pill'    => __( 'Pill', 'custom-backorder-pro' ),
					'custom'  => __( 'Custom', 'custom-backorder-pro' ),
				),
			)
		);

		$this->add_control(
			'badge_size',
			array(
				'label'   => __( 'Size', 'custom-backorder-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'medium',
				'options' => array(
					'small'  => __( 'Small', 'custom-backorder-pro' ),
					'medium' => __( 'Medium', 'custom-backorder-pro' ),
					'large'  => __( 'Large', 'custom-backorder-pro' ),
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
				),
			)
		);

		$this->add_control(
			'animation_on_hover',
			array(
				'label'        => __( 'Animation on Hover', 'custom-backorder-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'custom-backorder-pro' ),
				'label_off'    => __( 'No', 'custom-backorder-pro' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->end_controls_section();

		// Style Section - Badge.
		$this->start_controls_section(
			'section_badge_style',
			array(
				'label' => __( 'Badge', 'custom-backorder-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'badge_background',
			array(
				'label'     => __( 'Background Color', 'custom-backorder-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wc-cbs-badge' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_text_color',
			array(
				'label'     => __( 'Text Color', 'custom-backorder-pro' ),
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
				'label'      => __( 'Border Radius', 'custom-backorder-pro' ),
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
				'label'      => __( 'Padding', 'custom-backorder-pro' ),
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
				'label'     => __( 'Icon', 'custom-backorder-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_icon' => 'yes',
				),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Color', 'custom-backorder-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wc-cbs-badge-icon' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'icon_size',
			array(
				'label'      => __( 'Size', 'custom-backorder-pro' ),
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
				'label'      => __( 'Spacing', 'custom-backorder-pro' ),
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

		$settings = $this->get_settings_for_display();

		// Get product ID.
		$product_id = $settings['product_id'] ? $settings['product_id'] : get_the_ID();
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Product not found. This widget displays backorder badge.', 'custom-backorder-pro' ) . '</p>';
			}
			return;
		}

		// Get backorder status.
		$status = WMCBP_Product_Meta::get_product_backorder_status( $product );

		if ( ! $status ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'No backorder status set for this product.', 'custom-backorder-pro' ) . '</p>';
			}
			return;
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
