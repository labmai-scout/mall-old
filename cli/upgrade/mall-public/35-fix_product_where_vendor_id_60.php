#!/usr/bin/env php
<?php
//海铭威的数据中包括规格为生物制剂的数据571921条数据，需要处理为生物试剂
$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;

$db = Database::factory();
$str = '生物制剂';
$now = Date::time();
$db->query('UPDATE product SET type="%s",spec="%s",mtime="%d" WHERE spec="%s"','biologic_reagent', ' ', $now, $str);
$sphinx = Database::factory('@sphinx');
$index_name = 'mall_nankai_product_reagent';
$sphinx->query('DELETE FROM `'.$index_name.'` WHERE vendor_id=60');