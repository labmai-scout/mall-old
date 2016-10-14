<?php

class Transfer_Statement_Model extends Presentable_Model {

	const STATUS_DRAFT = 0;
	const STATUS_PENDING_TRANSFER = 1;
	const STATUS_TRANSFERRED = 2;
	const STATUS_FAILED = 3;
	const STATUS_CANCEL = 4;

	static $status = array(
		self::STATUS_DRAFT => '未支付',
		self::STATUS_PENDING_TRANSFER => '付款中',
		self::STATUS_TRANSFERRED => '已付款',
		self::STATUS_FAILED => '付款失败',
		self::STATUS_CANCEL =>'已取消',
	);

	static $status_label = array(
		self::STATUS_DRAFT => 'draft',
		self::STATUS_PENDING_TRANSFER => 'pending_transfer',
		self::STATUS_TRANSFERRED => 'transferred',
		self::STATUS_FAILED => 'failed',
	);

	protected $object_page = array(
		// 由于一个 user 可有多个 customer, url() 不好用
		'view' => '!customer/transfer/statement.%id[.%arguments]',
		'cancel_view' => '!customer/transfer/cancel_statement.%id[.%arguments]',
		'delete' => '!customer/transfer/delete_statement.%id[.%arguments]',
		'admin_view' => '!admin/transfer/statement/index.%id[.%arguments]',
		'admin_edit' => '!admin/transfer/statement/edit.%id[.%arguments]',
		'admin_pdf' => '!admin/transfer/pdf/view.%id[.%arguments]',
  	);

	function & links($mode='index', $type=NULL) {

		$links = new ArrayIterator;
		$me = L('ME');
		switch($mode) {
		case 'customer_index':
			break;
		case 'customer_view':


			$transfer_statement = O('transfer_statement', $this->id);

			if ($this->status != self::STATUS_TRANSFERRED && $this->status != self::STATUS_FAILED && $this->status != self::STATUS_PENDING_TRANSFER) {
				$label = $this->status == self::STATUS_DRAFT ? '确认付款' : '再次支付';

				if ($type && $type == 'test') {
					/*
					暂时屏蔽掉Test支付付款的方式，也就是tmall那边运用的方式
					$links['transfer'] = array(
					'url' => URI::url('!customer/approve/index', array('sid' => $this->id)),
					'text' => T($label),
					'extra' => 'class="button button_tick" q-object="approve_transfer" q-event="click" '.
					'q-static="' . H(array('id' => $this->id)) . '" ' .
					'q-src="'.$this->url(NULL, NULL, NULL, NULL).'"'
					);
					*/
				}
				else {

					$links['transfer'] = array(
						'url' => URI::url('!customer/approve/index', array('sid' => $this->id)),
						'text' => T($label),
						'extra' => ' class="button button_tick payment_approve" target="_blank" '
					);

					//临时处理
					// $links['transfer'] = array (
					// 	'extra' => 'class="button button_tick" q-object="payment_approve" q-event="click" ',
					// 	'text' => T($label),
					// );
				}

			}

			if ($this->status == self::STATUS_PENDING_TRANSFER) {
				$payment_pending_links = $this->get_payment()->get_pending_links();

				if (is_array($payment_pending_links)) {
					foreach ($payment_pending_links as $key => $link) {
						$links[$key] = $link;
					}
				}
			}

			$links['print'] = array(
				'url' => '#', //$this->url(NULL, NULL, NULL, 'customer_print'),
				'text' => T('打印'),
				'extra' =>'class="button button_print" onclick="window.print(); return false;"',
			);

			//添加取消订单按钮在未付款状态的订单页面中 edit by sunxu 2015-04-13
			//移动取消付款单未知至打印按钮后 <bug 8602> edit by sunxu 2015-04-15

			if ($this->status == self::STATUS_DRAFT && $me->is_allowed_to('取消付费', $transfer_statement)){

					$links['cancel'] = array(
						'url' => URI::url('!customer/transfer/cancel_statement', array('sid' => $this->id)),
						'text' => T('取消付款单'),
						'extra' => 'class="button button_stat_close payment_cancel" q-object="customer_cancel_transfer" q-event="click" '.
						'q-static="' . H(array('sid' => $this->id)) . '" ' .
						'q-src="'.$this->url(NULL, NULL, NULL, 'cancel_view').'"'
					);

			}


			break;
		case 'admin_index':
			if (!$this->approver->id) {
				$links['finish'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'admin_view'),
					'text' => T('查看'),
					'extra' =>'class="blue"',
				);
			}
			break;
		case 'admin_view':
			$can_approve = Config::get('payment.enable_admin_approve')?:FALSE;
			if ($can_approve && $this->status == self::STATUS_PENDING_TRANSFER && !$this->approver->id) {
				$links['finish'] = array(
					'url' => "#",
					'text' => T('批准付款'),
					'extra' => 'class="button button_tick" q-object="admin_approve_transfer" q-event="click" '.
					'q-static="' . H(array('id' => $this->id)) . '" ' .
					'q-src="'.$this->url(NULL, NULL, NULL, 'admin_view').'"'
					//'extra' =>'class="button button_tick" confirm="'.HT('您确定完成该单付款吗? 一旦确定, 相关订单会完成并关闭, 操作不能撤销.').'"',
				);

				$links['fail'] = array(
					'url' => "#",
					'text' => T('付款失败'),
					'extra' => 'class="button button_cancel" q-object="admin_fail_transfer" q-event="click" '.
					'q-static="' . H(array('id' => $this->id)) . '" ' .
					'q-src="'.$this->url(NULL, NULL, NULL, 'admin_view').'"'
				);

			}

			/*
			if ($this->status != self::STATUS_TRANSFERRED) {
				$links['edit'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'admin_edit'),
					'text' => T('编辑付款单'),
					'extra' => 'class="button button_edit"'
					//'extra' =>'class="button button_tick" confirm="'.HT('您确定完成该单付款吗? 一旦确定, 相关订单会完成并关闭, 操作不能撤销.').'"',
				);
			}
			*/

			if(self::can_print_pdf()){
				$links['print'] = array(
                    'url' => $this->url(NULL, NULL, NULL, 'admin_pdf'),
                    'text' => T('打印'),
                    'extra' => 'class="button button_print" target="_blank"'
                );
			}
			break;
            case 'admin_transafer':
            	if(self::can_print_pdf()) {
                    $links['print'] = array(
                        'url' => $this->url(NULL, NULL, NULL, 'admin_pdf'),
                        'text' => T('打印'),
                        'extra' => 'class="blue" target="_blank"'
                    );
                }
		}

