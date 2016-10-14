<?php

class CLI_Oauth {
	function create_generate_consumer_key() {
		$client_id = exec('uuidgen');
		echo "client_id : $client_id\n";
		$client_secret = exec('uuidgen');
		echo "client_secret : $client_secret";
	}
}