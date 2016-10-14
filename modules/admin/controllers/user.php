<?php

class User_Controller extends Layout_Admin_Controller {

	function _before_call($method, &$params) {
		if (!L('ME')->access('管理成员')) {
			URI::redirect('error/401');
		}

		parent::_before_call($method, $params);

		$this->layout->title = T('登录管理');
		$this->layout->body = V('user/body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');

		$this->layout->body->primary_tabs
			->add_tab('index', array(
						  'url'=>URI::url('!admin/user/index'),
						  'title'=>T('用户列表')
						  ));
	}

	function index($tab='activated') {

		$form = Site::form();

		$selector = 'user';

		if ($tab == 'unactivated') {
			$selector .= "[atime=0]";
		}
		else {
			$selector .= "[atime>0]";
		}

		if ($form['name']) {
			$name = Q::quote($form['name']);
			$selector .= "[name*={$name} | name_abbr^={$name}]";
		}

		if ($form['contact']) {
			$contact = Q::quote($form['contact']);
			$selector .= "[email*={$contact} | phone*={$contact}]";
		}

		if ($form['address']) {
			$address = Q::quote($form['address']);
			$selector .= "[address*={$address}]";
		}

		$users = Q($selector);

		$pagination = Site::pagination($users, (int)$form['st'], 20);

	    $secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
			->add_tab('activated', array(
						  'url'=>URI::url('!admin/user/index.activated'),
						  'title'=>T('已激活')
						  ))
			->add_tab('unactivated', array(
						  'url'=>URI::url('!admin/user/index.unactivated'),
						  'title'=>T('未激活')
						  ))
			->set('class', 'secondary_tabs')
			->select($tab);

		$content = V('admin:user/list', array(
						 'users'=>$users,
						 'form'=>$form,
						 'secondary_tabs'=>$secondary_tabs,
						 'pagination'=>$pagination
						 ));

		$this->layout->body->primary_tabs
			->select('index')
			->set('content', $content);

	}

	function view($id) {

		$user = O('user', $id);
		if (!$user->id) URI::redirect('error/404');

		$content = V('admin:user/view/index', array('user'=>$user));

		$this->layout->body->primary_tabs
			->add_tab('profile', array(
						  'url'=> $user->url(NULL, NULL, NULL, 'admin_view'),
						  'title'=> H($user->name),
						  ))
			->set('content', $content)
			->select('profile');

		$this->layout->title = H($user->name);
	}

	function add() {

        // 10362 【Win 8，firfox】【南通大学】0.1.0，mall-old，买方管理，添加买方功能去掉
        return;

		$form = Form::filter(Input::form());

		if ($form['submit']) {
			$form
				->validate('token', 'is_token', T('请填写符合规则的用户帐号!'))
				->validate('backend', 'not_empty', T('请选择验证后台！'))
				->validate('name', 'not_empty', T('请填写用户姓名!'))
				->validate('email', 'is_email', T('电子邮箱输入有误!'))
				->validate('phone', 'not_empty', T('请填写联系方式!'));

			if ($form['backend'] && !$this->_validate_token_backend($form['backend'])) {
				$form->set_error('backend', '验证后台不合法');
			}
			$auth_backends = Config::get('auth.backends');
			if ($form['backend'] && !$auth_backends[$form['backend']]['readonly']) {
				$form
					->validate('password', 'not_empty', T('密码不能为空！'))
					->validate('password', 'compare(==confirm_password)', T('两次输入密码不一致！'))
					->validate('password', 'length(6, 24)', T('输入的密码不能小于6位，最长24位！'));
			}

			if ($form->no_error) {
				try {

					// $token = Auth::normalize(trim($form['token']));
					$token = Auth::make_token($form['token'], $form['backend']);

					if (User_Model::is_reserved_token($token)) {
						Site::message(Site::MESSAGE_ERROR, T('您输入的登录帐号已被保留。'));
						throw new Error_Exception;
					}

					if (O('user', array('token'=>$token))->id) {
						//如果token不是唯一的跳转到注册页面.
						throw new Error_Exception(T('您输入的帐号在系统中已存在!'));
					}

					if (O('user', array('email'=>$form['email']))->id) {
						//如果email不是唯一的跳转到注册页面.
						throw new Error_Exception(T('您输入的电子邮箱在系统中已存在!'));
					}

					$auth = new Auth($token);
					if( !$auth->create($form['password'])) {
						throw new Error_Exception(T('添加新成员失败! 请与系统管理员联系.'));
					}

					$user = O('user');
					$user->name = $form['name'];
					$user->token = $token;
					$user->email = $form['email'];
					$user->phone = $form['phone'];
					$user->gender = $form['gender'];
					$user->address = $form['address'];
					$user->creator = $me;

					$user->hidden = $form['hidden'];

					if ($form['activate']) {
						$user->atime = Date::time();
					}

					if ($form['must_change_password']){
						$user->must_change_password = TRUE;
					}

					if ($user->save()) {
						Site::message(Site::MESSAGE_NORMAL, T('添加新成员成功!'));
						URI::redirect($user->url(NULL, NULL, NULL, 'admin_view'));
					}
					else {
						$auth->remove(); //添加新成员失败，去掉已添加的 token
						throw new Error_Exception(T('添加新成员失败! 请与系统管理员联系.'));
					}
				}
				catch (Exception $e) {
					Site::message(Site::MESSAGE_ERROR, $e->getMessage());
				}
			}
		}

		$content = V('admin:user/add', array(
						 'form' => $form
						 ));

		$this->layout->body->primary_tabs
			->add_tab('add', array(
						  'url' => URI::url('!admin/user/add'),
						  'title' => T('添加新用户')
						  ))
			->set('content', $content)
			->select('add');

	}

