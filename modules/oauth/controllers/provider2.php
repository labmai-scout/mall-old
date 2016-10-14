<?php

class Provider2_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->server = new OAuth2_Provider( OAuth2_Provider::GRANT_TYPE_AUTH_CODE );
	}

	function index() {
        Log::add('@provider2 index', 'oauth');

		// Tell the auth server to check the required parameters are in the query string
		try {
			$params = $this->server->checkAuthoriseParams();
		}
		catch(Exception $e) {
			$code = $e->getCode();

			switch (OAuth2_Provider::getExceptionType($code)) {
			case 'invalid_client':
				$msg = I18N::T('oauth', '客户端验证失败');
				break;
			// 其他错误见 http://tools.ietf.org/html/rfc6749#section-5.2
			default:
			}

			if ($msg) {
				Site::message(Site::MESSAGE_ERROR, $msg);
			}

			URI::redirect('error/401');
		}

		// Save the verified parameters to the user's session

		if (L('ME')->id) {
			// redirect to authorise if already logined
			$params['user_id'] = L('ME')->id;

			$_SESSION['oauth2_params'] = serialize($params);
			URI::redirect('!oauth/provider2/authorise');
		}

		// or redirect the user to sign-in
		$_SESSION['oauth2_params'] = serialize($params);
		URI::redirect('!oauth/provider2/signin');
	}

	function signin() {
        Log::add('@provider2 signin', 'oauth');

		if ( ! isset($_SESSION['oauth2_params'])) {
			throw new Exception('Missing auth parameters');
		}

		// Get the params from the session
		$params = unserialize($_SESSION['oauth2_params']);


		$form = Form::filter(Input::form());

		if ($form['submit']) {
			try {
				$form->validate('token', 'not_empty', I18N::T('people', '登录帐号不能为空！'))
					->validate('password', 'not_empty', I18N::T('people', '密码不能为空！'));

				if (!$form->no_error) {
					throw new Error_Exception;
				}

				$token_name = trim($form['token']);
				$backend = trim($form['token_backend']);
				$token = Auth::normalize($token_name, $backend);

				$user = O('user', array('token'=>$token));

				if (!$user->id) {
                    Log::add(strtr('[oauth] 用户名%token OAuth 验证失败', array(
                        '%token'=> $token
                    )), 'journal');

					throw new Error_Exception(I18N::T('people', '帐号和密码不匹配, 请您重新输入!'));
				}

				$auth = new Auth($token);
				if ($auth->verify($form['password'])) {
					Auth::login($token);
				}
				else {
                    Log::add(strtr('[oauth] 用户%user_name[%user_id] OAuth 验证失败', array(
                        '%user_name'=> $user->name,
                        '%user_id'=> $user->id
                    )), 'journal');

					throw new Error_Exception(I18N::T('people', '帐号和密码不匹配, 请您重新输入!'));
				}

				// $me = $user;

				// Check the user's credentials
				// if ($_POST['username'] === 'alex' && $_POST['password'] === 'password') {
					// do login


					// Add the user ID to the auth params and forward the user to authorise the client
				$params['user_id'] = $user->id;

				$_SESSION['oauth2_params'] = serialize($params);
					// $app->redirect('/oauth.php/authorise');
				URI::redirect('!oauth/provider2/authorise');
					// }
				// Wrong username/password
				/*
				  else {
				  	$app->redirect('/oauth.php/signin');
				  }
				*/

			}
			catch (Error_Exception $e) {
				$msg = $e->getMessage();
				if ($msg) {
					Site::message(Site::MESSAGE_ERROR, $msg);
				}
			}


		}

		$this->layout = V('oauth:provider/login', array(
									'form' => $form,
									'consumer_title' => $params['client_details']['name'],
									));

	}

	function authorise() {
		// Check the auth params are in the session
		if ( ! isset($_SESSION['oauth2_params'])) {
			throw new Exception('Missing auth parameters');
		}

		$params = unserialize($_SESSION['oauth2_params']);

		// TODO L('ME')
		// Check the user is signed in
		if ( ! isset($params['user_id']) ) {
			// $app->redirect('/oauth.php/signin');
			URI::redirect('!oauth/provider2/signin');
		}

		// 可配置自动授权
		$client_id = $params['client_id'];
		foreach (Config::get('oauth.consumers') as $consumer) {
			if ( $consumer['key'] == $client_id && $consumer['auto_authorise'] ) {

				$authCode = $this->server->newAuthoriseRequest('user', $params['user_id'], $params);

				$redirect_uri = OAuth2\Util\RedirectUri::make($params['redirect_uri'], array(
																  'code' => $authCode,
																  'state'	=> $params['state']
																  ));
				URI::redirect($redirect_uri);
			}
		}

		// 否则需手动授权
		// If the user approves the client then generate an authoriztion code
		if (isset($_POST['approve'])) { // TODO fix this auto authorise

			$authCode = $this->server->newAuthoriseRequest('user', $params['user_id'], $params);

			// Generate the redirect URI
			$redirect_uri = OAuth2\Util\RedirectUri::make($params['redirect_uri'], array(
												   'code' => $authCode,
												   'state'	=> $params['state']
												   ));
			URI::redirect($redirect_uri);
		}
		// The user denied the request so send them back to the client with an error
		elseif (isset($_POST['deny']))
		{
			// echo '<p>The user denied the request and so would be redirected back to the client...</p>';
			$redirect_uri = OAuth2\Util\RedirectUri::make($params['redirect_uri'], array(
												   'error' => 'access_denied',
												   // 'error_message' => $this->server::getExceptionMessage('access_denied'),
												   'error_message' => \OAuth2\AuthServer::getExceptionMessage('access_denied'),
												   'state'	=> $params['state']
												   ));
			URI::redirect($redirect_uri);
		}
		else {
	// GET
	?>

	<h1>Authorise <?php echo $params['client_details']['name']; ?></h1>

	<p>
		The application <strong><?php echo $params['client_details']['name']; ?></strong> would like permission to access your:
	</p>

	<ul>
		<?php foreach ($params['scopes'] as $scope): ?>
			<li>
				<?php echo $scope['name']; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<p>
		<form method="post" style="display:inline">
			<input type="submit" name="approve" id="approve" value="Approve">
		</form>

		<form method="post" style="display:inline">
			<input type="submit" name="deny" id="deny" value="Deny">
		</form>
	</p>

	<?php
		  // TODO 由于此处是自定义 view, 输出完就 die 以防 layout view 污染
		  die;

		  }
	}


	function access_token() {
		// must post
		header('Content-type: application/javascript');

		try {

			// Issue an access token
			$p = $this->server->issueAccessToken();
			echo json_encode($p);
		}

		catch (Exception $e)
		{
			// Show an error message
			echo json_encode(array('error' => $e->getMessage(), 'error_code' => $e->getCode()));
		}

		die; // 由于该 controller << layout controller, 所以输出完 json 后要 die, 以防输出 view

	}

}
