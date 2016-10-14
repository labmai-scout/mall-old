<?php

class Order_Index_Controller extends Order_Base_Controller {

    function go($id=0)
    {
        $vendor = O('vendor', ['gapper_group'=>$id]);
        if ($vendor->id) {
            return $this->index($vendor->id);
        }
        URI::redirect('error/401');
    }

	function index($vid=0, $tab='all') {
		$me = L('ME');

		$vendor = O('vendor', $vid);

		$this->_add_index_tabs($vendor);

		$status_tabs = Widget::factory('tabs');
		$status_tabs->class = 'secondary_tabs';

		// 全部订单 tab
		$label_all = 'all';
		$status_tabs->add_tab($label_all, array(
						'url' => $vendor->url($label_all, NULL, NULL, 'vendor_order'),
						'title' => HT('全部'),
						));

		// 订单发货状态相关的 tab
		$label_not_delivered = 'not_delivered';

		$not_delivered_selector = "order[vendor={$vendor}]" .
			"[deliver_status=" . Order_Model::DELIVER_STATUS_NOT_DELIVERED. "]" .
			"[status!=" . Order_Model::STATUS_REQUESTING . "]" .
			"[status!=" . Order_Model::STATUS_NEED_VENDOR_APPROVE . "]" .
			"[status!=" . Order_Model::STATUS_REQUESTING . "]" .
			"[status!=" . Order_Model::STATUS_NEED_CUSTOMER_APPROVE . "]" .
			"[status!=" . Order_Model::STATUS_PENDING_APPROVAL . "]" .
			"[status!=" . Order_Model::STATUS_RETURNING . "]" .
			"[status!=" . Order_Model::STATUS_RETURNING_APPROVAL . "]";

		$count = Q($not_delivered_selector)->total_count();

		$tab_data = array(
			'url' => $vendor->url($label_not_delivered, NULL, NULL, 'vendor_order'),
			'title' => '待发货',
			'weight' => 20
			);
		if ($count > 0) {
			$tab_data['reminder'] = TRUE;
		}
		$status_tabs->add_tab($label_not_delivered, $tab_data);

		// end 订单发货状态相关的 tab

		// 订单状态相关的 tab
		// TODO 以"未付款"替换 APPROVED + PENDING_TRANSFER (xiaopei.li@2012-04-19)
		$status_filters = array();
		if (Config::get('order.admin_approval_required')) {
			$status_filters = array(
					Order_Model::STATUS_PENDING_APPROVAL => array('weight'=>25),
				);
		}
		$status_filters += array(
			Order_Model::STATUS_NEED_VENDOR_APPROVE => array('weight'=>10, 'title'=>T('待确认')),
			//Order_Model::STATUS_NEED_CUSTOMER_APPROVE => array('weight'=>20),
			Order_Model::STATUS_APPROVED => array('weight'=>30),
			Order_Model::STATUS_RETURNING => array('weight'=>35),
			Order_Model::STATUS_RETURNING_APPROVAL => array('weight'=>37),
			Order_Model::STATUS_PENDING_TRANSFER => array('weight'=>40),
			Order_Model::STATUS_TRANSFERRED => array('weight'=>45),
			Order_Model::STATUS_PENDING_PAYMENT => array('weight'=>50),
			Order_Model::STATUS_PAID => array('weight'=>55),
			Order_Model::STATUS_CANCELED => array('weight'=>55),  //增加取消选项卡[by sunxu 2015-04-09]
		);

		$no_count = array(
			Order_Model::STATUS_PAID,
		);

		$tab_is_status = FALSE;
		foreach ($status_filters as $sf => $rows) {
			$label = Order_Model::$status_label[$sf];
			if (!($rows['title'])) {
				$title = T(Order_Model::$status[$sf]);
			}
			else {
				$title = $rows['title'];
			}

			if ($label == $tab) $tab_is_status = TRUE;
			/*TODO 简略方案，提升待发货/收货的weight 后期可能需要做统一的weight调正*/
			if ($sf <= Order_Model::STATUS_RETURNING) {
				$weight = $sf;
			}
			elseif ($sf == Order_Model::STATUS_NEED_CUSTOMER_APPROVE) {
				$weight = 2;
			}
			elseif ($sf == Order_Model::STATUS_RETURNING_APPROVAL) {
				$weight = 4;
			}
			else {
				$weight = $sf + 40;
			}

			$tab_data = array(
		            'url' => $vendor->url($label, NULL, NULL, 'vendor_order'),
		            'title' => H($title),
		            'weight' => $weight
		        );

			if (!in_array($sf, $no_count)) {

				//增加取消选项卡tab标红筛选掉审核未通过的总数查询 fix bug 8592  by sunxu 2015-04-15

				if($sf==Order_Model::STATUS_CANCELED){

					//管理方，买方，供应商的“已取消”，红点都不要 fix bug 8592  by sunxu 2015-04-16
/*
					$countCancel = Q("order[vendor=$vendor][status=$sf][customer_approved=1]")->total_count();

					if ($countCancel > 0) {
						$tab_data['reminder'] = TRUE;
					}
*/
				}else{

					$count = Q("order[vendor=$vendor][status=$sf]")->total_count();

					if ($count > 0) {
						$tab_data['reminder'] = TRUE;
					}

				}


			}
			else {
				$count1 = Q("$me<has_news order_item order[vendor={$vendor}][status={$sf}]:limit(1)")->length();
				$count2 = Q("$me<has_news order[vendor={$vendor}][status={$sf}]:limit(1)")->length();
				$count = $count1 + $count2;
				if ($count > 0) {
					$tab_data['reminder'] = TRUE;
				}
			}
		    $status_tabs->add_tab($label, $tab_data);
		}
		$db = Database::factory();
		$join = [];
		$where = [];
		$SQL = "SELECT DISTINCT order.id FROM `order` ";

		$label_status = array_flip(Order_Model::$status_label);

		if ($tab != $label_all) {
			if ($tab_is_status) {
				$status = $label_status[$tab];
			}
			else {
				if ($label_not_delivered != $tab) {
					URI::redirect('error/404');
				}
			}
		}

		$type = Input::form('type');
		if ($type == 'print') {
			return $this->_index_print();
		}

		$form = Site::form();

		$status_tabs->select($tab);
		$vendor_id = $vendor->id;
		$where[] = "order.vendor_id='$vendor_id'";

		if (isset($status)) {
			$where[] = "order.status=$status ";

			//当为取消订单的查看请求，增加过滤仅能看到含有买方管理确认状态的订单 by sunxu 2015-04-09

			if($status==Order_Model::STATUS_CANCELED){

				$where[] = "order.customer_approved=1 ";

			}

		}
		else {
			//修正bug 8606 在供应商全部查看选项卡中增加能显示通过买方确认后的取消的订单 by sunxu 2015-04-15
			//修正bug 8613 在供应商全部查看选项卡中无显示内容报错，并筛选掉取消操作且买方确认过的数据的问题，上次修改算法不严谨 by sunxu 2015-04-16
			$tmp_status_filter=implode(',',array(
                Order_Model::STATUS_REQUESTING,
                Order_Model::STATUS_NEED_CUSTOMER_APPROVE,
                Order_Model::STATUS_NEED_MANAGER_APPROVE,
				Order_Model::STATUS_CANCELED
			));

			$tmp_status_cancel=Order_Model::STATUS_CANCELED;

			$where[] = "(order.status not in ($tmp_status_filter) or (order.status =$tmp_status_cancel and order.customer_approved=1))";
		}
		if ($form['keyword']) {
			$keyword = $db->escape(trim($form['keyword']));
			$join[] = "LEFT JOIN order_item  ON (order.id =order_item.order_id) ";
			$join[] = "LEFT JOIN product ON (product.id=order_item.product_id) ";
			$where[] =  "(product.name LIKE '%$keyword%' or order.order_no LIKE '%$keyword%')";
		}

		if ($form['customer'] || $form['customer_owner']) {
			$customer = $db->escape($form['customer']);
			$customer_owner = $db->escape($form['customer_owner']);
			$join[] = "LEFT JOIN customer ON (customer.id=order.customer_id) ";
			$join[] = "LEFT JOIN user ON (user.id=customer.owner_id) ";
			if ($customer) {
				$where[] = "customer.name LIKE '%$customer%'";
			}
			if ($customer_owner) {
				$where[] = "user.name LIKE '%$customer_owner%'";
			}
        }

		if ($label_not_delivered == $tab) {
			$deliver_status = Order_Model::DELIVER_STATUS_NOT_DELIVERED;
			$where[] = "order.deliver_status=$deliver_status";
			$arr = array(
				Order_Model::STATUS_REQUESTING,
				Order_Model::STATUS_NEED_VENDOR_APPROVE,
				Order_Model::STATUS_PENDING_APPROVAL,
				Order_Model::STATUS_RETURNING,
				Order_Model::STATUS_RETURNING_APPROVAL,
				Order_Model::STATUS_CANCELED,
				Order_Model::STATUS_NEED_CUSTOMER_APPROVE,
				);
			$statuses = implode(',', $arr);
			$where[] = "order.status not in ($statuses)";
		}



		if (count($join)) {
			$SQL .= implode(' ', $join);
		}
		if (count($where)) {
			$SQL .= 'WHERE '.implode(' AND ', $where);
		}
		$form_token = Session::temp_token('vendor_order_',300);
		$SQL .= ' GROUP BY order.id ORDER BY order.ctime DESC ';
		$_SESSION[$form_token] = $SQL;
		$num = $db->query($SQL)->count();

		$start = (int) $form['st'];
		$per_page = 20;
		$start = $start - ($start % $per_page);
		if ($start > 0) {
			$last = floor($num/ $per_page) * $per_page;
			if ($last == $num) {
				$last = max(0, $last - $per_page);
			}
			if ($start > $last) {
				$start = $last;
			}
			$SQL .= 'LIMIT '.$start.','.$per_page;
			$result = $db->query($SQL);
		}
		else {
			$SQL .= 'LIMIT 0,'.$per_page;
			$result = $db->query($SQL);
		}
		$orders = [];
		if ($num > 0) {
			$objs = $result->rows();
			foreach ($objs as $obj) {
				$ids[] = $obj->id;
			}
			$ids = implode(',', $ids);
			$orders = Q("order[id=$ids]:sort(ctime D, id D)");
		}

		$pagination = Widget::factory('pagination');
		$pagination->set(array(
							 'start' => $start,
							 'per_page' => $per_page,
							 'total' => $num,
							 ));

		$content = V('vendor:orders/list', array(
			'form' => $form,
			'orders' => $orders,
			'pagination' => $pagination,
			'status_tabs' => $status_tabs,
			'form_token' => $form_token,
			'vendor' => $vendor,
		));

		$this->layout->body->primary_tabs
			->set('content', $content)
			->select('index');

	}

