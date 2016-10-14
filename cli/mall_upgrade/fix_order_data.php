<?php
/*
修正mall-old错误数据
1. 如果订单voucher为空将order_no的值赋值到voucher并更新对应的order_revision
2. order_revision的items中product_name 换为 name
*/
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
clean_cache();
$db = Database::factory();
// 清理掉由于商品唯一性导致的revision items对应异常的问题
$st = 0;
$lt = 50;
while(true) {
	$orders = Q("order")->limit($st, $lt);
	if (!count($orders)) break;
	$st += $lt;
	foreach ($orders as $order) {
		$items = Q("order_item[order={$order}]")->to_assoc('id', 'product_id');
		$revision = Q("order_revision[order={$order}]:sort(ctime D)")->current();
		if (!$revision->id) continue;
		$revision_items =  json_decode($revision->items, true);
		$ritems = [];
		foreach ($revision_items as $revision_item) {
			$ritems[] = $revision_item['id'];
		}
		// 说明有异常
		if (count(array_diff($ritems, $items))) {
			Q("order_revision[order={$order}]")->delete_all();
			$order->revision_hashs = '';
			$order->compare_hash = '';
			if ($order->save()) {
				echo '订单ID: '.$order->id."\n";
			}
		}
	}
}
echo "revison items product id fix end.....\n";
clean_cache();
$lt = 50;
$st = 0;
while (true) {
	$orders = Q("order")->limit($st, $lt);
	$st += $lt;
	if (!count($orders)) break;
	foreach ($orders as $order) {
		$revision_hashs = $order->revision_hashs;
		$hash_tree = array_pop(array_keys($revision_hashs));

		$oid = $order->id;
		$revision = Q("order_revision[order_id={$oid}]:sort(ctime D)")->current();
		if ($revision->id && $hash_tree && $revision->hash != $hash_tree) {
			echo $oid."\n";
			$order->revision_hashs = '';
			$order->compare_hash = '';
			if ($order->save()) {
				echo '.';
			}
		}
		elseif ($revision->id && $revision->status != $order->status) {
			echo $oid."\n";
			$order->revision_hashs = '';
			$order->compare_hash = '';
			if ($order->save()) {
				echo '.';
			}
		}
	}
}

echo "revison exception fix end.....\n";

clean_cache();
$lt = 50;
while (true) {
	$orders = Q("order[order_no][!voucher]")->limit($lt);
	if (!count($orders)) break;
	foreach ($orders as $order) {
		$oid = $order->id;
		$voucher = $order->order_no;

		if ($db->query("update `order` set voucher='".$voucher."' where id=$oid")) {
			$db->query("update `order_revision` set voucher='".$voucher."' where order_id=$oid");
			echo '.';
		}
	}
}
echo "order_no voucher fix end.....\n";

clean_cache();
$status = Transfer_Statement_Model::STATUS_DRAFT;
$orders = Q("transfer_statement[status=$status] order[status=2]");
foreach ($orders as $order) {
	$order->status = 4;
	if ($order->save()) {
		echo '~';
	}
}
echo "statement order status fix end.....\n";

$st = 0;
$lt = 50;
clean_cache();
while (true) {
	$revisions = Q("order_revision")->limit($st, $lt);
	$st += $lt;
	if (!count($revisions)) break;
	foreach ($revisions as $revision) {
		$ns = false;
		$items = json_decode($revision->items, true);
		foreach ($items as $key => $item) {
			if (isset($item['product_name'])) {
				$item['name'] = $item['product_name'];
				unset($item['product_name']);
				$items[$key] = $item;
				$ns = true;
			}
		}

		if ($ns) {
			$revision->items = json_encode($items);
			if ($revision->save()) {
				echo '.';
			}
			else {
				echo 'x';
			}
		}
		else {
			echo '-';
		}
	}
}
echo "revison product name fix end.....\n";
clean_cache();
echo "final end...\n";
