<?php
include 'base.php';
/*
* 生成付款单的pdf文件
*/

$shortopts = "s:n:";
$longopts = array(
	'statement:',
	'name:',
	);

$opts = getopt($shortopts, $longopts);

$sids = $opts['s'] ?: $opts['statement'];

if(!$sids){
	die("必须提供[-s|--statement]付款单id\n");
}

$pdf_name = $opts['n'] ?: $opts['name'];
if(!$pdf_name){
	die("必须提供[-n|--name]pdf名称\n");
}


$autoload = ROOT_PATH.'vendor/autoload.php';
if(file_exists($autoload)) require_once($autoload);
$pdf = new TCPDF;

$sids_array = array_unique(explode(',', $sids));

$path = Config::get('system.tmp_dir').'pdf/';
$file = $path.$pdf_name;

File::check_path($file);
$keep_alive = $file.'.keep';

$output = FALSE;

foreach($sids_array as $sid){
	clearstatcache();
	$s = @stat($keep_alive);
	$mtime = $s['mtime'];
	if(time() - $mtime > 3){
		$output = FALSE;
		break;
	}

	$statement = O('billing_statement', $sid);
    if(!$statement->id) continue;
	Financial::pdf_content($statement, $pdf);
	$output = TRUE;
}


if($output) {
	$pdf->Output($file, 'F');
}
if(file_exists($keep_alive)) unlink($keep_alive);



