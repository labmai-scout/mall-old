#!/usr/bin/env php
<?php
//根据cas号，下架对应的易制毒商品
require 'base.php';

$shortopts = "c:";
$longopts = array(
	'cas:',
	);

$opts = getopt($shortopts, $longopts);
$cas = $opts['c'] ?: $opts['cas'];
if(!$cas) {
	echo "usage:\n";
	echo "  php unpublish_cas_products.php -c 60-79-7\n";
	echo "  php unpublish_cas_products.php -cas=60-79-7\n";
	die;
}

$db = Database::factory();
$sphinx = Database::factory('@sphinx');

//有资质的供货商
$scope_vendors = '30, 20, 24';

$num = 100;
while (TRUE) {
	$products = $db->query("select id,vendor_id from product where approve_date>0 and type='reagent' and vendor_id not in ({$scope_vendors}) and _extra like '%cas_no\"\:\"$cas\"%' limit $num")->rows();
	if (!count($products)) break;
	$ids = [];
	$vendors = [];
	foreach ($products as $product) {

		$ids[$product->id] = $product->id;
		$vendors[$product->vendor_id] = $product->vendor_id;
	}

	$ids = join(',', $ids);

	$ret = $db->query("UPDATE `product` SET `approve_date`=0, `publish_date`=0 WHERE id in ($ids)");
	if(!$ret) {
		echo '易制毒下架失败';
		echo "UPDATE `product` SET `approve_date`=0, `publish_date`=0 WHERE id in ($ids)";
		die;
	}

	//更新product索引
	$product_table = Search_Iterator::get_index_name('product');
	$sphinx_sql = "UPDATE `$product_table` SET `approve_date`=0, `publish_date`=0 WHERE id IN ($ids)";
	$sphinx->query($sphinx_sql);

	//更新类别表的索引
	$pt = Search_Iterator::get_index_name('product_reagent');
	$sphinx_sql = "UPDATE `$pt` SET `approve_date`=0, `publish_date`=0 WHERE id IN ($ids)";
	$sphinx->query($sphinx_sql);

	foreach ($vendors as $vendor) {
		$vid = intval($product->vendor_id);
		$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");
	}

	echo '.';
}
clean_cache();
