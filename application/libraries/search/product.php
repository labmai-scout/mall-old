<?php

class Search_Product extends Search_Iterator {

	static $model_name = 'product';

	protected $name = 'product';
	function __construct($opt=NULL) {
		/**
		* phrase 关键字查询
		* category 分类查询, 目前旧商城不进行分类查询, 新商城需要. (普通试剂=>1, 危险化学品=>2, 易制毒化学品=>3)
		* name 商品名匹配
		* price_range 价格区间
		* vendor_id 供应商范围筛选 输入的是供应商对应的 id
		* is_frozen 是否冻结 0 是未冻结 、1 是冻结
		* status 筛选商品的状态 unpublished 未发布、approved 已通过、unapproved 未通过
		* type 设置是否进行分表查询 没设置默认为总表
		* group_by 进行 group by ，注意字段类型不能为 rt_field
		* order_by 设置排序设置
		* option_sql 用来设置排序规则，通过调整不同字段的 weight 来影响排名
		**/
		parent::__construct($opt);
		$where = array();
		$matchs = array();

        if ($opt['phrase']) {
            $phrase = $this->sphinx->escape($opt['phrase']);
            if (preg_match('/^\d{2,7}-\d{2}-\d$/', $phrase)) {
                $matchs[] = "@cas_no \"{$phrase}\"";
            }
            else {
                $phrase = trim(str_replace(['%','-','/','\''], ' ',$phrase));
                $seg_phrase = implode(' ', rb_split_ex($phrase, __RB_SIMPLE_MODE__));
                $matchs[] = "@* {$seg_phrase}";
            }
        }

		if ($opt['categories']) {
			$cids = $opt['categories'];
			$where[] = "category in ($cids)";
		}

		if ($opt['is_sale']) {
			$is_sale = (int)$opt['is_sale'];
			$where[] = "is_sale={$is_sale}";
		}

		if (isset($opt['stock_status'])) {
			$stock_status = (int)$opt['stock_status'];
			$where[] = "stock_status={$stock_status}";
		}

		if ($opt['name']) {
			$name = $this->sphinx->escape($opt['name']);
			$matchs[] = "@name \"{$name}\"";
		}

		if (isset($opt['price_range'])) {
			$price_range = (int)$opt['price_range'];
			$where[] = "price_range={$price_range}";
		}

		if ($opt['vendor_id']) {
			$vendor_id = (int)$opt['vendor_id'];
			$where[] = "vendor_id={$vendor_id}";
		}

		if (isset($opt['is_frozen'])) {
			$is_frozen = (int)$opt['is_frozen'];
			$where[] = "is_frozen={$is_frozen}";
		}

		if (isset($opt['supply_time'])) {
			$supply_time = (int)$opt['supply_time'];
			$where[] = "supply_time={$supply_time}";
		}

		if (isset($opt['manufacturer'])) {
			$manufacturer = $opt['manufacturer'];
			$matchs[] = "@manufacturer \"{$manufacturer}\"";
		}

		if (isset($opt['brand'])) {
			$brand = $opt['brand'];
			$matchs[] = "@brand \"{$brand}\"";
		}

		if ($opt['status']) {
			switch ($opt['status']) {
			case 'unpublished':
				$where[] = 'publish_date=0';
				if (!$opt['order_by']) {
					$order_by = ' ORDER BY `ctime` DESC ';
				}
				break;
			case 'approved':
				$where[] = 'approve_date>0';
				if (!$opt['order_by']) {
					$order_by = ' ORDER BY  w DESC,`approve_date` DESC ';
				}
				break;
			case 'unapproved':
			default:
				$where[] = 'publish_date>0';
				$where[] = 'approve_date=0';
				if (!$opt['order_by']) {
					$order_by = ' ORDER BY  w DESC,`publish_date` DESC ';
				}
				break;
			}
		}
		else {
			//默认查询已审核的
			$where[] = 'approve_date>0';
            $order_by = ' ORDER BY  w DESC, `sales` DESC, `approve_date` DESC ';
		}

		if ($opt['type']) {
			$index_name = parent::get_index_name(self::$model_name.'_'.$opt['type']);
			$type_class = 'Search_Product_'.$opt['type'];
			if (method_exists($type_class, 'split_opts')) {
				$type_class::split_opts($opt, $where, $matchs);
			}
		}
		else {
			$index_name = parent::get_index_name(self::$model_name);
		}

		if (count($matchs)) $where[] = "MATCH('" . implode(' ', $matchs) . "')";
		$SQL = 'SELECT * , WEIGHT() w FROM `' . $index_name .'`';
		if (count($where) > 0) {
			$SQL .= ' WHERE '.implode(' AND ', $where);
		}

		if (isset($opt['group_by'])) {
			$SQL .= ' GROUP BY ' . $opt['group_by'];
        }

		if (isset($opt['order_by'])) {
			$order_by = $opt['order_by'];
		}

		$this->sphinx_SQL = $SQL;

		if ($order_by) {
			$this->sphinx_order_by_sql = $order_by;
		}
		else {
			$this->sphinx_order_by_sql = ' ORDER BY w DESC, `is_sale` DESC, `stock_status` ASC, `valid_fields` DESC';
			if ($opt['group_by']) {
				$this->sphinx_order_by_sql .= ',id DESC';
			}
			else {
				$this->sphinx_order_by_sql .= ',`weight` DESC';
			}
		}
		$this->sphinx_option_SQL = $opt['option_sql'] ? : "OPTION ranker=expr('max(sum((4*lcs+2*(min_best_span_pos==1)+exact_hit)*user_weight)*1000-sum(100*min_best_span_pos),0)'), field_weights=(name=60, catalog_no=15, vendor_name=15,vendor_short_name=15, keywords=5, extra=10)";
	}

