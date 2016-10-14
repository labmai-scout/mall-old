#!/usr/bin/env php
<?php

include "base.php";

foreach(Q("order") as $order) {
	$order->price = Q("order_item[order=$order]")->sum('price');
	$order->save();
}
