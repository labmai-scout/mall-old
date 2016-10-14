<?php

class API_Mall_User {

    static function getUser($id=0) {
 
        if(!API_Mall::is_authenticated()) return;
        $user = O('user', $id);
        if (!$user->id) return [];

        $result = array(
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->token,
            'email' => $user->email,
            'gender' => $user->gender,
            'major' => $user->major,
            'phone' => $user->phone,
            'mobile' => $user->mobile,
            'address' => $user->address,
            'member_type' => $user->member_type,
            'ref_no' => $ref_no,
            'org_code' => $org_code,
            'gapper_user'=>$user->gapper_user,
            'lab_orders_customer_all'=>false,
            'lab_orders_url'=>'',
            'mall_old_url'=>'',
        );


        //用户未激活
        if(!$user->atime) {
            $result['disabled'] = true;
        }

        if($user->access('查看管理面板')){
            $result['roles']['admin'] = [
                'title' => H(T('管理面板')),
                'url' => URI::url('!admin'),
            ];
        }

        $is_vendor_manager = false;
        $vendors = Q("$user<member vendor");
        if($vendors->total_count()) {
            foreach ($vendors as $vendor){
                if($user->is_allowed_to('管理', $vendor)){
                    $is_vendor_manager = true;
                    $result['roles']['vendor'][$vendor->id] = [
                        'title' => H($vendor->get_display_name('short')),
                        'url' => $vendor->url(NULL, NULL, NULL, 'vendor_view'),
                    ];
                }
            }
        }

        $customers = Q("$user<member customer");
        if ($customers->total_count()) {
            $app = Config::get('gapper.apps')['lab-orders'];
            $rpc = Gapper::get_RPC();
            $login_token = $rpc->gapper->user->getLoginToken((int)$user->gapper_user, $app['client_id']);
            $url = $app['url'];
            $tmp_customer_count=0;
            foreach ($customers as $customer){
                 $result['roles']['customer'][$customer->id] = [
                    'title' => H($customer->name),
                    'url' => $customer->url(),
                    'group_id' => $customer->gapper_group,
                ];

                if ($user->id == $customer->owner->id) {
                    $perms = Config::get('customer.perms');
                    $result['roles']['customer'][$customer->id]['perms'] = array_keys($perms);
                }
                else {
                    $perms = Q("customer_member_perm[user={$user}][customer={$customer}]");
                    foreach ($perms as $perm) {
                        $result['roles']['customer'][$customer->id]['perms'][] = $perm->name;
                    }
                }
                //用户升级标记检测
                if($customer->unable_upgrade==true){
                   //$result['mall_old_url'] = $customer->url();
                }
                //检测含有lab-order插件的话则赋值给url
                if ($customer->check_app_installed('lab-orders')) {
                    //设置全局地址
                    //设置客户地址判断如果有lab-order权限则返回lab-order地址
                    $result['roles']['customer'][$customer->id]['url'] = URI::url($url, ['gapper-token'=>$login_token, 'gapper-group'=>$customer->gapper_group]);
                    $tmp_customer_count++;
                }

            }
            //如果客户全部为支持lab-orders的用户，则设置此标记位置
            if(!$is_vendor_manager && $tmp_customer_count==$customers->total_count()){
                $result['lab_orders_customer_all']=true;
                $result['lab_orders_url'] = URI::url($url, ['gapper-token'=>$login_token]);
            }

            if ($user->access('管理所有内容')) {
                $result['lab_orders_customer_all']=false;
            }
        }
        return $result;
    }

    static function getUserId($token=null) {
        if(!API_Mall::is_authenticated()) return;

        $user = O('user', array('token'=>$token));
        return (int)$user->id;
    }

    static function getRedirectURL($token)
    {
        if(!API_Mall::is_authenticated()) return;

        try {
            $user = Gapper::get_user_by_identity($token);
            if (empty($user) || !$user['id']) {
                return;
            }
            $rpc = Gapper::get_RPC();
            $app = Config::get('gapper.apps')['lab-orders'];
            $url = $app['url'];
            $token = $rpc->gapper->user->getLoginToken((int)$user['id'],  $app['client_id']);
            return URI::url($url, ['gapper-token'=>$token]);
        }
        catch (\Exception $e) {
        }
        return;
    }

    public function getUserImage($id=0, $index=0, $size=64) {
        if(!API_Mall::is_authenticated()) return;

        $user = O('user', $id);
        if (!$user->id) return '';

        /* TODO 因为目前没有做多图功能，故默认返回index为0的首发图片*/
        $size = $user->normalize_icon_size($size);
        if ($icon_file = Core::file_exists(PRIVATE_BASE.'icons/'.$user->name().'/'.$size.'/'.$user->id.'.png', '*')) {
            return Config::get('system.base_url').Cache::cache_file($icon_file).'?_='.$user->mtime;
        }

        return '';
    }
}
