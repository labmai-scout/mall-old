<?php
/**
 * 付款单号
 * 付款单ID
 * 付款单生成时间
 * 付款完成时间
 * 所属课题组
 * 课题组负责人
 * 付款金额
 */
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
$start = 0;
$limit = 20;
$file  = 'trans_stat.csv';
$csv   = new CSV($file, 'w');
$row = [
	'付款单号',
	'付款单ID',
	'付款单生成时间',
	'付款完成时间',
	'所属课题组',
	'课题组负责人',
	'付款金额',
];
$csv->write($row);
$status = Transfer_Statement_Model::STATUS_TRANSFERRED;
while (true) {
	$statements = Q("transfer_statement[status={$status}]:sort(id D)")->limit($start, $limit);
	$start += $limit;
	if (!count($statements)) break;
	foreach ($statements as $statement) {
		$row = [];
		$row[] = $statement->voucher;
		$row[] = $statement->id;
		$row[] = Date::format($statement->ctime, 'Y/m/d H:i:s');
		if ($statement->transferred_date) {
			$row[] = Date::format($statement->transferred_date, 'Y/m/d H:i:s');
		}
		else {
			$row[] = '暂无';
		}
		$row[] = $statement->customer->name;
		$row[] = $statement->customer->owner->name;
		$row[] = $statement->balance;
		$csv->write($row);
	}
}
$csv->close();