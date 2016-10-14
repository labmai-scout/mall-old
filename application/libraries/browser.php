<?php

class Browser {

	private static $name_pattern = array(
		'firefox' => '/\bFirefox\b/',
		'chrome' => '/\bChrome|chromeframe\b/',
		'safari' => '/\bSafari\b/',
		'ie' => '/\bMSIE\b/',
		'opera' => '/\bOpera\b/',
	);

	private static $version_pattern = array(
		'firefox' => '/\bFirefox\/(\d+\.\d+)(?:\.([\d.]+))?/',
		'chrome' => '/\bChrome\/(\d+\.\d+)(?:\.([\d.]+))?/',
		'safari' => '/\bVersion\/(\d+\.\d+)(?:\.([\d.]+))?/',
		'ie' => '/\bMSIE (\d+\.\d+)(?:\.([\d.]+))?/',
		'opera' => '/\bOpera\/(\d+\.\d+)(?:\.([\d.]+))?/',
	);
	
	private static $_version;
	private static $_revision;
	private static $_name;
	
	private static function extract() {
		$ua = $_SERVER['HTTP_USER_AGENT'];
		foreach (self::$name_pattern as $name => $pattern) {
			if (preg_match($pattern, $ua)) {
				self::$_name = $name;
				preg_match(self::$version_pattern[$name], $ua, $parts);
				self::$_version = $parts[1];
				self::$_revision = $parts[2];
				return;
			}
		}

		self::$_name = 'unknown';
	}
	
	static function reset() {
		self::$_name = null;
		self::$_version = null;
		self::$_revision = null;
	}
	
	static function name() {
		if (!isset(self::$_name)) self::extract();
		return self::$_name;
	}
	
	static function version() {
		if (!isset(self::$_version)) self::extract();
		return self::$_version;
	}

	static function revision() {
		if (!isset(self::$_revision)) self::extract();
		return self::$_revision;
	}
	
	static function supported() {
		$name = self::name();
		$supported = Config::get('system.supported_browsers');
		$version = self::version();
		if (isset($supported[$name]) && (!$version || $version >= $supported[$name])) {
			return TRUE;
		}
		return FALSE;
	}

}
