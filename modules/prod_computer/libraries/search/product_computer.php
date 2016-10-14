<?php

class Search_Product_computer {

    static $model_name = 'product_computer';

    static function update_index($product, &$v) {

        $v['cpu'] = $product->cpu;
        $v['memory'] = $product->memory;
        $v['disk'] = $product->disk;
        $v['display'] = implode(' ', rb_split_ex($product->display, __RB_SIMPLE_MODE__));
        $v['video_memory'] = $product->video_memory;
        $v['service_call'] = $product->service_call;
        $v['computer_type'] = $product->computer_type;

        $unit_price = $product->unit_price;
		$ranges = Config::get('computer.price_ranges');

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
    	if ($cpu = $opt['cpu']) {
			$matchs[] = "@cpu \"{$cpu}\"";
		}
		if ($memory = $opt['memory']) {
			$matchs[] = "@memory \"{$memory}\"";
		}
		if ($disk = $opt['disk']) {
			$matchs[] = "@disk \"{$disk}\"";
		}
		if ($display = $opt['display']) {
			$display = Search_Iterator::rb_str_split($opt['display']);
			$matchs[] = "@display \"{$display}\"";
		}
		if ($computer_type = $opt['computer_type']) {
			$computer_type = Search_Iterator::rb_str_split($opt['computer_type']);
			$matchs[] = "@computer_type \"{$computer_type}\"";
		}
		if ($video_memory = $opt['video_memory']) {
			$matchs[] = "@video_memory \"{$video_memory}\"";
		}
    }

	static function empty_index() {
		return Search_Iterator::empty_index_of(self::$model_name);
	}
    
}
