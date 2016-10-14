<?php
/**
 * @file statement_lifecycle.php
 * @brief 统计付款单生命周期
 * @author Jinlin Li <jinlin.li@geneegroup.com>
 * @version 0.1.0
 * @date 2015-09-18
 * 表二：付款单生命周期
 * 导出表包含列：付款单编号、付款单状态、付款单生成时间、院系会计审核时间、付款单支付完成时间、付款单取消时间
 * 付款单生成时间：取值年月日-时分（格式不限）
 * 院系会计审核时间：付款单变成可打印的时间与付款单生成时间的时间差
 * 付款单支付完成时间：付款单变成【已支付】与付款单生成时间的时间差
 * 付款单取消时间：付款单变成【已取消】与付款单生成时间的时间差
 * 导出3月份以后数据
 * 取不到的值显示--
 * 时间差，以h为单位，四舍五入保留2位小数
 * 系统没有记录的时间需增加记录
 */
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
clean_cache();
$time_start = mktime(0,0,0,3,31,2015);
$csv = new CSV('statement_lifecycle.csv', 'w');
$csv->write([
	'付款单编号','付款单状态','付款单生成时间','付款单支付完成时间', '付款单会计审核时间'
]);

$start = 0;
$limit = 20;
while (true) {
	$statements = Q("transfer_statement[ctime>$time_start]:sort(ctime A)")->limit($start, $limit);
	if (!count($statements)) break;
	$start += $limit;
	foreach ($statements as $statement) {
		$row = [
			$statement->voucher,
			Transfer_Statement_Model::$status[$statement->status],
			date('Y-m-d', $statement->ctime),
            $statement->transferred_date?date('Y-m-d', $statement->transferred_date):'--',
            $statement->approve_date ? date('Y-m-d', $statement->approve_date) : '--',// '会计审核时间:这个时间不准确', 
		];
		$csv->write($row);
	}
	echo '.';
}
