<?php

class Order_Base_Controller extends Layout_Admin_Controller {

    function _before_call($method, &$params) {
        parent::_before_call($method, $params);

        $this->add_css('sale');
        $this->layout->title = T('订单管理');
        $this->layout->body->primary_tabs = Widget::factory('tabs');
        $this->layout->body->primary_tabs
        	->add_tab('index', array(
        		'url'=> URI::url('!admin/order/index'),
        		'title'=> H(T('订单列表')),
        	));

    }

}
