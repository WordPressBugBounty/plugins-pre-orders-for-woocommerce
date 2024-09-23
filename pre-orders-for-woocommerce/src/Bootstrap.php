<?php

namespace Woocommerce_Preorders;

class Bootstrap {
	/**
	 * @var string
	 */
	public $cosm__title;
	/**
	 * @var string
	 */
	public $cosm_plugin_url;
	/**
	 * @var boolen
	 */
	public $cosm_activate;
	/**
	 * @var mixed
	 */
	static $instance = null;

	const TEXT_DOMAIN = 'preorders-for-woocommerce-pro';

	public function __construct() {
		$this->cosmDefination();
		add_action( 'admin_init', array( 'PAnD', 'init' ) );
		add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueStyles'] );
		add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
		add_action( 'wp_enqueue_scripts', [$this, 'frontEnqueueStyles'] );
		add_action( 'wp_enqueue_scripts', [$this, 'frontEnqueueScripts'] );

		add_action( 'admin_notices', [$this, 'show_cosm_notice'] );
		add_filter( 'woocommerce_email_classes', [$this, 'wcpoAddPreordersCustomEmail'] );
		add_action( 'admin_notices', [$this, 'review'] );
		add_action( 'admin_init', [$this, 'urlParamCheck'] );
		add_filter( "plugin_row_meta", [$this, 'pluginMetaLinks'], 20, 2 );
		add_shortcode( 'preorder_products', [$this, 'preorderProducts'] );

		$this->generatePreOrderStatus();

		// add_action('woocommerce_thankyou', [$checkout, 'checkGeneratedOrderStatus']);
		$this->initializeCheckout();
		$this->initializeSync();
		$this->initializeTabs();
		$this->initializeNotices();
		$this->initializeSettings();
		$this->initializeShop();
		$this->initializeOrder();
		new Elementor();
		//error_log( 'cosmSettingsTab' );
	}
	
	/**
	 * Initializes the Bootstrap class and returns the instance.
	 *
	 * @return Bootstrap The instance of the Bootstrap class.
	 */
	public static function init() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function cosmSettingsTab() {
		
		?>
			<div class=" csf-submessage csf-submessage-info">
			<p>🌟 Want to choose which status should the order be set after the preorder date passed?</br>Unlock this option by using <b>Custom Order Status Manager for WooCommerce</b> plugin completely free <a href="<?php echo esc_url( $this->cosm_plugin_url ); ?>"><?php echo esc_html( $this->cosm__title ); ?></a><p>
			</div>
			<?php
	}
	
	/**
	 * Returns the result of the cosmSettingsTab() method.
	 *
	 * @return mixed The result of the cosmSettingsTab() method.
	 */
	public static function cosmNotice() {
		
		return self::$instance->cosmSettingsTab();
	}

