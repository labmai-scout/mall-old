<?php

class NFS {

	private static $root = NULL;

	static function setup_profile() {
	}
	
	static function setup() {
		self::register_handler('attachments', 'NFS::get_attachments_path');
	}
	
	private static $handlers = [];
	static function register_handler($type, $callback) {
		self::$handlers[$type] = $callback;
	}
	
	static function get_path($object, $path='', $type = 'attachments' , $use_full_path=TRUE) {
		if (isset(self::$handlers[$type])) {
			return call_user_func(self::$handlers[$type], $object, $path, $use_full_path);
		}

		$root = Config::get('nfs.root');
		return $use_full_path ? $root.$path : $path;
	}

	static function get_link_path($object, $path_prefix, $path, $path_type) {
		$link_path = Event::trigger('get_link_path', $object, $path_prefix, $path, $path_type);
		return $link_path ? :$path;
	}

	static function get_path_prefix($object, $path, $path_type, $return_link_prefix=FALSE) {
		$path_prefix = Event::trigger('get_prefix_path', $object, $path, $path_type, $return_link_prefix);
		if (!$path_prefix) {
			if ($object->id) {
				$path_prefix = $path_type. '/' . $object->name() . '/' . $object->id . '/';
			}
		}
		return $path_prefix;
	}
	
	static function get_attachments_path($object, $path='', $use_full_path=TRUE) {
		$root = Config::get('nfs.root');
		if ($object->id) {
			$prefix = rtrim($root, '/') . '/attachments/' . $object->name() . '/' . $object->id . '/';
		}
		else {
			$prefix = rtrim(Config::get('system.tmp_dir'), '/').'/attachments/'.$object->name().'/'.L('ME')->id.'/';
		}

		$new_path =  $prefix . $path;  
		/* NOTICE!! 获取路径时不要新建路径  by Jia Huang
		if(!file_exists($new_path)) {
			File::check_path($new_path.'foo');
			if (is_dir($new_path)) {
				@mkdir($new_path, 0755);
			}
		}
		*/

		return $use_full_path ? $new_path : File::relative_path($new_path, $prefix);
	}

	//返回$file1和$file2的文件对比结果
	static function file_sort($file1,$file2) {
		if ($file1['dir'] && !$file2['dir']) return FALSE;
		if (!$file1['dir'] && $file2['dir']) return TRUE;
		if ($file1['mtime'] == $file2['mtime']) {
			return strcmp($file1['name'], $file2['name']) > 0;
		}
		else {
			return $file1['mtime'] < $file2['mtime'];
		}
	}
	
	//返回此全路经下文件的相关信息
	static function file_info($full_path) {
		if (!file_exists($full_path)) return NULL;
		$s = @stat($full_path);
		if (!$s) return NULL;
		$info =  [
			'mtime' => $s['mtime'],
			'atime' => $s['atime'],
			'ctime' => $s['ctime'],
			'size' => $s['size'],
			'file' => @is_file($full_path),
			'dir'  => @is_dir($full_path),
			'link' => @is_link($full_path),
		];

		if ($info['dir']) {
			$info['type'] = 'dir';
		}
		elseif ($info['file']) {
			$ext = File::extension($full_path);
			switch ($ext) {
			case 'jpg':
			case 'png':
			case 'gif':
			case 'tiff':
				$info['type'] = 'image';
				break;
			case 'xls':
			case 'csv':
			case 'xlsx':
				$info['type'] = 'excel';
				break;
			case 'pdf':
				$info['type'] = 'pdf';
				break;
			default:
				$info['type'] = 'default';
			}
		}
		else {
			$info['type'] = 'link';
		}
		
		return $info;
	}
	
	//返回此路径的去除点，空格等后的合法路径$path
	static function fix_path($path) {
        //由于不同浏览器解析问题，需要rawurldecode()统一进行处理为未转义情况
        $path = rawurldecode($path);
		$path = preg_replace('/^[\/\s]+|[\/\s]+$/', '', $path);
		$path = preg_replace('/^\.{1,2}\//', '', $path);
		$path = preg_replace('/\/\.{1,2}\//', '/', $path);
		return $path;
	}
	
