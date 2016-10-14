<?php
class Home_Controller extends Layout_Mall_Controller {

    function _before_call($method, &$params) {
        parent::_before_call($method, $params);
        $vendor_type = current($params);
        if (!$vendor_type || !array_key_exists($vendor_type, (array)Config::get('product.types'))) {
            $vendor_type = Config::get('mall.home_default_tab');
        }

        $this->layout->sidebar->params = array(
            'vendor_type'=> $vendor_type
        );
    }
    function home() {
    	$form = Site::form();
    	$this->layout->nav_tabs = V('mall:tab', array('select_tab' => 'home'));
    	$view = V('mall:home')->set('form', $form);
		$this->layout->body = (string)$view;
		$this->add_css(Config::get('mall.autoload_css'));
		$this->add_js(Config::get('mall.autoload_js'));
    }

	function index($tab = NULL) {
		if (!$tab) {
			$tab = Config::get('mall.home_default_tab');
		}

		Site::reset_form();

		$types = Product_Model::get_types();
		/*
			if (!isset($types[$tab])) {
				$tab = key($types);
			}
		*/

		$this->layout->nav_tabs = V('mall:tab', array('select_tab' => $tab));

		if (in_array($tab, array_keys($types)))	{
			/*
			$view = V("prod_{$tab}:mall/home")->set('form', $form);
			if ((string) $view) {
				$this->layout->body = (string)$view;
                $this->add_css(Config::get('mall.autoload_css'));
                $this->add_js(Config::get('mall.autoload_js'));
			}
			else {
				URI::redirect("!mall/search/{$tab}");
			}
			*/
			URI::redirect("!mall/search/{$tab}");
		}
		else {
			if ($tab == 'home') URI::redirect('!mall/home/home');
            if ($tab == 'vendor') URI::redirect('!mall/vendor');
            if ($tab == 'news') URI::redirect('!news/index');

            $tabs = Config::get('mall.index.tab');
            if ($tabs[$tab]['view']) {
                $view = $tabs[$tab]['view'];
            }
            else {
                $view = "mall:home/{$tab}";
            }
            $this->add_css('mall:content');
            $this->layout->body = V($view);
		}
	}
}
