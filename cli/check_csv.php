#!/usr/bin/env php
<?php

/*
* usage SITE_ID=nankai php check_csv.php test.csv
* author: yu.li@geneegroup.com
* date: 2013.02.27
*/

require 'base.php';

function fecho($msg) {
    Upgrader::echo_fail($msg. "\n");
}

function secho($msg) {
    Upgrader::echo_success($msg. "\n");
}

$csv_file = $argv[1];

if (!is_file($csv_file)) {
    fecho(T('CSV文件 %csv_file 不存在!', array('%csv_file'=>$csv_file)));
}


$fail_rows = 0;

//准备csv，写入错误文件
$new_path = SITE_PATH.'private/error_csv/'.$vendor->id.'/';
$error_file = $new_path.'error_csv_'.basename($csv_file);
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

while($row = $csv_import->read()) {
    
    $prev_row = $row;
    if(count($row) != 19 && count($row) != 20){
        $row[20] = '数据个数不正确';
        $csv->write( $row );
        $fail_rows++;
        // var_dump($prev_row);
        // var_dump($row);
        fecho(T('%vproduct_name 导入失败，数据个数不正确！', array('%vproduct_name'=>$row[0])));
        continue;
    }

    //由于第一行是表头，标明相关csv文件信息,故行号需要加1
    $line_no ++;
    
    if (!($row[0] && $row[1] && $row[2])) {
        $row[20] = '产品名称，生产商，目录号不能为空';
        $csv->write( $row );
        $fail_rows++;
        fecho(T('%vproduct_name 导入失败！产品名称，生产商，目录号不能为空', array('%vproduct_name'=>$row[0])));
        //var_dump($row);
        continue;
    }

    echo ".";
    unset($row);
}


$vendor->import_message = '';

$import_message = T('共 %line_no 条数据, 失败 %fail_no 条', array('%line_no'=>$line_no, '%fail_no'=>$fail_rows));

echo $import_message."\n";
$vendor->import_message = $import_message;
$csv->write( (array)$import_message );


$csv_import->close();

$csv->close();
