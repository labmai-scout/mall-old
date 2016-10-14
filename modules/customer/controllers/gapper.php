<?php

class Gapper_Controller extends Layout_Customer_Controller {

	public function index($cid=0) {

		$form = Input::form();
		$me = L('ME');

		if ($form['app']) {
			$app_key  = $form['app'];
			$app = Config::get('gapper.apps')[$app_key];
		}

		$customer = O('customer', $cid);

		if (!$customer->id || !$customer->has_member($me)) {
			URI::redirect('error/404');
		}

		if ($customer->owner->id != $me->id) {
			URI::redirect('error/401');
		}

		$group_id = (int)$customer->gapper_group;
		if (!$group_id) {
			$customer_group = O('customer_group', ['customer'=>$customer]);
			$group_id = $customer_group->gapper_group;
		}

		if (!$group_id) {
			URI::redirect('error/401');
		}

		$uid = $me->gapper_user;
		$rpc = Gapper::get_RPC();
		$login_token = $rpc->gapper->user->getLoginToken((int)$uid, $app['client_id']);
		URI::redirect(URI::url($app['url'], ['gapper-token'=>$login_token, 'gapper-group'=>$group_id]));

		/*
		$form_token = Session::temp_token('gapper_');
		$_SESSION[$form_token] = ['app'=>$app_key, 'customer_id'=>$cid];
		try {
			$rpc = Gapper::get_RPC();

			//如果 APP 不存在 跳转到
			if (!$rpc) {
				URI::redirect('error/401');
				return;
			}

			//判断 gapper 是否有这个组
			$group_id = (int)$customer->gapper_group;
			if (!$group_id) {
				throw new Error_Exception;
			}

			if(!$rpc->gapper->group->getInfo($group_id)) {
				//gapper那边把组删除了，则应该清楚本地组的gapper_group
				$customer->gapper_group = 0;
				$customer->save();
				throw new Error_Exception;
			}

			//判断该用户是否有 gapper 帐号
			if(!Gapper::is_gapper_user($me)) {
				//gapper中用户不会删除，所以没有做gapper_user清空的处理
				throw new Error_Exception;
			}

			$token = (string)$me->token;
			$user_gapper_groups = (array)$me->gapper_groups;
			if (!in_array($customer->gapper_group, $user_gapper_groups)) {
				if($rpc->gapper->group->addMember((int)$group_id, $token)) {
					$user_gapper_groups[] = $customer->gapper_group;
					$me->gapper_groups = $user_gapper_groups;
					$me->save();
				}
			}

			//RPC 登陆然后带着token进行跳转
			$login_token = $rpc->gapper->user->getLoginToken($token, $app['client_id']);
			URI::redirect(URI::url($app['url'], ['gapper-token'=>$login_token, 'gapper-group'=>$customer->gapper_group]));

		}
		catch(Error_Exception $e) {
			$this->app_intro($app, $customer, $form_token);
			return;
		}
		*/
	}

	public function help() {
		$this->layout->body = V('customer:gapper/help');
	}

	public function upgrade() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$info = $_SESSION[$form_token];

		$me = L('ME');

		if($info['app']) {
			$app_key  = $info['app'];
			$app = Config::get('gapper.apps')[$app_key];
		}

		$customer = O('customer', $info['customer_id']);

		if (!$customer->id || !$customer->has_member($me)) {
			URI::redirect('error/401');
		}

