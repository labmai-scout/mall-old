<?php

class Demo_Product_Reagent {

	static function buy_easymade_toxic($e, $product) {

		if (($product->rgt_type == Reagent_Type::EASYMADE_TOXIC)) {

			if (Site::get('allow_buy_easymade_toxic')) {
				return FALSE;
			}
			else {
				return HT('未开放购买易制毒!');
			}
		}
	}

}
