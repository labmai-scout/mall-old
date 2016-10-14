<?php

class Role {

	static function layout_admin_sidebar_menu($e) {
		if (L('ME')->access('管理分组')) {
			$sidebar = (array) $e->return_value;
			$sidebar['roles'] = (array) Config::get("layout.admin.sidebar.menu.roles");
			$e->return_value = $sidebar;
		}
	}


	static function user_ACL($e, $user, $action, $object, $options) {
        //object 为role对象

        if (!$object->id) {
            $e->return_value = FALSE;
            return FALSE;
        }
        
        switch($perm) {
            case '查看' ;
                $privacy = (int) $object->privacy;
                if ($privacy == Role_Model::Privacy_All) {
                    $e->return_value = TRUE; 
                    return FALSE;
                }

                if ($privacy == Role_Model::Privacy_Group) {
                    if ($user->access('添加/修改下属机构成员的信息') || $user->access('添加/修改所有成员信息')) {
                        $e->return_value = TRUE; 
                    }
                    return FALSE;
                }

                if ($privacy == Role_Model::Privacy_Admin) {
                    if ($user->access('添加/修改所有成员信息')) {
                        $e->return_value = TRUE; 
                    }
                    return FALSE;
                }
                break; 
            default : 
                $e->return_value = FALSE;
                return FALSE;
                break; 
        } 
    }
}
