#!/usr/bin/env php
<?php
  // TODO 增加 rsync 机制(xiaopei.li@2012-01-06)
  // TODO 根据 config 中的端口自动生成 xinetd 配置(xiaopei.li@2012-01-06)
require('./package.php');

define('PROGRAM_PATH', 'usr/share/mall');
define('CACHE_PATH', 'var/cache/mall');
define('USER_DATA_PATH', 'var/lib/mall');
define('ETC_PATH', 'etc');
define('MALL_ETC_PATH', 'etc/mall');

class Debian_Package extends Package {
	private static $mkdir_default_perm = 0755;

	function make($pkg_id) {
		$modules = $this->check();
		$this->init_env($pkg_id);
		$this->prepare_files($modules);

		// 重新组织程序的目录结构
		$this->render_program_struct();
		// 增加配置文件
		$this->add_etc_files();
		// 增加控制文件
		$this->add_control_files();
		// 按客户修改配置/控制文件
		$this->update_etc_and_control_files();
		// 转移备份脚本及备份清单
		$this->add_to_proj_list();

		// 添加通过 lims2 程序生成的 etc 配置
		$this->generate_etc();

		// 删除可能包进的日志文件
		$this->delete_logs();

		// 指明配置文件, 安装时(至少在 prerm 后),
		// 若这些文件被用户修改过, 会提示
		$this->add_conffiles();
		
		// 打包
		$this->dpkg_build();

		echo "\n========== {$this->fakeroot} =========\n";
	}

	function prepare($pkg_id) {
		$modules = $this->check();

		// echo 1;

		$this->init_env($pkg_id);

		// echo 2;
		$this->prepare_files($modules);

		// echo 3;
		// 增加配置文件
		$this->add_etc_files();

		// echo 4;
		// 按客户修改配置/控制文件
		$this->update_etc_and_control_files();
		// 转移备份脚本及备份清单
		$this->add_to_proj_list();

		// prepare 不打包
	}
	
	// 按包中的项目生成 etc
	function generate_etc() {
		// TODO
		// 0. modify get_* scripts, add fakeroot option
		// 1. read projs from proj_list
		// 2. foreach run get_* script and write to fakeroot

		$proj_list = file_get_contents("{$this->fakeroot}/etc/mall/proj_list");
		$projs = explode("\n", $proj_list);
		var_dump($projs);


		$root_path = ROOT_PATH;

		foreach ($projs as $proj) {
			if (!$proj) continue;

			$proj_site_id = $proj;

			// daemon
			$daemon_php = "$root_path/cli/get_daemon.php -r=/usr/share/mall/";
			$daemon_conf = $this->fakeroot . '/etc/mall/daemon.conf';
			$daemon_cmd = "SITE_ID=$proj_site_id Q_ROOT_PATH=$root_path php $daemon_php >> $daemon_conf";
			echo "$daemon_cmd\n";
			exec($daemon_cmd);

			// cron
			$cron_php = "$root_path/cli/get_cron.php -u=www-data -r=/usr/share/mall/";
			$cron_conf = $this->fakeroot . '/etc/cron.d/mall';
			$cron_cmd = "SITE_ID=$proj_site_id Q_ROOT_PATH=$root_path php $cron_php >> $cron_conf";
			echo "$cron_cmd\n";
			exec($cron_cmd);

			// sphinx
			$sphinx_php = "$root_path/cli/get_sphinx.php";
			$sphinx_conf = $this->fakeroot . '/etc/sphinxsearch/conf.d/mall.conf';
			$sphinx_cmd = "SITE_ID=$proj_site_id Q_ROOT_PATH=$root_path php $sphinx_php >> $sphinx_conf";
			echo "$sphinx_cmd\n";
			exec($sphinx_cmd);

		}

	}
	
	function add_to_proj_list() {
		exec("cp debian_backup {$this->fakeroot}/etc/mall/backup");
		file_put_contents("{$this->fakeroot}/etc/mall/proj_list",
						  "$this->site_id\n", FILE_APPEND);
	}

	function delete_logs() {
		$pwd = getcwd();
		if (chdir($this->fakeroot)) {
			exec('find . -type f -name "*.log" -delete');
		}
		chdir($pwd);
	}

	function add_conffiles() {
		$pwd = getcwd();
		if (chdir($this->fakeroot)) {
			exec('find etc/ -type f > DEBIAN/conffiles');
			exec('find var/ -type f >> DEBIAN/conffiles');
		}
		chdir($pwd);
	}
	
	// 创建临时包目录
	function init_env($pkg_id) {
		// $this->fakeroot = "/tmp/{$this->site_id}_fakeroot_" . uniqid();
		$this->fakeroot = "/tmp/mall_debian_package_fakeroot_" . $pkg_id;
		$this->program_path =  "{$this->fakeroot}/" . PROGRAM_PATH;
		if (!is_dir($this->program_path) && !mkdir($this->program_path, self::$mkdir_default_perm, TRUE)) {
			$this->fatal_error("创建打包临时目录失败 {$this->$program_path}");
		}
	}

