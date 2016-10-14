<?php

class Operation_Time_Model extends ORM_Model {
       
    const STATUS_REQUESTING_START = 0;    //申购订单  lab-order 申购增加
    
    const STATUS_VENDOR_APPROVE = 1;    //供应确认订单  mall-old
    
    const STATUS_CUSTOMER_APPROVE = 2;    //买方确认订单 lab-order 开始付款流程
    
    const STATUS_SEND = 3;    //订单发货  mall-old
    
    const STATUS_CANEL = 4;    //订单取消   lab-order 开始付款流程
    
    const STATUS_TAKE = 5;    //收货  lab-order 确认收货
    
    const STATUS_PAY = 6;    //开始付款  lab-order 开始付款流程
    
    const STATUS_PAY_SUCCESS = 7;    //付款成功
    
    const STATUS_PAY_FAIL = 8;    //付款失败
    
    const STATUS_RETURN_FEW = 9;    //申请个别退货   lab-order 开始付款流程
    
    const STATUS_RETURN_ALL = 10;    //申请全部退货  lab-order 开始付款流程
    
    const STATUS_RETURN_AGREE = 11;    //同意个别退货  mall-old
    
    const STATUS_RETURN_REJECT = 12;    //拒绝退货  mall-old

    function log_status($user,$status,$order){

        if($user==null)return ;
        if($status==null)return ;
        if($order==null)return ;

        $this->ctime=time();
        $this->op_user=$user;
        $this->status=$status;
        $this->order=$order;
        $this->save();


    }

/*

申购人
老师
买方
供应商


-------------------------

申购订单
审批订单
确认订单
订单发货
订单取消
收货
开始付款
付款成功
付款失败
申请个别退货
申请全部退货
同意个别退货
同意全部退货
拒绝退货


-------------------------

1.申购人申购订单，时间
2.老师审批订单，时间（老师申购订单直接确认，时间）
3.供应商确认订单，时间
4.老师二次确认时间（如果没有，空格不计，记录最后一次时间）
5.供应商二次确认时间（如果没有，空格不计，记录最后一次时间）
6.供应商订单发货，时间
7.买方订单取消，时间（如果没有，空格不计）
8.卖方订单取消，时间（如果没有，空格不计）
9.老师收货，时间
10.老师付款，时间
11.付款失败，时间（如果没有，空格不计）
12.付款成功，时间
13.申请个别退货，时间
14.申请全部退货，时间
15.供应商同意个别退货，时间
16.供应商同意全部退货，时间
17.供应商拒绝退货，时间
18.管理方审批同意退货，时间
19.管理方拒绝退货，时间
*/





}