	/**
	 * Display preorder products via shortcode
	 *
	 * @param  [type] $atts
	 * @return void
	 */
	public function preorderProducts( $atts ) {
		global $woocommerce_loop;

		// Attributes
		$atts = shortcode_atts(
			array(
				'columns'        => '3',
				'posts_per_page' => '-1',
				'order'          => 'DESC',
				'orderby'        => 'title',

			),
			$atts,
			'preorder_products'
		);

		$woocommerce_loop['columns'] = $atts['columns'];

		// The WP_Query
		//todo: variable preorder not showing
		$preorderQuery = new \WP_Query( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $atts['posts_per_page'],
			'orderby'        => $atts['orderby'],
			'order'          => $atts['order'],
			'meta_query'     => array(

				array(
					'key'     => '_is_pre_order',
					'value'   => 'yes',
					'compare' => '=',
				),
				array(
					'key'     => '_pre_order_date',
					'value'   => date( 'y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		) );

		ob_start();

		if ( $preorderQuery->have_posts() ) {
			echo '<div class="' . apply_filters( 'preorder_product_loop_wrapper', 'woocommerce' ) . '">';

			woocommerce_product_loop_start();

			while ( $preorderQuery->have_posts() ): $preorderQuery->the_post();

				wc_get_template_part( 'content', 'product' );

			endwhile; // end of the loop.

			woocommerce_product_loop_end();
			echo '</div>';
		} else {
			do_action( "woocommerce_shortcode_products_loop_no_results", $atts );
			echo "<p>" . __( 'No preorder products found.', 'pre-orders-for-woocommerce' ) . "</p>";
		}

		woocommerce_reset_loop();

		wp_reset_postdata();

		return '<div class="woocommerce columns-' . esc_attr( $atts['columns'] ) . '">' . ob_get_clean() . '</div>';
	}
	/**
	 * Get data of Custom Order status plugin
	 *
	 * @return void
	 */
	public function cosmDefination() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( is_plugin_active( 'bp-custom-order-status-for-woocommerce/main.php' ) ) {

			$this->cosm__title     = __( 'Check Options', 'bv-order-status' );
			$this->cosm_activate   = true;
			$this->cosm_plugin_url = apply_filters( 'cosm_admin_page', admin_url( 'admin.php?page=wcbv-order-status-setting' ) );

		} elseif ( file_exists( WP_PLUGIN_DIR . '/bp-custom-order-status-for-woocommerce/main.php' ) ) {

			$this->cosm__title     = __( 'Activate Now', 'bv-order-status' );
			$this->cosm_activate   = false;
			$this->cosm_plugin_url = wp_nonce_url( 'plugins.php?action=activate&plugin=bp-custom-order-status-for-woocommerce/main.php&plugin_status=all&paged=1', 'activate-plugin_bp-custom-order-status-for-woocommerce/main.php' );

		} else {

			$this->cosm__title     = __( 'Install Now', 'bv-order-status' );
			$this->cosm_activate   = false;
			$this->cosm_plugin_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=bp-custom-order-status-for-woocommerce' ), 'install-plugin_bp-custom-order-status-for-woocommerce' );

		}
	}
	/**
	 * Add links to plugin's description in plugins table
	 *
	 * @param  array   $links Initial list of links.
	 * @param  string  $file  Basename of current plugin.
	 * @return array
	 */
	public function pluginMetaLinks( $links, $file ) {
		if ( WCPO_PLUGIN_BASE !== $file ) {
			return $links;
		}
		$support_link = '<a style="color:red;" target="_blank" href="https://brightplugins.com/support/">' . __( 'Support', 'pre-orders-for-woocommerce' ) . '</a>';
		$review       = '<a target="_blank" title="Click here to rate and review this plugin on WordPress.org" href="https://wordpress.org/support/plugin/pre-orders-for-woocommerce/reviews/?filter=5"> Rate this plugin » </a>';

		$links[] = $support_link;
		$links[] = $review;

		return $links;
	} // plugin_meta_links
	/**
	 * @param  $email_classes
	 * @return mixed
	 */
	function wcpoAddPreordersCustomEmail( $email_classes ) {
		// include our custom email class
		require_once WCPO_PLUGIN_DIR . '/src/emails/class-wc-email-customer-preorder.php';
		require_once WCPO_PLUGIN_DIR . '/src/emails/class-wc-email-new-preorder.php';
		// add the email class to the list of email classes that WooCommerce loads

		$email_classes['WC_New_Customer_Pre_Order_Email'] = new \WC_Email_Customer_PreOrder();
		$email_classes['WC_New_Pre_Order_Email']          = new \WC_Email_New_Pre_Order();
		return $email_classes;
	}
	/**
	 * @return mixed
	 */
	public function preorderCount() {
		$args = array(
			'limit'        => 6,
			'meta_key'     => '_preorder_date',
			'meta_compare' => 'EXISTS',
			'return'       => 'ids',
		);

		$orders = wc_get_orders( $args );
		if ( !empty( $orders ) ) {
			return count( $orders );
		}

		return 0;
	}
	/**
	 * Leave Review Notice
	 *
	 * @return void
	 */
	public function review() {
		$dismiss_parm = array( 'preorder-review-dismiss' => '1' );
		$temp_dismiss = array( 'preorder-review-dismiss-temp' => '1' );

		if ( get_option( 'preorder-review-dismiss' ) || get_transient( 'preorder-review-dismiss-temp' ) ) {
			return;
		} elseif ( $this->preorderCount() >= 5 ) {?>
        <div class="notice notice-info bayna-review-notice">
			<h3><img draggable="false" class="emoji" alt="🎉" src="https://s.w.org/images/core/emoji/11/svg/1f389.svg"> Congrats! </h3>
        <p>You've just got more than 5 orders with <strong>Preorders for WooCommerce</strong>. that’s awesome!<br> Could you please do us a BIG favor and give the plugin a 5-star rating on WordPress to help us spread the word.</p>
        <p><strong>~ Bright Plugins</strong></p>

            <a style="margin-right:8px;" href="https://wordpress.org/support/plugin/pre-orders-for-woocommerce/reviews/?filter=5#new-post" target="_blank" class="button button-primary">Okay, you deserve it</a>
            <a style="margin-right:8px;" href="<?php echo esc_url( add_query_arg( $temp_dismiss ) ); ?>"  class="button button-primary">Nope, maybe later</a>
            <a href="<?php echo esc_url( add_query_arg( $dismiss_parm ) ); ?>" class="button">Hide this notice</a>
        <p></p>
        </div>
        <?php }
	}
	public function urlParamCheck() {
		if ( isset( $_GET['bp22-dismiss'] ) && 1 == $_GET['bp22-dismiss'] ) {
			update_option( 'bp22-dismiss', 1 );
		}
		if ( isset( $_GET['preorder-review-dismiss'] ) && 1 == $_GET['preorder-review-dismiss'] ) {
			update_option( 'preorder-review-dismiss', 1 );
		}
		if ( isset( $_GET['preorder-review-dismiss-temp'] ) && 1 == $_GET['preorder-review-dismiss-temp'] ) {
			set_transient( 'preorder-review-dismiss-temp', 1, 2 * WEEK_IN_SECONDS );
		}
	}
	/**
	 * @return null
	 */
	public function show_cosm_notice() {
		if ( $this->cosm_activate || !\PAnD::is_admin_notice_active( 'cosm-prfw-notice-35' ) ) {
			return;
		}

		?>
			<div data-dismissible="cosm-prfw-notice-35" class="info notice notice-info is-dismissible">
				<p><?php _e( 'Do you need full control over your Order Status Management? Try Bright Plugin\'s completely free <b>Custom Order Status Manager for WooCommerce</b> plugin. <a href="' . $this->cosm_plugin_url . '">' . $this->cosm__title . '</a>', 'pre-orders-for-woocommerce' );?></p>
			</div>
		<?php
}

	public function adminEnqueueScripts() {
		wp_enqueue_script(
			'preorders-field-date-js',
			WCPO_PLUGIN_URL . 'media/js/date-picker.js',
			['jquery', 'jquery-ui-core', 'jquery-ui-datepicker'],
			WCPO_PLUGIN_VER,
			true
		);
	}

	public function frontEnqueueStyles() {
		wp_register_style( 'woocommerce-pre-orders-main-css', WCPO_PLUGIN_URL . 'media/css/main.css', null, WCPO_PLUGIN_VER );
		wp_enqueue_style( 'woocommerce-pre-orders-main-css' );
		wp_enqueue_style( 'jquery-ui' );
		if ( is_checkout() ) {
			wp_enqueue_style( 'jquery-ui', WC()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', [], WCPO_PLUGIN_VER );
		}
	}

	public function frontEnqueueScripts() {
		wp_enqueue_script(
			'preorders-field-date-js',
			WCPO_PLUGIN_URL . 'media/js/date-picker.js',
			['jquery', 'jquery-ui-core', 'jquery-ui-datepicker'],
			WCPO_PLUGIN_VER,
			true
		);

		$data = [
			'default_add_to_cart_text'   => __( 'Add to cart', 'woocommerce' ),
			'preorders_add_to_cart_text' => get_option( 'wc_preorders_button_text' ),
		];

		wp_register_script(
			'preorders-main-js',
			WCPO_PLUGIN_URL . 'media/js/main.js',
			['jquery'],
			WCPO_PLUGIN_VER,
			true
		);

		wp_localize_script( 'preorders-main-js', 'DBData', $data );

		wp_enqueue_script(
			'preorders-main-js'
		);
	}

	public function adminEnqueueStyles() {
		wp_enqueue_style( 'jquery-ui' );
		wp_enqueue_style( 'preorder-admin', WCPO_PLUGIN_URL . 'media/css/admin.css', null, WCPO_PLUGIN_VER );
	}

	public function generatePreOrderStatus() {
		$statusManager = new StatusManager( [
			'statusName' => 'pre-ordered',
			'label'      => get_option( 'wc_preorders_status_text', 'Pre Ordered' ),
			'labelCount' => _n_noop( 'Pre Ordered <span class="count">(%s)</span>', 'Pre Ordered <span class="count">(%s)</span>', 'pre-orders-for-woocommerce' ),
		] );
		$statusManager->save();
	}

	public function initializeCheckout() {
		new Checkout();
	}

	public function initializeSync() {
		new Sync();
	}

	public function initializeTabs() {
		new Tabs();
	}

	public function initializeNotices() {
		new Notices();
	}

	public function initializeSettings() {
		new Settings();
	}

	public function initializeShop() {
		new Shop();
	}

	public function initializeOrder() {
		new Order();
	}
}
