#!/usr/bin/env php
<?php
  /*
	由于 $vendor->publish()/unpublish() 会联动 publish()/unpublish() 其下商品, 速度较慢
	所以有此脚本辅助后台运行 (xiaopei.li@2012-07-05)

	修改为专门下架供货商的商品，
	不再是publish()/unpublihs() 后再hook 判断供货商状态后下架。（yu.li@2013-05-07）
   */

require('base.php');
die;

// read opts
$shortopts = "v:u::";
$longopts = array(
	'vendor:',
	'user::'
	);

$opts = getopt($shortopts, $longopts);

if (!$opts) {
	echo "usage: \n";
	echo "1. publish_vendor.php -v 1\n";
	exit(1);
}

$vid = $opts['v'];
if (!$vid) {
	$vid = $opts['vendor'];
}

$vendor = O('vendor', $vid);

if (!$vendor->id) {
	echo("vendor 不存在\n");
	exit(1);
}

//执行此下架脚本的用户
if($opts['u'] || $opts['user']) {
	$user_id = $opts['u'] ?: $opts['user'];
}

if (!$vendor->is_unpublishing) {
    echo "unapproving {$vendor->name}: ";
    try {
        $vendor->is_unpublishing = TRUE;
        $vendor->save();

        // 架上的商品要下架
        // $approved_products = Q("{$vendor} vendor_product[publish_date>0][approve_date>0]:limit({$start},{$num})");
        $db = Database::factory();
        $sphinx = Database::factory('@sphinx');

        //下架的用户
        $unapprover_id = $user_id ?: 0;
        $unapprove_date = Date::time();

        // 将商家的商品打为待审核
        $sql = "UPDATE `product` SET `last_approver_id`=`product`.`approver_id`, `last_approve_date`=`product`.`approve_date`, `unapprover_id`={$unapprover_id}, `unapprove_date`={$unapprove_date},`approve_date`=0, `approver_id`=0 WHERE `vendor_id`={$vendor->id} and `publish_date`>0 and `approve_date`>0";

        $ret = $db->query($sql);

        if($ret) {
            //更新product表的索引
            $product_table = Search_Iterator::get_index_name('product');
            $sphinx_sql = "UPDATE `$product_table` SET `approve_date`=0 WHERE `vendor_id`={$vendor->id} and `publish_date`>0 and `approve_date`>0";
            $sphinx->query($sphinx_sql);

            //从配置中拿到各个类别的名称
            $types_sphinx_indexes = Config::get('product.types_sphinx_indexes');
            $product_types = array_keys($types_sphinx_indexes);

            //更新各个类别表的索引
            foreach ($product_types as $sp) {
                //更新sphinx，只是将对应商品的approve_date变为0，所以直接update
                $pt = Search_Iterator::get_index_name('product_'.$sp);
                $sphinx_sql = "UPDATE `$pt` SET `approve_date`=0 WHERE `vendor_id`={$vendor->id} and `publish_date`>0 and `approve_date`>0";
                $sphinx->query($sphinx_sql);
            }

        }

        $vendor->is_unpublishing = FALSE;
        $vid = intval($vendor->id);
        $db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");
        $vendor->save();
        clean_cache();
    }
    catch (\Exception $e) {
        $mail = new Email;
        $mail->to(['jinlin.li@geneegroup.com', 'hongjie.zhu@geneegroup.com']);
        $mail->subject('upapprove_products 出错');
        $mail->body('vendor#'.$vendor->id.': '.$e->getMessage());
        $mail->send();
    }
	echo "done\n";
}

