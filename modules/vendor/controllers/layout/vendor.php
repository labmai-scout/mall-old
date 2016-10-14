<?php

class Layout_Vendor_Controller extends Layout_Controller {

	protected $layout_name = 'vendor:layout';

	function _before_call($method, &$params) {

		parent::_before_call($method, $params);

		$this->layout->sidebar = V('vendor:sidebar');
		$this->layout->body = V('vendor:body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
	}

	function _after_call($method, &$params) {
		parent::_after_call($method, $params);

		// datebox 需要以下一个样式和两个脚本的支持
		$this->add_css('date_box  token_box');
		$this->add_js('date_box jquery.mousewheel');

		$this->add_js('toggle  token_box');

		$this->add_css('vendor:layout vendor:sbmenu');
		$this->add_css('vendor:common');
		$this->add_css('application:category');

	}
}
