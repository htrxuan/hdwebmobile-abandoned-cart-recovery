<?php

namespace htrxuan\hdcart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Marks an abandoned cart as recovered once its email places a real order.
 * woocommerce_checkout_order_processed fires for orders placed through both the
 * classic checkout and the Blocks/Store API checkout -- both ultimately go through
 * the same core order-processing code path.
 */
class HDCART_Order_Watcher
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
        add_action('woocommerce_checkout_order_processed', array($this, 'maybe_mark_recovered'), 10, 3);
    }

    public function maybe_mark_recovered($order_id, $posted_data, $order)
    {
        $email = $order->get_billing_email();

        if (!is_email($email)) {
            return;
        }

        $pending = HDCART_Repository::get_pending_by_email($email);

        foreach ($pending as $cart_row) {
            HDCART_Repository::mark_recovered($cart_row->id, $order_id);
            as_unschedule_action('hdcart_send_recovery', array($cart_row->session_key), 'hdcart');
        }
    }
}
