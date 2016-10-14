#!/usr/bin/env php
<?php
  /*
	由于 vendor 修改 scopes 后会造成相关商品连动 publish()/unpublish() , 速度较慢
	所以有此脚本辅助后台运行 (xiaopei.li@2012-08-28)
   */

require('base.php');


// read opts
$shortopts = "v:";
$longopts = array(
	'vendor:',
	);

$opts = getopt($shortopts, $longopts);

if (!$opts) {
	echo "usage: \n";
	echo "1. vendor_modify_scopes.php -v 1\n";
	exit(1);
}

// error_log(print_r($opts, TRUE));

$vid = $opts['v'];
if (!$vid) {
	$vid = $opts['vendor'];
}

// error_log($vid);

$vendor = O('vendor', $vid);

// error_log($vendor->name);

if (!$vendor->id) {
	echo("vendor 不存在\n");
	exit(1);
}

$vendor->is_modifying_scopes = TRUE;
$vendor->save();

if ($vendor->extended_scopes) {

	foreach ($vendor->extended_scopes as $scope_name) {
		// if 新加的资质未删除
		// then unfreeze 资质关联的商品
		// (若新加但过期, 则已在第 2 步操作)

		$scope = O('vendor_scope', array(
					   'name' => $scope_name,
					   'vendor' => $vendor,
					   ));
		if ($scope->id &&
			$scope->expire_date > Date::time()) {
			$scope->extend();
		}
	}

	$vendor->extended_scopes = NULL;
	$vendor->save();
}

$vendor->is_modifying_scopes = FALSE;
$vendor->save();
