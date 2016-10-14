<?php
/*
    SITE_ID=nankai php import_manufacturer.php csv.file
    csv 格式
    生产商名称 别名
    ps 别名如果是多个，使用";"来分割
*/
require 'base.php';

$csv_file = $argv[1];

if (!is_file($csv_file)) {
    fecho(T('CSV文件 %csv_file 不存在!', array('%csv_file'=>$csv_file)));
    return;
}

function fecho($msg) {
    Upgrader::echo_fail($msg. "\n");
}

function secho($msg) {
    Upgrader::echo_success($msg. "\n");
}

function trim_cell($str) {
    return trim($str, chr(0xC2).chr(0xA0).' ');
}

$csv_import = new CSV($csv_file, 'r');
$ret = TRUE;
$head_columns = $csv_import->read();
if (count($head_columns) != 2) {
    $ret = FALSE;
    fecho(T('csv文件列表格式数不正确，请检查 csv 文件'));
}

if (!mb_check_encoding(file_get_contents($csv_file), 'UTF-8')) {
    $ret = FALSE;
    fecho(T('%file_name 文件编码格式错误, 应该为 UTF-8', array('%file_name'=>$csv_file)));
}

if (!$ret) {
	return;
}

while($row = $csv_import->read()) {
    $manufacturer_name = trim_cell($row[0]);
    $aliases = trim_cell($row[1]);
    $manufacturer = O('manufacturer');
    $manufacturer->name = $manufacturer_name;
    $manufacturer->alias = $aliases;
    $manufacturer->authorised = TRUE;
    if ($manufacturer->save()) {
        echo '.';
    }
    else {
        echo 'x';
    } 
}