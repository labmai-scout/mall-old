<?php

class API_Mall_Order_Exception extends API_Exception
{
}

class API_Mall_Order
{
    /**
     * createOrder 创建订单.
     *
     * @param array  $data    订单数据
     * @param string $voucher 订单流水号，确定唯一订单
     *
     * @throws exception 1001: 用户不存在!
     * @throws exception 1002: 未激活用户不能购买商品, 请与管理员联系!
     * @throws exception 1003: 买方不存在!
     * @throws exception 1004: 供货商不存在!
     * @throws exception 1005: 订单生成失败!
     *
     * @return array 订单id和订单链接 或 异常码和异常信息
     */
    public function createOrder($data, $voucher = '')
    {
        if (!API_Mall::is_authenticated()) {
            return;
        }
        try {
            $auto_comfirm = true;

            $customer = O('customer', $data['customer']);

            if (!$customer->id && $data['customer']['gapper_group']) {
                $customer = Gapper::create_customer($data['customer']['gapper_group']);
            }

            //customer不存在
            if (!$customer->id) {
                throw new API_Mall_Order_Exception(T('买方不存在!'), 1003);
            }

            $user = O('user', $data['requester']);

            if (!$user->id && $data['requester']['gapper_user']) {
                $user = Gapper::create_user((int) $data['requester']['gapper_user']);
            }

            //如果没有得到用户, 则已管理员的身份购买
            if (!$user->id) {
                if ($data['requester_name']) {
                    //增加标志位，如果是默认负责人购买的话，不可跳过买方确认
                    $auto_comfirm = false;
                    $user = $customer->owner;
                    $user->name = $data['requester_name'];
                } else {
                    //user不存在
                    throw new API_Mall_Order_Exception(T('用户不存在!'),  1001);
                }
            }

            if (!$user->atime) {
                throw new API_Mall_Order_Exception(T('未激活用户不能购买商品, 请与管理员联系!'), 1002);
            }

            if (!$customer->has_member($user)) {
                $customer->connect($user, 'member');
            }

            Cache::L('ME', $user);

            $vendor = O('vendor', array(
                'gapper_group' => $data['vendor'],
            ));
            //vendor不存在
            if (!$vendor->id) {
                throw new API_Mall_Order_Exception(T('供货商不存在!'), 1004);
            }

            $order = O('order');

            if ($voucher) {
                //如果订单已经存在，则不生成新订单
                $exist_order = O('order', ['voucher' => $voucher]);
                if ($exist_order->id) {
                    return [
                        'id' => $exist_order->id,
                        'url' => $exist_order->url(),
                        'status' => $exist_order->status,
                    ];
                }

                $order->voucher = $voucher;
            }

            $db = Order_Model::db('order');
            $db->begin_transaction();

            $order->vendor = $vendor;
            $order->customer = $customer;
            $order->purchaser = $user;
            $order->purchase_date = max(strtotime($data['request_date']), 0);
            $order->invoice_title = $data['invoice_title'];
            $order->address = $data['address'];
            $order->phone = $data['phone'];
            $order->description = $data['note'] ?: '';
            $order->postcode = $data['postcode'];
            $order->email = $data['email'];
            $order->payment_status = (int) $data['payment_status'];
            $order->status = Order_Model::STATUS_REQUESTING;

            if ($data['extra_info']) {
                $order->extra_info = $data['extra_info'];
            }

            if ($order->save()) {
                if ($auto_comfirm && $user->is_allowed_to('以买方确认', $order)) {
                    $order->customer_confirmed = true;
                    //用户审核标记 被买方管理员直接购买后变更状态为true  by sunxu 2015-04-09
                    $order->customer_approved = true;
                    $order->status = Order_Model::STATUS_NEED_VENDOR_APPROVE;
                }
            }

            if (!$order->id) {
                throw new API_Mall_Order_Exception(T('订单生成失败!'), 1005);
            }

            foreach ((array) $data['items'] as $id => $quantity) {
                $product = O('product', $id);

                if (!$product->id) {
                    $error[$product->id] = T('商品不存在!');
                }

                // if ($product->freeze_reasons) {
                //     $error[$product->id] = T('该商品已被冻结!');
                //     continue;
                // }

                // if ($product->expire_date && $product->expire_date < time()) {
                //     $error[$product->id] = T('该商品已过期!');
                //     continue;
                // }
                if ($product->status != Product_Model::STATUS_ON_SALE) {
                    $error[$product->id] = T('该商品已下架!');
                    continue;
                }

                //之前的流程已经判断了商品是否可买，这里暂时不做处理
                $avoid_reason = Event::trigger('product.get_avoid_buy_msg', $product);
                if ($avoid_reason) {
                    $error[$product->id] = $avoid_reason;
                    continue;
                }

                //该vendor是否销售该product
                if ($product->vendor_id != $vendor->gapper_group) {
                    $error[$product->id] = T('该供货商不销售该商品!');
                    continue;
                }

                $order_item = O('order_item');

                $order_item->order = $order;
                $order_item->product = $product;
                $order_item->origin_quantity = $order_item->quantity = $quantity;
                $order_item->requester = $user;
                $order_item->request_date = Date::time();
                $order_item->status = $order->status;

                if ($data['price']) {
                    $order_item->price = $data['price'];
                    $order_item->unit_price = $data['price'];
                } else {
                    $order_item->price = $product->get_price($customer, $order_item->quantity);
                    $order_item->unit_price = $order_item->price > 0 ?
                        $order_item->price / $order_item->quantity :
                        $order_item->price; // 0 or 待询价
                }

                $order_item->save();

                if (!$order_item->id) {
                    $error[$product->id] = T('订单商品生成失败!');
                    continue;
                }
            }

            if (count($error)) {
                throw new API_Mall_Order_Exception();
            }

            $order->update_price()->save();
            $price = round($order->price, 2);
            $max_order_price = round(Config::get('mall.max_order_price', 100000), 2);
            if ($max_order_price <= $price) {
                throw new API_Mall_Order_Exception(T('订单金额过大, 不允许生成金额大于%max_price的订单!', ['%max_price' => $max_order_price]), 1006);
            }
            $db->commit();

            $result = [
                'id' => $order->id,
                'url' => $order->url(),
                'status' => $order->status,
            ];
            try {
                // 发消息提醒和添加comment
                Event::trigger('order_is_drafted', $order);
                //添加log
                // 2015-05-05发现由于IO性能较低，可能出现写日志失败的问题，导致抛出异常
                Log::add(sprintf('[order] %s[%d] 在新商城生成了订单 %d',
                      $user->name, $user->id, $order->order_no),
                'order');
            } catch (Exception $e) {
                error_log(sprintf('不应该出错的地方出错了: [order#%d] %s', $order->id, $e->getMessage()));
            }

            return $result;
        } catch (API_Mall_Order_Exception $e) {
            if ($e->getMessage()) {
                $error_msg['*'] = $e->getMessage();
            } else {
                $error_msg = $error;
            }

            //回滚
            $db->rollback();

            $result = [
                'error_msg' => $error_msg,
                'error_code' => $error_code,
            ];

            return $result;
        } catch (Exception $e) {
            $error_msg['*'] = T('系统错误, 请与管理员联系!');
            $error_code = $e->getCode();

            //回滚
            $db->rollback();

            $result = [
                'error_msg' => $error_msg,
                'error_code' => $error_code,
            ];

            return $result;
        }
    }

