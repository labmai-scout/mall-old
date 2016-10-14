<?php

/*用户数据*/
$config['user'] = array(
	'fields' => array(
		#用户账号
		'token' => array('type'=>'varchar(100)', 'null'=>TRUE),
		#用户姓名
		'name' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		#购物账号
		// 因为customer支持多个, 所以这里隐藏掉这个customer设置
		// 'customer' => array('type'=>'object', 'oname'=>'customer'),
		#未激活类型
		'email'=>array('type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''),
		'hidden'=>array('type'=>'tinyint', 'null'=>FALSE, 'default'=>0),
		'name_abbr'=>array('type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''),
		'phone'=>array('type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''),
		'address'=>array('type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''),
		'group' => array('type'=>'object', 'oname'=>'tag'),
		'member_type' => array('type'=>'int', 'null'=>TRUE),
		'creator' => array('type'=>'object', 'oname'=>'user'),
		'auditor' => array('type' => 'object', 'oname' => 'user'),
		'atime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'mtime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'org_code' => array('type'=> 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'ref_no' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'is_bind' => array('type' => 'int', 'null' => FALSE, 'default' => 0),
		'lims_user' => array('type' => 'int', 'null' => FALSE, 'default' => 0),
		'gapper_user' => array('type' => 'int', 'null' => FALSE, 'default' => 0),
	),
	'indexes' => array(
		'token' => array('type'=>'unique', 'fields'=>array('token')),
		// 'customer' => array('fields'=>array('customer')),
		'name' => array('fields'=>array('name')),
		'ctime' => array('fields'=>array('ctime')),
		'mtime' => array('fields'=>array('mtime')),
		'atime' => array('fields'=>array('atime')),
		'org_code' => array('fields' => array('org_code')),
		'ref_no' => array('fields' => array('ref_no')),
		'is_bind' => array('fields'=>array('is_bind')),
		'lims_user' => array('fields'=>array('lims_user')),
		'gapper_user' => array('fields'=>array('gapper_user'))
	)
);

$config['gapper_fallback_user'] = array(
	'fields' => array(
        'user' => array('type'=>'object', 'oname'=>'user'),
		'token' => array('type'=>'varchar(100)', 'null'=>FALSE, 'default'=>''),
    ),
	'indexes' => array(
		'user' => array('fields' => array('user'), 'type'=>'unique'),
		'token' => array('fields' => array('token'), 'type'=>'unique'),
	)
);

$config['user_auth'] = array(
    'fields'=> array(
        'user'=> array('type'=> 'object', 'oname'=> 'user'),
        'expire_time'=> array('type'=> 'int', 'null'=> FALSE, 'default'=> 0),
        'access_token'=> array('type'=> 'varchar(50)', 'null'=> FALSE)
    ),
    'indexes'=> array(
        'user'=> array('fields'=> array('user'))
    )
);

/*购物账号*/
$config['customer'] = array(
	'fields' => array(
		'name' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		'email'=>array('type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''),
		'description' => array('type'=>'text', 'null'=>FALSE, 'default'=>''),
		'account_no' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''), // 工资号
		'owner' => array('type'=>'object', 'oname'=>'user'),
		'group' => array('type'=>'object', 'oname'=>'tag'),
		'gapper_group' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'unable_upgrade' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'uuid' => array('type'=>'varchar(50)', 'null'=>TRUE, 'default'=>''),
        'bind_status'=> array('type'=> 'tinyint', 'null'=> FALSE, 'default'=> 0)
	),
	'indexes' => array(
		// 'owner' => array('type'=>'unique', 'fields'=>array('owner')),
		'name' => array('fields'=>array('name')),
		'ctime' => array('fields'=>array('ctime')),
		'unable_upgrade' => array('fields'=>array('unable_upgrade')),
		'uuid' => array('fields'=>array('uuid')),
        'gapper_group' => array('fields'=>array('gapper_group')),
	),
);

/*收货地址*/
$config['deliver_address'] = array(
	'fields' => array(
		#购物账号
		'customer' => array('type' => 'object', 'oname' => 'customer'),
		#收货地址
		'address' => array('type' => 'varchar(255)', 'null'=>FALSE, 'default' => ''),
		#联系方式
		'phone' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'email'	=> array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'postcode' => array('type' => 'varchar(20)', 'null' => FALSE, 'default' => ''),
	),
	'indexes' => array(
		'customer' => array('fields'=>array('customer')),
	)
);

/*购物车*/
$config['cart'] = array(
	'fields' => array(
		// 购物账号
		'user' => array('type'=>'object', 'oname'=>'user'),
	),
	'indexes' => array(
		'user' => array('type'=>'unique', 'fields'=>array('user')),
	)
);

/*购物单个清单*/
$config['cart_item'] = array(
	'fields' => array(
		#购物车
		'cart' => array('type'=>'object', 'oname'=>'cart'),
		#购买的产品
		'product' => array('type'=>'object', 'oname'=>'product'),
		'version' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		#购买的产品的数量
		'quantity' => array('type'=>'int', 'null'=>FALSE, 'default'=>1),
        'requester'=> array('type'=> 'object', 'oname'=> 'user'),
        #购买商品的版本
		'version'=> array('type'=>'int', 'null'=>FALSE, 'default'=>1),
	),
	'indexes' => array(
		'product' => array('type'=>'unique', 'fields'=>array('cart', 'product')),
		'quantity' => array('fields'=>array('quantity')),
		'version' => array('fields'=>array('version')),
	)
);

// 结算夹
$config['billing_bucket'] = array(
	'fields' => array(
		// 购物账号
		'vendor' => array('type'=>'object', 'oname'=>'vendor'),
	),
	'indexes' => array(
		'vendor' => array('type'=>'unique', 'fields'=>array('vendor')),
	)
);

// 付款夹
$config['transfer_bucket'] = array(
	'fields' => array(
		'customer' => array('type'=>'object', 'oname'=>'customer'),
	),
	'indexes' => array(
		'customer' => array('type'=>'unique', 'fields'=>array('customer')),
	)
);

/*订单*/
$config['order'] = array(
	'fields' => array(
		#上级订单
		'parent' => array('type'=>'object', 'oname'=>'order'),
		#供应商账号
		'vendor' => array('type'=>'object', 'oname'=>'vendor'),
		#购物账号
		'customer' => array('type'=>'object', 'oname'=>'customer'),
		#备注信息
		'purchase_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'purchaser' => array('type'=>'object', 'oname'=>'user'),
		'status'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		// 付款成功日期
		'transferred_date' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'deliver_date' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'description' => array('type'=>'text', 'null'=>FALSE, 'default'=>''),
		'ctime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'mtime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'invoice_title' => array('type' => 'varchar(255)', 'null'=>FALSE, 'default' => ''),
		#收货地址
		'address' => array('type' => 'varchar(255)', 'null'=>FALSE, 'default' => ''),
		#联系方式
		'phone' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'email'	=> array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'postcode' => array('type' => 'varchar(20)', 'null' => FALSE, 'default' => ''),
		//选定经费
		'grant' => array('type'=>'object', 'oname'=>'customer_grant'),
		//取消原因
		'cancel_reason' => array('type'=>'varchar(150)', 'null'=>FALSE, 'default' => ''),
		//总体价格
		'price'=> array('type'=>'double', 'null'=>FALSE, 'default'=>0),
		// 是否可取消
		'cannot_cancel' => array('type' => 'tinyint', 'null' => FALSE, 'default' => 0),
		// 是否完成递送
		//将is_delivered和is_received合并为一个状态deliver_status.0:待发货,1,待收货，2,已收货
		'deliver_status' => array('type' => 'tinyint', 'null'=>FALSE, 'default'=>0),
		'payment_status' => array('type' => 'tinyint', 'null'=>FALSE, 'default'=>0),
        'order_no' => array('type'=>'varchar(40)', 'null'=>FALSE, 'default' => ''),
		'hash' => array('type'=>'varchar(40)', 'null'=>FALSE, 'default' => ''),
        // voucher与order_no作用应该一样，暂时只处理voucher
		'voucher' => array('type'=>'varchar(40)', 'null'=>FALSE, 'default' => ''),
		'operator' => array('type'=>'object', 'oname'=>'user'),
		'revision_hashs' => array('type'=>'json', 'null'=>FALSE, 'default'=>''),
		//用户审核标记 默认填充0 ，被买方管理员审核后变更状态为1  by sunxu 2015-04-09
		'customer_approved' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		// 附加属性包括:
		// 是否正在编辑
		// 'is_editing' => array('type' => 'tinyint', 'null'=>FALSE, 'default'=>0),
		// 版本号, 初始为 NULL, 商家每确认过一次, 版本号 + 1
		// 'version' => array('type' => 'int', 'null' => TRUE),
		'label'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
	),
	'indexes' => array(
		'order_no' => array('fields' => array('order_no'), 'type'=>'unique'),
		'voucher' => array('fields' => array('voucher'), 'type'=>'unique'),
		'parent' => array('fields'=>array('parent')),
		'status' => array('fields'=>array('status')),
		'payment_status' => array('fields'=>array('payment_status')),
		//用户审核标记索引  by sunxu 2015-04-09
		'customer_approved' => array('fields'=>array('customer_approved')),
		'purchase_date' => array('fields'=>array('purchase_date')),
		'purchaser' => array('fields'=>array('purchaser')),
		'vendor' => array('fields'=>array('vendor')),
		'customer' => array('fields'=>array('customer')),
		'ctime' => array('fields'=>array('ctime')),
		'mtime' => array('fields'=>array('mtime')),
		'operator' => array('fields'=>array('operator')),
	)
);

$config['order_revision'] = array(
	'fields' => array(
		#供应商账号
		'vendor' => array('type'=>'object', 'oname'=>'vendor'),
		#购物账号
		'customer' => array('type'=>'object', 'oname'=>'customer'),
		#备注信息
		'requester' => array('type'=>'object', 'oname'=>'user'),
		'request_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		// 付款成功日期
		'transferred_date' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'deliver_date' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'status'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'note' => array('type' => 'varchar(255)', 'null'=>FALSE, 'default' => ''),
		'ctime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'invoice_title' => array('type' => 'varchar(255)', 'null'=>FALSE, 'default' => ''),
		#收货地址
		'address' => array('type' => 'varchar(255)', 'null'=>FALSE, 'default' => ''),
		#联系方式
		'phone' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'email'	=> array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'postcode' => array('type' => 'varchar(20)', 'null' => FALSE, 'default' => ''),
		//总体价格
		'price'=> array('type'=>'double', 'null'=>FALSE, 'default'=>0),
		// 是否完成递送
		//将is_delivered和is_received合并为一个状态deliver_status.0:待发货,1,待收货，2,已收货
		'deliver_status' => array('type' => 'tinyint', 'null'=>FALSE, 'default'=>0),
		'payment_status' => array('type' => 'tinyint', 'null'=>FALSE, 'default'=>0),
		'voucher' => array('type'=>'varchar(40)', 'null'=>FALSE, 'default' => ''),
		'hash' => array('type'=>'varchar(40)', 'null'=>FALSE, 'default' => ''),
        'items' => array('type'=>'text', 'null'=>FALSE, 'default' => ''),
		'binded_inventory' => array('type'=>'text', 'null'=>FALSE, 'default' => ''),
		'operator' => array('type'=>'object', 'oname'=>'user'),
		'order' => array('type'=>'object', 'oname'=>'order'),
	),
	'indexes' => array(
		'voucher' => array('fields' => array('voucher')),
		'status' => array('fields'=>array('status')),
		'payment_status' => array('fields'=>array('payment_status')),
		'request_date' => array('fields'=>array('request_date')),
		'requester' => array('fields'=>array('requester')),
		'vendor' => array('fields'=>array('vendor')),
		'customer' => array('fields'=>array('customer')),
		'ctime' => array('fields'=>array('ctime')),
		'hash' => array('fields'=>array('hash')),
		'operator' => array('fields'=>array('operator')),
		'order' => array('fields'=>array('order')),
	)
);

// 递送记录
$config['deliver_record'] = array(
	'fields' => array(
		// 所属订单
		'order' => array('type' => 'object', 'oname' => 'order'),
		// vendor 发货人
		'request_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'requester' => array('type'=>'object', 'oname'=>'user'),
		// customer 收货人
		'receive_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'receiver' => array('type'=>'object', 'oname'=>'user'),
		// 此次是否全部送出
		'is_all' => array('type' => 'tinyint', 'null'=>FALSE, 'default'=>0),
		// 备注, 虚属性, 可填数量等信息
		// 'note' => array('type' => 'text'),
	),
	'indexes' => array(
		'order' => array('fields' => array('order')),
	)
);


/*订单单个详细内容*/
$config['order_item'] = array(
	'fields' => array(
		'order' => array('type'=>'object', 'oname'=>'order'),
		'product' => array('type'=>'object', 'oname'=>'product'),
		'version' => array('type'=>'int', 'null'=>FALSE, 'default'=>1),
		'quantity'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'unit_price'=> array('type'=>'double', 'null'=>FALSE, 'default'=>0),
		'price'=> array('type'=>'double', 'null'=>FALSE, 'default'=>0),
		'request_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'requester' => array('type'=>'object', 'oname'=>'user'),
		'receive_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'receiver' => array('type'=>'object', 'oname'=>'user'),
		'ctime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'deliver_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
        'deliver_status'=> array('type'=> 'tinyint', 'null'=> FALSE, 'default'=> 0),
        'status'=> array('type'=> 'tinyint', 'null'=> FALSE, 'default'=> 0),
	),
	'indexes' => array(
		'order' => array('fields'=>array('order')),
		'product' => array('fields'=>array('product')),
	)
);

// 结算单
$config['billing_statement'] = array(
	'fields' => array(
		'vendor' => array('type'=>'object', 'oname'=>'vendor'),
		'balance'=> array('type'=>'double', 'null'=>FALSE, 'default'=>0),
		'approver' => array('type'=>'object', 'oname'=>'user'),
		'approve_date' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'status' => array('type'=>'tinyint', 'null'=>FALSE, 'default'=>0),
		'ctime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'reserv_no' => array('type' => 'varchar(50)', 'default' => ''),
		'payment_voucher'=> array('type'=> 'varchar(20)', 'null'=> FALSE, 'default'=> '')
	),
	'indexes' => array(
		'vendor' => array('fields'=>array('vendor')),
		'payment_voucher'=> array('fields'=> array('payment_voucher'))
	)
);

// 付款单
$config['transfer_statement'] = array(
	'fields' => array(
		'customer' => array('type'=>'object', 'oname'=>'customer'),
		'balance'=> array('type'=>'double', 'null'=>FALSE, 'default'=>0),
		'approver' => array('type'=>'object', 'oname'=>'user'),
		'approve_date' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		// 付款成功日期
		'transferred_date' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'status' => array('type'=>'tinyint', 'null'=>FALSE, 'default'=>0),
		'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'mtime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
        'voucher'=> array('type'=> 'varchar(20)', 'null'=> FALSE, 'default'=> ''), //预约单号
	),
	'indexes' => array(
		'customer' => array('fields'=>array('customer')),
		'ctime' => array('fields'=>array('ctime')),
		'mtime' => array('fields'=>array('mtime')),
        'voucher'=> array('fields'=> array('voucher'))
	)
);

/*供应商账号*/
$config['vendor'] = array(
	'fields' => array(
		'name' => array('type'=>'varchar(150)', 'null'=>FALSE), // 单位名称
		'short_name' => array('type'=>'varchar(50)', 'null'=>FALSE), // 单位短名称
		'short_abbr' => array('type'=>'varchar(50)', 'null'=>FALSE), // 单位短名称拼音
		'creator' => array('type'=>'object', 'oname'=>'user'), // 创建人 (同"厂商信息管理表"的"申请")
		'create_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 创建日期
		'owner' => array('type'=>'object', 'oname'=>'user'), // 管理员
		// 类似 product, 用户注册 vendor 后, 信息完善后, 先 publish , 再由管理员 approve(xiaopei.li@2012-03-19)
		'publisher' => array('type'=>'object', 'oname'=>'user'), // 发布者
		'publish_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 发布时间
		'approver' => array('type'=>'object', 'oname'=>'user'), // 审核人(同"厂商信息管理表"的"是否通过资质审核")
		'approve_date' => array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 审核日期
		'expire_date' => array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 过期日期
		'gapper_group' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'allowed_categories'=> array('type'=>'text', 'null'=>FALSE, 'default'=>''), // 允许经营类型
		'email'=>array('type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''),		// 公司邮件
		'product_count' => array('type'=>'int', 'null'=>false, 'default'=>0),	// 上架产品总数
		/*
		//其他附加属性
		'alt_name',					  // 企业曾用名
		'owner_name',				  // 企业法人姓名
		'manager_name',				  // 总经理姓名
		'manager_phone',			  // 总经理电话
		'contact_name',				  // 投标联系人姓名
		'contact_phone',			  // 投标联系人电话
		'phone',					  // 公司电话
		'fax',						  // 公司传真
		'address',					  // 公司地址
		'postcode',					  // 邮编
		'homepage',					  // 公司网址
		'license_no',				  // 营业执照注册号
		'license_valid_date',		  // 执照年检日期
		'license_last_valid_date',	  // 上一次系统年检日期
		'establish_date' => array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 企业成立日期
		'operation_due' => array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 企业经营期限至
		'capital' => array('type' => 'double', 'null' => TRUE), // 注册资金
		'nemployees' => array('type' => 'tinyint', 'null' => TRUE), // 员工人数(需结合表示人数范围的常量数组使用)
		'scope' => array('type'=>'text', 'null'=>FALSE, 'default'=>NULL), // 经营范围
		'description' => array('type'=>'text', 'null'=>FALSE, 'default'=>NULL), // 公司简介
		'bank_name',	//开户行
		'bank_account',	//开户行账号
		 */
		'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'mtime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'agreement_version' => array('type'=>'varchar(50)', 'null'=>TRUE),
		'agreement_time' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
	),
	'indexes' => array(
		'name' => array('fields'=>array('name')),
		'creator' => array('fields'=>array('creator')),
		'create_date' => array('fields'=>array('create_date')),
		'approver' => array('fields'=>array('approver')),
		'approve_date' => array('fields'=>array('approve_date')),
		'gapper_group' => array('fields'=>array('gapper_group')),
		'ctime'=>array('fields'=>array('ctime')),
		'mtime'=>array('fields'=>array('mtime')),
		'expire_date' => array('fields' => array('expire_date')),
		'product_count' => array('fields' => array('product_count')),
		'agreement_version' => array('fields' => array('agreement_version')),
		'agreement_time' => array('fields' => array('agreement_time')),
	)
);

//供应商准营范围
$config['vendor_scope'] = array(
	'fields' => array(
		'vendor' => array('type' => 'object', 'oname'=>'vendor'),
		'name' => array('type'=>'varchar(50)', 'null'=>FALSE), // 'product_type.reagent'	'dangerous_reagent.102'
		'expire_date' => array('type'=>'bigint', 'null'=>FALSE, 'default'=>0), // UNIX timestamp
	),
	'indexes' => array(
		'unique' => array('fields'=>array('vendor', 'name'), 'type'=>'unique'),
		'expire_date' => array('fields' => array('expire_date')),
	)
);

/*评价*/
$config['rating'] = array(
	'fields' => array(
		'user' => array('type'=>'object', 'oname'=>'user'),
		'object' => array('type'=>'object', 'oname'=>'product'),
		'score' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'description' => array('type'=>'text', 'null'=>FALSE, 'default'=>''),
		'type' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
	),
	'indexes' => array(
		'unique'=>array('fields'=>array('user', 'object'), 'type'=>'unique'),
		'score' => array('fields'=>array('score')),
		'type' => array('fields'=>array('type')),
		'ctime' => array('fields'=>array('ctime'))
	)
);

/*标签*/
$config['tag'] = array(
	'fields' => array(
		'name' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		'parent' => array('type'=>'object', 'oname'=>'tag'),
		'root' => array('type'=>'object', 'oname'=>'tag'),
		'weight' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'ctime'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'mtime'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0),
	),
	'indexes' => array(
		'unique'=>array('fields'=>array('parent', 'name'), 'type'=>'unique'),
		'root' => array('fields'=>array('root')),
		'weight' => array('fields'=>array('weight')),
		'ctime'=>array('fields'=>array('ctime')),
		'mtime'=>array('fields'=>array('mtime')),
	)
);

/*消息*/
$config['message'] = array(
	'fields' => array(
		'sender' => array('type'=>'object', 'oname'=>'user'),
		'receiver' => array('type'=>'object', 'oname'=>'user'),
		'title' => array('type'=>'varchar(100)', 'null'=>FALSE, 'default'=>''),
		'body' => array('type'=>'text', 'null'=>FALSE, 'default'=>''),
		'is_read' => array('type'=>'int(1)', 'null'=>FALSE, 'default'=>0),
		'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
	),
	'indexes' => array(
		'sender' => array('fields'=>array('sender')),
		'receiver' => array('fields'=>array('receiver')),
		'title' => array('fields'=>array('title')),
		'ctime' => array('fields'=>array('ctime')),
	)
);

$config['product_node'] = array(
	'fields' => array(
		'product' => array('type'=>'object', 'oname'=>'product'),
		'status'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'is_sale'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'supply_time' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		'node' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		'stock_status' => array('type' => 'tinyint', 'null' => FALSE, 'default' => 0),
	),
	'indexes' => array(
		'stock_status' => array('fields'=>array('stock_status')),
		'status' => array('fields'=>array('status')),
		'product' => array('fields' => array('product')),
	)
);

/*供应商产品*/
// TODO product 中相关的 product 属性改为实属性!!!!!(xiaopei.li@2012-03-28)
$config['product'] = array(
	'fields' => array(

		// product 自有的属性
		'vendor' => array('type'=>'object', 'oname'=>'vendor'),
		'unit_price'=> array('type'=>'double', 'null'=>FALSE, 'default'=>0), // 单价: 人民币报价, 必填, 参考价格, 针对不同标签还可有特殊价格
		'orig_price'=> array('type'=>'double', 'null'=>FALSE, 'default'=>0), // 促销前的价格，原价
		'sale_info' => array('type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''),//促销类型
		'vendor_note' => array('type'=>'text', 'null'=>FALSE, 'default'=>''), // 供应商商品备注
		'approver' => array('type'=>'object', 'oname'=>'user'),
		'approve_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'last_approver' => array('type'=>'object', 'oname'=>'user'),
		'last_approve_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'publisher' => array('type'=>'object', 'oname'=>'user'),
		'publish_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'last_publisher' => array('type'=>'object', 'oname'=>'user'),
		'last_publish_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
        'unapprover' => array('type'=>'object', 'oname'=>'user'),
        'unapprove_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'ctime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'mtime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),

		// product 从 product 继承的属性, 这些项只在 vendor 未批准, 或作为历史记录时使用,
		// !mall 中不显示这些属性, 而是显示 $vendor->product 的 (xiaopei.li@2012-03-28)
		// 产品名称, 必填
		'name' => array('type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''),
		// 生产商: e.g.: SIGMA, Merck, Invitrogen, etc, 必填
		'manufacturer' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		// 目录号(货号), 必填
		'catalog_no' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		// 型号
		'model' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		// 规格: 针对化学试剂可填写一些纯度，品级相关的信息（分析纯、化学纯、优级纯等）必填
		'spec' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		// 包装: e.g.: 25L, 4x4L, 1000pcs等, 必填
		'package' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		// 类型
		'type' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		// 分类: 目前化学试剂部分按照国家通用分类(2级分类), 必填
		'category' => array('type' => 'object', 'oname' => 'product_category'),
		'categ' => array('type' => 'varchar(40)'),
		// 关键字: 用于进行相关检索, 可录入一些产品别名，通俗叫法之类的
		'keywords' => array('type' => 'varchar(800)', 'null' => FALSE, 'default' => ''),
		// 说明: 可填写一些不用于检索但是有助于用户了解产品的文字, 可填
		'description' => array('type'=>'text', 'null'=>FALSE, 'default'=>''),
		// 是否现货
		'stock_status' => array('type' => 'tinyint', 'null' => FALSE, 'default' => 0),

		'expire_date' => array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 过期日期

		'freeze_reasons' => array('type'=>'varchar(250)', 'null'=>FALSE, 'default'=>''), // 冻结原因, json 数组
		'sale_volume' => array('type' => 'int', 'null' => FALSE, 'default' => 0), // 销量, 暂未使用(xiaopei.li@2012-09-01)
        // 品牌
        'brand' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
        // 供货时间
        'supply_time' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
        // 市场价格
        'market_price' => array('type'=>'double', 'null'=>TRUE,),
        'version' => array('type'=>'int', 'null'=>FALSE, 'default'=>1), // 版本号
        'dirty' => array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 下架后是否需要生成新版本
        'fixed_price' => array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 商品是否为一口价
		'seg_name' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'seg_manufacturer' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'seg_catalog_no' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'seg_model' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'seg_spec' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'seg_package' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'seg_keywords' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'seg_description' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'seg_brand' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
	),
	'indexes' => array(
		// 未审批时, product 为 null, 以下限制会导致无法添加 product, 故先注释 (xiaopei.li@2012-03-24)
		// 'unique'=>array('fields'=>array('product', 'vendor'), 'type'=>'unique'),
		'unit_price' => array('fields'=>array('unit_price')),
		'orig_price' => array('fields'=>array('orig_price')),
		'approver' => array('fields'=>array('approver')),
		'approve_date' => array('fields'=>array('approve_date')),
		'publisher' => array('fields'=>array('publisher')),
		'publish_date' => array('fields'=>array('publish_date')),
		// 'unapprover' => array('fields'=>array('unapprover')),
		// 'unapprove_date' => array('fields'=>array('unapprove_date')),
		'ctime' => array('fields'=>array('ctime')),
		'mtime' => array('fields'=>array('mtime')),
		'expire_date' => array('fields' => array('expire_date')),
		'vendor' => array('fields' => array('vendor')),
		'spec' => array('fields' => array('spec')),
        'unique' => [
			'fields'=> [ 'manufacturer', 'catalog_no', 'package', 'vendor' ],
            'type'=>'unique',
            ],
        'brand' => array('fields' => array('brand')),
        'version' => array('fields' => array('version')),
        'dirty' => array('fields' => array('dirty')),
        'fixed_price' => array('fields' => array('fixed_price')),
        'type' => array('fields' => array('type')),
	)
);


//商品历史版本
$config['product_revision'] = array(
	'fields' => array(

		// product 自有的属性
		'vendor' => array('type'=>'object', 'oname'=>'vendor'),
		'unit_price'=> array('type'=>'double', 'null'=>FALSE, 'default'=>0), // 单价: 人民币报价, 必填, 参考价格, 针对不同标签还可有特殊价格
		'orig_price'=> array('type'=>'double', 'null'=>FALSE, 'default'=>0), // 促销前的价格，原价
		'sale_info' => array('type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''),//促销类型
		'vendor_note' => array('type'=>'text', 'null'=>FALSE, 'default'=>''), // 供应商商品备注
		'approver' => array('type'=>'object', 'oname'=>'user'),
		'approve_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'last_approver' => array('type'=>'object', 'oname'=>'user'),
		'last_approve_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'publisher' => array('type'=>'object', 'oname'=>'user'),
		'publish_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'last_publisher' => array('type'=>'object', 'oname'=>'user'),
		'last_publish_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
        'unapprover' => array('type'=>'object', 'oname'=>'user'),
        'unapprove_date'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'ctime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'mtime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),

		// product 从 product 继承的属性, 这些项只在 vendor 未批准, 或作为历史记录时使用,
		// !mall 中不显示这些属性, 而是显示 $vendor->product 的 (xiaopei.li@2012-03-28)
		// 产品名称, 必填
		'name' => array('type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''),
		// 生产商: e.g.: SIGMA, Merck, Invitrogen, etc, 必填
		'manufacturer' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		// 目录号(货号), 必填
		'catalog_no' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		// 型号
		'model' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		// 规格: 针对化学试剂可填写一些纯度，品级相关的信息（分析纯、化学纯、优级纯等）必填
		'spec' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		// 包装: e.g.: 25L, 4x4L, 1000pcs等, 必填
		'package' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		// 类型
		'type' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		// 分类: 目前化学试剂部分按照国家通用分类(2级分类), 必填
		'category' => array('type' => 'object', 'oname' => 'product_category'),
		// 关键字: 用于进行相关检索, 可录入一些产品别名，通俗叫法之类的
		'keywords' => array('type' => 'text', 'null' => FALSE, 'default' => ''),
		// 说明: 可填写一些不用于检索但是有助于用户了解产品的文字, 可填
		'description' => array('type'=>'text', 'null'=>FALSE, 'default'=>''),
		// 是否现货
		'stock_status' => array('type' => 'tinyint', 'null' => FALSE, 'default' => 0),

		'expire_date' => array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 过期日期

		'freeze_reasons' => array('type'=>'varchar(250)', 'null'=>FALSE, 'default'=>''), // 冻结原因, json 数组
		'sale_volume' => array('type' => 'int', 'null' => FALSE, 'DEFAULT' => 0), // 销量, 暂未使用(xiaopei.li@2012-09-01)
        // 品牌
        'brand' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
        // 供货时间
        'supply_time' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
        'nodes' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
        // 市场价格
        'market_price' => array('type'=>'double', 'null'=>TRUE,),
        'version'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
        'product' => array('type' => 'object', 'oname' => 'product'),
	),
	'indexes' => array(
		// 未审批时, product 为 null, 以下限制会导致无法添加 product, 故先注释 (xiaopei.li@2012-03-24)
		// 'unique'=>array('fields'=>array('product', 'vendor'), 'type'=>'unique'),
		'product' => array('fields'=>array('product')),
		'unit_price' => array('fields'=>array('unit_price')),
		'orig_price' => array('fields'=>array('orig_price')),
		'approver' => array('fields'=>array('approver')),
		'approve_date' => array('fields'=>array('approve_date')),
		'publisher' => array('fields'=>array('publisher')),
		'publish_date' => array('fields'=>array('publish_date')),
		// 'unapprover' => array('fields'=>array('unapprover')),
		// 'unapprove_date' => array('fields'=>array('unapprove_date')),
		'ctime' => array('fields'=>array('ctime')),
		'mtime' => array('fields'=>array('mtime')),
		'expire_date' => array('fields' => array('expire_date')),
		'vendor' => array('fields' => array('vendor')),
		'spec' => array('fields' => array('spec')),
		'catalog_no' => array('fields' => array('catalog_no')),
		'manufacturer' => array('fields' => array('manufacturer')),
		'package' => array('fields' => array('package')),
        'brand' => array('fields' => array('package')),
        'version' => array('fields' => array('version')),
	)
);

$config['brand_alias'] = array(
	'fields' => array(
		'name' => array('type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''),
		'brand' => array('type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''),
		'brand_abbr'=>array('type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''),
		'manufacturer' => array('type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''),
		'types' => array('type'=>'varchar(1000)', 'null'=>FALSE, 'default'=>''),
		'correct'=> array('type'=> 'tinyint', 'null'=> FALSE, 'default'=> 0), //1 为错误，2为正确，0为待定
		'ctime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'mtime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
	),
	'indexes' => array(
		'name' => array('fields'=> array('name')),
		'brand'=> array('fields'=> array('brand')),
		'brand_abbr'=> array('fields'=> array('brand_abbr')),
		'types' => array('fields'=> array('types')),
		'correct'=> array('fields'=> array('correct')),
	),
);

$config['brand'] = array(
	'fields' => array(
		'name' => array('type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''),
		'name_abbr'=>array('type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''),
		'manufacturer' => array('type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''),
		'types' => array('type'=>'varchar(1000)', 'null'=>FALSE, 'default'=>''),
		'aliases' => array('type'=>'varchar(1000)', 'null'=>FALSE, 'default'=>''),
		'ctime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'mtime'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
	),
	'indexes' => array(
		'name'=>array('fields'=>array('name'), 'type'=>'unique'),
		'name_abbr'=> array('fields'=> array('name_abbr')),
		'manufacturer'=> array('fields'=> array('manufacturer')),
		'types' => array('fields'=> array('types')),
		'aliases' => array('fields'=> array('aliases')),

	),
);

/*
== product & product ==

1. vendor 添加商品后，生成一 product 对象，此对象用附加属性保存 product 的所有属性；

2. 在管理员审核上架时
  2.1 若无此 product (manufacture/catalog_no)，则新建 product；
  2.2 若已有 product，则比对/合并 product 属性（类似豆瓣修改条目属性）

 (xiaopei.li@2012-03-16)
*/

/*产品*/
// $config['product'] = array(
// 	'fields' => array(
// 		// 产品名称, 必填
// 		'name' => array('type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''),
// 		// 生产商: e.g.: SIGMA, Merck, Invitrogen, etc, 必填
// 		'manufacturer' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
// 		// 目录号(货号), 必填
// 		'catalog_no' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
// 		// 型号
// 		'model' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
// 		// 规格: 针对化学试剂可填写一些纯度，品级相关的信息（分析纯、化学纯、优级纯等）必填
// 		'spec' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
// 		// 包装: e.g.: 25L, 4x4L, 1000pcs等, 必填
// 		'package' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
// 		// 类型
// 		'type' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>0),
// 		// 分类: 可自定义(?) 目前化学试剂部分按照国家通用分类(2级分类), 必填
// 		'category' => array('type' => 'object', 'oname' => 'product_category'),
// 		// 关键字: 用于进行相关检索, 可录入一些产品别名，通俗叫法之类的
// 		'keywords' => array('type' => 'text', 'null' => FALSE, 'default' => ''),
// 		// 说明: 可填写一些不用于检索但是有助于用户了解产品的文字, 可填
// 		'description' => array('type'=>'text', 'null'=>FALSE, 'default'=>''),
// 		// 价格范围
// 		// 'min_price' => array('type'=>'double', 'null'=>FALSE, 'default'=>-1),
// 		// 'max_price' => array('type'=>'double', 'null'=>FALSE, 'default'=>-1),
// 		// 修改 [min|max]_price 允许为 null, 因为待议价为 "-1", 要区分没价格和待议价 (xiaopei.li@2012-05-18)
// 		'min_price' => array('type'=>'double', 'null'=>TRUE),
// 		'max_price' => array('type'=>'double', 'null'=>TRUE),
// 		'brand' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
// 	),
// 	'indexes' => array(
// 		// 南开要求化学试剂的合并条件为"名称", 生产商/目录号无需 unique
// 		'unique' => array('type'=>'unique', 'fields'=>array('manufacturer', 'catalog_no', 'package')),
// 		'name' => array('fields'=>array('name')),
// 		'min_price' => array('fields'=>array('min_price')),
// 		'max_price' => array('fields'=>array('max_price')),
// 		'manufacturer' => array('fields'=>array('manufacturer')),
// 		'catalog_no' => array('fields'=>array('catalog_no')),
// 		'model' => array('fields'=>array('model')),
// 		'type' => array('fields'=>array('type')),
// 		'category' => array('fields'=>array('category')),
// 		'package' => array('fields'=>array('package'))
// 	)
// );


/*产品价格*/
$config['product_price'] = array(
	'fields' => array(
		'product' => array('type'=>'object', 'oname'=>'product'),
		'price_tag' => array('type'=>'object', 'oname'=>'tag'),
		'price' => array('type'=>'double', 'null'=>FALSE, 'default'=>0),
	),
	'indexes' => array(
		'unique'=>array('fields'=>array('product', 'price_tag'), 'type'=>'unique'),
		'price' => array('fields'=>array('price')),
	)
);

/*产品分类*/
$config['product_category'] = array(
	'fields' => array(
		'name' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		'root' => array('type'=>'object', 'oname'=>'product_category'),
		'parent' => array('type'=>'object', 'oname'=>'product_category'),
		'weight' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'mtime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'description' => array('type'=>'text', 'null'=>TRUE, 'default'=>''),
	),
	'indexes' => array(
		'unique'=>array('fields'=>array('name', 'parent'), 'type'=>'unique'),
		'root'=>array('fields'=>array('root')),
		'weight' => array('fields'=>array('weight')),
		'ctime' => array('fields'=>array('ctime')),
		'mtime' => array('fields'=>array('mtime')),
	)
);

//添加customer_grant
$config['customer_grant'] = array(
	'fields' => array(
		'project_no' => array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		'balance' => array('type'=>'double', 'null'=>FALSE, 'default'=>0),
	),
);


$config['comment'] = array(
	'fields' => array(
		'content' => array('type'=>'varchar(250)', 'null'=>FALSE),
		'author' => array('type'=>'object', 'oname'=>'user'),
		'object' => array('type'=>'object'),
		'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'mtime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'is_log' => array('type'=>'tinyint', 'null'=>FALSE, 'default'=>0),
	),
	'indexes' => array(
		'author' => array('fields'=>array('author')),
		'ctime' => array('fields'=>array('ctime')),
		'mtime' => array('fields'=>array('mtime'))
	),
);

$config['order_count'] = array(
	'fields' => array(
		'year' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'month' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'day' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'count' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
	),
	'indexes' => array(
		'year' => array('fields' => array('year')),
		'month' => array('fields' => array('month')),
		'day' => array('fields' => array('day')),
	),
);

$config['order_item_comment'] = array(
	'fields' => array(
		'order_item' => array('type' => 'object', 'oname' => 'order_item'),
		'author' => array('type' => 'object', 'oname' => 'user'),
		'author_customer' => array('type' => 'object', 'oname' => 'customer'),
		'content' => array('type' => 'text', 'null' => FALSE, 'default' => ''),
		'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		),
	'indexes' => array(
		'order_item' => array('type' => 'unique', 'fields' => array('order_item')), // 一个订单项只能有一个评价
		'author' => array('fields' => array('author')),
		'author_customer' => array('fields' => array('author_customer')),
		'ctime' => array('fields'=>array('ctime')),
		),
	);

$config['order_item_comment_reply'] = array(
	'fields' => array(
		'order_item_comment' => array('type' => 'object', 'oname' => 'order_item_comment'),
		'author' => array('type' => 'object', 'oname' => 'user'),
		'content' => array('type' => 'text', 'null' => FALSE, 'default' => ''),
		'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		),
	'indexes' => array(
		'order_item_comment' => array('fields' => array('order_item_comment')),
		'author' => array('fields' => array('author')),
		'ctime' => array('fields'=>array('ctime')),
		),
	);

$config['order_item_rating'] = array(
	'fields' => array(
		'order_item_comment' => array('type' => 'object', 'oname' => 'order_item_comment'),
		'subject' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'rating' => array('type' => 'tinyint', 'null' => TRUE),
		'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		),
	'indexes' => array(
		'order_item_comment' => array('fields' => array('order_item_comment')),
		'ctime' => array('fields'=>array('ctime')),
		),
	);

$config['customer_member_perm'] = array(
	'fields' => array(
		'name' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'customer' => array('type' => 'object', 'oname' => 'customer'),
		'user' => array('type' => 'object', 'oname' => 'user'),
		),
	'indexes' => array(
		'customer' => array('fields' => array('customer')),
		'user' => array('fields' => array('user')),
		),
	);

$config['order_activity'] = array(
    'fields'=> array(
        'order'=> array('type'=> 'object', 'oname'=> 'order'),
        'status'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
        'time' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
        ),
    'indexes'=> array(
        'order'=> array('fields'=> array('order')),
        'status'=> array('fields'=> array('status')),
        'time'=> array('fields'=> array('time'))
        )
    );

$config['product_upload_record'] = array(
	'fields'=> array(
		'sheet_name' => array('type' => 'varchar(150)', 'null' => FALSE, 'default' => ''),
		'file_name' => array('type' => 'varchar(150)', 'null' => FALSE, 'default' => ''),
		'type' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		'vendor'=> array('type'=> 'object', 'oname'=> 'vendor'),
		'path' => array('type' => 'varchar(150)', 'null' => FALSE, 'default' => ''),
		'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'status'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		),
	'indexes'=> array(
		'path' => array('type'=>'unique', 'fields'=>array('path')),
		'vendor'=> array('fields'=> array('vendor')),
		'ctime'=> array('fields'=> array('ctime')),
		)
	);
//操作时间
$config['operation_time']=array(
    'fields'=> array(
        'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0), //创建时间
        'status'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0), //事件类型
        'op_user' => array('type'=>'object', 'oname'=>'user'),//操作用户
        'order'=> array('type'=> 'object', 'oname'=> 'order'),
    ),
    'indexes'=> array(
        'ctime'=> array('fields'=> array('ctime')),
        'status'=> array('fields'=> array('status')),
        'op_user' => array('fields'=> array('op_user')),
        'order'=> array('fields'=> array('order')),
    )
);

$config['customer_group']=array(
    'fields'=> array(
        'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0), //创建时间
        'gapper_group'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0), //事件类型
        'customer' => array('type'=>'object', 'oname'=>'customer'),//操作用户
    ),
    'indexes'=> array(
        'ctime'=> array('fields'=> array('ctime')),
        'gapper_group' => array('type'=>'unique', 'fields'=>array('gapper_group')),
        'customer' => array('type'=>'unique', 'fields'=>array('customer')),
    )
);

$config['user_group']=array(
    'fields'=> array(
        'ctime' => array('type'=>'int', 'null'=>FALSE, 'default'=>0),
        'gapper_group'=> array('type'=>'int', 'null'=>FALSE, 'default'=>0),
        'user' => array('type'=>'object', 'oname'=>'user'),
    ),
    'indexes'=> array(
        'ctime'=> array('fields'=> array('ctime')),
        'gapper_group' => array('type'=>'unique', 'fields'=>array('gapper_group')),
        'user' => array('type'=>'unique', 'fields'=>array('user')),
    )
);