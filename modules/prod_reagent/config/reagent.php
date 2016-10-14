<?php

if (!class_exists('Reagent_Types')) {

	class Reagent_Type {
		const REGULAR = 1;
		const DANGEROUS = 2;
		const EASYMADE_TOXIC = 3;
        const SUPER_TOXIC = 4;
        const EXPLOSIVE = 5;
        const PSYCHOTROPIC = 6;
        const NARCOTIC = 7;
	}

}
/*
$config['categories'] = array(
	100 => '通用试剂',
	101 =>	'有机',
	102 =>	'无机',
	103 =>	'分析',
	104 =>	'生化',
	105 =>	'CAS',
	106 =>	'离子交换',
	200 => '有机试剂',
	201 =>	'杂环化合物',
	202 =>	'聚合物试剂',
	203 =>	'离子液体',
	204 =>	'有机金属',
	205 =>	'同位素标记',
	206 =>	'无水试剂',
	300 => '无机试剂',
	301 =>	'催化剂',
	302 =>	'硅胶',
	303 =>	'分子筛',
	304 =>	'干燥剂',
	305 =>	'层析',
	306 =>	'无水试剂',
	307 =>	'无机盐',
	308 =>	'酸',
	309 =>	'碱',
	400 => '分析试剂',
	401 =>	'标准品',
	402 =>	'基准试剂',
	403 =>	'卡尔费休',
	404 =>	'气相色谱',
	405 =>	'液相色谱',
	406 =>	'指示剂',
	407 =>	'缓冲剂',
	500 => '高纯试剂',
	501 =>	'稀土金属',
	502 =>	'高纯无机试剂',
	503 =>	'光谱纯试剂',
	504 =>	'高纯金属',
	505 =>	'高纯溶剂',
	506 =>	'复配试剂',
	600 => '生化试剂',
	601 =>	'抗体',
	602 =>	'酶类',
	603 =>	'诊断试剂',
	604 =>	'糖类',
	605 =>	'维生素',
	606 =>	'氨基酸',
	607 =>	'蛋白质',
	608 =>	'培养基',
	609 =>	'核苷酸',
	610 =>	'生物碱',
	700 => '环保试剂',
	701 =>	'环保指示剂',
	702 =>	'缓冲液',
	703 =>	'环境测试盒',
	704 =>	'环保标样',
	705 =>	'微量分析',
	706 =>	'环保试纸',
	800 => '精细化工',
	801 =>	'化工产品',
	802 =>	'化工原料',
	803 =>	'助剂',
	804 =>	'大包装试剂',
	805 =>	'清洗消毒',
	806 =>	'硅烷偶联剂',
	);
*/

$config['types'] = array(
	'normal' => '普通试剂',
	'drug_precursor' => '危险化学品',
	'hazardous' => '易制毒化学品',
    'highly_toxic' => '剧毒化学品',
    'explosive'=> '易制爆化学品',
    'psychotropic'=> '精神药品',
    'narcotic'=> '麻醉药品',
);
$config['labels'] = [
	Reagent_Type::REGULAR => 'normal',
	Reagent_Type::DANGEROUS => 'drug_precursor',
    Reagent_Type::EASYMADE_TOXIC => 'hazardous',
    Reagent_Type::SUPER_TOXIC => 'highly_toxic',
    Reagent_Type::EXPLOSIVE => 'explosive',
    Reagent_Type::PSYCHOTROPIC=> 'psychotropic',
    Reagent_Type::NARCOTIC=> 'narcotic',
];
$config['danger_classes'] = array(
	0 => '--',
	'爆炸品' => array(
		101 => '具有整体爆炸危险的物质和物品',
		),
	'压缩气体和液化气体' => array(
		201 => '易燃气体 ',
		'不燃气体（包括助燃气体）',
		'有毒气体',
		),
	'易燃液体' => array(
		301 => '低闪点液体',
		'中闪点液体',
		'高闪点液体',
		),
	'易燃固体、自燃物品和遇湿易燃物品' => array(
		401 => '易燃固体',
		'自燃物品',
		'遇湿易燃物品',
		),
	'氧化剂和有机过氧化物' => array(
		501 => '氧化剂',
		'有机过氧化物',
		),
	'有毒品' => array(
		601 => '有毒品',
		),
	'放射性物品' => array(
		701 => '放射性物品',
		),
	'腐蚀品' => array(
		801 => '酸性腐蚀品',
		'碱性腐蚀品',
		'其他腐蚀品',
		),
	);

$config['enable_batch_import'] = FALSE;

$config['price_ranges'] = array(
			'0' => ['-1', '0'],
			'1' => ['0', '1000'],
			'2' => ['1000', '2000'],
			'3' => ['2000', '3000'],
			'4' => ['3000'],
		);
