<?php

class Buynew_Controller extends Layout_Mall_Controller {

	function _after_call($method, $params) {
		parent::_after_call($method, $params);
	}

	function index() {
		$this->layout->body = V('mall:buynew');
	}

}