		return (array)$links;
	}

	function approve() {
		if (!$this->id || !$this->status == self::STATUS_DRAFT)
			return FALSE;

		foreach(Q("$this order") as $order) {
			$order->set_pending_transfer();
		}

		$this->status = self::STATUS_PENDING_TRANSFER;
		$this->fail_reason = '';
		$this->approver = L('ME');
		$this->approve_date = Date::time();
        return $this->save();
	}

	function success() {
		if (!$this->id || $this->status == self::STATUS_TRANSFERRED)
			return FALSE;

		foreach(Q("$this order") as $order) {
			$order->set_transferred();
		}

		$this->status = self::STATUS_TRANSFERRED;
		$this->transferred_date = Date::time();
		$ret = $this->save();

		$log = sprintf('%s[%id] 操作付款单 #%d success() %s',
					   L('ME')->name, L('ME')->id,
					   $this->id,
					   $ret ? '成功' : '失败');
		Log::add($log, 'transfer');
		if ($ret) {
			 $this->debade_statement_update();
		}

		return $ret;
	}

	function debade_statement_update() {
        DeBaDe::of('transfer_statement')->push([
        	'id' => 'payment_statement',
        	'data' => [
        		'voucher' => $this->voucher
        	]
        ]);
	}

	function fail($reason = '') {
		if (!$this->id || !$this->status == self::STATUS_PENDING_TRANSFER)
			return FALSE;

		foreach(Q("$this order") as $order) {
			$order->set_transfer_failed();
		}

		$this->fail_reason = $reason;
		$this->status = self::STATUS_FAILED;
		$this->payment_method = NULL;
		$ret = $this->save();
		if ($ret) {
			 $this->debade_statement_update();
		}
		$log = sprintf('%s[%id] 操作付款单 #%d fail() %s',
					   L('ME')->name, L('ME')->id,
					   $this->id,
					   $ret ? '成功' : '失败');
		Log::add($log, 'transfer');

		return $ret;
	}

	function reset() {
		return true;
		// 付款中的订单可以重置
		if (!$this->id || !$this->status == self::STATUS_PENDING_TRANSFER) return FALSE;
		$this->status = self::STATUS_DRAFT;
		return $this->save();

	}

	function is_failed() {
		return (self::STATUS_FAILED == $this->status);
	}

	function can_print_pdf() {
		return ($this->reserv_no && $this->status == self::STATUS_PENDING_TRANSFER);
	}

	// 创建payment
	// 之所以用 get_payment(), 是为以后若要保存 payment 等考虑 (xiaopei.li@2012-08-27)
	function get_payment($method = NULL) {
		$method = $this->payment_method ? : $method;
		return new Payment($this, $method);
	}

    //确认付款操作, 返回数据提交是否成功，返回成功url
    function pay() {
		$payment = $this->get_payment();

		//执行pay操作
		return $payment->pay();
	}

    //获取支付状态
    function get_pay_status() {
		$payment = $this->get_payment();
        $return_value = $payment->get_pay_status();

        return $return_value;
    }

	function get_external_message() {
		// TODO transfer_statement 里应该存 payment! (xiaopei.li@2012-09-12)
		return Event::trigger('transfer_statement.get_external_message', $this);
	}

	// 生成付款单号，目前用于lab-orders
	public static function generate_voucher() {
        $prefix = 'PM'.date('Ymd');
        $file = SITE_PATH.'private/payment_voucher/'.$prefix;

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $fp = fopen($file, 'c+');
        if ($fp) {
            flock($fp, LOCK_EX);
            $index = (int)fgets($fp) + 1;
            ftruncate($fp, 0); rewind($fp);
            fputs($fp, $index);
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return $prefix . str_pad($index, 3, '0', STR_PAD_LEFT);
    }

    public function save($overwrite = false) {
    	if (!$this->voucher) {
            $this->voucher = self::generate_voucher();
    	}

    	return parent::save($overwrite);
    }
}