<?php

class Product_Biologic_Reagent {

	static function admin_product_sections($e, $product, $form, $sections) {

		if ($product->id && 'biologic_reagent' !== $product->type && ($product->dirty || Q("product_revision[product=$product]")->total_count())) {
			return;
		}

		$sections[] = V('prod_biologic_reagent:product/edit.biologic_reagent', array('form'=>$form, 'product'=>$product));
	}

	static function admin_product_validate($e, $product, $form) {
        if (!($product->type && $product->type === 'biologic_reagent') &&
            !($form['type'] && $form['type'] === 'biologic_reagent')) {
            return;
        }
        $now = Date::time();
        $scope_name = 'product_type.biologic_reagent';
        $vendor = $product->vendor;
        $scope = Q("vendor_scope[vendor={$vendor}][expire_date>{$now}][name={$scope_name}]")->current();
        if (!$scope->id) {
            $form->set_error('type', T('不允许销售该类别的商品'));
        }
    }

	static function admin_product_submit($e, $product, $form) {

		if (!($product->type && 'biologic_reagent' === $product->type) &&
			!($form['type'] && 'biologic_reagent' === $form['type'])) {
			return;
		}

		if ($form->no_error) {
			$product->transport_cond = $form['transport_cond'];
			$product->storage_cond = $form['storage_cond'];
		}

	}

	static function product_sections($e, $product, $form, $sections) {

		if ($product->id && 'biologic_reagent' !== $product->type && ($product->dirty || Q("product_revision[product=$product]")->total_count())) {
			return;
		}
		$sections[] = V('prod_biologic_reagent:product/vendor.edit.biologic_reagent', array('form'=>$form, 'product'=>$product));
	}

	static function product_submit($e, $product, $form) {
        if (!($product->type && $product->type === 'biologic_reagent') &&
            !($form['type'] && $form['type'] === 'biologic_reagent')) {
            return;
        }
        $now = Date::time();
        $scope_name = 'product_type.biologic_reagent';
        $vendor = $product->vendor;
        $scope = Q("vendor_scope[vendor={$vendor}][expire_date>{$now}][name={$scope_name}]")->current();
        if (!$scope->id) {
            $form->set_error('type', T('不允许销售该类别的商品'));
        }
		if ($form->no_error) {
			$product->transport_cond = $form['transport_cond'];
			$product->storage_cond = $form['storage_cond'];
		}
    }

   	static function product_init_form($e, $product, $form) {
		// 只处理 reagent(xiaopei.li@2012-08-24)
		if (!($product->type && 'biologic_reagent' === $product->type) &&
			!($form['type'] && 'biologic_reagent' === $form['type'])) {
			return;
		}

		$form['transport_cond'] = $product->transport_cond;
		$form['storage_cond'] = $product->storage_cond;
	}

   	static function product_approve_init_form($e, $product, $form) {
		if (!($product->type && 'biologic_reagent' === $product->type) &&
			!($form['type'] && 'biologic_reagent' === $form['type'])) {
			return;
		}

		$form['transport_cond'] = $product->transport_cond;
		$form['storage_cond'] = $product->storage_cond;
	}

	static function product_approve_sections($e, $product, $form, $sections) {

		if ($product->id && 'biologic_reagent' == $product->type) {
			$sections[] = V('prod_biologic_reagent:product/vendor.approve.biologic_reagent', array('template_product' => $product, 'form'=>$form));
		}
		elseif (!$product->id && 'biologic_reagent' == $form['type']) {
			$sections[] = V('prod_biologic_reagent:product/vendor.edit.biologic_reagent', array('form'=>$form));
		}
		else {
			return;
		}

	}

	static function product_saved($e, $product, $old_data, $new_data) {
		if($product->vendor->is_processing_import_products) return;
		if (!$product->id || $product->type != 'biologic_reagent') return;
		Search_Product::update_index($product);
	}

	static function mall_product_preview($e, $product) {
		if ('biologic_reagent' != $product->type) {
			return;
		}
		$e->return_value = V('prod_biologic_reagent:mall/vendor_product/preview', array(
								 'product' => $product
								 ));
	}

	static function admin_product_view_info_section($e, $product, $sections) {
		if ($product->type == 'bio_reagent') {
			$sections[] = V('prod_biologic_reagent:product/view.info.biologic_reagent', array(
								'product' => $product
								));
		}

	}

	static function product_view_info_section($e, $product, $sections) {
		return self::admin_product_view_info_section($e, $product, $sections);
	}

    static function api_vendor_add_product_prepare($e, $product, $data) {
        $type = $data['type'];
        if ($type != 'biologic_reagent') return;

        $now = Date::time();
        $vendor = $product->vendor;

        $product->type = 'biologic_reagent';

        $category_root = Product_Category_Model::root($type);

        if (isset($data['category'])) {

            $category = O('product_category', array('name'=>$data['category']));

            if ($category->id) {
                if (!$category_root->is_itself_or_ancestor_of($category)) throw new API_Vendor_Exception(T('分类号错误'), API_Vendor::INVALID_CATEGORY);
            }
        }
        else {
            $category = $category_root;
        }

        $main_scope_name = 'product_type.' . $type;
        $main_scope = Q("vendor_scope[vendor={$vendor}][name={$main_scope_name}][expire_date>{$now}]")->current();

        if (!$main_scope->id) throw new API_Vendor_Exception(T('禁止销售该类商品'), API_Vendor::ADD_DENIED);

        $product->category = $category;
        $product->storage_cond = $data['storage_cond'] ?: '';
        $product->transport_cond = $data['transport_cond'] ?: '';
    }

    static function api_vendor_edit_product_prepare($e, $product, $data) {
        $type = $product->type;
        if ($type != 'biologic_reagent') return;

        $now = Date::time();
        $vendor = $product->vendor;

        if (isset($data['category'])) {

            $category = O('product_category', array('name'=>$data['category']));

            if (!$category->id) throw new API_Vendor_Exception(T('分类号错误'), API_Vendor::INVALID_CATEGORY);

            $category_root = Product_Category_Model::root($type);

            if (!$category_root->is_itself_or_ancestor_of($category)) throw new API_Vendor_Exception(T('分类号错误'), API_Vendor::INVALID_CATEGORY);

            $main_scope_name = 'product_type.' . $type;
            $main_scope = Q("vendor_scope[vendor={$vendor}][name={$main_scope_name}][expire_date>{$now}]")->current();

            if (!$main_scope->id) throw new API_Vendor_Exception(T('禁止销售该类商品'), API_Vendor::ADD_DENIED);

            $product->category = $category;
        }

        if (isset($data['storage_cond'])) $product->storage_cond = $data['storage_cond'] ?: '';
        if (isset($data['transport_cond'])) $product->transport_cond = $data['transport_cond'] ?: '';
    }


	static function get_scopes_needed_to_check($product) {

		if ('bio_reagent' != $product->type) {
			return FALSE;
		}
		$scopes = array();
		$vendor = O('vendor', ['gapper_group'=>$product->vendor_id]);

		// 检查 biologic_reagent
		$type = 'biologic_reagent';
		$type_label = '生物试剂';
		$type_scope_name = 'product_type.' . $type;
		$type_scope = O('vendor_scope', array(
                                        'vendor' => $vendor,
                                        'name' => $type_scope_name,
                                        ));
		$scopes[] = array($type_label, $type_scope);

		return $scopes;
	}

	static function sphinx_product_get_matchs($e, $type, $phrase) {
		if ($type != 'biologic_reagent') return;
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
