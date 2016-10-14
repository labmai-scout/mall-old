<?php
// TODO license_no 是否不应与已激活的 vendor 相同(xiaopei.li@2012-03-20)
class Profile_Controller extends Base_Controller {
	// me 和 vendor 已在 is_accessible 中判断, 此 controller 中可不必判断(xiaopei.li@2012-03-19)

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		$this->layout->title = HT('企业信息');
	}

	function index() {
		return $this->view();
	}

	function view($vid=0, $tab = 'info') {
		$me = L('ME');
		$vendor = O('vendor', $vid);

		$apps = Config::get('gapper.apps', []);
		$app = $apps['mall-vendor'];
		$url = $app['url'];
		if ($tab == 'product') {
			$url .='/product/search';
		}
		if ($me->gapper_user && $vendor->gapper_group) {
		    $rpc = Gapper::get_RPC();
		    $login_token = $rpc->gapper->user->getLoginToken((int)$me->gapper_user, $app['client_id']);
		    $url = URI::url($url, ['gapper-token'=>$login_token, 'gapper-group'=>$vendor->gapper_group]);
		}
		URI::redirect($url);

		if (!$vendor->id ||
			!$me->is_allowed_to('查看供应商', $vendor)) {
			URI::redirect('error/401');
		}
		// 页首审核状态提示
		if (!$vendor->approve_date) {	   // vendor 尚未通过审核
			if ( $vendor->publish_date ) { // vendor 已发布
				Site::message(Site::MESSAGE_NORMAL, T('请等待管理员审核!'));
			}
			else {
				if ($vendor->reject_reason) {
					Site::message(Site::MESSAGE_NORMAL, T('您的账号已暂停, 原因是: %reason',
														  array(
															  '%reason' => H($vendor->reject_reason)
															  )));
				}
                /*
                // 供应商的基本信息应该有mall-vendor处理
				Site::message(Site::MESSAGE_NORMAL, T('请 %click_here 进一步完善信息, 并上传各类资质证明后, 提交管理员审核!',
													  array(
														  '%click_here' => URI::anchor(
															  $vendor->url(NULL, NULL, NULL, 'vendor_edit'  ),
															  HT('点击这里'),
															  'class="blue"')
                                                          )));
                 */
			}

		}

		$secondary_tabs = Widget::factory('tabs');

        Event::bind('vendor.view.content', array($this, '_view_info'), 0, 'info');
        // 供应商的成员信息也应该有mall-vendor处理
		// Event::bind('vendor.view.content', array($this, '_view_members'), 0, 'members');

        if (in_array($tab, ['service', 'confidentiality'])) {
            $file = SITE_PATH . PRIVATE_BASE . "agreement/{$tab}.pdf";
            if(file_exists($file)) Downloader::download($file, TRUE);
            $tab = 'info';
        }

		$secondary_tabs
			->add_tab('info', array(
						  'title' => T('基本信息'),
						  'url' => $vendor->url('info', NULL, NULL, 'vendor_view'),
                      ));
        /*
			->add_tab('members', array(
						  'title' => T('成员信息'),
						  'url' => $vendor->url('members', NULL, NULL, 'vendor_view'),
                      ));
         */

        $current_version = Config::get('vendor.current_agreement_version');
        $start = date_create(Config::get('vendor.current_agreement_date_start'));
        $current = date_create()->setTimestamp(time());

        $has_pdf_file = true;
        foreach (['service', 'confidentiality'] as $k) {
            $file = SITE_PATH . PRIVATE_BASE . "agreement/{$k}.pdf";
            if (!file_exists($file)) {
                $has_pdf_file = false;
                break;
            }
        }

		$secondary_tabs
			->tab_event('vendor.view.tab')
			->content_event('vendor.view.content')
			->set('vendor', $vendor)
			->select($tab);

		$this->layout->body = V('vendor:profile/view',
						array(
							'vendor' => $vendor,
							'secondary_tabs' => $secondary_tabs,
                            'has_agreement'=> !!($current_version && $current>=$start && $has_pdf_file)
						)
					);
	}

	function _view_info($e, $tabs) {
		$vendor = $tabs->vendor;
		$tabs->content = V('vendor:profile/view.info', array('vendor' => $vendor));
	}

	// TODO 针对新注册未发布的用户处理还无方案(xiaopei.li@2012-03-20)
	function _view_members($e, $tabs) {

		$vendor = $tabs->vendor;
		$stab = $tabs->stab ? : 'active';

		$form = Site::form();

		/*
		$secondary_tabs = Widget::factory('tabs')
			->add_tab('active', array(
						  'title' => T('已激活'),
						  'url' => $vendor->url('members.active', NULL, NULL, 'vendor_view')
						  ))
			->add_tab('unactive', array(
						  'title' => T('未激活'),
						  'url' => $vendor->url('members.unactive', NULL, NULL, 'vendor_view')
						  ))
			->content_event('vendor.view.members.content')
			->set('vendor', $vendor)
			->set('class', 'secondary_tabs')
			->select($stab);
		*/

		$selector = "$vendor<member user";
		/*
		if ($stab == 'active') {
			$selector .= "[atime>0]";
		}
		else {
			$selector .= "[atime=0]";
		}
		*/
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

		$pagination = Site::pagination($users, (int)$form['st'], 20);

		$tabs->content = V('vendor:profile/view.members', array(
							   'vendor' => $vendor,
							   //'secondary_tabs' => $secondary_tabs,
							   'users' => $users,
							   'pagination' => $pagination,
							   'form' => $form
							   ));

	}

    function edit($vid=0, $tab = 'info' ) {

        // 禁止在mall-old进行供应商的添加操作
        URI::redirect('/error/401');
        return;

		$me = L('ME');

		$vendor = O('vendor', $vid);

		if (!$vendor->can_edit() ||
			!$me->is_allowed_to('以供应商修改', $vendor)) {
			URI::redirect('/error/401');
		}

		if ($vendor->reject_reason) {
			Site::message(Site::MESSAGE_NORMAL, HT('下架原因: %reason', array('%reason' => $vendor->reject_reason)));
		}

		Event::bind('vendor.vendor.edit.content', array($this, '_edit_info'), 0, 'info');
		Event::bind('vendor.vendor.edit.content', array($this, '_edit_icon'), 0, 'icon');
        Event::bind('vendor.vendor.edit.content', array($this, '_edit_api'), 0, 'api');
		// TODO 附件与工商信息限制相同(xiaopei.li@2012-03-22)
		// Event::bind('vendor.vendor.edit.content', array($this, '_edit_attachments'), 0, 'attachments');
		Event::bind('vendor.vendor.edit.content', array($this, '_edit_credentials'), 0, 'credentials');

		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
			->add_tab('info', array(
						  'url' => $vendor->url('info', NULL, NULL, 'vendor_edit'),
						  'title' => T('基本信息')
						  ))
			->add_tab('icon', array(
						  'url' => $vendor->url('icon', NULL, NULL, 'vendor_edit'),
						  'title' => T('商标')
						  ))
            ->add_tab('api', array(
                        'url'=>$vendor->url('api', NULL, NULL, 'vendor_edit'),
                        'title'=>T('接口')
                        ))
            ->add_tab('credentials', array(
                        'url'=>$vendor->url('credentials', NULL, NULL, 'vendor_edit'),
                        'title'=>T('证件信息')
            ));

		$secondary_tabs
			->set('class', 'secondary_tabs')
			->set('vendor', $vendor)
			->content_event('vendor.vendor.edit.content')
			->select($tab); // select 会触发事件

		$content = V('vendor:profile/edit', array('secondary_tabs' => $secondary_tabs));

		$breadcrumb = array(
			array(
				'url' => URI::url('!vendor/profile/view.'.$vendor->id),
				'title' => H($vendor->short_name ?: $vendor->name),
				),
			array(
				'url' => URI::url('!vendor/profile/edit.'.$vendor->id),
				'title' => T('修改')
				)
			);

		$this->layout->body->primary_tabs
			->set_tab('mine', array('*' => $breadcrumb))
			->set('content', $content)
			->select('mine');

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

		$tabs->content = V('vendor:profile/edit.icon');
	}

	function _edit_info($e, $tabs) {

		$vendor = $tabs->vendor;
		$me = L('ME');

		$form = Form::filter(Input::form());

		if ($form['submit'] || $form['publish'] || $form['edit_publish']) {

			if (!$vendor->publish_date || // 未发布
				$form['edit_reg_info'] ) { // 或已发布但指明要修改"工商信息"
				$form->validate('name', 'not_empty', T('供应商名称不能为空!'))
					->validate('license_no', 'not_empty', T('营业执照注册号不能为空!'))
					->validate('scope', 'not_empty', T('经营范围不能为空!'));
					// TODO 还有其他什么属性属于"验证性", 应必填?
			}

			$form->validate('short_name', 'not_empty', T('供应商简称不能为空!'))
				->validate('email', 'not_empty', HT('请填写联系邮箱!'))
				->validate('email', 'is_email', HT('联系邮箱格式有误!'))
				->validate('bank_name', 'not_empty', HT('请填写开户行!'))
				->validate('bank_account', 'not_empty', HT('请填写开户行帐号!'))
				->validate('province', 'not_empty', HT('请填写开户行省份!'))
				->validate('city', 'not_empty', HT('请填写开户行城市!'));

			if ($form->no_error) {

				if (!$vendor->publish_date || // 未发布
					$form['edit_reg_info'] ) { // 或已发布但指明要修改"工商信息"

					$vendor->name = $form['name'];
					$vendor->alt_name = $form['alt_name'];
					$vendor->owner_name = $form['owner_name'];
					$vendor->owner_id_no = $form['owner_id_no'];
					$vendor->manager_name = $form['manager_name'];
					$vendor->manager_phone = $form['manager_phone'];
					$vendor->contact_name = $form['contact_name'];
					$vendor->contact_phone = $form['contact_phone'];
					$vendor->license_no = $form['license_no'];
					$vendor->license_valid_date = $form['license_valid_date'];
					$vendor->license_last_valid_date = $form['license_last_valid_date'];
					$vendor->establish_date = $form['establish_date'];
					$vendor->operation_due = $form['operation_due'];
					$vendor->capital = (int)$form['capital'];
					$vendor->nemployees = $form['nemployees'];
					$vendor->scope = $form['scope'];
					$vendor->hazardous_article_scope = $form['hazardous_article_scope'];
					$vendor->precursor_scope = $form['precursor_scope'];


					$vendor->bank_account = $form['bank_account'];
					$vendor->bank_name = $form['bank_name'];
					$vendor->province = $form['province'];
					$vendor->city = $form['city'];
				}

				$vendor->short_name = $form['short_name'];
                $vendor->short_abbr = PinYin::code($form['short_name']);
				$vendor->phone = $form['phone'];
				$vendor->fax = $form['fax'];
				$vendor->address = $form['address'];
				$vendor->postcode = $form['postcode'];
				$vendor->email = $form['email'];
				$vendor->homepage = $form['homepage'];
				$vendor->description = $form['description'];
				//清空拒绝理由
				$vendor->reject_reason = '';


				if ($vendor->save()) {

					if (!$vendor->publish_date || // 未发布
					$form['edit_reg_info'] ) { // 或已发布但指明要修改"工商信息"
						// 设置 scope_allowed
						$old_scopes = Q("vendor_scope[vendor=$vendor]")->to_assoc('id', 'id');

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

								$scope->expire_date_from = (int)($form['scope_expire_date_from'][$name] ?: Date::time());
								$scope->expire_date = (int)($form['scope_expire_date_to'][$name] ?: Date::time());
                                if ($scope->expire_date_from > $scope->expire_date) {
                                    list($scope->expire_date, $scope->expire_date_from) = array($scope->expire_date_from, $scope->expire_date);
                                }
								$scope->save();

	                            //创建新的scope后，保存上传的图片
	                            $scope_tmp_file = Vendor_Scope::get_tmp_file($scope->name, $scope->vendor->id);
	                            if (is_file($scope_tmp_file)) {
	                                $scope->save_pic($scope_tmp_file);
	                            }
							}
						}

						if (count($old_scopes)) foreach ($old_scopes as $key => $value) {
							O('vendor_scope', $key)->delete();
						}
					}

					//是否对供应商商品进行下架
					$product_unpublish = FALSE;
					if ($form['submit']) {
						if($form['edit_reg_info']){
							if ($vendor->approve_date > 0) {
								$vendor->unpublish(HT('供应商修改工商信息后供应商下架'));
							}
							elseif ($vendor->publish_date > 0) {
								$vendor->unpublish(HT('供应商修改工商信息后供应商审核自动暂停'));
							}
							$product_unpublish = TRUE;
						}
						Site::message(Site::MESSAGE_NORMAL, T('修改供应商成功!'));
						$log = sprintf('[vendor] %s[%d]修改供应商%s[%d]信息成功',
                		$me->name, $me->id,$vendor->name, $vendor->id);
                   		Log::add($log, 'vendor');
					}
					elseif ($form['publish'] && self::check_publish($vendor)) {
						Site::message(Site::MESSAGE_NORMAL, T('提交供应商审核成功!'));
						$vendor->publish();
						$log = sprintf('[vendor] %s[%d]提交供应商%s[%d]进行审核',
	                    $me->name, $me->id,$vendor->name, $vendor->id);
                    	Log::add($log, 'vendor');
					}elseif($form['edit_publish']){//勾选修改工商信息
						//商品下架
						if ($form['edit_reg_info']) {
							if($vendor->approve_date > 0){
								$vendor->unapprove();
								$product_unpublish = TRUE;
							}
						}

						Site::message(Site::MESSAGE_NORMAL, T('提交供应商审核成功!'));
						$log = sprintf('[vendor] %s[%d]修改供应商信息并提交供应商%s[%d]进行审核',
	                    $me->name, $me->id,$vendor->name, $vendor->id);
                    	Log::add($log, 'vendor');
					}

					//对供应商商品进行下架,防止重复进行下架操作
					if($product_unpublish){
						if(!$vendor->is_unpublishing){
							Admin::cli_unapprove_products($vendor);
						}
						URI::redirect($vendor->url(NULL, NULL, NULL, 'vendor_view'));
					}
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('修改供应商失败!'));
				}

			}

		}

		$tabs->content = V('vendor:profile/edit.info', array('form'=>$form, 'vendor'=>$vendor));
	}

    function _edit_api($e, $tabs) {
        $vendor = $tabs->vendor;
        $vendor_api = O('vendor_api', ['vendor'=>$vendor]);

        $form = Form::filter(Input::form());
        if ($form['submit']) {

            $retries = 3;
            $vendor_api->vendor = $vendor;
            while ($retries--) {
                $vendor_api->client_id = sha1(uniqid().mt_rand());
                $vendor_api->client_secret = sha1(uniqid().mt_rand());
                if ($vendor_api->save()) {
                    break;
                }
            }
        }

        $tabs->content = V('vendor:profile/edit.api', [
            'vendor'=>$vendor, 'vendor_api'=>$vendor_api, 'form'=>$form
        ]);
    }

	function _edit_attachments($e, $tabs) {
		$vendor = $tabs->vendor;

		$tabs->content = V('vendor:profile/edit.attachments', array('vendor'=>$vendor));
	}

	function _edit_credentials($e, $tabs) {
		$me = L('ME');
        $vendor = $tabs->vendor;

        $form = Form::filter(Input::form());

        if ($form['license_submit']) {
            $form
                ->validate('license_no', 'not_empty', T('营业执照注册代码不能为空!'))
                ->validate('license_valid_date', 'not_empty', T('营业执照证照年检日期不能为空!'))
                ->validate('license_period_date', 'not_empty', T('营业执照证照有效期不能为空!'));

            $file = Input::file('license_file');

            if (!$file && !$vendor->license_ready) $form->set_error('license_file', T('营业执照图片需要上传!'));

            if ($form->no_error) {
                $full_path = $vendor->get_path('license');
                if (!File::exists($full_path)) {
                    File::check_path($full_path.'foo');
                }

                $file_name = NFS::fix_name($file['name'], TRUE);

                $full_path .= $file_name;

                $vendor->license_no = $form['license_no'];
                $vendor->license_valid_date = $form['license_valid_date'];
                $vendor->license_period_date = $form['license_period_date'];
                $vendor->license_img = $file_name ? : $vendor->license_img;
                //清空拒绝理由
                $vendor->reject_reason = '';

                if ($vendor->save()) {
                    $vendor->license_ready = TRUE;
                    $vendor->save();
                    move_uploaded_file($file['tmp_name'], $full_path);
                    Site::message(Site::MESSAGE_NORMAL, T('供应商营业执照注册信息录入成功!'));
                    $log = sprintf('[vendor] %s[%d]上传了供应商营业执照注册信息,注册号%s',
                    $me->name, $me->id,$vendor->license_no);
                    Log::add($log, 'vendor');
                }
                else {
                    Site::message(Site::MESSAGE_ERROR, T('供应商营业执照注册信息录入失败!'));
                }
            }

        }
        elseif ($form['group_submit']) {
            $form
                ->validate('group_no', 'not_empty', T('供应商组织机构代码不能为空!'))
                ->validate('group_valid_date', 'not_empty', T('组织机构证照年检日期不能为空!'))
                ->validate('group_dto', 'not_empty', T('组织机构证照有效期不能为空!'));

            $file = Input::file('group_file');

            if (!$file && !$vendor->group_ready) $form->set_error('group_file', T('营业执照图片需要上传!'));

            if ($form->no_error) {
                $full_path = $vendor->get_path('group');
                if (!File::exists($full_path)) {
                    File::check_path($full_path.'foo');
                }

                $file_name = NFS::fix_name($file['name'], TRUE);

                $full_path .= $file_name;

                $vendor->group_no = $form['group_no'];
                $vendor->group_valid_date = $form['group_valid_date'];
                $vendor->group_dto = $form['group_dto'];
                $vendor->group_img = $file_name ? : $vendor->group_img;
                if ($vendor->save()) {
                    $vendor->group_ready = TRUE;
                    $vendor->save();
                    move_uploaded_file($file['tmp_name'], $full_path);
                    Site::message(Site::MESSAGE_NORMAL, T('供应商组织机构代码证信息录入成功!'));
                    $log = sprintf('[vendor] %s[%d]上传了供应商组织机构代码信息,代码%s',
                    $me->name, $me->id,$vendor->group_no);
                    Log::add($log, 'vendor');
                }
                else {
                    Site::message(Site::MESSAGE_ERROR, T('供应商组织机构代码证信息录入失败!'));
                }
            }
        }
        elseif ($form['tax_on_land_submit']) {
            $form->validate('tax_on_land_no', 'not_empty', HT('地税登记证代码不能为空!'));
            $file = Input::file('tax_on_land_file');

            if (!$file && !$vendor->tax_on_land_ready) $form->set_error('tax_on_land_file', HT('地税执照图片需要上传!'));

            if ($form->no_error) {
                $full_path = $vendor->get_path('tax_on_land');
                if (!File::exists($full_path)) {
                    File::check_path($full_path.'foo');
                }

                $file_name = NFS::fix_name($file['name'], TRUE);

                $full_path .= $file_name;

                $vendor->tax_on_land_no = $form['tax_on_land_no'];
                $vendor->tax_on_land_img = $file_name ? : $vendor->tax_on_land_img;

                if ($vendor->save()) {
                    $vendor->tax_on_land_ready = TRUE;
                    $vendor->save();
                    move_uploaded_file($file['tmp_name'], $full_path);
                    Site::message(Site::MESSAGE_NORMAL, T('地税登记证代码信息录入成功!'));
                    $log = sprintf('[vendor] %s[%d]上传了地税登记证代码信息,税字%s',
                    $me->name, $me->id,$vendor->tax_on_land_no);
                    Log::add($log, 'vendor');
                }
                else {
                    Site::message(Site::MESSAGE_ERROR, T('地税登记证代码信息录入失败!'));
                }
            }
        }
        elseif ($form['state_tax_submit']) {
            $form->validate('state_tax_no', 'not_empty', HT('国税登记证代码不能为空!'));

            $file = Input::file('state_tax_file');
            if (!$file && !$vendor->state_tax_ready) $form->set_error('state_tax_file', HT('国税执照图片需要上传!'));

            if ($form->no_error) {
                $full_path = $vendor->get_path('state_tax');
                if (!File::exists($full_path)) {
                    File::check_path($full_path.'foo');
                }

                $file_name = NFS::fix_name($file['name'], TRUE);

                $full_path .= $file_name;

                $vendor->state_tax_no = $form['state_tax_no'];
                $vendor->state_tax_img = $file_name ? : $vendor->state_tax_img;

                if ($vendor->save()) {
                    $vendor->state_tax_ready = TRUE;
                    $vendor->save();
                    move_uploaded_file($file['tmp_name'], $full_path);
                    Site::message(Site::MESSAGE_NORMAL, T('国税登记证代码信息录入成功!'));
                    $log = sprintf('[vendor] %s[%d]上传了国税登记证代码信息,税字%s',
                    $me->name, $me->id,$vendor->state_tax_no);
                    Log::add($log, 'vendor');
                }
                else {
                    Site::message(Site::MESSAGE_ERROR, T('国税登记证代码信息录入失败!'));
                }
            }
        }
        /*
        elseif ($form['owner_id_submit']) {
            // $form->validate('owner_id_no', 'not_empty', HT('法人身份证件号码不能为空!'));

            $file = Input::file('owner_id_file');
            if (!$file && !$vendor->owner_id_ready) $form->set_error('owner_id_file', HT('法人身份证件图片需要上传!'));

            if ($form->no_error) {
                $full_path = $vendor->get_path('owner_id');
                if (!File::exists($full_path)) {
                    File::check_path($full_path.'foo');
                }

                $file_name = NFS::fix_name($file['name'], TRUE);

                $full_path .= $file_name;

                $vendor->owner_id_no = $form['owner_id_no'];
                $vendor->owner_id_img = $file_name ? : $vendor->owner_id_img;

                if ($vendor->save()) {
                    $vendor->owner_id_ready = TRUE;
                    $vendor->save();
                    move_uploaded_file($file['tmp_name'], $full_path);
                    Site::message(Site::MESSAGE_NORMAL, T('法人身份证件信息录入成功!'));
                }
                else {
                    Site::message(Site::MESSAGE_ERROR, T('法人身份证件信息录入失败!'));
                }
            }
        }
        */
        elseif ($form['special_operate_submit']) {
			/*
			KNOWN BUGS: (xiaopei.li@2012-08-17)
			1. 删除某项其他资质时删除的 index 有问题;
			2. 查看/删除图片时 index 有问题;
			3. 名字需要改(special/other)
			*/

			// var_dump($form);

            // $form->validate('special_operate_no', 'not_empty', HT('其他特种经营许可代码不能为空!'));

            $file_properties = Input::file('special_operate_file');
			/*
			形如:
			array(5) {
			  ["name"]=>
			  array(1) {
			    [0]=>
			    string(12) "s3356209.jpg"
			  }
			  ...
			}
			*/
			// var_dump($file_properties);

			// die;

            // if (!$file && !$vendor->special_operate_ready) $form->set_error('special_operate_file', HT('其他特种经营许可图片需要上传!'));

            if ($form->no_error) {
                $base_path = $vendor->get_path('special_operate');
                if (!File::exists($base_path)) {
                    File::check_path($base_path.'foo');
                }

				$other_certs = array();

				$special_operate_keys = array_keys((array)$form['special_id']);


				//删除图片
				foreach ((array)$vendor->other_certs as $key => $value) {
					if(!in_array($key, (array)$special_operate_keys)){

						$full_path = $vendor->get_path('special_operate') . $vendor->other_certs[$key]['special_operate_img'];
						Cache::remove_cache_file($full_path);

						File::delete($full_path);
					}
				}


				//先处理已存在的。为上传的图片增加special_id，这样在确定删除哪张图片
				foreach ((array)$special_operate_keys  as $i) {
					$file_name = NFS::fix_name($file_properties['name'][$i], TRUE);
					if ($file_name) {
						$full_path = $base_path . $file_name ;
						$uploads[$i] = array(
							'from' => $file_properties['tmp_name'][$i],
							'to' => $full_path,
							);
					}

					$cert = array(
						'special_operate_type' => $form['special_operate_type'][$i],
						'special_operate_no' => $form['special_operate_no'][$i],
						'special_operate_img' => $file_name ? : $vendor->other_certs[$i]['special_operate_img'],
						'special_id' => $i,
						);


					$other_certs[$i] = $cert;
					unset($file_properties['name'][$i]);
				}

				$max_special_key = count($special_operate_keys) ? max($special_operate_keys) : 0;
				//处理新添加的文件
				foreach ((array)$file_properties['name'] as $key => $file) {
					$file_name = NFS::fix_name($file, TRUE);
					if ($file_name) {
						$full_path = $base_path . $file_name;
						$uploads[$key] = array(
							'from' => $file_properties['tmp_name'][$key],
							'to' => $full_path,
							);
					}

					$i = $max_special_key + 1;
					$t = count($special_operate_keys) ? $i : $key;

					$cert = array(
						'special_operate_type' => $form['special_operate_type'][$t],
						'special_operate_no' => $form['special_operate_no'][$t],
						'special_operate_img' => $file_name,
						'special_id' => $i,
						);


					$other_certs[$i] = $cert;

					$max_special_key++;

				}


				$vendor->other_certs = $other_certs;

				if ($vendor->save()) {
					foreach ((array)$uploads as $upload) {
						move_uploaded_file($upload['from'], $upload['to']);
					}

					Site::message(Site::MESSAGE_NORMAL, T('其他特种经营许可信息录入成功!'));
                    $log = sprintf('[vendor] %s[%d]上传了其他特种经营许可信息',
                    $me->name, $me->id);
                    Log::add($log, 'vendor');
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('其他特种经营许可信息录入失败!'));
				}
            }
        }
        elseif ($form['submit']) {
        	//不仅需要判断XXX_ready存在，还需要判断真实的文件是否存在
        	$license_img = $vendor->get_path('license') . $vendor->license_img;
            if(!($vendor->license_ready && file_exists($license_img))) {
                $form->set_error('license_file', HT('供应商营业执照图片未上传!'));
            }

            $group_img = $vendor->get_path('group') . $vendor->group_img;
            if (!($vendor->group_ready && file_exists($group_img))) {
                $form->set_error('group_file', HT('供应商组织机构代码证图片未上传!'));
            }

            $tax_on_land_img = $vendor->get_path('tax_on_land') . $vendor->tax_on_land_img;
            if (!($vendor->tax_on_land_ready && file_exists($tax_on_land_img))) {
                $form->set_error('tax_on_land_file', HT('地税登记证图片未上传!'));
            }

            $state_tax_img = $vendor->get_path('state_tax') . $vendor->state_tax_img;
            if (!($vendor->state_tax_ready && file_exists($state_tax_img))) {
                $form->set_error('state_tax_file', HT('国税登记证图片未上传!'));
            }
			/*
            if (!$vendor->owner_id_ready) {
                $form->set_error('owner_id_file', HT('法人身份证件图片未上传!'));
            }
            */
            if ($form->no_error && self::check_publish($vendor)) {
				if ($vendor->publish()) {
                    Site::message(Site::MESSAGE_NORMAL, HT('提交管理员审核成功!'));
                }
                else {
                    Site::message(Site::MESSAGE_ERROR, HT('提交审核失败! 请联系管理员!'));
                }
            }
        }

        $tabs->content = V('vendor:profile/edit.credentials', array('form'=>$form, 'vendor' => $vendor));

		$this->add_css('credentials');

    }

    function check_publish($vendor) {
    	if(!Vendor::check_credentials($vendor)){
    		Site::message(Site::MESSAGE_ERROR, HT('请完整填写相关证件信息!'));
		}
		elseif (!$vendor->name || !$vendor->license_no || !$vendor->scope || !$vendor->short_name || !$vendor->email) {
			Site::message(Site::MESSAGE_ERROR, HT('请完整填写基本信息!'));
		}
		else {
			return TRUE;
		}
    }
}

