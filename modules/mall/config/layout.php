<?php
$config['mall.inside_sidebar']['login'] = array(
	'weight' => -100,
	'view' => 'mall:sidebar/login'
);
$config['mall.inside_sidebar']['current_user'] = array(
	'weight' => -100,
	'view' => 'mall:sidebar/current_user'
);
// 商城首页删掉“我想成为供应商”，告知南开设备处供应商注册页面的地址就行，不需要暴露出来公共的注册链接(xiaopei.li@2012-04-28)
// $config['mall.sidebar']['vendor_apply'] = 'mall:sidebar/vendor_apply';

// $config['mall.sidebar']['cart'] = 'mall:sidebar/cart';

$config['mall.sidebar[!mall/list]'][] = 'mall:sidebar/categorys_list';
$config['mall.sidebar[!mall/list]'][] = 'mall:sidebar/buy_new_product';
$config['mall.sidebar[!mall/profile]'][] = 'mall:sidebar/buy_box';
$config['mall.sidebar[!mall/profile]'][] = 'mall:sidebar/cart';

$config['sidebar_admin.menu']['products'] = array(
	'desktop' => array(
		'title' => '产品管理',
		'icon' => '!mall/icons/48/products.png',
		'url' => '!products',
	),
	'icon' => array(
		'title' => '产品管理',
		'icon' => '!mall/icons/32/products.png',
		'url' => '!products',
	),
	'list'=>array(
		'title' => '产品管理',
		'icon' => '!mall/icons/16/products.png',
		'url' => '!products',
	),
);

/* 暂时移除
$config['mall.inside_sidebar']['product'] = array(
	'weight' => 100,
	'view' => 'mall:sidebar/best_product'
);
*/