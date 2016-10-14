<?php

require '../base.php';

//Config::set('database.nankaitst.url', 'mysql://genee:83719730@liyu-mysql.docker.local/pihizi_mall_test');

$db = database::factory();
$db->query("SET interactive_timeout = 144000;");
$db->query("SET wait_timeout = 144000;");

function logMe($log)
{
    echo "update_dirty_product {$log}\n";
}

function merge($row)
{
    updateMe($row, true);
}

$start = date('Y-m-d H:i:s');
$countRevision = 0;
$countUpdate = 0;
$countDelete = 0;

function updateMe($row, $needRevision = false)
{
    global $db;
    global $countRevision;
    global $countUpdate;
    global $dirty;
    $sql = "select * from _tmp_dirty_product where vendor_id={$db->quote($row->vendor_id)} and manufacturer={$db->quote($row->manufacturer)} and catalog_no={$db->quote($row->catalog_no)} and package={$db->quote($row->package)} order by mtime desc";
    $query = $db->query($sql);
    $nid = '';
    $nversion = 0;
    $ids = [];
    $loop = 1;
    while ($r = $query->row()) {
        // 第一条记录为最新记录，不做修改
        if (!$nid) {
            $nid = $r->id;
            $nversion = $r->version;
        } else {
            if ($needRevision) {
                $hash = hash('sha1', $r->vendor_id.$r->manufacturer.$r->catalog_no.$r->package);
                if (in_array($r->id, $dirty[$hash])) {
                    // 将重复的商品记录生成revision
                    $approveDate = $r->approve_date;
                    $lastApproveDate = $r->last_approve_date ?: $approveDate;
                    $unapproveDate = $r->unapprove_date ?: time();
                    $sql = "INSERT INTO `product_revision` (vendor_id,unit_price,orig_price,sale_info,vendor_note,approver_id,approve_date,publisher_id,publish_date,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra,last_approver_id,last_approve_date,last_publisher_id,last_publish_date,unapprover_id,unapprove_date, product_id,version) (SELECT vendor_id,unit_price,orig_price,sale_info,vendor_note,approver_id,approve_date,publisher_id,publish_date,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra,last_approver_id,{$lastApproveDate},last_publisher_id,last_publish_date,unapprover_id,{$unapproveDate},{$nid},{$nversion}+{$loop} FROM product WHERE id={$r->id})";
                    logMe("{$sql}");
                    $db->query($sql);
                    // 上品生成的订单，关联到最新的商品
                    $sql = "update `order_item` set `product_id`={$nid},`version`={$nversion}+{$loop} where `product_id`={$r->id}";
                    logMe("{$sql}");
                    $db->query($sql);
                    $countRevision++;
                    $loop++;
                }
            }
            // 删掉这个重复商品
            $ids[] = $r->id;
        }
    }
    if ($loop>1) {
        $sql = "update `product` set `vesion`={$nversion}+{$loop} where id={$nid}";
        logMe("{$sql}");
        $db->query($sql);
        $countUpdate++;
    }
    deleteMe($ids);
}

function deleteMe(array $ids = [])
{
    global $db;
    global $countDelete;
    if (empty($ids)) {
        return;
    }
    $sql = 'delete from product where id in ('.implode(',', $ids).')';
    $db->query($sql);
    logMe("{$sql}");
    $countDelete += count($ids);
}

// start

// 检查重复product，并生成临时表
logMe("开始统计重复商品数据");
$sql = 'create temporary table _tmp_dirty_product select product.id,product.vendor_id,product.manufacturer,product.catalog_no,product.package,product.mtime,product.version,product.approve_date,product.last_approve_date,product.unapprove_date from product join (select count(*),vendor_id,manufacturer,catalog_no,package from product group by vendor_id,manufacturer,catalog_no,package having count(*)>1) as t1 on product.vendor_id=t1.vendor_id and product.manufacturer=t1.manufacturer and product.catalog_no=t1.catalog_no and product.package=t1.package';
$results = $db->query($sql);
$total = $db->query('select count(*) from _tmp_dirty_product')->value();
logMe("\t共有{$total}条重复商品");

// 查询重复product生成的订单信息
logMe("查询受影响的订单");
$sql = 'create temporary table _tmp_dirty_order_item select id,product_id,count(*) as `count` from order_item where product_id in (select id from _tmp_dirty_product) group by product_id';
$results = $db->query($sql);
$total = $db->query('select count(*) from _tmp_dirty_order_item')->value();
logMe("\t共产生了{$total}条订单");

// 根据product.id,vendor_id,manufacturer,catalog_no,package
// 检查出来生成了订单的商品，并进行排序
logMe("查询生成订单的商品信息");
$sql = 'select p.id,p.vendor_id,p.manufacturer,p.catalog_no,p.package from _tmp_dirty_product as p join _tmp_dirty_order_item as oi on p.id=oi.product_id group by p.id,p.vendor_id,p.manufacturer,p.catalog_no,p.package order by p.mtime,p.vendor_id,p.manufacturer,p.catalog_no,p.package';
$results = $db->query($sql)->rows();

$dirty = [];
foreach ($results as $pdt) {
    $hash = hash('sha1', $pdt->vendor_id.$pdt->manufacturer.$pdt->catalog_no.$pdt->package);
    $dirty[$hash] = $dirty[$hash] ?: [];
    $dirty[$hash][] = $pdt->id;
}

/*
// 需要重点关注的数据
$alone = array_filter($dirty, function($v) {
    return count($v)==1;
});
$multi = array_filter($dirty, function($v) {
    return count($v)>1;
});
 */

$sql = 'select vendor_id,manufacturer,catalog_no,package from _tmp_dirty_product group by vendor_id,manufacturer,catalog_no,package';
$query = $db->query($sql);

//$db->begin_transaction();

$diff = time() - $mt;
$mt = time();

logMe("开始清苦数据：");
// main
while ($row = $query->row()) {
    $hash = hash('sha1', $row->vendor_id.$row->manufacturer.$row->catalog_no.$row->package);
    if (isset($dirty[$hash])) {
        merge($row);
        continue;
    }
    updateMe($row);
}
//$db->rollback();

logMe("\n删除{$countDelete}条数据\n");
logMe("\n为{$countUpdate}条商品生成{$countRevision}条Revision数据\n");
$end = date('Y-m-d H:i:s');
logMe("\n{$start} ~ {$end}\n");
