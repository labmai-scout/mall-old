<?php
/*
	定时运行, 未激活未过期未签订协议的供应商，并下架其products
	(hongjie.zhu@2014-11-19)
    usage: SITE_ID=nankai php check_vendor_agreement.php
*/

require 'base.php';


$now = Date::time();
$db = Database::factory();

// 1. 将未签协议的激活用户未激活处理
$version = Config::get('vendor.current_agreement_version');
if (!$version) return;
$vendors_sql = "SELECT vendor.id FROM vendor WHERE (vendor.agreement_version is NULL OR vendor.agreement_version!='{$version}') AND vendor.approve_date LIMIT %d,%d";
$vendors_start = 0;
$vendors_perpage = 500;
$vendors_current_page = 0;

while ($vendors = $db->query($vendors_sql, $vendors_start, $vendors_perpage)->rows()) {
    foreach ($vendors as $v) {
        $vendor = O('vendor', $v->id);
        if (!$vendor->id) continue;
        $vendor->unpublish(T('用户超期未签订协议'));
    }
    $vendors_current_page++;
    $vendors_start = $vendors_current_page * $vendors_perpage;
}
