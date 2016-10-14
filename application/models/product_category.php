<?php

class Product_Category_Model extends Presentable_Model {

	protected $object_page = array(
		'view' => '!mall/search/index.%type[.%arguments]?category=%id',
	);

	static function replace_category($product, $id, $root) {

		if (is_string($root)) {
			$root = self::root($root);
		}

		if (!$root->id) {
			return FALSE;
		}

		// disconnect old
		foreach (self::get_roots() as $old_root) {
			$old_root->disconnect($product);
		}

		// connect new
		$category = O('product_category', array(
					  'id' => $id,
					  'root' => $root
					  ));

		if ($category->id) {
			$category->connect($product);
		}

	}

	// 标记对象的时候 所有父节点需要标记对象
	function connect($obj, $type = NULL, $approved = FALSE) {
		if ($this->id) {
			if ($this->parent->id && $this->parent->id != $this->root->id) {
				$this->parent->connect($obj);
			}
		}
		return parent::connect($obj);
	}

	// 取消标记时 所有子节点需要去掉标记
	function disconnect($obj, $type = NULL, $approved = NULL) {
		if ($this->id) {
			foreach(Q("product_category[parent=$this]") as $category) {
				$category->disconnect($obj);
			}
		}
		return parent::disconnect($obj);
	}

	static function get_roots() {

		$types = Product_Model::get_types();

		$roots = array();
		foreach ($types as $type => $name) {
			$root = self::root($type, $name);
			$roots[$type] = $root;
		}

		return $roots;
	}

	static function root($type, $name = NULL) {
		$conf_name = 'product_category.'.$type;
		$conf_id_name = $conf_name.'_id';

		$id = Site::get($conf_id_name);
		$root = O('product_category', array('root_id'=>0, 'id'=>$id));
		if (!$root->id) {
			$root = O('product_category', array('root_id'=>0, 'name'=>$name));
			if (!$root->id) {
				$root->name = $name;
				$root->parent = NULL;
				$root->root = NULL;
				$root->readonly = TRUE;
				$root->type = $type;
				$root->save();
			}

			Site::set($conf_id_name, (int) $root->id);
		}
		return $root;
	}

	function children() {
		return $this->id ? Q("product_category[parent=$this]") : Q('empty');
	}

	function save($overwrite=FALSE) {
		if (!$this->parent->id) {
			$this->parent = $this->root;
		}
		/*
		  由于schema中tag的name和parent联合作为key，
		  所以保存tag当不小心未设parent时，可能造成保存
		  失败（因为虽然root不同但parent都为0），所以
		  添加以上逻辑，堵住上述bug的源头。
		  (xiaopei.li@2011.05.23)
		*/

		return parent::save($overwrite);
	}

	function delete() {
		if ($this->id) {
			if (!Q("product_category[parent=$this]")->delete_all()) return FALSE;
		}
		return parent::delete();
	}

	function update_root() {
		$parent = $this->parent;
		if ($parent->root->id) $this->root = $parent->root;
		elseif ($parent->id) $this->root = $parent;
		else $this->root = NULL;

		return $this;
	}

	function get_type() {

		if ($this->root->id) {
			$type = $this->root->get_type();
		}
		elseif ($this->type) {
			$type = $this->type;
		}
		else {
			$roots = self::get_roots();
			foreach ($roots as $t => $r) {
				if ($r->id == $this->id) {
					$type = $t;
					$r->type = $t;
					$r->save();
					break;
				}
			}
		}

		return $type;
	}

	function init() {
		parent::init();
		if ($this->id) {
			if (!$this->type) $this->type = $this->get_type();
		}
	}

	function icon_file($size=256, array $fields = array('id')) {
		foreach($fields as $field){
			$file = Core::file_exists(PRIVATE_BASE.'icons/'.$this->name().'/'.$size.'/'.$this->$field.'.png', '*');
			if($file) break;
		}

		if (!$file && $this->type) {
				$file = Core::file_exists(PRIVATE_BASE.'icons/'.$this->name().'.'.$this->type.'/'.$size.'.png', '*');
		}

		if (!$file) $file = Core::file_exists(PRIVATE_BASE.'icons/'.$this->name().'/'.$size.'.png', '*');

		if (!$file) $file = Core::file_exists(PRIVATE_BASE.'icons/'.$size.'.png', '*');

		return $file;
	}

	// 确认具有某tag子节点
	function has_descendant($obj) {
		if ($obj->id) {
			if($obj->parent->id == $this->id) return true;
			else return $this->has_descendant($obj->parent);
		}
		return false;
	}

	function is_itself_or_ancestor_of($obj) {
		return $obj->id == $this->id || $this->has_descendant($obj);
	}
}
