<?php

$config['form[admin.product].sections'][] = 'Product_Service::admin_product_sections';
$config['form[admin.product].validate'][] = 'Product_Service::admin_product_validate';
$config['form[admin.product].submit'][] = 'Product_Service::admin_product_submit';
$config['form[vendor.product].sections'][] = 'Product_Service::product_sections';
$config['form[vendor.product].submit'][] = 'Product_Service::product_submit';
$config['form[vendor.product].post_submit'][] = 'Product_Service::product_post_submit';
$config['form[vendor.product].init'][] = 'Product_Service::product_init_form';

$config['form[admin.product].approve.init'][] = 'Product_Service::product_approve_init_form';
$config['form[admin.product].approve.submit'][] = 'Product_Service::product_approve_submit';
$config['form[admin.product].approve.post_submit'] = 'Product_Service::admin_product_approve_post_submit';

$config['form[admin.product].approve.sections'][] = 'Product_Service::product_approve_sections';

$config['product_model.saved'][] = 'Product_Service::product_saved';
$config['mall.product.preview'][] = 'Product_Service::mall_product_preview';
$config['admin.product.view.info.sections'][] = 'Product_Service::admin_product_view_info_section';
$config['vendor.product.view.info.sections'][] = 'Product_Service::product_view_info_section';
$config['api[vendor::add_product].prepare'][] = 'Product_Service::api_vendor_add_product_prepare';

$config['product.get_avoid_buy_msg'][] = 'Product_Service::buy_service_product';

