<?php

class JS extends _JS {

	static function redirect($url='') {
		if (Site::$messages) {
			$_SESSION['system.unlisted_messages'] = Site::$messages;
		}
		parent::redirect($url);
	}
	
	static function refresh($selector='') {
		if (Site::$messages) {
			$_SESSION['system.unlisted_messages'] = Site::$messages;
		}
		parent::refresh($selector);
	}
}
