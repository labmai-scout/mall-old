<?php

class URI extends _URI {
	
	static function redirect($url='', $query=NULL) {
		if (Site::$messages) {
			$_SESSION['system.unlisted_messages'] = Site::$messages;
		}
		$_SESSION['HTTP_REFERER'] = self::url();
		parent::redirect($url, $query);
	}

}
