#!/usr/bin/env php
<?php
// regenerate sphinx index
// 此脚本可用作商品下架后，删除对应的product 索引
include "base.php";

	// 遍历
	$products = Q("vendor_product[approve_date=0] product");
	$total = $products->total_count();
	echo '下架商品共计: '.$total;
	echo "\n";
	$start = 0;
	$per_page = 20;
	$start_time = Date::time();
	echo '开始时间:'.$start_time;
	while ($start < $total) {
		foreach ($products->limit($start, $per_page) as $product) {
			Search_Product::delete_index($product);
			$start += $per_page;
		}
		echo '.';
	}

	echo "\n";


