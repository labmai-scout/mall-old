<?php

class Role_Model extends Presentable_Model {

    static $privacy = array(
        '2'=>'所有人可见',
        '0'=>'组织机构管理员可见',
        '1'=>'系统管理员可见'
    );

    const Privacy_All = 2;
    const Privacy_Group = 0;
    const Privacy_Admin = 1;

	protected $object_page = array(
		'delete'=>'!roles/delete.%id',
	);
	
	function save($overwrite = FALSE) {
		if (!$this->id) {
			$last_role = Q('role:sort(weight D)')->current();
			$this->weight = $last_role->weight + 1;
		}
		return parent::save($overwrite);
	}
}

