<?php

class Order_Item_AJAX_Controller extends AJAX_Controller {

	function index_show_comment_click() {
		$form = Input::form();

		$comment = O('order_item_comment', $form['comment_id']);

		if ($comment->id) {
			JS::dialog( V('admin:order_item/comment/show', array(
							  'comment' => $comment,
							  )),
						array('title'=>HT('查看评价')));
		}
	}
}
