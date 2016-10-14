#!/usr/bin/env php
<?php
$shortopts = "f:d:t";
$longopts = array(
	'list-file:',
	'dest:',
	'test',
	);

$opts = getopt($shortopts, $longopts);

$ks = array('f', 'd', 't', 'list-file', 'dest', 'test');
foreach ($ks as $k) {
	if (!isset($opts[$k])) {
		$opts[$k] = '';
	}
}


$list_file = $opts['f'] ? : $opts['list-file'];
$dest = $opts['d'] ? : ($opts['dest'] ? : '.');
$is_test = isset($opts['t']) || isset($opts['test']);

if (!$list_file) {
	die("usage: php debian_package_multi.php  -f|--list-file list_file [-d|--dest somewhere] [-t|--test]\n");
}

$project_list = array();

$handle = @fopen($list_file, "r");
if ($handle) {
	while (($buffer = fgets($handle, 4096)) !== false) {
		if (!trim($buffer)) continue;

		$site_id = $buffer;
		$project_list[] = array(
			'site_id' => trim($site_id),
			);
	}

	if (!feof($handle)) {
		echo "Error: unexpected fgets() fail\n";
	}
	fclose($handle);
}

$pkg_id = uniqid();
$required = FALSE;

echo $pkg_id . "\n";


$temp_dbs = array();

while ($project = array_pop($project_list)) {
	$site_id = $project['site_id'];

	$db_name = 'mall_' . $site_id;

	exec("mysql -ugenee $db_name -e 'exit'", $foo, $db_exists);
	$exec_true = 0;

	if ($db_exists != $exec_true) {
		// 数据库不存在
		$db_created = 1;
		echo 'c ' . $db_name . "\n";
		exec("mysql -ugenee -e 'create database $db_name'", $foo, $db_created);
	}

	if ($db_exists == $exec_true || $db_created == $exec_true) {

		if ($project_list) {
			echo "==> preparing\t{$site_id}\n";
			$cmd = "php debian_package.php -s $site_id -p $pkg_id -P";
			// echo $cmd . "\n";
			exec($cmd);
		}
		else {
			echo "==> making \t" . $site_id . "\n";
			$cmd = "php debian_package.php -s $site_id -p $pkg_id -M";
			// echo $cmd . "\n";
			exec($cmd);
		}
	}

	if ($db_exists != $exec_true && $db_created == $exec_true) {
		// drop
		$temp_dbs[] = $db_name;
	}

}


foreach ($temp_dbs as $db_to_drop) {
	echo 'd ' . $db_to_drop . "\n";
	exec("mysql -ugenee -e 'drop database $db_to_drop'");
}
