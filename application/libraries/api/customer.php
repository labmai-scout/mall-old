<?php

class API_Customer {

    function update_user($data) {

        $customer_id = $_SESSION['current_customer'];
        $customer = O('customer', $customer_id);
        $uuid = $customer->uuid;
        //如果指定绑定owner，则更具传入的data，对user赋值
        if($data['owner']) {
            $user = $customer->owner;
            if (!$user->id) throw new API_Exception(T('买方负责人错误!'));
        }
        else{
            if (!isset($data['username']) && !isset($data['uid'])) throw new API_Exception(T('账号或用户ID不能为空!'));

            if (!isset($data['name'])) throw new API_Exception(T('姓名不能为空!'));
            $token = $data['username'];
            if ($token) {
                list($name,$backend) = Auth::parse_token($token);
                if ($backend == 'database') {
                    $token = $data['username'].'%'.$uuid;
                }
            }
            // 更新用户信息
            if (isset($data['uid'])) {
                $user = O('user', $data['uid']);
                if (!$user->id) return FALSE;
                if ($token) {
                    if ($token != $user->token) {
                        $customer->disconnect($user, 'member');
                        $user = O('user', ['token'=>$token]);
                        $user->token = $token;
                    }
                }

            } // 新建用户
            else {
                $user = O('user', ['token'=>$token]);
                // token 为系统已有的用户
                if (!$user->id) {
                    $user->token = $token;
                }
            }

            $user->atime = $data['atime']?:0;
            list(,$backend) = Auth::parse_token($user->token);
            if ($backend == 'database') {
                $user->lims_user = TRUE;
            }
            else {
                $user->lims_user = FALSE;
            }

            $user->name = $data['name'];
        }

        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        if (isset($data['phone'])) {
            $user->phone = $data['phone'];
        }

        if (isset($data['address'])) {
            $user->address = $data['address'];
        }

        if (isset($data['gender'])) {
            $user->gender = $data['gender'];
        }

        if (isset($data['is_bind'])) {
            $user->is_bind = $data['is_bind'];
        }
        $user->save();
        if (!$user->id) throw new API_Exception(T('用户保存失败!'));
        $customer->connect($user, 'member');

        if (isset($data['perms'])) {
            //权限设定
            $all_perms = array_keys(Config::get('customer.perms'));

            foreach($all_perms as $perm) {
                $p = Q("customer_member_perm[customer={$customer}][user={$user}][name={$perm}]")->current();

                if(in_array($perm, $data['perms'])) {
                    if(!$p->id) {
                        $p = O('customer_member_perm');
                        $p->name = $perm;
                        $p->customer = $customer;
                        $p->user = $user;
                        $p->save();
                    }
                }
                else{
                    if ($p->id) $p->delete();
                }
            }
        }
        else {
            Q("customer_member_perm[customer={$customer}][user={$user}]")->delete_all();
        }

        return $user->id;
    }

    //返回随机码，用于进行加密验证
    public function get_site_code($site_id) {
        $customer = O('customer', array('uuid'=>$site_id));
        if ($customer->id) {
            $site_code = UUID::v4();

            //存储SITE_ID 用于后期获取
            $_SESSION['customer.site_id'] = $site_id;
            $_SESSION['customer.site_code'] = $site_code;

            return $site_code;
        }

        return FALSE;
    }

    //签名验证
    public function auth($signature) {

        if (!isset($_SESSION['customer.site_code'])) return FALSE;

        $customer = O('customer', array('uuid'=> $_SESSION['customer.site_id']));

        $signature = @base64_decode($signature);

        //customer对象存储了public_key属性
        $lims_data = $customer->lims_data;
        $public_key = $lims_data['public_key'];

        $ssl = new OpenSSL();

        //验证通过后存储customer
        if($ssl->verify($_SESSION['customer.site_code'], $signature, $public_key)) {
            $_SESSION['current_customer'] = $customer->id;
            return TRUE;
        }

        return FALSE;
    }


    public function get_user_token($uid) {

        if (!$_SESSION['current_customer']) return FALSE;

        //清理
        Q("user_auth[expire_time<{$now}]")->delete_all();

        $access_token = UUID::v4();

        $user_auth = O('user_auth');
        $user = O('user', $uid);

        if (!$user->id)  return FALSE;

        $user_auth->user = $user;

        //15秒过期时间
        $now = Date::time();
        $user_auth->expire_time = $now + 15;

        //存储access_token
        $user_auth->access_token = $access_token;

        if ($user_auth->save()) {
            return $access_token;
        }
        else {
            return FALSE;
        }
    }

    public function remove_members($opt) {
        if (!is_array($opt)) throw new API_Exception(T('参数应为数组!'));
        $customer_id = $_SESSION['current_customer'];
        $customer = O('customer', $customer_id);
        $ret = TRUE;
        foreach ($opt as $id) {
            $user = O('user',$id);
            if ($user->id && $customer->has_member($user)) {
                if (!$customer->disconnect($user, 'member')) $ret = FALSE;
            }
        }
        return $ret;
    }

