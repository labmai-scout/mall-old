<?php

$config['product_consumable']['fields']['name'] = array('type' => 'rt_field');
$config['product_consumable']['fields']['group_name'] = array('type' => 'rt_attr_string');
$config['product_consumable']['fields']['spec'] = array('type'=> 'rt_field');
$config['product_consumable']['fields']['package'] = array('type'=> 'rt_field');
$config['product_consumable']['fields']['catalog_no'] = array('type' => 'rt_field');
$config['product_consumable']['fields']['group_search'] = array('type' => 'rt_attr_string');
$config['product_consumable']['fields']['description'] = array('type' => 'rt_field');
$config['product_consumable']['fields']['keywords'] = array('type' => 'rt_field');
$config['product_consumable']['fields']['vendor_name'] = array('type'=> 'rt_field');
$config['product_consumable']['fields']['category'] = array('type' => 'rt_attr_multi_64');
$config['product_consumable']['fields']['vendor_id'] = array('type' => 'rt_attr_bigint');
$config['product_consumable']['fields']['is_frozen'] = array('type'=> 'rt_attr_uint');
$config['product_consumable']['fields']['is_sale'] = array('type'=> 'rt_attr_uint');
$config['product_consumable']['fields']['supply_time'] = array('type' => 'rt_attr_uint');
$config['product_consumable']['fields']['stock_status'] = array('type' => 'rt_attr_uint');
$config['product_consumable']['fields']['vendor_note'] = array('type'=> 'rt_field');
$config['product_consumable']['fields']['price'] = array('type'=> 'rt_attr_float');
$config['product_consumable']['fields']['publish_date'] = array('type'=> 'rt_attr_bigint');
$config['product_consumable']['fields']['approve_date'] = array('type'=> 'rt_attr_bigint');
$config['product_consumable']['fields']['ctime'] = array('type' => 'rt_attr_bigint');
$config['product_consumable']['fields']['vendor_short_name'] = array('type' => 'rt_field');
$config['product_consumable']['fields']['vendor_short_name_abbr'] = array('type' => 'rt_attr_string');
$config['product_consumable']['fields']['manufacturer'] = array('type'=> 'rt_field');
$config['product_consumable']['fields']['manufacturer_abbr'] = array('type' => 'rt_attr_string');
$config['product_consumable']['fields']['brand'] = array('type' => 'rt_field');
$config['product_consumable']['fields']['group_brand'] = array('type' => 'rt_attr_string');
$config['product_consumable']['fields']['price_range'] = array('type'=> 'rt_attr_bigint');
$config['product_consumable']['fields']['sales'] = array('type'=>'rt_attr_bigint');
$config['product_consumable']['fields']['weight'] = array('type' => 'rt_attr_bigint');
$config['product_consumable']['fields']['vendor_weight'] = array('type' => 'rt_attr_bigint');
$config['product_consumable']['fields']['expire_date'] = array('type' => 'rt_attr_bigint');
$config['product_consumable']['fields']['valid_fields'] = array('type'=>'rt_attr_bigint');
/* extra */
/*
* 发现1.7的版本增加了耗材的英文名, 暂时注释掉
* $config['product_consumable']['fields']['consumable_en_name'] = array('type' => 'rt_field');
*/
