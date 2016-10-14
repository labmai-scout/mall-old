<?php

class OAuth2_API_User {

	function get_username($access_token) {
		if(!$access_token) return;
		$server = new OAuth2_Resource_Server();
		$username = $server->get_username($access_token);
		return $username;
	}

	//通过access_token去获取对应的用户
	function info($access_token, $keys=null, $json=FALSE) {
		if(!$access_token) return;
		$server = new OAuth2_Resource_Server();
		$username = $server->get_username($access_token);
		$user = O('user', array('token'=>$username));

		if(!$user->id) return;
		$info = array(
			// 'id' => $user->id,
			'username' => $user->token,
			'name' => $user->name,
			'email' => $user->email,
			'ref_no'  => $user->ref_no,
			'card_no' => $user->card_no,
			'dfrom' => $user->dfrom,
			'dto' => $user->dto,
			'organization' => $user->organization,
			'gender' => $user->gender,
			'major' => $user->major,
			'phone' => $user->phone,
			'mobile' => $user->mobile,
			'address' => $user->address,
			'member_type' => $user->member_type,
		);

		if(!$keys){
			return $json ? json_encode($info) : $info;
		}
		else{
			if(is_array($keys)){
				$data = array();
				foreach ($keys as $key) {
					if(array_key_exists($key, $info)){
						$data[$key] = $info[$key];
					}
				}
				return $json ? json_encode($data) : $data;
			}
			else{
				if(array_key_exists($keys, $info)){
					return $json ? json_encode($info[$keys]) : $info[$keys];
				}
			}
		}
	}
}
