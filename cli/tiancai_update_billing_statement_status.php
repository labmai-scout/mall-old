#!/usr/bin/env php
<?php
/*
 * file tiancai_update_billing_statement_status.php
 * author Jinlin Li <jinlin.li@geneegroup.com>
 * date 2015-06-18
 *
 * brief 定时脚本，用于对结算单进行状态查询
 * usage SITE_ID=nankai php tiancai_update_billing_statement_status.php
 */

require_once('base.php');

//获取待审核
$pending_status = Billing_Statement_Model::STATUS_PENDING_CHECK;

//获取所有未付款的付款单
$billing_statements = Q("billing_statement[status={$pending_status}]");

foreach ($billing_statements as $statement) {
    $data = $statement->get_account_status();
    // $data = [
    //     '1' => [
    //         'JE' => 2000,
    //         'ZT' => 4,
    //         'YYDH' => 'DH100',

    //     ],
    // ];
    if (!$data) {
        $now = Date::time();
        $approve_date = $statement->approve_date;
        if ($approve_date && (($approve_date + 7200) < $now)) {
            // 审核后两小时依然查不到天财的数据, 我们将该付款单还原
            $statement->settle();
            continue;
        }
    }
    //data为返回数据合集
    $return = current($data);
    if (count($data) > 1) {
        $return['JE'] = 0;
        foreach($data as $d) {
            //金额总和
            $return['JE'] += $d['JE'];
        }
    }
	// billing_statement 没有 mtime!!!!...
    if (!$return) continue;
    if ($return['ZT'] == Account_Tiancai::PAY_STATUS_SUCCESSED) {
		if  ($statement->id && $return['JE'] == round($statement->balance, 2)) {
			$statement->success();
			$log = sprintf('定时脚本中检查到结算单 #%d 已支付成功, 调用 statement 的 success()',
						   $statement->id);
			Log::add($log, 'account');
		}
	}
	elseif (
		$return['ZT'] == Account_Tiancai::PAY_STATUS_FAILED ||
		$return['ZT'] == Account_Tiancai::PAY_STATUS_APPROVAL_FAILED) {
		$statement->reject(Config::get('account.default_fail_message', '结算失败!'));
		$log = sprintf('定时脚本中检查到结算单 #%d 支付失败, 调用 statement 的 fail()',
					   $statement->id);
		Log::add($log, 'account');
	}
    elseif (
        $return['ZT'] == Account_Tiancai::PAY_STATUS_APPROVAL_OUT ||
        $return['ZT'] == Account_Tiancai::PAY_STATUS_APPROVAL_SUCCESSED
        ) {
        //预约单号
        $reserv_no = $return['YYDH'];
        $statement->reserv_no = $reserv_no;
        if ($return['BMBH']) {
            $statement->bmbh = $return['BMBH'];
        }
        if ($return['XMBH']) {
            $statement->xmbh = $return['XMBH'];
        }
        if ($return['ZFLSH']) {
            $statement->lsh = $return['ZFLSH'];
        }

        Log::add(strtr('结算单[%statement_id]设定预约单号%reserv_no', array(
            '%statement_id'=> $statement->id,
            '%reserv_no'=> $reserv_no
        )), 'transfer');

        $statement->save();
    }
}
