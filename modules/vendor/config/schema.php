<?php

//vendor_api 用于存储vendor api的相关数据

$config['vendor_api'] = array(
	'fields' => array(
		'vendor' => array('type'=>'object', 'oname'=>'vendor'), //vendor
		'client_id' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''), //vendor api调用时用于对应的name
		'client_secret' => array('type'=>'text', 'null' => FALSE, 'default'=>''), //密码
	),
	'indexes' => array(
		'client_id' => array('type'=>'unique', 'fields'=>array('client_id')),
	)
);
