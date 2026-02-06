<?php

namespace Woocommerce_Preorders\Pages;

/**
 * This class is responsible for managing the logic on the product page only - Frontend
 * 
 * @since 2.3
 */
class ProductPage {

    private static $instance = null;

    private function __construct()
    {
        /**
         * Change the WooCommerce stock message when the product is pre-order
         */
        add_filter( 'woocommerce_get_availability_text', array( $this, 'set_stock_availability_text' ), 10, 2 );
    }

    /**
     * It returns the single instance of the class, if the instance doesn't exist, it creates it.
     * 
     * @since 2.3
     *
     * @return ProductPage
     */
    public static function init(): ProductPage {
        
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * It sets the stock availability text from the product page
     * 
     * @since 2.3
     * 
     * @param string $availability
     * @param \WC_Product $product
     * 
     * @return string $availability
     */
    public function set_stock_availability_text( $availability, $product ) {

        if ( !$product instanceof \WC_Product ) {
            return $availability;
        }

        $is_pre_order = $product->get_meta( '_is_pre_order', true );
        $stock_qty    = $product->get_stock_quantity();

        if ( $is_pre_order === 'yes' && $stock_qty > 0 ) {
            
            return sprintf( 
                _n( 
                    '%d pre-order available',
                    '%d pre-orders available',
                    $stock_qty,
                    'pre-orders-for-woocommerce'
                ), 
                $stock_qty
            );
        }

        return $availability;
    }

}