    public function get_users($opt) {
        $us = array();

        if (!$_SESSION['current_customer']) return $us;
        $customer = O('customer', $_SESSION['current_customer']);

        $uuid = $customer->uuid;

        $selector = "$customer<member user";

        if (isset($opt['bind'])) {
            $is_bind = Q::quote($opt['bind']);
            $selector .= "[is_bind={$is_bind}]";
        }

        if (isset($opt['atime'])) {
            $selector .= $opt['atime'] ? "[atime]" : "[!atime]";
        }

        //如果传入id则表示需要得到指定用户的信息，所以不需要过滤token,和limit
        if (isset($opt['ids'])) {
            $ids = $opt['ids'];
            if(is_array($opt['ids'])) $ids = join(',', $opt['ids']);
            $selector .= "[id={$ids}]";
        }
        else{
            // 目前不能让这边选择同步生成Lims默认用户
            // $selector .= ":not(user[token*={$uuid}])";

            $start = $opt['start'] ?: Config::get('mall.get_users_start', 0);

            $step = $opt['step'] ?: Config::get('mall.get_users_step', 10);

            $selector .= ":limit($start, $step):sort(is_bind A)";
        }
        $users = Q($selector);

        if ( count($users) ) {

            foreach ($users as $user) {
                $us[$user->id] = array(
                    'name' => $user->name,
                    'name_abbr' => $user->name_abbr,
                    'token' => $user->token,
                    'atime' => $user->atime,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'gapper_user' => $user->gapper_user,
                );
            }
        }
        return $us;

    }

    public function get_gapper_group() {

        if (!$_SESSION['current_customer']) return FALSE;
        $customer = O('customer', $_SESSION['current_customer']);
        try{
            $rpc = Gapper::get_RPC();
            if ($customer->gapper_group && $rpc->gapper->group->getInfo((int)$customer->gapper_group)) {
                return $customer->gapper_group;
            }
        }
        catch(Exception $e){}

        return FALSE;
    }

    public function set_gapper_group($gapper_group=0) {

        if (!$_SESSION['current_customer']) return FALSE;
        $customer = O('customer', $_SESSION['current_customer']);

        try{

            $rpc = Gapper::get_RPC();
            //没有group或者customer存在一个不正确的分组
            if(!$gapper_group ||
                ($customer->gapper_group && $rpc->gapper->group->getInfo((int)$customer->gapper_group))) return FALSE;

            $customer->gapper_group = $gapper_group;


            $rpc = Gapper::get_RPC();
            $mall_client_id = Config::get('mall.gapper')['client_id'];
            if($customer->save() && $rpc->gapper->app->installTo($mall_client_id, 'group', (int)$gapper_group)) {
                return TRUE;
            }
        }
        catch(Exception $e){}

        return FALSE;
    }

    public function update_user_token($user_id, $new_token) {
        if (!$_SESSION['current_customer']) return FALSE;
        $customer = O('customer', $_SESSION['current_customer']);

        $user = Q("{$customer}<member user[id={$user_id}]")->current();

        if(!$user->id) return FALSE;

        if(Gapper::is_gapper_user($user)) return FALSE;

        $old_token = $user->token;
        $user->token = $new_token;
        if($user->save() && Gapper::link_identity($user, $old_token)) {
            return TRUE;
        }

        return FALSE;
    }

    public function create_customer($data) {
        if(!API_Mall::is_authenticated()) return;
        $user_info = $data['user'];
        $customer_info = $data['customer'];
        list($name, $backend) = Auth::parse_token($user_info['token']);
        if ($backend != 'ids.nankai.edu.cn') {
            throw new API_Exception(T('只有一卡通用户可以创建买方!'));
        }
        $user = O('user', ['token'=>$user_info['token']]);
        if (!$user->id) {
            $user->token = $user_info['token'];
            $user->name = $user_info['name'];
            $user->phone = $user_info['phone'];
            $user->email = $user_info['email'];
            $user->address = $user_info['address'];
            $user->atime = time();
        }
        $user->save();
        $bind_status_draft = Customer_Model::BIND_STATUS_NOT_YET;
        $customer = Q("customer[owner={$user}][bind_status=$bind_status_draft]")->current();
        if (!$customer->id) {
            $customer = O('customer');
            $customer->name = $customer_info['name'];
            $customer->owner = $user;
        }
        $info = $customer_info['info'];
        $customer->lims_data = array(
            'public_key'=> $info['public_key'],
            'site_name'=> $info['site_name'],
            'order_url'=> $info['order_url'],
            'update_url'=> $info['update_url'],
            'bind_url'=> $info['bind_url'],
            'unbind_url' => $info['unbind_url'],
            'order_list' => $info['order_list'],
            'base_url' => $info['base_url'],
            'client_id' => $info['client_id'],
        );

        $customer->uuid = $info['site_id'];
        $customer->bind_status = Customer_Model::BIND_STATUS_SUCCESS;

        if ($customer->save()) {
            $customer->connect($user, 'member');
        }
        else {
            throw new API_Exception(T('买方创建失败!'));
        }
        $data = [
            'uid' => $user->id,
            'cid' => $customer->id,
        ];

        return $data;
    }
}