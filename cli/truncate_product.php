#!/usr/bin/env php
<?php

/*
	sphinx 的删除只需要 truncate rtindex table_name 即可
*/
require 'base.php';

$db = Database::factory();
$db->drop_table('product');


?>