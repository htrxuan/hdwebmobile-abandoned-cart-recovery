<?php

namespace htrxuan\hdcart;

if (!defined('ABSPATH')) {
    exit;
}

class HDCART_Activator
{

    public static function activate()
    {
        if (!self::is_woocommerce_active()) {
            deactivate_plugins(plugin_basename(HDCART_PLUGIN_FILE));
            set_transient('hdcart_wc_missing_notice', true, 30);
            return;
        }

        self::maybe_upgrade_db();
    }

    public static function is_woocommerce_active()
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active('woocommerce/woocommerce.php') || class_exists('WooCommerce');
    }

    public static function maybe_upgrade_db()
    {
        if (get_option('hdcart_db_version') === HDCART_DB_VERSION) {
            return;
        }

        require_once HDCART_PLUGIN_DIR . 'includes/class-hdcart-repository.php';
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta(HDCART_Repository::get_schema_sql());

        update_option('hdcart_db_version', HDCART_DB_VERSION);
    }

    public static function declare_hpos_compatibility()
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', HDCART_PLUGIN_FILE, true);
        }
    }
}
