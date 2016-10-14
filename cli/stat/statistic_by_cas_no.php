#!/usr/bin/env php
<?php

//商品销售排名情况 按cas号归类
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
clean_cache();

$db = Database::factory();
//获取cas号
$res = $db->query("select p.id, p.name, oi.order_id, oi.price, oi.quantity, p._extra from order_item as oi left join product as p on p.id = oi.product_id where p._extra like '%cas_no%'")
          ->rows('assoc');
$create = $db->query("create table statistic_cas_no (id int primary key auto_increment,
                                                    cas_no varchar(50),
                                                    name varchar(255),
                                                    order_id bigint(20),
                                                    product_id bigint(20),
                                                    price double,
                                                    quantity int(11)) 
                                                    charset UTF8
            ");
foreach ($res as $value) {
    $_extra = json_decode($value['_extra']);
    $cas_no = $_extra->cas_no;
    if (!empty($cas_no)) {
      $product_id = $value['id'];
      $name = $value['name'];
      $order_id = $value['order_id'];
      $price = $value['price'];
      $quantity = $value['quantity'];
      //将cas号插入表
      $insert = $db->query("insert into statistic_cas_no (cas_no, name, order_id, product_id, price, quantity) values ('$cas_no', '$name', '$order_id', '$product_id', '$price', '$quantity')");
    }
}

$products = $db->query("select group_concat(distinct name separator '$') as name,cas_no,".
    "count(order_id) as order_num, sum(price) as total_price, sum(quantity) as ".
    "total_quantity from statistic_cas_no where price!=-1 " .
    "group by cas_no order by total_quantity desc")
             ->rows('assoc');
$nd_products = $db->query("select group_concat(distinct name separator '$') as name,cas_no," .
    "count(order_id) as order_num,sum(quantity) as " .
    "total_quantity from statistic_cas_no where price = -1 " .
    "group by cas_no order by total_quantity desc")
             ->rows('assoc');
$csv = new CSV('statistic_by_cas_no.csv', 'w');
$csv->write(['商品名称', 'CAS 号', '销量', '购买总额', '订单数量']);
foreach ($products as $product) {
  if (!empty($product['name'])) {
    $total_quantity = $product['total_quantity'];
    $total_price = $product['total_price'];
    $order_num = $product['order_num'];
    foreach($nd_products as $nd_product){
      if($nd_product['name']===$product['name']){
        $total_quantity=$product['total_quantity'].'+'.$nd_product['total_quantity'];
        $total_price=$product['total_price'].'+'.'待询价';
        $order_num=$product['order_num'].'+'.$nd_product['order_num'];
        break;
      }
    }
    $csv->write(
      [
        $product['name'],
        $product['cas_no'],
        $total_quantity,
        $total_price,
        $order_num
      ]
    );
  }
}
$csv->close();
