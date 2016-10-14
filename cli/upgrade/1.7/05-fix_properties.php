#!/usr/bin/env php
<?php
    /*
     * file 00-fix_perperties.phhp
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-01-22
     *
     * useage SITE_ID=nankai php 00-fix_properties.php
     * brief 转移虚属性到实属性, 部分实属性使用connect关联
     */


$base = dirname(dirname(dirname(__FILE__))) . '/base.php';

require $base;

//disable notification
define('DISABLE_NOTIFICATION', TRUE);

class Foo {

    private $_object;
    private $_data = array();
    private $_table;

    static $db;

    public function __construct($object) {
        $this->_object = $object;

        $name = $object->name();
        $id = $object->id;
        $table = '_p_'. $name;

        $this->_table = $table;

        $_db = self::$db;
        if (! $_db instanceof Database) {
            $_db = self::$db = Database::factory();
        }

        $data = $_db->value('SELECT `data` FROM `%s` WHERE `id` = %d', $table, $id);

        $this->_data = (array) (@unserialize($data) ?: @unserialize(base64_decode($data)));
    }

    public function set($key, $value = NULL) {
        //错误设定
        if ($key instanceof ORM_Model) return $this;

        if ($value === NULL) {
            unset($this->_data[$key]);
        }
        else {
            $this->_data[$key] = $value;
        }

        return $this;
    }

    public function get($key) {
        return $this->_data[$key];
    }

    public function save() {
        $_db = self::$db;

        $_db->query('INSERT INTO `%1$s` (`id`, `data`) VALUES (%2$d, "%3$s") ON DUPLICATE KEY UPDATE `data`="%3$s"', $this->_table, $this->_object->id, base64_encode(serialize($this->_data)));

        return $this;
    }
}

//类似于P函数, 只不过该函数用于进行虚属性转移、删除操作
function _P($object) {
    return new Foo($object);
}

$u = new Upgrader;

$u->check = function() {
    //进行升级, 可多次进行升级
    return TRUE;
};

//数据库备份
$u->backup = function() {
    $dbfile = SITE_PATH. 'private/backup/before_fix_properties.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
    $db = Database::factory();
    return $db->snapshot($dbfile);
};

$u->upgrade = function() {

    $db = Database::factory();

    //需要修正的数据包括如下数据

    //vendor
    //      -> last_approver    转为id存储
    //      -> unapprover       删除

    //order -> deliver_address  转为id存储

    //vendor_product
    //      -> last_approver    转为id存储
    //      -> unapprover       转为id存储

    //cart_item -> requester    转为实属性存储

    //user vendor删除

    //vendor->last_approver ->unapprover 转为id存储

    //vendor数量相对较少，直接foreach即可
    foreach(Q('vendor') as $vendor) {

        $_vendor = _P($vendor);

        $_vendor->set('last_approver', $_vendor->get('last_approver')->id)->save();

        $_vendor->set('unapprover', NULL)->save();
    }

    //order -> deliver_address  转为id存储

    $start = 0;
    $perpage = 20;
    while($orders = $db->query('SELECT `id` FROM `order` LIMIT %d, %d', $start, $perpage)->rows('assoc')) {
        foreach($orders as $order) {
            $order = O('order', $order['id']);
            $_order = _P($order);
            if ($_order->get('deliver_address')) {
                $_order->set('deliver_address', $_order->get('deliver_address')->id)->save();
            }
        }

        $start += $perpage;
    }

    //vendor_product
    //      -> last_approver    转为id存储
    //      -> unapprover       转为id存储

    $start = 0;
    $perpage = 20;

    while($vendor_products = $db->query('SELECT `id` FROM `vendor_product` LIMIT %d, %d', $start, $perpage)->rows('assoc')) {
        foreach($vendor_products as $vendor_product) {
            $vendor_product = O('vendor_product', $vendor_product['id']);
            $_vendor_product = _P($vendor_product);

            if ($_vendor_product->get('last_approver')->id || $_vendor_product->get('unapprover')->id) {
                $_vendor_product
                    ->set('last_approver', $_vendor_product->get('last_approver')->id)
                    ->set('unapprover', $_vendor_product->get('unapprover')->id)
                    ->save();
            }

        }

        $start += $perpage;
    }

    //cart_item -> requester    转为实属性存储
    //schema中已配置requester为object
    //更新表结构
    ORM_Model::db('cart_item');
    $start = 0;
    $perpage = 20;

    while($cart_items = $db->query('SELECT `id` FROM `cart_item` LIMIT %d, %d', $start, $perpage)->rows('assoc')) {

        foreach($cart_items as $cart_item) {
            $cart_item = O('cart_item', $cart_item['id']);
            $_cart_item = _P($cart_item);

            $db->query('UPDATE `cart_item` SET `requester_id` = %d', $_cart_item->get('requester')->id);
        }

        $start += $perpage;
    }

    //user vendor删除

    ORM_Model::db('user');
    $start = 0;
    $perpage = 20;

    while($users = $db->query('SELECT `id` FROM `user` LIMIT %d, %d', $start, $perpage)->rows('assoc')) {

        foreach($users as $user) {
            $user = O('user', $user['id']);
            $_user = _P($user);

            $_user->set('vendor', NULL)->save();
        }

        $start += $perpage;
    }

    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '升级成功!');

    return TRUE;
};

//恢复数据
$u->restore = function() {
    $dbfile = SITE_PATH. 'private/backup/before_fix_properties.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
    $db = Database::factory();
    $db->restore($dbfile);
};

$u->run();
