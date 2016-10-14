<?php

class Vendor_Scope_Model extends Presentable_Model {

	// 根据 now 判断是否需要 expire
	function test_and_expire($now = NULL) {
		$now = $now ? : Date::time();
		if ($now > $this->expire_date) {
			$this->expire();
		}
    }

	// 由于 scope 值都是各 prod_ 模块自定义的, expire 使用 trigger, 由各个模块自行处理较适合
	function expire() {
		return true;
	}
	function extend() {
		return Event::trigger('vendor_scope.extended', $this);
	}


    //经营认证图片
    function save_pic($file) {
         
        $vendor = $this->vendor;

        File::check_path(SITE_PATH.'private/images/scope_pic/'. $vendor->id. '/foo');
        
        Cache::remove_cache_file($this->get_pic_realpath());

        return move_uploaded_file($file, $this->get_pic_realpath()) || @rename($file, $this->get_pic_realpath());
    }

    function get_pic() {
        return Cache::cache_file($this->get_pic_realpath());
    }

    function get_pic_realpath() {
        return SITE_PATH. 'private/images/scope_pic/'. $this->vendor->id. '/'. $this->id. '.jpg';
    }
}
