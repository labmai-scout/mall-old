<?php

class Profile_Controller extends Base_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->layout->title = T('我的档案');
	}

	function index() {

		$user = L('ME');
		if (!$user->id) {
			URI::redirect('error/404');
		}

		$content = V('people:view/index', array('user'=>$user));

		$this->layout->title = HT('我的档案');
		$this->layout->body = $content;

	}

	function view($id) {

        $user = O('user', $id);
		if (!$user->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');

		if($user->id == $me->id) {
			URI::redirect('!people/profile/index');
		}

		//当前用户与该用户在customer有交集的时候才可查看
		$customers = Q("{$me}<member customer")->to_assoc('id', 'id');
		$user_customers = Q("{$user}<member customer")->to_assoc('id', 'id');
		$me_vendors = Q("{$me}<member vendor")->to_assoc('id', 'id');
		$user_vendors = Q("{$user}<member vendor")->to_assoc('id', 'id');

		$same_customers = array_intersect($customers, $user_customers);
		$same_vendors = array_intersect($me_vendors, $user_vendors);

		if((!count($same_customers) && !count($same_vendors)) && !$me->is_admin()) {
			URI::redirect('error/404');
		}

		$content = V('people:view', array('user'=>$user));

		$this->layout->title = HT('我的档案');
		$this->layout->body = $content;
	}

	function edit($tab = 'info') {
		$user = L('ME');
		if (!$user->id) {
			URI::redirect('error/404');
		}
		$status = Customer_Model::BIND_STATUS_SUCCESS;
		$count = Q("{$user}<member customer[bind_status=$status]")->total_count();
		if ($count) {
			URI::redirect('error/401');
		}
		list($token, $backend) = Auth::parse_token($user->token);
		list(,$uuid) = explode('%', $backend);
		Event::bind('profile.edit.content', array($this, '_edit_user_info'), 0, 'info');
		Event::bind('profile.edit.content', array($this, '_edit_icon'), 0, 'icon');
		if (!$uuid) {
			Event::bind('profile.edit.content', array($this, '_edit_account'), 0, 'account');
		}
		Event::bind('profile.edit.content', array($this, '_edit_role'), 0, 'role');
        /*
        Event::bind('profile.edit.content', array($this, '_edit_notification'), 0, 'notification');
         */

		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
			->add_tab('info', array(
						'url' => $user->url('info', NULL, NULL, 'edit'),
						'title' => T('基本信息')
			))
			->add_tab('icon', array(
						'url' => $user->url('icon', NULL, NULL, 'edit'),
						'title' => T('头像')
			));
		if (!$uuid) {
			$secondary_tabs
				->add_tab('account', array(
						'url'=> $user->url('account', NULL, NULL, 'edit'),
						'title'=>T('帐号'),
			));
		}
		$secondary_tabs
			->add_tab('role', array(
						'url'=>$user->url('role', NULL, NULL, 'edit'),
						'title'=>T('角色'),
			))
            /*
             * [by hongjie.zhu] 2015-01-28 发现为用户提供了界面，但是用户的设置其实没有生效!
			->add_tab('notification', array(
						'url'=>$user->url('notification', NULL, NULL, 'edit'),
						'title'=>T('通知'),
			))
             */
			->set('class', 'secondary_tabs')
			->set('user', $user)
			->content_event('profile.edit.content')
			->select($tab);

		$content = V('people:edit', array(
						 'secondary_tabs' => $secondary_tabs
						 ));

		$breadcrumb = array(
			array(
				'url' => $user->url(NULL, NULL, NULL, 'view'),
				'title' => HT('我的档案')
				),
			array(
				'url' => $user->url($tab, NULL, NULL, 'edit'),
				'title' => HT('修改')
				)
			);

		$this->layout->body->primary_tabs = Widget::factory('tabs');
		$this->layout->body->primary_tabs
			->add_tab('edit', array( '*' => $breadcrumb ))
			->set('content', $content)
			->select('edit');

		$this->layout->title = HT('修改我的档案');
	}

	function _edit_user_info($e, $tabs) {

		$form = Form::filter(Input::form());
		$user = $tabs->user;
		$status = Customer_Model::BIND_STATUS_SUCCESS;
		$count = Q("{$user}<member customer[bind_status=$status]")->total_count();
		if ($count) {
			URI::redirect('error/401');
		}

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

				if ($user->save()) {
					Site::message(Site::MESSAGE_NORMAL, T('修改成员成功!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('修改成员失败!'));
				}

			}
		}

		$tabs->content = V('people:edit.info', array(
							   'form' => $form,
							   'user' => $user
							   ));
	}

	function _edit_icon($e, $tabs) {
		$user = $tabs->user;
		$status = Customer_Model::BIND_STATUS_SUCCESS;
		$count = Q("{$user}<member customer[bind_status=$status]")->total_count();
		if ($count) {
			URI::redirect('error/401');
		}
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

		$tabs->content = V('people:edit.icon');
	}

	function _edit_account($e, $tabs) {
		$user = $tabs->user;
		$status = Customer_Model::BIND_STATUS_SUCCESS;
		$count = Q("{$user}<member customer[bind_status=$status]")->total_count();
		if ($count) {
			URI::redirect('error/401');
		}
		$me = L('ME');
		list($token, $backend) = Auth::parse_token($user->token);
		list(,$uuid) = explode('%', $backend);
		if ($uuid) return false;
		if (Input::form('submit')) {

			$auth_backends = Config::get('auth.backends');
			$auth = new Auth($user->token);
			$form = Form::filter(Input::form());

			try {
				// 修改密码
				if ($form['new_pass']) {
					$form->validate('new_pass', 'length(6, 24)', T('输入的密码不能小于6位，最长24位！'))
						->validate('confirm_pass', 'compare(==new_pass)', T('两次输入的密码不一致！'));

					if (($me->id == $user->id) && $need_validate) {
						//本人修改, 需输入原始密码
						$form->validate('old_pass', 'not_empty', T('原始密码不能为空！'));
					}
				}
				else {
					//不输入密码进行提示
					$form->set_error('new_pass', T('输入的密码不能小于6位，最长24位！'));
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
						$form->set_error('token', '用户名已被使用');
						throw new Exception;
					}

					// 修改验证后台
					if ($backend != $new_backend) {

						$new_auth = new Auth($new_full_token);
						if ($new_auth->create(uniqid())) {
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
										Site::message(Site::MESSAGE_NORMAL, T('旧token已删除'));
									}
									else {
										Site::message(Site::MESSAGE_ERROR, T('旧token删除失败'));
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
							throw new Exception(T('token 更新失败!'));
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

				if ($form['new_pass']) {
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
		$tabs->content = V('people:edit.account', array(
							   'form' => $form,
							   'token'=> isset($new_token) ? $new_token : $token,
							   'backend'=>isset($new_backend) ? $new_backend : $backend)
			);
	}

	function _edit_role($e, $tabs) {
		$user = $tabs->user;
		$status = Customer_Model::BIND_STATUS_SUCCESS;
		$count = Q("{$user}<member customer[bind_status=$status]")->total_count();
		if ($count) {
			URI::redirect('error/401');
		}
		$tabs->content = V('people:edit.role', array(
					'user'=>$user
		));

	}

	function _edit_notification($e, $tabs) {
		$user = $tabs->user;
		$tabs->content = V('people:edit/notification/types', array(
					'types' => Config::get('notification.types')));
	}

	private function _validate_token_backend($backend) {
		$backends = Config::get('auth.backends');
		return in_array(trim($backend), array_keys($backends));
	}

}

class Profile_AJAX_Controller extends AJAX_Controller {
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

	function index_get_type_item_click () {
        $form = Input::form();
        Output::$AJAX['#'. $form['container_id']. ' > div:eq(0)'] = array(
            'data'=>(string) V('edit/notification/relate_view', array('key'=>$form['key'])),
            'mode'=>'replace'
			);
    }

    function index_edit_notification_types_submit() {
        $me = L('ME');
        $form = Input::form();

        //获取设置的用户分类
        $key = $form['key'];
        $types = Config::get('notification.types');

        //获取分类下属类目
        $types = $types[$key];
		// var_dump($types);

        //获取所有的notification的send方式
        $sends = array_keys((array) Config::get('notification.handlers'));

		// var_dump($form);
		// var_dump($sends);

		foreach ($types as $title => $notification_key) {
			if ($title[0] == '#') continue;
            foreach ($sends as $send_type) {

                if ($form['checks']["$notification_key.$send_type"] == 'on') {
                    $value = TRUE;
                }
                else {
                    $value = FALSE;
                }

				// notification.receive.daily_notif_for_admin.messages.1
                Site::set("receive.notification.$notification_key.$send_type.$me->id", $value);
            }

		}

		/*
        foreach (array_keys($form['titles']) as $k) {
			// echo $k . "\n";


            foreach ($sends as $send_type) {
                if ($form[($k). '_'. $send_type] == 'on') {
                    $default_value = TRUE;
                }
                else {
                    $default_value = FALSE;
                }

                //用户预约仪器消息提醒.message.110
				echo "notification.$k.$send_type.$me->id";
                Site::set("notification.$k.$send_type.$me->id", $default_value);
            }
        }
		*/

        Output::$AJAX['#'. $form['message_uniqid']] = array(
            'data' =>(string) V('edit/notification/message'),
            'mode'=>'append'
			);
    }

}

