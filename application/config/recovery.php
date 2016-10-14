<?php

$config['overdue'] = 86400;
$config['default_email_title'] = "重设 %name 在%system的密码";
$config['default_email_body'] = "
%name:\n
	您好!\n
	您的密码重设要求已经得到验证。请点击以下链接输入您新的密码:\n
	%url\n
	如果您的 email 程序不支持链接点击,请将上面的地址拷贝至您的浏览器(例如 IE)的地址栏重设。\n
	%system %system_url\n
	(这是一封自动产生的 Email,请勿回复。)\n
";
$config['reset_request_limit'] = 5;
