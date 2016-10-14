<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require $base;

$vendors = Q('vendor');
foreach ($vendors as $vendor) {
    $rvendor = O('rvendor', $vendor->gapper_group);
    if (!$rvendor->id) continue;
    $rvendor->refreshScopes();
    echo '.';
}

clean_cache();
