<?php

class Billing_Bucket_Model extends Presentable_Model {

	static function vendor_bucket($vendor) {
		if (!$vendor->id) return NULL;

		$bucket = O('billing_bucket', array('vendor'=>$vendor));
		if (!$bucket->id) {
			$bucket->vendor = $vendor;
			$bucket->save();
		}

		return $bucket;
	}

	protected $object_page = array(
        'view' => '!vendor/order/billing/bucket.%id[.%arguments]',
 	);

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
