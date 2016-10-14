<?php
require '../../base.php';

$db = Database::factory();
$sale_info = [
	'info' => '此商品有折扣且参加满赠',
	'types' => ['折','赠'],
];
$sale_info = json_encode($sale_info, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

$sql = "UPDATE `product` SET `sale_info` = '%s', `orig_price`=`unit_price`, `unit_price` = ROUND(`unit_price`*0.98,2) WHERE `vendor_id`=%d and `unit_price` > 0";

if($db->query($sql, $sale_info, 166)) {
	echo "done\n";
}
else {
	echo "升级失败\n";
}

echo exec("../../sphinx_update_by_vendor.php 166");
