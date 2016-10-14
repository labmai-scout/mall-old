#!/usr/bin/env php
<?php
/*
	定时运行, 下架过期 vendor_scope对应的vendor_product
	(xiaopei.li@2012-05-15)
*/

require 'base.php';


$now = Date::time();
$db = Database::factory();


// 2. 下架过准营期的 vendor_product
$num = 500;
$page = 0;
$start = 0;
$vs2e_sql = "SELECT vendor_scope.id FROM vendor_scope LEFT JOIN vendor ON (vendor_scope.vendor_id = vendor.id ) WHERE vendor.approve_date >0 and vendor_scope.expire_date<{$now} limit %d,%d";
//$vendor_scopes_to_expire = Q("vendor[approve_date>0] vendor_scope[expire_date=1~$now]");
while ($vendor_scopes_to_expires = $db->query($vs2e_sql, $start, $num)->rows()) {
	foreach ($vendor_scopes_to_expires as $v) {
		$vs2e = O('vendor_scope', $v->id);
		if(!$vs2e->id) continue;
		$vs2e->expire();

		//将设置为0的scope删除
		if($vs2e->expire_date == 0) $vs2e->delete();
	}
	$page++;
	$start = $page * $num;
}
