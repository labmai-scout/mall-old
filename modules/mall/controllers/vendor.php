<?php

class Vendor_Controller extends Layout_Mall_Controller {

	function index($sort_word=null) {

		$form = Site::form();

		$selector = 'vendor[approve_date>0]';
		if ($form['phrase']) {
			$phrase = $form['phrase'];
			$selector .= "[name*=$phrase|short_name*=$phrase]";
		}

		if ($sort_word) {
			if ($sort_word == 'extra') {
				$selector .= ':not(vendor[short_abbr^=A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z])';
			}
			else {
				$selector .= "[short_abbr^=$sort_word]";
			}
		}
		$selector .= ":sort(short_abbr A)";
		$vendors = Q($selector);
		$this->layout->nav_tabs = V('mall:tab', array('select_tab' => 'vendor'));

		$this->add_css("mall:content");

		$per_page = 10;

		$start = (int) $form['st'];
		$pagination = Site::pagination($vendors, $start, $per_page);

		$this->layout->title = HT('供应商列表');
		$this->layout->body = V('mall:vendor/search', array(
										'vendors' => $vendors,
										'pagination' => $pagination,
										'form'=>$form,
										'sort_word'=>$sort_word,
									));
	}

	function view($id=0, $tab='products') {
		// 未上架的 vendor 也应在前台显示, 供查看历史订单等用 (xiaopei.li@2012-05-07)

		$vendor = O('vendor', $id);
		if (!$vendor->id) $vendor = L('ME')->vendor;

		if (!$vendor->id) URI::redirect('error/404');

		$this->layout->nav_tabs = V('mall:tab', array('select_tab' => 'vendor'));

		Event::bind('vendor.profile.view.content', array($this, '_view_products'), 0, 'products');
		// 隐藏 info 页 (xiaopei.li@2012-04-12)
		// Event::bind('vendor.profile.view.content', array($this, '_view_info'), 0, 'info');
		Event::bind('vendor.profile.view.content', array($this, '_view_ratings'), 0, 'ratings');

		$vendor_tabs = Widget::factory('tabs');
		$vendor_tabs
			->add_tab('products', array(
				'title' => T('供应商品'),
				'url' => $vendor->url('products'),
			))
			/*
			// 隐藏 info 页 (xiaopei.li@2012-04-12)
			->add_tab('info', array(
				'title' => T('详细信息'),
				'url' => $vendor->url('info'),
			))
			*/
			->add_tab('ratings', array(
				'title' => T('买家评价'),
				'url' => $vendor->url('ratings'),
			))
			->set('vendor', $vendor)
			->tab_event('vendor.profile.view.tab')
			->content_event('vendor.profile.view.content')
			->select($tab);

		$this->add_css("mall:vendor");

		$this->layout->title = H($vendor->name);
		$this->layout->body = V('mall:vendor/view', array(
			'vendor'=>$vendor,
			'vendor_tabs'=>$vendor_tabs));
	}

	function _view_info($e, $tabs) {
		$vendor = $tabs->vendor;
	}

	function _view_products($e, $tabs) {
		$form = Site::form();
		$vendor = $tabs->vendor;

		$types = Product_Model::get_types();
		// TODO filter the types that this vendor can supply(xiaopei.li@2012-04-04)

		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
			->set('class', 'secondary_tabs');

		if ($types) { // TODO 是否要 if (count($types) > 1) 才显示 tab ?(xiaopei.li@2012-04-04)
			foreach ($types as $type => $type_name) {
				$secondary_tabs
					->add_tab($type, array(
						'url' => $vendor->url('products', H(array('type' => $type))),
						'title' => T($type_name),
					));
			}

			// 确定当前所在 tab
			$tab = $form['type'];
			$type_keys = array_keys($types);

			if (!$tab) {
				$tab = $type_keys[0];
			}
			else if (!in_array($tab, $type_keys)) {
				URI::redirect('error/404');
			}

			$secondary_tabs
				->select($tab);

			$function = "Product_{$tab}::mall_view_products";
			if (is_callable($function)) {
				$content = call_user_func($function, $vendor, $form);
			}
			else {
				$content = $this->view_products($vendor, $tab, $form);
			}

			$secondary_tabs
				->set('content', $content);

		}

		$tabs->content = V('mall:vendor/view.products',
						array(
							'secondary_tabs' => $secondary_tabs,
							));
	}

	function _view_ratings($e, $tabs) {
		$vendor = $tabs->vendor;
		$tabs->content = V('mall:vendor/view.ratings', array(
							   'vendor' => $vendor));
	}

	private function view_products($vendor, $tab, $form) {

		$products = new Search_Product( array(
					'type' => $tab,
					'status' => 'approved',
					'vendor_id' => $vendor->id,
					'phrase' => $form['phrase'],
					));

		$start = (int) $form['st'];
		$per_page = 15;

		$pagination = Site::pagination($products, $start, $per_page);

		return V('mall:vendor/products',
					array('form' => $form,
						  'products' => $products,
						  'pagination' => $pagination,
						));

	}
}
