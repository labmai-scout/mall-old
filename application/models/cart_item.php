<?php


class Cart_Item_Model extends Presentable_Model {

	protected $object_page = array(
        'view'=>'!customer/profile/index.%id[.%arguments]',
        'edit'=>'!customer/profile/edit.%id[.%arguments]',
        'delete'=>'!mall/cart/remove.%id[.%arguments]',
	);

	function & links($mode='index', $button=FALSE) {
		$links = new ArrayIterator;
		$me = L('ME');

		switch ($mode) {
		default:
			$links['delete'] = array(
				'url'=>'#',
				'text'=>T('删除'),
				'extra'=>'class="blue" q-object="cart_remove" q-event="click" q-static="id='.intval($this->id).'"',
			);
			break;
        }

		return (array)$links;
	}

}
