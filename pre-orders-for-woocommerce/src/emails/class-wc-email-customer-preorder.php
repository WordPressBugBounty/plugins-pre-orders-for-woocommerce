<?php
/**
 * Class WC_Email_Customer_PreOrder file.
 *
 * @package WooCommerce\Emails
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (! class_exists('WC_Email_Customer_PreOrder', false)) :

    /**
     * Customer Preorder  Email.
     *
     * An email sent to the customer when a new order is preorder for.

     * @package     WooCommerce\Classes\Emails
     * @extends     WC_Email
     */
    class WC_Email_Customer_PreOrder extends WC_Email
    {

        /**
         * Constructor.
         */
        public function __construct()
        {
            $this->id             = 'new_customer_preorder';
            $this->customer_email = true;
            $this->title          = __('Pre-Order Order', 'pre-orders-for-woocommerce');
            $this->description    = __('This is an order notification sent to customers containing order details after an order is placed Pre-order.', 'pre-orders-for-woocommerce');
            $this->template_html  = 'emails/customer-preorder.php';
            $this->template_plain = 'emails/plain/customer-preorder.php';
            $this->placeholders   = array(
                '{order_date}'   => '',
                '{order_number}' => '',
            );

            // Triggers for this email.
            add_action('woocommerce_order_status_pending_to_pre-ordered_notification', array( $this, 'trigger' ), 10, 2);
            add_action('woocommerce_order_status_failed_to_pre-ordered_notification', array( $this, 'trigger' ), 10, 2);
            add_action('woocommerce_order_status_cancelled_to_pre-ordered_notification', array( $this, 'trigger' ), 10, 2);
           

            // Call parent constructor.
            parent::__construct();

            $this->template_base  = WCPO_TEMPLATE_PATH;
        }

        /**
         * Get email subject.
         *
         * @since  3.1.0
         * @return string
         */
        public function get_default_subject()
        {
            return __('Your {site_title} Pre-Order has been received!', 'pre-orders-for-woocommerce');
        }

        /**
         * Get email heading.
         *
         * @since  3.1.0
         * @return string
         */
        public function get_default_heading()
        {
            return __('Thank you for your pre-order', 'pre-orders-for-woocommerce');
        }

        /**
         * Trigger the sending of this email.
         *
         * @param int            $order_id The order ID.
         * @param WC_Order|false $order Order object.
         */
        public function trigger($order_id, $order = false)
        {
            $this->setup_locale();

            if ($order_id && ! is_a($order, 'WC_Order')) {
                $order = wc_get_order($order_id);
            }

            if (is_a($order, 'WC_Order')) {
                $this->object                         = $order;
                $this->recipient                      = $this->object->get_billing_email();
                $this->placeholders['{order_date}']   = wc_format_datetime($this->object->get_date_created());
                $this->placeholders['{order_number}'] = $this->object->get_order_number();
            }

            if ($this->is_enabled() && $this->get_recipient()) {
                $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
            }

            $this->restore_locale();
        }

        /**
         * Get content html.
         *
         * @return string
         */
        public function get_content_html()
        {
            return wc_get_template_html(
                $this->template_html,
                array(
                    'order'              => $this->object,
                    'email_heading'      => $this->get_heading(),
                    'additional_content' => $this->get_additional_content(),
                    'sent_to_admin'      => false,
                    'plain_text'         => false,
                    'email'              => $this,
                ),
                '',
                $this->template_base
            );
        }

        /**
         * Get content plain.
         *
         * @return string
         */
        public function get_content_plain()
        {
            return wc_get_template_html(
                $this->template_plain,
                array(
                    'order'              => $this->object,
                    'email_heading'      => $this->get_heading(),
                    'additional_content' => $this->get_additional_content(),
                    'sent_to_admin'      => false,
                    'plain_text'         => true,
                    'email'              => $this,
                ),
                '',
                $this->template_base
            );
        }

        /**
         * Default content to show below main email content.
         *
         * @since 3.7.0
         * @return string
         */
        public function get_default_additional_content()
        {
            return __('We look forward to fulfilling your pre-order soon.', 'pre-orders-for-woocommerce');
        }
    }

endif;
return new WC_Email_Customer_PreOrder();
