<?php
Core::load(THIRD_BASE, 'oauth2_server/src/OAuth2/Storage/ClientInterface', '*');

class OAuth2_Storage_Client implements \OAuth2\Storage\ClientInterface {

	public function getClient($clientId = null, $clientSecret = null, $redirectUri = null) {
		$clients = Config::get('oauth.consumers');
		foreach ($clients as $client) {
			if ($clientId == $client['key']) {
				if (isset($clientSecret) && $clientSecret != $client['secret']) {
					return FALSE;
				}
				if (isset($redirectUri) && $redirectUri != $client['redirect_uri']) {
					return FALSE;
				}
				return array(
					'client_id' => $client['key'],
					'client secret' => $client['secret'],
					'redirect_uri' => $client['redirect_uri'],
					'name' => $client['title'],
					);
			}
		}

		return FALSE;
	}

}
