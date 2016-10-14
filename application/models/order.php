<?php

class Order_Model extends Presentable_Model
{

    // const STATUS_DRAFT = 0;
    const STATUS_NEED_VENDOR_APPROVE = 0;
    const STATUS_PENDING_APPROVAL = 1;
    const STATUS_APPROVED = 2;
    const STATUS_RETURNING = 3;
    const STATUS_PENDING_TRANSFER = 4;
    const STATUS_TRANSFERRED = 5;
    const STATUS_PENDING_PAYMENT = 6;
    const STATUS_PAID = 7;
    const STATUS_CANCELED = 8;
    const STATUS_REQUESTING = 9;
    const STATUS_RETURNING_APPROVAL = 10;
    const STATUS_NEED_CUSTOMER_APPROVE = 11;

    // 需要管理管理方审核
    const STATUS_NEED_MANAGER_APPROVE = 14;

    //收货状态
    const DELIVER_STATUS_NOT_DELIVERED = 0;
    const DELIVER_STATUS_DELIVERED = 1;
    const DELIVER_STATUS_RECEIVED = 2;

    // label 易制毒
    const LABEL_DRUG_PRECURSOR = 1;

    const PAYMENT_STATUS_UNABLE = 0;
    const PAYMENT_STATUS_PENDING = 1;

    static $deliver_status = array(
        self::DELIVER_STATUS_NOT_DELIVERED => '未发货',
        self::DELIVER_STATUS_DELIVERED => '已发货',
        self::DELIVER_STATUS_RECEIVED => '已到货',
    );


    static $status = array(
        self::STATUS_NEED_VENDOR_APPROVE => '待供应商确认',
        self::STATUS_PENDING_APPROVAL => '待审核',
        self::STATUS_NEED_MANAGER_APPROVE => '待审核',
        self::STATUS_APPROVED => '待付款',
        self::STATUS_RETURNING => '退货中',
        self::STATUS_PENDING_TRANSFER => '付款中',
        self::STATUS_TRANSFERRED => '已付款',
        self::STATUS_PENDING_PAYMENT => '待结算',
        self::STATUS_PAID => '已结算',
        self::STATUS_CANCELED => '已取消',
        self::STATUS_REQUESTING => '申购中',
        self::STATUS_RETURNING_APPROVAL => '拒绝退货',
        self::STATUS_NEED_CUSTOMER_APPROVE => '待买方确认',
    );

    static $customer_status = array(
        self::STATUS_NEED_VENDOR_APPROVE => '待供应商确认',
        self::STATUS_PENDING_APPROVAL => '待审核',
        self::STATUS_NEED_MANAGER_APPROVE => '待审核',
        self::STATUS_APPROVED => '待付款',
        self::STATUS_RETURNING => '退货中',
        self::STATUS_PENDING_TRANSFER => '付款中',
        self::STATUS_TRANSFERRED => '已付款',
        self::STATUS_PENDING_PAYMENT => '已付款',
        self::STATUS_PAID => '已付款',
        self::STATUS_CANCELED => '已取消',
        self::STATUS_REQUESTING => '申购中',
        self::STATUS_RETURNING_APPROVAL => '拒绝退货',
        self::STATUS_NEED_CUSTOMER_APPROVE => '待买方确认',
    );

    static $status_label = array(
        self::STATUS_NEED_VENDOR_APPROVE => 'need_vendor_approve',
        self::STATUS_PENDING_APPROVAL => 'pending_approval',
        self::STATUS_NEED_MANAGER_APPROVE => 'need_manager_approve',
        self::STATUS_APPROVED => 'approved',
        self::STATUS_RETURNING => 'returning',
        self::STATUS_PENDING_TRANSFER => 'pending_transfer',
        self::STATUS_TRANSFERRED => 'transferred',
        self::STATUS_PENDING_PAYMENT => 'pending_payment',
        self::STATUS_PAID => 'paid',
        self::STATUS_CANCELED => 'canceled',
        self::STATUS_REQUESTING => 'requesting',
        self::STATUS_RETURNING_APPROVAL => 'returning_approval',
        self::STATUS_NEED_CUSTOMER_APPROVE => 'need_customer_approve',
    );

    static $customer_status_label = array(
        self::STATUS_NEED_VENDOR_APPROVE => 'need_vendor_approve',
        self::STATUS_PENDING_APPROVAL => 'pending_approval',
        self::STATUS_NEED_MANAGER_APPROVE => 'need_manager_approve',
        self::STATUS_APPROVED => 'approved',
        self::STATUS_RETURNING => 'returning',
        self::STATUS_PENDING_TRANSFER => 'pending_transfer',
        self::STATUS_TRANSFERRED => 'transferred',
        self::STATUS_PENDING_PAYMENT => 'transferred',
        self::STATUS_PAID => 'transferred',
        self::STATUS_CANCELED => 'canceled',
        self::STATUS_REQUESTING => 'requesting',
        self::STATUS_RETURNING_APPROVAL => 'returning_approval',
        self::STATUS_NEED_CUSTOMER_APPROVE => 'need_customer_approve',
    );

    protected $object_page = array(
        'view' => '!customer/order/index.%id[.%arguments]',
        'cancel' => '!customer/order/cancel.%id[.%arguments]',
        'confirm' => '!customer/order/confirm.%id[.%arguments]',
        // 'transfer' => '!customer/order/transfer.%id[.%arguments]', // deprecated
        'return' => '!customer/order/return_order.%id[.%arguments]',
        'vendor_close' => '!vendor/order/billing/close.%id[.%arguments]',
        'vendor_view' => '!vendor/order/index/view.%id[.%arguments]',
        'vendor_confirm' => '!vendor/order/index/confirm.%id[.%arguments]',
        'vendor_send' => '!vendor/order/index/send.%id[.%arguments]',
        'vendor_recover' => '!vendor/order/index/recover.%id[.%arguments]',
        'approve' => '!admin/order/index/approve.%id[.%arguments]',
        'reject' => '!admin/order/index/reject.%id[.%arguments]',
        'admin_view' => '!admin/order/index/view.%id[.%arguments]',
    );

