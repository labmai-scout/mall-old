<?php

class API_Mall_Product_Exception extends API_Exception {

}

class API_Mall_Product {
    public function searchProducts($criteria) {
        if(!API_Mall::is_authenticated()) return;
        $opts = ['available' => TRUE];

        $token = Session::temp_token('mall.searchProducts.opts', 300);

        if (isset($criteria['@group']) && $criteria['@group']) {
            $opts['group_by'] = "group_search";
        }

        if (isset($criteria['@is_sale']) && $criteria['@is_sale'] ) {
            $opts['is_sale'] = TRUE;
        }

        if ($criteria['q']) {
            $opts['phrase'] = trim($criteria['q']);
        }

        if (!$criteria['template'] && preg_match('/^\d{2,7}-\d{2}-\d$/', $opts['phrase'])) {
            $criteria['template'] = 'chem_reagent';
        }

        if ($criteria['template']) {
            if ($criteria['template'] == 'chem_reagent') {
                $opts['option_sql'] = "OPTION ranker=expr('max(sum((4*lcs+2*(min_best_span_pos==1)+exact_hit)*user_weight)*1000-sum(100*min_best_span_pos),0)'), field_weights=(cas_no =4000, catalog_no = 501, name=501, alias=90, vendor_name=80, vendor_short_name=80, spec=80, package=80, manufacturer=7)";
            }

            // 化学试剂
            if ($criteria['chem_type']) {
                $opts['rgt_type'] = $criteria['chem_type'];
            }

            // 生物试剂
            $bio_configs = (array)Config::get('mall.mapping_bio_reagent')['fields'];
            if ($criteria['bio_transport_cond']) {
                $opts['transport_cond'] = $bio_configs['bio_transport_cond']['opts'][$criteria['bio_transport_cond']];
            }
            if ($criteria['bio_storage_cond']) {
                $opts['storage_cond'] = $bio_configs['bio_storage_cond']['opts'][$criteria['bio_storage_cond']];
            }

            // 电脑整机
            $computer_configs = (array)Config::get('mall.mapping_computer')['fields'];
            if ($criteria['computer_type']) {
                $opts['phrase'] .= ' ' . $computer_configs['computer_type']['opts'][$criteria['computer_type']];
            }
            if ($criteria['cpu']) {
                $opts['cpu'] = $computer_configs['cpu']['opts'][$criteria['cpu']];
            }
            if ($criteria['memory']) {
                $opts['memory'] = $computer_configs['memory']['opts'][$criteria['memory']];
            }
            if ($criteria['disk']) {
                $opts['disk'] = $computer_configs['disk']['opts'][$criteria['disk']];
            }
            if ($criteria['display']) {
                $opts['display'] = $computer_configs['display']['opts'][$criteria['display']];
            }
            if ($criteria['video_memory']) {
                $opts['video_memory'] = $computer_configs['video_memory']['opts'][$criteria['video_memory']];
            }


            $configs = (array)Config::get('mall.mapping_type');
            if (!$configs[$criteria['template']]) return [];
            $opts['type'] = $configs[$criteria['template']];
        }

        // 商品类别
        if($criteria['category']) {
            $opts['categories'] = $criteria['category'];
        }

        // 库存状态
        if (isset($criteria['stock_status'])) {
            $opts['stock_status'] = $criteria['stock_status'];
        }

        // 生产商
        if ($criteria['manufacturer']) {
            $opts['manufacturer'] = $criteria['manufacturer'];
        }

        //品牌
        if ($criteria['brand']) {
			$opts['brand'] = implode(' ', rb_split_ex($criteria['brand'], __RB_SIMPLE_MODE__));
        }

        // 供货时间
        if ($criteria['supply_time']) {
            $opts['supply_time'] = $criteria['supply_time'];
        }

        if ($criteria['is_frozen']) {
            $opts['is_frozen'] = $criteria['is_frozen'];
        }
        else {
            $opts['is_frozen'] = 0;
        }

        if ($criteria['vendor_id']) {
            $tmp = O('vendor', ['gapper_group'=>$criteria['vendor_id']]);
            if ($tmp && $tmp->id) {
                $opts['vendor_id'] = $tmp->id;
            }
        }

        if (isset($criteria['price'])) {
            $opts['price_range'] = $criteria['price'];
        }

        if ($criteria['@sort-by']) {
            $opts['order_by'] = ' ORDER BY ' . $criteria['@sort-by'] . ' ' . ($criteria['@sort-order'] ? 'ASC' : 'DESC');
            if ($criteria['@group']) {
                $opts['order_by'] .= ',`is_sale` DESC,`stock_status` ASC,`valid_fields` DESC,id DESC';
            }
            else {
                $opts['order_by'] .= ',`is_sale` DESC,`stock_status` ASC,`valid_fields` DESC,`weight` DESC';
            }
        }
        else {
            $opts['order_by'] = ' ORDER BY w DESC, `is_sale` DESC,`stock_status` ASC,`valid_fields` DESC,`weight` DESC';
        }

        if ($criteria['icon_size']) {
            $opts['icon_size'] = $criteria['icon_size'];
        }

        $_SESSION[$token] = $opts;
        // $products = new Search_Product($opts);
        return [
            'token' => $token,
            /*
            SE optimize
            */
            // 'total' => $products->total_count()
        ];
    }