    /**
     * 得到订单凭证
     *
     * @return voucher
     */
    public function getVoucher()
    {
        if (!API_Mall::is_authenticated()) {
            return;
        }

        return Order_Model::generate_voucher();
    }

    /**
     * 查询订单.
     *
     * @param array $criteria 查询条件
     */
    public function searchOrders($criteria)
    {
        if (!API_Mall::is_authenticated()) {
            return;
        }

        $token = Session::temp_token('mall.searchProducts.opts', 300);

        $selector = 'order';

        if (isset($criteria['product_id'])) {
            $pre_selectors[] = "product[id={$criteria['product_id']}] order_item ";
        }

        if (isset($criteria['ctime_start'])) {
            $selector .= "[ctime>={$criteria['ctime_start']}]";
        }

        if (isset($criteria['ctime_end'])) {
            $selector .= "[ctime<{$criteria['ctime_end']}]";
        }

        if (isset($criteria['mtime_start'])) {
            $selector .= "[mtime>={$criteria['mtime_start']}]";
        }

        if (isset($criteria['mtime_end'])) {
            $selector .= "[mtime<{$criteria['mtime_end']}]";
        }

        if (isset($criteria['id'])) {
            $selector .= "[id={$criteria['id']}]";
        }

        if (isset($criteria['voucher'])) {
            $selector .= "[voucher={$criteria['voucher']}]";
        }

        if (isset($criteria['status'])) {
            $selector .= "[status={$criteria['status']}]";
        }

        if (isset($criteria['payment_status'])) {
            $selector .= "[payment_status={$criteria['payment_status']}]";
        }

        if (count($pre_selectors) > 0) {
            $selector = '('.implode(', ', (array) $pre_selectors).') '.$selector;
        }

        $_SESSION[$token] = $selector;
        $total_count = Q($selector)->total_count();

        return [
            'token' => $token,
            'total_count' => $total_count,
        ];
    }