	function edit($id=0, $tab='info') {

		$user = O('user', $id);
		if (!$user->id) URI::redirect('error/404');

		Event::bind('admin.user.edit.content', array($this, '_edit_user_info'), 0, 'info');
		Event::bind('admin.user.edit.content', array($this, '_edit_icon'), 0, 'icon');
		if (!$user->is_lims_user()) {
			Event::bind('admin.user.edit.content', array($this, '_edit_account'), 0, 'account');
		}
		Event::bind('admin.user.edit.content', array($this, '_edit_role'), 0, 'role');

		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
			->add_tab('info', array(
						  'url' => $user->url('info', NULL, NULL, 'admin_edit'),
						  'title' => T('基本信息')
						  ))
			->add_tab('icon', array(
						  'url' => $user->url('icon', NULL, NULL, 'admin_edit'),
						  'title' => T('头像')
						  ));
		if (!$user->is_lims_user()) {
			$secondary_tabs
				->add_tab('account', array(
							  'url'=> $user->url('account', NULL, NULL, 'admin_edit'),
							  'title'=>T('帐号'),
							  ));
		}
		$secondary_tabs
			->add_tab('role', array(
						  'url'=> $user->url('role', NULL, NULL, 'admin_edit'),
						  'title'=>T('角色'),
						  ))
			->set('class', 'secondary_tabs')
			->set('user', $user)
			->content_event('admin.user.edit.content')
			->select($tab);

		$content = V('admin:user/edit', array(
						 'secondary_tabs' => $secondary_tabs
						 ));

		$breadcrumb = array(
			array(
				'url' => $user->url(NULL, NULL, NULL, 'admin_view'),
				'title' => H($user->name)
				),
			array(
				'url' => $user->url($tab, NULL, NULL, 'admin_edit'),
				'title' => T('修改')
				)
			);

		$this->layout->body->primary_tabs
			->add_tab('edit', array( '*' => $breadcrumb ))
			->set('content', $content)
			->select('edit');
	}

