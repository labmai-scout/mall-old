#!/usr/bin/env php
<?php
/*
* usage SITE_ID=nankai php import_reagent_product.php 184 商品导入样品.csv
* author: rui.ma@geneegroup.com
* date: 2012.04.19
* Yu.Li 2013.2.21修改
* date: 2014.4.10 增加列格式判断,字符编码判断
* jinlin.li 2014.04.30 优化导入脚本, 放弃使用orm方法, 使用SQL语句
* 每10000条数据导入时间从1100s 降至 606s 效率提升45%
* 存在重复数据默认更新，如果 dirty，生成新的历史版本
* 每10000条数据导入的时间606s 降至 80s 效率提升86%
*/

require 'base.php';
$start_time = Date::time();
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

$csv_file = $argv[2];

if (!is_file($csv_file)) {
    echo(T('CSV文件 %csv_file 不存在!', array('%csv_file'=>$csv_file)));
    return;
}

//分类tag辅助数组
$category_array =  array(
    '1'=>'化学试剂',
    '100'=>'通用试剂',
    '101'=>'有机',
    '102'=>'无机',
    '103'=>'分析',
    '104'=>'生化',
    '105'=>'CAS',
    '106'=>'离子交换',
    '200'=>'有机试剂',
    '201'=>'杂环化合物',
    '202'=>'聚合物试剂',
    '203'=>'离子液体',
    '204'=>'有机金属',
    '205'=>'同位素标记',
    '206'=>'无水试剂',
    '300'=>'无机试剂',
    '301'=>'催化剂',
    '302'=>'硅胶',
    '303'=>'分子筛',
    '304'=>'干燥剂',
    '305'=>'层析',
    '306'=>'无水试剂',
    '307'=>'无机盐',
    '308'=>'酸',
    '309'=>'碱',
    '400'=>'分析试剂',
    '401'=>'标准品',
    '402'=>'基准试剂',
    '403'=>'卡尔费休',
    '404'=>'气相色谱',
    '405'=>'液相色谱',
    '406'=>'指示剂',
    '407'=>'缓冲剂',
    '500'=>'高纯试剂',
    '501'=>'稀土金属',
    '502'=>'高纯无机试剂',
    '503'=>'光谱纯试剂',
    '504'=>'高纯金属',
    '505'=>'高纯溶剂',
    '506'=>'复配试剂',
    '600'=>'生化试剂',
    '601'=>'抗体',
    '602'=>'酶类',
    '603'=>'诊断试剂',
    '604'=>'糖类',
    '605'=>'维生素',
    '606'=>'氨基酸',
    '607'=>'蛋白质',
    '608'=>'培养基',
    '609'=>'核苷酸',
    '610'=>'生物碱',
    '700'=>'环保试剂',
    '701'=>'环保指示剂',
    '702'=>'缓冲剂',
    '703'=>'环境测试盒',
    '704'=>'环保标样',
    '705'=>'微量分析',
    '706'=>'环保试纸',
    '800'=>'精细化工',
    '801'=>'化工产品',
    '802'=>'化工原料',
    '803'=>'助剂',
    '804'=>'大包装试剂',
    '805'=>'清洗消毒',
    '806'=>'硅烷偶联剂'
);

$csv_line_format = array(
                HT('商品名称*'),
                HT('生产商*'),
                HT('品牌*'),
                HT('货号*'),
                HT('规格*'),
                HT('包装*'),
                HT('型号'),
                HT('分类'),
                HT('化学试剂性质*'),
                HT('常用危险化学品分类'),
                HT('化学试剂 CAS 号'),
                HT('化学试剂英文名'),
                HT('化学试剂别名'),
                HT('化学试剂分子式'),
                HT('化学试剂分子量'),
                HT('商品单价*'),
                HT('库存*'),
                HT('供货时间*'),
                HT('商品简介'),
                HT('关键字'),
                HT('商品备注'),
                HT('操作类别*'),
            );

$csv_import = new CSV($csv_file, 'r');
//表头检查
$head_columns = $csv_import->read();

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
    $ret = FALSE;
    echo(T('%file_name 文件编码格式错误, 应该为 UTF-8', array('%file_name'=>$csv_file)));
}

