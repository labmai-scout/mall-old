<?php

$config['form[admin.product].sections'][] = 'Product_Small_Device::admin_product_sections';
$config['form[admin.product].validate'][] = 'Product_Small_Device::admin_product_validate';
$config['form[admin.product].submit'][] = 'Product_Small_Device::admin_product_submit';
$config['api[vendor::add_product].prepare'][] = 'Product_Small_Device::api_vendor_add_product_prepare';
$config['form[vendor.product].sections'][] = 'Product_Small_Device::product_sections';
$config['form[vendor.product].submit'][] = 'Product_Small_Device::product_submit';
$config['form[vendor.product].post_submit'][] = 'Product_Small_Device::product_post_submit';
$config['form[vendor.product].init'][] = 'Product_Small_Device::product_init_form';
$config['form[admin.product].approve.init'][] = 'Product_Small_Device::product_approve_init_form';
$config['form[admin.product].approve.submit'][] = 'Product_Small_Device::product_approve_submit';
$config['form[admin.product].approve.post_submit'] = 'Product_Small_Device::admin_product_approve_post_submit';

$config['form[admin.product].approve.sections'][] = 'Product_Small_Device::product_approve_sections';

$config['product_model.saved'][] = 'Product_Small_Device::product_saved';
$config['mall.product.preview'][] = 'Product_Small_Device::mall_product_preview';
$config['admin.product.view.info.sections'][] = 'Product_Small_Device::admin_product_view_info_section';
$config['vendor.product.view.info.sections'][] = 'Product_Small_Device::product_view_info_section';
$config['sphinx[product].get_matchs'][] = 'Product_Small_Device::sphinx_product_get_matchs';