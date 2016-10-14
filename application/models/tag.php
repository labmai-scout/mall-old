<?php

class Tag_Model extends Presentable_Model {

	static function replace_tags($obj, $tag_names, $root, $create_tag_if_not_exist = FALSE) {

		if (is_string($root)) {
			$root = self::root($root);
		}

		if (!$root->id) return FALSE; //不允许替换所有类型的标签

		foreach(Q("tag[root=$root]") as $tag){
			$tag->disconnect($obj);
		}

		$tag_names = (array) $tag_names;
		foreach($tag_names as $id => $name){
			$tag = O('tag', array('id'=>$id, 'root'=>$root));
			if (!($tag->id && $tag->name == $name)) {
				$tag = O('tag', array('name'=>$name,'root'=>$root));
			}

			if ($create_tag_if_not_exist && !$tag->id) {
				$tag = O('tag');
				$tag->name = $name;
				$tag->root = $root;
				$tag->save();
			}

			if ($tag->id) {
				$tag->connect($obj);
			}
		}

		return TRUE;
	}

	/*
		TASK #641::(Cheng.liu@2011.04.21)
		组织机构的设计存在bug,由于缓存机制的存在依赖于mtime，
		所以让tag对象在保存时对其mtime进行默认修改
	*/
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

		$this->mtime = time();
		$ret = parent::save($overwrite);
		if ($ret) {
			foreach ($this->children() as $child) {
				$child->save($overwrite);
			}
		}
		return $ret;
	}

	function delete() {
		if ($this->id) {
			if (!Q("tag[parent=$this]")->delete_all()) return FALSE;
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

	// 标记对象的时候 所有父节点需要标记对象
	function connect($obj, $type = NULL, $approved = false) {
		if ($this->id) {
			if ($this->parent->id && $this->parent->id != $this->root->id) {
				$this->parent->connect($obj);
			}
		}
		return parent::connect($obj);
	}

	// 取消标记时 所有子节点需要去掉标记
	function disconnect($obj, $type = NULL, $approved = false) {
		if ($this->id) {
			foreach(Q("tag[parent=$this]") as $tag) {
				$tag->disconnect($obj);
			}
		}
		return parent::disconnect($obj);
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

	// (xiaopei.li@2010.12.07)
	function children() {
		return Q("tag[parent=$this]");
	}

	static function root($type, $name=NULL) {

		$conf_name = 'tag.'.$type;
		$conf_id_name = $conf_name.'_id';

		$name = Config::get($conf_name) ?: $name;
		$id = Site::get($conf_id_name);
		$root = O('tag', array('root_id'=>0, 'id'=>$id));
		if (!$root->id) {
			$root = O('tag', array('root_id'=>0, 'name'=>$name));
			if (!$root->id) {
				$root->name = $name;
				$root->parent = NULL;
				$root->root = NULL;
				$root->readonly = TRUE;
				$root->save();
			}

			Site::set($conf_id_name, (int) $root->id);
		}

		return $root;

	}

	/*
	guoping.zhang@2011.01.17
	当前标签是跟标签的第几层
	*/
	function current_levels($tag) {
		$level = 0;
		if ($tag->id) {
			if ($tag->root->id == $this->id){
				$level = 1;
				$tag = $tag->parent;
				$level += self::current_levels($tag);
			}
		}
		return $level;
    }

    /*
     *    rui.ma@2011.11.02
     *    删除特定对象特定类型的所有标签
     *
     */
    static function clean_tags($obj, $type) {
        $root = Tag_Model::root($type);
        $tags = Q("{$obj} tag[root={$root}]");
        foreach ($tags as $t) {
            $t->delete();
        }
    }

    static function get_default_tag($type = NULL) {
        if ($type === NULL) {
            return FALSE;
        }

        $default_name = Config::get("tag.$type.default_name");
        $tag = O('tag', array('name'=>$default_name));
        if ($tag->id) {
            return $tag;
        }

        $tag->name = $default_name;
        $tag->save();
        return $tag;
    }

}
