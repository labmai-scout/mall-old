<?php
$requirements = array(
	'oauth2_server/src/OAuth2/AuthServer',
	'oauth2_server/src/OAuth2/Exception/OAuth2Exception',
	'oauth2_server/src/OAuth2/Exception/ClientException',
	'oauth2_server/src/OAuth2/Exception/InvalidAccessTokenException',
	'oauth2_server/src/OAuth2/Exception/InvalidGrantTypeException',
	'oauth2_server/src/OAuth2/Grant/GrantTypeInterface',
	'oauth2_server/src/OAuth2/Grant/AuthCode',
	'oauth2_server/src/OAuth2/Grant/ClientCredentials',
	'oauth2_server/src/OAuth2/Grant/Password',
	'oauth2_server/src/OAuth2/Grant/RefreshToken',
	'oauth2_server/src/OAuth2/Util/RequestInterface',
	'oauth2_server/src/OAuth2/Util/RedirectUri',
	'oauth2_server/src/OAuth2/Util/Request',
	'oauth2_server/src/OAuth2/Util/SecureKey',
	);
foreach ($requirements as $requirement) {
	Core::load(THIRD_BASE, $requirement, '*');
}

class OAuth2_Provider {
    const GRANT_TYPE_AUTH_CODE          = 'authorization_code';
    const GRANT_TYPE_PASSWORD           = 'password';
    const GRANT_TYPE_CLIENT_CREDENTIALS = 'client_credentials';
    const GRANT_TYPE_REFRESH_TOKEN      = 'refresh_token';

	function __construct( $grant_type ) {


		// Initiate the auth server with the models
		$this->server = new \OAuth2\AuthServer(new OAuth2_Storage_Client, new OAuth2_Storage_Session, new OAuth2_Storage_Scope);
		// TODO $this->server 要换成 OAuth2_Provider,
		// 现在的参数 (new ...) 都应由 OAuth2_Provider 处理


		switch($grant_type) {
		case self::GRANT_TYPE_AUTH_CODE:
			// Enable support for the authorization code grant
			$this->server->addGrantType(new \OAuth2\Grant\AuthCode());
			break;
		case self::GRANT_TYPE_PASSWORD:
		case self::GRANT_TYPE_CLIENT_CREDENTIALS:
		case self::GRANT_TYPE_REFRESH_TOKEN:
		default:
			die;				// not supported yet
		}

	}

	//  __call() is triggered when invoking **INACCESSIBLE** methods in an object context.
	public function __call($name, $args) {
		if ( method_exists($this->server, $name) ) {
			return call_user_func_array(array($this->server, $name), $args);
		}
		else {
			throw new Exception;
		}
    }

    // __callStatic() is triggered when invoking **INACCESSIBLE** methods in a static context.
	public static function __callStatic($name, $args) {
		if ( method_exists('\OAuth2\AuthServer', $name) ) {
			return call_user_func_array(array('\OAuth2\AuthServer', $name), $args);
		}
    }

}

