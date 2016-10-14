<?php

class Product_Reagent {

	static function admin_product_sections($e, $product, $form, $sections) {
		// 1. 新建产品时, 则无论是否为试剂, 都添加表单;
		// 2. 商品没有被购买过;
		if ($product->id && 'reagent' !== $product->type && ($product->dirty || Q("product_revision[product=$product]")->total_count())) {
			return;
		}
		$sections[] = V('prod_reagent:product/edit.reagent', array('form'=>$form, 'product'=>$product));
	}

    static function admin_product_validate($e, $product, $form) {
		// 只处理 reagent(xiaopei.li@2012-08-24)
		if (!($product->type && 'reagent' === $product->type) &&
			!($form['type'] && 'reagent' === $form['type'])) {
			return;
		}

		$properties = Config::get('reagent.types');
		$property_keys = array_keys($properties);
		if (!($form['rgt_type'] && in_array($form['rgt_type'], $property_keys))) {
			$form->set_error('rgt_type', T('请选择试剂性质'));
		}
		else {
            if (!$form['rgt_type']) {
                $form->set_error('rgt_type', T('请选择试剂性质!'));
            }


			if ($form['rgt_type'] == Reagent_Type::DANGEROUS) {

                $reagent_danger_classes_keys = array();
                foreach(Config::get('reagent.danger_classes') as $reagent_danger_class) {
                    $reagent_danger_classes_keys =  array_merge($reagent_danger_classes_keys, array_keys((array)$reagent_danger_class));
                }
                //判断是否为系统预设分类
				if (!$form['rgt_danger_class'] || !in_array($form['rgt_danger_class'], $reagent_danger_classes_keys)) {
					$form->set_error('rgt_danger_class', T('请选择危险品分类!'));
				}
			}
			else {
				$form['rgt_danger_class'] = 0;
			}
		}
    }
    static function admin_product_submit($e, $product, $form) {
		// 只处理 reagent(xiaopei.li@2012-08-24)
		if (!($form['type'] && 'reagent' === $form['type'])) {
			return;
		}

		$properties = Config::get('reagent.types');
		$property_keys = array_keys($properties);
		if (!($form['rgt_type'] && in_array($form['rgt_type'], $property_keys))) {
			$form->set_error('rgt_type', T('请选择试剂性质'));
		}
		else {
            $now = Date::time();

            $vendor = $product->vendor;
            if ($form['rgt_type']) {
                $sub_scope_name = 'rgt_type.'. $form['rgt_type'];
                $sub_scope = Q("vendor_scope[vendor={$vendor}][name={$sub_scope_name}][expire_date>{$now}]")->current();
                if (!$sub_scope->id) {
                    $form->set_error('rgt_type', T('不允许销售该试剂性质的商品!'));
                }
            }
            else {
                $form->set_error('rgt_type', T('请选择试剂性质!'));
            }

			if ($form['rgt_type'] == Reagent_Type::DANGEROUS) {

				if (!$form['rgt_danger_class']) {
					// TODO 不光要判断是否为空, 还需判断值是否合法
					$form->set_error('rgt_danger_class', T('请选择危险品分类'));
				}
			}
			else {
				$form['rgt_danger_class'] = 0;
			}
        }

        if ($form['cas_no'] && !preg_match('/^\d{2,7}-\d{2}-\d$/', $form['cas_no'])) {
            $form->set_error('cas_no', T('请正确填写cas号'));
        }

		// assignment
		if ($form->no_error) {
			$reagent_extra_product_form = array();

			$reagent_extra_product_form['cas_no'] = $form['cas_no'];
			$reagent_extra_product_form['rgt_en_name'] = $form['rgt_en_name'];
			$reagent_extra_product_form['rgt_type'] = $form['rgt_type'];
			$reagent_extra_product_form['rgt_danger_class'] = $form['rgt_danger_class'];

			$reagent_extra_product_form['reagent_formula'] = $form['reagent_formula'];
			$reagent_extra_product_form['reagent_mw'] = $form['reagent_mw'];
			$reagent_extra_product_form['rgt_aliases'] = $form['rgt_aliases'];

			$product->cas_no = $form['cas_no'];
			$product->rgt_en_name = $form['rgt_en_name'];
			$product->rgt_aliases = $form['rgt_aliases'];
			$product->rgt_type = $form['rgt_type'];

			$product->rgt_danger_class = $form['rgt_danger_class'];

			$product->reagent_formula = $form['reagent_formula'];
			$product->reagent_mw = $form['reagent_mw'];


			$extra_product_form = (array) $e->return_value;
			$extra_product_form += $reagent_extra_product_form;
			$e->return_value = $extra_product_form;
		}
	}

