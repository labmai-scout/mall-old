#!/usr/bin/env php
<?php
/*
* -- delete_product
* TODO 更新词库相关，对应的词库为当初安居客的词库形式，如果要合并到robbe需要做一些处理
*/
include "base.php";

// SITE_ID=nankai php update_dic.php -f add -h --function delete -a --auto -h --history


$shortopts = 'f:ah';
$longopts = array(
	'function:',
	'auto',
	'history'
	);
$opts = getopt($shortopts, $longopts);

$delete_auto = isset($opts['a']) ? : isset($opts['auto']) ? : Config::get('dic.auto_delete_dic', TRUE);
$need_history = isset($opts['h']) ? : isset($opts['history']) ? : Config::get('dic.need_history', TRUE);
$function = $opts['f'] ?: $opts['function'];

if ($function == 'add') {
	$function .= '_dict';
	$function($need_history);
}
elseif ($function == 'delete') {
	$function .= '_dict';
	$function($need_history, $delete_auto);	
}
elseif (!$function) {
	add_dict($need_history);
	delete_dict($need_history, $delete_auto);
}
else {
	echo('必须提供 f 的正确参数');
	return;
}

function add_dict($need_history = TRUE) {

	$add_path = Config::get('dic.add_dic_path');
	$add_path_history = Config::get('dic.add_dic_path_history');
	$fp = fopen($add_path, 'r');

	if ($need_history) {
		$fhp = fopen($add_path_history, 'w+');
	}

	while (!feof($fp)) {	
		$buffer = fgets($fp);
		if (!$buffer) continue;
		if ($need_history) fwrite($fhp, trim($buffer)."\n");
		$arr = explode(' ', trim($buffer));
		$num = $arr[0];
		$word = $arr[1];
		if (mb_strlen($word) > 4) continue; //现有机制只支持最长四个字
		$seg_word = Mmseg::execute($word);
		
		$write_dic = Config::get('dic.write_dic');
		$fo=fopen($write_dic,"a" );  //写入update.dic词库
		fwrite($fo, trim($buffer)."\n");
		fclose($fo);

		$seg_word = trim($seg_word);
		if (strlen($seg_word) == strlen($word)) continue; //如果分词前后长度一致，表明词库中已有该词，不做处理
		
		$opts['name'] = $seg_word;
		$opts['option_sql'] = "OPTION ranker=expr('sum(lcs)')";
		$opts['type'] = 'reagent';
		$products = new Search_Product($opts);

		if (count($products) == 0) continue; //没有匹配就不做处理了

		foreach ($products as $product) {
			$GLOBALS['preload']['mall_dic'] = FALSE;
			Search_Product::update_index($product);
		}
	}
	unlink($add_path);
	fclose($fp);
	$fp1 = fopen($add_path, "w+");
	fclose($fp1);
	if ($need_history) fclose($fhp);
}

function delete_dict($need_history = TRUE, $delete_auto = TRUE) {

	$delete_path = Config::get('dic.delete_dic_path');
	$delete_path_history = Config::get('dic.delete_dic_path_history');

	$fp = fopen($delete_path, 'r');
	if ($need_history) {
		$fhp = fopen($delete_path_history, 'w+');
	}

	while(!feof($fp)) {
		
		$buffer = trim(fgets($fp));
		if (!$buffer) continue;
		$arr = explode(' ', $buffer);
		$word = $arr[1];
		if (mb_strlen($word) > 4) continue; //现有机制只支持最长四个字

		if ($need_history) fwrite($fhp, $buffer."\n"); //写入历史删除的历史词库中

		if ($delete_auto) { //如果是自动删除字库,则需要每个词逐条写入dic中

			$dics = Config::get('dic.mall_dics');
			foreach ($dics as $dic) { //逐个词库读取 生成删除词后的词库
				
				//创建临时词库
				$temp_file = Config::get('dic.tmp_dic');
				$ftemp = fopen($temp_file, "w+");

				$lines = file($dic);
				$rewrite = FALSE;

				foreach ($lines as $line) {
					if (strpos($line, $buffer) !== false) {
						$rewrite = TRUE;
					}
					else {
						fwrite($ftemp, trim($line)."\n");
					}
				}

				fclose($ftemp); //写入完毕  update.dic => update.dic.bak temp.dic => update.dic
				if ($rewrite) {
					rename($dic, $dic.'.bak');
					rename($temp_file, $dic);
					unlink($dic.'.bak');
				}
			}
			$preload_everytime = TRUE;
		}
		else {
			$preload_everytime = FALSE;
			//这时候我们认为用户已经手动将这个词在词库中删除了
		}

		$opts['name'] = $word;
		$opts['option_sql'] = "OPTION ranker=expr('sum(lcs)')";
		$opts['type'] = 'reagent';

		$products = new Search_Product($opts);
		if (count($products) == 0) continue; //没有匹配就不做处理了

		foreach ($products as $product) {
			if ($preload_everytime) {
				$GLOBALS['preload']['mall_dic'] = FALSE;
			}
			Search_Product::update_index($product);
		}

	}

	fclose($fp);
	unlink($delete_path);
	$fp2 = fopen($delete_path, "w+"); //新建一个空的delete.dic
	fclose($fp2);
	if ($need_history) fclose($fhp);
}

?>