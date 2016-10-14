<?php
/**
 *
 *
 */

$base = dirname(dirname(__FILE__)). '/base.php';
require $base;

$db = Database::factory();
$SQL = "SELECT * FROM product WHERE type='reagent' and _extra like '%rgt_type\":\"3%'";
$start = 0;
$limit = 200;
while (true) {
	$s = $SQL." LIMIT $start, $limit";
	$result = $db->query($s);
	$rows = $result->rows();
	if (!count($rows)) break;
	foreach ($rows as $row) {
		$pid = $row->id;
		$product = O('product', $pid);
		$product->fixed_price = true;
		if ($product->save()) {
			echo '.';
		}
	}
	$start += $limit;
}

$SQL = "SELECT * FROM product WHERE type='reagent' and _extra like '%rgt_type\":3%'";
$start = 0;
$limit = 200;
while (true) {
	$s = $SQL." LIMIT $start, $limit";
	$result = $db->query($s);
	$rows = $result->rows();
	if (!count($rows)) die;
	foreach ($rows as $row) {
		$pid = $row->id;
		$product = O('product', $pid);
		$product->fixed_price = true;
		if ($product->save()) {
			echo '.';
		}
	}
	$start += $limit;
}

?>
