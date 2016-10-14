<?php

class Product_Computer {

	static function admin_product_validate($e, $product, $form) {

        if (!($product->type && $product->type === 'computer') &&
            !($form['type'] && $form['type'] === 'computer')) {
            return;
        }
        $now = Date::time();
        $scope_name = 'product_type.computer';
        $vendor = $product->vendor;
        $scope = Q("vendor_scope[vendor={$vendor}][expire_date>{$now}][name={$scope_name}]")->current();
        if (!$scope->id) {
            $form->set_error('type', T('不允许销售该类别的商品'));
        }
    }

	static function admin_product_submit($e, $product, $form) {

		if (!($product->type && 'computer' === $product->type) &&
			!($form['type'] && 'computer' === $form['type'])) {
			return;
		}

		if ($form->no_error) {
			$product->computer_type = $form['computer_type'];
			$product->cpu = $form['cpu'];
			$product->memory = $form['memory'];
			$product->disk = $form['disk'];
			$product->display = $form['display'];
			$product->video_memory = $form['video_memory'];
			$product->service_call = $form['service_call'];
		}

	}

    static function admin_product_sections($e, $product, $form, $sections) {

        if ($product->id && 'computer' !== $product->type && ($product->dirty || Q("product_revision[product=$product]")->total_count())) {
            return;
        }
        $sections[] = V('prod_computer:product/edit.computer', array('form'=>$form, 'product'=>$product));
    }

	static function product_sections($e, $product, $form, $sections) {

		if ($product->id && 'computer' !== $product->type && ($product->dirty || Q("product_revision[product=$product]")->total_count())) {
			return;
		}
		$sections[] = V('prod_computer:product/vendor.edit.computer', array('form'=>$form, 'product'=>$product));
	}

	static function product_submit($e, $product, $form) {

		if (!($product->type && 'computer' === $product->type) &&
			!($form['type'] && 'computer' === $form['type'])) {
			return;
		}

        $now = Date::time();
        $scope_name = 'product_type.consumable';
        $vendor = $product->vendor;
        $scope = Q("vendor_scope[vendor={$vendor}][expire_date>{$now}][name={$scope_name}]")->current();
        if (!$scope->id) {
            $form->set_error('type', T('不允许销售该类别的商品'));
        }

		if ($form->no_error) {
			$product->computer_type = $form['computer_type'];
			$product->cpu = $form['cpu'];
			$product->memory = $form['memory'];
			$product->disk = $form['disk'];
			$product->display = $form['display'];
			$product->video_memory = $form['video_memory'];
			$product->service_call = $form['service_call'];
		}
	}

	static function product_init_form($e, $product, $form) {

		if (!($product->type && 'computer' === $product->type) &&
			!($form['type'] && 'computer' === $form['type'])) {
			return;
		}

		$form['origin'] = $product->origin;
	}

	static function admin_product_view_info_section($e, $product, $sections) {
		if ($product->type == 'computer') {
			$sections[] = V('prod_computer:product/view.info.computer', array(
								'product' => $product
								));
		}

	}

	static function product_view_info_section($e, $product, $sections) {
		return self::admin_product_view_info_section($e, $product, $sections);
	}

	static function product_saved($e, $product, $old_data, $new_data) {
		if (!$product->id || $product->type != 'computer') return;
		Search_Product::update_index($product);
	}

	static function mall_product_preview($e, $product) {
		if ('computer' != $product->type) {
			return;
		}
		$e->return_value = V('prod_computer:mall/vendor_product/preview', array(
								 'product' => $product
								 ));
	}

    static function api_vendor_add_product_prepare($e, $product, $data) {
        $type = $data['type'];
        if ($type != 'computer') return;

        $now = Date::time();
        $vendor = $product->vendor;

        $product->type = 'computer';

        $category = O('product_category', array('name'=>$data['category']));

        if (!$category->id) throw new API_Vendor_Exception(T('分类号错误'), API_Vendor::INVALID_CATEGORY);

        $category_root = Product_Category_Model::root($type);

        if (!$category_root->is_itself_or_ancestor_of($category)) throw new API_Vendor_Exception(T('分类号错误'), API_Vendor::INVALID_CATEGORY);

        $main_scope_name = 'product_type.' . $type;
        $main_scope = Q("vendor_scope[vendor={$vendor}][name={$main_scope_name}][expire_date>{$now}]")->current();

        if (!$main_scope->id) throw new API_Vendor_Exception(T('禁止销售该类商品'), API_Vendor::ADD_DENIED);

        $product->category = $category;
        $product->computer_type = $data['computer_type'] ?: '';
        $product->cpu = $data['cpu'] ?: '';
        $product->memory = $data['memory'] ?: '';
        $product->disk = $data['disk'] ?: '';
        $product->video_memory = $data['video_memory'] ?: '';
        $product->display = $data['display'] ?: '';
        $product->service_call = $data['service_call'] ?: '';
    }
	static function get_scopes_needed_to_check($product) {

		if ('computer' != $product->type) {
			return FALSE;
		}

		$scopes = array();
		$vendor = $product->vendor;

		// 检查 computer
		$type = 'computer';
		$type_label = '电脑整机';
		$type_scope_name = 'product_type.' . $type;
		$type_scope = O('vendor_scope', array(
                                        'vendor' => $vendor,
                                        'name' => $type_scope_name,
                                        ));
		$scopes[] = array($type_label, $type_scope);

		return $scopes;

	}

	static function sphinx_product_get_matchs($e, $type, $phrase) {
		if ($type != 'computer') return;
		$phrase = trim(str_replace('%', '',$phrase));
		$seg_phrase = implode(' ', rb_split_ex($phrase, __RB_SIMPLE_MODE__));
		if (strlen($phrase) == strlen($seg_phrase)) { //分词前后字符长度一致的条件下
			$phrase_arr = explode(' ', $phrase);
			foreach ($phrase_arr as $key => $value) {
				$new_arr[] = "\"".$value."\"";
			}
			$str = implode('|', $new_arr);
		}
		else {
			$seg_phrase_arr = explode(' ', $seg_phrase);
			$phrase_arr = explode(' ', $phrase);
			$merge = array_unique(array_merge($seg_phrase_arr, $phrase_arr));
			foreach ($merge as $key => $value) {
				$new_value = "\"".$value."\"";
				$new_arr[] = $new_value;
			}
			$str = implode('|', $new_arr);
		}
		$matchs = "@* {$str}";
		$e->return_value = $matchs;
    }

}
