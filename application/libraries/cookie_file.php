<?php

class Cookie_File {

    public $path;

    //初始化创建cookie文件
    public function __construct() {
        $this->path = tempnam(sys_get_temp_dir(), 'rpc.cookie');
    }

    //remove
    public function __destruct() {
        File::delete($this->path);
    }
}
