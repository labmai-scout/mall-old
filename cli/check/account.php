<?php
/**
 * SITE_ID=nankai php check/account.php [-m|--mode full]
 * mall-old中数据一致性检查
 * 1. 订单原始金额与对应商品快照金额总和一致 (是否保留了原始金额?)
 * 2. 订单商品摘要信息与商品快照信息吻合 (商品名称?)
 * 3. 付款单金额与付款单中订单金额总和一致
 * 4. 付款单中订单课题组id与付款单课题组id一致
 * 5. 结算单金额与结算单中订单金额总和一致
 * 6. 结算单中订单课题组id与结算单课题组id一致
 * 7. 南开天财相关:
 *   1. 付款单存在预约号的, 付款单金额, 经费编号, 项目编号应与天财返回数据一致
 *   2. 结算单存在预约号的, 付款单金额, 经费编号, 项目编号应与天财返回数据一致
 */
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
$shortopts = "m:";
$longopts = array(
    'mode',
    );
$opts = getopt($shortopts, $longopts);
$mode = $opts['m'] ? : $opts['mode'];

$file = fopen(ROOT_PATH.'/cli/notification.txt', 'w');
//订单原始金额与对应商品快照金额总和一致
$message = checkOrderPriceItemToal();
fwrite($file, $message);
//付款单金额与付款单中订单金额总和一致
$message = checkTransferBalance();
fwrite($file, $message);
//付款单中订单课题组id与付款单课题组id一致
$message = checkTransferCustomer();
fwrite($file, $message);
//结算单金额与结算单中订单金额总和一致
$message = checkBillingBalance();
fwrite($file, $message);
//结算单中订单课题组id与结算单课题组id一致
$message = checkBillingCustomer();
fwrite($file, $message);
if ($mode == 'full' && SITE_ID == 'nankai') {
    $message = checkTransferTiancaiData();
    fwrite($file, $message);
    $message = checkBillingTiancaiData();
    fwrite($file, $message);
}
fclose($file);

function checkOrderPriceItemToal()
{
    $message = "\n检测订单原始金额与对应商品快照金额总和一致\n";
    $sql = 'SELECT t2.voucher,t1.order_id,t2.price as order_price,
            SUM(IF(t1.price < 0,0,t1.price)) as total_price
            FROM order_item t1
            INNER JOIN `order` t2 ON t2.id = t1.order_id
            GROUP BY t1.order_id
            HAVING SUM(IF(t1.price < 0,0,t1.price)) <> order_price';
    $db      = Database::factory();
    $results = $db->query($sql)->rows();
    $total   = count($results);
    if ($total == 0) {
        $message .= "无异常数据\n";
    }
    else {
        $message .= "共计 $total 条异常订单记录\n";
        foreach ($results as $result) {
            $message .= "订单: ".$result->voucher."订单金额: ".$result->order_price." 商品金额总和: ".$result->total_price."\n";
        }
    }
    return $message;
}


function checkTransferBalance()
{
    $message = "\n检测付款单金额与付款单中订单金额总和一致\n";
    $sql     = 'SELECT t1.balance,t1.voucher as voucher ,
        sum(t3.price) as total_price  FROM transfer_statement t1
        LEFT JOIN _r_transfer_statement_order t2 ON t2.id1 = t1.id
        INNER JOIN `order` t3 ON t3.id = t2.id2
        GROUP BY t1.voucher
        HAVING ROUND(SUM(t3.price), 2) <> ROUND(balance, 2)';
    $db      = Database::factory();
    $results = $db->query($sql)->rows();
    $total   = count($results);
    if ($total == 0) {
        $message .= "无异常数据\n";
    }
    else {
        $message .= "共计 $total 条异常订单记录\n";
        foreach ($results as $result) {
            $message .= HT("付款单 %voucher 付款单金额 : %balance  订单总金额 %total_price \n", [
                    '%voucher' => $result->voucher,
                    '%balance' => round($result->balance, 2),
                    '%total_price' => round($result->total_price, 2),
                ]);
        }
    }
    return $message;
}

