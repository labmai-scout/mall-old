<?php

class Admin {

	static function vendor_notif_callback($item) {
		$me = L('ME');
		if (!$me->id) return 0;
        $vendors = RVendor_Model::getUnderApproval();
        //return Q('vendor[publish_date][!approve_date]')->total_count();
        return $vendors['total']?:0;
	}

	static function product_reminder_callback($item) {
		$me = L('ME');
		if (!$me->id) return 0;
		return Q('product[publish_date][!approve_date]:limit(1)')->length();
	}

	static function order_notif_callback($item) {
		$me = L('ME');
		if (!$me->id) return 0;
		return Q("order[vendor=$vendor][status=" . Order_Model::STATUS_PENDING_APPROVAL . "]")->total_count();
	}

	static function payment_notif_callback($item) {
		$me = L('ME');
		if (!$me->id) return 0;
		return Q("billing_statement[status=" . Billing_Statement_Model::STATUS_DRAFT . "]")->total_count();
		// 如果 vendor 真的要结算, 就拿着结算单找 admin 了, 此处不需特地提醒 admin DRAFT 结算单数 (xiaopei.li@2012-04-25)
		// return 0;
	}

	static function is_accessible($e, $module) {

	}

    static function layout_admin_sidebar_menu($e) {
        $me = L('ME');

        $sidebar = (array) $e->return_value;

        $mod_perms = array(
            'home' => array('首页'),
            'order' => array('管理订单'),
            'product' => array('管理商品'),
            'transfer' => array('管理付款'),
            'financial' => array('管理结算'),
            'vendor' => array('管理供应商'),
            'customer' => array('管理买方'),
            'user' => array('管理成员')
            // 10362 【Win 8，firfox】【南通大学】0.1.0，mall-old，买方管理，添加买方功能去掉
            //'system' => array('管理组织机构', '管理通知'),
            );

        foreach ($mod_perms as $mod => $perms) {
            foreach ($perms as $perm) {
                if ($me->access($perm)) {
                    $sidebar[$mod] = (array) Config::get("layout.admin.sidebar.menu.{$mod}");
                    break;
                }
            }
        }

        $e->return_value = $sidebar;

    }

