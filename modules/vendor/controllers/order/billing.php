<?php

class Order_Billing_Controller extends Order_Base_Controller {

	function _add_tab($vendor) {
		$me = L('ME');
		$bucket = Billing_Bucket_Model::vendor_bucket($vendor);

		$stabs = Widget::factory('tabs');
		$stabs
			->add_tab('statements', array(
				'url'=>'!vendor/order/billing/statements.'.$vendor->id,
				'title' => HT('结算单列表'),
			))
			->add_tab('bucket', array(
				'url'=>'!vendor/order/billing/bucket.'.$vendor->id,
				'title' => HT('结算夹'),
				'number' => $bucket->item_count() ?: NULL,
			))
			;

		$stabs->class = 'secondary_tabs';

		$content = V('vendor:billing/content', array('secondary_tabs'=>$stabs));

		$this->layout->body->primary_tabs->content = $content;
		$this->layout->body->primary_tabs->select('billing');
	}

	function index($vid=0) {
		return $this->statements($vid);
	}

	function bucket($vid=0) {
		$me = L('ME');
		$vendor = O('vendor', $vid);
		if (!$vendor->id) {
			URI::redirect('error/404');
		}

		$this->_add_index_tabs($vendor);
		$this->_add_tab($vendor);
		$bucket = Billing_Bucket_Model::vendor_bucket($vendor);

		$orders = Q("$bucket order:sort(ctime D)");

		$content = V('vendor:billing/bucket', array(
			'form' => $form,
			'vendor' => $vendor,
			'bucket' => $bucket,
			'orders' => $orders,
		));

		$stabs = $this->layout->body->primary_tabs->content->secondary_tabs;
		$stabs
			->set('content', $content)
			->select('bucket')
			;
	}

	function empty_bucket($vid=0) {
		$me = L('ME');
		$vendor = O('vendor', $vid);
		$bucket = Billing_Bucket_Model::vendor_bucket($vendor);
		if ($bucket->id) {
			$bucket->empty_bucket();
		}

		URI::redirect('!vendor/order/billing/bucket.'.$vendor->id);
	}

	function remove_order($id=0) {
		$me = L('ME');
		$order = O('order', $id);
		if ($order->vendor->id == $me->vendor->id || $order->vendor->has_member($me)) {
			$bucket = Billing_Bucket_Model::vendor_bucket($order->vendor);
			if ($bucket->id && $order->id) {
				$bucket->remove_item($order);
			}
		}
		URI::redirect('!vendor/order/billing/bucket.'.$order->vendor->id);
	}

	function to_statement($vid=0) {
		$me = L('ME');

		$vendor = O('vendor', $vid);
		if (!$vendor->id) {
			URI::redirect('error/404');
		}

		$bucket = Billing_Bucket_Model::vendor_bucket($vendor);
		if ($bucket->item_count() == 0) {
			URI::redirect('error/404');
		}
		$orders = Q("$bucket order");
		$balance = $orders->sum('price');
		$max_statement_price = Config::get('mall.max_billing_statement_price', 10000);
		if ($balance >= $max_statement_price) {
			Site::message(Site::MESSAGE_ERROR, HT('结算单金额过大, 单笔金额不得超过 %max .', ['%max'=>$max_statement_price]));
			URI::redirect('!vendor/order/billing/bucket.'.$vendor->id);
		}
		//增加锁机制，每个bucket只能有一个在生成付款单
		$mutex_file = Config::get('system.tmp_dir').Misc::key('bucket', $bucket->id);
		$fp = fopen($mutex_file, 'w+');
		if($fp){
			if (flock($fp, LOCK_EX | LOCK_NB)) {
				$db = Database::factory();
				$db->begin_transaction();
				$statement = O('billing_statement');
				$statement->vendor = $vendor;
				$statement->account_method = Config::get('account.default_account', NULL);
				$statement->save();

				if ($statement->id) {

                    $balance = 0;
                    $effectiveOrdersCount = 0;
					foreach($orders as $order) {
						//如果已经在其他结算单中，则不进行处理
						$statement_count = Q("$order billing_statement")->total_count();
                        if($statement_count) {
							$bucket->disconnect($order);
                            continue;
                        }
                        $effectiveOrdersCount ++;

						if ($order->set_pending_payment()) {
							$bucket->disconnect($order);
							$statement->connect($order);
							$balance += $order->price;
						}
					}

                    if ($effectiveOrdersCount) {
                        $statement->balance = $balance;
                        $statement->save();
                        $db->commit();
                        flock($fp, LOCK_UN);
                        fclose($fp);
                        //结算log
                        $log = sprintf('%s[%s] 生成了结算单 #%d',
                                L('ME')->name, L('ME')->id,
                                $statement->id
                                );
                        Log::add($log, 'order');
                        Site::message(Site::MESSAGE_NORMAL, HT('成功生成结算单'));
                        URI::redirect($statement->url());
                    } else {
                        $db->rollback();
                        flock($fp, LOCK_UN);
                        fclose($fp);
                        Site::message(Site::MESSAGE_ERROR, HT('生成结算单失败, 订单已经被结算过了'));
                        URI::redirect($bucket->url(NULL, NULL, NULL, 'view'));
                    }
				}
				else {
					flock($fp, LOCK_UN);
					fclose($fp);
					Site::message(Site::MESSAGE_ERROR, HT('生成结算单失败'));
					URI::redirect($bucket->url(NULL, NULL, NULL, 'view'));
				}
			}
			else{
				Site::message(Site::MESSAGE_ERROR, HT('系统繁忙, 请稍后重试'));
				URI::redirect('!vendor/order/billing/statements.'.$vendor->id);
			}
		}
	}


