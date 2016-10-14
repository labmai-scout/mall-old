<?php
$config['controller[!admin/admin/index].ready'][] = 'News::admin_admin_setup';

$config['is_allowed_to[添加].news'][] = 'News::news_ACL';
$config['is_allowed_to[修改].news'][] = 'News::news_ACL';
$config['is_allowed_to[删除].news'][] = 'News::news_ACL';

$config['is_allowed_to[列表文件].news'][] = 'News::attachments_ACL';
$config['is_allowed_to[下载文件].news'][] = 'News::attachments_ACL';
$config['is_allowed_to[上传文件].news'][] = 'News::attachments_ACL';
$config['is_allowed_to[修改文件].news'][] = 'News::attachments_ACL';
$config['is_allowed_to[删除文件].news'][] = 'News::attachments_ACL';