	static function vendor_ACL($e, $user, $action, $vendor, $options) {
		switch ($action) {
			case '批量审批商家商品':
				if ($user->access('管理商品')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '删除':
			case '列表文件':
			case '上传文件':
			case '下载文件':
			case '修改文件':
			case '删除文件':
			case '创建目录':
			case '修改目录':
			case '删除目录':
				if ($user->access('管理供应商')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			}
	}

	static function product_ACL($e, $user, $action, $product, $options) {
		if ($user->access('管理商品')) { // TODO 商品和产品的权限是否要分开
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	static function customer_ACL($e, $user, $action, $customer, $options) {
		if ($user->access('管理买方')) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	static function order_ACL($e, $user, $action, $order, $options) {
		if ($user->access('管理订单')) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	static function billing_statement_ACL($e, $user, $action, $statement, $options) {
		if ($user->access('管理结算')) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	static function transfer_statement_ACL($e, $user, $action, $statement, $options) {
		if ($user->access('管理付款')) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

    static function comment_ACL($e, $user, $perm_name, $comment, $options) {
        switch ($perm_name) {
            case '发表评论':
                $e->return_value =TRUE;
                break;
            case '删除':
                // TODO switch $object type
                if ($user->access('管理所有内容')) {
                    $e->return_value = TRUE;
                }
                break;
            default:
        }
    }

	static function comment_saved($e, $comment, $old_data, $new_data) {
		if ('order' == $comment->object->name()) {
			$comment->object->reset_has_news_to_all_except($comment->author);
		}
	}

	static function order_is_drafted($e, $order) {
		$me = L('ME');

		self::create_system_comment($order, T('订单已由 %user 订购, 备注如下: %content',
							   array('%user' => $me->name,
									 '%content' => $order->description ? : T('无备注'),
								   )));

		if (Config::get('notification.enable_order_rt_notif')) {
			self::send_order_rt_notif($order, 'order_is_drafted_rt_notif');
		}
	}

	static function send_order_rt_notif($order, $notif, $args = array(), $receivers = NULL, $receiver_type = 'customer') {
		/*
			TODO

			1. 将接口中的 $recervers 换为:
			$receivers = array(
				'customer' => user#1,
				'customer' => user#2,
				'vendor' => user#3,
				'admin' => user#4,
			);

			2. $order->get_users_to_notify() 作同样修改

			3. Notification::send("notification.{$receiver_type}.{$notif}", $receiver, $args);

		*/
		switch ($notif) {
		case 'order_is_drafted_rt_notif':
		case 'order_is_confirmed_rt_notif':
		case 'order_is_approved_rt_notif':
		case 'order_is_return_approved_rt_notif':
		case 'order_is_canceled_rt_notif':
		case 'order_is_returning_rt_notif':
		case 'order_is_reject_to_return_rt_notif':
		case 'order_is_recovered_rt_notif':
		case 'order_is_transferred_rt_notif':
		case 'order_is_transfer_failed_rt_notif':
		case 'order_need_customer_confirm':
			if (!$receivers) {
				$receivers = $order->get_users_to_notify();
			}

			$order_no = H('#' . $order->order_no);

            $root_url = URI::url('/');

			switch ($receiver_type) {
			case 'customer':
				$order_link = URI::anchor($order->url(), $order_no);
				break;
			case 'vendor':
				$order_link = URI::anchor($order->url(NULL, NULL, NULL, 'vendor_view'), $order_no);
				break;
			case 'admin':
				$order_link = URI::anchor($order->url(NULL, NULL, NULL, 'admin_view'), $order_no);
				break;
			default:
				$order_link = $order_no;
			}

			$mall_name = Config::get('page.title_default');

			$order_detail = "";
			foreach (Q("$order order_item") as $item) {
				$order_detail .= T("%item(%unit_price) X %quantity\n", array(
									   '%item' => H($item->product->name),
									   '%unit_price' => $item->unit_price >= 0 ? Number::currency($item->unit_price) : '待询价',
									   '%quantity' => $item->quantity,
										));
			}
			$order_detail .= T("\n总价: %total_price\n", array(
									'%total_price' => ($order->price >=0) ? Number::currency($order->price) : '待询价',
									));


			foreach ($receivers as $receiver) {
				$default_args = array(
					'%mall' => H($mall_name),
					'%order' => $order_no,
					'%order_link' =>  $order_link,
					'%customer' => H($order->customer->name),
					'%vendor' => H($order->vendor->name),
					'%receiver' => H($receiver->name),
					'%purchaser' => H($order->purchaser->name),
					'%order_detail' => $order_detail,
					);


				$args = ((array) $args) + $default_args; // 参数中传来的 $args 会将 $default_args 覆盖

				Notification::send("notification.{$notif}", $receiver, $args);
			}
			break;
		}

	}

	static function order_need_customer_confirm($e, $order) {
		$receivers[] = $order->purchaser;
		if (Config::get('notification.enable_order_rt_notif')) {
			self::send_order_rt_notif($order, 'order_need_customer_confirm', array(), $receivers);
		}
	}

	static function order_is_edited($e, $order, $description=null) {
        $me = L('ME');
        if ($description) {
            $message = $description;
        }
        else {
            $message = T('订单已由 %user 修改', array('%user' => $me->name));
        }
		self::create_system_comment($order, $message);
	}

	static function order_is_confirmed_by_vendor($e, $order) {
		$me = L('ME');

		self::create_system_comment($order, T('%user 确认 了该订单。', array(
												   '%user' => $me->name)));
	}

	static function order_is_confirmed($e, $order) {
		self::create_system_comment($order, T('订单已经双方确认'));

		if (Config::get('notification.enable_order_rt_notif')) {
			self::send_order_rt_notif($order, 'order_is_confirmed_rt_notif');
		}

	}

	static function order_is_approved($e, $order) {
		$me = L('ME');

		self::create_system_comment($order, T('订单已由 %user 审批通过', array(
												   '%user' => $me->name)));

		if (Config::get('notification.enable_order_rt_notif')) {
			self::send_order_rt_notif($order, 'order_is_approved_rt_notif');
		}

    }

    static function order_is_delivered($e, $order)
    {
        $me = L('ME');

		self::create_system_comment($order, T('订单已由供应商 %vendor 完成发货', array(
												   '%vendor' => $order->vendor->name)));
    }

	static function order_is_received($e, $order) {
		$me = L('ME');
		if ($me->name) {
			$user_name = $me->name;
		}
		else {
			$user_name = T('未知用户');
		}
		self::create_system_comment($order, T('订单已由 %user 确认收货', array(
												   '%user' => $user_name)));
	}

	static function order_is_return_approved($e, $order) {
		$me = L('ME');

		self::create_system_comment($order, T(' %user 拒绝了退货申请, 备注如下: %content', array(
												   '%user' => $me->name,
                                                     '%content' => $order->return_approved_reason ? : T('(无)'),
                                                   )));

		if (Config::get('notification.enable_order_rt_notif')) {
			self::send_order_rt_notif($order, 'order_is_return_approved_rt_notif');
		}

	}

	static function order_is_canceled($e, $order) {
		$me = L('ME');

		//取消订单，删除订单关联的付款单
		$status = Transfer_Statement_Model::STATUS_DRAFT;
		$transfer_statement = Q("$order transfer_statement[status={$status}]")->current();
		if($transfer_statement->id){
			$transfer_statement->delete(TRUE);
		}

		self::create_system_comment($order, T('订单已由 %user 取消, 备注如下: %content',
							   array('%user' => $me->name,
									 '%content' => $order->cancel_reason ? : T('(无)'),
								   )));

		if (Config::get('notification.enable_order_rt_notif')) {
			self::send_order_rt_notif($order, 'order_is_canceled_rt_notif', array(
										  '%canceler' => H($me->name)));
		}

	}

	static function order_is_returning($e, $order) {
		$me = L('ME');

		self::create_system_comment($order, T('订单已由 %user 申请退货, 备注如下: %content',
							   array('%user' => $me->name,
									 '%content' => $order->return_reason ? : T('(无)'),
								   )));

		if (Config::get('notification.enable_order_rt_notif')) {
			self::send_order_rt_notif($order, 'order_is_returning_rt_notif', array(
										  '%returner' => H($me->name)));
		}

	}

	static function order_is_reject_to_returning($e, $order) {
		$me = L('ME');

		self::create_system_comment($order, T('订单已由 %user 打回退货, 备注如下: %content',
							   array('%user' => $me->name,
									 '%content' => $order->return_reason ? : T('(无)'),
								   )));

		if (Config::get('notification.enable_order_rt_notif')) {
			self::send_order_rt_notif($order, 'order_is_reject_to_return_rt_notif', array(
										  '%returner' => H($me->name)));
		}

	}

	static function order_is_recovered($e, $order) {
		$me = L('ME');

		self::create_system_comment($order, T('订单已由 %user 拒绝退货, 备注如下: %content',
            array('%user' => $me->name,
                  '%content' => $order->recover_reason ? : T('(无)'),
            )));

		if (Config::get('notification.enable_order_rt_notif')) {
			self::send_order_rt_notif($order, 'order_is_recovered_rt_notif');
		}

	}

	static function order_is_transferred($e, $order) {
		self::create_system_comment($order, T('订单已付款成功'));

		// TODO 通知内容增加付款单的信息(xiaopei.li@2012-06-07)
		if (Config::get('notification.enable_order_rt_notif')) {
			self::send_order_rt_notif($order, 'order_is_transferred_rt_notif');
		}

	}

	static function order_is_transfer_failed($e, $order) {
		self::create_system_comment($order, T('订单付款失败'));

		// TODO 通知内容增加付款单的信息(xiaopei.li@2012-06-07)
		if (Config::get('notification.enable_order_rt_notif')) {
			self::send_order_rt_notif($order, 'order_is_transfer_failed_rt_notif');
		}

	}

	static function product_saved($e, $product, $old_data, $new_data) {

		if (!$product->id) return;

		//正在批量导入的时候不更新sphinx索引
		if($product->vendor->is_processing_import_products) return;

		$index_fields = array(
			'name', 'manufacturer','brand', 'catalog_no', 'category', 'description', 'keywords',
			'status', 'product', 'vendor', 'publish_date', 'approve_date',
			'freeze_reasons', 'price',
		);

		$modified_fields = (array) array_keys($old_data);
		//检查是否修改了相关字段
		$intersect = array_intersect($modified_fields, $index_fields);
		if (count($intersect) == 0) return;
		Search_Product::update_index($product);
	}


	static function product_deleted($e, $product) {
		Search_Product::delete_index($product);
		if (Module::is_installed('prod_servers')) {
			$gapper_app_product = O('gapper_app_product', ['product'=>$product]);
			if ($gapper_app_product->id) {
				$gapper_app_product->delete();
			}
		}
	}

	static function order_saved($e, $order, $old_data, $new_data) {
		if (!$order->id) return;

        $items = Q("order_item[order={$order}]");

        if (
            (isset($new_data['status']) && !isset($old_data['status']))
            || ($new_data['status'] != $old_data['status'])
            || ($new_data['confirm'] != $old_data['confirm'])
			|| ($new_data['deliver_status'] != $old_data['deliver_status'])
            ) {

            $activity = O('order_activity');
            $activity->order = $order;
            $activity->status = $new_data['status'] ?: $order->status;
            $activity->time = Date::time();
            $activity->save();

            //同步更新order_item的status
            foreach($items as $item) {
                if ($item->status != $order->status) {
                    $item->status = $order->status;
                    $item->save();
                }
            }
        }

		$index_fields = array(
			'price', 'status', 'order_no', 'vendor', 'customer', 'deliver_status',
		);

		$modified_fields = (array) array_keys($old_data);

		//检查是否修改了相关字段
		$intersect = array_intersect($modified_fields, $index_fields);
		if (count($intersect) == 0) return;

        //如果设定order为已收货，则item也均已收货
        if ($new_data['deliver_status'] == Order_Model::DELIVER_STATUS_RECEIVED) {
            foreach($items as $item) {
                if ($item->deliver_status != Order_Item_Model::DELIVER_STATUS_RECEIVED) {
                    $item->deliver_status = Order_Item_Model::DELIVER_STATUS_RECEIVED;
                    $item->save();
                }
            }
        }

        if ($new_data['deliver_status'] == Order_Model::DELIVER_STATUS_DELIVERED) {
            foreach($items as $item) {
                if ($item->deliver_status != Order_Item_Model::DELIVER_STATUS_RECEIVED) {
                    $item->deliver_status = Order_Item_Model::DELIVER_STATUS_DELIVERED;
                    $item->deliver_date = Date::time();
                    $item->save();
                }
            }
        }

	}

	// 处理形如 product_type.reagent 的准营类别过期 (xiaopei.li@2012-05-15)
	static function vendor_scope_expired($e, $vendor_scope) {
        $type_prefix = 'product_type.';
        return true;

		$pattern = "/^{$type_prefix}(?P<type>\w+)$/";

		if (preg_match($pattern, $vendor_scope->name, $matches)) {
			$type = $matches['type'];

			$db = Database::factory();
			$sphinx = Database::factory('@sphinx');

			$unapprove_date = Date::time();

			$query_sql = "UPDATE `product` SET `last_approver_id`=`product`.`approver_id`, `last_approve_date`=`product`.`approve_date`, `unapprove_date`={$unapprove_date},`approve_date`=0, `approver_id`=0 WHERE `vendor_id`={$vendor_scope->vendor->id} and `type`='{$type}' and `publish_date`>0 and `approve_date`>0";

			$ret = $db->query($query_sql);

			if($ret) {
				$types_sphinx_indexes =  Config::get('product.types_sphinx_indexes');
				$product_type = $types_sphinx_indexes[$type];
				//更新product表的索引
				$product_table = Search_Iterator::get_index_name('product');
				$sphinx_sql = "UPDATE `$product_table` SET `approve_date`=0 WHERE `vendor_id`={$vendor_scope->vendor->id} and `type`={$product_type} and `publish_date`>0 and `approve_date`>0";
				$sphinx->query($sphinx_sql);

				//更新sphinx，只是将对应商品的approve_date变为0，所以直接update
				$pt = Search_Iterator::get_index_name('product_'.$type);
				$sphinx_sql = "UPDATE `$pt` SET `approve_date`=0 WHERE `vendor_id`={$vendor_scope->vendor->id} and `publish_date`>0 and `approve_date`>0";
				$sphinx->query($sphinx_sql);

			}

			//unapprove 架上的商品
			//需要重新计算商品数量的供货商
			$vendors = $db->query("SELECT `vendor_id` FROM `product` WHERE `vendor_id`={$vendor_scope->vendor->id} and type='{$type}' and `publish_date`>0 and `approve_date`>0 group by `vendor_id`")->rows();

			//统计供货商的商品数量
			foreach ($vendors as $vendor) {
				$vid = $vendor->vendor_id;
				$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");
			}

			return FALSE;
		}

	}

	//资质未过期批量上架
	static function vendor_scope_approve($e, $vendor_scope) {
		$type_prefix = 'product_type.';

		$pattern = "/^{$type_prefix}(?P<type>\w+)$/";

		if (preg_match($pattern, $vendor_scope->name, $matches)) {
			$type = $matches['type'];
			$db = Database::factory();
			$sphinx = Database::factory('@sphinx');

			$now = time();

			$query_sql = "UPDATE `product` SET `approve_date`={$now}, `mtime`={$now} WHERE `vendor_id`={$vendor_scope->vendor->id} and `type`='{$type}' and `publish_date`>0 and `approve_date`=0";

			$ret = $db->query($query_sql);

			if($ret) {
				$types_sphinx_indexes =  Config::get('product.types_sphinx_indexes');
				$product_type = $types_sphinx_indexes[$type];
				//更新product表的索引
				$product_table = Search_Iterator::get_index_name('product');
				$sphinx_sql = "UPDATE `$product_table` SET `approve_date`={$now} WHERE `vendor_id`={$vendor_scope->vendor->id} and `type`={$product_type} and `publish_date`>0 and `approve_date`=0";
				$sphinx->query($sphinx_sql);

				//更新sphinx，只是将对应商品的approve_date变为$now，所以直接update
				$pt = Search_Iterator::get_index_name('product_'.$type);
				$sphinx_sql = "UPDATE `$pt` SET `approve_date`={$now} WHERE `vendor_id`={$vendor_scope->vendor->id} and `publish_date`>0 and `approve_date`=0";
				$sphinx->query($sphinx_sql);

			}
		}
	}


	static function vendor_scope_saved($e, $vendor_scope, $old_data, $new_data) {
		$now = Date::time();

		/*
		echo '<br/>';
		var_dump('new: ' . Date::format($new_data['expire_date']));
		echo '<br/>';
		var_dump('old: ' . Date::format($old_data['expire_date']));
		echo '<br/>';
		var_dump('now: ' . Date::format($vendor_scope->expire_date));
		echo '<br/>';
		*/

		if ((int)$new_data['expire_date'] > $now &&
			(int)$old_data['expire_date'] < $now) {
			// 新建/从过期延长的 vendor_scope 要记录在 vendor 中, 以待解冻关联商品 (xiaopei.li@2012-08-20)
			// TODO 此处这种延期的解决方法不好, 过于依赖 cron 脚本, 是否应像 vendor 一样 cli 连动?

			$extended_scopes = $vendor_scope->vendor->extended_scopes ? : array();
			$extended_scopes[$vendor_scope->name] = $vendor_scope->name;
			$vendor_scope->vendor->extended_scopes = $extended_scopes;
			$vendor_scope->vendor->save();
		}
	}

	// 在管理员通过 product 上架申请时, 要检查 product 的 vendor 的合法性(xiaopei.li@2012-05-24)
	static function approve_product_check_vendor($e, $product) {
		$vendor = O('vendor', ['gapper_group'=>$product->vendor_id]);

		if (!$vendor->id) {
			$e->return_value = HT('供应商不存在!');
			return FALSE;
		}

		if (!$vendor->approve_date || !$vendor->publish_date) {
			$e->return_value = HT('供应商已下架!');
			return FALSE;
		}
	}

	// 在管理员通过 product 上架申请时, 要检查 product 所属 vendor_scope 的合法性(xiaopei.li@2012-05-24)
	// 检测供应商是否已经过期
    static function approve_product_check_vendor_scope($e, $product) {
		$vendor = O('vendor', ['gapper_group'=>$product->vendor_id]);
		$now = Date::time();
        //供应商是否过期
        if($vendor->expire_date && $vendor->expire_date < $now) {
            $e->return_value = HT('该商家已过期');
            return FALSE;
        }

		$product_type = $product->type;
		switch ($product_type) {
			case 'chem_reagent':
				$type = 'reagent';
				break;
			case 'bio_reagent':
				$type = 'biologic_reagent';
				break;
			case 'consumable':
				$type = 'consumable';
				break;
        }
        $class_name = "Product_{$type}";

        if (class_exists($class_name)) {
            $label_and_scopes = (array) call_user_func("{$class_name}::get_scopes_needed_to_check", $product);
            /*
               $label_and_scopes = array(
               array(
               $label,
               $scope,
               ),
               ...
               );
             */

            $now = Date::time();
            try {
                foreach ($label_and_scopes as $label_and_scope) {
                    list($scope_label, $scope) = $label_and_scope;
                    if (!$scope->id) {
                        throw new Error_Exception( HT('该商家不允许销售 %scope_label',
                                    array('%scope_label' => $scope_label)) );
                    }
                    if ($scope->expire_date <= $now) {
                        throw new Error_Exception( HT('该商家对 %scope_label 的准营期限已过期',
                                    array('%scope_label' => $scope_label)) );
                    }
                }
            }
            catch (Error_Exception $exception) {
                $e->return_value = $exception->getMessage();
                return FALSE;
            }
        }
    }

	//只进行供应商商品的下架
	static function cli_unapprove_products($vendor) {
		putenv('Q_ROOT_PATH='.ROOT_PATH);
		putenv('SITE_ID='.SITE_ID);

		// 只要脚本符合 1. 无输出 2.后台运行, 就能让 controller 继续
        //setlocale是为了escapeshellarg中可以使用中文
        setlocale(LC_CTYPE, 'UTF8', 'en_US.UTF-8');
		$cmd = 'php ' . ROOT_PATH . 'cli/unapprove_products.php -v %vid %me %extra > /dev/null 2>&1 &';
		$cmd = strtr($cmd, array(
						 '%vid' => escapeshellarg($vendor->id),
						 //'%extra' => '--unpublish=' . escapeshellarg($reason),
						 '%me' => '-u='.L('ME')->id
						 ));
		exec($cmd);

		sleep(2);
	}

	static function create_system_comment($object, $content, $ctime=0) {
		$comment = O('comment');
		$comment->is_log = TRUE;
		$comment->object = $object;
		$comment->content = $content;
		$comment->author = L('ME');

        if ($ctime) {
            $comment->ctime = $ctime;
        }

		return $comment->save();
	}

	static function notif_types_check_admin($user) {
		return $user->access('查看管理面板');
	}
	static function notif_types_check_vendor($user) {
		return Q("vendor[owner={$user}]")->total_count();
	}
	static function notif_types_check_customer_owner($user) {
		return Q("customer[owner={$user}]")->total_count();
	}
	static function notif_types_check_order_purchaser($user) {
		return Q("{$user}<member customer")->total_count();
	}
}
