<?php
Core::load(THIRD_BASE, 'oauth2_server/src/OAuth2/Storage/SessionInterface', '*');

class OAuth2_Storage_Session implements \OAuth2\Storage\SessionInterface {
	/**
     * Create a new OAuth session
     *
     * Example SQL query:
     *
     * <code>
     * INSERT INTO oauth_sessions (client_id, redirect_uri, owner_type,
     * owner_id, auth_code, access_token, refresh_token, stage, first_requested,
     * last_updated) VALUES ($client_id, $redirect_uri, $type, $type_id, $auth_code,
     * $access_token, $stage, UNIX_TIMESTAMP(NOW()), UNIX_TIMESTAMP(NOW()))
     * </code>
     *
     * @param  string $client_id          The client ID
     * @param  string $redirect_uri       The redirect URI
     * @param  string $type              The session owner's type (default = "user")
     * @param  string $type_id            The session owner's ID (default = "null")
     * @param  string $auth_code          The authorisation code (default = "null")
     * @param  string $access_token       The access token (default = "null")
     * @param  string $refresh_token      The refresh token (default = "null")
     * @param  int    $access_token_expire The expiry time of an access token as a unix timestamp
     * @param  string $stage             The stage of the session (default ="request")
     * @return int                       The session ID
     */
    public function createSession(
        $client_id,
        $redirect_uri,
        $type = 'user',
        $type_id = null, // TODO type can be user or client, and type id need type first
        $auth_code = null,
        $access_token = null,
        $refresh_token = null,
        $access_token_expire = null,
        $stage = 'requested'
		) {

		$session = O('oauth2_session');
		$session->client_id = $client_id;
		$session->redirect_uri = $redirect_uri;
        $session->type = $type;
        $session->type_id = $type_id;
        $session->auth_code = $auth_code;
        $session->access_token = $access_token;
        $session->refresh_token = $refresh_token;
        $session->access_token_expire = $access_token_expire;
        $session->stage = $stage;

		$session->save();

		return $session->id;

	}

    /**
     * Update an OAuth session
     *
     * Example SQL query:
     *
     * <code>
     * UPDATE oauth_sessions SET auth_code = $auth_code, access_token =
     *  $access_token, stage = $stage, last_updated = UNIX_TIMESTAMP(NOW()) WHERE
     *  id = $session_id
     * </code>
     *
     * @param  string $session_id         The session ID
     * @param  string $auth_code          The authorisation code (default = "null")
     * @param  string $access_token       The access token (default = "null")
     * @param  string $refresh_token      The refresh token (default = "null")
     * @param  int    $access_token_expire The expiry time of an access token as a unix timestamp
     * @param  string $stage             The stage of the session (default ="request")
     * @return  void
     */
    public function updateSession(
        $session_id,
        $auth_code = null,
        $access_token = null,
        $refresh_token = null,
        $access_token_expire = null,
        $stage = 'requested'
		) {

		$session = O('oauth2_session', $session_id);
		if ($session->id) {
			$session->auth_code = $auth_code;
			$session->access_token = $access_token;
			$session->refresh_token = $refresh_token;
			$session->access_token_epires = $access_token_expire;
			$session->stage = $stage;
			$session->last_updated = Date::time();

			$session->save();
		}

	}


    /**
     * Delete an OAuth session
     *
     * <code>
     * DELETE FROM oauth_sessions WHERE client_id = $client_id AND owner_type =
     *  $type AND owner_id = $type_id
     * </code>
     *
     * @param  string $client_id The client ID
     * @param  string $type     The session owner's type
     * @param  string $type_id   The session owner's ID
     * @return  void
     */
    public function deleteSession(
        $client_id,
        $type,
        $type_id
		) {

		$session = O('oauth2_session', array(
						 'client_id' => $client_id,
						 'type' => $type,
						 'type_id' => $type_id,
						 ));
		if ($session->id) {
			$session->delete();
		}
	}


