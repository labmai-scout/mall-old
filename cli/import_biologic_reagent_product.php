#!/usr/bin/env php
<?php

/*
* usage SITE_ID=nankai php import_biologic_reagent_product.php 184 商品导入样品.csv
* author: jinlin.li@geneegroup.com
* date: 2014.04.09
* 格式
* 商品名称* 生产商* 品牌* 货号* 规格* 包装* 生物试剂分类代码* 型号 商品单价* 库存* 供货时间* 商品简介 运输条件 保存条件 关键字 商品备注
*/

require 'base.php';

if (!is_numeric($argv[1])) {
    echo('vendor_id 只能为数字');
}
else {
    $vendor = O('vendor', $argv[1]);
    if (!$vendor->id) {
        echo('vendor_id 填写有误！');
        return;
    }
}


//分类tag辅助数组
$category_array =  [];
$csv_file = $argv[2];

if (!is_file($csv_file)) {
    echo(T('CSV文件 %csv_file 不存在!', array('%csv_file'=>$csv_file)));
    return;
}

//分类tag辅助数组
$category_array =  array(
    '808'=>'生物试剂',
    '901'=>'试剂盒',
    '902'=>'酶类',
    '903'=>'抗原与抗体',
    '904'=>'核酸/蛋白电泳与分析试剂',
    '905'=>'细胞/菌株/载体',
    '906'=>'细胞/细菌培养试剂',
    '907'=>'色谱类试剂',
    '908'=>'氨基酸、多肽与蛋白质',
    '909'=>'其他实验试剂',
    '910'=>'抑制剂',
);

$csv_line_format = array(
                HT('商品名称*'),
                HT('生产商*'),
                HT('品牌*'),
                HT('货号*'),
                HT('规格*'),
                HT('包装*'),
                HT('生物试剂分类代码*'),
                HT('型号'),
                HT('商品单价*'),
                HT('库存*'),
                HT('供货时间*'),
                HT('商品简介'),
                HT('运输条件'),
                HT('保存条件'),
                HT('关键字'),
                HT('商品备注'),
                HT('操作类别*'),
            );
$csv_import = new CSV($csv_file, 'r');
$head_columns = $csv_import->read();
foreach ($head_columns as $key => $value) {
    $head_columns[$key] = trim($value);
}

$ret = TRUE;
$rule = [];
foreach ($csv_line_format as $key => $column) {
    if (strlen($hkey = array_search($column, $head_columns))) {
        $rule[$key] = $hkey;
    }
    else {
        $ret = FALSE;
        echo "csv文件 $csv_file $column 列不存在, 请检查上传文件!\n";
    }
}

if (!mb_check_encoding(file_get_contents($csv_file), 'UTF-8')) {
    echo(T('%file_name 文件编码格式错误, 应该为 UTF-8', array('%file_name'=>$csv_file)));
}

$record = O('product_upload_record',['path'=>$csv_file]);
if (!$ret) {
    if ($record->id) {
        $record->status = Product_Upload_Record_Model::RECORD_STATUS_FAILED;
        $record->save();
    }
    return;
}

$start_time = Date::time();
$base_path = '/data/product_upload/converted';
//准备csv，写入错误文件
$error_file = $base_path.'/'.$vendor->id.'-'.date("YmdHis").'-error.csv';
file::check_path($error_file);
$csv = new CSV($error_file, 'w');
$error_file_format = $csv_line_format + array(HT('错误信息'));
$csv->write($error_file_format);

//准备修正 csv 文件， 得到我们期望的 csv 文件来执行 load data infile
$new_file = $base_path.'/'.$vendor->id.'-'.date("YmdHis").'.csv';
file::check_path($new_file);
$new_csv = new CSV($new_file, 'w');

$vendor->is_processing_import_products = TRUE;
$vendor->save();

$delete_pids = [];
$can_not_delete_ids = [];
$fail_no = 0;
$update_success_no    = 0;
$update_fail_no       = 0;
$add_no               = 0;
$unpublish_success_no = 0;
$unpublish_fail_no    = 0;
$can_not_delete_no    = 0;
$delete_success_no    = 0;
$delete_fail_no       = 0;

$now = Date::time();
$vendor_id = $vendor->id;

