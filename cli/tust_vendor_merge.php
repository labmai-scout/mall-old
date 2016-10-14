<?php

//科大供应商数据与南大供应商数据合并（第一步） 
//完成后对南大科大都有订单的供应商执行tust_vendor_update.php 更新科大其他表的供应商ID
//SITE_ID=tust php tust_vendor_update.php -n 南大的供应商ID -t 科大的供应商ID

require "base.php";
clean_cache();
$db = Database::factory();

//获得冲突的ID
$sql = "select nankai.id as nankai_id, tust.id as tust_id from vendor_tust as tust join " . 
	"vendor as nankai on nankai.gapper_group = tust.gapper_group;";
$conflict_ids = $db->query($sql)->rows();

$nankai_ids = [];
$tust_ids = [];
$delete_ids = [];
$flag = false;
$update_ids = [];
if ($conflict_ids) {
	//获取有订单的供应商ID
	foreach ($conflict_ids as $value) {
		$nankai_ids[] = $value->nankai_id;
		$tust_ids[] = $value->tust_id;
	}
	$nankai_ids = implode(',', $nankai_ids);
	$tust_ids = implode(',', $tust_ids);
	$nankai_sql = "select id, vendor_id from `order` where vendor_id in ($nankai_ids);";
	$nankai_has_order_ids = $db->query($nankai_sql)->rows();
	$tust_sql = "select id, vendor_id from order_tust where vendor_id in ($tust_ids);";
	$tust_has_order_ids = $db->query($tust_sql)->rows();
	
	foreach ($nankai_has_order_ids as $value) {
		$nankai_has_order_ids[] = $value->vendor_id;
	}
	foreach ($tust_has_order_ids as $value) {
		$tust_has_order_ids[] = $value->vendor_id;
	}

	$file = fopen('tust_vendor_merge.txt', 'w');
	fwrite($file, "\n冲突的供应商ID:\n");
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
	$delete_ids = implode(',', $delete_ids);

	if ($flag) {
		echo '科大有订单,南大没有';
		die;
	}

	//将科大的数据插入的南大的数据中
	$sql = "insert into vendor(name, short_name, creator_id, create_date, owner_id, publisher_id, " . 
		"publish_date, approve_date, expire_date, allowed_categories, ctime, mtime, id, " .
		"approver_id, email, short_abbr, _extra, product_count, agreement_version, agreement_time, " . 
		"gapper_group) select name, short_name, creator_id, create_date, owner_id, publisher_id, " . 
		"publish_date, approve_date, expire_date, allowed_categories, ctime, mtime, id, " .
		"approver_id, email, short_abbr, _extra, product_count, agreement_version, agreement_time, " . 
		"gapper_group from vendor_tust where id not in ($delete_ids);";
	if (!$db->query($sql)) {
		echo "存在重复的ID\n";
	} else {
		echo "done.\n";
	}
}