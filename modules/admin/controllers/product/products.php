<?php

class Product_Products_Controller extends Product_Base_Controller {

    function index($tab = 'unapproved') {
        $secondary_tabs = Widget::factory('tabs');
        /*
		$reminder = FALSE;
		if (Q('product[!newer][publish_date][!approve_date]:limit(1)')->length()) {
			$reminder = TRUE;
		}
		*/
        $secondary_tabs
            ->add_tab('unapproved', array(
                'url'=>URI::url('!admin/product/products/index.unapproved'),
                'title'=>T('待审核'),
                'reminder'=>$reminder,
			))
            ->add_tab('approved', array(
                'url'=>URI::url('!admin/product/products/index.approved'),
                'title'=>T('已审核'),
            ))
            ->set('class', 'secondary_tabs')
            ->select($tab);

        $tab = $secondary_tabs->selected;
		$form = Site::form();

		$perpage = 20;
		if ($form['phrase']) {
			$name = trim($form['phrase']);
		}

		$start = (int) $form['st'];

        $products = Product_Model::getProducts($start, $perpage, $tab, $name?:'');
/*
    	$opt = array(
			'phrase' => $form['phrase'],
			'status' => $tab,
		);

        if (preg_match('/^\d{2,7}-\d{2}-\d$/', $form['phrase'])) {
        	$opt['type'] = 'reagent';
        }

		$products = new Search_Product($opt);
*/
        // $start = (int)$form['st'];
        // $pagination = Site::pagination($products, $start, $per_page);
        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start'=> $start,
            'per_page'=> $perpage,
            'total'=> $products['total']?:0,
            'total_found'=> $products['count']?:0,
            'url'=> null
        ]);


        $this->layout->body->primary_tabs
        	->select('products')
        	->set('content', V('admin:vendor_product/list', array(
        		'pagination'=>$pagination,
        		'products'=>$products['data']?:[],
        		'form'=>$form,
        		'secondary_tabs'=>$secondary_tabs,
        	)));

    }

	function approve($id) {

		$me = L('ME');
		$product = O('product', $id);
		if (!$product->id) {
			URI::redirect('/error/404');
		}

		$not_allow_msg = Event::trigger('product.get_not_allow_approve_msg', $product);
		if ($not_allow_msg) {
			// TODO 这里 not_allow_msg 会被两次 HT() (xiaopei.li@2012-05-24)
			Site::message(Site::MESSAGE_ERROR, HT('该商品不能通过审核, 原因是: %reason', array(
													  '%reason' => $not_allow_msg,
													  )));
			URI::redirect($product->url(NULL, NULL, NULL, 'admin_view'));
		}

		$this->layout->title = HT('审核商品');

		$breadcrumb = array(
			array(
				'url' => $product->url(NULL, NULL, NULL, 'admin_view'),
				'title' => H($form['name'] ? : $product->name),
				),
			array(
				'url'=> $product->url(NULL, NULL, NULL, 'admin_approve'),
				'title'=> HT('审核', array('%name' => $product->product->name))
				)
			);

		$this->layout->body->primary_tabs
			->add_tab('edit', array(
						  '*' => $breadcrumb,
						  ))
			->select('edit');

		$this->layout->title = HT('审核商品');

		Log::add(strtr('[admin] %user_name[%user_id] 对商品 %product_name[%product_id]进行了审核的操作', array('%user_name'=>$me->name, '%user_id'=>$me->id, '%product_name'=>$product->name, '%product_id'=>$product->id)), 'journal');
		//判断是否有last_publish_date来判断是否下架过
		if($product->last_publish_date > 0){
			$product->approve();
			$vid = intval($product->vendor_id);
			$db = ORM_Model::db('vendor');
			$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");
			URI::redirect('!admin/product/products');
		}
		else{
			/*
			*如果没有last_publish_date属性，说明是新发布的商品，需要生成新产品
			*不再有模板相关的限制
			* TODO approve_new直接写在approve()中
			*/
			return $this->approve_new($id);
		}
	}
	/*
	* --delete_product
	* approve_merge 方法不再需要了，全部为approve_new
	* private function approve_merge($id) {}
	*/

	private function approve_new($id) {

		$me = L('ME');
		$product = O('product', $id);
		if (!$product->id) {
			URI::redirect('/error/404');
		}

		/*
		需要用merge_criterias 来确认要validate哪些表单数据 保留
		*/
		$merge_criterias = Product_Model::get_merge_criterias($product->type);
		$merge_keys = array_keys($merge_criterias);

		$form = Input::form();

		if (!$form) { // GET
			$form = array();

			$form['type'] = $product->type;
			$form['name'] = $product->name;
			$form['catalog_no'] = $product->catalog_no;
			$form['manufacturer'] = $product->manufacturer;
			$form['model'] = $product->model;
			$form['spec'] = $product->spec;
			$form['package'] = $product->package;
			$form['description'] = $product->description;
			$form['keywords'] = $product->keywords;
			$form['category'] = $product->category->id;
			$form['brand'] = $product->brand;

			$extra_form = new ArrayIterator;
			Event::trigger('form[admin.product].approve.init', $product, $extra_form);
			$form += (array) $extra_form;
		}
		else if ($form['submit']) { // POST
			$form = Form::filter($form);

			$check_fields = array (
				'name' => array(
					'check' => 'not_empty',
					'alert' => '商品名称不能为空!',
					),
				'catalog_no' => array(
					'check' => 'not_empty',
					'alert' => '商品编号不能为空!',
					),
				'manufacturer' => array(
					'check' => 'not_empty',
					'alert' => '生产商不能为空!',
					),
				'package' => array(
					'check' => 'not_empty',
					'alert' => '商品包装不能为空!',
					),
				'spec' => array(
					'check' => 'not_empty',
					'alert' => '商品规格不能为空!',
					),
			);

			$check_fields = array_diff_key($check_fields, $merge_criterias);

			foreach ($check_fields as $check_field => $check_options) {
				$form->validate($check_field, $check_options['check'], HT($check_options['alert']));
			}

			/*
			* --delete_product
			*验证product中是否存在类型不同但是criterias相同的产品，暂时删除，后期转换为product的操作
			*再做考虑
			*/

			$category = O('product_category', $form['category']);

			if ($form->no_error) {
				$assign_fields = array(
					'name',
					'catalog_no',
					'brand',
					'manufacturer',
					'model',
					'package',
					'spec',
					'description',
					'keywords',
					);
				$assign_fields = array_diff($assign_fields, $merge_keys);

				foreach ($assign_fields as $assign_field) {
					$product->{$assign_field} = $form[$assign_field];
				}

				$product->category = $category;
				$product = ORM_Model::refetch($product);

				if ($form['has_expire_date']) {
					$product->expire_date = $form['expire_date'];
				}
				$product->note = $form['note']; // admin note

				if ($product->approve()) {
					$vid = intval($product->vendor_id);
					$db = ORM_Model::db('vendor');
					$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");
				}

				Site::message(Site::MESSAGE_NORMAL, T('审核已通过!'));
                $log = sprintf('[admin] %s[%d]确认通过商品%s[%d]的审核',
                $me->name, $me->id, $product->name, $product->id);
                Log::add($log, 'vendor');
				URI::redirect($product->url(NULL, NULL, NULL, 'admin_view'));

			}
		}

		$content = V('admin:vendor_product/approve_new',
					 array('user' => $me,
						   'product' => $product,
						   'form' => $form,
						   'merge_keys' => $merge_keys,
						 ));

		$this->layout->body->primary_tabs
			->set('content', $content);

	}

	function view($id = 0, $tab = 'info') {

		$form = Input::form();

		// $product = RProduct_Model::getProduct($id);
		$product = O('product', $id);

		if ($form['version'] && $form['version'] != $product->version) {
			$this->snapshot($id, $tab);
			return;
		}

		if (!$product->id) URI::redirect('error/404');

		$this->layout->body->primary_tabs
			->add_tab('view', array(
						  'url' => URI::url('!admin/product/products/view.'.$product->id),
						  'title' => H($product->name),
						  ))
			->select('view');

		Event::bind('admin.product.view.content', array($this, '_view_info'), 0, 'info');

		$secondary_tabs = Widget::factory('tabs');

		$secondary_tabs->add_tab('info', array(
									 'url' => URI::url('!admin/product/products/view.'.$product->id),
									 'title' => T('基本信息'),
									 'weight' => 0,
									 ));

		Event::bind('admin.product.view.content', array($this, '_view_snapshots'), 0, 'snapshots');
		$secondary_tabs->add_tab('snapshots', array(
										 'url' => URI::url('!admin/product/products/view.'.$product->id.'.snapshots'),
										 'title' => T('历史版本'),
										 ));

		$secondary_tabs->set('product', $product)
			->tab_event('admin.product.view.tab')
			->content_event('admin.product.view.content')
			->select($tab);


		$this->layout->body->primary_tabs->content = V('admin:vendor_product/view', array(
							   'product'=>$product,
							   'secondary_tabs' => $secondary_tabs,
							   ));
	}

	function snapshot($id = 0, $tab = 'info') {

		$form = Site::form();
		$snapshot = O('product_revision', $id);
		if (!$snapshot->id) URI::redirect('error/404');

		$this->layout->body->primary_tabs
			->add_tab('view', array(
						  'url' => URI::url(),
						  'title' => H($snapshot->name),
						  ))
			->select('view');

		Event::bind('admin.snapshot.view.content', array($this, '_snapshot_view_info'), 0, 'info');

		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs->add_tab('info', array(
									 'url' => URI::url(),
									 'title' => T('基本信息'),
									 'weight' => 0,
									 ));
		$links = '<a class="blue"  href="'.URI::url('!admin/product/products/view.'.$snapshot->product_id).'" >'.$snapshot->name.'</a>';;
		Site::message(Site::MESSAGE_NORMAL,
			T('您查看的是一个历史快照, %alt_link', array(
			           '%alt_link' => $links,
			           )));

		$secondary_tabs->set('snapshot', $snapshot)
			->tab_event('admin.snapshot.view.tab')
			->content_event('admin.snapshot.view.content')
			->select($tab);



		$this->layout->body->primary_tabs->content = V('admin:vendor_product/snapshot/view', array(
							   'product'=>$snapshot,
							   'secondary_tabs' => $secondary_tabs,
							   ));
	}

	function _snapshot_view_info($e, $tabs) {

		$snapshot = $tabs->snapshot;
		$sections = new ArrayIterator;
		$sections[] = V('admin:vendor_product/view.info.general', array(
							'product' => $snapshot
							));

		Event::trigger('admin.product.view.info.sections', $snapshot, $sections);

		$sections[] = V('admin:vendor_product/snapshot/view.info.price', array(
							'product' => $snapshot
							));

		$tabs->content = V('admin:vendor_product/view.info', array('sections' => $sections));
	}

	function _view_info($e, $tabs) {
		$product = $tabs->product;

		$sections = new ArrayIterator;
		$sections[] = V('admin:vendor_product/view.info.general', array(
							'product' => $product
							));

		Event::trigger('admin.product.view.info.sections', $product, $sections);

		$sections[] = V('admin:vendor_product/view.info.price', array(
							'product' => $product
							));

		$tabs->content = V('admin:vendor_product/view.info', array('sections' => $sections));
	}

	function _view_snapshots($e, $tabs) {
        $product = $tabs->product;

		$form = Site::form();

		// $selector = "product_revision[product={$product}]";

		// $selector .= ':sort(ctime DESC)';
		// $snapshots = Q($selector);

		$start = (int) $form['st'];

		$per_page = 20;

		$data = $product->getRevisions($start, $per_page);
		$start = $start - ($start % $per_page);
		$snapshots = $data['revisions'];
        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start'=> $start,
            'per_page'=> $per_page,
            'total'=> $data['total']?:0,
            'total_found'=> $data['count']?:0,
            'url'=> null
        ]);

		$tabs->content = V('admin:vendor_product/view.snapshots', array(
							   'snapshots' => $snapshots,
							   'pagination' => $pagination,
							   ));
	}

}

