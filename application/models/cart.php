<?php
// (xiaopei.li@2012-02-15)
class Cart_Model extends Presentable_Model {

	function add_item($product, $quantity = 1) {
		if (!$this->id) return FALSE;

		$item = O('cart_item', array(
					  'cart' => $this,
					  'product' => $product,
					  'version' => $product->version
					  ));
		$quantity = max((int)$quantity, 1);

		if ($item->id) {
			$item->quantity += $quantity;
		}
		else {
			$item->cart = $this;
			$item->product = $product;
			$item->version = $product->version;
			$item->quantity = $quantity;
			$item->version = $product->version;
			$item->requester = L('ME');
		}

		return $item->save();
	}

	// $address 为可以直接设置的运送地址, 用于重载从customer直接找到的默认的运送地址
	// TODO 此接口可改为 check_out(&$form, $orders) return 0 or error_msg  (xiaopei.li@2012-04-15)
	function check_out(&$form) {
		// $orders = array();
		// return $orders

		$me = L('ME');
		$customer = O('customer', $form['customer']);

		if (!($customer->id && $me->is_allowed_to('添加订单', $customer))) {
			return FALSE;
		}

		if ($customer->check_app_installed('lab-orders')) {
			return FALSE;
		}

		$order_items = array();
		$vendors = array();
		$orders = array();

		$items = Q("cart_item[cart={$this}]");

		// 按 vendor 分组 cart_items
		foreach ($items as $item) {
			$p = $item->product;

			if (!$p->can_buy($avoid_reason) || !$me->is_allowed_to('购买', $p) || $p->version != $item->version) return;

			if (!isset($vendors[$p->vendor->id])) {
				$vendors[$p->vendor->id] = $p->vendor;
			}
			$order_items[$p->vendor->id][$item->id] = $item;
		}

		if (!$address->id) {
			// 设置一个默认的运送地址
			$address = O('deliver_address', array('customer'=>$customer));
		}

		foreach ($order_items as $vid => $items) {

			$order = O('order');
			$order->deliver_address = $address->id;
			$order->vendor = $vendors[$vid];
			$order->customer = $customer;

			$order->purchase_date = Date::time();
			$order->purchaser = $me;

			$order->address = $form['address'];
			$order->phone = $form['phone'];
			$order->description = $form['description'][$vid];
			$order->postcode = $form['postcode'];
			$order->email = $form['email'];

			$order->status = Order_Model::STATUS_REQUESTING;
			if ($order->save()) {
				if ($me->is_allowed_to('以买方确认', $order)) {
					$order->customer_confirmed = TRUE;
					$order->status = Order_Model::STATUS_NEED_VENDOR_APPROVE;
				}
			}
			else {
				continue;
			}

			$price = 0;
			foreach($items as $item) {
				$product = $item->product;
				$order_item = O('order_item');
				$order_item->order = $order;
				$order_item->product = $product;
				$order_item->origin_quantity = $order_item->quantity = $item->quantity;
                $order_item->status = $order->status;
				// 订单项总价用 $product->get_price($customer, $quantity) 获得
				$order_item->price = $product->get_price($customer, $order_item->quantity);
				// 订单项单价 = 总价/数量
				$order_item->unit_price = $order_item->price > 0 ?
					$order_item->price / $order_item->quantity :
					$order_item->price; // 0 or 待询价

				$order_item->requester = $item->requester;
				$order_item->request_date = $item->request_date ? : Date::time();

				if ($order_item->save()) {
					// 如果 order_item保存成功, 则清空相应的cart_item
					$item->delete();
					$price += $order_item->price;
				}

			}

			$order->update_price()->save();
			//checkout生成的订单，添加log
			Log::add(sprintf('[order] %s[%d] 生成了订单 %d',
                      $me->name, $me->id, $order->order_no),
              		'order');

			Event::trigger('order_is_drafted', $order);

			$orders[$order->id] = $order;
		}
		// TODO 返回更详细的出错信息(xiaopei.li@2012-03-31)
		return $orders;
	}

	static function user_cart($user) {
		if (!$user->id) return NULL;

		$cart = O('cart', array('user'=>$user));
		if (!$cart->id) {
			$cart->user = $user;
			$cart->save();
		}

		return $cart;
	}

	function item_count() {
		return Q("cart_item[cart=$this]")->total_count();
	}

	function get_amount() {
		$amount = 0;

		foreach (Q("cart_item[cart=$this]") as $item) {
			$item_price = $item->product->get_price(NULL, $item->quantity);
			if ($item_price > 0) {
				$amount += $item_price;
			}
		}

		return $amount;
	}

}
