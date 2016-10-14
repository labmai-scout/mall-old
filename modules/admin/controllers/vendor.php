<?php

class Vendor_Controller extends Layout_Admin_Controller {

	function _before_call($method, &$params) {
		if (!L('ME')->access('管理供应商')) {
			URI::redirect('error/401');
		}

		parent::_before_call($method, $params);

		$this->layout->title = T('供应商管理');
		$this->layout->body = V('admin:vendor/body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');

		$this->layout->body->primary_tabs
			->add_tab('index', array(
						  'url'=>URI::url('!admin/vendor/'),
						  'title'=>T('供应商列表')
						  ));

		$this->add_css('admin:vendor');
    }

    function image($id, $type, $index=null)
    {
        header('Content-type: image/png');

        $me = L('ME');
        if (!$me->id) exit;
        $vendor = O('vendor', $id);
        if (!$vendor->id) exit;
        $rvendor = O('rvendor', $vendor->gapper_group);
        if (!$rvendor->id) exit;

        $img = (string)$rvendor->getCertImage($type, $index);

        if ($img) {
            @file_put_contents('php://output', $img);
        }
        else {
            @file_put_contents('php://output', @file_get_contents(APP_PATH . PRIVATE_BASE . 'icons/vendor/128.png'));
        }
        exit;
    }

    function iview($vid, $rvid=null)
    {
        $id = $vid;
        if ($id) {
            return $this->view($id);
        }
        $vendor = O('vendor', ['gapper_group'=>$rvid]);
        if ($vendor->id) {
            return $this->view($vendor->id);
        }
        if ($rvid) {
            $rvendor = O('rvendor', $rvid);
            $vendor = $rvendor->create();
            if ($vendor && $vendor->id) {
                return $this->view($vendor->id);
            }
        }
        URI::redirect('error/404');
    }

    function approve($id, $rvid=null)
    {
        if ($id) {
            $vendor = O('vendor', $id);
            $rvendor = O('rvendor', $vendor->gapper_group);
        }
        else if ($rvid) {
            $rvendor = O('rvendor', $rvid);
        }
        if (!$rvendor || !$rvendor->id) {
            URI::redirect('error/404');
        }
        $scopes = $rvendor->getScopes();
        $requesting = $rvendor->getRequestingScopes();
        $content = V('admin:vendor/view/info', [
            'vendor' => $rvendor,
            'scopes'=> $scopes,
            'allowApprove'=> !empty($requesting)
        ]);
		$this->layout->body->primary_tabs
			->add_tab('approve', array(
						  'url' => '#',
						  'title' => H(T('审核供应商: '.$rvendor->abbr))
						  ))
			->select('approve')
			->set('content', $content);
    }