class  Product_Products_AJAX_Controller extends AJAX_Controller {
	function index_approve_click() {
		$me = L('ME');
		$form = Input::form();
		$id = $form['id'];
		$product = O('product', $id);
		if (!$id || !$product->id) return FALSE;
		if (JS::confirm(HT('您确定审批该商品么?'))) {
			if ($product->approve()) {
				Site::message(Site::MESSAGE_NORMAL, HT('审批成功'));
				JS::refresh();
			}
		}
	}

	function index_unapprove_click() {
		$form = Input::form();
		$product = O('product', $form['id']);
		if ($product->id) {
			JS::dialog(V('admin:vendor_product/unapprove',
						 [
						 	'product' => $product,
						 ]));
		}
	}

	function index_unapprove_submit() {
		$form = Input::form();
		$product = O('product', $form['id']);

		if (!$form['reject_reason']) {
			JS::alert(T('请填写拒绝理由!'));
			return;
		}

		if ($product->id) {
			if ($product->soldout($form['reject_reason'])) {
				Site::message(Site::MESSAGE_NORMAL, HT('拒绝成功'));
				JS::refresh();
			}
		}
	}

	function index_soldout_click() {
		$form = Input::form();
		$product = O('product', $form['id']);
		if ($product->id) {
			JS::dialog(V('admin:vendor_product/soldout',
						 [
						 	'product' => $product,
						 ]));
		}
	}

