<?php
namespace Woocommerce_Preorders;

class Settings {

    protected $pluginBase             = WCPO_PLUGIN_BASE;
    protected $text_disabled          = '__disabled';
    protected $url_to_premiun_version = 'https://brightplugins.com/product/woocommerce-pre-orders-plugin/?utm_source=freemium&utm_medium=settings_page&utm_campaign=upgrade_pro';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'bp_admin_menu' ) );
        add_action( 'admin_menu', array( $this, 'later' ), 99 );
        add_filter( "plugin_action_links_$this->pluginBase", [$this, 'wcpo_plugin_settings_link'] );

        $this->plugin_options();
        add_action( 'csf_bp_preorder_save_after', [$this, 'update_options'], 10, 1 );
    }

    /**
     * This function is responsible for creating the plugin options and sections for the Pre-Orders for WooCommerce plugin.
     *
     * @return void
     */
    public function plugin_options() {

        // Set a unique slug-like ID
        $prefix = 'bp_preorder';

        // Create options
        \CSF::createOptions( $prefix, array(
            'menu_title'      => __( 'Pre-Orders Settings', 'preorders-for-woocommerce' ),
            'menu_slug'       => 'pre-order-options',
            'framework_title' => 'Pre-Orders For WooCommerce <small>version-' . WCPO_PLUGIN_VER . '</small>',
            'menu_type'       => 'submenu',
            'menu_parent'     => 'brightplugins',
            'theme'           => 'dark',
            'show_footer'     => false,
            'defaults'        => $this->optDefaults(),
            'footer_credit'   => 'If you enjoy using <b>Pre-Orders for WooCommerce</b>, kindly leave us a <a target="_" href="https://wordpress.org/support/plugin/pre-orders-for-woocommerce/reviews/?filter=5#new-post">★★★★★</a> rating. Your review holds significant importance to us as it contributes to our continuous growth and improvement.', //TODO: add review LINK

            'show_bar_menu' => false,
            'ajax_save'       => true,
        ) );

        // Generals
        \CSF::createSection( $prefix, array(
            'title'  => __( 'General', 'pre-orders-for-woocommerce' ),
            'icon'   => 'fas fa-sliders-h',
            'fields' => array(

                array(
                    'title'    => false,
                    'type'     => 'callback',
                    'function' => 'Woocommerce_Preorders\Bootstrap::cosmNotice',
                ),

                array(
                    'type'       => 'submessage',
                    'style'      => 'danger',
                    'dependency' => array( 'wc_preorders_mode', '!=', 'either' ),
                    'content'    => '<i>This mode is exclusively available in the <a target="_blank" href="https://brightplugins.com/product/woocommerce-pre-orders-plugin/?utm_source=freemium&utm_medium=settings_page&utm_campaign=upgrade_pro">Pro Version</a>. To access this feature, we recommend upgrading to the Pro Version, which offers a wider range of functionalities and enhanced capabilities. </i>',
                ),

                // A text field
                array(
                    'title'   => __( 'Pre-Order Modes', 'pre-orders-for-woocommerce' ),
                    'type'    => 'select',
                    'options' => [
                        'either'                    => __( 'Order only preorder products or available ones', 'pre-orders-for-woocommerce' ),
                        'Available in Pro Version!' => array(
                            'whole'      => __( 'Treat the entire order as preordered', 'pre-orders-for-woocommerce' ),
                            'partial'    => __( 'Generate a separate order with all preordered products', 'pre-orders-for-woocommerce' ),
                            'individual' => __( 'Generate a separate order for each preordered products', 'pre-orders-for-woocommerce' ),
                        ),
                    ],
                    'default' => 'either',
                    'id'      => 'wc_preorders_mode',
                ),
                // A Notice

                array(
                    'title'    => __( 'Choose shipping date', 'pre-orders-for-woocommerce' ),
                    'type'     => 'switcher',
                    'subtitle' => __( 'New feature added', 'pre-orders-for-woocommerce' ) . " <i class='fas fa-unlock'></i>",
                    'desc'     => __( 'Allow customers to choose a desired shipping date on checkout page (after all products are available).', 'pre-orders-for-woocommerce' ),
                    'id'       => 'wc_preorders_always_choose_date',
                ),

                array(
                    'title'    => __( 'Enable Pre-Order Bagde', 'pre-orders-for-woocommerce' ),
                    'id'       => 'wc_preorder_badge_array',
                    'subtitle' => __( 'New feature added', 'pre-orders-for-woocommerce' ) . " <i class='fas fa-unlock'></i>",
                    'type'     => 'checkbox',
                    'options'  => array(
                        'wc_preorder_badge_single_product' => __( 'Single Product Page', 'pre-orders-for-woocommerce' ),
                        'wc_preorder_badge_shop_page'      => __( 'Shop Page', 'pre-orders-for-woocommerce' ),
                    ),
                ),
                array(
                    'title'      => __( 'Badge Position (Shop)', 'pre-orders-for-woocommerce' ),
                    'type'       => 'select',
                    'subtitle'   => __( 'New feature added', 'pre-orders-for-woocommerce' ) . " <i class='fas fa-unlock'></i>",
                    'default'    => 'woocommerce_after_shop_loop_item_title',
                    'options'    => [
                        'woocommerce_after_shop_loop_item_title'  => __( 'After Title (Recommended)', 'pre-orders-for-woocommerce' ),
                        'woocommerce_before_shop_loop_item_title' => __( 'Before Title', 'pre-orders-for-woocommerce' ),
                        'woocommerce_before_shop_loop_item'       => __( 'Before Shop Loop', 'pre-orders-for-woocommerce' ),
                        'woocommerce_after_shop_loop_item'        => __( 'After Shop Loop', 'pre-orders-for-woocommerce' ),
                    ],
                    'desc'       => __( 'Where the preorder badge should be displayed (Catalog Pages)', 'pre-orders-for-woocommerce' ),
                    'id'         => 'wc_preorder_loop_badge_position',
                    'dependency' => array( 'wc_preorder_badge_array', 'any', 'wc_preorder_badge_shop_page' ),
                ),

                array(
                    'title'         => __( 'Display the Available Date', 'pre-orders-for-woocommerce' ),
                    'id'            => 'wc_preorders_avaiable_date_array',
                    'type'          => 'checkbox',
                    'checkboxgroup' => 'start',
                    'options'       => array(
                        'wc_preorders_avaiable_date_single_product' => __( 'Single Product Page', 'pre-orders-for-woocommerce' ),
                        'wc_preorders_avaiable_date_loop'           => __( 'Catalog Page(s)', 'pre-orders-for-woocommerce' ),
                        'wc_preorders_avaiable_date_cart_item'      => __( 'Cart Page', 'pre-orders-for-woocommerce' ),

                    ),
                ),

                /**
                 * PRO features (disabled)
                 *
                 * @since 2.0.0
                 */
                array(
                    'title'    => __( 'Available Date Position', 'pre-orders-for-woocommerce' ),
                    'type'     => 'select',
                    'subtitle' => "Available in <a target='_blank' href='{$this->url_to_premiun_version}'>Pro Version!</a> <i class='fas fa-lock'></i>",
                    'class'    => 'cix-only-pro',
                    'desc'     => __( 'Choose the position where the Available date Text will be displayed on the Single Product Page.', 'pre-orders-for-woocommerce' ),
                    'options'  => [
                        'before' => __( 'Before the pre-order button', 'pre-orders-for-woocommerce' ),
                        'after'  => __( 'After the pre-order button', 'pre-orders-for-woocommerce' ),
                    ],
                    'id'       => 'wc_preorders_position_avaiable_date_text',
                ),

                array(
                    'title'    => __( 'Pre-Order Transition Status', 'pre-orders-for-woocommerce' ),
                    'type'     => 'select',
                    'class'    => 'wc-enhanced-select  cix-only-pro',
                    'subtitle' => "Available in <a target='_blank' href='{$this->url_to_premiun_version}'>Pro Version!</a> <i class='fas fa-lock'></i>",
                    'desc'     => __( 'Choose which status should the order be set after the preorder date passed', 'pre-orders-for-woocommerce' ),
                    'options'  => wc_get_order_statuses(),
                    'default'  => 'wc-processing',
                    'id'       => 'wc_preorders_after_completed' . $this->text_disabled,
                ),

                array(
                    'title'       => __( 'Disable Payment Methods', 'pre-orders-for-woocommerce' ),
                    'type'        => 'checkbox',
                    'class'       => 'cix-only-pro',
                    'subtitle'    => 'Available in <a target="_blank" href="https://brightplugins.com/product/woocommerce-pre-orders-plugin/?utm_source=freemium&utm_medium=settings_page&utm_campaign=upgrade_pro">Pro Version!</a> <i class="fas fa-lock"></i>',
                    'desc'        => __( 'Disable Payment gateway if cart has pre-order product(s)', 'pre-orders-for-woocommerce' ),
                    'placeholder' => __( 'Select Payment method(s)', 'pre-orders-for-woocommerce' ),
                    'options'     => function () {
                        $active_gateways = array();
                        $gateways        = WC()->payment_gateways->payment_gateways();
                        foreach ( $gateways as $id => $gateway ) {
                            if ( 'yes' == $gateway->enabled ) {
                                $active_gateways[$id] = $gateway->title;
                            }
                        }
                        return $active_gateways;
                    },
                    'chosen'      => true,
                    'multiple'    => true,
                    'id'          => 'wc_preorders_disable_payment' . $this->text_disabled,
                ),
                array(
                    'type'       => 'notice',
                    'style'      => 'danger',
                    'dependency' => array( 'wc_preorders_mode', '==', 'individual' ),
                    'content'    => '"Generate a separate order for each preordered products preorder mode" is not compatible with <b>"Pre-Order Pay Later"</b>',
                ),
                array(
                    'title'    => __( 'Pre-Order Pay Later', 'pre-orders-for-woocommerce' ),
                    'type'     => 'switcher',
                    'class'    => 'cix-only-pro',
                    'subtitle' => 'Available in <a target="_blank" href="https://brightplugins.com/product/woocommerce-pre-orders-plugin/?utm_source=freemium&utm_medium=settings_page&utm_campaign=upgrade_pro">Pro Version!</a> <i class="fas fa-lock"></i>',
                    'help'     => __( 'Consider enabling the Pay Later gateway which allows customers to make payments on the available date instead of immediately place the order', 'pre-orders-for-woocommerce' ),
                    'id'       => 'wc_preorders_enable_paylater' . $this->text_disabled,
                ),
                array(
                    'title'    => __( 'Product Date Cycle', 'pre-orders-for-woocommerce' ),
                    'type'     => 'select',
                    'class'    => 'wc-enhanced-select  min-w-230 cix-only-pro',
                    'subtitle' => 'Available in <a target="_blank" href="https://brightplugins.com/product/woocommerce-pre-orders-plugin/?utm_source=freemium&utm_medium=settings_page&utm_campaign=upgrade_pro">Pro Version!</a> <i class="fas fa-lock"></i>',
                    'options'  => [
                        'instock'    => __( 'In Stock', 'pre-orders-for-woocommerce' ),
                        'outofstock' => __( 'Out of Stock', 'pre-orders-for-woocommerce' ),
                    ],
                    'desc'     => __( 'Once the preorder is finished on it\'s date cycle, the stock status will be automatically change to selected status', 'pre-orders-for-woocommerce' ),
                    'id'       => 'wc_preorders_product_date_cycle' . $this->text_disabled,
                ),
                array(
                    'title'    => __( 'Unify Shipping Costs', 'pre-orders-for-woocommerce' ),
                    'type'     => 'switcher',
                    'class'    => 'cix-only-pro',
                    'subtitle' => 'Available in <a target="_blank" href="https://brightplugins.com/product/woocommerce-pre-orders-plugin/?utm_source=freemium&utm_medium=settings_page&utm_campaign=upgrade_pro">Pro Version!</a> <i class="fas fa-lock"></i>',
                    'default'  => false,
                    'desc'     => __( 'If you have more than 1 order is generated, let the customer pay only 1 shipment for all orders.<br> This option will use the shipment method as if it were only 1 order.', 'pre-orders-for-woocommerce' ),
                    'id'       => 'wc_preorders_prevent_shipping_cost' . $this->text_disabled,
                ),
                array(
                    'title'      => __( 'Disable Payment Methods (Pay Later)', 'pre-orders-for-woocommerce' ),
                    'type'       => 'switcher',
                    'desc'       => __( 'When the "Pay Later" method is active, ensure that all payment methods are disabled.', 'pre-orders-for-woocommerce' ),
                    'dependency' => array( 'wc_preorders_enable_paylater', '==', 'true' ),
                    'id'         => 'paylater_hide_other_gateways' . $this->text_disabled,
                ),
                array(
                    'title'      => __( 'Pay Later Transition Status', 'pre-orders-for-woocommerce' ),
                    'type'       => 'select',
                    'class'      => 'wc-enhanced-select',
                    'desc'       => __( 'Choose which status should the order be set after the preorder date passed but order not paid by customer', 'pre-orders-for-woocommerce' ),
                    'options'    => wc_get_order_statuses(),
                    'default'    => 'wc-pending',
                    'dependency' => array( 'wc_preorders_enable_paylater|wc_preorders_mode', '==|!=', 'true|individual' ),
                    'id'         => 'pay_later_transition_status' . $this->text_disabled,
                ),
            ),
        ) );
        // Create a section
        \CSF::createSection( $prefix, array(
            'title'  => 'Text & Labels',
            'icon'   => 'fas fa-edit',
            'fields' => array(

                array(
                    'title' => __( 'Pre-Order Button Label', 'pre-orders-for-woocommerce' ),
                    'type'  => 'text',
                    'desc'  => __( 'Change button title for pre-order products', 'pre-orders-for-woocommerce' ),
                    'id'    => 'wc_preorders_button_text',
                ),
                array(
                    'title'   => __( 'Pre-Order Status Label', 'pre-orders-for-woocommerce' ),
                    'type'    => 'text',
                    'default' => 'Pre Ordered',
                    'desc'    => __( 'Change preorder status name', 'pre-orders-for-woocommerce' ),
                    'id'      => 'preorder_status_label',
                ),
                array(
                    'title'   => __( 'Pre-Order Cart Notice', 'pre-orders-for-woocommerce' ),
                    'type'    => 'text',
                    'default' => 'Note: this item will be available for shipping in {days_left} days',
                    'desc'    => '
                        Text will be shown below the preoder product on the cart page<br/>
                        <strong>Available variables:</strong><br/>
                        <code>{days_left}</code> = Count preorder date<br/>
                        <code>{human_readable}</code> = 3 weeks - Available in Pro Version!<br/>
                        <code>{date_format}</code> = Default site date format ( January 15, 2020 ) - Available in Pro Version!',

                    'id'      => 'wc_preorders_cart_product_text',

                ),
                array(
                    'title'   => __( 'Available Date Label', 'pre-orders-for-woocommerce' ),
                    'type'    => 'text',
                    'default' => 'Available on {date_format}',
                    'desc'    => '
                        Choose how the available date should be displayed. You can use 3 different variables inside the text,<br/> to show either the normal date, the amount of weeks left, or a dynamic countdown. For more information please visit this <a href="https://brightplugins.com/docs/dynamic-vars/" target="_blank">docs page</a><br/>
                        <strong>Available variables:</strong><br/>
                        <code>{date_format}</code> = Default site date format ( January 15, 2020 ) <br>
                        <code>{human_readable}</code> = 3 weeks - Available in Pro Version! <br>
                        <code>{countdown}</code> = Countdown Timer - Available in Pro Version!',
                    'id'      => 'wc_preorders_avaiable_date_text',
                ),
                /**
                 * PRO features (disabled)
                 *
                 * @since 2.0.0
                 */
                array(
                    'title'    => __( 'Countdown Format', 'pre-orders-for-woocommerce' ),
                    'type'     => 'text',
                    'desc'     => '%d days, %h hours, %i minutes, %s seconds',
                    'id'       => 'wc_preorders_countdown_format' . $this->text_disabled,
                    'class'    => 'cix-only-pro',
                    'subtitle' => 'Available in <a target="_blank" href="https://brightplugins.com/product/woocommerce-pre-orders-plugin/?utm_source=freemium&utm_medium=settings_page&utm_campaign=upgrade_pro">Pro Version!</a> <i class="fas fa-lock"></i>',
                ),
                array(
                    'title'    => __( 'Pre-Order Bagde Label', 'pre-orders-for-woocommerce' ),
                    'type'     => 'text',
                    'default'  => 'Pre-Order',
                    'subtitle' => "Available in <a target='_blank' href='{$this->url_to_premiun_version}'>Pro Version!</a> <i class='fas fa-lock'></i>",
                    'class'    => 'cix-only-pro',
                    'id'       => 'wc_preorders_badge_text' . $this->text_disabled,

                ),
                array(
                    'title'       => __( 'Pre-Order Note', 'pre-orders-for-woocommerce' ),
                    'type'        => 'text',
                    'placeholder' => __( 'The Pre-order date has been changed to {date_format}', 'pre-orders-for-woocommerce' ),
                    'default'     => 'The Pre-order date has been changed to {date_format}',
                    'desc'        => __( 'Add a note to customer if pre-order date change from order dashboard<br/> <strong>Available variable:</strong><br/> <code>{date_format}</code> = Default site date format ( January 15, 2020 )', 'pre-orders-for-woocommerce' ),
                    'subtitle'    => "Available in <a target='_blank' href='{$this->url_to_premiun_version}'>Pro Version!</a> <i class='fas fa-lock'></i>",
                    'class'       => 'cix-only-pro',
                    'id'          => 'preorder_date_order_note' . $this->text_disabled,
                ),
                array(
                    'title'    => __( 'Regular Product Notice', 'pre-orders-for-woocommerce' ),
                    'type'     => 'textarea',

                    'default'  => 'We detected that your cart has pre-order products. Please remove them before being able to add this product.',
                    'subtitle' => "Available in <a target='_blank' href='{$this->url_to_premiun_version}'>Pro Version!</a> <i class='fas fa-lock'></i>",
                    'class'    => 'cix-only-pro',
                    'id'       => 'regular_cart_error_notice' . $this->text_disabled,
                ),
                array(
                    'title'    => __( 'Pre-Order Product Notice', 'pre-orders-for-woocommerce' ),
                    'type'     => 'textarea',
                    'default'  => 'We detected that you are trying to add a pre-order product to your cart. Please remove the rest of the product(s) from the cart before being able to add this product.',
                    'subtitle' => "Available in <a target='_blank' href='{$this->url_to_premiun_version}'>Pro Version!</a> <i class='fas fa-lock'></i>",
                    'class'    => 'cix-only-pro',
                    'id'       => 'preorder_cart_error_notice' . $this->text_disabled,
                ),

            ),
        ) );
        // Notifications
        \CSF::createSection( $prefix, array(
            'title'  => __( 'Notifications', Bootstrap::TEXT_DOMAIN ),
            'icon'   => 'far fa-bell',
            'fields' => array(

                /**
                 * PRO features (disabled)
                 */
                array(
                    'title'    => __( 'Admin Notification', 'pre-orders-for-woocommerce' ),
                    'type'     => 'number',
                    'class'    => 'max-w-70',
                    'default'  => '0',
                    'class'    => 'cix-only-pro',
                    'subtitle' => 'Available in <a target="_blank" href="https://brightplugins.com/product/woocommerce-pre-orders-plugin/?utm_source=freemium&utm_medium=settings_page&utm_campaign=upgrade_pro">Pro Version!</a> <i class="fas fa-lock"></i>',
                    'desc'     => __( 'Amount of days in advance that admins will get an e-mail notification when a preorder will be available.<br/> Leave 0 if you want to leave this feature disabled', 'pre-orders-for-woocommerce' ),
                    'id'       => 'wc_preorders_alert' . $this->text_disabled,
                ),
                array(
                    'title'    => __( 'Customer Notification', 'pre-orders-for-woocommerce' ),
                    'type'     => 'switcher',
                    'class'    => 'cix-only-pro',
                    'subtitle' => 'Available in <a target="_blank" href="https://brightplugins.com/product/woocommerce-pre-orders-plugin/?utm_source=freemium&utm_medium=settings_page&utm_campaign=upgrade_pro">Pro Version!</a> <i class="fas fa-lock"></i>',
                    'desc'     => __( 'When preorder date will arrived customers will get email notification with order details', 'pre-orders-for-woocommerce' ),
                    'id'       => 'wc_preorder_customer_notification' . $this->text_disabled,
                ),
                array(
                    'title'    => __( 'Update Date Notification', 'pre-orders-for-woocommerce' ),
                    'type'     => 'switcher',
                    'class'    => 'cix-only-pro',
                    'subtitle' => 'Available in <a target="_blank" href="https://brightplugins.com/product/woocommerce-pre-orders-plugin/?utm_source=freemium&utm_medium=settings_page&utm_campaign=upgrade_pro">Pro Version!</a> <i class="fas fa-lock"></i>',
                    'desc'     => __( 'Send email notification to customers when preorder date is updated by using bulk-edit mode', 'pre-orders-for-woocommerce' ),
                    'id'       => 'preoder_bulk_edit_notification' . $this->text_disabled,
                ),
            ),
        ) );

        // Email Tempaltes
        \CSF::createSection( $prefix, array(
            'title'  => __( 'Email Templates', 'pre-orders-for-woocommerce' ),
            'icon'   => 'fas fa-envelope',
            'fields' => array(

                // A Callback Field Example
                array(
                    'type'     => 'callback',
                    'function' => [$this, 'wcPreOrderEmailList'],
                ),
            ),
        ) );

    }
    public function bp_admin_menu() {

        add_menu_page( 'Bright Plugins', 'Bright Plugins', 'manage_options', 'brightplugins', null, plugin_dir_url( __DIR__ ) . '/media/img/bp-logo-icon.png', 60 );

        //do_action( 'bp_sub_menu' );
    }
    public function later() {

        //add_submenu_page( 'brightplugins', '', __( 'Pre-Orders Settings', 'pre-orders-for-woocommerce' ), 'manage_options', admin_url( 'admin.php?page=wc-settings&tab=settings_tab_preorders' ) );
        remove_submenu_page( 'brightplugins', 'brightplugins' );

    }
    /**
     * @param $links
     */
    public function wcpo_plugin_settings_link( $links ) {
        $row_meta = array(
            'settings' => '<a href="' . get_admin_url( null, 'admin.php?page=pre-order-options' ) . '">' . __( 'Settings', 'pre-orders-for-woocommerce' ) . '</a>',
            'pro_link' => '<a href="' . esc_url( 'https://brightplugins.com/product/woocommerce-pre-orders-plugin/?utm_source=freemium&utm_medium=plugins&utm_campaign=upgrade_pro' ) . '" target="_blank" aria-label="' . esc_attr__( 'Pro Version', 'wpgs' ) . '" style="color:green;font-weight:600;">' . esc_html__( 'Pro Version', 'pre-orders-for-woocommerce' ) . '</a>',
        );

        return array_merge( $links, $row_meta );
    }

    /**
     * todo: need to remove this function
     * @param  $settings_tabs
     * @return mixed
     */
    public function addSettingsTab( $settings_tabs ) {
        $settings_tabs['settings_tab_preorders'] = __( 'Pre-Orders', 'pre-orders-for-woocommerce' );

        return $settings_tabs;
    }

    /**
     * Sets the default options for the pre-order plugin.
     *
     * @return void
     */
    public static function defaultOptions() {
        $defaultOptions = [
            'wc_preorders_button_text'        => 'Pre Order Now!',
            'wc_preorders_always_choose_date' => 'yes',
            'wc_preorders_mode'               => 'either',
            'wc_preorders_multiply_shipping'  => 'no',
            'wc_preorders_is_pro'             => 'no',
        ];

        foreach ( $defaultOptions as $option => $value ) {
            if ( !get_option( $option ) || '' === get_option( $option ) ) {
                update_option( $option, $value );
            }
        }
    }

    /**
     * This function generates all the content for the Email Templates tab within the plugin settings
     * 
     * @since 2.0.0
     * 
     * @return void
     */
    public function wcPreOrderEmailList() {

        $templates = [
            'WC_New_Pre_Order_Email'                                => 'New Pre-order - Admin',
            'WC_New_Customer_Pre_Order_Email'                       => 'Pre-Order Order - Customers',
            'WC_Pre_Order_Alert' . $this->text_disabled             => 'Pre-order Alert - Admin',
            'WC_Preorder_Ready' . $this->text_disabled              => 'Pre-order available today - Customers',
            'WC_Preorder_Paylater_available' . $this->text_disabled => 'Pre-Order Order (Pay later) - Customers',
        ];
        foreach ( $templates as $key => $template_name ) {

			if( strpos( $key, $this->text_disabled ) !== false ) { 
				$this->generate_premium_row_disabled_for_email_templates_tab( $template_name );
				continue;
			}

            echo '<a class="csf-email-templates" href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=' . $key ) ) . '">' . $template_name . '</a>';
        }
    }
    /**
     * @param $data
     */
    public function update_options( $data ) {
        $old_options = [
            'wc_preorders_button_text'        => $data['wc_preorders_button_text'],
            'wc_preorders_cart_product_text'  => $data['wc_preorders_cart_product_text'],
            'wc_preorders_avaiable_date_text' => $data['wc_preorders_avaiable_date_text'],
            'wc_preorders_status_text'        => $data['preorder_status_label'],
        ];
        foreach ( $old_options as $old_option => $value ) {
            update_option( $old_option, $value );
        }
    }
    /**
     * Returns an array of default options for the plugin from the database [wc_preorders_*] (if exists).
     *
     * @return array The default options.
     */
    public function optDefaults() {
        $opts = array(
            'wc_preorders_mode'               => get_option( 'wc_preorders_mode', 'either' ),
            'wc_preorders_button_text'        => get_option( 'wc_preorders_button_text', 'Pre Order Now!' ),
            'wc_preorders_cart_product_text'  => get_option( 'wc_preorders_cart_product_text', 'Note: this item will be available for shipping in {days_left} days' ),
            'wc_preorders_avaiable_date_text' => get_option( 'wc_preorders_avaiable_date_text', 'Available on {date_format}' ),
            'preorder_status_label'           => get_option( 'wc_preorders_status_text', 'Pre Ordered' ),
        );
        return $opts;
    }

	/**
	 * Generates premiun row disabled for email templates tab
	 * 
	 * @since 2.0.0
	 * 
	 * @return void
	 */
	public function generate_premium_row_disabled_for_email_templates_tab( $template_name ) {
		
		?><div class="csf-email-templates  cix-only-pro" style="color: black;cursor: not-allowed;" href='#'>
			<div><?php echo $template_name; ?></div>
			<div class="csf-subtitle-text">
				Available in <a style="text-decoration: none;" href='<?php echo $this->url_to_premiun_version; ?>'>Pro Version! <i class='fas fa-lock'></i></a>
			</div>
		</div><?php 
	}
}
