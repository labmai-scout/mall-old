<?php

class API_Order {

    //RPC 认证函数
    function auth($encrypted_by_server_pubkey, $signed_by_client_privkey, $client_name) {

        if (!$encrypted_by_server_pubkey || !$signed_by_client_privkey || !$client_name) return FALSE;

        $SSL = new OpenSSL();

        //发送过来已经用现在服务器公钥加密了的数据
        $encrypted_by_pubkey =  @base64_decode($encrypted_by_server_pubkey);

        //发送过来用原有服务器私钥签名了的数据
        $signed_by_privkey = @base64_decode($signed_by_client_privkey);

        $clients = (array) Config::get('order.clients');

        //本地的私钥匙
        $local_privkey = Config::get('rpc.private_key');

        if (!isset($clients[$client_name])) {
            return FALSE;
        }

        //远程发送来进行分析的服务器的公钥,通过服务器获取
        $client = $clients[$client_name];
        $client_pubkey = $client['public_key'];

        $decrypted_pubkey_code = $SSL->decrypt($encrypted_by_pubkey, $local_privkey, 'private');


        if (!$SSL->verify($decrypted_pubkey_code, $signed_by_privkey, $client_pubkey)) {
            return FALSE;
        }

        $_SESSION['client_name'] = $client_name;

        //返回用自己私钥签名的数据
        return @base64_encode($SSL->sign($decrypted_pubkey_code, $local_privkey));
    }


    /*
     *    requester     申请人一卡通卡号
     *    buy_no        采购编码 //此采购编码不不会保存到mall系统中
     *    product       试剂品名
     *    vendor        供应商
     *    catalog_no    目录号
     *    package       包装
     *    quantity      数量
     *    price         总价
     */
    function create_order($data) {
        if (! isset($_SESSION['client_name'])) return FALSE;

        try {

            $user = O('user', array(
                'token'=> $data['requester']
            ));

            //user不存在
            if (!$user->id) throw new Exception;

            $customer = Q("customer[owner={$user}]")->current();

            //customer不存在
            if (!$customer->id) throw new Exception;

            $vendor = O('vendor', array(
                'name'=> $data['vendor']
            ));

            //vendor不存在
            if (!$vendor->id) throw new Exception;

            $product = O('product', array(
                'name'=> $data['product']
            ));

            //判断是否有此类商品
            if (!$product->id) throw new Exception;

            //该vendor是否销售该product
            $product = O('product', array(
                'vendor'=> $vendor,
                'product'=> $product,
                'catalog_no'=> $data['catalog_no'],
                'package'=> $data['package']
            ));

            if (!$product->id) throw new Exception;

            $order = O('order');
            $order->vendor = $vendor;
            $order->customer = $customer;
            $order->purchaser = $user;
            $order->price = $data['price'];
            $order->product = $product;

            $order->save();

            if (!$order->id) throw new Exception;

            $item = O('order_item');

            //数量
            $item->origin_quantity = $item->quantity = $data['quantity'];

            //单价
            $item->unit_price = round($data['price'] / $data['quantity'], 2);

            //总价
            $item->price = $data['price'];

            //日期
            $item->request_date = Date::time();

            //人员
            $item->requester = $user;

            //order
            $item->order = $order;

            $item->save();

            return $order->id;
        }
        catch(Exception $e) {
            return NULL;
        }
    }

