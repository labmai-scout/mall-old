<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);

/*
 * file clean_sale_by_csv.php
 * author Yu Li <yu.li@geneegroup.com>
 * date 2014-09-04
 *
 * brief 根据csv文件删除促销信息
 * usage SITE_ID=nankai php clean_sale_by_csv.php test.csv
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
    if($product->orig_price) {
   	 $product->unit_price = $product->orig_price;
    	if ($product->dirty) {
            $product->update_version();
    	}
    }
    $product->orig_price = 0;
    $product->sale_info = '';
    $product->save();

}
