<?php

class Order_Statement_Controller extends Order_Base_Controller {

	function index($id=0) {

		$me = L('ME');

		$statement = O('billing_statement', $id);
		if (!$statement->id) {
			URI::redirect('error/404');
		}


		if (!$me->is_allowed_to('以供应商查看', $statement)) {
			URI::redirect('error/401');
		}

		$vendor = $statement->vendor;
		if (!$vendor->id) {
			URI::redirect('error/404');
		}

		$this->_add_index_tabs($vendor);

		$content = V('vendor:billing/statement', array(
			'statement' => $statement,
		));

		$this->layout->body->primary_tabs->content = $content;

		$this->layout->body->primary_tabs
			->add_tab('statement', array(
				'url'=> $statement->url(NULL, NULL, NULL, 'vendor_view'),
				'title' => HT('结算单 #%ref_no', array('%ref_no'=>Number::fill($statement->id, 6))),
			))
			->select('statement');

	}

	function close($id=0) {
		$statement = O('billing_statement', $id);
		if (!$statement->id) {
			URI::redirect('error/404');
		}
		if (!$statement->can_close()) {
			URI::redirect('error/404');
		}
		$payment_voucher = $statement->payment_voucher;
		$transfer_statement = O('transfer_statement', ['voucher'=>$payment_voucher]);
		if ($transfer_statement->id) {
			$transfer_statement->success();
		}
		$statement->success();
		$log = sprintf('%s[%id] 关闭了结算单 #%d',
					   L('ME')->name, L('ME')->id,
					   $statement->id
					  );
		//结算log
		Log::add($log, 'order');
		Site::message(Site::MESSAGE_NORMAL, HT('结算单已关闭!'));
		URI::redirect('!vendor/order/statement/index.'.$statement->id);
	}

	function delete($id=0) {
		$statement = O('billing_statement', $id);
		if (!$statement->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		if (!$statement->can_delete() ||
			!$me->is_allowed_to('以供应商删除', $statement)) {
			URI::redirect('error/401');
		}

		$statement->delete();

		$log = sprintf('%s[%id] 删除了结算单 #%d',
					   L('ME')->name, L('ME')->id,
					   $statement->id
					  );
		//结算log
		Log::add($log, 'order');

		Site::message(Site::MESSAGE_NORMAL, HT('结算单已删除!'));
		URI::redirect('!vendor/order/billing/statements.'.$statement->vendor->id);
	}

	function settle($id=0) {
		$statement = O('billing_statement', $id);
		if (!$statement->id) {
			URI::redirect('error/404');
		}

		$statement->settle();

		URI::redirect($statement->url(NULL, NULL, NULL, 'vendor_view'));
	}
}