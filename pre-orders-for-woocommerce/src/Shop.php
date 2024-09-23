<?php

namespace Woocommerce_Preorders;

class Shop {
	/**
	 * @var mixed
	 */
	public $badgeloopPriority;
	/**
	 * @var mixed
	 */
	public $badgeSinglePriority;

	public function __construct() {
		add_filter( 'woocommerce_product_add_to_cart_text', [$this, 'changeButtonText'], 10, 1 );
		add_filter( 'woocommerce_product_single_add_to_cart_text', [$this, 'changeButtonText'], 10, 1 );
		add_filter( 'woocommerce_available_variation', [$this, 'changeButtonTextForVariableProducts'], 10, 3 );
		add_action( 'woocommerce_before_add_to_cart_form', [$this, 'beforeAddToCartBtn'], 10 );
		add_action( 'preorder_product_loop_wrapper', [$this, 'addLoopClasses'], 10 );
		add_action( 'woocommerce_after_shop_loop_item', [$this, 'afterShopLoopItem'], 10 );
		$this->badgeloopPriority   = apply_filters( 'preorder_loop_badge_priority', 5 );
		$this->badgeSinglePriority = apply_filters( 'preorder_single_badge_priority', 5 );
		add_action( bp_preorder_option( 'wc_preorder_loop_badge_position' ), [$this, 'preorderBadgeLoop'], $this->badgeloopPriority );
		add_action( 'woocommerce_before_single_product_summary', [$this, 'preorderBadgeSingle'], $this->badgeSinglePriority );
	}
	/**
	 * badge for shop loop products
	 */
	public function preorderBadgeLoop() {
		$badgePosition = is_array( bp_preorder_option( 'wc_preorder_badge_array' ) ) ? bp_preorder_option( 'wc_preorder_badge_array' ) : [];
		if ( !in_array( 'wc_preorder_badge_shop_page', $badgePosition ) ) {
			return;
		}
		global $product;

		if ( 'yes' == get_post_meta( $product->get_id(), '_is_pre_order', true ) && strtotime( get_post_meta( $product->get_id(), '_pre_order_date', true ) ) > time() ):

			echo apply_filters( 'woocommerce_preorder_badge', '<span class="onsale on-preorder">' . get_option( 'wc_preorders_badge_text', 'Preorder' ) . '</span>', $product );

		endif;

		if ( $product->is_type( 'variable' ) ) {
			$product_variations   = $product->get_available_variations();
			$has_preorder_variant = false;
			foreach ( $product_variations as $variation ) {
				if ( 'yes' == get_post_meta( $variation['variation_id'], '_is_pre_order', true ) && strtotime( get_post_meta( $variation['variation_id'], '_pre_order_date', true ) ) > time() ) {
					$has_preorder_variant = true;
					break;
				}
			}
			if ( $has_preorder_variant ):

				echo apply_filters( 'woocommerce_preorder_badge', '<span class="onsale on-preorder">' . get_option( 'wc_preorders_badge_text', 'Preorder' ) . '</span>', $product );

			endif;
		}
	}

