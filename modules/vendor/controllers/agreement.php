<?php 

class Agreement_Controller extends Layout_Vendor_Controller {

    function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		if (!L('ME')->id) {
			URI::redirect('!vendor/signup');
		}
    }

    function _go_home($id=0) {
        $id = (int) $id;
        if (!$id) return URI::redirect('!mall');
        $rawURL = $_SESSION['HTTP_REFERER'];
        if ($rawURL) {
            return URI::redirect($rawURL);
        }
        return URI::redirect("!vendor/profile/view.{$id}");
    }

    function index($vid=0) {
        $vendor = O('vendor', $vid);
        if (!$vendor->id) return $this->_go_home();
        $me = L('ME');

        $current_version = Config::get('vendor.current_agreement_version');
        $start = date_create(Config::get('vendor.current_agreement_date_start'));
        $current = date_create()->setTimestamp(time());

        if ($current_version && $current<$start) {
            return $this->_go_home($vendor->id);
        }

        if ($vendor->agreement_time && $vendor->agreement_version===Config::get('vendor.current_agreement_version')) {
            return $this->_go_home($vendor->id);
        }

        $version = Config::get('vendor.current_agreement_version');
        $file = SITE_PATH . PRIVATE_BASE . "agreement/{$version}.md";

		$autoload = ROOT_PATH.'vendor/autoload.php';
		if(file_exists($autoload)) require_once($autoload);
        $agreement = \Michelf\MarkdownExtra::defaultTransform(@file_get_contents($file));

        if ($vendor->owner->id!==$me->id) {
            if (!$vendor->has_member($me)) return $this->_go_home();
            return $this->layout->body = V('vendor:agreement/warning', [
                'agreement'=> $agreement,
                'vendor'=> $vendor
            ]);
        }

        $this->layout->body = V('vendor:agreement/form', [
            'agreement'=> $agreement, 
            'vendor'=> $vendor, 
            'version'=> $version
        ]);
    }

}

class Agreement_AJAX_Controller extends AJAX_Controller {
    function index_agree_submit() {
        $me = L('ME');
        $form = Input::form();
        $vendor = O('vendor', $form['vid']);

        if (!$vendor->id || $vendor->owner->id!==$me->id) {
            return;
        }

        $accept = $form['accept']==='on';
        if (!$accept) {
            JS::alert(HT('请先阅读服务条款'));
            return;
        }

        $version = $form['version'];
        if (!$version) return;

        $vendor->agreement_version = $version;
        $vendor->agreement_time = time();
        $vendor->save();

        JS::refresh();
        return;

    }

    function index_disagree_click() {
        $me = L('ME');
        $form = Input::form();
        $vendor = O('vendor', $form['vid']);

        if (!$vendor->id || $vendor->owner->id!==$me->id) {
            return;
        }

        if (JS::confirm(HT('如果您拒绝以上协议, 您的供应商账户将会停用, 并会即刻下架所有商品。您确认拒绝吗?'))) {
            if ($vendor->unpublish(HT('用户拒绝签订协议。'))) {
                admin::cli_unapprove_products($vendor);
                JS::alert(HT('您已拒绝相关服务协议，如果有疑问，请联系系统管理员。'));
                JS::refresh();
                return;
            }
        }
    }
}