    /*
    public function getProducts($token, $start, $step) {
        if(!API_Mall::is_authenticated()) return;

        if (!$opts = $_SESSION[$token]) return [];
        $products = new Search_Product($opts);
        $products = $products->limit($start, $step);
        $results = [];
        $configs = array_flip((array)Config::get('mall.mapping_type'));
        foreach ($products as $p) {
            $results[$p->id] = [
                'id' => $p->id,
                'manufacturer' => $p->manufacturer,
                'catalog_no' => $p->catalog_no,
                'keywords' => $p->keywords,
                'package' => $p->package,
                'vendor' => $p->vendor->gapper_group,
                'vendor_abbr' => $p->vendor->short_name,
                'newer' => $p->newer_id ?: 0,
                'stock_status' => $p->stock_status,
                'price' => $p->unit_price,
                'name' => $p->name,
                'template' => $configs[$p->type],
                'brand' => $p->brand,
                'description' => $p->description,
                'approve_date' => $p->approve_date,
                'version' => $p->version,
                'sale_info' => json_decode($p->sale_info, true),
                'orig_price' => $p->orig_price,
                'cas_no' => $p->cas_no,
                'spec' => $p->spec,
                'rgt_type' => (int)$p->rgt_type,
                'fixed_price' => $p->fixed_price?:FALSE,
            ];

            if ($size = $opts['icon_size']) {
                $icon = '';
                $size = $p->normalize_icon_size($size);
                if ($icon_file = Core::file_exists(PRIVATE_BASE.'icons/product/'.$size.'/'.$p->id.'.png', '*')) {
                    $icon = URI::url(Config::get('system.base_url').'icon/index.product.'.$p->id.'.'.$size.'?'.$p->mtime);
                }
                $results[$p->id]['icon'] = $icon;
            }

            $results[$p->id]['selling'] = false;
            if($p->can_buy($avoid_reason)) {
                $results[$p->id]['selling'] = true;
            }
            $results[$p->id]['can_pay'] = true;
            switch ($p->rgt_type) {
                case Reagent_Type::DANGEROUS:
                $results[$p->id]['tags'][] = [
                    'style' => 'label',
                    'text' => '危险',
                    'color' => 'red',
                ];
                break;
                case Reagent_Type::EASYMADE_TOXIC:
                $results[$p->id]['tags'][] = [
                    'style' => 'label',
                    'text' => '易制毒',
                    'color' => 'black',
                ];
                break;
                case Reagent_Type::SUPER_TOXIC:
                $results[$p->id]['tags'][] = [
                    'style' => 'label',
                    'text' => '剧毒品',
                    'color' => 'black',
                ];
                $results[$p->id]['can_pay'] = false;
                break;
            }
            $mappings = (array)Config::get('mall.api_values_mapping')[$p->type]['fields'];
            foreach ($mappings as $k => $v) {
                if(is_array($v)) {
                    $data[$k] = $p->$v['field'];
                }
                else{
                    $data[$k] = $p->$v;
                }
            }
        }
        return $results;

    }
    */

