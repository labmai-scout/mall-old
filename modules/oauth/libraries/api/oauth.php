<?php

class API_OAuth {

	function get_username($access_token) {
		if(!$access_token) return;
		$server = new OAuth2_Resource_Server();
		$username = $server->get_username($access_token);
		return $username;
	}
}
