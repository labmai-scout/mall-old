<?php
$config['nfs'] = [
	'fields' => [
		'name' => ['type' => 'rt_field'],
		'spath' => ['type' => 'rt_field'],
		'spath_prefix' => ['type' => 'rt_field'],
		'path' => ['type' => 'rt_attr_string'],
		'path_prefix' => ['type' => 'rt_attr_string'],
		'mtime' => ['type' => 'rt_attr_timestamp'],
		'ctime' => ['type' => 'rt_attr_timestamp'],
		],
	'options' => [
		'dict' => ['value' => 'keywords'],
		'expand_keywords' => ['value' => '1'],
		'enable_star' => ['value' => '1'],
		'min_infix_len' => ['value' => '2'],
		'infix_fields' => ['value' => 'spath'],
		],
];