    function _index_print() {
		$SQL = $_SESSION[Input::form('form_token')];

        $printFrom = Input::form('from');
        $printFrom = $printFrom ? (int)$printFrom : 0;
        $printTo = Input::form('to');
        $printTo = $printTo ? (int)$printTo : time();
        $where = "order.ctime BETWEEN {$printFrom} AND {$printTo}";
        $count = 1;
        $SQL = str_replace('WHERE', "WHERE {$where} AND", $SQL, $count);

		$db = Database::factory();
		$orders = $db->query($SQL)->rows();
        $this->layout = V('vendor:orders/order_print', array(
            'orders'=>$orders,
            'from'=> $printFrom,
            'to'=> $printTo
        ));
	}

	function view($id=0) {

		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('以供应商查看', $order)) {
			URI::redirect('error/401');
		}

		$vendor = $order->vendor;
		$this->_add_index_tabs($vendor);

		$order->unset_has_news_to($me);

		$form = Input::form();
		if ($form['submit_note']) {
			$order->vendor_note = $form['vendor_note'];
			$order->save();
			Site::message(Site::MESSAGE_NORMAL, HT('备注已更新!'));
		}

        $base_url = Config::get('vendor.bind_wechat_url');
        $items = Q("order_item[order=$order]");
        foreach($items as $item) {
            $datas []= [
                'url'           => $base_url."/order/".SITE_ID."/$order->voucher/$item->product_id",
                'orderNo'       => $order->voucher,
                'productName'   => $item->product->name,
                'manufacturer'  => $item->product->manufacturer,
                'catalogNo'     => $item->product->catalog_no,
                'package'       => $item->product->package,
                '@times'        => intval($item->quantity),
            ];
        }

