<?php
/*
没有地方用，而且写错了
// client-side schema
$config['oauth_user'] = array(
	// access token
	'fields' => array(
		'server' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''),
		// server 目前按 config/oauth.php $config['clients'] 的 key 设置,
		// 以后 server 可能要存到数据库中, 并提供新增 server/client 的 controller
		// (xiaopei.li@2012-12-18)
		'remote_id' => array('type' => 'varchar(50)', 'null' => FALSE, 'default' => ''), // 用户在 provider 处的唯一标示(ID), 此项可选
		'user' => array('type' => 'object', 'oname' => 'user'),
		'version' => array('type' => 'tinyint', 'null' => FALSE, 'default' => 0), // oauth 1 or 2
		'access_token' => array('type' => 'varchar(255)', 'null' => FALSE, 'default' => ''), // for 1 and 2
		'access_token_secret' => array('type' => 'varchar(255)', 'null' => FALSE, 'default' => ''), // for 1
		'expires_in' => array('type' => 'bigint', 'null' => FALSE, 'default' => ''), // for 2
		'refresh_token' => array('type' => 'varchar(255)', 'null' => FALSE, 'default' => ''), // for 2
		),
	'indexes' => array(
		'unique' => array('type' => 'unique', 'fields' => array('server', 'remote_id')), // pending, remote_id 不一定有, 但若要单点登陆, 则 remote_id 必须有;
		// 'unique' => array('type' => 'unique', 'fields' => array('server', 'user', 'remote_id')), // pending, 用户可否在一个 server 绑多个账号?
		'user' => array('fields' => array('user')),
		),
);
*/

// server-side schema
// 目前 consumer 皆存在配置中, 以后也许需建立 consumer 类
$config['oauth_consumer_nonce'] = array(
	'fields' => array(
		'consumer' => array('type' => 'varchar(50)', 'null' => false),
		'timestamp' => array('type' => 'bigint', 'null' => false),
		'nonce' => array('type' => 'varchar(255)', 'null' => false),
		),
	'indexes' => array(
		),
);

$config['oauth_token'] = array(
	'fields' => array(
		'consumer' => array('type' => 'varchar(50)', 'null' => false),
		'type' => array('type' => 'tinyint', 'null' => FALSE, 'default' => 0),
		'token' => array('type' => 'varchar(255)', 'null' => false),
		'token_secret' => array('type' => 'varchar(255)', 'null' => false),
		'verifier' => array('type' => 'varchar(255)', 'null' => false),
		'callback_url' => array('type' => 'text', 'null' => false,'default' => ''),
		'user' => array('type' => 'object', 'oname' => 'user'),
	),
	'indexes' => array(
		'unique' => array('type' => 'unique', 'fields' => array('token')),
		'user' => array('fields' => array('user')),
	)
);

$config['oauth2_session'] = array(
	'fields' => array(
		'client_id' => array('type' => 'varchar(255)', 'null' => FALSE, 'default' => ''),
		'redirect_uri' => array('type' => 'varchar(255)', 'default' => ''),
		'type' => array('type' => 'varchar(63)', 'null' => FALSE, 'default' => 'user'), // user or client, but what client type is?
		'type_id' => array('type' => 'int'),
		// 'client' => array('type' => 'type' => 'object', 'oname' => 'oauth2_client'),
		'auth_code' => array('type' => 'varchar(255)', 'null' => TRUE, 'default' => ''),
		'access_token' => array('type' => 'varchar(255)', 'null' => TRUE, 'default' => ''),
		'refresh_token' => array('type' => 'varchar(255)', 'null' => TRUE, 'default' => ''),
		'access_token_epires' => array('type' => 'bigint'),
		'stage' => array('type' => 'varchar(63)', 'default' => 'requested'), // requested or granted
		'first_requested' => array('type' => 'bigint'),
		'last_updated' => array('type' => 'bigint'),
		'scopes' => array('type' => 'text', 'null' => FALSE, 'default' => ''),
		),
	'indexes' => array(
		'client_id' => array('fields' => array('client_id')),
		),
	);


