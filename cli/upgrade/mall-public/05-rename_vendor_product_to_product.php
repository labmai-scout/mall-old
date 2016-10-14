#!/usr/bin/env php
<?php
$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;

$db = Database::factory();
//修改 vendor_product 表为 product
$return = $db->query("RENAME TABLE `vendor_product` TO `product`");
if ($return) {
	echo 'vendor_product表rename为 product 成功!';
}
else {
	echo 'vendor_product表rename为 product 失败!';
}

$db->query("RENAME TABLE `_r_vendor_product_product_category` TO `_r_product_product_category`");