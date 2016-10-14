<?php

class Message_Model extends Presentable_Model {

	static $members = array(
		'学生'=>array(0=>'本科生', '硕士研究生', '博士研究生','其他'),
		'教师'=>array(10=>'课题负责人(PI)', '科研助理', 'PI助理/实验室管理员', '其他'),
		'其他'=>array(20=>'技术员', '博士后', '其他')
    );

	protected $object_page = array(
		'view'=>'!messages/message/index.%id[.%arguments]',
		'delete'=>'!messages/message/delete.%id[.%arguments]',
		'reply'=>'!messages/message/reply.%id[.%arguments]',
		'add'=>'!messages/index/add[.%arguments]',
	);

	function & links($mode = 'index', $button=FALSE) {
		$links = new ArrayIterator;
		switch ($mode) {
		case 'view':
			$links['reply'] = array(
				'url' => $this->url('','','','reply'),
				'text' => I18N::T('messages','回复'),
				'extra' => 'class="button button_add"'
			);

			$links['delete'] = array(
				'url' => $this->url('','','','delete'),
				'text' =>I18N::T('messages', '删除'),
				'extra'=>'class="button button_delete" confirm="'.I18N::T('messages', '你确定要删除吗？删除后不可恢复!').'"',
			);
			break;
		case 'index':
		default:
			$links['reply'] = array(
				'url' => $this->url('','','','reply'),
				'text' => I18N::T('messages','回复'),
				'extra' => 'class="blue"'
			);

			$links['delete'] = array(
				'url' => $this->url('','','','delete'),
				'text' =>I18N::T('messages', '删除'),
				'extra'=>'class="blue" confirm="'.I18N::T('messages', '你确定要删除吗？删除后不可恢复!').'"',
			);
		}
		return (array) $links;
	}

}
