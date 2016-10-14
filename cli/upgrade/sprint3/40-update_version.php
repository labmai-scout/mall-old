<?php
$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;
$time_start = microtime(true);

$mysql = Database::factory();

//order_item增加version列
if(!$mysql->query("ALTER TABLE `order_item` ADD `version` int(11) NOT NULL DEFAULT '1'")){
    die('order_item 增加字段 version 失败');
}

$start = 0;
$num = 100;
while(1) {
    $order_items = $mysql->query("select * from order_item limit $start,$num")->rows();
    if(!count($order_items)) break;

    foreach ($order_items as $order_item) {

        //通过id找到 product
        $product = $mysql->query("select id from product where id = {$order_item->product_id}")->row();

        //将现有的关联的 product 设置为dirty
        if($product->id) {
            $mysql->query("UPDATE `product` SET dirty=1 where id={$order_item->product_id}");
            $product_id = $product->id;
        }
        else{//如果没有product，则找product_revision
            $product = $mysql->query("select id,version,product_id from product_revision where tmp_id = {$order_item->product_id}")->row();
            $product_id = $product->product_id;
        }

        $version = $product->version ?: 1;


        if(!$product->id) {
            print_r($order_item);
            // die("没有找到product\n");
            continue;
        }

        if(!$mysql->query("update order_item set version={$version}, product_id={$product_id} where id={$order_item->id}")) {
            die("升级失败  update order_item set version={$version}, product_id={$product_id} where id={$order_item->id}");
        }
    }
	$start += $num;
    echo '.';

}

echo "order_item 升级完成\n";


// cart_item增加version列
if(!$mysql->query("ALTER TABLE `cart_item` ADD `version` int(11) NOT NULL DEFAULT '1'")){
    die('cart_item 增加字段 version 失败');
}


$start = 0;
$num = 100;
while(1) {
    $cart_items = $mysql->query("select * from cart_item limit $start,$num")->rows();
    if(!count($cart_items)) break;

    foreach ($cart_items as $cart_item) {

        //通过id找到 product
        $product = $mysql->query("select id,version from product where id = {$cart_item->product_id}")->row();

        //如果没有product，则找product_revision
        if(!$product->id) {
            $product = $mysql->query("select id,version,product_id from product_revision where tmp_id = {$cart_item->product_id}")->row();
            $product_id = $product->id;
        }

        $version = $product->version ?: 1;
        if(!$product->id) {
            print_r($cart_item);
            die("没有找到product\n");
        }

        if(!$mysql->query("update cart_item set version={$version},product_id={$product_id} where id={$cart_item->id}")) {
             die("升级失败  update cart_item set version={$version},product_id={$product_id} where id={$cart_item->id}");
        }
    }
	$start += $num;
    echo 'x';
}

echo "cart_item 升级完成\n";


$mysql->query("ALTER TABLE product_revision DROP tmp_id");

echo 'done';
echo "\n";
echo microtime(true) - $time_start;
echo "\n";

