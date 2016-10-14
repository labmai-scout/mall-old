<?php

// If we're running under `php -S` with PHP 5.4.0+
 
// Replicate the effects of basic "index.php"-hiding mod_rewrite rules
// Tested working under FatFreeFramework 2.0.6 through 2.0.12.

// Replicate the FatFree/WordPress/etc. .htaccess "serve existing files" bit
$url_parts = parse_url($_SERVER["REQUEST_URI"]);
$_req = rtrim($_SERVER['DOCUMENT_ROOT'] . $url_parts['path'], '/' . DIRECTORY_SEPARATOR);
if (__FILE__ !== $_req && __DIR__ !== $_req && file_exists($_req)) {
    return false;    // serve the requested resource as-is.
}

$url = parse_url($_SERVER["REQUEST_URI"]);
$ext = strtolower(pathinfo($url['path'], PATHINFO_EXTENSION));
if (in_array($ext, ["ico", "png", "jpg", "gif", "swf", "css", "js"])) {
	$_SERVER["PATH_INFO"] = '/public';
	$_SERVER["SCRIPT_NAME"] = '/index.php';
	$_GET['f'] = preg_replace('|^/|', '', $url['path']);
}
elseif (!preg_match('|^\/index.php|', $_SERVER["REQUEST_URI"])) {
	$url = parse_url($_SERVER["REQUEST_URI"]);
	$_SERVER["PATH_INFO"] = $url['path'];
	$_SERVER["REQUEST_URI"] = '/index.php'.$_SERVER["REQUEST_URI"];
	$_SERVER["SCRIPT_NAME"] = '/index.php';
}

$_SERVER['SITE_ID'] = 'nankai';

include __DIR__ . '/index.php';
