<?php

class API_Gapper {

    function getLocalUsername($username = '') {
        if (!$username) return false;

        list(,$backend) = Auth::parse_token($username);
        if($backend != 'database') return false;

        try{

            $gapper_fallback_user = O('gapper_fallback_user', ['token'=>$username]);
            if ($gapper_fallback_user->user->id) {
                return $gapper_fallback_user->user->token;
            }
        }
        catch(Exception $e) {
            return false;
        }
    }

    function checkAppInstalled($customer_id, $app_name) {
        $customer = O('customer', $customer_id);
        if (!$customer->id || !$app_name) return false;
        try{
            if ($group_id = $customer->gapper_group) {
                $rpc = Gapper::get_RPC();
                $app = Config::get('gapper.apps')[$app_name];
                $result = $rpc->gapper->app->getGroupInfo($app['client_id'], (int)$group_id);
                return $result;
            }
        }
        catch(Exception $e) {
            return false;
        }
    }
}