    public function getOrder($voucher)
    {
        if (!API_Mall::is_authenticated()) {
            return;
        }
        $order = O('order', ['voucher' => $voucher]);
        if (!$order->id) {
            return false;
        }
        $items = Q("order_item[order={$order}]");
        $confs = Config::get('gapper.apps');
        $conf  =  $confs['lab-orders'];
        $data = [];
        $data['order_id'] = $order->id;
        $data['node'] = SITE_ID;
        $data['voucher'] = $order->voucher;
        $data['price'] = $order->price;
        $data['ctime'] = $order->ctime;
        $data['request_date'] = $order->purchase_date;
        $data['status'] = $order->status;
        $data['customer'] = $order->customer->gapper_group;
        $data['customer_name'] = $order->customer->name;
        $data['customer_owner'] = $order->customer->owner->gapper_user;
        $data['vendor'] = $order->vendor->gapper_group;
        $data['vendor_name'] = $order->vendor->name;
        $data['vendor_owner'] = $order->vendor->owner->gapper_user;
        $data['address'] = $order->address;
        $data['postcode'] = $order->postcode;
        $data['phone'] = $order->phone;
        $data['email'] = $order->email;
        $data['revision_hashs'] = $order->revision_hashs;
        $data['description'] = $order->description;
        $data['lab_orders_client_id'] = $conf['client_id'];
        $items_data = [];
        foreach ($items as $item) {
            $items_data[$item->product_id] = [
                'quantity' => $item->quantity,
                'name' => $item->product->name,
                'manufacturer' => $item->product->manufacturer,
                'catalog_no' => $item->product->catalog_no,
                'model' => $item->product->model,
                'spec' => $item->product->spec,
                'unit_price' => $item->unit_price,
                'price' => $item->price,
                'cas_no' => $item->product->cas_no,
                'brand' => $item->product->brand,
                'package' => $item->product->package,
                'cas_no' => $item->product->cas_no,
            ];
        }
        $data['items'] = $items_data;
        $comments = Q("comment[object=$order]:sort(ctime D)");
        $comment_data = [];
        foreach ($comments as $comment) {
            $comment_data[] = [
                'author' => $comment->author->name ?: HT('系统'),
                'ctime' => $comment->ctime,
                'mtime' => $comment->mtime,
                'content' => $comment->content,
            ];
        }
        $data['comments'] = $comment_data;

        return $data;
    }

    /**
     * [getOrders description].
     *
     * @param [type] $token [description]
     * @param [type] $start [description]
     * @param [type] $step  [description]
     *
     * @return [type] [description]
     */
    public function getOrders($token, $start, $step)
    {
        if (!API_Mall::is_authenticated()) {
            return;
        }

        if (!$selector = $_SESSION[$token]) {
            return [];
        }

        $orders = Q($selector)->limit($start, $step);
        $results = [];

        foreach ($orders as $order) {
            $arr['order_id'] = $order->id;
            $arr['purchaser'] = array(
                'id' => $order->purchaser->id,
                'name' => $order->purchaser->name,
                'token' => $order->purchaser->token,
            );
            $arr['node'] = SITE_ID;
            $arr['gapper_group'] = $order->customer->gapper_group;
            $arr['purchase_date'] = $order->purchase_date;
            $arr['description'] = $order->description;
            $arr['voucher'] = $order->voucher;
            $arr['status'] = $order->status;
            $arr['deliver_status'] = $order->deliver_status;
            $arr['deliver_date'] = $order->deliver_date;
            $arr['invoice_title'] = $order->invoice_title;
            $arr['address'] = $order->address;
            $arr['postcode'] = $order->postcode;
            $arr['email'] = $order->email;
            $arr['phone'] = $order->phone;
            $arr['extra_info'] = $order->extra_info;
            $arr['binded_inventory'] = $order->binded_inventory;

            $comments = Q("comment[object=$order]:sort(ctime D)");
            $comment_data = [];
            foreach ($comments as $comment) {
                $comment_data[] = [
                    'author' => $comment->author->name ?: HT('系统'),
                    'ctime' => $comment->ctime,
                    'mtime' => $comment->mtime,
                    'content' => $comment->content,
                ];
            }
            $arr['comments'] = $comment_data;

            $item_arr = array();
            $items = Q("order_item[order=$order]");

            foreach ($items as $item) {
                $id = $item->product->id;
                $item_arr[$id]['product_name'] = $item->product->name;
                $item_arr[$id]['product_id'] = $id;
                $item_arr[$id]['item_id'] = $item->id;
                $item_arr[$id]['manufacturer'] = $item->product->manufacturer;
                $item_arr[$id]['catalog_no'] = $item->product->catalog_no;
                $item_arr[$id]['model'] = $item->product->model;
                $item_arr[$id]['spec'] = $item->product->spec;
                $item_arr[$id]['vendor'] = $item->product->vendor->name;
                $item_arr[$id]['quantity'] = $item->quantity;
                $item_arr[$id]['unit_price'] = $item->unit_price;
                $item_arr[$id]['price'] = $item->price;
                $item_arr[$id]['link'] = $item->order->url();
                $item_arr[$id]['request_date'] = $item->request_date;
                $item_arr[$id]['receive_date'] = $item->receive_date;
                $item_arr[$id]['requester'] = array(
                    'id' => $item->requester->id,
                    'name' => $item->requester->name,
                );
                $item_arr[$id]['receiver'] = array(
                    'id' => $item->receiver->id,
                    'name' => $item->receiver->name,
                );
                $item_arr[$id]['deliver_status'] = $item->deliver_status;
                $item_arr[$id]['request_confirm'] = $item->request_confirm;
                $item_arr[$id]['customer_confirm'] = $item->customer_confirm;
            }
            $arr['items'] = $item_arr;
            $results[] = $arr;
        }

        return $results;
    }

