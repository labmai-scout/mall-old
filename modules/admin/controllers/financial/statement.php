<?php

class Financial_Statement_Controller extends Financial_Base_Controller {

	function index($tab = 'draft') {
		$form = Input::form();

		$selector = "billing_statement";

		$status_tabs = Widget::factory('tabs');
		$status_tabs->class = 'secondary_tabs';

		$status_filters = array(
		    Billing_Statement_Model::STATUS_DRAFT => '待审核',
		    Billing_Statement_Model::STATUS_REJECTED => '已驳回',
		    Billing_Statement_Model::STATUS_PENDING_CHECK => '结算中',
		    Billing_Statement_Model::STATUS_PAID => NULL,
		);

		// 如果 vendor 真的要结算, 就拿着结算单找 admin 了, 此处不需特地提醒 admin DRAFT 结算单数 (xiaopei.li@2012-04-25)
		$no_count = array(
			// Billing_Statement_Model::STATUS_DRAFT,
			Billing_Statement_Model::STATUS_REJECTED,
			Billing_Statement_Model::STATUS_PAID,
		);

		$found_tab = FALSE;
		foreach ($status_filters as $sf => $title) {
			$label = Billing_Statement_Model::$status_label[$sf];
			if (is_null($title)) $title = T(Billing_Statement_Model::$status[$sf]);

			if ($label == $tab) $found_tab = TRUE;
			$tab_data = array(
		            'url' => URI::url('!admin/financial/statement/index.'.$label),
		            'title' => H($title),
		        );

			if (!in_array($sf, $no_count)) {
				$count = Q("billing_statement[status=$sf]")->total_count();
				if ($count > 0) {
					$tab_data['number'] = $count;
				}
			}

		    $status_tabs->add_tab($label, $tab_data);
		}

		$label_status = array_flip(Billing_Statement_Model::$status_label);
		if ($found_tab) {
			$status = $label_status[$tab];
		}
		else {
			reset($status_filters);
			$status = key($status_filters);
			$tab = Billing_Statement_Model::$status_label[$status];
		}

		$status_tabs->select($tab);

		$selector .= "[status={$status}]";

		if ($form['ref_no']) {
			$statement = O('billing_statement', $form['ref_no']);
			if ($statement->id) {
				URI::redirect($statement->url(NULL, NULL, NULL, 'admin_view'));
			}
		}

		$selector .= ":sort(ctime D)";

		$statements = Q($selector);

		$type = strtolower(Input::form('type'));
		if ($type == 'csv') {
			call_user_func(array($this, '_export_'.$type), $statements);
		}
		else {
			$pagination = Site::pagination($statements, (int)$form['st'], 20);

			if ($tab == 'paid') {
				$panel_buttons = new ArrayIterator;
				$panel_buttons[] = array(
					'url' => URI::url("!admin/financial/statement/index.paid?type=csv"),
					'text' => T('导出已结算的结算单'),
					'extra' => 'class="button button_save"',
					);
			}



			$this->layout->body->primary_tabs
				->select('statement')
				->set('content', V('admin:financial/statements', array(
						'statements'=>$statements,
						'pagination'=>$pagination,
						'status_tabs' => $status_tabs,
						'panel_buttons' => $panel_buttons,
					)));
		}
	}


	function view($id = 0) {
		$me = L('ME');

		$statement = O('billing_statement', $id);
		if (!$statement->id) {
			URI::redirect('error/404');
		}

		$content = V('admin:financial/statement', array(
			'statement' => $statement,
		));

		$this->layout->body->primary_tabs->content = $content;

		$this->layout->body->primary_tabs
			->add_tab('statement', array(
				'url'=> $statement->url(NULL, NULL, NULL, 'admin_view'),
				'title' => HT('结算单 #%ref_no', array('%ref_no'=>Number::fill($statement->id, 6))),
			))
			->select('statement');

    }

