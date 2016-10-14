<?php

abstract class Layout_Mall_Controller extends Layout_Controller {

	protected $layout_name = 'mall:layout';

	function _before_call($method, &$params) {
		$new_url = Config::get('mall.new_url');
		$params = [];
		if(Auth::logged_in()) {
    		$params = ['oauth-sso' => 'mall.nankai'];
		}
		URI::redirect(URI::url($new_url, $params));

		parent::_before_call($method, $params);
		$this->layout->header = V('mall:header');
		$this->layout->sidebar = V('mall:sidebar');
		$this->layout->footer = V('mall:footer');
		$this->layout->body_header = V('mall:body_header');

		$tabs = Widget::factory('tabs');

		$types = Product_Model::get_types();

		// mall layout 有 sidebar, 未登录时 sidebar 中有 login box,
		// 若要从 login box 登录后能返回登录前页面, 就需在有 login box 的页面
		// 增加 $_SESSION['#LOGIN_REFERER'] (xiaopei.li@2012-07-10)
		if (!L('ME')->id) {
			$_SESSION['#LOGIN_REFERER'] = URI::url();
		}

		$this->layout->nav_tabs = V('mall:tab', array(
			'select_tab' => 'reagent'
		));
		// $this->layout->sub_header = V('mall:search');
	}

	function _after_call($method, &$params) {
		parent::_after_call($method, $params);
		//$this->add_css('mall:layout');
		$this->add_css('mall:theme');
		$this->add_css('site');
		$this->add_css('preview');
		$this->add_js('preview');
        $this->add_css('popover');
        $this->add_js('popover');
	}
}
