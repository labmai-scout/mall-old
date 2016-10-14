<?php
class Vendor_Scope {

    //对于某些未进行保存的scope，我们需要去获取临时文件地址
    static function get_tmp_file($scope_name, $vendor_id) {
        return SITE_PATH. 'private/images/scope_tmp/'. $vendor_id. '/'. $scope_name. '.jpg';
    }

    //保存临时文件
    static function save_tmp_file($file, $scope_name, $vendor_id) {
        File::check_path(SITE_PATH. 'private/images/scope_tmp/'. $vendor_id. '/foo');
        return @move_uploaded_file($file, SITE_PATH. 'private/images/scope_tmp/'. $vendor_id. '/'. $scope_name. '.jpg');
    }
}
