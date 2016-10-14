#!/usr/bin/env php
<?php

//商品销售排名情况 按生产商+货号归类
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
clean_cache();

$db = Database::factory();
$result = $db->query("select group_concat(distinct p.name separator '$') as name, p.type, p.manufacturer, " . 
    "p.catalog_no, count(oi.order_id) as order_num, sum(oi.price) as total_price, sum(oi.quantity) as " . 
    "total_quantity from order_item as oi left join product as p on p.id = oi.product_id where oi.price != -1 " . 
    "group by concat(p.manufacturer, p.catalog_no) order by total_quantity desc;")
             ->rows('assoc');
//待询价的商品
$inquiry_result = $db->query("select group_concat(distinct p.name separator '$') as name, p.type, " . 
    "p.manufacturer, p.catalog_no, count(oi.order_id) as order_num, sum(oi.quantity) as " . 
    "total_quantity from order_item as oi left join product as p on p.id = oi.product_id where oi.price = -1 " . 
    "group by concat(p.manufacturer, p.catalog_no);")
                     ->rows('assoc');
export_csv($result, $inquiry_result);

function export_csv($arr, $inquiry_arr)
{
    //商品类型
    $type = (array) Config::get('mall.api_values_mapping');

    $csv = new CSV('statistic_by_manufacturer_and_catalog_no.csv', 'w');
    $csv->write(['商品名称', '商品类型', '生产商', '货号', '销量', '购买总额', '订单数量']);
    foreach ($arr as $value) {
        if (!empty($value['name'])) {
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
                    $type[$value['type']]['title'],
                    $value['manufacturer'],
                    $value['catalog_no'],
                    $total_quantity,
                    $total_price,
                    $order_num
                ]
            );
        }
    }
    $csv->close();
}
