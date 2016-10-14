<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);

/*
 * file clean_vendor_sale_2.php
 * author Yu Li <yu.li@geneegroup.com>
 * date 2014-11-03
 *
 * brief 根据vendor_id 删除促销信息,但是以折扣价作为现在商城价格
 * usage SITE_ID=nankai php clean_vendor_sale_2.php 125
 */
if (!is_numeric($argv[1])) {
    echo("vendor_id 只能为数字\n");
    die;
}
else {
    $vendor = O('vendor', $argv[1]);
    if (!$vendor->id) {
        echo("vendor_id 填写有误！\n");
        die;
    }
}

$db = Database::factory();

$vid = $vendor->id;

$insert_history = "INSERT INTO `product_revision` (vendor_id,unit_price,orig_price,sale_info,vendor_note,approver_id,approve_date,publisher_id,publish_date,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra,last_approver_id,last_approve_date,last_publisher_id,last_publish_date,unapprover_id,unapprove_date, product_id,version) (SELECT vendor_id,unit_price,orig_price,sale_info,vendor_note,approver_id,approve_date,publisher_id,publish_date,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra,last_approver_id,last_approve_date,last_publisher_id,last_publish_date,unapprover_id,unapprove_date,id,version FROM product WHERE vendor_id = $vid AND dirty=1 AND `orig_price` > 0)";

if(!$db->query($insert_history)) {
    die('升级product revision 失败');
}
$db->query("UPDATE `product` SET `version`=`version`+1, `dirty`=0 WHERE `vendor_id`={$vid} AND dirty=1 and `sale_info` != ''");

//清空促销信息
$db->query("UPDATE `product` SET `sale_info`='', `orig_price`=0 WHERE `sale_info`!='' AND `vendor_id`=$vid");



$sphinx = Database::factory('@sphinx');

$product_table = Search_Iterator::get_index_name('product');
$sphinx_sql = "UPDATE `$product_table` SET `is_sale`=0 WHERE `vendor_id`={$vid} and `is_sale`>0";
$sphinx->query($sphinx_sql);

//从配置中拿到各个类别的名称
$types_sphinx_indexes = Config::get('product.types_sphinx_indexes');
$product_types = array_keys($types_sphinx_indexes);

//更新各个类别表的索引
foreach ($product_types as $sp) {
    //更新sphinx，只是将对应商品的approve_date变为0，所以直接update
    $pt = Search_Iterator::get_index_name('product_'.$sp);
    $sphinx_sql = "UPDATE `$pt` SET `is_sale`=0 WHERE `vendor_id`={$vid} and `is_sale`>0";
    $sphinx->query($sphinx_sql);
}
