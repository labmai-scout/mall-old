#!/usr/bin/env php
<?php
    /*
     * file check_bin_extra_data.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-01-16
     *
     * useage SITE_ID=nankai php check_extra_data.php
     * brief 用于检测现有 _p_ 的extra属性是否有无法json_encode() json_decode() 的数据
     */

require 'base.php';

$db = Database::factory();

ob_start();

echo '系统ORM对象虚属性转移测试结果:';

//获取_p_开头的表
$tables_query = $db->query("SHOW TABLES LIKE '\_p\_%'");

//无法写入数据，结构: $errors[$object][$id] = $data;
$errors = array();

//遍历
while(($table = current($tables_query->row('num'))) != NULL) {

    $count = $db->query('SELECT `id` FROM `%s`', $table)->count();
    $max_id = current($db->query('SELECT MAX(`id`) FROM `%s`', $table)->row('assoc'));


    //获80%数据进行检索
    $ratio = 0.8;

    $check_count = $count * $ratio;

    $rand_id = array();
    //随机获取数据
    $i = 0;
    while($i <= $check_count) {
        //拆分为50个一数组
        $group = $i / 50;
        $rand_id[$group][] = mt_rand(0, $max_id);
        ++ $i;
    }

    foreach($rand_id as $step => $ids) {
        $in_condition = join(',', $ids);
        $_data = (array) $db->query('SELECT `id`, `data` FROM `%s` WHERE `id` IN (%s)', $table, $in_condition)->rows('assoc');
        foreach($_data as $d) {
            $id = $d['id'];

            $data = (array) (@unserialize($d['data']) ?: @unserialize(base64_decode($d['data'])));

            //虚属性的错误数据过滤
            if (isset($data[0]) && $data[0] === FALSE) unset($data[0]);

            if (!count($data)) continue;

            //进行写入

            try {
                $tmp_data = @json_encode($data);

                if (json_last_error()) throw new Error_Exception;

                @json_decode($tmp_data);

                if (json_last_error()) throw new Error_Exception;

                //echo '.';
            }
            catch(Error_Exception $e) {
                $errors[$table][$id] = $data;
            }
        }
    }
}


if (count($errors)) {

    $perrors = array();

    foreach($errors as $object=> $id_data) {
        echo "\n有无法转移数据! \n";

        foreach($id_data as $id=> $data) {
            foreach($data as $property=> $d) {

                try {
                    $tmp_data = @json_encode(array($property=>$d));

                    if (json_last_error()) throw new Error_Exception;

                    @json_decode($tmp_data);

                    if (json_last_error()) throw new Error_Exception;
                }
                catch(Error_Exception $e) {
                    $perrors[$object][] = $property;
                }
            }
        }
    }

    foreach($perrors as $object => $properties) {
        echo "\n$object 数据错误:";
        foreach(array_unique($properties) as $p) {
            echo "\n\t $p";
        }
    }

}
else {
    echo "\n 数据均可正常转移!";
}

echo "\n";

$output = ob_get_contents();

ob_end_clean();

$email = new Email;

$receiver = array(
    'rui.ma@geneegroup.com'
);

$email->to($receiver);

$subject = Config::get('page.title_default') . '系统虚属性转移测试';

$email->subject($subject);
$email->body($output);
$email->send();
