<?php

class Order_Index_Controller extends Order_Base_Controller {

	function index($tab='all') {
		$me = L('ME');

		if (!$me->is_allowed_to('列表', 'order')) {
			URI::redirect('error/401');
		}

		$db = Database::factory();
		$status_tabs = Widget::factory('tabs');
		$status_tabs->class = 'secondary_tabs';

		// 全部订单 tab
		$label_all = 'all';
		$status_tabs->add_tab($label_all, array(
						'url' => URI::url('!admin/order/index/' . $label_all),
						'title' => HT('全部'),
						));
		$status_filters = [];
		$status_filters += array(
			Order_Model::STATUS_REQUESTING => array('weight'=>5, 'title'=>T('待买方确认')),
			Order_Model::STATUS_NEED_VENDOR_APPROVE => array('weight'=>10),
			Order_Model::STATUS_RETURNING => array('weight'=>15),
			Order_Model::STATUS_RETURNING_APPROVAL => array('weight'=>20),
		    Order_Model::STATUS_APPROVED => array('weight'=>25),
		    Order_Model::STATUS_PENDING_TRANSFER => array('weight'=>30),
		    Order_Model::STATUS_TRANSFERRED => array('weight'=>35),
		    Order_Model::STATUS_PENDING_PAYMENT => array('weight'=>40),
		    Order_Model::STATUS_PAID => array('weight'=>45),
		    Order_Model::STATUS_CANCELED => array('weight'=>50),
		);

		$no_count = array(
			Order_Model::STATUS_PAID,
			Order_Model::STATUS_CANCELED,
		);

		$found_tab = FALSE;
		foreach ($status_filters as $sf => $rows) {

			$label = Order_Model::$status_label[$sf];
			if (!$rows['title']) {
				$title = T(Order_Model::$status[$sf]);
			}
			else {
				$title = $rows['title'];
			}

			if ($label == $tab) $found_tab = TRUE;

			$tab_data = array(
	            'url' => URI::url('!admin/order/index/'.$label),
	            'title' => H($title),
	            'weight' => $rows['weight'] ?: 0
	        );

			if (!in_array($sf, $no_count)) {
				$count = Q("order[vendor=$vendor][status=$sf]")->total_count();
				if ($count > 0) {
					$tab_data['reminder'] = TRUE;
				}
			}
			$status_tabs->add_tab($label, $tab_data);
		}
		$join = [];
		$where = [];

		$join[] = "LEFT JOIN order_item  ON (order.id =order_item.order_id) ";
		// $join[] = "LEFT JOIN product ON (product.id=order_item.product_id) ";
		$join[] = "LEFT JOIN customer ON (customer.id=order.customer_id) ";
		$join[] = "LEFT JOIN user ON (user.id=customer.owner_id) ";
		$type = Input::form('type');
		if ($type == 'csv') {
			$temp_token = Input::form('token');
			$where = $_SESSION[$temp_token];
			$SQL_CSV = "SELECT order.id FROM `order` ";
			if (count($join)) {
				$SQL_CSV .= implode(' ', $join);
			}
			if (count($where)) {
				$SQL_CSV .= 'WHERE '.implode(' AND ', $where);
			}

			$SQL_CSV .= 'GROUP BY order.id ORDER BY order.ctime DESC';
			$orders = $db->query($SQL_CSV)->rows();
			call_user_func(array($this, '_export_'.$type), $orders);
		}
		else {
			$SQL = "SELECT DISTINCT order.id FROM `order` ";
			$label_status = array_flip(Order_Model::$status_label);


			if ($tab != $label_all) {
				if ($found_tab) {
					$status = $label_status[$tab];
				}
				else {
					reset($status_filters);
					$status = key($status_filters);
					$tab = Order_Model::$status_label[$status];
				}

				//状态为待结算，已结算的订单，都会出现在已付款的状态中
				if($status == Order_Model::STATUS_REQUESTING){
					$status = array(
						Order_Model::STATUS_REQUESTING,
						Order_Model::STATUS_NEED_CUSTOMER_APPROVE,
					);
				}

				if (isset($status)) {
					if (is_array($status)) {
						$statuses = implode(',', $status);
						$where[] = "order.status in ($statuses) ";
					}
					else {
						$where[] = "order.status=$status ";
					}
				}
			}

			$form = Site::form();
			if ($form['keyword']) {
				$keyword = $db->escape(trim($form['keyword']));
				$where[] =  "(order.order_no LIKE '%$keyword%')";
			}

			if ($form['customer'] || $form['customer_owner']) {
				$customer = $db->escape($form['customer']);
				$customer_owner = $db->escape($form['customer_owner']);
				if ($customer) {
					$where[] = "customer.name LIKE '%$customer%'";
				}
				if ($customer_owner) {
					$where[] = "user.name LIKE '%$customer_owner%'";
				}
			}
			$temp_token = Session::temp_token('order_list_', 300);
			$status_tabs->select($tab);
			if (count($join)) {
				$SQL .= implode(' ', $join);
			}
			if (count($where)) {
				$_SESSION[$temp_token] = $where;
				$SQL .= 'WHERE '.implode(' AND ', $where);
			}
			$num = $db->query($SQL)->count();

			$show_order_count_status = array(
					Order_Model::STATUS_NEED_VENDOR_APPROVE,
					Order_Model::STATUS_PENDING_APPROVAL,
					Order_Model::STATUS_CANCELED,
					Order_Model::STATUS_RETURNING_APPROVAL
				);
			if (!$status || in_array($status, $show_order_count_status)) {
				$total_count = $num;
			}

			$start = (int) $form['st'];
			$per_page = 20;
			$start = $start - ($start % $per_page);
			if ($start > 0) {
				$last = floor($num/ $per_page) * $per_page;
				if ($last == $num) {
					$last = max(0, $last - $per_page);
				}
				if ($start > $last) {
					$start = $last;
				}
				$SQL .= 'GROUP BY order.id ORDER BY order.ctime DESC LIMIT '.$start.','.$per_page;
				$result = $db->query($SQL);
			}
			else {
				$SQL .= 'GROUP BY order.id ORDER BY order.ctime DESC LIMIT 0,'.$per_page;
				$result = $db->query($SQL);
			}

	        $orders = [];
			if ($num > 0) {
				$objs = $result->rows();
				foreach ($objs as $obj) {
					$ids[] = $obj->id;
				}
				$ids = implode(',', $ids);
				$orders = Q("order[id=$ids]:sort(ctime D, id D)");
			}

			$show_orders_amount_status = array(
				Order_Model::STATUS_APPROVED,
				Order_Model::STATUS_PENDING_TRANSFER,
				Order_Model::STATUS_TRANSFERRED,
				Order_Model::STATUS_PENDING_PAYMENT,
				Order_Model::STATUS_PAID,
			);

			if (in_array($status, $show_orders_amount_status)) {
				$SQL = "SELECT sum(temp.price) FROM (
						SELECT distinct order.id,order.price AS price FROM `order` ". implode(' ', $join) .'WHERE '.implode(' AND ', $where).') AS temp';
				$amount = $db->value($SQL);
			}

			$panel_buttons = new ArrayIterator;
			$panel_buttons[] = array(
				'url' => URI::url("!admin/order/index/?type=csv&token=".$temp_token),
				'text' => T('导出 CSV'),
				'extra' => 'class="button button_save"',
				);

			$pagination = Widget::factory('pagination');
			$pagination->set(array(
								 'start' => $start,
								 'per_page' => $per_page,
								 'total' => $num,
								 ));
			$content = V('admin:orders/list', array(
				'amount' => $amount?:NULL,
				'total_count'=> $total_count?:NULL,
				'form' => $form,
				'orders' => $orders,
				'pagination' => $pagination,
				'status_tabs' => $status_tabs,
				'panel_buttons' => $panel_buttons,
			));
			$this->layout->body->primary_tabs
				->set('content', $content)
				->select('index');
		}
	}

