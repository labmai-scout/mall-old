<?php

class NFS_Controller extends Controller {

	function index($oname = NULL , $id='0', $path_type = NULL) {
		
		$object = O($oname, $id);
		$user = L('ME');
		$path = NFS::fix_path(Input::form('path'));
		$full_path = NFS::get_path($object, $path, $path_type, TRUE);
		$form = Input::form();

		if ($form['search'] && count($form['select'])) {
			$this->_search_index_zip($object, $path, $form['select'], $path_type, $form['download_type'] == 'win');
			exit;

		}elseif (NFS::user_access($user, '下载文件', $object, ['path'=>$path.'/foo', 'type'=>$path_type])) {
			//批量下载
			if (count($form['select']) > 0) {
				#ifdef (nfs.enable_batch_operation)
				if (Config::get('nfs.enable_batch_operation')) {
					/* 记录日志 */
					Log::add(strtr('[nfs] %user_name[%user_id] 批量下载了 %path 目录下的 %paths' , [
							'%user_name' => $use->name,
							'%user_id' => $user->id,
							'%path' => $full_path,
							'%paths' => join(', ', $form['select']),
							]),'journal');
					$this->_index_zip($object, $path, $full_path, $form['select'], $form['download_type'] == 'win');
					exit;
				}
				#endif
			}
			//单文件下载（非批量下载）
			elseif (is_file($full_path)) {
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

        Event::trigger('nfs.list_dir', $object, $path, $path_type);

        if (is_dir($full_path) && NFS::user_access($user, '列表文件', $object, ['path'=> $path. '/foo', 'type'=> $path_type])) {
            $this->_index_dir($object, $path, $full_path, $path_type);
        }
	}

	private function _search_index_zip($object, $path, $file_paths, $path_type, $win) {
		$zip = new ZipArchive;
		$temp_file = tempnam(sys_get_temp_dir(), 'LIMS');
		if ($zip->open($temp_file, ZIPARCHIVE::CREATE) === TRUE) {

			$user = L('ME');
			$filename = File::basename($path);
			if (!$filename) $filename = 'archive';
			foreach ((array)$file_paths as $key => $path) {
				$path_info = pathinfo($path);
				$path_prefix = $path_info['dirname'];
				$file_path = NFS::get_path($object, $path, $path_type, TRUE);
				$path_info = pathinfo($file_path);
				$name = strtr($path_prefix, '/', '_').'_'.$path_info['basename'];
				if (NFS::user_access($user, '下载文件', $object, ['path'=>$path_prefix.'/foo', 'type'=>$path_type])) {
					File::traverse($file_path, 'NFS_Controller::_zip_traverse', ['base'=>$path_info['dirname'], 'zip'=>$zip, 'win'=>$win, 'name'=>$name]);
				}
			}
			$zip->close();

			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: application/zip");
			header("Content-Transfer-Encoding: binary");
			header("Content-Disposition: attachment; filename=".$filename.".zip");

			header("Content-Description: File Transfer");

			@readfile($temp_file);
			@unlink($temp_file);
			exit();
		}	
	}

	private function _index_zip($object, $path, $full_path, $selected_names, $win) {
		// 下载文件
		$zip = new ZipArchive;
		$temp_file = tempnam(sys_get_temp_dir(), 'LIMS');
		if ($zip->open($temp_file, ZIPARCHIVE::CREATE) === TRUE) {
			foreach ($selected_names as $name) {
				$name = NFS::fix_path($name);
				File::traverse($full_path.'/'.$name, 'NFS_Controller::_zip_traverse', ['base'=>$full_path, 'zip'=>$zip, 'win'=>$win]);
			}
			$zip->close();
			$filename = File::basename($path);
			if (!$filename) $filename = 'archive';

			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: application/zip");
			header("Content-Transfer-Encoding: binary");
			header("Content-Disposition: attachment; filename=".$filename.".zip");

			header("Content-Description: File Transfer");

			@readfile($temp_file);
			@unlink($temp_file);
			exit();
		}
	}

	static function _zip_traverse($path, $params) {

        $zip = $params['zip'];
        $win = $params['win'];
        $name = File::relative_path($path, $params['base']);

        if ($win) {
            $name = iconv('UTF-8', 'GB2312', $name);
        }

		//如果为文件，增加文件
		if (is_file($path)) {
			/*
				NO. BUG#164 (Cheng.Liu@2010.11.13)
				由于ubuntu中文文件名编码存在问题，导致此BUG无法彻底解决
				暂时也urlencode方式转化文件名.
			*/
			/* NO. BUG#164 (Jia Huang@2010.11.13)
			将%2F替换回'/' 用于保证目录结构
			*/

			$zip->addFile($path, $name);
		}
		//其他为目录，增加空目录
		else {
			$zip->addEmptyDir($name);
		}

		return $zip;
	}

	private function _index_dir($object, $path, $full_path, $path_type) {
		$form = Input::form();
		$form_token = $form['form_token'];
		$files = NFS::file_list($full_path,$path);
		$new_files = Event::trigger('nfs.filter.files', $object, $path, $full_path, $path_type);
		
		$files = $new_files ?: $files;
		echo V('nfs:nfs/index',[
			'files' => $files,
			'object' => $object,
			'path' => $path,
			'path_type' => $path_type,
			'form_token' => $form_token,
			'refresh' => TRUE
		]);
	}

	function upload($oname, $id='0', $path_type) {
		$object = O($oname, $id);
		$user = L('ME');

		$path = NFS::fix_path(Input::form('path'));
		if (!NFS::user_access($user, '上传文件', $object, ['path'=>$path.'/foo', 'type'=>$path_type])) {
			URI::redirect('error/404', 404);
		}

		$file = Input::file('Filedata');

		$post_size = ini_get('post_max_size');

		if ($file['error']) {
			echo '<textarea>'.htmlentities(@json_encode((string)V('nfs:nfs/virus', ['msg' => I18N::T('nfs', '您上传的文件发生异常错误或大于%size!', ['%size'=>$post_size])]))).'</textarea>';
			die;
		}

		if (!$file || !$file['tmp_name']) {
			URI::redirect('error/404', 404);
		}

		$ret = Event::trigger('NFS.validate.size', $object, $file);

		if ( $ret ) {
			$sizeFail = (string)V('nfs:nfs/sizefail');
			if (Input::form('single')) {
				echo '<textarea>'.htmlentities(@json_encode($sizeFail)).'</textarea>';
				die;
			}
			else {
				#ifdef (nfs.enable_batch_operation)
				if (Config::get('nfs.enable_batch_operation')) {
					echo $sizeFail;
					die;
				}
				#endif
			}
			exit;
		}

		//查看文件的病毒
		@exec('clamscan --quiet '.escapeshellarg($file['tmp_name']), $output, $ret);
		if ($ret != 0 ) {
			// $ret不为0 表示有病毒
			if (Input::form('single')) {
				//单个文件上传
				echo '<textarea>'.htmlentities(@json_encode((string)V('nfs:nfs/virus'))).'</textarea>';
				die;
			}
			else {
				//批量上传
				echo (string)V('nfs:nfs/virus');
				die;
			}
		}

		$file_name = $file['name'];

		/*
		  FIX BUG #604::文件名称为"."开始的文件上传后会隐藏
		  (xiaopei.li@2011.06.02)
		  将前缀带点和有空格的全部都隐藏掉。(cheng.liu@2011.06.14)
		*/
		$file_name = NFS::fix_name($file_name);
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

		Event::trigger('nfs.stat', $object, $path, $full_path, $path_type, 'upload');

		/* 记录日志 */
		Search_NFS::update_nfs_indexes($object, $path, $path_type, TRUE);
		
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
		$output = (string) V('nfs/file', [
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
		else {
			#ifdef (nfs.enable_batch_operation)
			if (Config::get('nfs.enable_batch_operation')) {
				//多个文件flash上传
				echo $output;
			}
			#endif
		}

		exit;
	}
}

class NFS_AJAX_Controller extends AJAX_Controller {

	function index_delete_all_click($oname, $id=0, $path_type) {
		$object = O($oname, $id);
		$user = L("ME");
		$path = Input::form('path');
		$delete_path = Input::form('delete_path');
		if (JS::confirm(I18N::T('nfs','您确定删除选中文件或目录吗?'))) {

			foreach($delete_path as $name) {

				$path_name = NFS::fix_path($path.'/'.$name);

				if (!NFS::user_access($user, '删除文件', $object, ['path'=>$path_name, 'type'=>$path_type])) return;

				$full_path = NFS::get_path($object, $path_name, $path_type);

				Search_NFS::delete_nfs_indexes($object, $path_name, $path_type);
				if (is_dir($full_path)) {
					Event::trigger('nfs.stat', $object, $path, $full_path, $path_type, 'delete');
					File::rmdir($full_path);
				}
				else {
					Event::trigger('nfs.stat', $object, $path, $full_path, $path_type, 'delete');
					File::delete($full_path);
				}

				/* 记录日志 */
				Log::add(strtr('[nfs] %user_name[%user_id] 删除了 %path', [
						'%user_name' => $use->name,
						'%user_id' => $user->id,
						'%path' => $full_path,
						]),'journal');
			}
			$form_token = Input::form('form_token');
			$uniqid = $_SESSION[$form_token]['uniqid'];
			JS::refresh($uniqid);
		}
	}

	function index_delete_click($oname, $id=0, $path_type) {

		$object = O($oname,$id);
		$user = L('ME');
		$path = NFS::fix_path(rawurldecode(Input::form('delete_path')));
		if (!NFS::user_access($user, '删除文件', $object, ['path'=>$path.'/foo', 'type'=>$path_type])) return;

		if (JS::confirm(I18N::T('nfs', '您确定删除 %filename 吗?', [
									'%filename' => basename($path),
									]))) {
			Search_NFS::delete_nfs_indexes($object, $path, $path_type);
			$full_path = NFS::get_path($object, $path, $path_type);
			if (is_dir($full_path)) {
				File::rmdir($full_path);
			}
			else {
				File::delete($full_path);
			}

			Event::trigger('nfs.stat', $object, $path, $full_path, $path_type, 'delete');	
			/* 记录日志 */
			Log::add(strtr('[nfs] %user_name[%user_id] 删除了 %path', [
					'%user_name' => $use->name,
					'%user_id' => $user->id,
					'%path' => $full_path,
					]),'journal');

			$form_token = Input::form('form_token');
			$uniqid = $_SESSION[$form_token]['uniqid'];
			JS::refresh($uniqid);
		}
	}

	function index_rename_click($oname='', $id=0, $path_type=NULL) {

		$user = L('ME');
		$object = O($oname, $id);
		$path = NFS::fix_path(rawurldecode(Input::form('path')));
		$name = NFS::fix_path(rawurldecode(Input::form('old_name')));
		if (!NFS::user_access($user, '修改文件', $object, ['path'=>$path.'/'.$name, 'type'=>$path_type])) return;
		$form_token = Input::form('form_token');
		JS::dialog(V('nfs:nfs/rename', ['path'=>$path, 'name'=>$name, 'form_token'=>$form_token]));
	}

	function index_rename_form_submit($oname='', $id=0, $path_type=NULL) {
		JS::close_dialog();
		$form = Form::filter(Input::form());
		$object = O($oname,$id);
		$user = L('ME');
		$base_path = NFS::fix_path(Input::form('path'));
		$old_name = NFS::fix_path(rawurldecode(Input::form('old_name')));
		$name = NFS::fix_name(Input::form('name'), TRUE);
		$name = NFS::fix_path($name);

		if (!NFS::user_access($user, '修改文件', $object, ['path'=>$base_path.'/'.$old_name, 'type'=>$path_type])) return;

		$old_path = NFS::get_path($object, $base_path . '/' . $old_name, $path_type);
		$full_path = NFS::get_path($object, $base_path . '/' . $name, $path_type);
		$path = NFS::get_path($object, $base_path . '/' . $name, $path_type, FALSE);
		if ($old_name != $name && file_exists($full_path)) {
			if (is_dir($full_path)) {
				$form->set_error('name', I18N::T('nfs', '该目录下已存在%name目录', ['%name'=>$name]));
			}
			else {
				$form->set_error('name', I18N::T('nfs', '该目录下已存在%name文件', ['%name'=>$name]));
			}
			$form_token = $form['form_token'];
			JS::dialog(V('nfs:nfs/rename', ['path'=>$base_path, 'name'=>$old_name, 'form_token'=>$form_token, 'form'=>$form]));
		}
		else {
			File::check_path($full_path);
			if (@rename($old_path, $full_path)) {
				Search_NFS::update_nfs_indexes($object, $path, $path_type);
				Event::trigger('nfs.stat', $object, $path, $full_path, $path_type, 'rename');
				Log::add(strtr('[nfs] %user_name[%user_id] 重命名 %old_path 到 %path', [
						'%user_name' => $use->name,
						'%user_id' => $user->id,
						'%old_path' => $old_path,
						'%path' => $full_path,
						]),'journal');
			 }
			else {
				JS::alert(I18N::T('nfs', '重命名失败！'));
			}
			$form_token = Input::form('form_token');
			$uniqid = $_SESSION[$form_token]['uniqid'];
			JS::refresh($uniqid);
		}
	}

	function index_new_folder_click($oname, $id=0, $path_type) {
		$user = L('ME');
		$object = o($oname,$id);

		$path = NFS::fix_path(Input::form('path'));
		/*
			BUG#265 (Cheng.liu@2010.12.19)
			$path增加'/foo',适应仪器中没有最原始路径
		*/
		if (!NFS::user_access($user, '创建目录', $object, ['path'=>$path.'/foo', 'type'=>$path_type])) return;

		$form_token = Input::form('form_token');
		JS::dialog(V('nfs:nfs/create', ['path'=>$path, 'form_token'=>$form_token]));
	}

	function index_create_form_submit($oname, $id=0, $path_type) {
		JS::close_dialog();

		$user = L('ME');
		$object = O($oname,$id);

		$base_path = NFS::fix_path(Input::form('path'));

		if (!NFS::user_access($user, '创建目录', $object, ['path'=>$base_path.'/foo', 'type'=>$path_type])) return;

		$name = NFS::fix_name(Input::form('name'));
		$name = NFS::fix_path($name);

		$full_path = NFS::get_path($object, $base_path.'/'.$name, $path_type);

		if (File::check_path($full_path) && @mkdir($full_path)) {
						
			/* 记录日志 */
			Log::add(strtr('[nfs] %user_name[%user_id] 新建文件夹 %path', [
					'%user_name' => $use->name,
					'%user_id' => $user->id,
					'%path' => $full_path,
					]),'journal');

			$form_token = Input::form('form_token');
			$uniqid = $_SESSION[$form_token]['uniqid'];
			JS::refresh($uniqid);
		}
		/*
			NO. BUG#213 (Cheng.Liu@2010.12.02)
			增加创建不成功文件夹的提示
		*/
		elseif (file_exists($full_path)) {
			JS::alert(I18N::T('nfs', '您创建的目录已存在！'));
		}
		/*
		 * BUG #1214::NFS修改文件明为特别长的名字，保存失败，但是没有提示。
		 * 没有对长度进行判断，但给出了目录创建失败的提示。（kai.wu@2011.10.13）
		 */
		else {
			JS::alert(I18N::T('nfs', '目录创建失败！'));
		}
	}

	function index_search_nfs_click() {

		$form = Input::form();
		$path = $form['path'];
		$path_type = $form['path_type'];
		$form_token = $form['form_token'];
		JS::dialog(V('nfs:nfs/search', ['path'=>$path,'path_type'=>$path_type, 'form_token'=>$form_token]),
			['title'=>T('文件搜索'),
			]);
	}

	function index_reset_search_click($oname, $id=0) {
		$object = O($oname,$id);
		$path = Input::form('path');
		$path_type = Input::form('path_type');
		$form_token = Input::form('form_token');

		$form = $_SESSION[$form_token];
		$uniqid = $form['uniqid'];
		$opt = [];

		if (Input::form('reset_field') == 'file_name') {
			unset($form['file_name']);
		}
		
		if (Input::form('reset_field') == 'nfs_date') {
			unset($form['dtstart_check']);
			unset($form['dtend_check']);
			unset($form['dtstart']);
			unset($form['dtend']);
		}

		if ($form['file_name']) {
			$opt['name'] = $form['file_name'];
		}

        if ($form['dtstart_check'] == 'on') {
            $opt['mstart'] = strtotime('midnight', $form['dtstart']);
        }

        if ($form['dtend_check'] == 'on') {
            $opt['mend'] = strtotime('midnight', $form['dtend'] + 86400) - 1;
        }
		if (count($opt)) {
			$opt['path_prefix'] =  NFS::get_path_prefix($object, $path, $path_type, TRUE);
			if ($path) {
				$realpath = realpath(NFS::get_path($object, $path, $path_type, TRUE));
				$prefix = Config::get('nfs.root').$opt['path_prefix'];
				$count = strpos($realpath,$prefix);
				$opt['path'] = substr_replace($realpath,"",$count,strlen($prefix));
			}
			$files = Search_NFS::search($opt);

			Output::$AJAX[$uniqid] = [
				'data' => (string) V('nfs:nfs/search_result',[
					'object'=> $object,
					'files' => $files,
					'form_token' => $form_token,
					'form' => $form,
					'path' => $path,
					'path_type' => $path_type,
					]),
			];	
		}
		else {
			JS::refresh();
		}
	}
	function index_search_nfs_submit($oname, $id=0) {
		
		$form = Input::form();
		$object = O($oname,$id);
		$form_token = $form['form_token'];
		$uniqid = $_SESSION[$form_token]['uniqid'];

		$path = $form['path'];
		$path_type = $form['path_type'];
		$opt = [];

        if ($form['file_name']) {
            $opt['name'] = $form['file_name'];
        }

        if ($form['dtstart_check'] == 'on') {
            $opt['mstart'] = strtotime('midnight', $form['dtstart']);
        }

        if ($form['dtend_check'] == 'on') {
            $opt['mend'] = strtotime('midnight', $form['dtend'] + 86400) - 1;
        }

        if (count($opt)) {
			$opt['path_prefix'] =  NFS::get_path_prefix($object, $path, $path_type, TRUE);
			if ($path) {
				$realpath = realpath(NFS::get_path($object, $path, $path_type, TRUE));
				$prefix = Config::get('nfs.root').$opt['path_prefix'];
				$count = strpos($realpath,$prefix);
				$opt['path'] = substr_replace($realpath,"",$count,strlen($prefix));
			}
			$files = Search_NFS::search($opt);

			Output::$AJAX[$uniqid] = [
				'data' => (string) V('nfs:nfs/search_result',[
					'object'=> $object,
					'files' => $files,
					'form_token' => $form_token,
					'form' => $form,
					'path' => $path,
					'path_type' => $path_type,
					]),
			];
        }
        else {
			JS::alert(I18N::T('nfs', '请填写搜索条件!'));
			return;        	
        }
		JS::close_dialog();
	}

	function index_nfs_direction_refresh() {

		$form = Input::form();
		$object = O($form['oname'],$form['id']);
		$path_type = $form['path_type'];
		$form_token = $form['form_token'];
		$uniqid = $form['uniqid'];
        //不同浏览器refresh后可能出现传递path不同的情况,rawurldecode进行修正
		$path = rawurldecode($form['path']);

		$full_path = NFS::get_path($object, $path, $path_type, TRUE);
		$files = NFS::file_list($full_path,$path);
		$new_files = Event::trigger('nfs.filter.files', $object, $path, $full_path, $path_type);
		$files = $new_files ?: $files;

        Output::$AJAX['#'.$uniqid] = [
			'data'=>(string) V('nfs:nfs/index',[
				'object'=>$object, 'path_type'=>$path_type,'path'=>$path,'form_token'=>$form_token,'files'=>$files,'refresh'=>FALSE]),
			'mode'=>'replace'
        ];
	}

	function index_show_tips_refresh() {

		$me = L('ME');
		$form = Input::form();
		$tip_path = 'tips.nfs_share.private';
		$sub_path = $form['sub_path'];
		$view = Event::trigger('nfs_sub_path_tips', $sub_path);
		Output::$AJAX['.tip'] = [
			'data'=>(string)$view,
			'mode'=>'replace'
			];
	}
}
