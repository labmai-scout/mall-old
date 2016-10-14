<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);

/*
 * file clean_all_sale.php
 * author Yu Li <yu.li@geneegroup.com>
 * date 2014-09-04
 *
 * brief 删除所有商品的促销信息
 * usage SITE_ID=nankai php clean_all_sale.php
 */

$db = Database::factory();

$insert_history = "INSERT INTO `product_revision` (vendor_id,unit_price,orig_price,sale_info,vendor_note,approver_id,approve_date,publisher_id,publish_date,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra,last_approver_id,last_approve_date,last_publisher_id,last_publish_date,unapprover_id,unapprove_date, product_id,version) (SELECT vendor_id,unit_price,orig_price,sale_info,vendor_note,approver_id,approve_date,publisher_id,publish_date,ctime,mtime,name,manufacturer,catalog_no,model,spec,package,type,category_id,keywords,description,stock_status,expire_date,freeze_reasons,sale_volume,brand,supply_time,market_price,_extra,last_approver_id,last_approve_date,last_publisher_id,last_publish_date,unapprover_id,unapprove_date,id,version FROM product WHERE  dirty=1 AND `orig_price` > 0)";

if(!$db->query($insert_history)) {
    die('升级product revision 失败');
}
$db->query("UPDATE `product` SET `version`=`version`+1, `dirty`=0 WHERE `dirty`=1 and `sale_info` != ''");

//先改变数据的价格
$db->query("UPDATE `product` SET `unit_price`=`product`.`orig_price` WHERE `orig_price` > 0");
//刷新索引
update_sphinx_by_orig_price();
//清空促销信息
$db->query("UPDATE `product` SET `sale_info`='', `orig_price`=0 WHERE `sale_info`!=''");



function update_sphinx_by_orig_price() {

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
            $products = $db->query("SELECT product.*,vendor.name as vendor_name,vendor.short_name as vendor_short_name FROM `product` LEFT JOIN vendor  ON
            (vendor.id=product.vendor_id) WHERE product.type='$type' AND sale_info != '' limit %d, %d", $start, $per_page)->rows();

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
                $v['brand'] = $v['group_brand'] = $product->brand;
                if(!$manufacturer) $manufacturer = $product->manufacturer;
                $v['manufacturer'] = $manufacturer;
                $v['manufacturer_abbr'] = PinYin::code($manufacturer, TRUE);
                $v['sales'] = $product->sale_volume;
                $v['weight'] = rand(1,10000);
                $v['expire_date'] = $product->expire_date;
                $v['type'] = $types_sphinx_indexes[$product->type];
                $v['is_sale'] = $product->sale_info ? 1 : 0;

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
