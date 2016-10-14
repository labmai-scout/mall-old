<?php
/**
* @file user.php
* @brief 南开老商城升级为lab-orders的“升级用户”功能
* @author PiHiZi <pihizi@msn.com>
* @version 0.1.0
* @date 2015-05-07
 */

$base = dirname(dirname(__FILE__)). '/base.php';
require($base);

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

$whiteList = getWhiteList('user');

function logMe($msg)
{
    echo $msg . "\n";
}

/**
    * @brief 将指定用户升级为gapper用户
    *
    * @param $user 一个user对象
    *
    * @return true | false
 */
function upgrade($user)
{
    $user = O('user', $user->id);
    if ($user->gapper_user) return true;
    $token = $user->token;
    $sources = Config::get('gapper.sources');
    // 如果已经绑定了 则不需要处理
    $data = Gapper::get_user_by_identity($token);
    if ($data['id']) {
        $user->gapper_user = $data['id'];
        return $user->save();
    }
    // 如果没有对应的gapper用户，创建这个用户
    else {
        $rpc = Gapper::get_RPC();
        $data = $rpc->gapper->user->GetInfo($user->email);
        //该邮箱已注册, 但是没有linkidentity 则进行连接
        if ($data['id']) {
            list($stoken,$backend) = Auth::parse_token($token);
            $source = $sources[$backend];
            return $rpc->gapper->user->linkIdentity((int)$data['id'], $source, $stoken);
        }
        else {
            $uid = $rpc->gapper->user->registerUser([
                'username'=> $user->email,
                'password'=> rand(10000000,99999999),
                'name'=> $user->name,
                'email'=> $user->email
            ]);
            $user->gapper_user = $uid;
            if ($uid && Gapper::link_identity($user, $user->token) && $user->save()) {
                return TRUE;
            }
        }
    }

    return FALSE;

}

// 获取可升级的用户
// 1. 所有的一卡通用户都可以升级
// 2. 邮箱存在冲突的用户不可以升级

/**
    * @brief 获取邮箱重复的需要升级的一卡通用户数据
    *
    * @return generator
 */
function getConflictUserFromDB()
{
    global $whiteList;
    $db = database::factory();
    $sql = 'select * from user where ';
    if (!empty($whiteList)) {
        $inids = implode(',', $whiteList);
        $sql = $sql . " id in ({$inids}) and ";
    }
    $sql = $sql . ' token REGEXP "^[0-9]+\\\\|ids\\\\.nankai\\\\.edu\\\\.cn$" and email in (select email from user group by email having count(*)>1)';
    $query = $db->query($sql);
    $data = [];
    while ($row=$query->row()) {
        $data[$row->email] = $data[$row->email] ?: [];
        $data[$row->email][] = $row;
    }
    foreach ($data as $email=>$rows) {
        if (count($rows)>1) {
            foreach ($rows as $row) {
                yield $row;
            }
        }
    }
}

/**
    * @brief 获取可以直接升级的一卡通用户数据
    *
    * @return generator
 */
function getNiceUserFromDB()
{
    global $whiteList;
    $db = database::factory();
    $sql = 'select * from user where ';
    if (!empty($whiteList)) {
        $inids = implode(',', $whiteList);
        $sql = $sql . " id in ({$inids}) and ";
    }
    $sql = $sql . ' token REGEXP "^[0-9]+\\\\|ids\\\\.nankai\\\\.edu\\\\.cn$" and email not in (select email from user group by email having count(*)>1)';
    $query = $db->query($sql);
    while ($row=$query->row()) {
        yield $row;
    }

    $sql = 'select * from user where ';
    if (!empty($whiteList)) {
        $inids = implode(',', $whiteList);
        $sql = $sql . " id in ({$inids}) and ";
    }
    $sql = $sql . ' token REGEXP "^[0-9]+\\\\|ids\\\\.nankai\\\\.edu\\\\.cn$" and email in (select email from user group by email having count(*)>1)';
    $query = $db->query($sql);
    $data = [];
    while ($row=$query->row()) {
        $data[$row->email] = $data[$row->email] ?: [];
        $data[$row->email][] = $row;
    }
    foreach ($data as $email=>$rows) {
        if (count($rows)==1) {
            foreach ($rows as $row) {
                yield $row;
            }
        }
    }
}

function getCSVData($row)
{
    return [
        $row->token,
        $row->name,
        $row->email,
        $row->hidden,
        $row->name_abbr,
        $row->phone,
        $row->address,
        $row->group_id,
        $row->member_type,
        $row->creator_id,
        $row->auditor_id,
        $row->atime,
        $row->ctime,
        $row->mtime,
        $row->id,
        $row->org_code,
        $row->ref_no,
        $row->is_bind,
        $row->lims_user,
        $row->gapper_user,
        $row->_extra
    ];
}

function getCSVFile($file)
{
    $dir = dirname(__FILE__);
    return $dir . '/' . $file . '.' . time() . '.csv';
}

/**
    * @name 将存在冲突的需要升级的用户进行记录
    * @{ */
$conflicts = getConflictUserFromDB();
$i = 0;
//CSV::$line_max_length = 14096;
$file = getCSVFile('mall.upgrade.user.conflict.back');
$csv = new CSV($file, 'w');
foreach ($conflicts as $row) {
    ++$i;
    logMe(strtr(':id - :name - :email - :token', [
        ':id'=> $row->id,
        ':name'=> $row->name,
        ':email'=> $row->email,
        ':token'=> $row->token,
    ]));
    $csv->write(getCSVData($row));
}
$csv->close();
/**  @} */
logMe("共有 {$i} 几个冲突用户没有升级。");

sleep(10);

clean_cache();

/**
    * @name 逐个升级，并对升级失败的进行记录
    * @{ */
$nices = getNiceUserFromDB();
$i = 0;
$j = 0;
$file = getCSVFile('mall.upgrade.user.failed.back');
$sfile = getCSVFile('mall.upgrade.user.success.back');
$csv = new CSV($file, 'w');
$scsv = new CSV($sfile, 'w');
foreach ($nices as $row) {
    $data = getCSVData($row);
    logMe(strtr(':id - :name - :email - :token', [
        ':id'=> $row->id,
        ':name'=> $row->name,
        ':email'=> $row->email,
        ':token'=> $row->token,
    ]));
    if (!upgrade($row)) {
        ++$i;
        $csv->write($data);
    }
    else {
        ++$j;
        $scsv->write($data);
    }
}
$csv->close();
$scsv->close();
/**  @} */
logMe("有 {$i} 个用户升级失败。");
logMe("有 {$j} 个用户升级成功。");

clean_cache();