        $datas = json_encode($datas, JSON_UNESCAPED_UNICODE);

        $content = V('vendor:order/view', array(
            'order' => $order,
            'datas' => $datas,
        ));

		$this->layout->body->primary_tabs
			->add_tab('view', array(
				'url'=> $order->url(NULL, NULL, NULL, 'vendor_view'),
				'title'=> HT('订单 #%order_no', array('%order_no'=>$order->order_no)),
			))
			->set('content', $content)
			->select('view');

		$this->layout->title = HT('订单 #%order_no', array('%order_no'=>$order->order_no));
	}

	function confirm($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		if (!($order->vendor_can_confirm() &&
			  $me->is_allowed_to('供应商确认订单', $order))) {
			URI::redirect('error/401');
		}

		$order->version += 1;
		if ($order->vendor_confirm()) {
			Site::message(Site::MESSAGE_NORMAL, HT('订单已确认'));
        	$log = sprintf('[vendor] %s[%d]确认了订单#%s[%d]',
            $me->name, $me->id, $order->order_no, $order->id);
            Log::add($log, 'vendor');

            O('operation_time')->log_status($me,Operation_Time_Model::STATUS_VENDOR_APPROVE,$order);

		}
		else {
			Site::message(Site::MESSAGE_ERROR, HT('订单确认失败, 若订单包含待询价商品, 请修改为实价!'));
		}

        $callback = $order->url(NULL, NULL, NULL, 'vendor_view');

        URI::redirect($callback);

	}

    //打印清单
    function print_order_info($id=0) {
        $order = O('order', $id);
        if (!$order->id) {
        	URI::redirect('error/404');
        }

        //权限判断
        $vendor = $order->vendor;
        $me = L('ME');
        if (!$vendor->has_member($me)) {
            URI::redirect('error/401');
        }

        $customer = $order->customer;
        $vendor = $order->vendor;
        $voucher = $order->voucher;
        $node = SITE_ID;
        $conf = Config::get('tag.servers');
        $tag = "labmai-{$node}/{$customer->gapper_group}";
        $rpc = new RPC($conf['api']);
        $token = $rpc->tagdb->authorize($conf['client_id'], $conf['client_secret']);

        $school = $rpc->tagdb->data->get($tag);
        $school_name = $conf['school_name'];
        $pdf = new TCPDF();
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetAutoPageBreak(TRUE, 0);
        $pdf->AddPage();
        $pdf->SetFont('cid0cs', '', 14);

        $html = '
            <div>
                <h3 >订单&#160;'.$order->voucher.'</h3>
                <hr />
            </div>
        ';
        $pdf->writeHTML($html);

        $html = '
            <table width="100%">
                <tr>
                    <td width="20%">订单编号:</td>
                    <td width="30%">'.$order->voucher.'</td>
                    <td width="20%">课题组:</td>
                    <td width="30%">'.$customer->name.'</td>
                </tr>
            </table>
        ';
        $pdf->writeHTML($html);
        $html = '
            <table width="100%">
                <tr>
                    <td width="20%">订货日期:</td>
                    <td width="30%">'.date("Y-m-d", $order->purchase_date).'</td>
                    <td width="20%">联系人姓名:</td>
                    <td width="30%">'.$customer->owner->name.'</td>
                </tr>
            </table>
        ';
        $pdf->writeHTML($html);
        $html = '
            <table width="100%">
                <tr>
                    <td width="20%">供应商:</td>
                    <td width="30%">'.$vendor->name.'</td>
                    <td width="20%">联系人电话:</td>
                    <td width="30%">'.$order->phone.'</td>
                </tr>
            </table>
        ';
        $pdf->writeHTML($html);
        $html = '
            <table width="100%">
                <tr>
                    <td width="20%">供应商电话:</td>
                    <td width="30%">'.$vendor->phone.'</td>
                    <td width="20%">买方单位:</td>
                    <td width="30%">'.$school_name.'</td>
                </tr>
            </table>
        ';
        $pdf->writeHTML($html);
        $html = '
            <table width="100%">
                <tr>
                    <td width="20%">总额:</td>
                    <td width="30%">'.H(Number::currency($order->price)).'</td>
                    <td width="20%">部门:</td>
                    <td width="30%">'.$school['organization']['department_name'].'</td>
                </tr>
            </table>
        ';
        $pdf->writeHTML($html);
        $html = '
            <table width="100%">
                <tr>
                    <td width="20%">课题组地址:</td>
                    <td width="30%">'.$order->address.'</td>
                </tr>
            </table>
        ';
        $pdf->writeHTML($html);

        $html = '
            <hr />
        ';
        $pdf->writeHTML($html);

        $html = V('vendor:order/print/pdf',['order' => $order, 'customer' => $customer, 'vendor' => $vendor]);

        $pdf->writeHTML($html);
        $style = array(
            'border' => 0,
            'padding' => 0,
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false,
            'module_width' => 1,
            'text' => true,
            'module_height' => 1,
        );
        $node = SITE_ID;
        $base_url = Config::get('vendor.bind_wechat_url');
        $padding = 25;
        $qrWidth = $qrHeight = 15;
        $x = 179;
        $y = 138;
        foreach (Q("order_item[order=$order]") as $item){
            if (!$item->id) URI::redirect('error/404');
            $product = $item->product;
            $pid = $product->id;
            $pname = $product->name;

            $url = $base_url."/order/$node/$voucher/$pid";

            $arr = [
                'U' => $url,
                'R' => $voucher,
                'P' => $pname
            ];

            $info = base64_encode(json_encode($arr, JSON_UNESCAPED_UNICODE));
            $pdf->write2DBarcode($info, 'QRCODE,L', $x, $y, $padding, $qrWidth, $style, 'N');
            $y += 25;
        }

        $pdf->Output('清单打印-'.$id.'.pdf', 'I');

	}

	function recover($id=0) {
		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');

		if (!($order->vendor_can_recover() &&
			  $me->is_allowed_to('恢复', $order))) {
			URI::redirect('error/401');
		}

		$order->version += 1;
		$now = new \Datetime();
		$now = $now->format('Y-m-d H:i:s');
		$order->mall_description = [
			'a'=>H(T('**:user(:vendor)** 拒绝退货', [
		        	':user'=>$me->name,
		        	':vendor'=>$order->vendor->short_name
            	])),
            't'=>$now,
            'u'=>$me->gapper_user,
		];

		if ($order->recover()) {
			Site::message(Site::MESSAGE_NORMAL, HT('订单已恢复'));
            $log = sprintf('[vendor] %s[%d]将订单%s[%d]恢复',
            $me->name, $me->id, $order->order_no, $order->id);
            Log::add($log, 'vendor');
		}
		else {
			Site::message(Site::MESSAGE_ERROR, HT('订单恢复失败'));
		}

		URI::redirect($order->url(NULL, NULL, NULL, 'vendor_view'));
	}
}

