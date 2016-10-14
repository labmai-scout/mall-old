<?php

$config['prefix'] = 'mall_' . SITE_ID . '_';
$config['dir'] = 'mall_' . SITE_ID;

$config['product']['fields']['name'] = array('type' => 'rt_field');
$config['product']['fields']['group_name'] = array('type' => 'rt_attr_string');
$config['product']['fields']['catalog_no'] = array('type' => 'rt_field');
$config['product']['fields']['group_search'] = array('type' => 'rt_attr_string');
$config['product']['fields']['description'] = array('type' => 'rt_field');
$config['product']['fields']['keywords'] = array('type' => 'rt_field');
$config['product']['fields']['vendor_name'] = array('type'=> 'rt_field');
$config['product']['fields']['category'] = array('type' => 'rt_attr_multi_64');
$config['product']['fields']['vendor_id'] = array('type' => 'rt_attr_bigint');
$config['product']['fields']['is_frozen'] = array('type'=> 'rt_attr_uint');
$config['product']['fields']['is_sale'] = array('type'=> 'rt_attr_uint');
$config['product']['fields']['vendor_note'] = array('type'=> 'rt_field');
$config['product']['fields']['price'] = array('type'=> 'rt_attr_float');
$config['product']['fields']['publish_date'] = array('type'=> 'rt_attr_bigint');
$config['product']['fields']['approve_date'] = array('type'=> 'rt_attr_bigint');
$config['product']['fields']['ctime'] = array('type' => 'rt_attr_bigint');
$config['product']['fields']['vendor_short_name'] = array('type' => 'rt_field');
$config['product']['fields']['vendor_short_name_abbr'] = array('type' => 'rt_attr_string');
$config['product']['fields']['manufacturer'] = array('type'=> 'rt_field');
$config['product']['fields']['manufacturer_abbr'] = array('type' => 'rt_attr_string');
$config['product']['fields']['supply_time'] = array('type' => 'rt_attr_uint');
$config['product']['fields']['stock_status'] = array('type' => 'rt_attr_uint');
$config['product']['fields']['spec'] = array('type'=> 'rt_field');
$config['product']['fields']['package'] = array('type'=> 'rt_field');
$config['product']['fields']['extra'] = array('type' => 'rt_field');
$config['product']['fields']['brand'] = array('type' => 'rt_field');
$config['product']['fields']['group_brand'] = array('type' => 'rt_attr_string');
$config['product']['fields']['price_range'] = array('type'=> 'rt_attr_bigint');
$config['product']['fields']['sales'] = array('type'=>'rt_attr_bigint');
$config['product']['fields']['weight'] = array('type' => 'rt_attr_bigint');
$config['product']['fields']['vendor_weight'] = array('type' => 'rt_attr_bigint');
$config['product']['fields']['expire_date'] = array('type'=> 'rt_attr_bigint');
$config['product']['fields']['type'] = array('type' => 'rt_attr_uint');
$config['product']['fields']['valid_fields'] = array('type'=>'rt_attr_bigint');
