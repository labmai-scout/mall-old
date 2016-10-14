<?php

// 只做跳转用
class Index_Controller extends Base_Controller {

	function index() {
		URI::redirect('!vendor/profile');
	}

}