    public function & links($mode = 'index', $button = false)
    {
        $links = new ArrayIterator();
        $me = L('ME');
        switch ($mode) {
        case 'view':
            break;
        case 'vendor_index':
            if ($this->can_pay() &&
                $me->is_allowed_to('申请结算', $this)) {
                $bucket = Billing_Bucket_Model::vendor_bucket($this->vendor);
                $rel = uniqid().'_link';
                if (!$bucket->contains($this)) {
                    $links['to_bucket'] = array(
                        'url' => '#',
                        'text' => T('加入结算夹'),
                        'extra' => 'id="'.$rel.'" class="blue" q-object="to_bucket" q-event="click" q-static="id='.intval($this->id).'&rel='.$rel.'"',
                    );
                } else {
                    $links['remove_from_bucket'] = array(
                        'url' => '#',
                        'text' => T('移出结算夹'),
                        'extra' => 'id="'.$rel.'" class="blue" q-object="remove_from_bucket" q-event="click" q-static="id='.intval($this->id).'&rel='.$rel.'"',
                    );
                }
            }
            $links['view'] = array(
                'url' => $this->url(null, null, null, 'vendor_view'),
                'text' => T('详单'),
                'extra' => 'class="blue" ',
            );
            break;
        case 'vendor_view':
            if ($this->status == Order_Model::STATUS_NEED_VENDOR_APPROVE &&
                $me->is_allowed_to('供应商确认订单', $this)) {
                $links['confirm'] = array(
                    'url' => $this->url(null, null, null, 'vendor_confirm'),
                    'text' => HT('确认订单'),
                    'extra' => 'class="button button_tick" confirm="'.HT('您确定要提交该订单吗?').'"',
                );
            }
            if ($this->vendor_can_recover() &&
                $me->is_allowed_to('拒绝退货', $this)) {
                $links['recover'] = array(
                        'url' => $this->url(null, null, null, 'vendor_recover'),
                        'text' => T('拒绝退货'),
                        'extra' => 'class="button button_delete" q-object="recover" q-event="click" q-src="'.$this->url(null, null, null, 'vendor_recover').'" q-static="'.H(array( 'id' => $this->id )).'"',
                        );
            }
            if ($this->vendor_can_cancel() &&
                $me->is_allowed_to('以供应商取消', $this)) {
                if ($this->status == self::STATUS_RETURNING) {
                    $links['cancel'] = array(
                            'url' => '#',
                            'text' => T('确认退货'),
                            'extra' => 'class="button button_tick" q-object="cancel" q-event="click" q-src="'.$this->url(null, null, null, 'vendor_view').'" q-static="'.H(array( 'id' => $this->id )).'"',
                        );
                } else {
                    $links['cancel'] = array(
                            'url' => '#',
                            'text' => T('取消订单'),
                            'extra' => 'class="button button_delete" q-object="cancel" q-event="click" q-src="'.$this->url(null, null, null, 'vendor_view').'" q-static="'.H(array( 'id' => $this->id )).'"',
                        );
                }
            }

            if ($this->can_pay() &&
                $me->is_allowed_to('申请结算', $this)) {
                $bucket = Billing_Bucket_Model::vendor_bucket($this->vendor);
                $rel = uniqid().'_link';
                if (!$bucket->contains($this)) {
                    $links['to_bucket'] = array(
                        'url' => '#',
                        'text' => T('加入结算夹'),
                        'extra' => 'id="'.$rel.'" class="button button_bucket" q-object="to_bucket" q-event="click" q-static="id='.intval($this->id).'&rel='.$rel.'"',
                    );
                } else {
                    $links['remove_from_bucket'] = array(
                        'url' => '#',
                        'text' => T('移出结算夹'),
                        'extra' => 'id="'.$rel.'" class="button button_bucket" q-object="remove_from_bucket" q-event="click" q-static="id='.intval($this->id).'&rel='.$rel.'"',
                    );
                }
            }
            if ($this->vendor_can_deliver() &&
                $me->is_allowed_to('确认发货', $this)) {
                $links['finish'] = array(
                    'url' => '#',
                    'text' => T('发货完成'),
                    'extra' => 'class="button button_tick" q-object="deliver" q-event="click" q-src="'.$this->url(null, null, null, 'vendor_view').'" q-static="'.H(array('id' => $this->id)).'"',
                    );
                }

                    /*
                      $links['send'] = array(
                      'url' => $this->url(NULL, NULL, NULL, 'vendor_send'),
                      'text' => T('部分发货'),
                      'extra' =>'class="button button_deliver"',
                      );
                    */
            break;
        case 'admin_index':
            $links['view'] = array(
                'url' => $this->url(null, null, null, 'admin_view'),
                'text' => T('详单'),
                'extra' => 'class="blue" ',
            );
            break;
        case 'admin_view':
            if ($this->admin_can_transfer()) {
                $links['transfer'] = array(
                    'url' => '#',
                    'text' => T('确认付款'),
                    'extra' => 'class="button button_tick" q-object="transfer_order" q-event="click" q-src="'.$this->url(null, null, null, 'admin_view').'"',
                );
            }

            if ($this->admin_can_pay()) {
                $links['paid'] = array(
                    'url' => '#',
                    'text' => T('结算成功'),
                    'extra' => 'class="button_tick button" q-object="paid_order" q-event="click" q-src="'.$this->url(null, null, null, 'admin_view').'"',
                );
            }

            if ($me->is_allowed_to('审核', $this) &&
                $this->can_approve()) {
                $links['approve'] = array(
                    'url' => '#',
                    'text' => T('审核订单'),
                    'extra' => 'class="button button_tick" q-object="approve_order" q-event="click" q-src="'.$this->url(null, null, null, 'admin_view').'"',
                    );
            }

            if ($me->is_allowed_to('取消', $this) &&
                $this->admin_can_cancel()) {
                $links['cancel'] = array(
                        'url' => '#',
                        'text' => T('取消订单'),
                        'extra' => 'class="button button_delete" q-object="cancel_order" q-event="click" q-src="'.$this->url(null, null, null, 'admin_view').'"',
                        );
            }

            if ($me->is_allowed_to('审核', $this) &&
                $this->can_return_approve()) {
                $links['approve'] = array(
                    'url' => '#',
                    'text' => T('拒绝退货'),
                    'extra' => 'class="button button_delete" q-object="return_approve_order" q-event="click" q-src="'.$this->url(null, null, null, 'admin_view').'"',
                    );

                $links['return'] = array(
                    'url' => '#',
                    'text' => T('同意退货'),
                    'extra' => 'class="button button_tick" q-object="return_order" q-event="click" q-src="'.$this->url(null, null, null, 'admin_view').'"',
                    );
            }

            if (Config::get('order.post_info_required', false) && in_array($this->status, [
                self::STATUS_PENDING_TRANSFER,
                self::STATUS_TRANSFERRED,
                self::STATUS_PENDING_PAYMENT,
                self::STATUS_PAID
                ])) {
                $links['bill_post'] = array(
                        'url' => '#',
                        'text' => T('买方票据邮递'),
                        'extra' => 'class="button button_save" q-object="order_bill" q-event="click" q-src="'.$this->url(null, null, null, 'admin_view').'"',
                        );
            }
            if (Config::get('order.set_order_paid_enable', FALSE) && $this->status == self::STATUS_APPROVED) {
                $links['set_paid'] = array(
                    'url'  => '#',
                    'text' => T('结算'),
                    'extra' => 'class="button button_save" q-object="set_paid" q-event="click" q-src="'.$this->url(null, null, null, 'admin_view').'"',
                );
            }
            break;
        case 'customer_index':

            if ($this->status == self::STATUS_REQUESTING &&
                $me->is_allowed_to('以买方确认', $this)) {
                $links['customer_approve'] = array(
                    'url' => '#',
                    'text' => T('确认订单'),
                    'extra' => 'class="blue" q-object="customer_approve_order" q-event="click" q-src="'.$this->url(null, null, null, 'view').'"',
                );
            }
            if ($this->status == self::STATUS_NEED_CUSTOMER_APPROVE &&
                $me->is_allowed_to('以买方确认', $this)) {
                $links['confirm'] = array(
                    'url' => $this->url($this->version, null, null, 'confirm'),
                    'text' => HT('确认订单'),
                    'extra' => 'class="blue" confirm="'.HT('您确定要提交该订单吗?').'"',
                );
            }
            // 付费相关链接
            if ($me->is_allowed_to('付费', $this)) {
                $status = Transfer_Statement_Model::STATUS_FAILED;
                $transfer_statement = Q("$this transfer_statement[status!={$status}]")->current();
                if ($transfer_statement->id) {
                    // 若已在付款单中, 显示付款单链接
                    $links['transfer_statement'] = array(
                        'url' => $transfer_statement->url(),
                        'text' => HT('已在付款单'),
                        'extra' => 'class="blue"',
                        );
                } elseif ($this->can_transfer()) {
                    $bucket = Transfer_bucket_Model::customer_bucket($this->customer);
                    $rel = uniqid().'_link';
                    if ($bucket->contains($this)) {
                        $links['remove_from_bucket'] = array(
                            'url' => '#',
                            'text' => T('移出付款夹'),
                            'extra' => 'id="'.$rel.'" class="blue" '.
                            'q-object="remove_from_bucket" q-event="click" '.
                            'q-static="'.H(array('id' => intval($this->id), 'rel' => $rel)).
                            '"',
                            );
                    } else {
                        if ($this->payment_status == self::PAYMENT_STATUS_PENDING) {
                            $links['to_bucket'] = array(
                                'url' => '#',
                                'text' => T('加入付款夹'),
                                'extra' => 'id="'.$rel.'" class="blue" '.
                                'q-object="to_bucket" q-event="click" '.
                                'q-static="'.H(array('id' => intval($this->id), 'rel' => $rel)).
                                '"',
                                );
                        }
                    }
                }
            }
            if ($this->customer_can_cancel() &&
                ($me->is_allowed_to('以买方取消', $this) || $this->purchaser->id == $me->id)) {
                $links['cancel'] = array(
                    'url' => '#',
                    'text' => T('取消订单'),
                    'extra' => 'class="blue" q-object="cancel" q-event="click" q-static="'.H(array( 'id' => $this->id )).'"',
                    );
            }

            $links['view'] = array(
                'url' => $this->url(),
                'text' => T('详单'),
                'extra' => 'class="blue" ',
            );
            break;
        case 'customer_view':
            if ($this->status == self::STATUS_REQUESTING) {
                if ($me->is_allowed_to('以买方确认', $this)) {
                    $links['customer_approve'] = array(
                        'url' => '#',
                        'text' => T('确认订单'),
                        'extra' => 'class="button button_tick" q-object="customer_approve_order" q-event="click" q-src="'.$this->url(null, null, null, 'view').'"',
                    );
                }
            }

            if ($this->status == self::STATUS_NEED_CUSTOMER_APPROVE &&
                $me->is_allowed_to('以买方确认', $this)) {
                $links['confirm'] = array(
                    'url' => $this->url($this->version, null, null, 'confirm'),
                    'text' => HT('确认订单'),
                    'extra' => 'class="button button_tick" confirm="'.HT('您确定要提交该订单吗?').'"',
                );
            }

            if ($me->is_allowed_to('付费', $this)) {
                $status = Transfer_Statement_Model::STATUS_FAILED;
                $transfer_statement = Q("$this transfer_statement[status!={$status}]")->current();
                if ($transfer_statement->id) {
                    // 若已在付款单中, 显示付款单链接
                    $links['transfer_statement'] = array(
                        'url' => $transfer_statement->url(),
                        'text' => HT('已在付款单 #%ref_no', array(
                                         '%ref_no' => H(Number::fill($transfer_statement->id, 6)),
                                         )),
                        'extra' => 'class="blue"',
                        );
                } elseif ($this->can_transfer()) {
                    $bucket = Transfer_bucket_Model::customer_bucket($this->customer);
                    $rel = uniqid().'_link';
                    if (!$bucket->contains($this)) {
                        if ($this->payment_status == self::PAYMENT_STATUS_PENDING) {
                            $links['to_bucket'] = array(
                                'url' => '#',
                                'text' => T('加入付款夹'),
                                'extra' => 'id="'.$rel.'" class="button button_bucket" '.
                                'q-object="to_bucket" q-event="click" '.
                                'q-static="'.H(array('id' => intval($this->id), 'rel' => $rel)).'"',
                                );
                        }
                    } else {
                        $links['remove_from_bucket'] = array(
                            'url' => '#',
                            'text' => T('移出付款夹'),
                            'extra' => 'id="'.$rel.'" class="button button_bucket" '.
                            'q-object="remove_from_bucket" q-event="click" '.
                            'q-static="'.H(array('id' => intval($this->id), 'rel' => $rel)).'"',
                            );
                    }
                }
            }

            if ($this->customer_can_receive() &&
                $me->is_allowed_to('确认收货', $this)) {
                $links['receive'] = array(
                    'url' => $this->url(null, null, null, 'receive'),
                    'text' => T('确认收货'),
                    'extra' => 'class="button button_tick" q-object="receive" q-event="click" q-static="'.H(array('id' => $this->id)).'"',
                    );
            }

            if ($this->customer_can_cancel() &&
                ($me->is_allowed_to('以买方取消', $this) || $this->purchaser->id == $me->id)) {
                $links['cancel'] = array(
                    'url' => '#',
                    'text' => T('取消订单'),
                    'extra' => 'class="button button_delete" q-object="cancel" q-event="click" q-src="'.$this->url(null, null, null, 'view').'" q-static="'.H(array( 'id' => $this->id )).'"',
                    );
            }

            if ($this->can_return() &&
                $me->is_allowed_to('退货', $this)) {
                $links['return'] = array(
                    // 'url' => $this->url(NULL, NULL, NULL, 'return'),
                    'url' => '#',
                    'text' => T('申请退货'),
                    // 'extra' => 'class="button button_delete" confirm="'.HT('您确定对该订单进行退货吗?').'"',
                    'extra' => 'class="button button_delete" q-object="return_order" q-event="click" q-src="'.$this->url(null, null, null, 'view').'"',
                    );
            }
            break;

        }

        return (array) $links;
    }