    function get_activity($search_options, $fetch_keys) {
        $selector = 'order_activity';
        $pre_selector = '';

        if (isset($search_options['aid'])) {
            $selector .= "[id>={$search_options['aid']}]";
        }
        if (isset($search_options['time'])) {
            $selector .= "[time>={$search_options['time']}]";
        }
        if (isset($search_options['status'])) {
             $selector .= "[status={$search_options['status']}]";
        }
        $activity_selector = $selector.":sort(id ASC)";
        $order_activity = Q($activity_selector)->limit(1)->current();
        if (isset($search_options['customer_uuid'])) {
             $customer = Q("customer[uuid={$search_options['customer_uuid']}]:limit(1)")->current();
        }
        $this->_verify($customer);
        if ($customer->id) {
            $selector .= ":sort(id ASC) order[customer={$customer}]:sort(id ASC)";
        }
        else {
            $selector .= ':sort(id ASC) order:sort(id ASC)';
        }
        $order = Q($selector)->limit(1)->current();
        $arr = array();
        if ($order->id) {
            $next_order_activity = Q("order[customer={$customer}] order_activity[id>{$search_options['aid']}]:sort(id ASC):limit(1)")->current();
            $arr['naid'] = $next_order_activity->id;
            $arr['atime'] = $order_activity->time;
            $fetch_keys = array_flip($fetch_keys);

            if (array_key_exists('grant_no', $fetch_keys)) {

                $statement = Q("$order transfer_statement")->current();
                if ($statement->id) {
                    $arr['grant_no'] = strtr('%bmbh - %xmbh', array(
                        '%bmbh'=> $statement->bmbh,
                        '%xmbh'=> $statement->xmbh
                    ));
                }
            }

            if (array_key_exists('order_id', $fetch_keys)) {

                $arr['order_id'] = $order->id;
            }

            if (array_key_exists('aid', $fetch_keys)) {
                $arr['aid'] = $order_activity->id;
            }

            if (array_key_exists('purchaser', $fetch_keys)) {
                $arr['purchaser'] = array(
                    'id'=>$order->purchaser->id,
                    'name'=>$order->purchaser->name,
                    );
            }

            if (array_key_exists('purchase_date', $fetch_keys)) {
                $arr['purchase_date'] = $order->purchase_date;
            }

            if (array_key_exists('description', $fetch_keys)) {
                $arr['description'] = $order->description;
            }

            if (array_key_exists('status', $fetch_keys)) {
                $arr['status'] = $order->status;
            }

            if (array_key_exists('deliver_status', $fetch_keys)) {
                $arr['deliver_status'] = $order->deliver_status;
            }

            if (array_key_exists('address', $fetch_keys)) {
                $arr['address'] = $order->address;
            }

            if (array_key_exists('postcode', $fetch_keys)) {
                $arr['postcode'] = $order->postcode;
            }

            if (array_key_exists('email', $fetch_keys)) {
                $arr['email'] = $order->email;
            }

            if (array_key_exists('phone', $fetch_keys)) {
                $arr['phone'] = $order->phone;
            }

            if (array_key_exists('tags', $fetch_keys)) {
                $arr['tags'] = $order->lims_tags;
            }

            if (array_key_exists('items', $fetch_keys)) {
                $item_arr = array();
                $items = Q("order_item[order=$order]");
                foreach ($items as $item) {
                    $id = $item->product->id;
                    $item_arr[$id]['product_name'] = $item->product->name;
                    $item_arr[$id]['item_id'] = $item->id;
                    $item_arr[$id]['manufacturer'] = $item->product->manufacturer;
                    $item_arr[$id]['catalog_no'] = $item->product->catalog_no;
                    $item_arr[$id]['model'] = $item->product->model;
                    $item_arr[$id]['spec'] = $item->product->spec;
                    $item_arr[$id]['vendor'] = $item->product->vendor->name;
                    $item_arr[$id]['quantity'] = $item->quantity;
                    $item_arr[$id]['unit_price'] = $item->unit_price;
                    $item_arr[$id]['price'] = $item->price;
                    // $keywords = @json_decode($item->product->keywords, TRUE);
                    //array_filter用于过滤空value的keywords, 避免出现错误标签传递
                    // $item_arr[$id]['keywords'] = array_filter(explode(',', $keywords['scalar']));
                    $item_arr[$id]['link'] = $item->order->url();
                    $item_arr[$id]['request_date'] = $item->request_date;
                    $item_arr[$id]['receive_date'] = $item->receive_date;
                    $item_arr[$id]['requester'] = array(
                        'id'=>$item->requester->id,
                        'name'=>$item->requester->name,
                    );
                    $item_arr[$id]['receiver'] = array(
                        'id'=>$item->receiver->id,
                        'name'=>$item->receiver->name,
                    );
                    $item_arr[$id]['deliver_status'] = $item->deliver_status;
                    $item_arr[$id]['request_confirm'] = $item->request_confirm;
                    $item_arr[$id]['customer_confirm'] = $item->customer_confirm;

                }
                $arr['items'] = $item_arr;
            }
            return $arr;
        }
        else {
            return NULL;
        }
    }

