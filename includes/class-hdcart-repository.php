<?php

namespace htrxuan\hdcart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Only file in the plugin that touches $wpdb directly. Every query is parameterized,
 * including the table name via the %i identifier placeholder (WP 6.2+).
 *
 * Direct queries against a custom table are unavoidable here -- there is no WP API
 * for this data -- so DirectDatabaseQuery/NoCaching advisories are expected and accepted
 * for this class, matching standard practice for custom-table plugins.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
class HDCART_Repository
{

    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'hdcart_carts';
    }

    public static function get_schema_sql()
    {
        global $wpdb;
        $table           = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_key VARCHAR(64) NOT NULL,
            email VARCHAR(200) NOT NULL,
            name VARCHAR(200) DEFAULT NULL,
            cart_contents LONGTEXT NOT NULL,
            cart_total VARCHAR(20) NOT NULL,
            currency VARCHAR(10) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            recovery_coupon_code VARCHAR(64) DEFAULT NULL,
            order_id BIGINT UNSIGNED DEFAULT NULL,
            captured_at DATETIME NOT NULL,
            email_sent_at DATETIME DEFAULT NULL,
            recovered_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY session_key (session_key),
            KEY status_email (status, email)
        ) {$charset_collate};";
    }

    /**
     * Update-if-exists, else insert -- same resubscribe-style upsert used by the
     * back-in-stock-waitlist plugin's add_subscriber(). Re-entering an email in the
     * same session refreshes the snapshot/timestamp rather than creating a duplicate row.
     *
     * @return int The row id.
     */
    public static function upsert_cart($session_key, $email, $name, array $cart_contents, $cart_total, $currency)
    {
        global $wpdb;

        $existing = $wpdb->get_row($wpdb->prepare(
            'SELECT id, status FROM %i WHERE session_key = %s',
            self::get_table_name(),
            $session_key
        ));

        $now              = current_time('mysql');
        $encoded_contents = wp_json_encode($cart_contents);

        if ($existing) {
            // A cart that already completed as an order shouldn't be silently reopened
            // just because the same session adds something new to the cart afterward.
            if ('recovered' === $existing->status) {
                return (int) $existing->id;
            }

            $wpdb->update(
                self::get_table_name(),
                array(
                    'email'         => $email,
                    'name'          => $name,
                    'cart_contents' => $encoded_contents,
                    'cart_total'    => $cart_total,
                    'currency'      => $currency,
                    'status'        => 'pending',
                    'captured_at'   => $now,
                ),
                array('id' => $existing->id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );

            return (int) $existing->id;
        }

        $wpdb->insert(
            self::get_table_name(),
            array(
                'session_key'   => $session_key,
                'email'         => $email,
                'name'          => $name,
                'cart_contents' => $encoded_contents,
                'cart_total'    => $cart_total,
                'currency'      => $currency,
                'status'        => 'pending',
                'captured_at'   => $now,
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        return (int) $wpdb->insert_id;
    }

    public static function get_by_session_key($session_key)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM %i WHERE session_key = %s',
            self::get_table_name(),
            $session_key
        ));
    }

    public static function get_pending_by_email($email)
    {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM %i WHERE email = %s AND status = 'pending'",
            self::get_table_name(),
            $email
        ));
    }

    public static function mark_email_sent($id, $coupon_code = null)
    {
        global $wpdb;

        $wpdb->update(
            self::get_table_name(),
            array(
                'email_sent_at'        => current_time('mysql'),
                'recovery_coupon_code' => $coupon_code,
            ),
            array('id' => $id),
            array('%s', '%s'),
            array('%d')
        );
    }

    public static function mark_recovered($id, $order_id)
    {
        global $wpdb;

        $wpdb->update(
            self::get_table_name(),
            array(
                'status'       => 'recovered',
                'order_id'     => $order_id,
                'recovered_at' => current_time('mysql'),
            ),
            array('id' => $id),
            array('%s', '%d', '%s'),
            array('%d')
        );
    }

    public static function count_by_status($status)
    {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM %i WHERE status = %s',
            self::get_table_name(),
            $status
        ));
    }

    /**
     * @param array $args { status, s (email search), per_page, paged, orderby, order }
     * @return array { items: array, total: int }
     */
    public static function get_for_list_table(array $args)
    {
        global $wpdb;

        $where  = array('1=1');
        $params = array();

        if (!empty($args['status']) && 'all' !== $args['status']) {
            $where[]  = 'status = %s';
            $params[] = $args['status'];
        }

        if (!empty($args['s'])) {
            $where[]  = 'email LIKE %s';
            $params[] = '%' . $wpdb->esc_like($args['s']) . '%';
        }

        $where_sql = implode(' AND ', $where);

        $allowed_orderby = array('captured_at', 'recovered_at', 'status', 'email', 'cart_total');
        $orderby         = in_array($args['orderby'] ?? '', $allowed_orderby, true) ? $args['orderby'] : 'captured_at';
        $order           = 'ASC' === strtoupper($args['order'] ?? '') ? 'ASC' : 'DESC';

        $per_page = max(1, (int) ($args['per_page'] ?? 20));
        $paged    = max(1, (int) ($args['paged'] ?? 1));
        $offset   = ($paged - 1) * $per_page;

        // $where_sql/$orderby/$order are built only from the hardcoded, safe fragments and
        // allow-lists above (never raw user input); all real values still go through prepare().
        $total = (int) $wpdb->get_var($wpdb->prepare( // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
            "SELECT COUNT(*) FROM %i WHERE {$where_sql}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            array_merge(array(self::get_table_name()), $params)
        ));

        $items = $wpdb->get_results($wpdb->prepare( // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
            "SELECT * FROM %i WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            array_merge(array(self::get_table_name()), $params, array($per_page, $offset))
        ));

        return array(
            'items' => $items,
            'total' => $total,
        );
    }
}
