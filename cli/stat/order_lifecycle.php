<?php
/**
 * @file order_lifecycle.php
 * @brief 统计订单生命周期
 * @author Jinlin Li <jinlin.li@geneegroup.com>
 * @version 0.1.0
 * @date 2015-09-18
 * 表一
 * 导出表包含列：订单编号、供应商名称、买方名称、订单总额、订单状态、创建时间、
 * 供应商第一次确认、双方确认、发货时间、收货时间、付款时间、付款完成时间、供应商结算时间、结算完成时间、取消时间
 * 订单状态：包含送货状态，以、分隔
 * 创建时间：取值年月日-时分（格式不限）
 * 供应商第一次确认：供应商第一次点击【确认订单】与订单创建时间的时间差
 * 双方确认：订单状态变为【待付款】与订单创建时间的时间差
 * 发货时间：供应商第一次点击【发货】或【全部发货】与订单创建时间的时间差
 * 收货时间：买方第一次点击【收货】或【全部收货】与订单创建时间的时间差
 * 付款时间：订单状态变为【付款中】与订单创建时间的时间差
 * 付款完成时间：订单状态变为【已付款】与订单创建时间的时间差
 * 供应商结算时间：订单变为【待结算】与订单创建时间的时间差
 * 结算完成时间：订单状态变为【已结算】与订单创建时间的时间差
 * 取消时间：订单状态变为【已取消】与订单创建时间的时间差
 * 时间差，以h为单位，四舍五入保留2位小数
 * 系统没有记录的时间需增加记录
 * 导出3月份以后数据
 * 取不到的值显示--
 */
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
clean_cache();
$time_start = mktime(0,0,0,3,31,2015);
$csv = new CSV('order_lifecycle.csv', 'w');
$csv->write([
'订单编号','供应商名称','买方名称','订单总额','订单状态','创建时间','供应商第一次确认','双方确认','发货时间','收货时间','付款时间','付款完成时间','供应商结算时间','结算完成时间','取消时间'
]);

$start = 0;
$limit = 20;
while (true) {
	$orders = Q("order[ctime>$time_start]:sort(ctime D)")->limit($start, $limit);
	if (!count($orders)) break;
	$start += $limit;
	foreach ($orders as $order) {
		if (!$order->id || !$order->ctime) continue;
		$vendor   = $order->vendor;
		$customer = $order->customer;
		$row = [
			$order->voucher,
			$vendor->name,
			$customer->name,
			$order->price,
			Order_Model::$status[$order->status].'('.Order_Model::$deliver_status[$order->deliver_status].')',
			date('Y-m-d', $order->ctime),
			getVendorConfirmDuration($order),
			getBothConfirmDuration($order),
			getDeliverDuration($order),
			getReceiveDuration($order),
			getPayDuration($order),
			getTransferredDuration($order),
			getBillingStartDuration($order),
			getBillingEndDuration($order),
			getCancelDuration($order),
		];
		$csv->write($row);
	}
	echo '.';
}
/**
 * 取消时间：订单状态变为【已取消】与订单创建时间的时间差
 */
function getCancelDuration($order) {
	$status = Order_Model::STATUS_CANCELED;
	if ($order->status != $status) return '--';
	$duration = getFromRevision($order, $status);
	if (!$duration) $duration = getFromActivity($order, $status);
	return $duration?:'--';
}

/**
 * 结算完成时间：订单状态变为【已结算】与订单创建时间的时间差
 */
function getBillingEndDuration($order) {
	if (in_array($order->status, [
		Order_Model::STATUS_NEED_VENDOR_APPROVE,
		Order_Model::STATUS_PENDING_APPROVAL,
		Order_Model::STATUS_APPROVED,
		Order_Model::STATUS_REQUESTING,
		Order_Model::STATUS_NEED_CUSTOMER_APPROVE,
		Order_Model::STATUS_PENDING_TRANSFER,
		Order_Model::STATUS_TRANSFERRED,
		Order_Model::STATUS_CANCELED,
		Order_Model::STATUS_PENDING_PAYMENT,
		])) return '--';
	$status = Order_Model::STATUS_PAID;
	$duration = getFromRevision($order, $status);
	if (!$duration) $duration = getFromActivity($order, $status);
	return $duration?:'--';
}

/**
 * 供应商结算时间：订单变为【待结算】与订单创建时间的时间差
 */
function getBillingStartDuration($order) {
	if (in_array($order->status, [
		Order_Model::STATUS_NEED_VENDOR_APPROVE,
		Order_Model::STATUS_PENDING_APPROVAL,
		Order_Model::STATUS_APPROVED,
		Order_Model::STATUS_REQUESTING,
		Order_Model::STATUS_NEED_CUSTOMER_APPROVE,
		Order_Model::STATUS_PENDING_TRANSFER,
		Order_Model::STATUS_TRANSFERRED,
		Order_Model::STATUS_CANCELED,
		])) return '--';
	$status = Order_Model::STATUS_PENDING_PAYMENT;
	$duration = getFromRevision($order, $status);
	if (!$duration) $duration = getFromActivity($order, $status);
	return $duration?:'--';
}

/**
 * 付款完成时间：订单状态变为【已付款】与订单创建时间的时间差
 */

