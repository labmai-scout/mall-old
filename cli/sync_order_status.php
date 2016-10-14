#!/usr/bin/env php
<?php
    /*
     * file sync_order_status.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013-08-01
     *
     * useage SITE_ID=wiscom php sync_order_status.php
     * brief 发送rpc到mall，抓取已完成状态的order的相关信息，再通过wiscom_order更新到中间库
     * 该脚本crontab，每两小时
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

$last_activity_time = Site::get('last_activity_time');
if (!$last_activity_time) $last_activity_time = 0;

$search_opts = array(
    'time'=> $last_activity_time,
    'status'=> Order_Model::STATUS_PAID, //已结算(已完成)
    'type' => 'reagent',
    'rgt_type' => 3
);

$fetch_keys = array(
    'grant_no',     //经费代码
    'status',       //状态
    'time',         //操作时间
    'id',           //order的id
);

while($orders = $rpc->get_orders($search_opts, $fetch_keys)) {

    foreach($orders as $order) {

        $change = array(
            'grant_no'=> 'grant_no',
            'status'=> 'order_status',
            'id'=> 'order_id',
        );

        foreach($change as $key => $value) {
            $data[$value] = $order[$key];
        }

        $ws->update_order_status($data);
    }

    $search_opts['time'] = $order['time'] + 1;
}

Site::set('last_activity_time', $search_opts['time']);
