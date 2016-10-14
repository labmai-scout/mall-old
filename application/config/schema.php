<?php
$config['recovery'] = array(
	'fields' => array(
		'key' => array('type'=>'varchar(150)', 'null'=>FALSE),
		'user' => array('type'=>'object', 'oname'=>'user'),
		'overdue' => array('type'=>'int', 'null'=>FALSE, 'default'=>0)
	),
	'indexes' => array(
		'key' => array('fields'=>array('key'), 'type'=>'unique'),
		'user' => array('fields'=>array('user')),
		'overdue' => array('fields'=>array('overdue'))
	),
);
