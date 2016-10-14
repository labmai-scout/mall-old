<?php

$config['mall_dics'] = array(
	ROOT_PATH.PUBLIC_BASE.'dict/chars.dic',
	ROOT_PATH.PUBLIC_BASE.'dict/words.dic',
	ROOT_PATH.PUBLIC_BASE.'dict/test.dic',
	ROOT_PATH.PUBLIC_BASE.'dict/update.dic',
);

$config['need_history'] = TRUE;
$config['auto_delete_dic'] = TRUE; // 脚本自动删除词库中的脚本

$config['add_dic_path'] = ROOT_PATH.PUBLIC_BASE.'dict/add.dic';
$config['add_dic_path_history'] = ROOT_PATH.PUBLIC_BASE.'dict/add_history.dic';
$config['delete_dic_path'] = ROOT_PATH.PUBLIC_BASE.'dict/delete.dic';
$config['delete_dic_path_history'] = ROOT_PATH.PUBLIC_BASE.'dict/delete_history.dic';
$config['write_dic'] = ROOT_PATH.PUBLIC_BASE.'dict/update.dic';
$config['tmp_dic'] = ROOT_PATH.PUBLIC_BASE.'dict/tmp.dic';