	function update_etc_and_control_files() {
		echo 'a';
		
		exec("sed -i 's/%site_id%/{$this->site_id}/' `grep -lrs '%site_id%' {$this->fakeroot}/etc {$this->fakeroot}/DEBIAN` 2>/dev/null");

		// TODO it's not very fit to assign $version here(xiaopei.li@2011-12-16)
		$version = Config::get('system.version');

		if (preg_match('/(\d.*$)/', $version, $matches)) {
			$this->version = $matches[1];
			preg_match('/([\d.]*)/', $this->version, $matches);
			$this->base_version = $matches[1];
		}
		else {
			$this->version = 0;
		}

		echo 'b';
		
		exec("sed -i 's/%VERSION%/{$this->version}/' `grep -lrs '%VERSION%' {$this->fakeroot}/etc {$this->fakeroot}/DEBIAN` 2>/dev/null");

		echo 'c';
		
		exec("sed -i 's/%BASE_VERSION%/{$this->base_version}/' `grep -lrs '%BASE_VERSION%' {$this->fakeroot}/etc {$this->fakeroot}/DEBIAN` 2>/dev/null");

		$nfs_root = Config::get('nfs.root');
		echo 'd';
		
		$str = "sed -i 's#%NFS_ROOT%#{$nfs_root}#' `grep -lrs '%NFS_ROOT%' {$this->fakeroot}/etc {$this->fakeroot}/DEBIAN` 2>/dev/null";
		exec($str);
	}

	function dpkg_build() {
		exec("dpkg -b {$this->fakeroot} mall_{$this->site_id}_{$this->version}.deb");
	}

	function add_control_files() {
		exec("cp -r DEBIAN {$this->fakeroot}");
	}

	function add_etc_files() {
		// mkdirs
		$this->etc_path = "{$this->fakeroot}/" . ETC_PATH;
		$this->mall_etc_path = "{$this->fakeroot}/" . MALL_ETC_PATH;
		if (!is_dir($this->etc_path)) {
			mkdir($this->etc_path, self::$mkdir_default_perm, TRUE);
		}
		if (!is_dir($this->mall_etc_path)) {
			mkdir($this->mall_etc_path, self::$mkdir_default_perm, TRUE);
		}
		
		
		exec("cp -r etc/* {$this->etc_path}"); // 默认 etc

		$user_etc_path = $this->user_data_path . "/sites/{$this->site_id}" .  '/etc' ;

		if (is_dir($user_etc_path)) {
			exec("cp -r {$user_etc_path}/* {$this->etc_path}"); // 项目重载的 etc
		}

		// other etc
		// logrotate
		$this->logrotate_path = "{$this->etc_path}/logrotate.d";
		@mkdir($this->logrotate_path);

		// dateext 会使用日期为后缀, 当 rotate 时已有该后缀的文件时, rotate 不会执行, 日志保持原样
		$logrotate_conf = '/var/lib/mall/sites/%site_id%/logs/*.log {
	weekly
	dateext
	missingok
	rotate 5000
	notifempty
	noolddir
	copytruncate
	compress
}
';
		// weekly + rotate 5000 可以保存快 100 年的备份
		$this->logrotate_file = "{$this->logrotate_path}/mall_{$this->site_id}";
		file_put_contents($this->logrotate_file, strtr($logrotate_conf, array(
									'%site_id%' => $this->site_id,
									)));
	}

	function render_program_struct() {
		// mkdirs
		$this->user_data_path = "{$this->fakeroot}/" . USER_DATA_PATH;
		mkdir($this->user_data_path, self::$mkdir_default_perm, TRUE);

		$this->cache_path = "{$this->fakeroot}/" . CACHE_PATH;
		mkdir($this->cache_path, self::$mkdir_default_perm, TRUE);

		// mv user data
		$pwd = getcwd();
		if (chdir($this->program_path)) {
			$sites = "sites";

			// mv sites
			if (is_dir($sites)) {
				exec( "cp -r --parents {$sites} {$this->user_data_path}");
			}
			exec("rm -r $sites");

			// link
			$link = realpath(dirname($sites)) . '/sites';
			$target = '/' . USER_DATA_PATH . "/{$sites}";
			symlink($target, $link);
		}
		chdir($pwd);

		// link cache
		$target = '/' . CACHE_PATH;
		$link = realpath($this->fakeroot) . '/usr/share/mall/public/cache';
		symlink($target, $link);
	}
}

if (!count(debug_backtrace())) { // like python's 'if (__name__ == "__main__" )'

	$shortopts = "s:p:d:tPM";
	$longopts = array(
		'site:',
		'pkg-id:',
		'dest:',
		'test',
		'prepare',
		'make',
		);

	$opts = getopt($shortopts, $longopts);

	$site_id = $opts['s'] ? : $opts['site'];

	if (!isset($opts['p'])) {
		$opts['p'] = '';
	}
	if (!isset($opts['pkg-id'])) {
		$opts['pkg-id'] = '';
	}

	$pkg_id = $opts['p'] ? : $opts['pkg-id'];
	if (!$pkg_id) {
		$pkg_id = uniqid();
	}
	
	if (!$site_id) {
		die("usage: php debian_package.php  -s|--site SITE_ID [-p|--pkg-id 123] [-d|--dest somewhere] [-t|--test] [-P|--prepare] [-M|--make]\n");
	}

	if (!isset($opts['d'])) {
		$opts['d'] = '';
	}
	if (!isset($opts['dest'])) {
		$opts['dest'] = '';
	}
	$dest = $opts['d'] ? : ($opts['dest'] ? : '.');

	$is_test = isset($opts['t']) || isset($opts['test']);
	$prepare_only = isset($opts['P']) || isset($opts['prepare']);

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

			$package = new Debian_Package($site_id, $dest, $is_test);

			if ($prepare_only) {
				$package->prepare($pkg_id);
				echo $package->fakeroot;
			}
			else {
				$package->make($pkg_id);
			}

			if ($db_exists != $exec_true && $db_created == $exec_true) {
				// drop
				exec("mysql -ugenee -e 'drop database $db_name'");
			}
		}
	}
}
