<?php
$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;

$root = O('product_category', 808);
if($root->name != '生物试剂') {
    die('升级失败');    
}
$product_categorys = [
	'901' => '试剂盒',
	'902' => '酶类',
	'903' => '抗原与抗体',
	'904' => '核酸/蛋白电泳与分析试剂',
	'905' => '细胞/菌株/载体',
	'906' => '细胞/细菌培养试剂',
	'907' => '色谱类试剂',
	'908' => '氨基酸、多肽与蛋白质',
	'909' => '其他实验试剂',
	'910' => '抑制剂'
];

foreach ($product_categorys as $id => $name) {
	$pc = O('product_category');
	$pc->id = $id;
	$pc->name = $name;
	$pc->root = $root;
	$pc->parent = $root;
    $pc->save();
    echo '.';

}
