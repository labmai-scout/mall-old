<?php

class Index_Controller extends Layout_Customer_Controller {

	function index() {
		$this->layout->body = V('customer:default_body');
	}

}