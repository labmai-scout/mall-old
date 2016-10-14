<?php


class User_Model extends Presentable_Model {

	static $genders = array(-1 => '--', '男','女');

	protected $object_page = array(
		'view' => '!people/profile/index[.%arguments]',
		'edit' => '!people/profile/edit[.%arguments]',
		'admin_view' => '!admin/user/view.%id[.%arguments]',
		'admin_edit' => '!admin/user/edit.%id[.%arguments]',
		'view_info' => '!people/profile/view.%id[.%arguments]',
	);

	function & links($mode='index', $button=FALSE) {
		$links = new ArrayIterator;
		$me = L('ME');
		switch ($mode) {
			case 'view':
				$status = Customer_Model::BIND_STATUS_SUCCESS;
				$count = Q("{$this}<member customer[bind_status=$status]")->total_count();
				if (!$count) {
				$links['edit'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text' => T('修改'),
					'extra' =>'class="button button_edit"',
				);
				}
				break;
			case 'admin_view':
				$links['edit'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'admin_edit'),
					'text' => T('修改'),
					'extra' =>'class="button button_edit"',
				);
				break;
			case 'admin_index':
				$links['edit'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'admin_edit'),
					'text' => T('修改'),
					'extra' =>'class="blue"',
				);
				break;
			case 'customer_index':
				break;
			case 'vendor_index':
				//TODO: 添加vendor成员管理链接
				$links['delete'] = array(
					'url' => '#',
					'text' => T('移除'),
					'extra' =>'class="blue" '.
							'q-object="delete_vendor_user" '.
							'q-event="click" '.
							'q-static="'.H(array('uid'=>$this->id)).'"'
				);
				break;
			case 'vendor':
				// TODO 应有位于 !people 的修改用户信息的 controller,
				// 以便不同身份的用户修改自己的信息 (xiaopei.li@2012-03-20)
				/*
				$links['edit'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text' => T('修改'),
					'extra' =>'class="blue"',
				);
				break;
				*/
		}

		return (array)$links;
	}

	function is_active() {

		return $this->atime > 0 ? TRUE : FALSE;
	}

	function access($perm_name){
		static $admin_tokens;

		if ($admin_tokens === NULL) {
			$admin_tokens = array_map("Auth::normalize", (array) Config::get('site.admin'));
		}

		if (in_array($this->token, $admin_tokens)) return TRUE;

		$now = time();

 		switch($perm_name) {
		case '登录用户':
			return $this->id > 0;
		case '激活用户':
			return $this->is_active();
		case '不必修改密码':
			return !$this->must_change_password;
		/*
		case '过期用户':
			if ( Config::get('people.enable_member_date')) {
				return $this->id > 0 && $this->dto && $this->dto < $now;
			}
			return TRUE;

		case '当前用户':
			if ( Config::get('people.enable_member_date')) {
				return $this->id > 0 && (!$this->dto || $this->dto> $now);
			}
			return $this->id > 0;
		*/
		}

		$perms = $this->perms();

		return  $perms[$perm_name]=='on' || $perms['管理所有内容']=='on';
	}

	function roles() {
		if ($this->id) {

			$user_roles = Q("$this role")->to_assoc('id', 'id');
			//加载预定权限
			if( Config::get('people.enable_member_date') && $this->dto && $this->dto < time()){
				$user_roles[ROLE_PAST_MEMBERS] = ROLE_PAST_MEMBERS;
			}
			else {
				$user_roles[ROLE_CURRENT_MEMBERS] = ROLE_CURRENT_MEMBERS;
			}

            //该处删除了对现有系统中members相关默认权限设定。如需增加请完善相关代码。
		}
		else {
			$user_roles[ROLE_VISITORS] = ROLE_VISITORS;
		}

		return $user_roles;
	}

	private $_perms = NULL;
	private $_perms_timestamp = 0;
	function perms() {

		$now = time();
		if ($now - $this->_perms_timestamp > 10) {
			$this->_perms_timestamp = $now;

			$perms = new ArrayIterator;

			Event::trigger('user_model.perms.enumerates', $this, $perms);

			$perms = (array) $perms;
			$roles = L('ROLES');
			$user_roles = $this->roles();
			foreach ($user_roles as $rid) {
				$perms += (array)($roles[$rid]->perms);
			}

			$this->_perms = $perms;

		}

		return $this->_perms;
	}

	/*
	 *	信权限判断规则：入口函数
	 *	@default: 默认返回值
	 */
	function is_allowed_to($perm_name, $object, $options = NULL) {
		if (is_string($object)) {
			$object_name = $object;
			//$object = O($object_class_name);
		}
		elseif ($object instanceof _ORM_Model) {
			$object_name = $object->name();
		}
		else {
			return FALSE;
		}

		$ret = FALSE;
		if ($object_name) {
			$ret = Event::trigger("is_allowed_to[$perm_name].$object_name", $this, $perm_name, $object, $options);
		}
		if ($ret === NULL) return $options['@default'];

		return $ret;
	}

	static function is_reserved_token($token) {
		return FALSE;
	}

	// TODO user 是基础的类,
	// 而以下 user 处理各类对象的方法也许放在各对象内更合适(xiaopei.li@2012-03-20)

	// 发布 vendor/product
	function publish($object) {
		$object->publisher = $this;
		$object->publish_date = Date::time();
		return $object->save();
	}

	function unpublish($object) {
		if ($object->approve_date) {
			$this->unapprove($object);
		}
		$object->publisher = NULL;
		$object->publish_date = 0;
		return $object->save();
	}

	function approve($object) {
		if (!$object->publish_date) {
			$this->publish($object);
		}
		$object->approver = $this;
		$object->approve_date = Date::time();
		return $object->save();
	}

	function unapprove($object) {
		$object->approver = NULL;
		$object->approve_date = 0;
		return $object->save();
	}


	/*
		审核供应商账号
	*/
	function approve_vendor($user) {
		/* 操作处理部分 */
		return $user->save();
	}

	/*
		审核产品的发布
	*/
	function approve_product($product) {
		/* 操作处理部分 */
		return $product->save();
	}

	/*
		给实验室充值
	*/
	function refill($lab, $amount) {
		/* 操作处理部分 */
		return $account->save();
	}

	/*
		审核用户账号
	*/
	function approve_user($user) {
		/* 操作处理部分 */
		return $user->save();
	}

    function is_admin() {
        return $this->access('查看管理面板');
    }

    //检测用户是否为lims用户
    function is_lims_user() {
		return $this->lims_user;
    }
}
