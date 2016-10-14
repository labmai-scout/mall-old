<?php

class NFS_Lite_Controller extends Controller {

    function index() {
        list($oname, $id, $path_type) = func_get_args();

		$object = O($oname, $id);
		$user = L('ME');

		$path = NFS::fix_path(Input::form('path')); /* 用户点击的路径，只应为文件不应为目录 */

		$full_path = NFS::get_path($object, $path, $path_type, TRUE);

		$form = Input::form();

		if (NFS::user_access($user, '下载文件', $object, ['path'=>$path.'/foo', 'type'=>$path_type])) {
			if (is_file($full_path)) {

				Downloader::download($full_path, TRUE);

				/* 记录日志 */
				Log::add(strtr('[nfs] %user_name[%user_id] 下载了 %path', [
						'%user_name' => $use->name,
						'%user_id' => $user->id,
						'%path' => $full_path,
						]),'journal');

				exit;
			}
		}

		if ((!file_exists($full_path) || is_dir($full_path)) && NFS::user_access($user, '列表文件', $object, ['path'=>$path.'/foo', 'type'=>$path_type])) {
			$this->_index_dir($object, $path, $full_path, $path_type);
		}

	}

	private function _index_dir($object, $path, $full_path, $path_type) {
		$files = NFS::file_list($full_path,$path);

		$form_token = Input::form('form_token');

		$extra = Input::form('extra');

		echo V('nfs:nfs_lite/index',[
			'files' => $files,
			'object' => $object,
			'path' => $path,
			'path_type' => $path_type,
			'form_token' => $form_token,
			'extra'=>$extra
		]);

	}

	function upload($oname, $id='0', $path_type) {
		/*
		  已测试可防止通过修改文件名或path参数尝试新建目录的行为
		 */

		$form = Input::form();
		/* 拼接目录 */
		$object = O($oname, $id);
		$user = L('ME');
		$path = NFS::fix_path($from['path']);

		/* 判断权限 */
		if (!NFS::user_access($user, '上传文件', $object, ['path'=>$path.'/foo', 'type'=>$path_type])) {
			URI::redirect('error/404', 404);
		}
		$file = Input::file('Filedata');

		$post_size = ini_get('post_max_size');

		if ($file['error']) {
			echo '<textarea>'.htmlentities(@json_encode((string)V('nfs:nfs_lite/virus', ['msg' => I18N::T('nfs', '您上传的文件发生异常错误或大于%size!', ['%size'=>$post_size])]))).'</textarea>';
			die;
		}

		if (!$file || !$file['tmp_name']) {
			URI::redirect('error/404', 404);
		}

		$file_name = $file['name'];

		/*
		  FIX BUG #604::文件名称为"."开始的文件上传后会隐藏
		  (xiaopei.li@2011.06.02)
		  将前缀带点和有空格的全部都隐藏掉。(cheng.liu@2011.06.14)
		*/
		$file_name = NFS::fix_name($file_name, TRUE);
		$file_path = ($path ? $path . '/': '').$file_name;
		$full_path = NFS::get_path($object, $file_path, $path_type);

		if (file_exists($full_path)) {
			$dirname = dirname($file_path).'/';
			$full_dirname = dirname($full_path).'/';

			$info = NFS::pathinfo($full_path);
			$extension = $info['extension'] ? '.'.$info['extension'] : '';
			$name = substr($file_name, 0, strrpos($file_name,'.') ? : strlen($file_name));
			/* BUG #839::重复上传.开头的文件后文件名丢失 */

			$suffix_count = 2;

			do {
				$file_name = $name.'('.$suffix_count.')'.$extension;
				$file_path = $dirname . $file_name;
				$full_path = $full_dirname . $file_name;
				$suffix_count++;
			}
			while (file_exists($full_path));

		}

		File::check_path($full_path);
		move_uploaded_file($file['tmp_name'], $full_path);
		Event::trigger('nfs.stat',$object, $path, $full_path, $path_type, 'upload');
		/* 记录日志 */
		Log::add(strtr('[nfs] %user_name[%user_id] 上传了 %path', [
				'%user_name' => $use->name,
				'%user_id' => $user->id,
				'%path' => $full_path,
				]),'journal');

		$file = NFS::file_info($full_path);
		if (!$file) throw new Error_Exception;

		$file += ['name'=>$file_name, 'path'=>$file_path];

		/*
			NO. BUG#213 (Cheng.Liu@2010.12.02)
			传值时需要将$path_type添加进去，才能在之后的link连接中通过权限判断
			NO. BUG#438 (Cheng.liu@2011.03.24)
			在文件刚上传之后传入权限来判断是否显示seletor框
		*/
		$can_download = NFS::user_access($user, '下载文件', $object, ['path'=>$path.'/foo', 'type'=>$path_type]);
		$can_edit = NFS::user_access($user, '修改文件', $object,  ['path'=>$path.'/foo', 'type'=>$path_type]);
		$output = (string) V('nfs_lite/file', [
			'object'=>$object,
			'path'=>$path,
			'file'=>$file,
			'form_token'=>$form_token,
			'path_type'=>$path_type,
			'can_edit'=>$can_edit,
			'can_download'=>$can_download
		]);

		//根据不同的上传方式进行不同处理
		if (Input::form('single')) {
			//单个文件上传
			echo '<textarea>'.htmlentities(@json_encode($output)).'</textarea>';
		}

		exit;
	}

}

