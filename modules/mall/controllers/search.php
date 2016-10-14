<?php

class Search_Controller extends Layout_Mall_Controller {

    function _before_call($method, &$params) {
        parent::_before_call($method, $params);
        $vendor_type = current($params);
        if (!array_key_exists($vendor_type, (array)Config::get('product.types'))) {
            $vendor_type = Config::get('mall.home_default_tab');
        }

        $this->layout->sidebar->params = array(
            'vendor_type'=> $vendor_type
        );
    }

	// (xiaopei.li@2012-02-12)
	function index($tab = NULL, $keyword = NULL) {

		$form = Site::form(function(&$old_form, &$new_form){
			if (isset($new_form['name'])) {
				$new_form['hidden_group'] = TRUE;
			}
			else {
				$old_form['hidden_group'] = FALSE;
				unset($old_form['name']);
			}

            if ($new_form['category']) {
                unset($old_form['keyword']);
            }
		});

        $form['keyword'] = rawurldecode($keyword) ? :trim($form['keyword']);
        $this->layout->nav_tabs = V('mall:tab', array('select_tab' => $tab, 'keyword'=>$form['keyword']));

		$function = "Product_{$tab}::mall_search";
		if (is_callable($function)) {
			call_user_func($function, $this, $form);
		}
		else {
			$this->mall_search($tab, $form);
		}
	}

	function mall_search($tab, $form) {

		$opt = array(
				'phrase' => $form['keyword'],
				'status' => 'approved',
				'available'=> TRUE
				);

		$types = Product_Model::get_types();
		if (isset($types[$tab])) {
			$opt['type'] = $tab;
			$sub_header_view = V("prod_{$tab}:mall/sub_header", array('form'=>$form));
		}
		else {
			$sub_header_view = V("mall:sub_header", array('form'=>$form));
		}
		$opt['order_by'] = ' ORDER BY w DESC,`stock_status` DESC,`valid_fields` DESC,`weight` DESC';
		$products = new Search_Product($opt);

		$start = (int) $form['st'];
		$per_page = 6;

		$pagination = Site::pagination($products, $start, $per_page);

		$this->add_css('mall:search');

        $this->layout->body = V('mall:search/index', array(
            'products' => $products,
            'tab' => $tab,
            'category' => $category,
            'form' => $form,
            'pagination' => $pagination,
            'sub_header' => (string)$sub_header_view
        ));
	}

    function mall_public() {
        $form = Input::form();
        $key = trim($form['keyword']);
        $new_url = Config::get('mall.new_url');
        $params = ['q'=>$key, 'oauth-sso' => 'mall.nankai'];

        URI::redirect(URI::url($new_url.'/search/', $params));
    }
}

class Search_AJAX_Controller extends AJAX_Controller {

	function index_mall_search_rank_click() {
		$form = Input::form();
		$type = $form['type'];
		$rank_name = $form['rank_name'];
		$rank_sort = $form['rank_sort'];
		$uid = $form['uid'];
		$keyword = trim($form['keyword']);
		if ($rank_name && $rank_sort) {
			$view = Event::trigger('mall_search_rank_view', $type, $rank_name, $rank_sort, $keyword, $uid);
			Output::$AJAX['#'. $uid] = array(
				'data' => $view,
				'mode' => 'replace',
				);
		}
	}
}
