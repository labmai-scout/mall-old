<?php

function rb_split_ex($str, $mode=null) {
    if (!defined('__RB_SIMPLE_MODE__')) {
        define('__RB_SIMPLE_MODE__', 1);
    }

    if (function_exists('friso_split')) {
        $splitKeywords = friso_split($str, ['mode' => RB_CMODE]);
        $participle = array_map(function($v){return $v['word'];}, $splitKeywords);
    } elseif (defined('RB_CMODE')) { // robbe 版本 = 1.6
        $splitKeywords = rb_split($str, ['mode' => RB_CMODE]);
        $participle = array_map(function($v){return $v['word'];}, $splitKeywords);
    } elseif (defined('__RB_SIMPLE_MODE__')) { // robbe 版本 < 1.6
        $participle = rb_split($str, __RB_SIMPLE_MODE__);
    }
    return $participle;
}

class Application {

	static function load_globals($path) {
		$globals = $path.'globals'.EXT;
		if (file_exists($globals)) @include($globals);
	}

	static function setup() {
		define('SITE_ID', $_SERVER['SITE_ID']?:'default');
		define('SITE_PATH', ROOT_PATH.'sites/'.SITE_ID.'/');
		if (!@is_dir(SITE_PATH)) {
			header('Status: 404 Not Found');
			die;
		}

		Core::include_modules(SITE_PATH);
		Core::include_path('application', SITE_PATH);

		Cache::$CACHE_PREFIX = hash('md4', SITE_PATH).':';

		if (preg_match('/^!(.+)$/', Input::arg(0), $parts)) {
			$mname = mb_convert_case($parts[1], MB_CASE_LOWER);
			if (!in_array($mname, array('system', 'application'))) define('MODULE_ID', $mname);
			array_shift(Input::args());
		}

		Config::load(SITE_PATH, 'site');

		$mods = (array) Config::get('site.modules');
		// 如果没有 site.modules 配置, 就加载所有模块 (xiaopei.li@2012-08-29)
		if (count($mods) == 0) {
			foreach (array_unique(array_values(Core::module_paths())) as $name) {
				$mods[$name] = TRUE;
			}
		}
		Core::set_legal_modules($mods);

		self::load_globals(SITE_PATH);

		Core::reload_config();
		Config::set('site.modules', $mods);
		Config::set('database.default', SITE_ID);

		if (defined('MODULE_ID')) {
			Core::include_path(MODULE_ID, Core::module_path(MODULE_ID));
			Core::include_path(MODULE_ID, SITE_PATH.MODULE_BASE.MODULE_ID.'/');
		}

		Config::set('system.log_path', SITE_PATH.'logs/%ident.log');
		Config::set('system.tmp_dir', sys_get_temp_dir().'/mall/'.SITE_ID.'/');

		// 为session_name添加LAB_ID后缀
		Config::set('system.session_name', 'session_mall_'.SITE_ID);

		// 设置locale

		// 设置数据库
		ORM_Model::setup();
		Q::setup();

        date_default_timezone_set(Config::get('system.timezone') ?: 'Asia/Shanghai');

		Session::setup();
		Properties::setup();

		$perms = array();
		$names = array('application') + array_keys((array) Config::get('site.modules'));
		foreach ($names as $name) {
			foreach ((array) Config::get("perms.$name") as $perm => $default) {
				if ($perm[0] == '#' || $perm[0] == '-') continue;
				$perms[$perm] = $default;
			}
		}

		Cache::L('PERMS', $perms);

		Event::bind('system.ready', 'Application::ready');

		Core::bind_events();

        $form = Input::form();

        if ($form['user-token']) {
            try {
                $token = $form['user-token'];
                $auth = O('user_auth', array('access_token'=> $token));

                if (!$auth->id) throw new Error_Exception;

                if ($auth->expire_time < Date::time()) throw new Error_Exception;

                $user = $auth->user;

                //用户登录
                Auth::login($user->token);

                //实际传递oid为order_item_id
                if ($form['oid']) {
                    $item = O('order_item', $form['oid']);
                    if ($item->id && $user->is_allowed_to('以买方查看', $item->order)) URI::redirect($item->order->url());
                }
            }
            catch(Error_Exception $e) {
                URI::redirect('login');
            }
        }
	}

	static function ready() {
		if (!defined('CLI_MODE')) {

			$form = Input::form();
			if ($form['oauth-sso'] && $oauth_provider = $form['oauth-sso']) {
				if (Auth::logged_in()) {
					Auth::logout();
				}
                $_SESSION['oauth_sso_referer'] = URI::url();
                URI::redirect("!oauth/consumer/sso?server=$oauth_provider");
            }

            if ($form['gapper-token']) {
				Gapper::login_by_token($form['gapper-token']);
	        }

			$locale = Input::get('locale');
			$locales = (array)Config::get('system.locales');
			if (isset($locale) && isset($locales[$locale]) && $_SESSION['system.locale'] !== $locale) {
				$_SESSION['system.locale'] = $locale;
			}

			if (!Auth::logged_in()) {
				Site::check_remember_login();
			}

			if (Auth::logged_in()) {
				$token = Auth::token();

				$me = O('user', array('token'=>$token));
				Cache::L('ME', $me);
				if ($me->id) {

					$locale = Properties::factory($me)->locale;
					if ($locale) {
						Config::set('system.locale', $locale);
					}
				}
			}
			else {
				Cache::L('ME', O('user'));		// 空用户
			}

			if (isset($_SESSION['system.locale'])) {
				Config::set('system.locale', $_SESSION['system.locale']);
			}

		}
        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) require_once($autoload);

		I18N::setup();

		$roles = Q('role:sort(weight A)');
		$role_num = $roles->length();
		$role_set = count($roles->to_assoc('weight', 'id'));

		if ($role_num != $role_set) {
			$first_role = $roles->current();
			$weight = $first_role->weight;
			foreach ($roles as $role) {
				if ($first_role->id != $role->id) {
					$weight ++;
					if ((int)$role->weight != $weight){
						$role->weight = $weight;
						$role->save();
					}
				}

			}
		}

		Cache::L('ROLES', $roles);

		$default_roles = Config::get('roles.default_roles');
		/* 对默认角色按weight排序 */
		uasort($default_roles, function($a, $b) {
				return $a['weight'] < $b['weight'];
			});

		foreach ($default_roles as $role_id => $role_description) {
			$roles->prepend(array('id' => $role_id, 'name' => T($role_description['name']), 'weight' => $role_description['weight']));
		}

	}

}
