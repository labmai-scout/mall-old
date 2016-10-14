#!/usr/bin/env php
<?php
/*
 * file tiancai_update_transfer_statement_status.php
 * author Rui Ma <rui.ma@geneegroup.com>
 * date 2012-08-22
 *
 * brief 定时脚本，用于对付款单进行状态查询
 * usage SITE_ID=nankai php tiancai_update_transfer_statement_status.php
 */

require_once('base.php');

//获取未付款状态
$pending_status = Transfer_Statement_Model::STATUS_PENDING_TRANSFER;

//获取所有未付款的付款单
$transfer_statements = Q("transfer_statement[status={$pending_status}]");

foreach ($transfer_statements as $statement) {
	if (count($statement->pdata)) continue;
    $data = $statement->get_pay_status();
    //data为返回数据合集
	if (!$data) continue;

    $return = current($data);

    if (count($data) > 1) {
        $return['JE'] = 0;
        foreach($data as $d) {
            //金额总和
            $return['JE'] += $d['JE'];
        }
    }

	if (!$return) {
		/*
		暂不处理
		$statement->reset();
		$log = sprintf('定时脚本中检查到付款单 #%d 支付异常未提交至天财支付平台, 调用 statement 的 reset()',
						$statement->id);
		Log::add($log, 'transfer');
		*/
    }
	elseif ($return['ZT'] == Payment_Tiancai::PAY_STATUS_SUCCESSED) {
		//如果存在transfer_statement，并且金额与系统中金额相同，则进行付款，并保存相关信息操作
		if  ($statement->id && $return['JE'] == round($statement->balance, 2)) {

			//修改付款单状态为已付款
			$statement->success();

			$log = sprintf('定时脚本中检查到付款单 #%d 已支付成功, 调用 statement 的 success()',
						   $statement->id);
			Log::add($log, 'transfer');

		}
	}
	elseif (
		$return['ZT'] == Payment_Tiancai::PAY_STATUS_FAILED ||
		$return['ZT'] == Payment_Tiancai::PAY_STATUS_APPROVAL_FAILED) {
		//如果返回状态为支付失败，那么修正状态为支付失败
		$statement->fail(Config::get('payment.default_fail_message', '支付失败!'));

		$log = sprintf('定时脚本中检查到付款单 #%d 支付失败, 调用 statement 的 fail()',
					   $statement->id);
		Log::add($log, 'transfer');
	}
    elseif (
        $return['ZT'] == Payment_Tiancai::PAY_STATUS_APPROVAL_OUT ||
        $return['ZT'] == Payment_Tiancai::PAY_STATUS_APPROVAL_SUCCESSED
        ) {
        //预约单号
        $reserv_no = $return['YYDH'];
        $statement->reserv_no = $reserv_no;

	//对部门编号、项目编号进行保存
	$statement->bmbh = $return['BMBH'];
	$statement->xmbh = $return['XMBH'];

        Log::add(strtr('付款单[%statement_id]设定预约单号%reserv_no', array(
            '%statement_id'=> $statement->id,
            '%reserv_no'=> $reserv_no
        )), 'transfer');

        $statement->save();
    }
    elseif (!$statement->bmbh) {
	$statement->bmbh = $return['BMBH'];
	$statement->xmbh = $return['XMBH'];
	$statement->save();
	clean_cache($statement);
    }
}