    function goStatements($id=0)
    {
        $vendor = O('vendor', ['gapper_group'=>$id]);
        if ($vendor->id) {
            return $this->statements($vendor->id);
        }
        URI::redirect('error/401');
    }
	function statements($vid=0, $tab = 'draft') {
		$me = L('ME');

		//$vendor = $me->vendor;
		$vendor = O('vendor', $vid);

		$this->_add_index_tabs($vendor);
		$this->_add_tab($vendor);
		if (!$vendor->id) {
			URI::redirect('error/404');
		}

		$form = Site::form();

		$selector = "billing_statement[vendor=$vendor]";

		$status_tabs = Widget::factory('tabs');
		$status_tabs->class = 'panel_tabs';

		$status_filters = array(
		    Billing_Statement_Model::STATUS_DRAFT => NULL,
		    Billing_Statement_Model::STATUS_REJECTED => NULL,
		    Billing_Statement_Model::STATUS_PENDING_CHECK => NULL,
		    Billing_Statement_Model::STATUS_PAID => NULL,
		);

		$no_count = array(
		    Billing_Statement_Model::STATUS_PAID,
		);

		$found_tab = FALSE;
		foreach ($status_filters as $sf => $title) {
			$label = Billing_Statement_Model::$status_label[$sf];
			if (is_null($title)) $title = T(Billing_Statement_Model::$status[$sf]);

			if ($label == $tab) $found_tab = TRUE;
			$tab_data = array(
		            'url' => URI::url('!vendor/order/billing/statements.'.$vendor->id.'.'.$label),
		            'title' => H($title),
		        );

			if (!in_array($sf, $no_count)) {
				$count = Q("billing_statement[status=$sf][vendor={$vendor}]")->total_count();
				if ($count > 0) {
					$tab_data['number'] = $count;
				}
			}

		    $status_tabs->add_tab($label, $tab_data);
		}

		$label_status = array_flip(Billing_Statement_Model::$status_label);
		if ($found_tab) {
			$status = $label_status[$tab];
		}
		else {
			reset($status_filters);
			$status = key($status_filters);
			$tab = Billing_Statement_Model::$status_label[$status];
		}

		$status_tabs->select($tab);

		$selector .= "[status=$status]";

		$selector .= ":sort(ctime D)";

		$statements = Q($selector);

		$start = (int)$form['st'];
		$per_page = 20;
		$pagination = Site::pagination($statements, $start, $per_page);

		$content = V('vendor:billing/statements', array(
			'form' => $form,
			'statements' => $statements,
			'pagination' => $pagination,
			'status_tabs' => $status_tabs,
		));

		$stabs = $this->layout->body->primary_tabs->content->secondary_tabs;

		$stabs
			->set('content', $content)
			->select('statements')
			;
	}

    function export($id=0)
    {
        $statement = O('billing_statement', $id);

        if (!$statement->id) {
            URI::redirect('error/404');
        }

        $pdf = new TCPDF();
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetAutoPageBreak(TRUE, 0);
        $pdf->AddPage();
        $pdf->SetFont('cid0cs', '', 10);

        $html = V('vendor:billing/print/pdf',['statement' => $statement]);
        $style = array(
            'border' => 0,
            'padding' => 0,
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        );
        $pdf->writeHTML($html);
        $url = URI::url('/show/billing.'.$statement->id);
        $pdf->write2DBarcode($url, 'QRCODE,L', 170, 30, 25, 25, $style, 'N');
        $pdf->Output('清单打印-'.$id.'.pdf', 'I');
    }

}

class Order_Billing_AJAX_Controller extends AJAX_Controller {

	function index_extra_info_click () {
		$form = Input::form();
		$statement = O('billing_statement', $form['id']);
		if (!$statement->id) return;
		JS::dialog( V('vendor:billing/extra', array('statement'=>$statement)));
	}

	function index_extra_info_submit () {
		$form = Form::filter(Input::form());
		$statement = O('billing_statement', $form['id']);
		if (!$statement->id) return;
		$voucher_no = $form['voucher_no'];
		$vendor_note = $form['vendor_note'];
		$form
			->validate('voucher_no', 'not_empty', T('凭证单号不能为空!'));
		$full_path = NFS::get_path($statement, '', 'attachments', TRUE);
		$files = NFS::file_list($full_path,'');
		if (count($files) == 0) {
			$errors = $form->errors;
			$errors[] = ['请上传附件'];
			$form->errors = $errors;
			$form->no_error = false;
		}
		if (!$form->no_error) {
			JS::dialog( V('vendor:billing/extra', array(
				'statement'=>$statement,
				'form'=>$form
				)));
		}
		else {
			$statement->voucher_no = $voucher_no;
			$statement->vendor_note = $vendor_note;
			if ($statement->save()) {
				Site::message(Site::MESSAGE_NORMAL, HT('结算信息补充成功!'));
			}
			else {
				Site::message(Site::MESSAGE_ERROR, HT('结算信息补充失败!'));
			}
			JS::refresh();
		}

	}
}
