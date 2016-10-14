<?php

class Catalog_Controller extends Layout_Controller {

	function index() {
		$this->layout->body = V('application:catalog');
	}

}
