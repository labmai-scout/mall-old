<?php

class Admin_Controller extends Layout_Admin_Controller {

    function index($tab=NULL) {
        // 10362 【Win 8，firfox】【南通大学】0.1.0，mall-old，买方管理，添加买方功能去掉
        return;
		$me = L('ME');

		$this->layout->title = T('系统设置');
		$this->layout->body = V('admin:admin/body');

		$tabs = Widget::factory('tabs');

		if (Config::get('tag.group_limit')>=0 &&
			$me->access('管理组织机构')) {
			Event::bind('admin.index.content', array($this, '_index_groups'), 0, 'groups');
			$tabs
			->add_tab('groups', array(
				'title'=>T('组织机构'),
				'url'=> URI::url('!admin/admin/groups'),
			));
		}

		if ($me->access('管理通知')) {
			Event::bind('admin.index.content', array($this, '_index_notif'), 0, 'notif');
			$tabs
				->add_tab('notif', array(
				'title'=>T('通知管理'),
				'url'=> URI::url('!admin/admin/notif'),
			));
		}

		$tabs
			->tab_event('admin.index.tab')
			->content_event('admin.index.content')
			->select($tab);

		if (!count($tabs->tabs)) {
			URI::redirect('error/401');
		};

		$this->add_css('tag_sortable');

		$this->layout->body->primary_tabs = $tabs;

	}

	function _index_groups($e, $tabs) {
		$root = Tag_Model::root('group');
		$tags = Q("tag[parent=$root]:sort(weight)");
		$this->add_js('tag_sortable');
		$tabs->content = V('admin:admin/groups', array('tags'=>$tags, 'root'=>$root));
	}

	function _index_notif($e, $tabs) {

		// 此处能设置哪些通知? 以配置名列出
		// $ grep 'config' `find . -name "notification.php"`
		$configs = array(
			// 订单实时通知
			'notification.order_is_drafted_rt_notif',
			'notification.order_need_customer_confirm',
			'notification.order_is_confirmed_rt_notif',
			'notification.order_is_approved_rt_notif',
			'notification.order_is_transferred_rt_notif',
			'notification.order_is_transfer_failed_rt_notif',
			'notification.order_is_canceled_rt_notif',
			'notification.order_is_returning_rt_notif',
			'notification.order_is_recovered_rt_notif',
			// 订单定时通知
			'notification.order_daily_notif_for_customer',
			'notification.order_daily_notif_for_vendor',
			'notification.order_hourly_notif_for_vendor',
			'notification.order_hourly_notif_for_requester',
			);

		// 处理表单提交
		if (Input::form('submit')) {
			$form = Form::filter(Input::form())
						->validate('title', 'not_empty', HT('消息标题不能为空！'))
						->validate('body', 'not_empty', HT('消息内容不能为空！'));
			$vars['form'] = $form;

			if ($form->no_error && in_array($form['type'], $configs)) {
				$config = Site::get($form['type'], Config::get($form['type']));
				$tmp = array(
					'description'=>$config['description'],
					'strtr'=>$config['strtr'],
					'title'=>$form['title'],
					'body'=>$form['body']
				);
				foreach (Site::get('notification.handlers') as $k=>$v) {
					if (isset($form['send_by_'.$k])) {
						$value = $form['send_by_'.$k];
					}
					else {
						$value = 0;
					}
					$tmp['send_by'][$k] = $value;
				}
				Site::set($form['type'], $tmp);

				Site::message(Site::MESSAGE_NORMAL, HT('内容修改成功'));
			}
		}

		// preference_views($conf, $vars=NULL, $module, $use_default=TRUE) {
		$views = Notification::preference_views($configs, $vars, 'admin');

		$tabs->content = $views;
	}

	function _index_vendor($e, $tabs) {

		$support_vendor = Site::get('support_vendor');

		if (Input::form('submit')) {
			$form =  Form::filter(Input::form());

			$vendor = O('vendor', $form['vendor']);

			if (!$vendor->id) {
				$form->set_error('vendor', HT('请填写代购商'));
			}

			if ($form->no_error) {
				Site::set('support_vendor', $vendor->id);
				Site::message(Site::MESSAGE_NORMAL, H('设置默认代购商成功!'));
			}
		}
		else {
			$form['vendor'] = $support_vendor;
		}

		$tabs->content = V('admin:admin/vendor', array(
							   'form' => $form,
							   ));
	}

}
