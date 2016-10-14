<?php
abstract class OAuth_Client {

	public $callback;
	// callback 应该是一个固定值, 在某些 provider 处注册时是要登记 callback 的


	function support_rpc() {
		return FALSE;

	}

	// PENDING 该方法是否要加 refer 参数?(xiaopei.li@2013-01-31)
	function authorization_request() {
		$authorization_request_url = $this->get_authorization_request_url();
		URI::redirect($authorization_request_url);
	}

	// abstract
	function get_authorization_request_url() {
	}

	// abstract
	function authorization_grant($form) {
	}

	// abstract
	function get_token( $info = NULL ) {
	}

	/*
	  TODO

	  单点登陆! 无论什么 URI, 只要有 ?oauth=lims2 就尝试按 lims2 OAuth 登陆
	*/

	static function factory($server) {

		$provider_confs = Config::get('oauth.providers');

		if (!isset($provider_confs[$server])) {
			// 配置中无此 oauth server
			return FALSE;
		}

		$provider_conf = $provider_confs[$server];
		$client_class = 'OAuth_Client_' . $provider_conf['client_class']; // TODO

		if (!class_exists($client_class)) {
			// 无此 client class
			return FALSE;
		}

		// $client = O($client_class, $provider_conf);
		$client = new $client_class($provider_conf);

		$client->callback = URI::url('!oauth/consumer/authorization_grant',"server=$server");
		// 由于有 oauth 1/2 及同版本 oauth 不同 provider 配置也可能不同,
		// 所以, factory 抽象部分就到这儿
		return $client;
	}


	static function get_oauth_login_links() {
		$oauth_login_links = array();

		$providers = (array)Config::get('oauth.providers');

		foreach ( $providers as $client_key => $client_opts ) {
            if ($client_opts['hidden']) continue;

			$client_class = 'Oauth_Client_' . $client_opts['client_class'];

			if ( class_exists($client_class) ) {

				$login_url = URI::url('!oauth/consumer/request_login',"server=$client_key");

				$client_title = $client_opts['title'] ? : $client_key;
				$oauth_login_links[$client_key] = array(
					'title' => HT("$client_title"),
					'link' => $login_url,
					);
			}

		}

		return $oauth_login_links;
	}

	static function fetch_error_401($e, $provider) {
		$provider_confs = Config::get('oauth.providers');
		$provider_conf = $provider_confs[$provider];

		// 每个调用 oauth api 的地方, 都应该考虑调用成功和失败的情况
		$_SESSION['oauth_refer'] = URI::url(NULL, array('oauth_failed'=>'true','server'=>$provider)); // TODO: 调成类似 form_token 的机制, token 传给 uri
		Site::message(Site::MESSAGE_ERROR, I18N::T('oauth', '您在 %provider 的 OAuth 授权已过期, 请重新授权: %auth_link', array(
													  '%provider' => $provider_conf['title'],
													  '%auth_link' => URI::anchor(
														  '!oauth/consumer/authorization_request?server=' . $provider,
														  '去授权',
														  'class="blue"'
														  ),
													  )));
	}

	static function delete_oauth_user($e, $user) {
		Q("oauth_user[user=$user]")->delete_all();
	}
}
