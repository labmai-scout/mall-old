<?php

class Vendor {

	static function is_accessible($e, $module) {
		$me = L('ME');

		if ($me->id && (Q("vendor[owner={$me}]")->total_count() || Q("$me<member vendor")->total_count())) {
			$e->return_value = TRUE;
		}

		return FALSE;
	}

	static function vendor_sidebar_menu($e, $vendor) {
		if(!$vendor->id) return;

		$product_menu_item = array(
			'icon' => array(
				'title' => '商品管理',
				'icon' => '!vendor/icons/32/product.png',
				'url' => $vendor->url(NULL, NULL, NULL, 'product')
			),
			'list'=>array(
				'title' => '商品管理',
				'icon' => '!vendor/icons/16/product.png',
				'url' => $vendor->url(NULL, NULL, NULL, 'product')
			),
		);

		$order_menu_item = array(
			'icon' => array(
				'title' => '订单管理',
				'icon' => '!vendor/icons/32/orders.png',
				'url' => $vendor->url(NULL, NULL, NULL, 'vendor_order')
			),
			'list'=>array(
				'title' => '订单管理',
				'icon' => '!vendor/icons/16/orders.png',
				'url' => $vendor->url(NULL, NULL, NULL, 'vendor_order')
			),
		);

        $billing_menu_item = array(
            'icon' => array(
                'title' => '结算管理',
                'icon' => '!vendor/icons/32/financial.png',
                'url' => $vendor->url(NULL, NULL, NULL, 'vendor_billing')
            ),
            'list'=>array(
                'title' => '结算管理',
                'icon' => '!vendor/icons/16/financial.png',
                'url' => $vendor->url(NULL, NULL, NULL, 'vendor_billing')
            ),
        );
		$config = (array) $e->return_value;
		if ($vendor->approve_date) { // 通过审核才能使用的功能

			$config += array(
				'product' => $product_menu_item,
				'order' => $order_menu_item,
                'billing' => $billing_menu_item,
			);
		}
		else if ($vendor->last_approve_date) {
			$config += array(
				'order' => $order_menu_item,
                'billing' => $billing_menu_item,
			);
		}

		$e->return_value = $config;
	}

	static function vendor_ACL($e, $user, $action, $object, $options) {
			switch ($action) {
			case '查看供应商':
			case '管理':
				if($user->vendor->id == $object->id || $object->has_member($user)){
					$e->return_value = TRUE;
				}
				return FALSE;
				break;
			case '查看商品':
			case '添加商品':
                if (($user->vendor->id && $user->vendor->id == $object->id || $object->has_member($user))
                	&& $object->approve_date) {
					$e->return_value = TRUE;
				}
				return FALSE;
				break;
			case '查看财务':
			case '查看订单':
                if (($user->vendor->id && $user->vendor->id == $object->id || $object->has_member($user))
                	&& ($object->approve_date || $object->last_approve_date)) {
                    $e->return_value = TRUE;
                }
                return FALSE;
				break;
			case '以供应商修改工商信息':
			case '上传文件':
			case '修改文件':
			case '删除文件':
			case '创建目录':
			case '修改目录':
			case '删除目录':
                if (($user->vendor->id && $user->vendor->id == $object->id || $object->has_member($user))
                       && !$object->publish_date) {
                    $e->return_value = TRUE;
                }
                return FALSE;
			case '以供应商修改':
			case '列表文件':
			case '下载文件':
                if ($user->vendor->id && $user->vendor->id == $object->id || $object->has_member($user)){
                    $e->return_value = TRUE;
                }
                return FALSE;
                break;
            case '查看证书':
            case '删除证书':
                //如果为vendor的vendor成员，或者为管理员，则可查看
                if (($user->vendor->id && $user->vendor->id == $object->id || $object->has_member($user))
                	|| $user->is_admin()) {
                    $e->return_value = TRUE;
                }
                return FALSE;
			default:
                return FALSE;
		}

	}

	static function billing_statement_ACL($e, $user, $action, $statement, $options) {
		if ($user->vendor->id && $user->vendor->id == $statement->vendor->id || $statement->vendor->has_member($user)) {
			switch ($action) {
			case '以供应商查看':
			case '以供应商删除':
				$e->return_value = TRUE;
				return FALSE;
            case '上传文件':
            case '修改文件':
            case '删除文件':
            case '创建目录':
            case '修改目录':
            case '删除目录':
                if ($statement->status == Billing_Statement_Model::STATUS_DRAFT && $statement->vendor->has_member($user)) {
                    $e->return_value = TRUE;
                }
                return FALSE;
                break;
            case '列表文件':
            case '下载文件':
                if ($statement->vendor->has_member($user)) {
                    $e->return_value = TRUE;
                }
                return FALSE;
                break;
			}

		}

	}