    public function save($overwrite = false)
    {
        if (!$this->id) {
            if (!$this->mtime) {
                $this->touch();
            }

            //如果有voucher则order_no与voucher相同
            if (!$this->voucher) {
                $this->voucher = self::generate_voucher();
            }

            $this->order_no = $this->voucher;

            $success = parent::save($overwrite);
        } else {
            //必须为order先save才会有order_item，所以order已经save过一次才会需要更新版本
            $old_hash = $this->compare_hash;
            $new_hash = $this->get_hash();

            if ($old_hash !== $new_hash) {
                $this->touch();
                //清空mall_description;
                $description = $this->mall_description;
                $this->mall_description = '';
                $this->compare_hash = $new_hash;

                //更新revision列表
                $hash = hash('sha1', $this->mtime.$new_hash);
                $revision_hashs = $this->revision_hashs;

                $mtime = new \Datetime();
                $mtime = $mtime->format('Y-m-d H:i:s');

                $revision_hashs[$hash] = $mtime;
                $this->revision_hashs = $revision_hashs;

                $success = parent::save($overwrite);
                if ($success) {
                    $data['hash'] = $hash;
                    $data['description'] = $description;
                    $success = $this->_create_revision($data);
                }
            } else {
                $success = parent::save($overwrite);
            }
            $this->debade_order_update();
        }

        return $success;
    }

