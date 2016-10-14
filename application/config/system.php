<?php

$config['version'] = '1.14';

$config['tmp_dir'] = '/tmp/mall/';

$config['email_address'] = 'mall@geneegroup.com';
$config['email_name'] = 'LabMai Mall';

if (extension_loaded('memcache')) {
	$config['session_handler'] = 'buildin';
	ini_set('session.save_handler', 'memcache');
	ini_set('session.save_path', 'tcp://localhost:11211');
}
elseif (extension_loaded('memcached')) {
	$config['session_handler'] = 'buildin';
	ini_set('session.save_handler', 'memcached');
	ini_set('session.save_path', 'localhost:11211');
}

$path_prefix = preg_replace('/([^\/])$/', '$1/', dirname($_SERVER['SCRIPT_NAME']));
$config['base_url'] = 'http://'.$_SERVER['HTTP_HOST'].$path_prefix;
$config['script_url'] = 'http://'.$_SERVER['HTTP_HOST'].$path_prefix;

$config['24hour'] = TRUE;
