<?php 

class Upgrader {
	
	//定义颜色
	const ANSI_RED = "\033[31m";
	const ANSI_GREEN = "\033[32m";
	const ANSI_RESET = "\033[0m";
	const ANSI_HIGHLIGHT = "\033[1m";
	
	//定义数据库信息
	static $HOST = 'localhost';
	static $USERNAME = 'genee';
	static $PASSWORD = '';
	
	//高亮默认色输出
	public static function echo_title($title='') {
		echo self::ANSI_HIGHLIGHT;
		echo "$title\n";
		echo self::ANSI_RESET;		
	}
	
	// success 输出
	public static function echo_success($text='') {
		echo self::ANSI_GREEN;
		echo "$text\n";
		echo self::ANSI_RESET;
	}
	
	// fail 输出
	public static function echo_fail($text='') {
		echo self::ANSI_RED;
		echo "$text\n";
		echo self::ANSI_RESET;
	}
	
	// separator 输出
	public static function echo_separator() {
		echo "\n".str_repeat('=', 30)."\n";
	}
	
	//不需要升级
	public static function upgrade_none() {
		echo self::echo_fail("数据库已是最新状态，暂不需要升级!");
	}
	
	//升级成功
	public static function upgrade_successful() {
		echo self::echo_separator();
		echo self::echo_success("数据库升级成功!");
	}
	
	//升级失败
	public static function upgrade_failed() {
		echo self::echo_separator();
		echo self::echo_fail("数据库升级失败!");
	}
	
	const MESSAGE_NORMAL = 0;
	const MESSAGE_ERROR = 1;
	const MESSAGE_WARNING = 2;
	const MESSAGE_HIGHLIGHT = 3;
	
	static $message_ansi;
	
	public static function echo_message() {
		$args = func_get_args();
		$type = array_shift($args);
		
		$ansi = self::$message_ansi[$type];
		if ($ansi) {
			echo $ansi;
		}
		call_user_func_array('printf', $args);
		echo self::ANSI_RESET;
		echo "\n";
	}
	
	//保证程序能够从外部直接调用动态的方法
	function __call($method, $params) {
		if ($method == __CLASS__) return;
		
		if ($this->$method instanceof Closure) {
			$func = $this->$method;
			return call_user_func_array($func,$params); 
		}

		return TRUE;	
	}
	
	function run() {

		if (FALSE === $this->check()) {
			self::echo_message(self::MESSAGE_WARNING, "无需进行该升级");
			return;
		}
		
		if (FALSE === $this->backup()) {
			self::echo_message(self::MESSAGE_ERROR, "备份旧数据失败");
			return;
		}

		try {
			if (FALSE === $this->upgrade()) {
				self::echo_message(self::MESSAGE_ERROR, "升级失败");
				throw new Error_Exception;
			}
			
			if (FALSE === $this->verify()) {
				self::echo_message(self::MESSAGE_ERROR, "升级验证失败");
				throw new Error_Exception;
			}
		}
		catch (Error_Exception $e) {
			$this->restore();	// 恢复旧数据
			return;
		}

		// <del>升级成功后的收尾工作</del>
		// 这步无论升级是否成功都会执行(xiaopei.li@2011.08.08)
		$this->post_upgrade();
		
	}
	
}


Upgrader::$message_ansi = array(
	Upgrader::MESSAGE_NORMAL => Upgrader::ANSI_RESET,
	Upgrader::MESSAGE_HIGHLIGHT => Upgrader::ANSI_HIGHLIGHT,
	Upgrader::MESSAGE_ERROR => Upgrader::ANSI_RED.Upgrader::ANSI_HIGHLIGHT,
	Upgrader::MESSAGE_WARNING => Upgrader::ANSI_RED,
);
	