    /**
     * updateOrder 更新订单信息.
     *
     * @param int   $id   订单id
     * @param array $data 订单信息数据
     *
     * @throws exception 404: 买方不存在!
     * @throws exception 404: 订单不存在!
     *
     * @return bool 是否更新成功
     */
    public function updateOrder($id, $data)
    {
        $ret = false;
        if (is_int($id)) {
            $order = O('order', $id);
        } else {
            $order = O('order', ['voucher' => $id]);
        }
        if (!$order->id) {
            throw new API_Mall_Order_Exception(T('订单不存在!'), 404);
        }

        // 更新订单的时候，还要检测买方信息？不合理，但是可能存在历史功能需要用到这部分逻辑
        if (isset($data['customer']['id']) || isset($data['customer']['gapper_group'])) {
            if (isset($data['customer']['id'])) {
                $customer = O('customer', $data['customer']['id']);
            } elseif (isset($data['customer']['gapper_group'])) {
                $customer = O('customer', ['gapper_group' => $data['customer']['gapper_group']]);
            }
            //customer不存在
            if (!$customer->id) {
                throw new API_Mall_Order_Exception(T('买方不存在!'), 404);
            }
        }

        if (isset($data['user']['id']) || isset($data['user']['gapper_user'])) {
            if ($user_id = $data['user']['id']) {
                $user = O('user', $user_id);
            } elseif ($user_id = $data['user']['gapper_user']) {
                $user = O('user', ['gapper_user' => $user_id]);
                if (!$user->id) {
                    $user = Gapper::create_user((int) $user_id);
                }
            }

            if (!$user->id) {
                if ($data['user']['name']) {
                    //增加标志位，如果是默认负责人购买的话，不可跳过买方确认
                    $auto_comfirm = false;
                    $user = $customer->owner;
                    $user->name = $data['user']['name'];
                } else {
                    //如果没有传过来user_name
                    throw new API_Exception(T('用户不存在!'), 1008);
                }
            }
        }

        if ($customer && $customer->id && $user && $user->id) {
            if (!$customer->has_member($user)) {
                $customer->connect($user, 'member');
            }
            Cache::L('ME', $user);
            $order->operator = $user;
        }

        if (isset($data['invoice_title'])) {
            $order->invoice_title = $data['invoice_title'];
        }

        if (isset($data['address'])) {
            $order->address = $data['address'];
        }
        if (isset($data['postcode'])) {
            $order->postcode = $data['postcode'];
        }
        if (isset($data['email'])) {
            $order->email = $data['email'];
        }
        if (isset($data['phone'])) {
            $order->phone = $data['phone'];
        }
        if (isset($data['transferred_date'])) {
            $order->transferred_date = strtotime($data['transferred_date']);
        }
        if (isset($data['mtime'])) {
            $order->mtime = strtotime($data['mtime']);
        }
        if (isset($data['note'])) {
            $order->note = $data['note'];
        }
        if (isset($data['deliver_status'])) {
            $order->deliver_status = $data['deliver_status'];
        }
        if (isset($data['deliver_date'])) {
            $order->deliver_date = $data['deliver_date'];
        }
        if (isset($data['payment_status'])) {
            $order->payment_status = $data['payment_status'];
        }
        if (isset($data['mall_description'])) {
            $order->mall_description = $data['mall_description'];
            $order->description = self::_convertMallDescription($order->mall_description);
        }

        if ($action = $data['action']) {
            if ($action == 'confirm') {
                $ret = $order->check_confirm_status();
            } elseif ($action == 'cancel') {
                $ret = $order->cancel();
            } elseif ($action == 'receive') {
                $ret = $order->receive();
            }
        }

        if (isset($data['items'])) {
            $exist_item = [];
            foreach ((array) $data['items'] as $item) {
                $order_item = O('order_item', ['order' => $order, 'product_id' => $item['id']]);

                if (!$order_item->id) {
                    continue;
                }

                if (isset($item['quantity'])) {
                    $order_item->quantity = (int) $item['quantity'];
                }
                if (isset($item['unit_price'])) {
                    $order_item->unit_price = $item['unit_price'];
                }
                if (isset($item['price'])) {
                    $order_item->price = $item['price'];
                }
                if (isset($item['deliver_status'])) {
                    $order_item->deliver_status = (int) $item['deliver_status'];
                }
                $order_item->save();

                $exist_item[] = $order_item->id;
            }

            // 商品不会增加，只会删除某个商品或者退货拆分，
            $new_items = $data['items'];
            $order_items = Q("order_item[order=$order]");
            $item_ids = $order_items->to_assoc('id', 'id');
            if ($order_items->total_count() != count($exist_item)) {
                foreach ($order_items as $item) {
                    if (!in_array($item->id, $exist_item)) {
                        $item->delete();
                    }
                }
            }

            $order->update_price();
        }

        if (isset($data['status'])) {
            $order->status = $data['status'];
            // 新的状态是STATUS_APPROVED
            // 需要判断是不是需要供应商审核？有待询价的商品，就需要供应商审核
            $has_inquiry = (bool) Q("order_item[order={$order}][price<0]")->total_count();
            if ($data['status'] == Order_Model::STATUS_APPROVED
                &&
                $has_inquiry
            ) {
                $order->status = STATUS_NEED_VENDOR_APPROVE;
            }
        }

        if ($ret = $order->save()) {
            if ($data['mall_description']) {
                $message = self::_convertMallDescription($data['mall_description']);
                Event::trigger('order_is_edited', $order, $message);
            } else {
                Event::trigger('order_is_edited', $order);
            }
        }

        return $ret;
    }