		try {
			$rpc = Gapper::get_RPC();
			if(!$rpc) URI::redirect('error/401');

			if(!$rpc->gapper->user->getInfo((string)$me->token)) {
				//普通用户和负责人区分开
				if($me->id == $customer->owner->id) {
					URI::redirect('!customer/gapper/incharge_upgrade', ['form_token'=>$form_token]);
				}
				else {
					URI::redirect('!customer/gapper/user_upgrade', ['form_token'=>$form_token]);
				}
			}
			elseif(!$customer->gapper_group || !$rpc->gapper->group->getInfo((int)$customer->gapper_group)) {
				URI::redirect('!customer/gapper/group_upgrade', ['form_token'=>$form_token]);
			}
			else {
				URI::redirect('!customer/gapper/success', ['form_token'=>$form_token]);
			}
		}
		catch(Error_Exception $e) {}

	}

	public function app_intro($app, $customer, $form_token) {
		$me = L('ME');

		try {
			$rpc = Gapper::get_RPC();
			if(!$rpc) throw new Error_Exception;

			$app_info = $rpc->gapper->app->getInfo($app['client_id']);

			if ($app_info['icon_url']) {
				$app['icon_url'] = $app_info['icon_url'];
			}
		}
		catch(Exception $e) {}

		$view = $customer->owner->id == $me->id ? 'incharge' : 'user';

		$cancel_url = $_SERVER['HTTP_REFERER'] ? : $customer->url(NULL, NULL, NULL, 'view');

		$this->layout->body = V('customer:gapper/upgrade/'.$view.'/intro', ['app'=>$app, 'customer'=>$customer, 'form_token'=>$form_token, 'cancel_url'=>$cancel_url]);
	}

	//负责人 升级用户
	public function incharge_upgrade() {
		$me = L('ME');

		$form = Form::filter(Input::form());
		$form_token = $form['form_token'];
		$info = $_SESSION[$form_token];

		//如果已经绑定用户，则直接跳转到组升级
		if(Gapper::is_gapper_user($me)) {
			URI::redirect('!customer/gapper/upgrade', ['form_token'=>$form_token]);
		}

		$customer_id = $info['customer_id'];
		$customer = O('customer', $customer_id);
		if(!$customer->id || $customer->owner->id != $me->id) {
			URI::redirect('error/401');
		}

		if($form['submit']) {
			try {

				$form
					->validate('name', 'not_empty', T('姓名不能为空!'))
					->validate('email', 'is_email', T('邮箱格式不正确!'))
                    ->validate('password', 'length(8,24)', T('输入的密码不能小于8位!'));

				if($form->no_error) {
					$rpc = Gapper::get_RPC();
					if(!$rpc) throw new Error_Exception;

					$user = [
						'name' => $form['name'],
						'email' => $form['email'],
						'username' => $form['email'],
						'password' => $form['password'],
					];

					$user_id = $rpc->gapper->user->registerUser($user);

					if(!$user_id) {
						$form->set_error('*', T('gapper用户生成失败!'));
					}
					else{

						$gapper_user_info = $rpc->gapper->user->getInfo((int)$user_id);

						$old_token = $me->token;
						$me->token = $gapper_user_info['username'];
						$me->gapper_user = $gapper_user_info['id'];

						if($me->save()) {

							// 如果存在gapper_group则需要将用户加入到对应的组中
							if((!$customer->gapper_group || $rpc->gapper->group->addMember((int)$customer->gapper_group, (int)$user_id))
								&& Gapper::link_identity($me, $old_token)) {
								Auth::login($me->token);
								URI::redirect('!customer/gapper/group_upgrade', ['form_token'=>$form_token]);
							}
							else {
								//添加用户失败的处理
								$me->token = $old_token;
								$me->save();
								$form->set_error('*', T('用户关联失败!'));
							}
						}
						else{
							$form->set_error('*', T('用户关联失败!'));
						}
					}
				}
			}
			catch(Exception $e) {
				if($e->getCode() == 10001) {
					$form->set_error('email', T('Email已存在!'));
				}
				else {
					$form->set_error('*', T('系统错误请联系管理员!'));
				}
			}
		}

		$this->layout->body = V('customer:gapper/upgrade/incharge/user_upgrade', ['customer'=>$customer, 'form'=>$form, 'form_token'=>$form_token]);
	}

	function bad_browser() {
		$this->layout->body = V('customer:gapper/bad_browser');
	}

	//普通用户升级用户
	public function user_upgrade() {
		$me = L('ME');

		$form = Form::filter(Input::form());
		$form_token = $form['form_token'];
		$info = $_SESSION[$form_token];

		$customer_id = $info['customer_id'];
		$customer = O('customer', $customer_id);
		if(!$customer->id || !$customer->gapper_group) {
			URI::redirect('error/401');
		}

		if($form['submit']) {
			try {
				$rpc = Gapper::get_RPC();
				if(!$rpc) URI::redirect('error/401');
				$form
					->validate('name', 'not_empty', T('姓名不能为空!'))
					->validate('email', 'is_email', T('邮箱格式不正确!'))
					->validate('password', 'length(8,24)', T('输入的密码不能小于8位!'));

				if($form->no_error) {

					$user = [
						'name' => $form['name'],
						'email' => $form['email'],
						'username' => $form['email'],
						'password' => $form['password'],
					];

					$gapper_user_id = $rpc->gapper->user->registerUser($user);

					if(!$gapper_user_id) {
						$form->set_error('*', T('Gapper用户生成失败!'));
					}
					else{

						$gapper_user_info = $rpc->gapper->user->getInfo((int)$gapper_user_id);

						$old_token = $me->token;
						$me->token = $gapper_user_info['username'];
						$me->gapper_user = $gapper_user_info['id'];

						//给user打个标记，记住所在的gapper分组
						$me->gapper_groups = [$customer->gapper_group];

						if($me->save()) {

							if($rpc->gapper->group->addMember((int)$customer->gapper_group, (int)$gapper_user_id)
								&& Gapper::link_identity($me, $old_token)) {
								Auth::login($me->token);
								URI::redirect('!customer/gapper/success', ['form_token'=>$form_token]);
							}
							else {
								//添加用户失败的处理
								$me->token = $old_token;
								$me->save();
								$form->set_error('*', T('用户关联失败!'));
							}
						}
						else{
							$form->set_error('*', T('用户关联失败!'));
						}
					}
				}
			}
			catch(Exception $e) {
				if($e->getCode() == 10001) {
					$form->set_error('email', T('Email已存在!'));
				}
				else {
					$form->set_error('*', T('系统错误请联系管理员!'));
				}
			}
		}

		$this->layout->body = V('customer:gapper/upgrade/user/upgrade', ['customer'=>$customer, 'form'=>$form, 'form_token'=>$form_token]);
	}

	//负责人登陆
	public function incharge_login() {
		$form = Form::filter(Input::form());
		$form_token = $form['form_token'];
		$info = $_SESSION[$form_token];

		$me = L('ME');
		$customer_id = $info['customer_id'];
		$customer = O('customer', $customer_id);

		if(!$customer->id || $customer->owner->id != $me->id) {
			URI::redirect('error/401');
		}

		if(Gapper::is_gapper_user($me)) {
			URI::redirect('!customer/gapper/upgrade', ['form_token'=>$form_token]);
		}

		$me = L('ME');
		if($form['submit']) {
			try{
				$form
					->validate('email', 'is_email', T('邮箱格式不正确!'))
					->validate('password', 'not_empty', T('密码不能为空!'));

				if($form->no_error) {

					$rpc = Gapper::get_RPC();
					if(!$rpc) URI::redirect('error/401');

					$ret = $rpc->gapper->user->verify($form['email'], $form['password']);

					if(!$ret) {
						$form->set_error('*', T('错误的电子邮箱、密码组合!'));
					}
					else{
						$gapper_user_info = $rpc->gapper->user->getInfo($form['email']);
						if(!$gapper_user_info['id']) {
							throw new Error_Exception;
						}

						$user = O('user', ['token'=>$gapper_user_info['username']]);
						if ($user->id) {
							$form->set_error('*', T('您选择的账户已经绑定了其他用户'));
						}
						else {
							$old_token = $me->token;
							$me->token = $gapper_user_info['username'];
							$me->gapper_user = $gapper_user_info['id'];

							if($me->save()) {

								if((!$customer->gapper_group || $rpc->gapper->group->addMember((int)$customer->gapper_group, (string)$me->token))
									&& Gapper::link_identity($me, $old_token)) {
									Auth::login($me->token);
									URI::redirect('!customer/gapper/group_upgrade', ['form_token'=>$form_token]);
								}
								else {
									//添加用户失败的处理
									$me->token = $old_token;
									$me->save();
									$form->set_error('*', T('用户关联失败!'));
								}
							}
							else {
								$form->set_error('*', T('用户关联失败!'));
							}
						}
					}
				}
			}
			catch(Exception $e) {
				$form->set_error('*', T('系统错误请联系管理员!'));
			}
		}

		$this->layout->body = V('customer:gapper/upgrade/incharge/login', ['customer'=>$customer, 'form'=>$form, 'form_token'=>$form_token]);
	}

	//普通用户登陆
	public function user_login() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$form_token = $form['form_token'];
		$info = $_SESSION[$form_token];

		$customer_id = $info['customer_id'];
		$customer = O('customer', $customer_id);
		if(!$customer->id || !$customer->gapper_group) {
			URI::redirect('error/401');
		}

		if(Gapper::is_gapper_user($me)) {
			URI::redirect('!customer/gapper/upgrade', ['form_token'=>$form_token]);
		}

		if($form['submit']) {
			try{
				$form
					->validate('email', 'is_email', T('邮箱格式不正确!'))
					->validate('password', 'not_empty', T('密码不能为空!'));

				if($form->no_error) {

					$rpc = Gapper::get_RPC();
					if(!$rpc) URI::redirect('error/401');

					$ret = $rpc->gapper->user->verify($form['email'], $form['password']);

					if(!$ret) {
						$form->set_error('*', T('错误的电子邮箱、密码组合!'));
					}
					else{
						$gapper_user_info = $rpc->gapper->user->getInfo($form['email']);
						if(!$gapper_user_info['id']) {
							throw new Error_Exception;
						}

						$user = O('user', ['token'=>$gapper_user_info['username']]);
						if ($user->id) {
							$form->set_error('*', T('您选择的账户已经绑定了其他用户'));
						}
						else {
							$old_token = $me->token;
							$me->token = $gapper_user_info['username'];
							$me->gapper_user = $gapper_user_info['id'];

                            //给user打个标记，记住所在的gapper分组
                            $me->gapper_groups = [$customer->gapper_group];

							if($me->save()) {

								if($rpc->gapper->group->addMember((int)$customer->gapper_group, (string)$me->token) && Gapper::link_identity($me, $old_token)) {
									Auth::login($me->token);
									URI::redirect('!customer/gapper/success', ['form_token'=>$form_token]);
								}
								else {
									$me->token = $old_token;
									$me->save();
									$form->set_error('*', T('用户关联失败!'));
								}
							}
							else {
								$form->set_error('*', T('用户关联失败!'));
							}
						}
					}
				}
			}
			catch(Exception $e) {
				$form->set_error('*', T('系统错误请联系管理员!'));
			}
		}

		$this->layout->body = V('customer:gapper/upgrade/user/login', ['customer'=>$customer, 'form'=>$form, 'form_token'=>$form_token]);
	}

	//负责人升级组信息
	public function group_upgrade() {
		$me = L('ME');

		$form = Form::filter(Input::form());
		$form_token = $form['form_token'];
		$info = $_SESSION[$form_token];

		$customer_id = $info['customer_id'];
		$customer = O('customer', $customer_id);

		if(!$customer->id || $customer->owner->id != $me->id || !Gapper::is_gapper_user($me)) {
			URI::redirect('error/401');
		}

		if($customer->gapper_group) {
			URI::redirect('!customer/gapper/upgrade', ['form_token'=>$form_token]);
		}

		try {
			$rpc = Gapper::get_RPC();
			if(!$rpc) URI::redirect('error/401');
			//获得自己是管理员的组
			$groups = $rpc->gapper->user->getGroups((string)$me->token);

			$mall_client_id = Config::get('mall.gapper')['client_id'];
			$rpc = Gapper::get_RPC();

			foreach ($groups as $key => $group) {

				$ret = $rpc->gapper->app->getGroupInfo($mall_client_id, (int)$group['id']);
				if ($ret) {
					unset($groups[$key]);
				}
                if (!$group['admin']) {
                    unset($groups[$key]);
                }
			}

			if($form['submit']) {

				$form
					->validate('name', 'not_empty', T('组标识不能为空!'))
					->validate('name', 'is_token', T('请填写符合规则的组标识!'))
					->validate('title', 'not_empty', T('组名称不能为空!'));

				if($form->no_error) {

					$group_info = [
						'user' => (string)$me->token,
						'name' => $form['name'],
						'title' => $form['title'],
					];

					$group_id = $rpc->gapper->group->create($group_info);

					if(!$group_id) {
						$form->set_error('*', T('Gapper组生成失败!'));
					}
					else {
						$customer->gapper_group = $group_id;
						if($customer->save()) {

							$mall_client_id = Config::get('mall.gapper')['client_id'];
							$rpc->gapper->app->installTo($mall_client_id, 'group', (int)$group_id);

							$mall_apps = Config::get('gapper.apps');
							foreach ($mall_apps as $ma) {
								$rpc->gapper->app->installTo($ma['client_id'], 'group', (int)$group_id);
							}

							//给user打个标记，记住所在的gapper分组
							$user_gapper_groups = $me->gapper_groups;
							$user_gapper_groups[] = $customer->gapper_group;
							$me->gapper_groups = $user_gapper_groups;
							$me->save();

							URI::redirect('!customer/gapper/success', ['form_token'=>$form_token]);
						}
						else {
							$form->set_error('*', T('组关联失败!'));
						}
					}
				}
			}

		}
		catch(Exception $e) {
			if($e->getCode() == 10001) {
				$form->set_error('name', T('组标识已存在!'));
			}
			else {
				$form->set_error('*', T('系统错误请联系管理员!'));
			}
		}

		$this->layout->body = V('customer:gapper/upgrade/incharge/group_upgrade', ['customer'=>$customer, 'groups'=>$groups, 'form'=>$form, 'form_token'=>$form_token]);

	}

	//升级成功
	public function success() {
		$me = L('ME');

		$form = Form::filter(Input::form());
		$form_token = $form_token ?: $form['form_token'];
		$info = $_SESSION[$form_token];

		$customer_id = $info['customer_id'];
		$customer = O('customer', $customer_id);

		if(!$customer->id || !Gapper::is_gapper_user($me) || !$customer->gapper_group) {
			URI::redirect('error/401');
		}

		$role = $customer->owner->id == $me->id ? 'incharge' : 'user';

		$this->layout->body = V('customer:gapper/upgrade/'.$role.'/success', ['customer'=>$customer, 'groups'=>$groups, 'form_token'=>$form_token]);
	}

}

