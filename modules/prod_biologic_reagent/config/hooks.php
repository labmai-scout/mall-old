<?php

$config['form[admin.product].sections'][] = 'Product_Biologic_Reagent::admin_product_sections';
$config['form[admin.product].validate'][] = 'Product_Biologic_Reagent::admin_product_validate';
$config['form[admin.product].submit'][] = 'Product_Biologic_Reagent::admin_product_submit';
$config['api[vendor::add_product].prepare'][] = 'Product_Biologic_Reagent::api_vendor_add_product_prepare'; 
$config['api[vendor::edit_product].prepare'][] = 'Product_Biologic_Reagent::api_vendor_edit_product_prepare'; 
$config['form[vendor.product].sections'][] = 'Product_Biologic_Reagent::product_sections';
$config['form[vendor.product].submit'][] = 'Product_Biologic_Reagent::product_submit';
$config['form[vendor.product].post_submit'][] = 'Product_Biologic_Reagent::product_post_submit';
$config['form[vendor.product].init'][] = 'Product_Biologic_Reagent::product_init_form';
$config['form[admin.product].approve.init'][] = 'Product_Biologic_Reagent::product_approve_init_form';
$config['form[admin.product].approve.submit'][] = 'Product_Biologic_Reagent::product_approve_submit';
$config['form[admin.product].approve.post_submit'] = 'Product_Biologic_Reagent::admin_product_approve_post_submit';

$config['form[admin.product].approve.sections'][] = 'Product_Biologic_Reagent::product_approve_sections';
$config['product_model.saved'][] = 'Product_Biologic_Reagent::product_saved';
$config['mall.product.preview'][] = 'Product_Biologic_Reagent::mall_product_preview';
$config['admin.product.view.info.sections'][] = 'Product_Biologic_Reagent::admin_product_view_info_section';
$config['vendor.product.view.info.sections'][] = 'Product_Biologic_Reagent::product_view_info_section';
$config['sphinx[product].get_matchs'][] = 'Product_Biologic_Reagent::sphinx_product_get_matchs';
