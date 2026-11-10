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
	 * Checks if there are any pre-order products in the order and sets $preOrderProducts
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
	 * Resolve the pre-order date to store on an order from server-owned cart data.
	 *
	 * The customer-supplied date is never trusted on its own: it is only kept when the
	 * cart contains a merchant-designated pre-order product, it is a strict Y-m-d date,
	 * and it is not earlier than the merchant's availability date. Otherwise the
	 * merchant's availability date is used.
	 *
	 * @since 2.3.2
	 *
	 * @param  array  $items          Cart contents.
	 * @param  string $requested_date Date submitted by the customer (optional).
	 * @return string Y-m-d date, or an empty string when the cart has no pre-order products.
	 */
	public function resolvePreOrderDate( $items, $requested_date = '' ) {

		/**
		 * Sets the preOrderProducts property based on the items in the cart.
		 */
		$this->checkPreOrderProducts( $items );
		if ( \count( $this->getPreOrderProducts() ) === 0 ) {
			return '';
		}

		$available_timestamp = strtotime( $this->getOldestDate() );
		if ( false === $available_timestamp ) {
			return '';
		}
		$available_date = gmdate( 'Y-m-d', $available_timestamp );

		$requested_date = is_string( $requested_date ) ? sanitize_text_field( $requested_date ) : '';
		$requested      = \DateTime::createFromFormat( '!Y-m-d', $requested_date );
		if ( !$requested || $requested->format( 'Y-m-d' ) !== $requested_date ) {
			return $available_date;
		}

		// Y-m-d strings compare correctly as plain strings.
		return $requested_date >= $available_date ? $requested_date : $available_date;
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