    private static function _convertMallDescription($description)
    {
        $title = $description['a'];
        $message = $description['d'];
        $message = "{$title}: {$message}";
        $message = \Michelf\Markdown::defaultTransform($message);
        $message = strip_tags($message);

        return $message;
    }

    public function getRevisionHashs($voucher)
    {
        if (!API_Mall::is_authenticated()) {
            return;
        }
        $order = O('order', ['voucher' => $voucher]);
        if (!$order->id) {
            return false;
        }
        $order = ORM_Model::refetch($order);

        return (array) $order->revision_hashs;
    }

    // 获取revision信息
    public function pullRevisions($voucher, $hashs)
    {
        if (!API_Mall::is_authenticated()) {
            return;
        }

        if (!$voucher || !count($hashs)) {
            return false;
        }

        foreach ($hashs as $hash) {
            $revision = O('order_revision', ['voucher' => $voucher, 'hash' => $hash]);
            if (!$revision->id) {
                return false;
            }

            $result[$hash] = [
                'vendor' => $revision->vendor->gapper_group,
                'customer' => $revision->customer->gapper_group,
                'requester' => $revision->requester->gapper_user,
                'request_date' => $revision->request_date ? date('Y-m-d H:i:s', $revision->request_date) : '0000-00-00 00:00:00',
                'transferred_date' => $revision->transferred_date ? date('Y-m-d H:i:s', $revision->transferred_date) : '0000-00-00 00:00:00',
                'invoice_title' => $revision->invoice_title,
                'address' => $revision->address,
                'phone' => $revision->phone,
                'postcode' => $revision->postcode,
                'email' => $revision->email,
                'note' => $revision->note,
                'voucher' => $revision->voucher,
                'price' => $revision->price,
                'status' => $revision->status,
                'deliver_status' => $revision->deliver_status,
                'deliver_date' => $revision->deliver_date ? date('Y-m-d H:i:s', $revision->deliver_date) : '0000-00-00 00:00:00',
                'payment_status' => $revision->payment_status,
                'ctime' => $revision->ctime ? date('Y-m-d H:i:s', $revision->ctime) : '0000-00-00 00:00:00',
                'hash_rand_key'=> $revision->hash_rand_key,
                'hash' => $revision->hash,
                'items' => json_decode($revision->items, true),
                'binded_inventory' => json_decode($revision->binded_inventory, true),
                'operator' => $revision->operator->gapper_user,
                'description' => $revision->description,
                'customer_confirmed' => $revision->customer_confirmed,
            ];
        }

        return $result;
    }

