<?php

class Autocomplete_Controller extends AJAX_Controller {

	function brand($manufacturer = '') {

		$s = Input::form('s');
		$mall_brand = Config::get('mall.brand');

		try{

			$rpc = new RPC($mall_brand['api']);
			$rpc->mall->brand->authorize($mall_brand['client_id'], $mall_brand['client_secret']);

			$params['name'] = $s;
			$params['abbr'] = $s;
			$params['alias'] = $s;
			$params['company'] = $manufacturer;

			$result = $rpc->mall->brand->searchBrands($params);
			$brands = $rpc->mall->brand->getBrands($result['token'], 0, 5);

			if (!count($brands)) {
				Output::$AJAX[] = array(
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				);
			}
			else {
				foreach ($brands as $brand) {

					Output::$AJAX[] = array(
						'html' => (string) V('autocomplete/brand', array('brand'=>$brand)),
						'alt' => $brand['name'],
						'text' => $brand['name'],
						'manufacturer' => $brand['company'],
					);
				}

				$rest = $result['total_count'] - count($brands);
				if ($rest > 0) {
					Output::$AJAX[] = array(
						'html' => (string) V('autocomplete/special/rest', array('rest' => $rest)),
						'special' => TRUE
					);
				}
			}
		}
		catch(Exception $e) {
			error_log($e->getMessage());
		}

	}

	function manufacturer() {

		$s = Input::form('s');
		$mall_brand = Config::get('mall.brand');

		try{

			$rpc = new RPC($mall_brand['api']);
			$rpc->mall->brand->authorize($mall_brand['client_id'], $mall_brand['client_secret']);

			$params['company'] = $s;
			$result = $rpc->mall->brand->searchBrands($params);
			$brands = $rpc->mall->brand->getBrands($result['token'], 0, 5);

			if (!count($brands)) {
				Output::$AJAX[] = array(
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				);
			}
			else {
				foreach ($brands as $brand) {
					Output::$AJAX[] = array(
						'html' => (string) V('autocomplete/manufacturer', array('brand'=>$brand)),
						'alt' => $brand['company'] ?: '',
						'text' => $brand['company'] ?: '',
						'brand' => $brand['name'] ?: '',
					);
				}

				$rest = $result['total_count'] - count($brands);
				if ($rest > 0) {
					Output::$AJAX[] = array(
						'html' => (string) V('autocomplete/special/rest', array('rest' => $rest)),
						'special' => TRUE
					);
				}
			}
		}
		catch(Exception $e) {
		}
	}
}