<?php

class Transfer_Statement_Controller extends Transfer_Base_Controller {

	function index($id = 0) {

		$statement = O('transfer_statement', $id);

		if (!$statement->id) URI::redirect('error/404');

		$this->layout->body->primary_tabs
			->add_tab('statement', array(
        			'url'=> $statement->url(NULL, NULL, NULL, 'admin_view'),
        			'title'=> H(T('付款单#'. ($statement->voucher ?: Number::fill($statement->id, 6)))),
        	))
        	->select('statement')
        	->set('content', V('statement/index', array(
        		'statement' => $statement
        	)));

	}

	/*
	function edit($id = 0) {
		$statement = O('transfer_statement', $id);
		if (!$statement->id || !L('ME')->is_allowed_to('管理', $statement)) return;

		$form = Site::form();

		$breadcrumb = array(
			array(
				'url' => $statement->url(NULL, NULL, NULL, 'admin_view'),
				'title' => H(T('付款单#'.($statement->voucher ?: Number::fill($statement->id, 6))))
				),
			array(
				'url' => $statement->url($tab, NULL, NULL, 'admin_edit'),
				'title' => T('修改')
				)
			);

		$this->layout->body->primary_tabs
			->add_tab('edit', array('*' => $breadcrumb))
			->select('edit')
			->set('content', V('statement/edit', array(
				'statement' => $statement,
				'form' => $form
			)));

	}
	*/
}

class Transfer_Statement_AJAX_Controller extends AJAX_Controller {
	function index_transfer_note_click () {
		$form = Input::form();
		$statement = O('transfer_statement', $form['statement_id']);
		if (!$statement->id) return;
		JS::dialog( V('admin:statement/statement_note_form', array('statement'=>$statement)));
	}

	function index_transfer_note_submit () {
		$form = Input::form();
		$statement = O('transfer_statement', $form['statement_id']);
		if (!$statement->id) return;

		$statement->admin_note = $form['admin_note'];
		$statement->save();
		Site::message(Site::MESSAGE_NORMAL, HT('备注已更新!'));
		JS::refresh();

	}

	function index_admin_approve_transfer_click() {

		$statement = O('transfer_statement', Input::form('id'));

		if (!$statement->id || !L('ME')->is_allowed_to('管理', $statement)) return;

		if (!JS::confirm('您确定完成该单付款吗? 一旦确定, 相关订单会完成并关闭, 操作不能撤销.')) return;

		$statement->success();

		Site::message(Site::MESSAGE_NORMAL, HT('批准付款成功!'));

		JS::refresh();

	}

	function index_admin_fail_transfer_click() {

		$statement = O('transfer_statement', Input::form('id'));

		if (!$statement->id || !L('ME')->is_allowed_to('管理', $statement)) return;
		JS::dialog(V('admin:statement/dialog/fail', array(
			'statement'=>$statement)),
			array('width'=>210, 'title'=>I18N::HT('orders', '请填写付款单失败原因')));
	}

	function index_admin_fail_transfer_submit() {
		$form = Form::filter(Input::form());
		$statement = O('transfer_statement', $form['id']);
		$form->validate('fail_reason', 'not_empty', T('请填写失败原因'));
		if ($form->no_error) {
			if ($statement->fail($form['fail_reason'])) {
				Site::message(Site::MESSAGE_NORMAL, T('付款失败操作成功!'));
			}
			else {
				Site::message(Site::MESSAGE_ERROR, T('付款失败操作失败!'));
			}
			JS::refresh();
		}
		else {
			JS::dialog(V('admin:statement/dialog/fail', array(
					'statement'=>$statement,
					'form'=>$form,
				)),
				array('width'=>210, 'title'=>I18N::HT('orders', '请填写付款单失败原因')));
		}
	}

	function index_remove_order_click() {
		$form = Input::form();
		$statement = O('transfer_statement', $form['sid']);
		$order = O('order', $form['oid']);

		if (!$statement->id || !L('ME')->is_allowed_to('管理', $statement)) return;

		$remove_one_order = TRUE;
		$msg = "您确定需要从付款单中移除该订单? 一旦确定，移除后订单将自动回到对应买方的付款夹!";

		if (Q("$statement order")->total_count() <= 1) {
			$remove_one_order = FALSE;
			$msg = "您确定需要删除该付款单吗？";
		}

		if (!JS::confirm($msg)) return;

		$bucket = Transfer_Bucket_Model::customer_bucket($statement->customer);

		if ($remove_one_order) {
			$statement->disconnect($order);

			$statement->balance = $statement->balance - $order->price;
			$statement->save();

			$bucket->add_item($order);
			Site::message(Site::MESSAGE_NORMAL, HT('您移除了 %order 订单', array(
				'%order' => Number::fill($order->id, 6)
			)));
			JS::refresh();
		}
		else {
			$sid = $statement->id;
			$statement->delete();
			Site::message(Site::MESSAGE_NORMAL, HT('您删除了 %statement 付款单', array(
				'%statement' => Number::fill($sid)
			)));
			JS::redirect('!admin/transfer');
		}


	}

}
