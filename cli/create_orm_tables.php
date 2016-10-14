#!/usr/bin/env php
<?php
    /*
     * file create_orm_tables.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date  2013/06/24
     *
     * useage SITE_ID=nankai php create_orm_tables.php
     * brief 对系统中的ORM对象的schema进行遍历，根据schema创建ORM对象在数据库中的表结构
     */

require 'base.php';
$table = $argv[1];
$db = Database::factory();
if ($table) {
    $schema = ORM_Model::schema($table);
    var_dump($schema);
    if ($db->prepare_table($table, $schema)) {
        echo $table."表更新成功\n";
    }
    else {
        echo $table."表更新失败\n";
    }
}
else {
    foreach(Config::$items['schema'] as $name=>$schema) {
        $schema = ORM_Model::schema($name);
        if ($schema) {
            $ret = $db->prepare_table($name, $schema);
            if (!$ret) {
                echo $name."表更新失败\n";
            }
        }
    }
}
