<?php

$config['check_order_received'] = array(
	'title' => '每天凌晨0:13分自动检查超过规定时间的已发货订单打为已收货',
	'cron' => '13 0 * * *',
	'job' => ROOT_PATH . 'cli/check_order_received.php'
);

