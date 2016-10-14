<?php
/**
* @file update_order_no_unique.php
* @brief 订单的order_no索引最初创建的时候不是unique，导致目前显示的订单编号不唯一
*        为了进行unique的升级，首先需要修复数据
*        bug#8591
* @author PiHiZi <pihizi@msn.com>
* @version 0.1.0
* @date 2015-04-15
 */

// select id,order_no,`status`, mtime, purchaser_id, vendor_id 
//        from `order` 
//        where order_no in (
//           select order_no from `order` group by order_no having count(*)>1
//        );
/*
+-------+-----------+--------+------------+--------------+-----------+
| id    | order_no  | status | mtime      | purchaser_id | vendor_id |
+-------+-----------+--------+------------+--------------+-----------+
|  5312 | 150108122 |      8 | 1420719385 |         2570 |        45 |
|  5313 | 150108122 |      5 | 1422329633 |         2570 |        52 |
|  5594 | 150112113 |      8 | 1426063420 |         2950 |        24 |
|  5595 | 150112113 |      6 | 1428543046 |         2487 |        64 |
|  6269 | 150119103 |      8 | 1421653006 |          410 |        52 |
|  6270 | 150119103 |      8 | 1421725237 |         3024 |       144 |
|  7084 | 150127094 |      6 | 1428542800 |         1285 |        64 |
|  7085 | 150127094 |      5 | 1426557691 |         2570 |        52 |
|  7698 | 150204043 |      4 | 1428569192 |          193 |       107 |
|  7699 | 150204043 |      6 | 1429069779 |          336 |       178 |
|  8775 | 150309029 |      2 | 1426145314 |         2435 |        19 |
|  8776 | 150309029 |      2 | 1425865518 |         1778 |        24 |
|  8892 | 150309145 |      2 | 1426235007 |         2895 |        55 |
|  8893 | 150309145 |      4 | 1428626179 |         2216 |        40 |
|  9040 | 150310033 |      5 | 1426835426 |         2763 |        29 |
|  9041 | 150310033 |      8 | 1425953288 |         2144 |        30 |
|  9758 | 150314014 |      2 | 1426821577 |         1226 |        55 |
|  9759 | 150314014 |      5 | 1427335837 |         2189 |       112 |
| 10935 | 150323016 |      2 | 1427074629 |         1213 |       170 |
| 10936 | 150323016 |      2 | 1427246283 |          950 |        20 |
| 12322 | 150401034 |      2 | 1428574177 |         2027 |        29 |
| 12323 | 150401034 |      8 | 1427939863 |          944 |       182 |
| 12541 | 150402056 |      8 | 1427962785 |          898 |        50 |
| 12542 | 150402056 |      5 | 1428541845 |          189 |        60 |
| 14311 | 150414072 |      8 | 1428983010 |          855 |        24 |
| 14312 | 150414072 |      8 | 1428989691 |         3439 |       139 |
+-------+-----------+--------+------------+--------------+-----------+
 */

require '../base.php';

$sql = 'select `id` from `order` where `order_no` in (select `order_no` from `order` group by `order_no` having count(*)>1)';
$db = database::factory();
$results = $db->query($sql)->rows();
$total = count($results);
if (!$total) {
    Upgrader::echo_fail('不存在订单号重复的数据, 不需要执行任何修复' . "\n");
    return;
}

$no = [];
$fixed = 0;
$canceled = 0;
foreach ($results as $p) {
    $order = O('order', $p->id);
    $no[$order->order_no] = (int)$no[$order->order_no] + 1;
    // 第一个，且已经取消，则不处理
    if ($no[$order->order_no]==1) {
        $canceled++;
        continue;
    }
    $count = (int)($no[$order->order_no] / 26);
    $char = chr(64 + ((int)($no[$order->order_no] % 26) ?: 26));
    $suffix = str_repeat(chr(65), $count) . $char;
    $order_no = $order->order_no;
    $order->order_no .= $suffix;
    $order->save();
    Upgrader::echo_success(vsprintf("id:%s\torder_no:%s(%s)\tstatus:%s\tpurchaser:%s(%s)\tvendor:%s(%s)", [
        $order->id,
        $order->order_no,
        $order_no,
        Order_Model::$status[$order->status],
        $order->purchaser->name,
        $order->purchaser->id,
        $order->vendor->name,
        $order->vendor->id,
    ]) . "\n");
    $fixed++;
}
Upgrader::echo_success("{$total}条重复数据中, {$canceled}条不需要修复, {$fixed}条修复完成\n");

