<?php

class Message {
	
	static function notif_callback($item) {
		$me = L('ME');
		if (!$me->id) return 0;
		return Q("message[receiver={$me}][!is_read]")->total_count();
	}
	
	static function setup_customer_index($e, $controller, $method, $params) {
		Event::bind('customer.primary.tab', "Message::primary_customer_tab");
		Event::bind('customer.primary.content', 'Message::primary_content', 0, 'message');
	}
	
	static function setup_vendor_index($e, $controller, $method, $params) {
		Event::bind('vendor.primary.tab', "Message::primary_vendor_tab");
		Event::bind('vendor.primary.content', 'Message::primary_content', 0, 'message');
	}
	
	static function customer_sidebar_menu($e) {
		$config = (array) $e->return_value;
		$config += (array)Config::get('messages.layout.sidebar');
		$config['messages']['list']['url'] = '!messages/customer';
		$e->return_value = $config;
		return FALSE;
	}

	static function vendor_sidebar_menu($e) {
		$config = (array) $e->return_value;
		$config += (array)Config::get('messages.layout.sidebar');
		$config['messages']['list']['url'] = '!messages/vendor';
		$e->return_value = $config;
		// 其他地方还会增加 menu item, 所以这儿不应 return FALSE(xiaopei.li@2012-03-24)
		// return FALSE;
	}

	
	static function primary_customer_tab($e, $tabs) {
		$tabs->add_tab('message', array(
			'title' => T('消息中心'),
			'url' => URI::url('!customer/messages/index.message')
		));
	}
	
	static function primary_vendor_tab($e, $tabs) {
		$tabs->add_tab('message', array(
			'title' => T('消息中心'),
			'url' => URI::url('!vendor/messages/index.message')
		));
	}
	
	static function primary_content($e, $tabs) {
		$tabs->content = V('messages:index');
	}

}
