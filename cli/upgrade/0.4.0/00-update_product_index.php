#!/usr/bin/env php
<?php
/*
 * 修正数据结构
 */

$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;

if (SITE_ID != 'nankai') return FALSE;

$db = Database::factory();
$db->query('drop index brand on product');
$db->query('drop table brand');