class Order_Index_AJAX_Controller extends AJAX_Controller {
	function index_vendor_note_click () {
		$form = Input::form();
		$order = O('order', $form['order_id']);
		if (!$order->id) return;
		JS::dialog( V('vendor:order/vendor_note_form', array('order'=>$order)));
	}

	function index_vendor_note_submit () {
		$form = Input::form();
		$order = O('order', $form['order_id']);
		if (!$order->id) return;

		$order->vendor_note = $form['vendor_note'];
		$order->save();
		Site::message(Site::MESSAGE_NORMAL, HT('备注已更新!'));
		JS::refresh();

	}

	function index_to_bucket_click() {
		$form = Input::form();

		$order = O('order', $form['id']);
		if (!($order->id && $order->can_pay())) return;

		$bucket = Billing_Bucket_Model::vendor_bucket($order->vendor);
        if (!($order->id && $order->can_pay())) return;

		if (!L('ME')->is_allowed_to('申请结算', $order)) {
			return;
		}

		if (!$bucket->contains($order)) {
			$bucket->add_item($order);
			$links = $order->links('vendor_index');
			$links = array($links['remove_from_bucket']);

			JS::run(JS::smart()->jQuery('#'.$form['rel'])->popFade(0, 0, '<strong style="color:#f70">+1</strong>'));
			Output::$AJAX['#'.$form['rel']] = array('data'=>(string)Widget::factory('application:links', array('links'=>$links)), 'mode'=>'replace');
			Output::$AJAX['#bucket_button'] = array('data'=>(string)V('vendor:orders/bucket_button', array('vendor'=>$order->vendor)), 'mode'=>'replace');
		}
	}

