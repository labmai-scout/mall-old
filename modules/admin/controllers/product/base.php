<?php

class Product_Base_Controller extends Layout_Admin_Controller {

    function _before_call($method, &$params) {
		if (!L('ME')->access('管理商品')) {
			URI::redirect('error/401');
		}

        parent::_before_call($method, $params);

        $this->layout->title = T('商品管理');

        $this->layout->body = V('product_body');
        $this->layout->body->primary_tabs = Widget::factory('tabs');

        $this->layout->body->primary_tabs
            ->add_tab('products', array(
                'url'=>URI::url('!admin/product/products'),
                'title'=>T('上架商品审批')
			));

		$this->add_css('admin:product');
    }

}
