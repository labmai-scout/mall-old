<?php

class Transfer_Index_Controller extends Transfer_Base_Controller {

	function index($tabs='transfer', $stabs='pending_transfer') {

		$form = Site::form();

		$this->layout->body->primary_tabs->select($tabs);

		$status_filters = array(
		    Transfer_Statement_Model::STATUS_DRAFT => NULL,
		    Transfer_Statement_Model::STATUS_PENDING_TRANSFER => NULL,
		    Transfer_Statement_Model::STATUS_TRANSFERRED => NULL,
		    Transfer_Statement_Model::STATUS_FAILED => NULL,
		);

		$status_tabs = Widget::factory('tabs')->set('class', 'secondary_tabs');

		foreach ($status_filters as $sf => $title) {
			$label = Transfer_Statement_Model::$status_label[$sf];
			if (is_null($title)) $title = T(Transfer_Statement_Model::$status[$sf]);
			if ($label == $stabs) $found_tab = TRUE;
			$tab_data = array(
		            'url' => URI::url('!admin/transfer/index/index.transfer.'.$label),
		            'title' => H($title),
		        );

		    $status_tabs->add_tab($label, $tab_data);
		}

		$selector = 'transfer_statement';

		$label_status = array_flip(Transfer_Statement_Model::$status_label);
		if ($found_tab) {
			$status = $label_status[$stabs];
		}
		else {
			reset($status_filters);
			$status = key($status_filters);
		}
		if ($form['ref_no']) {
			$ref_no = preg_replace('/^0*/', '', $form['ref_no']);
			$selector .="[id*={$ref_no}|voucher*={$ref_no}]";
		}
		$pre_selectors = new ArrayIterator;
		if ($form['customer']) {
			$customer = Q::quote($form['customer']);
			$pre_selectors[] = "customer[name*=$customer]";
		}
		if ($form['customer_owner']) {
			$customer_owner = Q::quote($form['customer_owner']);
			$pre_selectors[] = "user<owner[name*=$customer_owner|name_abbr^=$customer_owner] customer";
		}


		$status_tabs->select($stabs);

		if (count($pre_selectors) > 0) {
			$selector = '('.implode(', ', (array)$pre_selectors).') ' . $selector;
		}

		$selector .= "[status=$status]:sort(ctime D)";

		$statements = Q($selector);

		$pagination = Site::pagination($statements, (int)$form['st'], 20);
		$selector .= ' order';
		$orders = Q($selector);
		$amount = $orders->sum('price');
		$total_count = $orders->total_count();

		$status_tabs->content = V('admin:statement/list', array(
			'amount' => $amount,
			'total_count'=> $total_count,
			'pagination' => $pagination,
			'statements' => $statements,
			'form' => $form,
			'secondary_tabs'=>$status_tabs
		));

		$content = V('admin:statement', array('secondary_tabs' => $status_tabs));

		$this->layout->body->primary_tabs->content = $content;


	}
}
