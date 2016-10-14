<?php

//$config['product']['fields']['client_id'] = array('type'=>'varchar(40)', 'null'=>FALSE ,'default'=>'');
//$config['product']['indexes']['client_id'] = array('fields'=>'client_id');


$config['gapper_app_product'] = array(
	'fields' => array(
        'product' => array('type'=>'object', 'oname'=>'product'),
        'client_id'=> array('type'=>'varchar(40)', 'null'=>FALSE ,'default'=>''),
    ),
	'indexes' => array(
		'product' => array('fields' => array('product'), 'type'=>'unique'),
		'client_id' => array('fields' => array('client_id'), 'type'=>'unique'),
	)
);