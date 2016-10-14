<?php

class OAuth2_RPC_Exception extends Exception {}

class OAuth2_RPC {

	private $_client;
	private $_path;

	function __construct($server, $path=NULL) {

		$client = OAuth_Client::factory($server);
		$this->_client = $client;

		$this->_path = $path;
	}

	function __get($name) {
		return new OAuth2_RPC($this->_client->provider_key, $this->_path ? $this->_path . '/' . $name : $name);
	}

	function __call($method, $params) {
		if ($method === __FUNCTION__) return NULL;

		if ($this->_path) $method = $this->_path . '/' . $method;

		$data = $this->_client->call_api(array('method' => $method, 'params' => $params));

		if ($data['result']['error']) {
			throw new OAuth2_RPC_Exception($data['result']['error']);
		}

		$result = $data['result'];

		return $result;
	}

}
