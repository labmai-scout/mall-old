<?php
/**
* @file contract_customer_upgrade.php
* @brief 升级合同用户以便于其使用易制毒应用
* @author Jinlin Li <jinlin.li@geneegroup.com>
* @version 0.1.0
* @date 2015-09-09
 */

$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
clean_cache();
$confs = Config::get('rpc.servers');
$plan_client_id = $confs['drug-precursor-plan']['client_id'];
$rpc = Gapper::get_RPC();
$customers = Q('customer[unable_upgrade][!gapper_group]');
$sources = Config::get('gapper.sources');
foreach ($customers as $customer) {
    logMe('customer:'.$customer->id);
    $owner = $customer->owner;
    $email = $owner->email;
    $group_name = $customer->name;
    $gapper_user = $owner->gapper_user;
    $token = $owner->token;
    if (!$gapper_user) {
        logMe('owner no gapper user');
        $data = $rpc->gapper->user->GetInfo($email);
        //该邮箱已注册, 但是没有linkidentity 则进行连接
        if ($data['id']) {
            logMe('but gapper had him');
            list($stoken,$backend) = Auth::parse_token($token);
            $source = $sources[$backend];
            if ($source) {
                if ($rpc->gapper->user->linkIdentity((int)$data['id'], $source, $stoken)) {
                    logMe('link gapper user success');
                }
            }
            $owner->gapper_user = (int)$data['id'];
            $owner->save();
        }
        else {
            logMe('gapper had not him yet');
            $uid = $rpc->gapper->user->registerUser([
                'username'=> $email,
                'password'=> rand(10000000,99999999),
                'name'=> $owner->name,
                'email'=> $email
            ]);
            if ($uid) {
                $owner->gapper_user = $uid;
                Gapper::link_identity($owner, $owner->token);
                logMe('create gapper user fail');
                $owner->save();
            }
            else {
                logMe('create gapper user fail');
                exit;
            }
        }
    }
    else {
        logMe('owner has gapper user');
    }


    $gapper_user = $owner->gapper_user;
    if (!$gapper_user) exit;
    $results = $rpc->gapper->user->GetGroups((int)$gapper_user);
    $data = [
        'name'  => $rpc->gapper->group->getrandomgroupname(str_replace(' ', '',PinYin::code($customer->name))),
        'title' => $customer->name,
        'user'  => (int)$owner->gapper_user,
    ];
    $group_id = $rpc->gapper->group->create($data);
    if ($group_id) {
        logMe('create group as creator is this owner success');
        $customer_group = O('customer_group', ['customer'=>$customer, 'gapper_group'=>$group_id]);
        if (!$customer_group->id) {
            $customer_group->customer = $customer;
            $customer_group->gapper_group = (int)$group_id;
            if ($customer_group->save()) {
                logMe('create gapper group customer relationship success');
            }
            else {
                logMe('create gapper group customer relationship fail');
            }
        }

        if ($rpc->gapper->app->installTo($plan_client_id, 'group', (int)$group_id)) {
            logMe('this group add app plan success O(∩_∩)O~');
        }
        else {
            logMe('this group add app plan fail ಥ_ಥ');
        }
    }
}
$customers = Q("customer[gapper_group]");
foreach ($customers as $customer) {
    if ($rpc->gapper->app->installTo($plan_client_id, 'group', (int)$customer->gapper_group)) {
        logMe('this group add app plan success O(∩_∩)O~');
    }
    else {
        logMe('this group add app plan fail ಥ_ಥ');
    }
}

function logMe($msg)
{
    echo $msg . "\n";
}