<?php

class Icon_Controller extends Controller {

	function _before_call($method, &$params) {
		$this->ignore_extensions['index'][] = 'png';
		parent::_before_call($method, $params);
	}

	function index($name='', $id=0, $size=256){
		if ($name) O($name, $id)->show_icon($size);
	}
	
}
