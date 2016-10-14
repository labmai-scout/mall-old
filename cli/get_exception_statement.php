<?php
require 'base.php';

$pending_status = Transfer_Statement_Model::STATUS_PENDING_TRANSFER;
$transfer_statements = Q("transfer_statement[status={$pending_status}]");
$body = '';
foreach ($transfer_statements as $statement) {
	if ($statement->pdata) continue;
	$data = $statement->get_pay_status();
	if (!is_array($data)) {
		$customer = $statement->customer;
		$body .= '['.$statement->voucher.']'.' 金额: '.$statement->balance. ' 课题组: '.$customer->name.'{'.$customer->id.'} 生成时间: '.date('Y-m-d H:i:s', $statement->ctime)."\n";
	}
}


$mail = new Email;
$mail->to(['mall@geneegroup.com']);
$mail->subject('mall-old 合同用户每日统计异常的付款中付款单');
$mail->body($body);
$mail->send();
?>
