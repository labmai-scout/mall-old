<?php

$config['module[vendor].is_accessible'][] = 'Vendor::is_accessible';

$config['is_allowed_to[以供应商修改].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[以供应商修改工商信息].vendor'][] = 'Vendor::vendor_ACL';

$config['is_allowed_to[管理].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[查看供应商].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[查看财务].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[查看订单].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[查看商品].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[添加商品].vendor'][] = 'Vendor::vendor_ACL';

$config['is_allowed_to[列表文件].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[上传文件].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[下载文件].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[修改文件].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[删除文件].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[创建目录].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[修改目录].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[删除目录].vendor'][] = 'Vendor::vendor_ACL';

$config['is_allowed_to[查看证书].vendor'][] = 'Vendor::vendor_ACL';
$config['is_allowed_to[删除证书].vendor'][] = 'Vendor::vendor_ACL';

$config['is_allowed_to[列表文件].billing_statement'][] = 'Vendor::billing_statement_ACL';
$config['is_allowed_to[上传文件].billing_statement'][] = 'Vendor::billing_statement_ACL';
$config['is_allowed_to[下载文件].billing_statement'][] = 'Vendor::billing_statement_ACL';
$config['is_allowed_to[修改文件].billing_statement'][] = 'Vendor::billing_statement_ACL';
$config['is_allowed_to[删除文件].billing_statement'][] = 'Vendor::billing_statement_ACL';
$config['is_allowed_to[创建目录].billing_statement'][] = 'Vendor::billing_statement_ACL';
$config['is_allowed_to[修改目录].billing_statement'][] = 'Vendor::billing_statement_ACL';
$config['is_allowed_to[删除目录].billing_statement'][] = 'Vendor::billing_statement_ACL';

// 根据当前 $vendor 状态修改 sidebar 内容
$config['layout.vendor.sidebar.menu'][] = 'Vendor::vendor_sidebar_menu';

$config['is_allowed_to[以供应商查看].billing_statement'][] = 'Vendor::billing_statement_ACL';
$config['is_allowed_to[以供应商删除].billing_statement'][] = 'Vendor::billing_statement_ACL';

$config['is_allowed_to[以供应商查看].order'][] = 'Vendor::order_ACL';
$config['is_allowed_to[以供应商取消].order'][] = 'Vendor::order_ACL';
$config['is_allowed_to[申请结算].order'][] = 'Vendor::order_ACL';
$config['is_allowed_to[拒绝退货].order'][] = 'Vendor::order_ACL';
$config['is_allowed_to[以供应商修改].order'][] = 'Vendor::order_ACL';
$config['is_allowed_to[供应商确认订单].order'][] = 'Vendor::order_ACL';
$config['is_allowed_to[确认发货].order'][] = 'Vendor::order_ACL';

$config['is_allowed_to[发表评论].order'][] = 'Vendor::comment_ACL';
$config['is_allowed_to[删除].comment'][] = 'Vendor::comment_ACL';


$config['is_allowed_to[回复].order_item_comment'][] = 'Vendor::order_item_comment_ACL';


$config['is_allowed_to[以供应商查看].product'][] = 'Vendor::product_ACL';
$config['is_allowed_to[以供应商修改].product'][] = 'Vendor::product_ACL';
$config['is_allowed_to[以供应商删除].product'][] = 'Vendor::product_ACL';
$config['is_allowed_to[冻结/解冻].product'][] = 'Vendor::product_ACL';


// get_path for uploading licence
$config['vendor_model.call.get_path'][] = 'Vendor::get_path';
$config['vendor_model.call.fix_path'][] = 'Vendor::fix_path';
$config['vendor_model.saved'] = 'Vendor::vendor_saved';
$config['before_login_by_token'][] = 'Vendor::before_login_by_token';
