<?php
  /*
	每日对管理员的通知
	(xiaopei.li@2012-07-12)
  */

require 'base.php';

/*
   所有提供更新内容的函数, 都应符合以下接口:
   function get_xxx_update_content($since, $max) {
     // generate update content
     return $xxx_update_content;
   }
*/


/****** main ******/
$last_day = Date::time() - 86400;
$n_max_show = 1;

$update_content = "";

$follows = array(
	'order', 'vendor_product', 'vendor',
	);

foreach ($follows as $follow) {
	$update_content .= call_user_func("get_{$follow}_update_content", $last_day, $n_max_show);
}

if ($update_content) {

	$admins = Q('role[name=商城管理员] user');

	Notification::send('notification.daily_notif_for_admin', $admins, array(
						   '%mall' => H(Config::get('page.title_default')),
						   '%update_content' => $update_content,
						   ));
}


/****** functions ******/

// 订单
function get_order_update_content($since, $max = 10) {

	$order_format = "%no (%price)";

	$modified_orders = Q("comment[ctime>={$since}]<object order");
	$total = $modified_orders->total_count();

	if ($total) {

		$update_content = "更新订单:\n";

		if ($total > $max) {
			$modified_orders = $modified_orders->limit($max);
			$update_content .= "(共有 $total 条, 仅列出 $max 条)\n";
		}

        foreach ($modified_orders as $order) {
            $update_content .= strtr($order_format, array(
                '%no' => URI::anchor($order->url(NULL, NULL, NULL, 'admin_view'), 
                    H('#' . $order->voucher)),
                '%price' => $order->price >= 0 ? Number::currency($order->price) : '待询价',
            ));
            $update_content .= "\n\n";
        }

		return $update_content;
	}
}

// 商品申请上架
function get_vendor_product_update_content($since, $max = 10) {

	$vendor_product_format = "%name (%vendor)";

	$newly_published_vendor_products = Q("vendor_product[publish_date>={$since}][approve_date<=0]");

	$total = $newly_published_vendor_products->total_count();

	if ($total) {

		$update_content = "新申请上架的商品:\n";

		if ($total > $max) {
			$newly_published_vendor_products = $newly_published_vendor_products->limit($max);
			$update_content .= "(共有 $total 条, 仅列出 $max 条)\n";
		}

		foreach ($newly_published_vendor_products as $vendor_product) {
			$update_content .= strtr($vendor_product_format, array(
														'%name' => URI::anchor($vendor_product->url(NULL, NULL, NULL, 'admin_view'),
																			   H($vendor_product->name)),
														'%vendor' => H($vendor_product->vendor->name),
														));
			$update_content .= "\n\n";
		}

		return $update_content;
	}
}

// 供货商申请上架
function get_vendor_update_content($since, $max = 10) {

	$vendor_format = "%name";

	$newly_published_vendors = Q("vendor[publish_date>={$since}][approve_date<=0]");

	$total = $newly_published_vendors->total_count();

	if ($total) {

		$update_content = "新申请上架的商家:\n";

		if ($total > $max) {
			$newly_published_vendors = $newly_published_vendors->limit($max);
			$update_content .= "(共有 $total 条, 仅列出 $max 条)\n";
		}

		foreach ($newly_published_vendors as $vendor) {
			$update_content .= strtr($vendor_format, array(
												'%name' => URI::anchor($vendor->url(NULL, NULL, NULL, 'admin_view'), H($vendor->name)),
												));
			$update_content .= "\n\n";
		}

		return $update_content;
	}
}

