<?php
/**
* @file customer.php
* @brief 升级买方
* @author PiHiZi <pihizi@msn.com>
* @version 0.2.0
* @date 2015-05-07
 */

// TODO 允许只升级指定的买方

$base = dirname(dirname(__FILE__)). '/base.php';
require($base);

// 需要安装了nankai-lab-orders应用并且配置正确！
$lab_order_client_id =  Config::get('gapper.apps')['lab-orders']['client_id'];
if (!$lab_order_client_id) {
	fecho('lab orders client id not exists die!!!!');
	return FALSE;
}

$rpc = Gapper::get_RPC();
$customers = Q("customer[unable_upgrade=0]");
$fail_file = getCSVFile('mall.upgrade.customer.fail.back' . time());
$fail_csv = new CSV($fail_file, 'w');

function getWhiteList($type)
{
    $file = dirname(__FILE__) . '/data/' . $type;
    if (!file_exists($file)) return;
    $content = file_get_contents($file);
    if ($content) {
        $result = explode(',', trim($content));
        $result = array_map(function($v) {
            return (int) trim($v);
        }, $result);
        return $result;
    }
}

$whiteList = getWhiteList('customer');

foreach ($customers as $customer) {
    if ($whiteList && !in_array($customer->id, $whiteList)) {
        continue;
    }
	$owner = $customer->owner;
	if (is_yikatong($owner->token) && $owner->gapper_user) {
		secho('['.$customer->id.']'.' start:');
		// 如果已经创建了组, 则把对应的成员加到组中 ps 为了使程序可重复执行，防止中断后不可继续
		if ($gid = (int)$customer->gapper_group) {
			$ret = $rpc->gapper->app->installTo($lab_order_client_id, 'group', $gid);
			sync_members($customer, $fail_csv);
		}
		else {
			try {
				// 升级这个买方为gapper-server的组
				$group_name = $customer->name;
				$data = [
					'name'  => $rpc->gapper->group->getrandomgroupname(str_replace(' ', '',PinYin::code($customer->name))),
					'title' => $customer->name,
					'user'  => (int)$owner->gapper_user,
				];
				$group_id = $rpc->gapper->group->create($data);
				$customer->gapper_group = $group_id;
				if ($group_id && $customer->save()) {
					secho($customer->id.': 创建组成功!');
					$rpc->gapper->app->installTo($lab_order_client_id, 'group', (int)$group_id);
					// 创建组成功后将用户加入这个组中
					sync_members($customer, $fail_csv);
				}
				else {
					fecho($customer->id.': 创建组失败!');
					$fail_csv->write([$customer->id, '创建组失败!']);
				}
			}
			catch(Exception $e) {
				$message = $e->getMessage();
				fecho($customer->id.':'.$message);
				$fail_csv->write([$customer->id, $message]);
			}
		}
	}
	else {
		// 如果买方管理员不是一卡通用户，这个买方暂不升级为gapper组
		fecho('管理员非一卡通用户或者对应的gapper用户创建不成功, 暂不升级');
		$fail_csv->write([$customer->id, '管理员非一卡通用户或者对应的gapper用户创建不成功, 暂不升级', $owner->token,$owner->id, $owner->gapper_user]);
	}
}
$fail_csv->close();
clean_cache();
function is_yikatong($token) {
	list(,$backend) = Auth::parse_token($token);
	if ($backend == 'ids.nankai.edu.cn') return TRUE;
	return FALSE;
}
function getCSVFile($file)
{
    $dir = dirname(__FILE__);
    return $dir . '/' . $file . '.csv';
}
function fecho($message) {
	echo "\033[31m".$message."\033[0m \n";
}
function secho($message) {
	echo "\033[32m".$message."\033[0m \n";
}
function sync_members($customer, $fail_csv) {
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
			$fail_csv->write([$customer->id, 'gapper group 在gapper server 不存在!']);
			return false;
		}
	}
	catch (Exception $e) {
		$message = $e->getMessage();
		fecho($customer->id.':'.$message);
		$fail_csv->write([$customer->id, $message]);
	}
	foreach ($members as $member) {
		try {
			if (!$member->gapper_user) {
				fecho('['.$customer->id.']'.$member->id.'加入gapper group ('.$gapper_group.') 失败 非gapper用户');
				$fail_csv->write([$customer->id, '增加成员 '.$member->id.' 失败! 非gapper用户']);
				continue;
			};
			$ret = $rpc->gapper->group->ADDMember((int)$gapper_group, (int)$member->gapper_user);
			if ($ret) {
				secho('['.$customer->id.']'.$member->id.'加入gapper group ('.$gapper_group.') 成功');
			}
			else {
				fecho('['.$customer->id.']'.$member->id.'加入gapper group ('.$gapper_group.') 失败 添加失败');
				$fail_csv->write([$customer->id, '增加成员 '.$member->id.' 失败!']);
			}
		}
		catch(Exception $e) {
			$message = $e->getMessage();
			fecho($customer->id.':'.$message);
			$fail_csv->write([$customer->id, $message]);
		}
	}
}


