<?php

class Request_Controller extends Layout_Customer_Controller {

	function _before_call($method, &$params) {

		parent::_before_call($method, $params);

		$this->layout->title = T('代购管理');

		$this->layout->body = V('customer:request/body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
	}

	function index($id) {

	}

}
