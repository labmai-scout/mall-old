<?php

class API2_Controller extends Controller {

	private $server;
	private $user;

	function _before_call($method, &$params) {

		parent::_before_call($method, $params);

		$this->server = new OAuth2_Resource_Server();

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

			// $this->user = $user;
			Cache::L('ME', $user);

		}
		// The access token is missing or invalid...
		// catch (\OAuth2\Exception\InvalidAccessTokenException $e) {
		catch (Exception $e) {
			// TODO add header
			// header('Content-Type: application/json');
			// status(403);
			die(json_encode(array('error' => $e->getMessage())));
		}
	}

	function _after_call($method, &$params) {
		parent::_after_call($method, $params);

		header('Content-Type: application/json');
		echo json_encode($this->response);
	}

	function index() {

		$api = new OAuth2_API;
		$result = $api->dispatch();

		$this->response = $result;
	}

}
