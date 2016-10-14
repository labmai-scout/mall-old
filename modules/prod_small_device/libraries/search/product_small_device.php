<?php

class Search_Product_Small_Device {

    static $model_name = 'product_small_device';

    static function update_index($product, &$v) {

        $v['origin'] = $product->origin;
        $v['warranty_period'] = $product->warranty_period;
        $v['service_no'] = $product->service_no;

        $unit_price = $product->unit_price;
		$ranges = Config::get('small_device.price_ranges');

		foreach ($ranges as $status => $range) {
			if (isset($range[0]) && isset($range[1])) {
				if ($unit_price >= $range[0] && $unit_price < $range[1]) {
					$v['price_range'] = $status;
					break;
				}
			}
			elseif (isset($range[0]) && !isset($range[1])) {
				if ($unit_price >= $range[0]) {
					$v['price_range'] = $status;
					break;
				}
			}

		}
		$num = 0;
		foreach ($v as $key => $value) {
			if ($value) $num++;
		}
		$v['valid_fields'] = $num;
        Search_Product::update_query(self::$model_name, $v);
    }

    static function split_opts($opt, &$where, &$matchs) {
    	if ($opt['ept_type']) {
			$ept_type = (int)$opt['ept_type'];
			$where[] = "ept_type={$ept_type}";
		}
    }

	static function empty_index() {
		return Search_Iterator::empty_index_of(self::$model_name);
	}
    
}
