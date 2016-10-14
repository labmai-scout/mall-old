<?php

class Profile_Controller extends Layout_Customer_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->layout->title = T('买方管理');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
	}

	function index($id=0, $tab='members', $stab='active') {

		$me = L('ME');
		$customer = O('customer', $id);

		if (!$customer->id ||
			!$me->is_allowed_to('买方查看', $customer)) {
			URI::redirect('error/401');
		}

        // 如果买方已经升级lab-orders
        // 直接调走
        // echo "<a href='".$apps['lab-orders']['url']."'>".H($customer->name)."</a>";
        if ($customer->check_app_installed('lab-orders')) {
            $apps = Config::get('gapper.apps', []);
            $app = $apps['lab-orders'];
            $url = $app['url'];
            if ($me->gapper_user && $customer->gapper_group) {
                $rpc = Gapper::get_RPC();
                $login_token = $rpc->gapper->user->getLoginToken((int)$me->gapper_user, $app['client_id']);
                $url = URI::url($url, ['gapper-token'=>$login_token, 'gapper-group'=>$customer->gapper_group]);
            }
			URI::redirect($url);
            /*
			$this->layout->body = V('customer:lab_orders/view', array(
							 'customer' => $customer,
                         ));
             */
            return;
		}

        // content
        $secondary_tabs = Widget::factory('tabs');

        if ($tab == 'add_member' && L('ME')->is_allowed_to('买方修改成员信息', $customer) &&
            Q("$customer<member user")->total_count() < Config::get('customer.max_customer_members')) {
            $secondary_tabs->add_tab('add_member', array(
                                'url' => $customer->url('add_member'),
                                'title' =>T('添加成员'),
                                'weight' => 99,
                                ));
            Event::bind('customer.profile.content', array($this, '_index_add_member'), 0, 'add_member');
        }

        Event::bind('customer.profile.content', array($this, '_view_members'), 0, 'members');

        $secondary_tabs
            ->add_tab('members', array(
                            'title' => T('成员列表'),
                            'url' => $customer->url('members.'.$stab, NULL, NULL, 'view')
                            ))
            ->tab_event('customer.profile.tab')
            ->content_event('customer.profile.content')
            ->set('customer', $customer)
            ->set('stab', $stab)
            ->select($tab);

        $content = V('customer:profile/view', array(
                            'customer' => $customer,
                            'secondary_tabs' => $secondary_tabs,
                            ));

        // primary tabs
        $tabs = Widget::factory('tabs');
        $tabs->add_tab('profile', array(
                    'url'=> $customer->url(),
                    'title'=> H($customer->name),
                            ))
            ->set('content', $content)
            ->select('profile');

        $this->layout->title = H($customer->name);
        $this->layout->body->primary_tabs = $tabs;
	}
	function _index_add_member($e, $tabs) {

		$customer = $tabs->customer;

		if ($customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}

		$form = Form::filter(Input::form());
		$me = L('ME');
		if ($form['submit']) {
			if (!$customer->id ||
			!$me->is_allowed_to('买方修改成员信息', $customer)) {
				return;
			}
			$members = Q("$customer<member user");
			if (count($members) >= Config::get('customer.max_customer_members')) {
				return;
			}

			if($view = Event::trigger('customer.add_member.submit', $form, $customer)) {
				$tabs->content = $view;
				return;
			}

			if($form['user_id']) {

				$user = O('user', $form['user_id']);

				if(!$user->id) return;
				$customer->connect($user, 'member');
				Site::message(Site::MESSAGE_NORMAL, T('用户添加成功!'));
				URI::redirect($customer->url('view'));
				return;
			}

			$form
				->validate('token', 'is_token', T('请填写符合规则的用户帐号!'))
				->validate('name', 'not_empty', T('请填写用户姓名!'))
				->validate('email', 'is_email', T('电子邮箱输入有误!'))
				->validate('phone', 'not_empty', T('请填写联系方式!'));


			if($form['backend']  == 'database'){
			$form
				->validate('password', 'compare(==confirm_password)', T('两次输入密码不一致！'))
				->validate('password', 'length(6, 24)', T('输入的密码不能小于6位，最长24位！'));
			}

			if ($form->no_error) {
				try {
					$backend = $form['backend'] ?: 'database';
					$token = Auth::make_token($form['token'], $backend);

					if (User_Model::is_reserved_token($token)) {
						Site::message(Site::MESSAGE_ERROR, T('您输入的登录帐号已被保留。'));
						throw new Error_Exception;
					}

					if (O('user', array('token'=>$token))->id) {
						throw new Error_Exception(T('您输入的帐号在系统中已存在!'));
					}

					if (O('user', array('email'=>$form['email']))->id) {
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

					if ($form['activate']) {
						$user->atime = Date::time();
					}

					if ($form['must_change_password']){
						$user->must_change_password = TRUE;
					}

					if ($user->save()) {
						$customer->connect($user, 'member');
						Site::message(Site::MESSAGE_NORMAL, T('用户添加成功!'));
						URI::redirect($customer->url('view'));
					}
					else {
						$auth->remove();
						throw new Error_Exception(T('添加新成员失败! 请与系统管理员联系.'));
					}
				}
				catch (Exception $e) {
					Site::message(Site::MESSAGE_ERROR, $e->getMessage());
				}
			}
		}
		$tabs->content = V('profile/add_member', array(
						   'customer' => $customer,
						   'form' => $form,
						   ));
	}

	function _view_members($e, $tabs) {

		$customer = $tabs->customer;
		if ($customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}
		$stab = $tabs->stab;

		$form = Site::form();

		$secondary_tabs = Widget::factory('tabs')
			->add_tab('active', array(
						  'title' => T('已激活'),
						  'url' => $customer->url('members.active', NULL, NULL, 'view')
						  ))
			->add_tab('unactive', array(
						  'title' => T('未激活'),
						  'url' => $customer->url('members.unactive', NULL, NULL, 'view')
						  ))
			->content_event('customer.profile.members.content')
			->set('customer', $customer)
			->set('class', 'secondary_tabs')
			->select($stab);

		$selector = "$customer user.member";

		if ($stab == 'active') {
			$selector .= "[atime>0]";
		}
		else {
			$selector .= "[atime=0]";
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

		$pagination = Site::pagination($users, (int)$form['st'], 10);

		$tabs->content = V('customer:profile/index_members', array(
							   'customer' => $customer,
							   'secondary_tabs' => $secondary_tabs,
							   'users' => $users,
							   'pagination' => $pagination,
							   'form' => $form
							   ));
	}

	function member_edit($id, $cid, $tab='info') {
		$user = O('user', $id);
		$me = L('ME');
		$customer = O('customer', $cid);
		if ($customer->bind_status == Customer_Model::BIND_STATUS_SUCCESS) {
			URI::redirect('error/401');
		}

		if ($customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}
		if (!$user->id) {
			URI::redirect('error/404');
		}

		if (!$customer->id ||
			!$me->is_allowed_to('以买方成员修改信息', $customer)) {
			URI::redirect('error/404');
		}

		Event::bind('profile.customer_member_edit.content', array($this, '_edit_member_info'), 0, 'info');
		Event::bind('profile.customer_member_edit.content', array($this, '_edit_member_icon'), 0, 'icon');

		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
			->add_tab('info', array(
						'url' => URI::url('!customer/profile/member_edit.'.$id.'.'.$customer->id.'._edit_member_info'),
						'title' => T('基本信息')
			))
			->add_tab('icon', array(
						'url' => URI::url('!customer/profile/member_edit.'.$id.'.'.$customer->id.'.icon'),
						'title' => T('头像')
			))
			->set('class', 'secondary_tabs')
			->set('user', $user)
			->content_event('profile.customer_member_edit.content')
			->select($tab);

		$content = V('customer:member/edit', array(
						 'secondary_tabs' => $secondary_tabs
						 ));

		$breadcrumb = array(
			array(
				'url' => $user->url($tab, NULL, NULL, 'edit'),
				'title' => HT('修改买方成员')
				)
			);

		$this->layout->body->primary_tabs = Widget::factory('tabs');
		$this->layout->body->primary_tabs
			->add_tab('edit', array( '*' => $breadcrumb ))
			->set('content', $content)
			->select('edit');

		$this->layout->title = HT('修改人员信息');
	}

	function _edit_member_info($e, $tabs) {

		$form = Form::filter(Input::form());
		$user = $tabs->user;

        //不可以更改其它课题组、供应商、中心管理员信息
        if($user->access('管理所有内容')
            || Q("$user vendor.member")->total_count() > 0
            || Q("$user customer.member")->total_count() > 1) {
            URI::redirect('error/404');
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

		$tabs->content = V('customer:member/edit.info', array(
							   'form' => $form,
							   'user' => $user
							   ));
	}

	function _edit_member_icon($e, $tabs) {
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

		$tabs->content = V('customer:member/edit.icon');
	}


	function edit($id=0, $tab='info') {

		$customer = O('customer', $id);
		$me = L('ME');

		if (!$customer->id ||
			(!$me->is_allowed_to('以买方成员修改信息', $customer) && !$me->is_allowed_to('买方修改成员权限', $customer))) {
			URI::redirect('error/404');
		}

		Event::bind('customer.profile.edit.content', array($this, '_edit_info'), 0, 'info');
		Event::bind('customer.profile.edit.content', array($this, '_edit_icon'), 0, 'icon');
		Event::bind('customer.profile.edit.content', array($this, '_edit_address'), 0, 'address');
        Event::bind('customer.profile.edit.content', array($this, '_edit_lims'), 0, 'lims');

		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs->content_event('customer.profile.edit.content')
				->set('customer', $customer)
				->set('class', 'secondary_tabs');
		if ($me->is_allowed_to('以买方成员修改信息', $customer)) {
			$secondary_tabs
	            ->add_tab('info', array(
	                        'url' => $customer->url('info', NULL, NULL, 'edit'),
	                        'title' => HT('基本信息')
	                        ))
	            ->add_tab('icon', array(
	                        'url' => $customer->url('icon', NULL, NULL, 'edit'),
	                        'title' => HT('图标')
	                        ))
	            ->add_tab('address', array(
	                        'url' => $customer->url('address', NULL, NULL, 'edit'),
	                        'title' => HT('运送地址')
	                        ))
	            ->add_tab('lims', array(
	                        'url'=> $customer->url('lims', NULL, NULL, 'edit'),
	                        'title'=> HT('LIMS绑定')
	                        ));
		}
		else {
			$tab = 'perm';
		}
		if ($me->is_allowed_to('买方修改成员权限', $customer)) {
			Event::bind('customer.profile.edit.content', array($this, '_edit_perm'), 0, 'perm');
			$secondary_tabs->add_tab('perm', array(
				'url' => $customer->url('perm', NULL, NULL, 'edit'),
				'title' => HT('成员权限'),
			));
		}

		$secondary_tabs->select($tab);

		$content = V('customer:profile/edit', array('secondary_tabs' => $secondary_tabs));

		$breadcrumb = array(
			array(
				'url' => $customer->url(NULL, NULL, NULL, 'view'),
				'title' => HT($customer->name)
				),
			array(
				'url' => $customer->url($tab, NULL, NULL, 'edit'),
				'title' => HT('修改')
				)
			);

		$this->layout->body->primary_tabs
			->add_tab('edit', array( '*' => $breadcrumb ))
			->select('edit')
			->set('content', $content);
	}

	function _edit_info($e, $tabs) {
		$form = Form::filter(Input::form());

		$customer = $tabs->customer;
		$group_root = Tag_Model::root('group');

		if ($form['submit']) {
			$form->validate('email', 'not_empty', HT('请填写联系邮箱!'))
				->validate('email', 'is_email', HT('联系邮箱格式有误!'));

			/*
			$form
				->validate('name', 'not_empty', T('名称不能为空!'));

			$group = O('tag', array(
						   'id' => $form['group_id'],
						   'root' => $group_root,
						   ));

			if (!$group->id) {
				$form->set_error('group', T('组织机构不能为空'));
			}
			*/
			if ($form->no_error) {
				/*
				$customer->name = $form['name'];
				$customer->owner = O('user', $form['owner']);
				$customer->group = $group;
				$customer->account_no = $form['account_no'];
				*/

				$customer->email = $form['email'];
				$customer->description = $form['description'];

				if ($customer->save()) {
					/*
					$group_root->disconnect($customer);
					$customer->connect($customer->group);
					*/

					Site::message(Site::MESSAGE_NORMAL, T('修改买方信息成功!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('修改买方信息失败!'));
				}
			}
		}

		$tabs->content = V('customer:profile/edit.info', array(
							   'customer'=>$customer,
							   'form'=>$form,
							   'group_root' => $group_root,
							   ));
	}

	function _edit_icon($e, $tabs) {
		$customer = $tabs->customer;

		if (Input::form('submit')) {
			$file = Input::file('file');
			if ($file['tmp_name']) {
				try {
					$ext = File::extension($file['name']);
					$customer->save_icon(Image::load($file['tmp_name'], $ext));
					Site::message(Site::MESSAGE_NORMAL, T('买方头像已更新!'));
				}
				catch(Error_Exception $e){
					Site::message(Site::MESSAGE_ERROR, $e->getMessage());

					Site::message(Site::MESSAGE_ERROR, T('买方头像更新失败!'));
				}
			}
			else{
				Site::message(Site::MESSAGE_ERROR, T('请选择您要上传的买方头像文件'));
			}
		}

		$tabs->content = V('customer:profile/edit.icon');
	}

	function _edit_address($e, $tabs) {
		$form = Form::filter(Input::form());

		$customer = $tabs->customer;

		if ($form['submit']) {
			$form
				->validate('phone', 'not_empty', T('电话不能为空!'))
				->validate('address', 'not_empty', T('地址不能为空!'));
			if ( $form['email'] ) {
				$form->validate('email', 'is_email', T('电子邮箱格式有误!'));
			}

			if ($form->no_error) {
				$address = O('deliver_address', array('customer'=>$customer));
				if (!$address->id) $address->customer = $customer;
				$address->phone = $form['phone'];
				$address->address = $form['address'];
				$address->postcode = $form['postcode'];
				$address->email = $form['email'];

				if ($address->save()) {
					Site::message(Site::MESSAGE_NORMAL, T('修改买方送货地址成功!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('修改买方送货地址失败!'));
				}
			}
		}

		$tabs->content = V('customer:profile/edit.address', array('customer'=>$customer, 'form'=>$form));
	}

	function _edit_perm($e, $tabs) {
		$customer = $tabs->customer;
		if (!L('ME')->is_allowed_to('买方修改成员权限', $customer)) {
			URI::redirect('error/401');
		}

		$form = Form::filter(Input::form());
		$perms = Config::get('customer.perms');

		if ($form['submit']) {

			$users = array();
			$perm_members = array();

			foreach ($form['perms'] as $perm => $encoded_users) {

				if (!isset($perms[$perm])) {
					continue;
				}

				$user_ids = array_keys(json_decode($encoded_users, TRUE));

				foreach ($user_ids as $user_id) {
					if (!$user_id) {
						continue;
					}

					if (!isset($users[$user_id])) {
						$user = O('user', $user_id);

						if (!$customer->has_member($user)) {
							$form->set_error("perms[$perm]", HT("成员输入有误"));
						}

						$users[$user_id] = $user;

					}

					$perm_members[$perm][] = $user_id;

				}

			}

			if ($form->no_error) {
				foreach (Q("customer_member_perm[customer=$customer]") as $old_perm) {
					$old_perm->delete();
				}

				foreach ($perm_members as $perm_name => $member_ids) {
					foreach ($member_ids as $member_id) {
						$new_perm = O('customer_member_perm');
						$new_perm->name = $perm_name;
						$new_perm->customer = $customer;
						$new_perm->user = $users[$member_id];
						$new_perm->save();
					}
				}

				Site::message(Site::MESSAGE_NORMAL, HT('成员权限更新成功!'));
			}
		}
		else {
			foreach ($perms as $perm_name => $foo) {
				$members = Q("customer_member_perm[name=$perm_name][customer=$customer]<user user")->to_assoc('id', 'name');
				$form['perms'][$perm_name] = json_encode($members);
			}

		}

		$tabs->content = V('customer:profile/edit.perm', array(
							   'customer'=>$customer,
							   'perms' => $perms,
							   'form'=>$form));
	}

    function _edit_lims($e, $tabs) {

        $customer = $tabs->customer;

        $form = Input::form();

        try {
            if ($form['submit']) {
                $file = Input::file('file');
                if (!$file['tmp_name']) {
                    Site::message(Site::MESSAGE_ERROR, T('请上传公钥文件'));
                    throw new Error_Exception;
                }

                $extension = File::extension($file['name']);
                if ($extension != 'license') {
                    Site::message(Site::MESSAGE_ERROR, T('授权文件格式有误, 请重新上传绑定!'));
                    throw new Error_Exception;
                }

                $content = file_get_contents($file['tmp_name']);
                $data = @json_decode(base64_decode($content), TRUE);

                if (($data['ctime'] + Config::get('lims.license_overdue_time', 900)) < Date::time()) {
                    Site::message(Site::MESSAGE_ERROR, T('授权文件已过期, 请重新下载进行绑定'));
                    throw new Error_Exception;
                }

                if ($data['mall_uuid'] != Site::get('mall.uuid')) {
                    Site::message(Site::MESSAGE_ERROR, T('授权文件不正确, 请重新下载进行绑定'));
                    throw new Error_Exception;
                }

                $customer->lims_data = array(
                    'public_key'=> $data['public_key'],
                    'site_name'=> $data['site_name'],
                    'order_url'=> $data['order_url'],
                    'update_url'=> $data['update_url'],
                    'bind_url'=> $data['bind_url'],
                    'unbind_url' => $data['unbind_url'],
                    'order_list' => $data['order_list'],
                    'base_url' => $data['base_url'],
                    'client_id' => $data['client_id'],
                );

                $customer->uuid = $data['site_id'];
                $customer->bind_status = Customer_Model::BIND_STATUS_PADDING;

                $customer->save();

            }
        }
        catch(Error_Exception $e) {
        }

        if ($customer->bind_status == Customer_Model::BIND_STATUS_PADDING) {
            Site::message(Site::MESSAGE_ERROR, T('绑定处理中, 请到LIMS确认绑定!'));
        }

        $tabs->content = V('customer:profile/edit.lims', array(
            'customer'=> $customer
        ));
    }

	private function _validate_token_backend($backend) {
		$backends = Config::get('auth.backends');
		return in_array(trim($backend), array_keys($backends));
	}

}

class Profile_AJAX_Controller extends AJAX_Controller {

	function index_delete_member_icon_click() {
		$user = O('user', Input::form('id'));

		if ($user->id) {
			if (JS::confirm(T('您确定要删除用户头像么?'))) {
				$user->delete_icon();
				Site::message(Site::MESSAGE_NORMAL, T('用户头像删除成功!'));
				JS::refresh();
			}
		}
	}

	function index_delete_icon_click() {
		$customer = O('customer', Input::form('id'));

		if ($customer->id &&
			L('ME')->is_allowed_to('以买方成员修改信息', $customer)) {
			if (JS::confirm(T('您确定要删除图标么?'))) {
				$customer->delete_icon();
				Site::message(Site::MESSAGE_NORMAL, T('图标删除成功!'));
				JS::refresh();
			}
		}
	}

	function index_add_customer_member_click() {

		$form = Input::form();

		$customer = O('customer', $form['cid']);
		if ($customer->bind_status == Customer_Model::BIND_STATUS_SUCCESS) {
			return FALSE;
		}
		if ($customer->id &&
			L('ME')->is_allowed_to('买方修改成员信息', $customer)) {

			JS::dialog(V('customer:profile/index_add_member', array(
							 'customer' => $customer)), array(
								 'title' => T('添加成员')
								 ));
		}
	}

	function index_add_customer_member_submit() {

		if (Input::form('submit')) {
			$form = Form::filter(Input::form());

			$customer = O('customer', $form['cid']);
			if ($customer->bind_status == Customer_Model::BIND_STATUS_SUCCESS) {
				return;
			}
			if (!$customer->id ||
				!L('ME')->is_allowed_to('买方修改成员信息', $customer)) {
				return;
			}

			$user = O('user', $form['user']);

			if (!$user->id) {
				$form->set_error('user', T('请选择用户!'));
			}
			else if (!$customer->can_add_member($user)) {
				$form->set_error('user', HT('该用户无法加入 %name', array(
											   '%name' => $customer->name
											   )));
			}

			if ($form->no_error) {
				$customer->connect($user, 'member');
				if ($user->save()) {
					Site::message(Site::MESSAGE_NORMAL, T('添加成员成功!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('添加成员失败!'));
				}
				JS::refresh();
			}
			else {
				JS::dialog(V('customer:profile/index_add_member',
							 array(
								 'customer' => $customer,
								 'form' => $form
								 )),
						   array(
							   'title' => T('添加成员')
							   ));
			}
		}
	}

	function index_delete_customer_user_click($cid=0) {
		$ret = JS::confirm(T('您是否确认移除此成员? 移除之后该成员不会被删除, 依旧存在于系统中!'));
		if ($ret) {
			$form = Input::form();
			$user = O('user', $form['uid']);

			$customer = O('customer', $cid);
			if ($customer->bind_status == Customer_Model::BIND_STATUS_SUCCESS) {
				return;
			}
			if (!$customer->id ||
				!L('ME')->is_allowed_to('买方修改成员信息', $customer)) {
				return;
			}

			if ($user->id == $customer->owner->id) {
				JS::alert('不能移出买方负责人!');
				return;
			}

			$customer->disconnect($user, 'member');

			foreach (Q("customer_member_perm[customer=$customer][user=$user]") as $old_perm) {
				$old_perm->delete();
			}

			Site::message(Site::MESSAGE_NORMAL, T('移除成员成功!'));
			JS::refresh();
		}
	}

	function index_activate_customer_user_click() {
		$ret = JS::confirm(T('您是否确认激活此成员?'));
		if ($ret) {
			$form = Input::form();
			$user = O('user', $form['uid']);
			$customer = O('customer', $form['cid']);
			if ($customer->bind_status == Customer_Model::BIND_STATUS_SUCCESS) {
				return;
			}
			if (!$customer->id ||
				!L('ME')->is_allowed_to('买方修改成员信息', $customer)) {
				return;
			}

			$user->atime = Date::time();
			if ($user->save()) {
				Site::message(Site::MESSAGE_NORMAL, T('激活成员成功!'));
				JS::refresh();
			}
		}
	}

	function index_bind_again_click() {
		$form = Input::form();
		$customer = O('customer', $form['customer_id']);

		if(!$customer->id) return;

		$data = $customer->lims_data;

        JS::redirect(URI::url($data['bind_url'], array(
            'source'=> Config::get('mall.name'),
            'site_id'=> $data['site_id'],
            'callback'=> $form['url'],
        )));
	}

	//解绑定lims
	function index_unbind_lims_click() {
		if(!JS::confirm(HT('您确定要解绑定吗?'))) {
			return;
		}
		$form = Input::form();
		$customer = O('customer', $form['customer_id']);

		if(!$customer->id) return;
		$customer->bind_status = Customer_Model::BIND_STATUS_NOT_YET;
        //同步清除uuid和lims_data数据

        $customer->uuid = NULL;
        $customer->lims_data = NULL;

        if (!$customer->save()) {
            JS::alert(T('删除失败!'));
        }
        else{
        	Output::$AJAX['success'] = true;
        }
	}

	//添加成员的时候，显示用户信息页面
	function index_get_user_click() {
		$form = Input::form();

		$customer = O('customer', $form['cid']);
		if ($customer->bind_status == Customer_Model::BIND_STATUS_SUCCESS) {
			return;
		}
		if(!$customer->id) return;

		if($form['token'] && $form['backend']) {
			$token = Auth::make_token($form['token'], $form['backend']);
			$user = O('user', ['token'=>$token]);

			if(!$user->id) {
				$view = Event::trigger('customer.get_member_info', $token, $customer);
			}
		}
		else{
			$user = O('user', $form['uid']);
		}

		if(!$user->id && !$view) return;

		if(!$view) $view = (string)V('customer:profile/member_info', ['user'=>$user, 'customer'=>$customer]);

		Output::$AJAX['.member_content'] = [
			'data' => $view,
			'mode' => 'replace'
		];

	}

	//重置添加成员
	function index_cancel_select_member_click() {
		$form = Input::form();
		$customer = O('customer', $form['cid']);
		if(!$customer->id) return;
		if ($customer->bind_status == Customer_Model::BIND_STATUS_SUCCESS) {
			return;
		}

		Output::$AJAX['.member_content'] = [
			'data' => (string)V('customer:profile/add_member', ['customer' => $customer]),
			'mode' => 'replace'
		];

		//dropdown_menu 每次都会生成一个，所以重置的时候清除原来的
		Output::$AJAX['.dropdown_menu'] = [
			'data' => '',
			'mode' => 'replace'
		];
	}
}
