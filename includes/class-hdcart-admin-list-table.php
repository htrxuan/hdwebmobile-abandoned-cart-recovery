<?php

namespace htrxuan\hdcart;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class HDCART_Admin_List_Table extends \WP_List_Table
{

    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'cart',
            'plural'   => 'carts',
            'ajax'     => false,
        ));
    }

    public function get_columns()
    {
        return array(
            'email'        => __('Email', 'hdwebmobile-abandoned-cart-recovery'),
            'cart'         => __('Cart', 'hdwebmobile-abandoned-cart-recovery'),
            'status'       => __('Status', 'hdwebmobile-abandoned-cart-recovery'),
            'captured_at'  => __('Captured', 'hdwebmobile-abandoned-cart-recovery'),
            'recovered_at' => __('Recovered', 'hdwebmobile-abandoned-cart-recovery'),
        );
    }

    protected function get_sortable_columns()
    {
        return array(
            'email'        => array('email', false),
            'status'       => array('status', false),
            'captured_at'  => array('captured_at', true),
            'recovered_at' => array('recovered_at', false),
            'cart'         => array('cart_total', false),
        );
    }

    protected function extra_tablenav($which)
    {
        if ('top' !== $which) {
            return;
        }

        // Read-only filter param, same as core WP_List_Table screens -- no state
        // change occurs from reading it, so nonce verification doesn't apply here.
        $current_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $statuses        = array(
            'all'       => __('All statuses', 'hdwebmobile-abandoned-cart-recovery'),
            'pending'   => __('Pending', 'hdwebmobile-abandoned-cart-recovery'),
            'recovered' => __('Recovered', 'hdwebmobile-abandoned-cart-recovery'),
        );
        ?>
        <div class="alignleft actions">
            <select name="status">
                <?php foreach ($statuses as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($current_status, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <?php submit_button(__('Filter', 'hdwebmobile-abandoned-cart-recovery'), '', 'filter_action', false); ?>
        </div>
        <?php
    }

    public function column_email($item)
    {
        return esc_html($item->email) . ($item->name ? '<br /><span class="description">' . esc_html($item->name) . '</span>' : '');
    }

    public function column_cart($item)
    {
        $items = json_decode($item->cart_contents, true);
        $count = is_array($items) ? count($items) : 0;

        return sprintf(
            /* translators: 1: item count, 2: cart total */
            esc_html(_n('%1$d item, %2$s', '%1$d items, %2$s', $count, 'hdwebmobile-abandoned-cart-recovery')),
            $count,
            wp_strip_all_tags(wc_price($item->cart_total, array('currency' => $item->currency)))
        );
    }

    public function column_status($item)
    {
        $labels = array(
            'pending'   => __('Pending', 'hdwebmobile-abandoned-cart-recovery'),
            'recovered' => __('Recovered', 'hdwebmobile-abandoned-cart-recovery'),
        );

        $label = isset($labels[$item->status]) ? $labels[$item->status] : $item->status;

        return sprintf('<span class="hdcart-status-%s">%s</span>', esc_attr($item->status), esc_html($label));
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'captured_at':
                return esc_html($item->captured_at);
            case 'recovered_at':
                return $item->recovered_at ? esc_html($item->recovered_at) : '&mdash;';
            default:
                return '';
        }
    }

    public function prepare_items()
    {
        $per_page = 20;
        $paged    = $this->get_pagenum();

        // Read-only filter/search/sort params for this list table -- same pattern as core
        // WP_List_Table screens; nothing here changes state, so no nonce is needed.
        $status  = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $search  = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $orderby = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'captured_at'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order   = isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'desc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $result = HDCART_Repository::get_for_list_table(array(
            'status'   => $status,
            's'        => $search,
            'per_page' => $per_page,
            'paged'    => $paged,
            'orderby'  => $orderby,
            'order'    => $order,
        ));

        $this->items = $result['items'];

        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

        $this->set_pagination_args(array(
            'total_items' => $result['total'],
            'per_page'    => $per_page,
            'total_pages' => ceil($result['total'] / $per_page),
        ));
    }
}
