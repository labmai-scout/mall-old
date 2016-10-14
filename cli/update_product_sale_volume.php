<?php

require 'base.php';
$day_start = mktime(0,0,0);
$day_start = $day_start - 86400;
$day_end = Date::time();
$orders = Q("order[mtime>$day_start][mtime<$day_end]");
$arr = [];
foreach ($orders as $order) {
	$order_items = Q("order_item[order={$order}]");
	foreach ($order_items as $item) {
		$product = $item->product;
		if (!in_array($product->id, $arr)) {
			Search_Product::update_index($product);
		}
		$arr[$product->id] = $product->id;
	}
}
?>