<?php

namespace Woocommerce_Preorders;

class Elementor {

	public function __construct() {

		add_action( 'elementor/widgets/register', [$this, 'register_widgets'], 10, 1 );
		// woocommerce-elements
	//	add_filter( 'preorder_avaiable_date_text', [$this, 'override_date'], 20, 1 );
	}
	/**
	 * @param $t
	 * @return string
	 */
	public function override_date( $t ) {
		$t = '';
		return $t;
	}
	/**
	 * @param $widgets_manager
	 */
	public function register_widgets( $widgets_manager ) {

		require_once WCPO_PLUGIN_DIR . '/elementor/widgets/available-date.php';
		require_once WCPO_PLUGIN_DIR . '/elementor/widgets/preorder-products.php';

		$widgets_manager->register( new \bp_preorder_available_date() );
		$widgets_manager->register( new \bp_preorder_products() );

	}

}
