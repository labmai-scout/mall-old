<?php

class Logout_Controller extends Controller {

	function index(){
		
		$user = L('ME');
		
		Auth::logout();
		if ($user->id) {
			Log::add(sprintf('用户%s[%d]登出成功', $user->name, $user->id), 'logon');
			Log::add(sprintf('[%s] %s[%d]成功登出系统', 'application', $user->name, $user->id), 'journal');
		}
		
		URI::redirect('/');
	}
		
}
