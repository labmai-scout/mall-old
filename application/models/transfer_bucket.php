<?php

class Transfer_Bucket_Model extends Presentable_Model {

	static function customer_bucket($customer) {
		if (!$customer->id) return NULL;

		$bucket = O('transfer_bucket', array('customer'=>$customer));
		if (!$bucket->id) {
			$bucket->customer = $customer;
			
			$bucket->save();
		}

		return $bucket;
	}

	function item_count() {
		return Q("$this order")->total_count();
	}

	function add_item($order) {
		return $this->id && $this->connect($order);
	}

	function remove_item($order) {
		return $this->id && $this->disconnect($order);
	}

	function contains($order) {
		return $this->id && $this->connected_with($order);	
	}

	function empty_bucket() {
		if ($this->id) {
			foreach(Q("$this order") as $order) {
				$this->disconnect($order);
			}
		}
	}

	function get_balance() {
		return Q("$this order")->sum('price');
	}
}
