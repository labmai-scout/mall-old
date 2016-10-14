#!/usr/bin/env php
<?php
//批量发布商品
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
	publish_all_products($vid);
}
else if ($vp_ids) {
	publish_products($vp_ids);
}

clean_cache();

function publish_products($vp_ids){
	$n_success = 0;
	$n_fail = 0;

	foreach ($vp_ids as $vp_id) {
		$vp = O('product', $vp_id);

		if (!$vp->id) {
			fecho(strtr("ID#%id 无对应商品",
						array('%id' => $vp_id)));
			$n_fail++;
			continue;
		}

		if ($vp->publish_date > 0) {
			fecho(strtr("%vp[%id] 已发布",
						array('%vp' => $vp->name,
							  '%id' => $vp->id)));
			$n_fail++;
			continue;
		}

		if($vp->publish()){
			$n_success++;
		}
		else{
			$n_fail++;
		}

	}

	$total = $n_success + $n_fail;
	$summary = "$total 个商品需要发布\n" .
		"$n_success / $total 发布成功\n" .
		"$n_fail / $total 发布失败";


	secho($summary, TRUE);

	if ($n_fail > 0) {
		exit(1);
	}
}


function publish_all_products($vid){

	$vendor = O('vendor',$vid);
	if(!$vendor->id){
		fecho('供货商不存在');
	}

	$db = Database::factory();
	$sphinx = Database::factory('@sphinx');

	$total = $db->query("SELECT count(*) as count FROM `product` WHERE vendor_id={$vid} AND `publish_date`=0")->row();
	$total = $total->count;

	$publish_date = Date::time();

	$ret = $db->query("UPDATE `product` SET `publish_date`={$publish_date} WHERE `publish_date`=0 and vendor_id={$vendor->id}");

	if($ret) {
		//更新product表的索引
		$product_table = Search_Iterator::get_index_name('product');
		$sphinx_sql = "UPDATE `$product_table` SET `publish_date`={$publish_date} WHERE `vendor_id`={$vendor->id} and `publish_date`=0";
		$sphinx->query($sphinx_sql);

		//从配置中拿到各个类别的名称
		$types_sphinx_indexes = Config::get('product.types_sphinx_indexes');
		$product_types = array_keys($types_sphinx_indexes);

		//更新各个类别表的索引
		foreach ($product_types as $sp) {
			//更新sphinx，只是将对应商品的approve_date变为0，所以直接update
			$pt = Search_Iterator::get_index_name('product_'.$sp);
			$sphinx_sql = "UPDATE `$pt` SET `publish_date`={$publish_date} WHERE `vendor_id`={$vendor->id} and `publish_date`=0";
			$sphinx->query($sphinx_sql);
		}

	}

	$n_fail = $db->query("SELECT count(*) as count FROM `product` WHERE vendor_id={$vid} AND `publish_date`=0")->row();
	$n_fail = $n_fail->count;

	$n_success = $total - $n_fail;
	$summary = "$total 个商品需要发布\n" .
		"$n_success / $total 发布成功\n" .
		"$n_fail / $total 发布失败";

	secho($summary, TRUE);

	$vendor->publish_vp_pid = 0;
	$vendor->last_publish_products_time = Date::time();
	$vendor->last_publish_products_result = $summary;
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