class Gapper_AJAX_Controller extends AJAX_Controller {

	function index_bind_group_click() {
		$me = L('ME');

		if(!JS::confirm(H(T('您确定选择该组进行关联?')))) return;
		$form = Input::form();
		if(!$form['gid']) return;

		$form_token = $form['form_token'];
		$info = $_SESSION[$form_token];

		$customer_id = $info['customer_id'];
		$customer = O('customer', $customer_id);
		if(!$customer->id) return;

		try{
			$rpc = Gapper::get_RPC();
			if(!$rpc) JS::redirect('error/401');

			if($rpc->gapper->user->getGroupInfo((string)$me->token, (int)$form['gid'])) {
				$customer->gapper_group = $form['gid'];
				if($customer->save()) {

					//把mall加到这个组里
					$mall_client_id = Config::get('mall.gapper')['client_id'];
					$rpc->gapper->app->installTo($mall_client_id, 'group', (int)$form['gid']);

					$mall_apps = Config::get('gapper.apps');
					foreach ($mall_apps as $ma) {
						$rpc->gapper->app->installTo($ma['client_id'], 'group', (int)$form['gid']);
					}

					JS::redirect(URI::url('!customer/gapper/success', ['form_token'=>$form_token]));
				}
			}
		}
		catch(Exception $e){
			JS::alert(T('系统错误请联系管理员!'));
		}
	}
}
