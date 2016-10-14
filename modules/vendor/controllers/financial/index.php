<?php

class Financial_Index_Controller extends Base_Controller {

	// 只有已通过审核的 vendor 才可操作
	function _before_call($method, &$params) {
		URI::redirect('error/404');
		// depreacated !!!!! should be deleted later !!!!!! (xiaopei.li@2012-05-10)

		parent::_before_call($method, $params);

		$me = L('ME');
		$vendor = $me->vendor;

		if (!$me->is_allowed_to('查看财务', $vendor)) {
			URI::redirect('error/401');
		}
	}

	function index($tabs='list') {
		
		$user = L('ME');
		
		$this->layout->body->primary_tabs= Widget::factory('tabs');
		
		$content = V('vendor:financial/index');
		
		Event::bind('vendor.financial.content', array($this, '_index_financial_list'), 0, 'list');
		Event::bind('vendor.financial.content', array($this, '_index_financial_transaction'), 0, 'transaction');
		
		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
				->add_tab('list', array(
							'url'=>URI::url('!vendor/financial/index/index.list'),
							'title'=>T('财务概要')
						))
				->add_tab('transaction', array(
							'url'=>URI::url('!vendor/financial/index/index.transaction'),
							'title'=>T('财务明细'),
							'weight' => 10,
						))
				->set('class', 'secondary_tabs')
				->content_event('vendor.financial.content')
				->set('user', $user)
				->select($tabs);
		
		$content->secondary_tabs = $secondary_tabs;
		
		$this->layout->body->primary_tabs
			->add_tab('financial', array(
					'url'=> URI::url('!vendor/financial'),
					'title'=> H(T('财务情况')),
						  ))
			->set('content', $content)
			->select('financial');
			
		$this->layout->title = H(T('财务情况'));
		
	}
	
	function _index_financial_list($e, $tabs) {
		
		$user = $tabs->user;
		
		/*
		$panel_buttons[] = array(
			'url' => '#',
			'text' => T('充值'),
			'extra' => 'class="button button_add" 
						q-src="'.URI::url('!financial/index/index').'" 
						q-event="click" 
						q-object="account_credit" 
						q-static="'.H(array('vendor_id'=>$user->vendor->id)).'"',
		);
		
		$panel_buttons[] = array(
			'url' => '#',
			'text' => T('扣费'),
			'extra' => 'class="button button_delete" 
						q-src="'.URI::url('!financial/index/index').'" 
						q-event="click" 
						q-object="account_deduction" 
						q-static="'.H(array('vendor_id'=>$user->vendor->id)).'"',
		);
		*/
		
		$tabs->content = V('vendor:financial/list', array(
			'user' => $user,
			'panel_buttons' => $panel_buttons
		));
	}
	
	function _index_financial_transaction($e, $tabs) {
		$user = $tabs->user;
		
		$panel_buttons['print'] = array(
			'url' => URI::url('', array('type'=>'print')),
			'text' => T('打印'),
			'extra' => 'class="button button_print"',
		);
		
		$panel_buttons['csv'] = array(
			'url' => URI::url('', array('type'=>'csv')),
			'text' => T('导出CSV'),
			'extra' => 'class="button button_export"',
		);
		
		$tabs->content = V('vendor:financial/transactions', array(
			'user' => $user,
			'panel_buttons' => $panel_buttons
		));
	}
}