    function index($tab='unapproved') {


        if (!in_array($tab, [
            'approved',
            'unapproved'
        ])) {
            $tab = 'unapproved';
        }

		$form = Form::filter(Input::form());

		$perpage = 20;
		if ($form['phrase']) {
			$name = trim($form['phrase']);
		}

		$start = (int) $form['st'];

        if ($tab=='unapproved') {
            $vendors = RVendor_Model::getUnderApproval($start, $perpage, $name?:'');
        }
        else {
            $vendors = RVendor_Model::getApproved($start, $perpage, $name?:'');
        }

		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
			->add_tab('unapproved', array(
						  'url' => URI::url('!admin/vendor/index.unapproved'),
						  'title' => T('待审核'),
                          // 没有展示条数的必要了
						  //'number' => $unapproved['total']
                      ))
			->add_tab('approved', array(
						  'url' => URI::url('!admin/vendor/index.approved'),
                          'title' => T('已审核'),
                          // 没有展示条数的必要了
						  //'number' => $approved['total']
						  ))
			->set('class', 'secondary_tabs')
			->select($tab);


        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start'=> $start,
            'per_page'=> $perpage,
            'total'=> $vendors['total']?:0,
            'total_found'=> $vendors['count']?:0,
            'url'=> null
        ]);

		$content = V('admin:vendor/list', array(
						 'secondary_tabs'=>$secondary_tabs,
						 'pagination'=>$pagination,
						 'vendors'=>$vendors['data']?:[],
                         'form'=>$form,
                         'type'=> $tab
						 ));

		$this->layout->body->primary_tabs
			->select('index')
			->set('content', $content);
	}

	function add() {
        // 禁止在mall-old进行供应商的添加操作
        URI::redirect('error/401');
        return;

		$me = L('ME');

		$form = Form::filter(Input::form());

		if ($form['submit']) {

			$form->validate('name', 'not_empty', T('供应商名称不能为空!'))
				->validate('license_no', 'not_empty', T('营业执照注册号不能为空!'))
				->validate('scope', 'not_empty', T('经营范围不能为空!'))
				->validate('short_name', 'not_empty', T('供应商简称不能为空!'))
				->validate('email', 'not_empty', HT('请填写联系邮箱!'))
				->validate('email', 'is_email', HT('联系邮箱格式有误!'))
				->validate('bank_name', 'not_empty', HT('请填写开户行!'))
				->validate('bank_account', 'not_empty', HT('请填写开户行帐号!'))
				->validate('province', 'not_empty', HT('请填写开户行省份!'))
				->validate('city', 'not_empty', HT('请填写开户行城市!'))
				;

			$owner = O('user', $form['owner']);
			if (!$owner->id) {
				$form->set_error('owner', HT('请选择供应商负责人!'));
			}

			if ($form->no_error) {

				$vendor = O('vendor');
				$vendor->owner = $owner;

				$vendor->creator = $me;
				$vendor->create_date = Date::time();
				$vendor->name = $form['name'];

				$vendor->short_name = $form['short_name'];
                $vendor->short_abbr = PinYin::code($form['short_name']);
				$vendor->alt_name = $form['alt_name'];
				$vendor->owner_name = $form['owner_name'];
				$vendor->manager_name = $form['manager_name'];
				$vendor->manager_phone = $form['manager_phone'];
				$vendor->contact_name = $form['contact_name'];
				$vendor->contact_phone = $form['contact_phone'];
				$vendor->phone = $form['phone'];
				$vendor->fax = $form['fax'];
				$vendor->address = $form['address'];
				$vendor->postcode = $form['postcode'];
				$vendor->email = $form['email'];
				$vendor->homepage = $form['homepage'];
				$vendor->license_no = $form['license_no'];
				$vendor->license_valid_date = $form['license_valid_date'];
				$vendor->license_last_valid_date = $form['license_last_valid_date'];
				$vendor->establish_date = $form['establish_date'];
				$vendor->operation_due = $form['operation_due'];
				$vendor->capital = $form['capital'];
				$vendor->nemployees = $form['nemployees'];
				$vendor->scope = $form['scope'];
				$vendor->description = $form['description'];
				$vendor->hazardous_article_scope = $form['hazardous_article_scope'];
				$vendor->precursor_scope = $form['precursor_scope'];

				$vendor->bank_account = $form['bank_account'];
				$vendor->bank_name = $form['bank_name'];
				$vendor->province = $form['province'];
				$vendor->city = $form['city'];


				if ($form['has_expire_date']) {
					$vendor->expire_date = $form['expire_date'];
				}

				$vendor->note = $form['note'];

				if ($vendor->save()) {

					$vendor->connect($vendor->owner, 'member');

					foreach ((array)$form['scope_allowed'] as $name => $allowance) {
						$scope = O('vendor_scope', array('vendor'=>$vendor, 'name'=>$name));
						if ($allowance == 'on') {
							if (!$scope->id) {
								$scope->vendor = $vendor;
								$scope->name = $name;
							}

							$scope->expire_date_from = (int)($form['scope_expire_date_from'][$name] ?: Date::time());
							$scope->expire_date = (int)($form['scope_expire_date_to'][$name] ?: Date::time());
                            //矫正
                            if ($scope->expire_date_from > $scope->expire_date) {
                                list($scope->expire_date, $scope->expire_date_from) = array($scope->expire_date_from, $scope->expire_date);
                            }
							$scope->save();
						}
					}

					switch ($form['activate']) {
					case 'pend':
						if (!$vendor->publish_date) {
							$vendor->publish();
						}
						break;
					case 'pass':
						if (!$vendor->approve_date) {
							$vendor->approve();
						}
						break;
					case 'reject':
						$vendor->unpublish($form['reject_reason']);
						break;
					default:
					}

					Site::message(Site::MESSAGE_NORMAL, T('添加供应商成功!'));
					URI::redirect($vendor->url(NULL, NULL, NULL, 'admin_view'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('添加供应商失败! 请与系统管理员联系.'));
				}

			}

		}

		$content = V('admin:vendor/add', array(
						 'form' => $form
						 ));

		$this->layout->body->primary_tabs
			->add_tab('add', array(
						  'url' => URI::url('!admin/vendor/add'),
						  'title' => T('添加供应商')
						  ))
			->set('content', $content)
			->select('add');

    }

	function view($id = 0, $tab = 'info', $stab = NULL)
    {
		$me = L('ME');
        //$vendor = O('rvendor', $id);
        $vendor = O('vendor', $id);
        if (!$vendor->id) URI::redirect('error/404');

	 	if ($me->is_allowed_to('批量审批商家商品', $vendor) &&
	 		$vendor->last_approve_products_result) {

	 		$last_time = $vendor->last_approve_products_time ?
	 			Date::format($vendor->last_approve_products_time) : "";

	 		$message_class = Site::MESSAGE_NORMAL;
	 		if ((int)$vendor->last_approve_products_retval > 0) {
	 			$message_class = Site::MESSAGE_ERROR;
	 		}

	 		SITE::message($message_class, nl2br($last_time ." 批量审批结果:\n" .
	 					$vendor->last_approve_products_result));
	 	}

	 	$secondary_tabs = Widget::factory('tabs');

	 	Event::bind('admin.vendor.view.content', array($this, '_view_info'), 0, 'info');
	 	Event::bind('admin.vendor.view.content', array($this, '_view_product'), 0, 'product');
	 	// Event::bind('admin.vendor.view.content', array($this, '_view_vendor_product_upload'), 0, 'upload');

	 	$secondary_tabs
	 		->add_tab('info', array(
	 					  'title' => T('基本信息'),
	 					  'url' => $vendor->url('info', NULL, NULL, 'admin_view'),
	 					  ))
	 	    ->add_tab('product', array(
	 					  'title' => T('商品信息'),
	 					  'url' => $vendor->url('product', NULL, NULL, 'admin_view'),
	 					  ))

	 		// ->add_tab('upload', array(
	 		// 			  'url' => $vendor->url('upload', NULL, NULL, 'admin_view'),
	 		// 			  'title' => T('商品上传')
	 		// 			  ))

	 		->tab_event('admin.vendor.view.tab')
	 		->content_event('admin.vendor.view.content')
	 		->set('vendor', $vendor)
	 		->set('stab', $stab)
	 		->select($tab);

		$content = V('admin:vendor/view/index', array(
            'vendor'=>$vendor,
            'secondary_tabs' => $secondary_tabs
         ));

		$this->layout->body->primary_tabs
			->add_tab('profile', array(
						  'url'=> $vendor->url(NULL, NULL, NULL, 'admin_view'),
						  'title'=> T('%name', array('%name'=>H($vendor->abbr ?: $vendor->name))),
						  ))
			->set('content', $content)
			->select('profile');

		$this->layout->title = H($vendor->name);

    }

	// TODO view/edit 方法需要从 !vendor 同步(xiaopei.li@2012-03-22)
	//// function view($id = 0, $tab = 'info', $stab = NULL) {

	//// 	$vendor = O('vendor', $id);
	//// 	$me = L('ME');

	//// 	if (!$vendor->id) URI::redirect('error/404');

	//// 	if ($me->is_allowed_to('批量审批商家商品', $vendor) &&
	//// 		$vendor->last_approve_products_result) {

	//// 		$last_time = $vendor->last_approve_products_time ?
	//// 			Date::format($vendor->last_approve_products_time) : "";

	//// 		$message_class = Site::MESSAGE_NORMAL;
	//// 		if ((int)$vendor->last_approve_products_retval > 0) {
	//// 			$message_class = Site::MESSAGE_ERROR;
	//// 		}

	//// 		SITE::message($message_class, nl2br($last_time ." 批量审批结果:\n" .
	//// 					$vendor->last_approve_products_result));
	//// 	}

	//// 	$secondary_tabs = Widget::factory('tabs');

	//// 	Event::bind('admin.vendor.view.content', array($this, '_view_info'), 0, 'info');
	//// 	Event::bind('admin.vendor.view.content', array($this, '_view_product'), 0, 'product');
	//// 	Event::bind('admin.vendor.view.content', array($this, '_view_vendor_members'), 0, 'members');
	//// 	Event::bind('admin.vendor.view.content', array($this, '_view_vendor_product_upload'), 0, 'upload');

	//// 	$secondary_tabs
	//// 		->add_tab('info', array(
	//// 					  'title' => T('基本信息'),
	//// 					  'url' => $vendor->url('info', NULL, NULL, 'admin_view'),
	//// 					  ))
	//// 	    ->add_tab('product', array(
	//// 					  'title' => T('商品信息'),
	//// 					  'url' => $vendor->url('product', NULL, NULL, 'admin_view'),
	//// 					  ))

	//// 		->add_tab('members', array(
	//// 					  'title' => T('成员列表'),
	//// 					  'url' => $vendor->url('members', NULL, NULL, 'admin_view')
	//// 					  ))
	//// 		->add_tab('upload', array(
	//// 					  'url' => $vendor->url('upload', NULL, NULL, 'admin_view'),
	//// 					  'title' => T('商品上传')
	//// 					  ))

	//// 		->tab_event('admin.vendor.view.tab')
	//// 		->content_event('admin.vendor.view.content')
	//// 		->set('vendor', $vendor)
	//// 		->set('stab', $stab)
	//// 		->select($tab);

	//// 	$content = V('admin:vendor/view/index', array(
	//// 					 'vendor'=>$vendor,
	//// 					 'secondary_tabs' => $secondary_tabs
	//// 					 ));

	//// 	$this->layout->body->primary_tabs
	//// 		->add_tab('profile', array(
	//// 					  'url'=> $vendor->url(NULL, NULL, NULL, 'admin_view'),
	//// 					  'title'=> T('%name', array('%name'=>H($vendor->name))),
	//// 					  ))
	//// 		->set('content', $content)
	//// 		->select('profile');

	//// 	$this->layout->title = H($vendor->name);


	////  }

	function _view_info($e, $tabs) {
        $vendor = $tabs->vendor;
        $rvendor = O('rvendor', $vendor->gapper_group);
        $tabs->content = V('admin:vendor/view/info', [
            'vendor' => $rvendor,
            'scopes'=> $rvendor->getScopes(),
        ]);
	}

	/*
	function _view_vendor_product_upload($e, $tabs) {
		$vendor = $tabs->vendor;
		$form = Input::form();
		if (Input::form('submit')) {
			$upload_result = [];
			$file = Input::file('file');
			if ($file['tmp_name']) {
				$ext = File::extension($file['name']);
				if ($ext == 'xlsx') {
					$vendor_id = $form['vendor_id'];
					$vendor = O('vendor', $vendor_id);
					if (!$vendor->id) {
						Site::message(Site::MESSAGE_ERROR, T('供应商不存在'));
						return FALSE;
					}
					$ctime = Date::time();
					$file_name = $ctime.'.xlsx';
					$full_path = Config::get('product.upload_path').$vendor_id.'/'.$file_name;
					File::check_path($full_path);
					move_uploaded_file($file['tmp_name'], $full_path);
					if (file_exists($full_path)) {
						// 解析xlsx文件 xlsx 转换为 csv, 然后诊断
						$script = ROOT_PATH . 'cli/diagnose_product_upload_file.php';
						if (file_exists($script)) {
							putenv('Q_ROOT_PATH='.ROOT_PATH);
							putenv('SITE_ID='.SITE_ID);
							$cmd = 'php ' . $script . ' -v %vid -p  %path -n %name -r %raw > /dev/null 2>&1 &';
							$cmd = strtr($cmd, array(
											 '%vid' => escapeshellarg($vendor->id),
											 '%path' => $full_path,
											 '%name' => $ctime,
											 '%raw' => Q::quote($file['name']),
											 ));
							exec($cmd, $output, $retval);
							$upload_result['result'] = TRUE;
							$upload_result['summary'] = HT('上传完成, 系统正在分析, 请稍等.');
							// Site::message(Site::MESSAGE_NORMAL, HT('上传完成, 系统正在分析, 请稍等.'));
						}
						else {
							$upload_result['summary'] = HT('缺少审核脚本');
							// Site::message(Site::MESSAGE_ERROR, HT('审核脚本'));
						}
					}
					else {
						$upload_result['summary'] = HT('文件上传失败');
						// Site::message(Site::MESSAGE_ERROR, T('文件上传失败'));
					}
				}
				else {
					$upload_result['summary'] = HT('文件格式不正确, 请选择xlsx格式文件');
					// Site::message(Site::MESSAGE_ERROR, T('文件格式不正确, 请选择xlsx格式文件'));
				}
			}
			else{
				$upload_result['summary'] = HT('请选择您要上传文件');
				// Site::message(Site::MESSAGE_ERROR, T('请选择您要上传文件'));
			}
			$vendor->upload_result = $upload_result;
			$vendor->save();
		}

		$records = Q("product_upload_record[vendor={$vendor}]:sort(ctime D)");
		$pagination = Site::pagination($records, (int)$form['st'], 20);
		$tabs->content = V('admin:vendor/view/product_upload', array(
				'vendor' => $vendor,
				'records' => $records,
				'pagination' => $pagination,
				'form' => $form,
		));
	}
	*/
	function _view_product($e,$tabs) {

		$vendor = $tabs->vendor;
        $stab = $tabs->stab;

        $types = Product_Model::get_types();

        if ($types) {

            $type_keys = array_keys($types);
            if (!$stab) $stab = $type_keys[0];

            $secondary_tabs = Widget::factory('tabs');
            $secondary_tabs
                ->set('class', 'secondary_tabs');
            foreach ($types as $type => $type_name) {
                $secondary_tabs
                    ->add_tab($type, array(
                        'url' => URI::url("!admin/vendor/view.".$vendor->id.".product".".$type"),
                        'title' => T($type_name),
                    ));
            }
            $secondary_tabs
                ->add_tab('unapproved', array(
                    'url'=>URI::url('!admin/vendor/view.'.$vendor->id.'.product.unapproved'),
                    'title'=>T('待审核'),
                ))
                ->add_tab('approved', array(
                    'url'=>URI::url('!admin/vendor/view.'.$vendor->id.'.product.approved'),
                    'title'=>T('已审核'),
                ))
                ->set('class', 'secondary_tabs')
                ->select($stab);
        }

  //       $selector = "product[vendor={$vendor}]";
  //       $array = array('approved', 'unapproved');

		// if (!in_array($stab, $array)){
		// 	$selector .= "[type={$stab}]:sort(approve_date DESC)";
		// }
		// else {
		// 	switch ($stab) {
		// 		case 'approved':
		// 			$selector .= "[approve_date>0]:sort(approve_date DESC)";
		// 			break;
		// 		case 'unapproved':
  //                   $selector .= "[publish_date>0][approve_date=0]:sort(publish_date DESC)";
		// 			break;
		// 	}
		// }

		$form = Site::form();
		if ($form['phrase']) {
			$name = trim($form['phrase']);
		}

		// $products = Q($selector);
		// $pagination = Site::pagination($products, (int)$form['st'], 20);
        $start = (int)$form['st'];
        $perpage = 20;

        $products = Product_Model::getVendorProducts($start, $perpage, $stab, $vendor->gapper_group, $name?:'');
        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start'=> $start,
            'per_page'=> $perpage,
            'total'=> $products['total']?:0,
            'total_found'=> $products['count']?:0,
            'url'=> null
        ]);
		$tabs->content = V('admin:vendor/view/product', array(
						'vendor' => $vendor,
						'secondary_tabs' => $secondary_tabs,
						'products' => $products['data']?:[],
						'pagination' => $pagination,
						'form' => $form,
						));
	}


	// TODO 取消 2 级 tab (xiaopei.li@2012-03-28)
	function _view_vendor_members($e, $tabs) {

		$vendor = $tabs->vendor;
		$stab = $tabs->stab;

        if (!$stab) $stab = 'active';

		$form = Site::form();

		$secondary_tabs = Widget::factory('tabs')
			->add_tab('active', array(
						  'title' => T('已激活'),
						  'url' => $vendor->url('members.active', NULL, NULL, 'admin_view')
						  ))
			->add_tab('unactive', array(
						  'title' => T('未激活'),
						  'url' => $vendor->url('members.unactive', NULL, NULL, 'admin_view')
						  ))
			->content_event('admin.vendor.view.members.content')
			->set('vendor', $vendor)
			->set('class', 'secondary_tabs')
			->select($stab);

		//$selector = "user[vendor={$vendor}]";
		$selector = "$vendor user.member";

		if ($stab == 'active') {
			$selector .= "[atime>0]";
		}
		else {
			$selector .= "[atime=0]";
		}

		if ($form['name']) {
			$name = Q::quote($form['name']);
			$selector .= "[name*={$name} | name_abbr^={$name}]";
		}

		if ($form['contact']) {
			$contact = Q::quote($form['contact']);
			$selector .= "[email*={$contact} | phone*={$contact}]";
		}

		if ($form['address']) {
			$address = Q::quote($form['address']);
			$selector .= "[address*={$address}]";
		}


		$users = Q($selector);

		$pagination = Site::pagination($users, (int)$form['st'], 10);

		$tabs->content = V('admin:vendor/view/index_members', array(
							   'vendor' => $vendor,
							   'secondary_tabs' => $secondary_tabs,
							   'users' => $users,
							   'pagination' => $pagination,
							   'form' => $form
							   ));
	}

	function edit($id=0, $tab='info') {
        // 禁止在mall-old进行供应商的添加操作
        URI::redirect('error/401');
        return;

		$vendor = O('vendor', $id);
		if (!$vendor->id) URI::redirect('error/404');
		if (!$vendor->can_edit()) URI::redirect('error/401');

		Event::bind('admin.vendor.edit.content', array($this, '_edit_info'), 0, 'info');
		Event::bind('admin.vendor.edit.content', array($this, '_edit_icon'), 0, 'icon');
		// Event::bind('admin.vendor.edit.content', array($this, '_edit_scope'), 0, 'scope');
		// Event::bind('admin.vendor.edit.content', array($this, '_edit_attachments'), 0, 'attachments');
		Event::bind('admin.vendor.edit.content', array($this, '_edit_credentials'), 0, 'credentials');

		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
			->add_tab('info', array(
						  'url' => $vendor->url('info', NULL, NULL, 'admin_edit'),
						  'title' => T('基本信息')
						  ))
			->add_tab('icon', array(
						  'url' => $vendor->url('icon', NULL, NULL, 'admin_edit'),
						  'title' => T('商标')
						  ))
			/*
			->add_tab('scope', array(
						  'url' => $vendor->url('scope', NULL, NULL, 'admin_edit'),
						  'title' => T('准营范围')
						  ))
			*/
			/*
			->add_tab('attachments', array(
						  'url' => $vendor->url('attachments', NULL, NULL, 'admin_edit'),
						  'title' => T('附件')
						  ))
			*/
			->add_tab('credentials', array(
						  'url' => $vendor->url('credentials', NULL, NULL, 'admin_edit'),
						  'title' => T('证件信息')
						  ))
			->set('class', 'secondary_tabs')
			->set('vendor', $vendor)
			->content_event('admin.vendor.edit.content')
			->select($tab);

		$content = V('admin:vendor/edit', array('secondary_tabs' => $secondary_tabs));

		$breadcrumb = array(
			array(
				'url' => $vendor->url(NULL, NULL, NULL, 'admin_view'),
				'title' => H($vendor->name)
				),
			array(
				'url' => $vendor->url($tab, NULL, NULL, 'admin_edit'),
				'title' => T('修改')
				)
			);

		$this->layout->body->primary_tabs
			->add_tab('edit', array('*' => $breadcrumb))
			->set('content', $content)
			->select('edit');

	}

	function _edit_icon($e, $tabs) {
		$vendor = $tabs->vendor;

		if (Input::form('submit')) {
			$file = Input::file('file');
			if ($file['tmp_name']) {
				try {
					$ext = File::extension($file['name']);
					$vendor->save_icon(Image::load($file['tmp_name'], $ext));
					Site::message(Site::MESSAGE_NORMAL, T('供应商图标已更新!'));
				}
				catch(Error_Exception $e){
					Site::message(Site::MESSAGE_ERROR, $e->getMessage());

					Site::message(Site::MESSAGE_ERROR, T('供应商图标更新失败!'));
				}
			}
			else{
				Site::message(Site::MESSAGE_ERROR, T('请选择您要上传的供应商图标文件'));
			}
		}

		$tabs->content = V('admin:vendor/edit.icon');
	}

	function _edit_info($e, $tabs) {

		$vendor = $tabs->vendor;

		/*
		echo 'expired scopes';
		echo '<br/>';
		var_dump($vendor->expired_scopes);
		echo '<br/>';
		echo 'extended scopes';
		echo '<br/>';
		var_dump($vendor->extended_scopes);
		*/

		$form = Form::filter(Input::form());

		if ($form['submit']) {

			$form->validate('name', 'not_empty', T('供应商名称不能为空!'))
				->validate('license_no', 'not_empty', T('营业执照注册号不能为空!'))
				->validate('scope', 'not_empty', T('经营范围不能为空!'))
				->validate('short_name', 'not_empty', T('供应商简称不能为空!'))
				->validate('email', 'not_empty', HT('请填写联系邮箱!'))
				->validate('email', 'is_email', HT('联系邮箱格式有误!'))
				->validate('bank_name', 'not_empty', T('请填写开户行!'))
				->validate('bank_account', 'not_empty', T('请填写开户行帐号!'))
				->validate('province', 'not_empty', T('请填写开户行省份!'))
				->validate('city', 'not_empty', T('请填写开户行城市!'))
				;

			$owner = O('user', $form['owner']);
			if (!$owner->id) {
				$form->set_error('owner', HT('请选择供应商负责人'));
			}

            if ($form['activate'] == Vendor_Model::STATUS_REJECTED) {
                $form->validate('reject_reason', 'not_empty', HT('请填写拒绝理由'));
            }

			if ($form->no_error) {

				$vendor->name = $form['name'];
				$vendor->owner = $owner;

				$vendor->short_name = $form['short_name'];
                $vendor->short_abbr = PinYin::code($form['short_name']);
				$vendor->alt_name = $form['alt_name'];
				$vendor->owner_name = $form['owner_name'];
				$vendor->manager_name = $form['manager_name'];
				$vendor->manager_phone = $form['manager_phone'];
				$vendor->contact_name = $form['contact_name'];
				$vendor->contact_phone = $form['contact_phone'];
				$vendor->phone = $form['phone'];
				$vendor->fax = $form['fax'];
				$vendor->address = $form['address'];
				$vendor->postcode = $form['postcode'];
				$vendor->email = $form['email'];
				$vendor->homepage = $form['homepage'];
				$vendor->license_no = $form['license_no'];
				$vendor->license_valid_date = $form['license_valid_date'];
				$vendor->license_last_valid_date = $form['license_last_valid_date'];
				$vendor->establish_date = $form['establish_date'];
				$vendor->operation_due = $form['operation_due'];
				$vendor->capital = $form['capital'];
				$vendor->nemployees = $form['nemployees'];
				$vendor->scope = $form['scope'];
				$vendor->description = $form['description'];
				$vendor->hazardous_article_scope = $form['hazardous_article_scope'];
				$vendor->precursor_scope = $form['precursor_scope'];

				$vendor->bank_account = $form['bank_account'];
				$vendor->bank_name = $form['bank_name'];
				$vendor->province = $form['province'];
				$vendor->city = $form['city'];

				if ($form['has_expire_date'] == 'on') {
					$vendor->expire_date = $form['expire_date'];
				}
				else {
					$vendor->expire_date = 0;
				}

				$vendor->note = $form['note'];

				if ($vendor->save()) {

					if ($vendor->owner->id &&
						!$vendor->has_member($vendor->owner)) {
						$vendor->connect($vendor->owner, 'member');
					}

					$old_scopes = Q("vendor_scope[vendor={$vendor}]");
					// 使用 length 强制让 Q 实例化, 否则若未执行过 unset()
					// 那 Q 一直不实例化, 到删除 $old_scopes 再激发, 就不是
					// old 而是 current scopes 了 (xiaopei.li@2012-04-08)
					$old_scopes_length = $old_scopes->length();

					foreach ((array)$form['scope_allowed'] as $name => $allowance) {
						$scope = O('vendor_scope', array('vendor'=>$vendor, 'name'=>$name));
						if ($allowance == 'on') {
							if (!$scope->id) {
								$scope->vendor = $vendor;
								$scope->name = $name;
							}
							else {
								unset($old_scopes[$scope->id]);
							}

							$expire_date_from = (int)($form['scope_expire_date_from'][$name] ?: Date::time());
							$expire_date = (int)($form['scope_expire_date_to'][$name] ?: Date::time());
							if ($expire_date_from > $expire_date) {
								$tmp = $expire_date_from;
								$expire_date_from = $expire_date;
								$expire_date = $tmp;
							}
							$scope->expire_date_from = $expire_date_from;
							$scope->expire_date = $expire_date;
							$scope->save();
						}
					}


					if ($old_scopes->length() > 0) {
						foreach ($old_scopes as $old_scope) {
							$old_scope->expire_date_from = 0;
							$old_scope->expire_date = 0;
							$old_scope->save();
						}
					}

                    if (isset($form['activate'])) {
                        switch ($form['activate']) {
                            case Vendor_Model::STATUS_PENDING_APPROVAL :
                                $vendor->approve_date = 0;
                                $vendor->publish_date = Date::time();
                                $vendor->save();
                                Admin::cli_unapprove_products($vendor);
                                break;
                            case Vendor_Model::STATUS_APPROVED :
                                if (!$vendor->owner->atime) {
                                    // 激活 $vendor 时, 激活 $vendor->owner
                                    $vendor->owner->atime = Date::time();
                                    $vendor->owner->save();
                                }
                                if (!$vendor->approve_date) {
                                    $vendor->approve();
                                    URI::redirect($vendor->url(NULL, NULL, NULL, 'admin_view'));
                                }
                                break;
                            case Vendor_Model::STATUS_REJECTED :
                                // $vendor->newly_unpublished = TRUE;
                                $vendor->unpublish($form['reject_reason']);

                                Admin::cli_unapprove_products($vendor);
                                URI::redirect($vendor->url(NULL, NULL, NULL, 'admin_view'));
                                break;
                            default:
                        }
                    }

					Site::message(Site::MESSAGE_NORMAL, T('修改供应商成功!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('修改供应商失败!'));
				}

			}

		}

        $this->add_css('admin:vendor');
		$tabs->content = V('admin:vendor/edit.info', array('form'=>$form, 'vendor'=>$vendor));
	}

	/*
	function _edit_scope($e, $tabs) {

		$vendor = $tabs->vendor;
		$form = Input::form();

		if ($form['submit']) {
			$old_scopes = Q("vendor_scope[vendor=$vendor]");
			foreach ((array)$form['scope_allowed'] as $name => $allowance) {
				$scope = O('vendor_scope', array('vendor'=>$vendor, 'name'=>$name));
				if ($allowance == 'on') {
					if (!$scope->id) {
						$scope->vendor = $vendor;
						$scope->name = $name;
					}
					else {
						unset($old_scopes[$scope->id]);
					}
					$scope->expire_date = (int)($form['scope_expire_date'][$name] ?: Date::time());
					$scope->save();
				}
			}

			if ($old_scopes->length() > 0) {
				$old_scopes->delete_all();
			}
		}

		$tabs->content = V('admin:vendor/edit.scope', array('form'=>$form, 'vendor'=>$vendor));
	}
	*/

	function _edit_attachments($e, $tabs) {
		$tabs->content = V('admin:vendor/edit.attachments', array('vendor'=>$vendor));
	}

	function _edit_credentials($e, $tabs) {
		$vendor = $tabs->vendor;

		$tabs->content = V('admin:vendor/edit.credentials', array('vendor' => $vendor));

		$this->add_css('credentials');
	}

	function delete($id=0) {
        // 禁止在mall-old进行供应商的添加操作
        URI::redirect('error/401');
        return;

		$vendor = O('vendor', $id);
		$me = L('ME');

		if (!$vendor->id || !$me->is_allowed_to('删除', $vendor)) {
			URI::redirect('error/401', 401);
		}

		if (Q("$vendor order")->total_count()) {
			Site::message(Site::MESSAGE_ERROR, HT('此供应商有订单关联, 不能删除, 如想禁用, 可取消激活供应商的负责人!'));
			URI::redirect($vendor->url(NULL, NULL, NULL, 'admin_edit'));
		}

		$owner = $vendor->owner;

		if ($vendor->delete()) {
			Site::message(Site::MESSAGE_NORMAL,T('供应商删除成功!'));

			$owner->atime = 0;

			if ($owner->save()) {
				Site::message(Site::MESSAGE_NORMAL,T('供应商负责人已取消激活!'));
			}
			else {
				Site::message(Site::MESSAGE_NORMAL,T('供应商负责人已取消激活!'));
			}

			URI::redirect('!admin/vendor/');
		}
		else {
			Site::message(Site::MESSAGE_ERROR, T('供应商删除失败!'));
			URI::redirect($vendor->url(NULL, NULL, NULL, 'admin_edit'));
		}

	}

	function approve_products($id = 0) {

		$vendor = O('vendor', $id);
		$me = L('ME');

		// $this->_close_connection_and_go_on();

		if (!$me->is_allowed_to('批量审批商家商品', $vendor)) {
			URI::redirect('error/401');
		}

		if (!$vendor->approve_vp_pid) {

			$script = ROOT_PATH . 'cli/approve_products.php';
			if (file_exists($script)) {
				Log::add(strtr('[admin] %user_name[%user_id] 对供应商 %vendor_name[%vendor_id]进行了全部审批商品的操作', array('%user_name'=>$me->name, '%user_id'=>$me->id, '%vendor_name'=>$vendor->name, '%vendor_id'=>$vendor->id)), 'journal');
				putenv('Q_ROOT_PATH='.ROOT_PATH);
				putenv('SITE_ID='.SITE_ID);
				// 只要脚本符合 1. 无输出 2.后台运行, 就能让 controller 继续
				$cmd = 'php ' . $script . ' -v %vid -m  %extra > /dev/null 2>&1 &';
				$cmd = strtr($cmd, array(
								 '%vid' => escapeshellarg($vendor->id),
								 '%extra' => '-b',
								 // '%extra' => '-bmd', // -b, 单色输出; -d is 'dry run'; -m is 'merge'
								 ));
				exec($cmd, $output, $retval);
				// TODO 处理 $output 和 $retval (xiaopei.li@2012-09-20)

				sleep(1); // sleep 1 秒确保调用的 cli 脚本已修改 vendor 的 is_processing_approve_vendor_products 状态
			}
			else {
				Site::message(Site::MESSAGE_ERROR, HT('缺少商品批量审批脚本'));
			}

		}

		URI::redirect($vendor->url(NULL, NULL, NULL, 'admin_view'));

	}

}


class Vendor_AJAX_Controller extends AJAX_Controller {

	// vendor 现为单用户机制, 暂时隐藏用户相关的方法 (xiaopei.li@2012-08-06)

	function index_add_vendor_member_click() {

		$form = Input::form();

		$vendor = O('vendor', $form['vid']);

		if (!$vendor->id) return;

		JS::dialog(V('admin:vendor/view/index_add_member', array(
						 'vendor' => $vendor
						 )), array(
							 'title' => T('添加成员')
							 ));

	}

	function index_add_vendor_member_submit() {

		$form = Form::filter(Input::form());

		$vendor = O('vendor', $form['vid']);

		if (!$vendor->id) return;

		if ($form['submit']) {
			$user = O('user', $form['user']);

			if (!$user->id) {
				$form->set_error('user', T('请选择用户!'));
				JS::dialog(V('admin:vendor/view/index_add_member', array(
								 'vendor' => $vendor,
								 'form' => $form
								 )), array(
									 'title' => T('添加成员')
									 ));
			}

			if ($form->no_error) {
				//$user->vendor = $vendor;
				$vendor->connect($user, 'member');
				if ($user->save()) {
					Site::message(Site::MESSAGE_NORMAL, T('添加成员成功!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('添加成员失败!'));
				}
				JS::refresh();
			}
		}
	}

	function view_delete_vendor_user_click($vid=0) {


		$ret = JS::confirm(T('您是否确认从该供应商中移除此成员? 移除之后该成员不会被删除, 依旧存在于系统中!'));

		if ($ret) {
			$form = Input::form();
			$user = O('user', $form['uid']);
			$vendor = O('vendor', $vid);

			if ($user->id == $vendor->owner->id) {
				JS::alert('不能移出供应商负责人!');
				return;
			}

			$vendor->disconnect($user, 'member');
			Site::message(Site::MESSAGE_NORMAL, T('移除成员成功!'));

			JS::refresh();
		}
	}

	function index_delete_vendor_icon_click() {
        // 禁止在mall-old进行供应商的添加操作
        URI::redirect('error/401');
        return;

		if (JS::confirm(T('您确定要删除供应商商标么?'))) {
			$vendor = O('vendor', Input::form('id'));

			if ($vendor->id) {
				$vendor->delete_icon();

				Site::message(Site::MESSAGE_NORMAL, T('供应商商标删除成功!'));
				JS::refresh();
			}
		}
	}

	function index_unpublish_click() {
		$form = Input::form();
		$vendor = O('vendor', $form['id']);

		if ($vendor->id) {
			JS::dialog(V('admin:vendor/unpublish',
						 array('vendor' => $vendor)));
		}
	}

	function index_unpublish_submit() {
		$form = Form::filter(Input::form());
		$vendor = O('vendor', $form['id']);
        if (!$vendor->id) return FALSE;

        if (!trim($form['reject_reason'])) {
            $form->set_error('reject_reason', HT('暂停理由不能为空!'));
            JS::dialog(V('admin:vendor/unpublish', array(
                'vendor' => $vendor,
                'form'=>$form
                )));
        }
        else {
        	$vendor->unpublish($form['reject_reason']);
			Admin::cli_unapprove_products($vendor);
			JS::refresh();
		}
	}

	function index_reapprove_click() {
		if (JS::confirm(T('您确定要恢复供应商么?'))) {
			$vendor = O('vendor', Input::form('id'));

			if ($vendor->id) {
				$vendor->approve();
				Site::message(Site::MESSAGE_NORMAL, T('供应商已恢复!'));
				JS::refresh();
			}
		}
    }

    function index_approve_reject_submit()
    {
        $form = Form::filter(Input::form());
        $message = $form['reason'];
        $id = $form['id'];
        $rvendor = O('rvendor', $id);
        if (!$rvendor->id) return;
        if (!$message) {
            JS::alert('请您填写拒绝理由！');
            return;
        }
        $rvendor->ignore($message);
        JS::refresh();
    }

    function index_approve_reject_click()
    {
        $rvendor = O('rvendor', Input::form('id'));
        if ($rvendor->id) {
            JS::dialog(V('admin:vendor/ignore', [
                    'id'=> $rvendor->id
                ]));
        }
    }

    function index_approve_submit()
    {
        $form = Form::filter(Input::form());
        $id = $form['id'];
        $rvendor = O('rvendor', $id);
        if (!$rvendor->id) return;

        $ts = $form['type'];
        $rs = $form['reason'];
        $fk = $form['from'];
        $tk = $form['to'];

        $types = $rvendor->getRequestingScopes();
        if (empty($types)) {
            JS::alert('网络故障，请稍候重试！');
            return;
        }

        $diff = array_diff(array_keys($types), array_keys($ts));
        if (!empty($diff)) {
            return;
        }

        $needApprove = [];
        $needReject = [];
        foreach ($ts as $t=>$v) {
            switch ($v) {
            case 1:
                $needApprove[$t] = [
                    'from'=> date('Y-m-d H:i:s', $fk[$t]),
                    'to'=> date('Y-m-d H:i:s', $tk[$t])
                ];
                if ($fk[$t] >= $tk[$t]) {
                    $form->set_error("fromto[$t]", HT('指定的起止时间不合法'));
                }
                break;
            case 2:
                $needReject[$t] = $rs[$t];
                if (empty($rs[$t])) {
                    $form->set_error("reason[$t]", HT('请输入拒绝理由'));
                }
                break;
            default:
            }
        }

        if (!$form->no_error) {
            JS::dialog(V('admin:vendor/approve', [
                'id'=> $id,
                'types'=> $types,
                'form'=> $form
            ]));
            return;
        }

        $result = $rvendor->approveSets($needApprove, $needReject);
        if ($result===false) {
            JS::alert('操作失败，请重试！');
            return;
        }

        JS::alert('操作成功');
        JS::refresh();
    }

    function index_approve_confirm_click()
    {
        $form = Input::form();
        $id = $form['id'];
        if (!$id) return;
        $step = $form['step'];
        // 0: base
        // 1: business
        // 2: cert
        $steps = [
            'admin:vendor/info/base',
            'admin:vendor/info/business',
            'admin:vendor/info/cert'
        ];

        if (!$step) {
            $step = $steps[0];
        }
        else {
            $pos = array_search($step, $steps);
            if ($pos == count($steps)-1) {
                return $this->index_approve_click();
            }
            $step = $steps[$pos+1];
        }

        $vendor = O('vendor', ['gapper_group'=>$id]);
        if (!$vendor->id) {
            $rvendor = O('rvendor');
            $rvendor->create($id);
        }
        $vendor = O('rvendor', $id);

        if (!$vendor->id) {
            JS::alert('网络故障，请稍候重试！');
            return;
        }

        JS::dialog(V('admin:vendor/before-approve', [
            'vendor'=> $vendor,
            'id'=> $id,
            'step'=> $step
        ]));
    }

    function index_approve_click()
    {
        $form = Input::form();
        $id = $form['id'];
        $rvendor = O('rvendor', $id);
        if (!$rvendor->id) return;

        $types = $rvendor->getRequestingScopes();
        if (empty($types)) {
            JS::alert('网络故障，请稍候重试！');
            return;
        }

        JS::dialog(V('admin:vendor/approve', [
            'id'=> $id,
            'types'=> $types
        ]));
	}

	function index_preview_image_click() {

		$form = Input::form();
		$vendor = O('vendor', $form['vid']);

		$file = rawurldecode($form['file']);
		$path = $vendor->fix_path($form['path']);

		$full_path = $vendor->get_path($path) . $vendor->fix_path($file);

		if (!File::exists($full_path)) return;


		JS::dialog(V('admin:vendor/preview_credential_image', array('full_path'=>$full_path)), array('title'=>H(Input::form('title')), 'width'=> 400));
	}

	function index_upload_confirm_click() {
		if (JS::confirm(T('您确定上传该数据?'))) {
			$record = O('product_upload_record', Input::form('id'));
			if ($record->id) {
				$record->status = Product_Upload_Record_Model::RECORD_STATUS_READY;
				if ($record->save()) {
					Site::message(Site::MESSAGE_NORMAL, T('该数据在排队等候导入!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('确认失败!'));
				}
				JS::refresh();
			}
		}
	}

	function index_upload_cancel_click() {
		if (JS::confirm(T('您确定取消导入改数据?'))) {
			$record = O('product_upload_record', Input::form('id'));
			if ($record->id) {
				$record->status = Product_Upload_Record_Model::RECORD_STATUS_CANCELED;
				if ($record->save()) {
					Site::message(Site::MESSAGE_NORMAL, T('操作成功!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('操作失败!'));
				}
				JS::refresh();
			}
		}
	}

	function index_pause_approving_click() {
		$form = Input::form();
		if( !isset($form['id']) ) URI::redirect('error/404');

		$vendor = O('vendor', $form['id']);
		if ( !L('ME')->is_allowed_to('批量审批商家商品', $vendor) || !$vendor->approve_vp_pid )
			URI::redirect('error/401');
		$pid = $vendor->approve_vp_pid;
		$ret = FALSE;
		$catch_approve_product_pid = FALSE;

		exec('pgrep -l -f approve_products', $output, $retval);
		foreach ($output as $key => $value) {
			$arr = explode(' ', $value);
			if ($arr[0] == $pid) {
				$catch_approve_product_pid = TRUE;
				exec(strtr('kill %pid', array('%pid'=>$pid)), $output, $retval);
				if ($retval === 0) {
					$vendor->approve_vp_pid = NULL;
					$ret = TRUE;
				}
			}
		}

		//数据库中有该数据但是系统中该进程已经被外部KILL掉了
		if (!$catch_approve_product_pid) {
			$vendor->approve_vp_pid = NULL;
		}

		if ($vendor->save() && $ret) {
			Site::message(Site::MESSAGE_NORMAL, T('中止审批成功!'));
		}
		else {
			Site::message(Site::MESSAGE_ERROR, T('中止审批失败, 请重试!'));
		}
		JS::refresh();

	}

}
