<?php
$config['types']['computer'] = '电脑整机';
$config['types.computer.weight'] = 60;
$config['types_sphinx_indexes']['computer'] = 40;
$config['max_product']['computer'] = 10;

$config['computer.options'] = array(
	'product_list_fields' => array(
		'manufacturer' => '生产商',
		'model' => '型号',
		'memory' => '内存',
		'cpu' => 'CPU'
	),
	'product_view_fields' => array(
		'manufacturer' => '生产商',
		'model' => '型号',
		'memory' => '内存',
		'cpu' => 'CPU',
		'display' => '显示器',
		'video_memory' => '显存',
		'service_call' => '服务电话'

	)
);
