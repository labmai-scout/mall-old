
<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
$db = Database::factory();
$tables = ['_auth',
'_config',
'_r_order_billing_bucket',
'_r_order_billing_statement',
'_r_product_category_product',
'_r_product_product_category',
'_r_tag_customer',
'_r_transfer_bucket_order',
'_r_transfer_statement_order',
'_r_user_customer',
'_r_user_customer_bak',
'_r_user_order',
'_r_user_order_item',
'_r_user_role',
'_r_vendor_user',
'_remember_login',
'billing_bucket',
'billing_statement',
'brand',
'brand_alias',
'cart',
'cart_item',
'category',
'comment',
'customer',
'customer_grant',
'customer_group',
'customer_member_perm',
'deliver_address',
'deliver_record',
'distributor',
'except_order',
'gapper_app_product',
'gapper_fallback_user',
'manufacturer',
'message',
'news',
'nfs',
'oauth2_session',
'oauth_consumer_nonce',
'oauth_token',
'operation_time',
'order',
'order_activity',
'order_count',
'order_item',
'order_item_comment',
'order_item_comment_reply',
'order_item_rating',
'order_revision',
'product_category',
'product_price',
'product_temp',
'product_upload_record',
'rating',
'recovery',
'role',
'tag',
'transfer_bucket',
'transfer_statement',
'user',
'user_auth',
'vendor',
'vendor_api',
'vendor_scope'];
convert_vendor();
convert_time('ctime');
convert_time('mtime');
change_type();
change_product_category();
create_product_node();
foreach ($tables as $table) {
	if ($db->query('DROP table `%s`', $table)) {
		echo '.';
	}
	else {
		echo 'x';
	}
}
$start = 0;
$limit = 1000;
$db = Database::factory();
while (true) {
	echo $start."\n";
	$ps = $db->query("SELECT * FROM `product` LIMIT $start, $limit")->rows();
	if (!count($ps)) break;
	$start += $limit;
	foreach ($ps as $p) {
		$id = $p->id;
		$keywords = '';
		if ($p->keywords && $p->keywords != "{}") {
			$keywords = implode(' ', (array)json_decode($p->keywords, true));
		}

    	$seg_name = $p->name;
		$seg_manufacturer = seg_word($p->manufacturer);
		$seg_catalog_no = seg_word($p->catalog_no);
		$seg_model = seg_word($p->model);
		$seg_spec = seg_word($p->spec);
		$seg_package = seg_word($p->package);
		$seg_keywords = seg_word($keywords);
		$seg_description = seg_word($p->description);
		$seg_brand = seg_word($p->brand);
		$extra = json_decode($p->_extra, true);
		if ($extra['rgt_aliases'] && $extra['rgt_aliases'] != "{}") {
			$change_extra = true;
			$rgt_aliases = implode(', ', json_decode($extra['rgt_aliases'], true));
			$extra['rgt_aliases'] = $rgt_aliases;
			$extra = @json_encode($extra);
			$sql = "UPDATE `product` SET `keywords` = '%s', `seg_manufacturer` = '%s', `seg_catalog_no` = '%s', `seg_model` = '%s', `seg_spec` = '%s', `seg_package` = '%s', `seg_keywords` = '%s', `seg_description` = '%s', `seg_brand` = '%s', `_extra` = '%s' WHERE id=$id";
			if (!$db->query($sql, $keywords, $seg_manufacturer, $seg_catalog_no, $seg_model, $seg_spec, $seg_package, $seg_keywords, $seg_description, $seg_brand, $extra)) {
				Log::add($p->id.' p_ugd upgrade error', 'upgrade_record');
			}
		}
		else {
			$sql = "UPDATE `product` SET `keywords` = '%s', `seg_manufacturer` = '%s', `seg_catalog_no` = '%s', `seg_model` = '%s', `seg_spec` = '%s', `seg_package` = '%s', `seg_keywords` = '%s', `seg_description` = '%s', `seg_brand` = '%s' WHERE id=$id";
			if (!$db->query($sql, $keywords, $seg_manufacturer, $seg_catalog_no, $seg_model, $seg_spec, $seg_package, $seg_keywords, $seg_description, $seg_brand)) {
				Log::add($p->id.' p_ugd upgrade error', 'upgrade_record');
			}
		}
	}
}