	/**
	 * badge for single product page
	 */
	public function preorderBadgeSingle() {
		$badgePosition = is_array( bp_preorder_option( 'wc_preorder_badge_array' ) ) ? bp_preorder_option( 'wc_preorder_badge_array' ) : [];
		if ( !in_array( 'wc_preorder_badge_single_product', $badgePosition ) ) {
			return;
		}
		global $product;

		if ( 'yes' == get_post_meta( $product->get_id(), '_is_pre_order', true ) && strtotime( get_post_meta( $product->get_id(), '_pre_order_date', true ) ) > time() ):

			echo apply_filters( 'woocommerce_preorder_badge', '<span class="onsale on-preorder">' . get_option( 'wc_preorders_badge_text', 'Preorder' ) . '</span>', $product );
		endif;

		if ( $product->is_type( 'variable' ) ) {
			$product_variations   = $product->get_available_variations();
			$has_preorder_variant = false;
			foreach ( $product_variations as $variation ) {
				if ( 'yes' == get_post_meta( $variation['variation_id'], '_is_pre_order', true ) && strtotime( get_post_meta( $variation['variation_id'], '_pre_order_date', true ) ) > time() ) {
					$has_preorder_variant = true;
					break;
				}
			}
			if ( $has_preorder_variant ):

				echo apply_filters( 'woocommerce_preorder_badge', '<span class="onsale on-preorder">' . get_option( 'wc_preorders_badge_text', 'Preorder' ) . '</span>', $product );

			endif;
		}
	}
	/**
	 * This method is called after each item in the shop loop.
	 * It checks if the product is a pre-order and if the pre-order date is in the future.
	 * If so, it generates the available date text and applies any filters.
	 *
	 * @return void
	 */
	public function afterShopLoopItem() {
		global $product;
		if ( null !== $product && in_array( 'wc_preorders_avaiable_date_loop', $this->availableDatePosition() ) ) {
			if ( 'yes' == get_post_meta( $product->get_id(), '_is_pre_order', true ) && strtotime( get_post_meta( $product->get_id(), '_pre_order_date', true ) ) > time() ) {
				$timeFormat = date_i18n( get_option( 'date_format' ), strtotime( get_post_meta( $product->get_id(), '_pre_order_date', true ) ) );

				$text = '<span class="preoder-span-block">' . self::replaceDateTxt( get_option( 'wc_preorders_avaiable_date_text' ), $timeFormat ) . '<span>';

				echo apply_filters( 'preorder_avaiable_date_text_loop', $text, $timeFormat );
			}
		}
	}
	/**
	 * Override class wrapper based on theme style.
	 *
	 * @param  [type] $classes
	 *
	 * @return void
	 */
	public function addLoopClasses( $classes ) {

		$active_theme = wp_get_theme()->get_template();

		switch ( $active_theme ) {

		case 'storefront':
			$classes = 'site-main';
			break;

		default:

			break;
		}

		return $classes;
	}
	public function beforeAddToCartBtn() {
		global $post, $product;

		if ( null !== $product && in_array( 'wc_preorders_avaiable_date_single_product', $this->availableDatePosition() ) ) {
			if ( 'yes' == get_post_meta( $post->ID, '_is_pre_order', true ) && strtotime( get_post_meta( $post->ID, '_pre_order_date', true ) ) > time() ) {
				$timeFormat = date_i18n( get_option( 'date_format' ), strtotime( get_post_meta( $post->ID, '_pre_order_date', true ) ) );

				$text = $this->replaceDateTxt( get_option( 'wc_preorders_avaiable_date_text', 'Available on {date_format}' ), $timeFormat );

				echo apply_filters( 'preorder_avaiable_date_text', $text );
			}
		}
	}
	/**
	 * @param  $data
	 * @param  $product
	 * @param  $variation
	 * @return mixed
	 */
	public function changeButtonTextForVariableProducts( $data, $product, $variation ) {
		if ( get_post_meta( $variation->get_id(), '_is_pre_order', true ) == 'yes' && strtotime( get_post_meta( $variation->get_id(), '_pre_order_date', true ) ) > time() ) {
			$data['is_pre_order'] = true;
		}
		return $data;
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
	 * @param  $text
	 * @return mixed
	 */
	public function changeButtonText( $text ) {
		global $post, $product;

		if ( null !== $product ) {
			if ( 'yes' == get_post_meta( $post->ID, '_is_pre_order', true ) && strtotime( get_post_meta( $post->ID, '_pre_order_date', true ) ) > time() ) {
				return get_option( 'wc_preorders_button_text', 'Pre Order Now!' );
			}
		}

		return $text;
	}
	/**
	 * Returns the available date position.
	 *
	 * @return array The available date position array.
	 */
	public function availableDatePosition() {
		return is_array( bp_preorder_option( 'wc_preorders_avaiable_date_array' ) ) ? bp_preorder_option( 'wc_preorders_avaiable_date_array' ) : [];
	}

}