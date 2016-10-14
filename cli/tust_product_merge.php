<?php

//科大商品数据与南大商品数据合并（需先合并供应商）
//科大相关表为"表名_tust" product_tust order_item_tust product_revision_tust

require "base.php";
clean_cache();
$db = Database::factory();

//获得冲突的ID
// $sql = "select nankai.id as nankai_id, tust.id as tust_id from product_tust as tust join " . 
// 	"product as nankai on (nankai.manufacturer = tust.manufacturer and (select gapper_group " . 
// 	"from vendor where id = nankai.vendor_id) = (select gapper_group from vendor_tust " . 
// 	"where id = tust.vendor_id) and nankai.catalog_no = tust.catalog_no and " . 
// 	"nankai.package = tust.package);";
$sql = "select nankai.id as nankai_id, tust.id as tust_id from product_tust as tust join " . 
	"product as nankai on (nankai.manufacturer = tust.manufacturer and " . 
	"nankai.vendor_id = tust.vendor_id and nankai.catalog_no = tust.catalog_no and " . 
	"nankai.package = tust.package);";
$conflict_ids = $db->query($sql)->rows();
if ($conflict_ids) {
	echo "获得冲突的商品ID完成\n";
} else {
	echo "获得冲突的商品ID失败\n";
	die;
}

//为科大product补全fixed_price
$sql = "alter table product_tust add fixed_price int(11) not null default 0;";
if (!$db->query($sql)) {
	echo "添加一口价字段失败\n";
	die;
}

$sql = "create index fixed_price on product_tust(fixed_price);";
if (!$db->query($sql)) {
	echo "创建一口价索引异常\n";
	die;
}
echo "添加一口价属性完成\n";

$nankai_ids = [];
$tust_ids = [];
$delete_ids = [];
$flag = false;
if ($conflict_ids) {
	//获取有订单的商品ID
	foreach ($conflict_ids as $value) {
		$nankai_ids[] = $value->nankai_id;
		$tust_ids[] = $value->tust_id;
	}
	$nankai_ids = implode(',', $nankai_ids);
	$tust_ids = implode(',', $tust_ids);
	$nankai_sql = "select id, product_id from order_item where product_id in ($nankai_ids);";
	$nankai_has_order_ids = $db->query($nankai_sql)->rows();
	$tust_sql = "select id, product_id from order_item_tust where product_id in ($tust_ids);";
	$tust_has_order_ids = $db->query($tust_sql)->rows();
	
	foreach ($nankai_has_order_ids as $value) {
		$nankai_has_order_ids[] = $value->product_id;
	}
	foreach ($tust_has_order_ids as $value) {
		$tust_has_order_ids[] = $value->product_id;
	}

	$file = fopen('tust_product_merge.txt', 'w');
	fwrite($file, "\n冲突的商品ID:\n");
	foreach ($conflict_ids as $id) {
		fwrite($file, 'nankai: ' . $id->nankai_id . '; tust: ' . $id->tust_id);
		//南大有订单，科大没有
		if (in_array($id->nankai_id, $nankai_has_order_ids) && 
			!in_array($id->tust_id, $tust_has_order_ids)) {
			fwrite($file, " 南大有订单,科大没有\n");
			$delete_ids[] = $id->tust_id;
		}
		//科大有订单，南大没有
		elseif (!in_array($id->nankai_id, $nankai_has_order_ids) && 
			in_array($id->tust_id, $tust_has_order_ids)) {
			fwrite($file, " 科大有订单,南大没有\n");
			$flag = true;
		}
		//南大科大都有订单
		elseif (in_array($id->nankai_id, $nankai_has_order_ids) && 
			in_array($id->tust_id, $tust_has_order_ids)) {
			fwrite($file, " 南大科大都有订单\n");
			$delete_ids[] = $id->tust_id;
		}
		//南大科大都没有订单
		else {
			fwrite($file, " 南大科大都没有订单\n");
			$delete_ids[] = $id->tust_id;
		}
	}
	fclose($file);

	if ($flag) {
		echo "科大有订单,南大没有\n";
		die;
	}

	$delete_ids = implode(',', $delete_ids);
	//将科大的数据插入的南大的数据中
	$sql = "insert into product(vendor_id, unit_price, vendor_note, approver_id, approve_date, " . 
		"publisher_id, publish_date, ctime, mtime, name, manufacturer, catalog_no, model, spec, " . 
		"package, type, category_id, keywords, description, stock_status, id, expire_date, " . 
		"freeze_reasons, sale_volume, brand, supply_time, market_price, _extra, last_approver_id, " . 
		"last_approve_date, last_publisher_id, last_publish_date, unapprover_id, unapprove_date, " . 
		"version, dirty, orig_price, sale_info, fixed_price) select vendor_id, unit_price, " . 
		"vendor_note, approver_id, approve_date, publisher_id, publish_date, ctime, mtime, " . 
		"name, manufacturer, catalog_no, model, spec, package, type, category_id, keywords, " . 
		"description, stock_status, id, expire_date, freeze_reasons, sale_volume, brand, " . 
		"supply_time, market_price, _extra, last_approver_id, last_approve_date, last_publisher_id, " . 
		"last_publish_date, unapprover_id, unapprove_date, version, dirty, orig_price ,sale_info, " . 
		"fixed_price from product_tust where id not in ($delete_ids);";
	if (!$db->query($sql)) {
		echo "存在重复的ID.\n";
	} else {
		echo "商品数据合并完毕.\n";
	}

	//待删除的ID
	//print_r($delete_ids);

	// $delete_sql = "delete from product where id in ($delete_ids);";
	// if ($db->query($delete_sql)) {
	// 	echo "商品数据合并完毕.\n";
	// } else {
	// 	echo "商品数据合并失败.\n";
	// }
	//存在同时有订单的情况 update order order_item cart_item product_revision

	echo "开始更新product_revision\n";
	$sql = "insert into product_revision(vendor_id, unit_price, orig_price, sale_info, " . 
		"vendor_note, approver_id, approve_date, last_approver_id, last_approve_date, " . 
		"publisher_id, publish_date, last_publisher_id, last_publish_date, unapprover_id, " . 
		"unapprove_date, ctime, mtime, name, manufacturer, catalog_no, model, spec, " . 
		"package, type, category_id, keywords, description, stock_status, expire_date, " . 
		"freeze_reasons, sale_volume, brand, supply_time, market_price, version, " . 
		"product_id, _extra) select vendor_id, unit_price, orig_price, sale_info, " . 
		"vendor_note, approver_id, approve_date, last_approver_id, last_approve_date, " . 
		"publisher_id, publish_date, last_publisher_id, last_publish_date, unapprover_id, " . 
		"unapprove_date, ctime, mtime, name, manufacturer, catalog_no, model, spec, " . 
		"package, type, category_id, keywords, description, stock_status, expire_date, " . 
		"freeze_reasons, sale_volume, brand, supply_time, market_price, version, " . 
		"product_id, _extra from product_revision_tust;";
	if ($db->query($sql)) {
		echo "product_revision更新成功\n";
	} else {
		echo "product_revision更新失败\n";
	}

	echo "done.\n";
}