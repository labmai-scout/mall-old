<?php
/*
	定时运行, 下架过期 vendor
	(xiaopei.li@2012-05-15)
*/

require 'base.php';
$now = Date::time();
$db = Database::factory();

// 1. 下架过期 vendor
$vendors_to_expire = Q("vendor[approve_date>0][expire_date=1~$now]");
foreach ($vendors_to_expire as $v2e) {
	//暂停供货商
	$v2e->unpublish(HT('资质过期'));
}


$db = Database::factory();
$sphinx = Database::factory('@sphinx');
// $vendors = Q("vendor[approve_date=0]");
$vendors = $db->query("SELECT * FROM `vendor` WHERE `approve_date`=0")->rows();
foreach ($vendors as $vendor) {
	$unapprove_date = Date::time();
 	// 将商家的商品打为待审核
	$sql = "UPDATE `product` SET `last_approver_id`=`product`.`approver_id`, `last_approve_date`=`product`.`approve_date`,
    `unapprove_date`={$unapprove_date},`approve_date`=0, `approver_id`=0 WHERE `vendor_id`={$vendor->id} and `approve_date`>0";

	$ret = $db->query($sql);

	if($ret) {
		//更新product表的索引
		$product_table = Search_Iterator::get_index_name('product');
		$sphinx_sql = "UPDATE `$product_table` SET `approve_date`=0 WHERE `vendor_id`={$vendor->id} and `approve_date`>0";
		$sphinx->query($sphinx_sql);

		//从配置中拿到各个类别的名称
		$types_sphinx_indexes = Config::get('product.types_sphinx_indexes');
		$product_types = array_keys($types_sphinx_indexes);

		//更新各个类别表的索引
		foreach ($product_types as $sp) {
			//更新sphinx，只是将对应商品的approve_date变为0，所以直接update
			$pt = Search_Iterator::get_index_name('product_'.$sp);
			$sphinx_sql = "UPDATE `$pt` SET `approve_date`=0 WHERE `vendor_id`={$vendor->id}  and `approve_date`>0";
			$sphinx->query($sphinx_sql);
		}

	}

	$vid = intval($vendor->id);
	$db = ORM_Model::db('vendor');
	$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");
	echo "done\n";
}
clean_cache();