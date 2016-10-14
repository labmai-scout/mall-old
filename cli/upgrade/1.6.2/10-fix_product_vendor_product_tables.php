#!/usr/bin/env php
<?php
$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;

$db = Database::factory();
$fix_product = $db->query("alter table product modify column name varchar(255) NOT NULL DEFAULT ''");

if ($fix_product) {
	echo 'product 字段 更新成功';
}

$fix_vendor_product = $db->query("alter table vendor_product modify column name varchar(255) NOT NULL DEFAULT ''");

if ($fix_vendor_product) {
	echo 'vendor_product 字段 更新成功';
}
