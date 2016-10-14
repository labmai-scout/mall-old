<?php

$config['is_allowed_to[查看价格].product'][] = 'Mall::product_ACL';

$config['is_allowed_to[查看].cart'][] = 'Mall::cart_ACL';
$config['is_allowed_to[结算].cart'][] = 'Mall::cart_ACL';
$config['is_allowed_to[删除项目].cart'][] = 'Mall::cart_ACL';

$config['is_allowed_to[发表评论].vendor'][] = 'Mall::comment_ACL';
