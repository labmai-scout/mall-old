<?php


class Product_Upload_Record_Model extends Presentable_Model {

	const RECORD_STATUS_DRAFT    = 0;
	const RECORD_STATUS_READY    = 1;
	const RECORD_STATUS_SUCCESS  = 2;
	const RECORD_STATUS_FAILED   = 3;
	const RECORD_STATUS_CANCELED = 4;

	static $record_status = array(
		self::RECORD_STATUS_DRAFT => '待确认',
		self::RECORD_STATUS_READY => '待导入',
		self::RECORD_STATUS_SUCCESS => '已完成',
		self::RECORD_STATUS_FAILED => '导入失败',
		self::RECORD_STATUS_CANCELED => '已取消',
	);

    function & links($mode = 'index', $buttom = FALSE) {

		$links = new ArrayIterator;

		$me = L('ME');

		switch ($mode) {
		case 'admin_index':
			if ($this->status == self::RECORD_STATUS_DRAFT || $this->status == self::RECORD_STATUS_READY) {
				$links['cancel'] = array(
					'url' => '#',
					'text' => HT('取消导入'),
					'extra'=> 'class="blue" ' .
					'q-object="upload_cancel" q-event="click" ' .
					'q-src="' . URI::url() . '"' .
					'q-static="' . H(array('id' => $this->id)) . '"',
					);
				if ($this->status == self::RECORD_STATUS_DRAFT) {
					$links['confirm'] = array(
						'url' => '#',
						'text' => HT('确认导入'),
						'extra'=> 'class="blue" ' .
						'q-object="upload_confirm" q-event="click" ' .
						'q-src="' . URI::url() . '"' .
						'q-static="' . H(array('id' => $this->id)) . '"',
						);
				}
			}
			break;
		}
		return (array) $links;
	}

}