    const STATUS_DELETE_ORDER_SUCCESS = 1;
    const STATUS_DELETE_ORDER_FALSE = 0;

    function delete_order($order_id) {

        $order = O('order', $order_id);

        if(!$order->id) return self::STATUS_DELETE_ORDER_FALSE;

        $this->_verify($order->customer);

        //删除订单，同时删除订单对应的item
        $item = O('order_item', array('order'=>$order));
        if($order->delete() && $item->delete()) {
            return self::STATUS_DELETE_ORDER_SUCCESS;
        }
    }

    const STATUS_UPDATE_ORDER_SUCCESS = 1;
    const STATUS_UPDATE_ORDER_FALSE = 0;

    private function _verify($customer=NULL) {
        if (isset($_SESSION['client_name'])) return;
        if (isset($_SESSION['current_customer'])) {
            $id = is_object($customer) ? $customer->id : $customer;
            if ($id == $_SESSION['current_customer']) return;
        }

        throw new API_Exception(T('access denied!'));
    }

    function update_order($order_id, $items) {

        $order = O('order', $order_id);
        if(!$order->id) return self::STATUS_UPDATE_ORDER_FALSE;

        $this->_verify($order->customer);

        $update_array = array('price','description','phone','address','email');

        foreach ($update_array as $value) {
            if($items[$value]) {
                $order->$value = $items[$value];
            }
        }

        if($order->save()){
            return self::STATUS_UPDATE_ORDER_SUCCESS;
        }

    }

    function update_item($order_item_id, $data) {

        $order_item = O('order_item', $order_item_id);
        $order = $order_item->order;
        $order_items = Q("order_item[order={$order}]");
        if (!$order_item->id) throw new API_Exception(T('order不存在!'));
        $this->_verify($order_item->order->customer);
        $ret = FALSE;
        $user = O('user', $data['user_id']);
        //如果没有user则用传过来的user_name
        if(!$user->id) {
            $user->name = $data['user_name'];
        }
        Cache::L('ME', $user);

        if (isset($data['cancel'])) {
            if ($order->customer_can_cancel()) {
                return $order->cancel($data['cancel_note']);
            }
            else {
                return FALSE;
            }
        }

        if ($data['request_confirm']
            && $order->status == Order_Model::STATUS_REQUESTING) {
            $order_item->request_confirm = TRUE;
            $ret = $order_item->save();

            $order_confirm = TRUE;
            foreach ($order_items as $item) {
                if(!$item->request_confirm) {
                    $order_confirm = FALSE;
                }
            }

            //item都确认了 则订单进入待再次确认
            if($order_confirm) {
                $order->status = Order_Model::STATUS_NEED_VENDOR_APPROVE;
                $ret = $order->save();
            }

            return $ret;
        }

        if ($data['customer_confirm']
            && $order->status == Order_Model::STATUS_NEED_CUSTOMER_APPROVE) {
            $order_item->customer_confirm = TRUE;
            $ret = $order_item->save();

            $order_confirm = TRUE;
            foreach ($order_items as $item) {
                if(!$item->customer_confirm) {
                    $order_confirm = FALSE;
                }
            }
            //item都确认了 则订单确认
            if($order_confirm) {
                $order->customer_confirm($user);
            }
            return $ret;
        }

        if (isset($data['quantity'])) {
            $order_item->quantity = $data['quantity'];
            $order_item->price = $data['quantity'] * $order_item->unit_price;
        }
        if (isset($data['receive_date'])) $order_item->receive_date = $data['receive_date'];
        if (isset($data['receiver_id'])) {
            $receiver = O('user', $data['receiver_id']);
            if ($receiver->id) {
                $order_item->receiver_id = $data['receiver_id'];
                Cache::L('ME', $receiver);
            }
        }
        if (isset($data['status'])) $order_item->status = $data['status'];
        if (isset($data['deliver_status'])) $order_item->deliver_status = $data['deliver_status'];
        if (isset($data['lims_oid'])) $order_item->lims_oid = $data['lims_oid'];


        if ($order_item->save()) {
            $order_item->order->update_price()->save();
            $ret = TRUE;
        }

        //当所有item都到货了，则更新订单的状态
        $change_order_status = TRUE;
        foreach ($order_items as $key => $item) {
            if ($item->status != $order_item->status && $item->id != $order_item->id) {
                $change_order_status = FALSE;
            }
        }
        if ($change_order_status) {
            $order = $order_item->order;
            $order->status = $order_item->status;
            $order->save();
        }

        return $ret;
    }

