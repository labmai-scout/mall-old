#!/usr/bin/env php
<?php
// regenerate sphinx index
// 此脚本可用作更新商家商品的索引。

include dirname(dirname(__FILE__))."/base.php"; 


$sphinx = Database::factory('@sphinx');

$db = Database::factory();

$total = (int)$db->value("SELECT COUNT(*) FROM product");

echo "\t共计{$total}条产品数据需要更新索引!\n";

$start = 0;

$per = 10;

$dtstart = time();

while ($start < $total) {
	$ps = $db->query("SELECT * FROM product LIMIT %d, %d", $start, $per)->rows();
	
	foreach ($ps as $product) {
		$product = O('product', $product->id);
	
		$v = array('id' => $product->id);


		$str = Mmseg::execute($product->name);
		while ($token = mmseg_next_token($mmseg)) {
			$str .= $token['text'].' ';
		}

		mmseg_algor_destroy($mmseg);


		$v['name'] = $str;
		$v['group_name'] = $str;

		$v['catalog_no'] = $product->manufacturer. ' '.$product->catalog_no;
		$v['description'] = $product->description;
		$v['keywords'] = implode(', ', (array)@json_decode($product->keywords, TRUE));
		$v['min_price'] = (float) $product->min_price;
		$v['max_price'] = (float) $product->max_price;
		$v['available'] = (int) ($product->min_price != NULL && $product->max_price != NULL);

		$c = $product->category;
		$categories = array();
		while($c->id && $c->root->id) {
			$categories[] = (int) $c->id;
			$c = $c->parent;
		}
		$v['category'] = $categories;
		$v['ctime'] = (int)$product->ctime;
		
		unset($categories);

		$types_sphinx_indexes =  Config::get('product.types_sphinx_indexes');
		$v['type'] = $types_sphinx_indexes[$product->type];

		$type_class = 'Search_Product_'.$product->type;
		if (method_exists($type_class, 'update_index')) {
			$type_class::update_index($product, $v);
		}
		
		unset($product);

		$k = array();
		foreach ($v as $kk => &$vv) {
			$k[$kk] = $sphinx->quote_ident($kk);
			if (is_array($vv)) {
				$vv = '('.$sphinx->quote($vv).')';
			}
			else {
				$vv = $sphinx->quote($vv);
			}
		}

		$SQL = 'REPLACE INTO `product` ('.implode(',', $k).') VALUES ('.implode(',', $v).')';
		
		unset($k);
		unset($v);
		
		if ($sphinx->query($SQL)) {
			echo '.';
		}
		else {
			echo 'x';
		}
		
		unset($SQL);
		
	}
	
	unset($ps);
	
	$start += $per;
}

$time = time() - $dtstart;
echo "\n";
echo "\t\t共计耗时{$time}秒\n";
