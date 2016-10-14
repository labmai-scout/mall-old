#!/usr/bin/env php
<?php

/*

使用格式: add_user.php jia.huang -e jia.huang@geneegroup.com

*/

require "base.php";

try {

	$cmd = NULL;
	$args = $argv;
	array_shift($args);
	foreach ($args as $arg) {
	
		if ($arg[0] == '-') {
			$cmd = $arg[1];
		}
		else {
			switch ($cmd) {
			case 'e':
				$email = $arg;
				break;
			case 'n':
				$name = $arg;
				break;
			default:
				$token = $arg;
			}
		
		}
		
	}
	
	if (!$token) {
		throw new Error_Exception('没有设置token');
	}
	
	echo "Account: $token\n";
	
	$user = O('user');

	$user->token = Auth::normalize($token);
	echo 'Email[nobody@geneegroup.com]: ';
	$email = trim(fgets(STDIN));
	$user->email = $email ?: 'nobody@geneegroup.com';

	echo 'Name[Doe John]: ';
	$name = trim(fgets(STDIN));
	$user->name = $name ?: 'Doe John';

	$user->member_type = 0;
	$user->atime = time();

	echo 'Password: ';
	$password = trim(fgets(STDIN)) ?: '123456';

	$auth = new Auth($token);
	if( !$auth->create($password)) {
		throw new Error_Exception('用户注册失败。');
	}
	
	$user->save();
	if (!$user->id) {
		$auth->remove(); //添加新成员失败，去掉已添加的 token
		throw new Error_Exception('用户保存失败。');
	}
	
}
catch (Error_Exception $e) {
	echo $e->getMessage()."\n";
}

