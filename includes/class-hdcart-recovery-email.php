<?php

namespace htrxuan\hdcart;

if (!defined('ABSPATH')) {
    exit;
}

class HDCART_Recovery_Email extends \WC_Email
{

    public function __construct()
    {
        $this->id             = 'hdcart_recovery';
        $this->customer_email = true;
        $this->title          = __('Abandoned Cart Recovery', 'hdwebmobile-abandoned-cart-recovery');
        $this->description    = __('Sent to a customer who added items to their cart but did not complete checkout.', 'hdwebmobile-abandoned-cart-recovery');
        $this->template_html  = 'hdcart-recovery.php';
        $this->template_plain = 'plain/hdcart-recovery.php';
        $this->template_base  = HDCART_PLUGIN_DIR . 'emails/';
        $this->placeholders   = array(
            '{cart_items}'  => '',
            '{cart_total}'  => '',
            '{checkout_url}' => '',
            '{coupon_code}' => '',
        );

        parent::__construct();
    }

    public function get_default_subject()
    {
        return __('You left something in your cart!', 'hdwebmobile-abandoned-cart-recovery');
    }

    public function get_default_heading()
    {
        return __('Still thinking it over?', 'hdwebmobile-abandoned-cart-recovery');
    }

    /**
     * Send the recovery email for a single abandoned cart row.
     *
     * @param object      $cart_row    Row from HDCART_Repository (email, cart_contents, cart_total, ...).
     * @param string|null $coupon_code Optional discount code to include.
     * @return bool True if the email was sent.
     */
    public function trigger($cart_row, $coupon_code = null)
    {
        $this->setup_locale();

        $items = json_decode($cart_row->cart_contents, true);
        if (!is_array($items)) {
            $items = array();
        }

        $this->object                             = $cart_row;
        $this->recipient                          = $cart_row->email;
        $this->placeholders['{cart_items}']       = $this->format_items_list($items);
        $this->placeholders['{cart_total}']       = wc_price($cart_row->cart_total, array('currency' => $cart_row->currency));
        $this->placeholders['{checkout_url}']     = wc_get_checkout_url();
        $this->placeholders['{coupon_code}']      = $coupon_code ? $coupon_code : '';

        if (!$this->is_enabled() || !$this->get_recipient()) {
            $this->restore_locale();
            return false;
        }

        $sent = $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

        $this->restore_locale();

        return $sent;
    }

    private function format_items_list(array $items)
    {
        $lines = array();

        foreach ($items as $item) {
            $lines[] = sprintf('%1$s &times; %2$d', esc_html($item['name']), (int) $item['qty']);
        }

        return implode('<br>', $lines);
    }

    public function get_content_html()
    {
        return wc_get_template_html(
            $this->template_html,
            array(
                'cart_row'           => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'cart_items'         => $this->placeholders['{cart_items}'],
                'cart_total'         => $this->placeholders['{cart_total}'],
                'checkout_url'       => $this->placeholders['{checkout_url}'],
                'coupon_code'        => $this->placeholders['{coupon_code}'],
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'email'              => $this,
            ),
            '',
            $this->template_base
        );
    }

    public function get_content_plain()
    {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'cart_row'           => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'cart_items'         => $this->placeholders['{cart_items}'],
                'cart_total'         => $this->placeholders['{cart_total}'],
                'checkout_url'       => $this->placeholders['{checkout_url}'],
                'coupon_code'        => $this->placeholders['{coupon_code}'],
                'sent_to_admin'      => false,
                'plain_text'         => true,
                'email'              => $this,
            ),
            '',
            $this->template_base
        );
    }

    public function get_default_additional_content()
    {
        return __('If you have any questions, just reply to this email — we\'re happy to help.', 'hdwebmobile-abandoned-cart-recovery');
    }
}
