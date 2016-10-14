#!/usr/bin/env php
<?php
    /*
     * file create_order.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date  2013/07/24
     *
     * useage SITE_ID=wiscom php create_order.php
     * brief 抓取wiscom数据，发送rpc到mall的api，创建后的order再order_mark_read
     */

require 'base.php';

$SSL = new OpenSSL();

//当前身份为wiscom
$servers = Config::get('rpc.servers');

//发送到的服务器为mall.nankai
$rpc_info = $servers['mall.nankai'];

//远程服务器公钥
$remote_pubkey = $rpc_info['public_key'];

//本地服务器私钥
$local_privkey = Config::get('rpc.private_key');

//本地服务器名称,本地服务器名称为mall.nankai
$local_server_name = Config::get('rpc.server_name');

//获取随机数
$random = @openssl_random_pseudo_bytes('20');

//随机数用远程服务器公钥加密
$encrypted_by_remote_pubkey = $SSL->encrypt($random, $remote_pubkey, 'public');

//随机数使用本地私钥签名
$signed_by_local_prikey = $SSL->sign($random, $local_privkey);

$rpc = new RPC($rpc_info['url'], 'order');

//base64_encode防止出现数据传输错误情况产生
if(!@base64_encode($rpc->auth(@base64_encode($encrypted_by_remote_pubkey), @base64_encode($signed_by_local_prikey), $local_server_name))) {
    die('rpc链接失败');
}

$ws = new Wiscom_Order(Config::get('wiscom.database'));

while ($data = $ws->get_next_order()) {

    $change = array(
        'SQR'=> 'requester',
        'CGBM'=> 'buy_no',
        'SJPM'=> 'reagent',
        'GHS'=> 'vendor',
        'SCMLH'=> 'catalog_no',
        'BZLX'=> 'package',
        'SL'=> 'quantity',
        'ZJ'=> 'price',
        'SCWID' => 'id'
    );

    //新order数据
    $new_order_data = array();

    //对通过ws获取的数据进行过滤和转换处理
    foreach($change as $key=> $value) {
        $new_order_data[$value] = $data[$key];
    }

    $order_id = $rpc->create_order($new_order_data);

    if ($order_id) {

        $mark_order_data = array();

        //申请人
        $mark_order_data['card_no'] = $data['SQR'];

        //采购编码
        $mark_order_data['wiscom_no'] = $data['CGBM'];

        //订单编号
        $mark_order_data['order_id'] = $order_id;

        //默认mark的order_status 为 1
        $mark_order_data['order_status'] = 1;

        //mark时, grant_no暂时设定为''
        $mark_order_data['grant_no'] = '';

        $ws->order_mark_read($mark_order_data);
    }

    unset($data);
}
