<?php

$config['system.setup'][] = 'Application::setup';

$config['orm_model.before_save'][] = 'Site::save_abbr';

$config['auth.logout'][] = 'Site::forget_login';

//gapper用户被删除的时候，清除关联的本地用户的信息
$config['user_model.deleted'][] = 'Gapper::on_user_deleted';
