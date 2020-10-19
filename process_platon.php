<?php

$log = __DIR__ . '/logs/platon_callback.log';

if (!$_POST)
    die("ERROR: Empty POST");

// log callback data
file_put_contents($log, var_export($_POST, 1) . "\n\n", FILE_APPEND);


require ('includes/application_top.php');
include(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . FILENAME_CHECKOUT_PROCESS . '.php');

$callbackParams = $_POST;

// generate signature from callback params
$sign = md5(strtoupper(
                strrev($callbackParams['email']) .
                MODULE_PAYMENT_PLATON_PASSWORD .
                $callbackParams['order'] .
                strrev(substr($callbackParams['card'], 0, 6) . substr($callbackParams['card'], -4))
        ));

// verify signature
if ($callbackParams['sign'] !== $sign) {
    // log failure
    file_put_contents($log, date('Y-m-d H:i:s ') . "  Invalid signature" . "\n\n", FILE_APPEND);
    // answer with fail response
    die("ERROR: Invalid signature");
} else {
    // log success
    file_put_contents($log, date('Y-m-d H:i:s ') . "  Callback signature OK" . "\n\n", FILE_APPEND);

    // do processing stuff
    switch ($callbackParams['status']) {
        case 'SALE':
            $order_id = $callbackParams['order'];
            require(DIR_WS_CLASSES . 'order.php');
            $order = $db->Execute("select * from " . TABLE_PLATON . " where id = '" . $order_id . "'");

            if (!$order->RecordCount()) {
                file_put_contents($log, date('Y-m-d H:i:s ') . "  ERROR: wrong order_id: $order_id" . "\n\n", FILE_APPEND);
                die('ERROR: wrong order_id');
            }

            file_put_contents($log, date('Y-m-d H:i:s ') . "  Order {$callbackParams['order']} processed as successfull sale" . "\n\n", FILE_APPEND);
            break;
        case 'REFUND':
            $order_id = $callbackParams['order'];
            $platon_order = $db->Execute("select * from  " . TABLE_PLATON . " where id = '" . $order_id . "'");

            if ($platon_order->RecordCount() && $platon_order->fields['zen_order_id'] != 0) {

                $db->Execute("update " . TABLE_ORDERS . " set orders_status = '1', last_modified = now() where orders_id = '" . $platon_order->fields['zen_order_id'] . "'");

                $sql_data_array = array('orders_id' => $platon_order->fields['zen_order_id'],
                    'orders_status_id' => 1,
                    'date_added' => 'now()',
                    'customer_notified' => '0',
                    'comments' => 'Refunded to customer by Platon');
                zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
            }
            file_put_contents($log, date('Y-m-d H:i:s ') . "  Order {$callbackParams['order']} processed as successfull refund" . "\n\n", FILE_APPEND);
            break;
        case 'CHARGEBACK':
            file_put_contents($log, date('Y-m-d H:i:s ') . "  Order {$callbackParams['order']} processed as successfull chargeback" . "\n\n", FILE_APPEND);
            break;
        default:
            file_put_contents($log, date('Y-m-d H:i:s ') . "  Invalid callback data" . "\n\n", FILE_APPEND);
            die("ERROR: Invalid callback data");
    }

    $update = array(
        'finish_time' => 'now()',
        'status' => $callbackParams['status'],
        'platon_order_id' => $callbackParams['id']
    );

    zen_db_perform(TABLE_PLATON, $update, 'update', "id = '" . $callbackParams['order'] . "'");

    // answer with success response
    exit("OK");
}