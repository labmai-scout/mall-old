<?php
/**
 * @author Jinlin Li jinlin.li@geneegroup.com
 */
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
$user = Q('user')->current();
$customers = Q("customer[!gapper_group]");
$labs = [];
echo "(customer_id): (lab_id)\n";
foreach ($customers as $customer) {
	$lab_id = $customer->lab_id;
	if (!$lab_id) {
		fecho('customer: '.$customer->id.' 没有找到对应的lims站点');
		continue;
	}
	$labs[$customer->id] = $lab_id;
}
foreach ($labs as $key => $value) {
	echo $key.': '.$value."\n";
}
function fecho($message) {
	echo "\033[31m".$message."\033[0m \n";
}
function secho($message) {
	echo "\033[32m".$message."\033[0m \n";
}
?>