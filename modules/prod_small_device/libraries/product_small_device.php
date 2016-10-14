<?php

class Product_Small_Device {

	static function admin_product_validate($e, $product, $form) {

        if (!($product->type && $product->type === 'small_device') &&
            !($form['type'] && $form['type'] === 'small_device')) {
            return;
        }
        $now = Date::time();
        $scope_name = 'product_type.small_device';
        $vendor = $product->vendor;
        $scope = Q("vendor_scope[vendor={$vendor}][expire_date>{$now}][name={$scope_name}]")->current();
        if (!$scope->id) {
            $form->set_error('type', T('不允许销售该类别的商品'));
        }
    }

	static function admin_product_submit($e, $product, $form) {

		if (!($product->type && 'small_device' === $product->type) &&
			!($form['type'] && 'small_device' === $form['type'])) {
			return;
		}

		if ($form->no_error) {
			$product->origin = $form['origin'];
			$product->warranty_period = $form['warranty_period'];
			$product->service_no = $form['service_no'];
		}

	}

    static function admin_product_sections($e, $product, $form, $sections) {

        if ($product->id && 'small_device' !== $product->type && ($product->dirty || Q("product_revision[product=$product]")->total_count())) {
            return;
        }
        $sections[] = V('prod_small_device:product/edit.small.device', array('form'=>$form, 'product'=>$product));
    }

	static function product_sections($e, $product, $form, $sections) {

		if ($product->id && 'small_device' !== $product->type && ($product->dirty || Q("product_revision[product=$product]")->total_count())) {
			return;
		}
		$sections[] = V('prod_small_device:product/vendor.edit.small.device', array('form'=>$form, 'product'=>$product));
	}

	static function product_submit($e, $product, $form) {

		if (!($product->type && 'small_device' === $product->type) &&
			!($form['type'] && 'small_device' === $form['type'])) {
			return;
		}

		if ($form->no_error) {

			$product->origin = $form['origin'];
			$product->warranty_period = $form['warranty_period'];
			$product->service_no = $form['service_no'];

		}
	}

	static function product_init_form($e, $product, $form) {

		if (!($product->type && 'small_device' === $product->type) &&
			!($form['type'] && 'small_device' === $form['type'])) {
			return;
		}

		$form['origin'] = $product->origin;
		$form['warranty_period'] = $product->warranty_period;
		$form['service_no'] = $product->service_no;
	}

	static function product_approve_init_form($e, $product, $form) {

		if (!($product->type && 'small_device' === $product->type) &&
			!($form['type'] && 'small_device' === $form['type'])) {
			return;
		}

		$form['origin'] = $product->origin;
		$form['warranty_period'] = $product->warranty_period;
		$form['service_no'] = $product->service_no;
	}

	static function admin_product_view_info_section($e, $product, $sections) {
		if ($product->type == 'small_device') {
			$sections[] = V('prod_small_device:product/view.info.small_device', array(
								'product' => $product
								));
		}

	}

	static function product_view_info_section($e, $product, $sections) {
		return self::admin_product_view_info_section($e, $product, $sections);
	}

	static function product_approve_sections($e, $product, $form, $sections) {

		if ($product->id && 'small_device' == $product->type) {
			$sections[] = V('prod_small_device:product/vendor.approve.small_device', array('template_product' => $product, 'form'=>$form));
		}
		elseif(!$product->id && 'small_device' == $form['type']) {
			$sections[] = V('prod_small_device:product/vendor.edit.small.device', array('form'=>$form));
		}
		else {
			return;
		}
	}

	static function product_saved($e, $product, $old_data, $new_data) {
		if (!$product->id || $product->type != 'small_device') return;
		Search_Product::update_index($product);
	}

	static function mall_product_preview($e, $product) {
		if ('small_device' != $product->type) {
			return;
		}
		$e->return_value = V('prod_small_device:mall/vendor_product/preview', array(
								 'product' => $product
								 ));
	}

    static function api_vendor_add_product_prepare($e, $product, $data) {
        $type = $data['type'];
        if ($type != 'small_device') return;

        $now = Date::time();
        $vendor = $product->vendor;

        $product->type = 'small_device';

        $category = O('product_category', array('name'=>$data['category']));

        if (!$category->id) throw new API_Vendor_Exception(T('分类号错误'), API_Vendor::INVALID_CATEGORY);

        $category_root = Product_Category_Model::root($type);

        if (!$category_root->is_itself_or_ancestor_of($category)) throw new API_Vendor_Exception(T('分类号错误'), API_Vendor::INVALID_CATEGORY);

        $main_scope_name = 'product_type.' . $type;
        $main_scope = Q("vendor_scope[vendor={$vendor}][name={$main_scope_name}][expire_date>{$now}]")->current();

        if (!$main_scope->id) throw new API_Vendor_Exception(T('禁止销售该类商品'), API_Vendor::ADD_DENIED);

        $product->category = $category;
    }

	static function get_scopes_needed_to_check($product) {

		if ('small_device' != $product->type) {
			return FALSE;
		}

		$scopes = array();
		$vendor = $product->vendor;

		// 检查 small_device
		$type = 'small_device';
		$type_label = '小型仪器';
		$type_scope_name = 'product_type.' . $type;
		$type_scope = O('vendor_scope', array(
                                        'vendor' => $vendor,
                                        'name' => $type_scope_name,
                                        ));
		$scopes[] = array($type_label, $type_scope);

		return $scopes;

	}

	static function sphinx_product_get_matchs($e, $type, $phrase) {
		if ($type != 'small_device') return;
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
