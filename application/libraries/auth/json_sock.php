<?php
/*
sample auth config:

$config['backends']['remote'] = array(
	'title'=>'remote',
	'handler'=>'json_sock',
	'server'=>'gin.genee.cn',
	'port'=>2429,
	'readonly' => TRUE,
	'allow_create' => FALSE,
);

*/
/*
TODO (after all, done is better than perfect) (xiaopei.li@2012-02-09)
需评估: 除json格式/sock方法外是否还有别的通信方式.
若有, 此类还可继续抽象为 Auth_Sesame (芝麻开门), 同时写若干 client 类,
Auth_Sesame 中使用策略模式, $client->query()
*/
class Auth_JSON_Sock implements Auth_Handler {

	const ENDL = "\n";
	const MAX_BUF_SIZE = 1048576;
	const IO_TIMEOUT = 5;

	function __construct(array $opt) {
		$this->server = $opt['server'];
		$this->port = $opt['port'];
		$this->timeout = $opt['timeout'] ? : 15; // just connecting timeout, you should stream_set_timeout for IO timeout
	}

	/*
	  input:
	  command => 'verify',
	  token => 'token',
	  password => 'password'

	  return:
	  'result => 1/0
	 */
	function verify($token, $password) {
		$ret = FALSE;

		$ntoken = strtr($token, ':', '|'); // 参考Auth_RPC, 被用于做嵌套替换(xiaopei.li@2012-02-10)

		$request = array('command' => 'verify',
						 'token' => $ntoken,
						 'password' => $password);

		if ($this->query($request) &&
			$this->last_message['result']) {
			$ret = TRUE;
		}

		return $ret;
	}

	function change_token($token, $new_token) { return FALSE; }

	function change_password($token, $password) { return FALSE; }

	function add($token, $password) { return FALSE; }

	function remove($token) { return FALSE; }

	/*
	  通用的 query 接口

	  Usage:
	  if ($client->query($args)) {
	    $response = $client->get_last_response();
	  }
	  else {
	    $error = $client->get_last_error();
	  }
	*/
	function query($request) {

		if ($this->timeout) {
			$fp = @fsockopen($this->server, $this->port, $errno, $errstr, $this->timeout);
		}
		else {
			$fp = @fsockopen($this->server, $this->port, $errno, $errstr); // 若没设置timeout, 则fsockopen会用系统的timeout(即ini_get("default_socket_timeout")), 避免timeout为0
		}

		if (!$fp) {
			$this->last_error = "transport error - could not open socket\n";
			return FALSE;
		}

		$raw_request = json_encode($request) . self::ENDL;
		// error_log($raw_request); // uncomment to debug

		if (!fwrite($fp, $raw_request)) {
			$this->last_error = "transport error - could not write\n";
			return FALSE;
		}

		// set IO timeout
		stream_set_timeout($fp, self::IO_TIMEOUT);

		// 一去一回
		$line = fgets($fp, self::MAX_BUF_SIZE);
		if (!$line) {
			$this->last_error = "transport error - read nothing\n";
			return FALSE;
			// 以上判断为严谨考虑, 应该不会导致读了一半就跳出
			// 但 fgets 第二个参数已经设为较大值了, 可能只读一半么?(xiaopei.li@2012-01-06)
		}

		fclose($fp);

		// parse what we've got
		// error_log($line);	// uncomment to debug

		$message = json_decode($line, TRUE);

		// is the message a fault?
		if (!(is_array($message) && count($message))) {
			$this->last_error = "empty reply\n";
			return FALSE;
		}

		if ( isset($message['error']) ) {
			$this->last_error = 'error code: ' . $message['error'];
			return FALSE;
		}

		$this->last_message = $message;

		// query ok
		return TRUE;
	}
}