    public function debade_order_update()
    {
        $items = Q("order_item[order=$this]:sort(product_id)");
        $items_data = [];
        $lab_order_client = Config::get('gapper.apps')['lab-orders']['client_id'];
        foreach ($items as $item) {
            $product = $item->product;
            $product_str = 'product/'.$product->id.'/'.$item->version;
            $product_revision = O('product_revision', ['product' => $product, 'version' => $item->version]);

            $items_data[] = [
                'product'        => (string) $product_str,
                'quantity'       => (int) $item->quantity,
                'unit_price'     => round($item->unit_price, 2),
                'total_price'    => round($item->unit_price * $item->quantity, 2),
                'deliver_status' => $item->deliver_status,
                'type'           => $product->type?:'',
                'cas_no'         => $product->cas_no? :'',
                'name'           => $product_revision->name ?: $product->name,
                'manufacturer'   => $product_revision->manufacturer ?: $product->manufacturer,
                'catalog_no'     => $product_revision->catalog_no ?: $product->catalog_no,
                'brand'          => $product_revision->brand ?: $product->brand,
                'package'        => $product_revision->package ?: $product->package,
            ];
        }

        /**
         * 强制 string 防止之后生成 hash 会因为是 数字或者字符串 乱掉
         */
        $requester_id = (string) $this->purchaser->gapper_user;
        $customer = $this->customer;
        $gapper_group = $customer->gapper_group;
        if (!$gapper_group) {
            $customer_group = O('customer_group', ['customer'=>$customer]);
            $gapper_group = $customer_group->gapper_group;
        }

        $vendor_id = (string) $this->vendor->gapper_group;

        $data = [
            'voucher' => $this->voucher,
            'node'=> SITE_ID,
            'client' => [
                'lab_orders' => $lab_order_client,
            ],
            'requester' => [
                'id' => $requester_id,
                'name' => $this->purchaser->name,
            ],
            'customer' => [
               'id' => $gapper_group,
               'name' => $this->customer->name,
            ],
            'vendor' => [
                'id' => (int) $this->vendor->id,
                'name' => $this->vendor->name,
            ],
            'address' => (string) $this->address,
            'invoice_title' => (string) $this->invoice_title,
            'phone' => (string) $this->phone,
            'postcode' => (string) $this->postcode,
            'email' => (string) $this->email,
            'note' => (string) $this->description,
            'status' => (int) $this->status,
            'payment_status' => (int) $this->payment_status,
            'deliver_status' => (int) $this->deliver_status,
            'label'=> $this->label,
            'items' => (array) $items_data,
            'price'=> $this->price
        ];
        DeBaDe::of('order')->push(['id' => 'order', 'data' => $data ],
            "c-{$gapper_group}.v-{$vendor_id}");
    }

