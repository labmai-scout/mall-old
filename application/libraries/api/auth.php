<?php

class API_Auth {

    function verify($token=null, $password) {

        if(!$token) return false;

        $auth = new Auth($token);

        if ($auth->verify($password)) {
            return true;
        }

        return false;
    }

    function getBackends() {
        $backends_raw = Config::get('auth.backends', array());
        $backends = array();
        $default_backend = Config::get('auth.default_backend');

        foreach($backends_raw as $key=>$value) {
            if($key == $default_backend) {
                $backends[$key]['default']  = true;
            }
            $backends[$key]['title']  = $value['title'];
        }

        return $backends;
    }
}
