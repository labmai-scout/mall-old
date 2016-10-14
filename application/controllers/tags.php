<?php

class Tags_AJAX_Controller extends AJAX_Controller {

	function index_tag_edit_click() {
		$form = Input::form();
		if ($form['id']) {
			$tag = O('tag',$form['id']);
		}
		JS::dialog(V('application:admin/tags/tag_edit', array(
			'tag'=>$tag,
			'parent'=>$form['parent'],
			'uniqid'=>$form['uniqid'],
			'parent_uniqid'=>$form['parent_uniqid'],
			'is_root'=>$form['is_root'],
			'collapsed'=>$form['collapsed'],
			))
		);
	}

	function index_tag_edit_submit() {
		$form = Form::filter(Input::form());
		$name = trim($form['name']);
		$tag = O('tag', $form['id']);
        $form->validate('name', 'not_empty', I18N::T('application', '标签名称不能为空!'));
		
		if ($form->no_error) {
			try {

				if (empty($name)) {
					throw new Error_Exception;
				}
				
				$tag->name = $name;

				if ($tag->id) {
					$new = FALSE;
				}
				else {
					$new = TRUE;
					$parent = O('tag', $form['parent']);
					$tag->parent = $parent;
					$last_tag = Q("tag[parent={$parent}]:sort(weight D)")->current();
					$tag->weight = $last_tag->weight + 1;
				}

				$tag->update_root()->save();

				if (!$tag->id) throw new Error_Exception;

				$item_rel = '#'.$form['uniqid'];

				if ($new) {

					if ($parent->id) {
						$tags = Q("tag[parent=$parent]:sort(weight)");
					}
					else {
						$tags = Q("tag[!root]:sort(weight)");
					}

					if ($form['is_root']) {
						Output::$AJAX["$item_rel > .tag_container"] = (string) V('application:admin/tags/tag_list', array('tags'=> $tags, 'parent_uniqid'=>$form['uniqid']));
					}
					else {
						Output::$AJAX["$item_rel"] = array(
							'data' => (string) V('application:admin/tags/tag_item', array('tag'=>$parent, 'tags'=>$tags, 'parent_uniqid'=> $form['parent_uniqid'])),
							'mode' => 'replace',
						);

					}

				}
				else {
					Output::$AJAX["$item_rel > .tag_title"] = array(
						'data'=>(string) V('application:admin/tags/tag_title', array('tag'=>$tag, 'uniqid'=>$form['uniqid'], 'parent_uniqid'=>$form['parent_uniqid'], 'collapsed'=>$form['collapsed'])),
						'mode'=>'replace',
					);
				}

			}
			catch (Error_Exception $e) {
			}
			JS::close_dialog();
		}
		else {
			JS::dialog(V('application:admin/tags/tag_edit', array(
				'tag'=>$tag,
				'parent'=>$form['parent'],
				'uniqid'=>$form['uniqid'],
				'parent_uniqid'=>$form['parent_uniqid'],
				'is_root'=>$form['is_root'],
				'collapsed'=>$form['collapsed'],
				'form'=>$form,
				))
			);
		}	
 	}

	function index_tag_view_click(){
		$form = Form::filter(Input::form());

		$tag = O('tag', $form['tag']);

		$item_rel = '#'.$form['uniqid'];
		$view = V('application:admin/tags/tag_item', array('tag'=>$tag, 'uniqid'=>$form['uniqid'], 'parent_uniqid'=>$form['uniqid']));

		if (!$form['collapse'] && $tag->id) {
			$view->tags = Q("tag[parent=$tag]:sort(weight)");
		}

		Output::$AJAX["$item_rel"] = array(
			'data' => (string) $view,
			'mode' => 'replace',
		);

	}

	function index_tag_delete_click() {
		if (JS::confirm(T('您确定要删除该标签及其下面所有的子标签吗?删除后不可恢复!'))) {
			//遍历删除并将页面重置
			$form = Input::form();

			$tag = O('tag', $form['id']);
			if ($tag->id) {

				$parent = $tag->parent;

				$item_rel = '#'.$form['uniqid'];

				if (!$tag->delete()) {
					$messages = Site::messages(Site::MESSAGE_ERROR);
					if (count($messages) > 0) {
						JS::alert(implode("\n", $messages));
					}
					return;
				}

				Output::$AJAX["$item_rel"] = array(
					'data'=>'',
					'mode'=>'replace',
				);

				if($parent->id && Q("tag[parent=$parent]")->length() == 0) {
					$parent_item_rel = '#'.$form['parent_uniqid'];
					$js = "jQuery('$parent_item_rel > .tag_title .toggle_button').click()";
					JS::run($js);
				}
			}
		}
	}

