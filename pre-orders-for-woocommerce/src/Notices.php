<?php

namespace Woocommerce_Preorders;

class Notices {
	public function __construct() {
		
		$availableDatePosition = is_array( bp_preorder_option( 'wc_preorders_avaiable_date_array' ) ) ? bp_preorder_option( 'wc_preorders_avaiable_date_array' ) : [];
		if ( in_array( 'wc_preorders_avaiable_date_cart_item', $availableDatePosition ) ) {
			add_action( 'woocommerce_after_cart_item_name', [$this, 'addPreorderNotice'], 10, 1 );
			add_filter( 'woocommerce_widget_cart_item_quantity', [$this, 'showMinCartdate'], 10, 2 );
		}

		//TODO: This condition we need to check later.
		if ( get_option( 'woocommerce_preorders_show_general_cart_notice' ) == 'yes' ) {
			add_action( 'woocommerce_before_cart', [$this, 'addPreorderNotices'] );
		}
	}
	/**
	 * @param $output
	 * @param $cart_item
	 */
	public function showMinCartdate( $output, $cart_item ) {
		$min_cart_date = $this->addPreorderNotice( $cart_item );
		return '<span class="quantity">' . $output . '</span>' . $min_cart_date;
	}
	/**
	 * @param $cartItem
	 * @param $cartItemKey
	 */
	public function addPreorderNotice( $cartItem ) {
		$product = $cartItem['data'];
		if ( get_post_meta( $product->get_id(), '_pre_order_date', true ) !== null ) {
			$availableFrom = new \DateTime( get_post_meta( $product->get_id(), '_pre_order_date', true ) );
			$now           = new \DateTime();

			$diff = $now->diff( $availableFrom )->format( '%a' );

			if ( $availableFrom > $now && $diff > 0 ) {
				$notice = '<br/><small class="preorder-cart-notice" style="color:red">' . get_option( 'wc_preorders_cart_product_text', 'Note: this item will be available for shipping in {days_left} days' ) . '</small>';

				echo apply_filters( 'preorder_avaiable_date_text_cart', str_replace( '{days_left}', $diff, $notice ), $diff );

			}
		}
	}

	public function addPreorderNotices() {
		global $woocommerce;
		$actualCart = $woocommerce->cart->get_cart();

		$cart = new Cart();

		$cart->checkPreOrderProducts( $actualCart );
		if ( count( $cart->getPreOrderProducts() ) > 0 ) {
			wc_add_notice( __( 'Warning! you have selected certain products which are not available right now. You will have to choose a shipping date before you can place your order.', 'pre-orders-for-woocommerce' ), 'notice' );
		}
	}
}