	function view($id=0) {

		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('查看', $order)) {
			URI::redirect('error/401');
		}

		$order->unset_has_news_to($me);

		$form = Input::form();
		if ($form['submit_note']) {
			$order->admin_note = $form['admin_note'];
			$order->save();
			Site::message(Site::MESSAGE_NORMAL, HT('备注已更新!'));
		}

        $base_url = Config::get('vendor.bind_wechat_url');
        $items = Q("order_item[order=$order]");
        foreach($items as $item) {
            $datas []= [
                'url'           => $base_url."/order/".SITE_ID."/$order->voucher/$item->product_id",
                'orderNo'       => $order->voucher,
                'productName'   => $item->product->name,
                'manufacturer'  => $item->product->manufacturer,
                'catalogNo'     => $item->product->catalog_no,
                'package'       => $item->product->package,
                '@times'        => intval($item->quantity),
            ];
        }

        $datas = json_encode($datas, JSON_UNESCAPED_UNICODE);

        $content = V('admin:order/view', array(
            'order' => $order,
            'datas' => $datas,
        ));

		$this->layout->body->primary_tabs
			->add_tab('view', array(
				'url'=> $order->url(NULL, NULL, NULL, 'admin_view'),
				'title'=> HT('订单 #%order_no', array('%order_no'=>$order->order_no)),
			))
			->set('content', $content)
			->select('view');

