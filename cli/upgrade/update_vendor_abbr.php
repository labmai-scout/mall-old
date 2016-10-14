#!/usr/bin/env php
<?php

	$base = dirname(dirname(__FILE__)). '/base.php';
   	include $base;
    $vendors = Q('vendor');
    ORM_Model::db('vendor');
    $db = Database::factory();
    foreach($vendors as $vendor) {
    	$db->query('UPDATE vendor SET short_abbr = "%s" WHERE id = %d', PinYin::code($vendor->short_name, TRUE), $vendor->id);
    }