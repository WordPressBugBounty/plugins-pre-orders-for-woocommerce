<?php

namespace Woocommerce_Preorders;

class Cart {
	/**
	 * @var array
	 */
	private $preOrderProducts = [];

	public function __construct() {
	}

	/**
	 * @param $items
	 */
	public function checkPreOrderProducts( $items ) {
		if ( isset( $items['line_items'] ) ) {
			$items = $items['line_items'];
		}

		// Check if there are any pre-order products in the order
		// This array_filter function is used to look for the products marked as pre-ordered ones
		$preOrderProducts = @array_filter( $items, function ( $v ) {
			return 'yes' === get_post_meta( $v['product_id'], '_is_pre_order', true ) && new \DateTime( get_post_meta( $v['product_id'], '_pre_order_date', true ) ) > new \DateTime() || 'yes' === get_post_meta( $v['variation_id'], '_is_pre_order', true ) && new \DateTime( get_post_meta( $v['variation_id'], '_pre_order_date', true ) ) > new \DateTime();
		} );

		$this->setPreOrderProducts( $preOrderProducts );
	}

	/**
	 * Get Oldest Preorder Date
	 * @return mixed
	 */
	public function getOldestDate() {
		// This array_reduce function is used to look for the product with the oldest day
		$productWithOldestDate = array_reduce( $this->getPreOrderProducts(), function ( $a, $b ) {
			if ( null === $a ) {
				return $b;
			}
			// We use these short conditionals to get the variation id if exists or the product id if it is a simple product
			$aId = isset( $a['variation_id'] ) && 0 !== $a['variation_id'] ? $a['variation_id'] : $a['product_id'];
			$bId = isset( $b['variation_id'] ) && 0 !== $b['variation_id'] ? $b['variation_id'] : $b['product_id'];

			return $a ? ( strtotime( get_post_meta( $aId, '_pre_order_date', true ) ) > strtotime( get_post_meta( $bId, '_pre_order_date', true ) ) ? $a : $b ) : $b;
		} );

		$oldestId = isset( $productWithOldestDate['variation_id'] ) && 0 !== $productWithOldestDate['variation_id'] ? $productWithOldestDate['variation_id'] : $productWithOldestDate['product_id'];

		return get_post_meta( $oldestId, '_pre_order_date', true );
	}

	/**
	 * Get the value of preOrderProducts.
	 */
	public function getPreOrderProducts() {
		return $this->preOrderProducts;
	}

	/**
	 * Set the value of preOrderProducts.
	 *
	 * @param  mixed  $preOrderProducts
	 * @return self
	 */
	public function setPreOrderProducts( $preOrderProducts ) {
		$this->preOrderProducts = $preOrderProducts;

		return $this;
	}

	/**
	 * If the fourth case is on, then we have to only allow either preorder products or available ones
	 *
	 * @param  $passed
	 * @param  $productId
	 * @return mixed
	 */
	public function allowOneTypeOnly( $passed, $productId ) {
		$products      = array_filter( WC()->cart->get_cart_contents() );
		$havePreOrders = false;
		foreach ( $products as $product ) {
			$p   = new Product( $product['data']->get_id() );
			$id  = $product['data']->get_id();
			$has = $p->isPreOrder();
			if ( $p->isPreOrder() ) {
				$havePreOrders = true;
			}
		}
		$variableId = ( isset( $_POST['variation_id'] ) ) ? sanitize_text_field( $_POST['variation_id'] ) : 0;

		$newProduct = new Product( $productId, $variableId );
		if ( empty( $products ) || ( $newProduct->isPreOrder() && $havePreOrders ) || ( false === $newProduct->isPreOrder() && false === $havePreOrders ) ) {
			$passed = true;
		} else {
			$passed = false;
			if ( $newProduct->isPreOrder() ) {
				wc_add_notice( __( 'We detected that you are trying to add a pre-order product in your cart. Please remove the rest of the products before proceeding.', 'pre-orders-for-woocommerce' ), 'error' );
			} else {
				wc_add_notice( __( 'We detected that your cart has pre-order products. Please remove them before being able to add this product.', 'pre-orders-for-woocommerce' ), 'error' );
			}
		}

		return $passed;
	}
}
