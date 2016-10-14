<?php
/**
 * @file update_customer_name.php
 * @brief 由于线上的买方名称可能出现重名，所以，提供这个脚本，对历史存在的买方进
 *        行处理
 * @author Hongjie Zhu <pihizi@msn.com>
 * @version 0.1.0
 * @date 2015-01-27
 */
require '../base.php';

$customers = Q("customer");

foreach ($customers as $customer) {
    $owner = $customer->owner;
    if (!$owner->id || !$owner->name) {
        continue;
    }
    $cname = $customer->name;
    $oname = $owner->name;
    if (false !== mb_strpos($cname, $oname)) {
        continue;
    }
    $customer->name = "$cname $oname";
    if (!$customer->save()) {
        echo "\t{$customer->id}#{$cname} 改名失败\n";
    }
}
