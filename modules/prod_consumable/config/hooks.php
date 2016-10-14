<?php

$config['form[admin.product].sections'][] = 'Product_Consumable::admin_product_sections';
$config['form[admin.product].validate'][] = 'Product_Consumable::admin_product_validate';
$config['form[admin.product].submit'][] = 'Product_Consumable::admin_product_submit';
$config['api[vendor::add_product].prepare'][] = 'Product_Consumable::api_vendor_add_product_prepare'; 
$config['api[vendor::edit_product].prepare'][] = 'Product_Consumable::api_vendor_edit_product_prepare'; 
$config['form[vendor.product].submit'][] = 'Product_Consumable::product_submit';
$config['form[vendor.product].sections'][] = 'Product_Consumable::product_sections';
$config['form[vendor.product].init'][] = 'Product_Consumable::product_init_form';
$config['form[vendor.product].post_submit'][] = 'Product_Consumable::product_post_submit';
$config['form[admin.product].approve.init'][] = 'Product_Consumable::product_approve_init_form';
$config['form[admin.product].approve.post_submit'] = 'Product_Consumable::admin_product_approve_post_submit';
$config['form[admin.product].approve.sections'][] = 'Product_Consumable::product_approve_sections';
$config['product_model.saved'][] = 'Product_Consumable::product_saved';
$config['mall.product.preview'][] = 'Product_Consumable::mall_product_preview';
$config['admin.product.view.info.sections'][] = 'Product_Consumable::admin_product_view_info_section';
$config['vendor.product.view.info.sections'][] = 'Product_Consumable::product_view_info_section';
$config['sphinx[product].get_matchs'][] = 'Product_Consumable::sphinx_product_get_matchs';
