<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require $base;

// 同步供应商基本信息

$vid = $argv[1];
if (!$vid) {
    echo "请输入vendor id\n";
    exit;
}

$vendor = O('vendor', $vid);
if (!$vendor->id || !$vendor->gapper_group) {
    echo "供应商信息不正常\n";
    exit;
}

$rvendor = O('rvendor', $vendor->gapper_group);
if (!$rvendor->id) {
    echo "远程供应商信息不正常\n";
    exit;
}

$needSave = false;
function compare($rkey, $key)
{
    global $vendor;
    global $rvendor;
    global $needSave;

    if ($vendor->$key!=$rvendor->$rkey) {
        $vendor->$key = $rvendor->$rkey;
        $needSave = true;
    }
}

compare('name', 'name');
compare('abbr', 'short_name');
compare('phone', 'phone');
compare('fax', 'fax');
compare('email', 'email');
compare('website', 'homepage');
compare('address', 'address');
compare('summary', 'description');
compare('legal_person_name', 'owner_name');
compare('gm_name', 'manager_name');
compare('gm_phone', 'manager_phone');
compare('contact_name', 'contact_name');
compare('contact_phone', 'contact_phone');
compare('bank_name', 'bank_name');
compare('bank_account', 'bank_account');
compare('scope', 'scope');
compare('capital', 'capital');

if ($vendor->license_no!=$rvendor->scanned_copies['license']['serial_no']) {
    $needSave = true;
    $vendor->license_no = $rvendor->scanned_copies['license']['serial_no'];
}

if ($needSave) {
    if ($vendor->save()) {
        echo 'Done';
    }
    else {
        echo 'Save Failed';
    }
}
else {
    echo '不需要更新';
}

echo "\n";
