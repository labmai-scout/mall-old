<?php

require 'base.php';

$now = Date::time();
$auto_receive_duration = Config::get('mall.auto_receive_duration');

$status_delivered = Order_Item_Model::DELIVER_STATUS_DELIVERED;
$status_received = Order_Item_Model::DELIVER_STATUS_RECEIVED;

$start = 0;
$per_page = 30;

for (;;) {
	$order_items = Q("order_item[deliver_status={$status_delivered}]")->limit($start, $per_page);
	if (count($order_items) == 0) break;
	foreach ($order_items as $order_item) {
		$duration = $now - $order_item->deliver_date;
		if ($duration > $auto_receive_duration) {
			$order_item->deliver_status = $status_received;
			$order_item->receive_date =$now;
			if ($order_item->save()) {
				echo '.';
			}
		}
	}
	$start += $per_page; 
}
