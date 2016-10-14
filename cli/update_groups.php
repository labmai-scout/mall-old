#!/usr/bin/env php
<?php
    /*
     * file update_groups.php
     * author Liu Cheng <cheng.liu@geneegroup.com>
     * date 2013-2-28
     *
     * useage SITE_ID=nankai php update_groups.php
     * brief 从远程wiscom的站点获取相应的groups数据更新到本地数据库
     */
require 'base.php';

$SSL = new OpenSSL();

//当前身份为mall
$servers = Config::get('rpc.servers');
//发送到的服务器为mall.wiscom
$rpc_info = $servers['mall.wiscom'];
//远程服务器公钥
$remote_pubkey = $rpc_info['public_key'];
//本地服务器私钥
$local_privkey = Config::get('rpc.private_key');
//本地服务器名称
$local_server_name = Config::get('rpc.server_name');
//获取随机数
$random = @openssl_random_pseudo_bytes('20');
//随机数用远程服务器公钥加密
$encrypted_by_remote_pubkey = $SSL->encrypt($random, $remote_pubkey, 'public');
//随机数使用本地私钥签名
$signed_by_local_prikey = $SSL->sign($random, $local_privkey);
$rpc = new RPC($rpc_info['url'], 'user');
//base64_encode防止出现数据传输错误情况产生
if(!@base64_encode($rpc->auth(@base64_encode($encrypted_by_remote_pubkey), @base64_encode($signed_by_local_prikey), $local_server_name))) {
    die('rpc链接失败');
}

$groups = $rpc->get_groups();

$root = Tag_Model::root('group');

if (count($groups)) foreach ($groups as $key => $row) {
	$self_code = $row['DWDM'];
	$self_name = $row['DWMC'];
	$parent_code = $row['SJDM'];

	$parent = $root;

	/*
	 *  code是用已存储金智那边单位代码的数据，该代码仅在南开服务器上使用
	 */
	if ($parent_code) $parent = O('tag', array('code' => $parent_code, 'root' => $root));

	$group = O('tag', array('code' => $self_code, 'parent' => $parent, 'root' => $root));

	if (!$group->id) {
		$group->code = $self_code;
		$group->parent = $parent;
		$group->root = $root;
	}

	$group->name = $self_name;

	if ($group->save()) {
		clean_cache($group);
		echo '.';
	}
	else {
		echo 'x';
	}
}

echo "\n";
