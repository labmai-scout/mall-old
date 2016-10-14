<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
$customer_id = (int)$argv[1];
if ($customer_id) {
	$customers = Q("customer[!gapper_group][id=$customer_id]");
}
else {
	die;
	// $customers = Q('customer[!gapper_group]');
}
$arr = [];
foreach ($customers as $customer) {
	//测试买方
	if ($customer->id == 1015) continue;
	logMe($customer->name.'('.$customer->id.')');
	$lab_id = $customer->lab_id;
	if (!$lab_id) {
		fecho('customer: '.$customer->id.' 没有找到对应的lims站点');
		continue;
	}
	// 用户升级为 gapper 用户
	$ret = upgradeCustomerMembers($customer);
	if (!$ret) {
		fecho('用户升级中断');
		die;
	}
	// 买方升级为 gapper 组
	$group_id = upgradeCustomer($customer);
	if (!$group_id) {
		fecho('组升级中断');
		die;
	}
	$arr[$group_id] = $lab_id;
}


function upgradeCustomerMembers($customer) {
	$members = Q("$customer<member user[!gapper_user]");
	$ms = Q("$customer<member user")->to_assoc('id', 'name');
	if (!count($members)) return true;
	$rpc = Gapper::get_RPC();
	foreach ($members as $user) {
		usleep(100);
		$token = $user->token;
		$email = $user->email;
		list($stoken,$backend) = Auth::parse_token($token);
		$data = $rpc->gapper->user->GetInfo($email);
		if (is_array($data) && $data['id']) {
			$ugapperId = (int)$data['id'];
			$ret = true;
			$u = O('user', ['gapper_user'=>$ugapperId]);
			if (!$u->id) {
				$user->gapper_user = (int)$data['id'];
				$user->save();
				if ($backend == 'ids.nankai.edu.cn') {
					if (Gapper::link_identity($user, $user->token)) {
						secho('用户: '.$user->name.'('.$user->token.'): 升级绑定成功');
					}
					else {
						fecho('用户: '.$user->name.'('.$user->token.'): 升级绑定失败');
					}
				}
				else {
					secho('用户: '.$user->name.'('.$user->token.'): 升级成功');
				}
			}
			else {
				$orders = Q("order[purchaser={$user}][customer={$customer}]");
				// 如果该用户没有相关订单, 升级忽略该用户
				// 如果该用户有相关订单并且mall中重叠的
				if (!count($orders)) {
					// ignore
				}
				else {
					foreach ($orders as $order) {
						$order->purchaser = $u;
						$order->save();
					}
					// fecho('用户: '.$user->name.'('.$user->token.'): 邮箱对应的 gapper 用户在 mall 中已存在，但不是他, 是'.$u->name.'('.$u->token.")");
					if (array_key_exists(!$u->id, $ms)) {
						$customer->connect($u, 'member');
						secho($u->token.' 用户加入到组中');
					}
				}
			}
		}
		else {
			$uid = $rpc->gapper->user->registerUser([
				'username'=> $user->email,
				'password'=> rand(10000000,99999999),
				'name'=> $user->name,
				'email'=> $user->email
			]);
			$user->gapper_user = $uid;
			if ($backend == 'ids.nankai.edu.cn') {
				if (Gapper::link_identity($user, $user->token)) {
					secho('用户: '.$user->name.'('.$user->token.'): 升级绑定成功');
				}
				else {
					fecho('用户: '.$user->name.'('.$user->token.'): 升级绑定失败');
				}
			}
			else {
				secho('用户: '.$user->name.'('.$user->token.'): 升级成功');
			}
			$ret = true;
		}

		if (!$ret) {
			fecho($user->name.'('.$user->token.'): 升级失败');
			return false;
		}
	}
	return true;
}

function sync_members($customer) {
	$rpc = Gapper::get_RPC();
	usleep(100);
	$members = Q("$customer<member user");
	$gapper_group = $customer->gapper_group;
	if (!$gapper_group) return false;
	foreach ($members as $member) {
		try {
			if (!$member->gapper_user) {
				continue;
			};
			$ret = $rpc->gapper->group->ADDMember((int)$gapper_group, (int)$member->gapper_user);
			if ($ret) {
				secho('['.$customer->id.']'.$member->token.'加入gapper group ('.$gapper_group.') 成功');
			}
			else {
				fecho('['.$customer->id.']'.$member->token.'加入gapper group ('.$gapper_group.') 失败 添加失败');
			}
		}
		catch(Exception $e) {
			$message = $e->getMessage();
			fecho($customer->id.':'.$message);
		}
	}
	return $ret;
}

function upgradeCustomer($customer) {
	$ret = false;
	$LO_clientID = Config::get('gapper.apps')['lab-orders']['client_id'];
	$LG_clientID = Config::get('gapper.apps')['lab-grants']['client_id'];
	$LI_clientID = Config::get('gapper.apps')['lab-inventory']['client_id'];
	$DP_clientID = Config::get('gapper.apps')['drug-precursor-plan']['client_id'];
	//todo 一定要配置!
	if (!$LO_clientID || !$LG_clientID || !$LI_clientID || !$DP_clientID) return false;
	$rpc = Gapper::get_RPC();
	$data = [
		'name'  => $rpc->gapper->group->getrandomgroupname(str_replace(' ', '',PinYin::code($customer->name))),
		'title' => $customer->name,
		'user'  => (int)$customer->owner->gapper_user,
	];

	$group_id = $rpc->gapper->group->create($data);
	$customer->gapper_group = $group_id;
	if ($group_id && $customer->save()) {
		$ret = true;
		secho('创建组成功: '.$group_id);
		if (sync_members($customer)) {
			secho('组成员同步成功!');
			if ($rpc->gapper->app->installTo($LO_clientID, 'group', (int)$group_id)) {
				secho('lab-orders 应用创建成功!');
			}
			else {
				$ret = false;
				fecho('lab-orders 应用创建失败!');
			}
			if ($rpc->gapper->app->installTo($LG_clientID, 'group', (int)$group_id)) {
				secho('lab-grants 应用创建成功!');
			}
			else {
				$ret = false;
				fecho('lab-grants 应用创建失败!');
			}
			if ($rpc->gapper->app->installTo($LI_clientID, 'group', (int)$group_id)) {
				secho('lab-inventory 应用创建成功!');
			}
			else {
				$ret = false;
				fecho('lab-inventory 应用创建失败!');
			}
			if ($rpc->gapper->app->installTo($DP_clientID, 'group', (int)$group_id)) {
				secho('nankai-drug-precursor-plan 应用创建成功!');
			}
			else {
				$ret = false;
				fecho('nankai-drug-precursor-plan 应用创建失败!');
			}
		}
		else {
			$ret = false;
			fecho('组成员同步失败!');
		}
	}
	else {
		$ret = false;
		secho('组创建失败!');
	}
	return $ret;
}


function fecho($message) {
	echo "\033[31m".$message."\033[0m \n";
}
function secho($message) {
	echo "\033[32m".$message."\033[0m \n";
}
function logMe($message) {
    echo $message . "\n";
}