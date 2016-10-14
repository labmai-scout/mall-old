<?php

class Account {

    private $_handler;
    private $_options;

	private $_method;

    function __construct($billing_statement, $method = NULL, $extra_opts = NULL) {

        if ($method === NULL) {
            $method = Config::get('account.default_account');
        }

		$opts = Config::get("account.$method");
		$opts = (array)$extra_opts + $opts;

        assert($opts['handler']);	//handler必须存在

		$this->_method = $method;

        $class = 'Account_'.ucwords($opts['handler']);

		$this->_billing_statement = $billing_statement;

        $this->_debug = $opts['debug'];

        $this->_handler = new $class($opts);

        $this->_options = $opts;
    }

	function get_method() {
		return $this->_method;
	}

	function approve() {

		$ret = $this->_handler->approve($this->_billing_statement);

		$log = sprintf('用户 %s[%d] 使用 %s 接口批准结算单 #%d %s',
					   L('ME')->name, L('ME')->id,
					   $this->_options['handler'],
					   $this->_billing_statement->id,
					   $ret ? '成功' : '失败');
		Log::add($log, 'account');

		return $ret;

	}

	function get_pending_links() {
		if (method_exists($this->_handler, 'get_pending_links')) {
			$ret =  $this->_handler->get_pending_links($this->_billing_statement);
		}
		return $ret;
	}

    function __call($method, $params) {

        if ($method == __FUNCTION__) return;

        return $this->_handler->__call($method, $params);
    }

    function json_encode($data) {
        if (!$this->_handler) return FALSE;

        if (!is_array($data)) $data = array($data);

        return $this->_handler->json_encode($data);
    }

    function json_decode($str) {
        if (!$this->_handler) return FALSE;

        return $this->_handler->json_decode($str);
    }

    function __get($key) {
        if (!isset($this->$key)) {
            return $this->_handler->$key;
        }
    }
}
