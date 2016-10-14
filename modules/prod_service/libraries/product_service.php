<?php

class Product_Service {

    static function admin_product_sections($e, $product, $form, $sections) {

        if ($product->id && 'service' !== $product->type && ($product->dirty || Q("product_revision[product=$product]")->total_count())) {
            return;
        }

        $sections[] = V('prod_service:product/edit.service', array('form'=>$form, 'product'=>$product));
    }

    static function product_sections($e, $product, $form, $sections) {
        if ($product->id && 'service' !== $product->type && ($product->dirty || Q("product_revision[product=$product]")->total_count())) {
            return;
        }

        $sections[] = V('prod_service:product/vendor.edit.service', array('form'=>$form, 'product'=>$product));
    }

	// 由 Admin::approve_product_check_vendor_scope 调用, 获取需要检查的 scopes(xiaopei.li@2012-05-25)
	static function get_scopes_needed_to_check($product) {
        //如果不为消耗品,return
		if ('service' != $product->type) {
			return FALSE;
		}

		$scopes = array();
		$vendor = $product->vendor;

		// 检查 product_consuable
		$type = 'service';
		$type_label = '服务';
		$type_scope_name = 'product_type.' . $type;
		$type_scope = O('vendor_scope', array(
                                        'vendor' => $vendor,
                                        'name' => $type_scope_name,
                                        ));
        $scopes[] = array($type_label, $type_scope);

		return $scopes;

	}
    static function admin_product_validate($e, $product, $form) {
        if (!($product->type && $product->type === 'service') &&
            !($form['type'] && $form['type'] === 'service')) {
            return;
        }
        $now = Date::time();
        $scope_name = 'product_type.service';
        $vendor = $product->vendor;
        $scope = Q("vendor_scope[vendor={$vendor}][expire_date>{$now}][name={$scope_name}]")->current();
        if (!$scope->id) {
            $form->set_error('type', T('不允许销售该类别的商品'));
        }
    }

    static function admin_product_submit($e, $product, $form) {
        if (!($product->type && $product->type === 'service') &&
            !($form['type'] && $form['type'] === 'service')) {
            return;
        }
        if (!$form['client_id']) {
            $form->set_error('client_id', T('请填写client_id'));
        }
        if (!$form['template_link']) {
            $form->set_error('template_link', T('请填写链接模板'));
        }
        $now = Date::time();
        $scope_name = 'product_type.service';
        $vendor = $product->vendor;
        $scope = Q("vendor_scope[vendor={$vendor}][expire_date>{$now}][name={$scope_name}]")->current();
        if (!$scope->id) {
            $form->set_error('type', T('不允许销售该类别的商品'));
        }
        $gapper_app_product = O('gapper_app_product', ['client_id'=>'client_id']);

        if ($gapper_app_product->id) {
            $form->set_error('type', T('client_id不可重复'));
        }
        if ($form->no_error) {
            $product->template_link = $form['template_link'];
        }
    }

    static function product_post_submit($e, $product, $form) {
        if (!($product->type && $product->type === 'service') &&
            !($form['type'] && $form['type'] === 'service')) {
            return;
        }
        $client_id = $form['client_id'];
        $gapper_app_product =  O('gapper_app_product', ['product'=>$product]);
        $gapper_app_product->client_id = $client_id;
        $gapper_app_product->product = $product;
        $gapper_app_product->save();
    }

    static function product_submit($e, $product, $form) {
        if (!($product->type && $product->type === 'service') &&
            !($form['type'] && $form['type'] === 'service')) {
            return;
        }
        if (!$form['client_id']) {
            $form->set_error('client_id', T('请填写client_id'));
        }
        if (!$form['template_link']) {
            $form->set_error('template_link', T('请填写链接模板'));
        }
        $now = Date::time();
        $scope_name = 'product_type.service';
        $vendor = $product->vendor;
        $scope = Q("vendor_scope[vendor={$vendor}][expire_date>{$now}][name={$scope_name}]")->current();
        if (!$scope->id) {
            $form->set_error('type', T('不允许销售该类别的商品'));
        }
        $gapper_app_product = O('gapper_app_product', ['client_id'=>'client_id']);

        if ($gapper_app_product->id) {
            $form->set_error('type', T('client_id不可重复'));
        }
        if ($form->no_error) {
			$product->template_link = $form['template_link'];
        }
    }

    static function product_init_form($e, $product, $form) {

        if (!($product->type && 'service' === $product->type) &&
            !($form['type'] && 'service' === $form['type'])) {
            return;
        }
        $gapper_app_product = O('gapper_app_product', ['product'=>$product]);
        $form['client_id'] = $gapper_app_product->client_id;
		$form['template_link'] = $product->template_link;
    }

    static function product_approve_init_form($e, $product, $form) {

        if (!($product->type && 'service' === $product->type) &&
            !($form['type'] && 'service' === $form['type'])) {
            return;
        }
        $gapper_app_product = O('gapper_app_product', ['product'=>$product]);
        $form['client_id'] = $gapper_app_product->client_id;
		$form['template_link'] = $product->template_link;
    }

    static function product_approve_sections($e, $product, $form, $sections) {

        if ($product->id && $product->type == 'service') {
            $sections[] = V('prod_service:product/vendor.approve.service', array('template_product' => $product, 'form'=>$form));
        }
        elseif (!$product->id && $form['type'] == 'service') {
            $sections[] = V('prod_service:product/vendor.edit.service', array('form'=>$form));
        }
        else {
            return;
        }
    }

    static function product_saved($e, $product, $old_data, $new_data) {
        if($product->vendor->is_processing_import_products) return;
        if (!$product->id || $product->type != 'service') return;
        Search_Product::update_index($product);
    }

    static function mall_product_preview($e, $product) {
        if ('service' != $product->type) {
            return;
        }
        $e->return_value = V('prod_service:mall/vendor_product/preview', array(
                                 'product' => $product
                                 ));
    }

    static function admin_product_view_info_section($e, $product, $sections) {
        if ($product->type == 'service') {
            $sections[] = V('prod_service:product/view.info.service', array(
                                'product' => $product
                                ));
        }

    }

    static function product_view_info_section($e, $product, $sections) {
        return self::admin_product_view_info_section($e, $product, $sections);
    }

    static function api_vendor_add_product_prepare($e, $product, $data) {
        $type = $data['type'];
        if ($type != 'service') return;

        $now = Date::time();
        $vendor = $product->vendor;

        $product->type = 'service';

        $category = O('product_category', array('name'=>$data['category']));

        if (!$category->id) throw new API_Vendor_Exception(T('分类号错误'), API_Vendor::INVALID_CATEGORY);

        $category_root = Product_Category_Model::root($type);

        if (!$category_root->is_itself_or_ancestor_of($category)) throw new API_Vendor_Exception(T('分类号错误'), API_Vendor::INVALID_CATEGORY);

        $main_scope_name = 'product_type.' . $type;
        $main_scope = Q("vendor_scope[vendor={$vendor}][name={$main_scope_name}][expire_date>{$now}]")->current();

        if (!$main_scope->id) throw new API_Vendor_Exception(T('禁止销售该类商品'), API_Vendor::ADD_DENIED);

        $product->category = $category;
        $product->client_id = $data['client_id'];
        $product->template_link = $data['template_link'];
    }

    static function buy_service_product($e, $product) {
        if ($product->type == 'service') {
            $e->return_value = HT('未开放购买服务类商品');
            return FALSE;
        }
    }

}
