<?php
class Order_Base_Controller extends Layout_Customer_Controller {

    function _before_call($method, &$params) {
        parent::_before_call($method, $params);

        $this->add_css('sale');
        $this->layout->title = T('订单管理');
        $this->layout->body->primary_tabs = Widget::factory('tabs');

    }

    protected function _add_index_tabs($customer) {
		$bucket = Transfer_Bucket_Model::customer_bucket($customer);
		$me = L('ME');

        $this->layout->body->primary_tabs->add_tab('profile', array(
					'url'=> $customer->url(),
					'title'=> H($customer->name),
			));

		if ($me->is_allowed_to('列表订单', $customer)) {
			$this->layout->body->primary_tabs->add_tab('orders', array(
				'url'=> URI::url('!customer/orders/index.' . $customer->id),
				'title'=> HT('订单列表'),
			));
		}

		if ($me->is_allowed_to('列表付款单', $customer)) {
			$this->layout->body->primary_tabs->add_tab('transfer', array(
				'url'=> URI::url('!customer/transfer/index.' . $customer->id),
				'title'=> HT('付款管理'),
				'number' => $bucket->item_count() ?: NULL,
			));
		}
    }
}