    public function update_revisions($revision, $parent = 0)
    {
        $revision_hashs = (array) $this->revision_hashs;

        $ctime = new \Datetime();
        $ctime->setTimestamp($revision->ctime);
        $ctime = $ctime->format('Y-m-d H:i:s');

        if (!$parent) {
            $revision_hashs[$revision->hash] = $ctime;
        } else {
            $insert = [$revision->hash => $ctime];
            $pos = array_search($parent, array_keys($revision_hashs)) + 1;

            $revision_hashs = array_merge(
                array_slice($revision_hashs, 0, $pos),
                $insert,
                array_slice($revision_hashs, $pos)
            );
        }

        $this->revision_hashs = $revision_hashs;

        return $this;
    }

    public function can_approve()
    {
        return $this->status == self::STATUS_PENDING_APPROVAL ||
            $this->status == self::STATUS_RETURNING_APPROVAL;
    }

    public function approve()
    {
        if (!$this->can_approve()) {
            return false;
        }
        $this->status = self::STATUS_APPROVED;
        //用户审核标记 被买方管理员审核后变更状态为true  by sunxu 2015-04-09
        $this->customer_approved = true;
        $ret = $this->save();

        if ($ret) {
            Event::trigger('order_is_approved', $this);
        }

        return $ret;
    }

    public function can_return_approve()
    {
        return $this->status == self::STATUS_RETURNING_APPROVAL;
    }

    public function return_approve($reason = '')
    {
        if (!$this->can_return_approve()) {
            return false;
        }
        $this->status = self::STATUS_APPROVED;

        $this->return_approved_reason = $reason;
        $ret = $this->save();

        if ($ret) {
            Event::trigger('order_is_return_approved', $this);
        }

        return $ret;
    }

    /**
     * @brief 取消订单
     *
     * @return
     */
    public function cancel($reason = '')
    {
        if (!$this->id || $this->status == self::STATUS_CANCELED) {
            return false;
        }

        $this->status = self::STATUS_CANCELED;
        $this->cancel_reason = $reason;

        $ret = $this->save();
        if ($ret) {
            Event::trigger('order_is_canceled', $this);
        }

        return $ret;
    }

    public function recover($reason = '')
    {
        if (!$this->temp_price_to_real()) {
            return false;
        }

        $this->status = self::STATUS_RETURNING_APPROVAL;
        $this->recover_reason = $reason;

        $now = new \Datetime();
        $now = $now->format('Y-m-d H:i:s');
        $this->mall_description = [
            'a' => H(T('**:user(:vendor)** 拒绝退货', [
                        ':user' => L('ME')->name,
                        ':vendor' => $this->vendor->short_name
                    ])),
            't' => $now,
            'u' => L('ME')->gapper_user,
            'd' => $reason,
        ];

        $ret = $this->save();

        if ($ret) {
            Event::trigger('order_is_recovered', $this);
        }

        return $ret;
    }

    public function can_return()
    {
        $bucket = Transfer_bucket_Model::customer_bucket($this->customer);
        $transfer_statement = Q("$this transfer_statement")->current();

        return $this->status == Order_Model::STATUS_APPROVED &&
            !$bucket->contains($this) &&
            !$transfer_statement->id;
    }

    public function return_order($reason = '')
    {
        if (!($this->id && ($this->can_return() || $this->can_return_approve()))) {
            return false;
        }

        $this->status = self::STATUS_RETURNING;
        $this->return_reason = $reason;

        $ret = $this->save();

        if ($ret) {
            Event::trigger('order_is_returning', $this);
        }

        return $ret;
    }

    public function can_transfer()
    {
        return self::STATUS_APPROVED == $this->status && self::PAYMENT_STATUS_PENDING == $this->payment_status;
    }

    public function set_pending_transfer()
    {

        // TODO 检查状态 (xiaopei.li@2012-06-05)

        $this->status = self::STATUS_PENDING_TRANSFER;
        $this->is_transfer_failed = false;
        $ret = $this->save();

        if ($ret) {
            Event::trigger('order_is_pending_transfer', $this);
        }

        return $ret;
    }

