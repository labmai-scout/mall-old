#!/usr/bin/env php
<?php
/*
 * 修正数据库结构错误
 */

$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;

if (SITE_ID != 'nankai') return FALSE;

$db = Database::factory();
$return = $db->query("ALTER TABLE deliver_address ADD COLUMN `email` varchar(50) NOT NULL DEFAULT '', ADD COLUMN `postcode` varchar(50) NOT NULL DEFAULT ''");
if ($return) {
	echo '更改 deliver_address 的 字段成功';
}
else {
	echo '更改 deliver_address 的 字段失败';
	die;
}
echo "\n";

$total_sql = "SELECT count(*) total FROM `deliver_address`";

$total = $db->query($total_sql)->row()->total;
$start = 0;
$per_page = 100;
while($start < $total) {
	$query_sql = "SELECT * from `deliver_address` limit $start,$per_page";
	$deliver_addresses = $db->query($query_sql)->rows();
	foreach ($deliver_addresses as $deliver_address) {
		$id = $deliver_address->id;
		$extra = json_decode($deliver_address->_extra,true);
		$postcode = $extra['postcode'];
		$email = $extra['email'];
		unset($extra['postcode']);
		unset($extra['email']);
		$extra = json_encode($extra);
		$update_sql = "UPDATE `deliver_address` SET `postcode`='%s',`email`='%s',`_extra`='%s' WHERE `id`={$id}";
		if ($db->query($update_sql, $postcode, $email, $extra)) {
			echo '.';
		}
	}
	$start += $per_page;
}