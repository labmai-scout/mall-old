<?php

$config['is_allowed_to[查看价格].product'][] = 'Demo::product_ACL';
$config['customer.status.sort.filter'][] = 'Demo::customer_sort_filter';

$config['transfer_statement.get_external_message'][] = 'Demo::get_external_message';
$config['get_extra_vendor_links'][] = 'Demo::get_extra_vendor_links';
$config['product.get_avoid_buy_msg'][] = array('callback' => 'Demo_Product_Reagent::buy_easymade_toxic', 'weight' => -100);
