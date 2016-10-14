<?php

abstract class Base_Controller extends Layout_Controller {

	protected $layout_name = 'people:layout';

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->layout->sidebar = V('people:sidebar');		
		$this->layout->body = V('people:body');
	}
	
	function _after_call($method, &$params) {
		parent::_after_call($method, $params);
		$this->add_css('people:layout people:sbmenu');
	}

}