	static function admin_product_post_submit($e, $product, $form) {
		// 只处理 reagent(xiaopei.li@2012-08-24)
		if (!($product->type && 'reagent' === $product->type) &&
			!($form['type'] && 'reagent' === $form['type'])) {
			return;
		}
	}

	// depreacated (xiaopei.li@2012-08-25)
	static function admin_product_search_form($e, $table) {
		$form = $table->form;
		$type = $table->product_type;

		if (TRUE || $type == 'reagent') {
			$table->add_column('cas_no', array(
								   'title' => T('CAS 号'),
								   'invisible' => TRUE,
								   'filter' => array(
									   'form' => Widget::factory('table_input', array('name'=>'cas_no', 'value'=>$form['cas_no'])),
									   'value' => H($form['cas_no'] ? : NULL),
									   ),
								   ));

			$properties = Config::get('reagent.types');
			$table->add_column('rgt_type', array(
								   'title' => T('试剂性质'),
								   'invisible' => TRUE,
								   'filter' => array(
									   'form' => Widget::factory('prod_reagent:rgt_type_selector', array('name' => 'rgt_type', value => $form['rgt_type'])),
									   'value' => $form['rgt_type'] ? T($properties[$form['rgt_type']]) : NULL,
									   ),
								   ));
		}
	}

	// deprecated (xiaopei.li@2012-08-25)
	static function admin_product_search($e, $selector, $form) {

		if ($form['cas_no']) {
			$cas_no = Q::quote($form['cas_no']);
			$selector .= "[cas_no*={$cas_no}]";
		}

		if ($form['rgt_type']) {
			$property = Q::quote($form['rgt_type']);
			$selector .= "[rgt_type={$property}]";
		}

		$e->return_value = $selector;
	}

	static function product_saved($e, $product, $old_data, $new_data) {
		if($product->vendor->is_processing_import_products) return;
		if (!$product->id || $product->type != 'reagent') return;
		Search_Product::update_index($product);
	}

	// TODO 现在在执行以下流程时结果需讨论:
	// 1. 选择分类;
	// 2. 回到 home;
	// 3. 搜索;
	//
	// 结果: 搜索结果中包含"分类"的条件
	// 期望: 从 home 来的搜索, 应取消之前 session 中的条件
	static function mall_search($controller, $form) {

		$tab = 'reagent';

		if ($form['category']) {
			$category = O('product_category', $form['category']);
			if (!$category->root->id || $category->type != 'reagent') {
				$category = NULL;
			}
		}

		$opts = array(
			'category' => $category->id,
			'type' => 'reagent',
            'phrase' => $form['keyword'],
            'status' => 'approved'
		);


		if (isset($form['name'])) {
			$name = implode(' ', rb_split_ex($form['name'], __RB_SIMPLE_MODE__));
			$opts['name'] = '^'.$name.'$';
			$pagin_array = array('name' => $form['name']);
		}

		$r_form = $form;

        $opts['option_sql'] = "OPTION ranker=expr('max(sum((4*lcs+2*(min_best_span_pos==1)+exact_hit)*user_weight)*1000-sum(100*min_best_span_pos),0)'), field_weights=(cas_no =4000, catalog_no = 501, name=500, alias=90, vendor_name=80, vendor_short_name=80, spec=80, package=80, manufacturer=70)";

		$opts['group_by']= 'group_search';
		$opts['order_by'] = ' ORDER BY w DESC, `stock_status` DESC, `valid_fields` DESC, `weight` DESC';
		$products = new Search_Product($opts);
		$start = (int) $form['st'];
		$per_page = 10;
		$pagination = Site::pagination($products, $start, $per_page, URI::url('', $pagin_array));
		$controller->add_css('prod_reagent:theme');
		$controller->layout->body = V("prod_{$tab}:mall/search", array(
									'products' => $products,
									'tab' => $tab,
									'category' => $category,
									'form' => $r_form,
									'pagination' => $pagination,
									'sub_header' => V("prod_{$tab}:mall/sub_header", array('form'=>$form))
								));
	}