$record = O('product_upload_record',['path'=>$csv_file]);
if (!$ret) {
    if ($record->id) {
        $record->status = Product_Upload_Record_Model::RECORD_STATUS_FAILED;
        $record->save();
    }
    exit;
}

$base_path = '/data/product_upload/converted';
//准备csv，写入错误文件
$error_file = $base_path.'/'.$vendor->id.'-'.date("YmdHis").'-error.csv';
file::check_path($error_file);
$csv = new CSV($error_file, 'w');
$error_file_format = $csv_line_format + array(HT('错误信息'));
$csv->write($error_file_format);

$new_file = $base_path.'/'.$vendor->id.'-'.date("Y-m-d-H-i-s").'.csv';
file::check_path($new_file);
$new_csv = new CSV($new_file, 'w');

$vendor->is_processing_import_products = TRUE;
$vendor->save();

$delete_pids = [];
$can_not_delete_ids  = [];
$fail_no              = 0;
$update_success_no    = 0;
$update_fail_no       = 0;
$unpublish_success_no = 0;
$unpublish_fail_no    = 0;
$can_not_delete_no    = 0;
$delete_success_no    = 0;
$delete_fail_no       = 0;
$line_no              = 0;
$new_num              = 0;
$now = Date::time();
$vendor_id = $vendor->id;
$db = Database::factory();
while($row = $csv_import->read()) {
    $row = convert_row($row, $rule);
    $line_no ++;

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
    if (!$right_encoding) {
        $fail_no++;
        $error_msg = T('第%line_no行, %vproduct_name 导入失败，编码错误， 错误的列包括: %error_msg !', array('%line_no'=>$line_no, '%vproduct_name'=>$row[0], '%error_msg'=>implode(',', $wrong_columns)));
        $row[22] = $error_msg;
        echo($error_msg);
        $csv->write( $row );
        continue;
    }

    // 防止 SQL 注入
    foreach ($row as $key => $cell) {
        $row[$key] = $db->escape(trim_cell($row[$key]));
    }

    if ($row[19]) {
        //提前处理keywords
        $row[19] = json_encode((object) array_key_same_as_value(explode(',', trim_cell($row[19]))), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    if ($row[0] && $row[1] && $row[2] && $row[3] && $row[4] && $row[5] && $row[8] && strlen($row[15]) && $row[16] && $row[17] && $row[21]) {
        //查询已存在的最新的供应商，生产商，目录号，包装相同的商品。
        $manufacturer = $row[1];
        $catalog_no = $row[3];
        $package = $row[5];
        $sql = "SELECT `id`,`dirty` FROM `product` WHERE (`vendor_id` = $vendor_id AND `manufacturer`='$manufacturer' AND `catalog_no`='$catalog_no' AND `package`='$package')";
        $repeated = $db->query($sql)->rows();
        if ($row[21] == '删除') {
            if (!count($repeated)) {
                $fail_no++;
                $error_msg = T('第%line_no行, %vproduct_name 删除失败，没有找到对应商品', array('%line_no'=>$line_no, '%vproduct_name'=>$row[0]));
                $row[22] = $error_msg;
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
                        $row[22] = $error_msg;
                        $csv->write($row);
                        break;
                    }
                }
            }

        }
        elseif ($row[21] == '下架') {
            if (!count($repeated)) {
                $fail_no++;
                $error_msg = T('第%line_no行, %vproduct_name 下架失败，没有找到对应商品.', array('%line_no'=>$line_no, '%vproduct_name'=>$row[0]));
                $row[22] = $error_msg;
                $csv->write($row);
            }
            else {
                foreach ($repeated as $p) {
                    $pid = $p->id;
                    $product = O("product", $pid);
                    if ($count == 0) {
                        if ($product->publish_date>0 && $product->unpublish()) {
                            $unpublish_success_no++;
                        }
                        else {
                            $fail_no++;
                            $error_msg = T('第%line_no行, %vproduct_name 下架失败，系统原因.', array('%line_no'=>$line_no, '%vproduct_name'=>$row[0]));
                            $row[22] = $error_msg;
                            $csv->write($row);
                            break;
                        }
                    }
                }
            }
        }
        elseif ($row[21] == '上架') {
            $sql = "SELECT `id`,`dirty` FROM `product` WHERE (`vendor_id` = $vendor_id AND `manufacturer`='$manufacturer' AND `catalog_no`='$catalog_no' AND `package`='$package')";
            $repeated = $db->query($sql)->rows();
            // 不存在重复商品的条件下
            if (!count($repeated)) {
                $new_num ++;
                $new_row = [];
                //供应商 id
                $new_row[0] = $vendor_id;
                //商品类别
                $new_row[1] = 'reagent';
                //商品名称
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
                $new_row[7] = $row[6];
                //型号
                $category = array_key_exists($row[7], $category_array) ? $row[7] : 1;
                $category = O('product_category', array('name'=>$category_array[$category]));
                $category_id = $category->id? :0;
                $new_row[8] = $category_id;
                //商品简介
                $new_row[9] = $row[18];
                //关键字
                $new_row[10] = $row[19];
                //单价
                $new_row[11] = !$row[15] ? -1 : round($row[15], 2);
                //商品备注
                $new_row[12] = $row[20];
                //库存状态
                $new_row[13] = array_search($row[16], Product_Model::$stock_status);
                //品牌
                $new_row[14] = $row[2];
                //发布人
                $new_row[15] = '';
                //供货时间
                $new_row[16] = (int)$row[17];
                //发布时间
                $new_row[17] = $now;
                //ctime
                $new_row[18] = $now;
                //修改时间 mtime
                $new_row[19] = $now;
                //虚属性 _extra
                $extra = [];
                //化学试剂性质
                $types = Config::get('reagent.types');
                if (array_key_exists($row[8], $types)) {
                    $rgt_type = (int)$row[8];
                }
                else {
                    $rgt_type = 1;
                }
                $extra['rgt_type'] = $rgt_type;
                if ($rgt_type == Reagent_Type::DANGEROUS) {
                    $reagent_danger_classes_keys = array();
                    foreach(Config::get('reagent.danger_classes') as $reagent_danger_class) {
                        $reagent_danger_classes_keys =  array_merge($reagent_danger_classes_keys, array_keys((array)$reagent_danger_class));
                    }
                    if (in_array((int)$row[9], $reagent_danger_classes_keys)) {
                        $extra['rgt_danger_class'] = (int)$row[9];
                    }
                    else {
                        $extra['rgt_danger_class'] = 0;
                    }
                }
                //化学试剂CAS号
                $extra['cas_no'] = $row[10];
                //产品化学试剂英文名
                $extra['rgt_en_name'] = $row[11];
                //化学试剂别名
                $extra['rgt_aliases'] = json_encode((object) array_key_same_as_value(explode(',', $row[12])));
                //化学试剂分子式
                $extra['reagent_formula'] = $row[13];
                //化学试剂分子量
                $extra['reagent_mw'] = $row[14];
                //虚属性保存
                $new_row[20] = $db->escape(@json_encode($extra));

                $new_csv->write($new_row);
                // echo '.';
            }
            else {
                //替换已存在商品的信息，如果有修改制定内容则进行下架处理
                foreach ($repeated as $p) {
                    $pid = $p->id;
                    if ($p->dirty) {
                        $insert_history = "INSERT INTO `product_revision` (vendor_id,unit_price,vendor_note,approver_id,approve_date,publisher_id,publish_date,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra,last_approver_id,last_approve_date,last_publisher_id,last_publish_date,unapprover_id,unapprove_date, product_id,version) (SELECT vendor_id,unit_price,vendor_note,approver_id,approve_date,publisher_id,publish_date,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra,last_approver_id,last_approve_date,last_publisher_id,last_publish_date,unapprover_id,unapprove_date,id,version FROM product WHERE id = $pid)";
                        $ph_ret = $db->query($insert_history);
                        $update_product = "UPDATE `product` SET `last_approver_id`=`product`.`approver_id`, `last_approve_date`=`product`.`approve_date`, `unapprove_date`=0, `last_publisher_id`=`product`.`publisher_id`, `last_publish_date`=`product`.`publish_date`, `approve_date`={$now}, `approver_id`=0, `publisher_id`=0, `publish_date`=0, `freeze_reasons`='',`mtime`={$now}, `version`=`version`+1, `dirty`=0 WHERE id={$pid}";
                        $p_ret = $db->query($update_product);
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
        $row[22] = '产品名称, 生产商, 品牌, 供货时间, 库存状态, 目录号, 规格, 包装, 商品单价, 化学试剂性质,操作类别 不能为空';
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
$new_num_fail = $new_num - $success_no;
$fail_no += $new_num_fail;
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
$vendor->import_message = $import_message;
$vendor->is_processing_import_products = FALSE;
$vendor->save();

//如果全部导入成功则删除错误文件
if ($fail_no == 0) {
    File::delete($error_file);
}

echo $import_message."\n";
$end_time = Date::time();
$spend_time = $end_time - $start_time;
echo($line_no.'条数据共计耗时: '.$spend_time."s \n");
echo(" \n");
clean_cache();

function put_info($vproduct, $row, $category_array){
    $vproduct->type = 'reagent';
    //产品名称
    $vproduct->name = $row[0];
    //生产商
    $vproduct->manufacturer = $row[1];
    //品牌
    $vproduct->brand = $row[2];
    //目录号
    $vproduct->catalog_no = $row[3];
    //规格
    $vproduct->spec = $row[4];
    //包装
    $vproduct->package = $row[5];
    //型号
    $vproduct->model = $row[6];
    //分类
    $category = array_key_exists($row[7], $category_array) ? $row[7] : 1;
    $vproduct->category = O('product_category', array('name'=>$category_array[$category]));
    //说明
    $vproduct->description = $row[18];
    //关键字
    if ($row[19]) {
        $vproduct->keywords = $row[19];
    }
    //化学试剂性质
    $types = Config::get('reagent.types');
    if (array_key_exists($row[8], $types)) {
        $vproduct->rgt_type = $row[8];
    }
    else {
        $vproduct->rgt_type = 1;
    }

    if ($vproduct->rgt_type == Reagent_Type::DANGEROUS) {
        $reagent_danger_classes_keys = array();
        foreach(Config::get('reagent.danger_classes') as $reagent_danger_class) {
            $reagent_danger_classes_keys =  array_merge($reagent_danger_classes_keys, array_keys((array)$reagent_danger_class));
        }
        if (in_array((int)$row[9], $reagent_danger_classes_keys)) {
            $vproduct->rgt_danger_class = $row[9];
        }
    }
    else {
        $vproduct->rgt_danger_class = '';
    }
    //化学试剂CAS号
    $vproduct->cas_no = $row[10];
    //产品化学试剂英文名
    $vproduct->rgt_en_name = $row[11];
    //化学试剂别名
    $vproduct->rgt_aliases = json_encode((object) array_key_same_as_value(explode(',', $row[12])));
    //化学试剂分子式
    $vproduct->reagent_formula = $row[13];
    //化学试剂分子量
    $vproduct->reagent_mw = $row[14];
    //商品单价
    $unit_price = !$row[15] ? -1 : $row[15];
    $vproduct->unit_price = round($unit_price, 2);
    //商品备注
    $vproduct->vendor_note = $row[20];
    //库存状态
    $vproduct->stock_status = (int)array_search($row[16],Product_Model::$stock_status);

    $vproduct->supply_time = (int)$row[17];
    $vproduct->publish_date = Date::time();

    return $vproduct;
}
//返回key和value相同的array
function array_key_same_as_value($array) {
    $tmp_arr = array();
    foreach((array)$array as $value) {
        $tmp_arr[$value] = $value;
    }
    return array_values($tmp_arr);
}
// 去除空格及&#160;
function trim_cell($str) {
    $format = chr(0xC2).chr(0xA0);
    if (strpos($str, $format) !== FALSE) {
        $str = str_replace($format, ' ',$str);
    }
    return trim($str);
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
    return TRUE;
}

function convert_row($row, $rule) {
    $new_row = [];
    foreach ($rule as $k => $v) {
        $new_row[$k] = $row[$v];
    }
    return $new_row;
}
