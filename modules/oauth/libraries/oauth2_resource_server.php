<?php
$requirements = array(
	'oauth2_server/src/OAuth2/ResourceServer',
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

class OAuth2_Resource_Server {

	function __construct() {

		$this->server = new \OAuth2\ResourceServer(new OAuth2_Storage_Session, new OAuth2_Storage_Scope);

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
		if ( method_exists('\OAuth2\ResourceServer', $name) ) {
			return call_user_func_array(array('\OAuth2\ResourceServer', $name), $args);
		}
    }

	// 该方法通过伪造 http header, 以达到验证 access_token 的目的
	function get_username($access_token) {

		$_SERVER['HTTP_Authorization'] = base64_encode($access_token);

		try {
			$this->server->isValid();

			// owner_type 可为 user 或 client, 一般为 user,
			// client 是为特殊的授权流程准备;
			// 系统暂只支持 user 类型的 access_token
			if ($this->server->getOwnerType() !== 'user') {
				throw new Exception('系统不支持该类 access token');
			}

			$user_id = $this->server->getOwnerId();
			$user = O('user', $user_id);
			if (!$user->id) {
				throw new Exception('用户不存在');
			}

			return $user->token;

		}
		catch (Exception $e) {
			return FALSE;
		}

	}

}