function checkTransferCustomer()
{
    $message = "\n检测订单所属课题组与对应付款单所属课题组一致\n";
    $sql     = 'SELECT
        t1.voucher as o_voucher,t3.voucher s_voucher,
        t4.name as s_name,t4.gapper_group as s_group,
        t5.name as o_name,t5.gapper_group as o_group
        FROM `order` t1
        LEFT JOIN _r_transfer_statement_order t2 ON t2.id2=t1.id
        INNER JOIN transfer_statement t3 ON t3.id=t2.id1
        INNER JOIN customer t4 ON t4.id = t3.customer_id
        INNER JOIN customer t5 ON t5.id = t1.customer_id
        WHERE t3.customer_id <> t1.customer_id';
    $db      = Database::factory();
    $results = $db->query($sql)->rows();
    $total   = count($results);
    if ($total == 0) {
        $message .= "无异常数据\n";
    }
    else {
        $message .= "共计 $total 条异常订单记录\n";
        foreach ($results as $result) {
            $message .= HT("付款单 %s_voucher (%s_name - %s_group ) : 订单 %o_voucher (%o_name - %o_group) \n", [
                    '%o_voucher' => $result->o_voucher,
                    '%o_name' => $result->o_name,
                    '%o_group' => $result->o_group,
                    '%s_voucher' => $result->s_voucher,
                    '%s_name' => $result->s_name,
                    '%s_group' => $result->s_group,
                ]);
        }
    }
    return $message;
}

function checkBillingBalance()
{
    $message = "\n检测结算单金额与结算单中订单金额总和一致\n";
    $sql = "SELECT t1.balance,t1.id as billing_id,sum(t3.price) as total_price,from_unixtime(t1.ctime)  FROM billing_statement t1
    LEFT JOIN _r_order_billing_statement t2 ON t2.id2 = t1.id
    INNER JOIN `order` t3 ON t3.id = t2.id1
    GROUP BY t1.id
    HAVING ROUND(SUM(t3.price), 2) <> ROUND(balance, 2)";
    $db      = Database::factory();
    $results = $db->query($sql)->rows();
    $total   = count($results);
    if ($total == 0) {
        $message .= "无异常数据\n";
    }
    else {
        $message .= "共计 $total 条异常订单记录\n";
        foreach ($results as $result) {
            $message .= HT("结算单 %billing_id 结算单金额 : %balance  订单总金额 %total_price \n", [
                    '%billing_id' => $result->billing_id,
                    '%balance' => round($result->balance, 2),
                    '%total_price' => round($result->total_price, 2),
                ]);
        }
    }
    return $message;
}

function checkBillingCustomer()
{
    $message = "\n检测结算单所属供应商与结算单所属供应商一致\n";
    $sql = 'SELECT
        t1.voucher as o_voucher,t3.id s_voucher,
        t4.name as s_name,t4.gapper_group as s_group,
        t5.name as o_name,t5.gapper_group as o_group
        FROM `order` t1
        LEFT JOIN _r_order_billing_statement t2 ON t2.id1=t1.id
        INNER JOIN billing_statement t3 ON t3.id=t2.id2
        INNER JOIN vendor t4 ON t4.id = t3.vendor_id
        INNER JOIN vendor t5 ON t5.id = t1.vendor_id
        WHERE t3.vendor_id <> t1.vendor_id';
    $db      = Database::factory();
    $results = $db->query($sql)->rows();
    $total   = count($results);
    if ($total == 0) {
        $message .= "无异常数据\n";
    }
    else {
        $message .= "共计 $total 条异常订单记录\n";
        foreach ($results as $result) {
            $message .= HT("结算单 %s_voucher (%s_name - %s_group ) : 订单 %o_voucher (%o_name - %o_group) \n", [
                    '%o_voucher' => $result->o_voucher,
                    '%o_name' => $result->o_name,
                    '%o_group' => $result->o_group,
                    '%s_voucher' => $result->s_voucher,
                    '%s_name' => $result->s_name,
                    '%s_group' => $result->s_group,
                ]);
        }
    }
    return $message;

}

