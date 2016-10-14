<?php

class Comments_Widget extends Widget {

	function __construct($vars) {
		parent::__construct('application:comments', $vars);
	}

	function on_form_submit() {
		$form = Input::form();
		if ($form['submit']) {
			$content = trim($form['content']);
			if (!$content) return;

			$object = O($form['oname'], $form['oid']);
			
			$me = L('ME');
			if ($object->id && $me->is_allowed_to('发表评论', $object)) {
				$comment = O('comment');
				$comment->object = $object;
				$comment->content = $form['content'];
				$comment->author = $me;
				$comment->save();

				$list_id = '#'.$form['list_id'];
				Output::$AJAX[$list_id] = array(
					'data'=>(string)V('application:widgets/comments/list', array(
							'list_id'=>$form['list_id'], 
							'object'=>$object
						))
					);

                $span = '#'. $form['form_id']. ' span.max_length';

                Output::$AJAX[$span] = array(
                    'data'=> (string) V('application:widgets/comments/max_length', array(
                        'max_length'=> $form['max_length']
                    )),
                );

				JS::run(JS::smart()->jQuery('[q-widget=comments] [name=content]')->val(''));
			}
			else {
				JS::alert(HT('您无权发表评论!'));
			}
		}

	}

	function on_delete_click() {
		$form = Input::form();

		$comment = O('comment', $form['id']);
		if (!$comment->id) return;

		$me = L('ME');
		if ($me->is_allowed_to('删除', $comment)) {

			if (JS::confirm(HT('您确定要删除该条评论吗?'))) {
				$comment->delete();
				$comment_id = '#'.$form['comment_id'];
				$object = O($form['oname'], $form['oid']);
				if (!$object->id) {
					Output::$AJAX[$comment_id] = array('data'=>'', 'mode'=>'replace');
				}
				else {
					$list_id = '#' . $form['list_id'];
					$view = (string)V('application:widgets/comments/list', array('object'=>$object, 'list_id'=>$form['list_id']));
					Output::$AJAX[$list_id] = array('data'=>$view);
				}
			}
		}
		else {
			$message_id = '#'.$form['message_id'];
			JS::alert(HT('删除失败!'));
		}
	}	
	
	function on_more_click() {
		$form = Input::form();
		$object = O($form['oname'], $form['oid']);
		$start = (int)$form['start'];
		if ($object->id) {
			$more_id = '#'.$form['more_id'];

			$view = '';
			$comments = Q("comment[object=$object]:sort(ctime D)")->limit($start, 5);
			$list_id = $form['list_id'];
			foreach ($comments as $comment) {
				$view .= (string) V('application:widgets/comments/item', array(
					'comment'=>$comment,
					'object'=>$object,
					'list_id'=>$list_id
				));
			}

			if ($comments->total_count() > $start + 5) {
				$view .= (string) V('application:widgets/comments/more', array(
					'object'=>$object, 
					'start'=>$start + 5,
					'list_id'=>$list_id
				));
			}

			Output::$AJAX[$more_id] = array('data'=>$view, 'mode'=>'replace');
		}
	}

}

