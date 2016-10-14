#!/usr/bin/env php
<?php

/*
* usage SITE_ID=nankai php modify_vendor_product.php 184(供货商ID) 商品修改.csv
* author: jinlin.li@geneegroup.com
* date:2013.12.28
*/

require 'base.php';

$vendor_id = $argv[1];
$csv_file = $argv[2];
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

if (!is_numeric($vendor_id)) {
    fecho('vendor_id 只能为数字');
    return;
}
else {
    $vendor = O('vendor', $vendor_id);
    if (!$vendor->id) {
        fecho('vendor_id 填写有误！'); 
        return;
    }
}

if (!is_file($csv_file)) {
    fecho(T('CSV文件 %csv_file 不存在!', array('%csv_file'=>$csv_file)));
}

//准备csv，写入错误文件
$new_path = SITE_PATH.'private/error_csv/'.$vendor->id.'/';
$error_file = $new_path.'modify_error_'.basename($csv_file);
file::check_path($error_file);
$csv = new CSV($error_file, 'w');
$csv->write(array(
                HT('产品名称 *'),
                HT('生产商 *'),
                HT('目录号 *'),
                HT('规格 *'),
                HT('包装 *'),
                HT('型号'),
                HT('分类'),
                HT('说明'),
                HT('关键字'),
                HT('化学试剂性质'),
                HT('常用危险化学品分类'),
                HT('化学试剂 CAS 号'),
                HT('产化学试剂英文名'),
                HT('化学试剂别名'),
                HT('化学试剂分子式'),
                HT('化学试剂分子量'),
                HT('商品单价 *'),
                HT('商品备注'),
                HT('现货'),
                HT('品牌'),
                HT('错误信息'),
            ));


$csv_import = new CSV($csv_file, 'r');
$csv_import->read();
$update_no = 0;

$fail_rows = 0;

while($row = $csv_import->read()) {

    if(count($row) != 19 && count($row) != 20){
        $row[20] = '数据个数不正确';
        $csv->write( $row );
        $fail_rows++;
        fecho(T('%vproduct_name 导入失败，数据个数不正确！', array('%vproduct_name'=>$row[0])));
        continue;
    }

    //由于第一行是表头，标明相关csv文件信息,故行号需要加1
    $line_no ++;
    
    //如果产品名称、生成商、目录号都存在的
    if ($row[0] && $row[1] && $row[2]) {
        $db = database::factory();

        //查询已存在的最新的生产商，目录号，包装相同的商品。
        $manufacturer = $db->escape($row[1]);
        $catalog_no = $db->escape($row[2]);
        $package = $db->escape($row[4]);

        $sql = "SELECT `id` FROM `vendor_product` WHERE (`vendor_id`='$vendor->id' AND `manufacturer`='$manufacturer' AND `catalog_no`='$catalog_no' AND `package`='$package')";

        $results = $db->query($sql)->rows();

        if (!count($results)) {
            
            fecho(T('第%line_no行导入失败！未找到对应的vendor_product', array('%line_no'=>$line_no)));

        }
        else {
            //替换已存在商品的信息，如果有修改制定内容则进行下架处理
            foreach ($results as $p) {
                $vendor_product = O('vendor_product', $p->id);

                $unit_price = $vendor_product->unit_price;

                $vproduct = put_info($vendor_product, $row, $category_array);
                if ($vproduct->save()) {
                    $success_arr[] = $line_no;
                }
                $update_no ++;
                echo('.');
            }
        }
    }
    else {  
        $row[20] = '产品名称，生产商，目录号不能为空';
        $csv->write( $row );
        $fail_rows++;
        fecho(T('%vproduct_name 修正失败！', array('%vproduct_name'=>$row[0])));
    }
    unset($row);
}


$vendor->modify_message = '';


$modify_message = T('共 %line_no 条数据, 成功修正 %success_no 条，失败修正 %fail_no 条', array('%line_no'=>$line_no, '%success_no'=>count($success_arr), '%fail_no'=>$fail_rows));

echo $modify_message."\n";
$csv->write( (array)$modify_message );


$csv_import->close();



$vendor->save();
$csv->close();

function put_info($vproduct, $row, $category_array){
    $vproduct->type = 'reagent';
    
    //产品名称
    $vproduct->name = $row[0];
    //生产商
    $vproduct->manufacturer = $row[1];
    //目录号
    $vproduct->catalog_no = $row[2];
    //规格
    $vproduct->spec = $row[3];
    //包装
    $vproduct->package = $row[4];
    //型号
    $vproduct->model = $row[5];
    //分类
    // $category = array_key_exists($row[6], $category_array) ? $row[6] : 1;
    // $vproduct->category = O('product_category', array('name'=>$category_array[$category]));
    //说明
    $vproduct->description = $row[7];
    //关键字
    $vproduct->keywords = json_encode((object) array_key_same_as_value(explode(',', $row[8])));
    //化学试剂性质
    // $vproduct->rgt_type = $row[9] ? : 1;
    //常用危险化学分类
    // $vproduct->rgt_danger_class = $row[10];
    //化学试剂CAS号
    // $vproduct->cas_no = $row[11];
    //产品化学试剂英文名
    // $vproduct->rgt_en_name = $row[12];
    //化学试剂别名
    // $vproduct->rgt_aliases = json_encode((object) array_key_same_as_value(explode(',', $row[13])));
    //化学试剂分子式
    // $vproduct->reagent_formula = $row[14];
    //化学试剂分子量
    // $vproduct->reagent_mw = $row[15];
    //商品单价
    $vproduct->unit_price = !$row[16] ? -1 : $row[16];
    //商品备注
    $vproduct->vendor_note = $row[17];
    //现货
    $vproduct->stock_status = (int)$row[18];
    //品牌
    // $vproduct->brand = $row[19] ?: '';

    return $vproduct;
}

function fecho($msg) {
    Upgrader::echo_fail($msg. "\n");
}

function secho($msg) {
    Upgrader::echo_success($msg. "\n");
}


//返回key和value相同的array
function array_key_same_as_value($array) {
    $tmp_arr = array();
    foreach((array)$array as $value) {
        $tmp_arr[$value] = $value;
    }
    return $tmp_arr;
}

