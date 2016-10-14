#!/usr/bin/env php
<?php

//清除mall中错误关联的数据

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
	if (SITE_ID != 'nankai') return FALSE;
	return TRUE;
};

//数据库备份
$u->backup = function() {
	$dbfile = SITE_PATH . 'private/backup/before_fix_billing_statement.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
	$db = Database::factory();
	return $db->snapshot($dbfile, '_r_order_billing_statement');
};

$u->upgrade = function() {
	$db = Database::factory();
	$num = 20;
	$start = 0;
	$query_sql = "SELECT * FROM `_r_order_billing_statement` limit %d, %d";
	while($rows = $db->query($query_sql, $start, $num)->rows()){
	foreach ($rows as $row) {
		$order_id = $row->id1;
		$statement_id = $row->id2;

		$order = O('order', $order_id);
		$statement = O('billing_statement', $statement_id);

		if(!$order->id 
			|| !$statement->id
			|| $order->vendor->id != $statement->vendor->id){
			$error[] = $row;
			continue;
		}
	}
	$start += $num;
	}

	foreach((array)$error as $row){
		$db->query('DELETE FROM `_r_order_billing_statement` WHERE id1='.$row->id1.' AND id2='.$row->id2);
		echo "删除了数据id1=$row->id1, id2=$row->id2\n";
	}
    Upgrader::echo_success('升级完成');
};


//恢复数据
$u->restore = function() {
	$dbfile = SITE_PATH . 'private/backup/before_fix_billing_statement.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
	$db = Database::factory();
	$db->restore($dbfile);
};

$u->run();