	static function update_index($product) {
		if (!$product->id) return;

		$vendor = $product->vendor;
		$vendor_name = implode(' ', rb_split_ex($vendor->name, __RB_SIMPLE_MODE__));
		$vendor_short_name = implode(' ', rb_split_ex($vendor->short_name, __RB_SIMPLE_MODE__));
		$product_name = implode(' ', rb_split_ex($product->name, __RB_SIMPLE_MODE__));
		$type_class = 'Search_Product_'.$product->type;
		foreach (Product_Model::get_merge_criterias($product->type) as $name => $value) {
			$items[] = $product->$name;
		}
		$v = array('id' => $product->id);
		$v['name'] = str_replace(['%','-'], ' ', $product_name);
		$v['group_name'] = $product_name;
		$v['catalog_no'] = implode(' ', rb_split_ex($product->catalog_no, __RB_SIMPLE_MODE__));
		$v['group_search'] = implode(' ', $items);
		$v['description'] = $product->description;
		$keywords = implode(', ', (array)@json_decode($product->keywords, TRUE));
		$v['keywords'] = implode(' ', rb_split_ex($keywords, __RB_SIMPLE_MODE__));
		$v['is_frozen'] = (int) (boolean) $product->freeze_reasons;
		$v['price'] = (float) $product->unit_price;
		$v['vendor_name'] = $vendor_name.' '.$vendor_short_name;
		$v['vendor_short_name'] = $vendor_short_name;
		$v['vendor_short_name_abbr'] = PinYin::code($vendor_short_name, TRUE);
		$v['ctime'] = (int)$product->ctime;
		$v['vendor_id'] = $product->vendor->id;
		$v['publish_date'] = $product->publish_date;
		$v['approve_date'] = $product->approve_date;
		$v['stock_status'] = $product->stock_status;
		$category = $product->category;
		$categories = [];
		while ($category->id && $category->root->id) {
			$categories[] = (int)$category->id;
			$category = $category->parent;
		}
		$v['category'] = $categories; //mva 类型支持数组
		$v['spec'] = implode(' ', rb_split_ex($product->spec, __RB_SIMPLE_MODE__));
		$v['package'] = implode(' ', rb_split_ex($product->package, __RB_SIMPLE_MODE__));
		$v['brand'] = $v['group_brand'] = implode(' ', rb_split_ex($product->brand, __RB_SIMPLE_MODE__));
		$v['manufacturer'] = implode(' ', rb_split_ex($product->manufacturer, __RB_SIMPLE_MODE__));
		$v['manufacturer_abbr'] = PinYin::code($product->manufacturer, TRUE);
		$v['sales'] = Q("order_item[product={$product}]")->sum('quantity');
		$v['weight'] = rand(1,10000);
		$v['expire_date'] = $product->expire_date;
		$v['is_sale'] = $product->sale_info ? 1 : 0;

		//type通过对应的数字存储
		$types_sphinx_indexes =  Config::get('product.types_sphinx_indexes');
		$v['type'] = $types_sphinx_indexes[$product->type];

		// 供货时间区间
		$supply_time = (int)$product->supply_time;
		$intervals = Config::get('mall.supply_time');
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

		/* 需要对 特殊字段做文本排序 */
		$v['extra'] = Event::trigger('sphinx[product].get_extra_index',$v, $product);
		$num = 0;
		foreach ($v as $key => $value) {
			if ($value) {
				$num++;
			}
		}
		$v['valid_fields'] = $num;
		self::update_query(self::$model_name, $v);

		//type字段只是product表用，各个字段的表不需要
		unset($v['type']);
		//
		/*
		* update product sub index
		*/

		if ($product->type) {
			unset($v['extra']);
			if (method_exists($type_class, 'update_index')) {
				$type_class::update_index($product, $v);
			}
			else {

				self::update_query(self::$model_name.'_'.$product->type, $v);
			}
		}

	}

