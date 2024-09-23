<?php
/**
 * Plugin Name: Pre-Orders for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/pre-orders-for-woocommerce/
 * Description: Ultimate Preorders Plugin for WooCommerce.
 * Version: 2.0.1
 * Requires PHP: 7.2.0
 * Domain Path: /languages/
 * Requires Plugins: woocommerce
 * WC tested up to: 8.8.3
 * Tested up to: 6.5.3
 * WC requires at least: 4.0
 * Author: Bright Plugins
 * Author URI: https://brightplugins.com
 * Text Domain: pre-orders-for-woocommerce
 */

defined( 'ABSPATH' ) || exit;

// Define WCPO_PLUGIN_DIR.
if ( !defined( 'WCPO_PLUGIN_DIR' ) ) {
	define( 'WCPO_PLUGIN_DIR', __DIR__ );
}
if ( !defined( 'WCPO_PLUGIN_BASE' ) ) {
	define( 'WCPO_PLUGIN_BASE', plugin_basename( __FILE__ ) );
}
if ( !defined( 'WCPO_PLUGIN_URL' ) ) {
	define( 'WCPO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
define( 'WCPO_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
define( 'WCPO_PLUGIN_VER', '2.0.1' );

use Woocommerce_Preorders\Bootstrap;

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, false );
		}
} );

require_once WCPO_PLUGIN_DIR . '/vendor/autoload.php';
/**
 * Initialize the plugin tracker
 *
 */
function appsero_init_tracker_pre_orders_for_woocommerce() {

	$client = new Appsero\Client( '4ec1293f-9d9f-4a3d-b312-c93c19e16be8', 'Preorders for WooCommerce', __FILE__ );

	// Active insights
	$client->insights()->init();

}

appsero_init_tracker_pre_orders_for_woocommerce();

NS7_RDNC::instance()->add_notification( 152, '9d7bd777a9d8055e', 'https://brightplugins.com' );
final class Bright_Plugins_PFW {

	/**
	 * @var mixed
	 */
	static $instance = null;

	private function __construct() {

		$this->init_plugin();
	}

	/**
	 * Initializes a singleton instance
	 *
	 * @since 1.2.7
	 * @access public
	 * @static
	 *
	 * @return $instance
	 */
	public static function init() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the plugin
	 *
	 * @since 1.2.7
	 * @access public
	 *
	 * @return void
	 */
	/**
	 * @param $item_id
	 * @param $item
	 * @param $order
	 */
	/**
	 * @param $item_id
	 * @param $item
	 * @param $order
	 */
	public function init_plugin() {

		// Check if WooCommerce is active
		if ( defined( 'WC_VERSION' ) ) {

			$wcpo_bootstrap = Bootstrap::init();

		} else {
			add_action( 'admin_notices', function () {
				$class   = 'notice notice-error';
				$message = __( 'Oops! looks like WooCommerce is disabled. Please, enable it in order to use WooCommerce Pre-Orders.', 'pre-orders-for-woocommerce' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			} );
		}
	}

}
// A Custom function for get an preorder option
if ( !function_exists( 'bp_preorder_option' ) ) {
	/**
	 * @param $option
	 * @param $default
	 */
	function bp_preorder_option( $option = '', $default = null ) {
		$options = get_option( 'bp_preorder' ); // Attention: Set your unique id of the framework

		return ( isset( $options[$option] ) ) ? $options[$option] : $default;

	}
}
/**
 * Initializes the main plugin
 */
function Bright_Plugins_PFW_start() {
	return Bright_Plugins_PFW::init();
}
register_activation_hook( __FILE__, 'Woocommerce_Preorders\Settings::defaultOptions' );

add_action( 'plugins_loaded', 'Bright_Plugins_PFW_start' );
