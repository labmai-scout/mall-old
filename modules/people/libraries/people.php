<?php
class People {

	static function accessible_controller($e, $controller, $method, $params) {
		if ($controller instanceof Layout_Controller) {
			$me = L('ME');
			// 没有激活的用户表示尚未注册成功 因此需要转到注册页面
			$need_signup = (!$me->id && Auth::logged_in()) || ($me->id && !$me->is_active());
			if ( $need_signup && Input::arg(0) !== 'logout') {
				if (!defined('MODULE_ID') || MODULE_ID !== 'people' || Input::arg(0) !== 'signup') {
					Site::message(Site::MESSAGE_ERROR, HT('您的账号未激活, 请补充个人信息或联系管理员!'));
					URI::redirect('!people/signup');
				}
			}
            $path = (defined('MODULE_ID') ? '!'.MODULE_ID.'/' :'')
                        .Config::get('system.controller_path')
                        .'/'
                        .Config::get('system.controller_method');
            if ($me->must_change_password && $me->is_active() &&  $me->id && $path != '!people/password/index' && Input::arg(0) !== 'logout') {
                URI::redirect('!people/password');
            }
		}
	}

	static function people_ACL($e, $user, $action, $object, $options) {

		$token = $user->token;
		list($token, $backend) = explode('|', $token);

		if (in_array($token, (array)Config::get('mall.admin'))) {
			$e->return_value = TRUE;
			return FALSE;
		}
		
		if ($user->access('管理成员')) {
			$e->return_value = TRUE;
			return FALSE;
		}

		return FALSE;
	}

}
