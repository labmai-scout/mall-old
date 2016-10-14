<?php

class Payment_Test implements Payment_Handler {
	function pay($transfer_statement) {
		return TRUE;
	}

	function get_pending_links($transfer_statement) {
		return array(
			'transfer_success' => array(
					'url' => '#',
					'text' => T('付款成功'),
					'extra' => 'class="button button_tick" q-object="transfer_success" q-event="click" '.
					'q-static="' . H(array('id' => $transfer_statement->id)) . '" ' .
					'q-src="'.$transfer_statement->url(NULL, NULL, NULL, NULL).'"',
				),
			'transfer_fail' => array(
					'url' => '#',
					'text' => T('付款失败'),
					'extra' => 'class="button button_delete" q-object="transfer_fail" q-event="click" '.
					'q-static="' . H(array('id' => $transfer_statement->id)) . '" ' .
					'q-src="'.$transfer_statement->url(NULL, NULL, NULL, NULL).'"',
				),
			);
	}
}
