<?php

namespace Woocommerce_Preorders;

class Checkout {
	/**
	 * @var mixed
	 */
	private $preordersMode;
	/**
	 * @var mixed
	 */
	private $cart;
	/**
	 * @var array
	 */
	private $emailIds;

	public function __construct() {
		$this->preordersMode = get_option( 'wc_preorders_mode' );

		$this->cart = new Cart();
		if ( 'either' === $this->preordersMode ) {
			add_filter( 'woocommerce_add_to_cart_validation', [$this->cart, 'allowOneTypeOnly'], 99, 2 );
		}

		add_action( 'woocommerce_checkout_update_order_meta', [$this, 'managePreOrders'], 10, 2 );
		add_action( 'woocommerce_order_status_changed', [$this, 'emailNotifications'], 10, 4 );
		add_filter( 'woocommerce_payment_complete_order_status', [$this, 'setPreroderStatus'], 10, 3 );
		add_filter( 'woocommerce_billing_fields', [$this, 'addShippingDateField'] );
		// send pre-order emails for payment gateways that utilize webhooks.
		add_action( 'woocommerce_payment_complete', [$this, 'sendEmailsWebhookEvents'], 10, 2 );
		add_filter( 'woocommerce_cod_process_payment_order_status', [$this, 'setPreorderStatusCOD'], 10, 2 );

	}
	/**
	 * Set main order status 'pre-ordered' after payment complete
	 *
	 * @param  [string] $status
	 * @param  [type]   $order
	 * @return status

	 * @since 1.2.13
	 *
	 * @return string
	 */
	public function setPreorderStatusCOD( $status, $order ) {

		if ( $order->get_meta( '_preorder_date' ) ) {
			return 'pre-ordered';
		}
		return $status;
	}
	/**
	 * Set main order status 'pre-ordered' after payment complete
	 *
	 * @param  [string] $status
	 * @param  [int]    $order_id
	 * @param  [type]   $order
	 * @return status
	 */
	public function setPreroderStatus( $status, $order_id, $order ) {
		/*if ( get_post_meta( $order_id, '_preorder_date', true ) ) {
			return 'pre-ordered';
		}*/
		$order = wc_get_order( $order_id );
		if ( $order->get_meta( '_preorder_date' ) ) {
			return 'pre-ordered';
		}
		return $status;
	}
	/**
	 * Sends e-mails, triggered by webhook events
	 *
	 * Currently listed these plugin gateways
	 * @link https://wordpress.org/plugins/woocommerce-paynl-payment-methods/ @plugin_name PAY. Payment Methods for WooCommerce
	 * @link https://wordpress.org/plugins/mollie-payments-for-woocommerce/ @plugin_name Mollie Payments for WooCommerce
	 * @link https://en-gb.wordpress.org/plugins/clearpay-gateway-for-woocommerce/ @plugin_name Clearpay Gateway for WooCommerce
	 * @link https://www.radialstudios.com/woocommerce-plugins/ @plugin_name Vantiv WooCommerce Gateway
	 * @link https://github.com/Cardlink-SA/woocommerce-cardlink-payment-gateway/ @plugin_name Cardlink Payment Gateway
	 * @link https://wordpress.org/plugins/woocommerce-payfast-gateway/ @plugin_name WooCommerce Payfast Gateway
	 *
	 * @since 1.2.13
	 *
	 * @return void
	 */
	public function sendEmailsWebhookEvents( $order_id, $transaction_id ) {

		$payment_methods = array(
			'pay_gateway',
			'mollie_wc_gateway',
			'clearpay',
			'vantiv_woocommerce_gateway',
			'razorpay',
			'cardlink_payment_gateway_woocommerce',
			'payfast',
		);
		$gateway_found                          = false;
		$was_woocommerce_payment_complete_fired = !is_null( $transaction_id );
		$order                                  = wc_get_order( $order_id );
		foreach ( apply_filters( 'bp_preorder_email_by_payment_method_list', $payment_methods ) as $payment_method ) {
			if ( strpos( $order->get_payment_method(), $payment_method ) !== false ) {
				$gateway_found = true;
				break;
			}
		}

		if ( $gateway_found && $was_woocommerce_payment_complete_fired && $order->get_status() === 'pre-ordered' ) {

			// Sends appropriate pre-order emails.
			WC()->mailer()->get_emails()['WC_New_Customer_Pre_Order_Email']->trigger( $order_id );
			WC()->mailer()->get_emails()['WC_New_Pre_Order_Email']->trigger( $order_id );
		}

	}
	/**
	 * send preorder related emails
	 *
	 * @param  [int]    $order_id
	 * @param  [string] $old_status
	 * @param  [string] $new_status
	 * @param  [object] $order
	 * @return void
	 */
	public function emailNotifications( $order_id, $old_status, $new_status, $order ) {
		$valid_old_statuses = ( 'pending' == $old_status || 'on-hold' == $old_status || 'failed' == $old_status );
		if ( $valid_old_statuses && is_checkout() && 'pre-ordered' == $new_status ) {

			// Send "New Email" notification (to customer)
			WC()->mailer()->get_emails()['WC_New_Customer_Pre_Order_Email']->trigger( $order_id );
			// Send "New Email" notification (to admin)
			WC()->mailer()->get_emails()['WC_New_Pre_Order_Email']->trigger( $order_id );
		}
	}

