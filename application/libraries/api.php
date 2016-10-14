<?php

class API_Exception extends Exception {}

class API {

	private $_debug = FALSE;
	function debug($debug=TRUE) {
		$this->_debug = $debug;
	}

	function dispatch() {
		// 首先解析body中的json格式
		$data = @json_decode(file_get_contents('php://input'), TRUE);
		if (!is_array($data)) {
			$data = $_POST;
			if (!is_array($data['params'])) {
				$data['params'] = (array) @json_decode((string)$data['params']);
			}
		}

		if ($this->_debug) {
			Log::add(@json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'api');
		}

		$JSONRPC2 = ($data['jsonrpc'] == '2.0');
		
		$path = strtolower($data['method']);
		$params = (array) $data['params'];

		try {

			if (!$path) throw new API_Exception("method must not be empty!");

			$path_arr = explode('/', str_replace('::', '/', $path));
			$class = 'api_' . implode('_', $path_arr);

			if (Core::load(LIBRARY_BASE, 'api/'.$path, '*') && class_exists($class, FALSE) && method_exists($class, '_default')) {
				$object = new $class();
				$callback = array($object, '_default');
			}
			else {
				$method = array_pop($path_arr);
				$path = implode('/', $path_arr);
				$class = 'api_' . implode('_', $path_arr);
				if ($method[0] != '_' && count($path_arr) > 0
					&& Core::load(LIBRARY_BASE, 'api/'.$path, '*') && class_exists($class, FALSE) && method_exists($class, $method)
				) {
					$object = new $class();
					$callback = array($object, $method);
				}
			}

			if (!is_callable($callback)) {
				throw new API_Exception("method not exists!");
			}

			if ($this->_debug) {
				$method = $callback[1];
				$params_str = preg_replace('/\[(.*)\]/', '$1', @json_encode($params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
				Log::add( '<<< '.$class.'->'.$method.'('. $params_str.')', 'api');
			}

			$result = call_user_func_array($callback, $params);

			if ($JSONRPC2) {
				$response = array(
					'jsonrpc' => '2.0',
					'result' => $result,
					'id' => $data['id'],
				);
			}
			// 向下兼容
			else {
				$response = array(
					'success' => TRUE,
					'response' => $result,
				);
			}

			if ($this->_debug) {
				Log::add('>>> '.@json_encode($response, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'api');
			}
			
		}
		catch (API_Exception $e) {
			if ($this->_debug) {
				Log::add($e->getMessage(), 'api');
			}

			if ($JSONRPC2) {
				$response = array(
					'jsonrpc' => '2.0',
					'error' => array(
						'code' => $e->getCode(),
						'message' => $e->getMessage(),
						),
					'id' => $data['id'],
				);
			}
			// 向下兼容
			else {
				$response = array(
					'error' => $e->getMessage(),
				);
			}
		}

		while(ob_end_clean());

		echo @json_encode($response, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
		exit;
	}

}

