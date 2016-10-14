<?php

class Signup_Controller extends Layout_Vendor_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		if (L('ME')->id) {
			URI::redirect('!vendor/profile');
		}
	}

    function index() {
        URI::redirect('http://gapper.in/signup');
        return;
		$form = Form::filter(Input::form());

        $me = L('ME');
		if ($form['submit']) {
			try {

				// validation
				$form
					->validate('name', 'not_empty', T('供应商名称不能为空!'))
					->validate('license_no', 'not_empty', T('营业执照注册号不能为空!'))
					->validate('scope', 'not_empty', T('经营范围不能为空!'));

                if (!$me->id) {
                    $form
	                    ->validate('vendor_admin_name', 'not_empty', T('管理员姓名不能为空!'))
	                    ->validate('vendor_admin_token', 'is_token', T('管理员帐号格式有误!'))
	                    ->validate('vendor_admin_email', 'is_email', T('管理员邮箱格式有误!'))
	                    ->validate('vendor_admin_phone', 'not_empty', T('管理员电话不能为空!'))
	                    ->validate('password', 'length(6,24)', T('输入的密码不能小于6位，最长不能大于24位！'))
	                    ->validate('confirm_password', 'compare(==password)', T('请输入有效密码并确保两次输入的密码一致！'));
                    
                    if (O('user', array('email' => $form['vendor_admin_email']))->id) {
                        $form->set_error('vendor_admin_email', T('您输入的管理员邮箱已被使用!'));
                    }

                    $token = Auth::normalize($form['vendor_admin_token'], Config::get('auth.vendor_backend'));

                    if (User_Model::is_reserved_token($token)) {
                        $form->set_error('vendor_admin_token', T('您输入的管理员账号已被保留!'));
                    }

                    if (O('user', array('token' => $token))->id) {
                        $form->set_error('vendor_admin_token', T('您输入的管理员账号已被使用!'));
                    }
                }

				if ($form->no_error) {
                    if (!$me->id) {

                        $auth = new Auth($token);
                        if ( !$auth->create($form['password']) ) {
                            throw new Error_Exception( T('添加供应商管理员失败! 请与系统管理员联系.'));
                        }

                        $vendor_admin = O('user');
                        $vendor_admin->token = $token;
                        $vendor_admin->name = $form['vendor_admin_name'];
                        $vendor_admin->email = $form['vendor_admin_email'];
                        $vendor_admin->phone = $form['vendor_admin_phone'];

						// 由于现在只有激活用户才能登录, 所以为了新注册 vendor 的用户
						// 能修改 vendor 信息, 便在注册时就激活用户 (xiaopei.li@2012-08-06)
						$vendor_admin->atime = Date::time();

                        if (!$vendor_admin->save()) {
                            $auth->remove();
                            throw new Error_Exception( T('供应商添加管理员失败! 请与系统管理员联系'));
                        }

                    }

					// 先尝试保存 $vendor_admin
                    $vendor = O('vendor');
                    $vendor->name = $form['name'];
                    $vendor->license_no = $form['license_no'];
                    $vendor->scope = $form['scope'];
                    $vendor->create_date = Date::time();

                    // 再尝试保存 $vendor
                    if ($vendor->save()) {

                        $log = sprintf('[vendor] %s[%d]注册了供应商%s[%d]',
                        $vendor_admin->name?:$me->name, $vendor_admin->id?:$me->id,
                        $vendor->name, $vendor->id);
                        Log::add($log, 'vendor');

                        // 都保存成功后建立关联
                        if ($me->id) {
                            $vendor->connect($me, 'member');
                            $vendor->owner = $me;
                            $vendor->save();
                        }
                        else {
                            $vendor->connect($vendor_admin, 'member');
                            $vendor->owner = $vendor_admin;
                            $vendor->save();
                            Auth::login($vendor_admin->token);
                        }

                        URI::redirect('!vendor/profile/view.'.$vendor->id);
                    }
				}
			}
			catch (Error_Exception $e) {
				if ($e->getMessage()) {
					Site::message(Site::MESSAGE_ERROR, $e->getMessage());
				}
			}
		}

		$this->layout->body = V('vendor:signup/form', array('form' => $form));
	}

}
