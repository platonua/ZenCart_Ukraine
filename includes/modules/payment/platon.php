<?php

/*
 */

class platon extends base {

    var $code, $title, $description, $enabled;

    // class constructor
    function platon() {

        $this->code = 'platon';
        $this->title = MODULE_PAYMENT_PLATON_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_PLATON_TEXT_PUBLIC_TITLE;
        $this->description = MODULE_PAYMENT_PLATON_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_PLATON_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_PLATON_STATUS == 'True') ? true : false);

        if ((int) MODULE_PAYMENT_PLATON_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_PLATON_ORDER_STATUS_ID;
        }

        $this->form_action_url = MODULE_PAYMENT_PLATON_GW_URL;
    }

    function javascript_validation() {
        return false;
    }

    function selection() {
        return array('id' => $this->code,
            'module' => $this->public_title,
            'fields' => array(array('title' => '', 'field' => MODULE_PAYMENT_PLATON_TEXT_PUBLIC_DESCRIPTION))
        );
    }

    function pre_confirmation_check() {
        return false;
    }

    function confirmation() {
        $confirmation = array('title' => $this->title);
        return $confirmation;
    }

    function process_button() {
        global $currency, $order, $db;

        $url = zen_href_link(FILENAME_CHECKOUT_PROCESS);

        $insert = array(
            'start_time' => 'now()',
            'status' => PLATON_STATUS_REQUESTED,
            'amount' => $order->info['total']//,
                //'session_id' => zen_session_id()
        );
        zen_db_perform(TABLE_PLATON, $insert);
        $insert_id = $db->insert_ID();

        /* Prepare product data for coding */
        $data = base64_encode(
                json_encode(
                        array(
                            'amount' => number_format($order->info['total'], 2, '.', ''),
                            'name' => 'Order from ' . STORE_NAME,
                            'currency' => $currency
                        )
                )
        );

        /* Calculation of signature */
        $sign = md5(
                strtoupper(
                        strrev(MODULE_PAYMENT_PLATON_KEY) .
                        strrev($data) .
                        strrev($url) .
                        strrev(MODULE_PAYMENT_PLATON_PASSWORD)
                )
        );

        $process_button_string =
                zen_draw_hidden_field('key', MODULE_PAYMENT_PLATON_KEY) .
                zen_draw_hidden_field('order', $insert_id) .
                zen_draw_hidden_field('url', $url) .
                zen_draw_hidden_field('error_url', zen_href_link(FILENAME_CHECKOUT_PAYMENT)) .
                zen_draw_hidden_field('data', $data) .
                zen_draw_hidden_field('sign', $sign)
        ;


        return $process_button_string;
    }

    function before_process() {
        global $order;
        if ((int) MODULE_PAYMENT_PLATON_ORDER_STATUS_ID > 0) {
            $order->info['order_status'] = MODULE_PAYMENT_PLATON_ORDER_STATUS_ID;
        }
        return true;
    }

    function check() {
        global $db;
        if (!isset($this->_check)) {
            $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PLATON_STATUS'");
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    function after_process() {
        global $insert_id, $db;
        $platon_order = $db->Execute("select * from  " . TABLE_PLATON . " where id = '" . (int) $_GET['order'] . "'");
        if ($platon_order->RecordCount() && $platon_order->fields['status'] == PLATON_STATUS_SALE) {

            $db->Execute("update " . TABLE_PLATON . " set zen_order_id = '" . (int) $insert_id . "' where id = '" . (int) $_GET['order'] . "'");
            $db->Execute("update " . TABLE_ORDERS . " set orders_status = '2', last_modified = now() where orders_id = '" . (int) $insert_id . "'");

            $sql_data_array = array('orders_id' => $insert_id,
                'orders_status_id' => 2,
                'date_added' => 'now()',
                'customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
                'comments' => 'Paid by Platon');

            zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        }

        return false;
    }

    function install() {
        global $db;
        define('TABLE_PLATON', DB_PREFIX . 'platon');
        $db->Execute("CREATE TABLE " . TABLE_PLATON . " (
            `id` int(11) NOT NULL auto_increment,
            `start_time` datetime NOT NULL default '0000-00-00 00:00:00',
            `finish_time` datetime NOT NULL default '0000-00-00 00:00:00',
            `status` varchar(50) collate latin1_general_ci NOT NULL default '',
            `amount` float NOT NULL default '0',
            `platon_order_id` varchar(50) NOT NULL default '',
            `zen_order_id` int(11) NOT NULL default '0',
            PRIMARY KEY  (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values (
            'Enable Platon Gateway', 
            'MODULE_PAYMENT_PLATON_STATUS', 
            'False', 
            'Do you want to accept payments by Platon Gateway?', 
            '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())"
        );
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values (
            'Key', 
            'MODULE_PAYMENT_PLATON_KEY', 
            '', 
            'Key for Client identification.', 
            '6', '0', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values (
            'Password', 
            'MODULE_PAYMENT_PLATON_PASSWORD', 
            '', 
            'The Client\'s password', 
            '6', '0', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values (
            'Gateway URL', 
            'MODULE_PAYMENT_PLATON_GW_URL', 
            'https://secure.platononline.com/payment/auth', 
            'You can change it if required.', 
            '6', '0', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values (
            'Sort Order', 
            'MODULE_PAYMENT_PLATON_SORT_ORDER', 
            '0', 
            'Sort order of display. (Lowest is displayed first)', 
            '6', '0', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values (
            'Set Order Status', 
            'MODULE_PAYMENT_PLATON_ORDER_STATUS_ID', 
            '0', 
            'Set the status of orders made with this payment module to this value.', 
            '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    }

    function remove() {
        global $db;
        define('TABLE_PLATON', DB_PREFIX . 'platon');
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        $db->Execute("DROP TABLE IF EXISTS " . TABLE_PLATON);
    }

    function keys() {
        return array(
            'MODULE_PAYMENT_PLATON_STATUS',
            'MODULE_PAYMENT_PLATON_KEY',
            'MODULE_PAYMENT_PLATON_PASSWORD',
            'MODULE_PAYMENT_PLATON_GW_URL',
            'MODULE_PAYMENT_PLATON_SORT_ORDER',
            'MODULE_PAYMENT_PLATON_ORDER_STATUS_ID'
        );
    }

}

?>
