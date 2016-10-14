<?php
require('../../base.php');

/*
 * author Yu Li <yu.li@geneegroup.com>
 * 处理tci的数据
 */

$csv_file = $argv[1];

if (!is_file($csv_file)) {
    fecho(T('CSV文件 %csv_file 不存在!', array('%csv_file'=>$csv_file)));
    return;
}

$sale_info = [
    'info' => '累计消费可参加集分换购活动',
    'types' => ['赠'],
];
$sale_info = json_encode($sale_info, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

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
    $product->sale_info = $sale_info;
    $product->save();

}
