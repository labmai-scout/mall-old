<?php
require 'base.php';

$db = Database::factory();
$db->query('truncate brand');
$start = 0;
$num = 200;
$total_count = $db->value('select count(*) from (select brand from product group by brand) p');
$mtime = time();
$mappings = array_flip((array)Config::get('mall.mapping_type'));
$values_mapping = Config::get('mall.api_values_mapping');

$mall_brand = Config::get('mall.brand');
$rpc = new RPC($mall_brand['api']);
$rpc->mall->brand->authorize($mall_brand['client_id'], $mall_brand['client_secret']);

while ($start < $total_count){
	$product_brands = $db->query("SELECT brand FROM product GROUP BY brand LIMIT $start,$num")->rows();
	foreach ($product_brands as $product_brand) {
		$aliases = [];
		$brand_name = $product_brand->brand;
		if(!$brand_name) continue;

		//得到types
		$brand_types = $db->query("SELECT type FROM product WHERE brand='{$brand_name}' GROUP BY type")->rows();

		$btypes = [];
        $types = [];
		foreach ($brand_types as $brand_type) {
			$type = $brand_type->type;
			$types[] = [
				'title' => $values_mapping[$type]['title'],
				'name' => $mappings[$type],
			];
			//用于给brand的type赋值
			$btypes[] = $mappings[$type];
		}

		$params['types'] =  $types;
		//mall-brand的brand信息
		$brand_info = $rpc->mall->brand->getBrand($brand_name, $params);

		//如果没有信息返回，则getBrand会根据$params信息在mall-brand中新建
		if(!count($brand_info)) continue;

		if(!$brand_info['verified']) continue;

		//更新数据和索引
		if($brand_name != $brand_info['name']) {
			//先更新索引，然后更新数据
			update_sphinx_by_brand($brand_name, $brand_info['name'], $brand_info['company']);
			if($brand_info['company']) {
				$db->query("UPDATE product SET brand='%s', manufacturer='%s' WHERE brand='%s' AND dirty=0", $brand_info['name'], $brand_info['company'], $brand_name);
			}
			else {
				$db->query("UPDATE product SET brand='%s' WHERE brand='%s' AND dirty=0", $brand_info['name'], $brand_name);
			}
		}

		$brand = O('brand');

		$brand->name = $brand_info['name'];
		$brand->manufacturer = $brand_info['company'];
		$brand->types = json_encode(array_values($btypes));

		//得到品牌
		foreach ($brand_info['aliases'] as $key => $alias) {
			if($alias['correct'] == 2) $aliases[] = $alias['alias'];
		}

		$brand->aliases = json_encode($aliases);
		$brand->save();

	}

	$start += $num;
}

function update_sphinx_by_brand($alias, $brand, $manufacturer='') {

	if(!$alias || !$brand) return;
	$db = Database::factory();
	$sphinx = Database::factory('@sphinx');

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
			$products = $db->query("SELECT product.*,vendor.name as vendor_name,vendor.short_name as vendor_short_name FROM `product` LEFT JOIN vendor  ON (vendor.id=product.vendor_id) WHERE product.brand='%s' AND product.type='$type' AND dirty=0 limit %d, %d", $alias, $start, $per_page)->rows();

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
				//这里将brand替换提传入的brand
				$v['brand'] = $v['group_brand'] = $brand;
				if(!$manufacturer) $manufacturer = $product->manufacturer;
				$v['manufacturer'] = $manufacturer;
				$v['manufacturer_abbr'] = PinYin::code($manufacturer, TRUE);
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