    // 添加一条revision
    public function pushRevisions($voucher, $revisions, $hashs)
    {
        if (!API_Mall::is_authenticated()) {
            return;
        }
        if (!$voucher || !count($revisions) || !count($hashs)) {
            return false;
        }
        $order = O('order', ['voucher' => $voucher]);
        $order = ORM_Model::refetch($order);
        $revision_hashs = $order->revision_hashs ?: [];

        foreach ($revisions as $d) {
            $revision_hashs[$d['hash']] = $d['ctime'];
        }

        $target_hashs = $hashs;
        ksort($revision_hashs);
        ksort($target_hashs);
        if ($revision_hashs !== $target_hashs) {
            return false;
        }
        $db = Database::factory();
        $db->begin_transaction();

        //保证order有id，revision需要保存order

        if (!$order->id) {
            $order = O('order');
            $order->voucher = $voucher;
            if (!$order->save()) {
                $db->rollback();

                return false;
            }
            $willCreateNewObject = true;
        }

        foreach ($revisions as $d) {
            // 如果商品的价格不一致，不允许生成远程订单
            // [可能是]由于在lab-orders生成订单时没有同步成功，然后，mall这边修改了商品的价格
            // 但是
            //  1. 待询价的商品(unit_price===-1)又比较特殊了, 是允许同步的
            //  2. 如果是退货，也就比较特殊了，也是允许同步的
            $tmp = (array) $d['items'];
            foreach ($tmp as $k => $v) {
                if ($willCreateNewObject) {
                    $tmpP = O('product', $v['id']);
                    if (!$tmpP->canBuy()) {
                        $db->rollback();

                        return false;
                    }
                    if ($tmpP->unit_price >= 0
                        &&
                        round($tmpP->unit_price, 2) != round($v['unit_price'], 2)
                        &&
                        !in_array($d['status'], [
                            Order_Model::STATUS_RETURNING,
                            Order_Model::STATUS_RETURNING_APPROVAL,
                        ])
                    ) {
                        $db->rollback();

                        return false;
                    }
                }
            }

            $rcustomer = O('customer', ['gapper_group' => $d['customer']]);
            if (!$rcustomer->id) {
                $rcustomer = Gapper::create_customer($d['customer']);
            }

            $ruser = O('user', ['gapper_user' => $d['requester']]);
            if (!$ruser->id) {
                $ruser = Gapper::create_user((int) $d['requester']);
            }

            if (!$rcustomer->has_member($ruser)) {
                $rcustomer->connect($ruser, 'member');
            }

            $revision = O('order_revision');
            $revision->vendor = O('vendor', ['gapper_group' => $d['vendor']]);
            $revision->customer = $rcustomer;
            $revision->requester = $ruser;
            $revision->request_date = max(strtotime($d['request_date']), 0);
            $revision->transferred_date = max(strtotime($d['transferred_date']), 0);
            $revision->invoice_title = $d['invoice_title'];
            $revision->address = $d['address'];
            $revision->phone = $d['phone'];
            $revision->postcode = $d['postcode'];
            $revision->email = $d['email'];
            $revision->note = $d['note'];
            $revision->voucher = $d['voucher'];
            $revision->price = $d['price'];
            $revision->status = $d['status'];
            $revision->binded_inventory = json_encode($d['binded_inventory']);
            $revision->deliver_status = $d['deliver_status'];
            $revision->deliver_date = max(strtotime($d['deliver_date']), 0);
            $revision->payment_status = $d['payment_status'];
            $revision->ctime = max(strtotime($d['ctime']), 0);
            $revision->hash_rand_key = $d['hash_rand_key'];
            $revision->hash = $d['hash'];
            $revision->items = json_encode($d['items']);
            $revision->operator = O('user', ['gapper_user' => $d['operator']]);
            $revision->description = $d['description'];
            $revision->customer_confirmed = $d['customer_confirmed'];
            $revision->order = $order;
            $revision->save();
            if (!$revision->id) {
                $db->rollback();

                return false;
            }
            $description = $d['mall_description'];
            if ($description) {
                $description = \Michelf\Markdown::defaultTransform($description);
                $description = strip_tags($description);
                admin::create_system_comment($order, $description, strtotime($data['ctime']));
            }
        }
        $order->revision_hashs = $hashs;
        $target_hashs = array_keys($hashs);

        //如果更新过来的revision在最末端，则更新order
        if (end($revisions)['hash'] == end($target_hashs)) {
            if (!self::_updateOrder($order, end($revisions))) {
                $db->rollback();

                return false;
            }
        } else {
            if (!$order->save()) {
                $db->rollback();

                return false;
            }
        }

        $db->commit();

        return true;
    }

