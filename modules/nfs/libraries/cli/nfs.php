<?php

class CLI_Nfs {
	static function open() {
		$labs = Q('lab');
		$users = Q('user');

		foreach ($labs as $lab) {
			if ($lab->nfs_size == 0) {	/* 开通 尚未开通的实验室 */
				NFS_Share::setup_share($lab);
			}
		}

		foreach ($users as $user) {
			NFS_Share::setup_share($user); /* 开通/更新 所有成员 */
		}
	}

	static function clean_empty_dir() {
		$to_clean_object_array = [
                    'award',
                    'publication',
                    'patent',
                    'equipment',
                    'eq_record',
                    'eq_sample',
                    'cal_component',
                    'stock',
                    'tn_note'

                ];

		foreach($to_clean_object_array as $oname) {

		    $onames = Q($oname);
		    
		    foreach($onames as $o){
		        $a_path = NFS::get_path($o, '', 'attachments', TRUE);
		        if(self::empty_dir($a_path)) {
		            echo T("清除空目录 %path \n", ['%path'=>$a_path]);
		        } 
		    }
		}
	}

	//清空目录
	static private function empty_dir($dir=null) {
	    
	    //假设当前目录下都是隐藏文件
	    $all_hidden_file = TRUE;
	    $file_count = 0;
	    if($handle = @opendir($dir)) {
	        while(false != ($file = readdir($handle))) {
	            $file_count ++;
	            if($file != '.' && $file != '..' && !preg_match('/^\.\w+/', $file)) {
	                $all_hidden_file = FALSE;
	                return FALSE;
	            }
	        }
	        
	        //如果文件数量和为2，也就说当前目录下只有.和..两个目录，即该目录为空
	        if ($file_count == 2 || $all_hidden_file) {
	            File::rmdir($dir);
	            return TRUE;
	        }
	    }
	    else {
	        if(is_dir($dir)) {
	            File::rmdir($dir);
	            return TRUE;
	        } 
	    }
	    return FALSE;
	}

	static function sphinx_update() {
		$root = Config::get('nfs.root');

		Search_NFS::empty_index();

		$path = '';
		$path_type = 'share';
		$start = 0;
		$num = 10;

		for(;;) {
			$users = Q('user')->limit($start,$num);
			if (count($users) == 0) break;
			$start += $num;
			foreach ($users as $user) {
				Search_NFS::update_nfs_indexes($user, $path, $path_type, FALSE);
			}
		}

		$start = 0;
		$num = 10;

		for(;;) {
			$labs = Q('lab')->limit($start,$num);
			if (count($labs) == 0) break;
			$start += $num;
			foreach ($labs as $lab) {
				Search_NFS::update_nfs_indexes($lab, $path, $path_type, FALSE);
			}
		}

		$object = null;
		Search_NFS::update_nfs_indexes($object, $path, $path_type, FALSE);

		$path = '';
		$path_type = 'attachments';
		$object_arr = ['award', 'eq_record', 'eq_sample', 'equipment', 'publication', 'stock', 'cal_component'];
		foreach ($object_arr as $key => $name) {
			$start = 0;
			$num = 10;

			for(;;) {
				$objects = Q("$name")->limit($start,$num);
				if (count($objects) == 0) break;
				$start += $num;
				foreach ($objects as $object) {
					Search_NFS::update_nfs_indexes($object, $path, $path_type, FALSE);
					echo '.';
				}
			}
		}
	}
}