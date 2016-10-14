<?php

class Index_Controller extends Layout_Admin_Controller {

	function index() {
		$this->layout->body = V('admin:default_body');
	}

}

class Index_AJAX_Controller extends AJAX_Controller {
	function index_sidebar_lock_toggle() {
		$form = Input::form();
		$_SESSION['sidebar_unlock'] = $form['unlock'];
	}
	
	function index_sbmenu_mode_click() {
		$form = Input::form();
		$uniqid = $form['uniqid'];
		
		$_SESSION['sidebar_mode'] = $form['mode'];
		
		Output::$AJAX['#'.$uniqid] = array(
				'data'=>(string) V('admin:sidebar/menu'),
				'mode'=>'replace'
			);
			
	}

}
