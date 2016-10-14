<?php

$config['layout.admin.sidebar_menu'][] = 'Admin::layout_admin_sidebar_menu';
$config['module[admin].is_accessible'][] = "Admin::is_accessible";


// vendor 相关权限
$config['is_allowed_to[删除].vendor'][] = 'Admin::vendor_ACL';

$config['is_allowed_to[列表文件].vendor'][] = 'Admin::vendor_ACL';
$config['is_allowed_to[上传文件].vendor'][] = 'Admin::vendor_ACL';
$config['is_allowed_to[下载文件].vendor'][] = 'Admin::vendor_ACL';
$config['is_allowed_to[修改文件].vendor'][] = 'Admin::vendor_ACL';
$config['is_allowed_to[删除文件].vendor'][] = 'Admin::vendor_ACL';
$config['is_allowed_to[创建目录].vendor'][] = 'Admin::vendor_ACL';
$config['is_allowed_to[修改目录].vendor'][] = 'Admin::vendor_ACL';
$config['is_allowed_to[删除目录].vendor'][] = 'Admin::vendor_ACL';
$config['is_allowed_to[批量审批商家商品].vendor'][] = 'Admin::vendor_ACL';

$config['is_allowed_to[删除].product'][] = 'Admin::product_ACL';
$config['is_allowed_to[添加].product'][] = 'Admin::product_ACL';
$config['is_allowed_to[修改].product'][] = 'Admin::product_ACL';

$config['is_allowed_to[添加].customer'][] = 'Admin::customer_ACL';
$config['is_allowed_to[删除].customer'][] = 'Admin::customer_ACL';
$config['is_allowed_to[修改].customer'][] = 'Admin::customer_ACL';

$config['is_allowed_to[列表].order'][] = 'Admin::order_ACL';
$config['is_allowed_to[查看].order'][] = 'Admin::order_ACL';
$config['is_allowed_to[审核].order'][] = 'Admin::order_ACL';
$config['is_allowed_to[取消].order'][] = 'Admin::order_ACL';

$config['is_allowed_to[管理].transfer_statement'][] = 'Admin::transfer_statement_ACL';

$config['is_allowed_to[管理].billing_statement'][] = 'Admin::billing_statement_ACL';

$config['is_allowed_to[发表评论].order'][] = 'Admin::comment_ACL';
$config['is_allowed_to[删除].comment'][] = 'Admin::comment_ACL';


$config['order_is_drafted'][] = 'Admin::order_is_drafted';
$config['order_is_confirmed'][] = 'Admin::order_is_confirmed';
$config['order_is_approved'][] = 'Admin::order_is_approved';
$config['order_is_canceled'][] = 'Admin::order_is_canceled';
$config['order_is_delivered'][] = 'Admin::order_is_delivered';
$config['order_is_edited'][] = 'Admin::order_is_edited';
$config['order_is_return_approved'][] = 'Admin::order_is_return_approved';
$config['order_is_returning'][] = 'Admin::order_is_returning';
$config['order_is_reject_to_returning'][] = 'Admin::order_is_reject_to_returning';
$config['order_is_recovered'][] = 'Admin::order_is_recovered';
$config['order_is_transferred'][] = 'Admin::order_is_transferred';
$config['order_is_transfer_failed'][] = 'Admin::order_is_transfer_failed';
$config['order_is_received'][] = 'Admin::order_is_received';

// $config['order_need_vendor_confirm'][] = 'Admin::order_need_vendor_confirm';
$config['order_need_customer_confirm'][] = 'Admin::order_need_customer_confirm';

$config['order_is_confirmed_by_vendor'][] = 'Admin::order_is_confirmed_by_vendor';
$config['comment_model.saved'][] =  'Admin::comment_saved';
$config['product_model.deleted'][] = 'Admin::product_deleted';
$config['product_model.saved'][] = 'Admin::product_saved';
$config['vendor_scope_model.saved'][] = 'Admin::vendor_scope_saved';


// TODO vendor 更新索引还需考虑 vendor_scope:
// 1. 如果在 vendor_scope_saved 时 update, 可能造成多次浪费更新?
// 2. 或者是否要在 vendor_saved 的判断条件中查找更合适?
// $config['vendor_scope_model.saved'][] = 'Admin::vendor_saved';
// $config['vendor_scope_model.saved'][] = 'Admin::vendor_scope_saved';
$config['order_model.deleted'][] = 'Admin::order_deleted';
$config['order_model.saved'][] = 'Admin::order_saved';
// TODO order 更新索引还需考虑 order_item
// 但 order_item 修改后, 一定会调用 $order->update_price()->save
// $config['order_item.saved'][] = 'Admin::order_saved';
// $config['order_item.saved'][] = 'Admin::order_item_saved';


// TODO vendor/customer 名字更新后应更新关联的 order/product 的索引


$config['vendor_scope.expired'][] = 'Admin::vendor_scope_expired';


// 审查商品时的检查
// product 通过审核时, 要检查 vendor
$config['product.get_not_allow_approve_msg'][] = array(
	'callback' => 'Admin::approve_product_check_vendor',
	'weight' => '-1', // 最先检查 vendor
	);
// 还要检查 vendor_scope
$config['product.get_not_allow_approve_msg'][] = 'Admin::approve_product_check_vendor_scope';

// 购买商品时的检查
// 暂与审查相同, 故 hook 到了相同函数, 以后若有需要可分离为与审查不同的函数
// (xiaopei.li@2012-08-18)
$config['product.get_avoid_buy_msg'][] = array(
	'callback' => 'Admin::approve_product_check_vendor',
	'weight' => '-1', // 最先检查 vendor
	);
$config['product.get_avoid_buy_msg'][] = 'Admin::approve_product_check_vendor_scope';

//$config['order_item_model.saved'][] = 'Admin::order_item_saved';

//商品审核，如果资质未过期，则批量上架
$config['vendor_scope.approve'] = 'Admin::vendor_scope_approve';
