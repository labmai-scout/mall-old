<?php

class Download_Controller extends Layout_Controller {

	function index($file_name='', $file_type='') {
	
		$pre_path = SITE_PATH . 'modules/mall/public/';
		
		$file = $file_name . '.' . $file_type;
		
		$full_path = $pre_path . $file;
		
		Downloader::download($full_path, TRUE);
		
		exit;
		
	}

}