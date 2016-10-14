<?php

class Gapper {

	static function get_RPC() {
		//验证APP是否存在
		$config = Config::get('mall.gapper');

		try {
			$rpc = new RPC($config['api']);
			$client_id = $config['client_id'];
			$client_secret = $config['client_secret'];

			$ret = $rpc->gapper->authorize($config['client_id'], $config['client_secret']);

			//如果 APP 不存在 跳转到
			if (!$ret) {
				return false;
			}

			return $rpc;
		}
		catch(Exception $e) {}
	}

	static function is_gapper_user($user) {
		// 不发送RPC验证是否用户真的在gapper中存在，只是判断backend是gapper
		list(,$backend) = Auth::parse_token($user->token);

		if($backend == 'gapper') return true;
		return false;
	}

	static function link_identity($user, $identity) {
		if(!$user->id || !$identity) return false;

		list($token,$backend) = Auth::parse_token($identity);
		$sources = Config::get('gapper.sources');

		//处理需要link的backends
		if(array_key_exists($backend, $sources)) {
			$source = $sources[$backend];
			try{
				$rpc = self::get_RPC();
				if (!$user->gapper_user) return false;
				return $rpc->gapper->user->linkIdentity((int)$user->gapper_user, $source, $token);
			}
			catch(Exception $e){
				return false;
			}
		}
		else { //如果不需要远程link则在本地生成gapper_fallback_user
			$gapper_fallback_user = O('gapper_fallback_user', ['user'=>$user]);
			$gapper_fallback_user->user = $user;
			$gapper_fallback_user->token = $identity;
			return $gapper_fallback_user->save();
		}
		//如果不处理返回TRUE
		return true;
	}

	static function get_user_by_identity($token) {
		if(!$token) return false;
		list($token,$backend) = Auth::parse_token($token);
		$sources = Config::get('gapper.sources');
		// //处理需要link的backends
		if(array_key_exists($backend, $sources)) {
			$source = $sources[$backend];
			try{
				$rpc = self::get_RPC();
				return $rpc->gapper->user->getUserByIdentity($source, $token);
			}
			catch(Exception $e){
				return false;
			}
		}

		return false;
	}

	static function login_by_token($login_token) {
		try{
			if (!$login_token) throw new Error_Exception;

			$rpc = self::get_RPC();
			$user_info = $rpc->gapper->user->authorizeByToken($login_token);
			if (!$user_info) throw new Error_Exception;

			if (Auth::logged_in()) {
				Auth::logout();
			}
			Event::trigger('before_login_by_token', $user_info);
			$uid = $user_info['id'];
			$user = O('user', ['gapper_user'=>$uid]);
			if ($user->id) {
				Auth::login($user->token);
			}
			else {
				Auth::login($user_info['username']);
			}
		}
		catch(Exception $e){
			URI::redirect('login');
		}
	}

	static function on_user_deleted($e, $user) {
		$gapper_fallback_user = O('gapper_fallback_user', ['user'=>$user]);
		if($gapper_fallback_user->id) {
			$token = $gapper_fallback_user->token;
			$gapper_fallback_user->delete();
			$auth = new Auth($token);
			$auth->remove();
		}
	}

	static function get_login_token($client_id) {
		try{
			if (!$client_id) throw new Error_Exception;

			$me = L('ME');
			$rpc = self::get_RPC();
			return $rpc->gapper->user->getLoginToken($me->token, $client_id);
		}
		catch(Exception $e){}
	}

	static function create_customer($gapper_group=0) {
		$customer = O('customer', ['gapper_group'=>$gapper_group]);
		if (!$customer->id){
			try{
				if (!$gapper_group) throw new Error_Exception;

				$rpc = self::get_RPC();
				$group_info = $rpc->gapper->group->getInfo($gapper_group);

				if (count($group_info)) {
					$owner = O('user', ['token'=>$group_info['creator']]);
					if (!$owner->id) {
						$owner = self::create_user($group_info['creator']);
					}
					$customer->name = $group_info['title'];
					$customer->gapper_group = $group_info['id'];
					$customer->owner = $owner;
					$customer->save();
					$customer->connect($owner, 'member');
				}
			}
			catch(Exception $e){}
		}

		return $customer;
	}

	/**
	 * @param  [string|int] $gapper_user user的username或id
	 */
	static function create_user($gapper_user) {
		$user = O('user');
		try{
			if (!$gapper_user) throw new Error_Exception;

			$rpc = self::get_RPC();
			$user_info = $rpc->gapper->user->getInfo($gapper_user);
			if (count($user_info)) {
				$user->gapper_user = $user_info['id'];
				$user->name = $user_info['name'];
				$user->token = $user_info['username'];
				$user->email = $user_info['email'];
				$user->phone = $user_info['phone'];
				$user->atime = Date::time();
				$user->save();
			}
		}
		catch(Exception $e){}

		return $user;
	}
}
