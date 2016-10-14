<?php

class Transfer_Controller extends Order_Base_Controller {

	protected function _add_index_tabs($customer) {
		parent::_add_index_tabs($customer);

		$bucket = Transfer_Bucket_Model::customer_bucket($customer);

		$stabs = Widget::factory('tabs');
		$stabs
			->add_tab('statements', array(
				'url'=>'!customer/transfer/statements.' . $customer->id,
				'title' => HT('付款单列表'),
			))
			->add_tab('bucket', array(
				'url'=>'!customer/transfer/bucket.' . $customer->id,
				'title' => HT('付款夹'),
				'number' => $bucket->item_count() ?: NULL,
			))
			;

		$stabs->class = 'secondary_tabs';

		$content = V('customer:transfer/content', array('secondary_tabs'=>$stabs));

		$this->layout->body->primary_tabs->content = $content;
	}

	function index($id = 0) {
		return $this->statements($id);
	}

	function bucket($id = 0) {
		$customer = O('customer', $id);
		if (!$customer->id) {
			URI::redirect('error/404');
		}
		if ($customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}

		$bucket = Transfer_Bucket_Model::customer_bucket($customer);
		if (!L('ME')->is_allowed_to('修改', $bucket)) {
			URI::redirect('error/401');
		}

		$this->_add_index_tabs($customer);


		$orders = Q("$bucket order:sort(ctime D)");

		$content = V('customer:transfer/bucket', array(
			'customer' => $customer,
			'form' => $form,
			'bucket' => $bucket,
			'orders' => $orders,
		));

		$stabs = $this->layout->body->primary_tabs->content->secondary_tabs;
		$stabs
			->set('content', $content)
			->select('bucket')
			;

		$this->layout->body->primary_tabs->select('transfer');
	}

	function empty_bucket($id=0) {
		$customer = O('customer', $id);
		if (!$customer->id) {
			URI::redirect('error/404');
		}

		if ($customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}

		$me = L('ME');
		$bucket = Transfer_Bucket_Model::customer_bucket($customer);
		if ($bucket->id && $me->is_allowed_to('修改', $bucket)) {
			$bucket->empty_bucket();
		}

		URI::redirect('!customer/transfer/bucket.' . $customer->id);
	}

	function remove_order($id=0) {
		$me = L('ME');
		$order = O('order', $id);
		$customer = $order->customer;
		if ($customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}
		$bucket = Transfer_Bucket_Model::customer_bucket($customer);
		if ($bucket->id && $order->id && $me->is_allowed_to('修改', $bucket)) {
			$bucket->remove_item($order);
		}

		URI::redirect('!customer/transfer/bucket.' . $customer->id);
	}

	function to_statement($id=0) {
		$customer = O('customer', $id);
		if (!$customer->id) {
			URI::redirect('error/404');
		}

		if ($customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}
		$me = L('ME');
		$bucket = Transfer_Bucket_Model::customer_bucket($customer);
		if (!$me->is_allowed_to('修改', $bucket) || $bucket->item_count() == 0) {
			URI::redirect('error/401');
		}
		$orders = Q("$bucket order");
		$balance = $orders->sum('price');
		$max_statement_price = Config::get('mall.max_transfer_statement_price');
		if ($balance >= $max_statement_price) {
			Site::message(Site::MESSAGE_ERROR, HT('付款单金额过大, 单笔金额不得超过 %max .', ['%max'=>$max_statement_price]));
			URI::redirect('!customer/transfer/bucket.'.$customer->id);
		}
		//增加锁机制，每个bucket只能有一个在生成付款单
		$mutex_file = Config::get('system.tmp_dir').Misc::key('transfer_bucket', $bucket->id);
		$fp = fopen($mutex_file, 'w+');
		if($fp){
			if (flock($fp, LOCK_EX | LOCK_NB)) {

				$statement = O('transfer_statement');
				$statement->customer = $customer;
				$statement->save();

				if ($statement->id) {
					$balance = 0;

					$content = array();

					foreach($orders as $order) {
						/*
						  订单从付款夹加入付款单后, 暂不修改订单状态,
						  到付款单申请付款再修改, 以防付款前删除付款单使订单状态频繁变换
						  (xiaopei.li@2012-06-06)
						*/
						$bucket->disconnect($order);
						$statement->connect($order);
						$balance += $order->price;
						foreach (Q("order_item[order={$order}]") as $i) {
							$content[] = $i->product->name;
						}
					}

					$statement->balance = $balance;
					$statement->save();

					Site::message(Site::MESSAGE_NORMAL, HT('成功生成付款单'));

					URI::redirect($statement->url());
				}
				else {
					Site::message(Site::MESSAGE_ERROR, HT('生成付款单失败'));
				}

				flock($fp, LOCK_UN);
				fclose($fp);
			}
			else{
				Site::message(Site::MESSAGE_ERROR, HT('系统繁忙, 请稍后重试'));
				URI::redirect('!customer/transfer/statements.'.$customer->id);
			}
		}
	}

