<?php
/**
 * 结算单号
 * 结算单ID
 * 结算单生成时间
 * 结算完成时间
 * 所属供应商
 * 结算金额
 */
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
$start = 0;
$limit = 20;
$file  = 'billing_stat.csv';
$csv   = new CSV($file, 'w');
$status = Billing_Statement_Model::STATUS_PAID;
$row = [
	'结算单号',
	'结算单ID',
	'结算单生成时间',
	'结算完成时间',
	'所属供应商',
	'结算金额',
];
$csv->write($row);
while (true) {
	$statements = Q("billing_statement[status={$status}]:sort(id D)")->limit($start, $limit);
	$start += $limit;
	if (!count($statements)) break;
	foreach ($statements as $statement) {
		$row = [];
		$row[] = str_pad($statement->id, 6, 0, STR_PAD_LEFT);
		$row[] = $statement->id;
		$row[] = Date::format($statement->ctime, 'Y/m/d H:i:s');
		if ($statement->approve_date) {
			$row[] = Date::format($statement->approve_date, 'Y/m/d H:i:s');
		}
		else {
			$row[] = '暂无';
		}
		$row[] = $statement->vendor->name;
		$row[] = $statement->balance;
		$csv->write($row);
	}
}
$csv->close();