	static function mall_search_rank_view($e, $type, $rank_name, $rank_sort, $keyword=NULL, $uid) {
		if ($type != 'reagent') return;
		if (!$rank_name || !$rank_sort) return;
		$opts = array(
			'type' => 'reagent',
        	'phrase' => $keyword,
        	'status' => 'approved'
		);
		if ($rank_name == 'price') {
			if ($rank_sort == 'desc') {
				$opts['order_by'] = 'ORDER BY price DESC, `stock_status` DESC, `valid_fields` DESC, id ASC';
			}
			elseif ($rank_sort == 'asc') {
				$opts['order_by'] = 'ORDER BY price ASC, `stock_status` DESC, `valid_fields` DESC, id ASC';
			}
		}
		elseif ($rank_name == 'vendor') {
			if ($rank_sort == 'desc') {
				$opts['order_by'] = 'ORDER BY vendor_short_name_abbr DESC, `stock_status` DESC, `valid_fields` DESC, id ASC';
			}
			elseif ($rank_sort == 'asc') {
				$opts['order_by'] = 'ORDER BY vendor_short_name_abbr ASC, `stock_status` DESC, `valid_fields` DESC, id ASC';
			}
		}
		elseif ($rank_name == 'manufacturer') {
			if ($rank_sort == 'desc') {
				$opts['order_by'] = 'ORDER BY manufacturer_abbr DESC, `stock_status` DESC, `valid_fields` DESC, id ASC';
			}
			elseif ($rank_sort == 'asc') {
				$opts['order_by'] = 'ORDER BY manufacturer_abbr ASC, `stock_status` DESC, `valid_fields` DESC, id ASC';
			}
		}

        $opts['available'] = TRUE;
	    $opts['option_sql'] = "OPTION ranker=expr('max(sum((4*lcs+2*(min_best_span_pos==1)+exact_hit)*user_weight)*1000-sum(100*min_best_span_pos),0)'), field_weights=(cas_no =4000, catalog_no = 501, name=500, alias=90, vendor_name=80, vendor_short_name=80, spec=80, package=80, manufacturer=70)";
	    $opts['group_by'] = 'group_search';
	    $products = new Search_Product($opts);
		$start = 0; //排序从首页开始
		$per_page = 10;
		$pagination = Site::pagination($products, $start, $per_page, URI::url(''));
		$form = array();
		$form['keyword'] = $keyword;
		$e->return_value = (string)'<div class="content_body" id="'.$uid.'">'.$pagination.V("prod_reagent:mall/results", array('products' => $products,  'form' => $form)).$pagination.'</div>';
	}

	static function admin_product_view_info_section($e, $product, $sections) {
		if ($product->type == 'chem_reagent') {
			$sections[] = V('prod_reagent:product/view.info.reagent', array(
								'product' => $product
								));
		}

	}

	static function product_view_info_section($e, $product, $sections) {
		return self::admin_product_view_info_section($e, $product, $sections);
	}

	// 增加 reagent(化学试剂) 下的准营商品类别
	// 1. 试剂性质
	// 2. 危险品类型
	static function admin_vendor_sub_scope($e, $vendor, $form) {
		$properties = Config::get('reagent.types');

		$view = V('scope/admin.scope.properties',
					 array('properties' => $properties,
						   'vendor' => $vendor,
						   'form' => $form,
						   'allow_set_expire' => TRUE,
				));

		$e->return_value .= (string) $view;
	}

	static function vendor_vendor_sub_scope($e, $vendor) {
		$properties = Config::get('reagent.types');

		$view = V('scope/vendor.scope.properties',
					 array('properties' => $properties,
						   'vendor' => $vendor
				));

		$e->return_value .= (string) $view;
	}

	static function vendor_view_sub_scope($e, $vendor) {
		$properties = Config::get('reagent.types');

		$view = V('scope/view.scope.properties',
					 array('properties' => $properties,
						   'vendor' => $vendor
				));

		$e->return_value .= (string) $view;
	}

	static function product_init_form($e, $product, $form) {
		// 只处理 reagent(xiaopei.li@2012-08-24)
		if (!($product->type && 'reagent' === $product->type) &&
			!($form['type'] && 'reagent' === $form['type'])) {
			return;
		}

		$form['cas_no'] = $product->cas_no;
		$form['rgt_en_name'] = $product->rgt_en_name;
		$form['rgt_aliases'] = $product->rgt_aliases;
		$form['rgt_type'] = $product->rgt_type;
		$form['rgt_danger_class'] = $product->rgt_danger_class;
		$form['reagent_formula'] = $product->reagent_formula;
		$form['reagent_mw'] = $product->reagent_mw;
	}

