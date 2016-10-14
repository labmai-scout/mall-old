<?php

class Vendor_Model extends Presentable_Model {

    const STATUS_PENDING_APPROVAL = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
    static $status_options = array(
        self::STATUS_PENDING_APPROVAL => '待审核',
        self::STATUS_APPROVED => '审核通过',
        self::STATUS_REJECTED => '审核被驳回'
    );

    static $nemployees_options = array(
    		0 => '--',
            1 => '10 人以下',
            2 => '10 至 50 人',
            3 => '50 至 100 人',
            4 => '100 人以上'
    );

	protected $object_page = array(
		'view'=>'!mall/vendor/view.%id[.%arguments]',
		'vendor_index' => '!vendor/index.%id[.%arguments]',
		'vendor_view' => '!vendor/profile/view.%id[.%arguments]',
		'vendor_edit' => '!vendor/profile/edit.%id[.%arguments]',
		'admin_edit'=>'!admin/vendor/edit.%id[.%arguments]',
		'admin_view'=>'!admin/vendor/view.%id[.%arguments]',
		'admin_delete'=>'!admin/vendor/delete.%id[.%arguments]',
		'admin_unpublish'=>'!admin/vendor/unpublish.%id[.%arguments]',
		'approve_products' => '!admin/vendor/approve_products.%id[.%arguments]',
		'product'=>'!vendor/product/index/index.%id[.%arguments]',
		'vendor_order'=>'!vendor/order/index/index.%id[.%arguments]',
		'vendor_billing'=>'!vendor/order/billing/index.%id[.%arguments]',
	);

	function & links($mode='index', $button = FALSE) {

		$links = new ArrayIterator;
		$me = L('ME');

		switch ($mode) {
		case 'admin_view':

            // 禁止在mall-old修改供应商信息
			if (0 && $this->can_edit($msg)) {
				$links['edit'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'admin_edit'),
					'text' => T('修改'),
					'extra' =>'class="button button_edit"',
					);
			}

            if ($this->approve_date) {
                /*
                 * 冻结就是把approve_date和publish_date清空
                 * 但是供应商的审核审核状态在hub-vendor是和销售类别的审核状态相关的，存才冲突，不能开始这个功能
				$links['unpublish'] = array(
					'url' => '#',
					'text' => T('冻结帐户'),
					'extra'=> 'class="button button_delete" ' .
					'q-object="unpublish" q-event="click" ' .
					'q-static="' . H(array('id' => $this->id)) . '"',
                    );
                 */

				if ($me->is_allowed_to('批量审批商家商品', $this)) {
					$data = Product_Model::getVendorProducts(0, 20, 'unapproved', $this->gapper_group);
					$n_products_to_approve = $data['total'];
					if ($this->approve_vp_pid) {
						$links['pause_processing_approve_products'] = array(
							'url' => '#',
							'text' => T('中止审批'),
							'extra' => 'class="button button_delete" q-object="pause_approving" ' .
							'q-event="click" q-static="' . H(array('id' => $this->id)) . '" ',

						);
						$links['is_processing_approve_products'] = array(
							'text' => HT('正在处理商品批量审批...')
						);
					}
					else {
						if ($n_products_to_approve) {
							$links['approve_products'] = array(
								'url' => URI::url('!admin/vendor/approve_products.'.$this->id),
								'text' => T('全部审批'),
								'extra' =>'class="button button_tick" ' .
								'confirm="'.T('您确定通过该商家的所有已发布的商品么？').'"',
								);
							/*
							$links['approve_products_comment'] = array(
								'text' => T('(有 %n_to_approve 个商品待审核)', array(
												'%n_to_approve' => $n_products_to_approve
												)),
								'extra' =>'class="description"',
								);
							*/
						}
					}
				}
			}
			else if ($this->last_approve_date) {
				if (!$this->is_unpublishing) {
					$links['reapprove'] = array(
						'url' => '#',
						'text' => T('恢复'),
						'extra'=> 'class="button button_tick" ' .
						'q-object="reapprove" q-event="click" ' .
						'q-static="' . H(array('id' => $this->id)) . '"',
					);
				}
			}
			elseif ($this->publish_date) {
				$links['reapprove'] = array(
					'url' => '#',
					'text' => T('通过'),
					'extra'=> 'class="button button_tick" ' .
					'q-object="approve" q-event="click" ' .
					'q-static="' . H(array('id' => $this->id)) . '"',
				);
			}

			break;
        /*
         * 禁止在mall-old修改供应商信息
		case 'admin_index':
			$links['edit'] = array(
				'url' => $this->url(NULL, NULL, NULL, 'admin_edit'),
				'text' => T('修改'),
				'extra' =>'class="blue"',
			);
            break;
        */
		case 'vendor_view':
			if ($this->is_publishing) {
				$links['is_publishing'] = array(
					'text' => HT('...正在恢复上架, 请稍后查看'),
					'extra' =>'class="description"',
					);
				break;
			}

            /*
            * 禁止在mall-old修改供应商信息
			if ($me->is_allowed_to('以供应商修改', $this)) {
				$links['edit'] = array(
					'url' => $this->url(NULL, NULL, NULL, 'vendor_edit'),
					'text' => T('修改'),
					'extra' =>'class="button button_edit"',
					);
			}
             */
            break;
		}

