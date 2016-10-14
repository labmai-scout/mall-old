<?php

class Table_Widget extends Widget {
	
	protected $columns;
	protected $rows;
	
	function __construct($vars){
		parent::__construct('table', $vars);
	}

	function add_columns(array $columns) {
		foreach ($columns as $key => $column) {
			$this->add_column($key, $column);
		}
	}

	function add_rows(array $rows) {
		foreach ($rows as $row) {
			$this->add_row($row);
		}
	}
	
	function add_column($key, $column) {
		$this->columns[$key] = (array) $column;
	}

	function add_row($row) {
		$this->rows[] = (array) $row;
	}
	
	function count_visible_filters() {
		$count = 0;
		foreach ((array) $this->columns as $key => $column) {
			if (!$column['invisible'] && (string) $column['filter']['value']) {
				$count++;
			}
		}
		return $count;
	}
	
	function count_filters() {
		$count = 0;
		foreach ((array) $this->columns as $key => $column) {
			if ((string) $column['filter']['value']) {
				$count++;
			}
		}
		return $count;
	}
	
	function count_visible_columns() {
		$count = 0;
		foreach ((array) $this->columns as $key => $column) {
			if (!$column['invisible']) {
				$count++;
			}
		}
		return $count;
	}
	
	function __toString() {
	
		//清楚上次的缓冲
		$this->ob_clean();
		
		//hook相应的事件 修改columns或者rows
		$name = $this->vars['name'];
		Event::trigger("{$name}_table.prerender table.prerender", $this);
		$output = parent::__toString();
		
		$new_output = Event::trigger("{$name}_table.postrender table.postrender", $this, $output);
		
		return $new_output?:$output;
	}
	
}
