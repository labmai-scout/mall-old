<?php
// 此文件 copy 自 **tender 中位于 commit b3843d4 的相同文件 **
// (xiaopei.li@2012-05-22)
interface Notification_Handler {

	static function send($sender, $receivers, $title, $body);

}

class Notification{
	/*
	 * $conf_key 消息模版名字
	 * $receiver 接收者
	 * $params   参数
	 * $sender   发件者
	 */

	static $handler = array();

	static function send($conf_key, $receiver, $params=NULL, $sender=NULL) {

		if (defined('DISABLE_NOTIFICATION')) {
			return;
		}

		if(is_array($conf_key)){
			foreach($conf_key as $key){
				self::send($key, $receiver, $params, $sender);
			}
		}
		else{

			$configs = Site::get($conf_key) ?: Config::get($conf_key);

			if (is_array($configs) ) {

				$send_by = self::get_send_by($configs['send_by']);
                list($title, $body) = Notification::symbol_to_markup(array($configs['title'], $configs['body']), $params);

				$receivers = is_array($receiver) ? $receiver : array($receiver);

				$handlers = (array)Config::get('notification.handlers');
				foreach ( $handlers as $key=>$handler ) {
					if( isset($send_by[ $key ]) ? $send_by[$key] : $send_by['*'] ){

						$filtered_receivers = array();

						foreach ($receivers as $user) {
							$user_id = $user->id;
							$receive_option = "receive.$conf_key.$key.$user_id";

							/*
							error_log("@@@@{$receive_option}: " .
									  Site::get($receive_option));
							*/

							if (Site::get($receive_option, TRUE)) {
								$filtered_receivers[] = $user;
							}
						}

						$handler['class']::send($sender, $filtered_receivers, $title, $body);
					}
				}
			}
		}
	}

	/* 老的数据结构是：
	 * $config['xxxx']['send_by'] = array(
	 *     'message'=> array('通过消息中心发送',1),
	 *     'email'  => array('通过邮件发送',0),
	 * )
	 * 新的数据结构是：
	 * $config['xxxx']['[send_by'] = array(
	 *     '*'       =>FALSE,  //默认的配置
	 *     'message' =>TRUE,
	 *     'email'   =>FALSE,
	 * )
	 * 这个函数就是将老的数据结构转换成新的结构
	 */
	static function transform_send_by($send_types) {
		foreach($send_types as  &$value ) {
			if ( is_array($value) ) {
				$value = $value[1];
			}
		}
		return $send_types;
	}

	static function get_send_by($send_by){
		$defalut_send_by = Config::get('notification.default.send_by');
		$send_by = is_array($send_by) ? $send_by : array($send_by);
		$send_by = self::transform_send_by($send_by );
		$send_by = $send_by + $defalut_send_by;

		return $send_by;
	}

	static function symbol_to_markup($arr_str, $params){
		if(is_array($params)) {
			foreach($arr_str as $k=>$str){
				$arr_str[$k] = strtr($str, $params);
			}
		}
		foreach($arr_str as $k=>$str){
			if (preg_match_all('/\%(\w+)/', $str, $matches, PREG_SET_ORDER)) {
				foreach($matches as $parts){
					if(is_callable(array('Notification', '_token_'.$parts[1]))){
						$arr_str[$k] = preg_replace_callback('/(%'.preg_quote($parts[1]).')/', 'Notification::_token_'.$parts[1], $str);
					}
				}
			}
		}
		return $arr_str;
	}

	// %current_user
	static function _token_current_user($matches) {
		return Markup::encode_Q(L('ME'));
	}

	static function preference_views($conf, $vars=NULL, $module, $use_default=TRUE) {
		if(!$use_default){
			$prefix = $module.':admin/';
		}
		else{
			$prefix = 'application:admin/';
		}

		//生成view
		$output = '';
		foreach($conf as $c) {

			$opt = (array) Site::get($c) + (array) Config::get($c);
			$opt['send_by'] = self::preference_send($opt);
			$opt['type'] = $c;
			$opt['module_name'] = $module;
			$view_name = $opt['#view'] ?: $prefix.'notification';
			if(is_array($vars)) $opt = array_merge($opt, $vars);
			$output .= (string) V($view_name, $opt);
		}

		return $output;
	}

	static function preference_send ($opt) {

		$send_by = self::get_send_by($opt['send_by']);

		$sends = array();
		$handlers = (array)Config::get('notification.handlers');
		foreach( $handlers as $key => $handler ){
			$sends[$key][] = $handler['text'];
			$sends[$key][] = $send_by[$key] ?:$send_by['*'];
		}

		return $sends;
	}

}

