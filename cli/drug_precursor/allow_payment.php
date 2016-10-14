<?php

$base = dirname(dirname(__FILE__)). '/base.php';
require($base);

// putenv('Q_ROOT_PATH='.ROOT_PATH);
// putenv('SITE_ID='.SITE_ID);
// setlocale(LC_CTYPE, 'UTF8', 'en_US.UTF-8');
// $cmd = 'php ' . ROOT_PATH . 'cli/drug_precursor/allow_payment.php -v %vids > /dev/null 2>&1 &';
// $cmd = strtr($cmd, [
//     '%vids'=> 'id,id,id'
// ]);
// exec($cmd);
// return true;

$options = "v:";
$opts = getopt($options);
if (!isset($opts['v'])) return;
$vouchers = $opts['v'];
if (!$vouchers) return;
$vouchers = explode(',', $vouchers);
foreach ($vouchers as $voucher) {
    $order = O('order', ['voucher'=> $voucher]);
    if ($order->id && $order->payment_status!=Order_Model::PAYMENT_STATUS_PENDING) {
        $order->payment_status = Order_Model::PAYMENT_STATUS_PENDING;
        $order->save();
    }
}