function getTransferredDuration($order) {
	if (in_array($order->status, [
		Order_Model::STATUS_NEED_VENDOR_APPROVE,
		Order_Model::STATUS_PENDING_APPROVAL,
		Order_Model::STATUS_APPROVED,
		Order_Model::STATUS_REQUESTING,
		Order_Model::STATUS_NEED_CUSTOMER_APPROVE,
		Order_Model::STATUS_CANCELED,
		])) return '--';
	if ($order->transferred_date) {
		$duration = round(((int)$order->transferred_date - (int)$order->ctime)/3600, 2);
	}
	$status = Order_Model::STATUS_TRANSFERRED;
	if (!$duration) $duration = getFromRevision($order, $status);
	if (!$duration) $duration = getFromActivity($order, $status);
	return $duration?:'--';

}

/**
 * 付款时间：订单状态变为【付款中】与订单创建时间的时间差
 */
function getPayDuration($order) {
	if (in_array($order->status, [
		Order_Model::STATUS_NEED_VENDOR_APPROVE,
		Order_Model::STATUS_PENDING_APPROVAL,
		Order_Model::STATUS_APPROVED,
		Order_Model::STATUS_REQUESTING,
		Order_Model::STATUS_NEED_CUSTOMER_APPROVE,
		Order_Model::STATUS_CANCELED,
		])) return '--';
	$status = Order_Model::STATUS_PENDING_TRANSFER;
	$duration = getFromRevision($order, $status);
	if (!$duration) $duration = getFromActivity($order, $status);
	return $duration?:'--';
}

/**
 * 收货时间：买方第一次点击【收货】或【全部收货】与订单创建时间的时间差
 */
function getReceiveDuration($order) {
	if ($order->deliver_status == 0) return '--';
	$deliver_status = Order_Model::DELIVER_STATUS_RECEIVED;
	$revision = Q("order_revision[order={$order}][deliver_status={$deliver_status}]:sort(ctime A)")->current();
	if ($revision->id && ($revision->ctime > $order->ctime)) {
		$duration = round(((int)$revision->ctime - (int)$order->ctime)/3600, 2);
	}
	if (!$duration) {
		$keyword = '确认收货';
		$oid = (int)$order->id;
		$comment = Q("comment[object_name=order][object_id=$oid][content*={$keyword}]:sort(id A)")->current();
		if ($comment->id) {
			$duration = round(((int)$comment->ctime - (int)$order->ctime)/3600, 2);
		}
	}
	return $duration?:'--';

}

/**
 * 发货时间：供应商第一次点击【发货】或【全部发货】与订单创建时间的时间差
 */
function getDeliverDuration($order) {
	if ($order->deliver_status == 0) return '--';
	$deliver_status = Order_Model::DELIVER_STATUS_DELIVERED;
	$revision = Q("order_revision[order={$order}][deliver_status={$deliver_status}]:sort(ctime A)")->current();
	if ($revision->id && ($revision->ctime > $order->ctime)) {
		$duration = round(((int)$revision->ctime - (int)$order->ctime)/3600, 2);
	}
	if (!$duration) {
		$keyword = '完成发货';
		$oid = (int)$order->id;
		$comment = Q("comment[object_name=order][object_id=$oid][content*={$keyword}]:sort(id A)")->current();
		if ($comment->id) {
			$duration = round(((int)$comment->ctime - (int)$order->ctime)/3600, 2);
		}
	}
	return $duration?:'--';
}

/**
 * 订单状态变为【待付款】与订单创建时间的时间差
 */
function getBothConfirmDuration($order) {
	$s = Order_Model::STATUS_APPROVED;
	$duration = getFromRevision($order, $s);
	if (!$duration) $duration = getFromActivity($order, $s);
	return $duration?:'--';
}


/**
 * 获得订单第一次供应商确认的时间, 订单状态变为 STATUS_NEED_CUSTOMER_APPROVE 或者 STATUS_APPROVED 为第一次审核时间
 * 首先通过revision查找
 * 如果找不到 通过order_activity 查找
 */
function getVendorConfirmDuration($order) {
	$s1 = Order_Model::STATUS_NEED_CUSTOMER_APPROVE;
	$s2 = Order_Model::STATUS_APPROVED;
	$duration = getFromRevision($order, $s1);
	if (!$duration) $duration = getFromActivity($order, $s1);
	if (!$duration) $duration = getFromRevision($order, $s2);
	if (!$duration) $duration = getFromActivity($order, $s2);
	return $duration?:'--';

}

function getFromRevision($order, $status) {
	$revision = Q("order_revision[order={$order}][status={$status}]:sort(ctime A)")->current();
	if ($revision->id && ($revision->ctime > $order->ctime)) {
		$duration = round(((int)$revision->ctime - (int)$order->ctime)/3600, 2);
	}
	return $duration;
}

function getFromActivity($order, $status) {
	$activity = Q("order_activity[order={$order}][status={$status}]:sort(id A)")->current();
	if ($activity->id && ($activity->time > $order->ctime)) {
		$duration = round(((int)$activity->time - (int)$order->ctime)/3600, 2);
	}
	return $duration;
}

?>