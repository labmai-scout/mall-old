#!/usr/bin/env php
<?php
    /*
     * file 10-move_properties_to_extra.phhp
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-01-16
     *
     * useage SITE_ID=nankai php 10-move_properties_to_extra.php
     * brief 删除_p前缀的虚属性表，将数据移动到对应的object的extra列中
     */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    //检测是否需要进行升级
    $db = Database::factory();

    //\_p\_%用于转义_ 防止匹配update等
    if ($db->value("SHOW TABLES LIKE '\_p\_%'")) return TRUE; //存在_p开头的表，进行升级

    return FALSE;
};

//数据库备份
$u->backup = function() {
    $dbfile = SITE_PATH. 'private/backup/before_move_properties.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
    $db = Database::factory();
    return $db->snapshot($dbfile);
};

$u->upgrade = function() {
    $db = Database::factory();

    //获取_p_开头的表
    $tables_query = $db->query("SHOW TABLES LIKE '\_p\_%'");

    //无法写入数据，结构: $errors[$object][$id] = $data;
    $errors = array();

    //跳过
    $skips = array();

    //遍历
    while(($table = current($tables_query->row('num'))) != NULL) {

        $object = str_replace('_p_', '', $table);

        $schema = ORM_Model::schema($object);

        if (!$schema) {
            $skips[] = $table;
            continue;     //获取不到schema
        }

        $db->query('ALTER TABLE `%s` ADD `_extra` text', $object);

        echo Upgrader::ANSI_HIGHLIGHT. "updating $object". Upgrader::ANSI_RESET. "\n";

        $start_id = 0;
        $perpage = 10;

        //使用 id 进行，效率相对LIMIT OFFSET 比较高
        while($_data = $db->query('SELECT `id`, `data` FROM `%s` WHERE `id` > %d ORDER BY `id` LIMIT %d', $table, $start_id, $perpage)->rows('assoc')) {

            foreach($_data as $d) {

                $id = $d['id'];

                $data = (array) (@unserialize($d['data']) ?: @unserialize(base64_decode($d['data'])));

                //虚属性的错误数据矫正
                if (isset($data[0]) && $data[0] === FALSE) unset($data[0]);

                if (!count($data)) continue;

                //进行写入
                if (!$db->query("UPDATE `%s` SET `_extra` = '%s' WHERE `id` = %d", $object, @json_encode($data), $id)) {
                    $errors[$object][$id] = $data;
                }
                else {
                    echo '.';
                }
            }

            $start_id = $id;
            $start += $perpage;
        }


        //清除数据
        if (!count($errors[$object])) {
            echo Upgrader::ANSI_GREEN;
            echo "\n$table 数据升级成功! \n";
            echo Upgrader::ANSI_RESET;

            //删除_p_object表
            $db->drop_table($table);
        }
        else {
            echo Upgrader::ANSI_RED;
            echo "\n$table 数据升级失败! \n";
            echo Upgrader::ANSI_RESET;
        }

        echo "\n";
    }

    if (count($errors)) {
        foreach($errors as $object=> $id_data) {
            echo Upgrader::ANSI_RED;
            echo "$object 数据升级错误! \n";
            echo Upgrader::ANSI_RESET;
        }

        return false;
    }

    return true;
};

//恢复数据
$u->restore = function() {
    $dbfile = SITE_PATH. 'private/backup/before_move_properties.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
    $db = Database::factory();
    $db->restore($dbfile);
};

$u->run();