function checkTransferTiancaiData()
{
    $message = "\n检测付款单与天财信息一致\n";
    $start   = 0;
    $limit   = 20;
    while (true) {
        $transfer_statements = Q("transfer_statement[reserv_no]")->limit($start, $limit);
        if (!count($transfer_statements)) break;
        $start += $limit;
        foreach ($transfer_statements as $statement) {
            if ($statement->pdata) {
                continue;
            }
            $local_bmbh    = $statement->bmbh;
            $local_xmbh    = $statement->xmbh;
            $local_balance = $statement->balance;
            $data          = $statement->get_pay_status();
            $head_message  = HT("[%statement_id] %vendor_name[%vendor_id] 付款\n", [
                '%statement_id' => $statement->id,
                '%vendor_name'  => $statement->customer->name,
                '%vendor_id'    => $statement->customer->gapper_group,
            ]);
            $local_message = HT("本地数据 部门编号: %bmbh, 项目编号: %xmbh, 金额： %balance\n", [
                '%bmbh'    => $local_bmbh,
                '%xmbh'    => $local_xmbh,
                '%balance' => $local_balance
            ]);
            if (!$data) {
                $message .= $head_message;
                $message .= $local_message;
                $message .= HT("天财数据 暂无\n");
            }
            else {
                $return = current($data);
                if (count($data) > 1) {
                    $return['JE'] = 0;
                    foreach($data as $d) {
                        //金额总和
                        $return['JE'] += $d['JE'];
                    }
                }
                $tiancai_balance = round($return['JE'], 2);
                $tiancai_bmbh    = $return['BMBH'];
                $tiancai_xmbh    = $return['XMBH'];

                if ($tiancai_xmbh != $local_xmbh ||
                    $tiancai_bmbh != $local_bmbh || $tiancai_balance != $local_balance
                    ) {
                    $message .= $head_message;
                    $message .= $local_message;
                    $message .= HT("天财数据 部门编号: %bmbh, 项目编号: %xmbh, 金额： %balance\n", [
                        '%bmbh'    => $tiancai_bmbh,
                        '%xmbh'    => $tiancai_xmbh,
                        '%balance' => $tiancai_balance
                    ]);
                }
            }
        }
    }
    return $message;
}

function checkBillingTiancaiData()
{
    $message = "\n检测结算单与天财信息一致\n";
    $start   = 0;
    $limit   = 20;
    while (true) {
        $billing_statements = Q("billing_statement[reserv_no]")->limit($start, $limit);
        if (!count($billing_statement)) break;
        $start += $limit;
        foreach ($billing_statements as $statement) {
            $local_bmbh    = $statement->bmbh;
            $local_xmbh    = $statement->xmbh;
            $local_balance = $statement->balance;
            $data          = $statement->get_account_status();
            $head_message  = HT("[%statement_id] %vendor_name[%vendor_id] 结算\n", [
                '%statement_id' => $statement->id,
                '%vendor_name'  => $statement->vendor->name,
                '%vendor_id'    => $statement->vendor->gapper_group,
            ]);
            $local_message = HT("本地数据 部门编号: %bmbh, 项目编号: %xmbh, 金额： %balance\n", [
                '%bmbh'    => $local_bmbh,
                '%xmbh'    => $local_xmbh,
                '%balance' => $local_balance
            ]);
            if (!$data) {
                $message .= $head_message;
                $message .= $local_message;
                $message .= HT("天财数据 暂无\n");
            }
            else {
                $return = current($data);
                if (count($data) > 1) {
                    $return['JE'] = 0;
                    foreach($data as $d) {
                        //金额总和
                        $return['JE'] += $d['JE'];
                    }
                }
                $tiancai_balance = round($return['JE'], 2);
                $tiancai_bmbh    = $return['BMBH'];
                $tiancai_xmbh    = $return['XMBH'];

                if ($tiancai_xmbh != $local_xmbh ||
                    $tiancai_bmbh != $local_bmbh || $tiancai_balance != $local_balance
                    ) {
                    $message .= $head_message;
                    $message .= $local_message;
                    $message .= HT("天财数据 部门编号: %bmbh, 项目编号: %xmbh, 金额： %balance\n", [
                        '%bmbh'    => $tiancai_bmbh,
                        '%xmbh'    => $tiancai_xmbh,
                        '%balance' => $tiancai_balance
                    ]);
                }
            }
        }
    }
    return $message;
}