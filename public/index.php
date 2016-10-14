<?php
//系统维护代码
/*
$maintain_view = 'maintain/maintenance.phtml';
$path = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] . $path;

$_vars = array(
	'maintan_end_date' =>  date('Y.m.d H:i', strtotime('2014-1-1 10:11')),
	'path' => 'http://' .$_SERVER['HTTP_HOST'] . $path,
 );
if ( file_exists($maintain_view) ) {
	ob_start();
	extract($_vars);

	@include($maintain_view);

	$output = ob_get_contents();
	ob_end_clean();
	echo $output;
}

die;
*/

date_default_timezone_set('Asia/Shanghai');

$dir = dirname(__FILE__).'/..';
if(function_exists('realpath') && @realpath($dir) !== FALSE){
	define('ROOT_PATH',  @realpath($dir).'/');
}else{
	define('ROOT_PATH', $dir.'/');
}

$phar_path = ROOT_PATH.'system.phar';
if (is_file($phar_path)) {
	define('SYS_PATH', 'phar://'.ROOT_PATH.'system.phar/');
}
else {
	define('SYS_PATH', ROOT_PATH.'system/');
}

$GLOBALS['SCRIPT_START_AT'] = microtime(TRUE);
require SYS_PATH.'core/bootstrap.php';