    public function set_transferred()
    {
        if (!$this->id || $this->status == self::STATUS_TRANSFERRED) {
            return false;
        }

        $this->status = self::STATUS_TRANSFERRED;
        $this->transferred_date = Date::time();
        $this->mall_description = [
            'a' => '付款成功',
            't' => date('Y-m-d H:i:s'),
        ];
        $ret = $this->save();

        if ($ret) {
            Event::trigger('order_is_transferred', $this);
        }

        return $ret;
    }

    public function set_transfer_failed()
    {
        if (!$this->id || $this->status != self::STATUS_PENDING_TRANSFER) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->is_transfer_failed = true;
        $this->mall_description = [
            'a' => '付款失败',
            't' => date('Y-m-d H:i:s'),
        ];
        $ret = $this->save();

        if ($ret) {
            Event::trigger('order_is_transfer_failed', $this);
        }

        return $ret;
    }

    public function is_transfer_failed()
    {
        return $this->is_transfer_failed;
    }

    public function can_pay()
    {
        $enable = Config::get('payment.enable_vendor_close', false);
        return ($this->status == self::STATUS_TRANSFERRED) && !$enable;
    }

    public function set_pending_payment()
    {
        if (!$this->id || !$this->can_pay()) {
            return false;
        }

        $this->status = self::STATUS_PENDING_PAYMENT;
        $ret = $this->save();

        if ($ret) {
            Event::trigger('order_is_pending_payment', $this);
        }

        return $ret;
    }

    public function set_paid()
    {
        $enable = Config::get('payment.enable_vendor_close', false);
        if (!$this->id) {
            return false;
        }
        $allow_statuses = [
            self::STATUS_PENDING_PAYMENT,
        ];
        if ($enable) {
            $allow_statuses[] = self::STATUS_TRANSFERRED;
        }
        if (!in_array($this->status, $allow_statuses)) {
            return false;
        }

        $this->status = self::STATUS_PAID;
        $ret = $this->save();

        if ($ret) {
            Event::trigger('order_is_paid', $this);
        }

        return $ret;
    }

    public function item_count()
    {
        return Q("order_item[order=$this]")->total_count();
    }

    /**
     * @brief 更新订单的总价
     *
     * @return
     */
    public function update_price()
    {
        $this->price = Q("order_item[order=$this][price>=0]")->sum('price') ?: 0;

        return $this;
    }

    // (商家在修改订单项后)更新订单临时价格 (xiaopei.li@2012-07-30)
    public function update_temp_price()
    {
        $price = 0;
        foreach (Q("order_item[order=$this]") as $item) {
            if ($item->temp_delete) {
                continue;
            }

            $item_price = ($item->temp_price !== null) ?
                $item->temp_price : $item->price;
            if ($item_price > 0) {
                $price += $item_price;
            }
        }

        $this->temp_price = $price;

        return $this;
    }

    /**
     * @brief 买方确认订单
     *
     * @return
     */
    public function customer_confirm($user = null)
    {
        $customer = $user;
        if (!$customer->id) {
            $customer = L('ME');
        }

        if ($customer->is_allowed_to('以买方确认', $this)) {
            $order->customer_confirmed = true;
            $ret = $this->check_confirm_status();

            return $ret;
        }
    }

    public function customer_is_confirmed()
    {
        return $this->confirm & 010;
    }

    public function vendor_can_confirm()
    {
        return $this->status == Order_Model::STATUS_NEED_VENDOR_APPROVE &&
            !$this->vendor_is_confirmed();
    }

    public function customer_can_confirm()
    {
        return $this->status == Order_Model::STATUS_NEED_VENDOR_APPROVE &&
            !$this->customer_is_confirmed() &&
            $this->temp_price === null;
    }

    public function customer_can_cancel()
    {
        return $this->status == self::STATUS_NEED_VENDOR_APPROVE ||
            $this->status == self::STATUS_PENDING_APPROVAL ||
            $this->status == self::STATUS_NEED_CUSTOMER_APPROVE ||
            $this->status == self::STATUS_REQUESTING;
    }

    public function admin_can_cancel()
    {
        return $this->status == self::STATUS_NEED_VENDOR_APPROVE ||
            $this->status == self::STATUS_PENDING_APPROVAL ||
            $this->status == self::STATUS_REQUESTING ||
            $this->status == self::STATUS_NEED_CUSTOMER_APPROVE ||
            $this->status == self::STATUS_APPROVED;
    }

    public function vendor_can_cancel()
    {
        return $this->status == Order_Model::STATUS_RETURNING ||
            $this->status == self::STATUS_NEED_VENDOR_APPROVE;
    }

    public function vendor_can_recover()
    {
        return $this->status == Order_Model::STATUS_RETURNING;
    }

    public function vendor_can_edit()
    {
        return $this->status == Order_Model::STATUS_NEED_VENDOR_APPROVE ||
            $this->status == Order_Model::STATUS_RETURNING ||
            $this->status == Order_Model::STATUS_NEED_CUSTOMER_APPROVE;
    }

