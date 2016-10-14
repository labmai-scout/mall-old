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
	unpublish_all_products($vid);
}
else if ($vp_ids) {
	unpublish_products($vp_ids);
}
clean_cache();

//下架指定id的商品
function unpublish_products($vp_ids){

	$db = Database::factory();
	$n_success = 0;
	$n_fail = 0;
	foreach ($vp_ids as $vp_id) {

		$product = O('product', $vp_id);
		if(!$product->id) {
			$n_fail++;
			continue;
		}

		if($product->unpublish()){
			$n_success++;
		}
		else{
			$n_fail++;
		}
	}

	$total = $n_success + $n_fail;
	$summary = "$total 个商品需要下架\n" .
		"$n_success / $total 下架成功\n" .
		"$n_fail / $total 下架失败";


	secho($summary, TRUE);

	if ($n_fail > 0) {
		exit(1);
	}
}

//下架供货商的所有商品
function unpublish_all_products($vid){

	$vendor = O('vendor',$vid);
	if(!$vendor->id){
		fecho('供货商不存在');
	}

	$n_success = 0;
	$n_fail = 0;

	$unapprove_date = Date::time();

	$db = Database::factory();
	$sphinx = Database::factory('@sphinx');
	$total = $db->query("SELECT count(*) count FROM product WHERE vendor_id={$vid} AND approve_date>0")->row()->count;

	//事务处理历史版本备份
	$db->query("BEGIN");

	$insert_history = "INSERT INTO `product_revision` (vendor_id,unit_price,orig_price,sale_info,vendor_note,approver_id,approve_date,publisher_id,publish_date,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra,last_approver_id,last_approve_date,last_publisher_id,last_publish_date,unapprover_id,unapprove_date, product_id,version) (SELECT vendor_id,unit_price,orig_price,sale_info,vendor_note,approver_id,approve_date,publisher_id,publish_date,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra,last_approver_id,last_approve_date,last_publisher_id,last_publish_date,unapprover_id,unapprove_date,id,version FROM product WHERE vendor_id = $vid AND approve_date>0 and dirty=1)";

	//将product的数据生成历史版本在product_history中,将version+1
	$ret1 = $db->query($insert_history);
    //先把version需要加1的product处理完
	$ret2 = $db->query("UPDATE `product` SET `version`=`version`+1, `dirty`=0 WHERE vendor_id={$vid} AND approve_date>0 AND dirty=1");

    //处理所有的商品
	$update_product = "UPDATE `product` SET `last_approver_id`=`product`.`approver_id`, `last_approve_date`=`product`.`approve_date`, `unapprove_date`={$unapprove_date}, `last_publisher_id`=`product`.`publisher_id`, `last_publish_date`=`product`.`publish_date`, `approve_date`=0, `approver_id`=0, `publisher_id`=0, `publish_date`=0, `freeze_reasons`='' WHERE vendor_id={$vid} AND approve_date>0";
	$ret3 = $db->query($update_product);

	if($ret1 && $ret2 && $ret3){

    	$db->query("COMMIT");
		//更新product表的索引
		$product_table = Search_Iterator::get_index_name('product');
		$sphinx_sql = "UPDATE `$product_table` SET `approve_date`=0,`publish_date`=0 WHERE `vendor_id`={$vendor->id} and `approve_date`>0";
		$sphinx->query($sphinx_sql);

		//从配置中拿到各个类别的名称
		$types_sphinx_indexes = Config::get('product.types_sphinx_indexes');
		$product_types = array_keys($types_sphinx_indexes);

		//更新各个类别表的索引
		foreach ($product_types as $sp) {
			//更新sphinx，只是将对应商品的approve_date变为0，所以直接update
			$pt = Search_Iterator::get_index_name('product_'.$sp);
			$sphinx_sql = "UPDATE `$pt` SET `approve_date`=0,publish_date=0 WHERE `vendor_id`={$vendor->id} and `approve_date`>0";
			$sphinx->query($sphinx_sql);
		}

	    echo '提交成功。';

	    $n_fail = $db->query("SELECT count(*) count FROM product WHERE vendor_id={$vid} AND approve_date>0")->row()->count;
	    $n_success = $total - $n_fail;
		$summary = "$total 个商品需要下架\n" .
			"$n_success / $total 下架成功\n" .
			"$n_fail / $total 下架失败";
		secho($summary, TRUE);
	}else{
		$db->query("ROLLBACK");
	    echo '数据回滚。';
	    $summary = "商品下架失败";
	    secho($summary, TRUE);
	}

	$db->query("END");

	$vendor->unpublish_vp_pid = 0;
	$vendor->last_unpublish_products_time = Date::time();
	$vendor->last_unpublish_products_result = $summary;
	$vendor->save();

	$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");

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
