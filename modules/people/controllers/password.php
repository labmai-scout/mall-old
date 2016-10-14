<?php

class Password_Controller extends Base_Controller {

	function index() {

        $user = L('ME');
		if(!$user->id || !$user->must_change_password){
			URI::redirect('error/404');
		}


		$form = Form::filter(Input::form());
		
		if (Input::form('submit')) {
			$form
				->validate('new_pass', 'length(6, 40)', I18N::T('people', '输入的密码不能小于6位，最长不能大于24位！'))
				->validate('confirm_pass', 'compare(==new_pass)', I18N::T('people', '您两次输入的密码不一致！'));

			if ($form->no_error) {
				$auth = new Auth($user->token);

				if ($auth->change_password($form['new_pass'])) {
					Site::message(Site::MESSAGE_NORMAL, I18N::T('people', '您的登录密码已修改!'));
					if($user->must_change_password){
						$user->must_change_password = NULL;
						$user->save();
						URI::redirect('/');
					}

				} 
				else {
					Site::message(Site::MESSAGE_ERROR, I18N::T('people', '您的登录密码更新失败! 请与系统管理员联系.'));
				}
			}

		}

		$this->layout->body = V('people:password',array('user'=>$user, 'form'=>$form));
	}

}