    public function temp_price_to_real()
    {
        $this->mall_description['d'] = $description;
        $items = array();
        $description = $this->mall_description ?: [];
        foreach (Q("order_item[order=$this]") as $item) {
            if ($item->temp_delete) {
                $item->delete();
                $description['d'][] = H(T('[%product_name](product/%product_id/%version) 从订单中移除',
                    [
                        '%product_name' => $item->product->name,
                        '%product_id' => $item->product->id,
                        '%version' => $item->version
                    ]));
                continue;
            }
            //跟踪信息
            if ($item->temp_unit_price != null && $item->temp_quantity != null) {
                $description['d'][] = H(T('[%product_name](product/%product_id) 的价格从 %origin_unit_price 修改到 %current_unit_price, 数量从 %origin_quantity 修改到 %current_quantity',
                    [
                        '%product_name' => $item->product->name,
                        '%product_id' => $item->product->id,
                        '%origin_unit_price' => $item->unit_price == -1 ? '待询价' : $item->unit_price,
                        '%current_unit_price' => $item->temp_unit_price == -1 ? '待询价' : $item->temp_unit_price,
                        '%origin_quantity' => $item->quantity,
                        '%current_quantity' => $item->temp_quantity,
                        '%version' => $item->version
                    ]
                ));
            } elseif ($item->temp_unit_price != null) {
                $description['d'][] = H(T('[%product_name](product/%product_id/%version) 的价格从 %origin_unit_price 修改到 %current_unit_price',
                    [
                        '%product_name' => $item->product->name,
                        '%product_id' => $item->product->id,
                        '%origin_unit_price' => $item->unit_price == -1 ? '待询价' : $item->unit_price,
                        '%current_unit_price' => $item->temp_unit_price == -1 ? '待询价' : $item->temp_unit_price,
                        '%version' => $item->version
                    ]
                ));
            } elseif ($item->temp_quantity != null) {
                $description['d'][] = H(T('[%product_name](product/%product_id/%version) 的数量从 %origin_quantity 修改到 %current_quantity',
                    [
                        '%product_name' => $item->product->name,
                        '%product_id' => $item->product->id,
                        '%origin_quantity' => $item->quantity,
                        '%current_quantity' => $item->temp_quantity,
                        '%version' => $item->version
                    ]
                ));
            }

            $item->unit_price = $item->temp_unit_price !== null ? $item->temp_unit_price : $item->unit_price;
            $item->quantity = $item->temp_quantity !== null ? $item->temp_quantity : $item->quantity;
            $item->price = $item->temp_price !== null ? $item->temp_price : $item->price;

            $item->temp_unit_price = null;
            $item->temp_quantity = null;
            $item->temp_price = null;
            // TODO 这里应该判断 order_item 的 unit_price,
            // 而此 unit_price 在订单生成时就该赋值!!! (xiaopei.li@2012-04-16)
            if ($item->price < 0) {
                return false; // TODO 明确返回错误信息的形式(xiaopei.li@2012-04-16)
            }

            $items[] = $item;
        }

        foreach ($items as $item) {
            $item->save();
        }

        if ($this->temp_price !== null) {
            $this->temp_price = null;
        }

        $this->update_price();
        if ($description['d']) {
            $description['d'] = implode("\n", $description['d']);
        }

        $this->mall_description = $description;

        return true;
    }

    /**
     * @brief 供应商确认订单
     *
     * @return
     */
    public function vendor_confirm()
    {
        $ret = true;
        //如果订单存在 temp_price 就需要买方确认, 如果是请供应商确认则回到待买方确认
        if (($this->temp_price > $this->price) || !$this->customer_confirmed) {
            $this->status = self::STATUS_NEED_CUSTOMER_APPROVE;
        }
        $me = L('ME');

        //跟踪信息
        $now = new \Datetime();
        $now = $now->format('Y-m-d H:i:s');
        $this->mall_description = [
            'a' => H(T('**:user(:vendor)** **确认**了该订单', [
                    ':user' => $me->name,
                    ':vendor' => $this->vendor->short_name
                ])),
            't' => $now,
            'u' => $me->gapper_user,
        ];

        if (!$this->temp_price_to_real()) {
            return false;
        }
        //用户审核标记 被卖方确认后变更状态为true  fix bug 8624  by sunxu 2015-04-17
        $this->customer_approved = true;

        $ret = $this->save();

        if ($ret) {
            Event::trigger('order_is_confirmed_by_vendor', $this);
        }

        if ($this->status == self::STATUS_NEED_CUSTOMER_APPROVE) {
            Event::trigger('order_need_customer_confirm', $this);
        } else {
            $ret = $this->check_confirm_status();
        }

        return $ret;
    }

    public function reset_confirm()
    {
        $this->confirm = 0;

        return $this;
    }

    public function check_confirm_status()
    {
        $this->status = self::STATUS_APPROVED;

        $ret = $this->save();
        if ($ret) {
            Event::trigger('order_is_confirmed', $this);
        }

        return $ret;
    }

    const HAS_NEWS = 'has_news';

    public function set_has_news_to($user)
    {
        return $this->connect($user, self::HAS_NEWS);
    }

    public function unset_has_news_to($user)
    {
        return $this->disconnect($user, self::HAS_NEWS);
    }

    public function has_news_to($user)
    {
        return $this->connected_with($user, self::HAS_NEWS);
    }

    public function reset_has_news_to_all()
    {
        foreach (Q("{$this->customer}<member user") as $customer_member) {
            $this->set_has_news_to($customer_member);
        }

        foreach (Q("{$this->vendor}<member user") as $vendor_member) {
            $this->set_has_news_to($vendor_member);
        }
    }

    public function reset_has_news_to_all_except($except)
    {
        $this->reset_has_news_to_all();

        $this->unset_has_news_to($except);
    }

    public function vendor_can_deliver()
    {
        return ($this->status == self::STATUS_APPROVED ||
                  $this->status == self::STATUS_PENDING_TRANSFER ||
                  $this->status == self::STATUS_TRANSFERRED ||
                  $this->status == self::STATUS_PENDING_PAYMENT ||
                  $this->status == self::STATUS_PAID) &&
            $this->deliver_status == self::DELIVER_STATUS_NOT_DELIVERED;
    }