while($row = $csv_import->read()) {
    $row = convert_row($row, $rule);
    // 检测每行是否有字符乱码的情况
    $right_encoding = TRUE;
    $null_line = TRUE;
    $wrong_columns = array();
    foreach ($row as $key => $value) {
        if ($null_line && trim($row[$key])) {
            $null_line = FALSE;
        }
        if (!mb_detect_encoding($value, mb_detect_order(), TRUE)) {
            $right_encoding = FALSE;
            $wrong_columns[] = $head_columns[$key];
        }
    }
    if ($null_line) {
        //空行直接不处理
        continue;
    }
    $line_no ++;
    if (!$right_encoding) {
        $fail_no++;
        $error_msg = T('第%line_no行, %vproduct_name 导入失败，编码错误， 错误的列包括: %error_msg !', array('%line_no'=>$line_no, '%vproduct_name'=>$row[0], '%error_msg'=>implode(',', $wrong_columns)));
        $row[17] = $error_msg;
        echo($error_msg);
        $csv->write( $row );
        continue;
    }

    $db = database::factory();

    //去除cell左右空格
    foreach ($row as $key => $cell) {
        $row[$key] = $db->escape(trim_cell($row[$key]));
    }
    if ($row[14]) {
        //提前处理keywords
        $row[14] = json_encode((object) array_key_same_as_value(explode(',', trim_cell($row[14]))), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
    if ($row[0] && $row[1] && $row[2] && $row[3] && $row[4] && $row[5] && isset($row[6]) && strlen($row[8]) && $row[9] && $row[10] && $row[16]) {
        //查询已存在的最新的生产商，目录号，包装相同的商品。
        $manufacturer = $row[1];
        $catalog_no = $row[3];
        $package = $row[5];
        $sql = "SELECT `id`,`dirty` FROM `product` WHERE (`vendor_id` = $vendor_id AND `manufacturer`='$manufacturer' AND `catalog_no`='$catalog_no' AND `package`='$package')";
        $repeated = $db->query($sql)->rows();
        if ($row[16] == '删除') {
            if (!count($repeated)) {
                $fail_no++;
                $error_msg = T('第%line_no行, %vproduct_name 删除失败，没有找到对应商品', array('%line_no'=>$line_no, '%vproduct_name'=>$row[0]));
                $row[17] = $error_msg;
                $csv->write($row);
            }
            else {
                foreach ($repeated as $p) {
                    $pid = $p->id;
                    // 订单关联就不允许删除了
                    if (!Q("order_item[product_id={$pid}]")->total_count()) {
                        $delete_pids[] = $pid;
                        // 每100条进行一次删除
                        if (count($delete_pids) == 100) {
                            if (delete_products($delete_pids)) {
                                $delete_success_no += 100;
                            }
                            else {
                                $delete_fail_no += 100;
                            }
                            unset($delete_pids);
                        }
                    }
                    else {
                        $can_not_delete_no++;
                        $can_not_delete_ids[] = $pid;
                        $fail_no++;
                        $error_msg = T('第%line_no行, %vproduct_name 删除失败，改商品关联了订单', array('%line_no'=>$line_no, '%vproduct_name'=>$row[0]));
                        $row[17] = $error_msg;
                        $csv->write($row);
                        break;
                    }
                }
            }
        }
        elseif ($row[16] == '下架') {
            if (!count($repeated)) {
                $fail_no++;
                $error_msg = T('第%line_no行, %vproduct_name 下架失败，没有找到对应商品.', array('%line_no'=>$line_no, '%vproduct_name'=>$row[0]));
                $row[17] = $error_msg;
                $csv->write($row);
            }
            else {
                foreach ($repeated as $p) {
                    $pid = $p->id;
                    $product = O("product", $pid);
                    if ($product->publish_date > 0 && $product->unpublish()) {
                        Search_Product::update_index($product);
                        $unpublish_success_no++;
                    }
                    else {
                        $fail_no++;
                        $error_msg = T('第%line_no行, %vproduct_name 下架失败，系统原因.', array('%line_no'=>$line_no, '%vproduct_name'=>$row[0]));
                        $row[17] = $error_msg;
                        $csv->write($row);
                        break;
                    }
                }
            }
        }
        elseif ($row[16] == '上架') {

            if (!count($repeated)) {
                $add_no++;
                $new_row = [];
                //供应商 id
                $new_row[0] = $vendor_id;
                //商品类别
                $new_row[1] = 'biologic_reagent';
                //产品名称
                $new_row[2] = $row[0];
                //生产商
                $new_row[3] = $row[1];
                //货号
                $new_row[4] = $row[3];
                //规格
                $new_row[5] = $row[4];
                //包装
                $new_row[6] = $row[5];
                //型号
                $new_row[7] = $row[7];
                //分类 to do 生物试剂的分类????
                $category = array_key_exists($row[6], $category_array) ? $row[6] : 808;
                $category = O('product_category', array('name'=>$category_array[$category]));
                $category_id = $category->id? :0;
                $new_row[8] = $category_id;
                //商品简介
                $new_row[9] = $row[11];
                //关键字
                $new_row[10] = $row[14];
                //商品价格
                $new_row[11] = !$row[8] ? -1 : round($row[8], 2);
                //商品备注
                $new_row[12] = $row[15];
                //库存现货
                $new_row[13] = array_search($row[9], Product_Model::$stock_status);
                //品牌
                $new_row[14] = $row[2];
                //发布人
                $new_row[15] = '';
                //供货时间
                $new_row[16] = (int)$row[10];
                //发布时间
                $new_row[17] = $now;
                //ctime
                $new_row[18] = $now;
                //修改时间 mtime
                $new_row[19] = $now;
                //虚属性_extra
                $extra = [];
                //运输条件
                $extra['transport_cond'] = $row[12];
                //保存条件
                $extra['storage_cond'] = $row[13];
                $new_row[20] = $db->escape(@json_encode($extra));

                $new_csv->write($new_row);
                // echo '.';
            }
            else {
                foreach ($repeated as $p) {
                    $pid = $p->id;
                    if ($p->dirty) {
                        $insert_history = "INSERT INTO `product_revision` (vendor_id,unit_price,vendor_note,approver_id,approve_date,publisher_id,publish_date,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra,last_approver_id,last_approve_date,last_publisher_id,last_publish_date,unapprover_id,unapprove_date, product_id,version) (SELECT vendor_id,unit_price,vendor_note,approver_id,approve_date,publisher_id,publish_date,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra,last_approver_id,last_approve_date,last_publisher_id,last_publish_date,unapprover_id,unapprove_date,id,version FROM product WHERE id = $pid)";
                        $ph_ret = $db->query($insert_history);
                        $update_product = "UPDATE `product` SET `last_approver_id`=`product`.`approver_id`, `last_approve_date`=`product`.`approve_date`, `unapprove_date`=0, `last_publisher_id`=`product`.`publisher_id`, `last_publish_date`=`product`.`publish_date`, `approve_date`={$now}, `approver_id`=0, `publisher_id`=0, `publish_date`=0, `freeze_reasons`='',`mtime`={$now}, `version`=`version`+1, `dirty`=0 WHERE id={$pid}";
                        $p_ret = $db->query($update_product);
                        if($ph_ret && $p_ret) {
                            // echo '*';
                        }
                    }

                    $product = O('product', $pid);
                    $vproduct = put_info($product, $row, $category_array);
                    if ($vproduct->save()) {
                        $update_success_no++;
                    }
                    else {
                        $update_fail_no++;
                    }
                }
            }
        }
        else {
            $fail_no++;
        }
    }
    else {
        $row[17] = '产品名称，生产商，目录号，保证，规格，品牌，单价，库存状态，生物试剂分类代码，供货时间不能为空';
        $csv->write($row);
        $fail_no++;
        echo(T('%vproduct_name 导入失败！', array('%vproduct_name'=>$row[0])));
    }
    unset($row);
}

$new_csv->close();
$csv_import->close();

// 批量导入 csv 数据到数据库
$db->query("load data local infile '%s' ignore into table product character set utf8 fields terminated by ',' enclosed by '\"' lines terminated by '%s' (`vendor_id`,`type`,`name`,`manufacturer`,`catalog_no`,`spec`,`package`,`model`,`category_id`,`description`,`keywords`,`unit_price`,`vendor_note`,`stock_status`,`brand`,`publisher_id`,`supply_time`,`publish_date`,`ctime`,`mtime`, `_extra`)",$new_file,"\n");
$success_no = $db->affected_rows();
$add_fail_no = $add_no - $success_no;
$fail_no += $add_fail_no;
$record = O('product_upload_record',['path'=>$csv_file]);
if ($record->id) {
    $record->status = Product_Upload_Record_Model::RECORD_STATUS_SUCCESS;
    $record->save();
}

if ($delete_count = count($delete_pids)) {
    if (delete_products($delete_pids)) {
        $delete_success_no += $delete_count;
    }
    else {
        $delete_fail_no += $delete_count;
    }
    unset($delete_pids);
}
if ($record->id) {
    $record->status = Product_Upload_Record_Model::RECORD_STATUS_SUCCESS;
    $record->save();
}

$import_message = T('共 %line_no 条数据, 成功新建 %success_no 条, 成功更新 %update_success_no 条, 成功下架 %unpublish_success_no 条, 成功删除 %delete_success_no 条, 失败导入 %fail_no 条', array('%line_no'=>$line_no, '%success_no'=>$success_no, '%update_success_no'=>$update_success_no, '%fail_no'=>$fail_no, '%unpublish_success_no'=>$unpublish_success_no, '%delete_success_no'=>$delete_success_no));
if ($can_not_delete_no) {
    $import_message .= ' (由于关联订单, 以下ID商品无法删除: '.implode(',', $can_not_delete_ids).')';
}

$csv->write((array)$import_message);
$csv->close();

// 导入结束后统计供应商商品总数
$db = ORM_Model::db('vendor');
$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vendor_id AND approve_date>0) WHERE id=$vendor_id");

/*
 * 导入商品sphinx索引更新
 * 索引更新脚本的优化是接下来的任务，先放上之前的版本
 *
 */
$var = $db->query("SELECT MAX(`id`) as max,MIN(`id`) as min FROM product WHERE mtime=$now AND vendor_id=$vendor_id")->row();
$id_start = $var->min - 1;
$id_end = $var->max;
$cmd = strtr('php ' . ROOT_PATH . 'cli/sphinx_update.php  %start %end', array(
                 '%start' => $id_start,
                 '%end' => $id_end,
                 ));
exec($cmd);

//如果全部导入成功则删除错误文件
if ($fail_no == 0) {
    File::delete($error_file);
}

echo $import_message."\n";
$end_time = Date::time();
$spend_time = $end_time - $start_time;
echo($line_no.'条数据共计耗时: '.$spend_time.'s');
echo(" \n");
$vendor->import_message = $import_message;
$vendor->is_processing_import_products = FALSE;
$vendor->save();
clean_cache();
//返回key和value相同的array
function array_key_same_as_value($array) {
    $tmp_arr = array();
    foreach((array)$array as $value) {
        $tmp_arr[$value] = $value;
    }
    return array_values($tmp_arr);
}

function trim_cell($str) {
    $format = chr(0xC2).chr(0xA0);
    if (strpos($str, $format) !== FALSE) {
        $str = str_replace($format, ' ',$str);
    }
    return trim($str);
}

function put_info($vproduct, $row, $category_array){

    $vproduct->type = 'biologic_reagent';
    //产品名称
    $vproduct->name = $row[0];
    //生产商
    $vproduct->manufacturer = $row[1];
    //目录号
    $vproduct->catalog_no = $row[3];
    //包装
    $vproduct->package = $row[5];
    //品牌
    $vproduct->brand = $row[2];
    //规格
    $vproduct->spec = $row[4];
    //分类
    $category = array_key_exists($row[6], $category_array) ? $row[6] : 1;
    $vproduct->category = O('product_category', array('name'=>$category_array[$category]));
    //型号
    $vproduct->model = $row[7];
    //商品价格
    $vproduct->unit_price = !$row[8] ? -1 : round($row[8], 2);
    //库存现货
    $vproduct->stock_status = array_search($row[9], Product_Model::$stock_status);
    //供货时间
    $vproduct->supply_time = (int)$row[10];
    //商品简介
    $vproduct->description = $row[11];
    //运输条件
    $vproduct->transport_cond = $row[12];
    //保存条件
    $vproduct->storage_cond = $row[13];
    //关键字
    if ($row[14]) {
        $vproduct->keywords = $row[14];
    }
    //商品备注
    $vproduct->vendor_note = $row[15];

    $vproduct->publish_date = Date::time();

    return $vproduct;
}

function delete_products($pids) {
    $db = Database::factory();
    $sphinx = Database::factory('@sphinx');
    $delete_ids = implode(',', $pids);
    $sql = "DELETE FROM product WHERE id in ($delete_ids)";
    $ret = $db->query($sql);
    if ($ret) {
        $sphinx->query("DELETE FROM `".Search_Iterator::get_index_name('product')."` WHERE id in ($delete_ids)");
        $sphinx->query("DELETE FROM `".Search_Iterator::get_index_name('product_reagent')."` WHERE id in ($delete_ids)");
        return TRUE;
    }
    return FALSE;
}

function convert_row($row, $rule) {
    $new_row = [];
    foreach ($rule as $k => $v) {
        $new_row[$k] = $row[$v];
    }
    return $new_row;
}