    private static function _updateOrder($order, $data)
    {
        if (!$order->id) {
            return false;
        }
        $vendor = O('vendor', ['gapper_group' => $data['vendor']]);
        if (!$data['customer'] || !$data['requester']) {
            return false;
        }
        $customer = O('customer', ['gapper_group' => $data['customer']]);
        if (!$customer->id) {
            $customer = Gapper::create_customer($data['customer']);
        }

        $requester = O('user', ['gapper_user' => $data['requester']]);
        if ($customer->id && !$requester->id) {
            $requester = Gapper::create_user((int) $data['requester']);
        }
        if (!$customer->has_member($requester)) {
            $customer->connect($requester, 'member');
        }

        if ($data['operator']) {
            $operator = O('user', ['gapper_user' => $data['operator']]);
            if ($customer->id && !$operator->id) {
                $operator = Gapper::create_user((int) $data['operator']);
            }
            if (!$customer->has_member($operator)) {
                $customer->connect($operator, 'member');
            }
        } else {
            $operator = $customer->owner;
        }

        //customer不存在
        if (!$customer->id || !$vendor->id || !$requester->id) {
            return false;
        }

        $order->vendor = $vendor;
        $order->customer = $customer;
        $order->invoice_title = $data['invoice_title'];
        $order->address = $data['address'];
        $order->postcode = $data['postcode'];
        $order->email = $data['email'];
        $order->phone = $data['phone'];
        $order->purchaser = $requester;
        $order->purchase_date = max(strtotime($data['request_date']), 0);
        $order->transferred_date = max(strtotime($data['transferred_date']), 0);
        $order->mtime = max(strtotime($data['ctime']), 0);

        $order->description = $data['note'];
        $order->status = $data['status'];
        $order->deliver_status = $data['deliver_status'];
        $order->deliver_date = max(strtotime($data['deliver_date']), 0);
        $order->payment_status = $data['payment_status'];
        $order->binded_inventory = $data['binded_inventory'];
        $order->hash_rand_key = $data['hash_rand_key'];
        $order->hash = $data['hash'];
        $order->customer_confirmed = $data['customer_confirmed'];

        // by pihizi:
        //      如果不是申购中、待买方确认、已取消，则认为订单已经进入供应商可以处理的阶段
        if (!in_array($order->status, [Order_Model::STATUS_CANCELED, Order_Model::STATUS_REQUESTING, Order_Model::STATUS_NEED_CUSTOMER_APPROVE])) {
            $order->customer_approved = true;
        }

        $order->operator = $operator;

        if (!$order->id) {
            $order->ctime = max(strtotime($data['ctime']), 0);
            $order->voucher = $order->voucher;
            $order->save();
        }

        if (isset($data['items'])) {
            $exist_item = [];
            foreach ((array) $data['items'] as $item) {
                $order_item = O('order_item', ['order' => $order, 'product_id' => $item['id']]);

                if (!$order_item->id) {
                    $order_item->order = $order;
                    $pid = (int) $item['id'];
                    $order_item->product_id = $pid;
                    $product = O('product', $pid);
                    if (class_exists('Reagent_Type') && $product->rgt_type==Reagent_Type::EASYMADE_TOXIC && $order->label!=Order_Model::LABEL_DRUG_PRECURSOR) {
                        $order->label = Order_Model::LABEL_DRUG_PRECURSOR;
                    }
                }

                if (isset($item['quantity'])) {
                    $order_item->origin_quantity = $order_item->quantity = (int) $item['quantity'];
                }
                if (isset($item['unit_price'])) {
                    $order_item->unit_price = $item['unit_price'];
                }
                if (isset($item['price'])) {
                    $order_item->price = $item['price'];
                }
                if (isset($item['deliver_status'])) {
                    $order_item->deliver_status = (int) $item['deliver_status'];
                }

                $order_item->requester = $requester;
                $order_item->request_date = $order->purchase_date;
                $order_item->status = $order->status;
                $order_item->save();
                $exist_item[] = $order_item->id;
            }

            // 商品不会增加，只会删除某个商品或者退货拆分，
            $new_items = $data['items'];
            $order_items = Q("order_item[order=$order]");
            $item_ids = $order_items->to_assoc('id', 'id');
            if ($order_items->total_count() != count($exist_item)) {
                foreach ($order_items as $item) {
                    if (!in_array($item->id, $exist_item)) {
                        $item->delete();
                    }
                }
            }

            $order->update_price();
        }

        $order->compare_hash = $order->get_hash();
        $order->save();

        return $order;
    }

    public function getOrderRelease($voucher)
    {
        $order = O('order', ['voucher' => $voucher]);
        $revision = Q("order_revision[order={$order}]:sort(ctime D):limit(1)")->current();
        if (!$order->id) {
            return false;
        }
        $data = [
            'status' => [
                'value' => $order->status,
                'mtime' => self::_getModifyTime($order, $order->status, 'status'),
            ],
            'deliver_status' => [
                'value' => $order->deliver_status,
                'mtime' => self::_getModifyTime($order, $order->deliver_status, 'deliver_status'),
            ],
            'payment_status' => [
                'value' => $order->payment_status,
                'mtime' => self::_getModifyTime($order, $order->payment_status, 'payment_status'),
            ],
            'items' => [
                'value' => json_decode($revision->items, true),
                'mtime' => self::_getModifyTime($order, $revision->items, 'items'),
            ],
        ];

        return $data;
    }