	function index_soldout_submit() {
		$form = Input::form();
		$product = O('product', $form['id']);

		if (!$form['soldout_reason']) {
			JS::alert(T('请填写下架理由!'));
			return;
		}

		if ($product->id) {
			if ($product->soldout($form['soldout_reason'])) {
				Site::message(Site::MESSAGE_NORMAL, HT('下架成功'));
				JS::refresh();
			}
		}
	}

/*
	function index_unpublish_click() {
		$form = Input::form();
		$product = O('product', $form['id']);

		if ($product->id) {
			JS::dialog(V('admin:vendor_product/unpublish',
						 array('product' => $product)));
		}
	}

	function index_unpublish_submit() {
		$form = Input::form();
		$product = O('product', $form['id']);

		if (!$form['reject_reason']) {
			JS::alert(T('请填写拒绝理由!'));
			return;
		}

		if ($product->id) {
			$product->unpublish($form['reject_reason']);
			$vid = intval($product->vendor_id);
			$db = ORM_Model::db('vendor');
			$db->query("UPDATE vendor SET product_count=(SELECT COUNT(*) FROM product WHERE vendor_id=$vid AND approve_date>0) WHERE id=$vid");
            Site::message(Site::MESSAGE_NORMAL, T('已驳回该商品的审核申请!'));
			JS::redirect($product->url(NULL, NULL, NULL, 'admin_view'));
		}
	}
*/