	static function product_approve_init_form($e, $product, $form) {

		if (!($product->type && 'reagent' === $product->type) &&
			!($form['type'] && 'reagent' === $form['type'])) {
			return;
		}

		$form['cas_no'] = $product->cas_no;
		$form['rgt_en_name'] = $product->rgt_en_name;
		$form['rgt_aliases'] = $product->rgt_aliases;
		$form['rgt_type'] = $product->rgt_type;
		$form['rgt_danger_class'] = $product->rgt_danger_class;
		$form['reagent_formula'] = $product->reagent_formula;
		$form['reagent_mw'] = $product->reagent_mw;
	}

	static function product_sections($e, $product, $form, $sections) {
        //只要商品没有被购买过，则可以修改商品类别
		if ('reagent' !== $product->type && $product->id && ($product->dirty || Q("product_revision[product=$product]")->total_count())) {
			return;
		}

		$sections[] = V('prod_reagent:product/vendor.edit.reagent', array('form'=>$form, 'product'=>$product));
	}

	static function product_approve_sections($e, $product, $form, $sections) {

		if ($product->id && $product->type == 'reagent') {
			$sections[] = V('prod_reagent:product/vendor.approve.reagent', array('template_product' => $product, 'form'=>$form));
		}
		elseif (!$product->id && $form['type'] == 'reagent') {
			$sections[] = V('prod_reagent:product/vendor.edit.reagent', array('form'=>$form));
		}
		else {
			return;
		}

	}

	static function product_submit($e, $product, $form) {
		// 只处理 reagent(xiaopei.li@2012-08-24)
		if (!($form['type'] && 'reagent' === $form['type'])) {
			return;
		}

		// validation
		/*
		$form->validate('rgt_en_name', 'not_empty', T('试剂英文名不能为空!'))
			->validate('reagent_formula', 'not_empty', T('试剂分子式不能为空!'));

		$aliases = @json_decode($form['rgt_aliases'], TRUE);
		if (!(is_array($aliases) && count($aliases))) {
			$form->set_error('rgt_aliases', T('试剂别名不能为空'));
		}
		*/
		$properties = Config::get('reagent.types');
		$property_keys = array_keys($properties);
		if (!($form['rgt_type'] && in_array($form['rgt_type'], $property_keys))) {
			$form->set_error('rgt_type', T('请选择试剂性质'));
		}
		else {
            $now = Date::time();

            $vendor = $product->vendor;
            if ($form['rgt_type']) {
                $sub_scope_name = 'rgt_type.'. $form['rgt_type'];
                $sub_scope = Q("vendor_scope[vendor={$vendor}][name={$sub_scope_name}][expire_date>{$now}]")->current();
                if (!$sub_scope->id) {
                    $form->set_error('rgt_type', T('不允许销售该试剂性质的商品!'));
                }
            }
            else {
                $form->set_error('rgt_type', T('请选择试剂性质!'));
            }

			if ($form['rgt_type'] == Reagent_Type::DANGEROUS) {

				if (!$form['rgt_danger_class']) {
					// TODO 不光要判断是否为空, 还需判断值是否合法
					$form->set_error('rgt_danger_class', T('请选择危险品分类'));
				}
			}
			else {
				$form['rgt_danger_class'] = 0;
			}
		}

        if ($form['cas_no'] && !preg_match('/^\d{2,7}-\d{2}-\d$/', $form['cas_no'])) {
            $form->set_error('cas_no', T('请正确填写cas号'));
        }

		// assignment
		if ($form->no_error) {
			$reagent_extra_product_form = array();

			$reagent_extra_product_form['cas_no'] = $form['cas_no'];
			$reagent_extra_product_form['rgt_en_name'] = $form['rgt_en_name'];
			$reagent_extra_product_form['rgt_type'] = $form['rgt_type'];
			$reagent_extra_product_form['rgt_danger_class'] = $form['rgt_danger_class'];

			$reagent_extra_product_form['reagent_formula'] = $form['reagent_formula'];
			$reagent_extra_product_form['reagent_mw'] = $form['reagent_mw'];
			$reagent_extra_product_form['rgt_aliases'] = $form['rgt_aliases'];

			$product->cas_no = $form['cas_no'];
			$product->rgt_en_name = $form['rgt_en_name'];
			$product->rgt_aliases = $form['rgt_aliases'];
			$product->rgt_type = $form['rgt_type'];

			$product->rgt_danger_class = $form['rgt_danger_class'];

			$product->reagent_formula = $form['reagent_formula'];
			$product->reagent_mw = $form['reagent_mw'];


			$extra_product_form = (array) $e->return_value;
			$extra_product_form += $reagent_extra_product_form;
			$e->return_value = $extra_product_form;
		}

	}