    function create($data) {
        try{
            $auto_comfirm = TRUE;
            //提前连接一下mysql和sphinx，如果链接错误会报错
            $mysql = Database::factory();
            $sphinx = Database::factory('@sphinx');

            $ids = array();
            $requester = $data['requester']; // lims:uuid:uid
            $arr = explode(':', $requester);

            if ($arr[0] == 'lims') {
                $customer = O('customer', ['uuid'=>$arr[1]]);
                $this->_verify($customer);
                $uid = $arr[2];
                $user = O('user', $uid);
                if (!$user->id) {
                    //增加标志位，如果是默认负责人购买的话，不可跳过买方确认
                    $auto_comfirm = FALSE;
                    $user = $customer->owner;
                    $user->name = $data['requester_name'];
                }
            }
            else {
                $user = O('user', array('token'=>$requester));
                if (!$user->id) throw new API_Exception(T('requester不存在!'));
                $customer = Q("$user<owner customer")->current();
                $this->_verify($customer);
            }

            $description = $data['description'];
            $arr = array();

            foreach ($data['items'] as $item) {
                if ($item['id']) {
                    $order_item = O('order_item' , $item['id']);
                    $product = $order_item->product;
                    if (!$product->id) continue;
                    $vendor = $product->vendor;
                }
                else {
                    $vendor_name = $item['vendor'];
                    $vendor = Q("vendor[name={$vendor_name}]")->current();
                    $name = $item['product'];
                    $catalog_no = $item['catalog_no'];
                    $package = $item['package'];
                    $product = O('product',[
                            'vendor'=>$vendor,
                            'name'=>$name,
                            'catalog_no'=>$catalog_no,
                            'package'=>$package,
                            ]);

                    if (!$product->id) continue;
                }

                if (!$vendor->id) continue;

                //不允许购买，continue
                if (!$product->canBuy()) continue;

                $order = $orders[$vendor->id];
                $now = Date::time();
                if (!$order) {
                    $order = O('order');
                    $order->address = $data['receive_address'];
                    $order->postcode = $data['receive_postcode'];
                    $order->email = $data['receive_email'];
                    $order->phone = $data['receive_phone'];
                    $order->lims_tags = $data['tags'];
                    $order->lims_incharges = $data['incharges'];
                    $order->vendor = $vendor;
                    $order->customer = $customer;
                    $order->purchaser = $user;
                    $order->description = $description;
                    $order->ctime = $now;
                    $order->purchase_date = $now;
                    $order->status = Order_Model::STATUS_REQUESTING;
                    $order->requester_name = $user->name;
                    $order->purchaser_name = $user->name;

                    if ($order->save()) {
                        if ($auto_comfirm && $user->is_allowed_to('以买方确认', $order)) {
                            $order->customer_confirmed = TRUE;
                            $order->status = Order_Model::STATUS_NEED_VENDOR_APPROVE;
                            $order->save();
                        }
                    }
                    else{
                        continue;
                    }

                    Cache::L('ME', $user);
                    $orders[$vendor->id] = $order;
                }

                $order_item = O('order_item');
                $order_item->order = $order;
                $order_item->requester = $user;
                $order_item->request_date = $now;
                $order_item->product = $product;
                $order_item->origin_quantity = $order_item->quantity = $item['quantity'];
                $order_item->unit_price = $product->unit_price;
                $order_item->price = $product->unit_price * $item['quantity'];
                $order_item->status = $order->status;
                $order_item->ctime = Date::time();

                $order_item->save();
            }

            $ids = [];
            foreach ($orders as $order) {
                $order->update_price()->save();
                Event::trigger('order_is_drafted', $order);
                $ids[] = $order->id;
            }

            return $ids;
        }
        catch(Exception $e) {
            //删除订单和相应的item
            if ($order->id) {
                Q("order_item[order={$order}]")->delete_all();
                $order->delete();
            }

            throw new Exception;
        }
    }

    /**
    *记录操作过程
    *
    */
    function log_operation($user,$status,$order){

        O('operation_time')->log_status($user,$status,$order);

    }

}
