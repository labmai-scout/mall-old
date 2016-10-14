#!/usr/bin/env php
<?php
/*
 *
 * useage SITE_ID=nankai php 10-add_received_order_item_deliver_date.php
 */

require dirname(dirname(dirname(__FILE__))). '/base.php';

$db = Database::factory();
try {

    $status_delivered = Order_Item_Model::DELIVER_STATUS_DELIVERED;

    $start = 0;
    $per_page = 30;
    for (;;) { 
        $order_items = Q("order_item[deliver_status={$status_delivered}][deliver_date=0]")->limit($start, $per_page);
        if (count($order_items) == 0) break;
        foreach ($order_items as $order_item) {
            $order_item->deliver_date = Date::time();
            $order_item->save();
        }
        $start += $per_page;
    }
}
catch(Exception $e) {
    return FALSE;
}