		$this->layout->title = HT('订单 #%order_no', array('%order_no'=>$order->order_no));
	}

	function _export_csv($orders) {
		$csv = new CSV('php://output', 'w');
		/* 记录日志 */
		$me = L('ME');
		$log = sprintf('[order] %s[%d]以CSV导出了订单列表',
					   $me->name, $me->id);
		Log::add($log, 'order');

		$csv->write(array(
						T('订单ID'),
						T('订单编号'),
						T('时间'),
						T('订单商品'),
						T('买方'),
						T('买方ID'),
						T('买方负责人'),
						T('买方负责人ID'),
						T('订购人'),
						T('订购人ID'),
						T('供应商'),
						T('供应商ID'),
						T('商品单价'),
						T('购买数量'),
						T('商品总价'),
						T('订单总价'),
						T('状态'),
						));
		foreach ($orders as $order) {
			$order = O('order', $order->id);
			if ($order->id) {
				$order_items = Q("order_item[order=$order]");
				foreach ($order_items as $item) {
					$show_price = TRUE;
					$order_item = O('order_item', $item->id);
					$order = $order_item->order;
					$customer = $order->customer;
					$unit_price = $order_item->unit_price;
					if ($unit_price < 0) {
						$item_price = $order_price = $unit_price = T('待询价');
					}
					else {
						$unit_price = Number::currency($unit_price);
						$item_price = $order_item->unit_price * $order_item->quantity;
						$item_price = Number::currency($item_price);
						$order_price = Number::currency($order->price);
					}

					if ($order->status==Order_Model::STATUS_APPROVED && $order->is_transfer_failed()) {
						$status = HT('支付失败');
					}
					else {
						$status =  Order_Model::$status[$order->status];
					}
					if ($oid && ($oid==$order->id)) {
						$show_price = FALSE;

					}
					$oid = $order->id;
					$csv->write( array(
									 $order->id,
									 H($order->order_no),
									 Date::format($order->ctime, 'Y/m/d H:i:s'),
									 $order_item->product->name,
									 H($customer->name),
									 $customer->id,
									 H($customer->owner->name),
									 $customer->owner_id,
									 H($order_item->requester->name),
									 $order_item->requester_id,
									 H($order->vendor->name),
									 $order->vendor->id,
									 $unit_price,
									 $order_item->quantity,
									 $item_price,
									 $show_price ? $order_price: null,
									 $status,
									 ));
				}
				mysql_free_result($order_items);
			}
		}
		$csv->close();
	}

    function qr($id = 0)
    {
        $order_item = O('order_item', $id);

        if (!$order_item->id) {
            URI::redirect('error/404');
        }
        header('Pragma: no-cache');
        header('Content-type: image/png');
        $order = $order_item->order;
        $voucher = $order->voucher;
        $product = $order_item->product;
        $pid = $product->id;
        $pname = $product->name;
        $node = SITE_ID;
        $conf = Config::get('vendor.bind_wechat_url');

        $base_url = $conf."/order/$node/$voucher/$pid";

        $arr = [
            'U' => $base_url,
            'R' => $voucher,
            'P' => $pname
        ];

        $info = base64_encode(json_encode($arr, JSON_UNESCAPED_UNICODE));
        $qrCode = new \TCPDF2DBarcode($info, 'QRCODE,L');
        echo $qrCode->getBarcodePNG(5, 5);
        exit;
    }
	/*
	// deprecated(xiaopei.li@2012-07-04)
	function batch_approve() {
		$me = L('ME');
		$form = Input::form();

		if ($form['approve'] && is_array($form['select'])) {

			foreach ($form['select'] as $id) {

				$order = O('order', $id);
				if ($order->id &&
					$order->can_approve()  &&
					$me->is_allowed_to('审核', $order)) {

					$order->approve();
				}
			}
		}

		URI::redirect('!admin/order');
	}
	*/

}