	/**
	 * Sends normal order and invoice email to the customer when the user arrives to the thank you page.
	 */
	public function sendOrderEmail( $orderId ) {
		$orderObj                  = wc_get_order( $orderId );
		$email_new_order           = WC()->mailer()->get_emails()['WC_Email_New_Order'];
		$emailProcessingOrder      = WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order'];
		$emailOnHoldOrder          = WC()->mailer()->get_emails()['WC_Email_Customer_On_Hold_Order'];
		$emailCompletedOrder       = WC()->mailer()->get_emails()['WC_Email_Customer_Completed_Order'];
		$hasPreorderedProductsOnly = count( $orderObj->get_items() ) === count( $this->getPreorderedProducts( $orderObj ) );

		// We're only firing these emails if there's only a non-preordered product present.
		if ( !$hasPreorderedProductsOnly ) {
			$email_new_order->trigger( $orderId );
			if ( $orderObj->get_status() == 'on-hold' ) {
				$emailOnHoldOrder->trigger( $orderId );
			} elseif ( $orderObj->get_status() == 'processing' ) {
				$emailProcessingOrder->trigger( $orderId );
			} elseif ( $orderObj->get_status() == 'completed' ) {
				$emailCompletedOrder->trigger( $orderId );
			}
		}
	}

	/**
	 * Add New date field in the checkout form
	 * @param  $fields
	 * @return mixed
	 */
	public function addShippingDateField( $fields ) {
		if ( !is_checkout() && !is_cart() ) {
			return $fields;
		}
		if ( bp_preorder_option( 'wc_preorders_always_choose_date' ) != 1 ) {
			return $fields;
		} else {
			$class = ['form-row-wide'];
		}
		global $woocommerce;
		$cart = $woocommerce->cart->get_cart();
		$this->cart->checkPreOrderProducts( $cart );
		if ( \count( $this->cart->getPreOrderProducts() ) > 0 ) {

			$oldestDate = str_replace( [' 00:00:00'], [''], $this->cart->getOldestDate() );

			$fields['preorder_date'] = [
				'label'             => __( 'Pre-Order Date', 'pre-orders-for-woocommerce' ),
				'type'              => 'text',
				'class'             => $class,
				'description'       => __( 'Please enter the date when you want to receive your order', 'pre-orders-for-woocommerce' ),
				// 'input_class'   => 'datepicker',
				'priority'          => 35,
				'required'          => true,
				'default'           => $oldestDate,
				'custom_attributes' => ['data-pre_order_date' => $oldestDate],
			];
		}

		return $fields;
	}

