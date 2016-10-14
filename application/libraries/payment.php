<?php

class Payment {

    private $_handler;
    private $_options;

	private $_method;

    function __construct($transfer_statement, $method = NULL, $extra_opts = NULL) {

        if ($method === NULL) {
            $method = Config::get('payment.default_payment');
        }

		$opts = Config::get("payment.$method");

		$opts = (array)$extra_opts + $opts; // 数组相加, 重复键的值以加号前为准(xiaopei.li@2012-09-20)

        assert($opts['handler']);	//handler必须存在

		$this->_method = $method;

        $class = 'Payment_'.ucwords($opts['handler']);

		$this->_transfer_statement = $transfer_statement;

        $this->_debug = $opts['debug'];

        $this->_handler = new $class($opts);

        $this->_options = $opts;
    }

	function get_method() {
		return $this->_method;
	}

	function pay() {

		$ret = $this->_handler->pay($this->_transfer_statement);

		$log = sprintf('用户 %s[%d] 使用 %s 接口申请支付付款单 #%d %s',
					   L('ME')->name, L('ME')->id,
					   $this->_options['handler'],
					   $this->_transfer_statement->id,
					   $ret ? '成功' : '失败');
		Log::add($log, 'transfer');

		return $ret;

	}

	function get_pay_status() {
		if (method_exists($this->_handler, 'get_pay_status')) {
			$ret = $this->_handler->get_pay_status($this->_transfer_statement);

			$log = sprintf('用户 %s[%d] 使用 %s 接口检查付款单 #%d 状态, 状态为 %s',
						   L('ME')->name, L('ME')->id,
						   $this->_options['handler'],
						   $this->_transfer_statement->id,
						   strval($ret));
			Log::add($log, 'transfer');

		}
		
		return $ret;
		// throw new Exception($this->_handler . "don't have 'get_pay_status()' method");
	}

	function get_pending_links() {
		if (method_exists($this->_handler, 'get_pending_links')) {
			$ret =  $this->_handler->get_pending_links($this->_transfer_statement);
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
