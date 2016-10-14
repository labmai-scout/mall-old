#!/usr/bin/env php
<?php

/*
	usage:

    1. SITE_ID=nankai php approve_reagent_product.php -v,--verndor #vendor_id [-d, --dry] [-b, --black]
	e.g. SITE_ID=nankai php approve_reagent_product.php -v 5

    2. SITE_ID=nankai php approve_reagent_product.php -p,--product #vendor_product_ids [-d, --dry] [-b, --black]
	e.g. SITE_ID=nankai php approve_reagent_product.php -p 1,2,3

	-v, --vendor	批量审核某 vendor 的所有 vendor_product
	-p, --product	批量审核指定 id 的 vendor_product
	-d, --dry		dry run
	-b, --black		单色输出
	-V, --verbose	输出详细信息

	version: 0.5

	changelog:
	v0.6 删除product属性，所以不再需要merge相关的操作了
	v0.5 (xiaopei.li@2012-09-20)
	增加出错返回码:
	0	全部通过
	1	有失败记录

	v0.4 (xiaopei.li@2012-07-03)
	增加 -V, --verbose 参数, 若不用 -V, 只输出总结信息, 加 -V 才输出每个 vendor_product 的信息

	v0.3 (xiaopei.li@2012-06-07)
	1. 增加 -p 选项, 可对指定 id 的 vendor_product 通过审批;
	2. 在 vendor_product 审批前 trigger 判断条件;
	3. 对 approve_vendor() 增加分页;
	4. 使用 -v 时, 在批量批准前后修改 vendor 的属性以标记正在操作.

	v0.2 (xiaopei.li@2012-06-06)
	增加 merge, dry run 参数. 若使用 merge, 则新审核的商品都归入已有某产品名下.

	v0.1 (xiaopei.li@2012-04-30)
	仅通过所有不需合并的 vendor_product, 需合并的保持"待审核", 不做处理.

*/

require 'base.php';

/***** main *****/

// read opts
$shortopts = "v:p:dbV";
$longopts = array(
	'vendor:',
	'product:',
	'dry',
	'black',
	'verbose',
	);

$opts = getopt($shortopts, $longopts);

// 获得 vendor
$vid = $opts['v'] ? : $opts['vendor'];

// 获得 product
if ( $vp_id = $opts['p'] ? : $opts['product']) {
	$vp_ids = explode(',', $vp_id);
}

if (!($vid || $vp_ids)) {
	fecho('必须提供 v 或 p', TRUE);
}

if ($vid && $vp_ids) {
	fecho('v 和 p 不可同时使用', TRUE);
}

if (isset($opts['d']) || isset($opts['dry'])) {
	define('DRYRUN', TRUE);
}

if (isset($opts['b']) || isset($opts['black'])) {
	define('MONOCHROME', TRUE);
}

if (isset($opts['V']) || isset($opts['verbose'])) {
	define('VERBOSE', TRUE);
}

// approve
$products = array();

if ($vid) {
	approve_vendor($vid);
}
else if ($vp_ids) {
	approve_product($vp_ids);
}
clean_cache();
/***** functions *****/

// 批量通过某商家下的所有商品
function approve_vendor($vid) {
	$vendor = O('vendor', $vid);
	$gapper_group = $vendor->gapper_group;
	if (!$vendor->id) {
		fecho('vendor 不存在', TRUE);
	}

	if (!$vendor->approve_date || !$vendor->publish_date) {
		fecho('供应商已下架!', TRUE);
	}

	if (!$gapper_group) return FALSE;

	Product_Model::vendorApprove($gapper_group);
	$summary = "您的请求已收到，审批后不会立即更新索引，请耐心等候\n";
	$vendor->last_approve_products_time = Date::time();
	$vendor->last_approve_products_result = $summary;
	$vendor->save();

	/*
	$db = Database::factory();
	$sphinx = Database::factory('@sphinx');

	// 批量通过已发布的商品
	$total = Q("product[vendor={$vendor}][publish_date>0][approve_date<=0]")->total_count();

	$vendor->last_approve_products_result = '';

	$pid = getmypid();
	$vendor->approve_vp_pid = $pid;
	$vendor->save();


	$now = time();
	$vendor_scopes = Q("vendor_scope[vendor=$vendor][expire_date>$now]");

	foreach ($vendor_scopes as $vendor_scope) {
		//处理不同类别的商品，如果资质未过期则可批量上架
		Event::trigger('vendor_scope.approve', $vendor_scope);
	}

	$n_fail = Q("product[vendor={$vendor}][publish_date>0][approve_date<=0]")->total_count();

	$n_success = $total - $n_fail;
	$summary = "$total 个商品需要审批\n" .
		"$n_success / $total 审批通过\n" .
		"$n_fail / $total 审批失败";

	$vendor->last_approve_products_time = Date::time();
	$vendor->last_approve_products_result = $summary;

	$vendor->last_approve_products_retval = 0;
	if ($n_fail > 0) {
		$vendor->last_approve_products_retval = 1;
	}

	$vendor->approve_vp_pid = 0;
	$vendor->save();
	$vid = intval($vendor->id);

	$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");

	secho($summary, TRUE);

	if ($n_fail > 0) {
		exit(1);
	}
	*/

}

