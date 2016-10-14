<?php
// references lims2 develop branch commit 025f164(xiaopei.li@2012-07-31)

class Auth_RPC implements Auth_Handler {
	private $_rpc;
	private $_backend;

    function __construct(array $opt){
		if ($opt['remote_backend']) {
			$this->_remote_backend = $opt['remote_backend'];
		}

		$url = $opt['rpc.url'];

		$this->_rpc = new RPC($url);
		$this->_backend = $opt['backend'];
    }
    //验证令牌/密码
    function verify($token, $password) {
		// $ntoken = strtr($token, ':', '|');  // genee:ids.nankai.edu.cn : 被用于做嵌套替换
		$ntoken = preg_replace('/[\|:].*$/', '', $token);


		if ($this->_remote_backend) {
			$ntoken = $token . '|' . $this->_remote_backend;
		}

		$key = $this->_rpc->auth->verify($ntoken, $password);

		if ($key) {
			$_SESSION['#RPC_TOKEN_KEY'][$this->_backend][$token] = $key;
			return TRUE;
		}

		return FALSE;
    }
    //设置令牌
    function change_token($token, $new_token) {
        //安全问题 禁用
        return FALSE;
    }
    //设置密码
    function change_password($token, $password) {
        //安全问题 禁用
        return FALSE;
    }
    //添加令牌/密码对
    function add($token, $password) {
        //安全问题 禁用
        return FALSE;
    }
    //删除令牌/密码对
    function remove($token) {
        //安全问题 禁用
        return FALSE;

	}

	static function get_user_info($token) {
        list($token, $backend) = Auth::parse_token($token); //把backend (一般是RPC)与token剥离
		$key = $_SESSION['#RPC_TOKEN_KEY'][$backend][$token];
		$opts = (array) Config::get('auth.backends');
		if ($opts[$backend]['handler'] == 'rpc') {
			$rpc = new RPC($opts[$backend]['rpc.url']);
			return $rpc->auth->get_user_info($key);
		}

		return array();
	}

}


