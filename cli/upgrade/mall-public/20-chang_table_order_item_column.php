#!/usr/bin/env php
<?php
$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;

$db = Database::factory();
$return = $db->query("ALTER TABLE order_item change vendor_product_id product_id bigint(20) not null default 0");
if ($return) {
	echo '更改 order_item 的 vendor_product 字段为 product 成功';
}
else {
	echo '更改 order_item 的 vendor_product 字段为 product 失败';
}

$return = $db->query("ALTER TABLE cart_item change vendor_product_id product_id bigint(20) not null default 0");
if ($return) {
	echo '更改 cart_item 的 vendor_product 字段为 product 成功';
}
else {
	echo '更改 cart_item 的 vendor_product 字段为 product 失败';
}

