<?php

class Order_Controller extends Order_Base_Controller {

	function index($id=0) {
		$order = O('order', $id);

		if (!$order->id) {
			URI::redirect('error/404');
		}

		if ($order->customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('以买方查看', $order)) {
			URI::redirect('error/401');
		}

		if ($order->temp_price !== NULL && $order->status != Order_Model::STATUS_CANCELED) {
			Site::message(Site::MESSAGE_NORMAL, HT('供应商正在修改订单'));
		}

		$order->unset_has_news_to($me);

		$this->_add_index_tabs($order->customer);

		$form = Input::form();
		if ($form['submit_note']) {
			$order->customer_note = $form['customer_note'];
			$order->save();
			Site::message(Site::MESSAGE_NORMAL, HT('备注已更新!'));
		}

		$content = V('customer:order/view', array(
			'order' => $order,
		));

		$tabs = $this->layout->body->primary_tabs;
		$tabs
			->add_tab('view', array(
				'url'=> $order->url(),
				'title'=> HT('订单 #%order_no', array('%order_no'=>$order->order_no)),
			))
			->set('content', $content)
			->select('view');

		$this->layout->title = HT('订单 #%order_no', array('%order_no'=>$order->order_no));
		$this->layout->body->primary_tabs = $tabs;

	}

	function confirm($id = 0, $version = NULL) {
		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}

		$customer = $order->customer;
		if ($customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('以买方确认', $order)) {
			URI::redirect('error/401');
		}

		if ($order->version != $version) {
			Site::message(Site::MESSAGE_ERROR, HT('订单信息有过修改, 请重新确认'));
			URI::redirect($order->url());
		}

		if ($order->status == Order_Model::STATUS_NEED_VENDOR_APPROVE) {
			Site::message(Site::MESSAGE_ERROR, HT('订单信息有过修改, 需要供应商确认'));
			URI::redirect($order->url());
		}

		$order->customer_confirm();
		Site::message(Site::MESSAGE_NORMAL, HT('订单已确认'));
        URI::redirect($order->url());
	}

	function cancel($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}

		$customer = $order->customer;
		if ($customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}

		$me = L('ME');

		if (!($order->customer_can_cancel() &&
			  $me->is_allowed_to('以买方取消', $order))) {
			URI::redirect('error/401');
		}

		$order->cancel();
		Site::message(Site::MESSAGE_NORMAL, HT('订单已取消'));

		URI::redirect($order->url());
	}

	/*
	// deprecated
	function return_order($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		if (!($order->can_return() &&
			  $me->is_allowed_to('退货', $order))) {
			URI::redirect('error/401');
		}

		$order->return_order();
		Site::message(Site::MESSAGE_NORMAL, HT('退货请求已提交, 请联系卖家处理!'));

		URI::redirect($order->url());
	}
	*/

	/*
	// deprecated
	function transfer($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('确认付费', $order)) {
			URI::redirect('error/401');
		}

		$order->status = Order_Model::STATUS_PENDING_TRANSFER;
		$order->save();

		URI::redirect($order->url());
	}
	*/

}

class Order_AJAX_Controller extends AJAX_Controller {
	function index_customer_note_click () {
		$form = Input::form();
		$order = O('order', $form['order_id']);
		if (!$order->id) return;
		$customer = $order->customer;
		if ($customer->check_app_installed('lab-orders'))  return;
		JS::dialog( V('customer:order/customer_note_form', array('order'=>$order)));
	}

	function index_customer_note_submit () {
		$form = Input::form();
		$order = O('order', $form['order_id']);
		if (!$order->id) return;
		$customer = $order->customer;
		if ($customer->check_app_installed('lab-orders'))  return;
		$order->customer_note = $form['customer_note'];
		$order->save();
		Site::message(Site::MESSAGE_NORMAL, HT('备注已更新!'));
		JS::refresh();

	}

	function index_customer_approve_order_click($id=0) {

		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}
		if ($order->customer->check_app_installed('lab-orders'))  return FALSE;

		$me = L('ME');
		if ($order->status == Order_Model::STATUS_REQUESTING &&
			  $me->is_allowed_to('以买方确认', $order)) {
			$order->customer_confirmed = TRUE;
		}

