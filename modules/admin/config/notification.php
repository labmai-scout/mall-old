<?php

$config['enable_order_rt_notif'] = TRUE;

// 通知分类
$config['types'] = array(
	'admin' => array(
		'#name' => '商城管理员',
		'#callback' => 'Admin::notif_types_check_admin',
		),
	'vendor' => array(
		'#name' => '供应商',
		'#callback' => 'Admin::notif_types_check_vendor',
		),
	'customer_owner' => array(
		'#name' => '买方负责人',
		'#callback' => 'Admin::notif_types_check_customer_owner',
		),
	'order_purchaser' => array(
		'#name' => '买方成员',
		'#callback' => 'Admin::notif_types_check_order_purchaser',
		),
	);


// types 下不同 type 的 notification 不应有相同 key, 否则互相会影响(xiaopei.li@2012-09-04)

// 管理员
$config['types']['admin']['商城更新每日通知'] = 'daily_notif_for_admin';

// 供应商
$config['types']['vendor']['供应商订单更新每日通知'] = 'order_daily_notif_for_vendor';
$config['types']['vendor']['供应商订单更新每小时通知'] = 'order_hourly_notif_for_vendor';

// 买方负责人
$config['types']['customer_owner']['买方订单更新每日通知'] = 'order_daily_notif_for_customer';
$config['types']['customer_owner']['付款更新每日通知'] = 'transfer_daily_notif_for_customer';

// 下单者
$config['types']['order_purchaser']['提交订单'] = 'order_is_drafted_rt_notif';
$config['types']['order_purchaser']['卖方已确认'] = 'order_need_customer_confirm';
$config['types']['order_purchaser']['买卖双方已确认'] = 'order_is_confirmed_rt_notif';
$config['types']['order_purchaser']['管理员批准订单'] = 'order_is_approved_rt_notif';
$config['types']['order_purchaser']['管理员恢复退货订单'] = 'order_is_return_approved_rt_notif';
$config['types']['order_purchaser']['取消订单'] = 'order_is_canceled_rt_notif';
$config['types']['order_purchaser']['申请退货'] = 'order_is_returning_rt_notif';
$config['types']['order_purchaser']['打回退货'] = 'order_is_reject_to_return_rt_notif';
$config['types']['order_purchaser']['退货恢复'] = 'order_is_recovered_rt_notif';
$config['types']['order_purchaser']['付款成功'] = 'order_is_transferred_rt_notif';
$config['types']['order_purchaser']['付款失败'] = 'order_is_transfer_failed_rt_notif';
$config['types']['order_purchaser']['申购订单产生评论'] = 'order_hourly_notif_for_requester';

$config['order_is_drafted_rt_notif'] = array(
	'description'=>'提交订单的实时通知',
	'title'=>"%mall 购物提醒: 订单 %order 已提交",
	'body'=>"%receiver, 您好：\n\n%customer 对 %vendor 的订单 %order_link 已由 %purchaser 订购, 详情如下:\n\n%order_detail\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%order' => '订单标示',
		'%order_link' => '订单链接',
		'%customer' => '买家名称',
		'%vendor' => '供应商名称',
		'%receiver' => '收件人',
		'%purchaser' => '订单提交者',
		'%order_detail' => '订单详情',
	),
    'send_by'=>array(
        'email' => false,
		'messages' => array('通过消息中心发送', 1),
	),
);

$config['order_need_customer_confirm'] = array(
	'description'=>'订单由卖方修改并确定后, 对买方的实时通知',
	'title'=>"%mall 购物提醒: 订单 %order 已由供应商确认",
	'body'=>"%receiver, 您好：\n\n%customer 对 %vendor 的订单 %order_link 已由供应商修改, 现需您确认. 订单详情如下:\n\n%order_detail\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%order' => '订单标示',
		'%order_link' => '订单链接',
		'%customer' => '买家名称',
		'%vendor' => '供应商名称',
		'%receiver' => '收件人',
		'%purchaser' => '订单提交者',
		'%order_detail' => '订单详情',
	),
	'send_by'=>array(
        'email' => false,
		'messages' => array('通过消息中心发送', 1),
	),
);

$config['order_is_confirmed_rt_notif'] = array(
	'description'=>'双方确认订单的实时通知',
	'title'=>"%mall 购物提醒: 订单 %order 已由买卖双方确认",
	'body'=>"%receiver, 您好：\n\n%customer 对 %vendor 的订单 %order_link 已经买卖双方确认, 详情如下:\n\n%order_detail\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%order' => '订单标示',
		'%order_link' => '订单链接',
		'%customer' => '买家名称',
		'%vendor' => '供应商名称',
		'%receiver' => '收件人',
		'%purchaser' => '订单提交者',
		'%order_detail' => '订单详情',
	),
	'send_by'=>array(
        'email' => false,
		'messages' => array('通过消息中心发送', 1),
	),
);

