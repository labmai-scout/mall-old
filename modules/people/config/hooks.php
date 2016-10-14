<?php
$config['controller[*].ready'][] = 'People::accessible_controller';
$config['controller[!admin/admin/index].ready'][] = 'People_Admin::setup';

$config['is_allowed_to[查看系统].user'][] = 'People::people_ACL';
$config['is_allowed_to[激活].user'][] = 'People::people_ACL';
$config['is_allowed_to[隐藏].user'][] = 'People::people_ACL';
$config['is_allowed_to[管理角色].user'][] = 'People::people_ACL';

