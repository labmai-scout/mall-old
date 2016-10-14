<?php
/**
 * 订单号
 * 订单ID
 * 下单时间
 * 所属课题组
 * 课题组负责人
 * 所属供应商
 * 订单金额
 * 订单状态
 */
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
$start = 0;
$limit = 20;
$file  = 'order_stat.csv';
$csv   = new CSV($file, 'w');
$row = [
	'订单号',
	'订单ID',
	'下单时间',
	'所属课题组',
	'课题组负责人',
	'所属供应商',
	'订单金额',
	'订单状态',
];
$csv->write($row);
while (true) {
	$orders = Q("order:sort(id D)")->limit($start, $limit);
	$start += $limit;
	if (!count($orders)) break;
	foreach ($orders as $order) {
		$row = [];
		$row[] = $order->voucher;
		$row[] = $order->id;
		$row[] = Date::format($order->ctime, 'Y/m/d H:i:s');
		$row[] = $order->customer->name;
		$row[] = $order->customer->owner->name;
		$row[] = $order->vendor->name;
		$row[] = $order->price;
		$row[] = Order_Model::$status[$order->status];
		$csv->write($row);
	}
}