	function index_remove_from_bucket_click() {

		$form = Input::form();
		$order = O('order', $form['id']);
		if (!$order->id) return;

		$bucket = Billing_Bucket_Model::vendor_bucket($order->vendor);
		if (!$bucket->id) return;

		if (!L('ME')->is_allowed_to('申请结算', $order)) {
			return;
		}

		if ($bucket->contains($order)) {
			$bucket->remove_item($order);

			$links = $order->links('vendor_index');
			$links = array($links['to_bucket']);

			JS::run(JS::smart()->jQuery('#'.$form['rel'])->popFade(0, 0, '<strong style="color:#f70">-1</strong>'));
			Output::$AJAX['#'.$form['rel']] = array('data'=>(string)Widget::factory('application:links', array('links'=>$links)), 'mode'=>'replace');
			Output::$AJAX['#bucket_button'] = array('data'=>(string)V('vendor:orders/bucket_button', array('vendor'=>$order->vendor)), 'mode'=>'replace');
		}
	}

	function view_to_bucket_click() {
		$form = Input::form();
		$order = O('order', $form['id']);
		if (!$order->id) return;

		$bucket = Billing_Bucket_Model::vendor_bucket($order->vendor);
		if (!$bucket->id) return;

		if (!L('ME')->is_allowed_to('申请结算', $order)) {
			return;
		}

		if (!$bucket->contains($order)) {
			$bucket->add_item($order);

			JS::redirect(URI::url('!vendor/order/billing/bucket.'.$order->vendor->id));
			/*
			$links = $order->links('vendor_view');
			$links = array($links['remove_from_bucket']);

			JS::run(JS::smart()->jQuery('#'.$form['rel'])->popFade(0, 0, '<strong style="color:#f70">+1</strong>'));
			Output::$AJAX['#bucket_button'] = array('data'=>(string)V('vendor:orders/bucket_button'), 'mode'=>'replace');
			Output::$AJAX['#'.$form['rel']] = array('data'=>(string)Widget::factory('application:links', array('links'=>$links)), 'mode'=>'replace');
			*/
		}

	}

