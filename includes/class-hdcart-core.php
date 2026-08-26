<?php

namespace htrxuan\hdcart;

if (!defined('ABSPATH')) {
    exit;
}

final class HDCART_Core
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
        $this->includes();
        $this->init_hooks();
    }

    private function __clone()
    {
    }

    private function includes()
    {
        require_once HDCART_PLUGIN_DIR . 'includes/class-hdcart-repository.php';
        require_once HDCART_PLUGIN_DIR . 'includes/class-hdcart-coupon-manager.php';
        require_once HDCART_PLUGIN_DIR . 'includes/class-hdcart-capture.php';
        require_once HDCART_PLUGIN_DIR . 'includes/class-hdcart-order-watcher.php';
        require_once HDCART_PLUGIN_DIR . 'includes/class-hdcart-admin.php';
    }

    private function init_hooks()
    {
        add_action('admin_notices', array($this, 'render_missing_woocommerce_notice'));
        add_action('admin_init', array(HDCART_Activator::class, 'maybe_upgrade_db'));

        if (!class_exists('WooCommerce')) {
            return;
        }

        add_filter('woocommerce_email_classes', array($this, 'register_email'));
        add_action('hdcart_send_recovery', array($this, 'send_recovery_email'));

        HDCART_Capture::get_instance();
        HDCART_Order_Watcher::get_instance();

        // HDCART_Admin registers admin-menu/settings hooks itself, but the frontend capture
        // classes above must always load: HDCART_Admin::get_options() is read from the
        // frontend too, the same reason class-hdspin-core.php requires its Admin class
        // unconditionally rather than only when is_admin().
        HDCART_Admin::get_instance();
    }

    public function register_email($emails)
    {
        // WC_Emails::init() includes its own class-wc-email.php base class immediately
        // before applying this filter, so it's always safe to load our subclass here --
        // loading it any earlier (e.g. at plugins_loaded) would fatal on WC_Email not existing yet.
        require_once HDCART_PLUGIN_DIR . 'includes/class-hdcart-recovery-email.php';
        $emails['hdcart_recovery'] = new HDCART_Recovery_Email();
        return $emails;
    }

    public function send_recovery_email($session_key)
    {
        $cart_row = HDCART_Repository::get_by_session_key($session_key);

        if (!$cart_row || 'pending' !== $cart_row->status) {
            return;
        }

        $options     = HDCART_Admin::get_options();
        $coupon_code = null;

        if (!empty($options['discount_enabled'])) {
            $coupon = HDCART_Coupon_Manager::create_recovery_coupon(
                $cart_row->email,
                $options['discount_type'],
                $options['discount_amount'],
                $options['coupon_expiry_days']
            );

            if ($coupon) {
                $coupon_code = $coupon->get_code();
            }
        }

        $emails = WC()->mailer()->get_emails();
        $email  = isset($emails['hdcart_recovery']) ? $emails['hdcart_recovery'] : new HDCART_Recovery_Email();

        $logger = wc_get_logger();
        $sent   = $email->trigger($cart_row, $coupon_code);

        if ($sent) {
            $logger->info(
                sprintf('Recovery email sent to %s (cart #%d).', $cart_row->email, $cart_row->id),
                array('source' => 'hdcart')
            );
        } else {
            $logger->error(
                sprintf('Recovery email FAILED to send to %s (cart #%d).', $cart_row->email, $cart_row->id),
                array('source' => 'hdcart')
            );
        }

        HDCART_Repository::mark_email_sent($cart_row->id, $coupon_code);
    }

    public function render_missing_woocommerce_notice()
    {
        $screen = get_current_screen();
        if (!$screen || 'plugins' !== $screen->id) {
            return;
        }

        if (!get_transient('hdcart_wc_missing_notice')) {
            return;
        }
        delete_transient('hdcart_wc_missing_notice');
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('HDWebmobile Abandoned Cart Recovery requires WooCommerce to be installed and active. The plugin has been deactivated.', 'hdwebmobile-abandoned-cart-recovery'); ?>
            </p>
        </div>
        <?php
    }
}
