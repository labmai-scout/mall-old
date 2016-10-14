<?php
class Product_Category_Controller extends Product_Base_Controller {

    function index() {

		$roots = Product_Category_Model::get_roots();

		$this->layout->body->primary_tabs
			->select('category')
			->content = V('admin:product/category', array('roots' => $roots));

		$this->add_js('application:category_sortable');
		$this->add_css('application:category application:category_sortable');
	}

	function upload_icon($id = 0) {
		$category = O('product_category', $id);
		if (!$category->id) {
			URI::redirect('error/404');
		}

		$icon = Input::file('icon');

		if ($icon['tmp_name'] && File::exists($icon['tmp_name'])) {
			try {
				$ext = File::extension($icon['name']);
				$category->save_icon(Image::load($icon['tmp_name'], $ext));
				Site::message(Site::MESSAGE_NORMAL, HT('商品分类图片已更新!'));
			}
			catch(Error_Exception $e) {
				Site::message(Site::MESSAGE_ERROR, $e->getMessage());
				Site::message(Site::MESSAGE_ERROR, T('商品分类图片更新失败!'));
			}
		}
		else {
			Site::message(Site::MESSAGE_ERROR, T('请选择您要上传的商品分类图片'));
		}

		JS::refresh();

	}

}


class Product_Category_AJAX_Controller extends AJAX_Controller {
	function index_delete_icon_click() {
		$category = O('product_category', Input::form('id'));

		if ($category->id) {
			if (JS::confirm(T('您确定要删除商品分类图片么?'))) {
				$category->delete_icon();
				Site::message(Site::MESSAGE_NORMAL, T('商品分类图片删除成功!'));
				JS::refresh();
			}
		}
	}

	function index_category_edit_icon_click() {

		$form = Input::form();
		if ($form['id']) {
			$category = O('product_category',$form['id']);
		}
		// JS::dialog(V('admin:admin/categories/category_edit_icon', array(
		JS::dialog(V('admin:admin/categories/category_edit_icon', array(
			'category'=>$category,

			'parent'=>$form['parent'],
			'uniqid'=>$form['uniqid'],
			'parent_uniqid'=>$form['parent_uniqid'],
			'is_root'=>$form['is_root'],
			'collapsed'=>$form['collapsed'],
			)),
			array(
				'title' => T('编辑商品分类图片'),
				'drag' => TRUE
			)
		);
	}

	function index_category_edit_click() {

		$form = Input::form();
		if ($form['id']) {
			$category = O('product_category',$form['id']);
		}
		JS::dialog(V('admin:admin/categories/category_edit', array(
			'category'=>$category,
			'parent'=>$form['parent'],
			'uniqid'=>$form['uniqid'],
			'parent_uniqid'=>$form['parent_uniqid'],
			'is_root'=>$form['is_root'],
			'collapsed'=>$form['collapsed'],
			)),
			array(
				'title' => $form['id'] ? T('编辑商品分类') : T('添加商品分类'),
				'drag' => TRUE
			)
		);
	}

	function index_category_edit_submit() {
		$form = Form::filter(Input::form());

		try {
			$category = O('product_category', $form['id']);
			if ($category->id) {
				$new = FALSE;
				$category->name = $form['name'];
				$category->description = $form['description'];
			}
			else {
				$new = TRUE;
				if (!$form['name']) throw new Error_Exception;
				$parent = O('product_category', $form['parent']);
				$category->parent = $parent;
				$last_category = Q("product_category[parent={$parent}]:sort(weight D)")->current();
				$category->weight = $last_category->weight + 1;
				$category->name = $form['name'];
				$category->description = $form['description'];
			}

			$category->update_root()->save();

			if (!$category->id) throw new Error_Exception;

			$item_rel = '#'.$form['uniqid'];

			if ($new) {

				if ($parent->id) {
					$categories = Q("product_category[parent=$parent]:sort(weight)");
				}
				else {
					$categories = Q("product_category[!root]:sort(weight)");
				}

				if ($form['is_root']) {
					Output::$AJAX["$item_rel > .category_container"] = (string) V('admin:admin/categories/category_list', array('categories'=> $categories, 'parent_uniqid'=>$form['uniqid']));
				}
				else {
					Output::$AJAX["$item_rel"] = array(
						'data' => (string) V('admin:admin/categories/category_item', array('category'=>$parent, 'categories'=>$categories, 'parent_uniqid'=> $form['parent_uniqid'])),
						'mode' => 'replace',
					);

				}

			}
			else {
				Output::$AJAX["$item_rel > .category_title"] = array(
					'data'=>(string) V('admin:admin/categories/category_title', array('category'=>$category, 'uniqid'=>$form['uniqid'], 'parent_uniqid'=>$form['parent_uniqid'], 'collapsed'=>$form['collapsed'])),
					'mode'=>'replace',
				);
			}

		}
		catch (Error_Exception $e) {
		}

		JS::close_dialog();
 	}

	function index_category_view_click(){
		$form = Form::filter(Input::form());

		$category = O('product_category', $form['category']);

		$item_rel = '#'.$form['uniqid'];
		$view = V('admin:admin/categories/category_item', array('category'=>$category, 'uniqid'=>$form['uniqid'], 'parent_uniqid'=>$form['uniqid']));

		if (!$form['collapse'] && $category->id) {
			$view->categories = Q("product_category[parent=$category]:sort(weight)");
		}

		Output::$AJAX["$item_rel"] = array(
			'data' => (string) $view,
			'mode' => 'replace',
		);

	}

