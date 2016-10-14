#!/usr/bin/env php
<?php
/*
	SITE_ID=nankai php 10-fix_user_vendor.php
*/
$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;

try {

	$db = Database::factory();
	$owners = $db->query('SELECT owner_id as id,id as vendor_id FROM vendor')->rows();
	foreach ($owners as $owner) {

		$vendors = $db->query("SELECT vendor_id as id FROM user where id=%d", $owner->id)->rows();
		foreach ($vendors as $vendor) {
			if (!$vendor->id) {
				$db->query('UPDATE user SET vendor_id = "%d" WHERE id = %d',$owner->vendor_id, $owner->id);
			}
		}

	}	
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}
