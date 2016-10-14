<?php

class OAuth_Client_OAuth2_MALL extends OAuth_Client_OAuth2 {

	function __construct( $conf ) {
		parent::__construct( $conf );

		$this->api_base_url = $conf['api_url'];
	}

	function support_rpc() {
		return TRUE;
	}
	

	function call_api($post_data) {
		$this->set_token();
		$response = $this->client->fetch($this->api_base_url, $post_data);

		return $response;
	}

	static function logout_oauth_provider($e, $token) {

		list(,$backend) = Auth::parse_token($token);

		$backends = Config::get('auth.backends');
		$backend_options = $backends[$backend];

		if ($backend_options['handler'] == 'oauth'
			&& $backend_options['also_logout_provider'] ) {

			$oauth_confs = Config::get('oauth.providers');
			$oauth_options = $oauth_confs[$backend];

			$provider_baseurl = $oauth_options['site_url'];
			$logout_url = $provider_baseurl . '/logout';

			$_SESSION['logout_oauth_provider'] = $logout_url;
		}
	}	
}

