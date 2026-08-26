<?php

/**
 * Abandoned cart recovery email (plain text).
 *
 * @var object $cart_row
 * @var string $email_heading
 * @var string $additional_content
 * @var string $cart_items
 * @var string $cart_total
 * @var string $checkout_url
 * @var string $coupon_code
 */

if (!defined('ABSPATH')) {
    exit;
}

echo esc_html(wp_strip_all_tags($email_heading)) . "\n\n";

esc_html_e('You left the following in your cart:', 'hdwebmobile-abandoned-cart-recovery');
echo "\n" . esc_html(wp_strip_all_tags(str_replace('<br>', "\n", $cart_items))) . "\n\n";

esc_html_e('Total:', 'hdwebmobile-abandoned-cart-recovery');
echo ' ' . esc_html(wp_strip_all_tags($cart_total)) . "\n\n";

if ($coupon_code) {
    printf(
        /* translators: %s: discount code */
        esc_html__('Use code %s at checkout for a discount.', 'hdwebmobile-abandoned-cart-recovery'),
        esc_html($coupon_code)
    );
    echo "\n\n";
}

esc_html_e('Complete your order:', 'hdwebmobile-abandoned-cart-recovery');
echo "\n" . esc_html($checkout_url) . "\n\n";

if ($additional_content) {
    echo esc_html(wp_strip_all_tags($additional_content)) . "\n";
}
