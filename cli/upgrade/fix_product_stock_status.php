#!/usr/bin/env php
<?php

$base = dirname(dirname(__FILE__)). '/base.php';
include $base;

// const STOCK_STATUS_IN_STOCK = 1;
// const STOCK_STATUS_BOOKABLE = 3;
// const STOCK_STATUS_NO_STOCK = 0;
// const STOCK_STATUS_STOP_SUPPLY = 2;

// const STOCK_STATUS_IN_STOCK = 0;
// const STOCK_STATUS_BOOKABLE = 1;
// const STOCK_STATUS_NO_STOCK = 2;
// const STOCK_STATUS_STOP_SUPPLY = 3;

$format1 = array(
	0 => 10,
	1 => 11,
	2 => 12,
	3 => 13
);

$format2 = array(
	10 => 2,
	11 => 0,
	12 => 3,
	13 => 1,
);


$db = Database::factory();
foreach ($format1 as $key => $value) {
	$db->query("UPDATE product set stock_status=$value WHERE stock_status=$key");
}

foreach ($format2 as $key => $value) {
	$db->query("UPDATE product set stock_status=$value WHERE stock_status=$key");	
}

//Mysql 数据更新完毕，更新sphinx索引
// $sphinx = Database::factory('@sphinx');
// $product_table = Search_Iterator::get_index_name('product');
//从配置中拿到各个类别的名称
// $types_sphinx_indexes = Config::get('product.types_sphinx_indexes');
// $product_types = array_keys($types_sphinx_indexes);

// foreach ($format1 as $key => $value) {
// 	$sphinx->query("UPDATE `$product_table` SET `stock_status`=$value WHERE `stock_status`=$key ");
// 	//更新各个类别表的索引
// 	foreach ($product_types as $sp) {
// 		$pt = Search_Iterator::get_index_name('product_'.$sp);
// 		$sphinx->query("UPDATE `$pt` SET `stock_status`=$value WHERE `stock_status`=$key ");
// 	}
// }

// foreach ($format2 as $key => $value) {
// 	$sphinx->query("UPDATE `$product_table` SET `stock_status`=$value WHERE `stock_status`=$key ");
// 	//更新各个类别表的索引
// 	foreach ($product_types as $sp) {
// 		$pt = Search_Iterator::get_index_name('product_'.$sp);
// 		$sphinx->query("UPDATE `$pt` SET `stock_status`=$value WHERE `stock_status`=$key ");
// 	}
// }

?>