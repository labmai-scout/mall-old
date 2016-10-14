<?php
class OAuth_Client_Weibo extends OAuth_Client {

	private $provider_key;
	private $o;
	private $c;
	private $access_token;
	private $expires_in;

	function __construct( $confs ) {

		$this->provider_key= $confs['provider'];

		Core::load(THIRD_BASE, 'saetv2.ex.class', '*');
		$o = new SaeTOAuthV2( $confs['key'] , $confs['secret'] );

		$this->o = $o;
	}

	function get_authorization_request_url() {

		// oauth 2 grant authorization

		$state = uniqid( 'weibo_', true);
		$_SESSION['weibo_oauth_state'] = $state;

		$code_url = $this->o->getAuthorizeURL( $this->callback , 'code', $state );

		return $code_url;
	}

	function authorization_grant( $form ) {
		$state = $_SESSION['weibo_oauth_state'];
		unset($_SESSION['weibo_oauth_state']);

		if ($state != $form['state']) {
			return FALSE;
		}

		$code = $form['code'];
		$keys['code'] = $code;
		$keys['redirect_uri'] = $this->callback;
		try {
			$info = $this->o->getAccessToken( 'code', $keys );
			// http://open.weibo.com/wiki/Oauth2/access_token
			/*
			  array(4) {
			  ["access_token"]=>
			  string(32) "2.00PRw4nB9lw6lDd0ceb3a11feb3ETE"
			  ["remind_in"]=>
			  string(9) "157679999"
			  ["expires_in"]=>
			  int(157679999)
			  }
			*/
			$_SESSION['oauth_weibo'] = $info;

			return TRUE;
		}
		catch (OAuthException $e) {
			URI::redirect('error/401');
		}

		// return $some_oauth_user;

	}

	function set_token( $info = NULL ) {
		// 微博目前只提供短寿命的 access_token, 且不提供 refresh_token
		// 延长 expires_in 需在应用控制台提交申请
		if (!$info) {

			if ($_SESSION['oauth_weibo']) {
				$info = $_SESSION['oauth_weibo'];
			}
			else {
				// 未授权
				$_SESSION['oauth_refer'] = URI::url();
				URI::redirect('!oauth/consumer/authorization_request.' . $this->provider_key);
			}

		}

		$this->access_token = $info['access_token'];
		$this->expires_in = $info['expires_in'];

		if (!$this->c) {
			$this->c = new SaeTClientV2( $this->o->client_id, $this->o->client_secret, $this->access_token );
		}

	}

	function apicall_get_username() {
		$this->set_token();


		$uid_get = $this->c->get_uid();

		return $uid_get['uid'];
	}

	function apicall_current_user() {
		$this->set_token();


		$uid_get = $this->c->get_uid();

		$uid = $uid_get['uid'];
		$weibo_user_info = $this->c->show_user_by_id($uid); //根据ID获取用户等基本信息
		// 返回值见: http://open.weibo.com/wiki/2/users/show

		$gender_map = array( // weibo gender 到 lims2 user gender 的对应
			'm' => 0,
			'f' => 1,
			'n' => -1,
			);

		$user_info = array(
			// 'id' => $weibo_user_info['id'],
			// 'token' => $weibo_user_info['id'],
			'username' => $weibo_user_info['id'],
			'name' => $weibo_user_info['name'],
			'gender' => $gender_map[$weibo_user_info['gender']],
			'address' => $weibo_user_info['location'],
			);

		return $user_info;
	}

}
