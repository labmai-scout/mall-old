<?php
$requirements = array(
	'oauth2_client/Client',
	'oauth2_client/GrantType/IGrantType',
	'oauth2_client/GrantType/AuthorizationCode',
	'oauth2_client/GrantType/ClientCredentials',
	'oauth2_client/GrantType/Password',
	'oauth2_client/GrantType/RefreshToken',
	);
foreach ($requirements as $requirement) {
	Core::load(THIRD_BASE, $requirement, '*');
}


// TODO rename to lims2_oauth2
class OAuth_Client_OAuth2 extends OAuth_Client {

	function __construct( $conf ) {

		$this->provider_key = $conf['provider'];
		$this->client_id = $conf['key'];
		$this->client_secret = $conf['secret'];
		$this->authorization_endpoint = $conf['auth_url'];
		$this->token_endpoint = $conf['token_url'];

		$this->client = new OAuth2\Client($this->client_id, $this->client_secret);
	}

	function get_authorization_request_url() {
		$state = uniqid( 'oauth2_', TRUE );
		$_SESSION['oauth2_state'] = $state;

		// Log::add("@oauth2 req authorization url: $state", 'oauth');

		$extra_parameters = array(
			'state' => $state,
			);
		$code_url = $this->client->getAuthenticationUrl(
			$this->authorization_endpoint,
			$this->callback,
			$extra_parameters);

		// Log::add("@oauth2 got authorization url: $code_url", 'oauth');

		return $code_url;
	}

	function authorization_grant( $form ) {

		// OAuth_Client::log('@authorization_grant');


		$state = $_SESSION['oauth2_state'];
		unset($_SESSION['oauth2_state']);

		// OAuth_Client::log('local state: ' . $state);
		// OAuth_Client::log('form  state: ' . $form['state']);

		if ($state != $form['state']) {
			return FALSE;
		}

		if ('access_denied' == $form['error']) {
			Site::message(Site::MESSAGE_ERROR, I18N::HT('oauth', '授权被拒绝!'));
			return FALSE;
		}

		$code = $form['code'];

		$keys = array();
		$keys['code'] = $code;
		$keys['redirect_uri'] = $this->callback;
		try {
			// OAuth_Client::log('code: ' . $code);
			// OAuth_Client::log('redirect_uri: ' . $this->callback);
			$response = $this->client->getAccessToken($this->token_endpoint, 'authorization_code', $keys );

			// OAuth_Client::log(print_r($response, TRUE));

			$info = $response['result'];

			$access_token = $info['access_token'];

			// die( '<h1>Got Access Token: ' . $access_token . '</h1>');

			$_SESSION['oauth2_token'] = $info;
			return TRUE;
		}
		catch (OAuthException $e) {
			URI::redirect('error/401');
		}

		// return $some_oauth_user;

	}

	function set_token( $info = NULL ) {

		$access_token = $this->get_token($info);

		$this->client->setAccessToken($access_token);

	}

	// access token 在 OAuth 流程中应该是 private, OAuth 以外不可获得的,
	// 但由于有不希望 RPC 被 OAuth 限制的需求, 开放该方法
	function get_token( $info = NULL ) {
		if (!$info) {

			if ($_SESSION['oauth2_token']) {
				$info = $_SESSION['oauth2_token'];
			}
			else {
				// 未授权
				$_SESSION['oauth_refer'] = URI::url(null, "server=$this->provider_key");
				URI::redirect('!oauth/consumer/authorization_request?server=' . $this->provider_key);
			}

		}

		if (is_string($info)) {
			$info = json_decode($info, TRUE);
		}

		$access_token = $info['access_token'];

		return $access_token;
	}

}
