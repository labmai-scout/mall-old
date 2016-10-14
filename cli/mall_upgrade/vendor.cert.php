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


// 上传资质图片到七牛所要调用的cloudfs服务器地址
// mall-vendor 的地址
$server = 'http://192.168.0.5:8994';
// 七牛静态文件的域名
$domain = 'http://7xk7ep.com1.z0.glb.clouddn.com';

$vendors = Q('vendor');
foreach ($vendors as $vendor) {
    if (uploadCertImage($vendor)) {
        echo '.';
        continue;
    }
    echo $vendor->id;
}

function uploadFile($file)
{
    global $server;
    global $domain;

    if (!file_exists($file)) {
        return false;
    }

    $postData = [
        'files'=> new CURLFILE($file)
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    curl_setopt_array($ch, [
        CURLOPT_URL => $server . '/ajax/cloudfs/qiniu/upload',
        CURLOPT_RETURNTRANSFER=> 1
    ]);

    $data = curl_exec($ch);
    $errorNO = curl_errno($ch);
    if ($errorNO) {
        $error = curl_error($ch);
    }

    curl_close($ch);

    if ($errorNO) {
        return false;
    }

    $data = @json_decode($data);

    if (!$data->key) return false;
    $url = "{$domain}/{$data->key}";
    return $url;
}

// 资质图片上传
function uploadCertImage($vendor)
{
    try {
        $config = Config::get('mall.hub-vendor');
        $rpc = new RPC($config['api']);
        $clientId = $config['client_id'];
        $clientSecret = $config['client_secret'];
        $ret = $rpc->mall->authorize($clientId, $clientSecret);
        if (!$ret) {
            echo "{$vendor->id}同步资质信息失败\n";
            return;
        }
        $rvendor = O('rvendor', $vendor->gapper_group);
        if (!$rvendor->id) {
            echo "{$vendor->id}同步资质信息失败, 获取rvendor失败\n";
            return;
        }
    }
    catch (Exception $e) {
        echo "{$vendor->id}同步资质信息失败: {$e->getMessage()}\n";
        return;
    }

    $data = (array) $rvendor->scanned_copies;
    // 营业执照
    $file = $vendor->get_path('license') . $vendor->license_img;
    if ($vendor->license_img && file_exists($file)) {
        $file = uploadFile($file);
        if ($file) {
            $data['license']['image'] = $file;
            $data['license']['upload_time'] = date('Y-m-d H:i:s');
        }
    }

    // 组织机构代码证
    $file = $vendor->get_path('group') . $vendor->group_img;
    if ($vendor->group_img && file_exists($file)) {
        $file = uploadFile($file);
        if ($file) {
            $data['org_code_cert']['image'] = $file;
            $data['org_code_cert']['upload_time'] = date('Y-m-d H:i:s');
        }
    }

    // 地税
    $file = $vendor->get_path('tax_on_land') . $vendor->tax_on_land_img;
    if ($vendor->tax_on_land_img && file_exists($file)) {
        $file = uploadFile($file);
        if ($file) {
            $data['regional_tax_reg_cert']['image'] = $file;
            $data['regional_tax_reg_cert']['upload_time'] = date('Y-m-d H:i:s');
        }
    }

    // 国税
    $file = $vendor->get_path('state_tax') . $vendor->state_tax_img;
    if ($vendor->state_tax_img && file_exists($file)) {
        $file = uploadFile($file);
        if ($file) {
            $data['national_tax_reg_cert']['image'] = $file;
            $data['national_tax_reg_cert']['upload_time'] = date('Y-m-d H:i:s');
        }
    }

    // 其他资质
    $scopes = Q("vendor_scope[vendor={$vendor}]");
    $index = 0;
    foreach ($scopes as $scope) {
        $file = $scope->get_pic_realpath();
        $file = uploadFile($file);
        if ($file) {
            $data['misc'][$index]['image'] = $file;
            $data['misc'][$index]['upload_time'] = date('Y-m-d H:i:s');
            $index++;
        }
    }

    if (!empty($data)) {
        try {
            $result = $rpc->mall->vendor->updateVendor($vendor->gapper_group, [
                'scanned_copies'=> $data
            ]);
        }
        catch (\Exception $e) {
            echo "{$vendor->id}同步资质信息失败: {$e->getMessage()}\n";
        }
    }
    return true;
}
