#!/usr/bin/env php
<?php
/*
更新用户和vendor的关联
yu.li
SITE_ID=xx php 10-update_user_vendor.php
*/

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;


$u = new Upgrader;

//数据库备份
$u->backup = function() {
	$dbfile = SITE_PATH . 'private/backup/before_update_user_vendor.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
	$db = Database::factory();
	return $db->snapshot($dbfile);
};

$u->upgrade = function() {

    $db = Database::factory();


	$num = 20;
	$start = 0;
	$total = Q('vendor')->total_count();

	while($start < $total){
		
		foreach (Q('vendor')->limit($start, $num) as $vendor) {
			if($vendor->owner->id){
				$vendor->connect($vendor->owner, 'member');
			}
			echo '.';
		}

		$start += $num;
	}

	$db->query('ALTER TABLE `user` DROP COLUMN `vendor_id`');
};

//恢复数据
$u->restore = function() {
	$dbfile = SITE_PATH . 'private/backup/before_update_user_vendor.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
	$db = Database::factory();
	$db->restore($dbfile);
};

$u->run();