		return (array)$links;
	}

	// 发布
	function publish() {
		// TODO 判断状态
		// TODO log

		$this->publisher = L('ME');
		$this->publish_date = Date::time();
		$this->reject_reason = '';

		return $this->save();
	}

	// 审核
	function approve() {
		// TODO 判断状态
		// TODO log

		if (!$this->publish_date) {
			$this->publish();
		}

		$this->approver = L('ME');
		$this->approve_date = Date::time();

		return $this->save();
	}

	// 取消审核
	function unapprove() {
		// TODO 判断状态
		// TODO log

		$this->last_approver = $this->approver->id;
		$this->last_approve_date = $this->approve_date;

		$this->approver = NULL;
		$this->approve_date = 0;

		return $this->save();
	}

	// 下架
	function unpublish($reject_reason = '') {
		// TODO 判断状态
		// TODO log

		if ($this->approve_date) {
			$this->last_approver = $this->approver->id;
			$this->last_approve_date = $this->approve_date;

			$this->approver = NULL;
			$this->approve_date = 0;
		}

		$this->reject_reason = $reject_reason;

		$this->last_publisher = $this->publisher;
		$this->last_publish_date = $this->publish_date;

		$this->publisher = NULL;
		$this->publish_date = 0;

		return $this->save();
	}

	function get_display_name($type = NULL) {
		$ret = $this->name;

		switch ($type)  {
		case 'short':
			if ($this->short_name) {
				$ret = $this->short_name;
			}
			break;
		default:
		}

		return $ret;
	}

	function has_member($user) {
        return $user->connected_with($this, 'member');
	}

	function can_edit(&$msg=NULL) {
		$ret = TRUE;

		//下架操作可以进行编辑
		// if ($this->is_unpublishing) {
		// 	$ret = FALSE;
		// 	$msg =  HT('...正在处理下架, 请稍后查看');
		// }

		if ($this->is_publishing) {
			$ret = FALSE;
			$msg = HT('...正在恢复上架, 请稍后查看');
		}

		if ($this->is_modifying_scopes) {
			$ret = FALSE;
			$msg = HT('...正在处理资质修改, 请稍后查看');
		}

		return $ret;
	}

	//跳转到新商城商品信息页面
    function new_url() {
        $id = $this->gapper_group;
        $mp_url = Config::get('mall.new_url');
        if ($mp_url) {
        	return URI::url($mp_url.'/vendor/'.$id);
        }
        else {
        	return $this->url();
        }
    }

}
