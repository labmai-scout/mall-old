<?php

class Orders_Controller extends Order_Base_Controller {

	function index($id=0, $tab='all') {
		$me = L('ME');
		$customer = O('customer', $id);

		$ret = $customer->check_app_installed('lab-orders');
		if ($ret) {
			URI::redirect('error/401');
		}

		if (!$customer->id) {
			URI::redirect('error/404');
		}

		if (!$me->is_allowed_to('列表订单', $customer)) {
			URI::redirect('error/401');
		}
		$db = Database::factory();
		$this->_add_index_tabs($customer);

		$tabs = Widget::factory('tabs');

		$status_tabs = Widget::factory('tabs');
		$status_tabs->class = 'secondary_tabs';


		$status_array = array(
			Order_Model::STATUS_APPROVED,//'待付款',
			Order_Model::STATUS_PENDING_TRANSFER,//'付款中',
			Order_Model::STATUS_TRANSFERRED,//'已付款',
			Order_Model::STATUS_PENDING_PAYMENT,//'待结算',
			Order_Model::STATUS_PAID,//'已结算',
        );

		// 全部订单 tab
		$label_all = 'all';
		$status_tabs->add_tab($label_all, array(
						'url' => $customer->url($label_all, NULL, NULL, 'orders'),
						'title' => HT('全部'),
						));

		// 订单发货状态相关的 tab
		$label_not_delivered = 'not_delivered';

		$not_delivered_selector = "order[customer={$customer}]" .
			"[deliver_status=" . Order_Model::DELIVER_STATUS_NOT_DELIVERED . "]" .
			"[status=". Q::quote($status_array) ."]";


		$count = Q($not_delivered_selector)->total_count();

		$tab_data = array(
			'url' => $customer->url($label_not_delivered, NULL, NULL, 'orders'),
			'title' => '待发货',
			'weight' => 20
			);
		if ($count > 0) {
			$tab_data['reminder'] = TRUE;
		}
		$status_tabs->add_tab($label_not_delivered, $tab_data);


		// 订单收货状态相关的 tab
		$label_not_received = 'not_received';

		$not_received_selector = "order[customer={$customer}]" .
			"[deliver_status=" . Order_Model::DELIVER_STATUS_DELIVERED . "]" .
			"[status=". Q::quote($status_array)."]";

		$count = Q($not_received_selector)->total_count();

		$tab_data = array(
			'url' => $customer->url($label_not_received, NULL, NULL, 'orders'),
			'title' => '待收货',
			'weight' => 30
			);
		if ($count > 0) {
			$tab_data['reminder'] = TRUE;
		}
		$status_tabs->add_tab($label_not_received, $tab_data);

		// end 订单发货状态相关的 tab
		$status_filters = array();
		if (Config::get('order.admin_approval_required')) {
			$status_filters = array(
				Order_Model::STATUS_PENDING_APPROVAL => array('weight'=>20),
			);
		}
		$status_filters += array(
			Order_Model::STATUS_REQUESTING => array('weight'=>10, 'title'=>T('待确认')),
			Order_Model::STATUS_NEED_VENDOR_APPROVE => array('weight'=>15),
			// Order_Model::STATUS_DRAFT => array('weight'=>15),
			Order_Model::STATUS_APPROVED => array('weight'=>25),
			Order_Model::STATUS_RETURNING => array('weight'=>30),
			Order_Model::STATUS_PENDING_TRANSFER => array('weight'=>35),
			Order_Model::STATUS_TRANSFERRED => array('weight'=>40),
			Order_Model::STATUS_CANCELED => array('weight'=>45),
		);

		$no_count = array(
		    Order_Model::STATUS_TRANSFERRED,
			Order_Model::STATUS_CANCELED,
		);

		$found_tab = FALSE;

		$new_filters = Event::trigger('customer.status.sort.filter');

		if ($new_filters) {
			$status_filters = $new_filters;
		}

		foreach ($status_filters as $sf => $row) {
			$label = Order_Model::$status_label[$sf];
			if (!$row['title']) {
				$title = T(Order_Model::$customer_status[$sf]);
			}
			else {
				$title = $row['title'];
			}

			if ($label == $tab) $found_tab = TRUE;

			$tab_data = array(
		            'url' => $customer->url($label, NULL, NULL, 'orders'),
		            'title' => H($title),
		            'weight' => $row['weight'] ?: 0
		        );

			if (!in_array($sf, $no_count)) {
				$count = Q("order[customer=$customer][status=$sf]")->total_count();
				if ($count > 0) {
					$tab_data['reminder'] = TRUE;
				}
			}
			else {
				$label = Order_Model::$status_label[$sf];
				$count1 = Q("$me<has_news order_item order[customer={$customer}][status={$sf}]:limit(1)")->length();
				$count2 = Q("$me<has_news order[customer={$customer}][status={$sf}]:limit(1)")->length();
				$count = $count1 + $count2;

				//管理方，买方，供应商的“已取消”，红点都不要 fix bug 8592  by sunxu 2015-04-16

				if($sf!=Order_Model::STATUS_CANCELED){
					if ($count > 0) {
						$tab_data['reminder'] = TRUE;
					}
				}

			}
		    $status_tabs->add_tab($label, $tab_data);
		}

		$label_status = array_flip(Order_Model::$status_label);

		if ($tab != $label_all) {
			if ($found_tab) {
				$status = $label_status[$tab];
			}
			else {
				if ($label_not_received != $tab && $label_not_delivered != $tab) {
					URI::redirect('error/404');
				}
			}
		}

		//状态为待结算，已结算的订单，都会出现在已付款的状态中
		if($status == Order_Model::STATUS_TRANSFERRED){
			$status = array(
				Order_Model::STATUS_TRANSFERRED,
				Order_Model::STATUS_PENDING_PAYMENT,
				Order_Model::STATUS_PAID,
			);
		}
		if($status == Order_Model::STATUS_REQUESTING){
			$status = array(
				Order_Model::STATUS_REQUESTING,
				Order_Model::STATUS_NEED_CUSTOMER_APPROVE,
			);
		}
		if($status == Order_Model::STATUS_RETURNING) {
			$status = array(
				Order_Model::STATUS_RETURNING,
				Order_Model::STATUS_RETURNING_APPROVAL,
			);
		}

		$type = Input::form('type');
		if ($type == 'print') {
			return $this->_index_print();
		}

		$form = Site::form();
		$status_tabs->select($tab);
		$join = [];
		$where = [];
		$SQL = "SELECT DISTINCT order.id FROM `order` ";
		if ($form['keyword']) {
			$keyword = $db->escape($form['keyword']);
			$join[] = "LEFT JOIN order_item  ON (order.id =order_item.order_id) ";
			$join[] = "LEFT JOIN product ON (product.id=order_item.product_id) ";
			$where[] =  "(product.name LIKE '%$keyword%' or order.order_no LIKE '%$keyword%')";
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

		if ($customer->id) {
			$customer_id = (int)$customer->id;
			$join[] = "LEFT JOIN customer ON (customer.id=order.customer_id) ";
			$where[] = "customer.id = '$customer_id'";
		}

		if ($label_not_delivered == $tab) {
			$deliver_status = Order_Model::DELIVER_STATUS_NOT_DELIVERED;
			$where[] = "order.deliver_status=$deliver_status";
			$arr = array(
				Order_Model::STATUS_REQUESTING,
				Order_Model::STATUS_NEED_VENDOR_APPROVE,
				Order_Model::STATUS_NEED_CUSTOMER_APPROVE,
				Order_Model::STATUS_PENDING_APPROVAL,
				Order_Model::STATUS_RETURNING,
				Order_Model::STATUS_RETURNING_APPROVAL,
				Order_Model::STATUS_CANCELED,
				);
			$statuses = implode(',', $arr);
			$where[] = "order.status not in ($statuses)";
		}

		if ($label_not_received == $tab) {
			$deliver_status = Order_Model::DELIVER_STATUS_DELIVERED;
			$where[] = "order.deliver_status=$deliver_status";
			$arr = array(
				Order_Model::STATUS_REQUESTING,
				Order_Model::STATUS_NEED_VENDOR_APPROVE,
				Order_Model::STATUS_NEED_CUSTOMER_APPROVE,
				Order_Model::STATUS_PENDING_APPROVAL,
				Order_Model::STATUS_RETURNING,
				Order_Model::STATUS_RETURNING_APPROVAL,
				Order_Model::STATUS_CANCELED,
				);
			$statuses = implode(',', $arr);
			$where[] = "order.status not in ($statuses)";
		}

		if (count($join)) {
			$SQL .= implode(' ', $join);
		}
		if (count($where)) {
			$SQL .= 'WHERE '.implode(' AND ', $where);
		}
		$SQL .= 'GROUP BY order.id ORDER BY order.id DESC ';
		$form_token = Session::temp_token('vendor_order_',300);
		$_SESSION[$form_token] = $SQL;
		$num = $db->query($SQL)->count();
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
			$SQL .= 'LIMIT '.$start.','.$per_page;
			$result = $db->query($SQL);
		}
		else {
			$SQL .= 'LIMIT 0,'.$per_page;
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
		$pagination = Widget::factory('pagination');
		$pagination->set(array(
							 'start' => $start,
							 'per_page' => $per_page,
							 'total' => $num,
							 ));

		$content = V('customer:orders/list', array(
			'customer' => $customer,
			'status_tabs' => $status_tabs,
			'form' => $form,
			'orders' => $orders,
			'pagination' => $pagination,
			'form_token' => $form_token
		));

		/*
		$tabs
			->add_tab('orders', array(
					'url'=> $customer->url($status_tabs->selected, NULL, NULL, 'orders'),
					'title'=> HT('订单列表'),
						  ))
			->set('content', $content)
			->select('orders');
		*/
		$this->layout->title = HT('历史订单');
		$this->layout->body->primary_tabs
			->select('orders')
			->set('content', $content);


	}

	function _index_print() {
		$SQL = $_SESSION[Input::form('form_token')];
		$db = Database::factory();
		$orders = $db->query($SQL)->rows();
		$this->layout = V('customer:orders/order_print', array('orders'=>$orders));
	}

}

class Orders_AJAX_Controller extends AJAX_Controller {