$config['order_is_approved_rt_notif'] = array(
	'description'=>'管理员批准订单的实时通知',
	'title'=>"%mall 购物提醒: 订单 %order 已由管理员批准",
	'body'=>"%receiver, 您好：\n\n%customer 对 %vendor 的订单 %order_link 已由管理员批准, 详情如下:\n\n%order_detail\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%order' => '订单标示',
		'%order_link' => '订单链接',
		'%customer' => '买家名称',
		'%vendor' => '供应商名称',
		'%receiver' => '收件人',
		'%purchaser' => '订单提交者',
		'%order_detail' => '订单详情',
	),
	'send_by'=>array(
        'email' => false,
		'messages' => array('通过消息中心发送', 1),
	),
);

$config['order_is_return_approved_rt_notif'] = array(
	'description'=>'管理员批准退货恢复订单的实时通知',
	'title'=>"%mall 购物提醒: 订单 %order 的退货请求已由供应商拒绝, 并由管理员批准",
	'body'=>"%receiver, 您好：\n\n订单 %order_link 退货请求已由供应商拒绝, 并由管理员批准, 详情如下:\n\n%order_detail\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%order' => '订单标示',
		'%order_link' => '订单链接',
		'%customer' => '买家名称',
		'%vendor' => '供应商名称',
		'%receiver' => '收件人',
		'%purchaser' => '订单提交者',
		'%order_detail' => '订单详情',
	),
	'send_by'=>array(
        'email' => false,
		'messages' => array('通过消息中心发送', 1),
	),
);

$config['order_is_canceled_rt_notif'] = array(
	'description'=>'取消订单的实时通知',
	'title'=>"%mall 购物提醒: 订单 %order 已被取消",
	'body'=>"%receiver, 您好：\n\n%customer 对 %vendor 的订单 %order_link 已被 %canceler 取消, 详情如下:\n\n%order_detail\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%order' => '订单标示',
		'%order_link' => '订单链接',
		'%customer' => '买家名称',
		'%vendor' => '供应商名称',
		'%receiver' => '收件人',
		'%purchaser' => '订单提交者',
		'%order_detail' => '订单详情',
		'%canceler' => '订单取消者',
	),
	'send_by'=>array(
        'email' => false,
		'messages' => array('通过消息中心发送', 1),
	),
);

$config['order_is_returning_rt_notif'] = array(
	'description'=>'订单申请退货的实时通知',
	'title'=>"%mall 购物提醒: 订单 %order 已申请退货",
	'body'=>"%receiver, 您好：\n\n%customer 对 %vendor 的订单 %order_link 已被 %returner 申请退货, 详情如下:\n\n%order_detail\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%order' => '订单标示',
		'%order_link' => '订单链接',
		'%customer' => '买家名称',
		'%vendor' => '供应商名称',
		'%receiver' => '收件人',
		'%purchaser' => '订单提交者',
		'%order_detail' => '订单详情',
		'%returner' => '订单申请退货者',
	),
	'send_by'=>array(
        'email' => false,
		'messages' => array('通过消息中心发送', 1),
	),
);

$config['order_is_reject_to_return_rt_notif'] = array(
	'description'=>'订单打回退货的实时通知',
	'title'=>"%mall 购物提醒: 订单%order的退货申请已被拒绝",
	'body'=>"%receiver, 您好：\n\n订单 %order_link的退货申请已被 %returner拒绝, 详情如下:\n\n%order_detail\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%order' => '订单标示',
		'%order_link' => '订单链接',
		'%customer' => '买家名称',
		'%vendor' => '供应商名称',
		'%receiver' => '收件人',
		'%purchaser' => '订单提交者',
		'%order_detail' => '订单详情',
		'%returner' => '订单申请退货者',
	),
	'send_by'=>array(
        'email' => false,
		'messages' => array('通过消息中心发送', 1),
	),
);

$config['order_is_recovered_rt_notif'] = array(
	'description'=>'订单从退货状态被供应商拒绝的实时通知',
	'title'=>"%mall 购物提醒: 订单 %order 已被供应商拒绝退货",
	'body'=>"%receiver, 您好：\n\n%customer 对 %vendor 的订单 %order_link, 供应商拒绝退货. 详情如下:\n\n%order_detail\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%order' => '订单标示',
		'%order_link' => '订单链接',
		'%customer' => '买家名称',
		'%vendor' => '供应商名称',
		'%receiver' => '收件人',
		'%purchaser' => '订单提交者',
		'%order_detail' => '订单详情',
	),
	'send_by'=>array(
        'email' => false,
		'messages' => array('通过消息中心发送', 1),
	),
);