    /*
    public function getProduct($criteria) {
        if(!API_Mall::is_authenticated()) return;
        $selling = false;
        if (is_int($criteria)) {
            $p = O('product', $criteria);
            $p_id = $p->id;
        }
        elseif (is_array($criteria)) {
            $id = (int)$criteria['id'];
            $product = O('product', $id);
            if (isset($criteria['version']) && $product->version != $criteria['version']) {
                //如果是版本不是当前商品的历史版本，则返回 revision 的数据
                $p = O('product_revision', array('product_id'=>$id, 'version'=>$criteria['version']));
                $p_id = $p->product_id;
            }
            else {
                //如果版本为当前商品的版本就传递商品
                $p = $product;
                $p_id = $p->id;
            }
        }
        if (!$p_id) return [];
        if ($p->name() == 'product' && $p->can_buy($avoid_reason)) {
            $selling = TRUE;
        }

        $configs = array_flip((array)Config::get('mall.mapping_type'));
        $mappings = (array)Config::get('mall.api_values_mapping')[$p->type];
        $data =  [
            'id' => $p_id,
            'manufacturer' => $p->manufacturer,
            'catalog_no' => $p->catalog_no,
            'keywords' => $p->keywords,
            'package' => $p->package,
            'vendor' => $p->vendor->gapper_group,
            'stock_status' => $p->stock_status,
            'price' => $p->unit_price,
            'name' => $p->name,
            'template' => $configs[$p->type],
            'brand' => $p->brand,
            'description' => $p->description,
            'version' => $p->version,
            'approve_date' => $p->approve_date,
            'sale_info' => json_decode($p->sale_info, true),
            'orig_price' => $p->orig_price,
            'cas_no'=> $p->cas_no,
            'spec' => $p->spec,
            'rgt_type' => (int)$p->rgt_type,
            'fixed_price' => $p->fixed_price?:FALSE,
        ];

        if ($icon_file = Core::file_exists(PRIVATE_BASE.'icons/'.$p->name().'/256/'.$p->id.'.png', '*')) {
            $data['icon'] = URI::url(Config::get('system.base_url').'icon/index.product.'.$p->id.'.256?'.$p->mtime);
        }

        $data['selling'] = $selling;
        $data['can_pay'] = true;
        switch ($p->rgt_type) {
            case Reagent_Type::DANGEROUS:
            $data['tags'][] = [
                'style' => 'label',
                'text' => '危险',
                'abbr' => '危',
                'color' => 'red',
            ];
            break;
            case Reagent_Type::EASYMADE_TOXIC:
            $data['tags'][] = [
                'style' => 'label',
                'text' => '易制毒',
                'abbr' => '毒',
                'color' => 'black',
            ];
            $data['can_pay'] = false;
            break;
            case Reagent_Type::SUPER_TOXIC:
            $data['tags'][] = [
                'style' => 'label',
                'text' => '剧毒品',
                'abbr' => '毒',
                'color' => 'black',
            ];
            break;
        }

        $mappings = (array)Config::get('mall.api_values_mapping')[$p->type]['fields'];
        foreach ($mappings as $k => $v) {
            if(is_array($v)) {
                $data[$k] = $p->$v['field'];
            }
            else{
                $data[$k] = $p->$v;
            }
        }
        return $data;

    }
    */

    /*
    public function getProductRatings($id) {
        if(!API_Mall::is_authenticated()) return;
        $product = O('product',$id);
        if (!$product->id)  return [];
        $rating_summary = Order_Item_Rating_Model::get_rating_summary($product);
        $result = [];
        $result['quality'] = $rating_summary['quality'];
        $result['service'] = $rating_summary['service'];
        $result['delivery'] = $rating_summary['delivery'];
        return $result;
    }
    */

    public function getProductComments($id, $start=0, $step=5) {
        if(!API_Mall::is_authenticated()) return;
        $id = (int)$id;
        $comments = Q("product[id=$id] order_item order_item_comment:sort(ctime D)")->limit($start, $step);
        $results = [];
        foreach ($comments as $comment) {
            $result = [
                'author_name' => $comment->author->name,
                'author_customer' => $comment->author_customer->name,
                'ctime' => $comment->ctime,
                'content' => $comment->content,
            ];
            $ratings = Q("$comment<order_item_comment order_item_rating");
            $arr = [];
            foreach ($ratings as $r) {
                $arr[$r->subject] = $r->rating;
            }
            $result['ratings'] = $arr;
            $results[$comment->id] = $result;
        }
        return $results;

    }

