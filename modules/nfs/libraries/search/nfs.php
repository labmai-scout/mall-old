<?php
class Search_NFS {

	static protected $model_name = 'nfs';

    static function search($opt) {
        $sphinx = Database::factory('@sphinx');
        $SQL = 'SELECT * FROM `' . self::get_index_name() . '`';

        $where = [];

        if ($opt['mstart']) {
            $mdtstart = (int) $sphinx->escape($opt['mstart']);
            $where[] = "mtime>=$mdtstart";
        }

        if ($opt['mend']) {
            $mdtend = (int) $sphinx->escape($opt['mend']);
            $where[] = "mtime<=$mdtend";
        }

        if ($opt['path']) {
            $path = $sphinx->escape($opt['path']);
            $arr1[] = "@(spath) \"{$path}*\"";
        }

        if ($opt['name']) {
            $name = $sphinx->escape($opt['name']);
            $arr1[] = "@(spath) \"*{$name}*\"";
        }

        if ($opt['path_prefix']) {
            if (is_array($opt['path_prefix'])) {
                foreach ($opt['path_prefix'] as $key => $value) {
                    $path_prefix = $sphinx->escape($value);
                    $arr2[] = "@(spath_prefix) \"{$path_prefix}\"";
                }
            }
            else {
                $path_prefix = $sphinx->escape($opt['path_prefix']);
                $arr2[] = "@(spath_prefix) \"{$path_prefix}\"";
            }
            $pre_where2 = "(".implode('|', $arr2).")";
        }
        if (count($arr1)) {
            $pre_where1 = "(".implode('&', $arr1).")";
        }

        $pre_where = $pre_where1.' '.$pre_where2;
        if ($pre_where) {
           $where[] = "MATCH('".$pre_where."')";
        }
        if (count($where)) {
            $SQL .= ' WHERE '. implode(' AND ', $where);
        }
        return $sphinx->query($SQL)->rows();
    }

    static function empty_index() {
		$sphinx = Database::factory('@sphinx');
		$SQL = 'select * from `' . self::get_index_name() . '` limit 1000';
	    do {
			$results = $sphinx->query($SQL);
			$ids = [];
			if ($results) while ($row = $results->row()) {
				$ids[] = $row->id;
			}
			$DEL_SQL = 'DELETE FROM `' . self::get_index_name() . '` WHERE id IN (' . join(',', $ids) . ')';
			$sphinx->query($DEL_SQL);		
		}
		while (is_object($results) && $results->count());
    }

    static function delete_nfs_indexes($object, $path, $path_type) {
        if (!$object->id) return;

        $root = Config::get('nfs.root');
        $path_prefix = NFS::get_path_prefix($object, $path, $path_type);
        $full_path = $root.$path_prefix.$path;
        if (is_file($full_path)) {
            $file = fopen($full_path, "r");
            $stat = fstat($file);
            $sphinx = Database::factory('@sphinx');
            $sphinx->query('DELETE FROM `' . self::get_index_name() . '` WHERE ID=%d', $stat['ino']);
        }
        else {
            $full_path = NFS::get_path($object, $path, $path_type, TRUE);
            $files = NFS::file_list($full_path,$path);
            foreach ((array)$files as $file) {
                self::delete_nfs_indexes($object, $file['path'], $path_type);                
            }  
        }
    }
    
    static function update_nfs_indexes($object, $path, $path_type, $check_link_path = TRUE) {

        $root = Config::get('nfs.root');
        
        $old_path = $path;  
        $path_prefix = NFS::get_path_prefix($object, $path, $path_type);
        $full_path = $root.$path_prefix.$path;
        $realpath = realpath($full_path);

        if ($object->id && $realpath != $full_path) {
            $real_path_prefix = NFS::get_path_prefix($object, $path, $path_type, TRUE);
            $count = strpos($realpath,$root.$real_path_prefix);
            $path = substr_replace($realpath,"",$count,strlen($root.$real_path_prefix));
            $path_prefix = $real_path_prefix;
        }

        if ($realpath && $path_prefix) {
            if (($realpath == $full_path || $realpath.'/' == $full_path) || $check_link_path) {
                if (is_file($realpath)) {
                    $sphinx = Database::factory('@sphinx');
                    $file = NFS::file_info($realpath);
                    $stat = fstat(fopen($full_path, "r"));
                    $k[] = 'id';
                    $v[] = $stat['ino'];

                    if ($file['mtime']) {
                        $k[] = 'mtime';
                        $v[] = $sphinx->quote($file['mtime']);
                    }

                    if ($file['ctime']) {
                        $k[] = 'ctime';
                        $v[] = $sphinx->quote($file['ctime']);
                    }

                    if ($path) {
                        $k[] = 'path';
                        $v[] = $sphinx->quote($path);
                        
                        $k[] = 'spath';
                        $v[] = $sphinx->quote($path);
                    }

                    if ($path_prefix) {
                        $k[] = 'path_prefix';
                        $v[] = $sphinx->quote($path_prefix);

                        $k[] = 'spath_prefix';
                        $v[] = $sphinx->quote($path_prefix);
                    }
                    $SQL = 'REPLACE INTO `' . self::get_index_name() . '` ('.implode(',', $k).') VALUES ('. implode(',', $v) .')';
                    $sphinx->query($SQL);

                }
                else {
                    $files = NFS::file_list($full_path,$old_path);
                    foreach ((array)$files as $file) {
                        self::update_nfs_indexes($object, $file['path'], $path_type);                
                    } 
                }
            }
        }
    }

    static function get_index_name() {
        if (!static::$model_name) {
            throw new Exception;
        }
        $index_name = SITE_ID . '_' . LAB_ID . '_' . static::$model_name;
        $index_name = strtr($index_name, '-', '_');
        return $index_name;
    }
}
