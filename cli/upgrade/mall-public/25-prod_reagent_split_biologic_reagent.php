#!/usr/bin/env php
<?php
$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;

$db = Database::factory();
$str = '生物试剂';
$db->query('UPDATE product SET type="%s",spec="%s" WHERE spec="%s"','biologic_reagent', ' ', $str);