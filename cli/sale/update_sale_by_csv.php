<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
/*
 * file update_sale_by_csv.php
 * author Yu Li <yu.li@geneegroup.com>
 * date 2014-09-04
 *
 * brief 更新商品的促销信息
 *
 * 格式为
 * 商品id,商品货号,商品原价,折扣价,活动备注,标签
 *
 * usage SITE_ID=nankai php update_sale_by_csv.php test.csv
 */


$csv_file = $argv[1];

if (!is_file($csv_file)) {
    fecho(T('CSV文件 %csv_file 不存在!', array('%csv_file'=>$csv_file)));
    return;
}

$csv = new CSV($csv_file, 'r');
$csv->read();

while($row = $csv->read()) {

    $product = O('product', $row[0]);
    $csv_catalog_no = strtolower(trim($row[1]));
    $p_catalog_no = strtolower(trim($product->catalog_no));
    if ($csv_catalog_no != $p_catalog_no) {
        // 如果不一致而且，前面没有匹配，则报错
        if (strlen($row[1]) < 4 || strpos($p_catalog_no, $csv_catalog_no) === false) {
            echo T('%id 商品货号不一致, csv中为: %csv_no 实际为: %catalog_no', ['%id'=>$row[0], '%csv_no'=>$row[1], '%catalog_no'=>$product->catalog_no]);
            echo "\n";
            continue;
        }
    };


    $old_price = (float)trim($row[2]);
    $new_price = (float)trim($row[3]);

    $sale_info['info'] = $row[4];
    $sale_info['types'] = explode(' ', $row[5]);

    //如果新价格不为空，且不相等，说明有折扣
    if ($new_price !== ''  && $new_price < $old_price) {
        $product->unit_price = $new_price;
        $product->orig_price = $old_price;

        if ($product->dirty) {
            $product->update_version();
        }
    }

    $product->sale_info = json_encode($sale_info, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);


    $product->save();
}
echo "done\n";
