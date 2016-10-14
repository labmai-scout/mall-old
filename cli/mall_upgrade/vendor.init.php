<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require $base;

$start = 0;
$perpage = 20;
while (true) {
    $results = RVendor_Model::getUnderApproval($start, $perpage);
    if (empty($results['data'])) break;
    $start += $perpage;
    foreach ($results['data'] as $data) {
        $rvgid = $data['group'];
        $rvendor = O('rvendor', $rvgid);
        $rvendor->refreshScopes();
    }
}

$start = 0;
$perpage = 20;
while (true) {
    $results = RVendor_Model::getApproved($start, $perpage);
    if (empty($results['data'])) break;
    $start += $perpage;
    foreach ($results['data'] as $data) {
        $rvgid = $data['group'];
        $rvendor = O('rvendor', $rvgid);
        $rvendor->refreshScopes();
    }
}
