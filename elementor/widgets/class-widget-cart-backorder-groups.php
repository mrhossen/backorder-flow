<?php
/**
 * Elementor Widget: Cart Backorder Groups
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

/**
 * Cart Backorder Groups Widget.
 */
class WMBF_Widget_Cart_Backorder_Groups extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wc-cbs-cart-backorder-groups';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Cart Backorder Groups', 'backorder-flow' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-products';
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
		return array( 'woocommerce', 'cart', 'backorder', 'groups', 'shipping' );
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
			'layout',
			array(
				'label'   => __( 'Layout', 'backorder-flow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'table',
				'options' => array(
					'table' => __( 'Table', 'backorder-flow' ),
					'list'  => __( 'List', 'backorder-flow' ),
					'cards' => __( 'Cards', 'backorder-flow' ),
				),
			)
		);

		$this->add_control(
			'show_thumbnails',
			array(
				'label'        => __( 'Show Thumbnails', 'backorder-flow' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'backorder-flow' ),
				'label_off'    => __( 'No', 'backorder-flow' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_prices',
			array(
				'label'        => __( 'Show Prices', 'backorder-flow' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'backorder-flow' ),
				'label_off'    => __( 'No', 'backorder-flow' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_quantities',
			array(
				'label'        => __( 'Show Quantities', 'backorder-flow' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'backorder-flow' ),
				'label_off'    => __( 'No', 'backorder-flow' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// Style Section - Group Header.
		$this->start_controls_section(
			'section_group_header_style',
			array(
				'label' => __( 'Group Header', 'backorder-flow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'group_title_typography',
				'selector' => '{{WRAPPER}} .wc-cbs-cart-group-title',
			)
		);

		$this->add_control(
			'group_header_padding',
			array(
				'label'      => __( 'Padding', 'backorder-flow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-cbs-cart-group-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'group_header_margin',
			array(
				'label'      => __( 'Margin', 'backorder-flow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-cbs-cart-group-header' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Items.
		$this->start_controls_section(
			'section_items_style',
			array(
				'label' => __( 'Items', 'backorder-flow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'item_typography',
				'selector' => '{{WRAPPER}} .wc-cbs-item-name',
			)
		);

		$this->add_control(
			'item_padding',
			array(
				'label'      => __( 'Padding', 'backorder-flow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-cbs-cart-group-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'item_spacing',
			array(
				'label'      => __( 'Spacing Between Items', 'backorder-flow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wc-cbs-cart-group-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'item_border',
				'selector' => '{{WRAPPER}} .wc-cbs-cart-group-item',
			)
		);

		$this->end_controls_section();

		// Style Section - Columns.
		$this->start_controls_section(
			'section_columns_style',
			array(
				'label'     => __( 'Columns', 'backorder-flow' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'layout' => 'cards',
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => __( 'Columns', 'backorder-flow' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
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
				'selectors'      => array(
					'{{WRAPPER}} .wc-cbs-cart-group-cards' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				),
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
					'{{WRAPPER}} .wc-cbs-cart-group-cards' => 'gap: {{SIZE}}{{UNIT}};',
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

		// Get cart groups.
		$groups = WMBF_Cart_Grouping::get_groups();

		if ( empty( $groups ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Cart is empty. This widget displays cart items grouped by shipping status.', 'backorder-flow' ) . '</p>';
			}
			return;
		}

		?>
		<div class="wc-cbs-cart-groups wc-cbs-elementor-widget wc-cbs-layout-<?php echo esc_attr( $settings['layout'] ); ?>">
			<?php foreach ( $groups as $group ) : ?>
				<div class="wc-cbs-cart-group" data-group-id="<?php echo esc_attr( $group['id'] ); ?>">
					<div class="wc-cbs-cart-group-header" style="border-left-color: <?php echo esc_attr( $group['color'] ); ?>;">
						<h3 class="wc-cbs-cart-group-title" style="color: <?php echo esc_attr( $group['color'] ); ?>;">
							<?php echo esc_html( $group['name'] ); ?>
						</h3>
						<?php if ( ! empty( $group['description'] ) ) : ?>
							<p class="wc-cbs-cart-group-description">
								<?php echo esc_html( $group['description'] ); ?>
							</p>
						<?php endif; ?>
					</div>

					<div class="wc-cbs-cart-group-items">
						<?php if ( 'table' === $settings['layout'] ) : ?>
							<table class="wc-cbs-cart-group-table">
								<tbody>
									<?php foreach ( $group['items'] as $item ) : ?>
										<?php $this->render_item_table( $item, $settings ); ?>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php elseif ( 'list' === $settings['layout'] ) : ?>
							<ul class="wc-cbs-cart-group-list">
								<?php foreach ( $group['items'] as $item ) : ?>
									<?php $this->render_item_list( $item, $settings ); ?>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<div class="wc-cbs-cart-group-cards">
								<?php foreach ( $group['items'] as $item ) : ?>
									<?php $this->render_item_card( $item, $settings ); ?>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render item as table row.
	 *
	 * @param array $item     Item data.
	 * @param array $settings Widget settings.
	 */
	private function render_item_table( $item, $settings ) {
		$product = $item['product'];
		$quantity = $item['quantity'];
		?>
		<tr class="wc-cbs-cart-group-item">
			<?php if ( 'yes' === $settings['show_thumbnails'] ) : ?>
				<td class="wc-cbs-item-thumbnail">
					<?php echo wp_kses_post( $product->get_image( 'thumbnail' ) ); ?>
				</td>
			<?php endif; ?>
			<td class="wc-cbs-item-name">
				<strong><?php echo esc_html( $product->get_name() ); ?></strong>
			</td>
			<?php if ( 'yes' === $settings['show_quantities'] ) : ?>
				<td class="wc-cbs-item-quantity">
					<?php echo (int) $quantity; ?>
				</td>
			<?php endif; ?>
			<?php if ( 'yes' === $settings['show_prices'] ) : ?>
				<td class="wc-cbs-item-price">
					<?php
					if ( $quantity > 0 && $item['total_qty'] > 0 ) {
						echo wp_kses_post( wc_price( WMBF_Cart_Grouping::get_item_price( $item ) ) );
					}
					?>
				</td>
			<?php endif; ?>
		</tr>
		<?php
	}

	/**
	 * Render item as list item.
	 *
	 * @param array $item     Item data.
	 * @param array $settings Widget settings.
	 */
	private function render_item_list( $item, $settings ) {
		$product = $item['product'];
		$quantity = $item['quantity'];
		?>
		<li class="wc-cbs-cart-group-item">
			<?php if ( 'yes' === $settings['show_thumbnails'] ) : ?>
				<span class="wc-cbs-item-thumbnail">
					<?php echo wp_kses_post( $product->get_image( 'thumbnail' ) ); ?>
				</span>
			<?php endif; ?>
			<span class="wc-cbs-item-name"><?php echo esc_html( $product->get_name() ); ?></span>
			<?php if ( 'yes' === $settings['show_quantities'] ) : ?>
				<span class="wc-cbs-item-quantity">(<?php echo (int) $quantity; ?>)</span>
			<?php endif; ?>
			<?php if ( 'yes' === $settings['show_prices'] ) : ?>
				<span class="wc-cbs-item-price">
					<?php
					if ( $quantity > 0 && $item['total_qty'] > 0 ) {
						echo wp_kses_post( wc_price( WMBF_Cart_Grouping::get_item_price( $item ) ) );
					}
					?>
				</span>
			<?php endif; ?>
		</li>
		<?php
	}

	/**
	 * Render item as card.
	 *
	 * @param array $item     Item data.
	 * @param array $settings Widget settings.
	 */
	private function render_item_card( $item, $settings ) {
		$product = $item['product'];
		$quantity = $item['quantity'];
		?>
		<div class="wc-cbs-cart-group-item wc-cbs-item-card">
			<?php if ( 'yes' === $settings['show_thumbnails'] ) : ?>
				<div class="wc-cbs-item-thumbnail">
					<?php echo wp_kses_post( $product->get_image( 'medium' ) ); ?>
				</div>
			<?php endif; ?>
			<div class="wc-cbs-item-details">
				<h4 class="wc-cbs-item-name"><?php echo esc_html( $product->get_name() ); ?></h4>
				<?php if ( 'yes' === $settings['show_quantities'] ) : ?>
					<p class="wc-cbs-item-quantity">
						<?php
						/* translators: %d: quantity */
						printf( esc_html__( 'Qty: %d', 'backorder-flow' ), (int) $quantity );
						?>
					</p>
				<?php endif; ?>
				<?php if ( 'yes' === $settings['show_prices'] ) : ?>
					<p class="wc-cbs-item-price">
						<?php
						if ( $quantity > 0 && $item['total_qty'] > 0 ) {
							echo wp_kses_post( wc_price( WMBF_Cart_Grouping::get_item_price( $item ) ) );
						}
						?>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