	/*
	// deprecated
	function index_order_view_click() {
		$form = Input::form();
		$order = O('order', $form['order_id']);
		JS::dialog(V('customer:orders/order/show_items', array('order'=>$order)), array('width'=>500, 'title'=>T('查看订单详情')));
	}
	*/
    public function index_cancel_click() {
    	$form = Input::form();
    	$id = $form['id'];
        $order = O('order', $id);

        if (!$order->id) return FALSE;

        $customer = $order->customer;
		$ret = $customer->check_app_installed('lab-orders');
		if ($ret) return FALSE;
        $me = L('ME');
        if (!$order->customer_can_cancel() || ! ($me->is_allowed_to('以买方取消', $order) ||  $order->purchaser->id == $me->id)) return FALSE;
        JS::dialog(V('customer:order/cancel_form', array(
            'order'=> $order
        )));
    }

    public function index_cancel_submit() {
    	$form = Input::form();
    	$id = $form['id'];
        $order = O('order', $id);
        if (!$order->id) return FALSE;
        $customer = $order->customer;
		$ret = $customer->check_app_installed('lab-orders');
		if ($ret) return FALSE;
        $me = L('ME');
        if (!$order->customer_can_cancel() || ! ($me->is_allowed_to('以买方取消', $order) ||  $order->purchaser->id == $me->id)) return FALSE;
        $form = Form::filter(Input::form());
        $form->validate('reason', 'not_empty', HT('取消理由不能为空!'));

        if ($form->no_error) {
            $requester = Q("$order order_item")->current()->requester;

            if ($order->cancel($form['reason'])) {
                Site::message(Site::MESSAGE_NORMAL, HT('取消成功!'));
            }
            else {
                Site::message(Site::MESSAGE_ERROR, HT('取消失败!'));
            }

            JS::redirect($order->url());
        }
        else {
            JS::dialog(V('customer:order/cancel_form', array(
                'order'=> $order,
                'form'=> $form
            )));
        }
    }