	static function update_query($name, $v) {
		$k = array();
		$sphinx = Database::factory('@sphinx');
		foreach ($v as $kk => &$vv) {
			$k[$kk] = $sphinx->quote_ident($kk);
			if (is_array($vv)) {
				$vv = '('.$sphinx->quote($vv).')';
			}
			else {
				$vv = $sphinx->quote($vv);
			}
		}
		$SQL = 'REPLACE INTO `' . parent::get_index_name($name) . '` ('.implode(',', $k).') VALUES ('.implode(',', $v).')';
		$sphinx->query($SQL);
	}
	static function delete_index($product) {
		$sphinx = Database::factory('@sphinx');
		$id = (int) ($product->id);
		$SQL = "DELETE FROM `" . parent::get_index_name(self::$model_name) . "` WHERE id={$id}";
		$sphinx->query($SQL);
		if ($product->type) {
			$SQL = "DELETE FROM `" . parent::get_index_name(self::$model_name.'_'.$product->type) . "` WHERE id={$id}";
			$sphinx->query($SQL);
		}
	}

	static function empty_index() {
		return parent::empty_index_of(self::$model_name);
	}

	function check_query($scope='fetch') {
		if ($this->isset_query($scope)) return $this;
		$opt = $this->opt;

		$types = Config::get('product.types_sphinx_indexes');
		$type = $types[$opt['type']];

		if ($opt['type']) {
			$table = parent::get_index_name(self::$model_name.'_'.$opt['type']);
		}
		else {
			$table = parent::get_index_name(self::$model_name);
		}
		switch($scope) {
			case 'count':
				//如果为匹配到，进行其他匹配
				$SQL = $this->sphinx_SQL . ' LIMIT 1';
				$query = $this->sphinx->query($SQL);
				$meta = $this->sphinx->query('SHOW META');
				$total_found = 0;

				foreach ($meta->rows() as $row) {
				    if ($row->Variable_name == 'total_found') {
				        $total_found = (int) $row->Value;
				        $this->count = $total_found;
				        break;
				    }
				}
                break;
			default:
				$SQL = $this->sphinx_SQL;
				if ($this->sphinx_order_by_sql) $SQL .= ' '.$this->sphinx_order_by_sql;
				if ($this->sphinx_limit_SQL) $SQL .=  ' '.$this->sphinx_limit_SQL;
				if ($this->sphinx_option_SQL) $SQL .= ' '.$this->sphinx_option_SQL;
				$query = $this->sphinx->query($SQL);
				$this->objects = array();
				if ($query) while($row = $query->row()) {
					$object = O($this->name, $row->id);
					if ($object->id) {
						$this->objects[$object->id] = $object;
					}
				}

				$meta = $this->sphinx->query('SHOW META');
				$total_found = 0;
				foreach ($meta->rows() as $row) {
					if ($row->Variable_name == 'total_found') {
						$total_found = (int) $row->Value;
						break;
					}
				}
				$this->count = $total_found;
				$this->length = count($this->objects);
				$this->current_id = key($this->objects);
		}

		$this->set_query($scope, TRUE);

		return $this;
	}
}
