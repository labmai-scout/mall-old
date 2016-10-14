<?php

class Presentable_Model extends ORM_Model {

	protected $view;
	protected $object_page = array(
		'view'=>'show/%object.%id'
		);
	protected $icon_page = 'icon/%object.%id.%size?_=%mtime';
	protected $icon_size = array(16, 32, 36, 48, 64, 128, 256);

	private $vars = array();

	/*
	 * url用于实现一些和对象有关的路径
	 * 最后的$op默认永远是view 希望只要涉及到查看对象的都更改$object_page的view键值
	 * 不要使用info, show之类自定义的object_page
	 */
	function url($arguments=NULL, $query=NULL, $fragment=NULL, $op='view'){
		if (is_array($arguments)) $arguments = implode('.', $arguments);

		$url = $this->object_page[$op];

		$this->vars['object'] = $this->name();
		$this->vars['arguments'] = $arguments;

		$url = preg_replace_callback('/\[([^\[\]]+)\]/',
			array($this, '_url_ignore'), $url);
		
		if (preg_match_all('/%([a-z]+)/i', $url, $parts)) {
			foreach($parts[1] as $name) {
				$val = $this->vars[$name] ?: $this->get($name);
				if(NULL === $val) $val = $this->$name;
				$url = preg_replace('/%'.preg_quote($name).'/', $val, $url);
			}
		}

		return URI::url($url, $query, $fragment);
	}

	private function _url_ignore($matches) {
		$text = $matches[1];

		if (preg_match_all('/%([a-z]+)/i', $text, $parts)) {
			foreach($parts[1] as $name) {
				$val = $this->vars[$name];
				if(NULL === $val) $val = $this->$name;
				if(!$val && !is_numeric($val)) return ''; //返回清空值
				$text = preg_replace('/%'.preg_quote($name).'/', $val, $text);
			}
		}
		return $text;
	}

	function icon_url($size=256) {
		$size = $this->normalize_icon_size($size);
		$icon_file = $this->icon_file($size);
		return Config::get('system.base_url').Cache::cache_file($icon_file).'?_='.$this->mtime;
	}

	function icon($size=256, $extra=NULL){
		if ($extra) $extra = ' '.$extra;
		$icon_class = 'icon icon_'.$this->name();
		if (preg_match('/\bclass=\"(.+?)\"/', $extra)) {
			$extra = preg_replace('/\bclass=\"(.+?)\"/', 'class="$1 '.$icon_class.'"', $extra);
		}
		else {
			$extra .= ' class="'.$icon_class.'"';
		}

		if ($size != 256) {
			$size = $size ?: '32';
			$extra .= ' width="'.$size.'px" height="'.$size.'px"';
		}

		$return_value = '<img'.$extra.' src="'.$this->icon_url($size).'" />';
		return $return_value;
	}

	function icon_file($size=256, array $fields=array('id')) {
		foreach($fields as $field){
			$file = Core::file_exists(PRIVATE_BASE.'icons/'.$this->name().'/'.$size.'/'.$this->$field.'.png', '*');
			if($file) break;
		}

		if(!$file) $file = Core::file_exists(PRIVATE_BASE.'icons/'.$this->name().'/'.$size.'.png', '*');

		if (!$file) $file = Core::file_exists(PRIVATE_BASE.'icons/'.$size.'.png', '*');

		return $file;
	}

	function normalize_icon_size($size) {
		if(!in_array($size, $this->icon_size)){
			//如果不合适 选一个最接近的
			$nsize = 0;
			$csize = 16;
			foreach ($this->icon_size as $sz) {
				if ($size == $sz) {
					$nsize = $sz;
					break;
				}
				elseif (abs($size - $sz) < abs($size - $csize)){
					$csize = $sz;
				}
			}

			$size = $nsize ?: $csize;
		}

		return $size;
	}

	function show_icon($size=256, array $fields=array('id')){
		$size = $this->normalize_icon_size($size);
		$file = $this->icon_file($size, $fields);
		if ($file) {
			Image::show_file($file, 'png');
		}
	}

	private function _save_icon($image, $size, $base = '') {
		$base = $base ?: SITE_PATH.PRIVATE_BASE.'icons/'.$this->name().'/';

		$image->resize($size, $size, FALSE);
		// $image->crop_center($size, $size);
		$path = $base.$size.'/'.$this->id.'.png';

		File::check_path($path);
		$image->save('png', $path);
		Cache::cache_file($path, TRUE);

		return $path;
	}

	// 保存头像
	function save_icon($image){

		// 拼接 base 图像保存路径
		$base = SITE_PATH.PRIVATE_BASE.'icons/'.$this->name().'/';

		// 修改原始图像为特定尺寸
		$image->background_color('#ffffff');
		$image->resize(256, 256, FALSE);
		$image->crop_center(256, 256);

		// 保存图像
		$path = $base.'256/'.$this->id.'.png';
		File::check_path($path);
		$image->save('png', $path);
		// ...并做缓存
		Cache::cache_file($path, TRUE);

		// 保存缩略图
		$icon_size = $this->icon_size;
		rsort($icon_size);	// 反向排序
		foreach ($icon_size as $size) {
			if ($size == 256) continue;
			$this->_save_icon($image, $size, $base);
		}

		$this->touch()->save();

		return $this;
	}

	function copy_icon_from($object) {
		if (!$this->id || !$object->id) {
			return;
		}

		$source_base = SITE_PATH.PRIVATE_BASE.'icons/'.$object->name().'/';
		$dest_base = SITE_PATH.PRIVATE_BASE.'icons/'.$this->name().'/';

		foreach ($this->icon_size as $size) {
			$source = $source_base. $size . '/'.$object->id.'.png';
			// error_log($source);

			$dest = $dest_base. $size . '/'.$this->id.'.png';
			// error_log($dest);

			@unlink($dest);
			if (File::exists($source)) {
				@copy($source, $dest);
				Cache::cache_file($dest, TRUE);
			}
		}
	}

	function delete() {
		$return = parent::delete();
		if ($return) {
			$this->_delete_icon();
		}
		return $return;
	}

	private function _delete_icon() {
		$base = SITE_PATH.PRIVATE_BASE.'icons/'.$this->name().'/';
		foreach ($this->icon_size as $size) {
			$path = $base.$size.'/'.$this->id.'.png';
			@unlink($path);
			Cache::remove_cache_file($path);
		}
	}

	function delete_icon() {
		$this->_delete_icon();
		$this->touch()->save();
	}

	function render($view=NULL, $return = FALSE, $vars=array()){
		if(!$view) $view = V('objects/'.$this->name());
		$view = ($view instanceof View)? $view : V($view);
		$view->set($vars);
		$view->object = $this;

		if ($return) return (string) $view;

		echo $view;

		return $this;
	}

	function &links($mode=NULL, $button=FALSE) {
		return array();
	}

}

