<?php

class Node_Controller extends Layout_Admin_Controller {

    function index() {
		$apps = Config::get('gapper.apps', []);
		$app = $apps['hazardous-control'];
		$url = $app['url'];
		$me = L('ME');
		$user_group = O('user_group', ['user'=>$me]);
		if (!$me->access('管理所有内容')) {
			URI::redirect('error/401');
		}
		if ($me->gapper_user) {
		    $rpc = Gapper::get_RPC();
		    $login_token = $rpc->gapper->user->getLoginToken((int)$me->gapper_user, $app['client_id']);
		    $url = URI::url($url, ['gapper-token'=>$login_token]);
		}
		URI::redirect($url);
    }
}
