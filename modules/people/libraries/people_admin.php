<?php
class People_Admin {

	static function setup(){
		if(L('ME')->access('管理成员')){
			Event::bind('admin.index.tab', 'People_Admin::_primary_tab');
		}
	}

	static function _primary_tab($e, $tabs){
		Event::bind('admin.index.content', 'People_Admin::_primary_content', 0, 'people');

		$tabs->add_tab('people', array(
			'url'=>URI::url('!admin/admin/people'),
			'title'=> T('成员管理'),
		));
	}

	static function _primary_content($e, $tabs){
		$tabs->content = V('people:admin/view');

		Event::bind('admin.people.content', 'People_Admin::_secondary_signup_content', 0, 'signup');
		/*
		Event::bind('admin.people.content', 'People_Admin::_secondary_limit_content', 2, 'limit');
		Event::bind('admin.people.content', 'People_Admin::_secondary_role_content', 3, 'role');
		*/

		$tabs->content->secondary_tabs = Widget::factory('tabs')
				->add_tab('signup', array(
					'url'=>URI::url('!admin/admin/people.signup'),
					'title'=> T('注册须知'),
				))
			/*
				->add_tab('limit', array(
					'url'=>URI::url('!admin/admin/people.limit'),
					'title'=> T('帐号限制'),
                ))
                ->add_tab('role', array(
                    'url'=>URI::url('!admin/admin/people.role'),
                    'title' =>T('角色查看'),
                ))
			*/
				->set('class', 'secondary_tabs')
				->tab_event('admin.people.tab')
				->content_event('admin.people.content');

		$params = Config::get('system.controller_params');
		$tabs->content->secondary_tabs->select($params[1]);

	}

	static function _secondary_signup_content($e, $tabs){
		if(Input::form('submit')){
			$form = Form::filter(Input::form())->validate('signup_doc', 'not_empty', T('注册须知不能为空！'));
			if($form->no_error){
				Site::set('people.signup.doc', $form['signup_doc']);
				// 记录日志
				/*
				$log = sprintf('[people] %s[%d]修改了系统设置中的注册须知',
							   L('ME')->name, L('ME')->id);
				*/
				Log::add($log, 'journal');
				Site::message(Site::MESSAGE_NORMAL, T('内容修改成功'));
			}
		}

		$tabs->content = V('people:admin/signup', array('form'=>$form))
							->set('signup_doc', Site::get('people.signup.doc', Config::get('people.signup.doc')));
	}
}
