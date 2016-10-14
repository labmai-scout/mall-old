<?php

class Recovery_Controller extends Layout_Mall_Controller {
	
	function index() {
		$form = Form::filter(Input::form());
		
		/*清除过早的recovery*/
		$recoverys = Q("recovery[overdue][overdue<".(time() - Config::get('recovery.overdue'))."]");
		foreach ($recoverys as $res) {
			$res->delete();
		}
		
		if ($form['submit']) {
			if ($form['token']) {
				$backend = $form['token_backend'];
				$user = O('user', array('token'=>Auth::normalize($form['token'], $backend)));
				//如果输入了email，并且帐号的email和输入的email不同，则显示错误
				if ($user->id && $form['email'] && ($user->email != $form['email'])) {
					$form->set_error('', T('您输入的帐号和邮箱地址指向不同的帐号，请重新输入，或只输入其中一个!'));
				}
			}
			else {
				$user = O('user', array('email'=>$form['email']));
			}
			
			if (!$user->id) {
				$form->set_error('', T('您输入的帐号/Email有误，请重新输入！'));
			}
			else {
				$count = count(Q("recovery[user={$user}]"));
				if ($count >= Config::get('recovery.reset_request_limit')) {
					Site::message(Site::MESSAGE_ERROR, T('您执行了过多次密码重置操作，请您稍后再试。'));
					URI::redirect('/');
				}
			}
			
			if ($form->no_error) {
				$key = md5($user->email.uniqid().mt_rand());
				$recovery = O('recovery');
				$recovery->user = $user;
				$recovery->key = $key;
				$recovery->overdue = Config::get('recovery.overdue') + time();
				if ($recovery->save()) {
					Log::add(sprintf('[application] 用户%s[%d]帐号申请重置密码', $user->name, $user->id), 'journal');
					/*
						该处Email功能暂时不同，email指定到postfix上，仅仅是postfix上不work，待之后解决。
					*/
					$mail = new Email();
					$mail->to($user->email);
					$mail->subject(T(Config::get('recovery.default_email_title'), array("%name"=>$user->name, '%system'=>Config::get('system.email_name'))));
					$mail->body(T(Config::get('recovery.default_email_body'), array(
							'%name' => $user->name,
							'%url' => URI::url('recovery/reset_password', array('key'=>$key)),
							'%system' => Config::get('system.email_name'),
							'%system_url' => URI::url()
						)));
					Log::add(URI::url('recovery/reset_password', array('key'=>$key)), 'mail');
					if ($mail->send()) {
						Site::message(Site::MESSAGE_NORMAL, T('请尽快到您的邮箱查看邮件，并通过邮件重设密码。'));
						URI::redirect('/');
					}
				}
				$recovery->delete();
				Site::message(Site::MESSAGE_ERROR, T('未知原因找回失败！'));
			}
		}
		
        $this->add_css('recovery');
		$this->layout->body = V('application:recovery/index', array('form'=>$form));
	}	
	
	function reset_password() {
		$form = Form::filter(Input::form());
		$key = $form['key'];
	
		if (Auth::logged_in()) {
			$user = L('ME');
			Auth::logout();
			if ($user->id) {
				Log::add(sprintf('为了重设密码，登出当前登录的用户：%s[%d]', $user->name, $user->id), 'mail');
			}
			$redirect = URI::url(null, array('key'=>$key));
			URI::redirect($redirect);
		}
		/*自动运行程序，用来清除已经过期的recovery*/
		$recoverys = Q("recovery[overdue][overdue<".time()."]");
		foreach ($recoverys as $res) {
			$res->delete();
		}
		
		$recovery = O("recovery", array('key'=>$key));
		if (!$recovery->id) {
			URI::redirect('error/404');
		}
		
		if ($form['submit']) {
			$form
				->validate('new_pass', 'not_empty', T('新密码不能为空！'))
				->validate('confirm_pass', 'not_empty', T('确认新密码不能为空！'));
				
			if ($form['new_pass']) {
				$form
					->validate('confirm_pass', 'compare(==new_pass)', T('两次输入的密码不一致！'))
					->validate('new_pass', 'length(6, 24)', T('输入的密码不能小于6位，最长24位！'));
			}
			if ($form->no_error) {
				$user = $recovery->user;
				try {
					$auth = new Auth($user->token);
					if ($auth->change_password($form['new_pass'])) {
						$recovery->delete();
						Log::add(sprintf('[application] 用户%s[%d]帐号成功重置密码', $user->name, $user->id), 'journal');
						Site::message(Site::MESSAGE_NORMAL, T('用户密码已更新！'));
						URI::redirect('/');
					}
					else {
						throw new Exception(T('用户密码更新失败！'));
					}
				}
				catch (Exception $e){
					$message = $e->getMessage();
					if ($message) Site::message(Site::MESSAGE_ERROR, $message);
				}
			}
		}
		
        $this->add_css('recovery');
		$this->layout->body = V('application:recovery/reset_password', array('form'=>$form, 'key'=>$key));
	}
}
