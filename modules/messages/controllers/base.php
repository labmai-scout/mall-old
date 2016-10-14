<?php

abstract class Base_Controller extends Layout_Controller {

	protected $layout_name = 'people:layout';

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->layout->sidebar = V('people:sidebar');
		$this->layout->body = V('people:body');

		$me = L('ME');

		$this->layout->title = I18N::T('messages', '消息中心');
		// $this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		$this->layout->body->primary_tabs
				->add_tab('index', array(
					'url' => URI::url('!messages/index'),
					'title' => I18N::T('messages', '消息中心'),
				));

		if ($me->id && $me->is_active()) {
			$this->layout->body->primary_tabs
					->add_tab('add', array(
						'url' => URI::url('!messages/add'),
						'title' => I18N::T('messages', '添加消息'),
					));
		}

	}

	function _after_call($method, &$params) {
		parent::_after_call($method, $params);
		$this->add_css('people:layout people:sbmenu');
		$this->add_css('messages:message');
	}

}
