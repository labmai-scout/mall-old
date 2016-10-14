<?php

class Search_Product_Primers {

    static $model_name = 'product_service';

    static function update_index($product, &$v) {

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
