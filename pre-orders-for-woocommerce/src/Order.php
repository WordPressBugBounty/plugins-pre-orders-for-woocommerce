<?php

namespace Woocommerce_Preorders;

class Order {

	public function __construct() {
		add_filter( 'manage_edit-shop_order_columns', [$this, 'preorderCustomColumn'], 20 );
		add_action( 'manage_shop_order_posts_custom_column', [$this, 'preorderCustomColumnContent'], 20, 2 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', [$this, 'preorderCustomColumn'], 20 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', [$this, 'preorderCustomColumnContent'], 20, 2 );
		add_action( 'woocommerce_order_item_meta_end', [$this, 'PreorderDateOrderItem'], 10, 3 );
	}

	/**
	 * Displays the preorder date for a specific order item.
	 *
	 * @param int    $item_id The ID of the order item.
	 * @param object $item    The order item object.
	 * @param object $order   The order object.
	 */
	function PreorderDateOrderItem( $item_id, $item, $order ) {

		$preorderDate = $order->get_meta( '_preorder_date' );
		if ( $preorderDate ) {
			echo '<br><span style="color:red">Preorder Date: ' . $preorderDate . '</span>';
		}
	}

	/**
	 * @param  $columns
	 * @return mixed
	 */
	public function preorderCustomColumn( $columns ) {
		$newColumns = [];
		foreach ( $columns as $columnName => $columnInfo ) {
			$newColumns[$columnName] = $columnInfo;
			if ( 'order_total' === $columnName ) {
				$newColumns['order_preorder_date'] = __( 'Preorder Date', 'pre-orders-for-woocommerce' );
			}
		}

		return $newColumns;
	}

	/**
	 * @param  $order
	 * @return null
	 */
	public function getPreOrderDate( $order ) {
		if ( !$order ) {return;}

		if ( is_object( $order ) && ( $order instanceof \WC_Order ) ) {
			// already order object
		} else {
			if ( is_object( $order ) && ( $order instanceof \WP_POST ) ) {
				$order = wc_get_order( $order->ID );
			} else {
				$order = wc_get_order( $order );
			}

			if ( !$order ) {return;}

		}

		if ( $order->get_meta( 'preorder_date' ) == '' && $order->get_meta( '_preorder_date' ) == '' ) {
			return;
		}
		$metaKey      = ( $order->get_meta( '_preorder_date' ) != '' ) ? '_preorder_date' : 'preorder_date';
		$shippingDate = strtotime( $order->get_meta( $metaKey ) );

		$now  = time();
		$diff = round(  ( $shippingDate - $now ) / ( 60 * 60 * 24 ) );
		if ( $diff > 0 ) {
			return sprintf(
				/* translators: number of days. */
				__( 'Available in %s days', 'pre-orders-for-woocommerce' ), $diff
			);
		} elseif ( 0 == $diff ) {
			return __( 'Available today', 'pre-orders-for-woocommerce' );
		} else {
			return __( 'Already shipped', 'pre-orders-for-woocommerce' );
		}
	}

	/**
	 * @param $column
	 */
	public function preorderCustomColumnContent( $column, $order ) {
		if ( 'order_preorder_date' === $column ) {
			echo $this->getPreOrderDate( $order );
		}
	}
}