	function _edit_user_info($e, $tabs) {

		$me = L('ME');
		$form = Form::filter(Input::form());
		$user = $tabs->user;

		if ($form['submit']) {
			$form
				->validate('name', 'not_empty', T('请填写用户姓名!'))
				->validate('email', 'is_email', T('电子邮箱输入有误!'))
				->validate('phone', 'not_empty', T('请填写联系方式!'));

			$email = $form['email'];
			if ($email && Q("user[email={$email}][id!={$user->id}]")->length() > 0) {
				$form->set_error('email', T('您输入的邮箱在系统中已存在!'));
			}

			if ($form->no_error) {

				$user->name = $form['name'];
				$user->email = $form['email'];
				$user->phone = $form['phone'];
				$user->gender = $form['gender'];
				$user->address = $form['address'];
				$user->hidden = $form['hidden'];

				$active = $user->is_active() ? 1 : 0;
				if ($active != $form['activate']) {
					$user->atime = $form['activate'] ? Date::time() : 0;
				}

				if ($user->save()) {
					Site::message(Site::MESSAGE_NORMAL, T('修改成员成功!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('修改成员失败!'));
				}

				Log::add(sprintf('%s[%d]修改了用户%s[%d]的基本信息!', $me->name, $me->id, $user->name, $user->id), 'journal');

			}
		}

		if ($form['delete'] && $me->id != $user->id) {
			if ($user->delete()) {
				$auth = new Auth($user->token);
				$auth->remove();
				Site::message(Site::MESSAGE_NORMAL, T('删除成员成功!'));
				URI::redirect('!admin/user');
			}
			else {
				Site::message(Site::MESSAGE_ERROR, T('删除成员失败!'));
			}
		}

		$tabs->content = V('admin:user/edit.info', array(
							   'form' => $form,
							   'user' => $user
							   ));
	}

	function _edit_icon($e, $tabs) {
		$user = $tabs->user;

		if (Input::form('submit')) {
			$file = Input::file('file');
			if ($file['tmp_name']) {
				try {
					$ext = File::extension($file['name']);
					$user->save_icon(Image::load($file['tmp_name'], $ext));
					Site::message(Site::MESSAGE_NORMAL, T('用户头像已更新!'));
				}
				catch(Error_Exception $e){
					Site::message(Site::MESSAGE_ERROR, $e->getMessage());

					Site::message(Site::MESSAGE_ERROR, T('用户头像更新失败!'));
				}
			}
			else{
				Site::message(Site::MESSAGE_ERROR, T('请选择您要上传的用户头像文件'));
			}
		}

		$tabs->content = V('admin:user/edit.icon');
	}

	function _edit_account($e, $tabs) {
		$user = $tabs->user;
		$me = L('ME');
		list($token, $backend) = Auth::parse_token($user->token);

		if (Input::form('submit')) {

			$auth_backends = Config::get('auth.backends');
			$auth = new Auth($user->token);
			$form = Form::filter(Input::form());

			try {
				//不为readonly的backends才需要判断密码
				if(!$auth_backends[$form['backend']]['readonly']) {
					// 修改密码
					if (isset($form['new_pass'])) {
						$form->validate('new_pass', 'length(6, 24)', T('输入的密码不能小于6位，最长24位！'))
							->validate('confirm_pass', 'compare(==new_pass)', T('两次输入的密码不一致！'));

	                    if (($me->id == $user->id) && $need_validate) {
	                        //本人修改, 需输入原始密码
	                        $form->validate('old_pass', 'not_empty', T('原始密码不能为空！'));
	                    }
	                }
	            }

				if (!$form->no_error) throw new Exception;

				if ($me->access('管理所有内容') && ($token != $form['token'] || $backend != $form['backend'])) {

					$form->validate('token', 'is_token', T('登录帐号不符合要求！'));
					if (!$this->_validate_token_backend($form['backend'])) {
						$form->set_error('backend', '验证后台不合法');
					}

					$new_token = $form['token'];
					$new_token = preg_replace('/\|.*$/', '', $new_token);
					$new_backend = $form['backend'];

					if (!$form->no_error) {
						throw new Exception;
					}

					$new_full_token = Auth::make_token($new_token, $new_backend);
					if (User_Model::is_reserved_token($new_full_token)) {
						throw new Exception(T('您输入的帐号已被管理员保留。'));
					}
					if (O('user', array('token' => $new_full_token))->id) {
						$form->set_error('token', '登陆账号已被使用');
						throw new Exception;
					}

					// 修改验证后台
					if ($backend != $new_backend) {

						$new_auth = new Auth($new_full_token);
						if ($new_auth->create(uniqid()) || $new_auth->change_token($new_token)) {
							$user->token = $new_full_token;
							if ($user->save()) {
								Site::message(Site::MESSAGE_NORMAL, T('用户登录帐号已更新'));

								/* 记录日志 */
								$log = sprintf('[people] %s[%d]修改了用户%s[%d]的帐号',
											   L('ME')->name, L('ME')->id,
											   $user->name, $user->id);
								Log::add($log, 'journal');

								if ($form['remove_former_auth'] == 'on') {
									$ret = $auth->remove();
									if ($ret) {
										Site::message(Site::MESSAGE_NORMAL, T('旧登陆账号已删除'));
									}
									else {
										Site::message(Site::MESSAGE_ERROR, T('旧登陆账号删除失败'));
									}
								}
							}
							else {
								throw new Exception(T('用户登录帐号更新失败!'));
							}
						}
						else {
							throw new Exception(T('新帐号创建失败'));
						}
					}
					// 仅修改登录名
					else if ($token != $new_token) {
						$old_token = $user->token;

						if ($auth->change_token($new_token)) {
							$user->token = $new_full_token;
							if ($user->save()) {
								Site::message(Site::MESSAGE_NORMAL, T('用户登录帐号已更新'));

								/* 记录日志 */
								$log = sprintf('[people] %s[%d]修改了用户%s[%d]的帐号',
											   L('ME')->name, L('ME')->id,
											   $user->name, $user->id);
								Log::add($log, 'journal');
							}
							else {
								throw new Exception(T('用户登录帐号更新失败!'));
							}
						}
						else {
							throw new Exception(T('登陆账号更新失败!'));
						}
					}
				}

				$need_validate = !$auth_backends[$new_backend]['readonly'];

				if (($me->id == $user->id) && $need_validate) {
					//本人要求输入原始密码
					if (!$auth->verify($form['old_pass'])) {
						throw new Exception(T('旧密码输入错误!'));
					}
				}

                if ($form['new_pass'] && !$auth_backends[$form['backend']]['readonly']) {
                    if ($new_auth) $auth = $new_auth;
                    if ($auth->change_password($form['new_pass'])) {
                        $user->must_change_password = $form['must_change_password'];
                        $user->save();
                        Site::message(Site::MESSAGE_NORMAL, T('用户密码已更新'));

                        /* 记录日志 */
                        $log = sprintf('[people] %s[%d]修改了用户%s[%d]的密码',
                                       L('ME')->name, L('ME')->id,
                                       $user->name, $user->id);
                        Log::add($log, 'journal');
                    }
                    else {
                        throw new Exception(T('用户密码更新失败!'));
                    }
                }
			}
			catch (Exception $e) {
				$message = $e->getMessage();
				if ($message) Site::message(Site::MESSAGE_ERROR, $message);
			}
		}
		$this->layout->form = $form;
		$tabs->content = V('admin:user/edit.account', array(
				'form' => $form,
				'token'=> isset($new_token) ? $new_token : $token,
				'backend'=>isset($new_backend) ? $new_backend : $backend)
			);
	}

	function _edit_role($e, $tabs) {

		$user = $tabs->user;
		$me = L('ME');
		if (!$me->is_allowed_to('管理角色', $user)) {
			$uneditable = TRUE;
		}

		if(!$uneditable && Input::form('submit')) {

			$form = Form::filter(Input::form());

			$user_roles = $user->roles();

			if($form->no_error) {
				$form_roles = (array)$form['roles'];

				$add_roles = array_keys(array_diff_key($form_roles, $user_roles));
				$subtract_roles = array_keys(array_diff_key($user_roles, $form_roles));

				$add_roles = array();
				$my_perms = $me->perms();

				$legal_perms = (array) L('PERMS');
				$roles = L('ROLES');
				$is_admin = $me->access('管理所有内容') || $me->access('管理分组');
				foreach (array_diff_key($form_roles, $user_roles) as $rid => $foo) {
					if ($roles[$rid]) {
						if ($is_admin) {
							$add_roles[] = $rid;
						}
						else {
							$perms = array_intersect_key($roles[$rid]->perms, $legal_perms);
							if (count(array_diff_key($perms, $my_perms)) == 0) {
								$add_roles[] = $rid;
							}
						}
					}
				}

				if (count($add_roles)>0) $user->connect(array('role', $add_roles));
				if (count($subtract_roles)>0) $user->disconnect(array('role', $subtract_roles));

				/* 记录日志 */
				/*
				$log = sprintf('[people] %s[%d]修改了用户%s[%d]的角色',
							   $me->name, $me->id,
							   $user->name, $user->id);
				Log::add($log, 'journal');
				*/

				Site::message(Site::MESSAGE_NORMAL, HT('用户分组信息修改成功！'));
			}
		}

		$tabs->content = V('admin:user/edit.role', array('user'=>$user, 'uneditable'=>$uneditable));
	}

	private function _validate_token_backend($backend) {
		$backends = Config::get('auth.backends');
		return in_array(trim($backend), array_keys($backends));
	}

}


class User_AJAX_Controller extends AJAX_Controller {
	function index_delete_icon_click() {
		$user = O('user', Input::form('id'));

		if ($user->id) {
			if (JS::confirm(T('您确定要删除用户头像么?'))) {
				$user->delete_icon();
				Site::message(Site::MESSAGE_NORMAL, T('用户头像删除成功!'));
				JS::refresh();
			}
		}
	}
}
