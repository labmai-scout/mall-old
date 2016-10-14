<?php

class Site {

	static function forget_login() {
		$db = Database::factory();
		$db->query('DELETE FROM `_remember_login` WHERE id = "%s"', session_id());
	}

	static function check_remember_login() {

		if ($_SESSION['remember_login.checked']) return;
		$_SESSION['remember_login.checked'] = TRUE;

		$db = Database::factory();

		$uid = $db->value('SELECT uid FROM `_remember_login` WHERE id="%s"', session_id());
		$user = O('user', $uid);
		if ($user->id) {
			Auth::login($user->token);
			Site::remember_login($user);

			Log::add(sprintf('用户%s[%d]登入成功', $user->name, $user->id), 'logon');
		}

	}

	static function remember_login($user) {

		$now = time();

		//cookie有效期增加到30天
		setcookie(session_name(), session_id(), $now + 2592000, Config::get('system.session_path'), Config::get('system.session_domain'));

		//记录login状态
		$db = Database::factory();
		$db->prepare_table('_remember_login', array(
			'fields' => array(
				'id'=>array('type'=>'char(40)', 'null'=>FALSE, 'default'=>''),
				'uid'=>array('type'=>'bigint', 'null'=>FALSE, 'default'=>0),
				'mtime'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0),
			),
			'indexes' => array(
				'primary'=>array('type'=>'primary', 'fields'=>array('id')),
				'mtime'=>array('fields'=>array('mtime')),
			)
		));

		$exp_time = $now - 2592000;
		$db->query('DELETE FROM `_remember_login` WHERE mtime < %d', $exp_time);

