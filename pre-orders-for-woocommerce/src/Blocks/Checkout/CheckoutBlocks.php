<?php

namespace Woocommerce_Preorders\Blocks\Checkout;

use Woocommerce_Preorders\Cart;

class CheckoutBlocks {

    /**
	 * @var mixed
	 */
	private $preordersMode;
	/**
	 * @var mixed
	 */
	private $cart;

	public function __construct() {
		$this->preordersMode = get_option( 'wc_preorders_mode' );
		$this->cart = new Cart();

        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        // Register Store API extension data
        add_action('woocommerce_blocks_loaded', array($this, 'register_store_api_extension'));
        // Hook para WooCommerce Blocks (Store API)
        add_action('woocommerce_store_api_checkout_update_order_from_request', array($this, 'save_pre_order_date_blocks'), 10, 2);
	}

    /**
     * Register Store API extension data.
     */
    public function register_store_api_extension() {
        // Register extension data for Store API
        woocommerce_store_api_register_endpoint_data(
            array(
                'endpoint'        => 'checkout',
                'namespace'       => 'preorder-date-picker',
                'schema_callback' => array($this, 'get_extension_schema'),
                'data_callback'   => array($this, 'get_extension_data'),
            )
        );
    }
    
    /**
     * Get extension schema. 
     */
    public function get_extension_schema() {
        return array(
            'pre_order_date' => array(
                'description' => __('Pre-order date selected by customer', 'pre-orders-for-woocommerce'),
                'type'        => 'string',
                'context'     => array('view', 'edit'),
            ),
        );
    }
    
    /**
     * Get extension data.
     */
    public function get_extension_data() {
        return array(
            'pre_order_date' => $this->get_default_pre_order_date(),
        );
    }
    
    /**
     * Get default pre-order date (oldest date of pre-order products)
     */
    public function get_default_pre_order_date() {

		$cart = \WC()->cart->get_cart();
		$this->cart->checkPreOrderProducts( $cart );
        $default_date = '';

		if ( count( $this->cart->getPreOrderProducts() ) > 0 ) {
            $oldestDate = str_replace( [' 00:00:00'], [''], $this->cart->getOldestDate() );
            $default_date = $oldestDate;
        }

        return $default_date;
    }
    
    /**
     * Enqueue scripts. Add pre-order date picker to checkout page.
     */
    public function enqueue_scripts() {
        if (!is_checkout()) {
            return;
        }

        if ( bp_preorder_option( 'wc_preorders_always_choose_date' ) != 1 ) {
			return;
		}

        wp_enqueue_script(
            'preorder-date-picker',
            WCPO_PLUGIN_URL . 'src/Blocks/Checkout/assets/js/preorder-date-picker.js',
            array('wp-plugins', 'wp-element', 'wp-data', 'wp-i18n', 'wc-blocks-checkout'),
            WCPO_PLUGIN_VER,
            true
        );
        
        wp_enqueue_style(
            'preorder-date-picker',
            WCPO_PLUGIN_URL . 'src/Blocks/Checkout/assets/css/preorder-date-picker.css',
            array(),
            WCPO_PLUGIN_VER
        );
        
        wp_localize_script('preorder-date-picker', 'preOrderDatePicker', array(
            'translations' => array(
                'preOrderDateLabel' => __('Preorder Date', 'pre-orders-for-woocommerce'),
                'preOrderDatePlaceholder' => __('Select a date', 'pre-orders-for-woocommerce'),
            ),
            'defaultDate' => $this->get_default_pre_order_date(),
        ));
        
    }
    
    /**
     * Save pre-order date for WooCommerce Blocks (Store API)
     */
    public function save_pre_order_date_blocks($order, $request) {

        // Get the pre-order date from the request extensions
        $extensions = $request->get_param('extensions');
        $pre_order_date = null;
        
        // Get pre-order date from our plugin extension data
        if (isset($extensions['preorder-date-picker']['pre_order_date'])) {
            $pre_order_date = $extensions['preorder-date-picker']['pre_order_date'];
        }
        
        // If pre-order date is not set, get the oldest date of pre-order products
        if (!empty($pre_order_date)) {
            $pre_order_date = sanitize_text_field($pre_order_date);
        }
        else {
			$cart = \WC()->cart->get_cart();
			$this->cart->checkPreOrderProducts( $cart );
			if ( count( $this->cart->getPreOrderProducts() ) > 0 ) {
				$pre_order_date = str_replace( [' 00:00:00'], [''], $this->cart->getOldestDate() );
			}
            else {
                return;
            }
        }

        $order->update_meta_data('_preorder_date', $pre_order_date);
        $order->save(); 
        
    }


}
