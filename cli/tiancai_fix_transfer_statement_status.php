#!/usr/bin/env php
<?php
require_once('base.php');
$draft_status = Transfer_Statement_Model::STATUS_DRAFT;
$tokens = Config::get('site.admin');
foreach ($tokens as $token) {
	$user = O('user',['token'=>$token]);
	if ($user->id) {
		Cache::L('ME', $user);
		break;
	}
}

$transfer_statements = Q("transfer_statement[status={$draft_status}]");
foreach ($transfer_statements as $statement) {
    if (count($statement->pdata)) continue;
    $data = $statement->get_pay_status();
    $return = current($data);
    if ($statement->status == $draft_status && $return['ZT']) {
    	if ($statement->approve()) {
            clean_cache($statement);
    		echo '.';
    	}
    }
}
?>
