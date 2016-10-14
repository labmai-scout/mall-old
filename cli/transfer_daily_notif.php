<?php
/*
  检查 24 小时内更新的付款单, 对买方负责人通知
  应每日运行
  (xiaopei.li@2012-07-09)
*/

require 'base.php';

$last_day = Date::time() - 86400; // 最近一天

$query = "transfer_statement[mtime>={$last_day}]:sort(customer_id)";
$newly_updated_statements = Q($query);

$customer_statements = array();
$statement_format = "%no (%price)";

foreach ($newly_updated_statements as $s) {

	$s_statement = strtr($statement_format, array(
						'%no' => URI::anchor($s->url(NULL, NULL, NULL, 'view'), H('#' . $s->id)),
						'%price' => Number::currency($s->balance),
					));

	if ($s->status == Transfer_Statement_Model::STATUS_TRANSFERRED) {
		$customer_statements[$s->customer_id]['success'][] = $s_statement;
	}
	else if ($s->is_failed()) {
		$customer_statements[$s->customer_id]['failed'][] = $s_statement;
	}
	else {
		$customer_statements[$s->customer_id]['pending'][] = $s_statement;
	}
}

foreach ($customer_statements as $customer_id => $statements) {
	$customer = O('customer', $customer_id);

	$s_statements = "";

	foreach ($statements as $type => $type_statements) {
		switch($type) {
		case 'success':
			$s_statements .= "以下付款单支付成功:\n" . join("\n", $type_statements);
			break;
		case 'failed':
			$s_statements .= "以下付款单支付失败:\n" . join("\n", $type_statements);
			break;
		case 'pending':
			$s_statements .= "以下付款单申请付款:\n" . join("\n", $type_statements);
			break;
		}
	}

	Notification::send('notification.transfer_daily_notif_for_customer', $customer, array(
						'%mall' => H(Config::get('page.title_default')),
						'%customer' => H($customer->name),
						'%statements' => $s_statements,
						));
}

