<?php

class Search_Product_Biologic_Reagent {

    static $model_name = 'product_biologic_reagent';

    static function update_index($product, &$v) {

        $v['transport_cond'] = implode(' ', rb_split_ex($product->transport_cond, __RB_SIMPLE_MODE__));
        $v['storage_cond'] = implode(' ', rb_split_ex($product->storage_cond, __RB_SIMPLE_MODE__));

        $unit_price = $product->unit_price;
		$ranges = Config::get('biologic_reagent.price_ranges');

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

    static function split_opts($opt, &$where, &$matchs) {
    	
    	if ($opt['transport_cond']) {
			$transport_cond = Search_Iterator::rb_str_split($opt['transport_cond']);
			$matchs[] = "@transport_cond \"{$transport_cond}\"";
		}
		if ($opt['storage_cond']) {
			$storage_cond = Search_Iterator::rb_str_split($opt['storage_cond']);
			$matchs[] = "@storage_cond \"{$storage_cond}\"";
		}
    }

	static function empty_index() {
		return Search_Iterator::empty_index_of(self::$model_name);
	}

}
