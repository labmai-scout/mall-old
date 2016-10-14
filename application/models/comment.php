<?php

class Comment_Model extends ORM_Model {

	static function comment_ACL($e, $user, $perm, $comment, $options) {
		if (!$comment->id) return;

		if ($user->access('管理所有内容')) {
			$e->return_value = TRUE;
			return FALSE;
		}

		if ($user->id == $comment->author->id) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}
}
