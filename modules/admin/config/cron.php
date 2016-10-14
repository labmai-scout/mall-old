<?php
$config['check_vendor'] = array(
	'title' => '每天检查过期供应商',
	'cron' => '2 0 * * *',
	'job' => ROOT_PATH . 'cli/check_vendors.php'
	);

$config['check_vendor_scopes'] = array(
	'title' => '每天检查资质过期',
	'cron' => '3 0 * * *',
	'job' => ROOT_PATH . 'cli/check_vendor_scopes.php'
	);

$config['check_products'] = array(
	'title' => '每天检查商品过期',
	'cron' => '4 0 * * *',
	'job' => ROOT_PATH . 'cli/check_products.php'
	);

$config['order_daily_notif'] = array(
	'title' => '每天发送订单提醒邮件',
	'cron' => '5 6 * * *',
	'job' => ROOT_PATH . 'cli/order_daily_notif.php'
	);

$config['order_hourly_notif'] = array(
	'title' => '每小时发送订单提醒邮件',
    'cron' => '0 */1 * * *',
	'job' => ROOT_PATH . 'cli/order_hourly_notif.php'
	);

$config['admin_daily_notif'] = array(
	'title' => '每日对管理员的通知',
	'cron' => '10 0 * * *',
	'job' => ROOT_PATH . 'cli/admin_daily_notif.php'
	);

$config['delete_expire_pdf'] = array(
	'title' => '每天删除废旧的pdf',
	'cron' => '6 0 * * *',
	'job' => ROOT_PATH . 'cli/delete_expire_pdf.php'
	);
