#!/usr/bin/env php
<?php
require 'base.php';
$shortopts = "v:p:n:r:";
$opts = getopt($shortopts);
$vid  = $opts['v'];
$path = $opts['p'];
$name = $opts['n'];
$raw_name = $opts['r'];

$ret = true;

if (!$vid || !$path || !$name || !$raw_name) {
	$ret = false;
}

if (!file_exists($path)) {
	$ret = false;
}

$vendor = O('vendor', $vid);
if (!$vendor->id) {
	$ret = false;
}

$vendor->product_uploading = TRUE;
$vendor->upload_result = [
'result' => true,
'summary' => '数据分析中, 请耐心等候',
];
$vendor->save();
if ($ret) {
	// 文件转换为csv文件
	$new_path = Config::get('product.upload_path').$vid.'/'.$name.'/';
	// 环境部署 安装xlsx2csv
	//todo use php libaraies
	exec("xlsx2csv -a -i $path $new_path");
	// csv文件转换成功, 诊断是否合法
	$infos = [];
	if (file_exists($new_path)) {
		// 拿到所有的文件
		// 遍历目录下对应的sheet生成的csv, sheet命名规范为 生物试剂-数字(ps 生物试剂-1)
		$filesnames = array_diff(scandir($new_path), ['.','..']);
		$summary = $raw_name.": \n";
		$record_items = [];
		foreach ($filesnames as $filename) {
			$new_full_path = $new_path.$filename;
			$csv_import = new CSV($new_full_path, 'r');
			$sheet_name = str_replace('.csv', '', $filename);
			if (in_array($sheet_name, ['化学试剂','生物试剂','耗材'])) {
			//if (preg_match('/^(化学试剂|生物试剂|耗材){1}/', $sheet_name, $matches)) {

				$params = [
					'sheet_name' => $sheet_name,
					'raw_name' => $raw_name,
					'file_path' => $new_full_path,
				];
				$type = '';
				switch ($sheet_name) {
					case '化学试剂':
						$type = 'reagent';
						break;
					case '生物试剂':
						$type = 'biologic_reagent';
						break;
					case '耗材':
						$type = 'consumable';
						break;
					case '说明':
						continue;
						break;
					default:
						$result = false;
						$summary .= "存在异常的表单名, 系统无法识别上传类别:$sheet_name. \n";
						break;
				}
				if ($type) {
					$result = diagnose_file($type, $params, $summary);
				}

				if (!$result) {
					$ret = FALSE;
				}
				elseif ($result == 'empty') {
					continue;
				}
				else {
					$infos[] = [
						'type' => $type,
						'sheet_name' => $sheet_name,
						'path' => $new_full_path,
						'summary' => $result,
					];
				}

			}
			else {
				$summary .= "存在异常的表单名, 系统无法识别上传类别:$sheet_name. \n";
			}
		}

		if (!count($infos)) {
			$ret = FALSE;
			$summary .= "您上传的表数据都是空的, 请检查上传文件\n";
		}

		if ($ret) {
			foreach ($infos as $info) {
				$record = O('product_upload_record');
				$record->type = $info['type'];
				$record->path = $info['path'];
				$record->file_name = $raw_name;
				$record->sheet_name = $info['sheet_name'];
				$record->vendor = $vendor;
				$record->summary = $info['summary'];
				$record->ctime = Date::time();
				if (!$record->save()) {
					$ret = FALSE;
				}
			}

			if ($ret) {
				$summary = "上传成功\n".$summary;
				$vendor->product_uploading = FALSE;
			}
			else {
				$summary = "上传失败\n".$summary;
			}
		}
		else {
			$summary = "导入失败\n".$summary;
			foreach ($filesnames as $filename) {
				unlink($new_path.$filename);
			}
			rmdir($new_path);
		}
		// 删除对应的xlsx文件
		unlink($path);
		$upload_result = [
			'result' => $ret,
			'summary' => $summary,
		];
		$vendor->upload_result = $upload_result;
		$vendor->save();
	}
}
$vendor->product_uploading = false;
$vendor->save();

