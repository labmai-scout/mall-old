<?php

class Billing_Statement_Model extends Presentable_Model {

	const STATUS_DRAFT = 0;
	const STATUS_PAID = 1;
	const STATUS_REJECTED = 2;
	const STATUS_PENDING_CHECK = 3;

	static $status = array(
		self::STATUS_DRAFT => '待审核',
		self::STATUS_PENDING_CHECK => '结算中',
		self::STATUS_PAID => '已结算',
        self::STATUS_REJECTED => '已驳回',
	);

	static $status_label = array(
		self::STATUS_DRAFT => 'draft',
		self::STATUS_PAID => 'paid',
        self::STATUS_REJECTED => 'rejected',
        self::STATUS_PENDING_CHECK => 'check',
	);

	protected $object_page = array(
        'view' => '!vendor/order/statement/index.%id[.%arguments]',
        'delete' => '!vendor/order/statement/delete.%id[.%arguments]',
        'close' => '!vendor/order/statement/close.%id[.%arguments]',
        'vendor_view' => '!vendor/order/statement/index.%id[.%arguments]',
        'vendor_settle' => '!vendor/order/statement/settle.%id[.%arguments]',
        'admin_approve' => '!admin/financial/statement/approve.%id[.%arguments]',
        'admin_view' => '!admin/financial/statement/view.%id[.%arguments]',
        'admin_pdf' => '!admin/financial/pdf/view.%id[.%arguments]',
		'export' => '!vendor/order/billing/export.%id[.%arguments]',
 	);

