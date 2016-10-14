<?php
$config['merge_criterias'] = array(
	'manufacturer' => '生产商',
	'catalog_no' => '目录号',
	'package' => '包装'
);

// 批准商品一定会修改产品(product)信息, 以下开关控制是否同时修改商品(product)信息.
// 默认为关, 即不修改商品信息 (xiaopei.li@2012-06-06)
$config['assign_product_when_approve'] = FALSE;
