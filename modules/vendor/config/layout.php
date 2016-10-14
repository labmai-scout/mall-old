<?php
$config['vendor.sidebar']['current_user'] = array(
	'view' => 'vendor:sidebar/current_user',
	'weight' => -100
);

$config['vendor.sidebar']['menu'] = array(
	'view' => 'vendor:sidebar/menu',
	'weight' => -50
);

$config['vendor.sidebar']['admin'] = array(
    'view'=> 'people:sidebar/admin',
    'weight'=> -30
);

$config['vendor.sidebar']['customers'] = array(
    'view'=> 'people:sidebar/customers',
    'weight'=> 0
);

$config['vendor.sidebar.menu']['profile'] = array(
	'icon' => array(
		'title' => '企业信息',
		'icon' => '!vendor/icons/32/profile.png',
		'url' => '!vendor/profile',
	),
	'list'=>array(
		'title' => '企业信息',
		'icon' => '!vendor/icons/16/profile.png',
		'url' => '!vendor/profile',
	),
);

/* 暂时移除
$config['mall.inside_sidebar']['vendor'] = array(
	'view' => 'vendor:sidebar/vendors',
	'weight' => 200
);
*/