	function statements($id=0, $tab='draft') {
		$customer = O('customer', $id);
		if (!$customer->id) {
			URI::redirect('error/404');
		}
		if ($customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}

		if (!L('ME')->is_allowed_to('列表付款单', $customer)) {
			URI::redirect('error/401');
		}

		$this->_add_index_tabs($customer);

		$form = Site::form();

		$selector = "transfer_statement[customer=$customer]:sort(ctime D)";

		$status_tabs = Widget::factory('tabs');
		$status_tabs->class = 'panel_tabs';

		$status_filters = array(
		    Transfer_Statement_Model::STATUS_DRAFT => NULL,
		    Transfer_Statement_Model::STATUS_PENDING_TRANSFER => NULL,
		    Transfer_Statement_Model::STATUS_TRANSFERRED => NULL,
		    Transfer_Statement_Model::STATUS_FAILED => NULL,
		);

		$found_tab = FALSE;
		foreach ($status_filters as $sf => $title) {
			$label = Transfer_Statement_Model::$status_label[$sf];
			if (is_null($title)) $title = T(Transfer_Statement_Model::$status[$sf]);
			if ($label == $tab) $found_tab = TRUE;
			$tab_data = array(
		            'url' => URI::url('!customer/transfer/statements.'.$customer->id.'.'.$label),
		            'title' => H($title),
		        );

		    $status_tabs->add_tab($label, $tab_data);
		}

		$label_status = array_flip(Transfer_Statement_Model::$status_label);
		if ($found_tab) {
			$status = $label_status[$tab];
		}
		else {
			reset($status_filters);
			$status = key($status_filters);
			$tab = Transfer_Statement_Model::$status_label[$status];
		}

		$status_tabs->select($tab);

		$selector .= "[status=$status]";

		$statements = Q($selector);

		$pagination = Site::pagination($statements, (int)$form['st'], 20);

		$content = V('customer:transfer/statements', array(
			'form' => $form,
			'statements' => $statements,
			'pagination' => $pagination,
			'status_tabs' => $status_tabs,
		));

		$stabs = $this->layout->body->primary_tabs->content->secondary_tabs;
		$stabs
			->set('content', $content)
			->select('statements')
			;

		$this->layout->body->primary_tabs->select('transfer');
	}

	function statement($id=0) {

		$me = L('ME');
		$statement = O('transfer_statement', $id);
		$customer = $statement->customer;
		if (!$statement->id) {
			URI::redirect('error/404');
		}

		if ($customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}

		if (!$me->is_allowed_to('查看', $statement)) {
			URI::redirect('error/401');
		}

		if ($statement->fail_reason) {
			Site::message(Site::MESSAGE_ERROR, T('此付款单上次付款失败, 原因为: %reason', array(
													 '%reason' => H($statement->fail_reason)
												)));
		}

		$this->_add_index_tabs($customer);

		$content = V('customer:transfer/statement', array(
			'statement' => $statement,
		));

		$this->layout->body->primary_tabs->content = $content;

		$this->layout->body->primary_tabs
			->add_tab('statement', array(
				'url'=> $statement->url(),
				'title' => HT('付款单 #%ref_no', array('%ref_no'=>$statement->voucher ?: Number::fill($statement->id, 6))),
			))
			->select('statement');
	}

}

class Transfer_AJAX_Controller extends AJAX_Controller {


