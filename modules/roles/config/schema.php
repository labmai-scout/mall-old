<?php

$config['role'] = array(
	'fields' => array(
		'name'=>array('type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''),
		'weight'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0),
	),
	'indexes' =>array(
			'weight'=>array('fields'=>array('weight')),
	),
);

