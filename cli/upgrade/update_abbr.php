#!/usr/bin/env php
<?php

require 'base.php';


try {

	//用户名缩写
	$db = Database::factory();
	$ds = $db->query('SELECT id,name FROM user');
	while ($row = $ds->row()) {
		$db->query('UPDATE user SET name_abbr = "%s" WHERE id = %d', PinYin::code($row->name), $row->id);
	}
	
	//实验室名缩写
	$db = Database::factory();
	$ds = $db->query('SELECT id,name FROM lab');
	while ($row = $ds->row()) {
		$db->query('UPDATE lab SET name_abbr = "%s" WHERE id = %d', PinYin::code($row->name), $row->id);
	}
	
	$db->query('UPDATE user, lab SET user.lab_abbr = "" WHERE user.lab_id = 0');
	$db->query('UPDATE user, lab SET user.lab_abbr = lab.name_abbr WHERE user.lab_id = lab.id');
	
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}
