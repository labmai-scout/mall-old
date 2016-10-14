#!/usr/bin/env php
<?php
include 'base.php';

$usage = <<<EOT
根据 Q 语句生成 SQL 语句
usage:
	SITE_ID=site_id LAB_ID=lab_id ./q2sql.php 'q1' ['q2' ...]
example:
	SITE_ID=cf LAB_ID=test ./q2sql.php 'user#1 equipment'

EOT;

if ($argc < 2) {
	die($usage);
}

$db = Database::factory();

for ($i = 1; $i < $argc; $i++) {
	$q = $argv[$i];

	$q_maker = new Q_Query($db);
	$q_maker->parse_selector($q);
	$q_maker->makeSQL();

	printf("%s:\n%s\n\n", $q, $q_maker->SQL);
}
