<?php

class Approve_Controller extends Layout_Customer_Controller {

	function index() {

		$form = Input::form();

		$transfer_statement = O('transfer_statement', $form['sid']);

		$me = L('ME');

		if (!$transfer_statement->id || !$me->is_allowed_to('确认付费', $transfer_statement)) URI::redirect('error/401');
		$result = (array)$transfer_statement->pay();

		if ($result['success']) {
			$transfer_statement->approve();
			$pay_url = $result['url'] . '&cburl='. URI::url('!customer/approve/callback.'.$transfer_statement->id);
			URI::redirect($pay_url);
		}
		else {
            $this->layout->body = V('customer:transfer/error_message', array('code' => $result['ZT']));
		}
	}

	function callback($sid = 0) {
		$statement = O('transfer_statement', $sid);
		if (!$statement->id) URI::redirect('error/404');
		$this->layout->body = V('customer:transfer/callback_message', array('statement' => $statement));

	}

}