$config['order_is_transferred_rt_notif'] = array(
	'description'=>'订单付款成功的实时通知',
	'title'=>"%mall 购物提醒: 订单 %order 已付款成功",
	'body'=>"%receiver, 您好：\n\n%customer 对 %vendor 的订单 %order_link 已付款成功, 详情如下:\n\n%order_detail\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%order' => '订单标示',
		'%order_link' => '订单链接',
		'%customer' => '买家名称',
		'%vendor' => '供应商名称',
		'%receiver' => '收件人',
		'%purchaser' => '订单提交者',
		'%order_detail' => '订单详情',
	),
	'send_by'=>array(
        'email' => false,
		'messages' => array('通过消息中心发送', 1),
	),
);

$config['order_is_transfer_failed_rt_notif'] = array(
	'description'=>'订单付款失败的实时通知',
	'title'=>"%mall 购物提醒: 订单 %order 付款失败",
	'body'=>"%receiver, 您好：\n\n%customer 对 %vendor 的订单 %order_link 付款失败, 详情如下:\n\n%order_detail\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%order' => '订单标示',
		'%order_link' => '订单链接',
		'%customer' => '买家名称',
		'%vendor' => '供应商名称',
		'%receiver' => '收件人',
		'%purchaser' => '订单提交者',
		'%order_detail' => '订单详情',
	),
	'send_by'=>array(
        'email' => false,
		'messages' => array('通过消息中心发送', 1),
	),
);

// 每日提醒
$config['order_daily_notif_for_customer'] = array(
	'description'=>'买家每天收到的订单变动通知',
	'title'=>"您在 %mall 今日更新的订单",
	'body'=>"%customer, 您好：\n\n今日有以下订单状态更新或增加了评论:\n\n%orders\n\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%customer' => '买家名称',
		'%orders' => '订单列表',
		'%link' => '订单列表链接',
	),
	'send_by'=>array(
        'email' => array('通过电子邮件发送', 1),
        'messages' => false,
	),
);

$config['order_daily_notif_for_vendor'] = array(
	'description'=>'供应商每天收到的订单变动通知',
	'title'=>"您在 %mall 今日更新的订单",
	'body'=>"%vendor, 您好：\n\n今日有以下订单状态更新或增加了评论:\n\n%orders\n\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%vendor'=> '供应商名称',
		'%orders'=> '订单列表',
		'%link' => '订单列表链接',
	),
	'send_by'=>array(
		'email' => array('通过电子邮件发送', 1),
        'messages' => false,
	),
);

$config['order_hourly_notif_for_vendor'] = array(
	'description'=>'供应商每小时收到的订单变动通知',
	'title'=>"您在 %mall 的订单 %time 有更新",
	'body'=>"%vendor, 您好：\n\n%time有以下订单状态更新或增加了评论:\n\n%orders\n\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%vendor'=> '供应商名称',
        '%orders'=> '订单列表',
        '%time'=> '更新时间'
	),
	'send_by'=>array(
		'email' => array('通过电子邮件发送', 1),
        'messages' => false,
	),
);

$config['order_hourly_notif_for_requester'] = array(
	'description'=>'申购人每小时收到的订单变动通知',
	'title'=>"您在 %mall 的订单 %time 有更新",
	'body'=>"%requester, 您好：\n\n%time有以下订单状态更新或增加了评论:\n\n%orders\n\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%requester'=> '申购人名称',
		'%orders'=> '订单列表',
		'%time' => '更新时间',
	),
	'send_by'=>array(
		'email' => array('通过电子邮件发送', 1),
        'messages' => false,
	),
);

$config['transfer_daily_notif_for_customer'] = array(
	'description'=>'买家每天收到的付款单相关通知',
	'title'=>"%mall 今日更新的付款单",
	'body'=>"%customer, 您好：\n\n今日有以下付款单状态更新:\n\n%statements\n\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%customer' => '买家名称',
		'%statements' => '付款单列表',
		'%link' => '付款单列表链接',
	),
	'send_by'=>array(
		'email' => array('通过电子邮件发送', 1),
        'messages' => false,
	),
);

$config['daily_notif_for_admin'] = array(
	'description'=>'管理员收到的每天更新',
	'title'=>"%mall 今日的更新",
	'body'=>"商城管理员, 您好：\n\n%mall 今日有以下更新:\n\n%update_content\n请登录查看.",
	'strtr'=>array(
		'%mall' => '商城名称',
		'%update_content' => '更新内容',
	),
	'send_by'=>array(
		'email' => array('通过电子邮件发送', 1),
		'messages' => array('通过消息中心发送', 1),
	),
);

$config['cancel_order.user'] = array(
    'description'=> '订单申请被取消消息提醒',
    'title'=> '%user 您的订单被取消',
    'body'=> "%user, 您好! \n 您的订单因以下原因被%admin取消: \n%reason",
    'strtr'=>array(
        '%user'=> '订单申请人',
        '%admin'=> '管理员名称',
        '%reason'=> '取消理由'
    ),
    'send_by'=>array(
        'email' => false,
        'messages'=>array('通过消息中心发送', 1)
    )
);