function diagnose_file($type, $params, &$summary) {
	$ret = TRUE;
	$raw_name = $params['raw_name'];
	$csv_file = $params['file_path'];
	$sheet_name = $params['sheet_name'];
	$head_formats = [
		'reagent' => [
			'商品名称*',
			'生产商*',
			'品牌*',
			'货号*',
			'规格*',
			'包装*',
			'型号',
			'分类',
			'化学试剂性质*',
			'常用危险化学品分类',
			'化学试剂 CAS 号',
			'化学试剂英文名',
			'化学试剂别名',
			'化学试剂分子式',
			'化学试剂分子量',
			'商品单价*',
			'库存*',
			'供货时间*',
			'商品简介',
			'关键字',
			'商品备注',
			'操作类别*',
			],
		'biologic_reagent' => [
            '商品名称*',
            '生产商*',
            '品牌*',
            '货号*',
            '规格*',
            '包装*',
            '生物试剂分类代码*',
            '型号',
            '商品单价*',
            '库存*',
            '供货时间*',
            '商品简介',
            '运输条件',
            '保存条件',
            '关键字',
            '商品备注',
            '操作类别*',
		],
		'consumable' => [
			'商品名称*',
			'英文名称',
			'生产商*',
			'品牌*',
			'货号*',
			'规格*',
			'包装*',
			'型号',
			'商品单价*',
			'库存*',
			'供货时间*',
			'商品简介',
			'关键字',
			'商品备注',
			'操作类别*',
		],
	];
	if (in_array($type, $head_formats)) {
		return FALSE;
	}
	$head_format = $head_formats[$type];
	$csv_import  = new CSV($csv_file, 'r');
	$head_column = $csv_import->read();
	foreach ($head_column as $key => $value) {
		$head_column[$key] = trim($value);
	}
	$data = [
		'head_format' => $head_format,
		'head_column' => $head_column,
		'raw_name'    => $raw_name,
		'sheet_name'  => $sheet_name,
	];
	$ret = diagnose_default_format($summary, $data);
	if ($ret) {
		$rule = $ret;
	}
	// 编码检测
	if (!mb_check_encoding(file_get_contents($csv_file), 'UTF-8')) {
    	$ret = FALSE;
    	$summary .=  T("[ $sheet_name ] 编码格式错误, 应该为 UTF-8\n");
	}

	// 必填项以及字符类型判断及类型统计
	// 如果以及检测出必填项有没有填写, 就不在记录这种错误了
	$require_checked = $price_has_error = $stock_status_has_error =
	$supply_time_has_error = $cas_no_has_error = FALSE;
	$create_num = $unpublish_num = $delete_num = 0;

	if ($type == 'reagent') {
		$require_indexes = [0,1,2,3,4,5,8,15,16,17,21];
		$supply_time_index = 17;
		$stock_status_index = 16;
		$unit_price_index = 15;
		$action_index = 21;
	}
	elseif ($type == 'biologic_reagent') {
		$require_indexes = [0,1,2,3,4,5,6,8,9,10,16];
		$supply_time_index = 10;
		$stock_status_index = 9;
		$unit_price_index = 8;
		$action_index = 16;
	}
	elseif ($type == 'consumable') {
		$require_indexes = [0,2,3,4,5,6,8,9,10,14];
		$supply_time_index = 10;
		$stock_status_index = 9;
		$unit_price_index = 8;
		$action_index = 14;
	}
	$line = 0;
	$keys = [];
	$error_cas_no_lines = [];
	while($row = $csv_import->read()) {
	    foreach ($row as $key => $cell) {
	        $row[$key] = trim_cell($row[$key]);
	    }
		if (count($row)) {
			$line++;
		}
		foreach($row as $key => $cell) {
			$row[$key] = trim_cell($row[$key]);
		}
		if (!$rule) continue;
		$row = convert_row($row, $rule);
		if (is_numeric($key = check_require($row, $require_indexes))) {
			$keys[$key] = $key;
		}
		else {
			if (!$cas_no_error && $type == 'reagent' && strlen(trim($row[10])) && !preg_match('/^\d{2,7}-\d{2}-\d$/', $row[10])) {
				$error_cas_no_lines[] = $line;
				$cas_no_has_error = TRUE;
			}
			if (!$price_has_error) {
				if (!is_numeric($row[$unit_price_index])) {
					$price_has_error = TRUE;
				}
				elseif ($row[$unit_price_index] < 0 && $row[$unit_price_index] != -1) {
					$price_has_error = TRUE;
				}

			}
			if (!$supply_time_has_error) {
				if (!is_numeric($row[$supply_time_index])) {
					$supply_time_has_error = TRUE;
				}
				elseif ($row[$supply_time_index] < 0) {
					$supply_time_has_error = TRUE;
				}
			}
			if (!$stock_status_has_error && !in_array($row[$stock_status_index], Product_Model::$stock_status)) {
				$stock_status_has_error = TRUE;
			}
			if ($action = $row[$action_index]) {
				if (!$action_has_error && !in_array($action, ['上架','下架','删除'])) {
					$action_has_error = TRUE;
				}
				calculate_action($action, $create_num, $unpublish_num, $delete_num);
			}
		}
	}
	if (count($keys)) {
		$summary .= "[ $sheet_name ] 存在必填项没填写: ";
		foreach ($keys as $value) {
			$summary .= $head_formats[$type][$value].' ';
		}
		$summary .= "\n";
		$ret = FALSE;
	}

/*
	if ($line == 0) {
		$ret = FALSE;
		$summary .=  T("[ $sheet_name ] 空表请删除\n");
	}
*/
	if ($price_has_error) {
		$ret = FALSE;
		$summary .=  T("[ $sheet_name ] 商品单价格式不正确\n");
	}
	if ($supply_time_has_error) {
		$ret = FALSE;
		$summary .=  T("[ $sheet_name ] 供货时间格式不正确\n");
	}
	if ($stock_status_has_error) {
		$ret = FALSE;
		$summary .=  T("[ $sheet_name ] 库存格式格式不正确\n");
	}
	if ($cas_no_has_error) {
		$ret = FALSE;
		$summary .=  T("[ $sheet_name ] CAS 号格式不正确\n");
		$summary .= implode(',', $error_cas_no_lines)." 行数据有问题\n";
	}

	if ($action_has_error) {
		$ret = FALSE;
		$summary .=  T("[ $sheet_name ] 操作类别填写有误, 目前支持上架、下架、删除\n");
	}

	$calculate = "[ $sheet_name ] 上架: $create_num 个, 下架: $unpublish_num 个, 删除: $delete_num 个\n";
	$summary .= $calculate;
	if ($line == 0) {
		$calculate = 'empty';
	}
	return $ret?$calculate:FALSE;
}

