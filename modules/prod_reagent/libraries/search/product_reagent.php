<?php

class Search_Product_Reagent {

    static $model_name = 'product_reagent';

    static function update_index($product, &$v) {

    	$types = (array) Config::get('reagent.types');

        $v['cas_no'] = $product->cas_no;
        $v['alias'] = implode(',', (array)@json_decode($product->rgt_aliases, TRUE));
        $v['keywords'] .= ', '.$types[$product->rgt_type];
        $v['rgt_type'] = $product->rgt_type;
        $v['rgt_en_name'] = implode(' ', rb_split_ex($product->rgt_en_name, __RB_SIMPLE_MODE__));
        $v['reagent_formula'] = $product->reagent_formula;
        $v['reagent_mw'] = $product->reagent_mw;

        $unit_price = $product->unit_price;
		$ranges = Config::get('reagent.price_ranges');

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
    	if ($opt['rgt_type']) {
			$rgt_type = (int)$opt['rgt_type'];
			$where[] = "rgt_type={$rgt_type}";
		}
    }

	static function empty_index() {
		return Search_Iterator::empty_index_of(self::$model_name);
	}

}
