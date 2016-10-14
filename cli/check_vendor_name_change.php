#!/usr/bin/env php
<?php
/*
*--delete_product
*sphinx相关 暂时不处理
*/
require('base.php');

$start = 0;
$per_page = 20;

while (TRUE) {
	$vendors = Q("vendor")->limit($start, $per_page);
	if (count($vendors) == 0) break;
	foreach ($vendors as $vendor) {
		if ($vendor->name_edit == 1) {
			$v_start = 0;
			$v_page = 100;
			while (TRUE) {
				$vendor_products = Q("vendor_product[vendor={$vendor}]")->limit($v_start, $v_page);
				if (count($vendor_products) == 0) break 2;
				foreach ($vendor_products as $vendor_product) {
					$product = $vendor_product->product;
					Search_Product::update_index($product);
				}
				$vendor->name_edit = 0;
				$vendor->save();
				$v_start += $v_page;
			}
		}
	}

	$start += $per_page;
}