// 批量通过指定 id 的 vendor_product
function approve_product($product_ids) {

	Product_Model::batchApprove($product_ids);

	// $n_success = 0;
	// $n_fail = 0;

	// foreach ($product_ids as $vp_id) {
	// 	$vp = O('product', $vp_id);

	// 	if (!$vp->id) {
	// 		fecho(strtr("ID#%id 无对应商品",
	// 					array('%id' => $vp_id)));
	// 		continue;
	// 	}

	// 	if ($vp->publish_date <= 0) {
	// 		fecho(strtr("%vp[%id] 未发布",
	// 					array('%vp' => $vp->name,
	// 						  '%id' => $vp->id)));
	// 		continue;
	// 	}

	// 	if ($vp->approve_date > 0) {
	// 		fecho(strtr("%vp[%id] 已通过审批",
	// 					array('%vp' => $vp->name,
	// 						  '%id' => $vp->id)));
	// 		continue;
	// 	}

	// 	$not_allow_msg = Event::trigger('product.get_not_allow_approve_msg', $vp);
	// 	if ($not_allow_msg) {
	// 		$n_fail++;

	// 		fecho(strtr("%vp[%id] 合并失败: %reason",
	// 					array('%vp' => $vp->name,
	// 						  '%id' => $vp->id,
	// 						  '%reason' => $not_allow_msg)));
	// 		continue;
	// 	}

	// 	//如果商品有product属性，说明是下架商品，直接审核
	// 	if($vp->last_publish_date > 0){
	// 		$vp->approve();
	// 		$n_success++;
	// 	}
	// 	else{
	// 		$ret = approve_new($vp);

	// 		if ($ret) {
	// 			$n_success++;
	// 		}
	// 		else {
	// 			$n_fail++;
	// 		}
	// 	}

	// 	$vid = intval($vp->vendor_id);
	// 	$db = ORM_Model::db('vendor');
	// 	$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");
	// }

	// $total = $n_success + $n_fail;
	// $summary = "$total 个商品需要审批\n" .
	// 	"$n_success / $total 审批通过\n" .
	// 	"$n_fail / $total 审批失败";

	secho(HT('审批成功'), TRUE);

	// if ($n_fail > 0) {
	// 	exit(1);
	// }

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

function approve_new($product) {

	$db = Database::factory();
	if (defined('DRYRUN')) {
		secho(T('%vp_name(%vp_id) approve_new', array(
					'%vp_name' => $product->name,
					'%vp_id' => '#' . Number::fill($product->id, 6),
					)));
		return;
	}

	$ret = FALSE;

	if ($db->query('UPDATE `product` SET approve_date = %d WHERE id = %d', Date::time(), $product->id)) {
		$ret = TRUE;
		$product->approve_date = Date::time();
		Search_Product::update_index($product);
	}

	if ($ret) {
		secho(T('%vp_name(%vp_id) 审批通过', array(
					'%vp_name' => $product->name,
					'%vp_id' => '#' . Number::fill($product->id, 6),
					)));
	}
	else {
		fecho(T('%vp_name(%vp_id) 审批出错', array(
					'%vp_name' => $product->name,
					'%vp_id' => '#' . Number::fill($product->id, 6),
					)));

	}

	return $ret;

}
