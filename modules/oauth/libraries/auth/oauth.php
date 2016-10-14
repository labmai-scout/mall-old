<?php
class Auth_OAuth implements Auth_Handler {

	function __construct(array $opt) { }

	function verify($token, $password) {
		Site::message(Site::MESSAGE_ERROR, HT('请使用登陆链接登陆!'));
		return FALSE;
	}

	function change_token($token, $new_token) { return FALSE; }

	function change_password($token, $password) { return FALSE; }

	function add($token, $password) { return FALSE; }

	function remove($token) { return FALSE; }

}
