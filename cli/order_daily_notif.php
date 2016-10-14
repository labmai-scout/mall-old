<?php
/*
  检查 24 小时内更新过的订单, 按 供货商/买方 组织后, 对 供货商/买方 通知
  应每日运行
  (xiaopei.li@2012-05-22)
*/

require 'base.php';

$last_day = Date::time() - 86400; // 最近一天

$customer_orders = array();

$order_format = "%no (%price)";
$modified_orders = Q("comment[ctime>={$last_day}]<object order");
foreach ($modified_orders as $order) {
	$customer_orders[$order->customer->id][] = $order;
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
    global $last_day;
    $comments = Q("comment[object=$order][ctime>={$last_day}]:sort(ctime D)");

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
$lab_order_url = Config::get('customer.order_url');
foreach ($customer_orders as $customer_id => $orders) {
	$customer = O('customer', $customer_id);

	$order_links = array();
	foreach ($orders as $order) {
		$order_links[] = getOrderDetail($order, $lab_order_url.$order->id, Order_Model::$customer_status[$order->status]);
	}

	Notification::send('notification.order_daily_notif_for_customer', $customer->owner, array(
						   '%mall' => H(Config::get('page.title_default')),
						   '%customer' => H($customer->name),
						   '%orders' => join($br, $order_links),
						   ));
}
