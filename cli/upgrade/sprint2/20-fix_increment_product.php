#!/usr/bin/env php
<?php
/*
 * 处理增量的product
 */

$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;

if (SITE_ID != 'nankai') return FALSE;

$fix_time = strtotime('2014-05-9 17:47:00');

$db = Database::factory();

$total_sql = "SELECT count(*) total FROM `product` WHERE `mtime`>$fix_time";

$total = $db->query($total_sql)->row()->total;

$start = 0;
$per_page = 100;
while($start <= $total) {
	$query_sql = "SELECT * from `product` WHERE `mtime`>$fix_time limit $start,$per_page";
	$products = $db->query($query_sql)->rows();
	foreach ($products as $product) {

		$id = $product->id;

	    //替换或新建product
	    $replace_sql = "REPLACE INTO `product_foobar` (vendor_id,unit_price,vendor_note,approver_id,approve_date,publisher_id,publish_date,newer_id,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,id,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra) (SELECT vendor_id,unit_price,vendor_note,approver_id,approve_date,publisher_id,publish_date,newer_id,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,in_stock,id,expire_date,freeze_reasons,sale_volume,brand,lead_time,market_price,_extra FROM `product` WHERE id={$id})";
	    
	    if(!$db->query($replace_sql)) {
	    	echo $replace_sql;
	    	echo "\n";
	    	echo $update_sql;
	    	echo "\n";
	    	die('更新数据失败');
	    }

		$extra = json_decode($product->_extra,true);
		$last_approver_id = $extra['last_approver'] ?: 0;
		$last_approve_date = $extra['last_approve_date'] ?: '\'\'';

		$last_publisher_id = $extra['last_publisher'] ?: 0;
		//原来的字段有问题，last_publishe_date
		$last_publish_date = $extra['last_publishe_date'] ?: '\'\'';

		$unapprover_id = $extra['unapprover'] ?: 0;
		$unapprove_date = $extra['unapprove_date'] ?: '\'\'';
		
		//重新设置extra
		unset($extra['last_approver']);
		unset($extra['last_approve_date']);
		unset($extra['last_publisher']);
		unset($extra['last_publishe_date']);
		unset($extra['unapprover']);
		unset($extra['unapprove_date']);
		$extra = json_encode($extra);

		$update_sql = "UPDATE `product_foobar` SET `last_approver_id`={$last_approver_id},`last_approve_date`={$last_approve_date},`last_publisher_id`={$last_publisher_id},`last_publish_date`={$last_publish_date},`unapprover_id`={$unapprover_id},`unapprove_date`={$unapprove_date},`_extra`='%s' WHERE `id`={$id}";

		if($db->query($update_sql, $extra)) {
			echo '.';
		}

		unset($product);
	}

	$start += $per_page;	
}

if($db->query("DROP TABLE `product`")) {
	$ret = $db->query("RENAME TABLE `product_foobar` TO `product`");
	if(!$ret) die('重命名表product_foobar失败');
}
else{
	die('删除product表失败');
}
echo "done\n";

