<?php

class Downloader {

	static function download($path, $force = FALSE, $range_begin = 0, $range_end = 0) {

		if (!File::exists($path)) {
			URI::redirect('error/404');
			return; // 文件不存在
		}

		$extension = File::extension($path);
		$handler = self::$_extension_handlers[$extension];
		if (!$force && $handler) {
			call_user_func($handler, $extension, $path);
		}
		else {

			$size = File::size($path);
			$begin = 0;
			$end = $size;

			$fm=@fopen($path, 'rb');
			if (!$fm) {
				URI::redirect('error/401');
				return; // 文件无法打开
			}

			if ($range_begin || $range_end) {
				$begin = $range_begin;
				$end = $range_end ? : $size;

				// 部分下载(以实现断点续传)
				header('HTTP/1.0 206 Partial Content');

				$mime_type = 'application/octet-stream';
			}
			else {
				header('HTTP/1.0 200 OK');

				$mime_type = self::mime_type($filename);
				if ($force || !$mime_type) {
					$mime_type = 'application/force-download';
				}
			}

			$filename = File::basename($path);
			$encoded_filename = urlencode($filename);
			$encoded_filename = str_replace("+", "%20", $encoded_filename);

			header("Content-Type: $mime_type");
			header('Cache-Control: public, must-revalidate, max-age=0');
			header('Pragma: no-cache');
			header('Accept-Ranges: bytes');
			header('Content-Length:'.($end-$begin));
			header("Content-Range: bytes $begin-$end/$size");

			/*
			  NO. BUG#123 (Cheng.Liu@2010.11.09)
			  兼容IE系列浏览器文件头问题, 将获取浏览器方式改为内置方法
			  Browser::name()方式
			*/
			if (Browser::name() == 'ie') {
				header("Content-Disposition: inline; filename=\"$encoded_filename\"");
			}
			else{
				header("Content-Disposition: inline; filename=\"$filename\"");
			}

			header("Content-Transfer-Encoding: binary\n");
			header("Last-Modified: $time");
			header('Connection: close');

			$cur = $begin;
			fseek($fm,$begin,0);

			while (!feof($fm) && $cur < $end && (connection_status()==0)) {
				print fread($fm, min(1024*16, $end-$cur));
				$cur += 1024*16;
			}

		}
 		// 下载文件完 不一定要退出 让调用程序可以进行一些后续处理
	}

	private static $_extension_handlers;
	static function register_extension_handler($ext, $handler) {
		if (is_array($ext)) {
			foreach ($ext as $t) {
				self::register_extension_handler($t, $handler);
			}
		}
		else {
			self::$_extension_handlers[$ext] = $handler;
		}
	}

	static function image_handler($ext, $path) {
		switch ($ext) {
		case 'jpg':
		case 'jpeg':
			$type = 'jpg';
			break;
		case 'gif':
			$type = 'gif';
			break;
		default:
			$type = 'png';
		}

		Image::show_file($path, $type);
	}

	static function flash_handler($ext, $path) {
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/x-shockwave-flash");

		@readfile($path);

	}

	static function mime_type($filename) {
	   $fileext = substr(strrchr($filename, '.'), 1);
	   if (empty($fileext)) return (false);
	   $regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i";
	   $lines = file("/etc/mime.types");
	   foreach($lines as $line) {
		  if (substr($line, 0, 1) == '#') continue; // skip comments
		  $line = rtrim($line) . " ";
		  if (!preg_match($regex, $line, $matches)) continue; // no match to the extension
		  return ($matches[1]);
	   }
	   return (false); // no match at all
	}

}

Downloader::register_extension_handler(array('png', 'jpg', 'jpeg', 'gif'), 'Downloader::image_handler');
Downloader::register_extension_handler('swf', 'Downloader::flash_handler');
