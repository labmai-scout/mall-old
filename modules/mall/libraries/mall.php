<?php

class Mall {

	static function product_ACL($e, $user, $action, $product, $options) {

		switch ($action) {
		// 应显示列出所有可处理的 action
		case '查看价格':
            $is_customer_member = (bool) Q("{$user}<member customer")->total_count();
            if ($is_customer_member) {
                $e->return_value = TRUE;
                return FALSE;
            }

            if ($product->vendor->id == $user->vendor->id) {
                $e->return_value = TRUE;
                return FALSE;
            }

			$e->return_value = TRUE;
			break;
		default;
			// 若询问的权限在此类中未提及, 则转交其他类处理
			return;
		}

		if ($user->access('管理所有内容')) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	static function cart_ACL($e, $user, $action, $product, $options) {

		switch ($action) {
		case '查看':
		case '结算':
		case '删除项目':
			if ($user->id && Q("$user<member customer")->total_count() > 0) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}

	}

	static function comment_ACL($e, $user, $perm_name, $thing, $options) {
		if ($user->id) {
			$e->return_value =TRUE;
		}
	}
}