		if (JS::confirm(HT('您确定要确认该订单吗?'))) {
			$order->status = Order_Model::STATUS_NEED_VENDOR_APPROVE;
			//申购人生成订单买方管理员确认后又取消订单 fix bug 8612  by sunxu 2015-04-16
			$order->customer_approved = TRUE;
			$order->save();
			$items = Q("order_item[order={$order}]");
			$now = Date::time();
			foreach ($items as $item) {
				$item->buyer_id = $me->id;
				$item->buy_date = $now;
				$item->save();
			}
			JS::refresh();
		}

	}

    public function index_cancel_click($id) {
        $order = O('order', $id);
        if (!$order->id) return FALSE;
        if ($order->customer->check_app_installed('lab-orders'))  return FALSE;
        $me = L('ME');
        if (!$order->customer_can_cancel() || ! ($me->is_allowed_to('以买方取消', $order) ||  $order->purchaser->id == $me->id)) return FALSE;
        JS::dialog(V('customer:order/cancel_form', array(
            'order'=> $order
        )));
    }

    public function index_cancel_submit($id) {
        $order = O('order', $id);
        if (!$order->id) return FALSE;
        if ($order->customer->check_app_installed('lab-orders'))  return FALSE;
        $me = L('ME');
        if (!$order->customer_can_cancel() || ! ($me->is_allowed_to('以买方取消', $order) ||  $order->purchaser->id == $me->id)) return FALSE;

        $form = Form::filter(Input::form());
        $form->validate('reason', 'not_empty', HT('取消理由不能为空!'));

        if ($form->no_error) {
            $requester = Q("$order order_item")->current()->requester;

            if ($order->cancel($form['reason'])) {
                $customer = $order->customer;
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

		if (!$order->id) return;
		if ($order->customer->check_app_installed('lab-orders'))  return FALSE;

		$bucket = Transfer_Bucket_Model::customer_bucket($order->customer);

		if (!$bucket->id) return;

		if (!$bucket->contains($order) &&
			L('ME')->is_allowed_to('付费', $order) &&
			$order->can_transfer()) {
			$bucket->add_item($order);
			JS::redirect(URI::url('!customer/transfer/bucket.' . $order->customer->id));
			/*
			$links = $order->links('customer_view');
			$links = array($links['remove_from_bucket']);

			JS::run(JS::smart()->jQuery('#'.$form['rel'])->popFade(0, 0, '<strong style="color:#f70">+1</strong>'));
			Output::$AJAX['#bucket_button'] = array('data'=> (string) V('customer:orders/bucket_button',
															  array('customer' => $order->customer)),
													'mode'=>'replace');
			Output::$AJAX['#'.$form['rel']] = array('data'=>(string)Widget::factory('application:links', array('links'=>$links)), 'mode'=>'replace');
			*/
		}

	}

	function index_remove_from_bucket_click() {
		$form = Input::form();
		$order = O('order', $form['id']);
		if (!$order->id) return;
		if ($order->customer->check_app_installed('lab-orders'))  return FALSE;
		$bucket = Transfer_Bucket_Model::customer_bucket($order->customer);
		if (!$bucket->id) return;

		if ($bucket->contains($order) &&
			L('ME')->is_allowed_to('付费', $order) &&
			$order->can_transfer()) {
			$bucket->remove_item($order);

			$links = $order->links('customer_view');
			$links = array($links['to_bucket']);

			Site::message(Site::MESSAGE_NORMAL, HT('已将订单移出付款夹!'));
			JS::refresh();

			// 由于移出付款夹后, 按钮需替换为 "加入付款夹" + "申请退货",
			// 用 ajax 替换并不方便, 所以改用 refresh() (xiaopei.li@2012-06-06)
			/*
			JS::run(JS::smart()->jQuery('#'.$form['rel'])->popFade(0, 0, '<strong style="color:#f70">-1</strong>'));
			Output::$AJAX['#bucket_button'] = array('data' => (string) V('customer:orders/bucket_button',
																		 array('customer' => $order->customer)),
													'mode'=>'replace');
			Output::$AJAX['#'.$form['rel']] = array('data'=>(string)Widget::factory('application:links', array('links'=>$links)), 'mode'=>'replace');
			*/
		}

	}

	function index_return_order_click($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}
		if ($order->customer->check_app_installed('lab-orders'))  return FALSE;

		$me = L('ME');

		if ($order->can_return() &&
			$me->is_allowed_to('退货', $order)) {
			JS::dialog( V('customer:order/return_form', array('order'=>$order)),
					   array('title'=>HT('申请退货')));
		}
		else {
			JS::redirect('error/401');
		}

	}

	function index_return_order_submit($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			JS::redirect('error/404');
		}
		if ($order->customer->check_app_installed('lab-orders'))  return FALSE;
		$me = L('ME');

		if ($order->can_return() &&
			$me->is_allowed_to('退货', $order)) {

			$form = Form::filter(Input::form());
			$form->validate('reason', 'not_empty', T('您必须填写退货理由!'));
			if (!$form->no_error) {
				JS::dialog(V('customer:order/return_form', array('order'=>$order, 'form'=>$form)), array('title'=>HT('申请退货')));
				return;
			}

			$order->return_order($form['reason']);
			JS::redirect($order->url(NULL, NULL, NULL, 'view'));
		}
		else {
			JS::redirect('error/401');
		}

	}

	function index_receive_click() {
		if (!JS::confirm(HT('您确定要确认收货吗?'))) {
			return FALSE;
		}

		$form = Input::form();
		$id = $form['id'];
		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}
		if ($order->customer->check_app_installed('lab-orders'))  return FALSE;

		$me = L('ME');

		if (!($order->customer_can_receive() &&
				$me->is_allowed_to('确认收货', $order))) {
			URI::redirect('error/401');
		}

		if ($order->receive()) {
			Site::message(Site::MESSAGE_NORMAL, HT('订单已确认收货'));
		}
		else {
			Site::message(Site::MESSAGE_ERROR, HT('确认收货失败'));
		}

        $callback = $order->url(NULL, NULL, NULL, 'view');
        JS::redirect($callback);
	}

	/*
	// deprecated
	function index_change_grant_click($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			return;
		}

		$me = L('ME');
		if (!$me->is_allowed_to('修改付费账号', $order)) {
			return;
		}

		$form = Input::form();
		Output::$AJAX['#'.$form['container_id']] = (string) V('customer:order/change_grant', array('order'=>$order));
	}

	// deprecated
	function index_change_grant_submit($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			return;
		}

		$me = L('ME');
		if (!$me->is_allowed_to('修改付费账号', $order)) {
			return;
		}

		$form = Input::form();

		$customer = $order->customer;
		$grant = O('customer_grant', array('id'=>$form['id'], 'customer'=>$customer));
		if ($grant->id) {
			$order->grant = $grant;
			$order->save();
			Site::message(Site::MESSAGE_NORMAL, T('订单的付费账号已设置成功!'));
		}
		else {
			Site::message(Site::MESSAGE_ERROR, T('无法设置该订单的付费账号!'));
		}

		JS::redirect($order->url());
	}
	*/

}
