#!/usr/bin/env php
<?php

include "base.php";

$id = (int) $argv[1];
$order = O('order', $id);
if ($order->id) {
	$status = (int) $argv[2];
	$order->status = $status;
	$order->save();
}
echo 'done';

