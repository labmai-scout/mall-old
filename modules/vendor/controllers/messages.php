<?php

class Messages_Controller extends Vendor_Layout_Controller {
	
	function _before_call($method, &$params) {
	
		parent::_before_call($method, $params);
		
		$this->layout->body->primary_tabs= Widget::factory('tabs');
		
		$this->layout->body->primary_tabs
			->add_tab('profile', array(
					'url'=> URI::url('!vendor/profile'),
					'title'=> T('基本信息'),
				));
		
		$this->layout->body->primary_tabs
				->tab_event('vendor.primary.tab', $params)
				->content_event('vendor.primary.content', $params);
		
	}
	
	function index($tabs='profile') {
		$this->layout->body->primary_tabs->select($tabs);
	}

}