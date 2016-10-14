<?php
$config['user_model.before_delete'][] = 'OAuth_Client::delete_oauth_user';

$config['auth.post_logout'][] = 'OAuth_Client_OAuth2_MALL::logout_oauth_provider';

