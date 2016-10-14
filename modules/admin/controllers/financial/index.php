<?php

class Financial_Index_Controller extends Financial_Base_Controller {

	function index() {
		URI::redirect('!admin/financial/statement/');
	}
	
}
