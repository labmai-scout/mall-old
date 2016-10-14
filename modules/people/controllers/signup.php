<?php

class Signup_Controller extends Base_Controller {

	function _before_call($method, &$params){

		if (!Config::get('mall.enable_register') && !Auth::logged_in()) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		if ($me->id && $me->is_active()) {
			URI::redirect('/');
		}

		parent::_before_call($method, $params);
	}

	function index(){

		// 阻断用户自主激活
		if (Auth::logged_in()) {
			Auth::logout();
		}
		URI::redirect('error/404');

		$me = L('ME');
		/* (xiaopei.li@2011.01.04) */
		if ($me->id && !$me->is_active() ){
			URI::redirect('!people/signup/edit');
		}

		if (Input::form('logout')) {
			Auth::logout();
			URI::redirect('/');
		}

        $user_info = Event::trigger('signup.get_user_info', $me);

		if (Input::form('submit')) {

			/*
            NO.BUG#111（guoping.zhang@2010.11.12)
            用户账户密码长度的限制（不小于6位，最长不能大于24位）
            */
			$this->layout->form = $form = Form::filter(Input::form());

			$verified_token = Auth::token();

			if (!$verified_token) {

                $token = $verified_token ? : trim($form['token']);
                $backend = trim($form['token_backend']);
                $token = Auth::normalize($token, $backend);
                $auth = new Auth($token);

                if ($form['token']) {
                    if (O('user', array('token'=>$token))->id) {
                        $form->set_error('token', T('您输入的登录帐号在系统中已存在！'));
                    }
                }
                else {
                    $form->set_error('token', T('请输入登录帐号！'));
                }

                if (!$auth->is_readonly()) {
                    $form
                        ->validate('passwd', 'length(6,24)', T('输入的密码不能小于6位，最长不能大于24位！'))
                        ->validate('confirm_passwd', 'compare(==passwd)', T('请输入有效密码并确保两次输入的密码一致！'));
                }
			}
            else {
                list($token, $backend) = Auth::parse_token($verified_token);
                $auth = new Auth($verified_token);
            }

			/*
			if ($form['ref_no'] && O('user', array('ref_no'=>$form['ref_no']))->id) {
				$form->set_error('ref_no', T('您输入的学号/工号在系统中已存在！'));
			}
			*/

			if ($form['email']) {
				if (O('user', array('email'=>$form['email']))->id) {
					$form->set_error('email', T('您输入的电子邮箱在系统中已存在！'));
				}
                else {
                    $form->validate('email', 'is_email', T('请输入正确的电子邮箱'));
                }
			}
			else {
				$form->set_error('email', T('请输入电子邮箱！'));
			}

			$form
				->validate('name', 'not_empty', T('请输入真实姓名！'))
			/*
				->validate('organization', 'not_empty', T('请填写单位名称！'))
				// ->validate('department', 'not_empty', T('请填写所属系所！'))
				->validate('member_type','is_numeric',T('请填写人员类型'))
				->validate('major', 'not_empty', T('请填写专业！'))
			*/
				->validate('phone', 'not_empty', T('请填写联系电话！'))
				->validate('registration_agreement', 'not_empty', T('请您仔细阅读注册须知！'));

			if($form->no_error) {
				try {
					$user = O('user');

					if (count($user_info)) foreach ($user_info as $key => $value){
						$user->$key = $value;
					}

                    if ($verified_token) {
                        $user->token = $verified_token;
                    }
                    else {
                        $token = trim($form['token']);
                        $backend = trim($form['token_backend']);
                        $token = Auth::normalize($token, $backend);
                        $user->token = $token;
                    }

					$user->email = $form['email'];

					/*
					if (Q("user[email={$user->email}|token={$user->token}]")->length() > 0) {
						//如果token或者email不是唯一的跳转到注册页面.
						Site::message(Site::MESSAGE_ERROR, T('您输入的学号或电子邮箱在系统中已存在！'));
						throw new Error_Exception;
					}
					*/

					$user->name = $form['name'];
					/*
					$user->member_type = $form['member_type'];
					$user->organization = $form['organization'];
					$user->major = $form['major'];
					// $user->department = $form['department'];
					$user->ref_no = $form['ref_no'];
					*/
					$user->gender = $form['gender'];
					$user->phone = $form['phone'];
					$user->address = $form['address'];

					// $user->lab = O('lab', $form['lab_id']);

					/*
					if (count($user_info)) {
						$card_user = O('user', array('card_no'=>$user_info['card_no']));
						if ($user_info['card_no'] && !$card_user->id) {
							$user->card_no = $user_info['card_no'];
						}

						$user->dfrom = $user_info['dfrom'];
						$user->dto = $user_info['dto'];
					}
					*/

					if (!$verified_token) {
						$password = $form['passwd'];

						if ($auth->is_creatable()) {
							if (!$auth->create($form['passwd'])) {
								Site::message(Site::MESSAGE_ERROR, T('用户注册失败，请您重试。'));
								throw new Error_Exception;
							}
						}
						else {
							if (!$auth->verify($password)) {
								Site::message(Site::MESSAGE_ERROR, T('用户名与密码不匹配，请您重试。'));
								throw new Error_Exception;
							}
						}
					}

                    Event::trigger('user.signup.form.submit', $user);

                    if($user->save()) {

                        Event::trigger('user.signup.form.post_submit', $user);
                    }
					/*
						TODO
						用户注册失败的原因有好几种，比如邮箱重复，帐号重复等，应该优化提示错误信息
					*/
					if (!$user->id) {
						if (!$verified_token) {
							$auth->remove(); //添加新成员失败，去掉已添加的 token
						}
						Site::message(Site::MESSAGE_ERROR, T('用户注册失败，请您重试。'));
						throw new Error_Exception;
					}

                    if ($user->atime) {
                        Site::message(Site::MESSAGE_NORMAL, T('您已经成功注册用户!'));
                    }
                    else {
                        Site::message(Site::MESSAGE_NORMAL, T('您已经成功注册用户, 请等待审核通过。'));
                    }

					Auth::login($user->token);

                    URI::redirect('/');

				}
				catch (Error_Exception $e) {
				}
			}
		}

		$this->layout->form = $form;
		$this->layout->body = V('signup/signup', array('form'=>$form, 'user_info'=>$user_info));
	}

