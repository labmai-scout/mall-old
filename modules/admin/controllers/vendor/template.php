<?php

class Vendor_Template_Controller extends Layout_Admin_Controller {
	function download() {
		$me = L('ME');
		$is_admin = $me->access('管理所有内容') || $me->access('管理分组');
		if (!$is_admin) URI::redirect('error/401');
		$fullpath = SITE_PATH.'private/template/product/template.xlsx';
		Downloader::download($fullpath, TRUE);
	}
}
?>