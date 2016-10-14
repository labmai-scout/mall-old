<?php
/*
*	删除该供应所有没有和订单关联的商品
*/
require 'base.php';
$vendor_id = $argv[1];
$vendor = O('vendor', $vendor_id);
$vid = $vendor->id;
if (!$vid) {
	echo '请输入供货商ID';
	exit;
}
// 删除策略 判断商城是否存在 vendor_id 为 0 的商品, 存在 报错返回
// 查询供应商对应的订单的商品, vendor_id 置 0, 同时sphinx索引对应的 vendor_id 也置0,
// 批量删除vendor_id 为对应供应商的商品
$count = Q('product[vendor_id=0]')->total_count();
if (Q('product[vendor_id=0]')->total_count()) {
	echo '存在 vendor_id 为 0 的商品数据, 请查明原因还原至对应的供应商再执行脚本';
	exit;
}

$db = Database::factory();
$sphinx = Database::factory('@sphinx');
$ret = TRUE;
$pids = [];
$fail_pids = [];
$order_items = Q("order[vendor=$vendor] order_item");
foreach ($order_items as $order_item) {
	$product = $order_item->product;
	if (!in_array($product->id, $pids)) {
		$product->vendor_id = 0;
		if ($product->save()) {
			$pids[$product->id] = $product->id;
			echo '.';
		}
		else {
			$fail_pids[$product->id] = $product->id;
			$ret = FALSE;
			echo 'x';
		}
	}
}

if ($ret) {
	$SQL = "DELETE FROM product WHERE vendor_id=$vid";
	$ret = $db->query($SQL);
	if ($ret) {
		$main_table = Search_Iterator::get_index_name('product');
		$SQL2 = "DELETE FROM $main_table WHERE vendor_id=$vid";
		$sphinx->query($SQL2);
		//子表
		$types = Config::get('product.types');
		foreach ($types as $type => $foo) {
			$sub_table = Search_Iterator::get_index_name('product_'.$type);
			$SQL3 = "DELETE FROM $sub_table WHERE vendor_id=$vid";
			$sphinx->query($SQL3);
		}
	}

	//还原订单关联商品
	foreach ($pids as $pid) {
		$product = O('product', $pid);
		if ($product->name) {
			$product->vendor = $vendor;
			if ($product->save()) {
				echo '*';
			}
			else {
				echo '!';
			}
		}
	}
}
else {
	echo '出现商品保存失败, 请查明原因, 商品列表: '.implode(',', $fail_pids);
}
clean_cache();
?>