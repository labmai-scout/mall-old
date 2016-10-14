#!/usr/bin/env php
<?php
$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;

$db = Database::factory();
//删除 product 表
$db->drop_table('product');
$db->drop_table('_r_product_category_product');