	function introduction(){
		$this->layout = V('signup/introduction');
	}

	function edit($uid=0){
		$this->tab = 'edit';
		$user = L('ME');
		if (!$user->id) {
			URI::redirect('!people/signup');
		}

		if(Input::form('submit')){

			$form = Form::filter(Input::form())
						->validate('name', 'not_empty', T('请输入真实姓名！'))
				/*
						->validate('organization', 'not_empty', T('请填写单位名称！'))
						->validate('major', 'not_empty', T('请填写专业！'))
						->validate('member_type','is_numeric',T('请填写人员类型'))
				// ->validate('department', 'not_empty', T('请填写所属系所！'))
				*/
						->validate('email', 'is_email', T('Email输入有误！'))
						->validate('phone', 'not_empty', T('请填写联系电话！'));

			if ($form['passwd']) {
				$form
					->validate('original_password', 'not_empty', T('请输入原密码！'))
					->validate('passwd', 'length(6,24)', T('输入的密码不能小于6位，最长不能大于24位！'))
					->validate('confirm_passwd', 'compare(==passwd)', T('请输入有效密码并确保两次输入的密码一致！'));

				if ($form['original_password']) {
					$auth = new Auth($user->token);
					if (!$auth->verify($form['original_password'])) {
						$form->set_error('original_password', T('当前密码输入错误!'));
					}
				}
			}

			if ($form->no_error) {

				try {

					if ($form['passwd']) {
						$auth = new Auth($user->token);
						if( ! $auth->change_password($form['passwd'] )) {
							Site::message(Site::MESSAGE_ERROR, T('密码修改失败, 请您重试。'));
							throw new Error_Exception;
						}
					}

					if ($user->email != $form['email'] && Q("user[email={$form[email]}]")->length() > 0) {
						//如果email不是唯一的报错.
						Site::message(Site::MESSAGE_ERROR, T('您输入的电子邮箱在系统中已存在！'));
						throw new Error_Exception;
					}

					/*
					if ($form['ref_no']) {
						$ref_user = O('user', array('ref_no'=>$form['ref_no']));
						if ($ref_user->id && $ref_user->id != $user->id) {
							Site::message(Site::MESSAGE_ERROR, T('您输入的学号/工号在系统中已存在！'));
							throw new Error_Exception;
						}
					}
					*/

					$user->email = $form['email'];
					$user->name = $form['name'];
					$user->gender = $form['gender'];
					/*
					$user->member_type = $form['member_type'];
					$user->organization = $form['organization'];
					$user->major = $form['major'];
					// $user->department = $form['department'];
					$user->ref_no = $form['ref_no'];
					*/
					$user->phone = $form['phone'];
					$user->address = $form['address'];

					// $user->lab = O('lab', $form['lab_id']);

					$user->save();

					Site::message(Site::MESSAGE_NORMAL, T('注册信息已更新'));

				}
				catch (Error_Exception $e) {
				}

			}

		}

		$this->layout->body = V('signup/edit',array('user'=>$user, 'form'=>$form));

	}

}
