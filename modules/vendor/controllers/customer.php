<?php

class Customer_Controller extends Layout_Vendor_Controller {

	function index($id=0) {
		
		$me = L('ME');	
		$customer = O('customer', $id);

		if (!$customer->id) {
			URI::redirect('error/401');
		}

		$tabs = Widget::factory('tabs');
		
		$content = V('vendor:customer/view');

		$content->customer = $customer;

		$tabs
			->add_tab('profile', array(
					'url'=> $customer->url(NULL, NULL, NULL, 'vendor_view'),
					'title'=> H($customer->name),
						  ))
			->set('content', $content)
			->select('profile');
			
		$this->layout->title = H($user->name);
		$this->layout->body->primary_tabs = $tabs;
	}

}
