<?php

class Category_Selector_Widget extends Widget {

	function __construct($vars) {
		parent::__construct('application:category_selector', $vars);
	}

	function on_category_mouseover() {
		$form = Input::form();
		$category = O('product_category',$form['category_id']);
		$root = O('product_category',$form['root_id']);
		$root = $root->id ? $root : O('product_category', $category->root->id);
		$uniqid = $form['uniqid'];
		$real_root = $root->root->id ? $root->root : $root;
		$children = Q("product_category[root=$real_root][parent=$category]:sort(weight)");
		$items = array();
		foreach ($children as $t) {
			$items[$t->id] = array(
				'html' => (string) V('application:widgets/category_selector/item', array('category'=>$t)),
				'ccount' => Q("product_category[root=$real_root][parent=$t]")->total_count()
			);
		}

		if (count($children) > 0) {
			//判断是对标签隐藏还是展示
			Output::$AJAX['items'] = $items;
		}

	}

	function on_category_change() {
		$form = Input::form();
		$category = O('product_category',$form['category_id']);
		$root = O('product_category',$form['root_id']);
		Output::$AJAX['.'.$form['uniqid']] 
			= (string) V('application:widgets/category_selector/container',array(
					'category'=>$category->id ? $category : $root,
					'root'=>$root,
					'root_name' => $form['root_name'],
					'name'=>$form['name'],
					'uniqid'=>$form['uniqid'],
				));
	}
	
	function on_category_click() {
		$this->on_category_change();
	}
	

}