	static function file_list($full_path,$path) {

		if(!is_dir($full_path)) return null;
		
		$full_path = preg_replace('/[\/\s]+$/', '', $full_path);
		$dh = @opendir($full_path);
		if(!$dh) {
			return null;
		}
		while(FALSE!==($name=readdir($dh))) {
			if ($name[0] == '.') continue;
			$file = NFS::file_info($full_path.'/'.$name);
			if ($file) {
				$files[$name] = $file + ['name'=>$name, 'path'=>($path ? $path.'/' : '').$name];
			}
		}

		closedir($dh);
		
		$files = (array) $files;
		
		if (count($files)>0) usort($files, 'NFS::file_sort');
		return $files;
	}

	/* 计算某对象附件的个数 */
	static function count_attachments($object) {
		$path = '';
		$path_type = 'attachments';
		$full_path = NFS::get_path($object, $path, $path_type, TRUE);

		return self::count_files($full_path);
	}

	/* 计算某目录下文件(不包括目录)的个数 */
	static function count_files($path) {
		$ret = 0;
		if (is_dir($path)) {
			/* $ret = exec("ls -l {$path} | grep -c '^-.*'"); */
			foreach (scandir($path) as $file) {
				if (is_file($path . $file)) {
					$ret++;
				}
			}
		}
		return $ret;
	}

	static function pathinfo($path) {
		$pathinfo = [];
		
		$name = substr($path, strripos($path, '/')+1, strripos($path,'.'));

		$dirname = substr($path, 0, strripos($path,'/'));

		preg_match( "/(.*)\./ ",$name,$filename); 

		preg_match("/((?<=\.)\w+)$/", $name, $exp);
		
		$pathinfo['dirname'] = $dirname;
		$pathinfo['basename'] = $name;
		$pathinfo['extension'] = $exp[1];
		$pathinfo['filename'] = $filename[1];
		
		return $pathinfo;
	}
	
	static function user_access($user, $perm_name, $object, $opt) {
		
		/*
			NO. BUG#213 (Cheng.Liu@2010.12.14)
			将权限判断中 如果用户没有nfs_size就直接return FALSE 的判断去除掉
		*/
		$path = $opt['path'];
		$path_type = $opt['type'];
		
		switch ($perm_name) {
		case "下载文件":
		case "上传文件":
		case "创建目录":
		case "删除文件":
		case "修改文件":
		case "修改目录":
		case "删除目录":
			if (!$path) return FALSE;
			break;                  
		}

		$ret = Event::trigger('nfs.user_access', $user, $perm_name, $object, $opt);
		if (isset($ret)) return $ret;

		return $user->is_allowed_to($perm_name, $object, $opt);
	}
	
	static function fix_name($name, $delete_slash = FALSE) {
		//文件或目录名称修改函数
		if (is_null($name) || trim($name) == '') return;
		
		//无论如何，都会清空前面的.，如果delete_slash为true，会继续清空前面的/
		$name = preg_replace('/^[.\s\/]+/', '', $name);
		if ($delete_slash) {
			$name = preg_replace('/\//', '_', $name); /* 删除斜线(/) */
		}
		
		return $name;
	}

	static function move_files($old_path, $new_path) {
		$old_path = preg_replace('|/+$|', '', $old_path);
		$new_path = preg_replace('|/+$|', '', $new_path);
		if (is_dir($old_path)) {
			$dh = @opendir($old_path);
			if ($dh) {
				while($name = @readdir($dh)) {
					if ($name[0] == '.') continue;
					$new_file = $new_path.'/'.$name;
					File::check_path($new_file);
					@rename($old_path.'/'.$name, $new_file);
				}
			}
		}
		
	}
}