    public function getGroupedProducts($id, $start=0, $step=5) {
        if(!API_Mall::is_authenticated()) return;

        $p = O('product', $id);
        if (!$p->id) return [];

        $manufacturer = Q::quote($p->manufacturer);
        $catalog_no = Q::quote($p->catalog_no);
        $package = Q::quote($p->package);
        $products = Q("product[approve_date>0][manufacturer={$manufacturer}][catalog_no={$catalog_no}][package={$package}]:sort(unit_price A)")->limit($start, $step);

        $configs = array_flip((array)Config::get('mall.mapping_type'));
        $results = [];
        foreach ($products as $p) {
            $result = [
                'manufacturer' => $p->manufacturer,
                'catalog_no' => $p->catalog_no,
                'keywords' => $p->keywords,
                'package' => $p->package,
                'vendor_abbr' => $p->vendor->short_name,
                'vendor' => $p->vendor->gapper_group,
                'stock_status' => $p->stock_status,
                'price' => $p->unit_price,
                'name' => $p->name,
                'template' => $configs[$p->type],
                'brand' => $p->brand,
                'description' => $p->description,
                'approve_date' => $p->approve_date,
				'version' => $p->version,
                'sale_info' => json_decode($p->sale_info, true),
                'orig_price' => $p->orig_price,
                'cas_no' => $p->cas_no,
                'spec' => $p->spec,
            ];

            $result['selling'] = false;
            if($p->can_buy($avoid_reason)) {
                $result['selling'] = true;
            }

            switch ($p->rgt_type) {
                case Reagent_Type::DANGEROUS:
                $result['tags'][]  = [
                    'style' => 'label',
                    'text' => '危险',
                    'color' => 'red',
                ];
                break;
                case Reagent_Type::EASYMADE_TOXIC:
                $result['tags'][]  = [
                    'style' => 'label',
                    'text' => '易制毒',
                    'color' => 'black',
                ];
                break;
                case Reagent_Type::SUPER_TOXIC:
                $result['tags'][]  = [
                    'style' => 'label',
                    'text' => '剧毒品',
                    'color' => 'black',
                ];
                break;
            }
            $mappings = (array)Config::get('mall.api_values_mapping')[$p->type]['fields'];
            foreach ($mappings as $k => $v) {
                if(is_array($v)) {
                    $data[$k] = $p->$v['field'];
                }
                else{
                    $data[$k] = $p->$v;
                }
            }

            $results[$p->id] = $result;
        }
        return $results;
    }

    public function getProductIntro($criteria) {

        if(!API_Mall::is_authenticated()) return;
        if (is_int($criteria)) {
            $product = O('product', $criteria);
        }
        elseif (is_array($criteria)) {
            $id = $criteria['id'];
            $version = $criteria['version'];
            $product = O('product_revision', array('product_id'=>$id, 'version'=>$version));
        }
        if (!$product->id) return;

        return (string)V("prod_{$product->type}:product/intro", ['product'=>$product]);

    }

    public function getProductImage($id=0, $index=0, $size=64) {
        if(!API_Mall::is_authenticated()) return;

        $p = O('product', $id);
        if (!$p->id) return '';

        /* TODO 因为目前没有做多图功能，故默认返回index为0的首发图片*/
        $size = $p->normalize_icon_size($size);
        if ($icon_file = Core::file_exists(PRIVATE_BASE.'icons/'.$p->name().'/'.$size.'/'.$p->id.'.png', '*')) {
            return Config::get('system.base_url').Cache::cache_file($icon_file).'?_='.$p->mtime;
        }

        return '';
    }

