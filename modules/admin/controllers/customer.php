<?php

class Customer_Controller extends Layout_Admin_Controller {

	function _before_call($method, &$params) {
		// TODO 此 controller 也许需换更细的权限(xiaopei.li@2012-06-20)
		if (!L('ME')->access('管理买方')) {
			URI::redirect('error/401');
		}

		parent::_before_call($method, $params);

		$this->layout->title = T('买方管理');
		$this->layout->body = V('customer/body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');

		$this->layout->body->primary_tabs
			->add_tab('index', array(
						  'url'=>URI::url('!admin/customer/'),
						  'title'=>T('买方列表')
						  ));
	}

	function index() {
		$form = Site::form();

		$selector = 'customer';
		$pre_selectors = array();

		if ($form['name']) {
			$name = Q::quote($form['name']);
			$selector .= "[name*={$name}]";
		}

		if ($form['owner']) {
			$owner = Q::quote($form['owner']);
			$pre_selectors['owner'] = "user<owner[name*=$owner|name_abbr^=$owner]";
		}

		if (count($pre_selectors) > 0) {
			$selector = "(".implode(', ', $pre_selectors).') '. $selector;
		}

		$customers = Q($selector);

		$pagination = Site::pagination($customers, (int)$form['st'], 20);

		$content = V('customer/list', array(
						 'pagination'=>$pagination,
						 'customers'=>$customers,
						 'form'=>$form
						 ));

		$this->layout->body->primary_tabs->select('index')->set('content', $content);

	}

    function view($id=0, $tab='members', $stab='active', $is_gapper=null) {
        if ($is_gapper) {
            $customer = O('customer', ['gapper_group'=>$id]);
        }
        else {
            $customer = O('customer', $id);
        }
        if (!$customer->id) URI::redirect('error/404');

		$secondary_tabs = Widget::factory('tabs');

		Event::bind('admin.customer.view.content', array($this, '_view_members'), 0, 'members');

		$secondary_tabs
			->add_tab('members', array(
						  'title' => T('成员列表'),
						  'url' => $customer->url('members.'.$stab, NULL, NULL, 'admin_view')
						  ))
			->tab_event('admin.customer.view.tab')
			->content_event('admin.customer.view.content')
			->set('customer', $customer)
			->set('stab', $stab)
			->select($tab);

		$content = V('admin:customer/view/index', array(
						 'customer'=>$customer,
						 'secondary_tabs' => $secondary_tabs
						 ));

		$this->layout->body->primary_tabs
			->add_tab('profile', array(
						  'url'=> $customer->url(NULL, NULL, NULL, 'admin_view'),
						  'title'=> T('%name', array('%name'=>H($customer->name))),
						  ))
			->set('content', $content)
			->select('profile');

		$this->layout->title = H($customer->name);

	}

	function _view_members($e, $tabs) {

		$customer = $tabs->customer;
		$stab = $tabs->stab;

		$form = Site::form();

		$secondary_tabs = Widget::factory('tabs')
			->add_tab('active', array(
						  'title' => T('已激活'),
						  'url' => $customer->url('members.active', NULL, NULL, 'admin_view')
						  ))
			->add_tab('unactive', array(
						  'title' => T('未激活'),
						  'url' => $customer->url('members.unactive', NULL, NULL, 'admin_view')
						  ))
			->content_event('admin.customer.view.members.content')
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

		$tabs->content = V('admin:customer/view/index_members', array(
							   'customer' => $customer,
							   'secondary_tabs' => $secondary_tabs,
							   'users' => $users,
							   'pagination' => $pagination,
							   'form' => $form
							   ));
	}

	function add() {

        // 10362 【Win 8，firfox】【南通大学】0.1.0，mall-old，买方管理，添加买方功能去掉
        return;

		$form = Form::filter(Input::form());

		$group_root = Tag_Model::root('group');

		if ($form['submit']) {
			$customer = O('customer');

			$form
				->validate('name', 'not_empty', T('名称不能为空!'))
				->validate('owner', 'not_empty', T('负责人不能为空!'))
				->validate('account_no', 'not_empty', T('工资号不能为空!'))
				->validate('email', 'not_empty', HT('请填写联系邮箱!'))
				->validate('email', 'is_email', HT('联系邮箱格式有误!'));

			$group = O('tag', array(
						   'id' => $form['group_id'],
						   'root' => $group_root,
						   ));

			if (!$group->id) {
				$form->set_error('group', T('组织机构不能为空'));
			}

			if (isset($form['owner'])) {
                $owner = O('user', $form['owner']);
                if (!$owner->id) {
					$form->set_error('owner', HT('负责人不能为空!'));
                }
				if (!$customer->can_add_member($owner)) {
					$form->set_error('owner', HT('该用户无法加入新买方'));
				}
			}


			if ($form->no_error) {
				$customer->name = $form['name'];
				$customer->account_no = $form['account_no'];
				$customer->email = $form['email'];
				$customer->description = $form['description'];
				$customer->owner = $owner;
				$customer->group = $group;

				if ($customer->save()) {

					if ($customer->owner->id &&
						!$customer->has_member($customer->owner)) {
						$customer->connect($customer->owner, 'member');
					}

					$group_root->disconnect($customer);
					$customer->connect($customer->group);

					Site::message(Site::MESSAGE_NORMAL, T('添加买方成功!'));
					URI::redirect('!admin/customer');
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('添加买方失败!'));
				}
			}
		}


