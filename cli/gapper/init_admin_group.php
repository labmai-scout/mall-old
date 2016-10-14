<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
/*
arrs [ uid => gid]
*/
$arrs = [];

foreach ($arrs as $uid => $gid) {
	$user_gapper               = O('user_gapper');
	$user_gapper->user         = O('user', (int)$uid);
	$user_gapper->gapper_group = $gid;
	$user_gapper->save();
}