    /**
    * 得到筛选条件配置，存入文件中
    * @return array
    */
    public function getCriteria() {
        if(!API_Mall::is_authenticated()) return;

        $mapping_type = Config::get('mall.mapping_type');
        $types = array_keys($mapping_type);

        $db = ORM_Model::db('brand');

        //得到各个类型的配置文件
        foreach ($types as $type) {
        	//通用的筛选条件
	        $general_criteria = Config::get('mall.general_criteria');

	        //得到品牌的信息
	        $brand_opts = [];

            $rs = $db->query('SELECT DISTINCT b.name FROM brand AS b LEFT JOIN'
            .' (SELECT p.brand, COUNT(o.id) count FROM product AS p'
              .' JOIN order_item AS oi ON oi.product_id = p.id'
              .' JOIN `order` AS o ON oi.order_id = o.id AND o.status > 5 AND o.status < 8'
              .' WHERE p.brand <> "" GROUP BY p.brand) AS pb'
              .' ON pb.brand = b.name WHERE b.types LIKE "'.$db->escape('%'.$type.'%').'"'
              .' ORDER BY pb.count DESC, b.name_abbr ASC LIMIT 8');

            if ($rs) while ($row = $rs->row()) {
                print_r($row);
                $name = trim($row->name);
                $brand_opts[$name] = $name;
            }

	        $general_criteria['brand'] = [
	            'title' => '品牌',
	            'type' => 'list',
	            'weight' => -2,
	            'opts' => $brand_opts
	        ];


            $config = Config::get('mall.mapping_'.$type);
            if($config){
                //加上通用的筛选类型
                $config['fields'] += $general_criteria;

                //根据weight排序
                uasort($config['fields'], function($a, $b) {
                    $a = $a['weight'] ?: 0;
                    $b = $b['weight'] ?: 0;
                    if ($a==$b) return 0;
                    return $a < $b ? -1 : 1;
                });

                $result[$type] = $config;
            }
        }

        return $result;
    }

    public function getCriteriaOptionAZ($template, $key) {
        if(!API_Mall::is_authenticated()) return;

        if ($key == 'brand') {
            $db = ORM_Model::db('brand');
            $initials = [];
            for ($i=0;$i<26;$i++) {
                $initial = chr(65+$i);

                if($db->value('SELECT COUNT(id) FROM brand WHERE types LIKE "%s" AND LEFT(name_abbr,1)="%s" LIMIT 1', '%"'.$template.'"%', $initial) > 0) {
                    $initials[] = $initial;
                }
            }

            if ($db->value('SELECT COUNT(id) FROM brand WHERE types LIKE "%s" AND LEFT(name_abbr,1)  NOT IN ("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z") LIMIT 1', '%"'.$template.'"%') > 0) {
                $initials[] = '#';
            }

            return $initials;
        }

        return [];
    }

    public function searchCriteriaOptions($template, $key, $query) {
        if(!API_Mall::is_authenticated()) return;
        if ($key != 'brand') return;

        $token = Session::temp_token('mall.searchCriteria.opts', 300);

        $result['token'] = $token;
        $query['template'] = $template;
        $_SESSION[$token] = $query;

        return $result;
    }

    public function getCriteriaOptions($token, $start, $num) {
        if(!API_Mall::is_authenticated()) return;

        $query = (array) $_SESSION[$token];

        $db = ORM_Model::db('brand');

        $SQL = 'SELECT DISTINCT name FROM brand ';

        if (isset($query['template'])) {
	        $where[] = 'types LIKE "'.$db->escape('%"'.$query['template'].'"%').'"';
        }

        if (isset($query['initial'])) {
            if ($query['initial'] == '#') {
                $where[] = 'LEFT(name_abbr,1) NOT IN ("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z")';
            } else {
                $where[] = 'LEFT(name_abbr,1)="'.$db->escape($query['initial']).'"';
            }
        }

        if (isset($query['search'])) {
            $where[] = 'name LIKE "'.$db->escape('%'.$query['search'].'%').'"'
                . ' OR name_abbr LIKE "'.$db->escape('%'.$query['search'].'%').'"';
        }

        if (count($where) > 0) {
            $SQL .= ' WHERE '.implode(' AND ', $where);
        }

        // $SQL .= ' LIMIT '.intval($start).', '.intval($num);
        $criteria = [];
        $rs = $db->query($SQL);
        if ($rs) while ($row = $rs->row()) {
            $criteria[] = $row->name;
        }
        return $criteria;
    }

    /**
    * 得到模板配置，存入文件中
    * @return array
    */
    public function getTemplate() {
        if(!API_Mall::is_authenticated()) return;

        $mapping_type = Config::get('mall.mapping_type');
        $api_values_mapping = Config::get('mall.api_values_mapping');


        //得到各个类型的配置文件
        foreach ($mapping_type as $key => $type) {
            $config = $api_values_mapping[$type];

            if($config){

                foreach ($config['fields'] as $k => $v) {
                    // 去除'chem_type' => 'rgt_type'  这种需要给product赋值，但是不用放入配置的字段
                    if(!is_array($v)) unset($config['fields'][$k]);
                }

                $result[$key] = $config;

            }
        }

        return $result;
    }

}
