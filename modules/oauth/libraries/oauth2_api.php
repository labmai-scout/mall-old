<?php

class OAuth2_API_Exception extends Exception {}

class OAuth2_API {

	private $_debug = FALSE;
	function debug($debug=TRUE) {
		$this->_debug = $debug;
	}

	function dispatch() {
		// 首先解析body中的json格式
		$data = Input::form();

		if ($this->_debug) {
			Log::add(@json_encode($data), 'oauth');
		}

		$path = strtolower($data['method']);
		$params = (array) $data['params'];

		try {

			if (!$path) throw new OAuth2_API_Exception('method must not be empty!');

			$path_arr = explode('/', $path);
			$class = 'oauth2_api_' . implode('_', $path_arr);

			if (class_exists($class) && method_exists($class, '_default')) {
				$object = new $class();
				$callback = array($object, '_default');
			}
			else {
				$method = array_pop($path_arr);
				$path = implode('/', $path_arr);
				$class = 'oauth2_api_' . implode('_', $path_arr);
				if ($method[0] != '_' && count($path_arr) > 0
					&& class_exists($class) && method_exists($class, $method)
				) {
					$object = new $class();
					$callback = array($object, $method);
				}
			}

			if (!is_callable($callback)) {
				throw new OAuth2_API_Exception("method not exists!");
			}

			if ($this->_debug) {
				$method = $callback[1];
				$params_str = preg_replace('/\[(.*)\]/', '$1', @json_encode($params));
				Log::add( '<<< '.$class.'->'.$method.'('. $params_str.')', 'oauth');
			}

			
			$result = call_user_func_array($callback, $params);

			$response = array(
				'error' => null,
				'result' => $result,
				);
			

			if ($this->_debug) {
				Log::add('>>> '.@json_encode($response), 'oauth');
			}
			
		}
		catch (OAuth2_API_Exception $e) {
			if ($this->_debug) {
				Log::add($e->getMessage(), 'oauth');
			}

			$response = array(
				'error' => $e->getMessage(),
				);
		}
		
		return $response;
	}
}
