<?php

$config['admin.sidebar']['current_user'] = array(
	'view'=>'admin:sidebar/current_user',
	'weight'=> -100
);

$config['admin.sidebar']['menu'] = array(
	'view'=>'admin:sidebar/menu',
	'weight'=> -50
);

$config['admin.sidebar']['vendors'] = array(
    'view'=>'people:sidebar/vendors',
    'weight'=> -30
);

$config['admin.sidebar']['customers'] = array(
    'view'=> 'people:sidebar/customers',
    'weight'=> 0
);

// 按权限显示 sidebar 模块 (xiaopei.li@2012-06-20)
$config['admin.sidebar.menu.order'] = array(
	'title' => '订单管理',
	'icon' => '!admin/icons/16/orders.png',
	'url' => '!admin/order',
	'notif_callback' => 'Admin::order_notif_callback',
);

$config['admin.sidebar.menu.product'] = array(
	'title' => '商品管理',
	'icon' => '!admin/icons/16/product.png',
	'url' => '!admin/product/products',
	'reminder_callback' => 'Admin::product_reminder_callback',
);

$config['admin.sidebar.menu.transfer'] = array(
	'title' => '付款管理',
	'icon' => '!admin/icons/16/transfer.png',
	'url' => '!admin/transfer'
);

$config['admin.sidebar.menu.financial'] = array(
	'title' => '结算管理',
	'icon' => '!admin/icons/16/financial.png',
	'url' => '!admin/financial',
	'notif_callback' => 'Admin::payment_notif_callback',
);

$config['admin.sidebar.menu.vendor'] = array(
	'title' => '供应商管理',
	'icon' => '!admin/icons/16/vendor.png',
	'url' => '!admin/vendor',
	'notif_callback' => 'Admin::vendor_notif_callback',
);

$config['admin.sidebar.menu.customer'] = array(
	'title' => '买方管理',
	'icon' => '!admin/icons/16/customer.png',
	'url' => '!admin/customer',
);

$config['admin.sidebar.menu.user'] = array(
	'title' => '登录管理',
	'icon' => '!admin/icons/16/user.png',
	'url' => '!admin/user',
);

$config['admin.sidebar.menu.home'] = array(
	'title' => '首页',
	'icon' => '!admin/icons/16/home.png',
	'url' => '!admin/node',
	'target' => '_blank',
);

// 10362 【Win 8，firfox】【南通大学】0.1.0，mall-old，买方管理，添加买方功能去掉
/*
$config['admin.sidebar.menu.system'] = array(
	'title' => '系统设置',
	'icon' => '!admin/icons/16/admin.png',
	'url' => '!admin/admin',
	'weight' => 100,
);
 */