function check_require($row, $require_indexes) {
	foreach ($require_indexes as $index) {
		if (!$row[$index]) {
			return $index;
		}
	}
	return FALSE;
}

function diagnose_default_format(&$summary, $data) {
	$ret = TRUE;
	$head_format = $data['head_format'];
	$head_column = $data['head_column'];
	$raw_name    = $data['raw_name'];
	$sheet_name  = $data['sheet_name'];
	/*
	if (count($head_column) != count($head_format)) {
	    $ret = FALSE;
	    $summary .=  T("[ $sheet_name ] 格式数不正确，请检查 csv 文件\n");
	}
	*/
	// 表头检测
	$rule = [];
	foreach ($head_format as $key => $column) {
		if (strlen($hkey = array_search($column, $head_column))) {
			$rule[$key] = $hkey;
	    }
	    else {
	    	$ret = FALSE;
	    	$summary .= "[ $sheet_name ] $column 列不存在, 请检查上传文件!\n";
	    }
	}
	if ($ret) {
		$ret = $rule;
	}
	return $ret;
}

function convert_row($row, $rule) {
	$new_row = [];
	foreach ($rule as $k => $v) {
		$new_row[$k] = $row[$v];
	}
	return $new_row;
}

function calculate_action($action, &$create_num, &$unpublish_num, &$delete_num) {
	if ($action == '上架') {
		$create_num++;
	}
	elseif ($action == '下架') {
		$unpublish_num++;
	}
	elseif ($action == '删除') {
		$delete_num++;
	}
}

function trim_cell($str) {
    $format = chr(0xC2).chr(0xA0);
    if (strpos($str, $format) !== FALSE) {
        $str = str_replace($format, ' ',$str);
    }
    return trim($str);
}
?>
