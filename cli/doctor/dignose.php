<?php
$base = dirname(dirname(__FILE__)). '/base.php';
require($base);
function fecho($msg) {
    Upgrader::echo_fail($msg);
}

function secho($msg) {
    Upgrader::echo_success($msg);
}

function techo($msg) {
    Upgrader::echo_title($msg);
}

function necho($msg) {
    echo ($msg."\n");
}

necho('mall-old '.SITE_ID.' 部署检测');
echo "\n";
techo('目录权限检测');

$file_path = SITE_PATH.'private/payment_voucher/';

necho('检测 order_voucher');
$file_path = SITE_PATH.'private/order_voucher/';
check_voucher_file($file_path, 'www-data', 'www-data', 755);

necho('检测 payment_voucher');
$file_path = SITE_PATH.'private/payment_voucher/';
check_voucher_file($file_path, 'www-data', 'www-data', 755);

necho('检测 供应商文件路径');
$file_path = Config::get('nfs.root');
check_voucher_file($file_path, 'www-data', 'www-data', 755);

necho('检测 文件上传路径');
$file_path = Config::get('product.upload_path');
check_voucher_file($file_path, 'www-data', 'www-data', 755);

$conf = Config::get('gapper.apps');
if ($conf['drug-precursor-plan']['client_id']) {
    secho('gapper apps drug-precursor-plan 已配置');
}
else {
    fecho('gapper apps drug-precursor-plan 未配置!!!');
}


function check_voucher_file($file_path, $user, $group, $chmod) {
    $ret = true;
    if (!file_exists($file_path)) {
        $ret = false;
        fecho('目录不存在');
    }

    $file_user_info = posix_getpwuid(fileowner($file_path));
    if ($file_user_info['name'] != $user) {
        $ret = false;
        fecho('user不正确, 应为www-data');
    }

    $file_group_info = posix_getgrgid(filegroup($file_path));
    if ($file_group_info['name'] != $group) {
        $ret = false;
        fecho('group不正确, 应为www-data');
    }

    if ((int)substr(sprintf('%o', fileperms($file_path)), -4) != $chmod) {
        $ret = false;
        fecho('文件权限错误, 应为755');
    }

    if ($ret) {
        secho('检测通过');
    }
    else {
        fecho($file_path.' 配置异常! 请根据提示修正配置!');
    }
}
echo "\n";
techo('RPC检测');
necho('检测 Gapper');
if (Gapper::get_RPC()) {
    secho('gapper rpc 正常');
}
else {
    fecho('gapper rpc 异常, 请检查配置文件!');
}
echo "\n";
techo('检测针对hub-vendor和hub-node的rpc设置');
necho('检测 hub-vendor');
if (RVendor_Model::getRPC('hub-vendor')) {
    secho('hub-vendor rpc 正常');
}
else {
    fecho('hub-vendor rpc 异常, 请检查配置文件!');
}
necho('检测 hub-product');
if (RVendor_Model::getRPC('hub-product')) {
    secho('hub-product rpc 正常');
}
else {
    fecho('hub-product rpc 异常, 请检查配置文件!');
}
necho('检测 hub-node');
if (RVendor_Model::getRPC('hub-node')) {
    secho('hub-node rpc 正常');
}
else {
    fecho('hub-node rpc 异常, 请检查配置文件!');
}
echo "\n";
techo('检测 SPHINX');
$url = substr(Config::get('database.@sphinx.url'), 8);
$conn = mysql_connect($url);
if ($conn) {
    secho('sphinx 连接正常');
}
else {
    fecho('sphinx 配置异常, 请检查配置文件!');
}
echo "\n";
techo('检测 DEBADE');

$queues = Config::get('debade.queues');
$addr = current($queues)['addr'];
$addr = substr($addr, 6);
list($url, $port) = explode(':', $addr);

$fp = fsockopen($url, $port, $errno, $errstr);
if (!$fp) {
    fecho("debade telnet ERROR: $errno - $errstr");
} else {
    secho('debade telnet success');
}
fecho('请自行检测DEBADE消息接收');

echo "\n";
techo('检测 lab-orders');

$rpc = Gapper::get_RPC();
$app = Config::get('gapper.apps')['lab-orders'];

$ret = $rpc->gapper->app->getInfo($app['client_id']);
if ($ret) {
     secho('检测通过');
}
else {
    fecho('lab-orders 配置异常, 请检查配置文件!');
}

$needManagerApprove = Config::get('order.need_manager_approve');
if (!$needManagerApprove) {
    fecho('目前order.need_manager_approve配置，订单不需要管理方审核');
}


echo "\n";
techo('检测 插件');

exec("xlsx2csv --version");

echo "\n";
techo('检测 applet 目录');

$file_path = ROOT_PATH.'public/applet/order_printer/default.jnlp';

if (file_exists($file_path)) {
    secho('检测通过,请务必确认该目录下的另外两个 .jar 文件存在');
} else {
    fecho('public/applet/order_printer/default.jnlp 文件不存在');
}

echo "\n";
?>