	function view_remove_from_bucket_click() {
		$form = Input::form();
		$order = O('order', $form['id']);
		if (!$order->id) return;

		$bucket = Billing_Bucket_Model::vendor_bucket($order->vendor);
		if (!$bucket->id) return;

		if (!L('ME')->is_allowed_to('申请结算', $order)) {
			return;
		}

		if ($bucket->contains($order)) {
			$bucket->remove_item($order);

			$links = $order->links('vendor_view');
			$links = array($links['to_bucket']);

			/*
			Site::message(Site::MESSAGE_NORMAL, HT('已将订单移出结算夹!'));
			JS::refresh();
			*/
			JS::run(JS::smart()->jQuery('#'.$form['rel'])->popFade(0, 0, '<strong style="color:#f70">-1</strong>'));
			Output::$AJAX['#bucket_button'] = array('data'=>(string)V('vendor:orders/bucket_button', array('vendor'=>$order->vendor)), 'mode'=>'replace');
			Output::$AJAX['#'.$form['rel']] = array('data'=>(string)Widget::factory('application:links', array('links'=>$links)), 'mode'=>'replace');
		}
	}

	function index_edit_item_click() {
		$item = O('order_item', Input::form('id'));

		//如果供货商确认过则不可再修改
		if (!$item->id) {
			return;
		}

		$order = $item->order;

		$me = L('ME');

		if (!($order->vendor_can_edit() &&
			  $me->is_allowed_to('以供应商修改', $order))) {
			return;
		}

		JS::dialog(V('vendor:order/edit_item', array('item' => $item)), array('title' => HT('修改项目')));
	}

    function index_recover_item_click() {
        if (!JS::confirm(HT('您确定恢复该商品吗?'))) {
            return FALSE;
        }

        $item = O('order_item', Input::form('id'));
        if (!$item->id) {
            return;
        }

        $item->temp_delete = false;
        $item->save();

        $content = T('%user(供应商: %vendor) 将 %product_name(%product_id) 从订单中恢复。',
				   array('%user' => L('ME')->name,
				   		 '%vendor' => $item->order->vendor->name,
				   		 '%product_name' => $item->product->name,
				   		 '%product_id' => $item->product->id,
					   ));
		self::create_comment($item->order, $content);

        JS::refresh();
    }