	/**
	 * @param  $rates
	 * @param  $package
	 * @return mixed
	 */
	public function manageShippingCosts( $rates, $package ) {
		$factor = 1;
		if ( 'individual' === $this->preordersMode ) {
			/**
			 * If we are on "individual" mode, then we will have to multiply it by the number of
			 * orders that we are going to generate.
			 */

			global $woocommerce;
			$cart = $woocommerce->cart->get_cart();
			$this->cart->checkPreOrderProducts( $cart );
			if ( \count( $this->cart->getPreOrderProducts() ) > 0 ) {
				$factor = 1+\count( $this->cart->getPreOrderProducts() );
			}
		} elseif ( 'partial' === $this->preordersMode ) {
			/*
				* If we are in partial mode and the "multiply shipping" option is enabled,
				* then we will have to multiply our shipping costs by 2
			*/
			$factor = 2;
		}
		foreach ( $rates as $id => $rate ) {
			$rates[$id]->cost *= $factor;
		}

		return $rates;
	}

	/**
	 * @param $orderId
	 * @param $data
	 */
	public function managePreOrders( $orderId, $data ) {
		$order = wc_get_order( $orderId );
		/**
		 * Case #1: treat the whole order as a pre-order
		 * Check if the order is of type partial or individual, and if not set the whole order as pre-ordered
		 */
		if ( isset( $data['preorder_date'] ) ) {
			$order->update_meta_data( '_preorder_date', esc_attr( $data['preorder_date'] ) );

		} else {
			global $woocommerce;
			$cart = $woocommerce->cart->get_cart();
			$this->cart->checkPreOrderProducts( $cart );
			if ( \count( $this->cart->getPreOrderProducts() ) > 0 ) {
				$oldestDate = str_replace( [' 00:00:00'], [''], $this->cart->getOldestDate() );
				$order->update_meta_data( '_preorder_date', esc_attr( $oldestDate ) );
			}
		}
		$order->save();

		// main action firing emails.
		do_action( 'preorder_email', $this->emailIds );
	}

	/**
	 * @param $orderObj
	 * @param $cartObj
	 */
	public function orderHasOnlyPreorderedProducts( $orderObj, $cartObj ) {
		return count( $orderObj->get_items() ) === count( $cartObj->getPreOrderProducts() );
	}

	/**
	 * @param $order_id
	 */
	public function checkWholeOrders( $order_id ) {
		/*if ( get_post_meta( $order_id, '_pre_order_date', true ) ) {
			$order = wc_get_order( $order_id );
			$order->set_status( 'wc-pre-ordered' );
			$order->save();
		}*/

		$order = wc_get_order( $order_id );
		if ( $order->get_meta( '_pre_order_date' ) ) {
			$order->set_status( 'wc-pre-ordered' );
			$order->save();
		}
	}

	/**
	 * @param  $order
	 * @return mixed
	 */
	private function getPreorderedProducts( $order ) {
		$preorderedProducts = [];
		foreach ( $order->get_items() as $item ) {
			$productId  = 0 !== $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
			$isPreOrder = get_post_meta( $productId, '_pre_order_date', true );
			if ( $isPreOrder && strtotime( $isPreOrder ) > time() ) {
				$preorderedProducts[] = $item;
			}
		}

		return $preorderedProducts;
	}

	/**
	 * @param  $prefix
	 * @param  $fields
	 * @return mixed
	 */
	private function getFilteredFields( $prefix, $fields ) {
		return $this->stripFieldsPrefix( $prefix, $this->filterFields( $prefix, $fields ) );
	}

	/**
	 * @param $prefix
	 * @param $fields
	 */
	private function stripFieldsPrefix( $prefix, $fields ) {
		return array_combine(
			array_map(
				function ( $k ) use ( $prefix ) {
					return str_replace( $prefix, '', $k );
				},
				array_keys( $fields )
			),
			array_values( $fields )
		);
	}

	/**
	 * @param  $prefix
	 * @param  $fields
	 * @return int
	 */
	private function filterFields( $prefix, $fields ) {
		return array_filter( $fields, function ( $key ) use ( $prefix ) {
			return 0 === strpos( $key, $prefix );
		}, ARRAY_FILTER_USE_KEY );
	}
}
