#!/usr/bin/env php
<?php
// regenerate sphinx index
// 此脚本可用作更新商家商品的索引。
include dirname(dirname(__FILE__))."/base.php"; 

$sphinx = Database::factory('@sphinx');

$db = Database::factory();

$vid = (int)$argv[1];

if ($vid) $SQL = "SELECT COUNT(*) FROM vendor_product where vendor_id = {$vid}";
else $SQL = "SELECT COUNT(*) FROM vendor_product";

$total = (int)$db->value($SQL);

echo "\t共计{$total}条商品数据需要更新索引!\n";

$start = 0;

$per = 10;

$dtstart = time();

if ($vid) $query_sql = "SELECT * FROM vendor_product where vendor_id = {$vid} LIMIT %d, %d";
else $query_sql = "SELECT * FROM vendor_product LIMIT %d, %d";



while ($start < $total) {

	$vps = $db->query($query_sql, $start, $per)->rows();
	
	foreach ($vps as $vp) {
	
		$v = array('id' => $vp->id);

		$v['name'] = $vp->name;
		$v['group_name'] = $vp->name;
		$v['catalog_no'] = $vp->manufacturer. ' '.$vp->catalog_no;
		$v['description'] = $vp->description;
		$v['keywords'] = implode(', ', (array)@json_decode($vp->keywords, TRUE));
		$v['vendor_name'] = (string)$db->value('SELECT name FROM vendor WHERE id=%d', $vp->vendor_id);
		$v['is_frozen'] = (int) (boolean) $vp->freeze_reasons;
		$v['price'] = (float) $vp->price;
		$v['ctime'] = (int)$vp->ctime;
		$types_sphinx_indexes =  Config::get('product.types_sphinx_indexes');
		$v['type'] = $types_sphinx_indexes[$vp->type];
		$v['product_id'] = $vp->product_id;
		$v['vendor_id'] = $vp->vendor_id;
		$v['publish_date'] = $vp->publish_date;
		$v['approve_date'] = $vp->approve_date;
		
		$categories = array();
		if ($vp->category_id) {
			$c = O('product_category', $vp->category_id);
			while($c->id && $c->root->id) {
				$categories[] = (int) $c->id;
				$c = $c->parent;
			}
			
			unset($c);
		}
		$v['category'] = $categories;
		
		unset($categories);
		unset($vp);
		
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

		$SQL = 'REPLACE INTO `vendor_product` ('.implode(',', $k).') VALUES ('.implode(',', $v).')';
		
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
	
	unset($vps);
	
	$start += $per;
}

$time = time() - $dtstart;
echo "\n";
echo "\t\t共计耗时{$time}秒\n";