    private function _getModifyTime($order, $value, $field = '', $is_object_field = false)
    {
        $revisions = Q("order_revision[order={$order}]:sort(ctime D)");
        $ov = $value;
        foreach ($revisions as $revision) {
            $rv = $is_object_field ? $revision->$field->id : $revision->$field;
            if ($rv !== $ov) {
                break;
            }
            $modify_time = $revision->ctime;
        }

        return $modify_time ?: 0;
    }

    /**
     * @brief 返回带升级的订单信息
     *
     * @param $start
     * @param $limit
     *
     * @return
     */
    public function fetchAll($group_id, $start = 0, $limit = 10)
    {
        if (!API_Mall::is_authenticated()) {
            return;
        }
        $data = [];
        $group_id = (int) $group_id;
        $orders = Q("customer[gapper_group={$group_id}] order")->limit($start, $limit);
        foreach ($orders as $order) {
            $items = Q("order_item[order=$order]:sort(product_id)");
            $items_data = [];
            foreach ($items as $item) {
                $product = $item->product;
                $product_revision = O('product_revision', ['product' => $product, 'version' => $item->version]);

                $items_data[] = [
                    'product_id' => $product->id,
                    'version' => $product_revision->version ?: $product->version,
                    'name' => $product_revision->name ?: $product->name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => round($item->unit_price, 2),
                    'price' => round($item->unit_price * $item->quantity, 2),
                    'origin_quantity' => $item->origin_quantity ?: $item->quantity,
                    'deliver_status' => $item->deliver_status ?: $order->deliver_status,
                    'manufacturer' => $product_revision->manufacturer ?: $product->manufacturer,
                    'catalog_no' => $product_revision->catalog_no ?: $product->catalog_no,
                    'package' => $product_revision->package ?: $product->package,
                ];
            }
            $data[$order->id] = [
                'parent' => $order->parent->id,
                'vendor_id' => $order->vendor->gapper_group,
                'gapper_group' => $order->customer->gapper_group,
                'purchase_date' => $order->purchase_date ?: $order->ctime,
                'purchaser_id' => $order->purchaser->gapper_user,
                'purchaser_name' => $order->purchaser->name,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'transferred_date' => $order->transferred_date,
                'description' => $order->description,
                'ctime' => $order->ctime,
                'mtime' => $order->mtime,
                'invoice_title' => $order->invoice_title,
                'address' => $order->address,
                'phone' => $order->phone,
                'email' => $order->email,
                'postcode' => $order->postcode,
                'grant' => $order->grant->id,
                'cancel_reason' => $order->cancel_reason,
                'price' => $order->price,
                'cannot_cancel' => $order->cannot_cancel,
                'deliver_status' => $order->deliver_status,
                'order_no' => $order->order_no,
                'hash_rand_key'=> $order->hash_rand_key,
                'hash' => $order->hash,
                'voucher' => $order->voucher ?: $order->order_no ?: '',
                'operator_id' => $order->operator->gapper_user,
                'revision_hashs' => $orer->revision_hashs,
                'customer_approved' => $order->customer_approved,
                'customer_confirmed' => $order->customer_confirmed,
                'items' => $items_data,
                '_extra' => $order->_extra,
            ];
        }

        return $data;
    }

    // 获取某期易制毒的订单
    public function getDrugPrecursorProducts($customerGapperGroup, $chronology)
    {
        if (empty($chronology)) {
            return [];
        }
        $customer = O('customer', ['gapper_group' => $customerGapperGroup]);
        if (!$customer->id) {
            $customer_group = O('customer_group', ['gapper_group' => $customerGapperGroup]);
            $customer = $customer_group->customer;
        }
        if (!$customer->id) {
            return [];
        }
        $label = Order_Model::LABEL_DRUG_PRECURSOR;
        $canceledStatus = Order_Model::STATUS_CANCELED;
        $data = [];
        if (count($chronology) % 2 == 1) {
            $chronology[] = date('Y-m-d H:i:s');
        }
        while (!empty($chronology)) {
            $stime = strtotime(array_shift($chronology));
            $etime = strtotime(array_shift($chronology));
            $sql = "order[customer=$customer][ctime>$stime][ctime<$etime][label=$label][status!=$canceledStatus]";
            $orders = Q($sql);
            foreach ($orders as $order) {
                $items = Q("order_item[order=$order]");
                foreach ($items as $item) {
                    $product = $item->product();
                    $data[$product->id]['name'] = $product->name;
                    $data[$product->id]['cas_no'] = $product->cas_no;
                    $data[$product->id]['quantity'] += $item->quantity;
                    $data[$product->id]['version'] = $product->version;
                    $data[$product->id]['package'] = $product->package;
                    $data[$product->id]['spec'] = $product->spec;
                    $data[$product->id]['vendor_id'] = $order->vendor->id;
                    $data[$product->id]['vendor_name'] = $order->vendor->name;
                    $data[$product->id]['orders'][] = $order->voucher;
                }
            }
        }

        return $data;
    }
}
