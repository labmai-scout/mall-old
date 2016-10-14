<?php

class Guide_Controller extends Layout_Controller {

    protected $layout_name = 'mall:guide';

    function _before_call($method, &$params) {
        parent::_before_call($method, $params);
        $this->layout->header = V('mall:header');
        $this->layout->footer = V('mall:footer');
        $this->layout->body_header = V('mall:body_header');
    }

    function index() {

    }

    function _after_call($method, &$params) {

        parent::_after_call($method, $params);
        $this->add_css('mall:theme');
        $this->add_css('site');
        $this->add_css('preview');
        $this->add_js('preview');
        $this->add_css('popover');
        $this->add_js('popover');
        $this->add_css('mall:guide');
    }
}
