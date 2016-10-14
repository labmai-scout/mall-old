<?php
include 'base.php';
/*
* 每天删除一天时间之前的付款单pdf文件
*/

$path = Config::get('system.tmp_dir').'pdf/';
File::check_path($path.'foo');
$expire_date = strtotime('yesterday');
$files = NFS::file_list($path);

foreach($files as $file) {
    if($file['mtime'] <= $expire_date){
    	unlink($path.$file['name']);
    }
}
