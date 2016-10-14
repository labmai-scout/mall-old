#!/usr/bin/env php
<?php
/*
* 之前有四家供应商在通过 csv 导入 商品数据的时候错误导致数据异常
* 删除对应的数据并安排人员再次进行导入，在这次的脚本中删除对应的商品
* 四家供应商分别为 莫可曼(51) 安耐吉(52) 诺赛格(61) 鼎国(33)
* 删除对应的商品重新导入
*/
$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;
$vids = array(33,51,52,61);
//收集产生了订单的商品
foreach ($vids as $vid) {
	$orders = Q("order[vendor_id=$vid]");
	foreach ($orders as $order) {
		$items = Q("order_item[order={$order}]");
		foreach ($items as $item) {
			$product = $item->product;
			if ($product->id) {
				$pids[$product->id] = $product->id;
			}
		}
	}
}

function delete_vendor_product($vendor_id, $dtstart, $dtend, $pids) {
	$index = 'product';
	$ids = implode(', ', $pids);
	$sphinx = Database::factory('@sphinx');
	$db = Database::factory();
	$db->query('DELETE FROM `'.'%s'.'` WHERE vendor_id=%d AND ctime<%d AND ctime>%d AND id not in(%s)', $index, $vendor_id, $dtend, $dtstart, $ids);
	// 删除主表和所对应的分表
	$index_name =  Search_Iterator::get_index_name(Search_Product::$model_name);
	$sphinx->query('DELETE FROM `'.$index_name.'` WHERE vendor_id=%d AND publish_date<%d AND publish_date>%d AND id not in(%s)', $vendor_id, $dtend, $dtstart, $ids);
	$configs = Config::get('product.types');
	foreach ($configs as $sub => $foo) {
		$sub_index = $index_name.'_'.$sub;
		$sphinx->query('DELETE FROM `'.$sub_index.'` WHERE vendor_id=%d AND publish_date<%d AND publish_date>%d AND id not in(%s)', $vendor_id, $dtend, $dtstart, $ids);
	}
	echo 'vendor_id:'.$vendor_id.'删除成功';
}

//三个供应商分别处理
//莫可曼(51) 针对的是2013/12/28 导入的 csv 脚本 6160个数据
delete_vendor_product(51, 1388160000, 1388246400, $pids);
//安耐吉(52) 针对的是2013/12/09 导入的 csv 脚本 14万数据
delete_vendor_product(52, 1386518400, 1386604800, $pids);
//诺赛格(61) 针对的是2013/12/28 导入的 csv 脚本 6160个数据
delete_vendor_product(61, 1388160000, 1388246400, $pids);
//鼎国(33) 针对的是2013/12/24 导入的 csv 脚本 3万数据
delete_vendor_product(33, 1387814400, 1387900800, $pids);
if (count($pids)) {
	echo "\n".'关联订单的商品 id 包括:'.implode(', ', $pids);
}
?>	
