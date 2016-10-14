#!/usr/bin/env php
<?php
    /*
     * file 00-fix_vendor_scope_expire_date.phhp
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-02-10
     *
     * useage SITE_ID=nankai php 00-fix_vendor_scope_expire_date.php
     * 修正vendor_scope的expire_date错误问题
     */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    //可直接进行升级, 可重复升级
    return TRUE;
};

//数据库备份
$u->backup = function() {
    //无需备份数据
    return TRUE;
};

$u->upgrade = function() {
    foreach(Q('vendor_scope') as $scope) {
        if ($scope->expire_date < $scope->expire_date_from) {
            //矫正
            list($scope->expire_date, $scope->expire_date_from) = array($scope->expire_date_from, $scope->expire_date);
            $scope->save();
        }
    };
    return TRUE;
};

$u->run();
