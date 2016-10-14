<?php
$config['types']['biologic_reagent'] = '生物试剂';
$config['types.biologic_reagent.weight'] = 20;
$config['types_sphinx_indexes']['biologic_reagent'] = 30;
$config['max_product']['biologic_reagent'] = 10;

$config['biologic_reagent.options'] = array(
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
		'transport_cond' => '运输条件',
		'storage_cond' => '保存条件'
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