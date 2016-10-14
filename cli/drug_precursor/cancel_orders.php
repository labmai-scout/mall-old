<?php

$base = dirname(dirname(__FILE__)). '/base.php';
require($base);

// putenv('Q_ROOT_PATH='.ROOT_PATH);
// putenv('SITE_ID='.SITE_ID);
// setlocale(LC_CTYPE, 'UTF8', 'en_US.UTF-8');
// $cmd = 'php ' . ROOT_PATH . 'cli/drug_precursor/cancel_orders.php -v %vids > /dev/null 2>&1 &';
// $cmd = strtr($cmd, [
//     '%vids'=> 'id,id,id'
// ]);
// exec($cmd);
// return true;

$options = "v:";
$opts = getopt($options);
if (!isset($opts['v'])) return;
$vouchers = $opts['v'];
if (!$vouchers ) return;
$vouchers = explode(',', $vouchers);
foreach ($vouchers as $voucher) {
    $order = O('order', ['voucher'=> $voucher]);
    if ($order->id && $order->status!=Order_Model::STATUS_CANCELED && ($order->admin_can_cancel() || $order->vendor_can_cancel())) {
        $order->cancel('易制毒商品申购停止，未提交订单直接取消');
    }
}
