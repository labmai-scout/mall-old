<?php

class Order_item_Model extends Presentable_Model {

    //收货状态
    //未发货
    const DELIVER_STATUS_NOT_DELIVERED = 0;

    //已发货
    const DELIVER_STATUS_DELIVERED = 1;

    //已收货
    const DELIVER_STATUS_RECEIVED = 2;

    function & links($mode = 'view', $button = FALSE) {
        $links = new ArrayIterator;
        $me = L('ME');

        $comment = Q("$this order_item_comment")->current();

        switch ($mode) {
        case 'admin_view':
            if ($comment->id) {
                $links['show_comment'] = array(
                    'url' => '#',
                    'text' => HT('查看评价'),
                    'extra' => 'class="blue" q-object="show_comment" q-event="click" ' .
                    'q-src="'.URI::url('!admin/order/item').'" ' .
                    'q-static="comment_id='.intval($comment->id).'" ',
                    );
            }
            break;
		case 'vendor_view':
            //待发货状态并且可发货
            if ($this->order->vendor_can_deliver() && L('ME')->is_allowed_to('确认发货', $this->order)) {
                switch($this->deliver_status) {
                    case self::DELIVER_STATUS_NOT_DELIVERED :
                        $links['receive'] = array(
                            'url'=> '#',
                            'text'=> T('发货'),
                            'extra'=> strtr('class="blue" q-event="click" q-object="deliver" q-src="%src"', array(
                                '%src'=> URI::url('!vendor/order/item')
                            )).
                            'q-static="'. H(array('id'=> $this->id)). '"'
                        );
                }
            }

			if ($comment->id) {
				$links['show_comment'] = array(
					'url' => '#',
					'text' => HT('查看评价'),
					'extra' => 'class="blue" q-object="show_comment" q-event="click" ' .
					'q-src="'.URI::url('!vendor/order/item').'" ' .
					'q-static="comment_id='.intval($comment->id).'" ',
					);
			}
			if (!$this->product->fixed_price && $this->order->status == Order_Model::STATUS_NEED_VENDOR_APPROVE) {
                if ($this->temp_delete) {
                    $links['recover'] = array(
                        'url' => '#',
                        'text' => T('恢复'),
                        'extra' =>'class="blue" q-object="recover_item" q-event="click" ' .
                        'q-static="id='.intval($this->id).'"',
                    );
                }
                else {
    				$links['edit'] = array(
    					'url' => '#',
    					'text' => T('修改'),
    					'extra' =>'class="blue" q-object="edit_item" q-event="click" ' .
    					'q-static="id='.intval($this->id).'"',
    					);
                }
			}

			Event::trigger('get_extra_vendor_links', $this, $links);
			break;
		case 'customer_view':
            if ($this->order->customer_can_receive() && L('ME')->is_allowed_to('确认收货', $this->order)) {
                switch($this->deliver_status) {
                    //未发货、已发货可直接确认收货
                    case self::DELIVER_STATUS_NOT_DELIVERED :
                    case self::DELIVER_STATUS_DELIVERED :
                        $links['receive'] = array(
                            'url'=> '#',
                            'text'=> T('到货'),
                            'extra'=> strtr('class="blue" q-event="click" q-object="receive" q-src="%src"', array(
                                '%src'=> URI::url('!customer/order_item')
                            )).
                            'q-static="'. H(array('id'=> $this->id)). '"'
                        );
                }
            }

			if ($comment->id) {
				$links['show_comment'] = array(
					'url' => '#',
					'text' => HT('查看评价'),
					'extra' => 'class="blue" q-object="show_comment" q-event="click" ' .
					'q-src="'.URI::url('!customer/order_item').'" ' .
					'q-static="comment_id='.intval($comment->id).'" ',
					);
			}
			else if ($this->can_comment() && $me->is_allowed_to('评价', $this)) {
				$links['comment'] = array(
					'url' => '#',
					'text' => HT('发表评价'),
					'extra' => 'class="blue" q-object="post_comment" q-event="click" ' .
						'q-src="'.URI::url('!customer/order_item').'" ' .
						'q-static="id='.intval($this->id).'" ',
					);
			}

			if ($this->lims_oid) {
				$links['lims'] = array(
					'url' => URI::url('!customer/order_item/go_lims_order.'.$this->id),
					'text' => HT('LIMS订单'),
					'extra' => 'class="button" target="_blank"',
				);
			}
		}

		return (array) $links;
	}

	function can_comment() {
		$ret = FALSE;

		$can_comment_statuses = [
			Order_Model::STATUS_TRANSFERRED,
			Order_Model::STATUS_PENDING_PAYMENT,
			Order_Model::STATUS_PAID,
		];
		if (in_array($this->order->status, $can_comment_statuses) &&	// 订单为已付款状态
			!Q("$this order_item_comment")->total_count() // 且此订单项还未发表评
			) {

			/*
			不进行付款日期的判断，如果付款则可以修改
			// 判断日期
			$earlist = Config::get('comment.comment_publish_earliest');
			$latest = Config::get('comment.comment_publish_latest');

			$has_transffered = Date::time() - $this->order->transferred_date;

			if ($has_transffered > $earlist * 86400 &&
				$has_transffered < $latest * 86400) {
				$ret = TRUE ;
			}
			*/
			$ret = TRUE;
		}

		return $ret;
	}

	function save($overwrite = false) {
        //第一次save的时候设置product dirty
        if(!$this->id) {
            $pid = $this->product_id;
            if ($pid) {
            	$product = O('product', $pid);
            	$version = $product->getVersion();
            	$this->version = $version;
            }
        }

		$return = parent::save($overwrite);
		return $return;
	}

    // 是否允许更改订单商品数量
    function canChangeQuantity()
    {
        // by pihizi: 2016-09-19 上交大要求先管理方审核之后才能被供应商看到订单。为了这个需求
        // labmai团队讨论了修正订单的采购流程，其中有一条，不允许供应商修改商品数量
        return false;
        /*
        if (!$this->id) return false;
        if (Config::get('mall.haz-control-enable') && $this->product->cas_no) {
            $conf = Config::get('mall.chem-db');
            $haz_types = Config::get('mall.hazardous_control_types');
            $cas_no = $this->product->cas_no;
            $rpc = new RPC($conf['api']);
            if ($rpc) {
                $infos = $rpc->chemdb->getChemical($cas_no);
                if (count(array_intersect($infos['types'], $haz_types))) {
                    return false;
                }
                else {
                    return true;
                }
            }
            else {
                return false;
            }
        }
        return TRUE;
         */
    }

	function product() {
		$product = $this->product;
		if($product->version != $this->version) {
			$revision = O('product_revision', ['product' => $product, 'version'=>$this->version]);
			if($revision->id) {
				//id应该为product的id，product_revision没有用
				$revision->id = $product->id;
				return $revision;
			}
		}

		return $product;
	}

}
