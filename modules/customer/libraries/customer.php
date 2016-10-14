<?php

class Customer {

	static function is_accessible($e, $module) {
		$me = L('ME');

		if ($me->id && Q("{$me}<member customer")->total_count()) {
			$e->return_value = TRUE;
		}

		return FALSE;
	}

	static function customer_ACL($e, $user, $action, $object, $options) {

		if (!$user->id) return;

		$object_name = is_string($object) ? $object : $object->name();

		switch ($object_name) {
		case 'customer':
			$customer = $object;

			switch($action) {
			case '买方查看':
			case '列表订单':
			case '取消付费':
			case '列表代购':
				$perms_required = array();
				break;
			case '以买方成员修改信息':
				$perms_required = array('管理买家信息');
				break;
			case '买方修改成员信息':
				$perms_required = array('管理买家成员');
				break;
			case '买方修改成员权限':
				$perms_required = array('设置成员权限');
				break;
			case '列表付款单':
				$perms_required = array('管理付款');
				break;
			case '添加订单':
				$perms_required = array('申购订单','管理订单');
				break;
			case '添加代购':
				$for_any_customers = TRUE;
				$perms_required = array('申请代购');
				break;
			default:
				return;
			}

			break;
		case 'product':
			switch ($action) {
			case '购买':
				$for_any_customers = TRUE;
				$perms_required = array('管理订单','申购订单');
				break;
			default:
				return;
			}
			break;
		case 'order':
			$customer = $object->customer;

			switch($action) {
			case '以买方查看':
				$perms_required = array();
				break;
			case '以买方确认':
				$perms_required = array('管理订单', '确认订单');
				break;
			case '确认收货':
				$perms_required = array('管理订单', '确认收货');
				break;
			case '以买方取消':
				$perms_required = array('管理订单','确认订单');
				break;
			case '以买方驳回':
			case '买方确认订单':
			case '退货':
				$perms_required = array('管理订单');
				break;
			case '确认付费':
			case '付费':
				$perms_required = array('管理付款');
				break;
			default:
				return;
			}

			break;
		case 'order_item':
			$customer = $object->order->customer;

			switch ($action) {
			case '评价':
				$perms_required = array('发表评价');
				break;
			default:
				return;
			}

			break;
		case 'transfer_statement':
			$customer = $object->customer;
			switch($action) {
			case '查看':
				$perms_required = array('管理付款');
				break;
			case '确认付费':
			case '删除':
			//添加取消订单功能 edit by sunxu 2015-04-15
			case '取消付费':
				$perms_required = array('管理付款');
				break;
			default:
				return;
			}


			break;
		case 'transfer_bucket':
			$customer = $object->customer;

			switch($action) {
			case '生成付款单':
			case '修改':
				$perms_required = array('管理付款');
				break;
			default:
				return;
			}

			break;
		default:
			return;
		}

		// 以上权限判断中, 有些是判断用户在**特定**买方是否可以做某事;
		// 而有些判断不需指明买方, 只要在任意买方可以做就行, 此种条件下,
		// 就会设 $for_any_customers 为 TRUE (xiaopei.li@2012-09-22)
		if ($for_any_customers) {

			if (Q("customer[owner={$user}]")->total_count()) {
				// 买方管理员具有该买方下所有权限, 所以若不需指明买方, here it is
				$e->return_value = TRUE;
				return FALSE;
			}
		}
		else {
			if (!$customer->has_member($user)) {

				return;
			}
			if ($user->id == $customer->owner->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
		}

		if(count($perms_required) == 0) {
			$e->return_value = TRUE;
			return FALSE;
		}

		$ret = FALSE;
		foreach ($perms_required as $p) {
			$query = "customer_member_perm[name={$p}][user={$user}]";
			if (!$for_any_customers) {
				$query .= "[customer={$customer}]";
			}

			if (Q($query)->total_count()) {
				$ret = TRUE;
                break;
			}
		}

		if ($ret) {
			$e->return_value = $ret;
			return FALSE;
		}

	}

	static function check_order_approval_required($e, $product) {
		$e->return_value = TRUE;
	}

	static function comment_ACL($e, $user, $perm_name, $object, $options) {
		$e->return_value = FALSE;
		return FALSE;
		switch ($perm_name) {
			case '发表评论':
				$e->return_value =TRUE;
				return FALSE;
				break;
            case '删除' :
                //自己可以删除自己创建的comment
                if ($object->author->id === $user->id) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
			default:
		}
	}
}
