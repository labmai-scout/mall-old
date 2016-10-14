#!/usr/bin/env php
<?php
    /*
     * file 10-fix_product_reagent.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013
     *
     * useage SITE_ID=nankai php 10-fix_product_reagent.php
     * brief 用于更新type为reagent的product和vendor_product的部分附加实属性到虚属性
     */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$modify_fields = array(
    'cas_no',
    'rgt_type',
    'rgt_en_name',
    'rgt_aliases',
    'rgt_danger_class'
);

$u = new Upgrader;

$u->check = function() use ($modify_fields) {

    $db = Database::factory();

    $copy_modify_fields = $modify_fields;
    foreach($copy_modify_fields as &$field) {
        $field = "`Field` = '$field'";
    }

    $suffix_query = join(' OR ', $copy_modify_fields);
    //如果product或者vendor_product中包含如上field，进行升级
    return $db->value("SHOW COLUMNS FROM `product` WHERE $suffix_query") || $db->value("SHOW COLUMNS FROM `vendor_product` WHERE $suffix_query");
};

//数据库备份
$u->backup = function() {
    $dbfile = SITE_PATH . 'private/backup/before_fix_product_reagent.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
    $db = Database::factory();
    return $db->snapshot($dbfile);
};

//升级
$u->upgrade = function() use ($modify_fields) {

    $db = Database::factory();

    echo "product更新开始!\n";
    $products = Q('product[type=reagent]');

    $num = 20;
    $start = 0;
    $total = $products->total_count();

    while($start < $total) {

        foreach ($products->limit($start, $num) as $product) {

            $p = P($product);
            foreach($modify_fields as $item) {
                $p->set($item, $product->$item);
            }
            $p->save();
            echo '.';
            unset($p);
        }

        $start += $num;
    }

    foreach($modify_fields as $field) {
        $db->query("ALTER TABLE `product` DROP COLUMN `$field`");
    }
    echo "product更新完成!\n";

    echo "vendor_product更新开始!\n";

    $vendor_products = Q('vendor_product[type=reagent]');

    $num = 20;
    $start = 0;
    $total = $vendor_products->total_count();

    while($start < $total) {

        foreach ($vendor_products->limit($start, $num) as $vendor_product) {
            $v = P($vendor_product);
            foreach($modify_fields as $item) {
                $v->set($item, $vendor_product->$item);
            }
            $v->save();
            echo '.';
            unset($v);
        }

        $start += $num;
    }

    foreach($modify_fields as $field) {
        $db->query("ALTER TABLE `vendor_product` DROP COLUMN `$field`");
    }

    echo "vendor_product更新完成!\n";
};

//验证
$u->verify = function() use ($modify_fields) {

    $db = Database::factory();

    $copy_modify_fields = $modify_fields;
    foreach($copy_modify_fields as &$field) {
        $field = "`Field` = '$field'";
    }

    $suffix_query = join(' OR ', $copy_modify_fields);
    //如果product或者vendor_product中不包含如上field，则升级成功
    return ! ($db->value("SHOW COLUMNS FROM `product` WHERE $suffix_query") || $db->value("SHOW COLUMNS FROM `vendor_product` WHERE $suffix_query"));
};

//恢复数据
$u->restore = function() {
    $dbfile = SITE_PATH . 'private/backup/before_fix_product_reagent.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
    $db = Database::factory();
    $db->restore($dbfile);
};

$u->run();
