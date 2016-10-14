#!/usr/bin/env php
<?php
$base = dirname(dirname(dirname(__FILE__))). '/base.php';
require $base;
function delDirAndFile( $dirName ) {
	if ( $handle = opendir( "$dirName" ) ) {
	while ( false !== ( $item = readdir( $handle ) ) ) {
		if ( $item != "." && $item != ".." ) {
			if ( is_dir( "$dirName/$item" ) ) {
			delDirAndFile( "$dirName/$item" );
			} else {
				if( unlink( "$dirName/$item" ) )echo "成功删除文件： $dirName/$item<br />n";
			}
		}
	}
	closedir( $handle );
		if( rmdir( $dirName ) )echo "成功删除目录： $dirName<br />n";
	}
}

delDirAndFile(SITE_PATH.PRIVATE_BASE.'icons/product/');

rename(SITE_PATH.PRIVATE_BASE.'icons/vendor_product/', SITE_PATH.PRIVATE_BASE.'icons/product/');