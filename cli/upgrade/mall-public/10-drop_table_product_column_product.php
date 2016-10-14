#!/usr/bin/env php
<?php
$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;

$db = Database::factory();
// //删除 product 表的 product 字段
$return = $db->query("ALTER TABLE product drop column `product_id`");
if ($return) {
	echo 'product表product字段删除 成功!';
}
else {
	echo 'product表product字段删除 失败!';
}