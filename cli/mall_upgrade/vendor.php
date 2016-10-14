<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require $base;

// 1. 约定
//      1.1. 将mall-old的vendor同步到mall-hub-vendor: 两边的供应商id必须一致
// 2. 升级的逻辑
//      2.1. 用户升级
//          2.1.1. 升级供应商的owner为gapper-user
//          2.1.2. 为升级的gapper-user创建gapper-group，并绑定mall-hub-vendor APP
//          2.1.3. 存储gapper-group的信息到vendor
//      2.2. 供应商升级
//          2.2.1. 升级基本信息
//          2.2.2. 升级销售类别信息
//          2.2.3. 升级资质图片


// TODO
$appVendorClientID = '594de080abe9486193e3d9a396ffec1d3d5a87d2';

if ($argv && $argv[1]) {
    $argID = $argv[1];
    $vendor = O('vendor', $argID);
    doMe($vendor);
}
else {
    $vendors = Q('vendor');
    foreach ($vendors as $vendor) {
        doMe($vendor);
    }
}

function doMe($vendor)
{
    if ($vendor->upgrade_to_mall_hub_vendor) return;
    list($uid, $gid) = (array)upgradeOwner($vendor);
    if (!$uid || !$gid) {
        echo "{$vendor->id} upgradeOwner升级失败\n";
        return;
    }
    upgradeVendor($vendor);
    echo '.';
}

// 创建一个登录账号为email的用户，并把owner的gapper_user赋值
// 以新用户身份创建一个组，并安装mall-vendor应用
function upgradeOwner($vendor)
{
    global $appVendorClientID;
    $rpc = Gapper::get_RPC();

    // $owner = $vendor->owner;
    // $owner->gapper_user = 0;
    // $owner->save();

    // 创建一个gapper用户，与owner建立关系
    try {
        $uid = $vendor->owner->gapper_user;
        if (!$uid) {
            $vendor_email = $vendor->owner->email ?: $vendor->email;
            $data = $rpc->gapper->user->GetInfo($vendor_email);
            $uid = $data['id'];
            if (!$uid) {
                $data = [
                    'username'=> $vendor_email,
                    'name'=> $vendor->owner->name,
                    'email'=> $vendor_email,
                    'password'=> Misc::random_password(8,2),
                ];

                $uid = $rpc->gapper->user->registerUser($data);
            }
        }
    }
    catch (Exception $e) {
        echo "{$vendor->id} upgradeOwner升级常见用户失败: {$e->getMessage()}\n";
    }

    if (!$uid) return;

    // 创建新组
    try {
        // $vendor->gapper_group = 0;
        // $vendor->save();
        $gid = $vendor->gapper_group;
        if (!$gid) {
            $data = [
                'user'=> (int)$uid,
                'name'=> $rpc->gapper->group->getRandomGroupName('vendor'),
                'title'=> $vendor->name,
            ];
            $gid = $rpc->gapper->group->create($data);
        }
        // 绑定APP
        if ($gid) {
            $rpc->gapper->app->installTo($appVendorClientID, 'group', (int)$gid);
        }
    }
    catch (Exception $e) {
        echo "{$vendor->id} upgradeOwner升级创建组失败: {$e->getMessage()}\n";
    }

    $owner = $vendor->owner;
    $owner->gapper_user = $uid;
    $owner->save();

    if ($gid) {
        $vendor->gapper_group = $gid;
        $vendor->save();
    }

    return [$uid, $gid];

}

//
function upgradeVendor($vendor)
{
    $uid = $vendor->owner->gapper_user;
    $gid = $vendor->gapper_group;
    if (!$uid || !$gid) return;
    try {
        $config = Config::get('mall.hub-vendor');
        $rpc = new RPC($config['api']);
        // $rpc = Gapper::get_RPC();
        $clientId = $config['client_id'];
        $clientSecret = $config['client_secret'];
        $ret = $rpc->mall->authorize($clientId, $clientSecret);
        if (!$ret) {
            echo "{$vendor->id} rpc->mall->authorize升级失败\n";
            return;
        }

        $options = Vendor_Model::$nemployees_options;
        $data = [
            'owner'=> $uid,
            'group'=> $gid,
            //// agreement
            // 'agreement_time'=>
            // 'agreement_version'=>
            //
            'ctime'=> date('Y-m-d H:i:s'),
            'reg_date'=> date('Y-m-d H:i:s', $vendor->establish_date),

            // 应该就是锁定状态
            //'lock'=> 1,

            // 基本信息
            'name'=> $vendor->name,
            'abbr'=> $vendor->short_name,
            'phone'=> $vendor->phone,
            'fax'=> $vendor->fax,
            'email'=> $vendor->email,
            'website'=> $vendor->homepage,
            'address'=> $vendor->address,
            'summary'=> $vendor->description,

            // 法人
            'legal_person_name'=> $vendor->owner_name,
            // 总经理
            'gm_name'=> $vendor->manager_name,
            'gm_phone'=> $vendor->manager_phone,
            // 联系人
            'contact_name'=> $vendor->contact_name,
            'contact_phone'=> $vendor->contact_phone,
            // 银行信息
            'bank_name'=> $vendor->bank_name,
            'bank_account'=> $vendor->bank_account,
            // 经营范围
            'scope'=> $vendor->scope,
            //'scope_expiry_date'=> date('Y-m-d H:i:s', $vendor->establish_date),
            // 'hazardous_article_scope'=> $vendor->hazardous_article_scope
            // 'precursor_scope'=> $vendor->precursor_scope
            'prev_yearly_inspect_date'=> date('Y-m-d H:i:s', $vendor->license_last_valid_date),

            'capital'=> $vendor->capital,
            'employee_count'=> $options[$vendor->nemployees],
            'scanned_copies'=> [
                'license'=> [
                    'serial_no'=> $vendor->license_no,
                    'yearly_inspect_date'=> date('Y-m-d H:i:s', $vendor->license_valid_date),
                    'expiry_date'=> date('Y-m-d H:i:s', $vendor->operation_due),
                ],
                'org_code_cert'=> [
                    'serial_no'=> $vendor->group_no,
                    'yearly_inspect_date'=> date('Y-m-d H:i:s', $vendor->group_valid_date),
                    'expiry_date'=> date('Y-m-d H:i:s', $vendor->group_dto),
                ],
                'regional_tax_reg_cert'=> [
                    'tax_word'=> $vendor->tax_on_land_no,
                ],
                'national_tax_reg_cert'=> [
                    'tax_word'=> $vendor->state_tax_no,
                ],
            ]
        ];

        $hub_vid = $rpc->mall->vendor->createVendor($data);
        if (!$hub_vid) {
            echo "{$vendor->id} rpc->mall->vendor->createVendor升级失败\n";
            return;
        }

        // publish_date: 待审核
        // last_approve_date: 暂停中 
        // 否则就是：未发布
        if ($vendor->publish_date) {
            syncVendorScope($vendor, $rpc);
        }

        $vendor->upgrade_to_mall_hub_vendor = true;
        $vendor->save();

        return true;
    }
    catch(Exception $e) {
        echo "{$vendor->id} upgradeOwner升级数据失败: {$e->getMessage()}\n";
    }
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

// 同步销售类型
function syncVendorScope($vendor, $rpc)
{
    $nodeID = SITE_ID;
    $scopes = prepareScopes($vendor);
    if (!is_array($scopes)) return;
    $rpc->mall->vendor->setVendorNode($vendor->gapper_group, $nodeID, ['scopes'=>$scopes]);
    $rpc->mall->vendor->updateVendor($vendor->gapper_group, [
        'lock'=> 1
    ]);
}