	function index_category_delete_click() {
		if (JS::confirm(T('您确定要删除该标签及其下面所有的子标签吗?删除后不可恢复!'))) {
			//遍历删除并将页面重置
			$form = Input::form();

			$category = O('product_category', $form['id']);
			if ($category->id) {

				$parent = $category->parent;

				$item_rel = '#'.$form['uniqid'];

				if (!$category->delete()) {
					$messages = Site::$messages[Site::MESSAGE_ERROR];
					if (count($messages) > 0) {
						JS::alert(implode("\n", $messages));
					}
					return;
				}

				Output::$AJAX["$item_rel"] = array(
					'data'=>'',
					'mode'=>'replace',
				);

				if($parent->id && Q("product_category[parent=$parent]")->length() == 0) {
					$parent_item_rel = '#'.$form['parent_uniqid'];
					$js = "jQuery('$parent_item_rel > .category_title .toggle_button').click()";
					JS::run($js);
				}
			}
		}
	}

	function index_category_sortable_change(){
		$form = Form::filter(Input::form());
		$p_category = O('product_category',$form['prev_id']);
		$category = O('product_category',$form['current_id']);
		$uniqid = $form['uniqid'];
		$parent = $category->parent ?: $category->root;
		if($p_category->id && ($p_category->parent->id == $parent->id)) {
			if($p_category->weight < $category->weight) {
				$new_weight = $p_category->weight+1;
			}
			else{
				$new_weight = $p_category->weight;
			}
		}else{
			$new_weight = 0;
		}
		$old_weight = $category->weight;
		if($old_weight < $new_weight) {
			$categories = Q("product_category[parent={$parent}][weight>$old_weight][weight<=$new_weight]:sort(weight D)");
			$tmp = $new_weight;
			foreach($categories as $t) {
				if($tmp > $t->weight) break;
				$t->weight = $t->weight-1;
				$t->save();
				$tmp--;
			}
		}
		else{
			$categories = Q("product_category[parent={$parent}][weight>=$new_weight][weight<$old_weight]:sort(weight)");
			$tmp = $new_weight;
			foreach($categories as $t) {
				if($tmp < $t->weight) break;
				$t->weight = $t->weight+1;
				$t->save();
				$tmp++;
			}
		}
		$category->weight = $new_weight;
		$category->save();
		$categories = Q("product_category[parent={$parent}]:sort(weight)");
		if($parent->id == $category->root->id) {
			Output::$AJAX["#$uniqid > .category_root_container"] = array(
				'data'=>(string)V('admin:admin/categories/category_list', array(
															'categories'=>$categories,
															'parent_uniqid'=>$uniqid,
											)),
			);
		}
		else{
			Output::$AJAX["#$uniqid"] = array(
				'data'=>(string)V('admin:admin/categories/category_item', array(
													'categories'=>$categories,
													'category'=>$parent,
													'parent_uniqid'=> $form['parent_uniqid']
									)),
				'mode'=>'replace',
			);
		}
	}

	function index_category_move_change(){
		$form = Form::filter(Input::form());
		$rec_category = O('product_category',Q::quote($form['rec_id']));
		$category = O('product_category',Q::quote($form['current_id']));

		#if (category.group_limit >= 1)

		$root = $rec_category->root;
		$max_levels = Config::get('category.group_limit');
		if ($max_levels && $root->id) {
			//求target标签层数（第几层）
			$rec_levels = $root->current_levels($rec_category);
			//target标签，要移动的标签都要作限制
			if ($rec_levels >= $max_levels) {
				Output::$AJAX[] = array('error'=>true);
				return false;
			}
		}
		#endif

		$item = '#'.$form['uniqid'];
		$parent_uniqid = '#'.$form['parent_uniqid'];
		$view =  V('admin:admin/categories/category_item', array('category'=>$rec_category, 'parent_uniqid'=> $form['parent_uniqid']));
		
		if(!$category->has_descendant($rec_category) && ($category->parent->id != $rec_category->id || !$form['is_refresh'])) {
			$category->parent = $rec_category;
			$categories = Q("product_category[parent={$rec_category}]:sort(weight)");
			$t = $categories->current();
			if($t->weight == 0 && $t->id != $category->id) {
				$tmp = 0;
				foreach($categories as $v) {
					if ($v->weight > $tmp) break;
					$v->weight = $v->weight+1;
					$v->save();
					$tmp ++;
				}
			}
			$category->weight = 0;
			$category->save();
		}
		else{
			Output::$AJAX[] = array('error'=>true);
			return false;
		}
		
		$collapse = $form['collapse'];
		if(!$collapse) {
			$categories = Q("product_category[parent={$rec_category}]:sort(weight)");
			$view->categories = $categories;
		}
		
		if($form['is_refresh']) {
			if($rec_category->root->id == 0) {
				$categories = $categories ?: Q("product_category[parent={$rec_category}]:sort(weight)");
				Output::$AJAX["$parent_uniqid > .category_root_container"] = array(
					'data'=>(string)V('admin:admin/categories/category_list', array(
																'categories'=> $categories,
																'parent_uniqid'=>$form['parent_uniqid'],
												)),
				);
			}
			else{
				Output::$AJAX["$item"] = array(
					'data'=>(string)$view,
					'mode'=>'replace',
				);
			}
		}
		Output::$AJAX[] = array('error'=>false);
		return true;
	}
}