<?php
/**
 * @file order_hourly_notif.php
 * @brief 检查1小时内更新过的订单, 按供货商/申购人组织后, 对供货商/申购人通知
 * @author Hongjie Zhu <pihizi@msn.com>
 * @version 0.1.0
 * @date 2015-01-28
 */
require 'base.php';

$now = strtotime(date('Y-m-d H:00:00', Date::time()));
$last_hour = strtotime('-1 hour', $now);

$time = date('H:00', $now);
$before = date('Y-m-d H:00', $last_hour);
$time = "{$before} ~ {$time}";

$vendor_orders = [];
$requester_orders = [];

$modified_orders = Q("comment[ctime>={$last_hour}][ctime<={$now}]<object order");
foreach ($modified_orders as $order) {
    // TODO 供应商不应该收到申购中,已取消(申购中被取消)
    $isConfirmed = $order->customer_approved;
    if ($isConfirmed) {
        $vendor_orders[$order->vendor->id][] = $order;
    }
    $requester_orders[$order->purchaser->id][] = $order;
}

function getOrderDetail($order, $url, $status)
{
	$br = '<br/>';
	$space = str_repeat('&#160;', 4);
    $li_format = "%url %price (%status){$br}%products{$br}{$br}{$space}·评论{$br}%comments";

    $url = URI::anchor($url, H('#'.$order->order_no));
    $price = $order->price>= 0 ? Number::currency($order->price) : '待询价';

    $li = strtr($li_format, [
        '%url' => $url,
        '%price' => $price,
        '%status'=> $status,
        '%comments' => getComments($order, str_repeat($space, 2), $br),
        '%products'=> getProducts($order, str_repeat($space, 2), $br),
    ]);

    return $li;
}

function getProducts($order, $space, $br)
{
    $items = Q("order_item[order=$order]");

    $result = [];
    foreach ($items as $item) {
        $result[] = "[{$item->product->name}] x {$item->quantity}";
    }

	$space = str_repeat('&#160;', 8);
    return $space . implode("{$br}{$space}", $result);
}

function getComments($order, $space, $br)
{
    global $last_hour;
    global $now;
    $comments = Q("comment[object=$order][ctime>={$last_hour}][ctime<={$now}]:sort(ctime D)");

    $result = [];
    foreach ($comments as $comment) {
		$li_format = "· %author (%time): %content";
        $result[] = strtr($li_format, [
            '%author' => $comment->is_log ? ($comment->author->name ?: '系统') : '系统',
            '%time' => date('H:i', $comment->ctime),
            '%content' => H(str_replace("\n", ' ', trim($comment->content))),
        ]);
    }

    return $space . implode("{$br}{$space}", $result);
}

$br = str_repeat('<br/>', 2);
foreach ($vendor_orders as $vendor_id => $orders) {
    $vendor = O('vendor', $vendor_id);

    $lis = [];
    foreach ($orders as $order) {
        $url = $order->url(null, null, null, 'vendor_view');
        $lis[] = getOrderDetail($order, $url, Order_Model::$status[$order->status]);
    }

    $owner = $vendor->owner;
    $owner->email = $vendor->email ?: $owner->email;
    Notification::send('notification.order_hourly_notif_for_vendor',
        $owner,
        [
            '%mall' => H(Config::get('page.title_default')),
            '%vendor' => H($vendor->name),
            '%orders' => implode($br, $lis),
            '%time' => $time,
        ]
    );
}

$prefix = Config::get('system.script_url_for_lab_orders');
$lab_order_url = Config::get('customer.order_url');
foreach ($requester_orders as $requester_id => $orders) {
    $requester = O('user', $requester_id);

    $lis = [];
    foreach ($orders as $order) {
        $url = $prefix ? rtrim($prefix, '/').'/'.$order->voucher : $lab_order_url.$order->id;
        $lis[] = getOrderDetail($order, $url, Order_Model::$customer_status[$order->status]);
    }

    Notification::send('notification.order_hourly_notif_for_requester',
        $requester,
        [
            '%mall' => H(Config::get('page.title_default')),
            '%requester' => H($requester->name),
            '%orders' => implode($br, $lis),
            '%time' => $time,
        ]
    );
}
