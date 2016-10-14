<?php

class Order_Base_Controller extends Base_Controller {

    function _before_call($method, &$params) {
        parent::_before_call($method, $params);

        $this->add_css('sale');
        $this->layout->title = T('订单管理');
        $this->layout->body->primary_tabs = Widget::factory('tabs');

    }

    protected function _add_index_tabs($vendor) {
        if(!$vendor->id) return;

        $me = L('ME');
        if (!$me->is_allowed_to('查看订单', $vendor)) {
            URI::redirect('error/401');
        }

        $bucket = Billing_Bucket_Model::vendor_bucket($vendor);


        $this->layout->body->primary_tabs
                ->add_tab('index', array(
                        'url'=> URI::url('!vendor/order/index/index.'.$vendor->id),
                        'title'=> HT('订单列表'),
                ));
        if (Config::get('payment.accounting_management_approve')) {

            $this->layout->body->primary_tabs
                ->add_tab('billing', array(
                        'url'=> URI::url('!vendor/order/billing/statements.'.$vendor->id),
                        'title'=> HT('结算管理'),
                        'number' => $bucket->item_count() ?: NULL,
                ));
        }
                
    }

}
