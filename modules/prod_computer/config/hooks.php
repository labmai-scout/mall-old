<?php

$config['form[admin.product].sections'][] = 'Product_Computer::admin_product_sections';
$config['form[admin.product].validate'][] = 'Product_Computer::admin_product_validate';
$config['form[admin.product].submit'][] = 'Product_Computer::admin_product_submit';
$config['api[vendor::add_product].prepare'][] = 'Product_Computer::api_vendor_add_product_prepare'; 
$config['form[vendor.product].sections'][] = 'Product_Computer::product_sections';
$config['form[vendor.product].submit'][] = 'Product_Computer::product_submit';
$config['form[vendor.product].post_submit'][] = 'Product_Computer::product_post_submit';
$config['form[vendor.product].init'][] = 'Product_Computer::product_init_form';

$config['product_model.saved'][] = 'Product_Computer::product_saved';
$config['mall.product.preview'][] = 'Product_Computer::mall_product_preview';
$config['admin.product.view.info.sections'][] = 'Product_Computer::admin_product_view_info_section';
$config['vendor.product.view.info.sections'][] = 'Product_Computer::product_view_info_section';
$config['sphinx[product].get_matchs'][] = 'Product_Computer::sphinx_product_get_matchs';
