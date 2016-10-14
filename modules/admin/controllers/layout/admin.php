<?php

abstract class Layout_Admin_Controller extends Layout_Controller {

	protected $layout_name = 'admin:layout';

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->layout->sidebar = V('admin:sidebar');
		$this->layout->body = V('admin:body');
	}

	function _after_call($method, &$params) {
		parent::_after_call($method, $params);
		$this->add_css('admin:layout admin:sbmenu');
		$this->add_css('admin:theme');

		// datebox 需要以下一个样式和两个脚本的支持
		$this->add_css('date_box  token_box');
		$this->add_js('date_box jquery.mousewheel');

		$this->add_js('toggle  token_box');
		$this->add_css('category');

	}
}