    function &links($mode='index', $button=FALSE) {

        $links = new ArrayIterator;
        $me = L('ME');
        switch($mode) {
        case 'vendor_index':
            if ($this->status == self::STATUS_DRAFT) {
                $links['print'] = array(
                    'url' => $this->url(NULL, NULL, NULL, 'export'),
                    'text' => T('打印'),
                    'extra' =>'class="blue" target="_blank"',
                );
                if ($this->voucher_no) {
                    $links['close'] = array(
                        'url' => $this->url(NULL, NULL, NULL, 'close'),
                        'text' => T('关闭'),
                        'extra' =>'class="blue" confirm="'.HT('完成结算前，请务必确认您已收到必要款项。').'"',
                    );
                }
            }

            break;
        case 'vendor_view':
            if($this->status == self::STATUS_REJECTED) {
                if (!$this->account_method || !$this->get_account_status()) {
                    $links['settle'] = array(
                        'url' => $this->url(NULL, NULL, NULL, 'vendor_settle'),
                        'text' => T('申请结算'),
                        'extra' =>'class="button button_refresh" confirm="'.HT('您确定申请该单结算吗?').'"',
                    );
                }
			}

			if ($this->status == self::STATUS_DRAFT) {
				$links['print'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'export'), //$this->url(NULL, NULL, NULL, 'vendor_print'),
					'text' => T('打印'),
					'extra' =>'class="button button_print" target="_blank"',
				);
			}

			if ((int)$this->status !== self::STATUS_PAID) {
				$links['extra'] = array(
					'url' => $this->url(NULL, NULL, NULL, ' extra'),
					'text' => T('补充信息'),
					'extra' =>'class="button button_save" q-object="extra_info" q-static="id='.$this->id.'" q-event="click" q-src="'.URI::url('!vendor/order/billing').'"',
				);
			}

			if ($this->can_close()) {
				$links['close'] = array (
					'prefix' => '&#160;&#160;&#160;',
					'url' => $this->url(NULL, NULL, NULL, 'close'),
					'text' => T('关闭'),
					'extra' =>'class="button button_save" ' .
					'confirm="' . HT('完成结算前，请务必确认您已收到必要款项') . '"',
				);
			}
			if ($this->can_delete()) {
				$links['delete'] = array (
					'prefix' => '&#160;&#160;&#160;',
					'url' => $this->url(NULL, NULL, NULL, 'delete'),
					'text' => T('删除'),
					'extra' =>'class="button button_delete" ' .
					'confirm="' . HT('您确认删除该结算单么?') . '"',
				);
			}
			break;
		case 'admin_index':
			if ($this->status == self::STATUS_DRAFT) {
				$links['finish'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'admin_view'),
					'text' => T('查看'),
					'extra' =>'class="blue"',
				);
			}
			else if ($this->status == self::STATUS_PENDING_CHECK && $this->reserv_no) {
				$links['print'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'admin_pdf'),
					'text' => T('打印'),
					'extra' =>'class="blue" target="_blank"',
				);
			}
			break;
		case 'admin_view':
			if ($this->status == self::STATUS_DRAFT) {
				$links['finish'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'admin_approve'),
					'text' => T('批准结算'),
					'extra' =>'class="button button_tick account_approve" target="_blank"',
				);
				$links['rejected'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'admin_rejected'),
					'text' => T('驳回'),
					'extra' =>'class="button button_delete" q-object="reject_statement" q-static="id='.$this->id.'" q-event="click" q-src="'.URI::url('!admin/financial/statement').'"',
				);
			}
			else if ($this->status == self::STATUS_PAID) {
				$links['print'] = array(
					'url' => '#',
					'text' => T('打印'),
					'extra' =>'class="button button_print" onclick="window.print(); return false;"',
				);
			}
			else if ($this->status == self::STATUS_PENDING_CHECK && $this->reserv_no) {
				$links['print'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'admin_pdf'),
					'text' => T('打印'),
					'extra' =>'class="button button_print" target="_blank"',
				);
			}
			break;
		}

		return (array)$links;
	}

	function get_account($method = NULL) {
		$method = $this->account_method ? : $method;
		return new Account($this, $method);
	}

    //获取结算状态
    function get_account_status() {
		$account = $this->get_account();
        $return_value = $account->get_account_status($this);
        return $return_value;
    }

	function can_print_pdf() {
		return ($this->reserv_no && $this->status == self::STATUS_PENDING_CHECK);
	}

    function canApprove() {
		if (!$this->id) return FALSE;
		if ($this->status != self::STATUS_DRAFT) return FALSE;
		if ($this->account_method) {
			$data = $this->get_account_status();
			if ($data) return false;
		}

		return TRUE;
    }

    function canReject() {
    	if (!$this->id) return FALSE;
    	if ($this->status != self::STATUS_DRAFT) return FALSE;
		if ($this->account_method) {
			$data = $this->get_account_status();
			if ($data) return false;
		}
		return TRUE;
    }

	function approve() {

		if (!$this->id) return FALSE;
		if ($this->status != self::STATUS_DRAFT) return FALSE;
		if ($this->account_method) {
			$account = $this->get_account();
			$this->status = self::STATUS_PENDING_CHECK;
			$this->approve_date = Date::time();
			$this->approver = L('ME');
			if ($this->save()) {
				return $account->pay($this);
			}
			return FALSE;
		}
		else {
			$this->approve_date = Date::time();
			$this->approver = L('ME');
			$this->success();
		}
	}

	function success() {
		if (!$this->id) return FALSE;
		$this->status = self::STATUS_PAID;
		foreach(Q("$this order") as $order) {
			$order->set_paid();
		}
		return $this->save();
	}

	function reject($reject_reason) {
		if (!$this->id) return FALSE;

		$this->status = self::STATUS_REJECTED;
		$this->reject_reason = $reject_reason;
		return $this->save();
	}

	function delete() {
		if (!$this->id) return FALSE;
		if ($this->status == self::STATUS_PAID) return FALSE;
		$bucket = Billing_Bucket_Model::vendor_bucket($this->vendor);
		$enable = Config::get('payment.enable_vendor_close', false);
		foreach(Q("$this order") as $order) {
			$this->disconnect($order);
			if (!$enable) {
				$order->status = Order_Model::STATUS_TRANSFERRED;
				$order->save();
				$bucket->add_item($order);
			}
		}

		return parent::delete();
	}

	function settle() {
		if (!$this->id) return FALSE;

		$this->status = self::STATUS_DRAFT;
		$this->reject_reason = null;
		return $this->save();
	}

	function can_delete() {
		return $this->status == self::STATUS_REJECTED;
	}

	function can_close() {
		$full_path = NFS::get_path($this, '', 'attachments', TRUE);
		$files = NFS::file_list($full_path,'');
		$enable = Config::get('payment.enable_vendor_close', false);
		if ($enable && $this->voucher_no && count($files) && $this->status == self::STATUS_DRAFT) {
			return true;
		}
		return false;
	}

    public function save($overwrite = false) {
    	$this->touch();
    	return parent::save($overwrite);
    }

}
