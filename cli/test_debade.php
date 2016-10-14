<?php

require_once('base.php');

$order = Q('order:sort(ctime DESC):limit(1)')->current();
if ($order) {
    $order->debade_order_update();
}