	//添加取消订单功能 edit by sunxu 2015-04-13
	function index_customer_cancel_transfer_click(){

		if (JS::confirm(HT('确认取消该付款单?'))) {

			$form = Input::form();

			$transfer_statement = O('transfer_statement', $form['sid']);

			$me = L('ME');

			if (!$transfer_statement->id || !$me->is_allowed_to('取消付费', $transfer_statement))return JS::redirect('!customer/transfer/statements.'.$transfer_statement->customer->id);

			//如何查到付款单中所有的订单列表

			$order_list=Q("$transfer_statement order");

            $transfer_statement->status=Transfer_Statement_Model::STATUS_CANCEL;
            $transfer_statement->cancel_time = time();

			$transfer_statement->save();

			if(count($order_list)>0){

				foreach($order_list as $tmp_order){
					$tmp_order->status = Order_Model::STATUS_APPROVED;
					$tmp_order->save();
					$transfer_statement->disconnect($tmp_order);

				}

			}

			JS::redirect('!customer/transfer/statements.'.$transfer_statement->customer->id);

		}else{

			return false;

		}

	}

	function index_approve_transfer_click() {
		$transfer_statement = O('transfer_statement', Input::form('id'));
		$me = L('ME');
		$customer = $transfer_statement->customer;
		if ($customer->check_app_installed('lab-orders')) {
			return false;
		}

		if ($transfer_statement->id && $me->is_allowed_to('确认付费', $transfer_statement)) {

			JS::dialog((string)V('customer:transfer/approve_transfer', array(
					'statement' => $transfer_statement
			)), array(
				'title' => T('网上支付提醒:'),
			));
		}
	}

	function index_complete_pay_click() {
		$transfer_statement = O('transfer_statement', Input::form('sid'));
		$me = L('ME');
		$customer = $transfer_statement->customer;
		if ($customer->check_app_installed('lab-orders')) {
			return false;
		}
		if ($transfer_statement->id && $me->is_allowed_to('确认付费', $transfer_statement)) {
			$payment = $transfer_statement->get_payment();
        	$result = current((array)$payment->get_pay_status());

        	if (is_numeric($result['ZT'])) {
        		$transfer_statement->approve();
        	}
        	elseif ($result['ZT'] == '') {
        		$transfer_statement->reset();
        	}
		}

		JS::refresh();
	}

	function index_fail_pay_click() {
		$transfer_statement = O('transfer_statement', Input::form('sid'));
		$payment = $transfer_statement->get_payment();
		$result = current((array)$payment->get_pay_status());
		if ($result['ZT'] == '') {
			$transfer_statement->reset();
		}
		JS::refresh();
	}

	// TODO 此方法仅供测试用, 实际应为系统自动向第三方检查付款状态(xiaopei.li@2012-06-04)
	function index_transfer_success_click() {

		$transfer_statement = O('transfer_statement', Input::form('id'));
		$customer = $transfer_statement->customer;
		if ($customer->check_app_installed('lab-orders')) {
			return false;
		}
		$me = L('ME');

		// 由于仅是测试用, 所以此处未加权限判断
		if ($transfer_statement->id) {

			if ('Test' !== $transfer_statement->payment_method) {
				JS::redirect('error/401');
			}

			if (JS::confirm(HT('设置付款成功 (测试用, 可套用三方支付插件)'))) {
				$log = sprintf('%s[%id] 对付款单 #%d 调用了 Test 接口的 transfer_success',
							   $me->name, $me->id,
							   $transfer_statement->id);
				Log::add($log, 'transfer');

				$transfer_statement->success();
				Site::message(Site::MESSAGE_NORMAL, HT('付款成功'));

				JS::refresh();
			}
		}
	}

	// TODO 此方法仅供测试用, 实际应为系统自动向第三方检查付款状态(xiaopei.li@2012-06-07)
	function index_transfer_fail_click() {

		$transfer_statement = O('transfer_statement', Input::form('id'));
		$customer = $transfer_statement->customer;
		if ($customer->check_app_installed('lab-orders')) {
			return false;
		}
		$me = L('ME');

		// 由于仅是测试用, 所以此处未加权限判断
		if ($transfer_statement->id) {
			if ('Test' !== $transfer_statement->payment_method) {
				JS::redirect('error/401');
			}

			if (JS::confirm(HT('设置付款失败 (测试用, 实际应为系统自动向第三方检查付款状态)'))) {
				$log = sprintf('%s[%id] 对付款单 #%d 调用了 Test 接口的 transfer_fail',
							   $me->name, $me->id,
							   $transfer_statement->id);
				Log::add($log, 'transfer');

				$transfer_statement->fail('付款失败测试');
				Site::message(Site::MESSAGE_ERROR, HT('付款失败!'));

				JS::refresh();
			}
		}
	}

	function index_payment_approve_click() {
		$view = V('customer:transfer/payment_approve');
		JS::dialog($view);
	}
}
