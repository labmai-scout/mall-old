<?php

class Financial_Base_Controller extends Layout_Admin_Controller {

    function _before_call($method, &$params) {
		if (!L('ME')->access('管理结算')) {
			URI::redirect('error/401');
		}

        parent::_before_call($method, $params);

        $this->layout->title = T('结算管理');
        $this->layout->body = V('financial/body');
        $this->layout->body->primary_tabs = Widget::factory('tabs');

        $this->layout->body->primary_tabs
            ->add_tab('statement', array(
                'url'=>URI::url('!admin/financial/statement'),
                'title'=>T('结算管理')
            ))
            ;    

    }

}
