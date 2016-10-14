<?php

class API_Mall_Payment_Exception extends API_Exception {}

class API_Mall_Payment {

    /**
     * 得到付款单号
     * @return voucher
     */
    public function getVoucher() {
        if (!API_Mall::is_authenticated()) return;
       return Transfer_Statement_Model::generate_voucher();
    }

    /**
     * pushStatement 推送付款单
     * @param  string $voucher   付款单voucher
     * @param  array  $data 付款单数据
     * @throws exception   404: 数据不存在!
     * @throws exception   401: 数据错误!
     * @return $id    付款单id
     */
    public function pushStatement($voucher='', array $data = array()) {
        if(!API_Mall::is_authenticated()) return;

        try {
            if (!$voucher || !$data['gapper_group']) throw new API_Mall_Payment_Exception("data error", 404);

            $customer = O('customer', ['gapper_group'=>$data['gapper_group']]);
            if (!$customer->id) throw new API_Mall_Payment_Exception("data error", 404);

            $db = Order_Model::db('order');
            $db->begin_transaction();

            $statement = O('transfer_statement', ['voucher'=>$voucher]);

            if (!$statement->id) {
                $statement->voucher = $voucher;
                $statement->customer = $customer;
            }

            if (isset($data['ctime'])) {
                $statement->ctime = $data['ctime'] > 0 ? $data['ctime'] : 0;
            }
            if (isset($data['mtime'])) {
                $statement->mtime = $data['mtime'] > 0 ? $data['mtime'] : 0;
            }
            if (isset($data['transferred_date'])) {
                $statement->transferred_date = $data['transferred_date'] > 0 ? $data['transferred_date'] : 0;
            }
            if (isset($data['balance'])) {
                $statement->balance = round($data['balance'], 2);
            }
            if (isset($data['grant_no'])) {
                $statement->grant_no = $data['grant_no'];
            }
            if (isset($data['pdata'])) {
                $statement->pdata = $data['pdata'];
                Event::trigger('api[payment:pushStatement].data', $statement, $data['pdata']);
                $statement->reserv_no = $data['pdata']['reserv_no']?:'';
            }
            if (isset($data['status']) && in_array($data['status'], array_keys(Transfer_Statement_Model::$status))) {
                $statement->status = $data['status'];
            }

            if (!$statement->save()) {
                throw new API_Mall_Payment_Exception("statement save error");
            }

            if (isset($data['order_vouchers'])) {
                foreach ($data['order_vouchers'] as $voucher) {
                    $order = O('order', ['voucher'=>$voucher]);

                    if (!$order->id) {
                        //传过来的订单不存在，数据不对
                        throw new API_Mall_Payment_Exception("order data error", 401);
                    }

                    $statement->connect($order);
                }
            }

            $db->commit();
            $result['id'] = $statement->id;
            return $result;
        }
        catch(Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * pullStatement 得到订单信息
     * @param  string $voucher 订单voucher
     * @return array 订单数据 或 false
     */
    public function pullStatement($voucher='') {
        if(!API_Mall::is_authenticated()) return;

        $statement = O('transfer_statement', ['voucher'=>$voucher]);
        if (!$statement->id) return false;

        $data = [
            'transferred_date' => $statement->transferred_date,
            'ctime' => $statement->ctime,
            'mtime' => $statement->mtime,
            'balance' => $statement->balance,
            'status' => $statement->status,
            'voucher' => $statement->voucher,
            'fail_reason' => $statement->fail_reason,
        ];

        return $data;
    }

    public function fetchAll($gapper_group, $start, $limit) {
        if(!API_Mall::is_authenticated()) return;
        $customer = O('customer', ['gapper_group' => $gapper_group,]);
        if (!$customer->id) return false;
        $infos = [];
        $status = Transfer_Statement_Model::STATUS_CANCEL;
        $statements = Q("transfer_statement[customer={$customer}][status!={$status}]")->limit($start, $limit);
        foreach ($statements as $statement) {
            $orders = Q("$statement order");
            $odata  = [];
            foreach ($orders as $order) {
                $odata[] = ['voucher' => $order->voucher];
            }
            $voucher = $statement->voucher;
            if (stripos($voucher, 'PM') !== 0) {
                $voucher = Number::fill($statement->id, 6);
            }

            $infos[] = [
                'voucher' => $voucher,
                'id' => $statement->id,
                'mtime'   => $statement->mtime,
                'ctime'   => $statement->ctime,
                'status'  => $statement->status,
                'fail_reason' => $statement->fail_reason,
                'reserv_no' => $statement->reserv_no,
                'balance' => $statement->balance,
                'bmbh' => $statement->bmbh,
                'xmbh' => $statement->xmbh,
                'transferred_date' => $statement->transferred_date,
                'approve_date' => $statement->approve_date,
                'approver' => $statement->approver,
                'serial_no' => $this->_filter_lsh($statement->id),
                'orders'  => $odata,
            ];
        }
        return $infos;
    }

    function _filter_lsh($lsh) {
        $prefix = Config::get('payment.lsh_prefix');

        if (!is_array($lsh))  {
            if (is_numeric($lsh)) {
                return $prefix. str_pad($lsh, 9, 0, STR_PAD_LEFT);
            }
            else {
                if (substr($lsh, 0, 2) == $prefix && strlen($lsh) == 11) {
                    return $lsh;
                }
                else {
                    return FALSE;
                }
            }

        }
        else {
            return FALSE;
        }
    }

}