		$db->query('REPLACE INTO `_remember_login` (id, uid, mtime) VALUES ("%s", %d, %d)', session_id(), $user->id, $now);
	}

	const MESSAGE_NORMAL = 'normal';
	const MESSAGE_WARNING = 'warning';
	const MESSAGE_ERROR = 'error';

	static $messages = array();
	static $enable_message = TRUE;

	static function message($type, $text) {
		if (self::$enable_message && !(self::$messages[$type] && in_array($text, self::$messages[$type]))) {
			self::$messages[$type][] = $text;
		}
	}

	static function messages($type) {
		return array_unique(self::$messages[$type]);
	}

	static function enable_message($status = TRUE) {
		self::$enable_message = $status;
	}

	private static $cached = array();
	static function get($name, $default=NULL, $tag=NULL) {
		static $tags, $last_user;

		$db = Database::factory();

		if ($tag!='@' && $name != '@TAG' && 0 != strncmp($name, 'tag.', 4)) {

			if (isset(self::$cached['@TAG']) && !defined('CLI_MODE')) {
				$tagged = (array) self::$cached['@TAG'];
			}
			else {
				$ret = $db->value('SELECT `val` FROM `_config` WHERE `key`="@TAG"');
				$tagged = (array) @unserialize($ret);
			}

			if ($tag) {
				$val = $tagged[$tag][$name];
				if ($val !== NULL) return $val;
			}

			$user = L('ME');
			if ($last_user->id != $user->id || $tags === NULL) {
				$group_root = Tag_Model::root('group');
				$last_user = $user;

				$tags = Q("$user tag[root=$group_root]")->to_assoc('id', 'name');

				$lab = $user->lab;
				if ($lab->id) {
					$tags += Q("$lab tag[root=$group_root]")->to_assoc('id', 'name');
				}
			}

			foreach ((array) $tags as $tag) {
				$val = $tagged[$tag][$name];
				if ($val !== NULL) return $val;
			}

		}

		if (isset(self::$cached[$name]) && !defined('CLI_MODE')) {
			return self::$cached[$name];
		}

		$ret = $db->value('SELECT `val` FROM `_config` WHERE `key`="%s"', $name);
		if ($ret !== NULL) return self::$cached[$name] = unserialize($ret);

		return Config::get($name, $default);
	}

	static function set($name, $value=NULL, $tag=NULL) {

		$db = Database::factory();
		if (!$db->table_exists('_config')) {
			$fields=array(
				'key'=>array('type'=>'varchar(150)', 'null'=>TRUE, 'default'=>NULL),
				'val'=>array('type'=>'text', 'null'=>TRUE, 'default'=>NULL),
			);
			$indexes=array(
				'primary'=>array('type'=>'primary', 'fields'=>array('key')),
			);
			$db->create_table(
				'_config',
				$fields, $indexes,
				Config::get('lab.config_engine')
			);
		}

		if ($tag) {
			$ret = $db->value('SELECT `val` FROM `_config` WHERE `key`="@TAG"');
			$tagged = (array) @unserialize($ret);

			if ($tag == '*') {
				foreach ($tagged as & $t) {
					if($value === NULL) {
						unset($t[$name]);
						if (!$t) unset($t);
					}
					else {
						$t[$name]=$value;
					}
				}
			}
			else {
				$tagged[$tag][$name] = $value;
			}

			self::$cached['@TAG'] = $tagged;
			$db->query('REPLACE INTO `_config` (`key`, `val`) VALUES ("@TAG", "%s")', serialize($tagged));
		}
		else {
			if ($value === NULL) {
				unset(self::$cached[$name]);
				$db->query('DELETE FROM `_config` WHERE `key`="%s"', $name);
			}
			else {
				self::$cached[$name] = $value;
				$db->query('REPLACE INTO `_config` (`key`, `val`) VALUES ("%s", "%s")', $name, serialize($value));
			}
		}


	}

	static function save_abbr($e, $object, $new_data) {
		if ($new_data['name'] && class_exists('PinYin')) {
			$schema = ORM_Model::schema($object);
			if (isset($schema['fields']['name_abbr']))
				$object->name_abbr = PinYin::code($new_data['name']);
		}
	}

	static function reset_form() {
		Session::set_url_specific('form', NULL);
	}

	static function form($process=NULL) {
		$form = Input::form();
		$form_count = count($_POST);
		$old_form = Session::get_url_specific('form', array());

		if ($form['reset_search']) {
			//$form = array();
			self::reset_form();
			URI::redirect('');
		}

		if ($form['reset_field']) {
			$fields = explode(',', $form['reset_field']);
			foreach ($fields as $field) {
				unset($old_form[$field]);
			}
		}

		if ($process) {
			$process($old_form, $form);
		}

		if (!$old_form['sort']) {
			$form['sort'] = $form['sort'] ?: '';
			$form['sort_asc'] = $form['sort_asc'] ?: FALSE;
		}
		elseif ($form['sort'] && $form['sort'] == $old_form['sort']) {
			$form['sort_asc'] = !$old_form['sort_asc'];
		}

		$form += $old_form;
		Session::set_url_specific('form', $form);

		if ($form_count >0) {
			URI::redirect('');
		}

		return Session::get_url_specific('form', array());
	}

	static function store_form($form) {
		Session::set_url_specific('form', $form);
	}

	static function pagination(& $objects, $start, $per_page, $url=NULL) {
		$start = $start - ($start % $per_page);

		if ($objects instanceof Search_Iterator) {
			// sphinx
			$max_count = Config::get('database.sphinx.max_matches') ? : 1000;
			$total_found = $objects->total_count();

			if ($total_found < $max_count) {
				$total_count = $total_found;
			}
			else {
				$total_count = $max_count;
			}
		}
		else {
			// if ($objects instanceof Q)
			// Q
			$total_count = $total_found = $objects->total_count();
		}

		if($start > 0) {
			$last = floor($total_count / $per_page) * $per_page;
			if ($last == $total_count) $last = max(0, $last - $per_page);
			if ($start > $last) {
				$start = $last;
			}
			$objects = $objects->limit($start, $per_page);
		} else {
			$objects = $objects->limit($per_page);
		}

		$pagination = Widget::factory('pagination');
		$pagination->set(array(
			'start' => $start,
			'per_page' => $per_page,
			'total' => $total_count,
			'total_found' => $total_found,
			'url' => $url
		));

		return $pagination;
	}

}
