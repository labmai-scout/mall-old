<?php
// 定义OAuth2 的 scopes (类似于"权限")
$config['scopes']['user'] = array(
	'id'	=>	'user',
	'scope'	=>	'user',
	'name'	=>	'user details',
	'description'	=>	'retrives a user\'s details',
);



// 客户端需要指定自己需要的 providers
//
// 每指定一个 provider, 要同时在 config/auth.php 中
// 增加一个 key 相同的 backend, 以支持 OAuth 登陆
//
/*
// OAuth 2
$oauth2_provider_site_url = 'http://xiaopei.li.lab.gin.genee.cn/demo';
$config['providers']['oauth2_provider'] = array(
	'client_class' => 'oauth2_lims',
	'title' => 'OAuth2 Provicer',
	'provider' => 'oauth2_provider',
	'key' => '966f392b50046b4e016977332546993103f537d1',
	'secret' => 'b5db0854228b6c8ef191ffc7d4013be173785395',
	'site_url' => $oauth2_provider_site_url,
	'auth_url' => $oauth2_provider_site_url . '/!oauth/provider2',
	'token_url' => $oauth2_provider_site_url . '/!oauth/provider2/access_token',
	'api_url' => $oauth2_provider_site_url . '/!oauth/api2',
);
*/

// 服务器端需要指定已知的 consumers
// 
// 暂使用配置存 consumers, 以后可设计
// 管理 consumers 的 controller.
//
// consumer key/secret 由 cli/oauth/generate_consumer_key.php 生成
/*
$config['consumers']['test'] = array(
	// 'disabled' => TRUE,

	'provider' => 'test',
	'title' => '测试 Consumer',
	'key' => '966f392b50046b4e016977332546993103f537d1',
	'secret' => 'b5db0854228b6c8ef191ffc7d4013be173785395',

	'redirect_uri' => 'http://xiaopei.li.cf.gin.genee.cn/test/!oauth/consumer/authorization_grant.demo2',
	'auto_authorise' => TRUE,
);
*/

