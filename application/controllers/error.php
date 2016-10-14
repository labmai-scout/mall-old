<?php

class Error_Controller extends _Error_Controller {
	
	protected $layout_name = 'application:error_layout';
	
	function index($code=404) {
		$this->add_css('error');

		switch ($code) {
		case 401:
			if (!$_SESSION['#LOGIN_REFERER']) {
				$_SESSION['#LOGIN_REFERER'] = $_SESSION['HTTP_REFERER'];
			}
			break;
		}

		parent::index($code);
	}
	
}

