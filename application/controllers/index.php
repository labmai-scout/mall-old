<?php

class Index_Controller extends Layout_Controller {

    function index() {
        $me = L('ME');
        if ($me->id) {
            URI::redirect('!people/profile/index');
        }
        else {
            URI::redirect('login');
        }
    }

}
