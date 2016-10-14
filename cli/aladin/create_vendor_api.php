<?php
/**
 * 创建 阿拉丁 对应的vendor_api
 * SITE_ID=nankai php create_vendor_api.php
 */
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
$vendor = O('vendor', 45);
if (!$vendor->id) exit;
$vendor_api = O('vendor_api', ['vendor'=>$vendor]);
$vendor_api->vendor = $vendor;
$vendor_api->client_id = sha1(uniqid().mt_rand());
$vendor_api->client_secret = sha1(uniqid().mt_rand());
if ($vendor_api->save()) {
	echo "vendor api create success!.\n";
	echo "client_id: ".$vendor_api->client_id."\n";
	echo "client_secret: ".$vendor_api->client_secret."\n";
}