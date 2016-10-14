<?php

$config['customer.sidebar']['current_user'] = array(
	'view' => 'customer:sidebar/current_user',
	'weight' => -100
);

$config['customer.sidebar']['customer'] = array(
	'view' => 'customer:sidebar/customer_menu',
	'weight' => -50
);


$config['customer.sidebar']['admin'] = array(
    'view'=> 'people:sidebar/admin',
    'weight'=> -30
);

$config['customer.sidebar']['vendors'] = array(
    'view'=>'people:sidebar/vendors',
    'weight'=> -10
);

$config['customer.sidebar']['menu'] = array(
	'view' => 'customer:sidebar/menu',
	'weight' => 0
);
