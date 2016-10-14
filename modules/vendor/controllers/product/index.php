<?php
// TODO 发布和审核方案还没定(xiaopei.li@2012-03-09)

class Product_Index_Controller extends Base_Controller {

	// 只有已通过审核的 vendor 才可操作
	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
	}

	function _add_tab($vendor){
		if(!$vendor->id) return;

		$this->layout->body->primary_tabs= Widget::factory('tabs');
		$this->layout->body->primary_tabs
			->add_tab('products', array(
					'url'=> $vendor->url(NULL,NULL,NULL,'product'),
					'title'=> H(T('商品列表')),
						  ));
    }

    function go($id=0)
    {
        $vendor = O('vendor', ['gapper_group'=>$id]);
        if ($vendor->id) {
            return $this->index($vendor->id);
        }
        URI::redirect('error/401');
    }

	function index($id=0, $tab='approved') {
		URI::redirect('!vendor/profile/view.'.$id.'.product');
		// 供应商最关心的是自己在架上的商品 (xiaopei.li@2012-03-28)

		$me = L('ME');

        $vendor = O('vendor', $id);

		//增加tab
		$this->_add_tab($vendor);

		if (!$me->is_allowed_to('查看商品', $vendor)) {
			URI::redirect('error/401');
		}


		// 定义结构
		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
			->add_tab('approved', array(
						  'url'=>$vendor->url('approved', NULL, NULL, 'product'),
						  'title'=>T('已上架'),
						  'reminder' => Q("product[vendor={$vendor}][!newer][publish_date>0][approve_date>0]:limit(1)")->length(),
						  ))
			->add_tab('unapproved', array(
						  'url'=>$vendor->url('unapproved', NULL, NULL, 'product'),
						  'title'=>T('待审核'),
						  'reminder' => Q("product[vendor={$vendor}][!newer][publish_date>0][approve_date<=0]:limit(1)")->length(),
						  ))
            ->add_tab('unpublished', array(
						  'url'=>$vendor->url('unpublished', NULL, NULL, 'product'),
						  'title'=>T('未发布'),
						  'reminder' => Q("product[vendor={$vendor}][!newer][publish_date<=0]:limit(1)")->length(),
			))
			->set('class', 'secondary_tabs')
			->content_event('vendor.products.list.content')
			->select($tab);

		$form = Site::form();
    	$opt = array(
			'phrase' => $form['phrase'],
			'vendor_id' => $vendor->id,
			'status' => $tab,
		);
        if (preg_match('/^\d{2,7}-\d{2}-\d$/', $form['phrase'])) {
        	$opt['type'] = 'reagent';
        }

		$products = new Search_product($opt);

		// pagination
		$start = (int) $form['st'];
		$per_page = 20;
		$pagination = Site::pagination($products, $start, $per_page);

		// render view
		$panel_buttons[] = array(
			'url' => URI::url('!vendor/product/index/add.'.$vendor->id),
			'text' => T('添加商品'),
			'extra' => 'class="button button_add"',
			);

		$extra_buttons = Event::trigger('product.list_get_extra_panel_buttons', $vendor);

        if ($extra_buttons) {
            $panel_buttons = array_merge($panel_buttons, $extra_buttons);
        }

        if($tab == 'approved') {

        	if($vendor->unpublish_vp_pid) {
        		SITE::message(Site::MESSAGE_NORMAL, nl2br("批量下架脚本正在执行...\n" ));
        	}
        	else if($vendor->last_unpublish_products_result){
        		$last_time = $vendor->last_unpublish_products_time ?
	                Date::format($vendor->last_unpublish_products_time) : "";

		        SITE::message(Site::MESSAGE_NORMAL, nl2br($last_time . " 批量下架脚本结果:\n" .
		            $vendor->last_unpublish_products_result));

		        $vendor->last_unpublish_products_result = '';
		        $vendor->save();
        	}
	    }
	    elseif ($tab == 'unapproved') {
        	if($vendor->cancel_vp_pid) {
        		SITE::message(Site::MESSAGE_NORMAL, nl2br("批量取消待审核商品脚本正在执行...\n" ));
        	}
        	else if($vendor->last_cancel_products_result){
        		$last_time = $vendor->last_cancel_products_time ?
	                Date::format($vendor->last_cancel_products_time) : "";

		        SITE::message(Site::MESSAGE_NORMAL, nl2br($last_time . " 批量取消待审核商品脚本结果:\n" .
		            $vendor->last_cancel_products_result));

		        $vendor->last_cancel_products_result = '';
		        $vendor->save();
        	}
	    }
	    elseif($tab == 'unpublished') {
	    	if($vendor->publish_vp_pid) {
        		SITE::message(Site::MESSAGE_NORMAL, nl2br("批量发布脚本正在执行...\n" ));
        	}
        	else if($vendor->last_publish_products_result){
        		$last_time = $vendor->last_publish_products_time ?
	                Date::format($vendor->last_publish_products_time) : "";

		        SITE::message(Site::MESSAGE_NORMAL, nl2br($last_time . " 批量发布脚本结果:\n" .
		            $vendor->last_publish_products_result));

		        $vendor->last_publish_products_result = '';
		        $vendor->save();
        	}
	    }

		$primary_tabs = $this->layout->body->primary_tabs->select('list');
		$primary_tabs->content = V('vendor:product/list',
								   array(
									   'tab' => $tab,
									   'secondary_tabs' => $secondary_tabs,
									   'panel_buttons' => $panel_buttons,
									   'pagination' => $pagination,
									   'products' => $products,
									   'form' => $form,
                                       'vendor'=>$vendor,
									   ));

		$this->layout->title = H(T('商品列表'));

	}

	function view($id = 0, $tab = 'info') {
		$form = Input::form();
		$product = O('product', $id);
		if($form['version'] && $form['version'] != $product->version) {
			$this->snapshot($id, $tab);
			return;
		}
		$vendor = $product->vendor;
		$this->_add_tab($vendor);

		if (!$product->id) URI::redirect('error/404');

		if (!L('ME')->is_allowed_to('以供应商查看', $product)) URI::redirect('error/401');

		if ($product->reject_reason) {
			Site::message(Site::MESSAGE_NORMAL, HT('下架原因: %reason', array('%reason' => $product->reject_reason)));
		}

		$this->layout->body->primary_tabs
			->add_tab('view', array(
						  'url' => $product->url(NULL, NULL, NULL, 'vendor_view'),
						  'title' => H($product->name),
						  ))
			->select('view');

		Event::bind('vendor.product.view.content', array($this, '_view_info'), 0, 'info');
		Event::bind('vendor.product.view.content', array($this, '_view_order'), 0, 'order');
		$secondary_tabs = Widget::factory('tabs');

		$secondary_tabs->add_tab('info', array(
									 'url' => $product->url('info', NULL, NULL, 'vendor_view'),
									 'title' => T('基本信息'),
									 'weight' => 0,
									 ));

		$secondary_tabs->add_tab('order', array(
									 'url' => $product->url('order', NULL, NULL, 'vendor_view'),
									 'title' => T('相关订单'),
									 'weight' => 0,
									 ));

        Event::bind('vendor.product.view.content', array($this, '_view_snapshots'), 0, 'snapshots');
        $secondary_tabs->add_tab('snapshots', array(
							  'url' => $product->url('snapshots', NULL, NULL, 'vendor_view'),
							  'title' => T('历史版本'),
							  ));

		// 其他 secondary tab 如 product add Event::bind('vendor.product.view.content', array($this, '_view_vendors'), 0, 'vendors');

		$secondary_tabs->set('product', $product)
			->tab_event('vendor.product.view.tab')
			->content_event('vendor.product.view.content')
			->select($tab);


		$this->layout->body->primary_tabs->content = V('vendor:vendor_product/view', array(
							   'product'=>$product,
							   'secondary_tabs' => $secondary_tabs,
							   ));
	}

	function snapshot($id = 0, $tab = 'info') {
		$product = O('product', $id);
		$form = Site::form();
		$version = $form['version'];
		$snapshot = O('product_revision', ['product'=>$product, 'version'=>$version]);

		if (!$snapshot->id) URI::redirect('error/404');

		$vendor = $product->vendor;
		$this->_add_tab($vendor);


		if (!L('ME')->is_allowed_to('以供应商查看', $product)) URI::redirect('error/401');


		$this->layout->body->primary_tabs
			->add_tab('view', array(
						  'url' => $snapshot->url($snapshot->product->id, ['version'=>$snapshot->version], NULL, 'vendor_snapshot'),
						  'title' => H($snapshot->name),
						  ))
			->select('view');

		Event::bind('vendor.snapshot.view.content', array($this, '_snapshot_view_info'), 0, 'info');
		Event::bind('vendor.snapshot.view.content', array($this, '_snapshot_view_order'), 0, 'order');
		$secondary_tabs = Widget::factory('tabs');

		$secondary_tabs->add_tab('info', array(
									 'url' => $snapshot->url([$snapshot->product->id,'info'], ['version'=>$version], NULL, 'vendor_snapshot'),
									 'title' => T('基本信息'),
									 'weight' => 0,
									 ));

		$secondary_tabs->add_tab('order', array(
									 'url' => $snapshot->url([$snapshot->product->id,'order'], ['version'=>$version], NULL, 'vendor_snapshot'),
									 'title' => T('相关订单'),
									 'weight' => 0,
									 ));

    	Site::message(Site::MESSAGE_NORMAL,
			T('您查看的是一个历史快照, %alt_link', array(
			           '%alt_link' =>
			           URI::anchor($product->url(NULL, NULL, NULL, 'vendor_view'),
			                                   HT('查看最新版本'),
			                                   'class="blue"')
			           )));

		// 其他 secondary tab 如 product add Event::bind('vendor.product.view.content', array($this, '_view_vendors'), 0, 'vendors');

		$secondary_tabs->set('snapshot', $snapshot)
			->tab_event('vendor.snapshot.view.tab')
			->content_event('vendor.snapshot.view.content')
			->select($tab);



		$this->layout->body->primary_tabs->content = V('vendor:vendor_product/snapshot/view', array(
							   'snapshot'=>$snapshot,
							   'secondary_tabs' => $secondary_tabs,
							   ));
	}

	function _snapshot_view_info($e, $tabs) {
		$snapshot = $tabs->snapshot;

		$sections = new ArrayIterator;
		$sections[] = V('vendor:vendor_product/snapshot/view.info.general', array(
							'product' => $snapshot
							));

		Event::trigger('vendor.product.view.info.sections', $snapshot, $sections);

		$sections[] = V('vendor:vendor_product/snapshot/view.info.price', array(
							'product' => $snapshot
							));

		$tabs->content = V('vendor:vendor_product/view.info', array('sections' => $sections));
	}

	function _snapshot_view_order($e, $tabs) {
		$snapshot = $tabs->snapshot;

		$form = Site::form();

		// TODO 当前版本下显示所有版本下的订单, 历史快照下仅显示该版本下的订单(xiaopei.li@2012-08-06)

		$orders = Q("order_item[product={$snapshot->product}][version={$snapshot->version}] order");

		// pagination
		$start = (int) $form['st'];

		$per_page = 20;
		$start = $start - ($start % $per_page);

		$pagination = Site::pagination($orders, $start, $per_page);
		$tabs->content = V('vendor:vendor_product/view.order', array(
							   'orders' => $orders,
							   'pagination' => $pagination,
							   ));
	}

	function _view_info($e, $tabs) {
		$product = $tabs->product;

		$sections = new ArrayIterator;
		$sections[] = V('vendor:vendor_product/view.info.general', array(
							'product' => $product
							));

		Event::trigger('vendor.product.view.info.sections', $product, $sections);

		$sections[] = V('vendor:vendor_product/view.info.price', array(
							'product' => $product
							));

		$tabs->content = V('vendor:vendor_product/view.info', array('sections' => $sections));
	}

	function _view_snapshots($e, $tabs) {

		$product = $tabs->product;

		$form = Site::form();

		$selector = "product_revision[product={$product}]";

		$selector .= ':sort(ctime DESC)';
		$snapshots = Q($selector);

		// pagination
		$start = (int) $form['st'];

		$per_page = 20;
		$start = $start - ($start % $per_page);

		$pagination = Site::pagination($snapshots, $start, $per_page);

		$tabs->content = V('vendor:vendor_product/view.snapshots', array(
							   'snapshots' => $snapshots,
							   'pagination' => $pagination,
							   ));
	}

	function _view_order($e, $tabs) {
		$product = $tabs->product;

		$form = Site::form();

		// TODO 当前版本下显示所有版本下的订单, 历史快照下仅显示该版本下的订单(xiaopei.li@2012-08-06)
		$orders = Q("order_item[product={$product}] order");

		// pagination
		$start = (int) $form['st'];

		$per_page = 20;
		$start = $start - ($start % $per_page);

		$pagination = Site::pagination($orders, $start, $per_page);
		$tabs->content = V('vendor:vendor_product/view.order', array(
							   'orders' => $orders,
							   'pagination' => $pagination,
							   ));
	}

	function add($vid=0) {

		$me = L('ME');
		$vendor = O('vendor', $vid);
		$this->_add_tab($vendor);

		if (!$me->is_allowed_to('添加商品', $vendor)) URI::redirect('error/401');

		$form = Form::filter(Input::form());

		$product = O('product');

		if ($form['submit'] || $form['publish']) {
			$form
				->validate('type', 'not_empty', T('商品类型不能为空!'))
				->validate('name', 'not_empty', T('商品名称不能为空!'))
				->validate('catalog_no', 'not_empty', T('目录号不能为空!'))
				->validate('manufacturer_name', 'not_empty', T('生产商不能为空!'))
				->validate('brand_name', 'not_empty', T('品牌不能为空!'))
				->validate('package', 'not_empty', T('商品包装不能为空!'))
				->validate('supply_time', 'number(>0)', T('供货时间不能为零!'))
				->validate('spec', 'not_empty', T('商品规格不能为空!'));

            $product->vendor = $vendor;
			$category_input = "category_" . $form['type'];
			$category_id = (int) $form["$category_input"];
			$category = O('product_category', $category_id);

			/* 商品分类不必填(xiaopei.li@2012-04-18)
			if (!($category->id &&
				  $category->type == $form['type'] &&
				  $category->root->id != 0)) {
				$form->set_error($category_input, T('请选择商品分类!'));
			}
			*/

			try{
				$mall_brand = Config::get('mall.brand');
            	$rpc = new RPC($mall_brand['api']);
				$rpc->mall->brand->authorize($mall_brand['client_id'], $mall_brand['client_secret']);

				$mappings = array_flip((array)Config::get('mall.mapping_type'));
				$values_mapping = Config::get('mall.api_values_mapping');

				$product_type = $form['type'];
				$types[] = [
					'title' => $values_mapping[$product_type]['title'],
					'name' => $mappings[$product_type],
				];

				$params['types'] = $types;

				$brand_info = $rpc->mall->brand->getBrand($form['brand_name'], $params);
			}
			catch(Exception $e) {}

			if($brand_info['company'] && $brand_info['company'] != $form['manufacturer']) {
				$form->set_error('brand', T('该品牌与其他生产商品牌冲突!'));
			}

			//如果添加商品的生产商目录号对应的产品，超过了最大数量，则不允许添加
			if($form['manufacturer'] && $form['catalog_no']){
				$type = $form['type'];
				$max_product = Config::get('product.max_product', 10);
				$merge_criterias = Product_Model::get_merge_criterias($type);

				$options = array(
					'type' => $type,
				);

				foreach (array_keys($merge_criterias) as $merge_key) {
					$options[$merge_key] = $form[$merge_key];
				}

				$template_product = O('product', $options);
				if ($template_product->id) {
					$products = Q("product[product=$template_product]");

					if($products->total_count() >= $max_product[$type]){
						$form->set_error('catalog_no');
						$form->set_error('manufacturer', T('对应产品下的商品数量已经达到最大限制, 请联系管理员或修改生产商, 目录号'));
					}
				}
			}

			Event::trigger('form[vendor.product].submit', $product, $form);
			if ($form->no_error) {

				$product->type = $form['type'];
				$product->name = $form['name'];
				$product->catalog_no = $form['catalog_no'];
				$product->manufacturer = $form['manufacturer_name'];
				$manufacturer = O('manufacturer', array('name'=>$form['manufacturer_name']));
				$product->model = $form['model'];
				$product->spec = $form['spec'];
				$product->package = $form['package'];
				$product->description = $form['description'];
				$product->category = $category;

				if($form['keywords']) {
					$keywords = json_decode($form['keywords'], true);
					$keywords = json_encode((object)array_values($keywords), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
					$product->keywords = $keywords;
				}

				$product->supply_time = $form['supply_time'];
				$product->market_price = $form['market_price'];

				$product->unit_price = $form['price_inquiry'] ? -1 : $form['unit_price'];
				$product->stock_status = $form['stock_status'];
				$product->vendor_note = $form['vendor_note'];

				$product->brand = $form['brand_name'];
				$product->manufacturer = $form['manufacturer_name'];

                //如果得brand_ino则进行赋值矫正
				if(count($brand_info) && $brand_info['verified']){
					if($brand_info['name']){
						$product->brand = $form['brand_name'] = $brand_info['name'];
					}

					if($brand_info['company']) {
						$product->manufacturer = $form['manufacturer_name'] = $brand_info['company'];
					}
				}

				if ($product->save()) {

					Event::trigger('form[vendor.product].post_submit', $product, $form);

                    if ($form['publish']) {
                        $product->publish();
                        Site::message(Site::MESSAGE_NORMAL, T('已提交发布申请!'));
                        $log = sprintf('[vendor] %s[%d]提交了商品%s[%d]的发布申请',
                        $me->name, $me->id, $product->name, $product->id);
                        Log::add($log, 'vendor');
					    URI::redirect($product->url(NULL, NULL, NULL, 'vendor_view'));
                    }

					Product_Category_Model::replace_category($product, $product->category->id, $product->type);

					Site::message(Site::MESSAGE_NORMAL, T('商品添加成功!'));

					URI::redirect($product->url(NULL, NULL, NULL, 'vendor_view'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('商品添加失败!'));
				}
			}
		}

		$content = V('vendor:product/form', array('user'=>$me, 'form'=>$form));

		$this->layout->body->primary_tabs
			->add_tab('add', array(
				'url'=> URI::url('!vendor/product/index/add.'.$vendor->id),
				'title'=> H(T('添加商品'))
			))
			->set('content', $content)
			->select('add');

		$this->layout->title = H(T('添加商品'));
	}

	// 注意! 此方法未使用 LIMS2 中经典的 no_error 处理流程,
	// 而是另一种 View 只需知道 $form 无需知道 $object 的方式.
	// 好处就是简化 View 的编写
	// 逻辑如下:
	//
	// if ( GET ) :
	//     $form = render_form($object)
	// else :  // POST
	//     validate($form)
	//     assign($form)
	// endif
	//
	// view = v('form' = $form)
	//
	// TODO category !!  (xiaopei.li@2012-03-23)
	function edit($id = '0', $tab = 'info') {

		// TODO 下架后才可编辑产品信息, 架上可编辑销售信息(xiaopei.li@2012-03-29)

		$me = L('ME');
		$product = O('product', $id);

		$vendor = $product->vendor;
		$this->_add_tab($vendor);

		if (!$product->id) {
			URI::redirect('/error/404');
		}

		if ($product->freeze_reasons) {
			URI::redirect('/error/401');
		}

		if (!$product->can_edit() ||
			!$me->is_allowed_to('以供应商修改', $product)){
			URI::redirect('error/401');
		}

		Event::bind('product.edit.content', array($this, '_edit_info'), 0, 'info');
		Event::bind('product.edit.content', array($this, '_edit_icon'), 0, 'icon');

		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
			->add_tab('info', array(
				'url' => $product->url('info', NULL, NULL, 'vendor_edit'),
				'title' => T('基本信息')
			))
			->add_tab('icon', array(
				'url' => $product->url('icon', NULL, NULL, 'vendor_edit'),
				'title' => T('图片')
			))
			->set('class', 'secondary_tabs')
			->set('product', $product)
			->content_event('product.edit.content')
			->select($tab);
		$content = V('vendor:product/edit', array(
						'secondary_tabs' => $secondary_tabs,
						'user' => $me,
						'product' => $product,
						'form' => $form,
						));
		// 此 view 比较特殊, 要在 view 中 trigger 增加内容, 所以还需要传 $product 对象

		$breadcrumb = array(
			array(
				'url' => $product->url(NULL, NULL, NULL, 'vendor_view'),
				'title' => H($form['name'] ? : $product->name),
				),
			array(
				'url'=> $product->url(NULL, NULL, NULL, 'vendor_edit'),
				'title'=> H(T('编辑', array('%name' => $product->product->name)))
				)
			);

		$this->layout->body->primary_tabs
			->add_tab('edit', array(
						  '*' => $breadcrumb,
						  ))
			->set('content', $content)
			->select('edit');

		$this->layout->title = H(T('编辑商品'));

		if ($product->reject_reason) {
			Site::message(Site::MESSAGE_NORMAL, HT('上次申请拒绝理由: %reason', array('%reason' => $product->reject_reason)));
		}

	}

	function _edit_icon($e, $tabs) {
		$product = $tabs->product;

		if (Input::form('submit')) {
			$file = Input::file('file');
			if ($file['tmp_name']) {
				try {
					$ext = File::extension($file['name']);
					$product->save_icon(Image::load($file['tmp_name'], $ext));
					Site::message(Site::MESSAGE_NORMAL, T('商品图标已更新!'));
				}
				catch(Error_Exception $e){
					Site::message(Site::MESSAGE_ERROR, $e->getMessage());

					Site::message(Site::MESSAGE_ERROR, T('商品图标更新失败!'));
				}
			}
			else {
				Site::message(Site::MESSAGE_ERROR, T('请选择您要上传的商品图标文件'));
			}
		}

		$tabs->content = V('vendor:product/edit.icon');
	}

	function _edit_info($e, $tabs) {

		$me = L('ME');
		$form = Input::form();

		$product = $tabs->product;

		$merge_criterias = Product_Model::get_merge_criterias($product->type);
		$merge_keys = array_keys($merge_criterias);

		if (!$form) { // GET
			$form = array();

			$form['type'] = $product->type;
			$form['name'] = $product->name;
			$form['catalog_no'] = $product->catalog_no;
			$form['manufacturer_name'] = $product->manufacturer;
			$manufacturer = O('manufacturer', array('name'=>$product->manufacturer));
			if ($manufacturer->id) {
				$form['manufacturer'] = $manufacturer->id;
			}
			$form['model'] = $product->model;
			$form['spec'] = $product->spec;
			$form['package'] = $product->package;
			$form['description'] = $product->description;

			//处理keywords,{"0":"asdf","1":"test"} 转换为{"asdf":"asdf","test":"test"}
			$keywords = array_values(json_decode($product->keywords, true));
			$keywords = array_combine($keywords, $keywords);
			$keywords = json_encode($keywords);

			$form['keywords'] = $keywords;
			$form['category_' . $product->type] = $product->category->id;
			$form['unit_price'] = $product->unit_price;
			$form['stock_status'] = $product->stock_status;
			$form['vendor_note'] = $product->vendor_note;
			$form['brand_name'] = $product->brand;
			$form['supply_time'] = $product->supply_time;
			$form['market_price'] = $product->market_price;
			$extra_form = new ArrayIterator;
			Event::trigger('form[vendor.product].init', $product, $extra_form);
			$form += (array) $extra_form;

		}
		else if ($form['submit'] || $form['publish']) { // POST

			$form = Form::filter($form);

			if($product->dirty || Q("product_revision[product=$product]")->total_count()) {
				foreach ($merge_keys as $merge_key) {
					$form[$merge_key] = $product->{$merge_key};
				}
			}

			$check_fields = array(
				/*
				'type' => array(
					'check' => 'not_empty',
					'alert' => T('请选择产品类型!'),
					),
				*/
				'name' => array(
					'check' => 'not_empty',
					'alert' => T('商品名称不能为空!'),
					),
				'catalog_no' => array(
					'check' => 'not_empty',
					'alert' => T('目录号不能为空!'),
					),
				'manufacturer' => array(
					'check' => 'not_empty',
					'alert' => T('生产商不能为空!'),
					),
			    'brand' => array(
					'check' => 'not_empty',
					'alert' => T('品牌不能为空!'),
					),
				'package' => array(
					'check' => 'not_empty',
					'alert' => T('商品包装不能为空!'),
					),
				'spec' => array(
					'check' => 'not_empty',
					'alert' => T('商品规格不能为空!'),
					),
				);

			$check_fields = array_diff_key($check_fields, $merge_criterias);

			foreach ($check_fields as $check_field => $check_options) {
				$form->validate($check_field, $check_options['check'], HT($check_options['alert']));
			}

			// TODO ? 这儿与 !admin/vendor_product/approve_new() 不一样, 需检查是否有问题(xiaopei.li@2012-08-24)
			$category_input = "category_" . $product->type;

			//如果商品没有被买过，且修改了类型
			if(!$product->dirty && !Q("product_revision[product=$product]")->total_count() && $product->type != $form['type']){
				$category_input = "category_" . $form['type'];
			}
			$category_id = (int) $form[$category_input];
			$category = O('product_category', $category_id);

			/* 商品分类不必填(xiaopei.li@2012-04-18)
			if (!($category->id &&
				  $category->type == $form['type'] &&
				  $category->root->id != 0)) {
				$form->set_error($category_input, T('请选择商品分类!'));
			}
			*/
			//商品上过架，且修改了品牌名称，则进行判断

			try{
				$mall_brand = Config::get('mall.brand');
            	$rpc = new RPC($mall_brand['api']);
				$rpc->mall->brand->authorize($mall_brand['client_id'], $mall_brand['client_secret']);

				$mappings = array_flip((array)Config::get('mall.mapping_type'));
				$values_mapping = Config::get('mall.api_values_mapping');

				$product_type = $form['type'];
				$types[] = [
					'title' => $values_mapping[$product_type]['title'],
					'name' => $mappings[$product_type],
				];

				$params['types'] = $types;

				$brand_info = $rpc->mall->brand->getBrand($form['brand_name'], $params);
			}
			catch(Exception $e) {}

			if($form['brand_name'] != $product->brand || $form['manufacturer'] != $product->manufacturer){
				if($brand_info['company'] && $brand_info['company'] != $form['manufacturer']) {
					$form->set_error('brand', T('该品牌与其他生产商品牌冲突!'));
				}
			}

			Event::trigger('form[vendor.product].submit', $product, $form);

			if ($form->no_error) {

				$assign_fields = array(
					// 'type',
					'name',
					'catalog_no',
					// 'manufacturer',
					'model',
					'spec',
					'package',
					'description',
					'stock_status',
					'vendor_note',
					);
				$assign_fields = array_diff($assign_fields, $merge_keys);

				foreach ($assign_fields as $assign_field) {
					$product->{$assign_field} = $form[$assign_field];
				}

				//没人买过就可以修改type
				if($form['type']) {
					if(!$product->dirty && !Q("product_revision[product=$product]")->total_count()) {
						$product->type = $form['type'];
					}
				}

				if($form['keywords']) {
					$keywords = json_decode($form['keywords'], true);
					$keywords = json_encode((object)array_values($keywords), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
					$product->keywords = $keywords;
				}

				$product->category = $category;
				$product->supply_time = $form['supply_time'];
				$product->market_price = $form['market_price'];
				$product->unit_price = $form['price_inquiry'] ? -1 : $form['unit_price'];
				//清空拒绝理由
				$product->reject_reason = '';

				$product->brand = $form['brand_name'];

				if ($form['manufacturer_name']) {
					$product->manufacturer = $form['manufacturer_name'];
				}
				if ($form['catalog_no']) {
					$product->catalog_no = $form['catalog_no'];
				}
				if ($form['package']) {
					$product->package = $form['package'];
				}

				//如果得到brand_ino则进行赋值矫正
				if(count($brand_info) && $brand_info['verified'] && !$product->dirty){
					if($brand_info['name']){
						$product->brand = $form['brand_name'] = $brand_info['name'];
					}

					if($brand_info['company']) {
						$product->manufacturer = $form['manufacturer_name'] = $brand_info['company'];
					}
				}

				if ($product->save()) {
					Product_Category_Model::replace_category($product, $product->category->id, $product->type);

					Event::trigger('form[vendor.product].post_submit', $product, $form);

					if ($form['publish']) {
						$product->publish();
						// TODO publish 后提示未消除(xiaopei.li@2012-03-28)
						Site::message(Site::MESSAGE_NORMAL, T('上架申请已提交!'));
		                $log = sprintf('[vendor] %s[%d]提交了商品%s[%d]的上架申请',
		                $me->name, $me->id, $product->name, $product->id);
		                Log::add($log, 'vendor');
						URI::redirect($product->url(NULL, NULL, NULL, 'vendor_view'));
					}

					Site::message(Site::MESSAGE_NORMAL, T('商品修改成功!'));
	                $log = sprintf('[vendor] %s[%d]修改了商品%s[%d]',
	                $me->name, $me->id, $product->name, $product->id);
	                Log::add($log, 'vendor');

					// 下架 deprecated !!
					/*
					if ($form['edit_info']) {
						$product->unpublish();
					}
					*/

				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('商品修改失败!'));
				}

			}

		}

		$tabs->content = V('vendor:product/form', array(
							'user' => $me,
							'product' => $product,
							'form' => $form,
							'merge_keys' => $merge_keys,
							));
	}

	function publish($id) {
		$me = L('ME');
		$product = O('product', $id);
		if ($product->id) {
			if (!L('ME')->is_allowed_to('以供应商修改', $product)) URI::redirect('error/401');

			if ($product->publish()) {
				Site::message(Site::MESSAGE_NORMAL, HT('商品发布成功'));
                $log = sprintf('[vendor] %s[%d]发布商品%s[%d]成功',
                $me->name, $me->id, $product->name, $product->id);
                Log::add($log, 'vendor');
				URI::redirect($product->url(NULL, NULL, NULL, 'vendor_view'));
			}
		}
	}

	function delete($id) {
		$product = O('product', $id);
		if ($product->id) {
			if (!$product->can_delete() ||
				!L('ME')->is_allowed_to('以供应商删除', $product)) URI::redirect('error/401');
			if ($product->delete()) {
				$vid = intval($product->vendor_id);
				$db = ORM_Model::db('vendor');
				$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");
				Site::message(Site::MESSAGE_NORMAL, HT('商品删除成功'));
				URI::redirect('!vendor/product/index/index.'.$vid);
			}

			Site::message(Site::MESSAGE_ERROR, HT('商品删除失败, 该商品或已有购买记录, 但该商品未被购买过的历史版本已删除!'));
			URI::redirect($product->url(NULL, NULL, NULL, 'vendor_view'));
		}
	}
}

class Product_Index_AJAX_Controller extends AJAX_Controller {

	function index_unapprove_click() {
		$form = Input::form();
		$me = L('ME');
		$product = O('product', $form['id']);

		if (!L('ME')->is_allowed_to('以供应商修改', $product)) URI::redirect('error/401');

		if ($product->id && JS::confirm(HT('您确认下架么?'))) {

			if($product->unpublish()){
                $vid = intval($product->vendor_id);
                $db = ORM_Model::db('vendor');
                $db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");

				Site::message(Site::MESSAGE_NORMAL, HT('下架成功'));
	            $log = sprintf('[vendor] %s[%d]确认下架商品%s[%d]',
	            $me->name, $me->id, $product->name, $product->id);
	            Log::add($log, 'vendor');
				JS::redirect($product->url(NULL, NULL, NULL, 'vendor_view'));
			}
			else {
				Site::message(Site::MESSAGE_NORMAL, HT('下架失败'));
				JS::refresh();
			}
		}

	}

	function index_unpublish_click() {
		$form = Input::form();
		$me = L('ME');
		$product = O('product', $form['id']);

		if (!L('ME')->is_allowed_to('以供应商修改', $product)) URI::redirect('error/401');

		if ($product->id && JS::confirm(HT('您确认取消申请么?'))) {
			$product->unpublish();
			$vid = intval($product->vendor_id);
			$db = ORM_Model::db('vendor');
			$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");
            $log = sprintf('[vendor] %s[%d]取消了商品%s[%d]的上架申请',
            $me->name, $me->id, $product->name, $product->id);
            Log::add($log, 'vendor');
			JS::refresh();
		}
	}

	function index_freeze_click() {
		$form = Input::form();
		$me = L('ME');
		$product = O('product', $form['id']);

		if (!L('ME')->is_allowed_to('冻结/解冻', $product)) URI::redirect('error/401');

		if ($product->id && JS::confirm(HT('您确认冻结该商品吗?'))) {
			$product->freeze('vendor_freeze');
            $log = sprintf('[vendor] %s[%d]冻结了商品%s[%d]的上架申请',
            $me->name, $me->id, $product->name, $product->id);
            Log::add($log, 'vendor');
			JS::refresh();
		}
	}

	function index_unfreeze_click() {
		$form = Input::form();
		$me = L('ME');
		$product = O('product', $form['id']);

		if (!L('ME')->is_allowed_to('冻结/解冻', $product)) URI::redirect('error/401');

		if ($product->id && JS::confirm(HT('您确认解冻该商品吗?'))) {
			$product->unfreeze();
            $log = sprintf('[vendor] %s[%d]解冻了商品%s[%d]的上架申请',
            $me->name, $me->id, $product->name, $product->id);
            Log::add($log, 'vendor');
			JS::refresh();
		}
	}

	// product 可设置 icon
	// 而 product 的 icon 既可上传, 又可由管理员在 product 中选择
	function index_delete_icon_click() {
		$product = O('product', Input::form('id'));

		if (!L('ME')->is_allowed_to('以供应商修改', $product)) URI::redirect('error/401');

		if ($product->id) {
			if (JS::confirm(T('您确定要删除商品图片么?'))) {
				$product->delete_icon();
				Site::message(Site::MESSAGE_NORMAL, T('商品图片删除成功!'));
				JS::refresh();
			}
		}
	}

    //product页面中点击简单修改按钮
    function index_edit_infos_click() {
        $product = O('product', Input::form('id'));
        if (!$product->id) return FALSE;
        JS::dialog(V('vendor_product/edit.infos', array('product'=>$product)));
    }

    //product页面中点击简单修改按钮后dialog submit
    function index_edit_infos_submit() {
        $form = Form::filter(Input::form());
        $product = O('product', $form['id']);
        if (!$product->id) return FALSE;

        if ($form['submit']) {
            $product_category = O('product_category', $form['category']);

            $origin_root = Product_Category_Model::root($product->type);
            if (!$product_category->id || !$origin_root->is_itself_or_ancestor_of($product_category)) {
                $form->set_error('category', T('请选择正确类型!'));
            }

            if ($form->no_error) {
                $product->category = $product_category;

                if ($product->save()) {
                    Site::message(Site::MESSAGE_NORMAL, T('修改商品成功!'));
                }
                else {
                    Site::message(Site::MESSAGE_ERROR, T('修改商品失败!'));
                }
                JS::refresh();
            }
            else {
                JS::dialog(V('vendor_product/edit.infos', array('product'=>$product, 'form'=>$form)));
            }
        }
    }

    function index_batch_publish_submit(){
    	$me = L('ME');
		$form = Input::form();

    	if ($form['submit']) {
			if (is_array($form['select']) && count($form['select'])) {

				if (JS::confirm(HT('您确定发布这些商品么?'))) {

					$has_error = FALSE;

					$vp_ids = array_keys($form['select']);

					putenv('Q_ROOT_PATH='.ROOT_PATH);
					putenv('SITE_ID='.SITE_ID);

					$content = '';

					$script = ROOT_PATH . 'cli/publish_products.php';
					if (file_exists($script)) {

						$cmd = 'php ' . $script .' -p %vp_ids %extra';
						$cmd = strtr($cmd, array(
										 '%vp_ids' => escapeshellarg(join(',', $vp_ids)),
										 '%extra' => '-b',
										 // '%extra' => '-d -m -b', // -d is 'dry run', -m is 'merge'
										 ));

						exec($cmd, $output, $retval);

                        $log = sprintf('[vendor] %s[%d] %s[%d]批量发布 id为 %s 的商品',
                        $me->name, $me->id, $vendor->name, $vendor->id, escapeshellarg(join(',', $vp_ids)));
                        Log::add($log, 'vendor');

						if ($retval !== 0) {
							$has_error = TRUE;
					}

						$content .= join("\n", $output);
					}
					else {
						$has_error = TRUE;

						$content .= HT('缺少商品批量发布脚本');
					}

					// error_log($content);

					$message_class = $has_error ? Site::MESSAGE_ERROR : Site::MESSAGE_NORMAL;

					Site::message($message_class, nl2br(H($content)));
					JS::refresh();
				}
			}
			else {
				JS::alert(HT('请选择要发布的商品'));
			}
		}
    }

    function index_batch_unpublish_submit(){
    	$me = L('ME');
		$form = Input::form();

    	if ($form['submit']) {
			if (is_array($form['select']) && count($form['select'])) {

				if (JS::confirm(HT('您确定下架这些商品么?'))) {

					$has_error = FALSE;

					$vp_ids = array_keys($form['select']);

					putenv('Q_ROOT_PATH='.ROOT_PATH);
					putenv('SITE_ID='.SITE_ID);

					$content = '';

					$script = ROOT_PATH . 'cli/unpublish_products.php';
					if (file_exists($script)) {

						$cmd = 'php ' . $script .' -p %vp_ids %extra';
						$cmd = strtr($cmd, array(
										 '%vp_ids' => escapeshellarg(join(',', $vp_ids)),
										 '%extra' => '-b',
										 // '%extra' => '-d -m -b', // -d is 'dry run', -m is 'merge'
										 ));

						exec($cmd, $output, $retval);

						$log = sprintf('[vendor] %s[%d] %s[%d]批量下架 id为 %s 的商品',
                        $me->name, $me->id, $vendor->name, $vendor->id, escapeshellarg(join(',', $vp_ids)));
                        Log::add($log, 'vendor');


						if ($retval !== 0) {
							$has_error = TRUE;
						}

						$content .= join("\n", $output);
					}
					else {
						$has_error = TRUE;

						$content .= HT('缺少商品批量下架脚本');
					}

					//error_log($content);

					$message_class = $has_error ? Site::MESSAGE_ERROR : Site::MESSAGE_NORMAL;

					Site::message($message_class, nl2br(H($content)));
					JS::refresh();
				}
			}
			else {
				JS::alert(HT('请选择要下架的商品'));
			}
		}
    }

    function index_batch_cancel_submit(){
    	$me = L('ME');
		$form = Input::form();

    	if ($form['submit']) {
			if (is_array($form['select']) && count($form['select'])) {

				if (JS::confirm(HT('您确定取消发布这些商品么?'))) {

					$has_error = FALSE;

					$vp_ids = array_keys($form['select']);

					putenv('Q_ROOT_PATH='.ROOT_PATH);
					putenv('SITE_ID='.SITE_ID);

					$content = '';

					$script = ROOT_PATH . 'cli/cancel_publish_products.php';
					if (file_exists($script)) {

						$cmd = 'php ' . $script .' -p %vp_ids %extra';
						$cmd = strtr($cmd, array(
										 '%vp_ids' => escapeshellarg(join(',', $vp_ids)),
										 '%extra' => '-b',
										 // '%extra' => '-d -m -b', // -d is 'dry run', -m is 'merge'
										 ));

						exec($cmd, $output, $retval);

						$log = sprintf('[vendor] %s[%d]批量取消发布了id为 %s 的商品',
		                $me->name, $me->id, escapeshellarg(join(',', $vp_ids)));
		                Log::add($log, 'vendor');

						if ($retval !== 0) {
							$has_error = TRUE;
						}

						$content .= join("\n", $output);
					}
					else {
						$has_error = TRUE;

						$content .= HT('缺少批量取消待审核商品脚本');
					}

					//error_log($content);

					$message_class = $has_error ? Site::MESSAGE_ERROR : Site::MESSAGE_NORMAL;

					Site::message($message_class, nl2br(H($content)));
					JS::refresh();
				}
			}
			else {
				JS::alert(HT('请选择要取消发布的待审核商品'));
			}
		}
    }

    function index_publish_all_click(){
    	$me = L('ME');
		$form = Input::form();
		$vendor = O('vendor', $form['vid']);

		if(!$vendor->id && $vendor->owner->id !=$me->id){
			return;
		}

		if($vendor->publish_vp_pid){
			JS::alert(HT('批量发布脚本正在执行!'));
			return;
		}

		if (JS::confirm(HT('您确定发布所有商品么?'))) {
			$products = Q("product[vendor={$vendor}][!publish_date]");
			if(!$products->total_count()){
				JS::alert(HT('您没有未发布的商品!'));
				return;
			}

			putenv('Q_ROOT_PATH='.ROOT_PATH);
			putenv('SITE_ID='.SITE_ID);

			$content = '';

			$script = ROOT_PATH . 'cli/publish_products.php';
			if (file_exists($script)) {

				$cmd = 'php ' . $script .' -v %vid %extra > /dev/null 2>&1 & echo $!';
				$cmd = strtr($cmd, array(
								 '%vid' => $vendor->id,
								 '%extra' => '-b',
								 // '%extra' => '-d -m -b', // -d is 'dry run', -m is 'merge'
								 ));

				exec($cmd, $output, $retval);
				$pid = $output[0];

                $log = sprintf('[vendor] %s[%d]发布 %s[%d] 的所有商品',
                $me->name, $me->id, $vendor->name, $vendor->id);
                Log::add($log, 'vendor');

				if($pid) {
					$vendor->publish_vp_pid = $pid;
					$vendor->last_publish_products_result = '';
					$vendor->save();
				}
			}
			else {

				$content = HT('缺少商品批量发布脚本');
				Site::message(Site::MESSAGE_ERROR, nl2br(H($content)));
			}

			JS::refresh();
		}
    }

    function index_cancel_all_click(){
    	$me = L('ME');
		$form = Input::form();
		$vendor = O('vendor', $form['vid']);

		if(!$vendor->id && $vendor->owner->id !=$me->id){
			return;
		}

		if($vendor->cancel_vp_pid){
			JS::alert(HT('批量取消待审核商品脚本正在执行!'));
			return;
		}

		if (JS::confirm(HT('您确定取消发布所有商品么?'))) {
			$products = Q("product[vendor={$vendor}][publish_date>0][!approve_date]");
			if(!$products->total_count()){
				JS::alert(HT('您没有待审核的商品!'));
				return;
			}

			putenv('Q_ROOT_PATH='.ROOT_PATH);
			putenv('SITE_ID='.SITE_ID);

			$content = '';

			$script = ROOT_PATH . 'cli/cancel_publish_products.php';
			if (file_exists($script)) {

				$cmd = 'php ' . $script .' -v %vid %extra > /dev/null 2>&1 & echo $!';
				$cmd = strtr($cmd, array(
								 '%vid' => $vendor->id,
								 '%extra' => '-b',
								 // '%extra' => '-d -m -b', // -d is 'dry run', -m is 'merge'
								 ));

				exec($cmd, $output, $retval);
				$pid = $output[0];

				$log = sprintf('[vendor] %s[%d]取消发布了 %s[%d]的所有商品',
                $me->name, $me->id, $vendor->name, $vendor->id);
                Log::add($log, 'vendor');

				if($pid) {
					$vendor->cancel_vp_pid = $pid;
					$vendor->last_cancel_products_result = '';
					$vendor->save();
				}
			}
			else {

				$content = HT('缺少批量取消待审核商品脚本');
				Site::message(Site::MESSAGE_ERROR, nl2br(H($content)));
			}

			JS::refresh();
		}
    }

    function index_unpublish_all_click(){
    	$me = L('ME');
		$form = Input::form();
		$vendor = O('vendor', $form['vid']);

		if(!$vendor->id && $vendor->owner->id !=$me->id){
			return;
		}

		if($vendor->unpublish_vp_pid){
			JS::alert(HT('批量下架脚本正在执行!'));
			return;
		}

		if (JS::confirm(HT('您确定下架所有商品么?'))) {
			$products = Q("product[vendor={$vendor}][publish_date][approve_date]");
			if(!$products->total_count()){
				JS::alert(HT('您没有已上架的商品!'));
				return;
			}

			$has_error = FALSE;

			putenv('Q_ROOT_PATH='.ROOT_PATH);
			putenv('SITE_ID='.SITE_ID);

			$content = '';

			$script = ROOT_PATH . 'cli/unpublish_products.php';
			if (file_exists($script)) {

				$cmd = 'php ' . $script .' -v %vid %extra > /dev/null 2>&1 & echo $!';
				$cmd = strtr($cmd, array(
								 '%vid' => $vendor->id,
								 '%extra' => '-b',
								 // '%extra' => '-d -m -b', // -d is 'dry run', -m is 'merge'
								 ));

				exec($cmd, $output);
				$pid = $output[0];

				$log = sprintf('[vendor] %s[%d] 下架所有 %s[%d] 的商品',
                $me->name, $me->id, $vendor->name, $vendor->id, escapeshellarg(join(',', $vp_ids)));
                Log::add($log, 'vendor');

				if($pid) {
					$vendor->unpublish_vp_pid = $pid;
					$vendor->last_unpublish_products_result = '';
					$vendor->save();
				}

			}
			else {
				$has_error = TRUE;

				$content = HT('缺少商品批量下架脚本');

				Site::message(Site::MESSAGE_ERROR, nl2br(H($content)));
			}

			JS::refresh();
		}
    }
}
