<?php

class News_Model extends Presentable_Model {

    protected $object_page = array(
        'view' => '!news/index.%id[.%arguments]',
    );

	function & links ($mode = 'index', $button=FALSE) {
		switch ($mode) {
		case 'index':
		default:
			$me = L('ME');
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = array(
					'url' => URI::url('!admin/admin/news_edit.'.$this->id),
					'text' => T('修改'),
					'extra' => 'class="blue"',
						);
			}
			if ($me->is_allowed_to('删除', $this)) {
				$links['delete'] = array(
					'url' => NULL,
					'text' => T('删除'),
					'extra' => 'class="blue" q-event="click" q-object="delete_news"'.
					' q-static="'.H(array('a_id'=>$this->id)).
					'" q-src="'.URI::url("!news/index").'"',
						);
			}
			break;
		}
		return (array) $links;	

	}
}
