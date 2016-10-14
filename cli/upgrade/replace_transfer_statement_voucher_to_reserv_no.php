#!/usr/bin/env php
<?php
/*
 * 执行前确保已执行 create_orm_tables
 * SITE_ID=nankai php fix_transfer_statement_to_reserv_no.php
 * 将transfer_statement 的 voucher 顺序性赋值到 reserv_no中
 */
$base = dirname(dirname(__FILE__)). '/base.php';
include $base;
$start = 0;
$per_page = 20;
$db = Database::factory();

for (;;) {
	$transfer_statements = Q('transfer_statement')->limit($start, $per_page);
	$start += $per_page;
	if (count($transfer_statements) == 0) break;
	foreach ($transfer_statements as $transfer_statement) {
		$reserv_no = $transfer_statement->voucher;
		if ($reserv_no) {
			$ret = $db->query('UPDATE transfer_statement SET reserv_no="%s" WHERE id = %d', $reserv_no, $transfer_statement->id);
			if (!$ret) {
				fecho('statement_id:'.$transfer_statement->id.' update fail');
				die;
			}
			else {
				echo '.';
			}
		}
	}
}
function secho($message) {
	echo "\033[32m".$message."\033[0m \n";
}
?>