class Profile_AJAX_Controller extends AJAX_Controller {

	function index_delete_vendor_icon_click() {
        // 禁止在mall-old进行供应商的添加操作
        return;

		$vendor = O('vendor', Input::form('id'));

		if ($vendor->id) {
			if (JS::confirm(T('您确定要删除供应商商标么?'))) {
				$vendor->delete_icon();
				Site::message(Site::MESSAGE_NORMAL, T('供应商商标删除成功!'));
				JS::refresh();
			}
		}
	}

	function index_preview_image_click() {
		$form = Input::form();
		$vendor = O('vendor', $form['vid']);
		//vendor的owner和供应商中的成员可以查看
		if(!L('ME')->is_allowed_to('查看证书', $vendor)) return;

		$file = rawurldecode($form['file']);
		$path = $vendor->fix_path($form['path']);
		$full_path = $vendor->get_path($path) . $vendor->fix_path($file);

		if (!File::exists($full_path)) return;

		JS::dialog(V('vendor:profile/preview_credential_image', array('full_path'=>$full_path)), array('title'=>H(Input::form('title')), 'width'=> 400));
	}

	function index_delete_vendor_user_click() {
		$ret = JS::confirm(T('您是否确认移除此成员? 移除之后该成员不会被删除, 依旧存在于系统中!'));
		if ($ret) {
			$me = L('ME');
			$form = Input::form();
			if ($me->id == $form['uid']) {
				JS::alert('不允许删除自己');
				return FALSE;
			}
			$user = O('user', $form['uid']);
			$vendor = O('vendor', $form['vendor_id']);
			if ($vendor->owner_id != $me->id && !$me->access('管理所有内容')) {
				return;
			}
			if ($user->id && $vendor->id) {
				$ret = TRUE;
				if ($vendor->owner_id == $user->id) {
					$vendor->owner = NULL;
					$ret = $vendor->save();
				}

				if ($ret) {
					$vendor->disconnect($user, 'member');
					Site::message(Site::MESSAGE_NORMAL, T('移除成员成功!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('移除成员失败!'));
				}
			}
			else {
				Site::message(Site::MESSAGE_ERROR, T('移除成员失败!'));
			}

			JS::refresh();
		}
	}

	function index_delete_image_click() {

        // 禁止在mall-old进行供应商的添加操作
        return;

		$form = Input::form();
		$vendor = O('vendor', $form['vid']);

		//vendor的owner和供应商中的成员可以删除
		if(!L('ME')->is_allowed_to('删除证书', $vendor)) return;

		$file = rawurldecode($form['file']);
		$ready_attr = $form['ready_attr'];

		//处理form中得到的path
		$path = $vendor->fix_path($form['path']);
		$attribute = $path . '_img';

		$full_path = $vendor->get_path($path) . $vendor->fix_path($file);

		if (!File::exists($full_path)) return;


		if (JS::confirm(HT('您确定要删除证书图片么?'))) {

			Cache::remove_cache_file($full_path);

			File::delete($full_path);

            if($ready_attr){
	            $vendor->$attribute = '';
	            $vendor->$ready_attr = FALSE;

				$vendor->save();
			}

            Site::message(Site::MESSAGE_NORMAL, T('证书图片删除成功!'));
			JS::refresh();
		}
	}
}
