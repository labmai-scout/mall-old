<?php
class Auth extends _Auth {
	static function login($token) {
		parent::login($token);
	}
	
	static function logout() {
		Site::set('site.customize_user_info', NULL);
		parent::logout();
	}
}