    function approve($id=0) {
		$me = L('ME');

		$statement = O('billing_statement', $id);
		if (!$statement->id) {
			URI::redirect('error/404');
		}

		if (!$statement->canApprove()) {
		Site::message(Site::MESSAGE_ERROR, HT('结算单数据异常, 无法正常审批'));
			URI::redirect($statement->url(NULL, NULL, NULL, 'admin_view'));
		}
		$result = $statement->approve();
		if (is_array($result)) {
			if ($result['success']) {
				$pay_url = $result['url'] . '&cburl='. URI::url('!admin/financial/statement/callback.'.$statement->id);
				URI::redirect($pay_url);
			}
			else {
            	$this->layout->body = V('admin:financial/error_message', array('code' => $result['ZT']));
			}
		}
		else {
			URI::redirect($statement->url(NULL, NULL, NULL, 'admin_view'));
		}
	}

	function callback($sid = 0) {
		$statement = O('billing_statement', $sid);
		if (!$statement->id) URI::redirect('error/404');
		$this->layout->body = V('admin:financial/callback_message', array('statement' => $statement));

	}

	function rejected($id=0) {
		$me = L('ME');

		$statement = O('billing_statement', $id);
		if (!$statement->id) {
			URI::redirect('error/404');
		}

		$statement->rejected();

		URI::redirect($statement->url(NULL, NULL, NULL, 'admin_view'));
	}

	function _export_csv($statements) {
		$csv = new CSV('php://output', 'w');
		/* 记录日志 */
		$me = L('ME');
		$log = sprintf('[statement] %s[%d]以CSV导出了成员列表',
					   $me->name, $me->id);
		Log::add($log, 'journal');


		$p_type = Config::get('payment.pay_type');
		$pay_type = $p_type == 1 ? '试剂' : '';


		$csv->write(array(
						T('支付流水号'),
						T('类别'),
						T('金额'),
						T('对方省'),
						T('对方城市'),
						T('对方单位'),
						T('对方银行'),
						T('对方帐号'),
						T('日期'),
						));

		$roles = L('ROLES')->to_assoc('id', 'name');

		if ($statements->total_count() > 0) {
			foreach ($statements as $statement) {
				$csv->write( array(
								 H(Number::fill($statement->id, 6)),
								 H($pay_type),
								 Number::currency($statement->balance),
								 H($statement->vendor->province),
								 H($statement->vendor->city),
								 H($statement->vendor->name),
								 H($statement->vendor->bank_name),
								 H($statement->vendor->bank_account),
								 Date::format($statement->ctime),
								 ));
			}
		}
		$csv->close();
	}

}

class Financial_Statement_Ajax_Controller extends AJAX_Controller {
	function index_reject_statement_click(){
		$form = Input::form();
		$statement = O('billing_statement', $form['id']);
		if(!$statement->id) return;
		if (!$statement->canReject()) {
			JS::alert(HT('异常结算单, 无法驳回!'));
			return false;
		}
		if(JS::confirm(HT('您确定驳回该结算单吗?'))){
			JS::dialog(V('admin:financial/statements/reject', array('statement'=>$statement)));
		}
	}

	function index_reject_statement_submit(){
		$form = Form::filter(Input::form());
		$statement = O('billing_statement', $form['id']);
		if(!$statement->id) return;

		if (!trim($form['reject_reason'])) {
            $form->set_error('reject_reason', HT('驳回理由不能为空!'));
            JS::dialog(V('admin:financial/statements/reject', array(
                'statement' => $statement,
                'form'=>$form
                )));
        }
        else {
        	$statement->reject($form['reject_reason']);
			JS::refresh();
		}
	}

	function index_approve_account_click() {
		$billing_statement = O('billing_statement', Input::form('id'));
		$me = L('ME');
		if ($billing_statement->id) {

			JS::dialog((string)V('admin:financial/approve_account', array(
					'statement' => $billing_statement
			)), array(
				'title' => T('网上支付提醒:'),
			));
		}
	}

	function index_fail_account_click() {
		JS::refresh();
	}

	function index_complete_approve_click() {
		JS::refresh();
	}

}
