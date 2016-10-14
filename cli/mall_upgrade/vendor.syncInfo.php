<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require $base;


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

$vendors = Q('vendor');
foreach ($vendors as $vendor) {
    $rvendor = O('rvendor', $vendor->gapper_group);
    if (!$rvendor->id) continue;
    doSync();
}

clean_cache();

function doSync() {

    global $vendor;
    global $rvendor;
    global $needSave;

    $needSave = false;
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
    if ($needSave) {
        $bool = $vendor->save();
        echo $bool ? '.' : 'x';
    }
}