	static function product_approve_submit($e, $product, $form, $product) {
		// 只处理 reagent(xiaopei.li@2012-08-24)
		if (!($product->type && 'reagent' === $product->type) &&
			!($form['type'] && 'reagent' === $form['type'])) {
			return;
		}

		$properties = Config::get('reagent.types');
		$property_keys = array_keys($properties);
		if ($form['n_rgt_type']) {
			if (!($form['rgt_type'] && in_array($form['rgt_type'], $property_keys))) {
				$form->set_error('rgt_type', T('请选择试剂性质'));
			}
			else {
				if ($form['rgt_type'] == Reagent_Type::DANGEROUS) {
					if (!$form['rgt_danger_class']) {
						// TODO 不光要判断是否为空, 还需判断值是否合法

						$form->set_error('rgt_danger_class', T('请选择危险品分类'));
					}
				}
				else {
					$form['rgt_danger_class'] = 0;
				}
			}
		}

		// TODO 判断 n_rgt_danger_class(xiaopei.li@2012-03-29)

        if ($form['cas_no'] && !preg_match('/^\d{2,7}-\d{2}-\d$/', $form['cas_no'])) {
            $form->set_error('cas_no', T('请正确填写cas号'));
        }

		// assignment
		if ($form->no_error) {

			$inputs = array(
				'rgt_en_name',
				'rgt_aliases',
				'rgt_type',
				'cas_no',
				'rgt_danger_class',
				'reagent_formula',
				'reagent_mw',
				);

			foreach ($inputs as $input) {
				if ($form['n_' . $input]) {
					$product->$input = $form[$input];
					$product->$input = $form[$input];
				}
				else {
					$product->$input = $product->$input;
				}
			}

			// TODO rgt_danger_class

		}

	}

