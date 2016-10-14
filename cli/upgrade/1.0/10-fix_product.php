#!/usr/bin/env php
<?php
/*
 * file 10-fix_product.php
 * author Jinlin.Li <jinlin.li@geneegroup.com>
 * date  2013-08-08
 *
 * useage SITE_ID=smth php 10-fix_product.php
 */

require dirname(dirname(dirname(__FILE__))). '/base.php';

$db = Database::factory();
try {
		$sql = "ALTER TABLE `product` DROP INDEX `unique`";
		$db->query($sql);
   		$sql = "ALTER TABLE `product` ADD UNIQUE INDEX `unique` (`manufacturer`, `catalog_no`, `package`)";
   		$db->query($sql);
   }
catch(Exception $e) {
    return FALSE;
}
