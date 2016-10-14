<?php

class Transfer_Base_Controller extends Layout_Admin_Controller {

    function _before_call($method, &$params) {
		if (!L('ME')->access('管理付款')) {
			URI::redirect('error/401');
		}

        parent::_before_call($method, $params);

        $this->layout->title = T('付款管理');

        $this->layout->body->primary_tabs = Widget::factory('tabs');
        
        $this->layout->body->primary_tabs
        	->add_tab('transfer', array(
        			'url'=> URI::url('!admin/transfer'),
        			'title'=> H(T('付款列表')),
        	));
        
    }

}