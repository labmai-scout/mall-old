<?php 

class Index_AJAX_Controller extends AJAX_Controller {

	function index_import_reagent_click() {

        if (!Config::get('reagent.enable_batch_import')) return FALSE;

		$me = L('ME');
        $vendor_id = Input::form('vendor');
        $vendor = O('vendor', $vendor_id);
        if (!$me->is_allowed_to('添加商品', $vendor)) return false;

        JS::dialog(V('product/import_reagent', array('vendor_id'=>$vendor_id)), array('width'=>420));
	}

	function index_import_reagent_submit() {

        if (!Config::get('reagent.enable_batch_import')) return FALSE;

		$me = L('ME');
		$file = Input::file('file');
		$vendor_id = Input::form('vendor');
		$vendor = O('vendor', $vendor_id);
		if (!$me->is_allowed_to('添加商品', $vendor)) return false;

		if ($file['tmp_name']) {
			if ($file['type'] == 'text/csv') {
				
				$new_path = SITE_PATH.'private/import_csv/'.$vendor_id.'/';
				Product_Reagent::create_dir($new_path);
				
				$new_name = Date::time().$file['name'];
				$new_file = $new_path.$new_name;
				move_uploaded_file($file['tmp_name'],$new_file);
		
				$cli_path = Config::get('cli_path.default_path');
				$execute = 'SITE_ID='.SITE_ID.' php '.ROOT_PATH.'cli/import_reagent_product.php '.$vendor_id.' '.$new_file.' > /dev/null 2>&1 &';		
				
				$vendor = O('vendor', $vendor_id);
				exec($execute);
				sleep(2);

				
			}
			else {
				Site::message(Site::MESSAGE_ERROR, T('商品批量导入失败，可能是文件格式不正确，请选择csv格式的文件'));
			}
		}
		else {
			Site::message(Site::MESSAGE_ERROR, T('请上传csv文件'));
		}
		JS::refresh();
	}

}
