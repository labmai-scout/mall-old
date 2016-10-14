#!/usr/bin/env php
<?php

//商品销售排名情况 供应商
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
clean_cache();

$db = Database::factory();
$vendors = $db->query("select distinct(name) as name from vendor;")->rows('assoc');
$result = $db->query("select v.name, count(oi.id) as order_num, sum(oi.price) as total_price, " . 
    "sum(oi.quantity) as total_quantity from `order` as o left join order_item as oi " . 
    "on oi.order_id = o.id left join vendor as v on v.id = o.vendor_id where oi.price != -1 " . 
    "group by o.vendor_id order by total_quantity desc;")
             ->rows('assoc');
//待询价的商品
$inquiry_result = $db->query("select v.name, count(oi.id) as order_num, " . 
    "sum(oi.quantity) as total_quantity from `order` as o left join order_item as oi " . 
    "on oi.order_id = o.id left join vendor as v on v.id = o.vendor_id where oi.price = -1 " . 
    "group by o.vendor_id;")
                     ->rows('assoc');
export_csv($result, $inquiry_result, $vendors);

function export_csv($arr, $inquiry_arr, $vendors)
{
    //商品类型
    $type = (array) Config::get('mall.api_values_mapping');

    $csv = new CSV('statistic_by_vendor.csv', 'w');
    $csv->write(['供应商名称', '销量', '购买总额', '订单数量']);
    foreach ($arr as $value) {
        foreach ($vendors as $k => $v) {
            if (!empty($value['name']) && in_array($v['name'], $value)) {
                unset($vendors[$k]);
                $total_quantity = $value['total_quantity'];
                $total_price = $value['total_price'];
                $order_num = $value['order_num'];
                foreach ($inquiry_arr as $inquiry) {
                    if (in_array($value['name'], $inquiry)) {
                        $total_quantity = $value['total_quantity'] . ' + ' . $inquiry['total_quantity'];
                        $total_price = $value['total_price'] . ' + 待询价';
                        $order_num = $value['order_num'] . ' + ' . $inquiry['order_num'];
                        break;
                    }
                }

                $csv->write(
                    [
                        $value['name'],
                        $total_quantity,
                        $total_price,
                        $order_num
                    ]
                );
            }
        }
    }
    foreach ($vendors as $value) {
        $csv->write(
            [
                $value['name'],
                0,
                0,
                0
            ]
        );
    }
    $csv->close();
}
