<?php
/**
 * @file vendor_bargain.php
 * @brief 统计供应商议价比
 * @author Jinlin Li <jinlin.li@geneegroup.com>
 * @version 0.1.0
 * @date 2015-09-21
 * 导出列：商品名称、订单编号、订单状态、订单生成时间、供应商名称、买方名称、商品原价、商品原数量、商品成交价、商品成交数量
 */
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
$db = Database::factory();
$csv = new CSV('vendor_bargain.csv', 'w');
$csv->write([
	'商品名称','订单编号','订单状态','订单生成时间','供应商名称','买方名称','商品原价','商品成交价'
]);

$start = 0;
$limit = 100;
while (true) {
	$items = Q("order_item")->limit($start, $limit);
	if (!count($items)) break;
	foreach ($items as $item) {
		$order = $item->order;
		$product = $item->product;
		$unit_price = $item->unit_price;
		$version = $item->version;
		$revision = O('product_revision', ['product'=>$product, 'version'=>$version]);
		if ($revision->id && $revision->unit_price != $item->unit_price) {
			$row = [
				$product->name,
				$item->order->voucher,
				Order_Model::$status[$order->status],
				date('Y-m-d', $order->ctime),
				$order->vendor->name,
				$order->customer->name,
				$revision->unit_price,
				$item->unit_price,
			];
		}
	}
}