<?php

class API_Controller extends Controller {

	function index() {

		$api = new API;
		$api->dispatch();

	}

}
