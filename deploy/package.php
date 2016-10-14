#!/usr/bin/env php
<?php

require('./base.php');
// deploy/base 中定义了一些通用方法

class Package extends Base {
	public $program_path;

	function __construct($site_id, $to, $is_test = FALSE) {
		$this->mall_root = '..';	// TODO 根目录应用更严谨的方式指定(xiaopei.li@2011.12.04)

		$this->site_id = $site_id;	// SITE_ID

		$this->site_path = "{$this->mall_root}/sites/{$site_id}"; // site 目录
		$this->to = $to;			// 打包后放在哪儿

		$this->encode = !$is_test;
	}

	function make() {
		$modules = $this->check();
		$this->init_env();
		$this->prepare_files($modules);
		$this->zip();
	}

	// 检查各文件权限, 检查settings.ini
	function check() {
		if (!is_writable($this->to)) {
			$this->fatal_error("对目录［{$this->to}］丢失“写”权限");
		}

		if (!is_readable($this->mall_root)) {
			$this->fatal_error("对目录［{$this->mall_root}］丢失“读”权限");
		}

		$this->to = realpath($this->to);
		$this->mall_root = realpath($this->mall_root);
		if (!is_dir($this->site_path)) {
			$this->fatal_error("希望打包的站点［{$this->site_id}］不存在");
		}

		$modules = array_keys(Config::get('site.modules'));
		// TODO whether modules' TRUE not checked(but it's always T) (xiaopei.li@2011-12-15)
		return $modules;
	}

	// 创建临时包目录
	function do_init_env() {
		$this->program_path = "{$this->to}/{$this->site_id}";
		if (is_dir($this->program_path)) {
			$this->fatal_error("{$this->to}目录下已经存在名为{$this->site_id}的目录");
		}
		if (!mkdir($this->program_path)) {
			$this->fatal_error("创建站点目录{$this->site_id}");
		}
	}

	// 准备文件
	function prepare_files($modules) {
		$files = $this->compress($modules);
		$this->move($files);
		$this->copy();
	}

	// 压缩各模块
	function do_compress($modules) {
		$need_phar = array('system', 'application'); // 需要的模块
		$ms = array();
		foreach ($modules as $module) {
			$module = strtolower($module);
			$dir = "modules/{$module}";
			if (is_dir("{$this->mall_root}/{$dir}"))
				$ms[] = $dir;
			$dir = "sites/{$this->site_id}/modules/{$module}";
			if (is_dir("{$this->mall_root}/{$dir}"))
				$ms[] = $dir;
		} // 确定模块路径
		$need_phar = array_merge($need_phar, $ms);

		//生成压缩包文件
		require "includes/php_encoder.php";

		if ($this->encode) {
			$files = array();
			foreach ($need_phar as $f) {
				$file = $f.'.phar';
				$tmp_file = "{$this->mall_root}/{$file}";

				// TODO 若 file_exists, 可能是老包, 也应重新打包
				// 应检查md5(但检查就得记录之前的值...)
				if (!file_exists($tmp_file)) {
					$encoder = new PHP_Encoder("{$this->mall_root}/{$file}");
					$encoder->add("{$this->mall_root}/{$f}");
				}

				$files[] = $file;

			}
		}
		else {
			// TODO 此处 mv 了, 应 cp -r(xiaopei.li@2011.12.05)
			$files = $need_phar;
		}

		return $files;
	}

	// 转移 phar
	function do_move($files) {
		if (!empty($files)) {
			foreach ($files as $file) {
				$this->cp_or_mv('copy', $file);
			}
		}
	}

	private function cp_or_mv($operate, $file) {
		$from = $this->mall_root;
		$to = $this->program_path;

		$pwd = getcwd();
		if (chdir($from)) {

			if (is_file($file) || is_dir($file)) {

				$cmd = 'cp -r --parents "' . $file . '" "' . $to . '"';
				// error_log($cmd . "\n");
				exec($cmd);
				// error_log("== done ==\n");

				if ($operate == 'move') {
					exec('rm -r "' . $file . '"');
				}
			}

		}
		chdir($pwd);
	}	

	// copy 一些需要 copy 的文件
	function do_copy() {
		$site_path = "sites/{$this->site_id}";
		$site_copy = array();
		$dir = "{$this->mall_root}/{$site_path}";
		if (is_dir($dir)) {
			if ($handle=opendir($dir)) {
				while (($file=readdir($handle))!==false) {
					if (strpos($file, '.')===0) continue;
					$f = "{$site_path}/{$file}";
					if ($file=='modules' && is_dir($f)) {
					}
					else {
						$site_copy[] = $f;
					}
				}
			}
			closedir($handle);
		}

		// 增加 sites/SITE_ID 下的 config/ 和 globals.php(xiaopei.li@2011.12.04)
		$need_copy = array('cli', 'public/index.php', 'public/images',
						   'public/favicon.ico','public/dict','public/fonts','vendor'
			);
		$need_copy = array_merge($need_copy, $site_copy);
		foreach ($need_copy as $file) {
			$this->cp_or_mv('copy', $file);
		}
		$dir = __DIR__;
	}

	// 压缩
	function do_zip() {
		$output = array();
		exec("tar -zcf {$this->site_id}.tar.gz -C {$this->to} {$this->site_id} 2>&1", $output);
		if (!empty($output)) {
			$this->warning_error('文件打包失败！');
		}
	}
}

if (!count(debug_backtrace())) {

	$shortopts = "s:d:t";
	$longopts = array(
		'site:',
		'dest:',
		'test',
		);

	$opts = getopt($shortopts, $longopts);

	$site_id = $opts['s'] ? : $opts['site'];

	if (!$site_id) {
		die("usage: php package.php  -s|--site SITE_ID [-d|--dest somewhere] [-t|--test]\n");
	}

	$dest = $opts['d'] ? : ($opts['dest'] ? : '.');

	$is_test = isset($opts['t']) || isset($opts['test']);

	$_SERVER['SITE_ID'] = $site_id;

	if ($_SERVER['SITE_ID']) {
		$db_name = 'mall_' . $site_id;

		exec("mysql -ugenee $db_name -e 'exit'", $foo, $db_exists);
		$exec_true = 0;

		if ($db_exists != $exec_true) {
			// 数据库不存在
			$db_created = 1;
			exec("mysql -ugenee -e 'create database $db_name'", $foo, $db_created);
		}

		if ($db_exists == $exec_true || $db_created == $exec_true) {


			// 引入 cli/base, 以读取待打包 site 的配置 (xiaopei.li@2011-12-13)
			require('../cli/base.php');

			$package = new Package($site_id, $dest, $is_test);

			$package->make();

			if ($db_exists != $exec_true && $db_created == $exec_true) {
				// drop
				exec("mysql -ugenee -e 'drop database $db_name'");
			}
		}
	}
}

