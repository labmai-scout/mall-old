<?php

class Search_Product_Consumable {

    static $model_name = 'product_consumable';

    static function update_index($product, &$v) {
        $unit_price = $product->unit_price;
		$ranges = Config::get('consumable.price_ranges');

		foreach ((array)$ranges as $status => $range) {
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

	static function empty_index() {
		return Search_Iterator::empty_index_of(self::$model_name);
	}
}
