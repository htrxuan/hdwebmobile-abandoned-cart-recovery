<?php

namespace htrxuan\hdcart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Captures the customer's email as soon as it's entered at checkout -- before the
 * order is placed -- so an abandoned cart is still recoverable. Three independent
 * capture points are hooked, confirmed live in this environment via browser network
 * inspection (not just docs): the Blocks Checkout page calls the Store API's own
 * /checkout endpoint (not /cart/update-customer) whenever a field is edited, which
 * fires a distinct hook from the Cart block's own customer-update route. Both are
 * hooked here since either could fire depending on which block/flow is active, and
 * classic-checkout stores must not be left uncovered either:
 *
 * - woocommerce_store_api_checkout_update_customer_from_request: fires on the
 *   Checkout Block's own /wc/store/v1/checkout Store API route -- confirmed via
 *   live network inspection to be the one that actually fires while a guest is
 *   filling out this environment's real Checkout Block page.
 * - woocommerce_store_api_cart_update_customer_from_request: fires on the Cart
 *   Block's own /cart/update-customer Store API route (confirmed firing live in
 *   this environment in an earlier, separate test).
 * - woocommerce_checkout_update_order_review: fires on the classic checkout's own
 *   "update order review" AJAX call, triggered on blur/change of any checkout field.
 */
class HDCART_Capture
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('woocommerce_store_api_checkout_update_customer_from_request', array($this, 'capture_from_store_api'), 10, 2);
        add_action('woocommerce_store_api_cart_update_customer_from_request', array($this, 'capture_from_store_api'), 10, 2);
        add_action('woocommerce_checkout_update_order_review', array($this, 'capture_from_classic_checkout'));
    }

    public function capture_from_store_api($customer, $request)
    {
        $this->maybe_capture($customer->get_billing_email(), trim($customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name()));
    }

    public function capture_from_classic_checkout($posted_data)
    {
        parse_str($posted_data, $fields);

        $email = isset($fields['billing_email']) ? sanitize_email(wp_unslash($fields['billing_email'])) : '';
        $name  = isset($fields['billing_first_name']) ? sanitize_text_field(wp_unslash($fields['billing_first_name'])) : '';

        $this->maybe_capture($email, $name);
    }

    private function maybe_capture($email, $name)
    {
        if (!is_email($email)) {
            return;
        }

        if (!WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        $options = HDCART_Admin::get_options();
        if (empty($options['enabled'])) {
            return;
        }

        $session_key = WC()->session->get_customer_id();
        $items       = array();

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            if (!$product) {
                continue;
            }

            $items[] = array(
                'product_id'   => $cart_item['product_id'],
                'variation_id' => $cart_item['variation_id'],
                'name'         => $product->get_name(),
                'qty'          => (int) $cart_item['quantity'],
                'line_total'   => (float) $cart_item['line_total'],
            );
        }

        if (empty($items)) {
            return;
        }

        $id = HDCART_Repository::upsert_cart(
            $session_key,
            $email,
            $name,
            $items,
            WC()->cart->get_total('edit'),
            get_woocommerce_currency()
        );

        $this->schedule_recovery($session_key, $id, (int) $options['delay_hours']);
    }

    private function schedule_recovery($session_key, $id, $delay_hours)
    {
        if (as_has_scheduled_action('hdcart_send_recovery', array($session_key), 'hdcart')) {
            return;
        }

        as_schedule_single_action(
            time() + max(1, $delay_hours) * HOUR_IN_SECONDS,
            'hdcart_send_recovery',
            array($session_key),
            'hdcart'
        );
    }
}
