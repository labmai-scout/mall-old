#!/usr/bin/env php
<?php
// TODO encode.php 每次打的包 md5sum 都不同, 有随机种子? 是否可使之相同? (xiaopei.li@2012-01-13)

if ( 0!= strncmp(php_sapi_name(), 'cli', 3)) {
	die("Please use it in command-line\n");
}

require 'includes/php_encoder.php';

$args = $argv;
array_shift($args);

if (!count($args)) {
	die("Please specific directory name\nUsage: encode dir1 dir2 dir3\n");
}


foreach ($args as $i => $arg) {
	if ($arg[0] == '-') continue;

	$input = $arg;
	$input = preg_replace('|/+$|', '', $input);

	$output_file = $input.'.phar';

	$encoder = new PHP_Encoder($output_file);
	$encoder->add($input);

}


