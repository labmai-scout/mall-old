<?php

// 评价内容最少字数
$config['comment_content_length_min'] = 10;
// 评价内容最多字数
$config['comment_content_length_max'] = 500;

// 付款后最早多久可评价
$config['comment_publish_earliest'] = 0;
// 付款后最晚多久可评价
$config['comment_publish_latest'] = 30;

// 打分项
$config['product_rating_subjects'] = array(
	'quality' => '商品质量',
	);

$config['rating_subjects'] = $config['product_rating_subjects'] + array(
	'service' => '服务态度',
	'delivery' => '发货速度',
	);

// 评价后最早多久可回复
$config['reply_earliest'] = 0;
// 评价后最晚多久可回复
$config['reply_latest'] = 15;

// 回复内容最少字数
$config['reply_length_min'] = 10;
// 回复内容最多字数
$config['reply_length_max'] = 500;
