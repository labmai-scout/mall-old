#!/usr/bin/env php
<?php
/*
* 将extra中的last_unapprove_date等字段移出来便于搜索和update 
*/

$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;
$u = new Upgrader;

$u->check = function() {
	if (SITE_ID != 'nankai') return FALSE;
	return TRUE;
};

//数据库备份
$u->backup = function() {
    $dbfile = SITE_PATH . 'private/backup/before_fix_products.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
    $db = Database::factory();
    return $db->snapshot($dbfile, ['product']);
};

$u->upgrade = function() {
	$db = Database::factory();
    /*
	// 修正商品的字段 in_stock -> stock_status, lead_time -> supply_time
	$return = $db->query("ALTER TABLE product change in_stock stock_status tinyint(4) not null default 0");
	if ($return) {
		echo '更改 product 的 in_stock 字段为 stock_status 成功';
	}
	else {
		echo '更改 product 的 in_stock 字段为 stock_status 失败';
		die;
	}

	echo "\n";

	$return = $db->query("ALTER TABLE product change lead_time supply_time bigint(20) not null");
	if ($return) {
		echo '更改 product 的 lead_time 字段为 supply_time 成功';
	}
	else {
		echo '更改 product 的 lead_time 字段为 supply_time 失败';
		die;
	}
	echo "\n";
    
	//建立对应的表
	$column_sql = "ALTER TABLE `product` ADD COLUMN `last_approver_id` bigint(20) NOT NULL DEFAULT '0', ADD COLUMN `last_approve_date` int(11) NOT NULL DEFAULT '0', ADD COLUMN `last_publisher_id` bigint(20) NOT NULL DEFAULT '0', ADD COLUMN `last_publish_date` int(11) NOT NULL DEFAULT '0', ADD COLUMN `unapprover_id`  bigint(20) NOT NULL DEFAULT '0', ADD COLUMN `unapprove_date`  int(11) NOT NULL DEFAULT '0'";

	if($db->query($column_sql)) {
		echo '插入字段成功';
		echo "\n";
	}
	else{
		echo '插入字段失败';
		echo "\n";
		die;
	}
    
	//刷索引准备
	$search_class = 'Search_Product';
	$search_class::empty_index();
	$configs = Config::get('product.types');
	foreach ($configs as $sub_index => $foo) {
		$child_class = $search_class.'_'.$sub_index;
		$child_class::empty_index();
	}
*/
	$total_sql = "SELECT count(*) total FROM `product`";

	$total = $db->query($total_sql)->row()->total;

	$start = 0;
	$per_page = 100;
	while($start < $total) {
		$query_sql = "SELECT * from `product` limit $start,$per_page";
		$products = $db->query($query_sql)->rows();
		foreach ($products as $product) {
			$id = $product->id;
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

			$update_sql = "UPDATE `product` SET `last_approver_id`={$last_approver_id},`last_approve_date`={$last_approve_date},`last_publisher_id`={$last_publisher_id},`last_publish_date`={$last_publish_date},`unapprover_id`={$unapprover_id},`unapprove_date`={$unapprove_date},`_extra`='%s' WHERE `id`={$id}";

			if($db->query($update_sql, $extra)){
				//刷product索引
				$obj = O('product');
				$obj->id = $product->id;
				$obj->name = $product->name;
				$obj->manufacturer = trim($product->manufacturer);
				$obj->catalog_no = $product->catalog_no;
				$obj->type = $product->type;
				$obj->description = $product->description;
				$obj->keywords = $product->keywords;
				$obj->freeze_reasons = $product->freeze_reasons;
				$obj->unit_price = $product->unit_price;
				$obj->vendor = O('vendor', $product->vendor_id);
				$obj->newer = O('product', $product->newer_id);
				$obj->ctime  = $product->ctime;
				$obj->publish_date = $product->publish_date;
				$obj->approve_date = $product->approve_date;
				$obj->spec = $product->spec;
				$obj->package = $product->package;
				$obj->brand = $product->brand;
                $obj->expire_date = $product->expire_date;
				$obj->supply_time = $product->supply_time;
				$obj->category = O('product_category', $product->category_id);
				$obj->stock_status = $product->stock_status;

				$extra = json_decode($product->_extra, TRUE);
				if ($product->type == 'reagent') {
					$obj->cas_no = $extra['cas_no'];
					$obj->rgt_type = $extra['rgt_type'];
					$obj->reagent_mw = $extra['reagent_mw'];
					$obj->rgt_en_name = $extra['rgt_en_name'];
					$obj->rgt_aliases = $extra['rgt_aliases'];
					$obj->reagent_formula = $extra['reagent_formula'];
					$obj->rgt_danger_class = $extra['rgt_danger_class'];
				}
				elseif ($product->type == 'biologic_reagent') {
					$obj->storage_cond = $extra['storage_cond'];
					$obj->transport_cond = $extra['transport_cond'];
				}
				elseif ($product->type == 'consumable') {
					$obj->consumable_service_no = $extra['consumable_service_no'];
				}
				elseif ($product->type == 'computer') {
					$obj->cpu = $extra['cpu'];
					$obj->disk = $extra['disk'];
					$obj->memory = $extra['memory'];
					$obj->display = $extra['display'];
					$obj->service_call = $extra['service_call'];
					$obj->video_memory = $extra['video_memory'];
					$obj->computer_type = $extra['computer_type'];
				}

				Search_Product::update_index($obj);
				ORM_Pool::release($obj);
				echo '.';
			}
			else {
				echo $update_sql;
				echo "\n\n";
				die;
			}

			unset($product);
		}

		$start += $per_page;	
	}

	echo "done\n";
};


//恢复数据
$u->restore = function() {
	$dbfile = SITE_PATH . 'private/backup/before_fix_products.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
	$db = Database::factory();
	$db->restore($dbfile);
};

$u->run();
 
