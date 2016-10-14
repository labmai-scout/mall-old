#!/usr/bin/env php
<?php
    /*
     * file check_extra_data.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date  2014-01-16
     *
     * useage SITE_ID=nankai php check_extra_data.php
     * brief 用于检测现有 _p_ 的_extra属性转移后，是否可正常获取和调用
     */

require 'base.php';

//文件存在，不进行继续运行
$done_file = dirname(__FILE__). '/check_extra_data.done';

if (File::exists($done_file)) return FALSE;

$db = Database::factory();

ob_start();

$base_url = Config::get('system.base_url');

echo "base_url: $base_url\n";

echo "开始进行extra转移检测\n";

//获取_p_开头的表
$tables_query = $db->query("SHOW TABLES LIKE '\_p\_%'");

//无法写入数据，结构: $errors[$object][$id] = $data;
$errors = array();

//遍历
while(($table = current($tables_query->row('num'))) != NULL) {
    echo $table;

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

    $object = ltrim($table, '_p_');

    foreach($rand_id as $step => $ids) {
        $in_condition = join(',', $ids);
        $_data = (array) $db->query('SELECT `id`, `data` FROM `%s` WHERE `id` IN (%s)', $table, $in_condition)->rows('assoc');

        foreach($_data as $d) {

            $id = $d['id'];

            $data = (array) (@unserialize($d['data']) ?: @unserialize(base64_decode($d['data'])));

            //虚属性的错误数据过滤
            if (isset($data[0]) && $data[0] === FALSE) unset($data[0]);

            if (!count($data)) continue;

            try {
                //对二进制数进行过滤, 不符合可调用的进行跳过
                $tmp_data = @json_encode($data);

                if (json_last_error()) throw new Error_Exception;

                @json_decode($tmp_data);

                if (json_last_error()) throw new Error_Exception;

                $model = O($object, $id);
                if (!$model->id) continue;

                $schema = ORM_Model::schema($object);

                //之前已经写入, 直接进行获取比对即可
                foreach($data as $property => $value) {

                    //属于实属性，跳过
                    if (array_key_exists($property, $schema['fields'])) break;

                    if ($model->$property == $value && !($model->$property instanceof ORM_Model)) {
                        echo '.';
                    }
                    else {
                        $errors[$object][$property] = gettype($value);
                    }
                }
            }
            catch(Error_Exception $e) {
                //无法进行json_encode json_decode, continue跳过不予检测
                continue;
            }

        }
    }
}

if (count($errors)) {

    echo "\n有无法转移数据! \n";

    foreach($errors as $object => $properties) {

        echo "\t $object 错误数据如下:";

        foreach(array_unique($properties) as $p => $type) {
            echo "\n\t\t $p : $type";
        }
        echo "\n";
    }
}
else {
    echo "数据均可正常转移! \n";
}

$output = ob_get_contents();

ob_end_clean();

file_put_contents($done_file, $output);

$email = new Email;

$receiver = array('rui.ma@geneegroup.com');

$email->to($receiver);

$subject = Config::get('page.title_default') . '系统extra附加属性转移测试结果';

$email->subject($subject);
$email->body($output);
$email->send();