$start = 0;
$limit = 1000;
$db = Database::factory();
while (true) {
	echo $start."\n";
	$prs = $db->query("SELECT * FROM `product_revision` LIMIT $start, $limit")->rows();
	if (!count($prs)) break;
	$start += $limit;
	foreach ($prs as $pr) {
		$id = $pr->id;
		$keywords = '';
		if ($pr->keywords && $pr->keywords != "{}") {
			$keywords = implode(' ', (array)json_decode($pr->keywords, true));
		}
		$arr = [];
		$arr[SITE_ID]['stock_status'] = $pr->stock_status;
		$arr[SITE_ID]['supply_time'] = $pr->supply_time;
		$nodes = '['.json_encode($arr).']';
		$sql = "UPDATE `product_revision` SET `keywords` = '%s', `nodes` ='%s' WHERE id=$id";
		if (!$db->query($sql, $keywords, $nodes)) {
			Log::add($p->id.' pr_ugd upgrade error', 'upgrade_record');
		}
	}
}

$columns = [
'DROP COLUMN approver_id',
'DROP COLUMN approve_date',
'DROP COLUMN publisher_id',
'DROP COLUMN publish_date',
'DROP COLUMN category_id',
'DROP COLUMN keywords',
'DROP COLUMN description',
'DROP COLUMN stock_status',
'DROP COLUMN expire_date',
'DROP COLUMN freeze_reasons',
'DROP COLUMN supply_time',
'DROP COLUMN market_price',
'DROP COLUMN last_approver_id',
'DROP COLUMN last_approve_date',
'DROP COLUMN last_publisher_id',
'DROP COLUMN last_publish_date',
'DROP COLUMN unapprover_id',
'DROP COLUMN unapprove_date',
'DROP COLUMN sale_info',
];
$sql = "ALTER TABLE `product_revision` ".implode(',', $columns);
if ($db->query($sql)) {
	echo 'alter table product_revision drop column successed!';
}
$columns = [
'DROP COLUMN approver_id',
'DROP COLUMN approve_date',
'DROP COLUMN publisher_id',
'DROP COLUMN publish_date',
'DROP COLUMN category_id',
'DROP COLUMN stock_status',
'DROP COLUMN expire_date',
'DROP COLUMN freeze_reasons',
'DROP COLUMN supply_time',
'DROP COLUMN market_price',
'DROP COLUMN last_approver_id',
'DROP COLUMN last_approve_date',
'DROP COLUMN last_publisher_id',
'DROP COLUMN last_publish_date',
'DROP COLUMN unapprover_id',
'DROP COLUMN unapprove_date',
'DROP COLUMN dirty',
];
$sql = "ALTER TABLE `product` ".implode(',', $columns);
if ($db->query($sql)) {
	echo 'alter table product drop column successed!';
}


function seg_word($word) {
	return implode(' ', rb_split_ex($word, __RB_SIMPLE_MODE__));
}
// need create_orm_update first
function create_product_node() {
	$db = Database::factory();
	$sql = 'INSERT INTO `product_node` (`status`, `product_id`, `stock_status`, `supply_time`)  SELECT  CASE WHEN approve_date > 0 and publish_date>0 THEN 2 WHEN approve_date=0 and publish_date>0 THEN 1 ELSE NULL END,`id`,`stock_status`,`supply_time`  FROM `product` WHERE publish_date > 0';
	$db->query($sql);
	if (SITE_ID == 'nankai') {
		$sql = "UPDATE `product_node` SET node='nankai'";
	}
	elseif (SITE_ID == 'tust') {
		$sql = "UPDATE `product_node` SET node='tust'";
	}
	$db->query($sql);
}

// need create_orm_update first
function change_product_category() {
	$db = Database::factory();
	$pcs = Q('product_category');
	foreach ($pcs as $pc) {
		if ($pc->id == 1) continue;
		$pcid = $pc->id;
		if ($pc->parent->id) {
			$parent = $pc->parent;
			if ($parent->parent->id) {
				$grandpa = $parent->parent;
				$str = $grandpa->id.','.$parent->id.','.$pc->id;
			}
			else {
				$str = $parent->id.','.$pc->id;
			}
		}
		else {
			$str = $pc->id;
		}
		$SQL = "UPDATE `product` SET categ='".$str."' WHERE category_id = $pcid";
		if ($db->query($SQL)) {
			echo '.';
		}
		else {
			echo 'x';
		}
	}
	$SQL = "ALTER TABLE `product` CHANGE `categ` `category` varchar(40)";
	if ($db->query($SQL)) {
		echo '.';
	}
	else {
		echo 'x';
	}
}
/*
reagent ==> chem_reagent
biologic_reagent ==> bio_reagent
*/
function change_type() {
	$db = Database::factory();
	echo '*';
	$ret1 = $db->query("UPDATE `product` set `type`='chem_reagent' WHERE `type`='reagent'");
	echo '*';
	$ret2 = $db->query("UPDATE `product` set `type`='bio_reagent' WHERE `type`='biologic_reagent'");
	echo '*';
	$ret3 = $db->query("UPDATE `product_revision` set `type`='chem_reagent' WHERE `type`='reagent'");
	echo '*';
	$ret4 = $db->query("UPDATE `product_revision` set `type`='bio_reagent' WHERE `type`='biologic_reagent'");
	echo '*';
	return $ret1 && $ret2;
}

