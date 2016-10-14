<?php
class Provider2_User_Controller extends Controller {
    function index() {
        $access_token = Input::form()['access_token'];
        $user_api = new OAuth2_API_User;
        $keys = ['username', 'name', 'email'];
        $info = $user_api->info($access_token, $keys, true);
        echo $info;
    }
}
