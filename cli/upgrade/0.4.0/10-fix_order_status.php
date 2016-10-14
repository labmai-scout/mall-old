#!/usr/bin/env php
<?php

require dirname(dirname(dirname(__FILE__))). '/base.php';

$db = Database::factory();
$ret = $db->query('update `order` set status=11 where confirm=1');
if ($ret) {
	$ret2 = $db->query("ALTER TABLE `order` drop column `confirm`");
}
