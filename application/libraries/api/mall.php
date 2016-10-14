<?php
class API_Mall {

    public function authorize($client_id, $client_secret) {
        if(!$client_id || !$client_secret) return;
        $servers = Config::get('rpc.servers');

        foreach ($servers as $title=>$server) {
            if($server['client_id'] == $client_id && $server['client_secret'] == $client_secret){
                $_SESSION['rpc.server'] = $title;
                return true;
            }
        }
    }

    static function is_authenticated() {
        if ($_SESSION['rpc.server']) {
            return TRUE;
        }

        return FALSE;
    }

    public function get_uuid() {

        $uuid = Site::get('mall.uuid');

        if (!$uuid) {
            $uuid = UUID::v4();
            Site::set('mall.uuid', $uuid);
        }

        return $uuid;
    }

    public function check_uuid($uuid) {

        if ($uuid != Site::get('mall.uuid')) return FALSE;

        $customer = O('customer', $_SESSION['current_customer']);

        if (!$customer->id) throw new API_Exception(T('未找到对应的买方!'));

        $customer->bind_status = Customer_Model::BIND_STATUS_SUCCESS;

        return $customer->save();
    }

    public function unbind($uuid) {
        if ($uuid != Site::get('mall.uuid')) return FALSE;

        $customer = O('customer', $_SESSION['current_customer']);

        if (!$customer->id) throw new API_Exception(T('未找到对应的买方!'));

        $customer->bind_status = Customer_Model::BIND_STATUS_NOT_YET;
        //同步清除uuid和lims_data数据
        $customer->uuid = NULL;
        $customer->lims_data = NULL;

        if (!$customer->save()) {
            throw new API_Exception(T('解除绑定删除!'));
        }

        return true;
    }
}
