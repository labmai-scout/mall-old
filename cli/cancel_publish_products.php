#!/usr/bin/env php
<?php
//批量下架商品
require('base.php');
$shortopts = 'v:p:b';
$longopts = array(
	'vendor',
	'black',
	);

$opts = getopt($shortopts, $longopts);

if (isset($opts['b']) || isset($opts['black'])) {
	define('MONOCHROME', TRUE);
}
// 获得 vendor
$vid = $opts['v'] ? : $opts['vendor'];

$vp_ids = $opts['p'];

if (!($vid || $vp_ids)) {
	fecho('必须提供 v 或 p', TRUE);
}

if ($vid && $vp_ids) {
	fecho('v 和 p 不可同时使用', TRUE);
}

$vp_ids = explode(',', $opts['p']);

if ($vid) {
	cancel_all_publish_products($vid);
}
else if ($vp_ids) {
	cancel_publish_products($vp_ids);
}
clean_cache();
//下架指定id的商品
function cancel_publish_products($vp_ids){

	$db = Database::factory();
	$n_success = 0;
	$n_fail = 0;
	foreach ($vp_ids as $vp_id) {

		$product = O('product', $vp_id);
		if(!$product->id) {
			$n_fail++;
			continue;
		}
		if ($product->approve_date > 0) {
			//该商品已经通过审核了,该脚本只操作待审核的商品
			$n_fail++;
		}
		else {
			$product->publish_date = 0;
			$product->publisher_id = 0;
			if ($product->save()) {
				$n_success++;
			}
			else {
				$n_fail++;
			}
		}
	}

	$total = $n_success + $n_fail;
	$summary = "$total 个商品需要取消发布\n" .
		"$n_success / $total 取消发布成功\n" .
		"$n_fail / $total 取消发布失败";


	secho($summary, TRUE);

	if ($n_fail > 0) {
		exit(1);
	}
}

//下架供货商的所有商品
function cancel_all_publish_products($vid){

	$vendor = O('vendor',$vid);
	if(!$vendor->id){
		fecho('供货商不存在');
	}

	$db = Database::factory();
	$sphinx = Database::factory('@sphinx');

	$total = $db->query("SELECT count(*) as count FROM `product` WHERE vendor_id={$vid} AND `publish_date`>0 AND `approve_date`=0")->row();
	$total = $total->count;

	$publish_date = Date::time();

	$ret = $db->query("UPDATE `product` SET `publish_date`=0 WHERE `publish_date`>0 AND `approve_date`=0 AND vendor_id={$vendor->id}");

	if($ret) {
		//更新product表的索引
		$product_table = Search_Iterator::get_index_name('product');
		$sphinx_sql = "UPDATE `$product_table` SET `publish_date`=0 WHERE `vendor_id`={$vendor->id} and `publish_date`>0 and `approve_date`=0";
		$sphinx->query($sphinx_sql);

		//从配置中拿到各个类别的名称
		$types_sphinx_indexes = Config::get('product.types_sphinx_indexes');
		$product_types = array_keys($types_sphinx_indexes);

		//更新各个类别表的索引
		foreach ($product_types as $sp) {
			//更新sphinx，只是将对应商品的approve_date变为0，所以直接update
			$pt = Search_Iterator::get_index_name('product_'.$sp);
			$sphinx_sql = "UPDATE `$pt` SET `publish_date`=0 WHERE `vendor_id`={$vendor->id} and `publish_date`>0 and `approve_date`=0";
			$sphinx->query($sphinx_sql);
		}

	}

	$n_fail = $db->query("SELECT count(*) as count FROM `product` WHERE vendor_id={$vid} AND `publish_date`>0 AND `approve_date`=0")->row();
	$n_fail = $n_fail->count;

	$n_success = $total - $n_fail;
	$summary = "$total 个商品需要发布\n" .
		"$n_success / $total 发布成功\n" .
		"$n_fail / $total 发布失败";

	secho($summary, TRUE);

	$vendor->cancel_vp_pid = 0;
	$vendor->last_cancel_products_time = Date::time();
	$vendor->last_cancel_products_result = $summary;
	$vendor->save();

	if ($n_fail > 0) {
		exit(1);
	}
}

function fecho($msg, $is_deadly = FALSE, $is_important = FALSE) {

	if ($is_deadly || defined('VERBOSE') || $is_important) {
		if (defined('MONOCHROME')) {
			echo $msg . "\n";
		}
		else {
			Upgrader::echo_fail($msg. "\n");
		}
	}

	if ($is_deadly) {
		die;
	}
}

function secho($msg, $is_important = FALSE) {

	if (defined('VERBOSE') || $is_important) {
		if (defined('MONOCHROME')) {
			echo $msg . "\n";
		}
		else {
			Upgrader::echo_success($msg. "\n");
		}
	}
}
