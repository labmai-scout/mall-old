<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require $base;

if ($argv && $argv[1]) {
    $vendor = O('vendor', $argv[1]);
    list($uid, $gid, $scopes) = doCheck($vendor);
    echo "gapper_user: {$uid}\n";
    echo "gapper_group: {$gid}\n";
    echo 'scopes: ' . json_encode($scopes) . "\n";
}
else {
    $vendors = Q('vendor');
    $failed = [];
    foreach ($vendors as $vendor) {
        list($uid, $gid, $scopes) = doCheck($vendor);
        if (!$uid || !$gid) {
            array_push($failed, $vendor->id);
        }
    }
    if (!empty($failed)) {
        echo 'Error Counts: ' . count($failed) . "\n";
        echo json_encode($failed) . "\n";
    }
    else {
        echo 'Good Job!';
    }
}

function doCheck($vendor)
{
    $guid = $vendor->owner->gapper_user;
    $ggid = $vendor->gapper_group;
    $scopes = $vendor->prepareScopes($vendor);
    return [$guid, $ggid, $scopes];
}


function prepareScopes($vendor)
{
    $trans = [
        // product_type.computer
        // product_type.servers
        // product_type.service
        //'product_type.reagent'=> 0,
        'rgt_type.1'=> 'chem_reagent', // 1
        'rgt_type.2'=> 'chem_reagent.drug_precursor', // 2
        'rgt_type.3'=> 'chem_reagent.hazardous', // 4
        'product_type.biologic_reagent'=> 'bio_reagent', // 8
        'product_type.consumable'=> 'consumable' // 16
    ];
    $scopes = Q("vendor_scope[vendor={$vendor}]");
    $scs = [];
    foreach ($scopes as $scope) {
        if (!isset($trans[$scope->name])) continue;
        $scs[$trans[$scope->name]] = [
            'from'=> date('Y-m-d H:i:s', $scope->expire_date_from),
            'to'=> date('Y-m-d H:i:s', $scope->expire_date)
        ];
    }
    if (!count($scs)) return;

    $data = [];
    if ($vendor->approve_date) {
        foreach ($scs as $k=>$v) {
            if ($v['to'] > date('Y-m-d H:i:s')) {
                if ($v['from']==$v['to']) {
                    $v['from'] = date('Y-m-d H:i:s', strtotime($v['from']) - 1000);
                }
                $data[$k] = [
                    'status'=> 'approved',
                    'valid_period'=> [
                        $v['from'], $v['to']
                    ]
                ];
            }
            else {
                $data[$k] = [
                    'status'=> 'rejected',
                    'expiry_date'=> $v['to'],
                    'reason'=> '允许销售日期至'.$v['to'].', 已经过期'
                ];
            }
        }
    }
    else {
        foreach ($scs as $k=>$v) {
            $data[$k] = [
                'status'=> 'applying'
            ];
        }
    }

    return $data;
}