	function index_edit_item_submit() {
		$form = Input::form();
		$item = O('order_item', Input::form('id'));
		$max_order_price = round(Config::get('mall.max_order_price', 100000), 2);
		$order_price = $item->order->temp_price?:$item->order->price;
		$item_price = $item->quantity * round($item->unit_price, 2);
		if (!$item->id) {
			return;
		}
		if (!$item->canChangeQuantity()) {
			$form['quantity'] = $item->quantity;
		}
		$modify_order_price = $order_price - $item_price + round($form['unit_price'], 2) * (int)$form['quantity'];
		if ($modify_order_price >= $max_order_price) {
			JS::alert(HT('订单金额超过%max_price, 不允许修改!', ['%max_price'=>$max_order_price]));
			return;
		}

		$order = $item->order;

		$me = L('ME');

		if (!($order->vendor_can_edit() &&
			  $me->is_allowed_to('以供应商修改', $order))) {
			return;
		}


		if ($form['submit']) {
            //记录备注信息
            $now = new \datetime();
            $now = $now->format('Y-m-d H:i:s');
            $mall_description = $order->mall_description ?: [];
            $descriptions = $mall_description['d'] ?: [];

			if ('delete' == $form['submit'] || $form['quantity'] <= 0) {
				// 删除
				if (Q("order_item[order=$order]")->total_count() > 1) {
					$content = T('%user(供应商: %vendor) 将 %product_name 从订单中移除。',
							   array('%user' => $me->name,
							   		 '%vendor' => $order->vendor->name,
							   		 '%product_name' => $item->product->name,
								   ));
					self::create_comment($order, $content);

                    $item->temp_delete = true;
                    $item->temp_quantity = NULL;
                    $item->temp_unit_price = NULL;
                    $item->temp_price = NULL;
                    $item->save();

					if ($order->status == Order_Model::STATUS_NEED_CUSTOMER_APPROVE) {
						$order->status = Order_Model::STATUS_NEED_VENDOR_APPROVE;
					}

					$order->update_temp_price()->save();
					Site::message(Site::MESSAGE_NORMAL, HT('订单商品删除成功!'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, HT('订单最后一条商品不可删除!'));
				}
			}
			else {
				// 修改
				$origin_unit_price = $item->temp_unit_price?:$item->unit_price;
				$current_unit_price = $form['unit_price'];
				$origin_quantity = $item->temp_quantity ?:$item->quantity;
				$current_quantity = $form['quantity'];

                //如果没有修改就return
                if ($origin_quantity == $current_quantity && $origin_unit_price == $current_unit_price) {
                    JS::close_dialog();
                    return;
                }

                if ($origin_unit_price != $current_unit_price) {
                    $product_price = $item->product()->price;
					if ($origin_unit_price == -1) {
						$content = T('%user(供应商: %vendor) 将 %product_name 的价格修改为 %current_unit_price 。',
							array('%user' => $me->name,
								'%vendor' => $order->vendor->name,
								'%product_name' => $item->product->name,
								'%current_unit_price' => Number::currency($current_unit_price),
							));
					}
					elseif($current_unit_price == -1) {
                        Site::message(Site::MESSAGE_ERROR, HT('系统不支持将商品价格改为待询价'));
                        JS::refresh();
                        return;
                    }
                    /* po桌允许调高价格，调高价格之后供应商确认之后变为买方确认
                    else if ($product_price>0 && round($current_unit_price, 2)>round($product_price, 2)) {
                        Site::message(Site::MESSAGE_ERROR, HT('系统不支持调高商品价格'));
                        JS::refresh();
                        return;
                    }
                     */
					else {
						$content = T('%user(供应商: %vendor) 将 %product_name 的价格从 %origin_unit_price 修改到 %current_unit_price 。',
							array('%user' => $me->name,
								'%vendor' => $order->vendor->name,
								'%product_name' => $item->product->name,
								'%origin_unit_price' => Number::currency($origin_unit_price),
								'%current_unit_price' => Number::currency($current_unit_price),
							));
					}
					self::create_comment($order, $content);
				}

				//更改价格、数量 需要记录在评论中
				if ($origin_quantity != $current_quantity) {
					$content = T('%user(供应商: %vendor) 将 %product_name 的数量从 %origin_quantity 修改到 %current_quantity 。',
							   array('%user' => $me->name,
							   		 '%vendor' => $order->vendor->name,
							   		 '%product_name' => $item->product->name,
							   		 '%origin_quantity' => $origin_quantity,
							   		 '%current_quantity' => $current_quantity,
								   ));
					self::create_comment($order, $content);
				}


				$item->temp_unit_price = $form['unit_price'] != $item->unit_price ? $form['unit_price'] : NULL;
				$item->temp_quantity = $form['quantity'] != $item->origin_quantity ? $form['quantity'] : NULL;

                $item->temp_price = $form['unit_price'] * $form['quantity'];
                if ($item->temp_price == $item->price) {
                        $item->temp_price = NULL;
                }

				$item->save();
				Site::message(Site::MESSAGE_NORMAL, HT('订单商品修改成功!'));
				if ($order->status == Order_Model::STATUS_NEED_CUSTOMER_APPROVE) {
					$order->status = Order_Model::STATUS_NEED_VENDOR_APPROVE;

                    //跟踪信息
                    $now = new \Datetime();
                    $now = $now->format('Y-m-d H:i:s');
                    $order->mall_description = [
	                    'a'=>H(T('**:user(:vendor)** **修改**了订单', [
				        	':user'=>L('ME')->name,
				        	':vendor'=>$order->vendor->short_name
		            	])),
                        't'=>$now,
                        'u'=>L('ME')->gapper_user,
                    ];
				}

				$order->update_temp_price()->save();
			}
		}

		JS::refresh();

	}

	private function create_comment($object, $content) {
		$comment = O('comment');
		$me = L('ME');
		$comment->is_log = TRUE;
		$comment->object = $object;
		$comment->content = $content;
		$comment->author = $me;
		$comment->save();
	}

	function index_deliver_click() {
		if (!JS::confirm(HT('您确定发货完成吗?'))) {
			return FALSE;
		}

		$form = Input::form();
		$id = $form['id'];
		$order = O('order', $id);
		if (!$order->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');

		if (!($order->vendor_can_deliver() &&
				$me->is_allowed_to('确认发货', $order))) {
			URI::redirect('error/401');
		}

		if ($order->deliver()) {
			Site::message(Site::MESSAGE_NORMAL, HT('订单已确认发货完成'));
            $log = sprintf('[vendor] %s[%d]确认订单#%s[%d]发货完成',$me->name, $me->id,
            $order->order_no, $order->id);
            Log::add($log, 'vendor');
            O('operation_time')->log_status($me,Operation_Time_Model::STATUS_SEND,$order);
		}
		else {
			Site::message(Site::MESSAGE_ERROR, HT('确认发货完成失败'));
		}

        $callback = $order->url(NULL, NULL, NULL, 'vendor_view');
        JS::redirect($callback);
	}

	public function index_recover_click() {
		$form = Input::form();
        $order = O('order', $form['id']);
        if (!$order->id) return FALSE;
        $me = L('ME');
        if (!$order->vendor_can_cancel() || ! $me->is_allowed_to('以供应商取消', $order)) return FALSE;
        JS::dialog(V('vendor:order/recover_form', array('order'=> $order)));
	}

	public function index_recover_submit() {

		$form = Form::filter(Input::form());
        $order = O('order', $form['id']);

		$me = L('ME');

		if (!($order->vendor_can_recover() &&
			  $me->is_allowed_to('拒绝退货', $order))) {
			URI::redirect('error/401');
		}

        $form->validate('reason', 'not_empty', HT('拒绝理由不能为空!'));
        if ($form->no_error) {

            if ($order->recover($form['reason'])) {
				Site::message(Site::MESSAGE_NORMAL, HT('拒绝退货成功'));
	            $log = sprintf('[vendor] %s[%d]将订单%s[%d]拒绝退货',
	            $me->name, $me->id, $order->order_no, $order->id);
	            Log::add($log, 'vendor');
                O('operation_time')->log_status($me,Operation_Time_Model::STATUS_RETURN_REJECT,$order);
			}
			else {
				Site::message(Site::MESSAGE_ERROR, HT('拒绝退货失败'));
			}

			JS::redirect($order->url(NULL, NULL, NULL, 'vendor_view'));
        }
        else {
            JS::dialog(V('vendor:order/recover_form', array(
                'order'=> $order,
                'form'=> $form
            )));
        }
	}

    public function index_cancel_click() {

        if (!JS::confirm('您确定要取消该订单吗?')) return;

        $form = Form::filter(Input::form());
        $order = O('order', $form['id']);
        if (!$order->id) return FALSE;
        $me = L('ME');
        if (!$order->vendor_can_cancel() || ! $me->is_allowed_to('以供应商取消', $order)) return FALSE;

        //跟踪信息
        $now = new \Datetime();
        $now = $now->format('Y-m-d H:i:s');
        $order->mall_description = [
            'a'=>H(T('**:user(:vendor)** **取消**了该订单', [
                ':user'=>$me->name,
                ':vendor'=>$order->vendor->short_name
                ])),
            't'=>$now,
            'u'=>$me->gapper_use,
        ];

        $db = Database::factory();
        $db->begin_transaction();

        if ($order->cancel()) {
            $db->commit();
            O('operation_time')->log_status($me,Operation_Time_Model::STATUS_CANEL,$order);
            Site::message(Site::MESSAGE_NORMAL, HT('取消成功!'));
        }
        else {
            $db->rollback();
            Site::message(Site::MESSAGE_ERROR, HT('取消失败!'));
        }

		JS::redirect($order->url(NULL, NULL, NULL, 'vendor_view'));
    }
}
