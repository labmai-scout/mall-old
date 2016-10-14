<?php

$config['default.send_by'] = array(
	'*'        => FALSE,
	'messages' => TRUE,
	'email'    => TRUE,
	'sms'      => FALSE
);

$config['handlers']['email'] = array(
	'class' => 'Notification_Email',
	'text' => '通过电子邮件发送',
);

