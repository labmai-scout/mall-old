<?php

abstract class Base_Controller extends Layout_Vendor_Controller {

    function _need_agreement($class, $method, $params) {
        $data = [
            'Profile_Controller'=> [
                'index'=> function($p) {
                    return O('vendor', $p[0])->id;
                },
                'view'=> function($p) {
                    return O('vendor', $p[0])->id;
                },
                'edit'=> function($p) {
                    return O('vendor', $p[0])->id;
                },
            ],
            'Product_Index_Controller'=> [
                'index'=> function($p) {
                    return O('vendor', $p[0])->id;
                },
                'view'=> function($p) {
                    return O('product', $p[0])->vendor->id;
                },
                'snapshot'=> function($p) {
                    return O('product', $p[0])->vendor->id;
                },
                'add'=> function($p) {
                    return O('vendor', $p[0])->id;
                },
                'edit'=> function($p) {
                    return O('product', $p[0])->vendor->id;
                },
                'publish'=> function($p) {
                    return O('product', $p[0])->vendor->id;
                },
                'delete'=> function($p) {
                    return O('product', $p[0])->vendor->id;
                },
            ]/*,
            'Order_Billing_Controller'=> [
                'index'=> function($p) {
                    return O('vendor', $p[0])->id;
                },
                'bucket'=> function($p) {
                    return O('vendor', $p[0])->id;
                },
                'empty_bucket'=> function($p) {
                    return O('vendor', $p[0])->id;
                },
                'remove_order'=> function($p) {
                    return O('order', $p[0])->vendor->id;
                },
                'statements'=> function($p) {
                    return O('vendor', $p[0])->id;
                },
            ],
            'Order_Index_Controller'=> [
                'index'=> function($p) {
                    return O('vendor', $p[0])->id;
                },
                'view'=> function($p) {
                    return O('order', $p[0])->vendor->id;
                },
                'confirm'=> function($p) {
                    return O('order', $p[0])->vendor->id;
                },
                'recover'=> function($p) {
                    return O('order', $p[0])->vendor->id;
                },
            ],
            'Order_Statement_Controller'=> [
                'index'=> function($p) {
                    return O('billing_statement', $p[0])->vendor->id;
                },
                'delete'=> function($p) {
                    return O('billing_statement', $p[0])->vendor->id;
                },
                'settle'=> function($p) {
                    return O('billing_statement', $p[0])->vendor->id;
                },
            ]*/
        ];

        if (isset($data[$class][$method])) {
            return $data[$class][$method]($params);
        }
    }

    function _before_call($method, &$params) {

        $current_version = Config::get('vendor.current_agreement_version');
        $start = date_create(Config::get('vendor.current_agreement_date_start'));
        $current = date_create()->setTimestamp(time());

        if ($current_version && $current>=$start) {
            $vid = $this->_need_agreement(get_class($this), $method, $params);
            if ($vid) {
                $vendor = O('vendor', $vid);
                if ($vendor->id && $vendor->agreement_version!==$current_version) {
                    return URI::redirect("!vendor/agreement/index.{$vid}");
                }
            }
        }


		parent::_before_call($method, $params);
    }
	
}
