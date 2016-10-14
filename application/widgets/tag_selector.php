<?php

class Tag_Selector_Widget extends Widget {

	function __construct($vars) {
		parent::__construct('application:tag_selector', $vars);
	}

	function on_tag_mouseover() {
		$form = Input::form();
		$tag = O('tag',$form['tag_id']);
		$root = O('tag',$form['root_id']);
		$root = $root->id ? $root : O('tag', $tag->root->id);
		$uniqid = $form['uniqid'];
		$real_root = $root->root->id ? $root->root : $root;
		$children = Q("tag[root=$real_root][parent=$tag]:sort(weight)");
		$items = array();
		foreach ($children as $t) {
			$items[$t->id] = array(
				'html' => (string) V('application:widgets/tag_selector/item', array('tag'=>$t)),
				'ccount' => Q("tag[root=$real_root][parent=$t]")->total_count()
			);
		}

		if (count($children) > 0) {
			//判断是对标签隐藏还是展示
			Output::$AJAX['items'] = $items;
		}

	}

	function on_tag_change() {
		$form = Input::form();
		$tag = O('tag',$form['tag_id']);
		$root = O('tag',$form['root_id']);
		Output::$AJAX['.'.$form['uniqid']] 
			= (string) V('application:widgets/tag_selector/container',array(
					'tag'=>$tag->id ? $tag : $root,
					'root'=>$root,
					'root_name' => $form['root_name'],
					'name'=>$form['name'],
					'uniqid'=>$form['uniqid'],
				));
	}
	
	function on_tag_click() {
		$this->on_tag_change();
	}
	

}
