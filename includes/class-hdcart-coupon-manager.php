<?php

namespace htrxuan\hdcart;

if (!defined('ABSPATH')) {
    exit;
}

class HDCART_Coupon_Manager
{

    /**
     * @param string $email
     * @param string $type        'percent'|'fixed_cart'
     * @param float  $amount
     * @param int    $expiry_days
     * @return \WC_Coupon|null Null if the coupon could not be created.
     */
    public static function create_recovery_coupon($email, $type, $amount, $expiry_days)
    {
        $coupon = new \WC_Coupon();
        $coupon->set_code('CART-' . strtoupper(wp_generate_password(8, false, false)));
        $coupon->set_discount_type('percent' === $type ? 'percent' : 'fixed_cart');
        $coupon->set_amount((float) $amount);
        $coupon->set_individual_use(true);
        $coupon->set_usage_limit(1);
        $coupon->set_date_expires(strtotime('+' . absint($expiry_days) . ' days'));
        $coupon->add_meta_data('_hdcart_email', $email, true);

        $id = $coupon->save();

        return $id ? $coupon : null;
    }
}