class Order_Index_AJAX_Controller extends AJAX_Controller {
	function index_admin_note_click () {
		$form = Input::form();
		$order = O('order', $form['order_id']);
		if (!$order->id) return;
		JS::dialog( V('admin:order/admin_note_form', array('order'=>$order)));
	}

	function index_admin_note_submit () {
		$form = Input::form();
		$order = O('order', $form['order_id']);
		if (!$order->id) return;

		$order->admin_note = $form['admin_note'];
		$order->save();
		Site::message(Site::MESSAGE_NORMAL, HT('备注已更新!'));
		JS::refresh();

	}
	function view_approve_order_click($id=0) {

		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}

		$me = L('ME');
		if (!($order->can_approve() &&
			  $me->is_allowed_to('审核', $order))) {
			JS::redirect('error/401');
		}

		if (JS::confirm(HT('您确定要审核通过该订单吗?'))) {
            $order->approve();

            $callback = $order->url(NULL, NULL, NULL, 'admin_view');

            JS::redirect($callback);
		}

	}

	function view_set_paid_click($id = 0) {
		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}
		$me = L('ME');
		if (!$me->is_allowed_to('审核', $order)) {
			JS::redirect('error/401');
		}
		JS::dialog(V('admin:order/set_paid_form', array('order'=>$order)), array('title'=>HT('订单直接设置为已付款')));
	}

	function view_set_paid_submit($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}
		$me = L('ME');
		if (!$me->is_allowed_to('审核', $order)) {
			JS::redirect('error/401');
		}
		if (Config::get('order.set_order_paid_enable', FALSE) || $order->status != Order_Model::STATUS_APPROVED) {
			JS::redirect('error/401');
		}
		$form = Form::filter(Input::form());
		$order->status = Order_Model::STATUS_PAID;
        $now = new \Datetime();
        $now = $now->format('Y-m-d H:i:s');
		$order->mall_description = [
            'a' => H(T('**:user** 该订单直接设置为已结算', [
                        ':user' => $me->name,
                    ])),
            't' => $now,
            'u' => $me->gapper_user,
            'd' => $form['set_paid_note'],
        ];

		$comment = O('comment');
		$comment->is_log = TRUE;
		$comment->object = $order;
		$comment->content = H(T(':user 将该订单直接设置为已结算 ', [
                        ':user' => $me->name,
                    ])).$form['set_paid_note'];
		$comment->author = $me;
		$comment->save();

		if ($order->save()) {
			Site::message(Site::MESSAGE_NORMAL, HT('操作成功!'));
		}
		else {
			Site::message(Site::MESSAGE_ERROR, HT('操作失败!'));
		}
        $callback = $order->url(NULL, NULL, NULL, 'admin_view');
        JS::redirect($callback);
	}

	function view_order_bill_click($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('审核', $order)) {
			JS::redirect('error/401');
		}
		JS::dialog(V('admin:order/bill_form', array('order'=>$order)), array('title'=>HT('买方票据邮递')));
	}

	function view_bill_post_submit($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}
		$me = L('ME');
		if (!$me->is_allowed_to('审核', $order)) {
			JS::redirect('error/401');
		}

		$form = Form::filter(Input::form());
		$form->validate('post_no', 'not_empty', T('您必须填写快递单号!'));
		$form->validate('post_company', 'not_empty', T('您必须填写快递公司!'));
		if (!$form->no_error) {
			JS::dialog(V('admin:order/bill_form', array('order'=>$order, 'form'=>$form)), array('title'=>HT('买方票据邮递')));
			return;
		}

		$order->post_no = $form['post_no'];
		$order->post_company = $form['post_company'];
		$order->post_note = $form['post_note'];
		if ($order->save()) {
			Site::message(Site::MESSAGE_NORMAL, HT('更改票据邮递信息成功!'));
		}
		else {
			Site::message(Site::MESSAGE_ERROR, HT('更改票据邮递信息失败!'));
		}
        $callback = $order->url(NULL, NULL, NULL, 'admin_view');
        JS::redirect($callback);
	}

	function view_cancel_order_click($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}

		$me = L('ME');
		if (!($me->is_allowed_to('取消', $order)
			&& $order->admin_can_cancel())) {
			JS::redirect('error/401');
		}

		JS::dialog(V('admin:order/cancel_form', array('order'=>$order)), array('title'=>HT('取消订单')));
	}

	function view_cancel_order_submit($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}

		$me = L('ME');
		if (!( $me->is_allowed_to('取消', $order)
			&& $order->admin_can_cancel())) {
			JS::redirect('error/401');
		}

		$form = Form::filter(Input::form());
		$form->validate('reason', 'not_empty', T('您必须填写取消订单的理由!'));
		if (!$form->no_error) {
			JS::dialog(V('admin:order/cancel_form', array('order'=>$order, 'form'=>$form)), array('title'=>HT('取消订单')));
			return;
		}

		//跟踪信息
		$now = new \Datetime();
		$now = $now->format('Y-m-d H:i:s');
		$order->mall_description = [
			'a'=>H(T('**:user** **取消** 了该订单', [
		        	':user'=>$me->name,
            	])),
            't'=>$now,
            'u'=>$me->name,
            'd'=>$form['reason']
		];

		$order->cancel($form['reason']);

        $callback = $order->url(NULL, NULL, NULL, 'admin_view');
        JS::redirect($callback);
	}

	function view_return_approve_order_click($id=0) {

		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}

		$me = L('ME');
		if (!($order->can_return_approve() &&
			  $me->is_allowed_to('审核', $order))) {
			JS::redirect('error/401');
		}

		JS::dialog(V('admin:order/return_approve_form', array('order'=>$order)), array('title'=>HT('拒绝退货')));
	}

	function view_return_approve_order_submit($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}

		$me = L('ME');
		if (!($order->can_return_approve() &&
			  $me->is_allowed_to('审核', $order))) {
			JS::redirect('error/401');
		}

        $form = Form::filter(Input::form());
		$form->validate('reason', 'not_empty', T('您必须填写拒绝退货的理由!'));
        if ($form->no_error) {

        	//跟踪信息
			$now = new \Datetime();
			$now = $now->format('Y-m-d H:i:s');
			$order->mall_description = [
				'a'=>H(T('**:user** 驳回了退货申请', [
		        	':user'=>$me->name,
            	])),
	            't'=>$now,
	            'u'=>$me->gapper_user,
                'd'=>$form['reason']
			];

        	if ($order->return_approve($form['reason'])) {
	            Site::message(Site::MESSAGE_NORMAL, HT('拒绝退货成功!'));
	        }
	        else {
	            Site::message(Site::MESSAGE_ERROR, HT('拒绝退货失败!'));
	        }

	        $callback = $order->url(NULL, NULL, NULL, 'admin_view');
	        JS::redirect($callback);
        }
        else {
            JS::dialog(V('admin:order/return_approve_form', array('order'=>$order, 'form'=>$form)), array('title'=>HT('拒绝退货')));
        }
	}

	function view_return_order_click($id=0) {
		if (!JS::confirm('您确定要取消该订单吗?')) return;

		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}

		$me = L('ME');
		if (!($order->can_approve() &&
			  $me->is_allowed_to('审核', $order))) {
			JS::redirect('error/401');
		}

		$order->status = Order_Model::STATUS_CANCELED;

		//跟踪信息
		$now = new \Datetime();
		$now = $now->format('Y-m-d H:i:s');
		$order->mall_description = [
			'a'=>H(T('**:user** 同意了 **退货** 申请, **取消** 了该订单', [
		        	':user'=>$me->name,
            	])),
            't'=>$now,
            'u'=>$me->gapper_user,
		];

		$ret = $order->save();

		if ($ret) {
			Event::trigger('order_is_canceled', $order);

            Site::message(Site::MESSAGE_NORMAL, HT('取消成功!'));

            Notification::send('notification.cancel_order.user', $order->owner, array(
                '%user'=> Markup::encode_Q($order->owner),
                '%admin'=> Markup::encode_Q($me),
                '%reason'=> $form['reason']
            ), $me);
        }
        else {
            Site::message(Site::MESSAGE_ERROR, HT('取消失败!'));
        }

        $callback = $order->url(NULL, NULL, NULL, 'admin_view');

        JS::redirect($callback);
	}

	function index_batch_approve_submit() {
		$me = L('ME');
		$form = Input::form();

		if ($form['submit']) {
			if (is_array($form['select']) && count($form['select'])) {

				if (JS::confirm(HT('您确定审批这些订单么?'))) {

					$oids = array_keys($form['select']);
					$n_success = 0;

					foreach ($oids as $id) {

						$order = O('order', $id);
						if ($order->id &&
							$order->can_approve()  &&
							$me->is_allowed_to('审核', $order)) {

							if ($order->approve()) {
								$n_success++;
							}

						}
					}

					Site::message(Site::MESSAGE_NORMAL, HT('成功审批 %n_success 条订单', array(
								'%n_success' => $n_success
								)));
                    JS:refresh();
				}

			}
			else {
				JS::alert(HT('请选择要审核的订单'));
			}

		}

	}

    function view_transfer_order_click($id = 0) {
        $order = O('order', $id);
        if (!$order->id) JS::redirect('error/404');
        if (!$order->admin_can_transfer()) JS::redirect('error/401');;

        if (JS::confirm(T('您确定要对该订单付款吗?'))) {
            if ($order->set_transferred()) {
                Site::message(Site::MESSAGE_NORMAL, T('订单付款成功!'));
            }
            else {
                Site::message(Site::MESSAGE_ERROR, T('订单付款失败!'));
            }
            JS::refresh();
        }
    }

    function view_paid_order_click($id = 0) {
        $order = O('order', $id);
        if (!$order->id) JS::redirect('error/404');
        if (!$order->admin_can_pay()) JS::redirect('error/401');;

        if (JS::confirm(T('您确定要对该订单结算吗?'))) {

            if ($order->set_paid()) {
                Site::message(Site::MESSAGE_NORMAL, T('订单结算成功!'));
            }
            else {
                Site::message(Site::MESSAGE_ERROR, T('订单结算失败!'));
            }

            JS::refresh();
        }
    }

    function index_qrcode_export_click(){
        $form = Input::form();
        if (is_array($form)) {
            $item_id = $form['item_id'];
            if (!$item_id) JS::redirect('error/404');
            JS::dialog(V('admin:order/table/data/qrcode', array('item_id'=>$item_id)), array('title'=>HT('商品二维码')));
        }

    }
}
