#!/usr/bin/env php
<?php

/*
* usage:		SITE_ID=nankai php create_transfer_statement.php
* author: 		cheng.liu@geneegroup.com
* date: 		2013.08.31
* description: 	设置定时脚本, 定期将所有未付款但是已经送货的订单加入付款夹并生成付款单
*/

require 'base.php';

$approved = Order_Model::STATUS_APPROVED;

$delivered = Order_Model::DELIVER_STATUS_DELIVERED;

$received = Order_Model::DELIVER_STATUS_RECEIVED;


//查找出来已经被送货但是未被付款的订单，那么我们要找的亮点就应该是：1，已经送货或者收货；2，订单已经被确认，且状态不为付款中. 结果一并全部放入付款夹

$orders = Q("order[status={$approved}][deliver_status={$delivered}|deliver_status={$received}]:not(transfer_bucket order):not(transfer_statement order)");

foreach ($orders as $order) {
	$bucket = Transfer_Bucket_Model::customer_bucket($order->customer);
	$bucket->add_item($order);
}


$site_title = Config::get('page.title_default');

//循环查找存在订单的付款夹，将付款夹中的订单全部生成付款单
$buckets = Q("order transfer_bucket");
foreach ($buckets as $bucket) {
	$statement = O('transfer_statement');
	$statement->customer = $bucket->customer;
	$statement->save();

	$orders = Q("$bucket order");
	$balance = 0;
	$content = array();
	foreach($orders as $order) {
		$bucket->disconnect($order);
		$statement->connect($order);
		$balance += $order->price;
		foreach (Q("order_item[order={$order}]") as $i) {
			$content[] = $i->vendor_product->name;
		}
	}	
	if (count($content)) {
		$statement->description = HT("%site: %name 购买了 %pname.", array(
            '%site' => $site_title,
			'%name' => $customer->owner->name . ' [' . $customer->name .']',
			'%pname' => join(', ', $content)
		));
	}
	$statement->balance = $balance;
	$statement->save();

	echo sprintf("生成了订单[%d]\n", $statement->id);
}



