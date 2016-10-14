<?php
require('../../base.php');

/*
 * author Yu Li <yu.li@geneegroup.com>
 * 处理毕得数据
 */


$sale_info = [
    'info' => '此商品参加满额赠马克杯活动',
    'types' => ['赠'],
];
$sale_info = json_encode($sale_info, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);


$db = Database::factory();
$db->query("UPDATE product SET sale_info='%s' where vendor_id=170", $sale_info);
