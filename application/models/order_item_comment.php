<?php

class Order_Item_Comment_Model extends Presentable_Model {

	function links($mode = 'view', $button=FALSE) {
		$links = new ArrayIterator;
		$me = L('ME');

		switch ($mode) {
		case 'admin_view':
			// TODO 删除
			break;
		case 'vendor_view':
			// TODO 回复
			break;
		case 'customer_view':
			// TODO 追加
			break;
		}

		return (array) $links;
	}

	function can_reply() {
		$ret = FALSE;

		if (!Q("$this order_item_comment_reply")->total_count()) { // 未回复

			// 判断日期
			$earlist = Config::get('comment.reply_earliest');
			$latest = Config::get('comment.reply_latest');

			$has_comment = Date::time() - $this->ctime;

			if ($has_comment > $earlist * 86400 &&
				$has_comment < $latest * 86400) {
				$ret = TRUE ;
			}
		}

		return $ret;

	}
}
