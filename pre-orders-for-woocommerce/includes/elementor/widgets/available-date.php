<?php
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
class bp_preorder_available_date extends \Elementor\Widget_Base{

	public function get_name() {
		return 'bp_preorder_available_date';
	}

	public function get_title() {
		return esc_html__( 'Pre-Order available date', 'pre-orders-for-woocommerce' );
	}

	public function get_icon() {
		return 'eicon-calendar';
	}

	public function get_categories() {
		return ['woocommerce-elements'];
	}

	public function get_keywords() {
		return ['preorder', 'pre-order', 'pre order', 'available date', 'date'];
	}

	protected function register_controls() {

		// Content Tab Start

		$this->start_controls_section(
			'section_title',
			[
				'label' => esc_html__( 'Available Date', 'pre-orders-for-woocommerce' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'preorder_date',
			[
				'label'       => esc_html__( 'Available date Text', 'pre-orders-for-woocommerce' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'description' => esc_html__( 'Choose how the available date should be displayed. {date_format} = Default site date format (Ex: January 15, 2020 )', 'pre-orders-for-woocommerce' ),
				'default'     => get_option( 'wc_preorders_avaiable_date_text' )
			]
		);

		$this->add_responsive_control(
			'alignment',
			[
				'label'     => esc_html__( 'Alignment', 'pre-orders-for-woocommerce' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__( 'Left', 'pre-orders-for-woocommerce' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'pre-orders-for-woocommerce' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'pre-orders-for-woocommerce' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bp-preorder-available-date' => 'text-align: {{VALUE}}',
				],
			]
		);

		$this->end_controls_section();

		// Content Tab End

		// Style Tab Start

		$this->start_controls_section(
			'preorder_date_style',
			[
				'label' => esc_html__( 'Title', 'pre-orders-for-woocommerce' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Text Color', 'pre-orders-for-woocommerce' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bp-preorder-available-date' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'text_typography',
				'selector' => '{{WRAPPER}} .bp-preorder-available-date',
			]
		);

		$this->end_controls_section();

		// Style Tab End

	}
	/**
	 * replace the Available date Text field
	 *
	 * @param  [str]  $string
	 * @return void
	 */
	public function replaceDateTxt( $string, $timeFormat ) {
		$find    = array( "{date_format}" );
		$replace = array( $timeFormat );

		return str_replace( $find, $replace, $string );
	}

	/**
	 * @return mixed
	 */
	protected function render() {

		global $post, $product;

		$settings = $this->get_settings_for_display();

		if ( $product !== null ) {
			if ( 'yes' == get_post_meta( $post->ID, '_is_pre_order', true ) && strtotime( get_post_meta( $post->ID, '_pre_order_date', true ) ) > time() ) {
				$timeFormat = date_i18n( get_option( 'date_format' ), strtotime( get_post_meta( $post->ID, '_pre_order_date', true ) ) );

				$text = $this->replaceDateTxt( $settings['preorder_date'], $timeFormat );
				?>

                <p class="bp-preorder-available-date">
                    <?php echo $text; ?>
                </p>

                <?php

			}
		}
		// remove default pre-order date that dispaly before the add to cart button
		add_filter( 'preorder_avaiable_date_text', function ( $date_text ) {
			$date_text = '';
			return $date_text;
		} );

	}
}