function convert_time($column) {
	$db = Database::factory();
	$tmp_column = $column.'2';
	$SQL1 = "ALTER TABLE `product` ADD COLUMN `$tmp_column` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()";
	echo "step 0 \n";
	if ($db->query($SQL1)) {
		echo "step 1 \n";
		$SQL2 = "UPDATE `product` SET $tmp_column = FROM_UNIXTIME($column)";
		if ($db->query($SQL2)) {
			echo "step 2 \n";
			$SQL3 = "ALTER TABLE `product`  DROP COLUMN $column";
			if ($db->query($SQL3)) {
				echo "step 3 \n";
				$SQL4 = "ALTER TABLE `product` CHANGE $tmp_column $column TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()";
				$db->query($SQL4);
				echo "step 4 \n";
			}
		}
	}

	$SQL1 = "ALTER TABLE `product_revision` ADD COLUMN `$tmp_column` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()";
	echo "step 5 \n";
	if ($db->query($SQL1)) {
		echo "step 6 \n";
		$SQL2 = "UPDATE `product_revision` SET $tmp_column = FROM_UNIXTIME($column)";
		if ($db->query($SQL2)) {
			echo "step 7 \n";
			$SQL3 = "ALTER TABLE `product_revision`  DROP COLUMN $column";
			if ($db->query($SQL3)) {
				echo "step 8 \n";
				$SQL4 = "ALTER TABLE `product_revision` CHANGE $tmp_column $column TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()";
				$db->query($SQL4);
				echo "step 9 \n";
			}
		}
	}
	return false;
}


function convert_vendor()
{
	$vendors = Q("vendor");
	$db = Database::factory();
	$can_convert = true;
	clean_cache();
	foreach ($vendors as $vendor) {
		if (!$vendor->gapper_group) continue;
		$start_time = Date::time();
		secho($vendor->name.'('.$vendor->id.'):');
		$vid = $vendor->id;
		$m_vid = 100000 + $vid;
		if (O('vendor', $m_vid)->id) {
			fecho('the id '.$m_vid. 'has exist vendor');
			die;
		}
		$SQL = "UPDATE product SET vendor_id={$m_vid} WHERE vendor_id={$vid}";
		$SQL2 = "UPDATE product_revision SET vendor_id={$m_vid} WHERE vendor_id={$vid}";
		if ($db->query($SQL) && $db->query($SQL2)) {
			$spend = Date::time() - $start_time;
			secho('重置成功!, 耗时: '.$spend.'s');
		}
		else {
			$spend = Date::time() - $start_time;
			$can_convert = false;
			fecho('重置失败!, 耗时: '.$spend.'s');
		}
	}
	clean_cache();
	if ($can_convert) {
		$vendors = Q('vendor');
		foreach ($vendors as $vendor) {
			if (!$vendor->gapper_group) continue;
			$start_time = Date::time();
			secho($vendor->name.'('.$vendor->id.'):');
			$vid = $vendor->id;
			$vid = 100000 + $vid;
			$gapper_group = (int)$vendor->gapper_group;
			$SQL = "UPDATE product SET vendor_id={$gapper_group} WHERE vendor_id={$vid}";
			$SQL2 = "UPDATE product_revision SET vendor_id={$gapper_group} WHERE vendor_id={$vid}";
			if ($db->query($SQL) && $db->query($SQL2)) {
				$spend = Date::time() - $start_time;
				secho('更新成功!, 耗时: '.$spend.'s');
			}
			else {
				$spend = Date::time() - $start_time;
				fecho('更新失败!, 耗时: '.$spend.'s');
			}
		}
	}

	clean_cache();
}



function fecho($message) {
	echo "\033[31m".$message."\033[0m \n";
}
function secho($message) {
	echo "\033[32m".$message."\033[0m \n";
}
?>
