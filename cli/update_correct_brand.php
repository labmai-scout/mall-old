<?php
/*
* 定时脚本，从mall_brand更新品牌信息
*/
require('base.php');

$db = Database::factory();

const CORRECT_DELETE = 3;
const CORRECT_TRUE = 2;
const CORRECT_FALSE = 1;
const CORRECT_PENDING = 0;

function update_mysql_and_sphinx($name, $brand, $manufacturer='') {
	$db = Database::factory();
	$sphinx = Database::factory('@sphinx');

	$mtime = time();

	if($manufacturer) {
		$db->query("UPDATE product LEFT JOIN order_item ON product.id=order_item.product_id SET manufacturer='%s' WHERE order_item.id is null AND product.brand='%s' AND product.dirty=0", $manufacturer, $name);
	}

	//更新brand和mtime
	$db->query("UPDATE product SET brand='%s', mtime=%d WHERE brand='%s'", $brand, $mtime, $name);

	//对mtime=$mtime的product数据进行sphinx更新
	$types = Config::get('product.types');
	$reagent_types = (array) Config::get('reagent.types');
	$reagent_ranges = Config::get('reagent.price_ranges');
	$biologic_reagent_ranges = Config::get('biologic_reagent.price_ranges');
	$consumable_ranges = Config::get('consumable.price_ranges');
	$computer_ranges = Config::get('computer.price_ranges');
	$small_device_ranges = Config::get('small_device.price_ranges');

	$types_sphinx_indexes =  Config::get('product.types_sphinx_indexes');
	$intervals = Config::get('mall.supply_time');
	$index_name = 'product';
	foreach ($types as $type => $foo) {

		$start = 0;
		$per_page = 1000;
		$indexes = Config::get('sphinx.product_'.$type)['extra_weight'];
		while (true) {
			$products = $db->query("SELECT product.*,vendor.name as vendor_name,vendor.short_name as vendor_short_name FROM `product` LEFT JOIN vendor  ON (vendor.id=product.vendor_id) WHERE product.mtime=%d AND product.type='$type' limit %d, %d", $mtime, $start, $per_page)->rows();

			if (count($products) == 0) break;
			$values = [];
			$sub_values = [];
			foreach ($products as $product) {

				$unit_price = $product->unit_price;

				$extra = json_decode($product->_extra, TRUE);
				$vendor_name = implode(' ', rb_split_ex($product->vendor_name, __RB_SIMPLE_MODE__));
				$vendor_short_name = implode(' ', rb_split_ex($product->vendor_short_name, __RB_SIMPLE_MODE__));
				$product_name = implode(' ', rb_split_ex($product->name, __RB_SIMPLE_MODE__));
				$items = [];

				foreach (Product_Model::get_merge_criterias($product->type) as $name => $value) {

					$items[] = $product->$name;
				}
				$category = O('product_category', $product->category_id);
				$categories = [];
				while ($category->id && $category->root->id) {
					$categories[] = (int)$category->id;
					$category = $category->parent;
				}

				$v = [];
				$v['id'] = $product->id;
				$v['name'] = str_replace('%', '', $product_name);
				$v['group_name'] = $product_name;
				$v['catalog_no'] = $product->manufacturer. ' '.$product->catalog_no;
				$v['group_search'] = implode(' ', $items);
				$v['description'] = $product->description;
				$v['keywords'] = implode(', ', (array)@json_decode($product->keywords, TRUE));
				$v['is_frozen'] = (int) (boolean) $product->freeze_reasons;
				$v['price'] = (float) $unit_price;
				$v['vendor_name'] = $vendor_name.' '.$vendor_short_name;
				$v['vendor_short_name'] = $vendor_short_name;
				$v['vendor_short_name_abbr'] = PinYin::code($vendor_short_name, TRUE);
				$v['ctime'] = (int)$product->ctime;
				$v['vendor_id'] = $product->vendor_id;
				$v['publish_date'] = $product->publish_date;
				$v['approve_date'] = $product->approve_date;
				$v['stock_status'] = $product->stock_status;
				$v['category'] = $categories; //mva 类型支持数组
				$v['spec'] = $product->spec;
				$v['package'] = $product->package;
				$v['brand'] = $v['group_brand'] = $product->brand;
				$v['manufacturer'] = $product->manufacturer;
				$v['manufacturer_abbr'] = PinYin::code($product->manufacturer, TRUE);
				$v['sales'] = $product->sale_volume;
				$v['weight'] = rand(1,10000);
				$v['expire_date'] = $product->expire_date;
				$v['type'] = $types_sphinx_indexes[$product->type];

				// 供货时间区间
				$supply_time = (int)$product->supply_time;
				$v['supply_time'] = 50;
				foreach ($intervals as $status => $interval) {
					if (isset($interval[0]) && isset($interval[1])) {
						if ($supply_time > $interval[0] && $supply_time <= $interval[1]) {
							$v['supply_time'] = $status;
							break;
						}
					}
					elseif (isset($interval[0]) && !isset($interval[1])) {
						if ($supply_time >= $interval[0]) {
							$v['supply_time'] = $status;
							break;
						}
					}
				}

				$sv = [];
				if ($type == 'reagent') {
					$str = '';
					foreach ($indexes as $key => $value) {
						$arr[$value['weight']] = $value['index'];
					}
					foreach ($arr as $key => $attr) {
						/* 试剂类型需要处理为文本 */
						if ($attr == 'rgt_type') {
							$str .= ' '.$reagent_types[$extra[$attr]];
						}
						else {
							$str .= ' '.$extra[$attr];
						}
					}
					$v['extra'] = implode(' ', rb_split_ex($str, __RB_SIMPLE_MODE__));
					$v['keywords'] .= ', '.$reagent_types[$extra['rgt_type']];

					$sv['cas_no'] = $extra['cas_no'];
					$sv['alias'] = implode(',', (array)@json_decode($extra['rgt_aliases'], TRUE));
					$sv['rgt_type'] = $extra['rgt_type'];
					$sv['price_range'] = calculate_range($unit_price,$reagent_ranges);
				}
				elseif ($type == 'biologic_reagent') {
					$v['extra'] = $extra['transport_cond'].' '.$extra['storage_cond'];
	        		$sv['transport_cond'] = implode(' ', rb_split_ex($extra['transport_cond'], __RB_SIMPLE_MODE__));
	        		$sv['storage_cond'] = implode(' ', rb_split_ex($extra['storage_cond'], __RB_SIMPLE_MODE__));
	        		$sv['price_range'] = calculate_range($unit_price,$biologic_reagent_ranges);
				}
				elseif ($type == 'consumable') {
					$v['extra'] = $extra['consumable_en_name'];
					$sv['price_range'] = calculate_range($unit_price,$consumable_ranges);
				}
				elseif ($type == 'small_device') {
					$v['extra'] = $extra['origin'].' '.$extra['warranty_period'].' '.$extra['service_no'];
	        		$sv['origin'] = $extra['origin'];
	        		$sv['warranty_period'] = $extra['warranty_period'];
	        		$sv['service_no'] = $extra['service_no'];
	        		$sv['price_range'] = calculate_range($unit_price,$small_device_ranges);
				}
				elseif ($type == 'computer') {
					$v['extra'] = $extra['computer_type'].' '.$extra['cpu'].' '.$extra['memory'].' '.$extra['disk'].' '.$extra['display'].' '.$extra['video_memory'].' '.$extra['service_call'];
					$sv['cpu'] = $extra['cpu'];
					$sv['memory'] = $extra['memory'];
					$sv['disk'] = $extra['disk'];
					$sv['display'] = implode(' ', rb_split_ex($extra['display'], __RB_SIMPLE_MODE__));
					$sv['video_memory'] = $extra['video_memory'];
					$sv['service_call'] = $extra['service_call'];
					$sv['computer_type'] = $extra['computer_type'];
					$sv['price_range'] = calculate_range($unit_price,$computer_ranges);
				}
				else {
					$v['extra'] = null;
				}
				foreach ($v as $key => $value) {
					if (is_string($value)) {
						$v[$key] = $sphinx->quote($value);
					}
					elseif (is_array($value)) {
						$v[$key] = '('.$sphinx->quote($value).')';
					}
					else {
						$v[$key] = $value;
					}
				}
				$values[] = ' ('.implode(',', $v).') ';
				//分表的更新
				foreach ($sv as $key => $value) {
					if (is_array($value)) {
						$sv[$key] = '('.$sphinx->quote($value).')';
					}
					else {
						$sv[$key] = $sphinx->quote($value);
					}
				}
				$sub_v = $v + $sv;
				unset($sub_v['type']);
				unset($sub_v['extra']);
				$sub_values[] = ' ('.implode(',', $sub_v).') ';
			}
			$k = [];
			foreach ($v as $kk => $foo) {
				$k[$kk] = $sphinx->quote_ident($kk);
			}
			//主表
			$SQL = 'REPLACE INTO `' . Search_Iterator::get_index_name($index_name) . '` ('.implode(',', $k).') VALUES '.implode(', ', $values);

			$sphinx->query($SQL);
			//分表更新
			$sk = [];
			foreach ($sub_v as $skk => $foo) {
				$sk[$skk] = $sphinx->quote_ident($skk);
			}
			$SQL2 = 'REPLACE INTO `' . Search_Iterator::get_index_name($index_name.'_'.$type) . '` ('.implode(',', $sk).') VALUES '.implode(', ', $sub_values);
			$sphinx->query($SQL2);
			$start += $per_page;
		}
	}
}