	function index_to_bucket_click() {
		$form = Input::form();
		$order = O('order', $form['id']);
		$customer = $order->customer;
		$bucket = Transfer_Bucket_Model::customer_bucket($customer);
		if (!($order->id &&
			  $bucket->id &&
			  L('ME')->is_allowed_to('付费', $order)) || $customer->check_app_installed('lab-orders')) {
			return;
		}

		if (!$bucket->contains($order)) {
			$bucket->add_item($order);
			$links = $order->links('customer_index');
			$links = array($links['remove_from_bucket']);

			JS::run(JS::smart()->jQuery('#'.$form['rel'])->popFade(0, 0, '<strong style="color:#f70">+1</strong>'));
			Output::$AJAX['#bucket_button'] = array('data'=> (string) V('customer:orders/bucket_button',
															  array('customer' => $customer)),
													'mode'=>'replace');
			Output::$AJAX['#'.$form['rel']] = array('data'=>(string)Widget::factory('application:links', array('links'=>$links)), 'mode'=>'replace');
		}

	}

	function index_remove_from_bucket_click() {
		$form = Input::form();
		$order = O('order', $form['id']);
		$customer = $order->customer;
		$bucket = Transfer_Bucket_Model::customer_bucket($customer);

		if (!($order->id &&
			  $bucket->id &&
			  L('ME')->is_allowed_to('付费', $order)) || $customer->check_app_installed('lab-orders')) {
			return;
		}

		if ($bucket->contains($order)) {
			$bucket->remove_item($order);

			$links = $order->links('customer_index');
			$links = array($links['to_bucket']);

			JS::run(JS::smart()->jQuery('#'.$form['rel'])->popFade(0, 0, '<strong style="color:#f70">-1</strong>'));
			Output::$AJAX['#bucket_button'] = array('data' => (string) V('customer:orders/bucket_button',
																		 array('customer' => $order->customer)),
													'mode'=>'replace');
			Output::$AJAX['#'.$form['rel']] = array('data'=>(string)Widget::factory('application:links', array('links'=>$links)), 'mode'=>'replace');
		}

	}

}
