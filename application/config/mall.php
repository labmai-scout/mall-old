<?php
$config['admin'] = ['genee', 'admin'];
//是否允许进行注册
$config['enable_register'] = TRUE;
$config['auto_receive_duration'] = 86400 * 30;

$config['mapping_type'] = [
	'chem_reagent' => 'reagent',
	'bio_reagent'  => 'biologic_reagent',
	'consumable'   => 'consumable',
	'small_device' => 'small_device',
	'computer'	   => 'computer',
	'service'      => 'service',
];

//化学试剂
$config['mapping_chem_reagent'] = [
	'fields' => [
		'chem_type' => [
			'title' => '试剂性质',
			'opts' => [
				1 => '普通试剂',
				2 => '危险化学品',
				3 => '易制毒化学品',
				4 => '剧毒品'
			]
		],
		'price' => [
			'title' => '价格',
			'weight' => 21,
			'opts' => [
				0 => '待询价',
				1 => '0~999',
				2 => '1000~1999',
				3 => '2000~2999',
				4 => '3000以上'
			]
		]
	]
];

//生物试剂
$config['mapping_bio_reagent'] = [
	'fields' => [
		'category' => [
			'title' => '分类',
			'multiple' => true, //是否允许多选
			'opts' => [
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
			]
		],
		/*
		'bio_transport_cond' => [
			'title' => '运输条件',
			'opts' => [
				1 => '快递',
				2 => 'EMS',
				3 => '空运',
				4 => '海运'
			]
		],
		'bio_storage_cond' => [
			'title' => '保存条件',
			'opts' => [
				1 => '常温',
				2 => '零下',
				3 => '工业冰箱'
			]
		],
		*/
		'price' => [
			'title' => '价格',
			'weight' => 21,
			'opts' => [
				0 => '待询价',
				1 => '0~499',
				2 => '500~999',
				3 => '1000~1499',
				4 => '1500~1999',
				5 => '2000以上'
			]
		]
	]
];

//耗材
$config['mapping_consumable'] = [
	'fields' => [
		'price' => [
			'title' => '价格',
			'weight' => 21,
			'opts' => [
				0 => '待询价',
				1 => '0~999',
				2 => '1000~1999',
				3 => '2000~2999',
				4 => '3000以上'
			]
		]
	]
];

//电脑整机
$config['mapping_computer'] = [
	'fields' => [
		'computer_type' => [
			'title' => '机型',
			'opts' => [
				1 => '台式机',
				2 => '笔记本',
				3 => '一体机',
				4 => '工作基站'
			]
		],
		'cpu' => [
			'title' => 'CPU',
			'opts' => [
				1 => 'haswell',
				2 => '酷睿i7',
				3 => '酷睿i5',
				4 => '奔腾',
				5 => '赛扬'
			]
		],
		'memory' => [
			'title' => '内存',
			'opts' => [
				1 => 'DDR',
				2 => 'DDR2',
				3 => 'DDR3',
				4 => 'DDR4'
			]
		],
		'disk' => [
			'title' => '硬盘',
			'opts' => [
				1 => '320GB',
				2 => '500GB',
				3 => '750GB',
				4 => '1TB',
				5 => '2TB',
				6 => '3TB',
				7 => '3TB以上'
			]
		],
		'video_memory' => [
			'title' => '显存',
			'opts' => [
				1 => 'SDRAM',
				2 => 'SGRAM',
				3 => 'DDR',
				4 => 'DDR2',
				5 => 'DDR3',
				6 => 'VRAM',
				7 => 'WRAM',
				8 => 'RDRAM'
			]
		],
		'display' => [
			'title' => '显示器',
			'opts' => [
				1 => '17英寸',
				2 => '19英寸',
				3 => '22英寸',
				4 => '23英寸',
				5 => '23.6英寸',
				6 => '24英寸',
				7 => '26英寸',
				8 => '27英寸'
			]
		],
		'price' => [
			'title' => '价格',
			'weight' => 21,
			'opts' => [
				0 => '待询价',
				1 => '0~1999',
				2 => '2000~2999',
				3 => '3000~4999',
				4 => '5000~7999',
				5 => '8000以上'
			]
		]
	]
];

//通用的筛选条件
$config['general_criteria'] = [
	'stock_status' => [
		'title' => '库存',
		'weight' => 10,
		'opts' => [
			0 => '现货',
			1 => '可预订',
			2 => '暂无存货',
			3 => '停止供货'
		]
	],
	'supply_time' => [
		'title' => '供货时间',
		'weight' => 20,
		'opts' => [
			1 => '1~4天',
			2 => '5~7天',
			3 => '8~14天',
			4 => '14天以上',
		]
	],
];

//数据模板
$config['api_values_mapping'] = [
	'reagent' => [
		'title' => '化学试剂',
		'query_placeholder' => '请输入CAS号或其他关键字',
		'fields' => [
			'spec' => [
				'field' => 'spec',
				'label' => '规格',
				'type' => 'text'
			],
			'cas_no' => [
				'field' => 'cas_no',
				'label' => 'CAS号',
				'type' => 'text'
			],
			'aliases' => [
				'field' => 'rgt_aliases',
				'label' => '别名',
				'type' => 'text_array'
			],
			'en_name' => [
				'field' => 'rgt_en_name',
				'label' => '英文名',
				'type' => 'text'
			],
			'mol_weight' => [
				'field' => 'reagent_mw',
				'label' => '分子量',
				'type' => 'text'
			],
			'mol_formula' => [
				'field' => 'reagent_formula',
				'label' => '分子式',
				'type' => 'text'
			],
			'danger_class' => [
				'field' => 'rgt_danger_class',
				'label' => '危险品等级',
				'type' => 'text'
			],
			'chem_type' => 'rgt_type'
		]
	],
	'biologic_reagent' => [
		'title' => '生物试剂',
		'fields' => [
			'spec' => [
				'field' => 'spec',
				'label' => '规格',
				'type' => 'text'
			],
			'model' => [
				'field' => 'model',
				'label' => '型号',
				'type' => 'text'
			],
			'bio_transport_cond' => [
				'field' => 'transport_cond',
				'label' => '运输条件',
				'type' => 'text'
			],
			'bio_storage_cond' => [
				'field' => 'storage_cond',
				'label' => '保存条件',
				'type' => 'text'
			]
		]
	],
	'consumable' => [
		'title' => '耗材',
	],
	'service' => [
		'title' => '服务',
	],
	'computer' => [
		'title' => '电脑整机',
		'fields' => [
			'model' => [
				'field' => 'model',
				'label' => '型号',
				'type' => 'text'
			],
			'cpu' => [
				'field' => 'cpu',
				'label' => 'CPU',
				'type' => 'text'
			],
			'memory' => [
				'field' => 'memory',
				'label' => '内存',
				'type' => 'text'
			],
			'harddisk' => [
				'field' => 'disk',
				'label' => '硬盘',
				'type' => 'text'
			],
			'video_memory' => [
				'field' => 'video_memory',
				'label' => '显存',
				'type' => 'text'
			],
			'display' => [
				'field' => 'display',
				'label' => '显示器',
				'type' => 'text'
			]
		]
	],
];

//供货时间的配置，sphinx 需要用
$config['supply_time'] = [
	'1' => ['0', '4'],
	'2' => ['4', '7'],
	'3' => ['7', '14'],
	'4' => ['14'],
];

$config['max_order_price'] = 100000;
$config['max_transfer_statement_price'] = 100000;
$config['max_billing_statement_price'] = 100000;

$config['hazardous_control_types'] = ['hazardous','drug_precursor','highly_toxic','explosive','psychotropic','narcotic'];
