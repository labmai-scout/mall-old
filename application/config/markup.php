<?php

$config['filters'][] = ['callback'=>'Markup::decode_markdown_link', 'weight' => -10];
$config['filters'][] = ['callback'=>'Markup::decode_URL', 'weight' => -1];
$config['filters'][] = 'Markup::decode_Q';

