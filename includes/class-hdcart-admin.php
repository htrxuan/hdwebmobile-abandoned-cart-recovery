<?php

namespace htrxuan\hdcart;

if (!defined('ABSPATH')) {
    exit;
}

class HDCART_Admin
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
        require_once HDCART_PLUGIN_DIR . 'includes/class-hdcart-hub.php';
        add_filter('hdwebmobile_hub_tabs', array($this, 'register_hub_tabs'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets($hook)
    {
        if ('woocommerce_page_hdwebmobile' !== $hook) {
            return;
        }

        // Read-only tab selector, same pattern as core's own admin tab UIs -- no state
        // change occurs from reading it, so nonce verification doesn't apply here.
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ('abandoned-carts' === $tab) {
            wp_enqueue_style('hdcart-admin-css', HDCART_PLUGIN_URL . 'assets/css/hdcart-admin.css', array(), HDCART_VERSION);
        }

        if ('cart-recovery' === $tab) {
            wp_enqueue_script('hdcart-admin-js', HDCART_PLUGIN_URL . 'assets/js/hdcart-admin.js', array(), HDCART_VERSION, true);
        }
    }

    public function register_hub_tabs($tabs)
    {
        $tabs['abandoned-carts'] = array(
            'label'  => __('Abandoned Carts', 'hdwebmobile-abandoned-cart-recovery'),
            'order'  => 10,
            'render' => array($this, 'render_carts_page'),
        );
        $tabs['cart-recovery'] = array(
            'label'  => __('Cart Recovery', 'hdwebmobile-abandoned-cart-recovery'),
            'order'  => 20,
            'render' => array($this, 'render_settings_page'),
        );
        return $tabs;
    }

    public function render_carts_page()
    {
        require_once HDCART_PLUGIN_DIR . 'includes/class-hdcart-admin-list-table.php';

        $table = new HDCART_Admin_List_Table();
        $table->prepare_items();
        ?>
        <p><?php esc_html_e('Recovers abandoned WooCommerce carts with a scheduled email, correctly capturing the customer\'s email from both the Blocks and classic checkout. Carts captured so far are listed below.', 'hdwebmobile-abandoned-cart-recovery'); ?></p>
        <form method="get">
            <input type="hidden" name="page" value="hdwebmobile" />
            <input type="hidden" name="tab" value="abandoned-carts" />
            <?php
            $table->search_box(__('Search email', 'hdwebmobile-abandoned-cart-recovery'), 'hdcart-cart-search');
            $table->display();
            ?>
        </form>
        <?php
    }

    public function render_settings_page()
    {
        ?>
        <p><?php esc_html_e('Configure when the recovery email is sent, and optionally include a discount code to encourage the customer to complete their order.', 'hdwebmobile-abandoned-cart-recovery'); ?></p>
        <form method="post" action="options.php">
            <?php
            settings_fields('hdcart_option_group');
            do_settings_sections('hdcart-settings');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function page_init()
    {
        register_setting(
            'hdcart_option_group',
            'hdcart_options',
            array(
                'type'              => 'array',
                'sanitize_callback' => array($this, 'sanitize'),
                'default'           => array(),
            )
        );

        add_settings_section(
            'hdcart_section_general',
            __('General', 'hdwebmobile-abandoned-cart-recovery'),
            '__return_false',
            'hdcart-settings'
        );

        add_settings_field('enabled', __('Enable Cart Recovery', 'hdwebmobile-abandoned-cart-recovery'), array($this, 'enabled_callback'), 'hdcart-settings', 'hdcart_section_general');
        add_settings_field('delay_hours', __('Send recovery email after (hours)', 'hdwebmobile-abandoned-cart-recovery'), array($this, 'delay_callback'), 'hdcart-settings', 'hdcart_section_general');

        add_settings_section(
            'hdcart_section_discount',
            __('Discount Incentive', 'hdwebmobile-abandoned-cart-recovery'),
            array($this, 'print_discount_section_info'),
            'hdcart-settings'
        );

        add_settings_field('discount_enabled', __('Include a discount code', 'hdwebmobile-abandoned-cart-recovery'), array($this, 'discount_enabled_callback'), 'hdcart-settings', 'hdcart_section_discount');
        add_settings_field('discount_type', __('Discount type', 'hdwebmobile-abandoned-cart-recovery'), array($this, 'discount_type_callback'), 'hdcart-settings', 'hdcart_section_discount');
        add_settings_field('discount_amount', __('Discount amount', 'hdwebmobile-abandoned-cart-recovery'), array($this, 'discount_amount_callback'), 'hdcart-settings', 'hdcart_section_discount');
        add_settings_field('coupon_expiry_days', __('Coupon expires after (days)', 'hdwebmobile-abandoned-cart-recovery'), array($this, 'coupon_expiry_callback'), 'hdcart-settings', 'hdcart_section_discount');
    }

    public function print_discount_section_info()
    {
        esc_html_e('Optionally include a one-time discount code in the recovery email to encourage the customer to complete their order.', 'hdwebmobile-abandoned-cart-recovery');
    }

    public static function get_options()
    {
        $defaults = array(
            'enabled'             => 1,
            'delay_hours'         => 1,
            'discount_enabled'    => 0,
            'discount_type'       => 'percent',
            'discount_amount'     => 10,
            'coupon_expiry_days'  => 7,
        );

        return wp_parse_args(get_option('hdcart_options', array()), $defaults);
    }

    public function sanitize($input)
    {
        $new_input = array();

        $new_input['enabled']     = isset($input['enabled']) ? 1 : 0;
        $new_input['delay_hours'] = isset($input['delay_hours']) ? max(1, absint($input['delay_hours'])) : 1;

        $new_input['discount_enabled'] = isset($input['discount_enabled']) ? 1 : 0;

        $allowed_types = array('percent', 'fixed_cart');
        $new_input['discount_type'] = isset($input['discount_type']) && in_array($input['discount_type'], $allowed_types, true)
            ? $input['discount_type']
            : 'percent';

        $new_input['discount_amount']    = isset($input['discount_amount']) ? wc_format_decimal($input['discount_amount']) : 0;
        $new_input['coupon_expiry_days'] = isset($input['coupon_expiry_days']) ? max(1, absint($input['coupon_expiry_days'])) : 7;

        return $new_input;
    }

    public function enabled_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="checkbox" name="hdcart_options[enabled]" value="1" %s />',
            checked(1, $options['enabled'], false)
        );
    }

    public function delay_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="number" min="1" name="hdcart_options[delay_hours]" value="%s" class="small-text" />',
            esc_attr($options['delay_hours'])
        );
    }

    public function discount_enabled_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="checkbox" name="hdcart_options[discount_enabled]" value="1" %s />',
            checked(1, $options['discount_enabled'], false)
        );
    }

    public function discount_type_callback()
    {
        $options = self::get_options();
        $types   = array(
            'percent'    => __('Percent off', 'hdwebmobile-abandoned-cart-recovery'),
            'fixed_cart' => __('Fixed amount off', 'hdwebmobile-abandoned-cart-recovery'),
        );
        echo '<select name="hdcart_options[discount_type]">';
        foreach ($types as $value => $label) {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr($value),
                selected($options['discount_type'], $value, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }

    public function discount_amount_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="number" step="0.01" min="0" name="hdcart_options[discount_amount]" value="%s" class="small-text" />',
            esc_attr($options['discount_amount'])
        );
    }

    public function coupon_expiry_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="number" min="1" name="hdcart_options[coupon_expiry_days]" value="%s" class="small-text" />',
            esc_attr($options['coupon_expiry_days'])
        );
    }
}
