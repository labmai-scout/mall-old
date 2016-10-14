<?php
/**
* @file order.php
* @brief 升级订单前的数据准备
* @author PiHiZi <pihizi@msn.com>
* @version 0.1.0
* @date 2015-05-08
 */

// TODO 允许值升级指定买方的订单

$base = dirname(dirname(__FILE__)). '/base.php';
require($base);

clean_cache();

function getWhiteList($type)
{
    $file = dirname(__FILE__) . '/data/' . $type;
    if (!file_exists($file)) return;
    $content = file_get_contents($file);
    if ($content) {
        $result = explode(',', trim($content));
        $result = array_map(function($v) {
            return (int) trim($v);
        }, $result);
        return $result;
    }
}

$whiteList = getWhiteList('customer');

function logMe($msg)
{
    echo $msg . "\n";
}

function getOrders()
{
    global $whiteList;
    $db = database::factory();
    $cids = 'select id from customer where gapper_group>0';
    if (!empty($whiteList)) {
        $cids = implode(',', $whiteList);
    }
    $sql = 'select id,_extra from `order` where customer_id in ('.$cids.') and (!purchaser_id or !operator_id or purchaser_id in (select id from user where gapper_user=0) or operator_id in (select id from user where gapper_user=0))';
    $query = $db->query($sql);
    while ($row=$query->row()) {
        yield $row;
    }
}


function getNeedRehashRows()
{
    global $whiteList;
    $db = database::factory();
    $cids = 'select id from customer where gapper_group>0';
    if (!empty($whiteList)) {
        $cids = implode(',', $whiteList);
    }
    $sql = 'select id,_extra from `order` where customer_id in ('.$cids.')';
    $query = $db->query($sql);
    while ($row=$query->row()) {
        yield $row;
    }
}

function getRevisionHash($revision)
{
    $hash_data = [
        'requester' => (string) $revision->requester->gapper_user,
        'vendor' => (int) $revision->vendor->id,
        'address' => (string) $revision->address,
        'phone' => (string) $revision->phone,
        'postcode' => (string) $revision->postcode,
        'email' => (string) $revision->email,
        'note' => (string) $revision->note,
        'status' => (int) $revision->status,
        'deliver_status' => (int) $revision->deliver_status,
        'items' => (array)json_decode($revision->items_data, true),
    ];

    return hash('sha1', json_encode($hash_data));
}

$db = database::factory();
$rows = getOrders();
foreach ($rows as $row) {
    $extra = json_decode($row->_extra, true);
    $order = O('order', $row->id);
    $owner = $order->customer->owner;

    $sql = 'update `order` set ';
    $sets = [];
    if (!$order->purchaser->gapper_user) {
        $extra['upgrade_backup_purchaser'] = $order->purchaser->id;
        $order->purchaser = $owner;
        array_push($sets, 'purchaser_id='.$db->quote($owner->id));
    }
    if (!$order->operator->gapper_user) {
        $extra['upgrade_backup_operator'] = $order->operator->id;
        $order->operator = $owner;
        array_push($sets, 'operator_id='.$db->quote($owner->id));
    }

    $hash = $order->get_hash();
    $extra['compare_hash'] = $hash;

    array_push($sets, '_extra=' . $db->quote(json_encode($extra)));
    array_push($sets, 'hash=' . $db->quote($hash));

    $sql = $sql . implode(', ', $sets) . ' where id=' . $order->id;
    Log::add($sql, 'upgrade');

    $ret = $db->query($sql);
    if (!$ret) {
        echo 'rehash order#' . $order->id . ':' . $sql . "\n";
    }
    else {
        echo '.';
    }
}

clean_cache();

$rows = getNeedRehashRows();
foreach ($rows as $row) {
    $order = O('order', $row->id);
    $revisions = Q("order_revision[order={$order}]");
    $owner = $order->customer->owner;

    $changed = [];

    foreach ($revisions as $revision) {
        $tmp = false;
        if (!$revision->requester->gapper_user) {
            $revision->upgrade_backup_requester = $revision->requester->id;
            $revision->requester = $owner;
            $tmp = true;
        }
        if (!$revision->operator->gapper_user) {
            $revision->upgrade_backup_operator = $revision->operator->id;
            $revision->operator = $owner;
            $tmp = true;
        }
        if ($tmp) {
            $revision->upgrade_backup_hash = $revision->hash;
            $revision->hash = getRevisionHash($revision);
            $revision->save();
        }
        if (!$revision->upgrade_backup_hash || $revision->hash == $revision->upgrade_backup_hash) continue;
        $changed[$revision->upgrade_backup_hash] = $revision->hash;
    }

    if (!empty($changed)) {
        $news = [];
        foreach ((array)$order->revision_hashs as $hash=>$mtime) {
            if (isset($changed[$hash])) {
                $news[$changed[$hash]] = $mtime;
                continue;
            }
            $news[$hash] = $mtime;
        }

        $sql = 'update `order` set revision_hashs_json=' . $db->quote(json_encode($news)) . ' where id=' . $order->id;
        $ret = $db->query($sql);
        if (!$ret) {
            echo $order->id . ':' . $ret . "\n";
        }
        else {
            echo '-';
        }
    }
}

clean_cache();
