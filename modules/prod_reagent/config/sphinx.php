<?php
/*
* group_search 是name、manufacturer、catalog_no的合集，用于 group_by
*/
$config['product_reagent']['fields']['name'] = array('type' => 'rt_field');
$config['product_reagent']['fields']['group_name'] = array('type' => 'rt_attr_string');
$config['product_reagent']['fields']['spec'] = array('type'=> 'rt_field');
$config['product_reagent']['fields']['package'] = array('type'=> 'rt_field');
$config['product_reagent']['fields']['catalog_no'] = array('type' => 'rt_field');
$config['product_reagent']['fields']['group_search'] = array('type' => 'rt_attr_string');
$config['product_reagent']['fields']['description'] = array('type' => 'rt_field');
$config['product_reagent']['fields']['keywords'] = array('type' => 'rt_field');
$config['product_reagent']['fields']['vendor_name'] = array('type'=> 'rt_field');
$config['product_reagent']['fields']['category'] = array('type' => 'rt_attr_multi_64');
$config['product_reagent']['fields']['vendor_id'] = array('type' => 'rt_attr_bigint');
$config['product_reagent']['fields']['is_frozen'] = array('type'=> 'rt_attr_uint');
$config['product_reagent']['fields']['is_sale'] = array('type'=> 'rt_attr_uint');
$config['product_reagent']['fields']['supply_time'] = array('type' => 'rt_attr_uint');
$config['product_reagent']['fields']['stock_status'] = array('type' => 'rt_attr_uint');
$config['product_reagent']['fields']['vendor_note'] = array('type'=> 'rt_field');
$config['product_reagent']['fields']['price'] = array('type'=> 'rt_attr_float');
$config['product_reagent']['fields']['publish_date'] = array('type'=> 'rt_attr_bigint');
$config['product_reagent']['fields']['approve_date'] = array('type'=> 'rt_attr_bigint');
$config['product_reagent']['fields']['ctime'] = array('type' => 'rt_attr_bigint');
$config['product_reagent']['fields']['vendor_short_name'] = array('type' => 'rt_field');
$config['product_reagent']['fields']['vendor_short_name_abbr'] = array('type' => 'rt_attr_string');
$config['product_reagent']['fields']['manufacturer'] = array('type'=> 'rt_field');
$config['product_reagent']['fields']['manufacturer_abbr'] = array('type' => 'rt_attr_string');
$config['product_reagent']['fields']['brand'] = array('type' => 'rt_field');
$config['product_reagent']['fields']['group_brand'] = array('type' => 'rt_attr_string');
$config['product_reagent']['fields']['price_range'] = array('type'=> 'rt_attr_bigint');
$config['product_reagent']['fields']['sales'] = array('type'=>'rt_attr_bigint');
$config['product_reagent']['fields']['weight'] = array('type' => 'rt_attr_bigint');
$config['product_reagent']['fields']['vendor_weight'] = array('type' => 'rt_attr_bigint');
$config['product_reagent']['fields']['valid_fields'] = array('type'=>'rt_attr_bigint');
/* extra */
$config['product_reagent']['fields']['rgt_en_name'] = array('type'=> 'rt_field');
$config['product_reagent']['fields']['cas_no'] =  array('type'=> 'rt_field');
$config['product_reagent']['fields']['alias'] = array('type'=> 'rt_field');
$config['product_reagent']['fields']['reagent_formula'] = array('type'=> 'rt_field');
$config['product_reagent']['fields']['reagent_mw'] = array('type'=> 'rt_field');
$config['product_reagent']['fields']['rgt_type'] = array('type'=> 'rt_attr_uint');
$config['product_reagent']['fields']['rgt_danger_class'] = array('type'=> 'rt_attr_uint');
$config['product_reagent']['fields']['expire_date'] = array('type' => 'rt_attr_bigint');

//排序 TODO 放在这里 get_sphinx 会去抓，要调整
$config['product_reagent']['extra_weight'][] = array('index'=>'rgt_en_name', 'weight'=> 60);
$config['product_reagent']['extra_weight'][] = array('index'=>'rgt_aliases', 'weight'=> 50);
$config['product_reagent']['extra_weight'][] = array('index'=>'reagent_formula', 'weight'=> 40);
$config['product_reagent']['extra_weight'][] = array('index'=>'rgt_type', 'weight'=> 30);
$config['product_reagent']['extra_weight'][] = array('index'=>'cas_no', 'weight'=> 70);
$config['product_reagent']['extra_weight'][] = array('index'=>'reagent_mw', 'weight'=> 20);
$config['product_reagent']['extra_weight'][] = array('index'=>'rgt_danger_class', 'weight'=> 10);
