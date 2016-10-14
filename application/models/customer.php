<?php

class Customer_Model extends Presentable_Model {

    const BIND_STATUS_NOT_YET = 0;
    const BIND_STATUS_PADDING = 1;
    const BIND_STATUS_SUCCESS = 2;

    const CREDIT_NORMAL = 0; // 默认买方没有评信用等级
    const CREDIT_LV_A= 1; // 普通信用买方
    const CREDIT_LV_Z= -1; // 普通恶意买方

	protected $object_page = array(
        'view'=>'!customer/profile/index.%id[.%arguments]',
        'edit'=>'!customer/profile/edit.%id[.%arguments]',
        'orders'=>'!customer/orders/index.%id[.%arguments]',
        'financial'=>'!customer/financial/index.%id[.%arguments]',
        'admin_view'=>'!admin/customer/view.%id[.%arguments]',
        'admin_edit'=>'!admin/customer/edit.%id[.%arguments]',
        'admin_delete'=>'!admin/customer/delete.%id[%arguments]',
      	'vendor_view' => '!vendor/customer/index.%id[.%arguments]',
      	'add_member' => '!customer/profile/add_member.%id[.%arguments]',
      	'gapper_view' => '!customer/gapper/index.%id[.%arguments]',
	);

	function & links($mode='index', $button=FALSE) {
		$links = new ArrayIterator;
		$me = L('ME');

		switch ($mode) {
		case 'customer_view':
			if ($me->is_allowed_to('以买方成员修改信息', $this)) {
				$links['edit'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text' => T('修改'),
					'extra' =>'class="button button_edit"',
					);
			}
			break;
		case 'customer_index':
			$links['edit'] = array(
				'url' => $this->url(NULL, NULL, NULL, 'edit'),
				'text' => T('修改'),
				'extra' =>'class="blue"',
			);
			break;
		case 'admin_view' :
			$links['edit'] = array(
				'url'=>$this->url(NULL, NULL, NULL, 'admin_edit'),
				'text'=>T('修改'),
				'extra'=>'class="button button_edit"',
			);
			break;
		case 'admin_index' :
			$links['edit'] = array(
				'url'=>$this->url(NULL, NULL, NULL, 'admin_edit'),
				'text'=>T('修改'),
				'extra'=>'class="blue"',
			);
			break;
        }

		return (array)$links;
	}

	function get_cart() {
		$cart = NULL;

		if ($this->id) {
			$cart = O('cart', array('customer' => $this));

			if (!$cart->id) {
				$cart->customer = $this;
				$cart->save();
			}
		}

		return $cart;
	}

	function has_member($user) {
		return $user->connected_with($this, 'member');
	}

	function can_add_member($user) {
		// 1. 用户只能作为一个买方的负责人;
		// 2. 买方负责人不能作为其他买方的成员;
		// (xiaopei.li@2012-09-03)
		// return TRUE;
		// $user_owned_customer = O('customer', array(
		// 						'owner' => $user,
		// 						));
		// //mark
		// if ($user_owned_customer->id &&
		// 	$user_owned_customer->id != $this->id) {
		// 	return FALSE;
		// }
		//如果用户是lims同步过来的用户则不能添加到其他买方
		if($user->lims_user) {
			return FALSE;
		}

		return TRUE;
	}

	function check_app_installed($app_name) {
		try {
			if ($group_id = $this->gapper_group) {
				$rpc = Gapper::get_RPC();
				if (!$rpc) return false;
				$app = Config::get('gapper.apps')[$app_name];
				return $rpc->gapper->app->getGroupInfo($app['client_id'], (int)$group_id);
			}
			else {
				return false;
			}
		}
		catch (Exception $e) {
			return false;
		}
	}

}

