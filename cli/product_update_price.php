#!/usr/bin/env php
<?php
// 所有 product 更新 lowest/highest price
//暂时 注释掉 没有地方在用
/*
include "base.php";

$products = Q('product');
$total = $products->total_count();
$start = 0;
$per_page = 20;				// 分页加载, 以免太占用内存

echo "Updating products price:\n";

while ($start < $total) {
	foreach ($products->limit($start, $per_page) as $product) {
		$product->update_price()->save();
	}
	echo '.';
	$start += $per_page;
}

echo "\n";
*/