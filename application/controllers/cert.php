<?php

class Cert_Controller extends Layout_Controller {

    public $layout_name = 'cert/layout';

    public function upload() {
        
        $form = Input::form();
        $scope_id = $form['scope_id'];
        $vendor_id = $form['vendor_id'];
        $scope_name = $form['scope_name'];

        $vendor = O('vendor', $vendor_id);
        $scope = O('vendor_scope', $scope_id);

        $file = Input::file('test');

        if ($file['tmp_name']) {

            if ($scope->id) {
                if ($scope->save_pic($file['tmp_name'])) {
                    $this->layout->body = V('cert/message', array('message'=> HT('上传成功!')));
                }
                else {
                    $this->layout->body = V('cert/message', array('message'=> HT('上传失败!')));
                }
            }
            else {
                if (Vendor_Scope::save_tmp_file($file['tmp_name'], $scope_name, $vendor_id)) {
                    $this->layout->body = V('cert/message', array('message'=> HT('上传成功!')));
                }
                else {
                    $this->layout->body = V('cert/message', array('message'=> HT('上传失败!')));
                }
            }

        }
        else {
            $this->layout->body = V('cert/upload', array('scope_id'=>$scope_id, 'vendor_id'=>$vendor_id, 'scope_name'=>$scope_name));
        }

		$this->add_css('mall form tab table button tooltip user dialog dropdown comment tag token_box widgets/rateit autocomplete');
    }
}

class Cert_AJAX_Controller extends AJAX_Controller {

    public function index_show_pic_click() {
        $form = Input::form();
        $scope = O('vendor_scope', $form['sid']);

        if (!$scope->id || !L('ME')->is_allowed_to('查看证书', $scope->vendor)) return FALSE;

        JS::dialog(V('cert/show', array('scope'=>$scope)));
    }
    
    public function index_delete_pic_click() {
    	$form = Input::form();
    	$scope = O('vendor_scope', $form['sid']);
    	
    	if (!$scope->id || !JS::confirm(HT('您是否确认删除该许可证书!'))) return FALSE;
    	
    	Cache::remove_cache_file($scope->get_pic_realpath());
    	
    	@unlink($scope->get_pic_realpath());
    	
    	Site::message(SITE::MESSAGE_NORMAL, HT('成功删除许可证书!'));
    	
    	JS::refresh();
    }    
}