		$content = V('customer/add', array(
						 'form'=>$form,
						 'group_root'=>$group_root,
						 ));

		$this->layout->body->primary_tabs
			->add_tab('add', array(
						  'url'=>URI::url('!admin/customer/add'),
						  'title'=>T('添加新买方')
						  ))
			->select('add')
			->set('content', $content);
	}

	function edit($id=0, $tab='info') {

		$customer = O('customer', $id);
		if (!$customer->id) URI::redirect('error/404');

		Event::bind('admin.customer.edit.content', array($this, '_edit_info'), 0, 'info');
		Event::bind('admin.customer.edit.content', array($this, '_edit_icon'), 0, 'icon');
		Event::bind('admin.customer.edit.content', array($this, '_edit_address'), 0, 'address');
        Event::bind('admin.customer.edit.content', array($this, '_edit_perm'), 0, 'perm');

        $allowCredit = !!Config::get('customer.allow_credit');
        if ($allowCredit) {
            Event::bind('admin.customer.edit.content', array($this, '_edit_credit'), 0, 'credit');
        }

		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
			->add_tab('info', array(
						  'url' => $customer->url('info', NULL, NULL, 'admin_edit'),
						  'title' => T('基本信息')
						  ))
			->add_tab('icon', array(
						  'url' => $customer->url('icon', NULL, NULL, 'admin_edit'),
						  'title' => T('图标')
						  ))
			->add_tab('address', array(
						  'url' => $customer->url('address', NULL, NULL, 'admin_edit'),
						  'title' => T('运送地址')
						  ))
            ->add_tab('perm', array(
                        'url'=> $customer->url('perm', NULL, NULL, 'admin_edit'),
                        'title'=>T('成员权限')
                    ));
        if ($allowCredit) {
            $secondary_tabs->add_tab('credit', array(
                        'url'=> $customer->url('credit', NULL, NULL, 'admin_edit'),
                        'title'=>T('信用买方')
            ));
        }
        $secondary_tabs->content_event('admin.customer.edit.content')
			->set('customer', $customer)
			->set('class', 'secondary_tabs')
			->select($tab);

		$content = V('admin:customer/edit', array('secondary_tabs' => $secondary_tabs));

		$breadcrumb = array(
			array(
				'url' => $customer->url(NULL, NULL, NULL, 'admin_view'),
				'title' => H($customer->name)
				),
			array(
				'url' => $customer->url($tab, NULL, NULL, 'admin_edit'),
				'title' => HT('修改')
				)
			);

		$this->layout->body->primary_tabs
			->add_tab('edit', array( '*' => $breadcrumb ))
			->select('edit')
			->set('content', $content);

    }

    function _edit_credit($e, $tabs) {
        $allowCredit = !!Config::get('customer.allow_credit');
        if (!$allowCredit) return;

		$form = Form::filter(Input::form());
        $customer = $tabs->customer;
        $credits = [
            Customer_Model::CREDIT_NORMAL=> HT('未评级买方'),
            Customer_Model::CREDIT_LV_A=> HT('信用买方'),
            Customer_Model::CREDIT_LV_Z=> HT('恶意买方')
        ];
        if ($form['submit']) {
            $credit = $form['credit'];
            if (isset($credits[$credit])) {
                $customer->credit = $credit;
                if ($customer->save()) {
					Site::message(Site::MESSAGE_NORMAL, T('修改买方信用成功!'));
                }
                else {
					Site::message(Site::MESSAGE_ERROR, T('修改买方信用失败!'));
                }
            }
		}
		$tabs->content = V('customer/edit.credit', array(
							   'customer'=>$customer,
                               'form'=>$form,
                               'credits'=> $credits
							   ));
    }

	function _edit_info($e, $tabs) {
		$form = Form::filter(Input::form());

		$customer = $tabs->customer;
		$group_root = Tag_Model::root('group');

		if ($form['submit']) {
			$form
				->validate('name', 'not_empty', T('名称不能为空!'))
				->validate('owner', 'not_empty', T('负责人不能为空!'))
				->validate('account_no', 'not_empty', T('工资号不能为空!'))
				->validate('email', 'not_empty', HT('请填写联系邮箱!'))
				->validate('email', 'is_email', HT('联系邮箱格式有误!'));

			$group = O('tag', array(
						   'id' => $form['group_id'],
						   'root' => $group_root,
						   ));

			if (!$group->id) {
				$form->set_error('group', T('组织机构不能为空'));
			}

			if (isset($form['owner'])) {
				$owner = O('user', $form['owner']);
                if (!$owner->id) {
					$form->set_error('owner', HT('负责人不能为空!'));
                }
				if (!$customer->can_add_member($owner)) {
					$form->set_error('owner', HT('该用户无法加入 %name', array(
													 '%name' => $customer->name
													 )));
				}
			}

			if ($form->no_error) {

				$customer->name = $form['name'];
				$customer->account_no = $form['account_no'];
				$customer->email = $form['email'];
				$customer->description = $form['description'];
				$customer->owner = $owner;
				$customer->group = $group;

				if ($customer->save()) {

					if ($customer->owner->id &&
						!$customer->has_member($customer->owner)) {
						$customer->connect($customer->owner, 'member');
					}

					$group_root->disconnect($customer);
					$customer->connect($customer->group);

					Site::message(Site::MESSAGE_NORMAL, T('修改买方信息成功!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('修改买方信息失败!'));
				}
			}
		}

		if ($form['delete']) {
			if ($customer->delete()) {
				Site::message(Site::MESSAGE_NORMAL, T('删除买方成功!'));
				URI::redirect('!admin/customer');
			}
			else {
				Site::message(Site::MESSAGE_ERROR, T('删除买方失败!'));
			}
		}

		$tabs->content = V('customer/edit.info', array(
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

		$tabs->content = V('admin:customer/edit.icon');
	}

	function _edit_address($e, $tabs) {
		$form = Form::filter(Input::form());

		$customer = $tabs->customer;

		if ($form['submit']) {
			$form
				->validate('phone', 'not_empty', T('电话不能为空!'))
				->validate('address', 'not_empty', T('地址不能为空!'));
			if ($form['email']) {
				$form->validate('email', 'is_email', T('邮箱格式有误!'));
			}
			if ($form->no_error) {
				$address = O('deliver_address', array('customer'=>$customer));
				if (!$address->id) $address->customer = $customer;
				$address->postcode = $form['postcode'];
				$address->phone = $form['phone'];
				$address->address = $form['address'];
				$address->email = $form['email'];

				if ($address->save()) {
					Site::message(Site::MESSAGE_NORMAL, T('修改买方送货地址成功!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('修改买方送货地址失败!'));
				}
			}
		}

		$tabs->content = V('customer/edit.address', array('customer'=>$customer, 'form'=>$form));
	}

	function _edit_perm($e, $tabs) {
		$customer = $tabs->customer;

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

		$tabs->content = V('admin:customer/edit.perm', array(
							   'customer'=>$customer,
							   'perms' => $perms,
							   'form'=>$form));
	}
}

class Customer_AJAX_Controller extends AJAX_Controller {

	function index_add_customer_member_click() {

		$form = Input::form();

		$customer = O('customer', $form['cid']);

		if (!$customer->id) return;

		JS::dialog(V('admin:customer/view/index_add_member', array(
						 'customer' => $customer
						 )), array(
							 'title' => T('添加成员')
							 ));

	}

	function index_add_customer_member_submit() {

		if (Input::form('submit')) {
			$form = Form::filter(Input::form());

			$customer = O('customer', $form['cid']);

			if (!$customer->id) return;

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
				JS::dialog(V('admin:customer/view/index_add_member', array(
								 'customer' => $customer,
								 'form' => $form
								 )), array(
									 'title' => T('添加成员')
									 ));
			}
		}
	}

	function view_delete_customer_user_click($cid=0) {
		$ret = JS::confirm(T('您是否确认从该买方中移除此成员? 移除之后该成员不会被删除, 依旧存在于系统中!'));
		if ($ret) {
			$form = Input::form();
			$user = O('user', $form['uid']);

			$customer = O('customer', $cid);

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

	function index_delete_icon_click() {
		if (JS::confirm(T('您确定要删除图标么?'))) {
			$customer = O('customer', Input::form('id'));
			if ($customer->id) {
				$customer->delete_icon();
				Site::message(Site::MESSAGE_NORMAL, T('图标删除成功!'));
				JS::refresh();
			}
		}
	}
}

