<?php

class Public_Controller extends _Controller {
	
	function index(){
		$file = Input::form('f');
		
		list($category, $file) = explode(':', $file, 2);
		if (!$file) {
			$file = $category;
			$category = NULL;
		}

		//检查 !module/path 格式
		if (preg_match ('/^\!(.*?)(?:\/(.+))?$/', $file, $matches)) {
			$category = $matches[1] ?: NULL;
			$file = $matches[2];
		}

		//PUBLIC_BASE
		$path = Core::file_exists(PUBLIC_BASE.$file, $category);
		if (!$path) {
			$path = ROOT_PATH.PUBLIC_BASE.$file;
        }

        if (is_file($path)) {
			Downloader::download($path);
			exit;
		}

	}

}

