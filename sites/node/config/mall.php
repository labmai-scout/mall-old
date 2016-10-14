<?php

$config['footer_msg'] = '服务热线：400-0522-624';

$config['home_default_tab'] = 'home';

// 当天凌晨4天停止导入完成
$config['upload_end_time'] = [
    'end'  => '4',
];

$config['enable_register'] = FALSE;

$config['name'] = 'demo';

$config['gapper'] = [
    //'client_id' => '57573ed77ee39083ba7435a7a0b804cb3d01add3', //mall-old在gapper的client_id
    'client_id' => 'node-mall-old', //mall-old在gapper的client_id
    'client_secret' => 'node-mall-old', //mall-old在gapper的client_secret
    'api' => 'http://genee.cn/api',
];

$config['hub-vendor'] = [
    'api'=> 'http://vendor.hub.genee.cn/api',
    'client_id' => 'node-mall-old', //mall-old在gapper的client_id
    'client_secret' => 'node-mall-old', //mall-old在gapper的client_secret
];

$config['hub-node'] = [
    'api'=> 'http://node.hub.genee.cn/api',
    'client_id' => 'node-mall-old', //mall-old在gapper的client_id
    'client_secret' => 'node-mall-old', //mall-old在gapper的client_secret
];

$config['hub-product'] = [
    'api'=> 'http://product.hub.genee.cn/api',
    'client_id' => 'node-mall-old', //mall-old在gapper的client_id
    'client_secret' => 'node-mall-old', //mall-old在gapper的client_secret
];

$config['chem-db'] = [
	'api'=> 'http://chem-db.genee.cn/api'
];

//$config['haz-control-enable'] = true;
