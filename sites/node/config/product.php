<?php
// 商品文件上传目录
$config['upload_path'] = '/data/product_upload/';

$config['reagent.options'] = array(
	'product_list_fields' => array(
		'manufacturer' => '生产商',
		'catalog_no' => '目录号',
		'spec' => '规格',
		'package' => '包装',
	),
	'product_view_fields' => array(
		'manufacturer' => '生产商',
		'catalog_no' => '目录号',
		'rgt_en_name' => '英文名',
		'cas_no' => 'CAS 号',
		'reagent_formula' => '分子式',
		'reagent_mw' => '分子量',
		'rgt_aliases' => '别名',
		'keywords' => '关键字',
		'rgt_danger_class' => '危险品分类',
		),
	'product_view_supply_list_fields' => array(
		//'manufacturer' => '生产商',
		//'catalog_no' => '目录号',
		),
	/*
	'vendor_product_view_fields' => array(
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
