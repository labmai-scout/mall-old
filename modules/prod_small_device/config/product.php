<?php
//$config['types']['small_device'] = '小型仪器';
$config['types.small_device.weight'] = 60;
$config['types_sphinx_indexes']['small_device'] = 50;
$config['max_product']['small_device'] = 10;

$config['small_device.options'] = array(
	/*'merge_criterias' => array( 'name' => '商品名称' ),*/
	'product_list_fields' => array(
		'manufacturer' => '生产商',
		'catalog_no' => '目录号',
	),
	'product_view_fields' => array(
		'manufacturer' => '生产商',
		'catalog_no' => '货号',
		'spec' => '规格',
		'model' => '型号',
		'origin' => '产地',
		'warranty_period' => '质保期',
		'service_no' => '服务电话',

	),
	'product_view_supply_list_fields' => array(
		'package' => '包装'
		//'manufacturer' => '生产商',
		//'catalog_no' => '目录号',
	),
	/*
	'product_view_fields' => array(
		'manufacturer' => '生产商',
		'catalog_no' => '目录号',
		'spec' => '规格',
		'package' => '包装',
		'model' => '型号',
		'keywords' => '关键字',
		'rgt_en_name' => '英文名',
		'cas_no' => 'CAS 号',
		'reagent_formula' => '分子式',
		'reagent_mw' => '分子量',
		'rgt_aliases' => '别名',
		'rgt_danger_class' => '危险品分类',
		),
	*/
);
