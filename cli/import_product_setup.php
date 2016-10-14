#!/usr/bin/env php
<?php
/*
 遍历 product_upload_record status 为 ready的记录
*/
require 'base.php';
$status = Product_Upload_Record_Model::RECORD_STATUS_READY;
$records = Q("product_upload_record[status=$status]:sort(ctime A)");
$end_time = (int)Config::get('mall.upload_end_time')['end'];
$body = '';
foreach ($records as $record) {
	$vendor = $record->vendor;
	$current = (int)date("H");
	if ($current < $end_time) {
		$body .= $record->file_name.'['.$record->sheet_name."]\n";
		$id = $record->id;
		$record = O('product_upload_record', $id);
		if ($record->status == $status) {
			$script = ROOT_PATH . 'cli/import_'.$record->type.'_product.php ';
			putenv('SITE_ID='.SITE_ID);
			$cmd = 'php ' .$script.' '.$vendor->id.' '.$record->path;
			$output = '';
			exec($cmd, $output, $retval);
			$body .= implode("\n", $output);
			$record->save();
		}
	}
	else {
		break;
	}
}
if ($body) {
	$body = "供应商-".$vendor->name.":\n".$body;
	$token = 'genee|database';
	$user = O("user", ['token'=>$token]);
	if ($user->id) {
		$m = new Email;
		$m->to($user->email); //请换成收件人邮箱
		$m->subject('导入报告'); //发送标题
		$m->body(H($body)); // 发送内容
		$m->send();

		$message = O('message');
		$message->receiver = $user;
		$message->title = (string)new Markup('导入报告', FALSE);
		$message->body = $body;
		$message->save();
	}
	exit;
}

?>