	static function product_order_approval_required($e, $product, $params) {
		if ($product->rgt_type != Reagent_Type::REGULAR) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	static function mall_product_preview($e, $product) {
		if ('chem_reagent' != $product->type) {
			return;
		}
		$e->return_value = V('prod_reagent:mall/vendor_product/preview', array(
								 'product' => $product
								 ));
	}

	static function order_item_product_table_extra_view($e, $order_item) {
		if ('chem_reagent' != $order_item->product->type) {
			return;
		}
		$e->return_value = V('prod_reagent:order/item_extra', array('item' => $order_item));
	}

	// 处理形如 rgt_type.1 的准营类别过期 (xiaopei.li@2012-05-15)
	static function vendor_scope_expired($e, $vendor_scope) {
        $rgt_type_prefix = 'rgt_type.';
        return true;

		$pattern = "/^{$rgt_type_prefix}(?P<type>\d+)$/";

		if (preg_match($pattern, $vendor_scope->name, $matches)) {
			$type = $matches['type'];

			$db = Database::factory();
			$sphinx = Database::factory('@sphinx');

			$unapprove_date = Date::time();

			$num = 100;
			while (TRUE) {
				$product_table = Search_Iterator::get_index_name('product_reagent');
                $products = $sphinx->query("SELECT id,vendor_id FROM $product_table WHERE `rgt_type`=$type AND vendor_id={$vendor_scope->vendor->id} AND approve_date>0 limit $num");
                if (!$products) break;
                $products = $products->rows();

				if (!count($products)) break;
				$ids = [];
				$vendors = [];
				foreach ($products as $product) {

					$ids[$product->id] = $product->id;
					$vendors[$product->vendor_id] = $product->vendor_id;
				}
				$ids = join(',', $ids);
				$ret = $db->query("UPDATE `product` SET `last_approver_id`=`product`.`approver_id`, `last_approve_date`=`product`.`approve_date`, `unapprove_date`={$unapprove_date},`approve_date`=0, `approver_id`=0 WHERE id in ($ids)");

				if(!$ret) continue;

				//更新product索引
				$product_table = Search_Iterator::get_index_name('product');
				$sphinx_sql = "UPDATE `$product_table` SET `approve_date`=0 WHERE id IN ($ids)";
				$sphinx->query($sphinx_sql);

				//更新类别表的索引
				$pt = Search_Iterator::get_index_name('product_reagent');
				$sphinx_sql = "UPDATE `$pt` SET `approve_date`=0 WHERE id IN ($ids)";
				$sphinx->query($sphinx_sql);

				foreach ($vendors as $vendor) {
					$vid = intval($product->vendor_id);
					$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");
				}
			}

			return FALSE;
		}

	}

	//资质未过期批量上架
	static function vendor_scope_approve($e, $vendor_scope) {

		$type_prefix = 'product_type.';

		$pattern = "/^{$type_prefix}(?P<type>\w+)$/";
		//不处理化学试剂这种大类别的scope，只有rgt_type的scope未过期才上架
		if (preg_match($pattern, $vendor_scope->name, $matches)) {
			if($matches['type'] == 'reagent') return FALSE;
		}


		$rgt_type_prefix = 'rgt_type.';

		$pattern = "/^{$rgt_type_prefix}(?P<type>\d+)$/";

		if (preg_match($pattern, $vendor_scope->name, $matches)) {

			$now = time();
			$reagent_scope = Q("vendor_scope[vendor={$vendor_scope->vendor}][expire_date>{$now}][name=product_type.reagent]")->current();

			//小类别未过期，但是大类别过期了则不能上架
			if(!$reagent_scope->id) {
				return FALSE;
			}

			$type = $matches['type'];

			$db = Database::factory();
			$sphinx = Database::factory('@sphinx');

			$num = 100;

			while (TRUE) {
				$product_table = Search_Iterator::get_index_name('product_reagent');
				$products = $sphinx->query("SELECT id FROM $product_table WHERE `rgt_type`=$type AND vendor_id={$vendor_scope->vendor->id} AND publish_date>0 AND approve_date=0 limit $num");
                if (!$products) break;
                $products = $products->rows();

				if (!count($products)) break;
				$ids = [];
				$vendors = [];
				foreach ($products as $product) {
					$ids[$product->id] = $product->id;
				}
				$ids = join(',', $ids);
				$ret = $db->query("UPDATE `product` SET `approve_date`={$now} WHERE id in ($ids)");

				if(!$ret) continue;

				//更新product索引
				$product_table = Search_Iterator::get_index_name('product');
				$sphinx_sql = "UPDATE `$product_table` SET `approve_date`={$now} WHERE id IN ($ids)";
				$sphinx->query($sphinx_sql);

				//更新类别表的索引
				$pt = Search_Iterator::get_index_name('product_reagent');
				$sphinx_sql = "UPDATE `$pt` SET `approve_date`={$now} WHERE id IN ($ids)";
				$sphinx->query($sphinx_sql);
			}
		}

		return FALSE;
	}

	// 由 Admin::approve_product_check_vendor_scope 调用, 获取需要检查的 scopes(xiaopei.li@2012-05-25)
	static function get_scopes_needed_to_check($product) {
		if ('chem_reagent' != $product->type) {
			return;
		}

		$scopes = array();
		$vendor = O('vendor', ['gapper_group'=>$product->vendor_id]);

		// 检查 product_reagent
		$type = 'reagent';
		$type_label = '化学试剂';
		$type_scope_name = 'product_type.' . $type;
		$type_scope = O('vendor_scope', array(
							'vendor' => $vendor,
							'name' => $type_scope_name,
							));
		//$scopes[] = array($type_label, $type_scope);
		// 检查 商家填写的 rgt_type (xiaopei.li@2012-08-18)
		$sub_types = Config::get('reagent.types');

		$sub_types_to_check = array(
			$product->rgt_type,
			);

		// 检查 商品关联产品的 rgt_type (xiaopei.li@2012-08-18)
		$template_product = $product->get_template_product();
		if ($template_product->id &&
			$template_product->rgt_type != $product->rgt_type) {
			$sub_types_to_check[] = $template_product->rgt_type;
		}
		foreach ($sub_types_to_check as $sub_type) {
			$sub_type_label = $sub_types[$sub_type];
			if ($sub_type_label) {
				$sub_type_scope_name = 'rgt_type.' . $sub_type;
				$sub_scope = O('vendor_scope', array(
								   'vendor' => $vendor,
								   'name' => $sub_type_scope_name,
								   ));
				$scopes[] = array($sub_type_label, $sub_scope);
			}
		}

		return $scopes;

	}

	static function buy_easymade_toxic($e, $product) {


		if ($product->rgt_type == Reagent_Type::EASYMADE_TOXIC) {

			if (Site::get('allow_buy_easymade_toxic')) {
				$r = array();
				$rel = uniqid() . '_link';
				// TODO 此处以后要改为到达金智易制毒的接口(xiaopei.li@2012-08-23)
				$r['easymade_toxic'] = array(
					'url' => URI::url(NULL, NULL, 'BANG'),
					'text' => HT('申购易制毒'),
					'extra' => 'class="button button_add" '.
					'q-src="' . URI::url('!mall/cart') . '" '.
					'q-object="easymade_toxic_add" q-event="click" '.
					'q-static="' . H(array('id' => $product->id, 'rel' => $rel)) . '" ' .
					'id="' . $rel . '"',
					);
				$e->return_value = $r;
				// $e->return_value = 'EASYMADE_TOXIC';

			}
			else {
				$e->return_value = HT('未开放购买易制毒!');
			}


			return FALSE;
		}
	}

	static function admin_easymade_toxic_setup(){
		if(L('ME')->access('管理所有内容')){
			Event::bind('admin.index.tab', 'Product_Reagent::admin_easymade_toxic_primary_tab');
		}
	}

	static function admin_easymade_toxic_primary_tab($e, $tabs){
		Event::bind('admin.index.content', 'Product_Reagent::admin_easymade_toxic_primary_content', 0, 'easymade_toxic');

		$tabs->add_tab('easymade_toxic', array(
			'url'=>URI::url('!admin/admin/easymade_toxic'),
			'title'=> T('易制毒管理'),
		));
	}

	static function admin_easymade_toxic_primary_content($e, $tabs){
		$form = Input::form();

		if ($form['submit']) {

			if ('true' === $form['allow_buy_easymade_toxic']) {
				Site::set('allow_buy_easymade_toxic', TRUE);
				Site::message(Site::MESSAGE_NORMAL, HT('已开放购买易制毒!'));

			}
			else {
				Site::set('allow_buy_easymade_toxic', FALSE);
				Site::message(Site::MESSAGE_NORMAL, HT('已关闭购买易制毒!'));
			}

		}

		$form['allow_buy_easymade_toxic'] = Site::get('allow_buy_easymade_toxic');

		$tabs->content = V('prod_reagent:admin/easymade_toxic', array(
							   'form' => $form
							));
	}

	static function vendor_extra_panel_buttons($e, $vendor) {
		$extra_buttons = $e->return_value;
		if (!is_array($extra_buttons)) {
			$extra_buttons = array();
		}

		if (!$vendor->is_processing_import_products) {
            if (Config::get('reagent.enable_batch_import')) {
                $extra_buttons['import_reagent'] = array(
                    'url' => '#',
                    'text' => HT('批量导入化学试剂'),
                    'extra' => 'class="button button_add" q-event="click" q-object="import_reagent" q-src="'.URI::url('!prod_reagent/index').'" q-static="vendor='.$vendor->id.'"',
                );
            }
		}
		else {
			$extra_buttons['importimg_reagent'] = array(
				'text' => HT('正在批量导入...'),
				'extra' => 'class=""',
			);
		}

		if ($vendor->import_message) {
			Site::message(Site::MESSAGE_NORMAL, $vendor->import_message);
			$vendor->import_message = '';
			$vendor->save();
		}
		$e->return_value = $extra_buttons;
	}

	static function create_dir($path) {
		if (!file_exists($path)){
		    self::create_dir(dirname($path));
		    mkdir($path, 0777);
		}
	}

    static function api_vendor_add_product_prepare($e, $product, $data) {
        $type = $data['type'];
        if ($type != 'reagent') return;

        $now = Date::time();
        $vendor = $product->vendor;

        $product->type = 'reagent';

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

        $types = Config::get('reagent.types');
        if (array_key_exists($data['rgt_type'], $types)) {
			if ($data['rgt_type'] == Reagent_Type::EASYMADE_TOXIC) {
				$product->fixed_price = TRUE;
			}
            $product->rgt_type = $data['rgt_type'];
        }
        else {
            $product->rgt_type = 1;
        }

        $sub_scope_name = 'rgt_type.'. $product->rgt_type;

        $sub_scope = Q("vendor_scope[vendor={$vendor}][name={$sub_scope_name}][expire_date>{$now}]")->current();
        if (!$sub_scope->id) throw new API_Vendor_Exception(T('禁止销售该类商品'), API_Vendor::ADD_DENIED);

        if ($product->rgt_type == Reagent_Type::DANGEROUS) {
            $reagent_danger_classes_keys = array();
            foreach(Config::get('reagent.danger_classes') as $reagent_danger_class) {
                $reagent_danger_classes_keys =  array_merge($reagent_danger_classes_keys, array_keys((array)$reagent_danger_class));
            }
            //判断是否为系统预设分类
			if (!$data['rgt_danger_class'] || !in_array($data['rgt_danger_class'], $reagent_danger_classes_keys)) {
				throw new API_Vendor_Exception(T('危险品分类填写错误'), API_Vendor::DATA_FLAW);
			}
        }
        else {
            $data['rgt_danger_class'] = 0;
        }

        if ($data['cas_no'] && !preg_match('/^\d{2,7}-\d{2}-\d$/', $data['cas_no'])) {
            throw new API_Vendor_Exception(T('请正确填写cas号'), API_Vendor::DATA_FLAW);
        }

        $product->category = $category;
        $product->rgt_danger_class = $data['rgt_danger_class'];
        $product->cas_no = $data['cas_no'] ?: 0;
        $product->rgt_en_name = $data['rgt_en_name'] ?: '';
        $product->rgt_aliases = json_encode(array_unique(explode(',', $data['rgt_aliases'])));
        $product->reagent_formula = $data['reagent_formula'] ?: '';
        $product->reagent_mw = $data['reagent_mw'] ?: '';
    }

    static function api_vendor_edit_product_prepare($e, $product, $data) {
    	$type = $product->type;
        if ($type != 'reagent') return;

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

	    if (isset($data['rgt_type'])) {
	        $types = Config::get('reagent.types');
	        if (array_key_exists($data['rgt_type'], $types)) {
				if ($data['rgt_type'] == Reagent_Type::EASYMADE_TOXIC) {
					$product->fixed_price = TRUE;
				}
	            $product->rgt_type = $data['rgt_type'];
	        }

	        $sub_scope_name = 'rgt_type.'. $product->rgt_type;

	        $sub_scope = Q("vendor_scope[vendor={$vendor}][name={$sub_scope_name}][expire_date>{$now}]")->current();
	        if (!$sub_scope->id) throw new API_Vendor_Exception(T('禁止销售该类商品'), API_Vendor::ADD_DENIED);
	    }


        if ($product->rgt_type == Reagent_Type::DANGEROUS) {

            if (!isset($data['rgt_danger_class'])) {
            	//判断是否为系统预设分类
				if (!$product->rgt_danger_class) {
					throw new API_Vendor_Exception(T('危险品分类填写错误'), API_Vendor::DATA_FLAW);
				}
            }
            else {
            	$reagent_danger_classes_keys = array();
	            foreach(Config::get('reagent.danger_classes') as $reagent_danger_class) {
	                $reagent_danger_classes_keys =  array_merge($reagent_danger_classes_keys, array_keys((array)$reagent_danger_class));
	            }
            	if (!in_array($data['rgt_danger_class'], $reagent_danger_classes_keys)) {
					throw new API_Vendor_Exception(T('危险品分类填写错误'), API_Vendor::DATA_FLAW);
				}

            	$product->rgt_danger_class = $data['rgt_danger_class'];
            }
        }
        else {
            $product->rgt_danger_class = 0;
        }

        if ($data['cas_no'] && !preg_match('/^\d{2,7}-\d{2}-\d$/', $data['cas_no'])) {
            throw new API_Vendor_Exception(T('请正确填写cas号'), API_Vendor::DATA_FLAW);
        }

        if (isset($data['cas_no'])) $product->cas_no = $data['cas_no'] ?: 0;
        if (isset($data['rgt_en_name'])) $product->rgt_en_name = $data['rgt_en_name'] ?: '';
        if (isset($data['rgt_aliases'])) $product->rgt_aliases = json_encode(array_unique(explode(',', $data['rgt_aliases'])));
        if (isset($data['reagent_formula'])) $product->reagent_formula = $data['reagent_formula'] ?: '';
        if (isset($data['reagent_mw'])) $product->reagent_mw = $data['reagent_mw'] ?: '';
    }

	static function sphinx_product_get_matchs($e, $type, $phrase) {
		if ($type != 'reagent') return;
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

	static function sphinx_product_extra_index($e, $v, $product) {
		if ($product->type != 'reagent') return;
		$indexes = Config::get('sphinx.product_reagent')['extra_weight'];
		foreach ($indexes as $key => $value) {
			$arr[$value['weight']] = $value['index'];
		}
		krsort($arr);
		foreach ($arr as $key => $attr) {
			/* 试剂类型需要处理为文本 */
			if ($attr == 'rgt_type') {
				$str .= ' '.Config::get('reagent.types')[$product->$attr];
			}
			else {
				$str .= ' '.$product->$attr;
			}
		}
		/* 对字符串进行分词处理 */
		$e->return_value = implode(' ', rb_split_ex($str, __RB_SIMPLE_MODE__));
    }
}
