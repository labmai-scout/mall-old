#!/usr/bin/env php
<?php
    /*
     * file update_vendor_short_abbr.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-01-10
     *
     * useage SITE_ID=smth php update_vendor_short_abbr.php
     * brief 用于对vendor的short_abbr进行更新
     */

require 'base.php';

$db = Database::factory();

foreach(Q('vendor') as $vendor) {
    $db->query("UPDATE `vendor` SET `short_abbr` = '%s' WHERE `id` = %d", PinYin::code($vendor->short_name), $vendor->id); 
}
