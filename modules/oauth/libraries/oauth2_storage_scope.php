<?php
Core::load(THIRD_BASE, 'oauth2_server/src/OAuth2/Storage/ScopeInterface', '*');

class OAuth2_Storage_Scope implements \OAuth2\Storage\ScopeInterface {

	public function getScope( $scope_id ) {

		$scopes = Config::get('oauth.scopes');
		foreach ($scopes as $key => $scope) {
			if ( $key == $scope_id ) {
				return $scope;
			}
		}

		return FALSE;
	}

}
