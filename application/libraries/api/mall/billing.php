<?php

class API_Mall_Billing_Exception extends API_Exception {}

class API_Mall_Billing {
    public function createBilling($params)
    {
        if (!API_Mall::is_authenticated()) {
            return;
        }
        $vid                        = $params['vid'];
        $payment_voucher            = $params['payment_voucher'];
        $vouchers                   = $params['vouchers'];
        $statement                  = O('billing_statement');
        $vendor                     = O('vendor', ['gapper_group'=>$vid]);
        $statement->account_method  = Config::get('account.default_account', NULL);
        $statement->payment_voucher = $payment_voucher;
        $statement->vendor = $vendor;
        $statement->save();
        $balance = 0;
        $n = 0;
        foreach ($vouchers as $voucher) {
            $order = O('order', ['voucher'=>$voucher]);
            $statement_count = Q("$order billing_statement")->total_count();
            if($statement_count) {
                continue;
            }
            $n++;
            $statement->connect($order);
            $balance += $order->price;
        }
        if ($n > 0) {
            $statement->balance = $balance;
            return $statement->save();
        }
        else {
            $statement->delete();
            return false;
        }
    }

    public function getBilling($criteria)
    {
        if ($id = $criteria['id']) {
            $statement = O('billing_statement', $id);
        }
        else if ($payment_voucher = $criteria['payment_voucher']) {
            $statement = O('billing_statement', ['payment_voucher'=>$payment_voucher]);
        }
        $result = [];
        if ($statement->id) {
            $result = [
                'id' => $statement->id,
                'vendor' => $statement->vendor->gapper_group,
                'balance' => $statement->balance,
                'approver' => $statement->approver->gapper_user,
                'approve_date' => $statement->approve_date,
                'status' => $statement->status,
                'ctime' => $statement->ctime,
                'reserv_no' => $statement->reserv_no,
                'payment_voucher' => $statement->payment_voucher,
                'vendor_note' => $statement->vendor_note,
                'voucher_no' => $statement->voucher_no,
            ];
        }
        return $result;

    }

    public function updateBilling($params)
    {
        if ($id = $params['id']) {
            $statement = O('billing_statement', $id);
        }
        else if ($payment_voucher = $params['payment_voucher']) {
            $statement = O('billing_statement', ['payment_voucher'=>$payment_voucher]);
        }
        $vendor_id = $params['vendor_id'];
        $balance   = $params['balance'];
        $status = $params['status'];
        $voucher_no = $params['voucher_no'];
        if ($vendor_id) {
            $vendor = O('vendor', ['gapper_group'=>$vendor_id]);
            $statement->voucher = $vendor;
        }
        if ($balance) {
            $statement->balance = $balance;
        }
        if ($voucher_no) {
            $statement->voucher_no = $voucher_no;
        }
        switch ($status) {
            case 'reject':
                if ($statement->canReject()) {
                    $statement->reject();
                }
                break;
            case 'approve':
                if ($statement->canApprove()) {
                    $statement->approve();
                }
                break;
            case 'delete':
                $statement->delete();
                break;
            case 'close':
                if ($statement->can_close()) {
                    $statement->close();
                }
                break;
        }
        return $statement->save();
    }
}