    public function deliver()
    {
        $this->deliver_status = self::DELIVER_STATUS_DELIVERED;
        $now = new \Datetime();
        $this->deliver_date = Date::time();
        $now = $now->format('Y-m-d H:i:s');

        $this->mall_description = [
            'a' => H(T('**:user(:vendor)** 进行了 **发货** 操作', [
                    ':user' => L('ME')->name,
                    ':vendor' => $this->vendor->short_name
                ])),
            't' => $now,
            'u' => L('ME')->gapper_user,
        ];

        $ret = $this->save();
        if ($ret) {
            Event::trigger('order_is_delivered', $this);
        }

        return $ret;
    }

    public function customer_can_receive()
    {
        return ($this->status == self::STATUS_APPROVED ||
                $this->status == self::STATUS_PENDING_TRANSFER ||
                $this->status == self::STATUS_TRANSFERRED ||
                $this->status == self::STATUS_PENDING_PAYMENT ||
                $this->status == self::STATUS_PAID) &&
                ($this->deliver_status != self::DELIVER_STATUS_RECEIVED);
    }

    public function receive()
    {
        $this->deliver_status = self::DELIVER_STATUS_RECEIVED;
        $order_items = Q("order_item[order={$this}][!receive_date]");
        if ($order_items->total_count()) {
            foreach ($order_items as $order_item) {
                $order_item->receive_date = Date::time();
                $order_item->receiver = L('ME');
                $order_item->save();
            }
        }

        $ret = $this->save();

        if ($ret) {
            Event::trigger('order_is_received', $this);
        }

        return $ret;
    }

    public function get_users_to_notify()
    {
        $users_to_notify = array();

        // 订单的下单者
        $users_to_notify[$this->purchaser->id] = $this->purchaser;

        // 每个订单项的申购者
        foreach (Q("$this order_item<requester user") as $requester) {
            $users_to_notify[$requester->id] = $requester;
        }

        return $users_to_notify;
    }

    //管理员是否可对订单进行付款
    public function admin_can_transfer()
    {
        return Config::get('order.admin_can_transfer', false) && $this->can_transfer();
    }

    //管理员是否可对订单进行结算
    public function admin_can_pay()
    {
        return Config::get('order.admin_can_pay', false) && $this->can_pay();
    }

    public function get_hash()
    {
        $items = Q("order_item[order=$this]:sort(product_id)");
        if ($items->total_count()) {
            $items_data = [];
            foreach ($items as $item) {
                $product = 'product/'.$item->product_id.'/'.$item->version;

                $items_data[] = [
                    'product' => (string) $product,
                    'quantity' => (int) $item->quantity,
                    'price' => round($item->unit_price, 2),
                    'deliver_status' => $item->deliver_status,
                ];
            }
        }

        $hash_data = [
            'requester' => (string) $this->purchaser->gapper_user,
            'vendor' => (int) $this->vendor->gapper_group,
            'address' => (string) $this->address,
            'phone' => (string) $this->phone,
            'postcode' => (string) $this->postcode,
            'email' => (string) $this->email,
            'note' => (string) $this->description,
            'status' => (int) $this->status,
            'payment_status' => (int) $this->payment_status,
            'deliver_status' => (int) $this->deliver_status,
            'binded_inventory' => (array) $this->binded_inventory,
            'items' => (array) $items_data,
        ];
        if ($this->hash_rand_key) {
            $hash_data['hash_rand_key'] = $this->hash_rand_key;
        }
        return hash('sha1', json_encode($hash_data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }

    private function _create_revision(array $data)
    {
        $order_revision = O('order_revision');
        $now = time();

        $items = Q("order_item[order=$this]:sort(product_id)");
        if ($items->total_count()) {
            $items_data = [];
            foreach ($items as $item) {
                $items_data[] = [
                    'id' => $item->product_id,
                    'version' => $item->version,
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'price' => $item->price,
                    'deliver_status' => $item->deliver_status,
                    'origin_quantity' => $item->origin_quantity,
                ];
            }
        }

        $order_revision->vendor = $this->vendor;
        $order_revision->customer = $this->customer;
        $order_revision->requester = $this->purchaser;
        $order_revision->request_date = (int) $this->purchase_date;
        $order_revision->transferred_date = (int) $this->transferred_date;
        $order_revision->address = $this->address;
        $order_revision->invoice_title = $this->invoice_title;
        $order_revision->phone = $this->phone;
        $order_revision->postcode = $this->postcode;
        $order_revision->email = $this->email;
        $order_revision->note = $this->description;
        $order_revision->voucher = $this->voucher ?: '';
        $order_revision->price = $this->price ?: 0;
        $order_revision->status = $this->status;
        $order_revision->deliver_status = (int) $this->deliver_status;
        $order_revision->deliver_date = $this->deliver_date;
        $order_revision->payment_status = (int) $this->payment_status;
        $order_revision->ctime = $this->mtime;
        $order_revision->hash_rand_key = $this->hash_rand_key;
        $order_revision->hash = $data['hash'];
        $order_revision->items = json_encode($items_data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $order_revision->binded_inventory = json_encode($this->binded_inventory, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $order_revision->operator = $this->operator->id ? $this->operator : L('ME');
        $order_revision->order = $this;
        //部分虚属性
        $order_revision->description = $data['description'];

        return $order_revision->save();
    }

    public static function generate_voucher()
    {
        $prefix = 'M'.date('Ymd');
        $file = SITE_PATH.'private/order_voucher/'.$prefix;

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $fp = fopen($file, 'c+');
        if ($fp) {
            flock($fp, LOCK_EX);
            $index = (int) fgets($fp) + 1;
            ftruncate($fp, 0);
            rewind($fp);
            fputs($fp, $index);
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return $prefix.str_pad($index, 4, '0', STR_PAD_LEFT);
    }
}
