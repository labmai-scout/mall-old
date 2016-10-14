#!/usr/bin/env php
<?php
$base = dirname(dirname(__FILE__)). '/base.php';
include $base;
$statements = Q('billing_statement[status=3]');
foreach ($statements as $statement) {
	$data = $statement->get_account_status();
	$return = current($data);
    if ($return['BMBH']) {
        $statement->bmbh = $return['BMBH'];
    }
    if ($return['XMBH']) {
        $statement->xmbh = $return['XMBH'];
    }
    if ($return['ZFLSH']) {
        $statement->lsh = $return['ZFLSH'];
    }
    $statement->save();
}