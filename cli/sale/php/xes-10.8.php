<?php
require('../../base.php');

/*
 * author Yu Li <yu.li@geneegroup.com>
 * 处理希恩思数据
 */


$sale_info = [
    'info' => '此商品有折扣',
    'types' => ['折'],
];
$sale_info = json_encode($sale_info, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);


$db = Database::factory();
$db->query("UPDATE product SET sale_info='%s' where vendor_id=64 and type='consumable'", $sale_info);
