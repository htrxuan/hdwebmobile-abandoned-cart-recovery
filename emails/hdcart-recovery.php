<?php

/**
 * Abandoned cart recovery email (HTML).
 *
 * @var object $cart_row
 * @var string $email_heading
 * @var string $additional_content
 * @var string $cart_items
 * @var string $cart_total
 * @var string $checkout_url
 * @var string $coupon_code
 * @var bool $sent_to_admin
 * @var bool $plain_text
 * @var \htrxuan\hdcart\HDCART_Recovery_Email $email
 */

if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, not ours to prefix
?>

<p><?php esc_html_e('You left the following in your cart:', 'hdwebmobile-abandoned-cart-recovery'); ?></p>

<p><?php echo wp_kses_post($cart_items); ?></p>

<p>
    <strong><?php esc_html_e('Total:', 'hdwebmobile-abandoned-cart-recovery'); ?></strong>
    <?php echo wp_kses_post($cart_total); ?>
</p>

<?php if ($coupon_code) : ?>
    <p>
        <?php
        printf(
            /* translators: %s: discount code */
            esc_html__('Use code %s at checkout for a discount.', 'hdwebmobile-abandoned-cart-recovery'),
            '<strong>' . esc_html($coupon_code) . '</strong>'
        );
        ?>
    </p>
<?php endif; ?>

<p style="text-align: center; margin: 24px 0;">
    <a href="<?php echo esc_url($checkout_url); ?>" class="button" style="background-color:#05A67D;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">
        <?php esc_html_e('Complete Your Order', 'hdwebmobile-abandoned-cart-recovery'); ?>
    </a>
</p>

<?php
if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}
?>

<?php
do_action('woocommerce_email_footer', $email); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, not ours to prefix
