<?php

class API_Mall_Customer_Exception extends API_Exception {}

class API_Mall_Customer {
	public function searchCustomers($criteria) {
		$keyword = $criteria['keyword'];
		$selector = "customer";
		if ($keyword) {
			$selector .="[name*=$keyword]";
		}
        $total = Q($selector)->total_count();
        $token = Session::temp_token('customer.searchCustomers_',300);
        $_SESSION[$token] = $selector;
        $result['token'] = $token;
        $result['total'] = $total;
        return $result;
	}
	public function getCustomers($token, $start=0, $limit=20) {
		if(!API_Mall::is_authenticated()) return;
		$selector = $_SESSION[$token];
        $customers = Q($selector)->limit($start, $limit);
        $data = array();
        foreach ($customers as $key => $customer) {
            $data[$customer->gapper_group] = array(
                    'id' => $customer->gapper_group,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'owner' => $customer->owner->gapper_user,
                    'description' => $customer->description
                );
        }
        return $data;
	}

	public function getCustomer($criteria) {
		if(!API_Mall::is_authenticated()) return;

		$customer = O('customer', $criteria);

		$data = [];
		$data['name'] = $customer->name;
		$address = O('deliver_address', array('customer'=>$customer));
		$data['addresses']['default']['address'] = $address->address;
		$data['addresses']['default']['phone'] = $address->phone;
		$data['addresses']['default']['email'] = $address->email;
		$data['addresses']['default']['postcode'] = $address->postcode;

		//如果有lims的绑定信息
		if($customer->lims_data) {
			$data['lims_data'] = $customer->lims_data;
			$data['lims_data']['bind_status'] = $customer->bind_status;
		}

		return $data;
	}

	public function fetchGapperCustomerInfo($group_id) {
		if(!API_Mall::is_authenticated()) return;
		$data = [];
		$customer = O("customer", ['gapper_group'=>$group_id]);
		$members = Q("$customer<member user[gapper_user]");
		$member_roles = [];
		foreach ($members as $member) {
			$member_roles[$member->gapper_user] = Q("customer_member_perm[customer={$customer}][user={$member}]")->to_assoc('id','name');
		}
		$address = O('deliver_address', ['customer'=>$customer]);
		$iAddress = [];
		if ($address->id) {
			$iAddress['postcode'] = $address->postcode;
			$iAddress['phone'] = $address->phone;
			$iAddress['address'] = $address->address;
		}
		$data[$customer->id] = [
			'contacts' => $customer->owner->name,
			'account_no' => $customer->account_no,
			'gapper_group' => $customer->gapper_group,
			'member_permissions' => $member_roles,
			'lab_id' => $customer->lab_id,
			'delivery'=> $iAddress,
		];

		return $data;
	}


	public function fetchGapperCustomersInfo($start, $limit=10) {
		if(!API_Mall::is_authenticated()) return;
		$data = [];
		$customers = Q('customer[gapper_group][unable_upgrade=0]')->limit($start, $limit);
		// 需要RPC验证 gapper_group
		foreach ($customers as $customer) {

			$members = Q("$customer<member user[gapper_user]");
			$member_roles = [];
			foreach ($members as $member) {
				$member_roles[$member->gapper_user] = Q("customer_member_perm[customer={$customer}][user={$member}]")->to_assoc('id','name');
            }
            $address = O('deliver_address', ['customer'=>$customer]);
            $iAddress = [];
            if ($address->id) {
                $iAddress['postcode'] = $address->postcode;
                $iAddress['phone'] = $address->phone;
                $iAddress['address'] = $address->address;
            }
			$data[$customer->id] = [
				'contacts' => $customer->owner->name,
				'account_no' => $customer->account_no,
				'gapper_group' => $customer->gapper_group,
				'member_permissions' => $member_roles,
                'lab_id' => $customer->lab_id,
                'delivery'=> $iAddress,
			];
		}

		return $data;
	}
}