	function index_tag_sortable_change(){
		$form = Form::filter(Input::form());
		$p_tag = O('tag',$form['prev_id']);
		$tag = O('tag',$form['current_id']);
		$uniqid = $form['uniqid'];
		$parent = $tag->parent ?: $tag->root;
		if($p_tag->id && ($p_tag->parent->id == $parent->id)) {
			if($p_tag->weight < $tag->weight) {
				$new_weight = $p_tag->weight+1;
			}
			else{
				$new_weight = $p_tag->weight;
			}
		}else{
			$new_weight = 0;
		}
		$old_weight = $tag->weight;
		if($old_weight < $new_weight) {
			$tags = Q("tag[parent={$parent}][weight>$old_weight][weight<=$new_weight]:sort(weight D)");
			$tmp = $new_weight;
			foreach($tags as $t) {
				if($tmp > $t->weight) break;
				$t->weight = $t->weight-1;
				$t->save();
				$tmp--;
			}
		}
		else{
			$tags = Q("tag[parent={$parent}][weight>=$new_weight][weight<$old_weight]:sort(weight)");
			$tmp = $new_weight;
			foreach($tags as $t) {
				if($tmp < $t->weight) break;
				$t->weight = $t->weight+1;
				$t->save();
				$tmp++;
			}
		}
		$tag->weight = $new_weight;
		$tag->save();
		$tags = Q("tag[parent={$parent}]:sort(weight)");
		if($parent->id == $tag->root->id) {
			Output::$AJAX["#$uniqid > .tag_root_container"] = array(
				'data'=>(string)V('application:admin/tags/tag_list', array(
															'tags'=>$tags,
															'parent_uniqid'=>$uniqid,
											)),
			);
		}
		else{
			Output::$AJAX["#$uniqid"] = array(
				'data'=>(string)V('application:admin/tags/tag_item', array(
													'tags'=>$tags,
													'tag'=>$parent,
													'parent_uniqid'=> $form['parent_uniqid']
									)),
				'mode'=>'replace',
			);
		}
	}

	function index_tag_move_change(){
		$form = Form::filter(Input::form());
		$rec_tag = O('tag',Q::quote($form['rec_id']));
		$tag = O('tag',Q::quote($form['current_id']));

		#if (tag.group_limit >= 1)

		$root = $rec_tag->root;
		$max_levels = Config::get('tag.group_limit');
		if ($max_levels && $root->id) {
			//求target标签层数（第几层）
			$rec_levels = $root->current_levels($rec_tag);
			//target标签，要移动的标签都要作限制
			if ($rec_levels >= $max_levels) {
				Output::$AJAX[] = array('error'=>true);
				return false;
			}
		}
		#endif

		$item = '#'.$form['uniqid'];
		$parent_uniqid = '#'.$form['parent_uniqid'];
		$view =  V('application:admin/tags/tag_item', array('tag'=>$rec_tag, 'parent_uniqid'=> $form['parent_uniqid']));
		if(!$tag->has_descendant($rec_tag) && ($tag->parent->id != $rec_tag->id || !$form['is_refresh'])) {
			$tag->parent = $rec_tag;
			$tags = Q("tag[parent=$rec_tag]:sort(weight)");
			$t = $tags->current();
			if($t->weight == 0) {
				foreach($tags as $v) {
					$v->weight = $v->weight+1;
					$v->save();
				}
			}
			$tag->weight = 0;
			$tag->save();
		}
		else{
			Output::$AJAX[] = array('error'=>true);
			return false;
		}
		$tags = Q("tag[parent={$rec_tag}]:sort(weight)");
		$collapse = $form['collapse'];
		if(!$collapse) {
			$view->tags = $tags;
		}
		if($form['is_refresh']) {
			if($rec_tag->root->id == 0) {
				Output::$AJAX["$parent_uniqid > .tag_root_container"] = array(
					'data'=>(string)V('application:admin/tags/tag_list', array(
																'tags'=>$tags,
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
