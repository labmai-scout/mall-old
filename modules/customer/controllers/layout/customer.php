<?php

class Layout_Customer_Controller extends Layout_Controller {

	protected $layout_name = 'customer:layout';

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->layout->sidebar = V('customer:sidebar');
		$this->layout->body = V('customer:body');
	}

	function _after_call($method, &$params) {
		parent::_after_call($method, $params);
		$this->add_css('customer:layout customer:sbmenu');
		$this->add_css('');
		$this->add_js('toggle');
	}
}
