<?php

class OpenSSL {
	
	private $_public_key;
	private $_private_key;
	
	function __construct() {
		/*
		$res = openssl_pkey_new();
		openssl_pkey_export($res, $privkey);
		$pubkey = openssl_pkey_get_details($res);
		$this->_public_key = $pubkey['key'];
		$this->_private_key = $privkey;
		*/
	}
	
	function encrypt($data, $key, $type='public') {
		if ($type == 'public') {
			$pubkey = $this->_check_pubkey($key);
			@openssl_public_encrypt($data, $encrypt, $pubkey);
		}
		else {
			$privkey = $this->_check_privkey($key);
			@openssl_private_encrypt($data, $encrypt, $privkey);
		}
		
		return $encrypt;
	}
	
	function decrypt($data, $key, $type='public') {
		if ($type == 'public') {
			$pubkey = $this->_check_pubkey($key);
			@openssl_public_decrypt($data, $decrypt, $pubkey);
		}
		else {
			$privkey = $this->_check_privkey($key);
			@openssl_private_decrypt($data, $decrypt, $privkey);
		}
		
		return $decrypt;
	}

    //传入数据和private key 进行签名
    function sign($data, $key) {
        $privkey = $this->_check_privkey($key);
        if (@openssl_sign($data, $signature, $key)) {
            return $signature;
        }
        else {
            return FALSE;
        }
    }

    //传入data 签名后的值 public key 进行签名验证
    function verify($data, $signature, $key) {
        $pubkey = $this->_check_pubkey($key);
        if (@openssl_verify($data, $signature, $pubkey)) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

	function _check_pubkey ($key) {
		$key = $key ?: $this->_public_key;
		return @openssl_get_publickey($key);
	}
	
	function _check_privkey ($key) {
		$key = $key ?: $this->_private_key;
		return @openssl_get_privatekey($key);
	}
}
