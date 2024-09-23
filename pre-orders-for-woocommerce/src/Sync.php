<?php

namespace Woocommerce_Preorders;

class Sync {
	public function __construct() {
		if ( !wp_next_scheduled( 'check_for_released_preorders' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'check_for_released_preorders' );
		}

		add_action( 'check_for_released_preorders', [$this, 'checkForPreorders'] );
	}

	public function checkForPreOrders() {
		$args = [
			'status' => 'wc-pre-ordered',
		];

		$preOrderedOrders = wc_get_orders( $args );
		foreach ( $preOrderedOrders as $order ) {
			//$preOrderDate = strtotime( get_post_meta( $order->get_id(), '_preorder_date', true ) );
			$preOrderDate = strtotime( $order->get_meta('_preorder_date')  );
			if ( $preOrderDate < time() ) {
				$parentOrderId = $order->get_parent_id();
				if ( $parentOrderId !== 0 ) {
					$parentOrder = wc_get_order( $parentOrderId );
					if ( $parentOrder->get_status() == 'completed' ) {
						$order->update_status( 'wc-completed', '[WooCommerce Pre Orders] ' );
					}

				} else {
					if ( $order->get_status() == 'pre-ordered' && $order->payment_complete() ) {
						$order->update_status( apply_filters( 'change_order_status_on_preorder_date', 'wc-completed' ), '[WooCommerce Pre Orders] ' );
					}
				}
			}
		}
	}
}