function calculate_range($unit_price, $ranges) {
	foreach ((array)$ranges as $rang_status => $range) {
		if (isset($range[0]) && isset($range[1])) {
			if ($unit_price >= $range[0] && $unit_price < $range[1]) {
				return $rang_status;
			}
		}
		elseif (isset($range[0]) && !isset($range[1])) {
			if ($unit_price >= $range[0]) {
				return $rang_status;
			}
		}
	}
	return 50;
}

try{

$mall_brand = Config::get('mall.brand');

$rpc = new RPC($mall_brand['api']);
$rpc->mall->brand->authorize($mall_brand['client_id'], $mall_brand['client_secret']);
$mtime = Site::get('last_update_brand_mtime1') ?: 0;
$params['from'] = $mtime;

$result = $rpc->mall->brand->searchBrandAliases($params);

$start = 0;
$num = 100;
while ($start < $result['total_count']) {
    $aliases = $rpc->mall->brand->getBrandAliases($result['token'], $start, $num);

    foreach ($aliases as $alias) {

        $mtime = $alias['mtime'];
        $alias_name = $alias['alias'];

        $brand_name = $alias['brand'] ? $alias['brand']['name'] : '';
        $manufacturer = $alias['brand'] ? $alias['brand']['company'] : '';

        if(!$alias_name) {
        	continue;
        }
        //mall-brand删除了品牌
        elseif($alias_name && !$brand_name) {
        	$db->query("delete from brand_alias where name='%s'", $alias_name);
        }
        else {
        	$brand_abbr = PinYin::code($brand_name);
        	//未认证的品牌,不需要更新
        	if(!$alias['brand']['verified']) {
        		$db->query("update brand_alias set correct = 0 where name='%s' or brand='%s'", $alias_name, $brand_name);
        	}
        	elseif($alias['correct'] == CORRECT_DELETE) { //已经删除的别名
        		//更新brand表
        		$db->query("delete from brand_alias where name='%s'", $alias_name);
        	}
        	elseif($alias['correct'] == CORRECT_TRUE) { //正确的别名
        		//更新brand表
        		$db->query("update brand_alias set brand='%s', brand_abbr='%s', manufacturer='%s', correct = 2 where name='%s'", $brand_name, $brand_abbr, $manufacturer, $alias_name);

        		update_mysql_and_sphinx($alias_name, $brand_name, $manufacturer);
        	}
        	elseif($alias['correct'] == CORRECT_FALSE) { // 拼写错误的别名
        		$db->query("update brand_alias set brand='%s', brand_abbr='%s', manufacturer='%s', correct = 1 where name='%s'", $brand_name, $brand_abbr, $manufacturer, $alias_name);

        		update_mysql_and_sphinx($alias_name, $brand_name, $manufacturer);
        	}
        	else { //待定的别名
        		$db->query("update brand_alias set correct = 0 where name='%s'", $brand_name, $alias_name);
        	}

        }
    }

    $start += $num;
}




Site::set('last_update_brand_mtime', $mtime);

}
catch(Exception $e) {
    echo "升级失败:";
    echo $e->getMessage();
    echo "\n";
}
