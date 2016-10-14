<?php

$config['sphinx[product].get_matchs'][] = 'Product_Reagent::sphinx_product_get_matchs';
$config['sphinx[product].get_extra_index'][] = 'Product_Reagent::sphinx_product_extra_index';
// !admin 添加/修改 product
$config['form[admin.product].sections'][] = 'Product_Reagent::admin_product_sections';
$config['form[admin.product].validate'][] = 'Product_Reagent::admin_product_validate';
$config['form[admin.product].submit'][] = 'Product_Reagent::admin_product_submit';
$config['form[admin.product].post_submit'][] = 'Product_Reagent::admin_product_post_submit';

$config['api[vendor::add_product].prepare'][] = 'Product_Reagent::api_vendor_add_product_prepare';
$config['api[vendor::edit_product].prepare'][] = 'Product_Reagent::api_vendor_edit_product_prepare';
// !admin 列表 product
// deprecated !admin/product/products 已换为全文搜索, 以下不再适用 (xiaopei.li@2012-08-25)
/*
$config['admin_products_table.prerender'][] = 'Product_Reagent::admin_product_search_form';
$config['form[admin.product].search'][] = 'Product_Reagent::admin_product_search';
*/

// !admin 查看 reagent
$config['admin.product.view.info.sections'][] = 'Product_Reagent::admin_product_view_info_section';


$config['vendor.product.view.info.sections'][] = 'Product_Reagent::product_view_info_section';

// !admin 修改 vendor scope
$config['admin.vendor.sub_scope.reagent'][] = 'Product_Reagent::admin_vendor_sub_scope';

// !vendor 修改 vendor scope
$config['vendor.vendor.sub_scope.reagent'][] = 'Product_Reagent::vendor_vendor_sub_scope';

// 显示 vendor scope
$config['vendor.view.sub_scope.reagent'][] = 'Product_Reagent::vendor_view_sub_scope';

// !vendor 添加/修改 product
$config['form[vendor.product].init'][] = 'Product_Reagent::product_init_form';
$config['form[admin.product].approve.init'][] = 'Product_Reagent::product_approve_init_form';
$config['form[vendor.product].sections'][] = 'Product_Reagent::product_sections';
$config['form[admin.product].approve.sections'][] = 'Product_Reagent::product_approve_sections';
$config['form[vendor.product].submit'][] = 'Product_Reagent::product_submit';
$config['form[vendor.product].post_submit'][] = 'Product_Reagent::vendor_product_post_submit';
$config['form[admin.product].approve.post_submit'] = 'Product_Reagent::admin_product_approve_post_submit';

$config['form[admin.product].approve.submit'][] = 'Product_Reagent::product_approve_submit';

$config['product_model.saved'][] = 'Product_Reagent::product_saved';

$config['product_model.call.order_approval_required'][] = 'Product_Reagent::product_order_approval_required';

$config['mall.product.preview'][] = 'Product_Reagent::mall_product_preview';

$config['order_item.product.table.extra_view'][] = 'Product_Reagent::order_item_product_table_extra_view';

// 在购物时增加易制毒判断
$config['product.get_avoid_buy_msg'][] = 'Product_Reagent::buy_easymade_toxic';
/*
// event 有 bug, 当有多个 hook 为 array 形式时, 除第一个 hook 外无法触发 (xiaopei.li@2012-08-23)
$config['product.get_avoid_buy_msg'][] = array(
	'callback' => 'Product_Reagent::buy_easymade_toxic',
	'weight' => '100', // 易制毒检查应在最后(待商家/商家资质都无问题才做)
	);
*/

// 在管理面板增加易制毒
$config['controller[!admin/admin/index].ready'][] = 'Product_Reagent::admin_easymade_toxic_setup';

$config['product.list_get_extra_panel_buttons'][] = 'Product_Reagent::vendor_extra_panel_buttons';
$config['mall_search_rank_view'] = 'Product_Reagent::mall_search_rank_view';



//小类别信息存储在虚属性中，无法批量操作，所以上架和下架的处理先注释了

$config['vendor_scope.expired'][] = 'Product_Reagent::vendor_scope_expired';
/*
//商品审核，如果资质未过期，则批量上架
$config['vendor_scope.approve'] = [
    'callback'=>'Product_Reagent::vendor_scope_approve',
    'weight' => -1,
];
*/
