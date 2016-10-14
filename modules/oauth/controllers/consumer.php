<?php

class Consumer_Controller extends Controller {

	private function _normalize_username($username, $server=null) {
        //如果现有backend存在，不进行过滤
        $backends = Config::get('auth.backends');
        foreach($backends as $backend => $info) {
            $pos = strpos($username, $backend);

            if ($pos && $pos + strlen($backend) == strlen($username)) {
                return $username;
            }
        }

        $hostname = Config::get('rpc.hostname');
        if (strpos($username, $hostname)) {
            return substr($username, 0, strpos($username, $hostname) -1);
        }

        if($server) {
            if (strpos($username, '|')) {
                return $username . '%' . $server;
            }

            return $username . '|' . $server;
        }

        return $username;

	}

	function sso() {

		$server = Input::form('server');
		$client = OAuth_Client::factory($server);

		if (!$client) {
			URI::redirect('error/401');
		}

		$client = OAuth_Client::factory($server);

		if (!$client) {
			URI::redirect('error/401');
		}

		if ($client->support_rpc()) {

			$oauth_rpc = new OAuth2_RPC($server);
			$user_info = $oauth_rpc->user->info($_SESSION['oauth2_token']['access_token']);

		}
		else {
			$user_info = $client->apicall_current_user();
		}

		$username = $this->_normalize_username($user_info['username'], $server);
		$user = O('user', array('token' => $username));
		if ( !$user->id ) {
			URI::redirect("!oauth/consumer/request_login?server=$server");
		}

		Auth::login($username);
		$oauth_sso_referer = $_SESSION['oauth_sso_referer'];
		URI::redirect($oauth_sso_referer ? : '/');
	}

	function authorization_request() {
		$server = Input::form('server');
		$client = OAuth_Client::factory($server);
		if (!$client) {
			URI::redirect('error/401');
		}

		$client->authorization_request();
	}

	function authorization_grant() {

		$server = Input::form('server');

		$client = OAuth_Client::factory($server);

		if (!$client) {
			URI::redirect('error/401');
		}

		// exchange for access token
		$form = Input::form();
		if ($client->authorization_grant($form)) {
			$refer = $_SESSION['oauth_refer'];
            Log::add($refer, 'oauth');

			URI::redirect($refer);
		}
		else {
			URI::redirect('error/401');
		}

	}

	function request_login() {

		$server = Input::form('server');

		if (L('ME')->id) {
			URI::redirect('/');
		}

		$client = OAuth_Client::factory($server);
		if (!$client) {
			URI::redirect('error/401');
		}

		if ($client->support_rpc()) {

			$oauth_rpc = new OAuth2_RPC($server);
			$user_info = $oauth_rpc->user->info($_SESSION['oauth2_token']['access_token']);
		}
		else {
			$user_info = $client->apicall_current_user();
		}

		$username = $this->_normalize_username($user_info['username']);
		$user = O('user', ['token' => $username]);

		if ($user->id) {
			// 登陆
			Auth::login($username);
			//URI::redirect('/');
			//跳转到成功页面，会自动跳转

            URI::redirect($_SESSION['oauth_sso_referer']);
		}
		else {
			list($token_name, $token_backend) = Auth::parse_token($username);

			$auth_backends = Config::get('auth.backends');

			if (!$auth_backends[$token_backend]) {
				// 如果未设置同名 auth backend, 则 401
				// 但以后可做成到此跳转注册本地用户, 注册后新用户绑定 token, 做成这样后, 需同步修改 OAuth_Client::get_oauth_login_links()
				SITE::message(SITE::MESSAGE_ERROR, I18N::T('oauth', '不允许以此 OAuth 验证后台登陆'));
				URI::redirect('error/401');
			}
			// $token = $remote_id . '@' . $server;
			Auth::login($username);

			$backend_opts = $auth_backends[$token_backend];

			if ($backend_opts['auto_signup']) {
				$user = $this->add_user($user_info, $backend_opts['auto_active']);
				// if ($user->id) {
				// 	URI::redirect('/');
				// }
			}

			URI::redirect('/');
		}
	}

	private function add_user( $attrs, $active = FALSE ) {

		$auth_token = Auth::token();

		if (!$auth_token) {
			return FALSE;
		}

		$user = O('user');

		$user->token = $auth_token;

		$keys_should_unset = array(
			'id', 'token',
			);

		foreach ($keys_should_unset as $k) {
			unset($attrs[$k]);
		}

		foreach ($attrs as $k => $v) {
			$user->$k = $v;
		}

		if ($active) {
			$user->atime = Date::time();
		}

		$user->save();

		return $user;

	}
}

class Consumer_AJAX_Controller extends AJAX_Controller {
	function index_oauth_login_click(){
		$href = Input::form('href');
		JS::dialog(V('oauth:oauth_login', array('href'=>$href)), array('width'=>'800px'));
	}
}
