<?php
/*
 * SITE_ID=nankai php user.php customer_id
 */
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
$customer_id = (int)$argv[1];
if (!$customer_id) {
	fecho('请输入买方 ID');
	die;
}

$customer = O("customer", $customer_id);
if (!$customer->id) {
	fecho('该买方不存在');
	die;
}

if ($customer->gapper_group) {
	fecho('该买方已升级');
	die;
}

if (!$customer->owner->id) {
	fecho('买方负责人不存在');
	die;
}

$lab_order_client_id =  Config::get('gapper.apps')['lab-orders']['client_id'];
if (!$lab_order_client_id) {
	fecho('lab orders client id not exists die!!!!');
	return FALSE;
}
die;

// 买方成员升级为 gapper 用户
$members = Q("$customer<member user");
$rpc = Gapper::get_RPC();
foreach ($members as $user) {
	if (!$user->gapper_user) {
		$token = $user->token;
		list($stoken,$backend) = Auth::parse_token($token);
		if ($backend == 'ids.nankai.edu.cn') {
            $uid = $rpc->gapper->user->registerUser([
                'username'=> $user->email,
                'password'=> rand(10000000,99999999),
                'name'=> $user->name,
                'email'=> $user->email
            ]);
            $user->gapper_user = $uid;
            if ($uid && Gapper::link_identity($user, $user->token) && $user->save()) {
            	echo '.';
            }
		}
	}
}

// 创建对应的组
$rpc = Gapper::get_RPC();
$group_name = $customer->name;
$data = [
	'name'  => $rpc->gapper->group->getrandomgroupname(str_replace(' ', '',PinYin::code($customer->name))),
	'title' => $customer->name,
	'user'  => (int)$customer->owner->gapper_user,
];

$group_id = $rpc->gapper->group->create($data);
$customer->gapper_group = $group_id;
if ($group_id && $customer->save()) {
	secho('创建组成功!');
	$rpc->gapper->app->installTo($lab_order_client_id, 'group', (int)$group_id);
	// 创建组成功后将用户加入这个组中
	sync_members($customer);
}
else {
	secho('组创建失败!');
	die;
}

function sync_members($customer) {
	global $rpc;
	$members = Q("$customer<member user");
	$gapper_group = $customer->gapper_group;
	if (!$gapper_group) return false;
	try {
		$data = $rpc->gapper->group->getInfo((int)$gapper_group);
		if (!$data) {
			$customer->gapper_group = 0;
			$customer->save();
			fecho($customer->id.' gapper group 在gapper server 不存在!');
			return false;
		}
	}
	catch (Exception $e) {
		$message = $e->getMessage();
		fecho($customer->id.':'.$message);
	}
	foreach ($members as $member) {
		try {
			if (!$member->gapper_user) {
				fecho('['.$customer->id.']'.$member->id.'加入gapper group ('.$gapper_group.') 失败 非gapper用户');
				continue;
			};
			$ret = $rpc->gapper->group->ADDMember((int)$gapper_group, (int)$member->gapper_user);
			if ($ret) {
				secho('['.$customer->id.']'.$member->id.'加入gapper group ('.$gapper_group.') 成功');
			}
			else {
				fecho('['.$customer->id.']'.$member->id.'加入gapper group ('.$gapper_group.') 失败 添加失败');
			}
		}
		catch(Exception $e) {
			$message = $e->getMessage();
			fecho($customer->id.':'.$message);
		}
	}
}

function fecho($message) {
	echo "\033[31m".$message."\033[0m \n";
}
function secho($message) {
	echo "\033[32m".$message."\033[0m \n";
}