class NFS_Lite_AJAX_Controller extends AJAX_Controller {


	function index_delete_click($oname, $id=0, $path_type) {
		$form = Input::form();
		$object = O($oname,$id);
		$user = L('ME');

		$path = NFS::fix_path(rawurldecode($form['delete_path']));
		/*
		2014-11-12
		@zhongjian.xu
		由于PHP的basename函数本身不支持中文路径，所以此处用explode直接获取到文件名进行alert
		 */
		$path = explode('/', $path);
		$path = end($path);
		if (!NFS::user_access($user, '删除文件', $object, ['path'=>$path.'/foo', 'type'=>$path_type,'extra'=>$form['extr']])) return;

		if (JS::confirm(I18N::T('nfs','您确定删除 %filename 吗?', [
									'%filename' => $path,
									]))) {

			$full_path = NFS::get_path($object, $path, $path_type);
			if (is_dir($full_path)) {
				File::rmdir($full_path);
			}
			else {
				File::delete($full_path);
			}
			Event::trigger('nfs.stat',$object, $path, $full_path, $path_type, 'delete');
			/* 记录日志 */
			Log::add(strtr('[nfs] %user_name[%user_id] 删除了 %path', [
					'%user_name' => $use->name,
					'%user_id' => $user->id,
					'%path' => $full_path,
					]),'journal');

			$form_token = Input::form('form_token');
			$uniqid = $_SESSION[$form_token];
			JS::refresh($uniqid);

		}
	}

	function index_rename_file_submit($oname='', $id=0, $path_type=NULL) {
		/* 不容许通过更名的方式新建目录 */
		$form = Input::form();
		$object = O($oname,$id);
		$user = L('ME');

		$base_path = '';
		$old_name = NFS::fix_path($form['old_name']);
		$name = NFS::fix_name($form['name'], TRUE); /* 第二个参数用来删除文件名中的斜线(/)，禁止通过重命名生成目录 */
		$name = NFS::fix_path($name);

		if ($name === '0') {
			$alert = I18N::T('nfs', '非法的文件名', ['%name'=>$name]);
			JS::alert($alert);
			return;
		}

		if (!NFS::user_access($user, '修改文件', $object, ['path'=>$base_path.'/'.$old_name, 'type'=>$path_type])) return;

		$old_path = NFS::get_path($object, $base_path . '/' . $old_name, $path_type);
		$path = NFS::get_path($object, $base_path . '/' . $name, $path_type);

		if ($old_name != $name && file_exists($path)) {
			if (is_dir($path)) { /* 此段应不会到达 */
				$alert = I18N::T('nfs', '该目录下已存在%name目录', ['%name'=>$name]);
				JS::alert($alert);
			}
			else {
				$alert = I18N::T('nfs', '该目录下已存在%name文件', ['%name'=>$name]);
				JS::alert($alert);
			}
		}
		else {
			File::check_path($path);
			@rename($old_path, $path);
			Event::trigger('nfs.stat',$object, $path, $full_path, $path_type, 'rename');
			/* 记录日志 */
			Log::add(strtr('[nfs] %user_name[%user_id] 重命名 %old_path 到 %path', [
					'%user_name' => $use->name,
					'%user_id' => $user->id,
					'%old_path' => $old_path,
					'%path' => $path,
					]),'journal');

			$form_token = Input::form('form_token');
			$uniqid = $_SESSION[$form_token];
			JS::refresh($uniqid);
		}
	}
}
