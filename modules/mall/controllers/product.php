<?php

class Product_Controller extends Layout_Mall_Controller {

    function _before_call($method, &$params) {
        parent::_before_call($method, $params);

        //传递product的type
        $this->layout->sidebar->params = array(
            'vendor_type'=> O('product', current($params))->type
        );
    }
    function mall_list($id=0) {
    	$product = O('product', $id);
		if (!$product->id) {
			URI::redirect('error/404');
		}
		
		$selector = "product[publish_date>0][approve_date>0][!freeze_reasons]";
		foreach (Product_Model::get_merge_criterias($product->type) as $name => $value) {
			$selector .= "[$name={$product->$name}]";
		}

		$form = Site::form();
		$sort_by = $form['sort'];
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';
		switch ($sort_by) {
		case 'stock_status':
			$selector .= ":sort(stock_status $sort_flag, unit_price A)";
			break;
		case 'unit_price':
			// 此项虽与 default 有些重复, 但分开写当 "按单价排序" 时处理更正常(xiaopei.li@2012-08-31)
			$selector .= ":sort(unit_price $sort_flag, stock_status D)";
			break;
		default:
			$selector .= ":sort(unit_price A, stock_status D)";
		}
		$products = Q($selector);
		$n_vendors = $products->total_count();

		$start = (int) $form['st'];
		$per_page = 15;
		$start = $start - ($start % $per_page);
		$pagination = Site::pagination($products, $start, $per_page);

		$types = Product_Model::get_types();
		$tab = $product->type;
		if (!isset($types[$tab]))$tab = key($types);

		$this->layout->nav_tabs = V('mall:tab', array('select_tab' => $tab));
		$this->add_css("prod_{$tab}:theme");
		$this->layout->body = V("prod_{$tab}:mall/vendor_product/list", array(
			'product' => $product,
			'sub_header' => (string)V("prod_{$tab}:mall/sub_header"),
			'products' => $products,
			'n_vendors' => $n_vendors,
			'pagination' => $pagination
		));
    }
	function index($id=0) {
		$product = O('product', $id);
		if (!$product->id) {
			URI::redirect('error/404');
		}

		$types = Product_Model::get_types();
		$tab = $product->type;
		if (!isset($types[$tab]))$tab = key($types);

		//$this->layout->nav_tabs->select($tab);

		$this->layout->nav_tabs = V('mall:tab', array('select_tab' => $tab));
		$this->layout->sub_header = V("prod_{$tab}:mall/sub_header");

		$this->add_css("prod_{$tab}:theme");
		$this->layout->body = V("prod_{$tab}:mall/vendor_product/view", array('product' => $product));
		
	}
}

class Product_AJAX_Controller extends AJAX_Controller {

	function index_preview_click() {
		 $form = Input::form();
		 $product = O('product', $form['id']);

		 if (!$product->id) return;

		 $hooked_view = Event::trigger('mall.product.preview', $product);
		 Output::$AJAX['preview'] = (string) ($hooked_view ? :
											V('mall:vendor_product/preview',
												array('product'=>$product)));

	}
}
