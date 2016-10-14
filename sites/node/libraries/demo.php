<?php

class Demo {

	static function product_ACL($e, $user, $action, $product, $options) {

		switch ($action) {
		// 应显示列出所有可处理的 action
		case '查看价格':
            $is_customer_member = (bool) Q("{$user}<member customer")->total_count();

			if ($is_customer_member) {
				$e->return_value = TRUE;
				return FALSE;
			}
            if ($product->vendor->id == $user->vendor->id) {
                $e->return_value = TRUE;
                return FALSE;
            }
			break;
		default;
			// 若询问的权限在此类中未提及, 则转交其他类处理
			return;
		}

		if ($user->access('管理所有内容')) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

    static function customer_sort_filter($e) {
        $status = [];
        $status += array(
            Order_Model::STATUS_REQUESTING => array('weight'=>10, 'title'=>T('待确认')),
            Order_Model::STATUS_NEED_VENDOR_APPROVE => array('weight'=>15),
            Order_Model::STATUS_APPROVED => array('weight'=>25),
            Order_Model::STATUS_RETURNING => array('weight'=>30),
            Order_Model::STATUS_PENDING_TRANSFER => array('weight'=>35),
            Order_Model::STATUS_TRANSFERRED => array('weight'=>40),
            Order_Model::STATUS_CANCELED => array('weight'=>45),
        );

    	$e->return_value = $status;
    	return false;
    }

    public static function get_external_message($e, $statement)
    {
        $data = $statement->pdata;
        if (!empty($data) && is_array($data)) {
            $e->return_value = HT('%dep(%dep_no) - %prj(%prj_no)', [
                '%dep'=> $data['department'],
                '%dep_no'=> $data['department_no'],
                '%prj'=> $data['project'],
                '%prj_no'=> $data['project_no'],
            ]);
        }
    }

    public static function get_extra_vendor_links($e, $item, $links)
    {
        $links['QRcode'] = array(
            'text' => T('打印'),
            'url' => URI::url('!vendor/item/export.'.$item->id),
            'extra' =>'class="blue" target="_blank"'
            );
    }
}