	function index_batch_approve_submit() {
		$me = L('ME');
		$form = Input::form();

		if ($form['submit']) {
			if (is_array($form['select']) && count($form['select'])) {

				if (JS::confirm(HT('您确定审批这些商品么?'))) {

					$has_error = FALSE;

					$vp_ids = array_keys($form['select']);

					putenv('Q_ROOT_PATH='.ROOT_PATH);
					putenv('SITE_ID='.SITE_ID);

					$content = '';

					$script = ROOT_PATH . 'cli/approve_products.php';
					if (file_exists($script)) {
						$ids = implode(', ', $vp_ids);
						Log::add(strtr('[admin] %user_name[%user_id] 对id为 %ids的商品进行了批量审批的操作', array('%user_name'=>$me->name, '%user_id'=>$me->id, '%ids'=>$ids)), 'journal');
						$cmd = 'php ' . $script .' -p %vp_ids %extra';
						$cmd = strtr($cmd, array(
										 '%vp_ids' => escapeshellarg(join(',', $vp_ids)),
										 '%extra' => '-b',
										 // '%extra' => '-d -m -b', // -d is 'dry run', -m is 'merge'
										 ));

						exec($cmd, $output, $retval);

						if ($retval !== 0) {
							$has_error = TRUE;
						}

						$content .= join("\n", $output);
					}
					else {
						$has_error = TRUE;

						$content .= HT('缺少商品批量审批脚本');
					}

					// error_log($content);

					$message_class = $has_error ? Site::MESSAGE_ERROR : Site::MESSAGE_NORMAL;

					Site::message($message_class, nl2br(H($content)));
					JS::refresh();
				}
			}
			else {
				JS::alert(HT('请选择要审核的商品'));
			}
		}
	}

	function index_delete_icon_click() {
		$product = O('product', Input::form('id'));

		if ($product->id) {
			if (JS::confirm(T('您确定要删除商品图片么?'))) {
				$product->delete_icon();
				Site::message(Site::MESSAGE_NORMAL, T('商品图片删除成功!'));
				JS::refresh();
			}
		}
	}

}
