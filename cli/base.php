<?php

date_default_timezone_set('Asia/Shanghai');
define('REDIS_DB', 1);
define('REDIS_HOST', 'genee-redis.docker.local');

// 获得CGI的缓冲方式
if (extension_loaded('redis')) {
    define('CGI_DEFAULT_CACHE', 'redis');
}
elseif (extension_loaded('yac')) {
    define('CGI_DEFAULT_CACHE', 'yac');
}
elseif (extension_loaded('xcache') && ini_get('xcache.var_size')) {
    define('CGI_DEFAULT_CACHE', 'xcache');
}
elseif (extension_loaded('apc')) {
    define('CGI_DEFAULT_CACHE', 'apc');
}


$root_path = isset($_SERVER['Q_ROOT_PATH']) ? $_SERVER['Q_ROOT_PATH'] : dirname(__FILE__).'/..';

$root_path = preg_replace('/\/+$/', '', $root_path);
if(function_exists('realpath') && @realpath($root_path) !== FALSE){
	define('ROOT_PATH',  @realpath($root_path).'/');
}else{
	define('ROOT_PATH', $root_path.'/');
}

$phar_path = ROOT_PATH.'system.phar';
if (is_file($phar_path)) {
	define('SYS_PATH', 'phar://'.ROOT_PATH.'system.phar/');
}
else {
	define('SYS_PATH', ROOT_PATH.'system/');
}

require SYS_PATH.'core/cli.php';

function clean_cache($object = NULL) {

	$cache = Cache::factory(CGI_DEFAULT_CACHE);
	if ($object->id) {
		$key = $object->cache_name($object->id);
		$cache->remove($key);
	}
	else {
		$cache->flush();
	}
}
