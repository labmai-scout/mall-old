#!/usr/bin/env php
<?php

include "base.php";

$usage = <<<EOT

usage: change_transfer_statement_status.php ID STATUS

ID:
	付款单ID

STATUSES:
	0 => 未支付
	1 => 付款中
	2 => 已付款
当付款单为未支付状态时 STATUSES 1为付款中,2为付款成功
当付款单为支付中状态时 STATUSES 0为付款失败,2为付款成功

EOT;

if ($argc != 3) {
	die($usage);
}

$id = (int) $argv[1];
$transfer_statement = O('transfer_statement', $id);
if ($transfer_statement->id) {
	$status = (int) $argv[2];

	if ($transfer_statement->status == 0) {
		switch ($status) {
		case 1:
			$transfer_statement->approve();
			break;
		case 2:
			$transfer_statement->approve();
			$transfer_statement->success();
			break;
		}
		echo "done\n";
	}
	else if ($transfer_statement->status == 1) {
		switch ($status) {
		case 0:
			$transfer_statement->fail();
			break;
		case 2:
			$transfer_statement->success();
			break;
		}
		echo "done\n";
	}
	else if ($transfer_statement->status == 2) {
		die("there's no way back\n");
	}
}