    /**
     * Validate that an authorisation code is valid
     *
     * Example SQL query:
     *
     * <code>
     * SELECT id FROM oauth_sessions WHERE client_id = $clientID AND
     *  redirect_uri = $redirect_uri AND auth_code = $auth_code
     * </code>
     *
     * @param  string     $client_id    The client ID
     * @param  string     $redirect_uri The redirect URI
     * @param  string     $auth_code    The authorisation code
     * @return  int|bool   Returns the session ID if the auth code
     *  is valid otherwise returns false
     */
    public function validateAuthCode(
        $client_id,
        $redirect_uri,
        $auth_code
		) {

		$session = O('oauth2_session', array(
						 'client_id' => $client_id,
						 'redirect_uri' => $redirect_uri,
						 'auth_code' => $auth_code,
						 ));

		if ($session->id) {
			return array(
				'id' => $session->id,
				'client_id' => $client_id,
				'redirect_uri' => $redirect_uri,
				'auth_code' => $auth_code,
				);
		}

		return FALSE;

	}


    /**
     * Validate an access token
     *
     * Example SQL query:
     *
     * <code>
     * SELECT id, owner_id, owner_type FROM oauth_sessions WHERE access_token = $access_token
     * </code>
     *
     * Response:
     *
     * <code>
     * Array
     * (
     *     [id] => (int) The session ID
     *     [owner_type] => (string) The owner type
     *     [owner_id] => (string) The owner ID
     * )
     * </code>
     *
     * @param  [type] $access_token [description]
     * @return [type]              [description]
     */
    public function validateAccessToken($access_token) {
		$session = O('oauth2_session', array(
			  'access_token' => $access_token,
			  ));
		if ($session->id) {
			return array(
				'id' => $session->id,
				'owner_type' => $session->type,
				'owner_id' => $session->type_id,
				);
		}
		else {
			return false;
		}
	}

    /**
     * Return the access token for a given session
     *
     * Example SQL query:
     *
     * <code>
     * SELECT access_token FROM oauth_sessions WHERE id = $session_id
     * </code>
     *
     * @param  int         $session_id The OAuth session ID
     * @return string|null            Returns the access token as a string if
     *  found otherwise returns null
     */
    public function getAccessToken($session_id) {
		// TODO
	}


    /**
     * Validate a refresh token
     * @param  string $refresh_token The refresh token
     * @param  string $client_id     The client ID
     * @return int                  The session ID
     */
    public function validateRefreshToken($refresh_token, $client_id) {
		// TODO
	}


    /**
     * Update the refresh token
     *
     * Example SQL query:
     *
     * <code>
     * UPDATE oauth_sessions SET access_token = $newAccessToken, refresh_token =
     *  $newRefreshToken, access_toke_expires = $access_token_expires, last_updated = UNIX_TIMESTAMP(NOW()) WHERE
     *  id = $session_id
     * </code>
     *
     * @param  string $session_id             The session ID
     * @param  string $newAccessToken        The new access token for this session
     * @param  string $newRefreshToken       The new refresh token for the session
     * @param  int    $access_token_expires    The UNIX timestamp of when the new token expires
     * @return void
     */
    public function updateRefreshToken(
        $session_id,
        $newAccessToken,
        $newRefreshToken,
        $access_token_expires
		) {
		// TODO
	}

    /**
     * Associates a session with a scope
     *
     * Example SQL query:
     *
     * <code>
     * INSERT INTO oauth_session_scopes (session_id, scope_id) VALUE ($session_id,
     *  $scope_id)
     * </code>
     *
     * @param int    $session_id The session ID
     * @param string $scope_id   The scope ID
     * @return void
     */
    public function associateScope($session_id, $scope_id) {

		$session = O('oauth2_session', $session_id);
		$scopes = explode(' ', $session->scopes);
		if (!in_array($scope_id, $scopes)) {
			$scopes[] = $scope_id;
		}
		$session->scopes = join(' ', $scopes);
		$session->save();
	}


    /**
     * Return the scopes associated with an access token
     *
     * Example SQL query:
     *
     * <code>
     * SELECT oauth_scopes.scope FROM oauth_session_scopes JOIN oauth_scopes ON
     *  oauth_session_scopes.scope_id = oauth_scopes.id WHERE
     *  session_id = $session_id
     * </code>
     *
     * Response:
     *
     * <code>
     * Array
     * (
     *     [0] => (string) The scope
     *     [1] => (string) The scope
     *     [2] => (string) The scope
     *     ...
     *     ...
     * )
     * </code>
     *
     * @param  int   $session_id The session ID
     * @return array
     */
    public function getScopes($session_id) {
		// TODO
	}

}