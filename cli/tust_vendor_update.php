<?php

//科大南大都有订单的情况 将科大供应商ID更新为南大供应商ID（第二步）
require "base.php";
clean_cache();
$db = Database::factory();

// SITE_ID=tust php tust_vendor_update.php -n 南大的供应商ID -t 科大的供应商ID

$shortopts = 'n:t:';
$longopts = array(
	'nankai:',
	'tust'
	);
$opts = getopt($shortopts, $longopts);

$nankai_id = $opts['n'] ? : $opts['nankai'];
$tust_id = $opts['t'] ? : $opts['tust'];

$flag = false;
echo "billing_bucket 开始更新\n";
$update_sql = "update billing_bucket set vendor_id=$nankai_id where vendor_id=$tust_id";
if (!$db->query($update_sql)) {
	echo "billing_bucket 更新失败\n";
	$flag = true;
} else {
	echo "billing_bucket 更新成功\n";
}

echo "order 开始更新\n";
$update_sql = "update `order` set vendor_id=$nankai_id where vendor_id=$tust_id";
if (!$db->query($update_sql)) {
	echo "order 更新失败\n";
	$flag = true;
} else {
	echo "order 更新成功\n";
}

echo "order_revision 开始更新\n";
$update_sql = "update order_revision set vendor_id=$nankai_id where vendor_id=$tust_id";
if (!$db->query($update_sql)) {
	echo "order_revision 更新失败\n";
	$flag = true;
} else {
	echo "order_revision 更新成功\n";
}

echo "billing_statement 开始更新\n";
$update_sql = "update billing_statement set vendor_id=$nankai_id where vendor_id=$tust_id";
if (!$db->query($update_sql)) {
	echo "billing_statement 更新失败\n";
	$flag = true;
} else {
	echo "billing_statement 更新成功\n";
}

echo "vendor_scope 开始更新\n";
$update_sql = "update vendor_scope set vendor_id=$nankai_id where vendor_id=$tust_id";
if (!$db->query($update_sql)) {
	echo "vendor_scope 更新失败\n";
	$flag = true;
} else {
	echo "vendor_scope 更新成功\n";
}

echo "product 开始更新\n";
$update_sql = "update product set vendor_id=$nankai_id where vendor_id=$tust_id";
if (!$db->query($update_sql)) {
	echo "product 更新失败\n";
	$flag = true;
} else {
	echo "product 更新成功\n";
}

echo "product_revision 开始更新\n";
$update_sql = "update product_revision set vendor_id=$nankai_id where vendor_id=$tust_id";
if (!$db->query($update_sql)) {
	echo "product_revision 更新失败\n";
	$flag = true;
} else {
	echo "product_revision 更新成功\n";
}

if (!$flag) {
	echo "全部更新成功.\n";
} else {
	echo "更新失败.\n";
}
echo "done.\n";