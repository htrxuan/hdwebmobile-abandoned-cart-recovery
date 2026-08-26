<?php

/**
 * Plugin Name: HDWebmobile Abandoned Cart Recovery
 * Plugin URI: https://hdwebmobile.com/plugins/hdwebmobile-abandoned-cart-recovery/
 * Description: Recovers abandoned WooCommerce carts with a scheduled email, capturing the customer's email from both the Blocks and classic checkout.
 * Version: 1.0.2
 * Author: htrxuan - Han Tran
 * Author URI: https://hdwebmobile.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hdwebmobile-abandoned-cart-recovery
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.9
 */

namespace htrxuan\hdcart;

if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('HDCART_VERSION', '1.0.2');
define('HDCART_DB_VERSION', '1.0.0');
define('HDCART_PLUGIN_FILE', __FILE__);
define('HDCART_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDCART_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HDCART_PLUGIN_DIR . 'includes/class-hdcart-activator.php';

register_activation_hook(HDCART_PLUGIN_FILE, array(HDCART_Activator::class, 'activate'));
add_action('before_woocommerce_init', array(HDCART_Activator::class, 'declare_hpos_compatibility'));

add_action('plugins_loaded', function () {
    require_once HDCART_PLUGIN_DIR . 'includes/class-hdcart-core.php';
    HDCART_Core::get_instance();
});

add_filter('plugin_action_links_' . plugin_basename(HDCART_PLUGIN_FILE), function ($links) {
    $donate_link = '<a href="https://paypal.me/htrxuan/20" target="_blank" style="color:#d54e21;font-weight:bold;">' . __('Donate', 'hdwebmobile-abandoned-cart-recovery') . '</a>';
    array_unshift($links, $donate_link);
    return $links;
});
