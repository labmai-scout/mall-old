<?php

class Search_Iterator extends ORM_Iterator {

	protected $name;

	protected $sphinx;
	protected $sphinx_options;
	protected $sphinx_SQL;
	protected $sphinx_limit_SQL;
	protected $sphinx_option_SQL;

    protected $opt;

	function __construct($opt=NULL) {
        $this->opt = $opt;
		$this->sphinx = Database::factory('@sphinx');
	}

	private $_search_check_query = FALSE;
	protected function check_query($scope='fetch') {

		/*
		$describe = $this->sphinx->query('describe product');
		while ($row = $describe->row()) {
			var_dump($row);
		}
		*/

		if ($this->isset_query($scope)) return $this;

		switch($scope) {
		case 'count':
			$SQL = $this->sphinx_SQL . ' LIMIT 1';

            $query = $this->sphinx->query($SQL);

			$meta = $this->sphinx->query('SHOW META');
			$total_found = 0;
			foreach ($meta->rows() as $row) {
				if ($row->Variable_name == 'total_found') {
					$total_found = (int) $row->Value;
					break;
				}
			}

			$this->count = $total_found;
			break;
		default:
			$SQL = $this->sphinx_SQL;
			// 增加 order by 子句(xiaopei.li@2012-04-26)
			// http://sphinxsearch.com/docs/2.0.4/sphinxql-select.html
			if ($this->sphinx_order_by_sql) $SQL .= ' '.$this->sphinx_order_by_sql;
			if ($this->sphinx_limit_SQL) $SQL .=  ' '.$this->sphinx_limit_SQL;
			if ($this->sphinx_option_SQL) $SQL .= ' '.$this->sphinx_option_SQL;
			// if ($this->sphinx_option_SQL) $SQL .= ' '.$this->sphinx_option_SQL.' ,max_matches=10000';

			// $sql = 'SELECT id FROM `product` LIMIT 999, 20 OPTION max_matches=10000, field_weights=(catalog_no=10,name=5, keywords=3)';

			// echo $SQL;
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
			/*
			echo '<br/>';
			echo 'total_count: ' . $this->count;
			echo '<br/>';
			echo 'length: ' . $this->length;
			*/
			$this->current_id = key($this->objects);
		}

		$this->set_query($scope, TRUE);

		return $this;
	}

	// TODO: limit没有重新生成新的ORM_Iterator对象 因此修改来原来的对象, 在一些需要保留原有对象的应用中会有问题
	function limit() {
		$args = func_get_args();
		$args = array_slice($args, 0, 2);
		$this->sphinx_limit_SQL = 'LIMIT '.implode(', ', $args);
		return $this;
	}

	static function empty_index_of($model_name) {
		$sphinx = Database::factory('@sphinx');

		// error_log("deleting $index_name sphinx index");

		$name = self::get_index_name($model_name);

		$SQL = "select * from `" . $name . "` limit 1000";

		do {
			// http://sphinxsearch.com/docs/2.0.4/sphinxql-select.html
			// LIMIT ... an implicit LIMIT 0,20 is present by default. (xiaopei.li@2012-04-23)
			$query = $sphinx->query($SQL);

			$ids = array();
			if ($query) while ($row = $query->row()) {
				$ids[] = $row->id;
			}
			if ($ids) {
				$DEL_SQL = 'DELETE FROM `' . $name . '` WHERE id IN (' . join(',', $ids) . ')';

				$sphinx->query($DEL_SQL);
			}
		}
		while ($query->count());
	}

	static function get_index_name($model_name) {
		// 参考: http://php.net/manual/en/language.oop5.late-static-bindings.php
		if (!$model_name) {
			throw new Exception;
		}

		return Config::get('sphinx.prefix', 'mall_'.SITE_ID.'_') . $model_name;
	}

	static function str_split_unicode($str, $l = 0) {
	    if ($l > 0) {
	        $ret = array();
	        $len = mb_strlen($str, "UTF-8");
	        for ($i = 0; $i < $len; $i += $l) {
	            $ret[] = mb_substr($str, $i, $l, "UTF-8");
	        }
	        return $ret;
	    }
	    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
	}

	static function str_split2str($str, $glue = " ") {
		$arr = self::str_split_unicode($str);
		$new_str = implode($glue, $arr);
		return $new_str;
	}

	static function rb_str_split($str) {
		$phrase = trim(str_replace('%', '',$str));
		$seg_phrase = implode(' ', rb_split_ex($phrase, __RB_SIMPLE_MODE__));
		if (strlen($phrase) == strlen($seg_phrase)) { //分词前后字符长度一致的条件下
			$phrase_arr = explode(' ', $phrase);
			foreach ($phrase_arr as $key => $value) {
				$new_arr[] = "\"".$value."\"";
			}
			$str = implode('|', $new_arr);
		}
		else {
			$seg_phrase_arr = explode(' ', $seg_phrase);
			$phrase_arr = explode(' ', $phrase);
			if (count($phrase_arr) > 1) {
				$merge = array_unique(array_merge($seg_phrase_arr, $phrase_arr));
			}
			else {
				$merge = $seg_phrase_arr;
			}
			foreach ($merge as $key => $value) {
				$new_value = "\"".$value."\"";
				$new_arr[] = $new_value;
			}
			$str = implode('|', $new_arr);
		}
		return $str;
	}

}