	static function order_ACL($e, $user, $action, $order, $options) {
		if (!$user->id) return;

		switch($action) {
		case '以供应商查看':
		case '拒绝退货':
		case '以供应商取消':
		case '申请结算':
		case '以供应商修改':
		case '供应商确认订单':
		case '确认发货':
			$vendor = $order->vendor;

			if ($vendor->id && $vendor->id == $user->vendor->id || $vendor->has_member($user)) {
				$e->return_value = TRUE;
				return FALSE;
			}
		}
	}

	static function order_item_comment_ACL($e, $user, $action, $comment, $options) {
		if (!$user->id) return;

		switch($action) {
		case '回复':
			$vendor = $comment->order_item->order->vendor;

			if ($vendor->id && $vendor->id == $user->vendor->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
		}
	}

    static function comment_ACL($e, $user, $perm_name, $comment, $options) {
		$e->return_value = FALSE;
		return FALSE;
        switch($perm_name) {
            case '发表评论' :
                $e->return_value = TRUE;
                return FALSE;
                break;
            case '删除' :
                if ($user->id === $comment->author->id) {
                    $e->return_value =TRUE;
                    return FALSE;
                }
                break;
            default :
                break;
        }
    }

	static function product_ACL($e, $user, $action, $product, $options) {

		if (!$user->id) return;

		$vendor = $product->vendor;
		$is_vendor_member = !!(($vendor->id && $vendor->id == $user->vendor->id) || $vendor->has_member($user));

		switch($action) {
		case '以供应商查看':
		case '以供应商修改':
		case '以供应商删除':
			if ($is_vendor_member) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '冻结/解冻':
			if (Config::get('vendor.enable_freeze_and_unfreeze_product') && $is_vendor_member) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		default:
			return;
		}
	}

    static function get_path($e, $vendor, $params) {
		$path = $params[0];
		if (!$path) return;
		$root = Config::get('nfs.root');
		$full_path = "{$root}/{$path}/{$vendor->id}/";
		$e->return_value = $full_path;
		return FALSE;
    }

    static function fix_path($e, $vendor, $params){
    	$path = $params[0];

		//如果路径为非法路径，返回空
		if(preg_match('/^[\/\s]+|[\/\s]+$/', $path) || preg_match('/^.{1,2}\//', $path) || preg_match('/\.{1,2}\//', $path)){
			$path = '';
		}
		$e->return_value = $path;
		return FALSE;
	}

	static function check_credentials($vendor){
		//检查供应商证件信息是否上传
    	$license_img = $vendor->get_path('license') . $vendor->license_img;
        $group_img = $vendor->get_path('group') . $vendor->group_img;
        $tax_on_land_img = $vendor->get_path('tax_on_land') . $vendor->tax_on_land_img;
        $state_tax_img = $vendor->get_path('state_tax') . $vendor->state_tax_img;

		if (!($vendor->license_ready && file_exists($license_img))
			|| !($vendor->group_ready && file_exists($group_img))
			|| !($vendor->tax_on_land_ready && file_exists($tax_on_land_img))
			|| !($vendor->state_tax_ready && file_exists($state_tax_img))
		) {
			return FALSE;
		}
		return TRUE;
    }

    static function vendor_saved($e, $vendor, $old_data, $new_data) {
    	if ($vendor->name() != 'vendor') return;

    	if ($old_data['short_name'] != $new_data['short_name'] || $old_data['name'] != $new_data['name']) {
    		$vendor->name_edit = 1;
    		$vendor->save();
    	}
    }

	// gapper 用户 在 cendor 中的属性做
	static function before_login_by_token($e, $user_info = []) {
		$gapper_user = $user_info['id'];
		$user = O('user', ['gapper_user'=>$gapper_user]);
		$rpc = Gapper::get_RPC();
		$gids = $rpc->gapper->user->getGroups((int)$user_info['id']);
		if ($user->id) {
			$gids = array_keys($gids);
			$vgids = Q("{$user}<member vendor")->to_assoc('id', 'gapper_group');
			$dvgids = array_diff($vgids, $gids);
			$pvgids = array_diff($gids, $vgids);
			// 删除不在对应 gapper 组的供应商的 member 关联
			if (count($dvgids)) {
				foreach ($dvgids as $vgid) {
					$vendor = O('vendor', ['gapper_group'=>$vgid]);
					$vendor->disconnect($user, 'member');
				}
			}
			// 成员新加gapper的组 对应的供应商增加 对应member
			if (count($pvgids)) {
				foreach ($pvgids as $vgid) {
					$vendor = O('vendor', ['gapper_group'=>$vgid]);
					if ($vendor->id) {
						$vendor->connect($user, 'member');
					}
				}
			}
		}
		else {
			$user = Gapper::create_user((int)$gapper_user);
			foreach ($gids as $gid => $foo) {
				$vendor = O('vendor', ['gapper_group'=>$gid]);
				if ($vendor->id) {
					$vendor->connect($user, 'member');
				}
			}
		}
	}
}
